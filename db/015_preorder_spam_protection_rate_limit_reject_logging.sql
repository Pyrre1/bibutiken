-- 015: Preorder spam protection — rate limiting and rejection logging

CREATE TABLE preorder_rate_limit (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    hashed_ip    CHAR(64) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hashed_ip_time (hashed_ip, attempted_at)
) ENGINE=InnoDB;

CREATE TABLE preorder_rejected_log (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    hashed_ip       CHAR(64) NOT NULL,
    attempted_email VARCHAR(255) NULL,
    reason          ENUM('honeypot','timing','rate_limit','csrf') NOT NULL,
    rejected_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rejected_ip (hashed_ip),
    INDEX idx_rejected_at (rejected_at)
) ENGINE=InnoDB;