<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\GeneralSettings;
use App\Models\AppSettings;

class GeneralController extends Controller
{
    private const LOGO_DIR = 'uploads/logo';
    private const LOGO_MAX_SIZE = 2 * 1024 * 1024; // 2MB
    private const LOGO_ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

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
        $branding = AppSettings::getAppBranding();
        $this->view('general/index', [
            'settings'   => $settings,
            'branding'   => $branding,
            'regions'    => GeneralSettings::regions(),
            'timezones'  => GeneralSettings::timezones(),
        ]);
    }

    public function save(): void
    {
        $this->validateCsrf();
        if (!Auth::isAdmin()) {
            $this->redirect('/');
            return;
        }
        GeneralSettings::save([
            'region'   => $_POST['region'] ?? '',
            'timezone' => $_POST['timezone'] ?? GeneralSettings::DEFAULT_TIMEZONE,
        ]);

        $brandingData = [
            'company_name' => $_POST['company_name'] ?? '',
            'app_name'     => $_POST['app_name'] ?? 'PAPeR',
        ];

        if (!empty($_POST['remove_logo'])) {
            $brandingData['company_logo'] = '';
            $this->deleteLogoFile();
        } elseif (!empty($_FILES['company_logo']['tmp_name']) && is_uploaded_file($_FILES['company_logo']['tmp_name'])) {
            $result = $this->handleLogoUpload();
            if ($result['ok']) {
                $brandingData['company_logo'] = $result['path'];
            } else {
                $_SESSION['general_error'] = $result['error'];
                $this->redirect('/system/general');
                return;
            }
        } else {
            $brandingData['company_logo'] = AppSettings::get('company_logo', '');
        }

        AppSettings::saveAppBranding($brandingData);
        $_SESSION['general_saved'] = true;
        $this->redirect('/system/general');
    }

    private function handleLogoUpload(): array
    {
        $file = $_FILES['company_logo'];
        $tmpPath = $file['tmp_name'];

        if ($file['size'] > self::LOGO_MAX_SIZE) {
            return ['ok' => false, 'error' => 'Logo must be 2 MB or smaller.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);
        if (!in_array($mime, self::LOGO_ALLOWED, true)) {
            return ['ok' => false, 'error' => 'Logo must be JPEG, PNG, GIF, or WebP.'];
        }

        $img = @getimagesize($tmpPath);
        if (!$img || ($img[0] !== $img[1])) {
            return ['ok' => false, 'error' => 'Logo must be square (same width and height).'];
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'png',
        };

        $uploadDir = ROOT . '/public/' . self::LOGO_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $this->deleteLogoFile();

        $filename = 'company_logo.' . $ext;
        $destPath = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $destPath)) {
            return ['ok' => false, 'error' => 'Failed to save logo.'];
        }

        return ['ok' => true, 'path' => self::LOGO_DIR . '/' . $filename];
    }

    private function deleteLogoFile(): void
    {
        $current = AppSettings::get('company_logo', '');
        if ($current && strpos($current, '..') === false) {
            $path = ROOT . '/public/' . $current;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
