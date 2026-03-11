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
            'enable_notification_emails' => self::get('enable_notification_emails', '0') === '1',
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
        $enableNotificationEmails = !empty($data['enable_notification_emails']);
        self::set('enable_notification_emails', $enableNotificationEmails ? '1' : '0');
    }

    public static function getSecurityConfig(): object
    {
        $enabled = self::get('enable_email_2fa', '0') === '1';
        return (object) [
            'enable_email_2fa' => $enabled,
            '2fa_expiration_minutes' => (int) self::get('2fa_expiration_minutes', 15),
            'user_logout_after_minutes' => (int) self::get('user_logout_after_minutes', 30),
            // Login throttling / brute-force protection
            'login_throttle_enabled' => self::get('login_throttle_enabled', '1') === '1',
            'login_throttle_max_attempts' => (int) self::get('login_throttle_max_attempts', 5),
            'login_throttle_lockout_minutes' => (int) self::get('login_throttle_lockout_minutes', 15),
            // Password policy
            'password_min_length' => (int) self::get('password_min_length', 8),
            'password_require_upper' => self::get('password_require_upper', '1') === '1',
            'password_require_lower' => self::get('password_require_lower', '1') === '1',
            'password_require_number' => self::get('password_require_number', '1') === '1',
            'password_require_symbol' => self::get('password_require_symbol', '0') === '1',
            'password_expiry_days' => (int) self::get('password_expiry_days', 0),
            'password_history_limit' => (int) self::get('password_history_limit', 5),
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
        $logoutMins = max(0, min(10080, (int) ($data['user_logout_after_minutes'] ?? 30)));
        self::set('user_logout_after_minutes', (string) $logoutMins);

        // Login throttling settings
        $loginThrottleEnabled = !empty($data['login_throttle_enabled']);
        self::set('login_throttle_enabled', $loginThrottleEnabled ? '1' : '0');
        // When disabled we still persist last configured values so re-enabling restores them.
        $maxAttempts = (int) ($data['login_throttle_max_attempts'] ?? 5);
        // Hard bounds to keep values reasonable
        $maxAttempts = max(1, min(50, $maxAttempts));
        self::set('login_throttle_max_attempts', (string) $maxAttempts);

        $lockoutMinutes = (int) ($data['login_throttle_lockout_minutes'] ?? 15);
        // 1–1440 minutes (1 day) to allow flexibility but prevent absurd values
        $lockoutMinutes = max(1, min(1440, $lockoutMinutes));
        self::set('login_throttle_lockout_minutes', (string) $lockoutMinutes);

        // Password policy settings
        $minLength = (int) ($data['password_min_length'] ?? 8);
        $minLength = max(1, min(128, $minLength));
        self::set('password_min_length', (string) $minLength);

        $requireUpper = !empty($data['password_require_upper']);
        $requireLower = !empty($data['password_require_lower']);
        $requireNumber = !empty($data['password_require_number']);
        $requireSymbol = !empty($data['password_require_symbol']);
        self::set('password_require_upper', $requireUpper ? '1' : '0');
        self::set('password_require_lower', $requireLower ? '1' : '0');
        self::set('password_require_number', $requireNumber ? '1' : '0');
        self::set('password_require_symbol', $requireSymbol ? '1' : '0');

        $expiryDays = (int) ($data['password_expiry_days'] ?? 0);
        $expiryDays = max(0, min(3650, $expiryDays));
        self::set('password_expiry_days', (string) $expiryDays);

        $historyLimit = (int) ($data['password_history_limit'] ?? 5);
        $historyLimit = max(0, min(50, $historyLimit));
        self::set('password_history_limit', (string) $historyLimit);
    }

    /**
     * Branding (app name, company name, logo).
     */
    public static function getBrandingConfig(): object
    {
        // Defaults keep existing behavior if not configured
        $appName = self::get('app_name', 'PAPeR');
        $companyName = self::get('company_name', '');
        $logoPath = self::get('app_logo_path', '');
        return (object) [
            'app_name' => $appName,
            'company_name' => $companyName,
            'logo_path' => $logoPath,
        ];
    }

    public static function saveBrandingConfig(array $data): void
    {
        $appName = trim($data['app_name'] ?? '');
        $companyName = trim($data['company_name'] ?? '');
        if ($appName === '') {
            $appName = 'PAPeR';
        }
        self::set('app_name', $appName);
        self::set('company_name', $companyName);
        if (!empty($data['logo_path'])) {
            self::set('app_logo_path', (string) $data['logo_path']);
        }
    }
}
