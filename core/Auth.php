<?php
namespace Core;

class Auth
{
    private static ?object $user = null;

    public static function init(): void
    {
        session_start();
        if (isset($_SESSION['user_id'])) {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT u.*, r.name as role_name FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            self::$user = $stmt->fetch(\PDO::FETCH_OBJ);
        }
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
