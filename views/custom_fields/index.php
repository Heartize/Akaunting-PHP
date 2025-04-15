<?php
$pageTitle = 'Campos Personalizados';
include __DIR__ . '/../layouts/header.php';
?>

<div class="card">
    <div class="filter-bar" id="filters-bar">
        <form action="" method="GET">
            <input type="hidden" name="page" value="custom-fields">
            
            <div class="filter-item">
                <label class="filter-label">Módulo</label>
                <select class="filter-control" name="module">
                    <option value="">Todos los módulos</option>
                    <?php foreach ($modules as $key => $name): ?>
                        <option value="<?php echo $key; ?>" <?php echo isset($_GET['module']) && $_GET['module'] == $key ? 'selected' : ''; ?>>
                            <?php echo e($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                    <th>Nombre</th>
                    <th>Módulo</th>
                    <th>Tipo</th>
                    <th>Requerido</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customFields)): ?>
                    <tr>
                        <td colspan="6" align="center">No se encontraron campos personalizados</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customFields as $field): ?>
                        <tr>
                            <td><?php echo e($field['name']); ?></td>
                            <td>
                                <?php
                                    $moduleLabels = [
                                        'clients' => 'Clientes',
                                        'vendors' => 'Proveedores',
                                        'products' => 'Productos',
                                        'invoices' => 'Facturas',
                                        'bills' => 'Facturas de compra',
                                        'transactions' => 'Transacciones'
                                    ];
                                    echo $moduleLabels[$field['module']] ?? $field['module'];
                                ?>
                            </td>
                            <td>
                                <?php
                                    $typeLabels = [
                                        'text' => 'Texto',
                                        'textarea' => 'Área de texto',
                                        'number' => 'Número',
                                        'date' => 'Fecha',
                                        'select' => 'Lista desplegable'
                                    ];
                                    echo $typeLabels[$field['type']] ?? $field['type'];
                                ?>
                            </td>
                            <td><?php echo $field['required'] ? 'Sí' : 'No'; ?></td>
                            <td><?php echo $field['enabled'] ? 'Activo' : 'Inactivo'; ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo url('/index.php?page=custom-fields&action=edit&id=' . $field['id']); ?>" class="action-btn" title="Editar">✏️</a>
                                    <button class="action-btn" title="Eliminar" onclick="confirmDelete('<?php echo url('/index.php?page=custom-fields&action=delete&id=' . $field['id']); ?>')">🗑️</button>
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
                <a href="<?php echo url('/index.php?page=custom-fields&page_num=' . ($page - 1)); ?>" class="pagination-btn">Anterior</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Anterior</button>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo url('/index.php?page=custom-fields&page_num=' . $i); ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?php echo url('/index.php?page=custom-fields&page_num=' . ($page + 1)); ?>" class="pagination-btn">Siguiente</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Siguiente</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>