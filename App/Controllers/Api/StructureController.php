<?php
namespace App\Controllers\Api;

use App\Models\Structure;

class StructureController extends \App\Controllers\StructureController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function storeApi(): void
    {
        $this->validateCsrf();
        $this->requireCapability('add_structure');
        $ownerId = (int) ($_POST['owner_id'] ?? 0) ?: null;
        if (!$ownerId) {
            http_response_code(400);
            $this->json(['success' => false, 'error' => 'owner_id required']);
            return;
        }
        $taggingPaths = $this->handleUpload('tagging_images', 'tagging');
        $structurePaths = $this->handleUpload('structure_images', 'images');
        $id = Structure::create([
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
        $this->validateCsrf();
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
        $this->validateCsrf();
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
}

