<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\Models\Profile;
use App\Models\Project;
use App\ListConfig;
use App\AuditLog;
use App\NotificationService;
use App\UserProjects;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /** List profiles with pagination and search. */
    public function listApi(): void
    {
        if (!Auth::can('view_profiles')) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden']);
        }

        $search = trim($_GET['q'] ?? '');

        // Resolve searchable columns, falling back to default profile columns.
        $defaultColumns = ListConfig::getDefaultKeys('profile');
        $columnsParam = trim($_GET['columns'] ?? '');
        if ($columnsParam !== '') {
            $requested = array_map('trim', explode(',', $columnsParam));
            $valid = array_values(array_intersect($requested, $defaultColumns));
            $columns = $valid ?: $defaultColumns;
        } else {
            $columns = $defaultColumns;
        }

        $sort = $_GET['sort'] ?? ($columns[0] ?? 'id');
        if (!in_array($sort, $defaultColumns, true)) {
            $sort = $columns[0] ?? 'id';
        }
        $order = strtolower($_GET['order'] ?? 'desc');
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 15)));
        $afterId = isset($_GET['after_id']) && $_GET['after_id'] !== '' ? (int) $_GET['after_id'] : null;
        $beforeId = isset($_GET['before_id']) && $_GET['before_id'] !== '' ? (int) $_GET['before_id'] : null;

        $pagination = Profile::listPaginated($search, $columns, $sort, $order, $page, $perPage, $afterId, $beforeId);

        $this->json($pagination);
    }

    /** Get a single profile by ID. */
    public function getApi(int $id): void
    {
        if (!Auth::can('view_profiles')) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden']);
        }
        $profile = Profile::find($id);
        if (!$profile) {
            http_response_code(404);
            $this->json(['error' => 'Not found']);
        }

        $this->json($this->transformProfile($profile));
    }

    /** Create a new profile (JSON or form-encoded body). Attachments are not handled via this API. */
    public function storeApi(): void
    {
        $this->validateCsrf();
        if (!Auth::can('add_profiles')) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden']);
        }

        $input = $this->readInput();

        $projectId = isset($input['project_id']) && $input['project_id'] !== '' ? (int) $input['project_id'] : null;
        $allowed = UserProjects::allowedProjectIds();
        if ($allowed !== null) {
            if ($projectId !== null && !in_array($projectId, $allowed, true)) {
                http_response_code(403);
                $this->json(['error' => 'Forbidden', 'message' => 'Project not allowed for current user']);
            }
        }

        $fullName = trim((string) ($input['full_name'] ?? ''));
        $controlNumber = trim((string) ($input['control_number'] ?? ''));
        if ($fullName === '' && $controlNumber === '') {
            http_response_code(400);
            $this->json(['error' => 'Bad Request', 'message' => 'full_name or control_number is required']);
        }

        $data = $this->buildProfilePayload($input, null);
        $id = Profile::create($data);

        // Notify and audit, similar to web controller.
        $projectIdForNotify = (int) ($data['project_id'] ?? 0);
        if ($projectIdForNotify > 0) {
            $created = Profile::find($id);
            $msg = $created ? ('New profile: ' . ($created->papsid ?? '')) : 'New profile on linked project';
            NotificationService::notifyNewProfile($id, $projectIdForNotify, $msg);
        }
        AuditLog::record('profile', $id, 'created');

        http_response_code(201);
        $this->json([
            'success' => true,
            'id' => $id,
            'profile' => $this->transformProfile(Profile::find($id)),
        ]);
    }

    /** Update an existing profile. Attachments are not handled via this API. */
    public function updateApi(int $id): void
    {
        $this->validateCsrf();
        if (!Auth::can('edit_profiles')) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden']);
        }
        $current = Profile::find($id);
        if (!$current) {
            http_response_code(404);
            $this->json(['error' => 'Not found']);
        }

        $input = $this->readInput();

        $projectId = isset($input['project_id']) && $input['project_id'] !== '' ? (int) $input['project_id'] : (int) ($current->project_id ?? 0);
        $allowed = UserProjects::allowedProjectIds();
        if ($allowed !== null) {
            if ($projectId > 0 && !in_array($projectId, $allowed, true)) {
                http_response_code(403);
                $this->json(['error' => 'Forbidden', 'message' => 'Project not allowed for current user']);
            }
        }

        $data = $this->buildProfilePayload($input, $current);
        Profile::update($id, $data);

        // Compute basic change set for audit & optional notifications (simpler than full web controller logic).
        $updated = Profile::find($id);
        $changes = [];
        $fields = [
            'control_number',
            'full_name',
            'age',
            'contact_number',
            'project_id',
            'residing_in_project_affected',
            'residing_in_project_affected_note',
            'structure_owners',
            'structure_owners_note',
            'if_not_structure_owner_what',
            'own_property_elsewhere',
            'own_property_elsewhere_note',
            'availed_government_housing',
            'availed_government_housing_note',
            'hh_income',
        ];
        foreach ($fields as $field) {
            $old = $current->$field ?? null;
            $new = $updated->$field ?? null;
            if ((string) ($old ?? '') === (string) ($new ?? '')) {
                continue;
            }
            if ($field === 'project_id') {
                $oldProj = $old ? Project::find((int) $old) : null;
                $newProj = $new ? Project::find((int) $new) : null;
                $changes[$field] = [
                    'from' => $oldProj ? $oldProj->name : ($current->project_name ?? (string) $old),
                    'to' => $newProj ? $newProj->name : (string) $new,
                ];
            } else {
                $changes[$field] = ['from' => $old, 'to' => $new];
            }
        }
        if (!empty($changes)) {
            AuditLog::record('profile', $id, 'updated', $changes);
            $projectIdForNotify = (int) ($updated->project_id ?? 0);
            if ($projectIdForNotify > 0) {
                $label = $updated->papsid ?? $updated->full_name ?? ('Profile #' . $id);
                $message = 'Profile updated: ' . $label;
                NotificationService::notifyProfileUpdated($id, $projectIdForNotify, $message);
            }
        }

        $this->json([
            'success' => true,
            'profile' => $this->transformProfile($updated),
        ]);
    }

    public function deleteApi(int $id): void
    {
        $this->validateCsrf();
        if (!Auth::can('delete_profiles')) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden']);
        }
        $profile = Profile::find($id);
        if (!$profile) {
            http_response_code(404);
            $this->json(['error' => 'Not found']);
        }
        Profile::delete($id);
        AuditLog::record('profile', $id, 'deleted');
        $this->json(['success' => true]);
    }

    /** Read request body as array (JSON or form-encoded). */
    private function readInput(): array
    {
        if (!empty($_POST)) {
            return $_POST;
        }
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }
        return [];
    }

    /** Map incoming fields to Profile::create/update payload (without attachments). */
    private function buildProfilePayload(array $input, ?object $existing): array
    {
        $profile = $existing;

        $age = isset($input['age']) && $input['age'] !== '' ? (int) $input['age'] : ($profile->age ?? null);
        $hhIncome = isset($input['hh_income']) && $input['hh_income'] !== '' ? (float) $input['hh_income'] : ($profile->hh_income ?? null);
        $projectId = isset($input['project_id']) && $input['project_id'] !== '' ? (int) $input['project_id'] : ($profile->project_id ?? null);

        return [
            'papsid' => trim((string) ($input['papsid'] ?? ($profile->papsid ?? ''))),
            'control_number' => trim((string) ($input['control_number'] ?? ($profile->control_number ?? ''))),
            'full_name' => trim((string) ($input['full_name'] ?? ($profile->full_name ?? ''))),
            'age' => $age,
            'contact_number' => trim((string) ($input['contact_number'] ?? ($profile->contact_number ?? ''))),
            'project_id' => $projectId ?: null,
            'residing_in_project_affected' => isset($input['residing_in_project_affected'])
                ? (bool) $input['residing_in_project_affected']
                : (bool) ($profile->residing_in_project_affected ?? false),
            'residing_in_project_affected_note' => trim((string) ($input['residing_in_project_affected_note'] ?? ($profile->residing_in_project_affected_note ?? ''))),
            // Attachments are kept as-is for API (no upload/replace support here)
            'residing_in_project_affected_attachments' => $profile->residing_in_project_affected_attachments ?? '[]',
            'structure_owners' => isset($input['structure_owners'])
                ? (bool) $input['structure_owners']
                : (bool) ($profile->structure_owners ?? false),
            'structure_owners_note' => trim((string) ($input['structure_owners_note'] ?? ($profile->structure_owners_note ?? ''))),
            'structure_owners_attachments' => $profile->structure_owners_attachments ?? '[]',
            'if_not_structure_owner_what' => trim((string) ($input['if_not_structure_owner_what'] ?? ($profile->if_not_structure_owner_what ?? ''))),
            'if_not_structure_owner_attachments' => $profile->if_not_structure_owner_attachments ?? '[]',
            'own_property_elsewhere' => isset($input['own_property_elsewhere'])
                ? (bool) $input['own_property_elsewhere']
                : (bool) ($profile->own_property_elsewhere ?? false),
            'own_property_elsewhere_note' => trim((string) ($input['own_property_elsewhere_note'] ?? ($profile->own_property_elsewhere_note ?? ''))),
            'own_property_elsewhere_attachments' => $profile->own_property_elsewhere_attachments ?? '[]',
            'availed_government_housing' => isset($input['availed_government_housing'])
                ? (bool) $input['availed_government_housing']
                : (bool) ($profile->availed_government_housing ?? false),
            'availed_government_housing_note' => trim((string) ($input['availed_government_housing_note'] ?? ($profile->availed_government_housing_note ?? ''))),
            'availed_government_housing_attachments' => $profile->availed_government_housing_attachments ?? '[]',
            'hh_income' => $hhIncome,
        ];
    }

    /** Normalize profile object into JSON-friendly array. */
    private function transformProfile(?object $p): ?array
    {
        if (!$p) return null;
        return [
            'id' => (int) $p->id,
            'papsid' => $p->papsid ?? null,
            'control_number' => $p->control_number ?? null,
            'full_name' => $p->full_name ?? null,
            'age' => isset($p->age) && $p->age !== null ? (int) $p->age : null,
            'contact_number' => $p->contact_number ?? null,
            'project_id' => isset($p->project_id) ? (int) $p->project_id : null,
            'project_name' => $p->project_name ?? null,
            'residing_in_project_affected' => !empty($p->residing_in_project_affected),
            'residing_in_project_affected_note' => $p->residing_in_project_affected_note ?? null,
            'structure_owners' => !empty($p->structure_owners),
            'structure_owners_note' => $p->structure_owners_note ?? null,
            'if_not_structure_owner_what' => $p->if_not_structure_owner_what ?? null,
            'own_property_elsewhere' => !empty($p->own_property_elsewhere),
            'own_property_elsewhere_note' => $p->own_property_elsewhere_note ?? null,
            'availed_government_housing' => !empty($p->availed_government_housing),
            'availed_government_housing_note' => $p->availed_government_housing_note ?? null,
            'hh_income' => isset($p->hh_income) && $p->hh_income !== null ? (float) $p->hh_income : null,
        ];
    }
}

