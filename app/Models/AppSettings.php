<?php
namespace App\Models;

use Core\Database;

class AppSettings
{
    public static function get(string $key, $default = null)
    {
        $stmt = Database::getInstance()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v !== false ? $v : $default;
    }

    public static function set(string $key, string $value): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([$key, $value]);
    }

    public static function getEmailConfig(): object
    {
        return (object) [
            'smtp_host'     => self::get('smtp_host', ''),
            'smtp_port'     => (int) self::get('smtp_port', 587),
            'smtp_username' => self::get('smtp_username', ''),
            'smtp_password' => self::get('smtp_password', ''),
            'smtp_encryption' => self::get('smtp_encryption', 'tls'),
            'from_email'    => self::get('from_email', ''),
            'from_name'     => self::get('from_name', ''),
        ];
    }

    public static function saveEmailConfig(array $data): void
    {
        $keys = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_encryption', 'from_email', 'from_name'];
        foreach ($keys as $k) {
            $v = $data[$k] ?? '';
            if ($k === 'smtp_port') $v = (string) ((int) $v ?: 587);
            self::set($k, (string) $v);
        }
        if (($data['smtp_password'] ?? '') !== '') {
            self::set('smtp_password', $data['smtp_password']);
        }
    }

    public static function getSecurityConfig(): object
    {
        $enabled = self::get('enable_email_2fa', '0') === '1';
        return (object) [
            'enable_email_2fa' => $enabled,
            '2fa_expiration_minutes' => (int) self::get('2fa_expiration_minutes', 15),
        ];
    }

    public static function saveSecurityConfig(array $data): void
    {
        $enabled = !empty($data['enable_email_2fa']);
        self::set('enable_email_2fa', $enabled ? '1' : '0');
        if ($enabled) {
            $mins = max(1, min(1440, (int) ($data['2fa_expiration_minutes'] ?? 15)));
            self::set('2fa_expiration_minutes', (string) $mins);
        }
    }
}
