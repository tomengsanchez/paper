<?php
namespace Core;

class LoginThrottle
{
    private static ?string $dir = null;
    private static ?object $config = null;

    private static function dir(): string
    {
        if (self::$dir === null) {
            self::$dir = ROOT . '/logs/login_attempts';
            if (!is_dir(self::$dir)) {
                mkdir(self::$dir, 0750, true);
            }
        }
        return self::$dir;
    }

    private static function filePath(string $ip): string
    {
        return self::dir() . '/' . md5($ip) . '.json';
    }

    /**
     * Load and cache security configuration relevant to login throttling.
     */
    private static function getConfig(): object
    {
        if (self::$config === null) {
            // Uses App-level settings; keep defaults matching previous constants for backwards compatibility.
            $c = \App\Models\AppSettings::getSecurityConfig();
            // Ensure expected properties exist with sane defaults.
            if (!isset($c->login_throttle_enabled)) {
                $c->login_throttle_enabled = true;
            }
            if (!isset($c->login_throttle_max_attempts)) {
                $c->login_throttle_max_attempts = 5;
            }
            if (!isset($c->login_throttle_lockout_minutes)) {
                $c->login_throttle_lockout_minutes = 15;
            }
            self::$config = $c;
        }
        return self::$config;
    }

    /**
     * Resolve window (lockout) and max attempts from configuration.
     *
     * @return array{enabled: bool, windowSeconds: int, maxAttempts: int}
     */
    private static function getRuntimeSettings(): array
    {
        $c = self::getConfig();
        $enabled = !empty($c->login_throttle_enabled);
        // Lockout/window in seconds, clamped to reasonable bounds.
        $lockoutMinutes = (int) ($c->login_throttle_lockout_minutes ?? 15);
        $lockoutMinutes = max(1, min(1440, $lockoutMinutes));
        $windowSeconds = $lockoutMinutes * 60;

        $maxAttempts = (int) ($c->login_throttle_max_attempts ?? 5);
        $maxAttempts = max(1, min(50, $maxAttempts));

        return [
            'enabled' => $enabled,
            'windowSeconds' => $windowSeconds,
            'maxAttempts' => $maxAttempts,
        ];
    }

    public static function getClientIp(): string
    {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $v = $_SERVER[$k];
                if (strpos($v, ',') !== false) {
                    $v = trim(explode(',', $v)[0]);
                }
                if (filter_var($v, FILTER_VALIDATE_IP)) {
                    return $v;
                }
            }
        }
        return '0.0.0.0';
    }

    /** Return true if this IP is currently blocked (too many failed attempts). */
    public static function isBlocked(string $ip): bool
    {
        $settings = self::getRuntimeSettings();
        if (!$settings['enabled']) {
            return false;
        }
        $path = self::filePath($ip);
        if (!is_file($path)) return false;
        $data = @json_decode(file_get_contents($path), true);
        if (!is_array($data)) return false;
        $first = (int) ($data['first'] ?? 0);
        $count = (int) ($data['count'] ?? 0);
        if (time() - $first > $settings['windowSeconds']) return false;
        return $count >= $settings['maxAttempts'];
    }

    /** Record a failed login attempt for this IP. */
    public static function recordFailure(string $ip): void
    {
        $settings = self::getRuntimeSettings();
        if (!$settings['enabled']) {
            // When throttling is disabled, do not track attempts.
            return;
        }
        $path = self::filePath($ip);
        $data = ['count' => 1, 'first' => time()];
        if (is_file($path)) {
            $existing = @json_decode(file_get_contents($path), true);
            if (is_array($existing)) {
                $first = (int) ($existing['first'] ?? time());
                if (time() - $first <= $settings['windowSeconds']) {
                    $data['count'] = ((int) ($existing['count'] ?? 0)) + 1;
                    $data['first'] = $first;
                }
            }
        }
        file_put_contents($path, json_encode($data), LOCK_EX);
    }

    /** Clear throttle for this IP (e.g. after successful login). */
    public static function clear(string $ip): void
    {
        $path = self::filePath($ip);
        if (is_file($path)) @unlink($path);
    }
}
