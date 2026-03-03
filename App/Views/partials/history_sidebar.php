<?php
/** @var array<int,object> $history */
/** @var array<int,object>|null $statusLog */
$history = $history ?? [];
$statusLog = $statusLog ?? [];
$historyEntityType = $historyEntityType ?? null;
$historyEntityId = $historyEntityId ?? null;
$historyPageSize = $historyPageSize ?? 20;
$historyHasMore = !empty($historyHasMore);
?>
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="mb-0 small text-uppercase text-muted">Activity History</h6>
    </div>
    <div class="card-body p-2 history-scroll"
         style="max-height: 360px; overflow-y: auto;"
         data-entity-type="<?= htmlspecialchars((string) $historyEntityType) ?>"
         data-entity-id="<?= (int) $historyEntityId ?>"
         data-page="1"
         data-page-size="<?= (int) $historyPageSize ?>"
         data-has-more="<?= $historyHasMore ? '1' : '0' ?>">
        <?php if (empty($history)): ?>
        <p class="text-muted small mb-0 history-empty-message">No activity recorded yet.</p>
        <?php else: ?>
        <ul class="list-unstyled mb-0 small history-list">
            <?php foreach ($history as $entry): ?>
            <li class="mb-2">
                <div><strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $entry->action ?? ''))) ?></strong></div>
                <div class="text-muted"><?= htmlspecialchars($entry->created_at ?? '') ?><?= $entry->created_by_name ? ' · ' . htmlspecialchars($entry->created_by_name) : '' ?></div>
                <?php if (!empty($entry->changes) && is_array($entry->changes)): ?>
                <ul class="mb-0 mt-1 ps-3">
                    <?php foreach ($entry->changes as $field => $change): ?>
                    <li><?= htmlspecialchars($field) ?>: <span class="text-muted"><?= htmlspecialchars((string)($change['from'] ?? '')) ?></span> → <span class="text-success"><?= htmlspecialchars((string)($change['to'] ?? '')) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php if ($historyHasMore): ?>
        <div class="text-center small text-muted mt-2 history-loading" style="display:none;">Loading more…</div>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($statusLog)): ?>
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="mb-0 small text-uppercase text-muted">Status History</h6>
    </div>
    <div class="card-body p-2" style="max-height: 360px; overflow-y: auto;">
        <ul class="list-unstyled mb-0 small">
            <?php foreach ($statusLog as $entry): ?>
            <li class="mb-2">
                <div>
                    <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $entry->status))) ?></strong>
                </div>
                <div class="text-muted"><?= htmlspecialchars($entry->created_at ?? '') ?><?= $entry->created_by_name ? ' · ' . htmlspecialchars($entry->created_by_name) : '' ?></div>
                <?php if (!empty(trim($entry->note ?? ''))): ?>
                <div class="mt-1"><?= nl2br(htmlspecialchars($entry->note)) ?></div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<script>
// Lazy-load Activity History on scroll
(function(){
    function shouldLoadMore(container) {
        if (!container) return false;
        var hasMore = container.getAttribute('data-has-more') === '1';
        var loading = container.getAttribute('data-loading') === '1';
        if (!hasMore || loading) return false;
        return container.scrollTop + container.clientHeight >= container.scrollHeight - 40;
    }

    function attachScroll(container) {
        if (!container) return;
        container.addEventListener('scroll', function () {
            if (!shouldLoadMore(container)) return;
            loadNextPage(container);
        });
    }

    function loadNextPage(container) {
        var entityType = container.getAttribute('data-entity-type') || '';
        var entityId = parseInt(container.getAttribute('data-entity-id') || '0', 10);
        var page = parseInt(container.getAttribute('data-page') || '1', 10);
        var pageSize = parseInt(container.getAttribute('data-page-size') || '20', 10);
        if (!entityType || !entityId) return;

        container.setAttribute('data-loading', '1');
        var loadingEl = container.querySelector('.history-loading');
        if (loadingEl) loadingEl.style.display = 'block';

        var nextPage = page + 1;
        var url = '/api/history?entity_type=' + encodeURIComponent(entityType)
            + '&entity_id=' + encodeURIComponent(entityId)
            + '&page=' + encodeURIComponent(nextPage)
            + '&per_page=' + encodeURIComponent(pageSize);

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (data) {
                var list = container.querySelector('.history-list');
                if (!list) {
                    list = document.createElement('ul');
                    list.className = 'list-unstyled mb-0 small history-list';
                    var emptyMsg = container.querySelector('.history-empty-message');
                    if (emptyMsg) emptyMsg.remove();
                    container.insertBefore(list, loadingEl || null);
                }
                (data.items || []).forEach(function (entry) {
                    var li = document.createElement('li');
                    li.className = 'mb-2';
                    var title = document.createElement('div');
                    var action = (entry.action || '').replace(/_/g, ' ');
                    action = action.charAt(0).toUpperCase() + action.slice(1);
                    title.innerHTML = '<strong>' + escapeHtml(action) + '</strong>';
                    var meta = document.createElement('div');
                    var metaText = entry.created_at || '';
                    if (entry.created_by_name) {
                        metaText += (metaText ? ' · ' : '') + entry.created_by_name;
                    }
                    meta.className = 'text-muted';
                    meta.textContent = metaText;
                    li.appendChild(title);
                    li.appendChild(meta);
                    if (entry.changes && typeof entry.changes === 'object') {
                        var changesList = document.createElement('ul');
                        changesList.className = 'mb-0 mt-1 ps-3';
                        Object.keys(entry.changes).forEach(function (field) {
                            var change = entry.changes[field] || {};
                            var fromVal = String(change.from !== undefined ? change.from : '');
                            var toVal = String(change.to !== undefined ? change.to : '');
                            var cli = document.createElement('li');
                            cli.innerHTML = escapeHtml(field) + ': <span class="text-muted">' + escapeHtml(fromVal) + '</span> \u2192 <span class="text-success">' + escapeHtml(toVal) + '</span>';
                            changesList.appendChild(cli);
                        });
                        li.appendChild(changesList);
                    }
                    list.appendChild(li);
                });

                container.setAttribute('data-page', String(nextPage));
                container.setAttribute('data-has-more', data.has_more ? '1' : '0');
            })
            .catch(function () {
                // Ignore errors for now; user can scroll again to retry.
            })
            .finally(function () {
                container.removeAttribute('data-loading');
                if (loadingEl) loadingEl.style.display = 'none';
            });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var containers = document.querySelectorAll('.history-scroll');
        containers.forEach(attachScroll);
    });
})();
</script>

