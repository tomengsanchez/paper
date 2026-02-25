<?php ob_start();
$taggingImages = [];
$structureImages = [];
if (!empty($structure)) {
    $taggingImages = \App\Models\Structure::parseImages($structure->tagging_images ?? '[]');
    $structureImages = \App\Models\Structure::parseImages($structure->structure_images ?? '[]');
}
$allImageUrls = array_map(function($img) { return \App\Controllers\StructureController::imageUrl($img); }, array_merge($taggingImages, $structureImages));
$imgIdx = 0;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $structure ? 'Edit Structure' : 'Add Structure' ?></h2>
    <a href="/structure" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $structure ? "/structure/update/{$structure->id}" : '/structure/store' ?>" enctype="multipart/form-data">
            <?= \Core\Csrf::field() ?>
            <?php if (!empty($structure)): ?>
            <div class="mb-3">
                <label class="form-label">Structure ID</label>
                <input type="text" name="strid" class="form-control" value="<?= htmlspecialchars($structure->strid ?? '') ?>" readonly>
                <small class="text-muted">Assigned when structure was created</small>
            </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Paps/Owner</label>
                <select name="owner_id" id="ownerSelect" class="form-select" style="width:100%">
                    <option value="">-- Select Profile --</option>
                    <?php if (!empty($structure->owner_id)): ?>
                    <option value="<?= (int)$structure->owner_id ?>" selected><?= htmlspecialchars($structure->owner_name ?? '') ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Structure Tag #</label>
                <input type="text" name="structure_tag" class="form-control" value="<?= htmlspecialchars($structure->structure_tag ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Structure Description/Comment</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($structure->description ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Tagging Images</label>
                <input type="file" name="tagging_images[]" class="form-control" accept="image/*" multiple>
                <div id="taggingImagesContainer" class="d-flex flex-wrap gap-2 mt-2">
                <?php if (!empty($taggingImages)): ?>
                    <?php foreach ($taggingImages as $img): 
                        $imgUrl = \App\Controllers\StructureController::imageUrl($img);
                    ?>
                    <div class="struct-img-wrapper position-relative d-inline-block">
                        <a href="#" class="img-thumb" data-src="<?= htmlspecialchars($imgUrl) ?>" data-index="<?= $imgIdx++ ?>" style="cursor:pointer;">
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="rounded" style="width:80px;height:80px;object-fit:cover;" onerror="this.style.background='#eee';this.alt='Image failed to load';">
                        </a>
                        <button type="button" class="struct-img-remove btn btn-danger btn-sm position-absolute top-0 end-0" data-path="<?= htmlspecialchars($img) ?>" data-type="tagging" title="Remove image" style="padding:1px 5px;font-size:12px;">&times;</button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
                <div id="taggingImagesRemove"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Structure Images</label>
                <input type="file" name="structure_images[]" class="form-control" accept="image/*" multiple>
                <div id="structureImagesContainer" class="d-flex flex-wrap gap-2 mt-2">
                <?php if (!empty($structureImages)): ?>
                    <?php foreach ($structureImages as $img): 
                        $imgUrl = \App\Controllers\StructureController::imageUrl($img);
                    ?>
                    <div class="struct-img-wrapper position-relative d-inline-block">
                        <a href="#" class="img-thumb" data-src="<?= htmlspecialchars($imgUrl) ?>" data-index="<?= $imgIdx++ ?>" style="cursor:pointer;">
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="rounded" style="width:80px;height:80px;object-fit:cover;" onerror="this.style.background='#eee';this.alt='Image failed to load';">
                        </a>
                        <button type="button" class="struct-img-remove btn btn-danger btn-sm position-absolute top-0 end-0" data-path="<?= htmlspecialchars($img) ?>" data-type="structure" title="Remove image" style="padding:1px 5px;font-size:12px;">&times;</button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
                <div id="structureImagesRemove"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Other Details</label>
                <textarea name="other_details" class="form-control" rows="4"><?= htmlspecialchars($structure->other_details ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>

<!-- Image preview modal -->
<div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Image Preview <span id="imgModalCounter" class="text-muted ms-2"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 position-relative">
                <div class="d-flex justify-content-center gap-2 py-2 bg-light border-bottom">
                    <button type="button" id="imgModalZoomOut" class="btn btn-outline-secondary btn-sm" title="Zoom out">&#8722; Zoom Out</button>
                    <button type="button" id="imgModalZoomReset" class="btn btn-outline-secondary btn-sm" title="Reset zoom">1:1</button>
                    <button type="button" id="imgModalZoomIn" class="btn btn-outline-secondary btn-sm" title="Zoom in">&#43; Zoom In</button>
                    <span id="imgModalZoomLevel" class="align-self-center text-muted small ms-2">100%</span>
                </div>
                <div id="imgModalViewport" class="overflow-auto text-center" style="min-height:60vh;max-height:70vh;cursor:grab;user-select:none;">
                    <img id="imgModalImg" src="" alt="" draggable="false" style="transition:width 0.15s;">
                </div>
                <div class="position-absolute top-50 start-0 translate-middle-y ms-2" style="z-index:5;">
                    <button type="button" id="imgModalPrev" class="btn btn-dark" style="opacity:0.9;" title="Previous">&#9664; Previous</button>
                </div>
                <div class="position-absolute top-50 end-0 translate-middle-y me-2" style="z-index:5;">
                    <button type="button" id="imgModalNext" class="btn btn-dark" style="opacity:0.9;" title="Next">Next &#9654;</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$scripts = "
<script>
$(function(){
    $('#ownerSelect').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '/api/profiles',
            dataType: 'json',
            delay: 250,
            data: function(params){ return { q: params.term || '' }; },
            processResults: function(data){ return { results: data.map(function(r){ return { id: r.id, text: r.name || ('Profile #'+r.id) }; }) }; }
        },
        minimumInputLength: 0,
        placeholder: 'Search profile...',
        allowClear: true
    });
    var allImages = " . json_encode($allImageUrls) . ";
    var currentIdx = 0;
    var zoomLevel = 1;
    var zoomMin = 0.25;
    var zoomMax = 5;
    var zoomStep = 0.25;
    var imgNaturalW = 0;
    $('#imgModalImg').on('load', function(){ imgNaturalW = this.naturalWidth || this.width; if(zoomLevel !== 1) applyZoom(); });
    function applyZoom() {
        var w = imgNaturalW * zoomLevel;
        $('#imgModalImg').css('width', w > 0 ? w + 'px' : 'auto');
        $('#imgModalZoomLevel').text(Math.round(zoomLevel * 100) + '%');
    }
    function showImg(idx) {
        if (allImages.length === 0) return;
        currentIdx = ((idx % allImages.length) + allImages.length) % allImages.length;
        zoomLevel = 1;
        $('#imgModalImg').attr('src', allImages[currentIdx]).css('width', 'auto');
        $('#imgModalCounter').text((currentIdx + 1) + ' / ' + allImages.length);
        $('#imgModalPrev').toggle(allImages.length > 1);
        $('#imgModalNext').toggle(allImages.length > 1);
        $('#imgModalZoomLevel').text('100%');
    }
    $(document).on('click', '.img-thumb', function(e){
        e.preventDefault();
        var idx = parseInt($(this).data('index'), 10);
        if (!isNaN(idx)) currentIdx = idx;
        showImg(currentIdx);
        new bootstrap.Modal(document.getElementById('imgModal')).show();
    });
    $('#imgModalPrev').on('click', function(e){ e.preventDefault(); showImg(currentIdx - 1); });
    $('#imgModalNext').on('click', function(e){ e.preventDefault(); showImg(currentIdx + 1); });
    $('#imgModalZoomIn').on('click', function(){ zoomLevel = Math.min(zoomMax, zoomLevel + zoomStep); applyZoom(); });
    $('#imgModalZoomOut').on('click', function(){ zoomLevel = Math.max(zoomMin, zoomLevel - zoomStep); applyZoom(); });
    $('#imgModalZoomReset').on('click', function(){ zoomLevel = 1; applyZoom(); });
    $('#imgModalViewport').on('wheel', function(e){
        if (!e.ctrlKey && !e.metaKey) return;
        e.preventDefault();
        zoomLevel += (e.originalEvent.deltaY > 0 ? -zoomStep : zoomStep);
        zoomLevel = Math.max(zoomMin, Math.min(zoomMax, zoomLevel));
        applyZoom();
    });
    var isDragging = false, startX, startY, startScrollLeft, startScrollTop;
    $('#imgModalViewport').on('mousedown', function(e){
        if (e.button !== 0) return;
        isDragging = true;
        startX = e.pageX;
        startY = e.pageY;
        startScrollLeft = $(this).scrollLeft();
        startScrollTop = $(this).scrollTop();
        $(this).css('cursor', 'grabbing');
    });
    $(document).on('mousemove', function(e){
        if (!isDragging) return;
        var vp = $('#imgModalViewport');
        vp.scrollLeft(startScrollLeft + startX - e.pageX);
        vp.scrollTop(startScrollTop + startY - e.pageY);
    });
    $(document).on('mouseup', function(){
        if (isDragging) {
            $('#imgModalViewport').css('cursor', 'grab');
            isDragging = false;
        }
    });
    $(document).on('click', '.struct-img-remove', function(e){
        e.preventDefault();
        e.stopPropagation();
        var path = $(this).data('path');
        var type = $(this).data('type');
        var removeEl = type === 'tagging' ? '#taggingImagesRemove' : '#structureImagesRemove';
        $(this).closest('.struct-img-wrapper').remove();
        $('<input>').attr({ type: 'hidden', name: type + '_images_remove[]', value: path }).appendTo(removeEl);
    });
});
</script>";
$content = ob_get_clean();
$pageTitle = $structure ? 'Edit Structure' : 'Add Structure';
$currentPage = 'structure';
require __DIR__ . '/../layout/main.php';
?>
