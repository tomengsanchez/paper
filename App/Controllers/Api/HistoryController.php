<?php
namespace App\Controllers\Api;

use Core\Controller;

/**
 * Audit history API. PAPeR entity types (profile, structure, grievance) are no longer supported.
 * Extend to support new entity types as needed.
 */
class HistoryController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $entityType = $_GET['entity_type'] ?? '';
        if (in_array($entityType, ['profile', 'structure', 'grievance'], true)) {
            http_response_code(410);
            $this->json(['error' => 'Entity type no longer supported.']);
            return;
        }
        http_response_code(400);
        $this->json(['error' => 'Invalid parameters.']);
    }
}
