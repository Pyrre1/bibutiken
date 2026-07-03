ALTER TABLE pre_order_items
    ADD COLUMN needs_manual_work TINYINT(1) NOT NULL DEFAULT 0;

-- Backfill existing rows from their product
UPDATE pre_order_items i
JOIN products p ON p.id = i.product_id
SET i.needs_manual_work = p.needs_manual_work;