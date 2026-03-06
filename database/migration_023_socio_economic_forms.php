<?php
/**
 * Migration 023: Socio Economic form builder
 *
 * - socio_economic_forms: form definitions per project
 * - socio_economic_fields: field definitions (including repeatable + conditions)
 */
return [
    'name' => 'migration_023_socio_economic_forms',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS socio_economic_forms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_socio_forms_project
                    FOREIGN KEY (project_id) REFERENCES projects(id)
                    ON DELETE SET NULL,
                INDEX idx_socio_forms_project (project_id),
                INDEX idx_socio_forms_title (title)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS socio_economic_fields (
                id INT AUTO_INCREMENT PRIMARY KEY,
                form_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                description VARCHAR(255) NULL,
                type VARCHAR(50) NOT NULL,
                is_required TINYINT(1) NOT NULL DEFAULT 0,
                is_repeatable TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                condition_json TEXT NULL,
                custom_html TEXT NULL,
                settings_json TEXT NULL,
                CONSTRAINT fk_socio_fields_form
                    FOREIGN KEY (form_id) REFERENCES socio_economic_forms(id)
                    ON DELETE CASCADE,
                INDEX idx_socio_fields_form_sort (form_id, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec("DROP TABLE IF EXISTS socio_economic_fields");
        $db->exec("DROP TABLE IF EXISTS socio_economic_forms");
    },
];

