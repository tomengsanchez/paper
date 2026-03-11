<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * Column configuration for list tables. All configured columns are searchable.
 * Selected columns are persisted per logged-in user.
 */
class ListConfig
{
    private static array $configs = [
        'profile' => [
            ['key' => 'papsid', 'label' => 'PAPSID', 'sortable' => true],
            ['key' => 'control_number', 'label' => 'Control Number', 'sortable' => true],
            ['key' => 'full_name', 'label' => 'Full Name', 'sortable' => true],
            ['key' => 'age', 'label' => 'Age', 'sortable' => true],
            ['key' => 'contact_number', 'label' => 'Contact', 'sortable' => true],
            ['key' => 'project_name', 'label' => 'Project', 'sortable' => true],
            ['key' => 'other_details', 'label' => 'Other Details', 'sortable' => true],
        ],
        'structure' => [
            ['key' => 'strid', 'label' => 'Structure ID', 'sortable' => true],
            ['key' => 'owner_name', 'label' => 'Paps/Owner', 'sortable' => true],
            ['key' => 'structure_tag', 'label' => 'Structure Tag #', 'sortable' => true],
            ['key' => 'description', 'label' => 'Description', 'sortable' => true],
        ],
        'users' => [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'username', 'label' => 'Username', 'sortable' => true],
            ['key' => 'display_name', 'label' => 'Display name', 'sortable' => true],
            ['key' => 'email', 'label' => 'Email', 'sortable' => true],
            ['key' => 'role_name', 'label' => 'Role', 'sortable' => true],
            ['key' => 'linked_projects_count', 'label' => 'Linked Projects', 'sortable' => true],
        ],
        'library' => [
            ['key' => 'name', 'label' => 'Project Name', 'sortable' => true],
            ['key' => 'description', 'label' => 'Description', 'sortable' => true],
            ['key' => 'linked_users_count', 'label' => 'Linked Users', 'sortable' => true],
        ],
        'roles' => [
            ['key' => 'name', 'label' => 'Role', 'sortable' => true],
            ['key' => 'capabilities', 'label' => 'Capabilities', 'sortable' => false],
        ],
        'grievance' => [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'date_recorded', 'label' => 'Date Recorded', 'sortable' => true],
            ['key' => 'grievance_case_number', 'label' => 'Case Number', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'respondent_name', 'label' => 'Respondent', 'sortable' => true],
            ['key' => 'profile_name', 'label' => 'Profile (PAPS)', 'sortable' => true],
        ],
    ];

    /** All exportable columns per module (subset of list columns + additional fields). Used in export dialog. */
    private static array $exportColumns = [
        'profile' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'papsid', 'label' => 'PAPSID'],
            ['key' => 'control_number', 'label' => 'Control Number'],
            ['key' => 'full_name', 'label' => 'Full Name'],
            ['key' => 'age', 'label' => 'Age'],
            ['key' => 'contact_number', 'label' => 'Contact Number'],
            ['key' => 'project_name', 'label' => 'Project'],
            ['key' => 'structure_count', 'label' => 'Structures Count'],
            ['key' => 'residing_in_project_affected', 'label' => 'Residing in Project Affected'],
            ['key' => 'residing_in_project_affected_note', 'label' => 'Residing Note'],
            ['key' => 'structure_owners', 'label' => 'Structure Owners'],
            ['key' => 'structure_owners_note', 'label' => 'Structure Owners Note'],
            ['key' => 'if_not_structure_owner_what', 'label' => 'If Not Owner - What'],
            ['key' => 'own_property_elsewhere', 'label' => 'Own Property Elsewhere'],
            ['key' => 'own_property_elsewhere_note', 'label' => 'Own Property Elsewhere Note'],
            ['key' => 'availed_government_housing', 'label' => 'Availed Government Housing'],
            ['key' => 'availed_government_housing_note', 'label' => 'Availed Government Housing Note'],
            ['key' => 'hh_income', 'label' => 'Household Income'],
        ],
        'structure' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'strid', 'label' => 'Structure ID'],
            ['key' => 'owner_name', 'label' => 'Owner (PAPS)'],
            ['key' => 'structure_tag', 'label' => 'Structure Tag'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'other_details', 'label' => 'Other Details'],
        ],
        'grievance' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'date_recorded', 'label' => 'Date Recorded'],
            ['key' => 'grievance_case_number', 'label' => 'Case Number'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'project_name', 'label' => 'Project'],
            ['key' => 'profile_name', 'label' => 'Profile (PAPS)'],
            ['key' => 'respondent_name', 'label' => 'Respondent Name'],
            ['key' => 'is_paps', 'label' => 'Is PAPS'],
            ['key' => 'gender', 'label' => 'Gender'],
            ['key' => 'gender_specify', 'label' => 'Gender Specify'],
            ['key' => 'valid_id_philippines', 'label' => 'Valid ID Philippines'],
            ['key' => 'id_number', 'label' => 'ID Number'],
            ['key' => 'vulnerability_names', 'label' => 'Vulnerabilities'],
            ['key' => 'respondent_type_names', 'label' => 'Respondent Types'],
            ['key' => 'respondent_type_other_specify', 'label' => 'Respondent Type Other'],
            ['key' => 'home_business_address', 'label' => 'Address'],
            ['key' => 'mobile_number', 'label' => 'Mobile Number'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'contact_others_specify', 'label' => 'Contact Other'],
            ['key' => 'grm_channel_names', 'label' => 'GRM Channels'],
            ['key' => 'preferred_language_names', 'label' => 'Preferred Languages'],
            ['key' => 'preferred_language_other_specify', 'label' => 'Language Other'],
            ['key' => 'grievance_type_names', 'label' => 'Grievance Types'],
            ['key' => 'grievance_category_names', 'label' => 'Grievance Categories'],
            ['key' => 'location_same_as_address', 'label' => 'Location Same as Address'],
            ['key' => 'location_specify', 'label' => 'Location Specify'],
            ['key' => 'incident_one_time', 'label' => 'Incident One Time'],
            ['key' => 'incident_date', 'label' => 'Incident Date'],
            ['key' => 'incident_multiple', 'label' => 'Incident Multiple'],
            ['key' => 'incident_dates', 'label' => 'Incident Dates'],
            ['key' => 'incident_ongoing', 'label' => 'Incident Ongoing'],
            ['key' => 'description_complaint', 'label' => 'Description/Complaint'],
            ['key' => 'desired_resolution', 'label' => 'Desired Resolution'],
            ['key' => 'progress_level_name', 'label' => 'Progress Level'],
        ],
        'users' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'username', 'label' => 'Username'],
            ['key' => 'display_name', 'label' => 'Display Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'role_name', 'label' => 'Role'],
            ['key' => 'linked_projects_count', 'label' => 'Linked Projects Count'],
            ['key' => 'created_at', 'label' => 'Created At'],
            ['key' => 'updated_at', 'label' => 'Updated At'],
        ],
        'library' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'name', 'label' => 'Project Name'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'linked_users_count', 'label' => 'Linked Users Count'],
        ],
    ];

    public static function getExportColumns(string $module): array
    {
        return self::$exportColumns[$module] ?? self::getColumns($module);
    }

    public static function getColumns(string $module): array
    {
        return self::$configs[$module] ?? [];
    }

    public static function getDefaultKeys(string $module): array
    {
        $cols = self::getColumns($module);
        return array_column($cols, 'key');
    }

    public static function getColumnByKey(string $module, string $key): ?array
    {
        foreach (self::getColumns($module) as $col) {
            if ($col['key'] === $key) return $col;
        }
        foreach (self::getExportColumns($module) as $col) {
            if ($col['key'] === $key) {
                return array_merge($col, ['sortable' => $col['sortable'] ?? false]);
            }
        }
        return null;
    }

    public static function resolveSelectedKeys(string $module, ?string $param, ?array $session): array
    {
        $defaults = self::getDefaultKeys($module);
        $validKeys = array_column(self::getExportColumns($module), 'key');
        if (!empty($param)) {
            $requested = array_map('trim', explode(',', $param));
            return array_values(array_intersect($requested, $validKeys)) ?: $defaults;
        }
        if (!empty($session)) {
            return array_values(array_intersect($session, $validKeys)) ?: $defaults;
        }
        return $defaults;
    }

    /** Resolve columns from GET (columns param or col[] array). When from GET, saves to user prefs. */
    public static function resolveFromRequest(string $module, ?array $get = null, ?array $session = null): array
    {
        $get = $get ?? $_GET ?? [];
        $param = $get['columns'] ?? null;
        if (empty($param) && !empty($get['col']) && is_array($get['col'])) {
            $param = implode(',', array_map('trim', $get['col']));
        }

        $userPrefs = self::getUserColumns($module);
        $resolved = self::resolveSelectedKeys($module, $param, $session ?? $userPrefs);

        if (!empty($param) && Auth::id()) {
            self::saveUserColumns(Auth::id(), $module, $resolved);
        }

        return $resolved;
    }

    /** Load saved column keys for the current user and module */
    public static function getUserColumns(string $module): ?array
    {
        $userId = Auth::id();
        if (!$userId) return null;

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT column_keys FROM user_list_columns WHERE user_id = ? AND module = ?');
        $stmt->execute([$userId, $module]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row || $row->column_keys === '') return null;

        return array_values(array_filter(array_map('trim', explode(',', $row->column_keys))));
    }

    /** Save column keys for a user and module */
    public static function saveUserColumns(int $userId, string $module, array $columnKeys): void
    {
        $columnKeys = array_values(array_filter($columnKeys));
        $keysStr = implode(',', $columnKeys);

        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO user_list_columns (user_id, module, column_keys) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE column_keys = VALUES(column_keys)');
        $stmt->execute([$userId, $module, $keysStr]);
    }

    /** Returns true if the current user has custom column preferences saved for this module */
    public static function hasCustomColumns(string $module): bool
    {
        return Auth::id() && self::getUserColumns($module) !== null;
    }
}
