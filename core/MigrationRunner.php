<?php
namespace Core;

/**
 * Database migration runner.
 *
 * Scans database/migration_*.php files, runs those not yet applied.
 * Migration format: return ['name' => string, 'up' => callable, 'down' => ?callable]
 */
class MigrationRunner
{
    private \PDO $db;
    private string $migrationsDir;
    private string $table = 'migrations';

    public function __construct(\PDO $db, string $migrationsDir = null)
    {
        $this->db = $db;
        $this->migrationsDir = $migrationsDir ?? (ROOT . '/database');
    }

    public function ensureMigrationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                ran_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }

    /** @return string[] */
    public function getRanMigrations(): array
    {
        $this->ensureMigrationsTable();
        $stmt = $this->db->query("SELECT name FROM {$this->table} ORDER BY id");
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
    }

    /** @return string[] Migration file paths sorted by name */
    public function getPendingMigrations(): array
    {
        $ran = array_flip($this->getRanMigrations());
        $files = glob($this->migrationsDir . '/migration_*.php');
        $pending = [];
        foreach ($files as $f) {
            $m = require $f;
            $name = is_array($m) && isset($m['name']) ? $m['name'] : basename($f, '.php');
            if (!isset($ran[$name])) {
                $pending[$name] = $f;
            }
        }
        ksort($pending);
        return array_values($pending);
    }

    /** @return array{ran: int, errors: array} */
    public function runPending(): array
    {
        $pending = $this->getPendingMigrations();
        $ran = 0;
        $errors = [];

        foreach ($pending as $file) {
            try {
                $m = require $file;
                if (!is_array($m) || !isset($m['up']) || !is_callable($m['up'])) {
                    $errors[] = basename($file) . ': invalid migration (missing "up" callable)';
                    continue;
                }
                $name = $m['name'] ?? basename($file, '.php');
                ($m['up'])($this->db);
                $stmt = $this->db->prepare("INSERT INTO {$this->table} (name) VALUES (?)");
                $stmt->execute([$name]);
                $ran++;
            } catch (\Throwable $e) {
                $errors[] = basename($file) . ': ' . $e->getMessage();
            }
        }

        return ['ran' => $ran, 'errors' => $errors];
    }

    public function status(): array
    {
        $ran = $this->getRanMigrations();
        $files = glob($this->migrationsDir . '/migration_*.php');
        $all = [];
        foreach ($files as $f) {
            $m = require $f;
            $name = is_array($m) && isset($m['name']) ? $m['name'] : basename($f, '.php');
            $all[] = [
                'name' => $name,
                'file' => basename($f),
                'status' => in_array($name, $ran) ? 'ran' : 'pending',
            ];
        }
        usort($all, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $all;
    }
}
