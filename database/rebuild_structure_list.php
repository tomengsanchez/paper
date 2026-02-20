<?php
/**
 * Rebuild structure_list cache from EAV data.
 * Run after creating structure_list table or when cache is stale.
 *
 * Usage: php database/rebuild_structure_list.php
 */
require __DIR__ . '/../bootstrap.php';

use App\Models\Structure;

$count = Structure::rebuildStructureList();
echo "Rebuilt structure_list: $count structures\n";
