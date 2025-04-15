<?php
// Configuración de la base de datos
$db_config = [
    'host' => 'localhost',
    'dbname' => 'akaunting_php',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Intentar conectar a la base de datos
try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Definir PDO como variable global
    $GLOBALS['db'] = $pdo;
    
    return $pdo;
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}