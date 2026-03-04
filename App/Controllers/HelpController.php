<?php
namespace App\Controllers;

use Core\Controller;

class HelpController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $from = isset($_GET['from']) ? (string)$_GET['from'] : '';
        $module = $this->resolveModuleKey($from);
        $this->view('help/index', [
            'module' => $module,
            'from' => $from,
        ]);
    }

    private function resolveModuleKey(string $from): string
    {
        if ($from === '') {
            return 'general';
        }

        switch ($from) {
            case 'dashboard':
                return 'dashboard';
            case 'profile':
                return 'profile';
            case 'structure':
                return 'structure';
            case 'library':
                return 'library';
            case 'settings':
            case 'security-settings':
            case 'general':
                return 'settings';
            case 'users':
            case 'user-roles':
                return 'user-management';
            case 'grievance-dashboard':
                return 'grievance-dashboard';
            case 'grievance':
            case 'grievance-list':
            case 'grievance-vulnerabilities':
            case 'grievance-respondent-types':
            case 'grievance-grm-channels':
            case 'grievance-preferred-languages':
            case 'grievance-types':
            case 'grievance-categories':
            case 'grievance-progress-levels':
                return 'grievance';
            case 'account':
                return 'account';
            default:
                return 'general';
        }
    }
}

