<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\AppSettings;

class SecuritySettingsController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_security_settings');
        $config = AppSettings::getSecurityConfig();
        $this->view('security_settings/index', ['config' => $config]);
    }

    public function update(): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_security_settings');
        AppSettings::saveSecurityConfig([
            'enable_email_2fa' => isset($_POST['enable_email_2fa']),
            '2fa_expiration_minutes' => (int) ($_POST['2fa_expiration_minutes'] ?? 15),
            'user_logout_after_minutes' => (int) ($_POST['user_logout_after_minutes'] ?? 30),
            'login_throttle_enabled' => isset($_POST['login_throttle_enabled']),
            'login_throttle_max_attempts' => (int) ($_POST['login_throttle_max_attempts'] ?? 5),
            'login_throttle_lockout_minutes' => (int) ($_POST['login_throttle_lockout_minutes'] ?? 15),
        ]);
        $this->redirect('/settings/security?success=1');
    }
}
