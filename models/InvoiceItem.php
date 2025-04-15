<?php
/**
 * Modelo para elementos de facturas
 */
class InvoiceItem {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Obtiene todos los elementos de una factura
     */
    public function getByInvoiceId($invoiceId) {
        $sql = "
            SELECT ii.*, p.name as product_name, p.code as product_code
            FROM invoice_items ii
            LEFT JOIN products p ON ii.product_id = p.id
            WHERE ii.invoice_id = ?
            ORDER BY ii.id ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$invoiceId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crea un nuevo elemento de factura
     */
    public function create($data) {
        $sql = "
            INSERT INTO invoice_items (
                invoice_id, product_id, description, quantity, 
                price, tax_rate, tax_amount, total
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['invoice_id'],
            $data['product_id'] ?? null,
            $data['description'],
            $data['quantity'],
            $data['price'],
            $data['tax_rate'] ?? 0,
            $data['tax_amount'] ?? 0,
            $data['total'] ?? 0
        ]);
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualiza un elemento de factura existente
     */
    public function update($id, $data) {
        $sql = "
            UPDATE invoice_items SET
                product_id = ?,
                description = ?,
                quantity = ?,
                price = ?,
                tax_rate = ?,
                tax_amount = ?,
                total = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['product_id'] ?? null,
            $data['description'],
            $data['quantity'],
            $data['price'],
            $data['tax_rate'] ?? 0,
            $data['tax_amount'] ?? 0,
            $data['total'] ?? 0,
            $id
        ]);
    }
    
    /**
     * Elimina un elemento de factura
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM invoice_items WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Elimina todos los elementos de una factura
     */
    public function deleteByInvoiceId($invoiceId) {
        $stmt = $this->db->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
        return $stmt->execute([$invoiceId]);
    }
}