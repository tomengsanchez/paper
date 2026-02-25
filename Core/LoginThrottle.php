<?php
namespace Core;

class LoginThrottle
{
    private const WINDOW_SECONDS = 900;  // 15 minutes
    private const MAX_ATTEMPTS = 5;
    private static ?string $dir = null;

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
        $path = self::filePath($ip);
        if (!is_file($path)) return false;
        $data = @json_decode(file_get_contents($path), true);
        if (!is_array($data)) return false;
        $first = (int) ($data['first'] ?? 0);
        $count = (int) ($data['count'] ?? 0);
        if (time() - $first > self::WINDOW_SECONDS) return false;
        return $count >= self::MAX_ATTEMPTS;
    }

    /** Record a failed login attempt for this IP. */
    public static function recordFailure(string $ip): void
    {
        $path = self::filePath($ip);
        $data = ['count' => 1, 'first' => time()];
        if (is_file($path)) {
            $existing = @json_decode(file_get_contents($path), true);
            if (is_array($existing)) {
                $first = (int) ($existing['first'] ?? time());
                if (time() - $first <= self::WINDOW_SECONDS) {
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
