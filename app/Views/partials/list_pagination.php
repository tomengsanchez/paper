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
<?php if (($p['total_pages'] ?? 0) > 1): ?>
<nav class="mt-3" aria-label="List pagination">
    <ul class="pagination pagination-sm mb-0 justify-content-center">
        <li class="page-item <?= ($p['page'] ?? 1) <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= ($p['page'] ?? 1) <= 1 ? '#' : htmlspecialchars($base . '&page=' . (($p['page'] ?? 1) - 1)) ?>">Previous</a></li>
        <?php 
        $page = $p['page'] ?? 1;
        $totalPages = $p['total_pages'] ?? 0;
        $range = 2;
        for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++):
        ?><li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($base . '&page=' . $i) ?>"><?= $i ?></a></li><?php endfor; ?>
        <li class="page-item <?= ($p['page'] ?? 1) >= ($p['total_pages'] ?? 1) ? 'disabled' : '' ?>"><a class="page-link" href="<?= ($p['page'] ?? 1) >= ($p['total_pages'] ?? 1) ? '#' : htmlspecialchars($base . '&page=' . (($p['page'] ?? 1) + 1)) ?>">Next</a></li>
    </ul>
    <p class="text-center text-muted small mt-1">Page <?= $page ?> of <?= $totalPages ?: 1 ?></p>
</nav>
<?php endif; ?>
