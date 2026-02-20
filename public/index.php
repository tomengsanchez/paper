<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$router = new \Core\Router();

// Auth routes (public)
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
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
$router->get('/grievance', 'GrievanceController@index');
$router->get('/library', 'LibraryController@index');
$router->get('/library/create', 'LibraryController@create');
$router->post('/library/store', 'LibraryController@store');
$router->get('/library/edit/{id}', 'LibraryController@edit');
$router->post('/library/update/{id}', 'LibraryController@update');
$router->get('/library/delete/{id}', 'LibraryController@delete');
$router->get('/settings', 'SettingsController@index');

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

// API for dropdown search (AJAX)
$router->get('/api/coordinators', 'ApiController@coordinators');
$router->get('/api/projects', 'ApiController@projects');

$router->dispatch();
