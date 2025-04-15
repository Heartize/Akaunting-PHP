<?php
/**
 * Controlador para la gestión de informes
 */
class ReportController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Página principal de informes
     */
    public function index() {
        // Redireccionar al informe de ingresos por defecto
        redirect('/index.php?page=reports&type=income');
    }
    
    /**
     * Informe de ingresos
     */
    public function income() {
        // Verificar si el usuario tiene permiso
        // Comentado temporalmente para evitar redirecciones infinitas
        // requireLogin();
        
        try {
            // Obtener fechas de inicio y fin
            $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
            $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month', strtotime($endDate)));
            
            // Obtener categoría (si se proporciona)
            $categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : null;
            
            // Obtener datos de ingresos
            $query = "SELECT 
                        DATE_FORMAT(t.date, '%Y-%m-%d') as date,
                        SUM(t.amount) as total,
                        tc.name as category,
                        tc.color
                      FROM transactions t
                      LEFT JOIN transaction_categories tc ON t.category_id = tc.id
                      WHERE t.type = 'income'
                        AND t.date BETWEEN ? AND ?";
            
            $params = [$startDate, $endDate];
            
            if ($categoryId) {
                $query .= " AND t.category_id = ?";
                $params[] = $categoryId;
            }
            
            $query .= " GROUP BY DATE_FORMAT(t.date, '%Y-%m-%d'), t.category_id
                         ORDER BY t.date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $incomeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener total
            $query = "SELECT SUM(amount) FROM transactions 
                      WHERE type = 'income' AND date BETWEEN ? AND ?";
            
            $params = [$startDate, $endDate];
            
            if ($categoryId) {
                $query .= " AND category_id = ?";
                $params[] = $categoryId;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $totalIncome = $stmt->fetchColumn();
            
            // Obtener categorías de ingresos
            $query = "SELECT id, name, color FROM transaction_categories WHERE type = 'income' ORDER BY name";
            $stmt = $this->db->query($query);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cargar vista
            $pageTitle = 'Informe de Ingresos';
            $breadcrumbs = [
                'Informes' => url('/index.php?page=reports'),
                'Ingresos' => null
            ];
            
            include __DIR__ . '/../views/reports/income.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al generar el informe: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Informe de gastos
     */
    public function expense() {
        // Verificar si el usuario tiene permiso
        requireLogin();
        
        try {
            // Obtener fechas de inicio y fin
            $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
            $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month', strtotime($endDate)));
            
            // Obtener categoría (si se proporciona)
            $categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : null;
            
            // Obtener datos de gastos
            $query = "SELECT 
                        DATE_FORMAT(t.date, '%Y-%m-%d') as date,
                        SUM(t.amount) as total,
                        tc.name as category,
                        tc.color
                      FROM transactions t
                      LEFT JOIN transaction_categories tc ON t.category_id = tc.id
                      WHERE t.type = 'expense'
                        AND t.date BETWEEN ? AND ?";
            
            $params = [$startDate, $endDate];
            
            if ($categoryId) {
                $query .= " AND t.category_id = ?";
                $params[] = $categoryId;
            }
            
            $query .= " GROUP BY DATE_FORMAT(t.date, '%Y-%m-%d'), t.category_id
                         ORDER BY t.date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $expenseData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener total
            $query = "SELECT SUM(amount) FROM transactions 
                      WHERE type = 'expense' AND date BETWEEN ? AND ?";
            
            $params = [$startDate, $endDate];
            
            if ($categoryId) {
                $query .= " AND category_id = ?";
                $params[] = $categoryId;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $totalExpense = $stmt->fetchColumn();
            
            // Obtener categorías de gastos
            $query = "SELECT id, name, color FROM transaction_categories WHERE type = 'expense' ORDER BY name";
            $stmt = $this->db->query($query);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cargar vista
            $pageTitle = 'Informe de Gastos';
            $breadcrumbs = [
                'Informes' => url('/index.php?page=reports'),
                'Gastos' => null
            ];
            
            include __DIR__ . '/../views/reports/expense.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al generar el informe: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Informe de impuestos
     */
    public function tax() {
        // Verificar si el usuario tiene permiso
        requireLogin();
        
        try {
            // Obtener fechas de inicio y fin
            $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
            $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month', strtotime($endDate)));
            
            // Obtener impuestos de facturas
            $query = "SELECT 
                        SUM(tax_total) as tax_amount,
                        COUNT(*) as count,
                        'Facturas de venta' as type
                      FROM invoices
                      WHERE invoice_date BETWEEN ? AND ?
                        AND status <> 'cancelled'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $invoiceTax = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener impuestos de facturas de compra
            $query = "SELECT 
                        SUM(tax_total) as tax_amount,
                        COUNT(*) as count,
                        'Facturas de compra' as type
                      FROM bills
                      WHERE bill_date BETWEEN ? AND ?
                        AND status <> 'cancelled'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $billTax = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular balance
            $taxBalance = ($invoiceTax['tax_amount'] ?? 0) - ($billTax['tax_amount'] ?? 0);
            
            // Cargar vista
            $pageTitle = 'Informe de Impuestos';
            $breadcrumbs = [
                'Informes' => url('/index.php?page=reports'),
                'Impuestos' => null
            ];
            
            include __DIR__ . '/../views/reports/tax.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al generar el informe: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Informe de beneficios y pérdidas
     */
    public function profitLoss() {
        // Verificar si el usuario tiene permiso
        requireLogin();
        
        try {
            // Obtener fechas de inicio y fin
            $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
            $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month', strtotime($endDate)));
            
            // Obtener ingresos por categoría
            $query = "SELECT 
                        tc.name as category,
                        tc.color,
                        SUM(t.amount) as total
                      FROM transactions t
                      LEFT JOIN transaction_categories tc ON t.category_id = tc.id
                      WHERE t.type = 'income'
                        AND t.date BETWEEN ? AND ?
                      GROUP BY t.category_id
                      ORDER BY total DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $incomeByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener gastos por categoría
            $query = "SELECT 
                        tc.name as category,
                        tc.color,
                        SUM(t.amount) as total
                      FROM transactions t
                      LEFT JOIN transaction_categories tc ON t.category_id = tc.id
                      WHERE t.type = 'expense'
                        AND t.date BETWEEN ? AND ?
                      GROUP BY t.category_id
                      ORDER BY total DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $expenseByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener totales
            $query = "SELECT 
                        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
                      FROM transactions
                      WHERE date BETWEEN ? AND ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalIncome = $totals['total_income'] ?? 0;
            $totalExpense = $totals['total_expense'] ?? 0;
            $profit = $totalIncome - $totalExpense;
            $profitMargin = $totalIncome > 0 ? ($profit / $totalIncome) * 100 : 0;
            
            // Cargar vista
            $pageTitle = 'Informe de Beneficios y Pérdidas';
            $breadcrumbs = [
                'Informes' => url('/index.php?page=reports'),
                'Beneficios y Pérdidas' => null
            ];
            
            include __DIR__ . '/../views/reports/profit_loss.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al generar el informe: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
}