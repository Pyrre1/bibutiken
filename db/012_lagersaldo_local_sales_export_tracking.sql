-- 012: Lagersaldo, local sales, export tracking, price note on items

CREATE TABLE lagersaldo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  restocked_at DATE NOT NULL,
  calculated_price_ore INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE local_sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  sold_at DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE orders_exported_at (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pre_order_id INT NOT NULL,
  exported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pre_order_id) REFERENCES pre_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE pre_order_items
  ADD COLUMN actual_price_note VARCHAR(255) NULL,
  ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;