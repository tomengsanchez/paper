<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use Core\Database;
use Core\Logger;
use Core\LoginThrottle;
use App\ApiToken;
use App\Models\AppSettings;

/**
 * REST API authentication.
 * POST /api/auth/login - JSON { username, password } → { token, expires_at, user }
 * GET  /api/auth/me    - Requires Bearer token → current user
 * POST /api/auth/logout - Requires Bearer token → revokes token
 */
class AuthController extends Controller
{
    public function login(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        Logger::auth('API login attempt', ['username' => $username ?: '(empty)']);

        if ($username === '' || $password === '') {
            http_response_code(400);
            $this->json(['error' => 'Bad Request', 'message' => 'username and password required']);
            return;
        }

        $ip = LoginThrottle::getClientIp();
        if (LoginThrottle::isBlocked($ip)) {
            http_response_code(429);
            $this->json(['error' => 'Too Many Requests', 'message' => 'Too many login attempts']);
            return;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT id, username, password_hash, email, display_name, password_changed_at, created_at FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_OBJ);

            if (!$user || !password_verify($password, $user->password_hash)) {
                LoginThrottle::recordFailure($ip);
                Logger::auth('API login failed: invalid credentials', ['username' => $username]);
                http_response_code(401);
                $this->json(['error' => 'Unauthorized', 'message' => 'Invalid credentials']);
                return;
            }

            $security = AppSettings::getSecurityConfig();
            $expiryDays = (int) ($security->password_expiry_days ?? 0);
            if ($expiryDays > 0) {
                $changedAt = $user->password_changed_at ?? $user->created_at ?? null;
                if ($changedAt) {
                    $changedTs = strtotime($changedAt);
                    if ($changedTs !== false) {
                        $expirySeconds = $expiryDays * 86400;
                        if (time() - $changedTs > $expirySeconds) {
                            Logger::auth('API login blocked: password expired', ['user_id' => $user->id, 'expiry_days' => $expiryDays]);
                            http_response_code(403);
                            $this->json(['error' => 'Forbidden', 'message' => 'Password has expired. Please contact your administrator.']);
                            return;
                        }
                    }
                }
            }
            if ($security->enable_email_2fa) {
                Logger::auth('API login blocked: 2FA enabled', ['user_id' => $user->id]);
                http_response_code(403);
                $this->json(['error' => 'Forbidden', 'message' => '2FA is enabled. Use web login.']);
                return;
            }

            LoginThrottle::clear($ip);
            $result = ApiToken::create((int) $user->id);
            \App\AuditLog::record('user', (int) $user->id, 'login', ['ip' => $ip]);

            $this->json([
                'token'      => $result['token'],
                'expires_at' => $result['expires_at'],
                'user'       => [
                    'id'           => (int) $user->id,
                    'username'     => $user->username,
                    'display_name' => $user->display_name ?? $user->username,
                    'email'        => $user->email ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            LoginThrottle::recordFailure($ip ?? LoginThrottle::getClientIp());
            Logger::auth('API login exception', ['error' => $e->getMessage()]);
            http_response_code(500);
            $this->json(['error' => 'Internal Server Error', 'message' => 'Login failed']);
        }
    }

    public function me(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        $u = Auth::user();
        $this->json([
            'id'           => (int) $u->id,
            'username'     => $u->username,
            'display_name' => $u->display_name ?? $u->username,
            'email'        => $u->email ?? null,
            'role_name'    => $u->role_name ?? null,
        ]);
    }

    public function logout(): void
    {
        $bearer = ApiToken::getBearerToken();
        if ($bearer !== null) {
            ApiToken::revoke($bearer);
        } elseif (Auth::check()) {
            Auth::logout();
        }
        $this->json(['message' => 'Logged out']);
    }
}
