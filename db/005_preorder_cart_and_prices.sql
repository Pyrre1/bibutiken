-- Add price in "öre" (1/100 of SEK) to products table to avoid floating point issues.
ALTER TABLE products
    ADD COLUMN price_ore INT NOT NULL DEFAULT 0;

-- Drop the old single-product-per-order pre_orders table (empty, test data only)
-- and replace with an order header + line items structure to support multi-product carts.
DROP TABLE IF EXISTS pre_orders;

CREATE TABLE pre_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- unit_price_ore is the price of the product at the time of order, in case prices change later.
CREATE TABLE pre_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pre_order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price_ore INT NOT NULL,
    FOREIGN KEY (pre_order_id) REFERENCES pre_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Seed starting products with placeholder prices (confirm real prices later).
INSERT INTO products (name, price_ore, active, sort_order) VALUES
('Bifor 12,5kg', 22000, 1, 1),
('Dulcofruct 2kg', 10500, 1, 2),
('Foderlåda obehandlad (håller ca 2-3 år)', 2500, 1, 3),
('Foderlåda lackad/målad (håller ca 5 år +)', 3500, 1, 4);