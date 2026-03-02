<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$db = Core\Database::getInstance();
$db->exec('SET FOREIGN_KEY_CHECKS = 0');
$db->prepare('DELETE FROM audit_log WHERE entity_type = ?')->execute(['grievance']);
$db->exec('TRUNCATE TABLE grievance_status_log');
$db->exec('TRUNCATE TABLE grievances');
$db->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "Truncated: audit_log (grievance entries), grievance_status_log, grievances\n";
