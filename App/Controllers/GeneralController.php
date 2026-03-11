<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\GeneralSettings;
use App\Models\AppSettings;

class GeneralController extends Controller
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
        $settings = GeneralSettings::get();
        $branding = AppSettings::getBrandingConfig();
        $this->view('general/index', [
            'settings'  => $settings,
            'regions'   => GeneralSettings::regions(),
            'timezones' => GeneralSettings::timezones(),
            'branding'  => $branding,
        ]);
    }

    public function save(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect('/');
            return;
        }

        // Save per-user general settings (region, timezone)
        GeneralSettings::save([
            'region'   => $_POST['region'] ?? '',
            'timezone' => $_POST['timezone'] ?? GeneralSettings::DEFAULT_TIMEZONE,
        ]);

        // Handle branding (app name, company name, logo)
        $logoPath = null;
        if (!empty($_FILES['app_logo']['name'] ?? '')) {
            $file = $_FILES['app_logo'];
            if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $allowed = [
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg',
                    'image/x-icon' => 'ico',
                    'image/vnd.microsoft.icon' => 'ico',
                ];
                if (isset($allowed[$mime])) {
                    $ext = $allowed[$mime];
                    $dir = dirname(__DIR__, 2) . '/public/uploads/app';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $filename = 'logo.' . $ext;
                    $dest = $dir . '/' . $filename;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $logoPath = '/uploads/app/' . $filename;
                    }
                }
            }
        }

        AppSettings::saveBrandingConfig([
            'app_name' => $_POST['app_name'] ?? '',
            'company_name' => $_POST['company_name'] ?? '',
            'logo_path' => $logoPath,
        ]);

        $_SESSION['general_saved'] = true;
        $this->redirect('/system/general');
    }
}
