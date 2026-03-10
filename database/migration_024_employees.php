<?php
/**
 * Migration 024: Employees table (Human Resources)
 */
return [
    'name' => 'migration_024_employees',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS employees (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) DEFAULT NULL,
                department VARCHAR(100) DEFAULT NULL,
                position VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_name (name),
                INDEX idx_department (department)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $adminId = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn();
        if ($adminId) {
            $caps = ['view_employees', 'add_employees', 'edit_employees', 'delete_employees'];
            $ins = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');
            foreach ($caps as $c) {
                $ins->execute([$adminId, $c]);
            }
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS employees');
        $db->prepare('DELETE FROM role_capabilities WHERE capability IN (?, ?, ?, ?)')
            ->execute(['view_employees', 'add_employees', 'edit_employees', 'delete_employees']);
    },
];
