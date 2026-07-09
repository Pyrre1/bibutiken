UPDATE customer_roles SET name = 'vinterfoder'  WHERE name = 'customer';
UPDATE customer_roles SET name = 'paminnelse'   WHERE name = 'reminder';
UPDATE customer_roles SET name = 'nyhetsbrev'   WHERE name = 'newsletter';
UPDATE customer_roles SET name = 'upplevelse'   WHERE name = 'experience';
INSERT INTO customer_roles (name) VALUES ('ingen_mejl');