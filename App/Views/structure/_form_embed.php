<?php
// Embedded structure form for Profile lightbox. Expects: $structure (nullable), $strid, $fixedOwnerId (profile id), $formId, $submitBtnId
// Image thumbnails are populated via JS when editing (embedTaggingImages, embedStructureImages)
?>
<form id="<?= htmlspecialchars($formId ?? 'structureFormEmbed') ?>" enctype="multipart/form-data">
    <input type="hidden" name="owner_id" value="<?= (int)($fixedOwnerId ?? 0) ?>">
    <input type="hidden" name="structure_id" value="<?= !empty($structure) ? (int)$structure->id : '' ?>">
    <?php if (!empty($structure)): ?>
    <div class="mb-2">
        <label class="form-label">Structure ID</label>
        <input type="text" name="strid" class="form-control form-control-sm" value="<?= htmlspecialchars($structure->strid ?? '') ?>" readonly>
    </div>
    <?php endif; ?>
    <div class="mb-2">
        <label class="form-label">Structure Tag #</label>
        <input type="text" name="structure_tag" class="form-control form-control-sm" value="<?= htmlspecialchars($structure->structure_tag ?? '') ?>">
    </div>
    <div class="mb-2">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($structure->description ?? '') ?></textarea>
    </div>
    <div class="mb-2">
        <label class="form-label">Tagging Images</label>
        <input type="file" name="tagging_images[]" class="form-control form-control-sm" accept="image/*" multiple>
        <div id="embedTaggingImages" class="d-flex flex-wrap gap-1 mt-1"></div>
    </div>
    <div class="mb-2">
        <label class="form-label">Structure Images</label>
        <input type="file" name="structure_images[]" class="form-control form-control-sm" accept="image/*" multiple>
        <div id="embedStructureImages" class="d-flex flex-wrap gap-1 mt-1"></div>
    </div>
    <div class="mb-2">
        <label class="form-label">Other Details</label>
        <textarea name="other_details" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($structure->other_details ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary btn-sm" id="<?= htmlspecialchars($submitBtnId ?? 'structureFormSubmit') ?>">Save Structure</button>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="structureFormCancel">Cancel</button>
</form>
