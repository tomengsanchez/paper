<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\DevelopmentSettings;
use App\DevClock;

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
        $simulatedOverride = DevClock::getOverride();
        $this->view('development/index', [
            'settings' => $settings,
            'simulatedOverride' => $simulatedOverride,
        ]);
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

    public function setSimulatedTime(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect('/');
            return;
        }
        $date = trim((string) ($_POST['simulated_date'] ?? ''));
        DevClock::setOverride($date);
        $_SESSION['development_saved'] = true;
        $_SESSION['development_message'] = $date !== '' ? 'Simulated date set.' : 'Simulated date cleared.';
        $this->redirect('/system/development');
    }

    public function clearSimulatedTime(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect('/');
            return;
        }
        DevClock::clearOverride();
        $_SESSION['development_saved'] = true;
        $_SESSION['development_message'] = 'Using real date again.';
        $this->redirect('/system/development');
    }
}
