<?php
namespace App;

use Core\Database;

/**
 * API token management for REST Bearer authentication.
 * Tokens are stored hashed; raw token is returned only on create.
 */
class ApiToken
{
    private const DEFAULT_EXPIRY_DAYS = 30;

    /** Create a token for user; returns ['token' => raw, 'expires_at' => datetime]. */
    public static function create(int $userId, int $expiryDays = self::DEFAULT_EXPIRY_DAYS): array
    {
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));

        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO api_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $hash, $expiresAt]);

        return [
            'token'      => $raw,
            'expires_at' => $expiresAt,
        ];
    }

    /** Validate token; returns user_id or null. */
    public static function validate(string $token): ?int
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $hash = hash('sha256', $token);
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT user_id FROM api_tokens WHERE token_hash = ? AND expires_at > NOW()');
        $stmt->execute([$hash]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ? (int) $row->user_id : null;
    }

    /** Revoke token (logout). Returns true if revoked. */
    public static function revoke(string $token): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }
        $hash = hash('sha256', $token);
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM api_tokens WHERE token_hash = ?');
        $stmt->execute([$hash]);
        return $stmt->rowCount() > 0;
    }

    /** Get Authorization Bearer token from request. */
    public static function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
            return trim($m[1]);
        }
        return null;
    }
}
