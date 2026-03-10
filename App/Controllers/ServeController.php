<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\AppSettings;

/**
 * Serves uploaded assets (e.g. company logo) when document root is not public/.
 */
class ServeController extends Controller
{
    public function companyLogo(): void
    {
        $path = AppSettings::get('company_logo', '');
        if (!$path || strpos($path, '..') !== false) {
            http_response_code(404);
            exit;
        }
        $file = ROOT . '/public/' . $path;
        if (!is_file($file)) {
            http_response_code(404);
            exit;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        $mime = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }
}
