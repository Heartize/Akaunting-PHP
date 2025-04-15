<?php
/**
 * Modelo para facturas
 */
class Invoice {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Obtiene todas las facturas
     */
    public function getAll($limit = null, $offset = 0, $filters = []) {
        $sql = "
            SELECT i.*, c.name as client_name
            FROM invoices i
            JOIN clients c ON i.client_id = c.id
        ";
        
        $params = [];
        $conditions = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $conditions[] = "i.client_id = ?";
                $params[] = $filters['client_id'];
            }
            
            if (isset($filters['status']) && !empty($filters['status'])) {
                $conditions[] = "i.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $conditions[] = "i.invoice_date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $conditions[] = "i.invoice_date <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $conditions[] = "(i.invoice_number LIKE ? OR c.name LIKE ?)";
                $searchParam = "%{$filters['search']}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
        }
        
        $sql .= " ORDER BY i.invoice_date DESC";
        
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
     * Obtiene una factura por su ID
     */
    public function getById($id) {
        $sql = "
            SELECT i.*, c.name as client_name
            FROM invoices i
            JOIN clients c ON i.client_id = c.id
            WHERE i.id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crea una nueva factura
     */
    public function create($data) {
        $sql = "
            INSERT INTO invoices (
                invoice_number, client_id, status, invoice_date, 
                due_date, notes, footer
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['invoice_number'],
            $data['client_id'],
            $data['status'] ?? 'draft',
            $data['invoice_date'],
            $data['due_date'],
            $data['notes'] ?? null,
            $data['footer'] ?? null
        ]);
        
        if ($result) {
            $invoiceId = $this->db->lastInsertId();
            
            // Guardar campos personalizados si existen
            if (isset($data['custom_fields'])) {
                $this->saveCustomFields($invoiceId, $data['custom_fields']);
            }
            
            return $invoiceId;
        }
        
        return false;
    }
    
    /**
     * Actualiza una factura existente
     */
    public function update($id, $data) {
        $sql = "
            UPDATE invoices SET
                invoice_number = ?,
                client_id = ?,
                status = ?,
                invoice_date = ?,
                due_date = ?,
                notes = ?,
                footer = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['invoice_number'],
            $data['client_id'],
            $data['status'] ?? 'draft',
            $data['invoice_date'],
            $data['due_date'],
            $data['notes'] ?? null,
            $data['footer'] ?? null,
            $id
        ]);
        
        if ($result && isset($data['custom_fields'])) {
            $this->saveCustomFields($id, $data['custom_fields']);
        }
        
        return $result;
    }
    
    /**
     * Elimina una factura
     */
    public function delete($id) {
        // Primero eliminar valores de campos personalizados
        $this->deleteCustomFieldValues($id);
        
        // Luego eliminar la factura
        $stmt = $this->db->prepare("DELETE FROM invoices WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Actualiza el estado de una factura
     */
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("
            UPDATE invoices 
            SET status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        return $stmt->execute([$status, $id]);
    }
    
    /**
     * Obtiene el siguiente número de factura
     */
    public function getNextInvoiceNumber() {
        $stmt = $this->db->query("
            SELECT invoice_number FROM invoices 
            WHERE invoice_number LIKE 'FACT-%' 
            ORDER BY id DESC LIMIT 1
        ");
        
        $lastInvoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastInvoice) {
            // Extraer el número de la factura
            $parts = explode('-', $lastInvoice['invoice_number']);
            $lastNumber = (int)end($parts);
            $nextNumber = $lastNumber + 1;
            
            return 'FACT-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }
        
        // Si no hay facturas anteriores
        return 'FACT-0001';
    }
    
    /**
     * Guarda valores de campos personalizados para una factura
     */
    private function saveCustomFields($invoiceId, $customFields) {
        // Primero eliminar valores existentes
        $this->deleteCustomFieldValues($invoiceId);
        
        // Luego insertar nuevos valores
        foreach ($customFields as $fieldId => $value) {
            if (empty($value)) continue;
            
            $stmt = $this->db->prepare("
                INSERT INTO custom_field_values (field_id, model_id, module, value)
                VALUES (?, ?, 'invoices', ?)
            ");
            
            $stmt->execute([$fieldId, $invoiceId, $value]);
        }
    }
    
    /**
     * Elimina valores de campos personalizados para una factura
     */
    private function deleteCustomFieldValues($invoiceId) {
        $stmt = $this->db->prepare("
            DELETE FROM custom_field_values 
            WHERE model_id = ? AND module = 'invoices'
        ");
        
        return $stmt->execute([$invoiceId]);
    }
    
    /**
     * Cuenta el total de facturas
     */
    public function count($filters = []) {
        $sql = "
            SELECT COUNT(*) 
            FROM invoices i
            JOIN clients c ON i.client_id = c.id
        ";
        
        $params = [];
        $conditions = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $conditions[] = "i.client_id = ?";
                $params[] = $filters['client_id'];
            }
            
            if (isset($filters['status']) && !empty($filters['status'])) {
                $conditions[] = "i.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $conditions[] = "i.invoice_date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $conditions[] = "i.invoice_date <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $conditions[] = "(i.invoice_number LIKE ? OR c.name LIKE ?)";
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
}