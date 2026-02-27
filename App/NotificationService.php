<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * Creates and manages user notifications for profile/grievance events.
 */
class NotificationService
{
    public const TYPE_NEW_PROFILE = 'new_profile';
    public const TYPE_PROFILE_UPDATED = 'profile_updated';
    public const TYPE_NEW_GRIEVANCE = 'new_grievance';
    public const TYPE_GRIEVANCE_STATUS_CHANGE = 'grievance_status_change';
    public const TYPE_NEW_STRUCTURE = 'new_structure';

    public const RELATED_PROFILE = 'profile';
    public const RELATED_GRIEVANCE = 'grievance';
    public const RELATED_STRUCTURE = 'structure';

    /**
     * Notify users with linked project_id and matching preference.
     */
    public static function notifyNewProfile(int $profileId, int $projectId, string $message): void
    {
        self::notifyUsersForProject(
            $projectId,
            UserNotificationSettings::NOTIFY_NEW_PROFILE,
            self::TYPE_NEW_PROFILE,
            self::RELATED_PROFILE,
            $profileId,
            $message
        );
    }

    public static function notifyProfileUpdated(int $profileId, int $projectId, string $message): void
    {
        self::notifyUsersForProject(
            $projectId,
            UserNotificationSettings::NOTIFY_PROFILE_UPDATED,
            self::TYPE_PROFILE_UPDATED,
            self::RELATED_PROFILE,
            $profileId,
            $message
        );
    }

    public static function notifyNewGrievance(int $grievanceId, ?int $projectId, string $message): void
    {
        if (!$projectId) {
            return;
        }
        self::notifyUsersForProject(
            $projectId,
            UserNotificationSettings::NOTIFY_NEW_GRIEVANCE,
            self::TYPE_NEW_GRIEVANCE,
            self::RELATED_GRIEVANCE,
            $grievanceId,
            $message
        );
    }

    public static function notifyGrievanceStatusChange(int $grievanceId, ?int $projectId, string $message): void
    {
        if (!$projectId) {
            return;
        }
        self::notifyUsersForProject(
            $projectId,
            UserNotificationSettings::NOTIFY_GRIEVANCE_STATUS_CHANGE,
            self::TYPE_GRIEVANCE_STATUS_CHANGE,
            self::RELATED_GRIEVANCE,
            $grievanceId,
            $message
        );
    }

    public static function notifyNewStructure(int $structureId, int $projectId, string $message): void
    {
        if (!$projectId) {
            return;
        }
        self::notifyUsersForProject(
            $projectId,
            UserNotificationSettings::NOTIFY_NEW_PROFILE,
            self::TYPE_NEW_STRUCTURE,
            self::RELATED_STRUCTURE,
            $structureId,
            $message
        );
    }

    private static function notifyUsersForProject(
        int $projectId,
        string $prefKey,
        string $type,
        string $relatedType,
        int $relatedId,
        string $message
    ): void {
        $db = Database::getInstance();

        // Get users who have this project linked
        $stmt = $db->prepare('SELECT DISTINCT user_id FROM user_projects WHERE project_id = ?');
        $stmt->execute([$projectId]);
        $userIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($userIds)) {
            return;
        }

        $ins = $db->prepare('INSERT INTO notifications (user_id, type, related_type, related_id, project_id, message) VALUES (?, ?, ?, ?, ?, ?)');

        foreach ($userIds as $uid) {
            $uid = (int) $uid;
            $prefs = self::getPrefsForUser($db, $uid);
            if (empty($prefs[$prefKey])) {
                continue;
            }
            $ins->execute([$uid, $type, $relatedType, $relatedId, $projectId, $message]);
        }
    }

    private static function getPrefsForUser(\PDO $db, int $userId): array
    {
        $stmt = $db->prepare('SELECT config FROM user_dashboard_config WHERE user_id = ? AND module = ?');
        $stmt->execute([$userId, UserNotificationSettings::MODULE]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row || !$row->config) {
            return UserNotificationSettings::defaultConfig();
        }
        $d = json_decode($row->config, true);
        if (!is_array($d)) {
            return UserNotificationSettings::defaultConfig();
        }
        // Merge stored values with defaults to ensure any new prefs default to true.
        $merged = UserNotificationSettings::defaultConfig();
        foreach ($merged as $key => $defaultVal) {
            if (array_key_exists($key, $d)) {
                $merged[$key] = !empty($d[$key]);
            }
        }
        return $merged;
    }

    public static function getForUser(int $userId, int $limit = 20): array
    {
        $db = Database::getInstance();
        $limit = max(1, min(100, (int) $limit));
        $sql = 'SELECT id, type, related_type, related_id, project_id, message, created_at, clicked_at
                FROM notifications 
                WHERE user_id = ? AND (clicked_at IS NULL)
                ORDER BY created_at DESC 
                LIMIT ' . $limit;
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Paginated list of notifications for history view with filters.
     *
     * @param int   $userId
     * @param array $filters ['module' => ?, 'project_id' => ?, 'from' => ?, 'to' => ?]
     * @param int   $page
     * @param int   $perPage
     * @return array{items: array<int,object>, total:int, page:int, per_page:int, total_pages:int}
     */
    public static function listForUser(int $userId, array $filters, int $page = 1, int $perPage = 20): array
    {
        $db = Database::getInstance();
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));

        $where = ['n.user_id = :uid'];
        $params = ['uid' => $userId];

        // Module filter (by related_type)
        $module = $filters['module'] ?? '';
        if (in_array($module, [self::RELATED_PROFILE, self::RELATED_GRIEVANCE, self::RELATED_STRUCTURE], true)) {
            $where[] = 'n.related_type = :rtype';
            $params['rtype'] = $module;
        }

        // Project filter
        if (!empty($filters['project_id'])) {
            $where[] = 'n.project_id = :pid';
            $params['pid'] = (int) $filters['project_id'];
        }

        // Date range filter (created_at)
        if (!empty($filters['from'])) {
            $where[] = 'n.created_at >= :from';
            $params['from'] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'n.created_at <= :to';
            $params['to'] = $filters['to'] . ' 23:59:59';
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT n.id, n.type, n.related_type, n.related_id, n.project_id, n.message, n.created_at, n.clicked_at
            FROM notifications n
            WHERE {$whereSql}
            ORDER BY n.created_at DESC, n.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countSql = "SELECT COUNT(*) FROM notifications n WHERE {$whereSql}";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();
        $totalPages = (int) ceil($total / $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Mark notification as clicked and return redirect URL.
     */
    public static function clickAndGetUrl(int $notificationId, int $userId): ?string
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id, related_type, related_id FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->execute([$notificationId, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row) {
            return null;
        }
        $upd = $db->prepare('UPDATE notifications SET clicked_at = NOW() WHERE id = ?');
        $upd->execute([$notificationId]);

        if ($row->related_type === self::RELATED_PROFILE) {
            return '/profile/view/' . (int) $row->related_id;
        }
        if ($row->related_type === self::RELATED_GRIEVANCE) {
            return '/grievance/view/' . (int) $row->related_id;
        }
        if ($row->related_type === self::RELATED_STRUCTURE) {
            return '/structure/view/' . (int) $row->related_id;
        }
        return '/';
    }
}
