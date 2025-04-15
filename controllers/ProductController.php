<?php
/**
 * Controlador para la gestión de productos
 */
class ProductController {
    private $db;
    private $product;
    
    public function __construct($db) {
        $this->db = $db;
        require_once __DIR__ . '/../models/Product.php';
        $this->product = new Product($db);
    }
    
    /**
     * Lista de productos
     */
    public function index() {
        // Parámetros de paginación
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = $GLOBALS['config']['default_pagination_limit'];
        $offset = ($page - 1) * $limit;
        
        // Filtros
        $filters = [];
        
        if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
            $filters['category_id'] = $_GET['category_id'];
        }
        
        if (isset($_GET['stock_status'])) {
            $filters['stock_status'] = $_GET['stock_status'];
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        // Obtener productos con paginación
        $products = $this->product->getAll($limit, $offset, $filters);
        $total = $this->product->count($filters);
        
        // Calcular páginas
        $totalPages = ceil($total / $limit);
        
        // Obtener categorías para el filtro
        require_once __DIR__ . '/../models/ProductCategory.php';
        $categoryModel = new ProductCategory($this->db);
        $categories = $categoryModel->getAll();
        
        // Cargar vista
        $pageTitle = 'Productos';
        $includeSearch = true;
        $actionButton = 'Nuevo Producto';
        $actionButtonUrl = url('/index.php?page=products&action=create');
        
        $breadcrumbs = [
            'Productos' => null
        ];
        
        include __DIR__ . '/../views/products/index.php';
    }
    
    /**
     * Formulario para crear un nuevo producto
     */
    public function create() {
        // Obtener categorías
        require_once __DIR__ . '/../models/ProductCategory.php';
        $categoryModel = new ProductCategory($this->db);
        $categories = $categoryModel->getAll();
        
        // Obtener campos personalizados
        $customFields = getCustomFields('products');
        
        // Generar código de producto
        $nextProductCode = $this->product->getNextProductCode();
        
        // Cargar vista
        $pageTitle = 'Nuevo Producto';
        $breadcrumbs = [
            'Productos' => url('/index.php?page=products'),
            'Nuevo' => null
        ];
        
        include __DIR__ . '/../views/products/form.php';
    }
    
    /**
     * Guardar un nuevo producto
     */
    public function store() {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateProduct($_POST);
        
        if (empty($errors)) {
            // Preparar datos del producto
            $data = [
                'code' => $_POST['code'],
                'name' => $_POST['name'],
                'description' => $_POST['description'] ?? '',
                'category_id' => $_POST['category_id'] ?? null,
                'purchase_price' => $_POST['purchase_price'] ?? 0,
                'sale_price' => $_POST['sale_price'] ?? 0,
                'tax_rate' => $_POST['tax_rate'] ?? 21.00,
                'unit' => $_POST['unit'] ?? 'unidad',
                'stock' => $_POST['stock'] ?? 0,
                'min_stock' => $_POST['min_stock'] ?? 0,
                'location' => $_POST['location'] ?? '',
                'enabled' => isset($_POST['enabled']) ? 1 : 0
            ];
            
            // Recopilar campos personalizados
            $customFields = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'custom_') === 0) {
                    $fieldId = substr($key, 7);
                    $customFields[$fieldId] = $value;
                }
            }
            
            $data['custom_fields'] = $customFields;
            
            // Crear producto
            $productId = $this->product->create($data);
            
            if ($productId) {
                setFlashMessage('success', 'Producto creado correctamente');
                redirect('/index.php?page=products');
            } else {
                setFlashMessage('error', 'Error al crear el producto');
                redirect('/index.php?page=products&action=create');
            }
        } else {
            // Hay errores, volver al formulario
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=products&action=create');
        }
    }
    
    /**
     * Formulario para editar un producto
     */
    public function edit($id) {
        $product = $this->product->getById($id);
        
        if (!$product) {
            setFlashMessage('error', 'Producto no encontrado');
            redirect('/index.php?page=products');
        }
        
        // Obtener categorías
        require_once __DIR__ . '/../models/ProductCategory.php';
        $categoryModel = new ProductCategory($this->db);
        $categories = $categoryModel->getAll();
        
        // Obtener campos personalizados
        $customFields = getCustomFields('products');
        $customFieldValues = getCustomFieldValues('products', $id);
        
        // Cargar vista
        $pageTitle = 'Editar Producto';
        $breadcrumbs = [
            'Productos' => url('/index.php?page=products'),
            'Editar' => null
        ];
        
        include __DIR__ . '/../views/products/form.php';
    }
    
    /**
     * Actualizar un producto existente
     */
    public function update($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateProduct($_POST);
        
        if (empty($errors)) {
            // Preparar datos del producto
            $data = [
                'code' => $_POST['code'],
                'name' => $_POST['name'],
                'description' => $_POST['description'] ?? '',
                'category_id' => $_POST['category_id'] ?? null,
                'purchase_price' => $_POST['purchase_price'] ?? 0,
                'sale_price' => $_POST['sale_price'] ?? 0,
                'tax_rate' => $_POST['tax_rate'] ?? 21.00,
                'unit' => $_POST['unit'] ?? 'unidad',
                'stock' => $_POST['stock'] ?? 0,
                'min_stock' => $_POST['min_stock'] ?? 0,
                'location' => $_POST['location'] ?? '',
                'enabled' => isset($_POST['enabled']) ? 1 : 0
            ];
            
            // Recopilar campos personalizados
            $customFields = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'custom_') === 0) {
                    $fieldId = substr($key, 7);
                    $customFields[$fieldId] = $value;
                }
            }
            
            $data['custom_fields'] = $customFields;
            
            // Actualizar producto
            $result = $this->product->update($id, $data);
            
            if ($result) {
                setFlashMessage('success', 'Producto actualizado correctamente');
                redirect('/index.php?page=products');
            } else {
                setFlashMessage('error', 'Error al actualizar el producto');
                redirect('/index.php?page=products&action=edit&id=' . $id);
            }
        } else {
            // Hay errores, volver al formulario
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=products&action=edit&id=' . $id);
        }
    }
    
    /**
     * Ver detalles de un producto
     */
    public function show($id) {
        $product = $this->product->getById($id);
        
        if (!$product) {
            setFlashMessage('error', 'Producto no encontrado');
            redirect('/index.php?page=products');
        }
        
        // Obtener campos personalizados
        $customFieldValues = getCustomFieldValues('products', $id);
        
        // Cargar vista
        $pageTitle = 'Detalles del Producto';
        $breadcrumbs = [
            'Productos' => url('/index.php?page=products'),
            'Detalles' => null
        ];
        
        include __DIR__ . '/../views/products/show.php';
    }
    
    /**
     * Eliminar un producto
     */
    public function delete($id) {
        // Verificar token CSRF si se envía mediante POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken($_POST['csrf_token'] ?? '');
        }
        
        $result = $this->product->delete($id);
        
        if ($result) {
            setFlashMessage('success', 'Producto eliminado correctamente');
        } else {
            setFlashMessage('error', 'Error al eliminar el producto');
        }
        
        redirect('/index.php?page=products');
    }
    
    /**
     * Formulario para ajustar stock
     */
    public function adjustStock($id) {
        $product = $this->product->getById($id);
        
        if (!$product) {
            setFlashMessage('error', 'Producto no encontrado');
            redirect('/index.php?page=products');
        }
        
        // Cargar vista
        $pageTitle = 'Ajustar Stock';
        $breadcrumbs = [
            'Productos' => url('/index.php?page=products'),
            'Ajustar Stock' => null
        ];
        
        include __DIR__ . '/../views/products/adjust_stock.php';
    }
    
    /**
     * Guardar ajuste de stock
     */
    public function saveStockAdjustment($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        $product = $this->product->getById($id);
        
        if (!$product) {
            setFlashMessage('error', 'Producto no encontrado');
            redirect('/index.php?page=products');
        }
        
        $quantity = (float)($_POST['quantity'] ?? 0);
        $operation = $_POST['operation'] ?? 'add';
        
        if ($quantity <= 0) {
            setFlashMessage('error', 'La cantidad debe ser mayor que cero');
            redirect('/index.php?page=products&action=adjust_stock&id=' . $id);
        }
        
        // Verificar si hay suficiente stock para restar
        if ($operation === 'subtract' && $quantity > $product['stock']) {
            setFlashMessage('error', 'No hay suficiente stock para restar esta cantidad');
            redirect('/index.php?page=products&action=adjust_stock&id=' . $id);
        }
        
        // Actualizar stock
        $result = $this->product->updateStock($id, $quantity, $operation);
        
        if ($result) {
            setFlashMessage('success', 'Stock actualizado correctamente');
            
            // Si se ha configurado el registro de transacciones de inventario
            if (isset($_POST['register_transaction']) && $_POST['register_transaction'] == 1) {
                // Aquí podrías registrar una transacción de inventario
                // Esto requeriría un modelo adicional para transacciones de inventario
            }
            
            redirect('/index.php?page=products');
        } else {
            setFlashMessage('error', 'Error al actualizar el stock');
            redirect('/index.php?page=products&action=adjust_stock&id=' . $id);
        }
    }
    
    /**
     * Validar datos de producto
     */
    private function validateProduct($data) {
        $errors = [];
        
        // Validar código
        if (empty($data['code'])) {
            $errors['code'] = 'El código es obligatorio';
        }
        
        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es obligatorio';
        }
        
        // Validar precio de venta
        if (!isset($data['sale_price']) || $data['sale_price'] === '') {
            $errors['sale_price'] = 'El precio de venta es obligatorio';
        } elseif (!is_numeric($data['sale_price'])) {
            $errors['sale_price'] = 'El precio de venta debe ser un número';
        }
        
        // Validar campos personalizados
        $customFields = getCustomFields('products');
        foreach ($customFields as $field) {
            $fieldName = 'custom_' . $field['id'];
            
            if ($field['required'] && empty($data[$fieldName])) {
                $errors[$fieldName] = 'Este campo es obligatorio';
            }
        }
        
        return $errors;
    }
}