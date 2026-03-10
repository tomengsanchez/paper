<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$router = new \Core\Router();

// Auth routes (public)
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/login/2fa', 'AuthController@twoFactorForm');
$router->post('/login/2fa/verify', 'AuthController@twoFactorVerify');
$router->get('/logout', 'AuthController@logout');

// Protected routes
$router->get('/', 'DashboardController@index');
$router->get('/account', 'AccountController@index');
$router->get('/notifications', 'NotificationController@index');
$router->get('/notifications/click/{id}', 'NotificationController@click');
$router->get('/help', 'HelpController@index');
$router->get('/admin-guide', 'AdminGuideController@index');

$router->get('/serve/company-logo', 'ServeController@companyLogo');

$router->get('/settings', 'SettingsController@index');
$router->post('/settings/ui', 'SettingsController@updateUi');
$router->post('/settings/notifications', 'SettingsController@updateNotifications');
$router->get('/settings/email', 'EmailSettingsController@index');
$router->post('/settings/email/update', 'EmailSettingsController@update');
$router->post('/settings/email/test', 'EmailSettingsController@testMail');
$router->get('/settings/security', 'SecuritySettingsController@index');
$router->post('/settings/security/update', 'SecuritySettingsController@update');

// System (Admin) — General, Audit Trail, Debug Log, Development
$router->get('/system/general', 'GeneralController@index');
$router->post('/system/general/save', 'GeneralController@save');
$router->get('/system/audit-trail', 'AuditTrailController@index');
$router->get('/system/debug-log', 'DebugLogController@index');
$router->get('/api/system/log', 'DebugLogController@log');
$router->get('/system/development', 'DevelopmentController@index');
$router->post('/system/development/save', 'DevelopmentController@save');
$router->post('/system/development/set-simulated-time', 'DevelopmentController@setSimulatedTime');
$router->post('/system/development/clear-simulated-time', 'DevelopmentController@clearSimulatedTime');

// User Management (Admin)
$router->get('/users', 'UserController@index');
$router->get('/users/view/{id}', 'UserController@show');
$router->get('/users/create', 'UserController@create');
$router->post('/users/store', 'UserController@store');
$router->get('/users/edit/{id}', 'UserController@edit');
$router->post('/users/update/{id}', 'UserController@update');
$router->post('/users/delete/{id}', 'UserController@delete');
$router->get('/users/roles', 'RoleController@index');
$router->get('/users/roles/view/{id}', 'RoleController@show');
$router->get('/users/roles/edit/{id}', 'RoleController@edit');
$router->post('/users/roles/update/{id}', 'RoleController@update');

// REST API Auth (token-based)
$router->post('/api/auth/login', 'Api\AuthController@login');
$router->get('/api/auth/me', 'Api\AuthController@me');
$router->post('/api/auth/logout', 'Api\AuthController@logout');

$router->get('/api/notifications', 'Api\ApiController@notifications');
$router->get('/api/history', 'Api\HistoryController@index');
$router->get('/api/dashboard', 'Api\DashboardController@index');

$router->dispatch();
