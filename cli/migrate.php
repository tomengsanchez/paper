#!/usr/bin/env php
<?php
/**
 * Database migration CLI
 *
 * Usage:
 *   php cli/migrate.php           Run pending migrations
 *   php cli/migrate.php --status  Show migration status
 */

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    die('This script must be run from the command line.');
}

require_once dirname(__DIR__) . '/bootstrap.php';

use Core\Database;
use Core\MigrationRunner;

$db = Database::getInstance();
$runner = new MigrationRunner($db);

$statusOnly = in_array('--status', $argv ?? [], true);

if ($statusOnly) {
    $status = $runner->status();
    echo "Migration status:\n";
    foreach ($status as $m) {
        $mark = $m['status'] === 'ran' ? '[x]' : '[ ]';
        echo "  $mark {$m['name']} ({$m['file']})\n";
    }
    exit(0);
}

$result = $runner->runPending();

if (!empty($result['errors'])) {
    foreach ($result['errors'] as $err) {
        fwrite(STDERR, "Error: $err\n");
    }
    exit(1);
}

if ($result['ran'] > 0) {
    echo "Ran {$result['ran']} migration(s).\n";
} else {
    echo "No pending migrations.\n";
}
