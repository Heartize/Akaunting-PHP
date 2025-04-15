<?php
/**
 * Funciones de autenticación
 */

if (!function_exists('login')) {
    /**
     * Iniciar sesión de usuario
     */
    function login($email, $password) {
        global $db;
        
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND active = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Iniciar sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Registrar último login
                $updateStmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                return true;
            }
        } catch (Exception $e) {
            logMessage('error', 'Error en login: ' . $e->getMessage());
        }
        
        return false;
    }
}

if (!function_exists('logout')) {
    /**
     * Cerrar sesión de usuario
     */
    function logout() {
        // Eliminar todas las variables de sesión
        $_SESSION = [];
        
        // Si se utiliza una cookie para la sesión, eliminarla
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
    }
}

if (!function_exists('hasRole')) {
    /**
     * Verificar si el usuario tiene el rol requerido
     */
    function hasRole($requiredRole) {
        if (!isLoggedIn()) {
            return false;
        }
        
        if ($requiredRole === 'any') {
            return true;
        }
        
        $userRole = $_SESSION['user_role'] ?? '';
        
        if ($requiredRole === 'admin' && $userRole === 'admin') {
            return true;
        }
        
        if ($requiredRole === 'manager' && in_array($userRole, ['admin', 'manager'])) {
            return true;
        }
        
        return false;
    }
}

if (!function_exists('requireRole')) {
    /**
     * Requerir un rol específico para acceder a una página
     */
    function requireRole($requiredRole) {
        if (!hasRole($requiredRole)) {
            setFlashMessage('error', 'No tiene permiso para acceder a esta página');
            redirect('/index.php?page=dashboard');
        }
    }
}