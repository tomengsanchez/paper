<?php
// Expects: $listModule, $listBaseUrl, $listSearch, $listSort, $listOrder, $listColumns, $listAllColumns, $listPagination, $listHasCustomColumns
$listModule = $listModule ?? '';
$listBaseUrl = $listBaseUrl ?? '';
$listSearch = $listSearch ?? '';
$listSort = $listSort ?? '';
$listOrder = $listOrder ?? 'asc';
$listColumns = $listColumns ?? [];
$listAllColumns = $listAllColumns ?? [];
$listPagination = $listPagination ?? ['total' => 0, 'page' => 1, 'per_page' => 15, 'total_pages' => 0];
$listHasCustomColumns = $listHasCustomColumns ?? false;
$p = $listPagination;
$modalId = 'listColumnsModal_' . preg_replace('/[^a-z0-9]/', '_', $listModule);
?>
<div class="list-toolbar d-flex flex-wrap align-items-center gap-2 mb-3">
    <form method="get" action="<?= htmlspecialchars($listBaseUrl) ?>" class="d-flex gap-2 flex-grow-1">
        <input type="hidden" name="columns" value="<?= htmlspecialchars(implode(',', $listColumns)) ?>">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($listSort) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($listOrder) ?>">
        <input type="hidden" name="per_page" value="<?= (int)($p['per_page'] ?? 15) ?>">
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Search in visible columns..." value="<?= htmlspecialchars($listSearch) ?>" style="max-width:280px;">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
        <?php if ($listSearch): ?><a href="<?= htmlspecialchars($listBaseUrl) ?>?columns=<?= htmlspecialchars(implode(',', $listColumns)) ?>&sort=<?= htmlspecialchars($listSort) ?>&order=<?= htmlspecialchars($listOrder) ?>&per_page=<?= (int)($p['per_page'] ?? 15) ?>" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
    </form>
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted small fw-medium"><?= number_format((int)$p['total']) ?> record<?= (int)$p['total'] !== 1 ? 's' : '' ?></span>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= (int)($p['per_page'] ?? 15) ?> / page</button>
            <ul class="dropdown-menu">
                <?php foreach ([10, 15, 25, 50, 100] as $n): 
                    $url = $listBaseUrl . '?q=' . urlencode($listSearch) . '&columns=' . urlencode(implode(',', $listColumns)) . '&sort=' . urlencode($listSort) . '&order=' . urlencode($listOrder) . '&per_page=' . $n;
                ?><li><a class="dropdown-item" href="<?= htmlspecialchars($url) ?>"><?= $n ?></a></li><?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary position-relative" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>" title="Customize columns">Columns<?php if ($listHasCustomColumns): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size:0.5rem" title="Custom columns saved">●</span><?php endif; ?></button>
    </div>
</div>

<div class="modal fade" id="<?= $modalId ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Select Columns</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="get" action="<?= htmlspecialchars($listBaseUrl) ?>" id="listColForm_<?= $modalId ?>">
                <div class="modal-body">
                    <input type="hidden" name="q" value="<?= htmlspecialchars($listSearch) ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($listSort) ?>">
                    <input type="hidden" name="order" value="<?= htmlspecialchars($listOrder) ?>">
                    <input type="hidden" name="per_page" value="<?= (int)($p['per_page'] ?? 15) ?>">
                    <p class="text-muted small">Selected columns are shown and used for search.</p>
                    <?php foreach ($listAllColumns as $col): $checked = in_array($col['key'], $listColumns); ?>
                    <div class="form-check"><input class="form-check-input list-col-cb" type="checkbox" name="col[]" value="<?= htmlspecialchars($col['key']) ?>" id="col_<?= $modalId ?>_<?= htmlspecialchars($col['key']) ?>" <?= $checked ? 'checked' : '' ?>><label class="form-check-label" for="col_<?= $modalId ?>_<?= htmlspecialchars($col['key']) ?>"><?= htmlspecialchars($col['label']) ?></label></div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Apply</button></div>
            </form>
        </div>
    </div>
</div>
