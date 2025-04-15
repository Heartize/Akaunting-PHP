<?php
/**
 * Controlador para la gestión de campos personalizados
 */
class CustomFieldController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Lista de campos personalizados
     */
    public function index() {
        // Parámetros de paginación
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = $GLOBALS['config']['default_pagination_limit'];
        $offset = ($page - 1) * $limit;
        
        // Filtros
        $filters = [];
        if (isset($_GET['module']) && !empty($_GET['module'])) {
            $filters['module'] = $_GET['module'];
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        // Obtener campos personalizados
        try {
            $query = "SELECT * FROM custom_fields";
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters)) {
                $conditions = [];
                
                if (isset($filters['module'])) {
                    $conditions[] = "module = ?";
                    $params[] = $filters['module'];
                }
                
                if (isset($filters['search'])) {
                    $conditions[] = "name LIKE ?";
                    $params[] = '%' . $filters['search'] . '%';
                }
                
                if (!empty($conditions)) {
                    $query .= " WHERE " . implode(" AND ", $conditions);
                }
            }
            
            $query .= " ORDER BY module ASC, position ASC";
            $query .= " LIMIT $offset, $limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $customFields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total para paginación
            $countQuery = "SELECT COUNT(*) FROM custom_fields";
            
            if (!empty($filters)) {
                $conditions = [];
                
                if (isset($filters['module'])) {
                    $conditions[] = "module = ?";
                }
                
                if (isset($filters['search'])) {
                    $conditions[] = "name LIKE ?";
                }
                
                if (!empty($conditions)) {
                    $countQuery .= " WHERE " . implode(" AND ", $conditions);
                }
            }
            
            $stmt = $this->db->prepare($countQuery);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            // Calcular páginas
            $totalPages = ceil($total / $limit);
            
            // Cargar vista
            $pageTitle = 'Campos Personalizados';
            $includeSearch = true;
            $actionButton = 'Nuevo Campo';
            $actionButtonUrl = url('/index.php?page=custom-fields&action=create');
            
            $breadcrumbs = [
                'Campos Personalizados' => null
            ];
            
            // Obtener módulos disponibles
            $modules = [
                'clients' => 'Clientes',
                'vendors' => 'Proveedores',
                'products' => 'Productos',
                'invoices' => 'Facturas',
                'bills' => 'Facturas de compra',
                'transactions' => 'Transacciones'
            ];
            
            include __DIR__ . '/../views/custom_fields/index.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al cargar campos personalizados: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Formulario para crear un nuevo campo personalizado
     */
    public function create() {
        // Obtener módulos disponibles
        $modules = [
            'clients' => 'Clientes',
            'vendors' => 'Proveedores',
            'products' => 'Productos',
            'invoices' => 'Facturas',
            'bills' => 'Facturas de compra',
            'transactions' => 'Transacciones'
        ];
        
        // Tipos de campos disponibles
        $fieldTypes = [
            'text' => 'Texto',
            'textarea' => 'Área de texto',
            'number' => 'Número',
            'date' => 'Fecha',
            'select' => 'Lista desplegable'
        ];
        
        // Cargar vista
        $pageTitle = 'Nuevo Campo Personalizado';
        $breadcrumbs = [
            'Campos Personalizados' => url('/index.php?page=custom-fields'),
            'Nuevo' => null
        ];
        
        include __DIR__ . '/../views/custom_fields/form.php';
    }
    
    /**
     * Guardar nuevo campo personalizado
     */
    public function store() {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateField($_POST);
        
        if (empty($errors)) {
            try {
                // Preparar datos del campo
                $data = [
                    'module' => $_POST['module'],
                    'name' => $_POST['name'],
                    'type' => $_POST['type'],
                    'required' => isset($_POST['required']) ? 1 : 0,
                    'position' => $_POST['position'] ?? 0,
                    'enabled' => isset($_POST['enabled']) ? 1 : 0,
                    'options' => null
                ];
                
                // Si es un campo de selección, guardar opciones
                if ($_POST['type'] === 'select' && !empty($_POST['options'])) {
                    $data['options'] = $_POST['options'];
                }
                
                // Insertar campo personalizado
                $query = "INSERT INTO custom_fields (module, name, type, options, required, position, enabled)
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([
                    $data['module'],
                    $data['name'],
                    $data['type'],
                    $data['options'],
                    $data['required'],
                    $data['position'],
                    $data['enabled']
                ]);
                
                if ($result) {
                    setFlashMessage('success', 'Campo personalizado creado correctamente');
                    redirect('/index.php?page=custom-fields');
                } else {
                    setFlashMessage('error', 'Error al crear el campo personalizado');
                    redirect('/index.php?page=custom-fields&action=create');
                }
                
            } catch (Exception $e) {
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=custom-fields&action=create');
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=custom-fields&action=create');
        }
    }
    
    /**
     * Formulario para editar un campo personalizado
     */
    public function edit($id) {
        try {
            // Obtener el campo personalizado
            $query = "SELECT * FROM custom_fields WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $customField = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customField) {
                setFlashMessage('error', 'Campo personalizado no encontrado');
                redirect('/index.php?page=custom-fields');
            }
            
            // Obtener módulos disponibles
            $modules = [
                'clients' => 'Clientes',
                'vendors' => 'Proveedores',
                'products' => 'Productos',
                'invoices' => 'Facturas',
                'bills' => 'Facturas de compra',
                'transactions' => 'Transacciones'
            ];
            
            // Tipos de campos disponibles
            $fieldTypes = [
                'text' => 'Texto',
                'textarea' => 'Área de texto',
                'number' => 'Número',
                'date' => 'Fecha',
                'select' => 'Lista desplegable'
            ];
            
            // Cargar vista
            $pageTitle = 'Editar Campo Personalizado';
            $breadcrumbs = [
                'Campos Personalizados' => url('/index.php?page=custom-fields'),
                'Editar' => null
            ];
            
            include __DIR__ . '/../views/custom_fields/form.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=custom-fields');
        }
    }
    
    /**
     * Actualizar un campo personalizado
     */
    public function update($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateField($_POST);
        
        if (empty($errors)) {
            try {
                // Preparar datos del campo
                $data = [
                    'module' => $_POST['module'],
                    'name' => $_POST['name'],
                    'type' => $_POST['type'],
                    'required' => isset($_POST['required']) ? 1 : 0,
                    'position' => $_POST['position'] ?? 0,
                    'enabled' => isset($_POST['enabled']) ? 1 : 0,
                    'options' => null
                ];
                
                // Si es un campo de selección, guardar opciones
                if ($_POST['type'] === 'select' && !empty($_POST['options'])) {
                    $data['options'] = $_POST['options'];
                }
                
                // Actualizar campo personalizado
                $query = "UPDATE custom_fields 
                          SET module = ?, name = ?, type = ?, options = ?, 
                              required = ?, position = ?, enabled = ?,
                              updated_at = CURRENT_TIMESTAMP
                          WHERE id = ?";
                
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([
                    $data['module'],
                    $data['name'],
                    $data['type'],
                    $data['options'],
                    $data['required'],
                    $data['position'],
                    $data['enabled'],
                    $id
                ]);
                
                if ($result) {
                    setFlashMessage('success', 'Campo personalizado actualizado correctamente');
                    redirect('/index.php?page=custom-fields');
                } else {
                    setFlashMessage('error', 'Error al actualizar el campo personalizado');
                    redirect('/index.php?page=custom-fields&action=edit&id=' . $id);
                }
                
            } catch (Exception $e) {
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=custom-fields&action=edit&id=' . $id);
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=custom-fields&action=edit&id=' . $id);
        }
    }
    
    /**
     * Eliminar un campo personalizado
     */
    public function delete($id) {
        // Verificar token CSRF si se envía por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken($_POST['csrf_token'] ?? '');
        }
        
        try {
            // Eliminar primero los valores asociados
            $query = "DELETE FROM custom_field_values WHERE field_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            // Luego eliminar el campo
            $query = "DELETE FROM custom_fields WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                setFlashMessage('success', 'Campo personalizado eliminado correctamente');
            } else {
                setFlashMessage('error', 'Error al eliminar el campo personalizado');
            }
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
        }
        
        redirect('/index.php?page=custom-fields');
    }
    
    /**
     * Validar datos del campo personalizado
     */
    private function validateField($data) {
        $errors = [];
        
        // Validar módulo
        if (empty($data['module'])) {
            $errors['module'] = 'El módulo es obligatorio';
        }
        
        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es obligatorio';
        }
        
        // Validar tipo
        if (empty($data['type'])) {
            $errors['type'] = 'El tipo es obligatorio';
        }
        
        // Validar opciones para tipo select
        if ($data['type'] === 'select' && empty($data['options'])) {
            $errors['options'] = 'Las opciones son obligatorias para campos de tipo lista';
        }
        
        return $errors;
    }
}