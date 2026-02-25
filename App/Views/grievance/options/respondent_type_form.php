<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $item ? 'Edit Respondent Type' : 'Add Respondent Type' ?></h2>
    <a href="/grievance/options/respondent-types" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $item ? "/grievance/options/respondent-types/update/{$item->id}" : '/grievance/options/respondent-types/store' ?>">
            <?= \Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item->name ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Type</label>
                <select name="type" id="respondentTypeSelect" class="form-select">
                    <option value="Directly Affected" <?= ($item->type ?? '') === 'Directly Affected' ? 'selected' : '' ?>>Directly Affected</option>
                    <option value="Indirectly Affected" <?= ($item->type ?? '') === 'Indirectly Affected' ? 'selected' : '' ?>>Indirectly Affected</option>
                    <option value="Others" <?= ($item->type ?? '') === 'Others' ? 'selected' : '' ?>>Others</option>
                </select>
            </div>
            <div class="mb-3" id="typeSpecifyBlock" style="display:<?= ($item->type ?? '') === 'Others' ? 'block' : 'none' ?>">
                <label class="form-label">Specify (if Others)</label>
                <input type="text" name="type_specify" class="form-control" value="<?= htmlspecialchars($item->type_specify ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Guide</label>
                <textarea name="guide" class="form-control" rows="2"><?= htmlspecialchars($item->guide ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($item->description ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<script>document.getElementById('respondentTypeSelect').addEventListener('change', function(){ document.getElementById('typeSpecifyBlock').style.display = this.value === 'Others' ? 'block' : 'none'; });</script>
<?php $content = ob_get_clean(); $pageTitle = $item ? 'Edit Respondent Type' : 'Add Respondent Type'; $currentPage = 'grievance-respondent-types'; require __DIR__ . '/../../layout/main.php'; ?>
