<?php
/**
 * Modelo para productos
 */
class Product {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Obtiene todos los productos
     */
    public function getAll($limit = null, $offset = 0, $filters = []) {
        $sql = "SELECT p.*, pc.name as category_name
                FROM products p
                LEFT JOIN product_categories pc ON p.category_id = pc.id";
        
        $params = [];
        $conditions = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            if (isset($filters['category_id']) && !empty($filters['category_id'])) {
                $conditions[] = "p.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (isset($filters['stock_status'])) {
                if ($filters['stock_status'] === 'low') {
                    $conditions[] = "p.stock <= p.min_stock AND p.min_stock > 0";
                } elseif ($filters['stock_status'] === 'out') {
                    $conditions[] = "p.stock = 0";
                }
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $conditions[] = "(p.name LIKE ? OR p.code LIKE ? OR p.description LIKE ?)";
                $searchParam = "%{$filters['search']}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
        }
        
        $sql .= " ORDER BY p.name";
        
        if ($limit) {
            $sql .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene un producto por su ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, pc.name as category_name
                FROM products p
                LEFT JOIN product_categories pc ON p.category_id = pc.id
                WHERE p.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crea un nuevo producto
     */
    public function create($data) {
        $sql = "INSERT INTO products (
                    code, name, description, category_id, purchase_price,
                    sale_price, tax_rate, unit, stock, min_stock, location, enabled
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['code'],
            $data['name'],
            $data['description'] ?? '',
            $data['category_id'] ?? null,
            $data['purchase_price'] ?? 0,
            $data['sale_price'] ?? 0,
            $data['tax_rate'] ?? 21.00,
            $data['unit'] ?? 'unidad',
            $data['stock'] ?? 0,
            $data['min_stock'] ?? 0,
            $data['location'] ?? '',
            $data['enabled'] ?? 1
        ]);
        
        if ($result) {
            $productId = $this->db->lastInsertId();
            
            // Guardar campos personalizados si existen
            if (isset($data['custom_fields'])) {
                $this->saveCustomFields($productId, $data['custom_fields']);
            }
            
            return $productId;
        }
        
        return false;
    }
    
    /**
     * Actualiza un producto existente
     */
    public function update($id, $data) {
        $sql = "UPDATE products SET
                    code = ?,
                    name = ?,
                    description = ?,
                    category_id = ?,
                    purchase_price = ?,
                    sale_price = ?,
                    tax_rate = ?,
                    unit = ?,
                    stock = ?,
                    min_stock = ?,
                    location = ?,
                    enabled = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['code'],
            $data['name'],
            $data['description'] ?? '',
            $data['category_id'] ?? null,
            $data['purchase_price'] ?? 0,
            $data['sale_price'] ?? 0,
            $data['tax_rate'] ?? 21.00,
            $data['unit'] ?? 'unidad',
            $data['stock'] ?? 0,
            $data['min_stock'] ?? 0,
            $data['location'] ?? '',
            $data['enabled'] ?? 0,
            $id
        ]);
        
        if ($result && isset($data['custom_fields'])) {
            $this->saveCustomFields($id, $data['custom_fields']);
        }
        
        return $result;
    }
    
    /**
     * Elimina un producto
     */
    public function delete($id) {
        // Primero eliminar valores de campos personalizados
        $this->deleteCustomFieldValues($id);
        
        // Luego eliminar el producto
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Actualiza el stock de un producto
     */
    public function updateStock($id, $quantity, $operation = 'add') {
        $sql = "UPDATE products SET
                    stock = " . ($operation === 'add' ? "stock + ?" : "stock - ?") . ",
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$quantity, $id]);
    }
    
    /**
     * Guarda valores de campos personalizados para un producto
     */
    private function saveCustomFields($productId, $customFields) {
        // Primero eliminar valores existentes
        $this->deleteCustomFieldValues($productId);
        
        // Luego insertar nuevos valores
        foreach ($customFields as $fieldId => $value) {
            if (empty($value)) continue;
            
            $stmt = $this->db->prepare("
                INSERT INTO custom_field_values (field_id, model_id, module, value)
                VALUES (?, ?, 'products', ?)
            ");
            
            $stmt->execute([$fieldId, $productId, $value]);
        }
    }
    
    /**
     * Elimina valores de campos personalizados para un producto
     */
    private function deleteCustomFieldValues($productId) {
        $stmt = $this->db->prepare("
            DELETE FROM custom_field_values 
            WHERE model_id = ? AND module = 'products'
        ");
        
        return $stmt->execute([$productId]);
    }
    
    /**
     * Cuenta el total de productos
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) FROM products p";
        
        $params = [];
        $conditions = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            if (isset($filters['category_id']) && !empty($filters['category_id'])) {
                $conditions[] = "p.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (isset($filters['stock_status'])) {
                if ($filters['stock_status'] === 'low') {
                    $conditions[] = "p.stock <= p.min_stock AND p.min_stock > 0";
                } elseif ($filters['stock_status'] === 'out') {
                    $conditions[] = "p.stock = 0";
                }
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $conditions[] = "(p.name LIKE ? OR p.code LIKE ? OR p.description LIKE ?)";
                $searchParam = "%{$filters['search']}%";
                $params[] = $searchParam;
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
     * Obtiene el siguiente código de producto
     */
    public function getNextProductCode() {
        $stmt = $this->db->query("
            SELECT code FROM products 
            WHERE code LIKE 'PROD-%' 
            ORDER BY id DESC LIMIT 1
        ");
        
        $lastProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastProduct) {
            // Extraer el número del código
            $parts = explode('-', $lastProduct['code']);
            $lastNumber = (int)end($parts);
            $nextNumber = $lastNumber + 1;
            
            return 'PROD-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }
        
        // Si no hay productos anteriores
        return 'PROD-0001';
    }
}