<?php
$timestamp = $timestamp ?? '';
$loadTimeMs = $loadTimeMs ?? 0;
$queries = $queries ?? [];
$classesLoaded = $classesLoaded ?? [];
$functions = $functions ?? [];

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">System Debug Log</h2>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <strong class="text-muted small text-uppercase">Request timestamp</strong>
                <div class="fw-semibold"><?= htmlspecialchars($timestamp) ?></div>
            </div>
            <div class="col-md-6">
                <strong class="text-muted small text-uppercase">Loading time</strong>
                <div class="fw-semibold"><?= number_format($loadTimeMs, 2) ?> ms</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="mb-0">Database queries</h5>
                <small class="text-muted"><?= count($queries) ?> query(ies)</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($queries)): ?>
                    <p class="text-muted mb-0 p-3">No queries recorded for this request.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 3rem;">#</th>
                                    <th>Query</th>
                                    <th class="text-end" style="width: 7rem;">Duration (ms)</th>
                                    <th class="text-end" style="width: 7rem;">Time (ms)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($queries as $i => $q): ?>
                                <tr>
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td>
                                        <code class="small text-break"><?= htmlspecialchars(preg_replace('/\s+/', ' ', trim($q['sql'] ?? ''))) ?></code>
                                        <?php if (!empty($q['params']) && is_array($q['params'])): ?>
                                            <div class="mt-1 small text-muted">Params: <?= htmlspecialchars(json_encode($q['params'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= number_format($q['duration'] ?? 0, 2) ?></td>
                                    <td class="text-end text-muted"><?= number_format($q['time'] ?? 0, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="mb-0">PHP functions (user-defined)</h5>
                <small class="text-muted"><?= count($functions) ?> function(s)</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($functions)): ?>
                    <p class="text-muted mb-0 p-3">No user-defined functions listed.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 3rem;">#</th>
                                    <th>Function</th>
                                    <th>File</th>
                                    <th class="text-end" style="width: 5rem;">Line</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($functions as $i => $f): ?>
                                <tr>
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td><code><?= htmlspecialchars($f['function'] ?? '') ?></code></td>
                                    <td class="small text-break"><?= htmlspecialchars($f['file'] ?? '') ?></td>
                                    <td class="text-end"><?= (int)($f['line'] ?? 0) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="mb-0">Class loading</h5>
                <small class="text-muted"><?= count($classesLoaded) ?> class(es)</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($classesLoaded)): ?>
                    <p class="text-muted mb-0 p-3">No classes recorded for this request.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 3rem;">#</th>
                                    <th>Class</th>
                                    <th class="text-end" style="width: 8rem;">Loaded at (ms)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classesLoaded as $i => $c): ?>
                                <tr>
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td><code><?= htmlspecialchars($c['class'] ?? '') ?></code></td>
                                    <td class="text-end"><?= number_format($c['time'] ?? 0, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'System Debug Log';
$currentPage = 'debug-log';
require __DIR__ . '/../layout/main.php';
