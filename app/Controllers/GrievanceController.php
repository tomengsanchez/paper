<?php
namespace App\Controllers;

use Core\Controller;

class GrievanceController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->view('grievance/index');
    }
}
