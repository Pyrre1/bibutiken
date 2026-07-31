CREATE TABLE mail_template_roles (
    mail_template_id INT UNSIGNED NOT NULL,
    role_name        VARCHAR(64)  NOT NULL,
    PRIMARY KEY (mail_template_id, role_name),
    FOREIGN KEY (mail_template_id)
        REFERENCES mail_templates(id)
        ON DELETE CASCADE
);

-- Migrate existing single-column data
INSERT INTO mail_template_roles (mail_template_id, role_name)
SELECT id, roll FROM mail_templates WHERE roll IS NOT NULL AND roll != '';

-- Keep roll column for now as a fallback — drop in a later migration
-- once all reads go through mail_template_roles.