<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$db = Core\Database::getInstance();
$db->exec('SET FOREIGN_KEY_CHECKS = 0');
$db->exec('TRUNCATE TABLE structures');
$db->exec('TRUNCATE TABLE profiles');
$db->exec('TRUNCATE TABLE projects');
$db->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "Truncated: structures, profiles, projects\n";
