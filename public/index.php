<?php
/**
 * Application entry point (front controller).
 * Bootstrap loads config, autoloader, database, auth.
 * Routes are defined in routes/web.php (or routes/api.php).
 */
require_once dirname(__DIR__) . '/bootstrap.php';

$router = new \Core\Router();
require dirname(__DIR__) . '/routes/web.php';
