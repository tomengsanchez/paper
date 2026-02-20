<?php
namespace App;

/**
 * Column configuration for list tables. All configured columns are searchable.
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
            ['key' => 'email', 'label' => 'Email', 'sortable' => true],
            ['key' => 'role_name', 'label' => 'Role', 'sortable' => true],
        ],
        'user_profiles' => [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'role_name', 'label' => 'Role', 'sortable' => true],
            ['key' => 'username', 'label' => 'Linked User', 'sortable' => true],
        ],
        'library' => [
            ['key' => 'name', 'label' => 'Project Name', 'sortable' => true],
            ['key' => 'description', 'label' => 'Description', 'sortable' => true],
            ['key' => 'coordinator_name', 'label' => 'Coordinator', 'sortable' => true],
        ],
        'roles' => [
            ['key' => 'name', 'label' => 'Role', 'sortable' => true],
            ['key' => 'capabilities', 'label' => 'Capabilities', 'sortable' => false],
        ],
    ];

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
        return null;
    }

    public static function resolveSelectedKeys(string $module, ?string $param, ?array $session): array
    {
        $defaults = self::getDefaultKeys($module);
        if (!empty($param)) {
            $requested = array_map('trim', explode(',', $param));
            $validKeys = array_column(self::getColumns($module), 'key');
            return array_values(array_intersect($requested, $validKeys)) ?: $defaults;
        }
        if (!empty($session)) {
            $validKeys = array_column(self::getColumns($module), 'key');
            return array_values(array_intersect($session, $validKeys)) ?: $defaults;
        }
        return $defaults;
    }

    /** Resolve columns from GET (columns param or col[] array) */
    public static function resolveFromRequest(string $module, ?array $get = null, ?array $session = null): array
    {
        $get = $get ?? $_GET ?? [];
        $param = $get['columns'] ?? null;
        if (empty($param) && !empty($get['col']) && is_array($get['col'])) {
            $param = implode(',', array_map('trim', $get['col']));
        }
        return self::resolveSelectedKeys($module, $param, $session);
    }
}
