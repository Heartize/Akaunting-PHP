<?php
/**
 * Punto de entrada principal para la aplicación
 */

// Activar mensajes de error para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión (IMPORTANTE: antes de cargar cualquier archivo que pueda modificar configuraciones de sesión)
session_start();

// Cargar configuración
$config = require_once __DIR__ . '/config/config.php';

// Cargar funciones útiles
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// Cargar base de datos
try {
    $db = require_once __DIR__ . '/config/database.php';
} catch (Exception $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Determinar la página requerida
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// Matriz de controladores permitidos y sus acciones
$controllers = [
    'dashboard' => 'DashboardController',
    'clients' => 'ClientController',
    'invoices' => 'InvoiceController',
    'products' => 'ProductController',
    'categories' => 'CategoryController',
    'vendors' => 'VendorController',
    'bills' => 'BillController',
    'transactions' => 'TransactionController',
    'accounts' => 'AccountController',
    'reports' => 'ReportController',
    'settings' => 'SettingController',
    'users' => 'UserController',
    'custom-fields' => 'CustomFieldController',
    'login' => 'AuthController',
    'register' => 'AuthController',
    'logout' => 'AuthController'
];

// Definir rutas públicas (no requieren autenticación)
$publicRoutes = ['login', 'register', 'logout'];

// Comprobar si la ruta requiere autenticación
if (!in_array($page, $publicRoutes)) {
    // Comentado temporalmente para depuración
    // requireLogin();
}

// Mapear la solicitud a un controlador
if (isset($controllers[$page])) {
    $controllerName = $controllers[$page];
    $controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';
    
    if (file_exists($controllerFile)) {
        // Cargar el controlador
        require_once $controllerFile;
        
        // Verificar que la clase exista
        if (class_exists($controllerName)) {
            // Crear una instancia del controlador
            $controller = new $controllerName($db);
            
            // Verificar si el método existe
            if (method_exists($controller, $action)) {
                // Llamar al método adecuado según la acción
                if ($id !== null) {
                    $controller->$action($id);
                } else {
                    $controller->$action();
                }
            } else {
                // Método no encontrado
                header("HTTP/1.0 404 Not Found");
                echo "Método '{$action}' no encontrado en el controlador '{$controllerName}'";
                // Si existe, incluir la página de error 404
                if (file_exists(__DIR__ . '/views/errors/404.php')) {
                    include(__DIR__ . '/views/errors/404.php');
                }
            }
        } else {
            // Clase del controlador no encontrada
            header("HTTP/1.0 404 Not Found");
            echo "Clase del controlador '{$controllerName}' no encontrada";
            // Si existe, incluir la página de error 404
            if (file_exists(__DIR__ . '/views/errors/404.php')) {
                include(__DIR__ . '/views/errors/404.php');
            }
        }
    } else {
        // Archivo del controlador no encontrado
        header("HTTP/1.0 404 Not Found");
        echo "Archivo del controlador '{$controllerFile}' no encontrado";
        // Si existe, incluir la página de error 404
        if (file_exists(__DIR__ . '/views/errors/404.php')) {
            include(__DIR__ . '/views/errors/404.php');
        }
    }
} else {
    // Página no encontrada
    header("HTTP/1.0 404 Not Found");
    echo "Página '{$page}' no encontrada";
    // Si existe, incluir la página de error 404
    if (file_exists(__DIR__ . '/views/errors/404.php')) {
        include(__DIR__ . '/views/errors/404.php');
    }
	exit;
}