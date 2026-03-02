<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * Per-user development preferences (e.g. show system status in footer).
 * Stored in user_dashboard_config with module = 'development'.
 */
class DevelopmentSettings
{
    public const MODULE = 'development';

    public static function isStatusCheckEnabled(): bool
    {
        $userId = Auth::id();
        if (!$userId) {
            return false;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT config FROM user_dashboard_config WHERE user_id = ? AND module = ?');
        $stmt->execute([$userId, self::MODULE]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row || $row->config === null || $row->config === '') {
            return false;
        }
        $decoded = json_decode($row->config, true);
        return !empty($decoded['status_check']);
    }

    public static function get(): array
    {
        return [
            'status_check' => self::isStatusCheckEnabled(),
        ];
    }

    public static function save(array $config): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }
        $statusCheck = !empty($config['status_check']);
        $db = Database::getInstance();
        $json = json_encode(['status_check' => $statusCheck]);
        $stmt = $db->prepare('INSERT INTO user_dashboard_config (user_id, module, config) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE config = VALUES(config)');
        $stmt->execute([$userId, self::MODULE, $json]);
    }
}
