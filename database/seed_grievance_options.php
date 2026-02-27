<?php
/**
 * Seeder: Grievance Options Library (commonly used default data)
 *
 * Seeds vulnerabilities, respondent types, GRM channels, preferred languages,
 * grievance types, and grievance categories with standard Philippine project data.
 *
 * Run from project root: php database/seed_grievance_options.php
 *
 * Safe to re-run: skips tables that already have data.
 */

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;

$db = Database::getInstance();

function seedIfEmpty(\PDO $db, string $table, array $rows, array $columns): int
{
    $count = (int) $db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    if ($count > 0) {
        echo "  Skipping {$table} (already has {$count} row(s)).\n";
        return 0;
    }
    $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
    $colList = '`' . implode('`,`', $columns) . '`';
    $stmt = $db->prepare("INSERT INTO `{$table}` ({$colList}) VALUES {$placeholders}");
    $inserted = 0;
    foreach ($rows as $row) {
        $stmt->execute($row);
        $inserted++;
    }
    echo "  Seeded {$table}: {$inserted} row(s).\n";
    return $inserted;
}

echo "Grievance Options Library Seeder\n";
echo "=================================\n";

// 1. Grievance Vulnerabilities
seedIfEmpty($db, 'grievance_vulnerabilities', [
    ['Indigent, Household below the poverty threshold', '', 0],
    ['Senior Citizen', '', 0],
    ['Person with disability', '', 0],
    ['Female headed household', '', 0],
], ['name', 'description', 'sort_order']);

// 2. Grievance Respondent Types (name, type, type_specify, guide, description, sort_order)
seedIfEmpty($db, 'grievance_respondent_types', [
    ['Residing Person', 'Directly Affected', '', '', '', 0],
    ['Non Residing Person', 'Directly Affected', '', '', '', 0],
    ['Business', 'Directly Affected', '', '', '', 0],
    ['Institution', 'Directly Affected', '', '', '', 0],
    ['Non-Government Organization', 'Directly Affected', '', '', '', 0],
    ['Local Government Representatives', 'Directly Affected', '', '', '', 0],
    ['Others', 'Directly Affected', '', '', '', 0],
    ['Person Residing Near Project Area', 'Indirectly Affected', '', '', '', 0],
    ['Business', 'Indirectly Affected', '', '', '', 0],
    ['Institution', 'Indirectly Affected', '', '', '', 0],
    ['Non-Government Organization', 'Indirectly Affected', '', '', '', 0],
    ['Local Government Representatives', 'Indirectly Affected', '', '', '', 0],
], ['name', 'type', 'type_specify', 'guide', 'description', 'sort_order']);

// 3. GRM Channels
seedIfEmpty($db, 'grievance_grm_channels', [
    ['GRM Boxes', '', 0],
    ['Barangay Help Desk', '', 0],
    ['Lgu Helpdesk', '', 0],
    ['Dedicated GRM Email', '', 0],
    ['SMS in GRM Hotline', '', 0],
    ['Phone Call in GRM Hotline', '', 0],
    ['Verbal to GRM Personnel', '', 0],
    ['Verbal to LGU Representative', '', 0],
], ['name', 'description', 'sort_order']);

// 4. Preferred Languages
seedIfEmpty($db, 'grievance_preferred_languages', [
    ['Filipino', '', 0],
    ['English', '', 0],
], ['name', 'description', 'sort_order']);

// 5. Grievance Types
seedIfEmpty($db, 'grievance_types', [
    ['Complaint', '', 0],
    ['Comment', '', 0],
    ['Concern', '', 0],
    ['Information Request', '', 0],
], ['name', 'description', 'sort_order']);

// 6. Grievance Categories
seedIfEmpty($db, 'grievance_categories', [
    ['Environment', '', 0],
    ['Involuntary Resettlement', '', 0],
    ['Indigenous People', '', 0],
], ['name', 'description', 'sort_order']);

echo "\nDone.\n";
