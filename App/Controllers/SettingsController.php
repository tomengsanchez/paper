<?php
namespace App\Controllers;

use Core\Controller;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_settings');
        $this->view('settings/index');
    }
}
