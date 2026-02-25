<?php
/**
 * Copy to database/migration_XXX_your_name.php
 * Replace XXX with next number (e.g. 013), your_name with table/feature name.
 * Then run: php cli/migrate.php
 */
return [
    'name' => 'migration_XXX_resource_names',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE resource_names (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS resource_names');
    },
];
