<?php
/**
 * Modelo para categorías de productos
 */
class ProductCategory {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Obtiene todas las categorías
     */
    public function getAll() {
        $sql = "SELECT * FROM product_categories ORDER BY name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene una categoría por su ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM product_categories WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crea una nueva categoría
     */
    public function create($name, $description = null) {
        $sql = "INSERT INTO product_categories (name, description) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$name, $description]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualiza una categoría existente
     */
    public function update($id, $name, $description = null) {
        $sql = "UPDATE product_categories SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$name, $description, $id]);
    }
    
    /**
     * Elimina una categoría
     */
    public function delete($id) {
        // Primero actualizar productos con esta categoría
        $sql = "UPDATE products SET category_id = NULL WHERE category_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        // Luego eliminar la categoría
        $sql = "DELETE FROM product_categories WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Cuenta productos por categoría
     */
    public function countProducts($id) {
        $sql = "SELECT COUNT(*) FROM products WHERE category_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }
}