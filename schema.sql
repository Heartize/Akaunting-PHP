CREATE DATABASE IF NOT EXISTS akaunting_db;
USE akaunting_db;

-- Configuración
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_tax_number VARCHAR(50),
    company_address TEXT,
    company_email VARCHAR(100),
    company_phone VARCHAR(50),
    currency VARCHAR(10) DEFAULT 'EUR',
    decimal_separator CHAR(1) DEFAULT ',',
    thousands_separator CHAR(1) DEFAULT '.',
    date_format VARCHAR(20) DEFAULT 'd/m/Y',
    tax_rate DECIMAL(5,2) DEFAULT 21.00,
    fiscal_year VARCHAR(10) DEFAULT '01-12',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Clientes
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(50),
    address TEXT,
    tax_number VARCHAR(50),
    website VARCHAR(255),
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'EUR',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Proveedores
CREATE TABLE vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(50),
    address TEXT,
    tax_number VARCHAR(50),
    website VARCHAR(255),
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'EUR',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categorías de productos
CREATE TABLE product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Productos
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    purchase_price DECIMAL(15,2) DEFAULT 0.00,
    sale_price DECIMAL(15,2) DEFAULT 0.00,
    tax_rate DECIMAL(5,2) DEFAULT 21.00,
    unit VARCHAR(20) DEFAULT 'unidad',
    stock INT DEFAULT 0,
    min_stock INT DEFAULT 0,
    location VARCHAR(100),
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL
);

-- Facturas de ventas
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
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
CREATE TABLE invoice_items (
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
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(50) NOT NULL UNIQUE,
    vendor_id INT NOT NULL,
    status ENUM('draft', 'received', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
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
CREATE TABLE bill_items (
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
CREATE TABLE accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    number VARCHAR(100),
    currency VARCHAR(10) DEFAULT 'EUR',
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
CREATE TABLE transaction_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    color VARCHAR(7) DEFAULT '#5D5CDE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Transacciones (ingresos y gastos)
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('income', 'expense', 'transfer') NOT NULL,
    account_id INT NOT NULL,
    category_id INT,
    invoice_id INT,
    bill_id INT,
    amount DECIMAL(15,2) NOT NULL,
    date DATE NOT NULL,
    description TEXT,
    reference VARCHAR(100),
    contact_id INT,
    contact_type ENUM('client', 'vendor') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES transaction_categories(id) ON DELETE SET NULL
);

-- Transferencias entre cuentas
CREATE TABLE transfers (
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
CREATE TABLE custom_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(50) NOT NULL,  -- clients, products, invoices, etc.
    name VARCHAR(100) NOT NULL,
    type ENUM('text', 'number', 'date', 'select', 'textarea') NOT NULL,
    options TEXT, -- Para campos de selección, almacena opciones separadas por comas
    required TINYINT(1) DEFAULT 0,
    position INT DEFAULT 0,
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Valores de campos personalizados
CREATE TABLE custom_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    model_id INT NOT NULL,  -- ID del registro (cliente, producto, etc.)
    module VARCHAR(50) NOT NULL,  -- clients, products, invoices, etc.
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES custom_fields(id) ON DELETE CASCADE
);