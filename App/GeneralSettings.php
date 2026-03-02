<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * Per-user general system preferences (region, timezone).
 * Stored in user_dashboard_config with module = 'general'.
 */
class GeneralSettings
{
    public const MODULE = 'general';

    public const DEFAULT_REGION = '';
    public const DEFAULT_TIMEZONE = 'UTC';

    /** @return array{region: string, timezone: string} */
    public static function get(): array
    {
        $userId = Auth::id();
        $out = ['region' => self::DEFAULT_REGION, 'timezone' => self::DEFAULT_TIMEZONE];
        if (!$userId) {
            return $out;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT config FROM user_dashboard_config WHERE user_id = ? AND module = ?');
        $stmt->execute([$userId, self::MODULE]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row || $row->config === null || $row->config === '') {
            return $out;
        }
        $decoded = json_decode($row->config, true);
        if (is_array($decoded)) {
            if (isset($decoded['region'])) {
                $out['region'] = (string) $decoded['region'];
            }
            if (isset($decoded['timezone']) && (string) $decoded['timezone'] !== '') {
                $out['timezone'] = (string) $decoded['timezone'];
            }
        }
        return $out;
    }

    public static function save(array $config): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }
        $region = trim((string) ($config['region'] ?? ''));
        $timezone = trim((string) ($config['timezone'] ?? self::DEFAULT_TIMEZONE));
        if ($timezone === '' || !in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = self::DEFAULT_TIMEZONE;
        }
        $db = Database::getInstance();
        $json = json_encode(['region' => $region, 'timezone' => $timezone]);
        $stmt = $db->prepare('INSERT INTO user_dashboard_config (user_id, module, config) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE config = VALUES(config)');
        $stmt->execute([$userId, self::MODULE, $json]);
    }

    /** Regions for dropdown (value => label). */
    public static function regions(): array
    {
        return [
            '' => '— Select region —',
            'asia_pacific' => 'Asia Pacific',
            'south_asia'   => 'South Asia',
            'east_asia'    => 'East Asia',
            'europe'       => 'Europe',
            'americas'     => 'Americas',
            'africa'       => 'Africa',
            'middle_east'  => 'Middle East',
        ];
    }

    /** All valid PHP timezones for dropdown. */
    public static function timezones(): array
    {
        return timezone_identifiers_list();
    }
}
