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
use App\Models\GrievanceStatusLog;
use App\ListConfig;

class GrievanceController extends Controller
{
    private const LIST_BASE = '/grievance/list';
    private const UPLOAD_BASE = __DIR__ . '/../../public/uploads/grievance/status';
    private const WEB_BASE = '/uploads/grievance/status';
    private const ALLOWED_ATTACH_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    private const LIST_MODULE = 'grievance';

    public function __construct()
    {
        $this->requireAuth();
    }

    public function dashboard(): void
    {
        $this->requireCapability('view_grievance');
        $db = \Core\Database::getInstance();
        $total = (int) $db->query('SELECT COUNT(*) FROM grievances')->fetchColumn();
        $recent = $db->query('SELECT id, grievance_case_number, date_recorded FROM grievances ORDER BY id DESC LIMIT 5')->fetchAll(\PDO::FETCH_OBJ);
        $this->view('grievance/dashboard', [
            'totalGrievances' => $total,
            'recentGrievances' => $recent,
        ]);
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

        $pagination = Grievance::listPaginated($search, $columns, $sort, $order, $page, $perPage, $afterId, $beforeId);

        $this->view('grievance/index', [
            'grievances' => $pagination['items'],
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
        $this->requireCapability('add_grievance');
        $data = $this->gatherGrievanceData(null);
        if (empty(trim($data['grievance_case_number'] ?? ''))) {
            $data['grievance_case_number'] = Grievance::generateCaseNumber();
        }
        $id = Grievance::create($data);
        GrievanceStatusLog::create($id, 'open', null, '', [], \Core\Auth::id());
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
        $this->view('grievance/view', compact('grievance', 'vulnerabilities', 'respondentTypes', 'grmChannels', 'preferredLanguages', 'grievanceTypes', 'grievanceCategories', 'statusLog'));
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
        $this->requireCapability('edit_grievance');
        $grievance = Grievance::find($id);
        if (!$grievance) {
            $this->redirect('/grievance/list');
            return;
        }
        $data = $this->gatherGrievanceData($grievance);
        Grievance::update($id, $data);
        $this->redirect('/grievance/view/' . $id);
    }

    public function delete(int $id): void
    {
        $this->requireCapability('delete_grievance');
        Grievance::delete($id);
        $this->redirect('/grievance/list');
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
        $this->requireCapability('edit_grievance');
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
            if (in_array($pl, [1, 2, 3], true)) $progressLevel = $pl;
        }
        $note = trim($_POST['status_note'] ?? '');
        $attachments = $this->handleStatusUpload();
        Grievance::updateStatus($id, $status, $progressLevel);
        GrievanceStatusLog::create($id, $status, $progressLevel, $note, $attachments, \Core\Auth::id());
        $this->redirect('/grievance/view/' . $id);
    }

    public function serveGrievanceAttachment(): void
    {
        $this->requireAuth();
        if (!\Core\Auth::can('view_grievance')) {
            http_response_code(403);
            exit;
        }
        $file = $_GET['file'] ?? '';
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
            http_response_code(400);
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

    public static function attachmentUrl(string $path): string
    {
        $prefix = defined('BASE_URL') && BASE_URL ? BASE_URL : '';
        if (preg_match('#^/uploads/grievance/status/([a-zA-Z0-9_\-\.]+)$#', $path, $m)) {
            return $prefix . '/serve/grievance?file=' . urlencode($m[1]);
        }
        return $prefix . ($path[0] === '/' ? $path : '/' . $path);
    }
}
