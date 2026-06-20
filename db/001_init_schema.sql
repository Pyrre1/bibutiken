CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE pre_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE hours_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('default','long_term','week_specific') NOT NULL,
    header_text VARCHAR(255) NULL,
    free_text_1 TEXT NULL,
    free_text_2 TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    week_number INT NULL,
    year INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE hours_plan_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    day_of_week TINYINT NOT NULL,
    open_time TIME NULL,
    close_time TIME NULL,
    closed TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (plan_id) REFERENCES hours_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB;