<?php
/**
 * Migration 024: Socio Economic form entries
 *
 * Stores submitted answers for Socio Economic forms.
 */
return [
    'name' => 'migration_024_socio_economic_entries',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS socio_economic_entries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                form_id INT NOT NULL,
                project_id INT NULL,
                profile_id INT NULL,
                data_json JSON NOT NULL,
                created_by INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_socio_entries_form (form_id),
                INDEX idx_socio_entries_project (project_id),
                INDEX idx_socio_entries_profile (profile_id),
                CONSTRAINT fk_socio_entries_form
                    FOREIGN KEY (form_id) REFERENCES socio_economic_forms(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_socio_entries_project
                    FOREIGN KEY (project_id) REFERENCES projects(id)
                    ON DELETE SET NULL,
                CONSTRAINT fk_socio_entries_profile
                    FOREIGN KEY (profile_id) REFERENCES profiles(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec("DROP TABLE IF EXISTS socio_economic_entries");
    },
];

