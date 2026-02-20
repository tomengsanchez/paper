<?php
namespace App\Controllers;

use Core\Controller;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->requireCapability('manage_settings');
    }

    public function index(): void
    {
        $this->view('settings/index');
    }
}
