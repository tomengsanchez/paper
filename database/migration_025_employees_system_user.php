<?php
/**
 * Migration 025: Add is_active to users; add is_system_user and user_id to employees.
 * - users.is_active: TINYINT(1) DEFAULT 1 (1=active, 0=inactive; inactive users cannot log in)
 * - employees.is_system_user: TINYINT(1) DEFAULT 0
 * - employees.user_id: FK to users, nullable (links employee to system user when is_system_user=1)
 */
return [
    'name' => 'migration_025_employees_system_user',
    'up' => function (\PDO $db): void {
        // Add is_active to users (default 1 for existing users)
        $db->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role_id");
        // Add is_system_user and user_id to employees
        $db->exec("ALTER TABLE employees ADD COLUMN is_system_user TINYINT(1) NOT NULL DEFAULT 0 AFTER position");
        $db->exec("ALTER TABLE employees ADD COLUMN user_id INT NULL DEFAULT NULL AFTER is_system_user");
        $db->exec("
            ALTER TABLE employees
            ADD CONSTRAINT fk_employees_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ");
        $db->exec("CREATE INDEX idx_employees_user_id ON employees (user_id)");
    },
    'down' => function (\PDO $db): void {
        $db->exec("ALTER TABLE employees DROP FOREIGN KEY fk_employees_user");
        $db->exec("ALTER TABLE employees DROP INDEX idx_employees_user_id");
        $db->exec("ALTER TABLE employees DROP COLUMN user_id");
        $db->exec("ALTER TABLE employees DROP COLUMN is_system_user");
        $db->exec("ALTER TABLE users DROP COLUMN is_active");
    },
];
