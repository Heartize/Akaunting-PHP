<?php
$pageTitle = isset($filters['type']) ? 
    ($filters['type'] == 'income' ? 'Ingresos' : 
     ($filters['type'] == 'expense' ? 'Gastos' : 'Transacciones')) : 
    'Transacciones';

include __DIR__ . '/../layouts/header.php';
?>

<div class="card">
    <div class="filter-bar" id="filters-bar">
        <form action="" method="GET">
            <input type="hidden" name="page" value="transactions">
            <?php if (isset($filters['type'])): ?>
                <input type="hidden" name="type" value="<?php echo $filters['type']; ?>">
            <?php endif; ?>
            
            <div class="filter-item">
                <label class="filter-label">Cuenta</label>
                <select class="filter-control" name="account_id">
                    <option value="">Todas las cuentas</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>" <?php echo isset($_GET['account_id']) && $_GET['account_id'] == $account['id'] ? 'selected' : ''; ?>>
                            <?php echo e($account['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-item">
                <label class="filter-label">Categoría</label>
                <select class="filter-control" name="category_id">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categories as $category): ?>
                        <?php if (!isset($filters['type']) || $category['type'] == $filters['type']): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo isset($_GET['category_id']) && $_GET['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo e($category['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
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
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Categoría</th>
                    <th>Cuenta</th>
                    <th>Referencia</th>
                    <th>Monto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" align="center">No se encontraron transacciones</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo formatDate($transaction['date']); ?></td>
                            <td><?php echo e($transaction['description']); ?></td>
                            <td><?php echo e($transaction['category_name'] ?? '—'); ?></td>
                            <td><?php echo e($transaction['account_name']); ?></td>
                            <td><?php echo e($transaction['reference'] ?? '—'); ?></td>
                            <td style="color: <?php echo $transaction['type'] == 'income' ? 'var(--success-color)' : 'var(--danger-color)'; ?>">
                                <?php echo $transaction['type'] == 'income' ? '+' : '-'; ?> <?php echo formatAmount($transaction['amount']); ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo url('/index.php?page=transactions&action=show&id=' . $transaction['id']); ?>" class="action-btn" title="Ver">👁️</a>
                                    <a href="<?php echo url('/index.php?page=transactions&action=edit&id=' . $transaction['id']); ?>" class="action-btn" title="Editar">✏️</a>
                                    <button class="action-btn" title="Eliminar" onclick="confirmDelete('<?php echo url('/index.php?page=transactions&action=delete&id=' . $transaction['id']); ?>')">🗑️</button>
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
                <a href="<?php echo url('/index.php?page=transactions' . (isset($filters['type']) ? '&type=' . $filters['type'] : '') . '&page_num=' . ($page - 1)); ?>" class="pagination-btn">Anterior</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Anterior</button>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo url('/index.php?page=transactions' . (isset($filters['type']) ? '&type=' . $filters['type'] : '') . '&page_num=' . $i); ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?php echo url('/index.php?page=transactions' . (isset($filters['type']) ? '&type=' . $filters['type'] : '') . '&page_num=' . ($page + 1)); ?>" class="pagination-btn">Siguiente</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Siguiente</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>