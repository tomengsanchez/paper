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
$router->get('/profile', 'ProfileController@index');
$router->get('/profile/create', 'ProfileController@create');
$router->post('/profile/store', 'ProfileController@store');
$router->get('/profile/edit/{id}', 'ProfileController@edit');
$router->post('/profile/update/{id}', 'ProfileController@update');
$router->get('/profile/delete/{id}', 'ProfileController@delete');
$router->get('/structure', 'StructureController@index');
$router->get('/structure/create', 'StructureController@create');
$router->post('/structure/store', 'StructureController@store');
$router->get('/structure/edit/{id}', 'StructureController@edit');
$router->post('/structure/update/{id}', 'StructureController@update');
$router->get('/structure/delete/{id}', 'StructureController@delete');
$router->get('/grievance', 'GrievanceController@index');
$router->get('/library', 'LibraryController@index');
$router->get('/library/create', 'LibraryController@create');
$router->post('/library/store', 'LibraryController@store');
$router->get('/library/edit/{id}', 'LibraryController@edit');
$router->post('/library/update/{id}', 'LibraryController@update');
$router->get('/library/delete/{id}', 'LibraryController@delete');
$router->get('/settings', 'SettingsController@index');
$router->get('/settings/email', 'EmailSettingsController@index');
$router->post('/settings/email/update', 'EmailSettingsController@update');
$router->post('/settings/email/test', 'EmailSettingsController@testMail');
$router->get('/settings/security', 'SecuritySettingsController@index');
$router->post('/settings/security/update', 'SecuritySettingsController@update');

// User Management (Admin)
$router->get('/users/profiles', 'UserProfileController@index');
$router->get('/users/profiles/create', 'UserProfileController@create');
$router->post('/users/profiles/store', 'UserProfileController@store');
$router->get('/users/profiles/edit/{id}', 'UserProfileController@edit');
$router->post('/users/profiles/update/{id}', 'UserProfileController@update');
$router->get('/users/profiles/delete/{id}', 'UserProfileController@delete');
$router->get('/users', 'UserController@index');
$router->get('/users/create', 'UserController@create');
$router->post('/users/store', 'UserController@store');
$router->get('/users/edit/{id}', 'UserController@edit');
$router->post('/users/update/{id}', 'UserController@update');
$router->get('/users/delete/{id}', 'UserController@delete');
$router->get('/users/roles', 'RoleController@index');
$router->get('/users/roles/edit/{id}', 'RoleController@edit');
$router->post('/users/roles/update/{id}', 'RoleController@update');

// Serve structure upload images (works when doc root is not public/)
$router->get('/serve/structure', 'StructureController@serveImage');

// API for dropdown search (AJAX)
$router->get('/api/coordinators', 'ApiController@coordinators');
$router->get('/api/projects', 'ApiController@projects');
$router->get('/api/profiles', 'ApiController@profiles');
$router->get('/api/profile/{id}/structures', 'ApiController@profileStructures');

// Structure API (for Profile lightbox CRUD)
$router->get('/api/structure/next-strid', 'StructureController@nextStridApi');
$router->get('/api/structure/{id}', 'StructureController@getApi');
$router->post('/api/structure/store', 'StructureController@storeApi');
$router->post('/api/structure/update/{id}', 'StructureController@updateApi');
$router->post('/api/structure/delete/{id}', 'StructureController@deleteApi');

$router->dispatch();
