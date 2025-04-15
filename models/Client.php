<?php
/**
 * Modelo de Cliente
 */
class Client {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Obtiene todos los clientes
     */
    public function getAll($limit = null, $offset = 0, $search = null) {
        $sql = "SELECT * FROM clients";
        $params = [];
        
        if ($search) {
            $sql .= " WHERE name LIKE ? OR email LIKE ? OR tax_number LIKE ?";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam];
        }
        
        $sql .= " ORDER BY name ASC";
        
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
     * Obtiene un cliente por su ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crea un nuevo cliente
     */
    public function create($data) {
        $sql = "
            INSERT INTO clients (
                name, email, phone, address, tax_number, website, 
                credit_limit, currency, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['tax_number'] ?? null,
            $data['website'] ?? null,
            $data['credit_limit'] ?? 0.00,
            $data['currency'] ?? 'EUR',
            $data['notes'] ?? null
        ]);
        
        if ($result) {
            $clientId = $this->db->lastInsertId();
            
            // Guardar campos personalizados si existen
            if (isset($data['custom_fields'])) {
                $this->saveCustomFields($clientId, $data['custom_fields']);
            }
            
            return $clientId;
        }
        
        return false;
    }
    
    /**
     * Actualiza un cliente existente
     */
    public function update($id, $data) {
        $sql = "
            UPDATE clients SET
                name = ?, 
                email = ?, 
                phone = ?, 
                address = ?, 
                tax_number = ?, 
                website = ?, 
                credit_limit = ?, 
                currency = ?, 
                notes = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['tax_number'] ?? null,
            $data['website'] ?? null,
            $data['credit_limit'] ?? 0.00,
            $data['currency'] ?? 'EUR',
            $data['notes'] ?? null,
            $id
        ]);
        
        if ($result && isset($data['custom_fields'])) {
            $this->saveCustomFields($id, $data['custom_fields']);
        }
        
        return $result;
    }
    
    /**
     * Elimina un cliente
     */
    public function delete($id) {
        // Primero eliminar valores de campos personalizados
        $this->deleteCustomFieldValues($id);
        
        // Luego eliminar el cliente
        $stmt = $this->db->prepare("DELETE FROM clients WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Cuenta el total de clientes
     */
    public function count($search = null) {
        $sql = "SELECT COUNT(*) FROM clients";
        $params = [];
        
        if ($search) {
            $sql .= " WHERE name LIKE ? OR email LIKE ? OR tax_number LIKE ?";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Guarda valores de campos personalizados para un cliente
     */
    private function saveCustomFields($clientId, $customFields) {
        // Primero eliminar valores existentes
        $this->deleteCustomFieldValues($clientId);
        
        // Luego insertar nuevos valores
        foreach ($customFields as $fieldId => $value) {
            if (empty($value)) continue;
            
            $stmt = $this->db->prepare("
                INSERT INTO custom_field_values (field_id, model_id, module, value)
                VALUES (?, ?, 'clients', ?)
            ");
            
            $stmt->execute([$fieldId, $clientId, $value]);
        }
    }
    
    /**
     * Elimina valores de campos personalizados para un cliente
     */
    private function deleteCustomFieldValues($clientId) {
        $stmt = $this->db->prepare("
            DELETE FROM custom_field_values 
            WHERE model_id = ? AND module = 'clients'
        ");
        
        return $stmt->execute([$clientId]);
    }
    
    /**
     * Obtiene los saldos pendientes de un cliente
     */
    public function getPendingBalance($clientId) {
        $stmt = $this->db->prepare("
            SELECT SUM(total) as total
            FROM invoices
            WHERE client_id = ? AND status IN ('sent', 'overdue')
        ");
        
        $stmt->execute([$clientId]);
        return $stmt->fetchColumn() ?: 0;
    }
}