<?php ob_start();
$g = $grievance;
$curStatus = $g->status ?? 'open';
$curLevel = $g->progress_level ?? null;
$progressLevels = $progressLevels ?? [];
$levelNameById = [];
foreach ($progressLevels as $pl) { $levelNameById[(int)$pl->id] = $pl->name; }
$vulnIds = \App\Models\Grievance::parseJson($g->vulnerability_ids ?? '');
$respIds = \App\Models\Grievance::parseJson($g->respondent_type_ids ?? '');
$grmIds = \App\Models\Grievance::parseJson($g->grm_channel_ids ?? '');
$langIds = \App\Models\Grievance::parseJson($g->preferred_language_ids ?? '');
$typeIds = \App\Models\Grievance::parseJson($g->grievance_type_ids ?? '');
$catIds = \App\Models\Grievance::parseJson($g->grievance_category_ids ?? '');
$vulnNames = array_filter(array_map(function($id) use ($vulnerabilities) { foreach ($vulnerabilities as $v) if ($v->id == $id) return $v->name; return null; }, $vulnIds));
$respNames = array_filter(array_map(function($id) use ($respondentTypes) { foreach ($respondentTypes as $r) if ($r->id == $id) return $r->name; return null; }, $respIds));
$grmNames = array_filter(array_map(function($id) use ($grmChannels) { foreach ($grmChannels as $c) if ($c->id == $id) return $c->name; return null; }, $grmIds));
$langNames = array_filter(array_map(function($id) use ($preferredLanguages) { foreach ($preferredLanguages as $l) if ($l->id == $id) return $l->name; return null; }, $langIds));
$typeNames = array_filter(array_map(function($id) use ($grievanceTypes) { foreach ($grievanceTypes as $t) if ($t->id == $id) return $t->name; return null; }, $typeIds));
$catNames = array_filter(array_map(function($id) use ($grievanceCategories) { foreach ($grievanceCategories as $c) if ($c->id == $id) return $c->name; return null; }, $catIds));
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Grievance <?= htmlspecialchars($g->grievance_case_number ?: '#' . $g->id) ?></h2>
    <div>
        <?php if (\Core\Auth::can('edit_grievance')): ?><a href="/grievance/edit/<?= (int)$g->id ?>" class="btn btn-outline-primary">Edit</a><?php endif; ?>
        <a href="/grievance/list" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<?php if (!empty($_SESSION['grievance_attachment_error'])): ?>
<div class="alert alert-warning alert-dismissible fade show mb-3">
    <?= htmlspecialchars($_SESSION['grievance_attachment_error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['grievance_attachment_error']); endif; ?>

<?php if (!empty($g->escalation_message)): ?>
<div class="alert alert-danger mb-3">
    <?= htmlspecialchars($g->escalation_message) ?>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Grievance Registration</h6></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Date Recorded</dt><dd class="col-sm-9"><?= $g->date_recorded ? date('M j, Y H:i', strtotime($g->date_recorded)) : '-' ?></dd>
            <dt class="col-sm-3">Case Number</dt><dd class="col-sm-9"><?= htmlspecialchars($g->grievance_case_number ?: '-') ?></dd>
        </dl>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Respondent's Profile</h6></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Project</dt><dd class="col-sm-9"><?= htmlspecialchars($g->project_name ?? '-') ?></dd>
            <dt class="col-sm-3">Is PAPS</dt><dd class="col-sm-9"><?= !empty($g->is_paps) ? 'Yes' : 'No' ?></dd>
            <?php if (!empty($g->is_paps)): ?>
            <dt class="col-sm-3">Profile</dt><dd class="col-sm-9"><a href="/profile/view/<?= (int)$g->profile_id ?>"><?= htmlspecialchars($g->profile_name ?? $g->papsid ?? '') ?></a></dd>
            <?php else: ?>
            <dt class="col-sm-3">Full Name</dt><dd class="col-sm-9"><?= htmlspecialchars($g->respondent_full_name ?: '-') ?></dd>
            <?php endif; ?>
            <dt class="col-sm-3">Gender</dt><dd class="col-sm-9"><?= htmlspecialchars($g->gender ?: '-') ?><?= $g->gender === 'Others' && $g->gender_specify ? ' (' . htmlspecialchars($g->gender_specify) . ')' : '' ?></dd>
            <dt class="col-sm-3">Valid ID</dt><dd class="col-sm-9"><?= htmlspecialchars($g->valid_id_philippines ?: '-') ?></dd>
            <dt class="col-sm-3">ID Number</dt><dd class="col-sm-9"><?= htmlspecialchars($g->id_number ?: '-') ?></dd>
            <dt class="col-sm-3">Vulnerabilities</dt><dd class="col-sm-9"><?= implode(', ', $vulnNames) ?: '-' ?></dd>
            <dt class="col-sm-3">Respondent Type</dt><dd class="col-sm-9"><?= implode(', ', $respNames) ?: '-' ?><?= !empty($g->respondent_type_other_specify) ? ' (' . htmlspecialchars($g->respondent_type_other_specify) . ')' : '' ?></dd>
        </dl>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Contact Details</h6></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Address</dt><dd class="col-sm-9"><?= nl2br(htmlspecialchars($g->home_business_address ?: '-')) ?></dd>
            <dt class="col-sm-3">Mobile</dt><dd class="col-sm-9"><?= htmlspecialchars($g->mobile_number ?: '-') ?></dd>
            <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?= htmlspecialchars($g->email ?: '-') ?></dd>
            <dt class="col-sm-3">Others</dt><dd class="col-sm-9"><?= htmlspecialchars($g->contact_others_specify ?: '-') ?></dd>
        </dl>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">GRM Mode</h6></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">GRM Channel</dt><dd class="col-sm-9"><?= implode(', ', $grmNames) ?: '-' ?></dd>
            <dt class="col-sm-3">Preferred Language</dt><dd class="col-sm-9"><?= implode(', ', $langNames) ?: '-' ?><?= !empty($g->preferred_language_other_specify) ? (($langNames ? ', ' : '') . htmlspecialchars($g->preferred_language_other_specify)) : '' ?></dd>
            <dt class="col-sm-3">Type of Grievance</dt><dd class="col-sm-9"><?= implode(', ', $typeNames) ?: '-' ?></dd>
            <dt class="col-sm-3">Category</dt><dd class="col-sm-9"><?= implode(', ', $catNames) ?: '-' ?></dd>
        </dl>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Location of Interest</h6></div>
    <div class="card-body">
        <p class="mb-0"><?= !empty($g->location_same_as_address) ? 'Same as home/business address' : htmlspecialchars($g->location_specify ?: '-') ?></p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Date of Incident</h6></div>
    <div class="card-body">
        <?php if (!empty($g->incident_one_time)): ?>
        <p><strong>One-time:</strong> <?= $g->incident_date ? date('M j, Y', strtotime($g->incident_date)) : '-' ?></p>
        <?php endif; ?>
        <?php if (!empty($g->incident_multiple)): ?>
        <p><strong>Multiple:</strong> <?= htmlspecialchars($g->incident_dates ?: '-') ?></p>
        <?php endif; ?>
        <?php if (!empty($g->incident_ongoing)): ?>
        <p><strong>Ongoing</strong> (Currently experiencing the problem)</p>
        <?php endif; ?>
        <?php if (empty($g->incident_one_time) && empty($g->incident_multiple) && empty($g->incident_ongoing)): ?>
        <p class="text-muted mb-0">-</p>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Description of the Complaint</h6></div>
    <div class="card-body"><p class="mb-0"><?= nl2br(htmlspecialchars($g->description_complaint ?: '-')) ?></p></div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Desired Resolution</h6></div>
    <div class="card-body"><p class="mb-0"><?= nl2br(htmlspecialchars($g->desired_resolution ?: '-')) ?></p></div>
</div>

<?php $cardAttachments = $attachments ?? []; if (!empty($cardAttachments)): ?>
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Attachments</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($cardAttachments as $att): ?>
            <div class="col-md-6 col-lg-4">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-1"><?= htmlspecialchars($att->title ?: 'Untitled') ?></h6>
                    <?php if (!empty(trim($att->description ?? ''))): ?>
                    <p class="small text-muted mb-2"><?= nl2br(htmlspecialchars($att->description)) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($att->file_path)): ?>
                    <a href="/serve/grievance-card-attachment?id=<?= (int)$att->id ?>" target="_blank" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(basename($att->file_path)) ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Status Card -->
<div class="card mb-3 border-primary">
    <div class="card-header bg-light"><h6 class="mb-0">Status</h6></div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-auto mb-2">
                <span class="badge fs-6 <?= $curStatus === 'closed' ? 'bg-secondary' : ($curStatus === 'in_progress' ? 'bg-primary' : 'bg-success') ?>">
                    <?= $curStatus === 'open' ? 'Open' : ($curStatus === 'closed' ? 'Closed' : 'In Progress' . ($curLevel && isset($levelNameById[(int)$curLevel]) ? ' ' . htmlspecialchars($levelNameById[(int)$curLevel]) : ($curLevel ? ' L' . (int)$curLevel : ''))) ?>
                </span>
            </div>
        </div>
        <?php if (\Core\Auth::can('change_grievance_status')): ?>
        <form method="post" action="/grievance/status-update/<?= (int)$g->id ?>" enctype="multipart/form-data" class="mt-3">
            <?= \Core\Csrf::field() ?>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small">Status</label>
                    <select name="status" id="statusSelect" class="form-select form-select-sm">
                        <option value="open" <?= $curStatus === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="in_progress" <?= $curStatus === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="closed" <?= $curStatus === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-4" id="progressLevelBlock" style="display:<?= $curStatus === 'in_progress' ? 'block' : 'none' ?>">
                    <label class="form-label small">Level</label>
                    <select name="progress_level" class="form-select form-select-sm">
                        <option value="">-- Select --</option>
                        <?php foreach ($progressLevels as $pl): ?>
                        <option value="<?= (int)$pl->id ?>" <?= $curLevel == $pl->id ? 'selected' : '' ?>><?= htmlspecialchars($pl->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-2">
                <label class="form-label small">Note</label>
                <textarea name="status_note" class="form-control form-control-sm" rows="2" placeholder="Add a note for this status/stage"></textarea>
            </div>
            <div class="mt-2">
                <label class="form-label small">Attachments</label>
                <input type="file" name="status_attachments[]" class="form-control form-control-sm" multiple accept="image/*,.pdf">
            </div>
            <button type="submit" class="btn btn-primary btn-sm mt-2">Update Status</button>
        </form>
        <script>
        document.getElementById('statusSelect')?.addEventListener('change', function() {
            document.getElementById('progressLevelBlock').style.display = this.value === 'in_progress' ? 'block' : 'none';
        });
        </script>
        <?php endif; ?>
        <?php if (!empty($statusLog)): ?>
        <hr class="my-3">
        <h6 class="small text-muted mb-2">Status History</h6>
        <div class="status-log">
            <?php foreach ($statusLog as $entry): ?>
            <div class="border-start border-2 ps-2 mb-2 small">
                <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $entry->status))) ?><?= $entry->progress_level && isset($levelNameById[(int)$entry->progress_level]) ? ' ' . htmlspecialchars($levelNameById[(int)$entry->progress_level]) : ($entry->progress_level ? ' L' . (int)$entry->progress_level : '') ?></strong>
                <span class="text-muted"><?= date('M j, Y H:i', strtotime($entry->created_at)) ?> <?= $entry->created_by_name ? 'by ' . htmlspecialchars($entry->created_by_name) : '' ?></span>
                <?php if (!empty(trim($entry->note ?? ''))): ?><p class="mb-1 mt-1"><?= nl2br(htmlspecialchars($entry->note)) ?></p><?php endif; ?>
                <?php $atts = \App\Models\GrievanceStatusLog::parseAttachments($entry->attachments ?? ''); if (!empty($atts)): ?>
                <div class="mb-1">
                    <?php foreach ($atts as $att): $url = \App\Controllers\GrievanceController::attachmentUrl($att, $g->id); ?>
                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="me-2"><?= htmlspecialchars(basename($att)) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = 'Grievance ' . ($g->grievance_case_number ?: '#' . $g->id); $currentPage = 'grievance-list'; require __DIR__ . '/../layout/main.php'; ?>
