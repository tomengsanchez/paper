<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * Helper for per-user project scoping (multi-project visibility).
 *
 * - Administrators: see all projects (no restriction).
 * - Other roles: limited to projects listed in user_projects for the current user.
 */
class UserProjects
{
    /** Cached per-request to avoid repeated queries. */
    private static bool $loaded = false;
    private static ?array $allowed = null;

    /**
     * Returns:
     * - null  => no restriction (Administrator)
     * - []    => user has no linked projects (see nothing)
     * - array => list of allowed project IDs (ints)
     */
    public static function allowedProjectIds(): ?array
    {
        if (!Auth::check()) {
            // No session (e.g. CLI seeders, cron). No restriction so scripts see all data.
            return null;
        }

        if (Auth::isAdmin()) {
            return null;
        }

        if (self::$loaded) {
            return self::$allowed;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT project_id FROM user_projects WHERE user_id = ?');
        $stmt->execute([Auth::id()]);
        $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $ids = array_map('intval', $rows ?: []);

        self::$allowed = $ids;
        self::$loaded = true;

        return self::$allowed;
    }
}

