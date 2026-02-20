<?php
namespace Core;

class Auth
{
    private static ?object $user = null;
    private static ?array $userCapabilities = null;

    public static function init(): void
    {
        session_start();
        if (isset($_SESSION['user_id'])) {
            $logoutMins = (int) \App\Models\AppSettings::get('user_logout_after_minutes', 30);
            if ($logoutMins > 0) {
                $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
                if ($lastActivity && (time() - $lastActivity) > $logoutMins * 60) {
                    session_unset();
                    session_destroy();
                    header('Location: /login?timeout=1');
                    exit;
                }
            }
            $_SESSION['last_activity'] = time();

            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT u.*, r.name as role_name FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            self::$user = $stmt->fetch(\PDO::FETCH_OBJ);
        }
    }

    public static function can(string $capability): bool
    {
        if (!self::check()) return false;
        if (self::isAdmin()) return true;
        if (self::$userCapabilities === null) {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT capability FROM role_capabilities WHERE role_id = ?');
            $stmt->execute([self::$user->role_id]);
            self::$userCapabilities = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        return in_array($capability, self::$userCapabilities, true);
    }

    public static function canAny(array $capabilities): bool
    {
        foreach ($capabilities as $cap) {
            if (self::can($cap)) return true;
        }
        return false;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?object
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function login(int $userId): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        session_destroy();
    }

    public static function hasRole(string $role): bool
    {
        return self::$user && self::$user->role_name === $role;
    }

    public static function isAdmin(): bool
    {
        return self::hasRole('Administrator');
    }

    public static function isCoordinator(): bool
    {
        return self::hasRole('Coordinator');
    }
}
