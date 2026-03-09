CREATE DATABASE IF NOT EXISTS pos_flex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_flex;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(100),
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'pcs',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type ENUM('in','out') NOT NULL,
    qty INT NOT NULL,
    notes VARCHAR(255),
    user_id INT,
    created_at DATETIME,
    CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(120),
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(30) DEFAULT 'cash',
    user_id INT,
    created_at DATETIME,
    CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_sale_item_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_sale_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE settings (
    id INT PRIMARY KEY,
    settings_json JSON NOT NULL,
    updated_at DATETIME
);

CREATE TABLE ai_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    payload_json JSON NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at DATETIME
);

CREATE TABLE ai_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NULL,
    provider_name VARCHAR(80),
    suggestion_text TEXT,
    created_at DATETIME,
    CONSTRAINT fk_ai_suggestion_task FOREIGN KEY (task_id) REFERENCES ai_tasks(id) ON DELETE SET NULL
);

INSERT INTO users (name, email, password_hash, role) VALUES
('Administrator', 'admin@posflex.local', '$2y$12$2a9c7oQO/VPJyTSzN4Y.LeKUXws9CjlC4EYpdntGVLnM8QhRbNkG2', 'admin');
-- Password default: admin123

INSERT INTO settings (id, settings_json, updated_at)
VALUES (1, JSON_OBJECT('business_name','POS Flex Demo','business_type','Retail Umum','address','-','phone','-','modules',JSON_ARRAY('inventory','sales','ai')), NOW())
ON DUPLICATE KEY UPDATE settings_json=VALUES(settings_json), updated_at=VALUES(updated_at);
