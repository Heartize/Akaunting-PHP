<?php
// debug.php - Utilidad de diagnóstico para el proyecto
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico del Sistema AkauntingPHP</h1>";

// Verificar versión de PHP
echo "<h2>Versión de PHP</h2>";
echo "<p>Versión actual: " . phpversion() . "</p>";
echo "<p>Se recomienda PHP 7.4 o superior</p>";

// Verificar extensiones requeridas
echo "<h2>Extensiones PHP</h2>";
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
echo "<ul>";
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li style='color:green'>✓ {$ext} está instalada</li>";
    } else {
        echo "<li style='color:red'>✗ {$ext} NO está instalada</li>";
    }
}
echo "</ul>";

// Verificar permisos de archivos
echo "<h2>Permisos de directorios</h2>";
$directories = [
    'uploads' => 0755,
    'logs' => 0755
];

echo "<ul>";
foreach ($directories as $dir => $permission) {
    if (file_exists($dir)) {
        $currentPerms = substr(sprintf('%o', fileperms($dir)), -4);
        if (octdec($currentPerms) >= octdec(decoct($permission))) {
            echo "<li style='color:green'>✓ {$dir} tiene permisos correctos ({$currentPerms})</li>";
        } else {
            echo "<li style='color:red'>✗ {$dir} NO tiene permisos suficientes (actual: {$currentPerms}, requerido: " . decoct($permission) . ")</li>";
        }
    } else {
        echo "<li style='color:orange'>⚠ {$dir} no existe. Intente crearlo con permisos " . decoct($permission) . "</li>";
    }
}
echo "</ul>";

// Verificar conexión a base de datos
echo "<h2>Conexión a la base de datos</h2>";
if (file_exists('config/database.php')) {
    try {
        $db = require_once 'config/database.php';
        echo "<p style='color:green'>✓ Conexión exitosa a la base de datos</p>";
        
        // Verificar tablas principales
        $mainTables = ['users', 'clients', 'products', 'invoices', 'transactions'];
        echo "<h3>Tablas principales</h3><ul>";
        
        foreach ($mainTables as $table) {
            try {
                $query = $db->query("SELECT 1 FROM {$table} LIMIT 1");
                echo "<li style='color:green'>✓ Tabla {$table} existe</li>";
            } catch (PDOException $e) {
                echo "<li style='color:red'>✗ Tabla {$table} NO existe o hay un problema: " . $e->getMessage() . "</li>";
            }
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Error de conexión a la base de datos: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>✗ El archivo config/database.php no existe</p>";
}

// Verificar funciones clave
echo "<h2>Funciones principales</h2>";
echo "<ul>";
$coreFunctions = ['url', 'formatAmount', 'formatDate', 'isLoggedIn', 'e'];

foreach ($coreFunctions as $function) {
    if (function_exists($function)) {
        echo "<li style='color:green'>✓ La función {$function}() existe</li>";
    } else {
        echo "<li style='color:red'>✗ La función {$function}() NO está definida</li>";
    }
}
echo "</ul>";

echo "<hr><p>Complete este diagnóstico antes de continuar con la solución de problemas.</p>";