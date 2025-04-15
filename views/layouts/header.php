<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? APP_NAME; ?></title>
    
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/styles.css'); ?>">
    
    <!-- JavaScript principal -->
    <script src="<?php echo asset('js/app.js'); ?>" defer></script>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/notifications.php'; ?>
            
            <div class="header">
                <div class="header-left">
                    <button class="toggle-sidebar" id="toggle-sidebar">☰</button>
                    <h1 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                </div>
                <div class="header-actions">
                    <?php if (isset($includeSearch) && $includeSearch): ?>
                        <div class="search-bar">
                            <span class="search-icon">🔍</span>
                            <form action="" method="GET">
                                <input type="hidden" name="page" value="<?php echo $_GET['page'] ?? 'dashboard'; ?>">
                                <input type="text" class="search-input" name="search" placeholder="Buscar..." value="<?php echo $_GET['search'] ?? ''; ?>">
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline" id="theme-toggle">
                        🌙 / ☀️
                    </button>
                    
                    <?php if (isset($actionButton)): ?>
                        <a href="<?php echo $actionButtonUrl; ?>" class="btn btn-primary"><?php echo $actionButton; ?></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
                <div class="breadcrumb">
                    <div class="breadcrumb-item">
                        <a href="<?php echo url('/'); ?>" class="breadcrumb-link">Dashboard</a>
                    </div>
                    <?php foreach ($breadcrumbs as $title => $url): ?>
                        <div class="breadcrumb-item">
                            <?php if ($url): ?>
                                <a href="<?php echo $url; ?>" class="breadcrumb-link"><?php echo $title; ?></a>
                            <?php else: ?>
                                <a href="#" class="breadcrumb-link active"><?php echo $title; ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>