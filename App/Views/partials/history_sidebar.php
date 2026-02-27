<?php
/** @var array<int,object> $history */
/** @var array<int,object>|null $statusLog */
$history = $history ?? [];
$statusLog = $statusLog ?? [];
?>
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="mb-0 small text-uppercase text-muted">Activity History</h6>
    </div>
    <div class="card-body p-2" style="max-height: 360px; overflow-y: auto;">
        <?php if (empty($history)): ?>
        <p class="text-muted small mb-0">No activity recorded yet.</p>
        <?php else: ?>
        <ul class="list-unstyled mb-0 small">
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

