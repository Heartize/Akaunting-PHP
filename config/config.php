<?php
// Configuración general
$config = [
    'site_name' => 'AkauntingPHP',
    'site_url' => 'http://localhost/akaunting-php',
    'timezone' => 'Europe/Madrid',
    'locale' => 'es_ES',
    'debug' => true,
    'upload_max_size' => 5 * 1024 * 1024, // 5MB
    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
    'default_pagination_limit' => 15,
    'currency' => 'EUR',
    'decimal_separator' => ',',
    'thousands_separator' => '.',
    'date_format' => 'd/m/Y',
    'tax_rate' => 21.00
];

// Definir constantes globales
define('APP_NAME', $config['site_name']);
define('APP_URL', $config['site_url']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ASSETS_URL', $config['site_url'] . '/assets');

// Configurar la zona horaria
date_default_timezone_set($config['timezone']);

// Mostrar u ocultar errores según configuración
if ($config['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// NOTA: No configuramos ni iniciamos sesión aquí, pues ya se inició en index.php

return $config;