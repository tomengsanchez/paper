<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\Structure;
use App\ListConfig;

class StructureController extends Controller
{
    private const UPLOAD_BASE = __DIR__ . '/../../public/uploads/structure';
    private const WEB_BASE = '/uploads/structure';

    /** Build URL for structure image (uses serve route so it works regardless of document root) */
    public static function imageUrl(string $storedPath): string
    {
        $prefix = defined('BASE_URL') && BASE_URL ? BASE_URL : '';
        if (preg_match('#^/uploads/structure/(tagging|images)/([a-zA-Z0-9_\-\.]+)$#', $storedPath, $m)) {
            return $prefix . '/serve/structure?subdir=' . urlencode($m[1]) . '&file=' . urlencode($m[2]);
        }
        return $prefix . (($storedPath !== '' && $storedPath[0] === '/') ? $storedPath : '/' . ltrim($storedPath, '/'));
    }
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct()
    {
        $this->requireAuth();
    }

    private const LIST_BASE = '/structure';
    private const LIST_MODULE = 'structure';

    public function index(): void
    {
        $this->requireCapability('view_structure');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $_SESSION['list_columns'][self::LIST_MODULE] = $columns;
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? ($columns[0] ?? 'id');
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'asc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));

        $pagination = Structure::listPaginated($search, $columns, $sort, $order, $page, $perPage);

        $this->view('structure/index', [
            'structures' => $pagination['items'],
            'listModule' => self::LIST_MODULE,
            'listBaseUrl' => self::LIST_BASE,
            'listSearch' => $search,
            'listSort' => $sort,
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::LIST_MODULE),
            'listPagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        $this->requireCapability('add_structure');
        $strid = Structure::generateSTRID();
        $this->view('structure/form', ['structure' => null, 'strid' => $strid]);
    }

    public function store(): void
    {
        $this->requireCapability('add_structure');
        $taggingPaths = $this->handleUpload('tagging_images', 'tagging');
        $structurePaths = $this->handleUpload('structure_images', 'images');
        $id = Structure::create([
            'strid' => trim($_POST['strid'] ?? Structure::generateSTRID()),
            'owner_id' => (int) ($_POST['owner_id'] ?? 0) ?: null,
            'structure_tag' => trim($_POST['structure_tag'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'tagging_images' => json_encode($taggingPaths),
            'structure_images' => json_encode($structurePaths),
            'other_details' => trim($_POST['other_details'] ?? ''),
        ]);
        $this->redirect('/structure/view/' . $id);
    }

    public function show(int $id): void
    {
        $this->requireCapability('view_structure');
        $structure = Structure::find($id);
        if (!$structure) {
            $this->redirect('/structure');
            return;
        }
        $this->view('structure/view', ['structure' => $structure]);
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_structure');
        $structure = Structure::find($id);
        if (!$structure) {
            $this->redirect('/structure');
            return;
        }
        $this->view('structure/form', ['structure' => $structure]);
    }

    public function update(int $id): void
    {
        $this->requireCapability('edit_structure');
        $structure = Structure::find($id);
        if (!$structure) {
            $this->redirect('/structure');
            return;
        }
        $taggingPaths = Structure::parseImages($structure->tagging_images ?? '[]');
        $structurePaths = Structure::parseImages($structure->structure_images ?? '[]');
        $removeTagging = (array) ($_POST['tagging_images_remove'] ?? []);
        $removeStructure = (array) ($_POST['structure_images_remove'] ?? []);
        $taggingPaths = array_values(array_diff($taggingPaths, $removeTagging));
        $structurePaths = array_values(array_diff($structurePaths, $removeStructure));
        $taggingPaths = array_merge($taggingPaths, $this->handleUpload('tagging_images', 'tagging'));
        $structurePaths = array_merge($structurePaths, $this->handleUpload('structure_images', 'images'));
        Structure::update($id, [
            'strid' => trim($_POST['strid'] ?? $structure->strid),
            'owner_id' => (int) ($_POST['owner_id'] ?? 0) ?: null,
            'structure_tag' => trim($_POST['structure_tag'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'tagging_images' => json_encode($taggingPaths),
            'structure_images' => json_encode($structurePaths),
            'other_details' => trim($_POST['other_details'] ?? ''),
        ]);
        $this->redirect('/structure/view/' . $id);
    }

    public function delete(int $id): void
    {
        $this->requireCapability('delete_structure');
        Structure::delete($id);
        $this->redirect('/structure');
    }

    public function storeApi(): void
    {
        $this->requireCapability('add_structure');
        $ownerId = (int) ($_POST['owner_id'] ?? 0) ?: null;
        if (!$ownerId) {
            http_response_code(400);
            $this->json(['success' => false, 'error' => 'owner_id required']);
            return;
        }
        $taggingPaths = $this->handleUpload('tagging_images', 'tagging');
        $structurePaths = $this->handleUpload('structure_images', 'images');
        $strid = trim($_POST['strid'] ?? '');
        $id = Structure::create([
            'strid' => $strid ?: Structure::generateSTRID(),
            'owner_id' => $ownerId,
            'structure_tag' => trim($_POST['structure_tag'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'tagging_images' => json_encode($taggingPaths),
            'structure_images' => json_encode($structurePaths),
            'other_details' => trim($_POST['other_details'] ?? ''),
        ]);
        $this->json(['success' => true, 'id' => $id]);
    }

    public function updateApi(int $id): void
    {
        $this->requireCapability('edit_structure');
        $structure = Structure::find($id);
        if (!$structure) {
            http_response_code(404);
            $this->json(['success' => false, 'error' => 'Not found']);
            return;
        }
        $taggingPaths = Structure::parseImages($structure->tagging_images ?? '[]');
        $structurePaths = Structure::parseImages($structure->structure_images ?? '[]');
        $removeTagging = (array) ($_POST['tagging_images_remove'] ?? []);
        $removeStructure = (array) ($_POST['structure_images_remove'] ?? []);
        $taggingPaths = array_values(array_diff($taggingPaths, $removeTagging));
        $structurePaths = array_values(array_diff($structurePaths, $removeStructure));
        $taggingPaths = array_merge($taggingPaths, $this->handleUpload('tagging_images', 'tagging'));
        $structurePaths = array_merge($structurePaths, $this->handleUpload('structure_images', 'images'));
        Structure::update($id, [
            'strid' => trim($_POST['strid'] ?? $structure->strid),
            'owner_id' => (int) ($_POST['owner_id'] ?? $structure->owner_id) ?: $structure->owner_id,
            'structure_tag' => trim($_POST['structure_tag'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'tagging_images' => json_encode($taggingPaths),
            'structure_images' => json_encode($structurePaths),
            'other_details' => trim($_POST['other_details'] ?? ''),
        ]);
        $this->json(['success' => true]);
    }

    public function deleteApi(int $id): void
    {
        $this->requireCapability('delete_structure');
        Structure::delete($id);
        $this->json(['success' => true]);
    }

    public function nextStridApi(): void
    {
        $this->requireCapability('add_structure');
        $this->json(['strid' => Structure::generateSTRID()]);
    }

    public function getApi(int $id): void
    {
        $this->requireCapability('view_structure');
        $structure = Structure::find($id);
        if (!$structure) {
            http_response_code(404);
            $this->json(['error' => 'Not found']);
            return;
        }
        $this->json([
            'id' => $structure->id,
            'strid' => $structure->strid,
            'owner_id' => $structure->owner_id,
            'owner_name' => $structure->owner_name ?? null,
            'structure_tag' => $structure->structure_tag,
            'description' => $structure->description,
            'other_details' => $structure->other_details,
            'tagging_images' => $structure->tagging_images,
            'structure_images' => $structure->structure_images,
        ]);
    }

    public function serveImage(): void
    {
        $this->requireAuth();
        if (!\Core\Auth::can('view_structure')) {
            http_response_code(403);
            exit;
        }
        $subdir = $_GET['subdir'] ?? '';
        $file = $_GET['file'] ?? '';
        if (!in_array($subdir, ['tagging', 'images'], true) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
            http_response_code(400);
            exit;
        }
        $path = self::UPLOAD_BASE . '/' . $subdir . '/' . $file;
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    private function handleUpload(string $field, string $subdir): array
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
                default => 'jpg',
            };
            $filename = uniqid('img_', true) . '.' . $ext;
            $dest = $dir . '/' . $filename;
            if (move_uploaded_file($tmps[$i], $dest)) {
                $paths[] = self::WEB_BASE . '/' . $subdir . '/' . $filename;
            }
        }
        return $paths;
    }
}
