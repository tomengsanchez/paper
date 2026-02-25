<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Database;
use Core\Logger;
use Core\Mailer;
use Core\LoginThrottle;
use App\Models\AppSettings;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/login');
    }

    public function login(): void
    {
        if (!\Core\Csrf::validate()) {
            $this->redirect('/login?error=csrf');
            return;
        }
        $ip = LoginThrottle::getClientIp();
        if (LoginThrottle::isBlocked($ip)) {
            $this->view('auth/login', ['error' => 'Too many login attempts. Please try again in 15 minutes.']);
            return;
        }
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        Logger::auth('Login attempt', [
            'username' => $username ?: '(empty)',
            'password_provided' => !empty($password),
        ]);

        if (empty($username) || empty($password)) {
            Logger::auth('Login rejected: missing credentials');
            $this->view('auth/login', ['error' => 'Username and password required']);
            return;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT id, username, password_hash, email FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_OBJ);

            Logger::auth('User lookup result', [
                'username' => $username,
                'user_found' => (bool) $user,
                'user_id' => $user->id ?? null,
            ]);

            if (!$user) {
                LoginThrottle::recordFailure($ip);
                Logger::auth('Login failed: user not found', ['username' => $username]);
                $this->view('auth/login', ['error' => 'Invalid credentials']);
                return;
            }

            $passwordValid = password_verify($password, $user->password_hash);
            Logger::auth('Password verification', [
                'username' => $username,
                'password_valid' => $passwordValid,
            ]);

            if (!$passwordValid) {
                LoginThrottle::recordFailure($ip);
                Logger::auth('Login failed: wrong password', ['username' => $username]);
                $this->view('auth/login', ['error' => 'Invalid credentials']);
                return;
            }

            $security = AppSettings::getSecurityConfig();
            if ($security->enable_email_2fa) {
                $email = trim($user->email ?? '');
                if (empty($email)) {
                    Logger::auth('2FA required but user has no email', ['user_id' => $user->id]);
                    $this->view('auth/login', ['error' => '2FA is enabled but your account has no email. Please contact administrator.']);
                    return;
                }
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires = time() + ($security->{'2fa_expiration_minutes'} * 60);
                $_SESSION['pending_2fa_user_id'] = (int) $user->id;
                $_SESSION['pending_2fa_code'] = $code;
                $_SESSION['pending_2fa_expires'] = $expires;
                $result = Mailer::send($email, 'Login Code', "Your verification code is: $code\n\nThis code expires in {$security->{'2fa_expiration_minutes'}} minutes.");
                if (!$result['success']) {
                    Logger::auth('2FA email send failed', ['user_id' => $user->id, 'error' => $result['error'] ?? '']);
                    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires']);
                    $this->view('auth/login', ['error' => 'Failed to send verification code. Please try again or contact administrator.']);
                    return;
                }
                Logger::auth('2FA code sent', ['user_id' => $user->id]);
                $this->redirect('/login/2fa');
                return;
            }

            LoginThrottle::clear($ip);
            Auth::login((int) $user->id);
            Logger::auth('Login success', ['username' => $username, 'user_id' => $user->id]);
            $this->redirect('/');
        } catch (\Throwable $e) {
            LoginThrottle::recordFailure($ip ?? LoginThrottle::getClientIp());
            Logger::auth('Login exception', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Logger::php('Auth exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->view('auth/login', ['error' => 'Invalid credentials']);
        }
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }

    public function twoFactorForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
            return;
        }
        if (empty($_SESSION['pending_2fa_user_id']) || empty($_SESSION['pending_2fa_code'])) {
            $this->redirect('/login');
            return;
        }
        if (time() > ($_SESSION['pending_2fa_expires'] ?? 0)) {
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires']);
            $this->redirect('/login?error=2fa_expired');
            return;
        }
        $this->view('auth/2fa');
    }

    public function twoFactorVerify(): void
    {
        if (!\Core\Csrf::validate()) {
            $this->redirect('/login?error=csrf');
            return;
        }
        $code = trim($_POST['code'] ?? '');
        $userId = $_SESSION['pending_2fa_user_id'] ?? null;
        $storedCode = $_SESSION['pending_2fa_code'] ?? '';
        $expires = $_SESSION['pending_2fa_expires'] ?? 0;

        if (!$userId || time() > $expires) {
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires']);
            $this->redirect('/login?error=2fa_expired');
            return;
        }

        if ($code !== $storedCode) {
            Logger::auth('2FA verification failed', ['user_id' => $userId]);
            $this->view('auth/2fa', ['error' => 'Invalid or expired code.']);
            return;
        }

        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires']);
        Auth::login((int) $userId);
        Logger::auth('2FA verification success', ['user_id' => $userId]);
        $this->redirect('/');
    }
}
