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
$router->get('/profile/view/{id}', 'ProfileController@show');
$router->get('/profile/create', 'ProfileController@create');
$router->post('/profile/store', 'ProfileController@store');
$router->get('/profile/edit/{id}', 'ProfileController@edit');
$router->post('/profile/update/{id}', 'ProfileController@update');
$router->get('/profile/delete/{id}', 'ProfileController@delete');
$router->get('/structure', 'StructureController@index');
$router->get('/structure/view/{id}', 'StructureController@show');
$router->get('/structure/create', 'StructureController@create');
$router->post('/structure/store', 'StructureController@store');
$router->get('/structure/edit/{id}', 'StructureController@edit');
$router->post('/structure/update/{id}', 'StructureController@update');
$router->get('/structure/delete/{id}', 'StructureController@delete');
// Grievance module
$router->get('/grievance', 'GrievanceController@dashboard');
$router->get('/grievance/list', 'GrievanceController@index');
$router->get('/grievance/create', 'GrievanceController@create');
$router->post('/grievance/store', 'GrievanceController@store');
$router->get('/grievance/view/{id}', 'GrievanceController@show');
$router->get('/grievance/edit/{id}', 'GrievanceController@edit');
$router->post('/grievance/update/{id}', 'GrievanceController@update');
$router->get('/grievance/delete/{id}', 'GrievanceController@delete');
$router->post('/grievance/status-update/{id}', 'GrievanceController@statusUpdate');
// Options Library
$router->get('/grievance/options/vulnerabilities', 'GrievanceOptionsController@vulnerabilities');
$router->get('/grievance/options/vulnerabilities/create', 'GrievanceOptionsController@vulnerabilityCreate');
$router->post('/grievance/options/vulnerabilities/store', 'GrievanceOptionsController@vulnerabilityStore');
$router->get('/grievance/options/vulnerabilities/edit/{id}', 'GrievanceOptionsController@vulnerabilityEdit');
$router->post('/grievance/options/vulnerabilities/update/{id}', 'GrievanceOptionsController@vulnerabilityUpdate');
$router->get('/grievance/options/vulnerabilities/delete/{id}', 'GrievanceOptionsController@vulnerabilityDelete');
$router->get('/grievance/options/respondent-types', 'GrievanceOptionsController@respondentTypes');
$router->get('/grievance/options/respondent-types/create', 'GrievanceOptionsController@respondentTypeCreate');
$router->post('/grievance/options/respondent-types/store', 'GrievanceOptionsController@respondentTypeStore');
$router->get('/grievance/options/respondent-types/edit/{id}', 'GrievanceOptionsController@respondentTypeEdit');
$router->post('/grievance/options/respondent-types/update/{id}', 'GrievanceOptionsController@respondentTypeUpdate');
$router->get('/grievance/options/respondent-types/delete/{id}', 'GrievanceOptionsController@respondentTypeDelete');
$router->get('/grievance/options/grm-channels', 'GrievanceOptionsController@grmChannels');
$router->get('/grievance/options/grm-channels/create', 'GrievanceOptionsController@grmChannelCreate');
$router->post('/grievance/options/grm-channels/store', 'GrievanceOptionsController@grmChannelStore');
$router->get('/grievance/options/grm-channels/edit/{id}', 'GrievanceOptionsController@grmChannelEdit');
$router->post('/grievance/options/grm-channels/update/{id}', 'GrievanceOptionsController@grmChannelUpdate');
$router->get('/grievance/options/grm-channels/delete/{id}', 'GrievanceOptionsController@grmChannelDelete');
$router->get('/grievance/options/preferred-languages', 'GrievanceOptionsController@preferredLanguages');
$router->get('/grievance/options/preferred-languages/create', 'GrievanceOptionsController@preferredLanguageCreate');
$router->post('/grievance/options/preferred-languages/store', 'GrievanceOptionsController@preferredLanguageStore');
$router->get('/grievance/options/preferred-languages/edit/{id}', 'GrievanceOptionsController@preferredLanguageEdit');
$router->post('/grievance/options/preferred-languages/update/{id}', 'GrievanceOptionsController@preferredLanguageUpdate');
$router->get('/grievance/options/preferred-languages/delete/{id}', 'GrievanceOptionsController@preferredLanguageDelete');
$router->get('/grievance/options/types', 'GrievanceOptionsController@grievanceTypes');
$router->get('/grievance/options/types/create', 'GrievanceOptionsController@grievanceTypeCreate');
$router->post('/grievance/options/types/store', 'GrievanceOptionsController@grievanceTypeStore');
$router->get('/grievance/options/types/edit/{id}', 'GrievanceOptionsController@grievanceTypeEdit');
$router->post('/grievance/options/types/update/{id}', 'GrievanceOptionsController@grievanceTypeUpdate');
$router->get('/grievance/options/types/delete/{id}', 'GrievanceOptionsController@grievanceTypeDelete');
$router->get('/grievance/options/categories', 'GrievanceOptionsController@grievanceCategories');
$router->get('/grievance/options/categories/create', 'GrievanceOptionsController@grievanceCategoryCreate');
$router->post('/grievance/options/categories/store', 'GrievanceOptionsController@grievanceCategoryStore');
$router->get('/grievance/options/categories/edit/{id}', 'GrievanceOptionsController@grievanceCategoryEdit');
$router->post('/grievance/options/categories/update/{id}', 'GrievanceOptionsController@grievanceCategoryUpdate');
$router->get('/grievance/options/categories/delete/{id}', 'GrievanceOptionsController@grievanceCategoryDelete');
$router->get('/library', 'LibraryController@index');
$router->get('/library/view/{id}', 'LibraryController@show');
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
$router->get('/users/profiles/view/{id}', 'UserProfileController@show');
$router->get('/users/profiles/create', 'UserProfileController@create');
$router->post('/users/profiles/store', 'UserProfileController@store');
$router->get('/users/profiles/edit/{id}', 'UserProfileController@edit');
$router->post('/users/profiles/update/{id}', 'UserProfileController@update');
$router->get('/users/profiles/delete/{id}', 'UserProfileController@delete');
$router->get('/users', 'UserController@index');
$router->get('/users/view/{id}', 'UserController@show');
$router->get('/users/create', 'UserController@create');
$router->post('/users/store', 'UserController@store');
$router->get('/users/edit/{id}', 'UserController@edit');
$router->post('/users/update/{id}', 'UserController@update');
$router->get('/users/delete/{id}', 'UserController@delete');
$router->get('/users/roles', 'RoleController@index');
$router->get('/users/roles/view/{id}', 'RoleController@show');
$router->get('/users/roles/edit/{id}', 'RoleController@edit');
$router->post('/users/roles/update/{id}', 'RoleController@update');

// Serve structure upload images (works when doc root is not public/)
$router->get('/serve/structure', 'StructureController@serveImage');
// Serve profile attachments (images + PDF)
$router->get('/serve/profile', 'ProfileController@serveProfileFile');
$router->get('/serve/grievance', 'GrievanceController@serveGrievanceAttachment');

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
