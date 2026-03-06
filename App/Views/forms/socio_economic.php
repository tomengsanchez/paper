<?php
/** @var array $forms @var string $search @var array $pagination */
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Socio Economic Forms</h2>
    <a href="/forms/socio-economic/create" class="btn btn-primary">Add Form</a>
</div>
<form method="get" action="/forms/socio-economic" class="row g-2 mb-3">
    <div class="col-sm-6 col-md-4">
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Search by title or project..." value="<?= htmlspecialchars($search ?? '') ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
        <?php if (!empty($search)): ?>
        <a href="/forms/socio-economic" class="btn btn-sm btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Project</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th style="width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $f): ?>
                <tr>
                    <td>
                        <a href="/forms/socio-economic/edit/<?= (int)$f->id ?>" class="text-decoration-none">
                            <?= htmlspecialchars($f->title) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($f->project_name ?? '-') ?></td>
                    <td><?= htmlspecialchars($f->created_at ?? '') ?></td>
                    <td><?= htmlspecialchars($f->updated_at ?? '') ?></td>
                    <td class="d-flex flex-wrap gap-1">
                        <a href="/forms/socio-economic/fill/<?= (int)$f->id ?>" class="btn btn-sm btn-outline-success">Fill</a>
                        <a href="/forms/socio-economic/entries/<?= (int)$f->id ?>" class="btn btn-sm btn-outline-secondary">Entries</a>
                        <a href="/forms/socio-economic/edit/<?= (int)$f->id ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="post" action="/forms/socio-economic/delete/<?= (int)$f->id ?>" class="d-inline" onsubmit="return confirm('Delete this form?');">
                            <?= \Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($forms)): ?>
                <tr><td colspan="5" class="text-muted text-center py-4">No Socio Economic forms yet.</td></tr>
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
    ?>
    <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="text-muted small">
            <?= $total > 0 ? 'Showing ' . number_format($start) . '–' . number_format($end) . ' of ' . number_format($total) : '0 records' ?>
        </span>
        <?php if ($totalPages > 1): ?>
        <div class="d-flex align-items-center gap-2">
            <?php
            $base = '/forms/socio-economic?q=' . urlencode($search ?? '') . '&per_page=' . $perPage;
            $prevUrl = $page <= 1 ? '#' : $base . '&page=' . ($page - 1);
            $nextUrl = $page >= $totalPages ? '#' : $base . '&page=' . ($page + 1);
            ?>
            <a class="btn btn-sm btn-outline-secondary<?= $page <= 1 ? ' disabled' : '' ?>" href="<?= $page <= 1 ? '#' : htmlspecialchars($prevUrl) ?>">Previous</a>
            <span class="text-muted small">Page <?= number_format($page) ?> of <?= number_format($totalPages) ?></span>
            <a class="btn btn-sm btn-outline-secondary<?= $page >= $totalPages ? ' disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : htmlspecialchars($nextUrl) ?>">Next</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Socio Economic Forms';
$currentPage = 'forms-socio-economic';
require __DIR__ . '/../layout/main.php';
