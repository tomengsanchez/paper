<?php
/** @var object $form @var array $entries @var array $pagination */
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">Socio Economic Entries</h2>
        <div class="text-muted small">
            Form: <?= htmlspecialchars($form->title ?? '') ?>
            <?php if (!empty($form->project_name)): ?>
                · Project: <?= htmlspecialchars($form->project_name) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="/forms/socio-economic/fill/<?= (int)$form->id ?>" class="btn btn-primary btn-sm">Fill Form</a>
        <a href="/forms/socio-economic" class="btn btn-outline-secondary btn-sm">Back to forms</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th>ID</th>
                <th>Project</th>
                <th>PAPS Profile</th>
                <th>Submitted By</th>
                <th>Submitted At</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $e): ?>
                <tr>
                    <td><?= (int)$e->id ?></td>
                    <td><?= htmlspecialchars($e->project_name ?? '-') ?></td>
                    <td><?= htmlspecialchars($e->profile_name ?? '-') ?></td>
                    <td><?= htmlspecialchars($e->created_by_name ?? '-') ?></td>
                    <td><?= htmlspecialchars($e->created_at ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($entries)): ?>
                <tr>
                    <td colspan="5" class="text-muted text-center py-4">No entries submitted yet.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $p = $pagination ?? ['page' => 1, 'per_page' => 15, 'total' => 0, 'total_pages' => 0];
    $page = (int)($p['page'] ?? 1);
    $totalPages = max(1, (int)($p['total_pages'] ?? 0));
    $total = (int)($p['total'] ?? 0);
    $perPage = (int)($p['per_page'] ?? 15);
    $start = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
    $end = min($page * $perPage, $total);
    $base = '/forms/socio-economic/entries/' . (int)$form->id . '?per_page=' . $perPage;
    $prevUrl = $page <= 1 ? '#' : $base . '&page=' . ($page - 1);
    $nextUrl = $page >= $totalPages ? '#' : $base . '&page=' . ($page + 1);
    ?>
    <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="text-muted small">
            <?= $total > 0 ? 'Showing ' . number_format($start) . '–' . number_format($end) . ' of ' . number_format($total) : '0 records' ?>
        </span>
        <?php if ($totalPages > 1): ?>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-sm btn-outline-secondary<?= $page <= 1 ? ' disabled' : '' ?>"
                   href="<?= $page <= 1 ? '#' : htmlspecialchars($prevUrl) ?>">Previous</a>
                <span class="text-muted small">Page <?= number_format($page) ?> of <?= number_format($totalPages) ?></span>
                <a class="btn btn-sm btn-outline-secondary<?= $page >= $totalPages ? ' disabled' : '' ?>"
                   href="<?= $page >= $totalPages ? '#' : htmlspecialchars($nextUrl) ?>">Next</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Socio Economic Entries';
$currentPage = 'forms-socio-economic';
require __DIR__ . '/../layout/main.php';

