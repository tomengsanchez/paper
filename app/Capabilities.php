<?php
namespace App;

/**
 * Central registry of modules and their capabilities.
 * Add new modules here - capabilities are derived from modules.
 */
class Capabilities
{
    /** [capability_key => display_label] - one capability per module/feature */
    private static array $registry = [
        'manage_profiles'      => 'Profile',
        'manage_structure'     => 'Structure',
        'manage_grievance'     => 'Grievance',
        'manage_projects'      => 'Library - Project',
        'manage_settings'      => 'Settings',
        'manage_user_profiles' => 'User Profile',
        'manage_users'         => 'Users',
        'manage_roles'         => 'User Roles & Capabilities',
    ];

    /** Map capability to route/menu for access check */
    private static array $routeMap = [
        'profile'       => 'manage_profiles',
        'structure'     => 'manage_structure',
        'grievance'     => 'manage_grievance',
        'library'       => 'manage_projects',
        'settings'      => 'manage_settings',
        'user-profiles' => 'manage_user_profiles',
        'users'         => 'manage_users',
        'user-roles'    => 'manage_roles',
    ];

    public static function all(): array
    {
        return self::$registry;
    }

    public static function keys(): array
    {
        return array_keys(self::$registry);
    }

    public static function getLabel(string $key): string
    {
        return self::$registry[$key] ?? $key;
    }

    public static function forPage(string $page): ?string
    {
        return self::$routeMap[$page] ?? null;
    }

    /** Add a new module - call this when adding features */
    public static function register(string $capability, string $label, string $pageKey = null): void
    {
        self::$registry[$capability] = $label;
        if ($pageKey !== null) {
            self::$routeMap[$pageKey] = $capability;
        }
    }
}
