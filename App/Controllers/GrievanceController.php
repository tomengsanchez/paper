<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\Grievance;
use App\Models\GrievanceVulnerability;
use App\Models\GrievanceRespondentType;
use App\Models\GrievanceGrmChannel;
use App\Models\GrievancePreferredLanguage;
use App\Models\GrievanceType;
use App\Models\GrievanceCategory;
use App\Models\GrievanceProgressLevel;
use App\Models\GrievanceStatusLog;
use App\Models\GrievanceAttachment;
use App\ListConfig;
use App\DashboardConfig;
use Core\Logger;

class GrievanceController extends Controller
{
    private const LIST_BASE = '/grievance/list';
    private const UPLOAD_BASE = __DIR__ . '/../../public/uploads/grievance/status';
    private const WEB_BASE = '/uploads/grievance/status';
    private const UPLOAD_BASE_ATTACHMENTS = __DIR__ . '/../../public/uploads/grievance/attachments';
    private const WEB_BASE_ATTACHMENTS = '/uploads/grievance/attachments';
    private const ALLOWED_ATTACH_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    private const LIST_MODULE = 'grievance';

    public function __construct()
    {
        $this->requireAuth();
    }

    public function dashboard(): void
    {
        $this->requireCapability('view_grievance');

        $selectedProjectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
        if ($selectedProjectId < 0) {
            $selectedProjectId = 0;
        }
        $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

        $projects = \App\Models\Project::all();
        $dashboardWidgets = DashboardConfig::GRIEVANCE_WIDGETS_DEFAULT;
        $visibleWidgets = DashboardConfig::visibleWidgets(DashboardConfig::MODULE_GRIEVANCE);
        $chartOptions = DashboardConfig::chartOptions(DashboardConfig::MODULE_GRIEVANCE);
        $progressLevels = GrievanceProgressLevel::all();

        // Initial load is now lightweight; heavy aggregates are fetched via AJAX.
        $this->view('grievance/dashboard', [
            'totalGrievances'        => null,
            'recentGrievances'       => [],
            'statusBreakdown'        => [],
            'thisMonth'              => null,
            'lastMonth'              => null,
            'byProject'              => [],
            'monthlyTrend'           => [],
            'byCategory'             => [],
            'byType'                 => [],
            'inProgressLevels'       => [],
            'dashboardWidgets'       => $dashboardWidgets,
            'visibleWidgets'         => $visibleWidgets,
            'chartOptions'           => $chartOptions,
            'progressLevels'         => $progressLevels,
            'needsEscalationByLevel' => [],
            'projects'               => $projects,
            'selectedProjectId'      => $selectedProjectId,
            'dateFrom'               => $dateFrom,
            'dateTo'                 => $dateTo,
        ]);
    }


    public function dashboardSaveConfig(): void
    {
        $this->validateCsrf();
        $this->requireCapability('view_grievance');
        $widgets = $_POST['widgets'] ?? [];
        $widgets = is_array($widgets) ? array_values(array_filter(array_map('trim', $widgets))) : [];
        $allowed = DashboardConfig::GRIEVANCE_WIDGETS_DEFAULT;
        $widgets = array_values(array_intersect($widgets, $allowed));
        if (empty($widgets)) {
            $widgets = $allowed;
        }
        $trendType = trim($_POST['chart_trend_type'] ?? 'bar');
        if (!in_array($trendType, ['bar', 'line'], true)) {
            $trendType = 'bar';
        }
        $current = DashboardConfig::get(DashboardConfig::MODULE_GRIEVANCE);
        DashboardConfig::save(DashboardConfig::MODULE_GRIEVANCE, [
            'widgets'       => $widgets,
            'order'        => $widgets,
            'chart_options' => array_merge($current['chart_options'] ?? [], [
                'trend_type'   => $trendType,
            ]),
        ]);
        $this->redirect('/grievance');
    }

    public function index(): void
    {
        $this->requireCapability('view_grievance');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $_SESSION['list_columns'][self::LIST_MODULE] = $columns;
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? ($columns[0] ?? 'id');
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));
        $afterId = !empty($_GET['after_id']) ? (int) $_GET['after_id'] : null;
        $beforeId = !empty($_GET['before_id']) ? (int) $_GET['before_id'] : null;

        $filterStatus = $_GET['status'] ?? '';
        $filterProjectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
        $filterStageId = isset($_GET['progress_level']) ? (int) $_GET['progress_level'] : 0;
        $filterNeedsEscalation = $_GET['needs_escalation'] ?? '';

        $pagination = Grievance::listPaginated(
            $search,
            $columns,
            $sort,
            $order,
            $page,
            $perPage,
            $afterId,
            $beforeId,
            [
                'status' => $filterStatus,
                'project_id' => $filterProjectId,
                'progress_level' => $filterStageId,
                'needs_escalation' => $filterNeedsEscalation,
            ]
        );

        $progressLevels = GrievanceProgressLevel::all();
        $projects = \App\Models\Project::all();
        $progressLevelMap = $this->buildProgressLevelMap($progressLevels);

        // Determine when each grievance entered its current in-progress level.
        $levelStartedAt = [];
        $items = $pagination['items'] ?? [];
        $ids = array_map(fn($it) => (int) ($it->id ?? 0), $items);
        $ids = array_values(array_filter($ids));
        if (!empty($ids)) {
            $db = \Core\Database::getInstance();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "
                SELECT grievance_id, progress_level, MAX(created_at) AS level_started_at
                FROM grievance_status_log
                WHERE grievance_id IN ($placeholders)
                  AND status = 'in_progress'
                  AND progress_level IS NOT NULL
                GROUP BY grievance_id, progress_level
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            foreach ($rows as $row) {
                $gId = (int) ($row->grievance_id ?? 0);
                $plId = (int) ($row->progress_level ?? 0);
                if ($gId <= 0 || $plId <= 0 || empty($row->level_started_at)) {
                    continue;
                }
                $levelStartedAt[$gId . '_' . $plId] = $row->level_started_at;
            }
        }

        foreach ($pagination['items'] as $item) {
            if (!is_object($item)) {
                continue;
            }
            $gId = (int) ($item->id ?? 0);
            $plId = (int) ($item->progress_level ?? 0);
            $startedAt = $gId && $plId ? ($levelStartedAt[$gId . '_' . $plId] ?? null) : null;
            $item->level_started_at = $startedAt;
            $item->escalation_message = $this->computeEscalationMessageForGrievance($item, $progressLevelMap, $startedAt);
        }

        $this->view('grievance/index', [
            'grievances' => $pagination['items'],
            'progressLevels' => $progressLevels,
            'projects' => $projects,
            'filterStatus' => $filterStatus,
            'filterProjectId' => $filterProjectId,
            'filterStageId' => $filterStageId,
            'filterNeedsEscalation' => $filterNeedsEscalation,
            'listModule' => self::LIST_MODULE,
            'listBaseUrl' => self::LIST_BASE,
            'listSearch' => $search,
            'listSort' => $sort,
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::LIST_MODULE),
            'listPagination' => $pagination,
            'listHasCustomColumns' => ListConfig::hasCustomColumns(self::LIST_MODULE),
        ]);
    }

    public function create(): void
    {
        $this->requireCapability('add_grievance');
        $this->view('grievance/form', [
            'grievance' => null,
            'attachments' => [],
            'vulnerabilities' => GrievanceVulnerability::all(),
            'respondentTypes' => GrievanceRespondentType::all(),
            'grmChannels' => GrievanceGrmChannel::all(),
            'preferredLanguages' => GrievancePreferredLanguage::all(),
            'grievanceTypes' => GrievanceType::all(),
            'grievanceCategories' => GrievanceCategory::all(),
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireCapability('add_grievance');
        $err = $this->validateGrmModeFields();
        if ($err !== null) {
            $_SESSION['grievance_validation_error'] = $err;
            $this->redirect('/grievance/create');
            return;
        }
        $data = $this->gatherGrievanceData(null);
        if (empty(trim($data['grievance_case_number'] ?? ''))) {
            $data['grievance_case_number'] = Grievance::generateCaseNumber();
        }
        $id = Grievance::create($data);
        GrievanceStatusLog::create($id, 'open', null, '', [], \Core\Auth::id());
        $projectId = (int) ($data['project_id'] ?? 0);
        $caseNum = trim($data['grievance_case_number'] ?? '');
        $msg = $caseNum ? ('New grievance: ' . $caseNum) : ('New grievance #' . $id);
        \App\NotificationService::notifyNewGrievance($id, $projectId ?: null, $msg);
        $this->processAttachmentCards($id, false);
        $this->redirect('/grievance/view/' . $id);
    }

    public function show(int $id): void
    {
        $this->requireCapability('view_grievance');
        $grievance = Grievance::find($id);
        if (!$grievance) {
            $this->redirect('/grievance/list');
            return;
        }
        $vulnerabilities = GrievanceVulnerability::all();
        $respondentTypes = GrievanceRespondentType::all();
        $grmChannels = GrievanceGrmChannel::all();
        $preferredLanguages = GrievancePreferredLanguage::all();
        $grievanceTypes = GrievanceType::all();
        $grievanceCategories = GrievanceCategory::all();
        $statusLog = GrievanceStatusLog::byGrievance($id);
        $progressLevels = GrievanceProgressLevel::all();

        $progressLevelMap = $this->buildProgressLevelMap($progressLevels);

        // Determine when the grievance entered its current in-progress level.
        $levelStartedAt = null;
        if (($grievance->status ?? 'open') === 'in_progress' && !empty($grievance->progress_level)) {
            $db = \Core\Database::getInstance();
            $stmt = $db->prepare("
                SELECT MAX(created_at) AS level_started_at
                FROM grievance_status_log
                WHERE grievance_id = ?
                  AND status = 'in_progress'
                  AND progress_level = ?
            ");
            $stmt->execute([$id, (int) $grievance->progress_level]);
            $levelStartedAt = $stmt->fetchColumn() ?: null;
        }

        $grievance->escalation_message = $this->computeEscalationMessageForGrievance($grievance, $progressLevelMap, $levelStartedAt);

        $attachments = GrievanceAttachment::byGrievance($id);
        $this->view('grievance/view', compact('grievance', 'attachments', 'vulnerabilities', 'respondentTypes', 'grmChannels', 'preferredLanguages', 'grievanceTypes', 'grievanceCategories', 'statusLog', 'progressLevels'));
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_grievance');
        $grievance = Grievance::find($id);
        if (!$grievance) {
            $this->redirect('/grievance/list');
            return;
        }
        $this->view('grievance/form', [
            'grievance' => $grievance,
            'attachments' => GrievanceAttachment::byGrievance($id),
            'vulnerabilities' => GrievanceVulnerability::all(),
            'respondentTypes' => GrievanceRespondentType::all(),
            'grmChannels' => GrievanceGrmChannel::all(),
            'preferredLanguages' => GrievancePreferredLanguage::all(),
            'grievanceTypes' => GrievanceType::all(),
            'grievanceCategories' => GrievanceCategory::all(),
        ]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('edit_grievance');
        $grievance = Grievance::find($id);
        if (!$grievance) {
            $this->redirect('/grievance/list');
            return;
        }
        $err = $this->validateGrmModeFields();
        if ($err !== null) {
            $_SESSION['grievance_validation_error'] = $err;
            $this->redirect('/grievance/edit/' . $id);
            return;
        }
        $data = $this->gatherGrievanceData($grievance);
        Grievance::update($id, $data);
        $this->processAttachmentCards($id, true);
        $this->redirect('/grievance/view/' . $id);
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('delete_grievance');
        Grievance::delete($id);
        $this->redirect('/grievance/list');
    }

    private function validateGrmModeFields(): ?string
    {
        $grmChannelId = $_POST['grm_channel_id'] ?? '';
        $langIds = $_POST['preferred_language_ids'] ?? [];
        $typeIds = $_POST['grievance_type_ids'] ?? [];
        $catIds = $_POST['grievance_category_ids'] ?? [];
        $grmChannels = GrievanceGrmChannel::all();
        $preferredLanguages = GrievancePreferredLanguage::all();
        $grievanceTypes = GrievanceType::all();
        $grievanceCategories = GrievanceCategory::all();
        if (!empty($grmChannels) && (!isset($grmChannelId) || $grmChannelId === '')) {
            return 'Please select a GRM Channel.';
        }
        if (!is_array($langIds)) $langIds = [];
        if (!empty($preferredLanguages) && array_filter(array_map('intval', $langIds)) === []) {
            return 'Please select at least one Preferred Language of Communication.';
        }
        if (!is_array($typeIds)) $typeIds = [];
        if (!empty($grievanceTypes) && array_filter(array_map('intval', $typeIds)) === []) {
            return 'Please select at least one Type of Grievance.';
        }
        if (!is_array($catIds)) $catIds = [];
        if (!empty($grievanceCategories) && array_filter(array_map('intval', $catIds)) === []) {
            return 'Please select at least one Category of Grievance.';
        }
        return null;
    }

    private function gatherGrievanceData(?object $grievance): array
    {
        $vulnIds = $_POST['vulnerability_ids'] ?? [];
        $respIds = $_POST['respondent_type_ids'] ?? [];
        $grmChannelId = $_POST['grm_channel_id'] ?? '';
        $langIds = $_POST['preferred_language_ids'] ?? [];
        $typeIds = $_POST['grievance_type_ids'] ?? [];
        $catIds = $_POST['grievance_category_ids'] ?? [];
        if (!is_array($vulnIds)) $vulnIds = [];
        if (!is_array($respIds)) $respIds = [];
        if (!is_array($langIds)) $langIds = [];
        if (!is_array($typeIds)) $typeIds = [];
        if (!is_array($catIds)) $catIds = [];
        return [
            'date_recorded' => $_POST['date_recorded'] ?? null,
            'grievance_case_number' => trim($_POST['grievance_case_number'] ?? ''),
            'project_id' => (int) ($_POST['project_id'] ?? 0) ?: null,
            'is_paps' => !empty($_POST['is_paps']),
            'profile_id' => (int) ($_POST['profile_id'] ?? 0) ?: null,
            'respondent_full_name' => trim($_POST['respondent_full_name'] ?? ''),
            'gender' => trim($_POST['gender'] ?? ''),
            'gender_specify' => trim($_POST['gender_specify'] ?? ''),
            'valid_id_philippines' => trim($_POST['valid_id_philippines'] ?? ''),
            'id_number' => trim($_POST['id_number'] ?? ''),
            'vulnerability_ids' => array_map('intval', array_filter($vulnIds)),
            'respondent_type_ids' => array_map('intval', array_filter($respIds)),
            'respondent_type_other_specify' => trim($_POST['respondent_type_other_specify'] ?? ''),
            'home_business_address' => trim($_POST['home_business_address'] ?? ''),
            'mobile_number' => trim($_POST['mobile_number'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'contact_others_specify' => trim($_POST['contact_others_specify'] ?? ''),
            'grm_channel_ids' => (isset($grmChannelId) && $grmChannelId !== '') ? [(int)$grmChannelId] : [],
            'preferred_language_ids' => array_map('intval', array_filter($langIds)),
            'preferred_language_other_specify' => trim($_POST['preferred_language_other_specify'] ?? ''),
            'grievance_type_ids' => array_map('intval', array_filter($typeIds)),
            'grievance_category_ids' => array_map('intval', array_filter($catIds)),
            'location_same_as_address' => !empty($_POST['location_same_as_address']),
            'location_specify' => trim($_POST['location_specify'] ?? ''),
            'incident_one_time' => !empty($_POST['incident_one_time']),
            'incident_date' => $_POST['incident_date'] ?? null,
            'incident_multiple' => !empty($_POST['incident_multiple']),
            'incident_dates' => trim($_POST['incident_dates'] ?? ''),
            'incident_ongoing' => !empty($_POST['incident_ongoing']),
            'description_complaint' => trim($_POST['description_complaint'] ?? ''),
            'desired_resolution' => trim($_POST['desired_resolution'] ?? ''),
            'status' => $grievance ? ($grievance->status ?? 'open') : 'open',
            'progress_level' => $grievance ? ($grievance->progress_level ?? null) : null,
        ];
    }

    public function statusUpdate(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('change_grievance_status');
        $grievance = Grievance::find($id);
        if (!$grievance) {
            $this->redirect('/grievance/list');
            return;
        }
        $status = $_POST['status'] ?? 'open';
        if (!in_array($status, ['open', 'in_progress', 'closed'], true)) $status = 'open';
        $progressLevel = null;
        if ($status === 'in_progress') {
            $pl = (int) ($_POST['progress_level'] ?? 0);
            if ($pl > 0 && GrievanceProgressLevel::find($pl)) $progressLevel = $pl;
        }
        $note = trim($_POST['status_note'] ?? '');
        $attachments = $this->handleStatusUpload();
        Grievance::updateStatus($id, $status, $progressLevel);
        GrievanceStatusLog::create($id, $status, $progressLevel, $note, $attachments, \Core\Auth::id());
        $projectId = (int) ($grievance->project_id ?? 0);
        $caseNum = $grievance->grievance_case_number ?? ('#' . $id);
        $msg = 'Grievance ' . $caseNum . ' status changed to ' . $status;
        \App\NotificationService::notifyGrievanceStatusChange($id, $projectId ?: null, $msg);
        $this->redirect('/grievance/view/' . $id);
    }

    public function serveGrievanceAttachment(): void
    {
        $this->requireAuth();
        if (!\Core\Auth::can('view_grievance')) {
            http_response_code(403);
            exit;
        }
        $grievanceId = isset($_GET['grievance_id']) ? (int) $_GET['grievance_id'] : 0;
        $file = $_GET['file'] ?? '';
        if (!$grievanceId || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
            http_response_code(400);
            exit;
        }
        $grievance = Grievance::find($grievanceId);
        if (!$grievance) {
            http_response_code(404);
            exit;
        }
        $logs = GrievanceStatusLog::byGrievance($grievanceId);
        $allowedFiles = [];
        foreach ($logs as $log) {
            $atts = GrievanceStatusLog::parseAttachments($log->attachments ?? '[]');
            foreach ($atts as $path) {
                $base = basename($path);
                if ($base !== '') $allowedFiles[$base] = true;
            }
        }
        if (!isset($allowedFiles[$file])) {
            http_response_code(403);
            exit;
        }
        $path = self::UPLOAD_BASE . '/' . $file;
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /** Serve a grievance attachment card file by attachment id (IDOR: user must have view_grievance). */
    public function serveGrievanceCardAttachment(): void
    {
        $this->requireAuth();
        if (!\Core\Auth::can('view_grievance')) {
            http_response_code(403);
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            exit;
        }
        $att = GrievanceAttachment::find($id);
        if (!$att || (int) $att->grievance_id <= 0) {
            http_response_code(404);
            exit;
        }
        $grievance = Grievance::find((int) $att->grievance_id);
        if (!$grievance) {
            http_response_code(404);
            exit;
        }
        $file = basename($att->file_path ?? '');
        if ($file === '' || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
            http_response_code(400);
            exit;
        }
        $path = self::UPLOAD_BASE_ATTACHMENTS . '/' . $file;
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    private function handleStatusUpload(): array
    {
        $paths = [];
        if (!is_dir(self::UPLOAD_BASE)) mkdir(self::UPLOAD_BASE, 0755, true);
        $files = $_FILES['status_attachments'] ?? null;
        if (!$files || empty($files['name'])) return $paths;
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmps = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
        for ($i = 0; $i < count($names); $i++) {
            if ($errors[$i] !== UPLOAD_ERR_OK || !is_uploaded_file($tmps[$i])) continue;
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmps[$i]);
            finfo_close($finfo);
            if (!in_array($mime, self::ALLOWED_ATTACH_TYPES, true)) continue;
            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
                default => 'bin',
            };
            $filename = uniqid('att_', true) . '.' . $ext;
            if (move_uploaded_file($tmps[$i], self::UPLOAD_BASE . '/' . $filename)) {
                $paths[] = self::WEB_BASE . '/' . $filename;
            }
        }
        return $paths;
    }

    /**
     * Process attachment cards from POST: create (store), update/delete (update).
     */
    private function processAttachmentCards(int $grievanceId, bool $isUpdate): void
    {
        $ids = $_POST['attachment_id'] ?? [];
        $titles = $_POST['attachment_title'] ?? [];
        $descriptions = $_POST['attachment_description'] ?? [];
        $files = $_FILES['attachment_file'] ?? null;
        // PHP may send a single value as string when there's only one input; normalize to array
        if (!is_array($ids)) $ids = $ids === '' || $ids === null ? [] : [$ids];
        if (!is_array($titles)) $titles = $titles === '' || $titles === null ? [] : [$titles];
        if (!is_array($descriptions)) $descriptions = $descriptions === '' || $descriptions === null ? [] : [$descriptions];
        $fileNames = $files && isset($files['name']) ? $files['name'] : [];
        $fileTmps = $files && isset($files['tmp_name']) ? $files['tmp_name'] : [];
        $fileErrors = $files && isset($files['error']) ? $files['error'] : [];
        if (!is_array($fileNames)) $fileNames = $fileNames === '' ? [] : [$fileNames];
        if (!is_array($fileTmps)) $fileTmps = $fileTmps === '' ? [] : [$fileTmps];
        if (!is_array($fileErrors)) $fileErrors = $fileErrors === '' || $fileErrors === null ? [] : [$fileErrors];

        $hasAttachmentData = count($ids) > 0 || count($titles) > 0 || count($descriptions) > 0;
        $attachmentSectionSent = isset($_POST['attachment_section']);
        $numCards = max(count($ids), count($titles), count($descriptions));

        Logger::log('attachment_debug.log', 'processAttachmentCards', [
            'grievanceId' => $grievanceId,
            'isUpdate' => $isUpdate,
            'numCards' => $numCards,
            'hasAttachmentData' => $hasAttachmentData,
            'attachmentSectionSent' => $attachmentSectionSent,
            'post_attachment_id' => array_key_exists('attachment_id', $_POST),
            'files_set' => $files !== null,
        ]);

        if ($isUpdate && !$hasAttachmentData && !$attachmentSectionSent) {
            return;
        }

        if (!is_dir(self::UPLOAD_BASE_ATTACHMENTS)) {
            mkdir(self::UPLOAD_BASE_ATTACHMENTS, 0755, true);
        }

        $submittedIds = [];
        for ($i = 0; $i < $numCards; $i++) {
            $id = isset($ids[$i]) ? trim((string) $ids[$i]) : '';
            $title = isset($titles[$i]) ? trim((string) $titles[$i]) : '';
            $description = isset($descriptions[$i]) ? trim((string) $descriptions[$i]) : '';
            $hasFile = isset($fileErrors[$i]) && (int)$fileErrors[$i] === UPLOAD_ERR_OK && !empty($fileTmps[$i]) && is_uploaded_file($fileTmps[$i]);

            Logger::log('attachment_debug.log', "card[$i]", ['id' => $id, 'title' => substr($title, 0, 40), 'hasFile' => $hasFile, 'fileError' => $fileErrors[$i] ?? null]);

            try {
                if ($isUpdate && $id !== '') {
                    $attId = (int) $id;
                    if ($attId > 0) {
                        $existingAtt = GrievanceAttachment::find($attId);
                        if ($existingAtt && (int)$existingAtt->grievance_id === $grievanceId) {
                            $submittedIds[] = $attId;
                            $newPath = $hasFile ? $this->uploadOneAttachmentCard($i) : null;
                            GrievanceAttachment::update($attId, $title !== '' ? $title : 'Untitled', $description, $newPath);
                        }
                    }
                } else {
                    if ($title !== '' && $hasFile) {
                        $path = $this->uploadOneAttachmentCard($i);
                        if ($path !== null) {
                            $newId = GrievanceAttachment::create($grievanceId, $title, $description, $path, $i);
                            $submittedIds[] = $newId; // so cleanup does not delete the row we just created
                        } else {
                            Logger::log('attachment_debug.log', "card[$i] upload returned null", []);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Logger::log('attachment_debug.log', 'attachment_error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
                $_SESSION['grievance_attachment_error'] = 'Attachments could not be saved. See logs/attachment_debug.log.';
            }
        }

        try {
            if ($isUpdate && ($hasAttachmentData || $attachmentSectionSent)) {
                $existing = GrievanceAttachment::byGrievance($grievanceId);
                foreach ($existing as $att) {
                    if (!in_array((int) $att->id, $submittedIds, true)) {
                        $fullPath = self::UPLOAD_BASE_ATTACHMENTS . '/' . basename($att->file_path ?? '');
                        if (is_file($fullPath)) @unlink($fullPath);
                        GrievanceAttachment::delete((int) $att->id);
                    }
                }
            }
        } catch (\Throwable $e) {
            Logger::log('attachment_debug.log', 'attachment_delete_error', ['message' => $e->getMessage()]);
            $_SESSION['grievance_attachment_error'] = 'Attachments could not be saved. See logs/attachment_debug.log.';
        }
    }

    private function uploadOneAttachmentCard(int $index): ?string
    {
        $files = $_FILES['attachment_file'] ?? null;
        if (!$files) return null;
        $tmps = $files['tmp_name'] ?? [];
        $errors = $files['error'] ?? [];
        if (!is_array($tmps)) $tmps = [$tmps];
        if (!is_array($errors)) $errors = [$errors];
        if (!isset($tmps[$index], $errors[$index]) || (int)$errors[$index] !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = $tmps[$index];
        if (empty($tmp) || !is_uploaded_file($tmp)) return null;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        if (!in_array($mime, self::ALLOWED_ATTACH_TYPES, true)) return null;
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
        $filename = uniqid('card_', true) . '.' . $ext;
        $dest = self::UPLOAD_BASE_ATTACHMENTS . '/' . $filename;
        if (move_uploaded_file($tmp, $dest)) {
            return self::WEB_BASE_ATTACHMENTS . '/' . $filename;
        }
        return null;
    }

    public static function attachmentUrl(string $path, ?int $grievanceId = null): string
    {
        $prefix = defined('BASE_URL') && BASE_URL ? BASE_URL : '';
        if (preg_match('#^/uploads/grievance/status/([a-zA-Z0-9_\-\.]+)$#', $path, $m)) {
            $file = $m[1];
            $q = 'file=' . urlencode($file);
            if ($grievanceId !== null && $grievanceId > 0) {
                $q .= '&grievance_id=' . (int) $grievanceId;
            }
            return $prefix . '/serve/grievance?' . $q;
        }
        return $prefix . ($path[0] === '/' ? $path : '/' . $path);
    }

    /**
     * Build helper maps for progress levels: by id, next level by id, and last level id.
     *
     * @param array<int,object> $progressLevels
     * @return array{byId: array<int,object>, nextById: array<int,?object>, lastId: int|null}
     */
    private function buildProgressLevelMap(array $progressLevels): array
    {
        $ordered = $progressLevels;
        usort($ordered, function ($a, $b) {
            $sa = (int) ($a->sort_order ?? 0);
            $sb = (int) ($b->sort_order ?? 0);
            if ($sa === $sb) {
                return (int) ($a->id ?? 0) <=> (int) ($b->id ?? 0);
            }
            return $sa <=> $sb;
        });

        $byId = [];
        $nextById = [];
        $lastId = null;
        $count = count($ordered);

        for ($i = 0; $i < $count; $i++) {
            $pl = $ordered[$i];
            $id = (int) ($pl->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $byId[$id] = $pl;
            $nextById[$id] = $ordered[$i + 1] ?? null;
        }

        if ($count > 0) {
            $last = $ordered[$count - 1];
            $lastId = (int) ($last->id ?? 0) ?: null;
        }

        return [
            'byId' => $byId,
            'nextById' => $nextById,
            'lastId' => $lastId,
        ];
    }

    /**
     * Determine if a grievance should be escalated or closed based on days_to_address.
     *
     * @param array{byId: array<int,object>, nextById: array<int,?object>, lastId: int|null} $progressLevelMap
     */
    private function computeEscalationMessageForGrievance(object $g, array $progressLevelMap, ?string $levelStartedAt): ?string
    {
        $status = $g->status ?? 'open';
        if ($status !== 'in_progress') {
            return null;
        }

        $levelId = (int) ($g->progress_level ?? 0);
        if ($levelId <= 0 || empty($progressLevelMap['byId'][$levelId])) {
            return null;
        }

        $level = $progressLevelMap['byId'][$levelId];
        $daysTarget = isset($level->days_to_address) ? (int) $level->days_to_address : 0;
        if ($daysTarget <= 0) {
            return null;
        }

        if ($levelStartedAt === null || $levelStartedAt === '') {
            return null;
        }

        try {
            $started = new \DateTimeImmutable($levelStartedAt);
        } catch (\Exception $e) {
            return null;
        }

        $now = new \DateTimeImmutable('now');
        $daysOpen = (int) $started->diff($now)->format('%a');
        if ($daysOpen <= $daysTarget) {
            return null;
        }

        $lastId = $progressLevelMap['lastId'] ?? null;
        if ($lastId !== null && $levelId === $lastId) {
            return 'Should be closed';
        }

        $next = $progressLevelMap['nextById'][$levelId] ?? null;
        $nextName = $next && !empty($next->name) ? $next->name : 'next level';
        return 'Should be escalated to ' . $nextName;
    }
}
