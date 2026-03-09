<?php
/**
 * Seed audit_log for existing users, profiles, and structures.
 * Use when the DB has data (e.g. from dump) but audit trail is empty for these modules.
 *
 * Run from project root: php database/seed_audit_log.php
 * Safe to re-run: adds entries; does not truncate.
 *
 * The pattern and output are kept consistent with the sample seeding
 * at the end of database/seed_profiles_structures.php.
 */

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;

$db = Database::getInstance();

$firstUserId = (int) ($db->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn() ?: 1);
$profileIds = $db->query('SELECT id FROM profiles ORDER BY id DESC LIMIT 150')->fetchAll(\PDO::FETCH_COLUMN);
$structureIds = $db->query('SELECT id FROM structures ORDER BY id DESC LIMIT 80')->fetchAll(\PDO::FETCH_COLUMN);

$auditStmt = $db->prepare('INSERT INTO audit_log (entity_type, entity_id, action, changes, created_by) VALUES (?, ?, ?, ?, ?)');
$auditCount = 0;

echo "\nSeeding audit log (sample) for profiles and structures...\n";

// User login/logout and viewed (Users module)
$auditStmt->execute(['user', $firstUserId, 'login', null, $firstUserId]);
$auditCount++;
$auditStmt->execute(['user', $firstUserId, 'viewed', null, $firstUserId]);
$auditCount++;
if (count($profileIds) > 0) {
    $auditStmt->execute(['user', (int) $profileIds[array_rand($profileIds)], 'viewed', null, $firstUserId]);
    $auditCount++;
}
$auditStmt->execute(['user', $firstUserId, 'logout', null, $firstUserId]);
$auditCount++;

foreach (array_slice($profileIds, 0, 80) as $pid) {
    $auditStmt->execute(['profile', (int) $pid, 'created', null, $firstUserId]);
    $auditCount++;
    if (random_int(0, 2) === 0) {
        $auditStmt->execute(['profile', (int) $pid, 'viewed', null, $firstUserId]);
        $auditCount++;
    }
}
foreach (array_slice($profileIds, 0, 30) as $pid) {
    $changes = json_encode(['age' => ['from' => random_int(40, 60), 'to' => random_int(41, 65)], 'contact_number' => ['from' => '09120000001', 'to' => '09181111111']]);
    $auditStmt->execute(['profile', (int) $pid, 'updated', $changes, $firstUserId]);
    $auditCount++;
}
foreach (array_slice($profileIds, 0, 15) as $pid) {
    $changes = json_encode(['sections' => ['residing_in_project_affected', 'structure_owners']]);
    $auditStmt->execute(['profile', (int) $pid, 'attachments_uploaded', $changes, $firstUserId]);
    $auditCount++;
}

foreach (array_slice($structureIds, 0, 50) as $sid) {
    $auditStmt->execute(['structure', (int) $sid, 'created', null, $firstUserId]);
    $auditCount++;
    if (random_int(0, 2) === 0) {
        $auditStmt->execute(['structure', (int) $sid, 'viewed', null, $firstUserId]);
        $auditCount++;
    }
}
foreach (array_slice($structureIds, 0, 15) as $sid) {
    $changes = json_encode(['structure_tag' => ['from' => 'RES-A-0001', 'to' => 'RES-B-0002'], 'description' => ['from' => 'Old desc', 'to' => 'Updated description']]);
    $auditStmt->execute(['structure', (int) $sid, 'updated', $changes, $firstUserId]);
    $auditCount++;
}
foreach (array_slice($structureIds, 0, 8) as $sid) {
    $changes = json_encode(['sections' => ['tagging_images', 'structure_images']]);
    $auditStmt->execute(['structure', (int) $sid, 'attachments_uploaded', $changes, $firstUserId]);
    $auditCount++;
}

echo "  Audit log entries: " . number_format($auditCount) . "\n";
