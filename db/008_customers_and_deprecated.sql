-- Products: soft delete support
ALTER TABLE products ADD COLUMN deprecated TINYINT(1) NOT NULL DEFAULT 0;

-- Customers
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Roles
CREATE TABLE customer_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE customer_role_assignments (
    customer_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (customer_id, role_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES customer_roles(id)
) ENGINE=InnoDB;

-- Seed roles
INSERT INTO customer_roles (name) VALUES 
    ('customer'),
    ('reminder'),
    ('newsletter'),
    ('experience');

-- Link pre_orders to customers
ALTER TABLE pre_orders
    ADD COLUMN customer_id INT NULL,
    ADD CONSTRAINT fk_preorder_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id);

-- Drop old columns
ALTER TABLE pre_orders
    DROP COLUMN customer_name,
    DROP COLUMN customer_email;