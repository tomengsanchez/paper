<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\DevelopmentSettings;

class DevelopmentController extends Controller
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
        $settings = DevelopmentSettings::get();
        $this->view('development/index', ['settings' => $settings]);
    }

    public function save(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect('/');
            return;
        }
        $statusCheck = isset($_POST['status_check']) && $_POST['status_check'] === '1';
        DevelopmentSettings::save(['status_check' => $statusCheck]);
        $_SESSION['development_saved'] = true;
        $this->redirect('/system/development');
    }
}
