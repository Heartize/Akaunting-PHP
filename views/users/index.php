<?php
$pageTitle = 'Usuarios';
include __DIR__ . '/../layouts/header.php';
?>

<div class="card">
    <div class="filter-bar" id="filters-bar">
        <form action="" method="GET">
            <input type="hidden" name="page" value="users">
            
            <div class="filter-item">
                <label class="filter-label">Rol</label>
                <select class="filter-control" name="role">
                    <option value="">Todos los roles</option>
                    <option value="admin" <?php echo isset($_GET['role']) && $_GET['role'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="manager" <?php echo isset($_GET['role']) && $_GET['role'] == 'manager' ? 'selected' : ''; ?>>Gerente</option>
                    <option value="user" <?php echo isset($_GET['role']) && $_GET['role'] == 'user' ? 'selected' : ''; ?>>Usuario</option>
                </select>
            </div>
            
            <div class="filter-item">
                <label class="filter-label">Estado</label>
                <select class="filter-control" name="status">
                    <option value="">Todos los estados</option>
                    <option value="1" <?php echo isset($_GET['status']) && $_GET['status'] == '1' ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo isset($_GET['status']) && $_GET['status'] == '0' ? 'selected' : ''; ?>>Inactivo</option>
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
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Último acceso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" align="center">No se encontraron usuarios</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo e($user['name']); ?></td>
                            <td><?php echo e($user['email']); ?></td>
                            <td>
                                <?php
                                    $roleLabels = [
                                        'admin' => 'Administrador',
                                        'manager' => 'Gerente',
                                        'user' => 'Usuario'
                                    ];
                                    echo $roleLabels[$user['role']] ?? $user['role'];
                                ?>
                            </td>
                            <td><?php echo $user['active'] ? 'Activo' : 'Inactivo'; ?></td>
                            <td><?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Nunca'; ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo url('/index.php?page=users&action=edit&id=' . $user['id']); ?>" class="action-btn" title="Editar">✏️</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="action-btn" title="Eliminar" onclick="confirmDelete('<?php echo url('/index.php?page=users&action=delete&id=' . $user['id']); ?>')">🗑️</button>
                                    <?php endif; ?>
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
                <a href="<?php echo url('/index.php?page=users&page_num=' . ($page - 1)); ?>" class="pagination-btn">Anterior</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Anterior</button>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo url('/index.php?page=users&page_num=' . $i); ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?php echo url('/index.php?page=users&page_num=' . ($page + 1)); ?>" class="pagination-btn">Siguiente</a>
            <?php else: ?>
                <button class="pagination-btn" disabled>Siguiente</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>