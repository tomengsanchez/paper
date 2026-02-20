<?php ob_start();
$taggingImages = \App\Models\Structure::parseImages($structure->tagging_images ?? '[]');
$structureImages = \App\Models\Structure::parseImages($structure->structure_images ?? '[]');
$allImageUrls = array_map(function($img) { return \App\Controllers\StructureController::imageUrl($img); }, array_merge($taggingImages, $structureImages));
$imgIdx = 0;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>View Structure</h2>
    <div>
        <a href="/structure" class="btn btn-outline-secondary">Back</a>
        <?php if (\Core\Auth::can('edit_structure')): ?><a href="/structure/edit/<?= (int)$structure->id ?>" class="btn btn-primary">Edit</a><?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Structure ID</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($structure->strid ?? '') ?></dd>
            <dt class="col-sm-3">Paps/Owner</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($structure->owner_name ?? '-') ?></dd>
            <dt class="col-sm-3">Structure Tag #</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($structure->structure_tag ?? '-') ?></dd>
            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($structure->description ?? '-')) ?></dd>
            <dt class="col-sm-3">Tagging Images</dt>
            <dd class="col-sm-9">
                <?php if (!empty($taggingImages)): ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($taggingImages as $img): $imgUrl = \App\Controllers\StructureController::imageUrl($img); ?>
                    <a href="#" class="struct-view-img" data-src="<?= htmlspecialchars($imgUrl) ?>" style="cursor:pointer;"><img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="rounded" style="width:80px;height:80px;object-fit:cover;" onerror="this.style.display='none'"></a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>-<?php endif; ?>
            </dd>
            <dt class="col-sm-3">Structure Images</dt>
            <dd class="col-sm-9">
                <?php if (!empty($structureImages)): ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($structureImages as $img): $imgUrl = \App\Controllers\StructureController::imageUrl($img); ?>
                    <a href="#" class="struct-view-img" data-src="<?= htmlspecialchars($imgUrl) ?>" style="cursor:pointer;"><img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="rounded" style="width:80px;height:80px;object-fit:cover;" onerror="this.style.display='none'"></a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>-<?php endif; ?>
            </dd>
            <dt class="col-sm-3">Other Details</dt>
            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($structure->other_details ?? '-')) ?></dd>
        </dl>
    </div>
</div>
<div class="modal fade" id="structViewImgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Image Preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center"><img id="structViewImgSrc" src="" alt="" class="img-fluid" style="max-height:80vh;"></div>
        </div>
    </div>
</div>
<?php
$scripts = "<script>$(function(){ $(document).on('click','.struct-view-img',function(e){ e.preventDefault(); var s=$(this).data('src')||$(this).find('img').attr('src'); if(s){ $('#structViewImgSrc').attr('src',s); $('#structViewImgModal').modal('show'); }}); });</script>";
$content = ob_get_clean();
$pageTitle = 'View Structure';
$currentPage = 'structure';
require __DIR__ . '/../layout/main.php';
?>
