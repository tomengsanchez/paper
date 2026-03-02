<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\GeneralSettings;

class GeneralController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            $this->redirect('/');
        }
    }

    public function index(): void
    {
        $settings = GeneralSettings::get();
        $this->view('general/index', [
            'settings'  => $settings,
            'regions'   => GeneralSettings::regions(),
            'timezones' => GeneralSettings::timezones(),
        ]);
    }

    public function save(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect('/');
            return;
        }
        GeneralSettings::save([
            'region'   => $_POST['region'] ?? '',
            'timezone' => $_POST['timezone'] ?? GeneralSettings::DEFAULT_TIMEZONE,
        ]);
        $_SESSION['general_saved'] = true;
        $this->redirect('/system/general');
    }
}
