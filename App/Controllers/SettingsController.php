<?php
namespace App\Controllers;

use Core\Controller;
use App\UserUiSettings;
use App\UserNotificationSettings;

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
        $notifyPrefs = UserNotificationSettings::get();
        $this->view('settings/index', [
            'uiTheme'     => $ui['theme'],
            'uiLayout'    => $ui['layout'],
            'notifyPrefs' => $notifyPrefs,
        ]);
    }

    public function updateNotifications(): void
    {
        $this->validateCsrf();
        $this->requireCapability('view_settings');
        UserNotificationSettings::save([
            'notify_new_profile'             => !empty($_POST['notify_new_profile']),
            'notify_profile_updated'         => !empty($_POST['notify_profile_updated']),
            'notify_new_grievance'           => !empty($_POST['notify_new_grievance']),
            'notify_grievance_status_change' => !empty($_POST['notify_grievance_status_change']),
        ]);
        $_SESSION['settings_notifications_saved'] = true;
        $this->redirect('/settings');
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
