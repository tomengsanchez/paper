<?php
namespace App\Controllers;

use Core\Controller;
use App\UserUiSettings;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_settings');
        $ui = UserUiSettings::get();
        $this->view('settings/index', [
            'uiTheme'  => $ui['theme'],
            'uiLayout' => $ui['layout'],
        ]);
    }

    public function updateUi(): void
    {
        $this->validateCsrf();
        $this->requireCapability('view_settings');
        UserUiSettings::save([
            'theme'  => trim($_POST['ui_theme'] ?? ''),
            'layout' => trim($_POST['ui_layout'] ?? ''),
        ]);
        $_SESSION['settings_ui_saved'] = true;
        $this->redirect('/settings');
    }
}
