<?php
// Script para crear directorios y archivos de vistas faltantes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Creación de vistas faltantes</h1>";

// Directorios de vistas necesarios
$viewDirs = [
    'clients',
    'dashboard',
    'errors',
    'invoices',
    'products',
    'transactions',
    'layouts'
];

// Archivos de vistas por directorio
$viewFiles = [
    'clients' => ['index.php', 'form.php', 'show.php'],
    'dashboard' => ['index.php'],
    'errors' => ['404.php'],
    'invoices' => ['index.php', 'form.php', 'show.php'],
    'products' => ['index.php', 'form.php', 'show.php'],
    'transactions' => ['index.php', 'form.php', 'show.php'],
    'layouts' => ['header.php', 'footer.php', 'sidebar.php', 'notifications.php']
];

// Crear directorios faltantes
$viewsDir = __DIR__ . '/views';
if (!is_dir($viewsDir)) {
    mkdir($viewsDir, 0755);
    echo "<p style='color:green'>✅ Directorio principal de vistas creado</p>";
}

foreach ($viewDirs as $dir) {
    $path = $viewsDir . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755);
        echo "<p style='color:green'>✅ Directorio creado: {$dir}</p>";
    } else {
        echo "<p>Directorio ya existe: {$dir}</p>";
    }
}

// Crear archivos de vistas faltantes
$created = 0;
foreach ($viewFiles as $dir => $files) {
    foreach ($files as $file) {
        $path = $viewsDir . '/' . $dir . '/' . $file;
        if (!file_exists($path)) {
            // Crear un archivo básico de vista
            $content = "<?php\n// Vista {$dir}/{$file}\n";
            
            if ($file === 'index.php') {
                $content .= "\$pageTitle = '" . ucfirst($dir) . "';\n";
                $content .= "include __DIR__ . '/../layouts/header.php';\n?>\n\n";
                $content .= "<div class='card'>\n  <h2>" . ucfirst($dir) . "</h2>\n  <p>Contenido pendiente.</p>\n</div>\n\n";
                $content .= "<?php include __DIR__ . '/../layouts/footer.php'; ?>\n";
            } elseif ($file === 'form.php') {
                $content .= "\$isEditing = isset(\${$dir});\n";
                $content .= "\$pageTitle = \$isEditing ? 'Editar " . rtrim(ucfirst($dir), 's') . "' : 'Nuevo " . rtrim(ucfirst($dir), 's') . "';\n";
                $content .= "include __DIR__ . '/../layouts/header.php';\n?>\n\n";
                $content .= "<div class='card'>\n  <h2><?php echo \$pageTitle; ?></h2>\n  <form method='post'>\n    <!-- Formulario pendiente -->\n  </form>\n</div>\n\n";
                $content .= "<?php include __DIR__ . '/../layouts/footer.php'; ?>\n";
            } elseif ($file === 'show.php') {
                $content .= "\$pageTitle = 'Detalles de " . rtrim(ucfirst($dir), 's') . "';\n";
                $content .= "include __DIR__ . '/../layouts/header.php';\n?>\n\n";
                $content .= "<div class='card'>\n  <h2><?php echo \$pageTitle; ?></h2>\n  <!-- Detalles pendientes -->\n</div>\n\n";
                $content .= "<?php include __DIR__ . '/../layouts/footer.php'; ?>\n";
            } elseif ($file === '404.php') {
                $content .= "\$pageTitle = 'Página no encontrada';\n";
                $content .= "include __DIR__ . '/../layouts/header.php';\n?>\n\n";
                $content .= "<div class='card'>\n  <h2>Error 404</h2>\n  <p>La página que estás buscando no existe.</p>\n  <a href='<?php echo url('/'); ?>' class='btn btn-primary'>Volver al inicio</a>\n</div>\n\n";
                $content .= "<?php include __DIR__ . '/../layouts/footer.php'; ?>\n";
            }
            
            file_put_contents($path, $content);
            $created++;
            echo "<p style='color:green'>✅ Archivo creado: {$dir}/{$file}</p>";
        } else {
            echo "<p>Archivo ya existe: {$dir}/{$file}</p>";
        }
    }
}

echo "<h2>Resumen</h2>";
echo "<p>Total de archivos creados: {$created}</p>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";