<?php
namespace App;

use Core\Database;

/**
 * Manages user notifications.
 * Legacy PAPeR-related types (profile, grievance, structure) redirect to dashboard.
 * When admin enables "notification emails", messages are queued (email_queue) and sent in the background
 * by cli/send_queued_emails.php (cron).
 */
class NotificationService
{
    public const TYPE_NEW_PROFILE = 'new_profile';
    public const TYPE_PROFILE_UPDATED = 'profile_updated';
    public const TYPE_NEW_GRIEVANCE = 'new_grievance';
    public const TYPE_GRIEVANCE_STATUS_CHANGE = 'grievance_status_change';
    public const TYPE_GRIEVANCE_UPDATED = 'grievance_updated';
    public const TYPE_NEW_STRUCTURE = 'new_structure';

    public const RELATED_PROFILE = 'profile';
    public const RELATED_GRIEVANCE = 'grievance';
    public const RELATED_STRUCTURE = 'structure';

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

        $module = $filters['module'] ?? '';
        if (in_array($module, [self::RELATED_PROFILE, self::RELATED_GRIEVANCE, self::RELATED_STRUCTURE], true)) {
            $where[] = 'n.related_type = :rtype';
            $params['rtype'] = $module;
        }

        if (!empty($filters['project_id'])) {
            $where[] = 'n.project_id = :pid';
            $params['pid'] = (int) $filters['project_id'];
        }

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
     * Legacy PAPeR entity links redirect to dashboard (those modules removed).
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

        if (in_array($row->related_type ?? '', [self::RELATED_PROFILE, self::RELATED_GRIEVANCE, self::RELATED_STRUCTURE], true)) {
            return '/';
        }
        return '/';
    }
}
