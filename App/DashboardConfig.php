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
        'chart_status',
        'chart_trend',
        'chart_by_project',
        'chart_in_progress',
        'by_category',
        'by_type',
        'by_project',
        'in_progress_levels',
        'recent',
    ];

    /** Default chart options for grievance dashboard */
    public const GRIEVANCE_CHART_OPTIONS_DEFAULT = [
        'trend_months' => 12,
        'trend_type'   => 'bar', // 'bar' | 'line'
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
                'widgets'      => self::GRIEVANCE_WIDGETS_DEFAULT,
                'order'        => self::GRIEVANCE_WIDGETS_DEFAULT,
                'chart_options' => self::GRIEVANCE_CHART_OPTIONS_DEFAULT,
            ];
        }
        return ['widgets' => [], 'order' => []];
    }

    /** Returns chart options for the module (trend_months, trend_type, etc.) */
    public static function chartOptions(string $module): array
    {
        $config = self::get($module);
        $defaults = $module === self::MODULE_GRIEVANCE ? self::GRIEVANCE_CHART_OPTIONS_DEFAULT : [];
        $opts = $config['chart_options'] ?? [];
        return array_merge($defaults, is_array($opts) ? $opts : []);
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
