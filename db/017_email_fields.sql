-- Migration 017: Email system fields

-- ─────────────────────────────────────────────
-- 1. Pre-order email tracking columns
-- ─────────────────────────────────────────────
ALTER TABLE pre_orders
  ADD COLUMN info_sent_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN paminnelse_sent_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN reminders_count INT NOT NULL DEFAULT 0;

-- ─────────────────────────────────────────────
-- 2. Mail log
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mail_log (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sent_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  recipient    VARCHAR(255) NOT NULL,
  subject      VARCHAR(500) NOT NULL,
  role         VARCHAR(100) NULL,
  order_id     INT UNSIGNED NULL,
  customer_id  INT UNSIGNED NULL,
  success      TINYINT(1) NOT NULL DEFAULT 0,
  error_msg    TEXT NULL,
  INDEX idx_recipient (recipient),
  INDEX idx_sent_at   (sent_at),
  INDEX idx_order_id  (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- 3. Email templates
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mail_templates (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  namn        VARCHAR(100) NOT NULL,
  amne        VARCHAR(500) NOT NULL,
  brodtext    TEXT NOT NULL,
  roll        VARCHAR(100) NOT NULL DEFAULT 'vinterfoder',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-populate the three starting templates
INSERT INTO mail_templates (namn, amne, brodtext, roll) VALUES
(
  'Orderbekräftelse',
  'Tack för din föranmälan – Strängnäs Biredskap',
  'Hej {namn},\n\nTack för din föranmälan! Vi bekräftar att vi tagit emot din beställning:\n\n{vara}\n\nDu kommer att bli kontaktad när varorna finns tillgängliga för avhämtning.\n\nMed vänliga hälsningar,\nSträngnäs Biredskap',
  'vinterfoder'
),
(
  'Varor anlända',
  'Dina varor har anlänt – Strängnäs Biredskap',
  'Hej {namn},\n\nDina föranmälda varor har nu anlänt och är redo för avhämtning.\n\nDin beställning:\n{vara}\n\nTotalt pris: {pris} kr\n\nKontakta oss för att boka tid för avhämtning.\n\nMed vänliga hälsningar,\nSträngnäs Biredskap',
  'vinterfoder'
),
(
  'Påminnelse',
  'Påminnelse: Dina varor väntar – Strängnäs Biredskap',
  'Hej {namn},\n\nDetta är en påminnelse om att dina föranmälda varor fortfarande väntar på avhämtning.\n\nDin beställning:\n{vara}\n\nTotalt pris: {pris} kr\n\nKontakta oss snarast för att hämta dina varor.\n\nMed vänliga hälsningar,\nSträngnäs Biredskap',
  'vinterfoder'
);

-- ─────────────────────────────────────────────
-- 4. Timestamps on role assignments + unsubscribe history table
-- ─────────────────────────────────────────────
ALTER TABLE customer_role_assignments
  ADD COLUMN IF NOT EXISTS subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS customer_role_unsubscribed (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id     INT NOT NULL,
  role_id         INT NOT NULL,
  unsubscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customer (customer_id),
  INDEX idx_role     (role_id),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id)     REFERENCES customer_roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- 5. Mark which roles can be unsubscribed via email link
-- ─────────────────────────────────────────────
ALTER TABLE customer_roles
  ADD COLUMN IF NOT EXISTS unsubscribable TINYINT(1) NOT NULL DEFAULT 0;

UPDATE customer_roles SET unsubscribable = 1 WHERE name IN ('vinterfoder', 'nyhetsbrev', 'upplevelse', 'paminnelse');