<?php
// Script para verificar que todos los archivos requeridos existen

// Lista de directorios a verificar
$directories = [
    'assets/css',
    'assets/js',
    'config',
    'controllers',
    'database',
    'includes',
    'models',
    'views/dashboard',
    'views/clients',
    'views/invoices',
    'views/layouts',
    'views/products',
    'views/transactions',
    'uploads'
];

// Verificar directorios
echo "<h2>Verificando directorios...</h2>";
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "<p style='color:red'>Directorio faltante: {$dir}</p>";
        // Intentar crear el directorio
        mkdir($dir, 0755, true);
        echo "<p style='color:green'>Directorio creado: {$dir}</p>";
    } else {
        echo "<p style='color:green'>Directorio existe: {$dir}</p>";
    }
}

// Lista de archivos esenciales a verificar
$requiredFiles = [
    'config/config.php',
    'config/database.php',
    'includes/auth.php',
    'includes/functions.php',
    'includes/helpers.php',
    'controllers/DashboardController.php',
    'controllers/ClientController.php',
    'controllers/InvoiceController.php',
    'controllers/ProductController.php',
    'models/Client.php',
    'models/Invoice.php',
    'models/InvoiceItem.php',
    'models/Product.php',
    'models/Transaction.php',
    'views/layouts/header.php',
    'views/layouts/footer.php',
    'views/layouts/sidebar.php',
    'views/layouts/notifications.php',
    'views/dashboard/index.php',
    'index.php',
    '.htaccess'
];

// Verificar archivos
echo "<h2>Verificando archivos esenciales...</h2>";
$missingFiles = [];
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        echo "<p style='color:red'>Archivo faltante: {$file}</p>";
        $missingFiles[] = $file;
    } else {
        echo "<p style='color:green'>Archivo existe: {$file}</p>";
    }
}

// Resumen
echo "<h2>Resumen:</h2>";
if (empty($missingFiles)) {
    echo "<p style='color:green'>¡Todos los archivos esenciales están presentes!</p>";
} else {
    echo "<p style='color:red'>Faltan " . count($missingFiles) . " archivos esenciales.</p>";
    echo "<ul>";
    foreach ($missingFiles as $file) {
        echo "<li>{$file}</li>";
    }
    echo "</ul>";
}