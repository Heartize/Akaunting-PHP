<?php
/**
 * Controlador para la gestión de clientes
 */
class ClientController {
    private $db;
    private $client;
    
    public function __construct($db) {
        $this->db = $db;
        require_once __DIR__ . '/../models/Client.php';
        $this->client = new Client($db);
    }
    
    /**
     * Lista de clientes
     */
    public function index() {
        // Parámetros de paginación
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = $GLOBALS['config']['default_pagination_limit'];
        $offset = ($page - 1) * $limit;
        
        // Filtros de búsqueda
        $search = isset($_GET['search']) ? trim($_GET['search']) : null;
        
        // Obtener clientes con paginación
        $clients = $this->client->getAll($limit, $offset, $search);
        $total = $this->client->count($search);
        
        // Calcular páginas
        $totalPages = ceil($total / $limit);
        
        // Cargar vista
        include __DIR__ . '/../views/clients/index.php';
    }
    
    /**
     * Formulario para crear un nuevo cliente
     */
    public function create() {
        // Obtener campos personalizados
        $customFields = getCustomFields('clients');
        
        // Cargar vista
        include __DIR__ . '/../views/clients/form.php';
    }
    
    /**
     * Guardar un nuevo cliente
     */
    public function store() {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateClient($_POST);
        
        if (empty($errors)) {
            // Preparar datos del cliente
            $data = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
                'tax_number' => $_POST['tax_number'] ?? '',
                'website' => $_POST['website'] ?? '',
                'credit_limit' => $_POST['credit_limit'] ?? 0,
                'currency' => $_POST['currency'] ?? 'EUR',
                'notes' => $_POST['notes'] ?? ''
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
            
            // Crear cliente
            $clientId = $this->client->create($data);
            
            if ($clientId) {
                setFlashMessage('success', 'Cliente creado correctamente');
                redirect('/index.php?page=clients');
            } else {
                setFlashMessage('error', 'Error al crear el cliente');
                redirect('/index.php?page=clients&action=create');
            }
        } else {
            // Hay errores, volver al formulario
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=clients&action=create');
        }
    }
    
    /**
     * Formulario para editar un cliente
     */
    public function edit($id) {
        $client = $this->client->getById($id);
        
        if (!$client) {
            setFlashMessage('error', 'Cliente no encontrado');
            redirect('/index.php?page=clients');
        }
        
        // Obtener campos personalizados
        $customFields = getCustomFields('clients');
        $customFieldValues = getCustomFieldValues('clients', $id);
        
        // Cargar vista
        include __DIR__ . '/../views/clients/form.php';
    }
    
    /**
     * Actualizar un cliente existente
     */
    public function update($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateClient($_POST);
        
        if (empty($errors)) {
            // Preparar datos del cliente
            $data = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
                'tax_number' => $_POST['tax_number'] ?? '',
                'website' => $_POST['website'] ?? '',
                'credit_limit' => $_POST['credit_limit'] ?? 0,
                'currency' => $_POST['currency'] ?? 'EUR',
                'notes' => $_POST['notes'] ?? ''
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
            
            // Actualizar cliente
            $result = $this->client->update($id, $data);
            
            if ($result) {
                setFlashMessage('success', 'Cliente actualizado correctamente');
                redirect('/index.php?page=clients');
            } else {
                setFlashMessage('error', 'Error al actualizar el cliente');
                redirect('/index.php?page=clients&action=edit&id=' . $id);
            }
        } else {
            // Hay errores, volver al formulario
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=clients&action=edit&id=' . $id);
        }
    }
    
    /**
     * Ver detalles de un cliente
     */
    public function show($id) {
        $client = $this->client->getById($id);
        
        if (!$client) {
            setFlashMessage('error', 'Cliente no encontrado');
            redirect('/index.php?page=clients');
        }
        
        // Obtener campos personalizados
        $customFieldValues = getCustomFieldValues('clients', $id);
        
        // Obtener saldo pendiente
        $pendingBalance = $this->client->getPendingBalance($id);
        
        // Cargar vista
        include __DIR__ . '/../views/clients/show.php';
    }
    
    /**
     * Eliminar un cliente
     */
    public function delete($id) {
        // Verificar token CSRF si se envía mediante POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken($_POST['csrf_token'] ?? '');
        }
        
        $result = $this->client->delete($id);
        
        if ($result) {
            setFlashMessage('success', 'Cliente eliminado correctamente');
        } else {
            setFlashMessage('error', 'Error al eliminar el cliente');
        }
        
        redirect('/index.php?page=clients');
    }
    
    /**
     * Validar datos de cliente
     */
    private function validateClient($data) {
        $errors = [];
        
        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es obligatorio';
        }
        
        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es obligatorio';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }
        
        // Validar número fiscal si se proporciona
        if (!empty($data['tax_number']) && strlen($data['tax_number']) < 5) {
            $errors['tax_number'] = 'El número fiscal debe tener al menos 5 caracteres';
        }
        
        // Validar campos personalizados
        $customFields = getCustomFields('clients');
        foreach ($customFields as $field) {
            $fieldName = 'custom_' . $field['id'];
            
            if ($field['required'] && empty($data[$fieldName])) {
                $errors[$fieldName] = 'Este campo es obligatorio';
            }
        }
        
        return $errors;
    }
}