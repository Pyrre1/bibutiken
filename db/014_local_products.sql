-- 014: Local farm products (honey + related products from own hives)

CREATE TABLE local_product_types (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  name      VARCHAR(100) NOT NULL,           -- e.g. 'Honung', 'Relaterade produkter'
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO local_product_types (name, sort_order) VALUES
  ('Honung',               1),
  ('Relaterade produkter', 2);

CREATE TABLE local_products (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  type_id         INT NOT NULL,
  size            VARCHAR(100) NOT NULL,       -- e.g. '500 g', '1 kg'
  name            VARCHAR(255) NOT NULL,       -- e.g. 'Sommarhonung'
  description     TEXT NULL,                  -- free text
  price_ore       INT NOT NULL,               -- price in öre
  active          TINYINT(1) NOT NULL DEFAULT 1,
  sort_order      INT NOT NULL DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES local_product_types(id)
) ENGINE=InnoDB;