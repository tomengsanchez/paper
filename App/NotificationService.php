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
    public const TYPE_NEW_GRIEVANCE = 'new_grievance';
    public const TYPE_GRIEVANCE_STATUS_CHANGE = 'grievance_status_change';

    public const RELATED_PROFILE = 'profile';
    public const RELATED_GRIEVANCE = 'grievance';

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

    private static function notifyUsersForProject(
        int $projectId,
        string $prefKey,
        string $type,
        string $relatedType,
        int $relatedId,
        string $message
    ): void {
        $db = Database::getInstance();

        // Get users who have this project linked (exclude the current actor)
        $actorId = Auth::id();
        $stmt = $db->prepare('SELECT DISTINCT user_id FROM user_projects WHERE project_id = ? AND user_id != ?');
        $stmt->execute([$projectId, $actorId ?? 0]);
        $userIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($userIds)) {
            return;
        }

        $ins = $db->prepare('INSERT INTO notifications (user_id, type, related_type, related_id, message) VALUES (?, ?, ?, ?, ?)');

        foreach ($userIds as $uid) {
            $uid = (int) $uid;
            $prefs = self::getPrefsForUser($db, $uid);
            if (empty($prefs[$prefKey])) {
                continue;
            }
            $ins->execute([$uid, $type, $relatedType, $relatedId, $message]);
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
        return array_merge(UserNotificationSettings::defaultConfig(), [
            UserNotificationSettings::NOTIFY_NEW_PROFILE            => !empty($d[UserNotificationSettings::NOTIFY_NEW_PROFILE]),
            UserNotificationSettings::NOTIFY_NEW_GRIEVANCE          => !empty($d[UserNotificationSettings::NOTIFY_NEW_GRIEVANCE]),
            UserNotificationSettings::NOTIFY_GRIEVANCE_STATUS_CHANGE => !empty($d[UserNotificationSettings::NOTIFY_GRIEVANCE_STATUS_CHANGE]),
        ]);
    }

    public static function getForUser(int $userId, int $limit = 20): array
    {
        $db = Database::getInstance();
        $limit = max(1, min(100, (int) $limit));
        $sql = 'SELECT id, type, related_type, related_id, message, created_at 
                FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ' . $limit;
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Delete notification and return redirect URL.
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
        $del = $db->prepare('DELETE FROM notifications WHERE id = ?');
        $del->execute([$notificationId]);

        if ($row->related_type === self::RELATED_PROFILE) {
            return '/profile/view/' . (int) $row->related_id;
        }
        if ($row->related_type === self::RELATED_GRIEVANCE) {
            return '/grievance/view/' . (int) $row->related_id;
        }
        return '/';
    }
}
