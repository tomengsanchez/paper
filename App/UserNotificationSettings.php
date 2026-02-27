<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * Per-user notification preferences (what to notify on).
 * Stored in user_dashboard_config with module = 'notification_preferences'.
 */
class UserNotificationSettings
{
    public const MODULE = 'notification_preferences';

    public const NOTIFY_NEW_PROFILE = 'notify_new_profile';
    public const NOTIFY_PROFILE_UPDATED = 'notify_profile_updated';
    public const NOTIFY_NEW_GRIEVANCE = 'notify_new_grievance';
    public const NOTIFY_GRIEVANCE_STATUS_CHANGE = 'notify_grievance_status_change';

    public static function defaultConfig(): array
    {
        return [
            self::NOTIFY_NEW_PROFILE            => true,
            self::NOTIFY_PROFILE_UPDATED        => true,
            self::NOTIFY_NEW_GRIEVANCE          => true,
            self::NOTIFY_GRIEVANCE_STATUS_CHANGE => true,
        ];
    }

    public static function get(): array
    {
        $userId = Auth::id();
        if (!$userId) {
            return self::defaultConfig();
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT config FROM user_dashboard_config WHERE user_id = ? AND module = ?');
        $stmt->execute([$userId, self::MODULE]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row || $row->config === null || $row->config === '') {
            return self::defaultConfig();
        }
        $decoded = json_decode($row->config, true);
        if (!is_array($decoded)) {
            return self::defaultConfig();
        }
        return array_merge(self::defaultConfig(), [
            self::NOTIFY_NEW_PROFILE            => !empty($decoded[self::NOTIFY_NEW_PROFILE]),
            self::NOTIFY_PROFILE_UPDATED        => !empty($decoded[self::NOTIFY_PROFILE_UPDATED] ?? true),
            self::NOTIFY_NEW_GRIEVANCE          => !empty($decoded[self::NOTIFY_NEW_GRIEVANCE]),
            self::NOTIFY_GRIEVANCE_STATUS_CHANGE => !empty($decoded[self::NOTIFY_GRIEVANCE_STATUS_CHANGE]),
        ]);
    }

    public static function save(array $config): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }
        $json = json_encode([
            self::NOTIFY_NEW_PROFILE            => !empty($config[self::NOTIFY_NEW_PROFILE]),
            self::NOTIFY_PROFILE_UPDATED        => !empty($config[self::NOTIFY_PROFILE_UPDATED]),
            self::NOTIFY_NEW_GRIEVANCE          => !empty($config[self::NOTIFY_NEW_GRIEVANCE]),
            self::NOTIFY_GRIEVANCE_STATUS_CHANGE => !empty($config[self::NOTIFY_GRIEVANCE_STATUS_CHANGE]),
        ]);
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO user_dashboard_config (user_id, module, config) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE config = VALUES(config)');
        $stmt->execute([$userId, self::MODULE, $json]);
    }
}
