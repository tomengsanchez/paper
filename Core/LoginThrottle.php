<?php
namespace Core;

use App\Models\AppSettings;

class LoginThrottle
{
    private const STORAGE_FILE = ROOT . '/logs/login_throttle.json';

    private static function getConfig(): object
    {
        return AppSettings::getSecurityConfig();
    }

    public static function getClientIp(): string
    {
        $candidates = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $value = $_SERVER[$key];
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $value);
                $value = trim($parts[0]);
            }
            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }

        return '0.0.0.0';
    }

    public static function isBlocked(string $ip): bool
    {
        $config = self::getConfig();
        if (empty($config->login_throttle_enabled)) {
            return false;
        }

        $state = self::loadState();
        if (!isset($state[$ip])) {
            return false;
        }

        $blockedUntil = isset($state[$ip]['blocked_until']) ? (int) $state[$ip]['blocked_until'] : 0;
        if ($blockedUntil > time()) {
            return true;
        }

        // Lockout period has expired – clear entry.
        unset($state[$ip]);
        self::saveState($state);
        return false;
    }

    public static function recordFailure(string $ip): void
    {
        $config = self::getConfig();
        if (empty($config->login_throttle_enabled)) {
            return;
        }

        $state = self::loadState();
        $now = time();

        $maxAttempts = (int) ($config->login_throttle_max_attempts ?? 5);
        $maxAttempts = max(1, min(50, $maxAttempts));

        $lockoutMinutes = (int) ($config->login_throttle_lockout_minutes ?? 15);
        $lockoutMinutes = max(1, min(1440, $lockoutMinutes));

        $entry = $state[$ip] ?? [
            'count' => 0,
            'blocked_until' => 0,
        ];

        // If currently blocked and still within lockout window, keep as-is.
        if (!empty($entry['blocked_until']) && (int) $entry['blocked_until'] > $now) {
            return;
        }

        $entry['count'] = (int) ($entry['count'] ?? 0) + 1;

        if ($entry['count'] >= $maxAttempts) {
            $entry['blocked_until'] = $now + ($lockoutMinutes * 60);
            $entry['count'] = 0;
        }

        $state[$ip] = $entry;
        self::saveState($state);
    }

    public static function clear(string $ip): void
    {
        $config = self::getConfig();
        if (empty($config->login_throttle_enabled)) {
            return;
        }

        $state = self::loadState();
        if (isset($state[$ip])) {
            unset($state[$ip]);
            self::saveState($state);
        }
    }

    private static function loadState(): array
    {
        $file = self::STORAGE_FILE;
        if (!is_file($file)) {
            return [];
        }
        $json = @file_get_contents($file);
        if ($json === false || $json === '') {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private static function saveState(array $state): void
    {
        $file = self::STORAGE_FILE;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        @file_put_contents($file, json_encode($state), LOCK_EX);
    }
}

