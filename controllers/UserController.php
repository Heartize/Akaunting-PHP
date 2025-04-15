<?php
/**
 * Controlador para la gestión de usuarios
 */
class UserController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Lista de usuarios
     */
    public function index() {
        // Verificar si el usuario actual tiene rol de administrador
        requireRole('admin');
        
        // Parámetros de paginación
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = $GLOBALS['config']['default_pagination_limit'];
        $offset = ($page - 1) * $limit;
        
        // Filtros
        $filters = [];
        if (isset($_GET['role']) && !empty($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        // Obtener usuarios
        try {
            $query = "SELECT * FROM users";
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters)) {
                $conditions = [];
                
                if (isset($filters['role'])) {
                    $conditions[] = "role = ?";
                    $params[] = $filters['role'];
                }
                
                if (isset($filters['status'])) {
                    $conditions[] = "active = ?";
                    $params[] = $filters['status'];
                }
                
                if (isset($filters['search'])) {
                    $conditions[] = "(name LIKE ? OR email LIKE ?)";
                    $searchParam = '%' . $filters['search'] . '%';
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                }
                
                if (!empty($conditions)) {
                    $query .= " WHERE " . implode(" AND ", $conditions);
                }
            }
            
            $query .= " ORDER BY id DESC";
            $query .= " LIMIT $offset, $limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total para paginación
            $countQuery = "SELECT COUNT(*) FROM users";
            
            if (!empty($filters)) {
                $conditions = [];
                
                if (isset($filters['role'])) {
                    $conditions[] = "role = ?";
                }
                
                if (isset($filters['status'])) {
                    $conditions[] = "active = ?";
                }
                
                if (isset($filters['search'])) {
                    $conditions[] = "(name LIKE ? OR email LIKE ?)";
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
            $pageTitle = 'Usuarios';
            $includeSearch = true;
            $actionButton = 'Nuevo Usuario';
            $actionButtonUrl = url('/index.php?page=users&action=create');
            
            $breadcrumbs = [
                'Usuarios' => null
            ];
            
            include __DIR__ . '/../views/users/index.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al cargar usuarios: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Formulario para crear un nuevo usuario
     */
    public function create() {
        // Verificar si el usuario actual tiene rol de administrador
        requireRole('admin');
        
        // Roles disponibles
        $roles = [
            'admin' => 'Administrador',
            'manager' => 'Gerente',
            'user' => 'Usuario'
        ];
        
        // Cargar vista
        $pageTitle = 'Nuevo Usuario';
        $breadcrumbs = [
            'Usuarios' => url('/index.php?page=users'),
            'Nuevo' => null
        ];
        
        include __DIR__ . '/../views/users/form.php';
    }
    
    /**
     * Guardar nuevo usuario
     */
    public function store() {
        // Verificar si el usuario actual tiene rol de administrador
        requireRole('admin');
        
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateUser($_POST);
        
        if (empty($errors)) {
            try {
                // Verificar que el email no exista
                $query = "SELECT COUNT(*) FROM users WHERE email = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$_POST['email']]);
                $exists = $stmt->fetchColumn();
                
                if ($exists) {
                    setFlashMessage('error', 'El email ya está registrado');
                    redirect('/index.php?page=users&action=create');
                    return;
                }
                
                // Crear hash de contraseña
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                // Insertar usuario
                $query = "INSERT INTO users (name, email, password, role, active)
                          VALUES (?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    $hashedPassword,
                    $_POST['role'],
                    isset($_POST['active']) ? 1 : 0
                ]);
                
                if ($result) {
                    setFlashMessage('success', 'Usuario creado correctamente');
                    redirect('/index.php?page=users');
                } else {
                    setFlashMessage('error', 'Error al crear el usuario');
                    redirect('/index.php?page=users&action=create');
                }
                
            } catch (Exception $e) {
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=users&action=create');
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=users&action=create');
        }
    }
    
    /**
     * Formulario para editar un usuario
     */
    public function edit($id) {
        // Verificar si el usuario actual tiene rol de administrador o es el propio usuario
        if (!hasRole('admin') && $_SESSION['user_id'] != $id) {
            setFlashMessage('error', 'No tiene permiso para editar este usuario');
            redirect('/index.php?page=dashboard');
            return;
        }
        
        try {
            // Obtener el usuario
            $query = "SELECT * FROM users WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                setFlashMessage('error', 'Usuario no encontrado');
                redirect('/index.php?page=users');
            }
            
            // Roles disponibles
            $roles = [
                'admin' => 'Administrador',
                'manager' => 'Gerente',
                'user' => 'Usuario'
            ];
            
            // Cargar vista
            $pageTitle = 'Editar Usuario';
            $breadcrumbs = [
                'Usuarios' => url('/index.php?page=users'),
                'Editar' => null
            ];
            
            include __DIR__ . '/../views/users/form.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=users');
        }
    }
    
    /**
     * Actualizar un usuario
     */
    public function update($id) {
        // Verificar si el usuario actual tiene rol de administrador o es el propio usuario
        if (!hasRole('admin') && $_SESSION['user_id'] != $id) {
            setFlashMessage('error', 'No tiene permiso para editar este usuario');
            redirect('/index.php?page=dashboard');
            return;
        }
        
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateUser($_POST, true);
        
        if (empty($errors)) {
            try {
                // Verificar que el email no exista para otro usuario
                $query = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$_POST['email'], $id]);
                $exists = $stmt->fetchColumn();
                
                if ($exists) {
                    setFlashMessage('error', 'El email ya está registrado por otro usuario');
                    redirect('/index.php?page=users&action=edit&id=' . $id);
                    return;
                }
                
                // Actualizar usuario
                if (!empty($_POST['password'])) {
                    // Si se proporciona contraseña, actualizarla
                    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    
                    $query = "UPDATE users 
                              SET name = ?, email = ?, password = ?, role = ?, active = ?,
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE id = ?";
                    
                    $stmt = $this->db->prepare($query);
                    $result = $stmt->execute([
                        $_POST['name'],
                        $_POST['email'],
                        $hashedPassword,
                        $_POST['role'] ?? 'user',
                        isset($_POST['active']) ? 1 : 0,
                        $id
                    ]);
                } else {
                    // Sin cambio de contraseña
                    $query = "UPDATE users 
                              SET name = ?, email = ?, role = ?, active = ?,
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE id = ?";
                    
                    $stmt = $this->db->prepare($query);
                    $result = $stmt->execute([
                        $_POST['name'],
                        $_POST['email'],
                        $_POST['role'] ?? 'user',
                        isset($_POST['active']) ? 1 : 0,
                        $id
                    ]);
                }
                
                if ($result) {
                    setFlashMessage('success', 'Usuario actualizado correctamente');
                    redirect('/index.php?page=users');
                } else {
                    setFlashMessage('error', 'Error al actualizar el usuario');
                    redirect('/index.php?page=users&action=edit&id=' . $id);
                }
                
            } catch (Exception $e) {
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=users&action=edit&id=' . $id);
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=users&action=edit&id=' . $id);
        }
    }
    
    /**
     * Eliminar un usuario
     */
    public function delete($id) {
        // Verificar si el usuario actual tiene rol de administrador
        requireRole('admin');
        
        // Verificar token CSRF si se envía por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken($_POST['csrf_token'] ?? '');
        }
        
        try {
            // No permitir eliminar el propio usuario
            if ($_SESSION['user_id'] == $id) {
                setFlashMessage('error', 'No puede eliminar su propio usuario');
                redirect('/index.php?page=users');
                return;
            }
            
            // Verificar que no sea el último administrador
            $query = "SELECT COUNT(*) FROM users WHERE role = 'admin'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            $query = "SELECT role FROM users WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $userRole = $stmt->fetchColumn();
            
            if ($adminCount <= 1 && $userRole === 'admin') {
                setFlashMessage('error', 'No puede eliminar el último administrador');
                redirect('/index.php?page=users');
                return;
            }
            
            // Eliminar usuario
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                setFlashMessage('success', 'Usuario eliminado correctamente');
            } else {
                setFlashMessage('error', 'Error al eliminar el usuario');
            }
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
        }
        
        redirect('/index.php?page=users');
    }
    
    /**
     * Cambiar contraseña del usuario actual
     */
    public function changePassword() {
        // Solo usuarios autenticados
        requireLogin();
        
        // Cargar vista
        $pageTitle = 'Cambiar Contraseña';
        $breadcrumbs = [
            'Usuarios' => url('/index.php?page=users'),
            'Cambiar Contraseña' => null
        ];
        
        include __DIR__ . '/../views/users/change_password.php';
    }
    
    /**
     * Guardar nueva contraseña
     */
    public function updatePassword() {
        // Solo usuarios autenticados
        requireLogin();
        
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Validar contraseña actual
        if (empty($currentPassword)) {
            $errors['current_password'] = 'La contraseña actual es obligatoria';
        }
        
        // Validar nueva contraseña
        if (empty($newPassword)) {
            $errors['new_password'] = 'La nueva contraseña es obligatoria';
        } elseif (strlen($newPassword) < 6) {
            $errors['new_password'] = 'La contraseña debe tener al menos 6 caracteres';
        }
        
        // Validar confirmación
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Las contraseñas no coinciden';
        }
        
        if (empty($errors)) {
            try {
                // Verificar contraseña actual
                $query = "SELECT password FROM users WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    setFlashMessage('error', 'La contraseña actual es incorrecta');
                    redirect('/index.php?page=users&action=change-password');
                    return;
                }
                
                // Actualizar contraseña
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $query = "UPDATE users 
                          SET password = ?, updated_at = CURRENT_TIMESTAMP
                          WHERE id = ?";
                
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([$hashedPassword, $userId]);
                
                if ($result) {
                    setFlashMessage('success', 'Contraseña actualizada correctamente');
                    redirect('/index.php?page=dashboard');
                } else {
                    setFlashMessage('error', 'Error al actualizar la contraseña');
                    redirect('/index.php?page=users&action=change-password');
                }
                
            } catch (Exception $e) {
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=users&action=change-password');
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=users&action=change-password');
        }
    }
    
    /**
     * Ver perfil de usuario
     */
    public function profile() {
        // Solo usuarios autenticados
        requireLogin();
        
        $userId = $_SESSION['user_id'];
        
        try {
            // Obtener el usuario
            $query = "SELECT * FROM users WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                setFlashMessage('error', 'Usuario no encontrado');
                redirect('/index.php?page=dashboard');
            }
            
            // Cargar vista
            $pageTitle = 'Mi Perfil';
            $breadcrumbs = [
                'Mi Perfil' => null
            ];
            
            include __DIR__ . '/../views/users/profile.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Validar datos del usuario
     */
    private function validateUser($data, $isUpdate = false) {
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
        
        // Validar contraseña (solo en creación o si se proporciona en actualización)
        if (!$isUpdate || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors['password'] = 'La contraseña es obligatoria';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
            }
        }
        
        return $errors;
    }
}