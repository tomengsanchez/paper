<?php
namespace App\Controllers;

use Core\Controller;

class StructureController extends Controller
{
    public function __construct()
    {
        $this->requireCapability('manage_structure');
    }

    public function index(): void
    {
        $this->view('structure/index');
    }
}
