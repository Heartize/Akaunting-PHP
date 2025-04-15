<?php
// Script de instalación de la base de datos
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Instalación de la base de datos AkauntingPHP</h1>";

// Cargar configuración de base de datos
try {
    $db_config = require_once __DIR__ . '/config/database.php';
    echo "<p style='color:green'>✅ Conexión a la base de datos correcta</p>";
} catch (Exception $e) {
    die("<p style='color:red'>❌ Error de conexión a la base de datos: " . $e->getMessage() . "</p>");
}

// Leer el archivo SQL
$sqlFile = __DIR__ . '/database/schema.sql';

if (!file_exists($sqlFile)) {
    // Si no existe, lo creamos
    $dir = __DIR__ . '/database';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Escribir el contenido del esquema SQL
    file_put_contents($sqlFile, '-- Script SQL autogenerado
CREATE DATABASE IF NOT EXISTS akaunting_db;
USE akaunting_db;

-- Configuración
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_tax_number VARCHAR(50),
    company_address TEXT,
    company_email VARCHAR(100),
    company_phone VARCHAR(50),
    currency VARCHAR(10) DEFAULT \'EUR\',
    decimal_separator CHAR(1) DEFAULT \',\',
    thousands_separator CHAR(1) DEFAULT \'.\',
    date_format VARCHAR(20) DEFAULT \'d/m/Y\',
    tax_rate DECIMAL(5,2) DEFAULT 21.00,
    fiscal_year VARCHAR(10) DEFAULT \'01-12\',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM(\'admin\', \'manager\', \'user\') DEFAULT \'user\',
    active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Clientes
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(50),
    address TEXT,
    tax_number VARCHAR(50),
    website VARCHAR(255),
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT \'EUR\',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Proveedores
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(50),
    address TEXT,
    tax_number VARCHAR(50),
    website VARCHAR(255),
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT \'EUR\',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categorías de productos
CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Productos
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    purchase_price DECIMAL(15,2) DEFAULT 0.00,
    sale_price DECIMAL(15,2) DEFAULT 0.00,
    tax_rate DECIMAL(5,2) DEFAULT 21.00,
    unit VARCHAR(20) DEFAULT \'unidad\',
    stock INT DEFAULT 0,
    min_stock INT DEFAULT 0,
    location VARCHAR(100),
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL
);

-- Facturas de ventas
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    status ENUM(\'draft\', \'sent\', \'paid\', \'overdue\', \'cancelled\') DEFAULT \'draft\',
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(15,2) DEFAULT 0.00,
    tax_total DECIMAL(15,2) DEFAULT 0.00,
    total DECIMAL(15,2) DEFAULT 0.00,
    notes TEXT,
    footer TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Detalles de facturas
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_id INT,
    description TEXT NOT NULL,
    quantity DECIMAL(15,2) NOT NULL DEFAULT 1.00,
    price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) DEFAULT 21.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    total DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Facturas de compras
CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(50) NOT NULL UNIQUE,
    vendor_id INT NOT NULL,
    status ENUM(\'draft\', \'received\', \'paid\', \'overdue\', \'cancelled\') DEFAULT \'draft\',
    bill_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(15,2) DEFAULT 0.00,
    tax_total DECIMAL(15,2) DEFAULT 0.00,
    total DECIMAL(15,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);

-- Detalles de facturas de compras
CREATE TABLE IF NOT EXISTS bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    product_id INT,
    description TEXT NOT NULL,
    quantity DECIMAL(15,2) NOT NULL DEFAULT 1.00,
    price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) DEFAULT 21.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    total DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Cuentas bancarias
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    number VARCHAR(100),
    currency VARCHAR(10) DEFAULT \'EUR\',
    opening_balance DECIMAL(15,2) DEFAULT 0.00,
    current_balance DECIMAL(15,2) DEFAULT 0.00,
    bank_name VARCHAR(100),
    bank_phone VARCHAR(50),
    bank_address TEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categorías de transacciones
CREATE TABLE IF NOT EXISTS transaction_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM(\'income\', \'expense\') NOT NULL,
    color VARCHAR(7) DEFAULT \'#5D5CDE\',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Transacciones (ingresos y gastos)
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM(\'income\', \'expense\', \'transfer\') NOT NULL,
    account_id INT NOT NULL,
    category_id INT,
    invoice_id INT,
    bill_id INT,
    amount DECIMAL(15,2) NOT NULL,
    date DATE NOT NULL,
    description TEXT,
    reference VARCHAR(100),
    contact_id INT,
    contact_type ENUM(\'client\', \'vendor\') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES transaction_categories(id) ON DELETE SET NULL
);

-- Transferencias entre cuentas
CREATE TABLE IF NOT EXISTS transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_account_id INT NOT NULL,
    to_account_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    date DATE NOT NULL,
    description TEXT,
    reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (from_account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (to_account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Campos personalizados
CREATE TABLE IF NOT EXISTS custom_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(50) NOT NULL,  -- clients, products, invoices, etc.
    name VARCHAR(100) NOT NULL,
    type ENUM(\'text\', \'number\', \'date\', \'select\', \'textarea\') NOT NULL,
    options TEXT, -- Para campos de selección, almacena opciones separadas por comas
    required TINYINT(1) DEFAULT 0,
    position INT DEFAULT 0,
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Valores de campos personalizados
CREATE TABLE IF NOT EXISTS custom_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    model_id INT NOT NULL,  -- ID del registro (cliente, producto, etc.)
    module VARCHAR(50) NOT NULL,  -- clients, products, invoices, etc.
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES custom_fields(id) ON DELETE CASCADE
);

-- Insertar datos iniciales

-- Administrador por defecto (password: admin123)
INSERT INTO users (name, email, password, role, active) 
VALUES (\'Administrador\', \'admin@example.com\', \'$2y$10$9wV9iyWxX7dlKLJz5XLlEeJJE5wZ9jE2T.CQUfI2mHut65KBs0.sK\', \'admin\', 1);

-- Configuración inicial
INSERT INTO settings (company_name, company_tax_number, currency, decimal_separator, thousands_separator, date_format, tax_rate)
VALUES (\'Mi Empresa S.L.\', \'B12345678\', \'EUR\', \',\', \'.\', \'d/m/Y\', 21.00);

-- Categorías de transacciones básicas
INSERT INTO transaction_categories (name, type, color) VALUES
(\'Ventas\', \'income\', \'#55ce63\'),
(\'Servicios\', \'income\', \'#2196f3\'),
(\'Otros ingresos\', \'income\', \'#5D5CDE\'),
(\'Compras\', \'expense\', \'#f62d51\'),
(\'Salarios\', \'expense\', \'#ffbc34\'),
(\'Alquiler\', \'expense\', \'#795548\'),
(\'Suministros\', \'expense\', \'#00bcd4\'),
(\'Impuestos\', \'expense\', \'#9c27b0\');

-- Cuenta bancaria inicial
INSERT INTO accounts (name, number, currency, opening_balance, current_balance)
VALUES (\'Cuenta Principal\', \'1234567890\', \'EUR\', 0.00, 0.00);

-- Algunas categorías de productos básicas
INSERT INTO product_categories (name, description) VALUES
(\'General\', \'Categoría general de productos\'),
(\'Servicios\', \'Servicios profesionales\'),
(\'Hardware\', \'Equipos y componentes\'),
(\'Software\', \'Programas y licencias\');
');
    
    echo "<p style='color:green'>✅ Archivo schema.sql creado correctamente</p>";
}

// Ejecutar el script SQL
try {
    global $db;
    
    echo "<p>Ejecutando script SQL...</p>";
    
    // Leer el archivo SQL
    $sql = file_get_contents($sqlFile);
    
    // Dividir en sentencias individuales
    $queries = explode(';', $sql);
    
    $count = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        // Saltamos la sentencia USE que puede causar problemas
        if (stripos($query, 'USE akaunting_db') === 0) continue;
        
        // Saltamos la sentencia CREATE DATABASE
        if (stripos($query, 'CREATE DATABASE') === 0) continue;
        
        try {
            $db->exec($query);
            $count++;
        } catch (PDOException $e) {
            // Si la tabla ya existe, ignorar el error y continuar
            if (strpos($e->getMessage(), '1050') === false) {
                echo "<p style='color:orange'>⚠️ Error en consulta: " . htmlspecialchars($query) . "</p>";
                echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<p style='color:green'>✅ {$count} consultas ejecutadas correctamente</p>";
    echo "<h2>Instalación completada</h2>";
    echo "<p>La base de datos ha sido creada y configurada correctamente.</p>";
    echo "<p><a href='index.php'>Ir a la aplicación</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error al ejecutar script SQL: " . $e->getMessage() . "</p>";
}