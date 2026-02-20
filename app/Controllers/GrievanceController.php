<?php
namespace App\Controllers;

use Core\Controller;

class GrievanceController extends Controller
{
    public function __construct()
    {
        $this->requireCapability('manage_grievance');
    }

    public function index(): void
    {
        $this->view('grievance/index');
    }
}
