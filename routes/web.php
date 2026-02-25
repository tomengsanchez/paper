<?php
/**
 * Web routes - scaffold: auth, dashboard, user management, settings, example Items.
 * $router is available (injected from public/index.php). See docs/ROUTING.md.
 */

// Auth (public)
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/login/2fa', 'AuthController@twoFactorForm');
$router->post('/login/2fa/verify', 'AuthController@twoFactorVerify');
$router->get('/logout', 'AuthController@logout');

// Dashboard
$router->get('/', 'DashboardController@index');

// User Management
$router->get('/users/profiles', 'UserProfileController@index');
$router->get('/users/profiles/view/{id}', 'UserProfileController@show');
$router->get('/users/profiles/create', 'UserProfileController@create');
$router->post('/users/profiles/store', 'UserProfileController@store');
$router->get('/users/profiles/edit/{id}', 'UserProfileController@edit');
$router->post('/users/profiles/update/{id}', 'UserProfileController@update');
$router->post('/users/profiles/delete/{id}', 'UserProfileController@delete');
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

// Settings
$router->get('/settings', 'SettingsController@index');
$router->get('/settings/email', 'EmailSettingsController@index');
$router->post('/settings/email/update', 'EmailSettingsController@update');
$router->post('/settings/email/test', 'EmailSettingsController@testMail');
$router->get('/settings/security', 'SecuritySettingsController@index');
$router->post('/settings/security/update', 'SecuritySettingsController@update');

// Example module: Items (CRUD)
$router->get('/item', 'ItemController@index');
$router->get('/item/create', 'ItemController@create');
$router->post('/item/store', 'ItemController@store');
$router->get('/item/view/{id}', 'ItemController@show');
$router->get('/item/edit/{id}', 'ItemController@edit');
$router->post('/item/update/{id}', 'ItemController@update');
$router->post('/item/delete/{id}', 'ItemController@delete');

$router->dispatch();
