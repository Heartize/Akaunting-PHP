<?php
$pageTitle = 'Facturas';
$includeSearch = true;
$actionButton = 'Nueva Factura';
$actionButtonUrl = url('/index.php?page=invoices&action=create');

$breadcrumbs = [
    'Facturas' => null
];

include __DIR__ . '/../layouts/header.php';
?>

<!-- Filtros -->
<button class="filters-toggle" id="toggle-filters">
    <span>Mostrar filtros</span> 🔍
</button>

<div class="filter-bar" id="filters-bar" style="display: none;">
    <form action="" method="GET">
        <input type="hidden" name="page" value="invoices">
        
        <div class="filter-item">
            <label class="filter-label">Cliente</label>
            <select class="filter-control" name="client_id">
                <option value="">Todos los clientes</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['id']; ?>" <?php echo isset($_GET['client_id']) && $_GET['client_id'] == $client['id'] ? 'selected' : ''; ?>>
                        <?php echo e($client['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-item">
            <label class="filter-label">Estado</label>
            <select class="filter-control" name="status">
                <option value="">Todos los estados</option>
                <option value="draft" <?php echo isset($_GET['status']) && $_GET['status'] == 'draft' ? 'selected' : ''; ?>>Borrador</option>
                <option value="sent" <?php echo isset($_GET['status']) && $_GET['status'] == 'sent' ? 'selected' : ''; ?>>Enviada</option>
                <option value="paid" <?php echo isset($_GET['status']) && $_GET['status'] == 'paid' ? 'selected' : ''; ?>>Pagada</option>
                <option value="overdue" <?php echo isset($_GET['status']) && $_GET['status'] == 'overdue' ? 'selected' : ''; ?>>Vencida</option>
                <option value="cancelled" <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelada</option>
            </select>
        </div>
        
        <div class="filter-item">
            <label class="filter-label">Desde</label>
            <input type="date" class="filter-control" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>">
        </div>
        
        <div class="filter-item">
            <label class="filter-label">Hasta</label>
            <input type="date" class="filter-control" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>">
        </div>
        
        <div class="filter-item">
            <label class="filter-label">Buscar</label>
            <input type="text" class="filter-control" name="search" placeholder="Número de factura, concepto..." value="<?php echo $_GET['search'] ?? ''; ?>">
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
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Vencimiento</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="7" align="center">No se encontraron facturas</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><?php echo e($invoice['invoice_number']); ?></td>
                            <td><?php echo e($invoice['client_name']); ?></td>
                            <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                            <td><?php echo formatDate($invoice['due_date']); ?></td>
                            <td><?php echo formatAmount($invoice['total']); ?></td>
                            <td><?php echo statusBadge($invoice['status']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo url('/index.php?page=invoices&action=show&id=' . $invoice['id']); ?>" class="action-btn" title="Ver">👁️</a>
                                    <a href="<?php echo url('/index.php?page=invoices&action=edit&id=' . $invoice['id']); ?>" class="action-btn" title="Editar">✏️</a>
                                    <button class="action-btn" title="Eliminar" onclick="confirmDelete('<?php echo url('/index.php?page=invoices&action=delete&id=' . $invoice['id']); ?>')">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo url('/index.php?page=invoices&page_num=' . ($page - 1)); ?>" class="pagination-btn">Anterior</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Anterior</button>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo url('/index.php?page=invoices&page_num=' . $i); ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?php echo url('/index.php?page=invoices&page_num=' . ($page + 1)); ?>" class="pagination-btn">Siguiente</a>
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