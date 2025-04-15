<?php
// Determinar la página actual
$currentPage = $_GET['page'] ?? 'dashboard';
$currentSection = $_GET['section'] ?? '';
$currentType = $_GET['type'] ?? '';
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">AkauntingPHP</div>
    </div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=dashboard'); ?>" class="nav-link <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
                <i>📊</i> Dashboard
            </a>
        </li>
        
        <li class="nav-title">Ventas</li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=clients'); ?>" class="nav-link <?php echo $currentPage == 'clients' ? 'active' : ''; ?>">
                <i>👥</i> Clientes
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=invoices'); ?>" class="nav-link <?php echo $currentPage == 'invoices' ? 'active' : ''; ?>">
                <i>📃</i> Facturas
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=transactions&type=income'); ?>" class="nav-link <?php echo $currentPage == 'transactions' && $currentType == 'income' ? 'active' : ''; ?>">
                <i>💰</i> Ingresos
            </a>
        </li>
        
        <li class="nav-title">Compras</li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=vendors'); ?>" class="nav-link <?php echo $currentPage == 'vendors' ? 'active' : ''; ?>">
                <i>🏢</i> Proveedores
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=bills'); ?>" class="nav-link <?php echo $currentPage == 'bills' ? 'active' : ''; ?>">
                <i>🧾</i> Facturas de compra
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=transactions&type=expense'); ?>" class="nav-link <?php echo $currentPage == 'transactions' && $currentType == 'expense' ? 'active' : ''; ?>">
                <i>💸</i> Gastos
            </a>
        </li>
        
        <li class="nav-title">Inventario</li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=products'); ?>" class="nav-link <?php echo $currentPage == 'products' ? 'active' : ''; ?>">
                <i>📦</i> Productos
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=categories'); ?>" class="nav-link <?php echo $currentPage == 'categories' ? 'active' : ''; ?>">
                <i>🏷️</i> Categorías
            </a>
        </li>
        
        <li class="nav-title">Contabilidad</li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=accounts'); ?>" class="nav-link <?php echo $currentPage == 'accounts' ? 'active' : ''; ?>">
                <i>🏦</i> Cuentas
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=transactions'); ?>" class="nav-link <?php echo $currentPage == 'transactions' && empty($currentType) ? 'active' : ''; ?>">
                <i>📒</i> Transacciones
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=reconciliations'); ?>" class="nav-link <?php echo $currentPage == 'reconciliations' ? 'active' : ''; ?>">
                <i>🔄</i> Conciliaciones
            </a>
        </li>
        
        <li class="nav-title">Informes</li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=reports&type=income'); ?>" class="nav-link <?php echo $currentPage == 'reports' && $currentType == 'income' ? 'active' : ''; ?>">
                <i>📈</i> Ingresos
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=reports&type=expense'); ?>" class="nav-link <?php echo $currentPage == 'reports' && $currentType == 'expense' ? 'active' : ''; ?>">
                <i>📉</i> Gastos
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=reports&type=tax'); ?>" class="nav-link <?php echo $currentPage == 'reports' && $currentType == 'tax' ? 'active' : ''; ?>">
                <i>📊</i> Impuestos
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=reports&type=profit-loss'); ?>" class="nav-link <?php echo $currentPage == 'reports' && $currentType == 'profit-loss' ? 'active' : ''; ?>">
                <i>💹</i> Beneficios
            </a>
        </li>
        
        <li class="nav-title">Configuración</li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=settings&section=company'); ?>" class="nav-link <?php echo $currentPage == 'settings' && $currentSection == 'company' ? 'active' : ''; ?>">
                <i>🏢</i> Empresa
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=users'); ?>" class="nav-link <?php echo $currentPage == 'users' ? 'active' : ''; ?>">
                <i>👤</i> Usuarios
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo url('/index.php?page=custom-fields'); ?>" class="nav-link <?php echo $currentPage == 'custom-fields' ? 'active' : ''; ?>">
                <i>🔧</i> Campos personalizados
            </a>
        </li>
        
        <?php if (isLoggedIn()): ?>
            <li class="nav-item" style="margin-top: 20px;">
                <a href="<?php echo url('/index.php?page=logout'); ?>" class="nav-link">
                    <i>🚪</i> Cerrar sesión
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>