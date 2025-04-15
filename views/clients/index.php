<?php
$pageTitle = 'Clientes';
$includeSearch = true;
$actionButton = 'Nuevo Cliente';
$actionButtonUrl = url('/index.php?page=clients&action=create');

$breadcrumbs = [
    'Clientes' => null
];

include __DIR__ . '/../layouts/header.php';
?>

<!-- Filtros -->
<button class="filters-toggle" id="toggle-filters">
    <span>Mostrar filtros</span> 🔍
</button>

<div class="filter-bar" id="filters-bar" style="display: none;">
    <form action="" method="GET">
        <input type="hidden" name="page" value="clients">
        
        <div class="filter-item">
            <label class="filter-label">Nombre/Empresa</label>
            <input type="text" class="filter-control" name="search" placeholder="Buscar por nombre" value="<?php echo $_GET['search'] ?? ''; ?>">
        </div>
        
        <div class="filter-item">
            <label class="filter-label"></label>
            <button type="submit" class="btn btn-primary" style="margin-top: 5px; width: 100%;">Filtrar</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>NIF/CIF</th>
                    <th>Crédito</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="7" align="center">No se encontraron clientes</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?php echo e($client['id']); ?></td>
                            <td><?php echo e($client['name']); ?></td>
                            <td><?php echo e($client['email']); ?></td>
                            <td><?php echo e($client['phone']); ?></td>
                            <td><?php echo e($client['tax_number']); ?></td>
                            <td><?php echo formatAmount($client['credit_limit']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo url('/index.php?page=clients&action=show&id=' . $client['id']); ?>" class="action-btn" title="Ver">👁️</a>
                                    <a href="<?php echo url('/index.php?page=clients&action=edit&id=' . $client['id']); ?>" class="action-btn" title="Editar">✏️</a>
                                    <button class="action-btn" title="Eliminar" onclick="confirmDelete('<?php echo url('/index.php?page=clients&action=delete&id=' . $client['id']); ?>')">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo url('/index.php?page=clients&page_num=' . ($page - 1) . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '')); ?>" class="pagination-btn">Anterior</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Anterior</button>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo url('/index.php?page=clients&page_num=' . $i . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '')); ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?php echo url('/index.php?page=clients&page_num=' . ($page + 1) . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '')); ?>" class="pagination-btn">Siguiente</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Siguiente</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Filtros toggle
    document.getElementById('toggle-filters').addEventListener('click', function() {
        const filtersBar = document.getElementById('filters-bar');
        
        if (filtersBar.style.display === 'none') {
            filtersBar.style.display = 'flex';
            this.querySelector('span').textContent = 'Ocultar filtros';
        } else {
            filtersBar.style.display = 'none';
            this.querySelector('span').textContent = 'Mostrar filtros';
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>