<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * User dashboard widget config (visibility and order) per module.
 */
class DashboardConfig
{
    public const MODULE_GRIEVANCE = 'grievance';

    /** Default widget keys for grievance dashboard (order matters) */
    public const GRIEVANCE_WIDGETS_DEFAULT = [
        'total',
        'status_breakdown',
        'trend',
        'by_project',
        'in_progress_levels',
        'recent',
    ];

    public static function get(string $module): array
    {
        $userId = Auth::id();
        if (!$userId) return self::defaultConfig($module);

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT config FROM user_dashboard_config WHERE user_id = ? AND module = ?');
        $stmt->execute([$userId, $module]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row || $row->config === null || $row->config === '') {
            return self::defaultConfig($module);
        }
        $decoded = json_decode($row->config, true);
        return is_array($decoded) ? $decoded : self::defaultConfig($module);
    }

    public static function save(string $module, array $config): void
    {
        $userId = Auth::id();
        if (!$userId) return;

        $db = Database::getInstance();
        $json = json_encode($config);
        $stmt = $db->prepare('INSERT INTO user_dashboard_config (user_id, module, config) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE config = VALUES(config)');
        $stmt->execute([$userId, $module, $json]);
    }

    public static function defaultConfig(string $module): array
    {
        if ($module === self::MODULE_GRIEVANCE) {
            return [
                'widgets' => self::GRIEVANCE_WIDGETS_DEFAULT,
                'order'   => self::GRIEVANCE_WIDGETS_DEFAULT,
            ];
        }
        return ['widgets' => [], 'order' => []];
    }

    /** Returns list of widget keys to show (in order) for the module */
    public static function visibleWidgets(string $module): array
    {
        $config = self::get($module);
        $order = $config['order'] ?? [];
        $widgets = $config['widgets'] ?? [];
        $visible = array_intersect($order, $widgets);
        return array_values($visible);
    }
}
