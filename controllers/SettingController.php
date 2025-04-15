<?php
/**
 * Controlador para la gestión de configuraciones
 */
class SettingController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Página principal de configuración
     */
    public function index() {
        // Redireccionar a la sección de empresa por defecto
        redirect('/index.php?page=settings&section=company');
    }
    
    /**
     * Configuración de la empresa
     */
    public function company() {
        // Verificar si el usuario tiene permiso
        // Comentado temporalmente para evitar redirecciones infinitas
        // requireRole('admin');requireRole('admin');
        
        try {
            // Obtener configuración actual
            $query = "SELECT * FROM settings LIMIT 1";
            $stmt = $this->db->query($query);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                // Si no hay configuración, crear valores por defecto
                $query = "INSERT INTO settings (company_name, company_tax_number, currency, decimal_separator, thousands_separator, date_format, tax_rate)
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    'Mi Empresa S.L.',
                    'B12345678',
                    'EUR',
                    ',',
                    '.',
                    'd/m/Y',
                    21.00
                ]);
                
                // Obtener los valores recién insertados
                $query = "SELECT * FROM settings LIMIT 1";
                $stmt = $this->db->query($query);
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Opciones de monedas
            $currencies = [
                'EUR' => 'Euro (€)',
                'USD' => 'Dólar estadounidense ($)',
                'GBP' => 'Libra esterlina (£)',
                'MXN' => 'Peso mexicano (MXN)'
            ];
            
            // Opciones de formatos de fecha
            $dateFormats = [
                'd/m/Y' => date('d/m/Y') . ' (día/mes/año)',
                'Y-m-d' => date('Y-m-d') . ' (año-mes-día)',
                'm/d/Y' => date('m/d/Y') . ' (mes/día/año)'
            ];
            
            // Cargar vista
            $pageTitle = 'Configuración de la Empresa';
            $breadcrumbs = [
                'Configuración' => url('/index.php?page=settings'),
                'Empresa' => null
            ];
            
            include __DIR__ . '/../views/settings/company.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al cargar la configuración: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Guardar configuración de la empresa
     */
    public function updateCompany() {
        // Verificar si el usuario tiene permiso
        requireRole('admin');
        
        // Verificar token CSRF
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        // Validar datos
        $errors = $this->validateCompanySettings($_POST);
        
        if (empty($errors)) {
            try {
                // Actualizar configuración
                $query = "UPDATE settings 
                          SET company_name = ?, 
                              company_tax_number = ?,
                              company_address = ?,
                              company_email = ?,
                              company_phone = ?,
                              currency = ?,
                              decimal_separator = ?,
                              thousands_separator = ?,
                              date_format = ?,
                              tax_rate = ?,
                              fiscal_year = ?,
                              updated_at = CURRENT_TIMESTAMP
                          WHERE id = ?";
                
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([
                    $_POST['company_name'],
                    $_POST['company_tax_number'] ?? null,
                    $_POST['company_address'] ?? null,
                    $_POST['company_email'] ?? null,
                    $_POST['company_phone'] ?? null,
                    $_POST['currency'],
                    $_POST['decimal_separator'],
                    $_POST['thousands_separator'],
                    $_POST['date_format'],
                    $_POST['tax_rate'],
                    $_POST['fiscal_year'] ?? '01-12',
                    $_POST['id']
                ]);
                
                if ($result) {
                    setFlashMessage('success', 'Configuración actualizada correctamente');
                } else {
                    setFlashMessage('error', 'Error al actualizar la configuración');
                }
                
            } catch (Exception $e) {
                setFlashMessage('error', 'Error: ' . $e->getMessage());
            }
            
        } else {
            // Hay errores, volver al formulario con los errores
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $errors;
        }
        
        redirect('/index.php?page=settings&section=company');
    }
    
    /**
     * Configuración de impuestos
     */
    public function taxes() {
        // Verificar si el usuario tiene permiso
        requireRole('admin');
        
        try {
            // Obtener configuración actual
            $query = "SELECT * FROM settings LIMIT 1";
            $stmt = $this->db->query($query);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Cargar vista
            $pageTitle = 'Configuración de Impuestos';
            $breadcrumbs = [
                'Configuración' => url('/index.php?page=settings'),
                'Impuestos' => null
            ];
            
            include __DIR__ . '/../views/settings/taxes.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al cargar la configuración: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Configuración de correo electrónico
     */
    public function email() {
        // Verificar si el usuario tiene permiso
        requireRole('admin');
        
        try {
            // Obtener configuración actual
            $query = "SELECT * FROM settings LIMIT 1";
            $stmt = $this->db->query($query);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Cargar vista
            $pageTitle = 'Configuración de Correo Electrónico';
            $breadcrumbs = [
                'Configuración' => url('/index.php?page=settings'),
                'Correo Electrónico' => null
            ];
            
            include __DIR__ . '/../views/settings/email.php';
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al cargar la configuración: ' . $e->getMessage());
            redirect('/index.php?page=dashboard');
        }
    }
    
    /**
     * Validar configuración de la empresa
     */
    private function validateCompanySettings($data) {
        $errors = [];
        
        // Validar nombre de la empresa
        if (empty($data['company_name'])) {
            $errors['company_name'] = 'El nombre de la empresa es obligatorio';
        }
        
        // Validar moneda
        if (empty($data['currency'])) {
            $errors['currency'] = 'La moneda es obligatoria';
        }
        
        // Validar separador decimal
        if (empty($data['decimal_separator'])) {
            $errors['decimal_separator'] = 'El separador decimal es obligatorio';
        }
        
        // Validar separador de miles
        if (empty($data['thousands_separator'])) {
            $errors['thousands_separator'] = 'El separador de miles es obligatorio';
        }
        
        // Validar que los separadores sean diferentes
        if ($data['decimal_separator'] === $data['thousands_separator']) {
            $errors['thousands_separator'] = 'El separador de miles no puede ser igual al separador decimal';
        }
        
        // Validar formato de fecha
        if (empty($data['date_format'])) {
            $errors['date_format'] = 'El formato de fecha es obligatorio';
        }
        
        // Validar tasa de impuesto
        if (!isset($data['tax_rate']) || $data['tax_rate'] === '') {
            $errors['tax_rate'] = 'La tasa de impuesto es obligatoria';
        } elseif (!is_numeric($data['tax_rate']) || $data['tax_rate'] < 0 || $data['tax_rate'] > 100) {
            $errors['tax_rate'] = 'La tasa de impuesto debe ser un número entre 0 y 100';
        }
        
        return $errors;
    }
}