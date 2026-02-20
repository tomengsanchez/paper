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
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary py-2">
                <h5 class="modal-title text-light">Image Preview</h5>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-light" id="structViewImgZoomOut" title="Zoom out">−</button>
                        <button type="button" class="btn btn-outline-light" id="structViewImgZoomReset" title="Reset">1:1</button>
                        <button type="button" class="btn btn-outline-light" id="structViewImgZoomIn" title="Zoom in">+</button>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 overflow-hidden position-relative" id="structViewImgViewport" style="min-height:70vh;">
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" id="structViewImgWrapper" style="cursor:grab;touch-action:none;transform-origin:center center;">
                    <img id="structViewImgSrc" src="" alt="" draggable="false" style="user-select:none;pointer-events:none;display:block;max-width:100%;max-height:70vh;object-fit:contain;">
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$scripts = '<script>
(function(){
var wrap=document.getElementById("structViewImgWrapper");
var viewport=document.getElementById("structViewImgViewport");
var img=document.getElementById("structViewImgSrc");
if(!wrap||!viewport||!img)return;
var scale=1,tx=0,ty=0,minScale=0.25,maxScale=8,step=0.25,isDragging=false,lastX=0,lastY=0;
function applyTransform(){wrap.style.transform="translate("+tx+"px,"+ty+"px) scale("+scale+")";wrap.style.cursor=scale>1?(isDragging?"grabbing":"grab"):"grab";}
function zoomAt(cx,cy,d){var rect=viewport.getBoundingClientRect();var px=cx-rect.left-rect.width/2,py=cy-rect.top-rect.height/2;var s0=scale;scale=Math.max(minScale,Math.min(maxScale,scale+d));tx=tx-px*(scale/s0-1);ty=ty-py*(scale/s0-1);applyTransform();}
function reset(){scale=1;tx=0;ty=0;applyTransform();}
wrap.addEventListener("mousedown",function(e){if(scale>1){isDragging=true;lastX=e.clientX;lastY=e.clientY;}});
document.addEventListener("mousemove",function(e){if(isDragging){tx+=e.clientX-lastX;ty+=e.clientY-lastY;lastX=e.clientX;lastY=e.clientY;applyTransform();}});
document.addEventListener("mouseup",function(){isDragging=false;});
viewport.addEventListener("wheel",function(e){e.preventDefault();zoomAt(e.clientX,e.clientY,e.deltaY>0?-step:step);},{passive:false});
document.getElementById("structViewImgZoomIn")&&document.getElementById("structViewImgZoomIn").addEventListener("click",function(){var r=viewport.getBoundingClientRect();zoomAt(r.left+r.width/2,r.top+r.height/2,step);});
document.getElementById("structViewImgZoomOut")&&document.getElementById("structViewImgZoomOut").addEventListener("click",function(){var r=viewport.getBoundingClientRect();zoomAt(r.left+r.width/2,r.top+r.height/2,-step);});
document.getElementById("structViewImgZoomReset")&&document.getElementById("structViewImgZoomReset").addEventListener("click",reset);
$(function(){
$(document).on("click",".struct-view-img",function(e){e.preventDefault();var s=$(this).attr("data-src")||$(this).find("img").attr("src");if(s){img.setAttribute("src",s);reset();$("#structViewImgModal").modal("show");}});
});
})();
</script>';
$content = ob_get_clean();
$pageTitle = 'View Structure';
$currentPage = 'structure';
require __DIR__ . '/../layout/main.php';
?>
