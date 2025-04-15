<?php
/**
 * Controlador para la gestión de facturas
 */
class InvoiceController {
    private $db;
    private $invoice;
    private $invoiceItem;
    private $client;
    private $product;
    
    public function __construct($db) {
        $this->db = $db;
        require_once __DIR__ . '/../models/Invoice.php';
        require_once __DIR__ . '/../models/InvoiceItem.php';
        require_once __DIR__ . '/../models/Client.php';
        require_once __DIR__ . '/../models/Product.php';
        
        $this->invoice = new Invoice($db);
        $this->invoiceItem = new InvoiceItem($db);
        $this->client = new Client($db);
        $this->product = new Product($db);
    }
    
    /**
     * Lista de facturas
     */
    public function index() {
        // Parámetros de paginación
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = $GLOBALS['config']['default_pagination_limit'];
        $offset = ($page - 1) * $limit;
        
        // Filtros
        $filters = [];
        
        if (isset($_GET['client_id']) && !empty($_GET['client_id'])) {
            $filters['client_id'] = $_GET['client_id'];
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
        
        // Obtener facturas con paginación
        $invoices = $this->invoice->getAll($limit, $offset, $filters);
        $total = $this->invoice->count($filters);
        
        // Calcular páginas
        $totalPages = ceil($total / $limit);
        
        // Obtener clientes para el filtro
        $clients = $this->client->getAll();
        
        // Cargar vista
        include __DIR__ . '/../views/invoices/index.php';
    }
    
    /**
     * Formulario para crear una nueva factura
     */
    public function create() {
        // Obtener clientes
        $clients = $this->client->getAll();
        
        // Obtener productos
        $products = $this->product->getAll();
        
        // Obtener campos personalizados
        $customFields = getCustomFields('invoices');
        
        // Generar número de factura
        $nextInvoiceNumber = $this->invoice->getNextInvoiceNumber();
        
        // Cargar vista
        include __DIR__ . '/../views/invoices/form.php';
    }
    
/**
 * Guardar una nueva factura
 */
public function store() {
    // Verificar token CSRF
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    // Validar datos
    $errors = $this->validateInvoice($_POST);
    
    if (empty($errors)) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Preparar datos de la factura
            $data = [
                'invoice_number' => $_POST['invoice_number'],
                'client_id' => $_POST['client_id'],
                'status' => $_POST['status'] ?? 'draft',
                'invoice_date' => $_POST['invoice_date'],
                'due_date' => $_POST['due_date'],
                'subtotal' => $_POST['subtotal'],
                'tax_total' => $_POST['tax_total'],
                'total' => $_POST['total'],
                'notes' => $_POST['notes'] ?? null,
                'footer' => $_POST['footer'] ?? null
            ];
            
            // Crear factura
            $invoiceId = $this->invoice->create($data);
            
            if (!$invoiceId) {
                throw new Exception("Error al crear la factura");
            }
            
            // Insertar elementos de la factura
            if (isset($_POST['product']) && is_array($_POST['product'])) {
                $itemsCount = count($_POST['product']);
                
                for ($i = 0; $i < $itemsCount; $i++) {
                    if (empty($_POST['description'][$i])) continue;
                    
                    $itemData = [
                        'invoice_id' => $invoiceId,
                        'product_id' => !empty($_POST['product'][$i]) ? $_POST['product'][$i] : null,
                        'description' => $_POST['description'][$i],
                        'quantity' => $_POST['quantity'][$i],
                        'price' => $_POST['price'][$i],
                        'tax_rate' => $_POST['tax_rate'][$i],
                        'tax_amount' => $_POST['tax_amount'][$i],
                        'total' => $_POST['item_total'][$i]
                    ];
                    
                    $this->invoiceItem->create($itemData);
                }
            }
            
            // Guardar campos personalizados
            if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
                $customFields = [];
                foreach ($_POST['custom_fields'] as $fieldId => $value) {
                    if (!empty($value)) {
                        $customFields[$fieldId] = $value;
                    }
                }
                
                // Solo guardar si hay campos con valores
                if (!empty($customFields)) {
                    $this->saveCustomFields($invoiceId, $customFields);
                }
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            setFlashMessage('success', 'Factura creada correctamente');
            redirect('/index.php?page=invoices');
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            setFlashMessage('error', 'Error al crear la factura: ' . $e->getMessage());
            redirect('/index.php?page=invoices&action=create');
        }
    } else {
        // Hay errores, volver al formulario
        $_SESSION['form_data'] = $_POST;
        $_SESSION['form_errors'] = $errors;
        redirect('/index.php?page=invoices&action=create');
    }
}

/**
 * Guardar campos personalizados
 */
private function saveCustomFields($invoiceId, $customFields) {
    foreach ($customFields as $fieldId => $value) {
        $query = "INSERT INTO custom_field_values (field_id, model_id, module, value)
                  VALUES (?, ?, 'invoices', ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$fieldId, $invoiceId, $value]);
    }
}
    
    /**
     * Formulario para editar una factura
     */
    public function edit($id) {
        $invoice = $this->invoice->getById($id);
        
        if (!$invoice) {
            setFlashMessage('error', 'Factura no encontrada');
            redirect('/index.php?page=invoices');
        }
        
        // Obtener elementos de la factura
        $items = $this->invoiceItem->getByInvoiceId($id);
        
        // Obtener clientes
        $clients = $this->client->getAll();
        
        // Obtener productos
        $products = $this->product->getAll();
        
        // Obtener campos personalizados
        $customFields = getCustomFields('invoices');
        $customFieldValues = getCustomFieldValues('invoices', $id);
        
        // Cargar vista
        include __DIR__ . '/../views/invoices/form.php';
    }
    
    /**
     * Actualizar una factura existente
     */
    public function update($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateInvoice($_POST);
        
        if (empty($errors)) {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            try {
                // Preparar datos de la factura
                $data = [
                    'invoice_number' => $_POST['invoice_number'],
                    'client_id' => $_POST['client_id'],
                    'status' => $_POST['status'] ?? 'draft',
                    'invoice_date' => $_POST['invoice_date'],
                    'due_date' => $_POST['due_date'],
                    'notes' => $_POST['notes'] ?? '',
                    'footer' => $_POST['footer'] ?? ''
                ];
                
                // Recopilar campos personalizados
                $customFields = [];
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'custom_') === 0) {
                        $fieldId = substr($key, 7);
                        $customFields[$fieldId] = $value;
                    }
                }
                
                $data['custom_fields'] = $customFields;
                
                // Actualizar factura
                $result = $this->invoice->update($id, $data);
                
                if ($result) {
                    // Eliminar elementos actuales
                    $this->invoiceItem->deleteByInvoiceId($id);
                    
                    // Procesar elementos de la factura
                    $itemsCount = count($_POST['items']['product_id'] ?? []);
                    
                    for ($i = 0; $i < $itemsCount; $i++) {
                        if (empty($_POST['items']['product_id'][$i]) && empty($_POST['items']['description'][$i])) {
                            continue;  // Saltar elementos vacíos
                        }
                        
                        $productId = !empty($_POST['items']['product_id'][$i]) ? $_POST['items']['product_id'][$i] : null;
                        $quantity = $_POST['items']['quantity'][$i] ?? 1;
                        $price = $_POST['items']['price'][$i] ?? 0;
                        $taxRate = $_POST['items']['tax_rate'][$i] ?? 21;
                        
                        $taxAmount = ($price * $quantity) * ($taxRate / 100);
                        $total = ($price * $quantity) + $taxAmount;
                        
                        $itemData = [
                            'invoice_id' => $id,
                            'product_id' => $productId,
                            'description' => $_POST['items']['description'][$i] ?? '',
                            'quantity' => $quantity,
                            'price' => $price,
                            'tax_rate' => $taxRate,
                            'tax_amount' => $taxAmount,
                            'total' => $total
                        ];
                        
                        $this->invoiceItem->create($itemData);
                    }
                    
                    // Actualizar totales de la factura
                    updateInvoiceTotals($id);
                    
                    // Confirmar transacción
                    $this->db->commit();
                    
                    setFlashMessage('success', 'Factura actualizada correctamente');
                    redirect('/index.php?page=invoices');
                } else {
                    throw new Exception('Error al actualizar la factura');
                }
            } catch (Exception $e) {
                // Revertir en caso de error
                $this->db->rollBack();
                
                setFlashMessage('error', 'Error al procesar la factura: ' . $e->getMessage());
                redirect('/index.php?page=invoices&action=edit&id=' . $id);
            }
        } else {
            // Hay errores, volver al formulario
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=invoices&action=edit&id=' . $id);
        }
    }
    
    /**
     * Ver detalles de una factura
     */
    public function show($id) {
        $invoice = $this->invoice->getById($id);
        
        if (!$invoice) {
            setFlashMessage('error', 'Factura no encontrada');
            redirect('/index.php?page=invoices');
        }
        
        // Obtener elementos de la factura
        $items = $this->invoiceItem->getByInvoiceId($id);
        
        // Obtener cliente
        $client = $this->client->getById($invoice['client_id']);
        
        // Obtener campos personalizados
        $customFieldValues = getCustomFieldValues('invoices', $id);
        
        // Cargar vista
        include __DIR__ . '/../views/invoices/show.php';
    }
    
    /**
     * Eliminar una factura
     */
    public function delete($id) {
        // Verificar token CSRF si se envía mediante POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken($_POST['csrf_token'] ?? '');
        }
        
        // Iniciar transacción
        $this->db->beginTransaction();
        
        try {
            // Eliminar elementos de la factura
            $this->invoiceItem->deleteByInvoiceId($id);
            
            // Eliminar la factura
            $result = $this->invoice->delete($id);
            
            if ($result) {
                // Confirmar transacción
                $this->db->commit();
                
                setFlashMessage('success', 'Factura eliminada correctamente');
            } else {
                throw new Exception('Error al eliminar la factura');
            }
        } catch (Exception $e) {
            // Revertir en caso de error
            $this->db->rollBack();
            
            setFlashMessage('error', 'Error al eliminar la factura: ' . $e->getMessage());
        }
        
        redirect('/index.php?page=invoices');
    }
    
    /**
     * Cambiar el estado de una factura
     */
    public function changeStatus($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        $newStatus = $_POST['status'] ?? '';
        
        if (empty($newStatus)) {
            setFlashMessage('error', 'El estado no puede estar vacío');
            redirect('/index.php?page=invoices&action=show&id=' . $id);
        }
        
        $result = $this->invoice->updateStatus($id, $newStatus);
        
        if ($result) {
            setFlashMessage('success', 'Estado de la factura actualizado correctamente');
        } else {
            setFlashMessage('error', 'Error al actualizar el estado de la factura');
        }
        
        redirect('/index.php?page=invoices&action=show&id=' . $id);
    }
    
    /**
     * Generar PDF de una factura
     */
    public function pdf($id) {
        $invoice = $this->invoice->getById($id);
        
        if (!$invoice) {
            setFlashMessage('error', 'Factura no encontrada');
            redirect('/index.php?page=invoices');
        }
        
        // Obtener elementos de la factura
        $items = $this->invoiceItem->getByInvoiceId($id);
        
        // Obtener cliente
        $client = $this->client->getById($invoice['client_id']);
        
        // Aquí se generaría el PDF con una librería como TCPDF o FPDF
        // Por simplicidad, sólo mostraremos un mensaje
        echo "Generación de PDF no implementada en esta versión";
        exit;
    }
    
    /**
     * Validar datos de factura
     */
    private function validateInvoice($data) {
        $errors = [];
        
        // Validar número de factura
        if (empty($data['invoice_number'])) {
            $errors['invoice_number'] = 'El número de factura es obligatorio';
        }
        
        // Validar cliente
        if (empty($data['client_id'])) {
            $errors['client_id'] = 'Debe seleccionar un cliente';
        }
        
        // Validar fecha de emisión
        if (empty($data['invoice_date'])) {
            $errors['invoice_date'] = 'La fecha de emisión es obligatoria';
        }
        
        // Validar fecha de vencimiento
        if (empty($data['due_date'])) {
            $errors['due_date'] = 'La fecha de vencimiento es obligatoria';
        }
        
        // Validar elementos (al menos uno)
        if (empty($data['items']['product_id']) || count(array_filter($data['items']['product_id'])) == 0) {
            $errors['items'] = 'Debe agregar al menos un elemento a la factura';
        }
        
        // Validar campos personalizados
        $customFields = getCustomFields('invoices');
        foreach ($customFields as $field) {
            $fieldName = 'custom_' . $field['id'];
            
            if ($field['required'] && empty($data[$fieldName])) {
                $errors[$fieldName] = 'Este campo es obligatorio';
            }
        }
        
        return $errors;
    }
}