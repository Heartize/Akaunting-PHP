<?php
/**
 * Controlador para la gestión de categorías
 */
class CategoryController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Lista de categorías
     */
    public function index() {
        // Parámetros de paginación
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = $GLOBALS['config']['default_pagination_limit'];
        $offset = ($page - 1) * $limit;
        
        // Obtener categorías de productos
        try {
            // Consulta para categorías de productos
            $query = "SELECT 
                        id, name, description, 'product' as category_type, 
                        NULL as type, NULL as color,
                        (SELECT COUNT(*) FROM products WHERE category_id = pc.id) as item_count
                      FROM product_categories pc";
            
            // Filtros
            $filters = [];
            $params = [];
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filters[] = "name LIKE ?";
                $params[] = '%' . $_GET['search'] . '%';
            }
            
            if (!empty($filters)) {
                $query .= " WHERE " . implode(" AND ", $filters);
            }
            
            // Unir con categorías de transacciones
            $query .= " UNION ALL
                      SELECT 
                        id, name, NULL as description, 'transaction' as category_type, 
                        type, color,
                        (SELECT COUNT(*) FROM transactions WHERE category_id = tc.id) as item_count
                      FROM transaction_categories tc";
            
            if (!empty($filters)) {
                $query .= " WHERE " . implode(" AND ", $filters);
            }
            
            $query .= " ORDER BY category_type, name";
            $query .= " LIMIT ?, ?";
            
            $stmt = $this->db->prepare($query);
            
            // Ejecutar con los parámetros
            $paramCount = count($params);
            for ($i = 0; $i < $paramCount; $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
            
            // Parámetros para consulta de transacciones (duplicamos los mismos filtros)
            for ($i = 0; $i < $paramCount; $i++) {
                $stmt->bindValue($paramCount + $i + 1, $params[$i]);
            }
            
            // Parámetros para limit y offset
            $stmt->bindValue($paramCount * 2 + 1, $offset, PDO::PARAM_INT);
            $stmt->bindValue($paramCount * 2 + 2, $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total para paginación
            $countQuery = "SELECT COUNT(*) FROM (
                             SELECT id FROM product_categories";
            
            if (!empty($filters)) {
                $countQuery .= " WHERE " . implode(" AND ", $filters);
            }
            
            $countQuery .= " UNION ALL
                             SELECT id FROM transaction_categories";
            
            if (!empty($filters)) {
                $countQuery .= " WHERE " . implode(" AND ", $filters);
            }
            
            $countQuery .= ") as count_table";
            
            $stmt = $this->db->prepare($countQuery);
            
            // Ejecutar con los parámetros (sin limit y offset)
            for ($i = 0; $i < $paramCount; $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
            
            // Parámetros para consulta de transacciones (duplicamos los mismos filtros)
            for ($i = 0; $i < $paramCount; $i++) {
                $stmt->bindValue($paramCount + $i + 1, $params[$i]);
            }
            
            $stmt->execute();
            $total = $stmt->fetchColumn();
            
            // Calcular páginas
            $totalPages = ceil($total / $limit);
            
            // Cargar vista
            $pageTitle = 'Categorías';
            $includeSearch = true;
            $actionButton = 'Nueva Categoría';
            $actionButtonUrl = url('/index.php?page=categories&action=create');
            
            $breadcrumbs = [
                'Categorías' => null
            ];
            
            include __DIR__ . '/../views/categories/index.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al cargar categorías: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Formulario para crear una nueva categoría
     */
    public function create() {
        // Tipo de categoría
        $categoryType = $_GET['type'] ?? 'product';
        
        // Si es categoría de transacción, necesitamos el tipo (ingreso/gasto)
        $transactionTypes = [
            'income' => 'Ingreso',
            'expense' => 'Gasto'
        ];
        
        // Colores predefinidos para categorías de transacciones
        $predefinedColors = [
            '#5D5CDE', // Azul (default)
            '#55ce63', // Verde
            '#ffbc34', // Amarillo
            '#f62d51', // Rojo
            '#2196f3', // Azul claro
            '#795548', // Marrón
            '#9c27b0', // Púrpura
            '#00bcd4'  // Turquesa
        ];
        
        // Cargar vista
        $pageTitle = 'Nueva Categoría';
        $breadcrumbs = [
            'Categorías' => url('/index.php?page=categories'),
            'Nueva Categoría' => null
        ];
        
        include __DIR__ . '/../views/categories/form.php';
    }
    
    /**
     * Guardar una nueva categoría
     */
    public function store() {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        $categoryType = $_POST['category_type'] ?? 'product';
        
        // Validar datos
        $errors = $this->validateCategory($_POST);
        
        if (empty($errors)) {
            try {
                if ($categoryType === 'product') {
                    // Insertar categoría de producto
                    $query = "INSERT INTO product_categories (name, description)
                              VALUES (?, ?)";
                    
                    $stmt = $this->db->prepare($query);
                    $result = $stmt->execute([
                        $_POST['name'],
                        $_POST['description'] ?? null
                    ]);
                } else {
                    // Insertar categoría de transacción
                    $query = "INSERT INTO transaction_categories (name, type, color)
                              VALUES (?, ?, ?)";
                    
                    $stmt = $this->db->prepare($query);
                    $result = $stmt->execute([
                        $_POST['name'],
                        $_POST['transaction_type'],
                        $_POST['color'] ?? '#5D5CDE'
                    ]);
                }
                
                if ($result) {
                    setFlashMessage('success', 'Categoría creada correctamente');
                    redirect('/index.php?page=categories');
                } else {
                    setFlashMessage('error', 'Error al crear la categoría');
                    redirect('/index.php?page=categories&action=create&type=' . $categoryType);
                }
                
            } catch (Exception $e) {
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=categories&action=create&type=' . $categoryType);
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=categories&action=create&type=' . $categoryType);
        }
    }
    
    /**
     * Formulario para editar una categoría
     */
    public function edit($id) {
        $categoryType = $_GET['type'] ?? 'product';
        
        try {
            if ($categoryType === 'product') {
                // Obtener categoría de producto
                $stmt = $this->db->prepare("SELECT * FROM product_categories WHERE id = ?");
                $stmt->execute([$id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$category) {
                    setFlashMessage('error', 'Categoría no encontrada');
                    redirect('/index.php?page=categories');
                }
                
                $category['category_type'] = 'product';
            } else {
                // Obtener categoría de transacción
                $stmt = $this->db->prepare("SELECT * FROM transaction_categories WHERE id = ?");
                $stmt->execute([$id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$category) {
                    setFlashMessage('error', 'Categoría no encontrada');
                    redirect('/index.php?page=categories');
                }
                
                $category['category_type'] = 'transaction';
                $category['transaction_type'] = $category['type'];
            }
            
            // Si es categoría de transacción, necesitamos el tipo (ingreso/gasto)
            $transactionTypes = [
                'income' => 'Ingreso',
                'expense' => 'Gasto'
            ];
            
            // Colores predefinidos para categorías de transacciones
            $predefinedColors = [
                '#5D5CDE', // Azul (default)
                '#55ce63', // Verde
                '#ffbc34', // Amarillo
                '#f62d51', // Rojo
                '#2196f3', // Azul claro
                '#795548', // Marrón
                '#9c27b0', // Púrpura
                '#00bcd4'  // Turquesa
            ];
            
            // Cargar vista
            $pageTitle = 'Editar Categoría';
            $breadcrumbs = [
                'Categorías' => url('/index.php?page=categories'),
                'Editar Categoría' => null
            ];
            
            include __DIR__ . '/../views/categories/form.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=categories');
        }
    }
    
    /**
     * Actualizar una categoría existente
     */
    public function update($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        $categoryType = $_POST['category_type'] ?? 'product';
        
        // Validar datos
        $errors = $this->validateCategory($_POST);
        
        if (empty($errors)) {
            try {
                if ($categoryType === 'product') {
                    // Actualizar categoría de producto
                    $query = "UPDATE product_categories 
                              SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP
                              WHERE id = ?";
                    
                    $stmt = $this->db->prepare($query);
                    $result = $stmt->execute([
                        $_POST['name'],
                        $_POST['description'] ?? null,
                        $id
                    ]);
                } else {
                    // Actualizar categoría de transacción
                    $query = "UPDATE transaction_categories 
                              SET name = ?, type = ?, color = ?, updated_at = CURRENT_TIMESTAMP
                              WHERE id = ?";
                    
                    $stmt = $this->db->prepare($query);
                    $result = $stmt->execute([
                        $_POST['name'],
                        $_POST['transaction_type'],
                        $_POST['color'] ?? '#5D5CDE',
                        $id
                    ]);
                }
                
                if ($result) {
                    setFlashMessage('success', 'Categoría actualizada correctamente');
                    redirect('/index.php?page=categories');
                } else {
                    setFlashMessage('error', 'Error al actualizar la categoría');
                    redirect('/index.php?page=categories&action=edit&id=' . $id . '&type=' . $categoryType);
                }
                
            } catch (Exception $e) {
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=categories&action=edit&id=' . $id . '&type=' . $categoryType);
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=categories&action=edit&id=' . $id . '&type=' . $categoryType);
        }
    }
    
    /**
     * Eliminar una categoría
     */
    public function delete($id) {
        // Verificar token CSRF si se envía por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken($_POST['csrf_token'] ?? '');
        }
        
        $categoryType = $_GET['type'] ?? 'product';
        
        try {
            if ($categoryType === 'product') {
                // Verificar si hay productos asociados
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->fetchColumn() > 0) {
                    // Actualizar productos para quitar la categoría en lugar de impedir la eliminación
                    $stmt = $this->db->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
                    $stmt->execute([$id]);
                }
                
                // Eliminar categoría de producto
                $stmt = $this->db->prepare("DELETE FROM product_categories WHERE id = ?");
                $result = $stmt->execute([$id]);
            } else {
                // Verificar si hay transacciones asociadas
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->fetchColumn() > 0) {
                    // Actualizar transacciones para quitar la categoría en lugar de impedir la eliminación
                    $stmt = $this->db->prepare("UPDATE transactions SET category_id = NULL WHERE category_id = ?");
                    $stmt->execute([$id]);
                }
                
                // Eliminar categoría de transacción
                $stmt = $this->db->prepare("DELETE FROM transaction_categories WHERE id = ?");
                $result = $stmt->execute([$id]);
            }
            
            if ($result) {
                setFlashMessage('success', 'Categoría eliminada correctamente');
            } else {
                setFlashMessage('error', 'Error al eliminar la categoría');
            }
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
        }
        
        redirect('/index.php?page=categories');
    }
    
    /**
     * Validar datos de categoría
     */
    private function validateCategory($data) {
        $errors = [];
        
        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es obligatorio';
        }
        
        // Validar tipo de transacción (solo para categorías de transacción)
        if ($data['category_type'] === 'transaction' && empty($data['transaction_type'])) {
            $errors['transaction_type'] = 'El tipo de transacción es obligatorio';
        }
        
        return $errors;
    }
}