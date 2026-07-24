-- 016: Site banners for dynamic public notices

CREATE TABLE site_banners (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    message    TEXT NOT NULL,
    type       ENUM('info', 'warning', 'success') NOT NULL DEFAULT 'info',
    active     TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;