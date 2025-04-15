<?php
/**
 * Controlador para la gestión de cuentas bancarias
 */
class AccountController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Lista de cuentas bancarias
     */
    public function index() {
        // Parámetros de paginación
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = $GLOBALS['config']['default_pagination_limit'];
        $offset = ($page - 1) * $limit;
        
        // Obtener cuentas
        try {
            $query = "SELECT * FROM accounts";
            
            // Filtros
            $filters = [];
            $params = [];
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filters[] = "(name LIKE ? OR number LIKE ? OR bank_name LIKE ?)";
                $searchTerm = '%' . $_GET['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters)) {
                $query .= " WHERE " . implode(" AND ", $filters);
            }
            
            $query .= " ORDER BY name";
            $query .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            
            // Ejecutar con los parámetros
            $index = 1;
            foreach ($params as $param) {
                $stmt->bindValue($index++, $param);
            }
            
            $stmt->execute();
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total para paginación
            $countQuery = "SELECT COUNT(*) FROM accounts";
            
            if (!empty($filters)) {
                $countQuery .= " WHERE " . implode(" AND ", $filters);
            }
            
            $stmt = $this->db->prepare($countQuery);
            
            // Ejecutar con los parámetros (excepto offset y limit)
            $index = 1;
            foreach (array_slice($params, 0, -2) as $param) {
                $stmt->bindValue($index++, $param);
            }
            
            $stmt->execute();
            $total = $stmt->fetchColumn();
            
            // Calcular páginas
            $totalPages = ceil($total / $limit);
            
            // Cargar vista
            $pageTitle = 'Cuentas Bancarias';
            $includeSearch = true;
            $actionButton = 'Nueva Cuenta';
            $actionButtonUrl = url('/index.php?page=accounts&action=create');
            
            $breadcrumbs = [
                'Cuentas Bancarias' => null
            ];
            
            include __DIR__ . '/../views/accounts/index.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al cargar cuentas: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Formulario para crear una nueva cuenta
     */
    public function create() {
        // Monedas disponibles
        $currencies = [
            'EUR' => 'Euro (€)',
            'USD' => 'Dólar estadounidense ($)',
            'GBP' => 'Libra esterlina (£)',
            'MXN' => 'Peso mexicano ($)'
        ];
        
        // Cargar vista
        $pageTitle = 'Nueva Cuenta Bancaria';
        $breadcrumbs = [
            'Cuentas Bancarias' => url('/index.php?page=accounts'),
            'Nueva Cuenta' => null
        ];
        
        include __DIR__ . '/../views/accounts/form.php';
    }
    
    /**
     * Guardar una nueva cuenta
     */
    public function store() {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateAccount($_POST);
        
        if (empty($errors)) {
            try {
                // Verificar que el número de cuenta no exista
                if (!empty($_POST['number'])) {
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM accounts WHERE number = ?");
                    $stmt->execute([$_POST['number']]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        setFlashMessage('error', 'El número de cuenta ya existe');
                        redirect('/index.php?page=accounts&action=create');
                        return;
                    }
                }
                
                // Insertar nueva cuenta
                $query = "INSERT INTO accounts (
                            name, number, currency, opening_balance, current_balance,
                            bank_name, bank_phone, bank_address, enabled
                          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $openingBalance = !empty($_POST['opening_balance']) ? $_POST['opening_balance'] : 0;
                
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([
                    $_POST['name'],
                    $_POST['number'] ?? null,
                    $_POST['currency'],
                    $openingBalance,
                    $openingBalance, // El saldo actual es igual al saldo inicial al crear
                    $_POST['bank_name'] ?? null,
                    $_POST['bank_phone'] ?? null,
                    $_POST['bank_address'] ?? null,
                    isset($_POST['enabled']) ? 1 : 0
                ]);
                
                if ($result) {
                    setFlashMessage('success', 'Cuenta bancaria creada correctamente');
                    redirect('/index.php?page=accounts');
                } else {
                    setFlashMessage('error', 'Error al crear la cuenta bancaria');
                    redirect('/index.php?page=accounts&action=create');
                }
                
            } catch (Exception $e) {
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=accounts&action=create');
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=accounts&action=create');
        }
    }
    
    /**
     * Formulario para editar una cuenta
     */
    public function edit($id) {
        try {
            // Obtener la cuenta
            $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ?");
            $stmt->execute([$id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$account) {
                setFlashMessage('error', 'Cuenta bancaria no encontrada');
                redirect('/index.php?page=accounts');
            }
            
            // Monedas disponibles
            $currencies = [
                'EUR' => 'Euro (€)',
                'USD' => 'Dólar estadounidense ($)',
                'GBP' => 'Libra esterlina (£)',
                'MXN' => 'Peso mexicano ($)'
            ];
            
            // Cargar vista
            $pageTitle = 'Editar Cuenta Bancaria';
            $breadcrumbs = [
                'Cuentas Bancarias' => url('/index.php?page=accounts'),
                'Editar Cuenta' => null
            ];
            
            include __DIR__ . '/../views/accounts/form.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=accounts');
        }
    }
    
    /**
     * Actualizar una cuenta existente
     */
    public function update($id) {
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateAccount($_POST, true);
        
        if (empty($errors)) {
            try {
                // Verificar que el número de cuenta no exista para otra cuenta
                if (!empty($_POST['number'])) {
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM accounts WHERE number = ? AND id != ?");
                    $stmt->execute([$_POST['number'], $id]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        setFlashMessage('error', 'El número de cuenta ya existe');
                        redirect('/index.php?page=accounts&action=edit&id=' . $id);
                        return;
                    }
                }
                
                // Obtener el saldo actual antes de actualizar
                $stmt = $this->db->prepare("SELECT opening_balance, current_balance FROM accounts WHERE id = ?");
                $stmt->execute([$id]);
                $oldAccount = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Calcular el ajuste al saldo actual si cambia el saldo inicial
                $newOpeningBalance = !empty($_POST['opening_balance']) ? $_POST['opening_balance'] : 0;
                $adjustment = $newOpeningBalance - $oldAccount['opening_balance'];
                $newCurrentBalance = $oldAccount['current_balance'] + $adjustment;
                
                // Actualizar cuenta
                $query = "UPDATE accounts 
                          SET name = ?, number = ?, currency = ?, 
                              opening_balance = ?, current_balance = ?,
                              bank_name = ?, bank_phone = ?, bank_address = ?, 
                              enabled = ?, updated_at = CURRENT_TIMESTAMP
                          WHERE id = ?";
                
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([
                    $_POST['name'],
                    $_POST['number'] ?? null,
                    $_POST['currency'],
                    $newOpeningBalance,
                    $newCurrentBalance,
                    $_POST['bank_name'] ?? null,
                    $_POST['bank_phone'] ?? null,
                    $_POST['bank_address'] ?? null,
                    isset($_POST['enabled']) ? 1 : 0,
                    $id
                ]);
                
                if ($result) {
                    setFlashMessage('success', 'Cuenta bancaria actualizada correctamente');
                    redirect('/index.php?page=accounts');
                } else {
                    setFlashMessage('error', 'Error al actualizar la cuenta bancaria');
                    redirect('/index.php?page=accounts&action=edit&id=' . $id);
                }
                
            } catch (Exception $e) {
                setFlashMessage('error', 'Error: ' . $e->getMessage());
                redirect('/index.php?page=accounts&action=edit&id=' . $id);
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
            redirect('/index.php?page=accounts&action=edit&id=' . $id);
        }
    }
    
    /**
     * Ver detalles de una cuenta
     */
    public function show($id) {
        try {
            // Obtener la cuenta
            $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ?");
            $stmt->execute([$id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$account) {
                setFlashMessage('error', 'Cuenta bancaria no encontrada');
                redirect('/index.php?page=accounts');
            }
            
            // Obtener transacciones recientes
            $stmt = $this->db->prepare("
                SELECT t.*, tc.name as category_name
                FROM transactions t
                LEFT JOIN transaction_categories tc ON t.category_id = tc.id
                WHERE t.account_id = ?
                ORDER BY t.date DESC, t.id DESC
                LIMIT 10
            ");
            $stmt->execute([$id]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cargar vista
            $pageTitle = 'Detalles de Cuenta';
            $breadcrumbs = [
                'Cuentas Bancarias' => url('/index.php?page=accounts'),
                'Detalles' => null
            ];
            
            include __DIR__ . '/../views/accounts/show.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
            redirect('/index.php?page=accounts');
        }
    }
    
    /**
     * Eliminar una cuenta
     */
    public function delete($id) {
        // Verificar token CSRF si se envía por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken($_POST['csrf_token'] ?? '');
        }
        
        try {
            // Verificar si hay transacciones asociadas
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM transactions WHERE account_id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->fetchColumn() > 0) {
                setFlashMessage('error', 'No se puede eliminar la cuenta porque tiene transacciones asociadas');
                redirect('/index.php?page=accounts');
                return;
            }
            
            // Verificar si hay transferencias asociadas
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM transfers 
                WHERE from_account_id = ? OR to_account_id = ?
            ");
            $stmt->execute([$id, $id]);
            
            if ($stmt->fetchColumn() > 0) {
                setFlashMessage('error', 'No se puede eliminar la cuenta porque tiene transferencias asociadas');
                redirect('/index.php?page=accounts');
                return;
            }
            
            // Eliminar cuenta
            $stmt = $this->db->prepare("DELETE FROM accounts WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                setFlashMessage('success', 'Cuenta bancaria eliminada correctamente');
            } else {
                setFlashMessage('error', 'Error al eliminar la cuenta bancaria');
            }
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error: ' . $e->getMessage());
        }
        
        redirect('/index.php?page=accounts');
    }
    
    /**
     * Validar datos de cuenta
     */
    private function validateAccount($data, $isUpdate = false) {
        $errors = [];
        
        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es obligatorio';
        }
        
        // Validar moneda
        if (empty($data['currency'])) {
            $errors['currency'] = 'La moneda es obligatoria';
        }
        
        // Validar saldo inicial
        if (isset($data['opening_balance']) && $data['opening_balance'] !== '' && !is_numeric($data['opening_balance'])) {
            $errors['opening_balance'] = 'El saldo inicial debe ser un número';
        }
        
        return $errors;
    }
}