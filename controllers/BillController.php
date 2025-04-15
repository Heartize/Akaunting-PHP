<?php
/**
 * Controlador para la gestión de facturas de compra
 */
class BillController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Lista de facturas de compra
     */
    public function index() {
        // Parámetros de paginación
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = $GLOBALS['config']['default_pagination_limit'];
        $offset = ($page - 1) * $limit;
        
        // Filtros
        $filters = [];
        
        if (isset($_GET['vendor_id']) && !empty($_GET['vendor_id'])) {
            $filters['vendor_id'] = $_GET['vendor_id'];
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        // Obtener facturas
        try {
            $query = "SELECT b.*, v.name as vendor_name
                      FROM bills b
                      JOIN vendors v ON b.vendor_id = v.id";
            
            $params = [];
            $conditions = [];
            
            // Aplicar filtros
            if (!empty($filters)) {
                if (isset($filters['vendor_id'])) {
                    $conditions[] = "b.vendor_id = ?";
                    $params[] = $filters['vendor_id'];
                }
                
                if (isset($filters['status'])) {
                    $conditions[] = "b.status = ?";
                    $params[] = $filters['status'];
                }
                
                if (isset($filters['date_from'])) {
                    $conditions[] = "b.bill_date >= ?";
                    $params[] = $filters['date_from'];
                }
                
                if (isset($filters['date_to'])) {
                    $conditions[] = "b.bill_date <= ?";
                    $params[] = $filters['date_to'];
                }
                
                if (isset($filters['search'])) {
                    $conditions[] = "(b.bill_number LIKE ? OR v.name LIKE ?)";
                    $searchParam = '%' . $filters['search'] . '%';
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                }
                
                if (!empty($conditions)) {
                    $query .= " WHERE " . implode(" AND ", $conditions);
                }
            }
            
            $query .= " ORDER BY b.bill_date DESC";
            $query .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total para paginación
            $countQuery = "SELECT COUNT(*) FROM bills b JOIN vendors v ON b.vendor_id = v.id";
            
            if (!empty($conditions)) {
                $countQuery .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $stmt = $this->db->prepare($countQuery);
            $stmt->execute(array_slice($params, 0, -2)); // Elimina parámetros de LIMIT
            $total = $stmt->fetchColumn();
            
            // Calcular páginas
            $totalPages = ceil($total / $limit);
            
            // Obtener proveedores para el filtro
            $stmt = $this->db->query("SELECT id, name FROM vendors ORDER BY name");
            $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cargar vista
            $pageTitle = 'Facturas de Compra';
            $includeSearch = true;
            $actionButton = 'Nueva Factura';
            $actionButtonUrl = url('/index.php?page=bills&action=create');
            
            $breadcrumbs = [
                'Facturas de Compra' => null
            ];
            
            include __DIR__ . '/../views/bills/index.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al cargar facturas de compra: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Formulario para crear una nueva factura de compra
     */
    public function create() {
        try {
            // Obtener proveedores
            $stmt = $this->db->query("SELECT id, name FROM vendors ORDER BY name");
            $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener productos
            $stmt = $this->db->query("SELECT id, code, name, purchase_price, tax_rate FROM products WHERE enabled = 1 ORDER BY name");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generar número de factura
            $stmt = $this->db->query("SELECT bill_number FROM bills WHERE bill_number LIKE 'COMP-%' ORDER BY id DESC LIMIT 1");
            $lastBill = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastBill) {
                $parts = explode('-', $lastBill['bill_number']);
                $lastNumber = (int)end($parts);
                $nextNumber = $lastNumber + 1;
                $nextBillNumber = 'COMP-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            } else {
                $nextBillNumber = 'COMP-0001';
            }
            
            // Obtener configuración para impuestos
            $stmt = $this->db->query("SELECT tax_rate FROM settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            $defaultTaxRate = $settings ? $settings['tax_rate'] : 21.00;
            
            // Obtener campos personalizados
            $customFields = getCustomFields('bills');
            
            // Cargar vista
            $pageTitle = 'Nueva Factura de Compra';
            $breadcrumbs = [
                'Facturas de Compra' => url('/index.php?page=bills'),
                'Nueva Factura' => null
            ];
            
            include __DIR__ . '/../views/bills/form.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=bills');
        }
    }
    
    /**
     * Guardar una nueva factura de compra
     */
    public function store() {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateBill($_POST);
        
        if (empty($errors)) {
            try {
                // Iniciar transacción
                $this->db->beginTransaction();
                
                // Insertar factura
                $query = "INSERT INTO bills (
                            bill_number, vendor_id, status, bill_date, 
                            due_date, subtotal, tax_total, total, notes
                          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    $_POST['bill_number'],
                    $_POST['vendor_id'],
                    $_POST['status'],
                    $_POST['bill_date'],
                    $_POST['due_date'],
                    $_POST['subtotal'],
                    $_POST['tax_total'],
                    $_POST['total'],
                    $_POST['notes'] ?? null
                ]);
                
                $billId = $this->db->lastInsertId();
                
                // Insertar elementos de la factura
                if (isset($_POST['product']) && is_array($_POST['product'])) {
                    $itemsCount = count($_POST['product']);
                    
                    $query = "INSERT INTO bill_items (
                                bill_id, product_id, description, quantity, 
                                price, tax_rate, tax_amount, total
                              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $this->db->prepare($query);
                    
                    for ($i = 0; $i < $itemsCount; $i++) {
                        if (empty($_POST['description'][$i])) continue;
                        
                        $stmt->execute([
                            $billId,
                            !empty($_POST['product'][$i]) ? $_POST['product'][$i] : null,
                            $_POST['description'][$i],
                            $_POST['quantity'][$i],
                            $_POST['price'][$i],
                            $_POST['tax_rate'][$i],
                            $_POST['tax_amount'][$i],
                            $_POST['item_total'][$i]
                        ]);
                    }
                }
                
                // Guardar campos personalizados
                if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
                    foreach ($_POST['custom_fields'] as $fieldId => $value) {
                        if (empty($value)) continue;
                        
                        $query = "INSERT INTO custom_field_values (field_id, model_id, module, value)
                                  VALUES (?, ?, 'bills', ?)";
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$fieldId, $billId, $value]);
                    }
                }
                
                // Si la factura es "paid", registrar la transacción
                if ($_POST['status'] === 'paid') {
                    $query = "INSERT INTO transactions (
                                type, account_id, bill_id, amount, 
                                date, description, reference, 
                                contact_id, contact_type
                              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    // Obtener el ID de la cuenta principal
                    $stmt = $this->db->query("SELECT id FROM accounts ORDER BY id LIMIT 1");
                    $account = $stmt->fetch(PDO::FETCH_ASSOC);
                    $accountId = $account ? $account['id'] : 1;
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        'expense',
                        $accountId,
                        $billId,
                        $_POST['total'],
                        $_POST['bill_date'],
                        'Pago factura de compra ' . $_POST['bill_number'],
                        $_POST['bill_number'],
                        $_POST['vendor_id'],
                        'vendor'
                    ]);
                    
                    // Actualizar saldo de la cuenta
                    $query = "UPDATE accounts 
                              SET current_balance = current_balance - ?,
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE id = ?";
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$_POST['total'], $accountId]);
                }
                
                // Si hay productos, actualizar inventario
                if (isset($_POST['product']) && is_array($_POST['product'])) {
                    $itemsCount = count($_POST['product']);
                    
                    for ($i = 0; $i < $itemsCount; $i++) {
                        if (empty($_POST['product'][$i])) continue;
                        
                        $query = "UPDATE products 
                                  SET stock = stock + ?,
                                      updated_at = CURRENT_TIMESTAMP
                                  WHERE id = ?";
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$_POST['quantity'][$i], $_POST['product'][$i]]);
                    }
                }
                
                // Confirmar transacción
                $this->db->commit();
                
                setFlashMessage('success', 'Factura de compra creada correctamente');
                redirect('/index.php?page=bills');
                
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $this->db->rollBack();
                
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=bills&action=create');
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=bills&action=create');
        }
    }
    
    /**
     * Formulario para editar una factura de compra
     */
    public function edit($id) {
        try {
            // Obtener la factura
            $query = "SELECT * FROM bills WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $bill = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$bill) {
                setFlashMessage('error', 'Factura de compra no encontrada');
                redirect('/index.php?page=bills');
            }
            
            // Obtener elementos de la factura
            $query = "SELECT * FROM bill_items WHERE bill_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $billItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener proveedores
            $stmt = $this->db->query("SELECT id, name FROM vendors ORDER BY name");
            $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener productos
            $stmt = $this->db->query("SELECT id, code, name, purchase_price, tax_rate FROM products WHERE enabled = 1 ORDER BY name");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener campos personalizados
            $customFields = getCustomFields('bills');
            $customFieldValues = getCustomFieldValues('bills', $id);
            
            // Cargar vista
            $pageTitle = 'Editar Factura de Compra';
            $breadcrumbs = [
                'Facturas de Compra' => url('/index.php?page=bills'),
                'Editar Factura' => null
            ];
            
            include __DIR__ . '/../views/bills/form.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=bills');
        }
    }
    
    /**
     * Actualizar una factura de compra existente
     */
    public function update($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateBill($_POST);
        
        if (empty($errors)) {
            try {
                // Iniciar transacción
                $this->db->beginTransaction();
                
                // Obtener datos actuales de la factura para comparar
                $query = "SELECT * FROM bills WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$id]);
                $oldBill = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Actualizar factura
                $query = "UPDATE bills 
                          SET bill_number = ?, vendor_id = ?, status = ?, 
                              bill_date = ?, due_date = ?, 
                              subtotal = ?, tax_total = ?, total = ?, 
                              notes = ?, updated_at = CURRENT_TIMESTAMP
                          WHERE id = ?";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    $_POST['bill_number'],
                    $_POST['vendor_id'],
                    $_POST['status'],
                    $_POST['bill_date'],
                    $_POST['due_date'],
                    $_POST['subtotal'],
                    $_POST['tax_total'],
                    $_POST['total'],
                    $_POST['notes'] ?? null,
                    $id
                ]);
                
                // Obtener elementos actuales para contabilizar cambios en inventario
                $query = "SELECT product_id, quantity FROM bill_items WHERE bill_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$id]);
                $currentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Crear un mapa de productos y cantidades actuales
                $productQuantityMap = [];
                foreach ($currentItems as $item) {
                    if ($item['product_id']) {
                        $productQuantityMap[$item['product_id']] = $item['quantity'];
                    }
                }
                
                // Eliminar elementos existentes
                $query = "DELETE FROM bill_items WHERE bill_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$id]);
                
                // Insertar elementos actualizados
                if (isset($_POST['product']) && is_array($_POST['product'])) {
                    $itemsCount = count($_POST['product']);
                    
                    $query = "INSERT INTO bill_items (
                                bill_id, product_id, description, quantity, 
                                price, tax_rate, tax_amount, total
                              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $this->db->prepare($query);
                    
                    // Crear mapa de productos y cantidades nuevas
                    $newProductQuantityMap = [];
                    
                    for ($i = 0; $i < $itemsCount; $i++) {
                        if (empty($_POST['description'][$i])) continue;
                        
                        $productId = !empty($_POST['product'][$i]) ? $_POST['product'][$i] : null;
                        $quantity = $_POST['quantity'][$i];
                        
                        $stmt->execute([
                            $id,
                            $productId,
                            $_POST['description'][$i],
                            $quantity,
                            $_POST['price'][$i],
                            $_POST['tax_rate'][$i],
                            $_POST['tax_amount'][$i],
                            $_POST['item_total'][$i]
                        ]);
                        
                        // Guardar para actualización de inventario
                        if ($productId) {
                            if (isset($newProductQuantityMap[$productId])) {
                                $newProductQuantityMap[$productId] += $quantity;
                            } else {
                                $newProductQuantityMap[$productId] = $quantity;
                            }
                        }
                    }
                    
                    // Actualizar inventario
                    // Para productos que ya no están o cambiaron cantidad
                    foreach ($productQuantityMap as $productId => $oldQuantity) {
                        $newQuantity = $newProductQuantityMap[$productId] ?? 0;
                        $quantityDiff = $newQuantity - $oldQuantity;
                        
                        if ($quantityDiff != 0) {
                            $query = "UPDATE products 
                                      SET stock = stock + ?,
                                          updated_at = CURRENT_TIMESTAMP
                                      WHERE id = ?";
                            
                            $stmt = $this->db->prepare($query);
                            $stmt->execute([$quantityDiff, $productId]);
                        }
                    }
                    
                    // Para productos nuevos
                    foreach ($newProductQuantityMap as $productId => $quantity) {
                        if (!isset($productQuantityMap[$productId])) {
                            $query = "UPDATE products 
                                      SET stock = stock + ?,
                                          updated_at = CURRENT_TIMESTAMP
                                      WHERE id = ?";
                            
                            $stmt = $this->db->prepare($query);
                            $stmt->execute([$quantity, $productId]);
                        }
                    }
                }
                
                // Actualizar campos personalizados
                // Primero eliminar valores existentes
                $query = "DELETE FROM custom_field_values WHERE model_id = ? AND module = 'bills'";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$id]);
                
                // Luego insertar nuevos valores
                if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
                    foreach ($_POST['custom_fields'] as $fieldId => $value) {
                        if (empty($value)) continue;
                        
                        $query = "INSERT INTO custom_field_values (field_id, model_id, module, value)
                                  VALUES (?, ?, 'bills', ?)";
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$fieldId, $id, $value]);
                    }
                }
                
                // Gestionar transacción asociada si cambia el estado
                if ($oldBill['status'] !== $_POST['status']) {
                    // Si antes era pagada y ahora no lo es
                    if ($oldBill['status'] === 'paid' && $_POST['status'] !== 'paid') {
                        // Eliminar transacción existente
                        $query = "DELETE FROM transactions WHERE bill_id = ?";
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$id]);
                        
                        // Actualizar saldo de la cuenta (revertir gasto)
                        $query = "UPDATE accounts 
                                  SET current_balance = current_balance + ?,
                                      updated_at = CURRENT_TIMESTAMP
                                  WHERE id = (
                                      SELECT account_id FROM transactions
                                      WHERE bill_id = ? LIMIT 1
                                  )";
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$oldBill['total'], $id]);
                    }
                    // Si ahora es pagada y antes no lo era
                    elseif ($oldBill['status'] !== 'paid' && $_POST['status'] === 'paid') {
                        // Registrar transacción
                        $query = "INSERT INTO transactions (
                                    type, account_id, bill_id, amount, 
                                    date, description, reference, 
                                    contact_id, contact_type
                                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        // Obtener el ID de la cuenta principal
                        $stmt = $this->db->query("SELECT id FROM accounts ORDER BY id LIMIT 1");
                        $account = $stmt->fetch(PDO::FETCH_ASSOC);
                        $accountId = $account ? $account['id'] : 1;
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([
                            'expense',
                            $accountId,
                            $id,
                            $_POST['total'],
                            $_POST['bill_date'],
                            'Pago factura de compra ' . $_POST['bill_number'],
                            $_POST['bill_number'],
                            $_POST['vendor_id'],
                            'vendor'
                        ]);
                        
                        // Actualizar saldo de la cuenta
                        $query = "UPDATE accounts 
                                  SET current_balance = current_balance - ?,
                                      updated_at = CURRENT_TIMESTAMP
                                  WHERE id = ?";
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$_POST['total'], $accountId]);
                    }
                    // Si el total cambió y sigue siendo pagada
                    elseif ($oldBill['status'] === 'paid' && $_POST['status'] === 'paid' && $oldBill['total'] != $_POST['total']) {
                        // Actualizar monto de la transacción
                        $query = "UPDATE transactions 
                                  SET amount = ?,
                                      updated_at = CURRENT_TIMESTAMP
                                  WHERE bill_id = ?";
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$_POST['total'], $id]);
                        
                        // Ajustar saldo de la cuenta
                        $difference = $_POST['total'] - $oldBill['total'];
                        
                        $query = "UPDATE accounts 
                                  SET current_balance = current_balance - ?,
                                      updated_at = CURRENT_TIMESTAMP
                                  WHERE id = (
                                      SELECT account_id FROM transactions
                                      WHERE bill_id = ? LIMIT 1
                                  )";
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$difference, $id]);
                    }
                }
                
                // Confirmar transacción
                $this->db->commit();
                
                setFlashMessage('success', 'Factura de compra actualizada correctamente');
                redirect('/index.php?page=bills');
                
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $this->db->rollBack();
                
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=bills&action=edit&id=' . $id);
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=bills&action=edit&id=' . $id);
        }
    }
    
    /**
     * Ver detalles de una factura de compra
     */
    public function show($id) {
        try {
            // Obtener la factura
            $query = "SELECT b.*, v.name as vendor_name, v.phone as vendor_phone, v.email as vendor_email
                      FROM bills b
                      JOIN vendors v ON b.vendor_id = v.id
                      WHERE b.id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $bill = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$bill) {
                setFlashMessage('error', 'Factura de compra no encontrada');
                redirect('/index.php?page=bills');
            }
            
            // Obtener elementos de la factura
            $query = "SELECT bi.*, p.name as product_name
                      FROM bill_items bi
                      LEFT JOIN products p ON bi.product_id = p.id
                      WHERE bi.bill_id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $billItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener transacción relacionada (pago)
            $query = "SELECT t.*, a.name as account_name
                      FROM transactions t
                      JOIN accounts a ON t.account_id = a.id
                      WHERE t.bill_id = ?
                      ORDER BY t.date DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener campos personalizados
            $customFieldValues = getCustomFieldValues('bills', $id);
            
            // Cargar vista
            $pageTitle = 'Detalles de Factura de Compra';
            $breadcrumbs = [
                'Facturas de Compra' => url('/index.php?page=bills'),
                'Detalles' => null
            ];
            
            include __DIR__ . '/../views/bills/show.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=bills');
        }
    }
    
    /**
     * Cambiar estado de una factura de compra
     */
    public function changeStatus($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        $newStatus = $_POST['status'] ?? '';
        
        if (empty($newStatus)) {
            setFlashMessage('error', 'El estado es obligatorio');
            redirect('/index.php?page=bills&action=show&id=' . $id);
            return;
        }
        
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Obtener datos actuales de la factura
            $query = "SELECT * FROM bills WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $bill = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$bill) {
                setFlashMessage('error', 'Factura de compra no encontrada');
                redirect('/index.php?page=bills');
                return;
            }
            
            // Actualizar estado
            $query = "UPDATE bills SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$newStatus, $id]);
            
            // Gestionar transacción relacionada
            if ($bill['status'] !== $newStatus) {
                // Si antes era pagada y ahora no lo es
                if ($bill['status'] === 'paid' && $newStatus !== 'paid') {
                    // Eliminar transacción existente
                    $query = "DELETE FROM transactions WHERE bill_id = ?";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$id]);
                    
                    // Actualizar saldo de la cuenta (revertir gasto)
                    $query = "UPDATE accounts 
                              SET current_balance = current_balance + ?,
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE id = (
                                  SELECT account_id FROM transactions
                                  WHERE bill_id = ? LIMIT 1
                              )";
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$bill['total'], $id]);
                }
                // Si ahora es pagada y antes no lo era
                elseif ($bill['status'] !== 'paid' && $newStatus === 'paid') {
                    // Registrar transacción
                    $query = "INSERT INTO transactions (
                                type, account_id, bill_id, amount, 
                                date, description, reference, 
                                contact_id, contact_type
                              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    // Obtener el ID de la cuenta principal
                    $stmt = $this->db->query("SELECT id FROM accounts ORDER BY id LIMIT 1");
                    $account = $stmt->fetch(PDO::FETCH_ASSOC);
                    $accountId = $account ? $account['id'] : 1;
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        'expense',
                        $accountId,
                        $id,
                        $bill['total'],
                        $bill['bill_date'],
                        'Pago factura de compra ' . $bill['bill_number'],
                        $bill['bill_number'],
                        $bill['vendor_id'],
                        'vendor'
                    ]);
                    
                    // Actualizar saldo de la cuenta
                    $query = "UPDATE accounts 
                              SET current_balance = current_balance - ?,
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE id = ?";
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$bill['total'], $accountId]);
                }
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            setFlashMessage('success', 'Estado de la factura actualizado correctamente');
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            setFlashMessage('error', 'Error: ' . $e->getMessage());
        }
        
        redirect('/index.php?page=bills&action=show&id=' . $id);
    }
    
    /**
     * Eliminar una factura de compra
     */
    public function delete($id) {
        // Verificar token CSRF si se envía por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken($_POST['csrf_token'] ?? '');
        }
        
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Obtener datos de la factura
            $query = "SELECT * FROM bills WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $bill = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$bill) {
                setFlashMessage('error', 'Factura de compra no encontrada');
                redirect('/index.php?page=bills');
                return;
            }
            
            // Revertir inventario
            $query = "SELECT product_id, quantity FROM bill_items WHERE bill_id = ? AND product_id IS NOT NULL";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($items as $item) {
                // Restar del stock
                $query = "UPDATE products 
                          SET stock = stock - ?,
                              updated_at = CURRENT_TIMESTAMP
                          WHERE id = ?";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Si la factura estaba pagada, eliminar transacción y revertir saldo
            if ($bill['status'] === 'paid') {
                // Obtener cuenta de la transacción
                $query = "SELECT account_id FROM transactions WHERE bill_id = ? LIMIT 1";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$id]);
                $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($transaction) {
                    // Actualizar saldo de la cuenta (revertir gasto)
                    $query = "UPDATE accounts 
                              SET current_balance = current_balance + ?,
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE id = ?";
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$bill['total'], $transaction['account_id']]);
                    
                    // Eliminar transacción
                    $query = "DELETE FROM transactions WHERE bill_id = ?";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$id]);
                }
            }
            
            // Eliminar elementos de la factura
            $query = "DELETE FROM bill_items WHERE bill_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            // Eliminar valores de campos personalizados
            $query = "DELETE FROM custom_field_values WHERE model_id = ? AND module = 'bills'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            // Eliminar factura
            $query = "DELETE FROM bills WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            // Confirmar transacción
            $this->db->commit();
            
            setFlashMessage('success', 'Factura de compra eliminada correctamente');
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            setFlashMessage('error', 'Error: ' . $e->getMessage());
        }
        
        redirect('/index.php?page=bills');
    }
    
    /**
     * Validar datos de factura de compra
     */
    private function validateBill($data) {
        $errors = [];
        
        // Validar número de factura
        if (empty($data['bill_number'])) {
            $errors['bill_number'] = 'El número de factura es obligatorio';
        }
        
        // Validar proveedor
        if (empty($data['vendor_id'])) {
            $errors['vendor_id'] = 'El proveedor es obligatorio';
        }
        
        // Validar fecha de factura
        if (empty($data['bill_date'])) {
            $errors['bill_date'] = 'La fecha de factura es obligatoria';
        }
        
        // Validar fecha de vencimiento
        if (empty($data['due_date'])) {
            $errors['due_date'] = 'La fecha de vencimiento es obligatoria';
        }
        
        // Validar que haya al menos un elemento
        if (!isset($data['description']) || !is_array($data['description']) || empty($data['description'][0])) {
            $errors['items'] = 'Debe agregar al menos un elemento a la factura';
        }
        
        return $errors;
    }
}