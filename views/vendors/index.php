<?php
$pageTitle = 'Proveedores';
include __DIR__ . '/../layouts/header.php';
?>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>CIF/NIF</th>
                    <th>Moneda</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vendors)): ?>
                    <tr>
                        <td colspan="6" align="center">No se encontraron proveedores</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vendors as $vendor): ?>
                        <tr>
                            <td><?php echo e($vendor['name']); ?></td>
                            <td><?php echo e($vendor['email']); ?></td>
                            <td><?php echo e($vendor['phone']); ?></td>
                            <td><?php echo e($vendor['tax_number']); ?></td>
                            <td><?php echo e($vendor['currency']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo url('/index.php?page=vendors&action=show&id=' . $vendor['id']); ?>" class="action-btn" title="Ver">👁️</a>
                                    <a href="<?php echo url('/index.php?page=vendors&action=edit&id=' . $vendor['id']); ?>" class="action-btn" title="Editar">✏️</a>
                                    <button class="action-btn" title="Eliminar" onclick="confirmDelete('<?php echo url('/index.php?page=vendors&action=delete&id=' . $vendor['id']); ?>')">🗑️</button>
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
                <a href="<?php echo url('/index.php?page=vendors&page_num=' . ($page - 1)); ?>" class="pagination-btn">Anterior</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Anterior</button>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo url('/index.php?page=vendors&page_num=' . $i); ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?php echo url('/index.php?page=vendors&page_num=' . ($page + 1)); ?>" class="pagination-btn">Siguiente</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Siguiente</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>