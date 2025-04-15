<?php
/**
 * Modelo de transacciones financieras
 */
class Transaction {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Obtiene todas las transacciones
     */
    public function getAll($limit = null, $offset = 0, $filters = []) {
        $sql = "
            SELECT t.*, tc.name as category_name, a.name as account_name,
                   CASE
                       WHEN t.contact_type = 'client' THEN c.name
                       WHEN t.contact_type = 'vendor' THEN v.name
                       ELSE NULL
                   END as contact_name
            FROM transactions t
            LEFT JOIN transaction_categories tc ON t.category_id = tc.id
            LEFT JOIN accounts a ON t.account_id = a.id
            LEFT JOIN clients c ON t.contact_id = c.id AND t.contact_type = 'client'
            LEFT JOIN vendors v ON t.contact_id = v.id AND t.contact_type = 'vendor'
        ";
        
        $params = [];
        $conditions = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            if (isset($filters['type']) && !empty($filters['type'])) {
                $conditions[] = "t.type = ?";
                $params[] = $filters['type'];
            }
            
            if (isset($filters['account_id']) && !empty($filters['account_id'])) {
                $conditions[] = "t.account_id = ?";
                $params[] = $filters['account_id'];
            }
            
            if (isset($filters['category_id']) && !empty($filters['category_id'])) {
                $conditions[] = "t.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $conditions[] = "t.date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $conditions[] = "t.date <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $conditions[] = "(t.description LIKE ? OR t.reference LIKE ?)";
                $searchParam = "%{$filters['search']}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
        }
        
        $sql .= " ORDER BY t.date DESC, t.id DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene una transacción por su ID
     */
    public function getById($id) {
        $sql = "
            SELECT t.*, tc.name as category_name, a.name as account_name,
                   CASE
                       WHEN t.contact_type = 'client' THEN c.name
                       WHEN t.contact_type = 'vendor' THEN v.name
                       ELSE NULL
                   END as contact_name
            FROM transactions t
            LEFT JOIN transaction_categories tc ON t.category_id = tc.id
            LEFT JOIN accounts a ON t.account_id = a.id
            LEFT JOIN clients c ON t.contact_id = c.id AND t.contact_type = 'client'
            LEFT JOIN vendors v ON t.contact_id = v.id AND t.contact_type = 'vendor'
            WHERE t.id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crea una nueva transacción
     */
    public function create($data) {
        $sql = "
            INSERT INTO transactions (
                type, account_id, category_id, invoice_id, bill_id,
                amount, date, description, reference, contact_id, contact_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['type'],
            $data['account_id'],
            $data['category_id'] ?? null,
            $data['invoice_id'] ?? null,
            $data['bill_id'] ?? null,
            $data['amount'],
            $data['date'],
            $data['description'] ?? null,
            $data['reference'] ?? null,
            $data['contact_id'] ?? null,
            $data['contact_type'] ?? null
        ]);
        
        if ($result) {
            // Actualizar saldo de la cuenta
            $this->updateAccountBalance($data['account_id'], $data['amount'], $data['type']);
            
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualiza una transacción existente
     */
    public function update($id, $data) {
        // Obtener la transacción anterior para revertir el saldo
        $oldTransaction = $this->getById($id);
        if ($oldTransaction) {
            // Revertir el saldo anterior
            $this->updateAccountBalance(
                $oldTransaction['account_id'], 
                $oldTransaction['amount'], 
                $oldTransaction['type'] === 'income' ? 'expense' : 'income'
            );
        }
        
        $sql = "
            UPDATE transactions SET
                type = ?,
                account_id = ?,
                category_id = ?,
                invoice_id = ?,
                bill_id = ?,
                amount = ?,
                date = ?,
                description = ?,
                reference = ?,
                contact_id = ?,
                contact_type = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['type'],
            $data['account_id'],
            $data['category_id'] ?? null,
            $data['invoice_id'] ?? null,
            $data['bill_id'] ?? null,
            $data['amount'],
            $data['date'],
            $data['description'] ?? null,
            $data['reference'] ?? null,
            $data['contact_id'] ?? null,
            $data['contact_type'] ?? null,
            $id
        ]);
        
        if ($result) {
            // Actualizar saldo de la cuenta con los nuevos datos
            $this->updateAccountBalance($data['account_id'], $data['amount'], $data['type']);
        }
        
        return $result;
    }
    
    /**
     * Elimina una transacción
     */
    public function delete($id) {
        // Obtener la transacción para revertir el saldo
        $transaction = $this->getById($id);
        if ($transaction) {
            // Revertir el saldo
            $this->updateAccountBalance(
                $transaction['account_id'], 
                $transaction['amount'], 
                $transaction['type'] === 'income' ? 'expense' : 'income'
            );
        }
        
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Actualiza el saldo de una cuenta
     */
    private function updateAccountBalance($accountId, $amount, $type) {
        $sql = "UPDATE accounts SET current_balance = current_balance " . ($type === 'income' ? '+' : '-') . " ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$amount, $accountId]);
    }
    
    /**
     * Cuenta el total de transacciones
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) FROM transactions t";
        
        $params = [];
        $conditions = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            if (isset($filters['type']) && !empty($filters['type'])) {
                $conditions[] = "t.type = ?";
                $params[] = $filters['type'];
            }
            
            if (isset($filters['account_id']) && !empty($filters['account_id'])) {
                $conditions[] = "t.account_id = ?";
                $params[] = $filters['account_id'];
            }
            
            if (isset($filters['category_id']) && !empty($filters['category_id'])) {
                $conditions[] = "t.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $conditions[] = "t.date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $conditions[] = "t.date <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $conditions[] = "(t.description LIKE ? OR t.reference LIKE ?)";
                $searchParam = "%{$filters['search']}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Obtiene los totales de ingresos y gastos para un período
     */
    public function getTotals($dateFrom, $dateTo) {
        $sql = "
            SELECT 
                type, 
                SUM(amount) as total
            FROM transactions
            WHERE date BETWEEN ? AND ?
            GROUP BY type
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totals = [
            'income' => 0,
            'expense' => 0,
            'profit' => 0
        ];
        
        foreach ($results as $row) {
            $totals[$row['type']] = $row['total'];
        }
        
        $totals['profit'] = $totals['income'] - $totals['expense'];
        
        return $totals;
    }
}