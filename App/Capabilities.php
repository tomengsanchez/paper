<?php
namespace App;

/**
 * Central registry of entity capabilities.
 * Each entity has: view (list), add, edit, delete.
 * Add new entities here - capabilities are derived from modules.
 */
class Capabilities
{
    /** [entity_key => [capability_key => label]] */
    private static array $entities = [
        'Profile' => [
            'view_profiles'   => 'View List',
            'add_profiles'    => 'Add',
            'edit_profiles'   => 'Edit',
            'delete_profiles' => 'Delete',
        ],
        'Structure' => [
            'view_structure'   => 'View List',
            'add_structure'    => 'Add',
            'edit_structure'   => 'Edit',
            'delete_structure' => 'Delete',
        ],
        'Grievance' => [
            'view_grievance'        => 'View List',
            'add_grievance'         => 'Add',
            'edit_grievance'        => 'Edit',
            'delete_grievance'      => 'Delete',
            'change_grievance_status' => 'Change Status',
        ],
        'Grievance Options' => [
            'manage_grievance_options' => 'Manage Options Library',
        ],
        'Library - Project' => [
            'view_projects'   => 'View List',
            'add_projects'    => 'Add',
            'edit_projects'   => 'Edit',
            'delete_projects' => 'Delete',
        ],
        'Settings' => [
            'view_settings' => 'View',
            'manage_settings' => 'Manage',
        ],
        'Email Settings' => [
            'view_email_settings' => 'View',
            'manage_email_settings' => 'Manage',
        ],
        'Security' => [
            'view_security_settings' => 'View',
            'manage_security_settings' => 'Manage',
        ],
        'Users' => [
            'view_users'   => 'View List',
            'add_users'    => 'Add',
            'edit_users'   => 'Edit',
            'delete_users' => 'Delete',
        ],
        'User Roles & Capabilities' => [
            'view_roles'  => 'View List',
            'edit_roles'  => 'Edit',
        ],
    ];

    /** Map page/menu to view capability for visibility */
    private static array $menuCapability = [
        'profile'       => 'view_profiles',
        'structure'     => 'view_structure',
        'grievance'     => 'view_grievance',
        'grievance-dashboard' => 'view_grievance',
        'grievance-list' => 'view_grievance',
        'grievance-vulnerabilities' => 'manage_grievance_options',
        'grievance-respondent-types' => 'manage_grievance_options',
        'grievance-grm-channels' => 'manage_grievance_options',
        'grievance-preferred-languages' => 'manage_grievance_options',
        'grievance-types' => 'manage_grievance_options',
        'grievance-categories' => 'manage_grievance_options',
        'library'       => 'view_projects',
        'settings'      => 'view_settings',
        'email-settings' => 'view_email_settings',
        'security-settings' => 'view_security_settings',
        'users'         => 'view_users',
        'user-roles'    => 'view_roles',
    ];

    public static function entities(): array
    {
        return self::$entities;
    }

    /** Flat list [cap_key => label] for backward compat */
    public static function all(): array
    {
        $flat = [];
        foreach (self::$entities as $caps) {
            $flat = array_merge($flat, $caps);
        }
        return $flat;
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function getLabel(string $key): string
    {
        foreach (self::$entities as $caps) {
            if (isset($caps[$key])) return $caps[$key];
        }
        return $key;
    }

    public static function forMenu(string $page): ?string
    {
        return self::$menuCapability[$page] ?? null;
    }

    /** Add a new entity - call when adding features */
    public static function registerEntity(string $entityName, array $capabilities, ?string $menuKey = null): void
    {
        self::$entities[$entityName] = $capabilities;
        $viewKey = array_key_first($capabilities);
        if ($menuKey && $viewKey) {
            self::$menuCapability[$menuKey] = $viewKey;
        }
    }
}
