<?php
// Expects: $listBaseUrl, $listSearch, $listColumns, $listSort, $listOrder, $listPagination
$p = $listPagination ?? ['page' => 1, 'per_page' => 15, 'total' => 0, 'total_pages' => 0];
$listBaseUrl = $listBaseUrl ?? '';
$listSearch = $listSearch ?? '';
$listColumns = $listColumns ?? [];
$listSort = $listSort ?? '';
$listOrder = $listOrder ?? 'asc';
$base = $listBaseUrl . '?q=' . urlencode($listSearch) . '&columns=' . urlencode(implode(',', $listColumns)) . '&sort=' . urlencode($listSort) . '&order=' . urlencode($listOrder) . '&per_page=' . (int)($p['per_page'] ?? 15);
?>
<?php 
$page = $p['page'] ?? 1;
$totalPages = max(1, $p['total_pages'] ?? 0);
$total = (int)($p['total'] ?? 0);
$perPage = (int)($p['per_page'] ?? 15);
$start = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
$end = min($page * $perPage, $total);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
    <span class="text-muted small"><?= $total > 0 ? 'Showing ' . $start . '–' . $end . ' of ' . number_format($total) : '0 records' ?></span>
    <?php if ($totalPages > 1): ?>
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-sm btn-outline-secondary<?= $page <= 1 ? ' disabled' : '' ?>" href="<?= $page <= 1 ? '#' : htmlspecialchars($base . '&page=' . ($page - 1)) ?>" <?= $page <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Previous</a>
        <select class="form-select form-select-sm" style="width:auto;" onchange="if(this.value)window.location.href=this.value" aria-label="Go to page">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <option value="<?= htmlspecialchars($base . '&page=' . $i) ?>" <?= $i === $page ? 'selected' : '' ?>>Page <?= $i ?> of <?= $totalPages ?></option>
            <?php endfor; ?>
        </select>
        <a class="btn btn-sm btn-outline-secondary<?= $page >= $totalPages ? ' disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : htmlspecialchars($base . '&page=' . ($page + 1)) ?>" <?= $page >= $totalPages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Next</a>
    </div>
    <?php endif; ?>
</div>
