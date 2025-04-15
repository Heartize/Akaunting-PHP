<?php
$pageTitle = 'Dashboard';
include __DIR__ . '/../layouts/header.php';
?>

<!-- Tarjetas de estadísticas principales -->
<div class="dashboard-header">
    <div class="stat-card">
        <div class="stat-icon icon-primary">💰</div>
        <div class="stat-details">
            <div class="stat-value"><?php echo formatAmount($stats['income'] ?? 0); ?></div>
            <div class="stat-label">Ingresos (mes)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-danger">💸</div>
        <div class="stat-details">
            <div class="stat-value"><?php echo formatAmount($stats['expense'] ?? 0); ?></div>
            <div class="stat-label">Gastos (mes)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-success">📈</div>
        <div class="stat-details">
            <div class="stat-value"><?php echo formatAmount(($stats['income'] ?? 0) - ($stats['expense'] ?? 0)); ?></div>
            <div class="stat-label">Beneficio</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-warning">⚠️</div>
        <div class="stat-details">
            <div class="stat-value"><?php echo $stats['overdue_invoices'] ?? 0; ?></div>
            <div class="stat-label">Facturas vencidas</div>
        </div>
    </div>
</div>

<div class="dashboard-cards">
    <div class="card">
        <div class="card-title">
            <span>Cuentas por cobrar</span>
            <div class="actions">
                <button>↻</button>
                <button>⋮</button>
            </div>
        </div>
        <div class="card-value"><?php echo formatAmount($stats['receivables'] ?? 0); ?></div>
        <div class="card-subtitle">Pendiente de cobro</div>
    </div>
    <div class="card">
        <div class="card-title">
            <span>Cuentas por pagar</span>
            <div class="actions">
                <button>↻</button>
                <button>⋮</button>
            </div>
        </div>
        <div class="card-value"><?php echo formatAmount($stats['payables'] ?? 0); ?></div>
        <div class="card-subtitle">Pendiente de pago</div>
    </div>
    <div class="card">
        <div class="card-title">
            <span>Inventario</span>
            <div class="actions">
                <button>↻</button>
                <button>⋮</button>
            </div>
        </div>
        <div class="card-value"><?php echo $stats['total_products'] ?? 0; ?></div>
        <div class="card-subtitle"><?php echo $stats['low_stock_products'] ?? 0; ?> productos con bajo stock</div>
    </div>
</div>

<div class="card">
    <div class="card-title">
        <span>Evolución financiera</span>
        <div class="actions">
            <button>↻</button>
            <button>⋮</button>
        </div>
    </div>
    <div class="chart"></div>
</div>

<div class="card">
    <div class="card-title">
        <span>Últimas facturas</span>
        <div class="actions">
            <a href="<?php echo url('/index.php?page=invoices'); ?>">Ver todas</a>
        </div>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Vencimiento</th>
                    <th>Monto</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentInvoices)): ?>
                    <tr>
                        <td colspan="6" align="center">No hay facturas recientes</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentInvoices as $invoice): ?>
                        <tr>
                            <td><?php echo e($invoice['invoice_number']); ?></td>
                            <td><?php echo e($invoice['client_name']); ?></td>
                            <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                            <td><?php echo formatDate($invoice['due_date']); ?></td>
                            <td><?php echo formatAmount($invoice['total']); ?></td>
                            <td><?php echo statusBadge($invoice['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="dashboard-cards">
    <div class="card">
        <div class="card-title">
            <span>Transacciones recientes</span>
            <div class="actions">
                <a href="<?php echo url('/index.php?page=transactions'); ?>">Ver todas</a>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Cuenta</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTransactions)): ?>
                        <tr>
                            <td colspan="4" align="center">No hay transacciones recientes</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentTransactions as $transaction): ?>
                            <tr>
                                <td><?php echo formatDate($transaction['date']); ?></td>
                                <td><?php echo e($transaction['description'] ?? ''); ?></td>
                                <td><?php echo e($transaction['account_name'] ?? ''); ?></td>
                                <td style="color: <?php echo $transaction['type'] == 'income' ? 'var(--success-color)' : 'var(--danger-color)'; ?>">
                                    <?php echo $transaction['type'] == 'income' ? '+' : '-'; ?> <?php echo formatAmount($transaction['amount']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-title">
            <span>Productos con bajo stock</span>
            <div class="actions">
                <a href="<?php echo url('/index.php?page=products'); ?>">Ver todos</a>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Stock actual</th>
                        <th>Stock mínimo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStockProducts)): ?>
                        <tr>
                            <td colspan="3" align="center">No hay productos con bajo stock</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><?php echo e($product['name']); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo $product['min_stock']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>