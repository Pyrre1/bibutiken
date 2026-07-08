CREATE TABLE settings (
    `key`   VARCHAR(50)  NOT NULL PRIMARY KEY,
    value   VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT INTO settings (`key`, value) VALUES ('preorder_enabled', '1');