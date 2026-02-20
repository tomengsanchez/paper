<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Database;
use Core\Logger;

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
            $stmt = $db->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_OBJ);

            Logger::auth('User lookup result', [
                'username' => $username,
                'user_found' => (bool) $user,
                'user_id' => $user->id ?? null,
            ]);

            if (!$user) {
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
                Logger::auth('Login failed: wrong password', ['username' => $username]);
                $this->view('auth/login', ['error' => 'Invalid credentials']);
                return;
            }

            Auth::login((int) $user->id);
            Logger::auth('Login success', ['username' => $username, 'user_id' => $user->id]);
            $this->redirect('/');
        } catch (\Throwable $e) {
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
}
