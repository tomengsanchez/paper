<?php
/**
 * Migration 023: Drop PAPeR-specific tables (profile, structure, project, grievance modules).
 * Use this framework as a base for new projects without PAPeR domain logic.
 */
return [
    'name' => 'migration_023_drop_paper_tables',
    'up' => function (\PDO $db): void {
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');

        $tables = [
            'grievance_attachments',
            'grievance_status_log',
            'grievances',
            'grievance_progress_levels',
            'grievance_categories',
            'grievance_types',
            'grievance_preferred_languages',
            'grievance_grm_channels',
            'grievance_respondent_types',
            'grievance_vulnerabilities',
            'structures',
            'profiles',
            'user_projects',
            'projects',
        ];

        foreach ($tables as $table) {
            $db->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    },
    'down' => function (\PDO $db): void {
        // Tables cannot be recreated; rollback only removes this migration from the migrations table.
        // To restore PAPeR tables, you would need to reset and re-run migrations 001–014.
    },
];
