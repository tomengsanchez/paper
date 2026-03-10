<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;

/**
 * Logged-in user's own profile/account page.
 */
class AccountController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
            return;
        }
        $this->view('account/index', ['user' => $user]);
    }
}
