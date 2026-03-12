<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\GeneralSettings;
use App\DevelopmentSettings;
use App\DevClock;
use App\Models\AppSettings;
use App\UserUiSettings;
use App\UserNotificationSettings;

class SettingsController extends Controller
{
    /**
     * Current user's UI and notification preferences.
     */
    public function ui(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }

        $ui = UserUiSettings::get();
        $notify = UserNotificationSettings::get();

        $this->json([
            'ui' => $ui,
            'notifications' => $notify,
        ]);
    }

    /**
     * General system settings (region, timezone, branding).
     * Admin-only.
     */
    public function general(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden', 'message' => 'Administrator access required.']);
        }

        $settings = GeneralSettings::get();
        $branding = AppSettings::getBrandingConfig();

        $this->json([
            'settings' => $settings,
            'branding' => $branding,
            'regions' => GeneralSettings::regions(),
            'timezones' => GeneralSettings::timezones(),
        ]);
    }

    /**
     * Email (SMTP) settings.
     * Requires view_email_settings capability.
     */
    public function email(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::can('view_email_settings')) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden', 'message' => 'view_email_settings capability required.']);
        }

        $config = AppSettings::getEmailConfig();

        $this->json([
            'config' => $config,
        ]);
    }

    /**
     * Security settings (password policy, login throttling, etc.).
     * Requires view_security_settings capability.
     */
    public function security(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::can('view_security_settings')) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden', 'message' => 'view_security_settings capability required.']);
        }

        $config = AppSettings::getSecurityConfig();

        $this->json([
            'config' => $config,
        ]);
    }

    /**
     * Development settings and simulated time.
     * Admin-only.
     */
    public function development(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden', 'message' => 'Administrator access required.']);
        }

        $settings = DevelopmentSettings::get();
        $simulatedOverride = DevClock::getOverride();

        $this->json([
            'settings' => $settings,
            'simulated_date' => $simulatedOverride,
        ]);
    }
}

