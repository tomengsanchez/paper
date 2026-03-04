<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;

class AdminGuideController extends Controller
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
        $this->view('admin/guide');
    }
}

