<?php
/**
 * Example module: items table (scaffold sample CRUD).
 */
return [
    'name' => 'migration_012_example_items',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS items');
    },
];
