<?php
/**
 * Controlador para la gestión de transacciones
 */
class TransactionController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        require_once __DIR__ . '/../models/Transaction.php';
        $this->transaction = new Transaction($db);
    }
    
    /**
     * Lista de transacciones
     */
    public function index() {
        // Parámetros de paginación
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = $GLOBALS['config']['default_pagination_limit'];
        $offset = ($page - 1) * $limit;
        
        // Filtros
        $filters = [];
        
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $filters['type'] = $_GET['type'];
        }
        
        if (isset($_GET['account_id']) && !empty($_GET['account_id'])) {
            $filters['account_id'] = $_GET['account_id'];
        }
        
        if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
            $filters['category_id'] = $_GET['category_id'];
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
        
        // Obtener transacciones
        $transactions = $this->transaction->getAll($limit, $offset, $filters);
        $total = $this->transaction->count($filters);
        
        // Calcular páginas
        $totalPages = ceil($total / $limit);
        
        // Obtener cuentas para el filtro
        try {
            $stmt = $this->db->query("SELECT id, name FROM accounts WHERE enabled = 1 ORDER BY name");
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $accounts = [];
        }
        
        // Obtener categorías para el filtro
        try {
            $stmt = $this->db->query("SELECT id, name, type FROM transaction_categories ORDER BY type, name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $categories = [];
        }
        
        // Cargar vista
        $pageTitle = isset($filters['type']) ? 
            ($filters['type'] == 'income' ? 'Ingresos' : 
             ($filters['type'] == 'expense' ? 'Gastos' : 'Transacciones')) : 
            'Transacciones';
        
        $includeSearch = true;
        $actionButton = 'Nueva ' . (isset($filters['type']) ? 
            ($filters['type'] == 'income' ? 'Ingreso' : 
             ($filters['type'] == 'expense' ? 'Gasto' : 'Transacción')) : 
            'Transacción');
        
        $actionButtonUrl = url('/index.php?page=transactions&action=create' . 
            (isset($filters['type']) ? '&type=' . $filters['type'] : ''));
        
        $breadcrumbs = [
            $pageTitle => null
        ];
        
        include __DIR__ . '/../views/transactions/index.php';
    }
    
    /**
     * Formulario para crear una nueva transacción
     */
    public function create() {
        // Determinar el tipo de transacción
        $transactionType = $_GET['type'] ?? 'income';
        
        // Obtener cuentas
        try {
            $stmt = $this->db->query("SELECT id, name FROM accounts WHERE enabled = 1 ORDER BY name");
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $accounts = [];
        }
        
        // Obtener categorías
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM transaction_categories WHERE type = ? ORDER BY name");
            $stmt->execute([$transactionType]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $categories = [];
        }
        
        // Obtener clientes o proveedores según el tipo
        if ($transactionType == 'income') {
            try {
                $stmt = $this->db->query("SELECT id, name FROM clients ORDER BY name");
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $contactType = 'client';
            } catch (Exception $e) {
                $contacts = [];
            }
        } else {
            try {
                $stmt = $this->db->query("SELECT id, name FROM vendors ORDER BY name");
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $contactType = 'vendor';
            } catch (Exception $e) {
                $contacts = [];
            }
        }
        
        // Obtener campos personalizados
        $customFields = getCustomFields('transactions');
        
        // Cargar vista
        $pageTitle = $transactionType == 'income' ? 'Nuevo Ingreso' : 'Nuevo Gasto';
        $breadcrumbs = [
            ($transactionType == 'income' ? 'Ingresos' : 'Gastos') => url('/index.php?page=transactions&type=' . $transactionType),
            'Nuevo' => null
        ];
        
        include __DIR__ . '/../views/transactions/form.php';
    }
    
    /**
     * Guardar una nueva transacción
     */
    public function store() {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateTransaction($_POST);
        
        if (empty($errors)) {
            // Preparar datos de la transacción
            $data = [
                'type' => $_POST['type'],
                'account_id' => $_POST['account_id'],
                'category_id' => $_POST['category_id'] ?? null,
                'amount' => $_POST['amount'],
                'date' => $_POST['date'],
                'description' => $_POST['description'] ?? null,
                'reference' => $_POST['reference'] ?? null,
                'contact_id' => $_POST['contact_id'] ?? null,
                'contact_type' => $_POST['contact_type'] ?? null
            ];
            
            // Crear transacción
            $transactionId = $this->transaction->create($data);
            
            if ($transactionId) {
                setFlashMessage('success', 'Transacción creada correctamente');
                redirect('/index.php?page=transactions&type=' . $data['type']);
            } else {
                setFlashMessage('error', 'Error al crear la transacción');
                redirect('/index.php?page=transactions&action=create&type=' . $data['type']);
            }
        } else {
            // Hay errores, volver al formulario
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=transactions&action=create&type=' . ($_POST['type'] ?? 'income'));
        }
    }
    
    /**
     * Formulario para editar una transacción
     */
    public function edit($id) {
        $transaction = $this->transaction->getById($id);
        
        if (!$transaction) {
            setFlashMessage('error', 'Transacción no encontrada');
            redirect('/index.php?page=transactions');
        }
        
        // Obtener cuentas
        try {
            $stmt = $this->db->query("SELECT id, name FROM accounts WHERE enabled = 1 ORDER BY name");
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $accounts = [];
        }
        
        // Obtener categorías según el tipo
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM transaction_categories WHERE type = ? ORDER BY name");
            $stmt->execute([$transaction['type']]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $categories = [];
        }
        
        // Obtener clientes o proveedores según el tipo
        if ($transaction['type'] == 'income') {
            try {
                $stmt = $this->db->query("SELECT id, name FROM clients ORDER BY name");
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $contactType = 'client';
            } catch (Exception $e) {
                $contacts = [];
            }
        } else {
            try {
                $stmt = $this->db->query("SELECT id, name FROM vendors ORDER BY name");
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $contactType = 'vendor';
            } catch (Exception $e) {
                $contacts = [];
            }
        }
        
        // Obtener campos personalizados
        $customFields = getCustomFields('transactions');
        $customFieldValues = getCustomFieldValues('transactions', $id);
        
        // Cargar vista
        $pageTitle = 'Editar Transacción';
        $breadcrumbs = [
            'Transacciones' => url('/index.php?page=transactions'),
            'Editar' => null
        ];
        
        include __DIR__ . '/../views/transactions/form.php';
    }
    
    /**
     * Actualizar una transacción existente
     */
    public function update($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateTransaction($_POST);
        
        if (empty($errors)) {
            // Preparar datos de la transacción
            $data = [
                'type' => $_POST['type'],
                'account_id' => $_POST['account_id'],
                'category_id' => $_POST['category_id'] ?? null,
                'amount' => $_POST['amount'],
                'date' => $_POST['date'],
                'description' => $_POST['description'] ?? null,
                'reference' => $_POST['reference'] ?? null,
                'contact_id' => $_POST['contact_id'] ?? null,
                'contact_type' => $_POST['contact_type'] ?? null
            ];
            
            // Actualizar transacción
            $result = $this->transaction->update($id, $data);
            
            if ($result) {
                setFlashMessage('success', 'Transacción actualizada correctamente');
                redirect('/index.php?page=transactions&type=' . $data['type']);
            } else {
                setFlashMessage('error', 'Error al actualizar la transacción');
                redirect('/index.php?page=transactions&action=edit&id=' . $id);
            }
        } else {
            // Hay errores, volver al formulario
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=transactions&action=edit&id=' . $id);
        }
    }
    
    /**
     * Ver detalles de una transacción
     */
    public function show($id) {
        $transaction = $this->transaction->getById($id);
        
        if (!$transaction) {
            setFlashMessage('error', 'Transacción no encontrada');
            redirect('/index.php?page=transactions');
        }
        
        // Obtener campos personalizados
        $customFieldValues = getCustomFieldValues('transactions', $id);
        
        // Cargar vista
        $pageTitle = 'Detalles de Transacción';
        $breadcrumbs = [
            'Transacciones' => url('/index.php?page=transactions'),
            'Detalles' => null
        ];
        
        include __DIR__ . '/../views/transactions/show.php';
    }
    
    /**
     * Eliminar una transacción
     */
    public function delete($id) {
        // Verificar token CSRF si se envía mediante POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken($_POST['csrf_token'] ?? '');
        }
        
        // Obtener el tipo de transacción antes de eliminarla
        $transaction = $this->transaction->getById($id);
        $type = $transaction ? $transaction['type'] : null;
        
        $result = $this->transaction->delete($id);
        
        if ($result) {
            setFlashMessage('success', 'Transacción eliminada correctamente');
        } else {
            setFlashMessage('error', 'Error al eliminar la transacción');
        }
        
        if ($type) {
            redirect('/index.php?page=transactions&type=' . $type);
        } else {
            redirect('/index.php?page=transactions');
        }
    }
    
    /**
     * Crear transferencia entre cuentas
     */
    public function transfer() {
        // Obtener cuentas
        try {
            $stmt = $this->db->query("SELECT id, name FROM accounts WHERE enabled = 1 ORDER BY name");
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $accounts = [];
        }
        
        // Cargar vista
        $pageTitle = 'Nueva Transferencia';
        $breadcrumbs = [
            'Transacciones' => url('/index.php?page=transactions'),
            'Nueva Transferencia' => null
        ];
        
        include __DIR__ . '/../views/transactions/transfer.php';
    }
    
    /**
     * Guardar transferencia entre cuentas
     */
    public function storeTransfer() {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateTransfer($_POST);
        
        if (empty($errors)) {
            try {
                // Iniciar transacción
                $this->db->beginTransaction();
                
                // Crear transferencia
                $query = "INSERT INTO transfers (
                            from_account_id, to_account_id, amount, 
                            date, description, reference
                          ) VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    $_POST['from_account_id'],
                    $_POST['to_account_id'],
                    $_POST['amount'],
                    $_POST['date'],
                    $_POST['description'] ?? null,
                    $_POST['reference'] ?? null
                ]);
                
                $transferId = $this->db->lastInsertId();
                
                // Actualizar saldo de la cuenta de origen (gasto)
                $query = "UPDATE accounts 
                          SET current_balance = current_balance - ?,
                              updated_at = CURRENT_TIMESTAMP
                          WHERE id = ?";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([$_POST['amount'], $_POST['from_account_id']]);
                
                // Actualizar saldo de la cuenta de destino (ingreso)
                $query = "UPDATE accounts 
                          SET current_balance = current_balance + ?,
                              updated_at = CURRENT_TIMESTAMP
                          WHERE id = ?";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([$_POST['amount'], $_POST['to_account_id']]);
                
                // Confirmar transacción
                $this->db->commit();
                
                setFlashMessage('success', 'Transferencia realizada correctamente');
                redirect('/index.php?page=transactions');
                
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $this->db->rollBack();
                
                setFlashMessage('error', 'Error al realizar la transferencia: ' . $e->getMessage());
                redirect('/index.php?page=transactions&action=transfer');
            }
        } else {
            // Hay errores, volver al formulario
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=transactions&action=transfer');
        }
    }
    
    /**
     * Validar datos de transacción
     */
    private function validateTransaction($data) {
        $errors = [];
        
        // Validar tipo
        if (empty($data['type'])) {
            $errors['type'] = 'El tipo es obligatorio';
        }
        
        // Validar cuenta
        if (empty($data['account_id'])) {
            $errors['account_id'] = 'La cuenta es obligatoria';
        }
        
        // Validar importe
        if (empty($data['amount'])) {
            $errors['amount'] = 'El importe es obligatorio';
        } elseif (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'El importe debe ser un número positivo';
        }
        
        // Validar fecha
        if (empty($data['date'])) {
            $errors['date'] = 'La fecha es obligatoria';
        }
        
        return $errors;
    }
    
    /**
     * Validar datos de transferencia
     */
    private function validateTransfer($data) {
        $errors = [];
        
        // Validar cuenta de origen
        if (empty($data['from_account_id'])) {
            $errors['from_account_id'] = 'La cuenta de origen es obligatoria';
        }
        
        // Validar cuenta de destino
        if (empty($data['to_account_id'])) {
            $errors['to_account_id'] = 'La cuenta de destino es obligatoria';
        }
        
        // Validar que las cuentas sean diferentes
        if (!empty($data['from_account_id']) && !empty($data['to_account_id']) && 
            $data['from_account_id'] == $data['to_account_id']) {
            $errors['to_account_id'] = 'Las cuentas de origen y destino deben ser diferentes';
        }
        
        // Validar importe
        if (empty($data['amount'])) {
            $errors['amount'] = 'El importe es obligatorio';
        } elseif (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'El importe debe ser un número positivo';
        }
        
        // Validar fecha
        if (empty($data['date'])) {
            $errors['date'] = 'La fecha es obligatoria';
        }
        
        return $errors;
    }
}