<?php
/**
 * Controlador para el dashboard
 */
class DashboardController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Página principal del dashboard
     */
    public function index() {
        // Verificar autenticación (comentado temporalmente para depuración)
        // requireLogin();
        
        // Inicializar estadísticas con valores predeterminados
        $stats = [
            'income' => 0,
            'expense' => 0,
            'profit' => 0,
            'profit_percent' => 0,
            'overdue_invoices' => 0,
            'receivables' => 0,
            'payables' => 0,
            'total_products' => 0,
            'low_stock_products' => 0
        ];
        
        // Obtener estadísticas reales si es posible
        $stats = $this->getStats();
        
        // Inicializar variables para evitar errores
        $recentInvoices = [];
        $recentTransactions = [];
        $lowStockProducts = [];
        
        // Intentar obtener facturas recientes
        try {
            $stmt = $this->db->query("
                SELECT i.*, c.name as client_name
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                ORDER BY i.invoice_date DESC
                LIMIT 5
            ");
            $recentInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logMessage('error', 'Error al obtener facturas recientes: ' . $e->getMessage());
        }
        
        // Intentar obtener transacciones recientes
        try {
            $stmt = $this->db->query("
                SELECT t.*, a.name as account_name
                FROM transactions t
                JOIN accounts a ON t.account_id = a.id
                ORDER BY t.date DESC
                LIMIT 4
            ");
            $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logMessage('error', 'Error al obtener transacciones recientes: ' . $e->getMessage());
        }
        
        // Intentar obtener productos con bajo stock
        try {
            $stmt = $this->db->query("
                SELECT *
                FROM products
                WHERE stock <= min_stock AND min_stock > 0
                ORDER BY (stock / min_stock)
                LIMIT 5
            ");
            $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logMessage('error', 'Error al obtener productos con bajo stock: ' . $e->getMessage());
        }
        
        // Cargar la vista
        include __DIR__ . '/../views/dashboard/index.php';
    }
    
    /**
     * Obtener estadísticas para el dashboard
     */
    private function getStats() {
        $stats = [
            'income' => 0,
            'expense' => 0,
            'profit' => 0,
            'profit_percent' => 0,
            'overdue_invoices' => 0,
            'receivables' => 0,
            'payables' => 0,
            'total_products' => 0,
            'low_stock_products' => 0
        ];
        
        try {
            // Primer día del mes actual
            $firstDayOfMonth = date('Y-m-01');
            
            // Ingresos del mes
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM transactions 
                WHERE type = 'income' AND date >= ?
            ");
            $stmt->execute([$firstDayOfMonth]);
            $stats['income'] = $stmt->fetchColumn();
            
            // Gastos del mes
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM transactions 
                WHERE type = 'expense' AND date >= ?
            ");
            $stmt->execute([$firstDayOfMonth]);
            $stats['expense'] = $stmt->fetchColumn();
            
            // Beneficio
            $stats['profit'] = $stats['income'] - $stats['expense'];
            $stats['profit_percent'] = $stats['income'] > 0 ? ($stats['profit'] / $stats['income']) * 100 : 0;
            
            // Facturas vencidas
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM invoices 
                WHERE status = 'overdue'
            ");
            $stmt->execute();
            $stats['overdue_invoices'] = $stmt->fetchColumn();
            
            // Cuentas por cobrar
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(total), 0) FROM invoices 
                WHERE status IN ('sent', 'overdue')
            ");
            $stmt->execute();
            $stats['receivables'] = $stmt->fetchColumn();
            
            // Cuentas por pagar
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(total), 0) FROM bills 
                WHERE status IN ('received', 'overdue')
            ");
            $stmt->execute();
            $stats['payables'] = $stmt->fetchColumn();
            
            // Productos
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM products");
            $stmt->execute();
            $stats['total_products'] = $stmt->fetchColumn();
            
            // Productos con bajo stock
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM products 
                WHERE stock <= min_stock AND min_stock > 0
            ");
            $stmt->execute();
            $stats['low_stock_products'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            logMessage('error', 'Error al obtener estadísticas: ' . $e->getMessage());
        }
        
        return $stats;
    }
}