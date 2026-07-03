ALTER TABLE products
    ADD COLUMN needs_manual_work TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE pre_orders
    ADD COLUMN is_delivered TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN has_manual_work TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE pre_order_items
    ADD COLUMN manual_work_status ENUM('ej_tillämplig','ej_behandlad','fardig') NOT NULL DEFAULT 'ej_tillämplig',
    ADD COLUMN actual_price_ore INT NULL;

INSERT INTO products (name, price_ore, active, sort_order, needs_manual_work)
VALUES ('Färdiglackad foderlåda EPS', 44500, 1, 5, 1);

UPDATE products SET sort_order = 3 WHERE id = 3;