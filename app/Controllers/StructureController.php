<?php
namespace App\Controllers;

use Core\Controller;

class StructureController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_structure');
        $this->view('structure/index');
    }
}
