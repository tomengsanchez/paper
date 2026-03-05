<?php
namespace App;

use Core\Auth;
use Core\Database;
use App\Models\AppSettings;
use App\Models\Profile;
use App\Models\Grievance;
use App\Models\Structure;
use App\Models\GrievanceProgressLevel;

/**
 * Creates and manages user notifications for profile/grievance events.
 * When admin enables "notification emails", messages are queued (email_queue) and sent in the background
 * by cli/send_queued_emails.php (cron), so save/update requests return immediately.
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

    public static function notifyGrievanceUpdated(int $grievanceId, ?int $projectId, string $message): void
    {
        if (!$projectId) {
            return;
        }
        self::notifyUsersForProject(
            $projectId,
            UserNotificationSettings::NOTIFY_GRIEVANCE_UPDATED,
            self::TYPE_GRIEVANCE_UPDATED,
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

        // Get users who have this project linked, with email for notification delivery
        $stmt = $db->prepare('SELECT DISTINCT u.id, u.email FROM users u INNER JOIN user_projects up ON up.user_id = u.id WHERE up.project_id = ?');
        $stmt->execute([$projectId]);
        $users = $stmt->fetchAll(\PDO::FETCH_OBJ);

        if (empty($users)) {
            return;
        }

        $ins = $db->prepare('INSERT INTO notifications (user_id, type, related_type, related_id, project_id, message) VALUES (?, ?, ?, ?, ?, ?)');
        $emailConfig = AppSettings::getEmailConfig();
        $sendNotificationEmail = !empty($emailConfig->enable_notification_emails);
        $baseUrl = defined('BASE_URL') && BASE_URL ? rtrim(BASE_URL, '/') : '';

        foreach ($users as $u) {
            $uid = (int) $u->id;
            $prefs = self::getPrefsForUser($db, $uid);
            if (empty($prefs[$prefKey])) {
                continue;
            }
            $ins->execute([$uid, $type, $relatedType, $relatedId, $projectId, $message]);
            $notificationId = (int) $db->lastInsertId();

            if ($sendNotificationEmail && $notificationId && !empty(trim($u->email ?? ''))) {
                $clickUrl = $baseUrl . '/notifications/click/' . $notificationId;
                $subject = 'PAPeR: ' . $message;
                try {
                    $body = self::buildNotificationEmailBody($relatedType, $relatedId, $type, $message, $clickUrl);
                    $bodyFormat = 'html';
                } catch (\Throwable $e) {
                    \Core\Logger::log('notification_error.log', 'buildNotificationEmailBody failed', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'relatedType' => $relatedType,
                        'relatedId' => $relatedId,
                    ]);
                    $body = self::fallbackEmailBody($message, $clickUrl);
                    $bodyFormat = 'html';
                }
                $db->prepare('INSERT INTO email_queue (to_email, subject, body, body_format) VALUES (?, ?, ?, ?)')
                    ->execute([trim($u->email), $subject, $body, $bodyFormat]);
            }
        }
    }

    /**
     * Build HTML email body with full record details; changed fields (from audit) highlighted in yellow.
     */
    private static function buildNotificationEmailBody(string $relatedType, int $relatedId, string $type, string $message, string $viewUrl): string
    {
        $changes = [];
        if (in_array($type, [self::TYPE_PROFILE_UPDATED, self::TYPE_GRIEVANCE_STATUS_CHANGE, self::TYPE_GRIEVANCE_UPDATED], true)) {
            $history = AuditLog::for($relatedType, $relatedId, 1);
            $action = $history[0]->action ?? '';
            $wantAction = ($type === self::TYPE_GRIEVANCE_UPDATED) ? 'updated' : (($type === self::TYPE_GRIEVANCE_STATUS_CHANGE) ? 'status_changed' : 'updated');
            if (!empty($history) && $action === $wantAction) {
                $changes = is_array($history[0]->changes ?? null) ? $history[0]->changes : [];
            }
        }

        $rows = '';
        if ($relatedType === self::RELATED_PROFILE) {
            $record = Profile::find($relatedId);
            if (!$record) {
                return self::fallbackEmailBody($message, $viewUrl);
            }
            $fields = [
                'papsid' => 'PAPSID',
                'control_number' => 'Control Number',
                'full_name' => 'Full Name',
                'age' => 'Age',
                'contact_number' => 'Contact Number',
                'project_name' => 'Project',
                'residing_in_project_affected' => 'Residing in project affected?',
                'residing_in_project_affected_note' => 'Residing note',
                'structure_owners' => 'Structure owners?',
                'structure_owners_note' => 'Structure owners note',
                'if_not_structure_owner_what' => 'If not owner, what?',
                'own_property_elsewhere' => 'Own property elsewhere?',
                'own_property_elsewhere_note' => 'Own elsewhere note',
                'availed_government_housing' => 'Availed govt housing?',
                'availed_government_housing_note' => 'Availed housing note',
                'hh_income' => 'HH Income',
            ];
            foreach ($fields as $key => $label) {
                $val = $record->$key ?? null;
                if ($key === 'residing_in_project_affected' || $key === 'structure_owners' || $key === 'own_property_elsewhere' || $key === 'availed_government_housing') {
                    $val = !empty($val) ? 'Yes' : 'No';
                } else {
                    $val = $val === null || $val === '' ? '-' : (string) $val;
                }
                $rows .= self::emailRow($label, $val, $changes[$key] ?? null);
            }
        } elseif ($relatedType === self::RELATED_GRIEVANCE) {
            $record = Grievance::find($relatedId);
            if (!$record) {
                return self::fallbackEmailBody($message, $viewUrl);
            }
            $statusLabel = match ($record->status ?? '') {
                'open' => 'Open',
                'in_progress' => 'In Progress',
                'closed' => 'Closed',
                default => (string) ($record->status ?? '-'),
            };
            $progressLevelId = isset($record->progress_level) ? (int) $record->progress_level : null;
            $plRecord = $progressLevelId ? GrievanceProgressLevel::find($progressLevelId) : null;
            $progressLevelName = $plRecord ? $plRecord->name : ($progressLevelId ? (string) $progressLevelId : '-');
            $progressLevelChange = null;
            if (!empty($changes['progress_level']) && (isset($changes['progress_level']['from']) || isset($changes['progress_level']['to']))) {
                $plFrom = $changes['progress_level']['from'] ?? null;
                $plTo = $changes['progress_level']['to'] ?? null;
                $resolvedFrom = ($plFrom !== null && $plFrom !== '') ? GrievanceProgressLevel::find((int) $plFrom) : null;
                $resolvedTo = ($plTo !== null && $plTo !== '') ? GrievanceProgressLevel::find((int) $plTo) : null;
                $progressLevelChange = [
                    'from' => $resolvedFrom ? $resolvedFrom->name : (string) $plFrom,
                    'to' => $resolvedTo ? $resolvedTo->name : (string) $plTo,
                ];
            }
            $fields = [
                'date_recorded' => 'Date Recorded',
                'grievance_case_number' => 'Case Number',
                'project_name' => 'Project',
                'status' => 'Status',
                'progress_level' => 'Progress Level',
                'profile_name' => 'Profile (PAPS)',
                'respondent_full_name' => 'Respondent Name',
                'gender' => 'Gender',
                'home_business_address' => 'Address',
                'mobile_number' => 'Mobile',
                'email' => 'Email',
                'nature_of_grievance' => 'Nature of Grievance',
                'desired_outcome' => 'Desired Outcome',
            ];
            $overrides = ['status' => $statusLabel, 'progress_level' => $progressLevelName];
            foreach ($fields as $key => $label) {
                $val = $overrides[$key] ?? $record->$key ?? null;
                $val = $val === null || $val === '' ? '-' : (string) $val;
                if ($key === 'date_recorded' && $val !== '-') {
                    $val = date('M j, Y H:i', strtotime($val));
                }
                $changeForRow = $key === 'progress_level' ? $progressLevelChange : ($changes[$key] ?? null);
                $rows .= self::emailRow($label, $val, $changeForRow);
            }
        } elseif ($relatedType === self::RELATED_STRUCTURE) {
            $record = Structure::find($relatedId);
            if (!$record) {
                return self::fallbackEmailBody($message, $viewUrl);
            }
            $fields = [
                'strid' => 'Structure ID',
                'owner_name' => 'Owner',
                'structure_tag' => 'Structure Tag',
                'description' => 'Description',
                'other_details' => 'Other Details',
            ];
            foreach ($fields as $key => $label) {
                $val = $record->$key ?? null;
                $val = $val === null || $val === '' ? '-' : (string) $val;
                $rows .= self::emailRow($label, $val, $changes[$key] ?? null);
            }
        } else {
            return self::fallbackEmailBody($message, $viewUrl);
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family: sans-serif; font-size: 14px;">';
        $html .= '<h2 style="margin-bottom: 1em;">' . htmlspecialchars($message) . '</h2>';
        $html .= '<p style="margin-bottom: 0.5em;"><strong>Record details</strong></p>';
        $html .= '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-bottom: 1.5em;">';
        $html .= $rows;
        $html .= '</table>';
        $html .= '<p><a href="' . htmlspecialchars($viewUrl) . '">View in PAPeR</a></p>';
        $html .= '</body></html>';
        return $html;
    }

    private static function emailRow(string $label, string $value, ?array $change): string
    {
        if ($change !== null && (isset($change['to']) || isset($change['from']))) {
            $oldVal = (string) ($change['from'] ?? '');
            $display = '<span style="background-color: #ffff00;">' . htmlspecialchars($value ?: '-') . '</span>';
            if ($oldVal !== '') {
                $display .= ' <span style="color: #666; font-size: 0.9em;">(was: ' . htmlspecialchars($oldVal) . ')</span>';
            }
        } else {
            $display = htmlspecialchars($value);
        }
        return '<tr><td style="vertical-align: top; font-weight: bold;">' . htmlspecialchars($label) . '</td><td>' . $display . '</td></tr>';
    }

    private static function fallbackEmailBody(string $message, string $viewUrl): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family: sans-serif;">'
            . '<p>' . htmlspecialchars($message) . '</p>'
            . '<p><a href="' . htmlspecialchars($viewUrl) . '">View in PAPeR</a></p></body></html>';
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
        // Merge stored values with defaults so new prefs (e.g. notify_grievance_updated) default to true.
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
