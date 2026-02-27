<?php
namespace App\Controllers;

use Core\Controller;
use App\AuditLog;
use App\Models\Profile;
use App\Models\Structure;
use App\ListConfig;

class ProfileController extends Controller
{
    private const BASE_URL = '/profile';
    private const MODULE = 'profile';
    private const UPLOAD_BASE = __DIR__ . '/../../public/uploads/profile';
    private const WEB_BASE = '/uploads/profile';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];

    public function __construct()
    {
        $this->requireAuth();
    }

    /** Build URL for profile attachment (served via /serve/profile) */
    public static function attachmentUrl(string $storedPath): string
    {
        $prefix = defined('BASE_URL') && BASE_URL ? BASE_URL : '';
        if (preg_match('#^/uploads/profile/([a-zA-Z0-9_-]+)/([a-zA-Z0-9_\-\.]+)$#', $storedPath, $m)) {
            return $prefix . '/serve/profile?subdir=' . urlencode($m[1]) . '&file=' . urlencode($m[2]);
        }
        return $prefix . (($storedPath !== '' && $storedPath[0] === '/') ? $storedPath : '/' . ltrim($storedPath, '/'));
    }

    public function serveProfileFile(): void
    {
        $this->requireAuth();
        if (!\Core\Auth::can('view_profiles')) {
            http_response_code(403);
            exit;
        }
        $subdir = $_GET['subdir'] ?? '';
        $file = $_GET['file'] ?? '';
        $allowedSubdirs = ['residing_in_project_affected', 'structure_owners', 'if_not_structure_owner', 'own_property_elsewhere', 'availed_government_housing'];
        if (!in_array($subdir, $allowedSubdirs, true) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
            http_response_code(400);
            exit;
        }
        $path = self::UPLOAD_BASE . '/' . $subdir . '/' . $file;
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

    private function handleProfileUpload(string $field, string $subdir): array
    {
        $paths = [];
        $dir = self::UPLOAD_BASE . '/' . $subdir;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $files = $_FILES[$field] ?? null;
        if (!$files || empty($files['name'])) return $paths;
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmps = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
        for ($i = 0; $i < count($names); $i++) {
            if ($errors[$i] !== UPLOAD_ERR_OK || !is_uploaded_file($tmps[$i])) continue;
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmps[$i]);
            finfo_close($finfo);
            if (!in_array($mime, self::ALLOWED_TYPES, true)) continue;
            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
                default => 'bin',
            };
            $filename = uniqid('att_', true) . '.' . $ext;
            $dest = $dir . '/' . $filename;
            if (move_uploaded_file($tmps[$i], $dest)) {
                $paths[] = self::WEB_BASE . '/' . $subdir . '/' . $filename;
            }
        }
        return $paths;
    }

    public function index(): void
    {
        $this->requireCapability('view_profiles');
        $columns = ListConfig::resolveFromRequest(self::MODULE);
        $_SESSION['list_columns'][self::MODULE] = $columns;
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? ($columns[0] ?? 'id');
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));
        $afterId = !empty($_GET['after_id']) ? (int) $_GET['after_id'] : null;
        $beforeId = !empty($_GET['before_id']) ? (int) $_GET['before_id'] : null;

        $pagination = Profile::listPaginated($search, $columns, $sort, $order, $page, $perPage, $afterId, $beforeId);

        $this->view('profile/index', [
            'profiles' => $pagination['items'],
            'listModule' => self::MODULE,
            'listBaseUrl' => self::BASE_URL,
            'listSearch' => $search,
            'listSort' => $sort,
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::MODULE),
            'listPagination' => $pagination,
            'listHasCustomColumns' => ListConfig::hasCustomColumns(self::MODULE),
        ]);
    }

    public function create(): void
    {
        $this->requireCapability('add_profiles');
        $this->view('profile/form', ['profile' => null]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireCapability('add_profiles');
        $data = $this->gatherProfileData(null);
        $id = Profile::create($data);
        $projectId = (int) ($data['project_id'] ?? 0);
        if ($projectId > 0) {
            $created = Profile::find($id);
            $msg = $created ? ('New profile: ' . ($created->papsid ?? '')) : 'New profile on linked project';
            \App\NotificationService::notifyNewProfile($id, $projectId, $msg);
        }
        AuditLog::record('profile', $id, 'created');
        $this->redirect('/profile/view/' . $id);
    }

    public function show(int $id): void
    {
        $this->requireCapability('view_profiles');
        $profile = Profile::find($id);
        if (!$profile) {
            $this->redirect('/profile');
            return;
        }
        $structures = \Core\Auth::can('view_structure') ? Structure::byOwner($profile->id) : [];
        $history = \App\AuditLog::for('profile', $profile->id);
        $this->view('profile/view', ['profile' => $profile, 'structures' => $structures, 'history' => $history]);
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_profiles');
        $profile = Profile::find($id);
        if (!$profile) {
            $this->redirect('/profile');
            return;
        }
        $this->view('profile/form', ['profile' => $profile]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('edit_profiles');
        $profile = Profile::find($id);
        if (!$profile) {
            $this->redirect('/profile');
            return;
        }
        $data = $this->gatherProfileData($profile);
        Profile::update($id, $data);
        $changes = [];
        $fields = [
            'control_number',
            'full_name',
            'age',
            'contact_number',
            'project_id',
            'residing_in_project_affected',
            'structure_owners',
            'own_property_elsewhere',
            'availed_government_housing',
            'hh_income',
        ];
        $booleanFields = [
            'residing_in_project_affected',
            'structure_owners',
            'own_property_elsewhere',
            'availed_government_housing',
        ];
        foreach ($fields as $field) {
            $old = $profile->$field ?? null;
            $new = $data[$field] ?? null;
            if (in_array($field, $booleanFields, true)) {
                $oldVal = !empty($old);
                $newVal = !empty($new);
                if ($oldVal === $newVal) {
                    continue;
                }
                $changes[$field] = [
                    'from' => $oldVal ? 'Yes' : 'No',
                    'to'   => $newVal ? 'Yes' : 'No',
                ];
                continue;
            }
            if ((string)($old ?? '') === (string)($new ?? '')) {
                continue;
            }
            $changes[$field] = ['from' => $old, 'to' => $new];
        }
        if (!empty($changes)) {
            AuditLog::record('profile', $id, 'updated', $changes);
            // Notify users on linked projects about profile updates (respecting separate preference)
            $projectId = (int) ($data['project_id'] ?? $profile->project_id ?? 0);
            if ($projectId > 0) {
                $label = $profile->papsid ?? $profile->full_name ?? ('Profile #' . $id);
                $parts = [];
                foreach ($changes as $field => $change) {
                    $from = (string)($change['from'] ?? '');
                    $to   = (string)($change['to'] ?? '');
                    $parts[] = $field . ': ' . $from . ' → ' . $to;
                }
                $details = implode('; ', $parts);
                $message = 'Profile updated: ' . $label;
                if ($details !== '') {
                    $message .= ' (' . $details . ')';
                }
                \App\NotificationService::notifyProfileUpdated($id, $projectId, $message);
            }
        }
        $this->redirect('/profile/view/' . $id);
    }

    private function gatherProfileData(?object $profile): array
    {
        $residingPaths = $this->handleProfileUpload('residing_in_project_affected_attachments', 'residing_in_project_affected');
        $ownersPaths = $this->handleProfileUpload('structure_owners_attachments', 'structure_owners');
        $ifNotPaths = $this->handleProfileUpload('if_not_structure_owner_attachments', 'if_not_structure_owner');
        $ownElsewherePaths = $this->handleProfileUpload('own_property_elsewhere_attachments', 'own_property_elsewhere');
        $availedPaths = $this->handleProfileUpload('availed_government_housing_attachments', 'availed_government_housing');

        if ($profile) {
            $residingPaths = array_merge(Profile::parseAttachments($profile->residing_in_project_affected_attachments ?? '[]'), $residingPaths);
            $ownersPaths = array_merge(Profile::parseAttachments($profile->structure_owners_attachments ?? '[]'), $ownersPaths);
            $ifNotPaths = array_merge(Profile::parseAttachments($profile->if_not_structure_owner_attachments ?? '[]'), $ifNotPaths);
            $ownElsewherePaths = array_merge(Profile::parseAttachments($profile->own_property_elsewhere_attachments ?? '[]'), $ownElsewherePaths);
            $availedPaths = array_merge(Profile::parseAttachments($profile->availed_government_housing_attachments ?? '[]'), $availedPaths);
            $removeResiding = (array) ($_POST['residing_in_project_affected_attachments_remove'] ?? []);
            $removeOwners = (array) ($_POST['structure_owners_attachments_remove'] ?? []);
            $removeIfNot = (array) ($_POST['if_not_structure_owner_attachments_remove'] ?? []);
            $removeOwnElsewhere = (array) ($_POST['own_property_elsewhere_attachments_remove'] ?? []);
            $removeAvailed = (array) ($_POST['availed_government_housing_attachments_remove'] ?? []);
            $residingPaths = array_values(array_diff($residingPaths, $removeResiding));
            $ownersPaths = array_values(array_diff($ownersPaths, $removeOwners));
            $ifNotPaths = array_values(array_diff($ifNotPaths, $removeIfNot));
            $ownElsewherePaths = array_values(array_diff($ownElsewherePaths, $removeOwnElsewhere));
            $availedPaths = array_values(array_diff($availedPaths, $removeAvailed));
        }

        return [
            'papsid' => trim($_POST['papsid'] ?? ($profile->papsid ?? '')),
            'control_number' => trim($_POST['control_number'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'age' => isset($_POST['age']) && $_POST['age'] !== '' ? (int) $_POST['age'] : null,
            'contact_number' => trim($_POST['contact_number'] ?? ''),
            'project_id' => (int) ($_POST['project_id'] ?? 0) ?: null,
            'residing_in_project_affected' => !empty($_POST['residing_in_project_affected']),
            'residing_in_project_affected_note' => trim($_POST['residing_in_project_affected_note'] ?? ''),
            'residing_in_project_affected_attachments' => json_encode($residingPaths),
            'structure_owners' => !empty($_POST['structure_owners']),
            'structure_owners_note' => trim($_POST['structure_owners_note'] ?? ''),
            'structure_owners_attachments' => json_encode($ownersPaths),
            'if_not_structure_owner_what' => trim($_POST['if_not_structure_owner_what'] ?? ''),
            'if_not_structure_owner_attachments' => json_encode($ifNotPaths),
            'own_property_elsewhere' => !empty($_POST['own_property_elsewhere']),
            'own_property_elsewhere_note' => trim($_POST['own_property_elsewhere_note'] ?? ''),
            'own_property_elsewhere_attachments' => json_encode($ownElsewherePaths),
            'availed_government_housing' => !empty($_POST['availed_government_housing']),
            'availed_government_housing_note' => trim($_POST['availed_government_housing_note'] ?? ''),
            'availed_government_housing_attachments' => json_encode($availedPaths),
            'hh_income' => isset($_POST['hh_income']) && $_POST['hh_income'] !== '' ? (float) $_POST['hh_income'] : null,
        ];
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('delete_profiles');
        Profile::delete($id);
        $this->redirect('/profile');
    }
}
