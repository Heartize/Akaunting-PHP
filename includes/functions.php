<?php
/**
 * Funciones globales para la aplicación
 */

if (!function_exists('url')) {
    // Generar URL para enlace
    function url($path = '') {
        $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $baseUrl .= "://" . $_SERVER['HTTP_HOST'];
        $baseDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($baseDir == '/' || $baseDir == '\\') {
            $baseDir = '';
        }
        return $baseUrl . $baseDir . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    // Generar URL para assets
    function asset($path) {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('formatAmount')) {
    // Formatear importes con moneda
    function formatAmount($amount, $includeCurrency = true, $format = true) {
        global $config;
        
        // Valores predeterminados
        $currency = 'EUR';
        $decimalSeparator = ',';
        $thousandsSeparator = '.';
        
        // Usar configuración si está disponible
        if (isset($config) && is_array($config)) {
            $currency = $config['currency'] ?? 'EUR';
            $decimalSeparator = $config['decimal_separator'] ?? ',';
            $thousandsSeparator = $config['thousands_separator'] ?? '.';
        }
        
        if ($format) {
            $formatted = number_format((float)$amount, 2, $decimalSeparator, $thousandsSeparator);
        } else {
            $formatted = $amount;
        }
        
        if ($includeCurrency) {
            switch ($currency) {
                case 'EUR':
                    return $formatted . ' €';
                case 'USD':
                    return '$' . $formatted;
                case 'GBP':
                    return '£' . $formatted;
                default:
                    return $currency . ' ' . $formatted;
            }
        }
        
        return $formatted;
    }
}

if (!function_exists('formatDate')) {
    // Formatear fechas
    function formatDate($date, $format = null) {
        global $config;
        
        // Formato predeterminado
        $dateFormat = 'd/m/Y';
        
        // Usar configuración si está disponible
        if (isset($config) && is_array($config)) {
            $dateFormat = $format ?? $config['date_format'] ?? 'd/m/Y';
        }
        
        if ($date === null) {
            return '';
        }
        
        if (is_string($date)) {
            try {
                $date = new DateTime($date);
            } catch (Exception $e) {
                return $date; // Devolver el string original si hay error
            }
        }
        
        return $date->format($dateFormat);
    }
}

if (!function_exists('e')) {
    // Escapar HTML
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('isLoggedIn')) {
    // Verificar si el usuario está autenticado
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('requireLogin')) {
    // Requerir autenticación para acceder a una página
    function requireLogin() {
        if (!isLoggedIn()) {
            setFlashMessage('error', 'Debe iniciar sesión para acceder a esta página');
            redirect('/login.php');
            exit;
        }
    }
}

if (!function_exists('redirect')) {
    // Redireccionar
    function redirect($path) {
        header("Location: " . url($path));
        exit;
    }
}

if (!function_exists('setFlashMessage')) {
    // Generar un mensaje flash
    function setFlashMessage($type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('getFlashMessage')) {
    // Obtener y eliminar un mensaje flash
    function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $flash;
        }
        return null;
    }
}

if (!function_exists('showFlashMessages')) {
    // Mostrar notificaciones flash
    function showFlashMessages() {
        $flashMessage = getFlashMessage();
        
        if ($flashMessage) {
            $type = $flashMessage['type'];
            $message = $flashMessage['message'];
            
            $icon = '';
            switch ($type) {
                case 'success':
                    $icon = '✅';
                    break;
                case 'error':
                    $icon = '❌';
                    break;
                case 'warning':
                    $icon = '⚠️';
                    break;
                case 'info':
                    $icon = 'ℹ️';
                    break;
            }
            
            echo '<div class="notification notification-' . $type . '">';
            echo '<span class="notification-icon">' . $icon . '</span>';
            echo '<span class="notification-message">' . $message . '</span>';
            echo '<span class="notification-close" onclick="this.parentElement.remove()">×</span>';
            echo '</div>';
        }
    }
}

if (!function_exists('generateCsrfToken')) {
    // Generar un token CSRF
    function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    // Verificar un token CSRF
    function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            setFlashMessage('error', 'Error de validación de formulario. Por favor, inténtelo de nuevo.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}

if (!function_exists('getSettings')) {
    // Obtener configuración de la empresa
    function getSettings() {
        global $db;
        
        try {
            $stmt = $db->query("SELECT * FROM settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                // Si no hay configuración, crear valores por defecto
                $stmt = $db->prepare("
                    INSERT INTO settings 
                    (company_name, company_tax_number, currency, decimal_separator, thousands_separator, date_format, tax_rate) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    'Mi Empresa S.L.',
                    'B12345678',
                    'EUR',
                    ',',
                    '.',
                    'd/m/Y',
                    21.00
                ]);
                
                $settings = [
                    'company_name' => 'Mi Empresa S.L.',
                    'company_tax_number' => 'B12345678',
                    'currency' => 'EUR',
                    'decimal_separator' => ',',
                    'thousands_separator' => '.',
                    'date_format' => 'd/m/Y',
                    'tax_rate' => 21.00
                ];
            }
            
            return $settings;
        } catch (Exception $e) {
            // Si hay un error, devolver configuración predeterminada
            return [
                'company_name' => 'Mi Empresa S.L.',
                'company_tax_number' => 'B12345678',
                'currency' => 'EUR',
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'date_format' => 'd/m/Y',
                'tax_rate' => 21.00
            ];
        }
    }
}

if (!function_exists('getCustomFields')) {
    // Obtener campos personalizados de un módulo
    function getCustomFields($module) {
        global $db;
        
        try {
            $stmt = $db->prepare("SELECT * FROM custom_fields WHERE module = ? AND enabled = 1 ORDER BY position");
            $stmt->execute([$module]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // En caso de error, devolver array vacío
            return [];
        }
    }
}

if (!function_exists('getCustomFieldValues')) {
    // Obtener valores de campos personalizados
    function getCustomFieldValues($module, $modelId) {
        global $db;
        
        try {
            $stmt = $db->prepare("
                SELECT cfv.*, cf.name, cf.type 
                FROM custom_field_values cfv
                JOIN custom_fields cf ON cfv.field_id = cf.id
                WHERE cfv.module = ? AND cfv.model_id = ?
            ");
            $stmt->execute([$module, $modelId]);
            
            $values = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $values[$row['field_id']] = $row['value'];
            }
            
            return $values;
        } catch (Exception $e) {
            // En caso de error, devolver array vacío
            return [];
        }
    }
}

if (!function_exists('statusBadge')) {
    // Generar badge según estado
    function statusBadge($status) {
        $class = '';
        $label = $status;
        
        switch ($status) {
            case 'draft':
                $class = 'badge-info';
                $label = 'Borrador';
                break;
            case 'sent':
                $class = 'badge-primary';
                $label = 'Enviada';
                break;
            case 'paid':
                $class = 'badge-success';
                $label = 'Pagada';
                break;
            case 'overdue':
                $class = 'badge-danger';
                $label = 'Vencida';
                break;
            case 'cancelled':
                $class = 'badge-secondary';
                $label = 'Cancelada';
                break;
            case 'Pagada':
                $class = 'badge-success';
                break;
            case 'Pendiente':
                $class = 'badge-warning';
                break;
            case 'Vencida':
                $class = 'badge-danger';
                break;
        }
        
        return '<span class="badge ' . $class . '">' . $label . '</span>';
    }
}

if (!function_exists('logMessage')) {
    // Registrar en log
    function logMessage($level, $message, $context = []) {
        $logDir = __DIR__ . '/../logs';
        
        // Crear directorio si no existe
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
        
        $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $message;
        
        if (!empty($context)) {
            $logEntry .= ' ' . json_encode($context);
        }
        
        $logEntry .= PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}