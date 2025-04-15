<?php
/**
 * Controlador para la gestión de proveedores
 */
class VendorController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Lista de proveedores
     */
    public function index() {
        // Parámetros de paginación
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = $GLOBALS['config']['default_pagination_limit'];
        $offset = ($page - 1) * $limit;
        
        // Filtros
        $filters = [];
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        // Obtener proveedores
        try {
            $query = "SELECT * FROM vendors";
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters)) {
                if (isset($filters['search'])) {
                    $query .= " WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ? OR tax_number LIKE ?)";
                    $searchParam = '%' . $filters['search'] . '%';
                    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
                }
            }
            
            $query .= " ORDER BY name";
            $query .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total para paginación
            $countQuery = "SELECT COUNT(*) FROM vendors";
            
            // Aplicar filtros
            if (!empty($filters)) {
                if (isset($filters['search'])) {
                    $countQuery .= " WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ? OR tax_number LIKE ?)";
                    // Los parámetros para el conteo son los mismos que para la consulta principal, sin limit y offset
                    $countParams = array_slice($params, 0, -2);
                }
            } else {
                $countParams = [];
            }
            
            $stmt = $this->db->prepare($countQuery);
            $stmt->execute($countParams);
            $total = $stmt->fetchColumn();
            
            // Calcular páginas
            $totalPages = ceil($total / $limit);
            
            // Cargar vista
            $pageTitle = 'Proveedores';
            $includeSearch = true;
            $actionButton = 'Nuevo Proveedor';
            $actionButtonUrl = url('/index.php?page=vendors&action=create');
            
            $breadcrumbs = [
                'Proveedores' => null
            ];
            
            include __DIR__ . '/../views/vendors/index.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al cargar proveedores: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Formulario para crear un nuevo proveedor
     */
    public function create() {
        // Obtener monedas disponibles
        $currencies = [
            'EUR' => 'Euro (€)',
            'USD' => 'Dólar estadounidense ($)',
            'GBP' => 'Libra esterlina (£)',
            'MXN' => 'Peso mexicano (MXN)'
        ];
        
        // Obtener campos personalizados
        $customFields = getCustomFields('vendors');
        
        // Cargar vista
        $pageTitle = 'Nuevo Proveedor';
        $breadcrumbs = [
            'Proveedores' => url('/index.php?page=vendors'),
            'Nuevo Proveedor' => null
        ];
        
        include __DIR__ . '/../views/vendors/form.php';
    }
    
    /**
     * Guardar un nuevo proveedor
     */
    public function store() {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateVendor($_POST);
        
        if (empty($errors)) {
            try {
                // Iniciar transacción
                $this->db->beginTransaction();
                
                // Insertar proveedor
                $query = "INSERT INTO vendors (
                            name, email, phone, address, tax_number, 
                            website, credit_limit, currency, notes
                          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'] ?? null,
                    $_POST['phone'] ?? null,
                    $_POST['address'] ?? null,
                    $_POST['tax_number'] ?? null,
                    $_POST['website'] ?? null,
                    $_POST['credit_limit'] ?? 0,
                    $_POST['currency'] ?? 'EUR',
                    $_POST['notes'] ?? null
                ]);
                
                $vendorId = $this->db->lastInsertId();
                
                // Guardar campos personalizados
                if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
                    foreach ($_POST['custom_fields'] as $fieldId => $value) {
                        if (empty($value)) continue;
                        
                        $query = "INSERT INTO custom_field_values (field_id, model_id, module, value)
                                  VALUES (?, ?, 'vendors', ?)";
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$fieldId, $vendorId, $value]);
                    }
                }
                
                // Confirmar transacción
                $this->db->commit();
                
                setFlashMessage('success', 'Proveedor creado correctamente');
                redirect('/index.php?page=vendors');
                
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $this->db->rollBack();
                
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=vendors&action=create');
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=vendors&action=create');
        }
    }
    
    /**
     * Formulario para editar un proveedor
     */
    public function edit($id) {
        try {
            // Obtener el proveedor
            $query = "SELECT * FROM vendors WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vendor) {
                setFlashMessage('error', 'Proveedor no encontrado');
                redirect('/index.php?page=vendors');
            }
            
            // Obtener monedas disponibles
            $currencies = [
                'EUR' => 'Euro (€)',
                'USD' => 'Dólar estadounidense ($)',
                'GBP' => 'Libra esterlina (£)',
                'MXN' => 'Peso mexicano (MXN)'
            ];
            
            // Obtener campos personalizados
            $customFields = getCustomFields('vendors');
            $customFieldValues = getCustomFieldValues('vendors', $id);
            
            // Cargar vista
            $pageTitle = 'Editar Proveedor';
            $breadcrumbs = [
                'Proveedores' => url('/index.php?page=vendors'),
                'Editar Proveedor' => null
            ];
            
            include __DIR__ . '/../views/vendors/form.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=vendors');
        }
    }
    
    /**
     * Actualizar un proveedor existente
     */
    public function update($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateVendor($_POST);
        
        if (empty($errors)) {
            try {
                // Iniciar transacción
                $this->db->beginTransaction();
                
                // Actualizar proveedor
                $query = "UPDATE vendors 
                          SET name = ?, email = ?, phone = ?, address = ?, 
                              tax_number = ?, website = ?, credit_limit = ?, 
                              currency = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                          WHERE id = ?";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'] ?? null,
                    $_POST['phone'] ?? null,
                    $_POST['address'] ?? null,
                    $_POST['tax_number'] ?? null,
                    $_POST['website'] ?? null,
                    $_POST['credit_limit'] ?? 0,
                    $_POST['currency'] ?? 'EUR',
                    $_POST['notes'] ?? null,
                    $id
                ]);
                
                // Actualizar campos personalizados
                // Primero eliminar valores existentes
                $query = "DELETE FROM custom_field_values WHERE model_id = ? AND module = 'vendors'";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$id]);
                
                // Luego insertar nuevos valores
                if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
                    foreach ($_POST['custom_fields'] as $fieldId => $value) {
                        if (empty($value)) continue;
                        
                        $query = "INSERT INTO custom_field_values (field_id, model_id, module, value)
                                  VALUES (?, ?, 'vendors', ?)";
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$fieldId, $id, $value]);
                    }
                }
                
                // Confirmar transacción
                $this->db->commit();
                
                setFlashMessage('success', 'Proveedor actualizado correctamente');
                redirect('/index.php?page=vendors');
                
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $this->db->rollBack();
                
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=vendors&action=edit&id=' . $id);
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=vendors&action=edit&id=' . $id);
        }
    }
    
    /**
     * Ver detalles de un proveedor
     */
    public function show($id) {
        try {
            // Obtener el proveedor
            $query = "SELECT * FROM vendors WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vendor) {
                setFlashMessage('error', 'Proveedor no encontrado');
                redirect('/index.php?page=vendors');
            }
            
            // Obtener facturas de compra
            $query = "SELECT * FROM bills WHERE vendor_id = ? ORDER BY bill_date DESC LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener transacciones
            $query = "SELECT t.*, tc.name as category_name
                      FROM transactions t
                      LEFT JOIN transaction_categories tc ON t.category_id = tc.id
                      WHERE t.contact_id = ? AND t.contact_type = 'vendor'
                      ORDER BY t.date DESC LIMIT 10";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener campos personalizados
            $customFieldValues = getCustomFieldValues('vendors', $id);
            
            // Cargar vista
            $pageTitle = 'Detalles del Proveedor';
            $breadcrumbs = [
                'Proveedores' => url('/index.php?page=vendors'),
                'Detalles' => null
            ];
            
            include __DIR__ . '/../views/vendors/show.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=vendors');
        }
    }
    
    /**
     * Eliminar un proveedor
     */
    public function delete($id) {
        // Verificar token CSRF si se envía por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken($_POST['csrf_token'] ?? '');
        }
        
        try {
            // Verificar si hay facturas de compra asociadas
            $query = "SELECT COUNT(*) FROM bills WHERE vendor_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            if ($stmt->fetchColumn() > 0) {
                setFlashMessage('error', 'No se puede eliminar el proveedor porque tiene facturas asociadas');
                redirect('/index.php?page=vendors');
                return;
            }
            
            // Verificar si hay transacciones asociadas
            $query = "SELECT COUNT(*) FROM transactions WHERE contact_id = ? AND contact_type = 'vendor'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            if ($stmt->fetchColumn() > 0) {
                setFlashMessage('error', 'No se puede eliminar el proveedor porque tiene transacciones asociadas');
                redirect('/index.php?page=vendors');
                return;
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Eliminar campos personalizados
            $query = "DELETE FROM custom_field_values WHERE model_id = ? AND module = 'vendors'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            // Eliminar proveedor
            $query = "DELETE FROM vendors WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            // Confirmar transacción
            $this->db->commit();
            
            setFlashMessage('success', 'Proveedor eliminado correctamente');
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            setFlashMessage('error', 'Error: ' . $e->getMessage());
        }
        
        redirect('/index.php?page=vendors');
    }
    
    /**
     * Validar datos de proveedor
     */
    private function validateVendor($data) {
        $errors = [];
        
        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es obligatorio';
        }
        
        // Validar email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }
        
        // Validar límite de crédito
        if (!empty($data['credit_limit']) && !is_numeric($data['credit_limit'])) {
            $errors['credit_limit'] = 'El límite de crédito debe ser un número';
        }
        
        // Validar sitio web
        if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            $errors['website'] = 'El sitio web no es válido';
        }
        
        return $errors;
    }
}