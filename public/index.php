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
$router->get('/profile', 'ProfileController@index');
$router->get('/profile/export', 'ProfileController@export');
$router->get('/profile/view/{id}', 'ProfileController@show');
$router->get('/profile/create', 'ProfileController@create');
$router->post('/profile/store', 'ProfileController@store');
$router->get('/profile/edit/{id}', 'ProfileController@edit');
$router->post('/profile/update/{id}', 'ProfileController@update');
$router->post('/profile/delete/{id}', 'ProfileController@delete');
$router->get('/structure', 'StructureController@index');
$router->get('/structure/export', 'StructureController@export');
$router->get('/structure/view/{id}', 'StructureController@show');
$router->get('/structure/create', 'StructureController@create');
$router->post('/structure/store', 'StructureController@store');
$router->get('/structure/edit/{id}', 'StructureController@edit');
$router->post('/structure/update/{id}', 'StructureController@update');
$router->post('/structure/delete/{id}', 'StructureController@delete');
// Grievance module
$router->get('/grievance', 'GrievanceController@dashboard');
$router->post('/grievance/dashboard-config', 'GrievanceController@dashboardSaveConfig');
$router->get('/grievance/list', 'GrievanceController@index');
$router->get('/grievance/export', 'GrievanceController@export');
$router->get('/grievance/create', 'GrievanceController@create');
$router->post('/grievance/store', 'GrievanceController@store');
$router->get('/grievance/view/{id}', 'GrievanceController@show');
$router->get('/grievance/edit/{id}', 'GrievanceController@edit');
$router->post('/grievance/update/{id}', 'GrievanceController@update');
$router->post('/grievance/delete/{id}', 'GrievanceController@delete');
$router->post('/grievance/status-update/{id}', 'GrievanceController@statusUpdate');
// Options Library
$router->get('/grievance/options/vulnerabilities', 'GrievanceOptionsController@vulnerabilities');
$router->get('/grievance/options/vulnerabilities/create', 'GrievanceOptionsController@vulnerabilityCreate');
$router->post('/grievance/options/vulnerabilities/store', 'GrievanceOptionsController@vulnerabilityStore');
$router->get('/grievance/options/vulnerabilities/edit/{id}', 'GrievanceOptionsController@vulnerabilityEdit');
$router->post('/grievance/options/vulnerabilities/update/{id}', 'GrievanceOptionsController@vulnerabilityUpdate');
$router->post('/grievance/options/vulnerabilities/delete/{id}', 'GrievanceOptionsController@vulnerabilityDelete');
$router->get('/grievance/options/respondent-types', 'GrievanceOptionsController@respondentTypes');
$router->get('/grievance/options/respondent-types/create', 'GrievanceOptionsController@respondentTypeCreate');
$router->post('/grievance/options/respondent-types/store', 'GrievanceOptionsController@respondentTypeStore');
$router->get('/grievance/options/respondent-types/edit/{id}', 'GrievanceOptionsController@respondentTypeEdit');
$router->post('/grievance/options/respondent-types/update/{id}', 'GrievanceOptionsController@respondentTypeUpdate');
$router->post('/grievance/options/respondent-types/delete/{id}', 'GrievanceOptionsController@respondentTypeDelete');
$router->get('/grievance/options/grm-channels', 'GrievanceOptionsController@grmChannels');
$router->get('/grievance/options/grm-channels/create', 'GrievanceOptionsController@grmChannelCreate');
$router->post('/grievance/options/grm-channels/store', 'GrievanceOptionsController@grmChannelStore');
$router->get('/grievance/options/grm-channels/edit/{id}', 'GrievanceOptionsController@grmChannelEdit');
$router->post('/grievance/options/grm-channels/update/{id}', 'GrievanceOptionsController@grmChannelUpdate');
$router->post('/grievance/options/grm-channels/delete/{id}', 'GrievanceOptionsController@grmChannelDelete');
$router->get('/grievance/options/preferred-languages', 'GrievanceOptionsController@preferredLanguages');
$router->get('/grievance/options/preferred-languages/create', 'GrievanceOptionsController@preferredLanguageCreate');
$router->post('/grievance/options/preferred-languages/store', 'GrievanceOptionsController@preferredLanguageStore');
$router->get('/grievance/options/preferred-languages/edit/{id}', 'GrievanceOptionsController@preferredLanguageEdit');
$router->post('/grievance/options/preferred-languages/update/{id}', 'GrievanceOptionsController@preferredLanguageUpdate');
$router->post('/grievance/options/preferred-languages/delete/{id}', 'GrievanceOptionsController@preferredLanguageDelete');
$router->get('/grievance/options/types', 'GrievanceOptionsController@grievanceTypes');
$router->get('/grievance/options/types/create', 'GrievanceOptionsController@grievanceTypeCreate');
$router->post('/grievance/options/types/store', 'GrievanceOptionsController@grievanceTypeStore');
$router->get('/grievance/options/types/edit/{id}', 'GrievanceOptionsController@grievanceTypeEdit');
$router->post('/grievance/options/types/update/{id}', 'GrievanceOptionsController@grievanceTypeUpdate');
$router->post('/grievance/options/types/delete/{id}', 'GrievanceOptionsController@grievanceTypeDelete');
$router->get('/grievance/options/categories', 'GrievanceOptionsController@grievanceCategories');
$router->get('/grievance/options/categories/create', 'GrievanceOptionsController@grievanceCategoryCreate');
$router->post('/grievance/options/categories/store', 'GrievanceOptionsController@grievanceCategoryStore');
$router->get('/grievance/options/categories/edit/{id}', 'GrievanceOptionsController@grievanceCategoryEdit');
$router->post('/grievance/options/categories/update/{id}', 'GrievanceOptionsController@grievanceCategoryUpdate');
$router->post('/grievance/options/categories/delete/{id}', 'GrievanceOptionsController@grievanceCategoryDelete');
$router->get('/grievance/options/progress-levels', 'GrievanceOptionsController@progressLevels');
$router->get('/grievance/options/progress-levels/create', 'GrievanceOptionsController@progressLevelCreate');
$router->post('/grievance/options/progress-levels/store', 'GrievanceOptionsController@progressLevelStore');
$router->get('/grievance/options/progress-levels/edit/{id}', 'GrievanceOptionsController@progressLevelEdit');
$router->post('/grievance/options/progress-levels/update/{id}', 'GrievanceOptionsController@progressLevelUpdate');
$router->post('/grievance/options/progress-levels/delete/{id}', 'GrievanceOptionsController@progressLevelDelete');
$router->get('/library', 'LibraryController@index');
$router->get('/library/export', 'LibraryController@export');
$router->get('/library/view/{id}', 'LibraryController@show');
$router->get('/library/create', 'LibraryController@create');
$router->post('/library/store', 'LibraryController@store');
$router->get('/library/edit/{id}', 'LibraryController@edit');
$router->post('/library/update/{id}', 'LibraryController@update');
$router->post('/library/delete/{id}', 'LibraryController@delete');
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

// User Management (Admin) — User Profile merged into Users
$router->get('/users', 'UserController@index');
$router->get('/users/export', 'UserController@export');
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

// Serve structure upload images (works when doc root is not public/)
$router->get('/serve/structure', 'StructureController@serveImage');
// Serve profile attachments (images + PDF)
$router->get('/serve/profile', 'ProfileController@serveProfileFile');
$router->get('/serve/grievance', 'GrievanceController@serveGrievanceAttachment');
$router->get('/serve/grievance-card-attachment', 'GrievanceController@serveGrievanceCardAttachment');
$router->get('/serve/app-logo', 'AssetController@logo');

// REST API Auth (token-based)
$router->post('/api/auth/login', 'Api\AuthController@login');
$router->get('/api/auth/me', 'Api\AuthController@me');
$router->post('/api/auth/logout', 'Api\AuthController@logout');
// API for dropdown search (AJAX)
$router->get('/api/projects', 'Api\ApiController@projects');
$router->get('/api/projects/{id}/users', 'Api\ApiController@projectUsers');
$router->get('/api/users/{id}/projects', 'Api\ApiController@userProjects');
$router->get('/api/profiles', 'Api\ApiController@profiles');
$router->get('/api/profile/{id}/structures', 'Api\ApiController@profileStructures');
$router->get('/api/notifications', 'Api\ApiController@notifications');
// API for audit history lazy loading
$router->get('/api/history', 'Api\HistoryController@index');
// API for main dashboard aggregates (Profile, Structure, Grievance, Users)
$router->get('/api/dashboard', 'Api\DashboardController@index');
// API for grievance dashboard aggregates (AJAX)
$router->get('/api/grievance/dashboard', 'Api\GrievanceController@dashboard');

// Profile API (CRUD + list)
$router->get('/api/profile/list', 'Api\ProfileController@listApi');
$router->get('/api/profile/{id}', 'Api\ProfileController@getApi');
$router->post('/api/profile/store', 'Api\ProfileController@storeApi');
$router->post('/api/profile/update/{id}', 'Api\ProfileController@updateApi');
$router->post('/api/profile/delete/{id}', 'Api\ProfileController@deleteApi');

// Structure API (for Profile lightbox CRUD)
$router->get('/api/structure/next-strid', 'Api\StructureController@nextStridApi');
$router->get('/api/structure/{id}', 'Api\StructureController@getApi');
$router->post('/api/structure/store', 'Api\StructureController@storeApi');
$router->post('/api/structure/update/{id}', 'Api\StructureController@updateApi');
$router->post('/api/structure/delete/{id}', 'Api\StructureController@deleteApi');

$router->dispatch();
