<?php
$pageTitle = 'Facturas de Compra';
include __DIR__ . '/../layouts/header.php';
?>

<div class="card">
    <div class="filter-bar" id="filters-bar">
        <form action="" method="GET">
            <input type="hidden" name="page" value="bills">
            
            <div class="filter-item">
                <label class="filter-label">Proveedor</label>
                <select class="filter-control" name="vendor_id">
                    <option value="">Todos los proveedores</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo $vendor['id']; ?>" <?php echo isset($_GET['vendor_id']) && $_GET['vendor_id'] == $vendor['id'] ? 'selected' : ''; ?>>
                            <?php echo e($vendor['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-item">
                <label class="filter-label">Estado</label>
                <select class="filter-control" name="status">
                    <option value="">Todos los estados</option>
                    <option value="draft" <?php echo isset($_GET['status']) && $_GET['status'] == 'draft' ? 'selected' : ''; ?>>Borrador</option>
                    <option value="received" <?php echo isset($_GET['status']) && $_GET['status'] == 'received' ? 'selected' : ''; ?>>Recibida</option>
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
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Proveedor</th>
                    <th>Fecha</th>
                    <th>Vencimiento</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bills)): ?>
                    <tr>
                        <td colspan="7" align="center">No se encontraron facturas de compra</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td><?php echo e($bill['bill_number']); ?></td>
                            <td><?php echo e($bill['vendor_name']); ?></td>
                            <td><?php echo formatDate($bill['bill_date']); ?></td>
                            <td><?php echo formatDate($bill['due_date']); ?></td>
                            <td><?php echo formatAmount($bill['total']); ?></td>
                            <td>
                                <?php
                                    $statusLabels = [
                                        'draft' => 'Borrador',
                                        'received' => 'Recibida',
                                        'paid' => 'Pagada',
                                        'overdue' => 'Vencida',
                                        'cancelled' => 'Cancelada'
                                    ];
                                    $statusClass = [
                                        'draft' => 'badge-info',
                                        'received' => 'badge-primary',
                                        'paid' => 'badge-success',
                                        'overdue' => 'badge-danger',
                                        'cancelled' => 'badge-secondary'
                                    ];
                                    $status = $bill['status'];
                                    $label = $statusLabels[$status] ?? $status;
                                    $class = $statusClass[$status] ?? 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $class; ?>"><?php echo $label; ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo url('/index.php?page=bills&action=show&id=' . $bill['id']); ?>" class="action-btn" title="Ver">👁️</a>
                                    <a href="<?php echo url('/index.php?page=bills&action=edit&id=' . $bill['id']); ?>" class="action-btn" title="Editar">✏️</a>
                                    <button class="action-btn" title="Eliminar" onclick="confirmDelete('<?php echo url('/index.php?page=bills&action=delete&id=' . $bill['id']); ?>')">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo url('/index.php?page=bills&page_num=' . ($page - 1)); ?>" class="pagination-btn">Anterior</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Anterior</button>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo url('/index.php?page=bills&page_num=' . $i); ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?php echo url('/index.php?page=bills&page_num=' . ($page + 1)); ?>" class="pagination-btn">Siguiente</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Siguiente</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>