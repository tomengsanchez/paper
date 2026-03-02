<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\SystemDebug;

class DebugLogController extends Controller
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
        $requestStart = SystemDebug::getRequestStartTime();
        $loadTimeMs = SystemDebug::getLoadTimeMs();
        $timestamp = date('Y-m-d H:i:s', (int) $requestStart) . sprintf('.%03d', ($requestStart - floor($requestStart)) * 1000);

        $queries = SystemDebug::getQueries();
        $classesLoaded = SystemDebug::getClassesLoaded();
        $functions = SystemDebug::getDefinedFunctions();

        $this->view('debug_log/index', [
            'timestamp'      => $timestamp,
            'loadTimeMs'     => $loadTimeMs,
            'queries'        => $queries,
            'classesLoaded'  => $classesLoaded,
            'functions'      => $functions,
        ]);
    }
}
