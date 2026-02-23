<?php ob_start();
$structures = $structures ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>View Profile</h2>
    <div>
        <a href="/profile" class="btn btn-outline-secondary">Back</a>
        <?php if (\Core\Auth::can('edit_profiles')): ?><a href="/profile/edit/<?= (int)$profile->id ?>" class="btn btn-primary">Edit</a><?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">PAPSID</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($profile->papsid ?? '') ?></dd>
            <dt class="col-sm-3">Control Number</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($profile->control_number ?? '-') ?></dd>
            <dt class="col-sm-3">Full Name</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($profile->full_name ?? '-') ?></dd>
            <dt class="col-sm-3">Age</dt>
            <dd class="col-sm-9"><?= isset($profile->age) && $profile->age !== '' && $profile->age !== null ? (int)$profile->age : '-' ?></dd>
            <dt class="col-sm-3">Contact Number</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($profile->contact_number ?? '-') ?></dd>
            <dt class="col-sm-3">Project</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($profile->project_name ?? '-') ?></dd>
        </dl>
    </div>
</div>

<!-- Relevant Information -->
<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Relevant Information</h6></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Residing in the Project Affected Structure?</dt>
            <dd class="col-sm-8"><?= !empty($profile->residing_in_project_affected) ? 'Yes' : 'No' ?></dd>
            <?php if (!empty($profile->residing_in_project_affected_note)): ?>
            <dt class="col-sm-4">Note</dt>
            <dd class="col-sm-8"><?= nl2br(htmlspecialchars($profile->residing_in_project_affected_note)) ?></dd>
            <?php endif; ?>
            <?php $residingAtt = \App\Models\Profile::parseAttachments($profile->residing_in_project_affected_attachments ?? '[]'); if (!empty($residingAtt)): ?>
            <dt class="col-sm-4">Attachments</dt>
            <dd class="col-sm-8"><?php foreach ($residingAtt as $p): ?><a href="<?= htmlspecialchars(\App\Controllers\ProfileController::attachmentUrl($p)) ?>" target="_blank" class="me-2"><?= htmlspecialchars(basename($p)) ?></a><?php endforeach; ?></dd>
            <?php endif; ?>
            <dt class="col-sm-4">Structure owners?</dt>
            <dd class="col-sm-8"><?= !empty($profile->structure_owners) ? 'Yes' : 'No' ?></dd>
            <?php if (!empty($profile->structure_owners_note)): ?>
            <dt class="col-sm-4">Note</dt>
            <dd class="col-sm-8"><?= nl2br(htmlspecialchars($profile->structure_owners_note)) ?></dd>
            <?php endif; ?>
            <?php $ownersAtt = \App\Models\Profile::parseAttachments($profile->structure_owners_attachments ?? '[]'); if (!empty($ownersAtt)): ?>
            <dt class="col-sm-4">Attachments</dt>
            <dd class="col-sm-8"><?php foreach ($ownersAtt as $p): ?><a href="<?= htmlspecialchars(\App\Controllers\ProfileController::attachmentUrl($p)) ?>" target="_blank" class="me-2"><?= htmlspecialchars(basename($p)) ?></a><?php endforeach; ?></dd>
            <?php endif; ?>
            <?php if (empty($profile->structure_owners) && (trim($profile->if_not_structure_owner_what ?? '') !== '' || !empty(\App\Models\Profile::parseAttachments($profile->if_not_structure_owner_attachments ?? '[]')))): ?>
            <dt class="col-sm-4">If not Structure owner, what are they?</dt>
            <dd class="col-sm-8"><?= nl2br(htmlspecialchars($profile->if_not_structure_owner_what ?? '')) ?>
                <?php $ifNotAtt = \App\Models\Profile::parseAttachments($profile->if_not_structure_owner_attachments ?? '[]'); foreach ($ifNotAtt as $p): ?><a href="<?= htmlspecialchars(\App\Controllers\ProfileController::attachmentUrl($p)) ?>" target="_blank" class="me-2 d-block"><?= htmlspecialchars(basename($p)) ?></a><?php endforeach; ?>
            </dd>
            <?php endif; ?>
        </dl>
    </div>
</div>

<!-- Additional Information -->
<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Additional Information</h6></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Do they own property somewhere else?</dt>
            <dd class="col-sm-8"><?= !empty($profile->own_property_elsewhere) ? 'Yes' : 'No' ?></dd>
            <?php if (!empty($profile->own_property_elsewhere_note)): ?>
            <dt class="col-sm-4">Note</dt>
            <dd class="col-sm-8"><?= nl2br(htmlspecialchars($profile->own_property_elsewhere_note)) ?></dd>
            <?php endif; ?>
            <?php $ownAtt = \App\Models\Profile::parseAttachments($profile->own_property_elsewhere_attachments ?? '[]'); if (!empty($ownAtt)): ?>
            <dt class="col-sm-4">Attachments</dt>
            <dd class="col-sm-8"><?php foreach ($ownAtt as $p): ?><a href="<?= htmlspecialchars(\App\Controllers\ProfileController::attachmentUrl($p)) ?>" target="_blank" class="me-2"><?= htmlspecialchars(basename($p)) ?></a><?php endforeach; ?></dd>
            <?php endif; ?>
            <dt class="col-sm-4">Have they availed previously of any government socialized housing program?</dt>
            <dd class="col-sm-8"><?= !empty($profile->availed_government_housing) ? 'Yes' : 'No' ?></dd>
            <?php if (!empty($profile->availed_government_housing_note)): ?>
            <dt class="col-sm-4">Note</dt>
            <dd class="col-sm-8"><?= nl2br(htmlspecialchars($profile->availed_government_housing_note)) ?></dd>
            <?php endif; ?>
            <?php $availAtt = \App\Models\Profile::parseAttachments($profile->availed_government_housing_attachments ?? '[]'); if (!empty($availAtt)): ?>
            <dt class="col-sm-4">Attachments</dt>
            <dd class="col-sm-8"><?php foreach ($availAtt as $p): ?><a href="<?= htmlspecialchars(\App\Controllers\ProfileController::attachmentUrl($p)) ?>" target="_blank" class="me-2"><?= htmlspecialchars(basename($p)) ?></a><?php endforeach; ?></dd>
            <?php endif; ?>
            <dt class="col-sm-4">HH Income</dt>
            <dd class="col-sm-8"><?= isset($profile->hh_income) && $profile->hh_income !== '' && $profile->hh_income !== null ? htmlspecialchars($profile->hh_income) : '-' ?></dd>
        </dl>
    </div>
</div>

<?php if (\Core\Auth::can('view_structure') && !empty($profile->structure_owners)): ?>
<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Structures</h6></div>
    <div class="card-body">
        <?php if (empty($structures)): ?>
        <p class="text-muted small mb-0">No structures linked to this profile.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Structure ID</th><th>Tag #</th><th>Description</th><th>Images</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($structures as $s):
                        $taggingImgs = \App\Models\Structure::parseImages($s->tagging_images ?? '[]');
                        $structImgs = \App\Models\Structure::parseImages($s->structure_images ?? '[]');
                        $allImgs = array_merge($taggingImgs, $structImgs);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($s->strid ?? '') ?></td>
                        <td><?= htmlspecialchars($s->structure_tag ?? '') ?></td>
                        <td><?= htmlspecialchars(\mb_substr($s->description ?? '', 0, 60)) ?><?= \mb_strlen($s->description ?? '') > 60 ? '...' : '' ?></td>
                        <td>
                            <?php if (!empty($allImgs)): ?>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($allImgs as $img): $imgUrl = \App\Controllers\StructureController::imageUrl($img); ?>
                                <a href="#" class="profile-view-struct-img" data-src="<?= htmlspecialchars($imgUrl) ?>" style="cursor:pointer;display:inline-block;" title="Click for full size"><img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="rounded" style="width:36px;height:36px;object-fit:cover;" loading="lazy" onerror="this.style.display='none'"></a>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td>
                            <?php if (\Core\Auth::can('view_structure')): ?><button type="button" class="btn btn-sm btn-outline-secondary profile-view-structure-btn" data-id="<?= (int)$s->id ?>">View</button><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="profileViewStructImgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary py-2">
                <h5 class="modal-title text-light">Image Preview</h5>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-light" id="profileViewImgZoomOut" title="Zoom out">−</button>
                        <button type="button" class="btn btn-outline-light" id="profileViewImgZoomReset" title="Reset">1:1</button>
                        <button type="button" class="btn btn-outline-light" id="profileViewImgZoomIn" title="Zoom in">+</button>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 overflow-hidden position-relative" id="profileViewImgViewport" style="min-height:70vh;">
                <div id="profileViewImgLoading" class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark d-none" style="z-index:5;"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div></div>
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" id="profileViewImgWrapper" style="cursor:grab;touch-action:none;transform-origin:center center;">
                    <img id="profileViewStructImgSrc" src="" alt="" draggable="false" style="user-select:none;pointer-events:none;display:block;max-width:100%;max-height:70vh;object-fit:contain;">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profileViewStructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">View Structure</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="profileViewStructureBody"></div>
        </div>
    </div>
</div>
<?php
$baseUrl = defined('BASE_URL') && BASE_URL ? BASE_URL : '';
$scripts = '<script>
var baseUrl = '.json_encode($baseUrl).';
function imgUrl(path) {
    if (!path) return "";
    var re = new RegExp("^/uploads/structure/(tagging|images)/([a-zA-Z0-9_.-]+)$");
    if (re.test(path)) {
        var m = path.match(new RegExp("(tagging|images)/([a-zA-Z0-9_.-]+)"));
        return baseUrl + \'/serve/structure?subdir=\' + encodeURIComponent(m[1]) + \'&file=\' + encodeURIComponent(m[2]);
    }
    return baseUrl + (path[0]===\'/\' ? path : \'/\'+path);
}
function escapeHtml(t){ return $(\'<div>\').text(t||\'\').html(); }
function renderImgs(arr) {
    if (!arr || !arr.length) return \'-\';
    var h = \'\';
    arr.forEach(function(p){ var u=imgUrl(p); if(u) h += \'<a href="#" class="profile-view-struct-img" data-src="\'+u.replace(/"/g,"&quot;")+\'" style="cursor:pointer;margin:2px;"><img src="\'+u+\'" alt="" class="rounded" style="width:60px;height:60px;object-fit:cover;" onerror="this.style.display=none"></a>\'; });
    return h || \'-\';
}
$(function(){
    var imgLightbox = (function(){
        var img = document.getElementById(\'profileViewStructImgSrc\');
        var wrap = document.getElementById(\'profileViewImgWrapper\');
        var viewport = document.getElementById(\'profileViewImgViewport\');
        if (!img || !wrap || !viewport) return {};
        var scale = 1, tx = 0, ty = 0;
        var minScale = 0.25, maxScale = 8, step = 0.25;
        var isDragging = false, lastX = 0, lastY = 0;
        function applyTransform(){ wrap.style.transform = \'translate(\'+tx+\'px,\'+ty+\'px) scale(\'+scale+\')\'; wrap.style.cursor = scale > 1 ? (isDragging ? \'grabbing\' : \'grab\') : \'grab\'; }
        function zoomAt(clientX, clientY, delta){
            var rect = viewport.getBoundingClientRect();
            var cx = clientX - rect.left - rect.width/2, cy = clientY - rect.top - rect.height/2;
            var s0 = scale; scale = Math.max(minScale, Math.min(maxScale, scale + delta));
            tx = tx - cx * (scale/s0 - 1); ty = ty - cy * (scale/s0 - 1);
            applyTransform();
        }
        function reset(){ scale = 1; tx = 0; ty = 0; applyTransform(); }
        wrap.addEventListener(\'mousedown\', function(e){ if (scale > 1){ isDragging = true; lastX = e.clientX; lastY = e.clientY; } });
        document.addEventListener(\'mousemove\', function(e){ if (isDragging){ tx += e.clientX - lastX; ty += e.clientY - lastY; lastX = e.clientX; lastY = e.clientY; applyTransform(); } });
        document.addEventListener(\'mouseup\', function(){ isDragging = false; wrap.style.cursor = scale > 1 ? \'grab\' : \'grab\'; });
        viewport.addEventListener(\'wheel\', function(e){ e.preventDefault(); zoomAt(e.clientX, e.clientY, e.deltaY > 0 ? -step : step); }, { passive: false });
        document.getElementById(\'profileViewImgZoomIn\') && document.getElementById(\'profileViewImgZoomIn\').addEventListener(\'click\', function(){ zoomAt(viewport.getBoundingClientRect().left + viewport.offsetWidth/2, viewport.getBoundingClientRect().top + viewport.offsetHeight/2, step); });
        document.getElementById(\'profileViewImgZoomOut\') && document.getElementById(\'profileViewImgZoomOut\').addEventListener(\'click\', function(){ zoomAt(viewport.getBoundingClientRect().left + viewport.offsetWidth/2, viewport.getBoundingClientRect().top + viewport.offsetHeight/2, -step); });
        document.getElementById(\'profileViewImgZoomReset\') && document.getElementById(\'profileViewImgZoomReset\').addEventListener(\'click\', reset);
        return { reset: reset };
    })();
    $(document).on(\'click\',\'.profile-view-struct-img\',function(e){ e.preventDefault(); var fullSrc=$(this).attr(\'data-src\')||$(this).find(\'img\').attr(\'src\'); if(fullSrc){ var $img=$(\'#profileViewStructImgSrc\'); var $loading=$(\'#profileViewImgLoading\'); $img.attr(\'src\',\'\'); $img.hide(); $loading.removeClass(\'d-none\'); imgLightbox.reset && imgLightbox.reset(); $(\'#profileViewStructImgModal\').modal(\'show\'); $img.one(\'load\',function(){ $loading.addClass(\'d-none\'); $img.show(); }).attr(\'src\',fullSrc); if($img[0].complete) $img.trigger(\'load\'); }});
    $(document).on(\'click\',\'.profile-view-structure-btn\',function(){
        var id=$(this).data(\'id\');
        $.get(\'/api/structure/\'+id,function(s){
            var tagImgs=[]; try{ tagImgs=JSON.parse(s.tagging_images||\'[]\'); }catch(e){}
            var structImgs=[]; try{ structImgs=JSON.parse(s.structure_images||\'[]\'); }catch(e){}
            var html=\'<dl class="row mb-0"><dt class="col-sm-4">Structure ID</dt><dd class="col-sm-8">\'+escapeHtml(s.strid)+\'</dd>\';
            html+=\'<dt class="col-sm-4">Paps/Owner</dt><dd class="col-sm-8">\'+escapeHtml(s.owner_name||s.owner_id||\'-\')+\'</dd>\';
            html+=\'<dt class="col-sm-4">Structure Tag #</dt><dd class="col-sm-8">\'+escapeHtml(s.structure_tag)+\'</dd>\';
            html+=\'<dt class="col-sm-4">Description</dt><dd class="col-sm-8">\'+escapeHtml(s.description).replace(/\\n/g,"<br>")+\'</dd>\';
            html+=\'<dt class="col-sm-4">Tagging Images</dt><dd class="col-sm-8">\'+renderImgs(tagImgs)+\'</dd>\';
            html+=\'<dt class="col-sm-4">Structure Images</dt><dd class="col-sm-8">\'+renderImgs(structImgs)+\'</dd>\';
            html+=\'<dt class="col-sm-4">Other Details</dt><dd class="col-sm-8">\'+escapeHtml(s.other_details).replace(/\\n/g,"<br>")+\'</dd></dl>\';
            $(\'#profileViewStructureBody\').html(html);
            $(\'#profileViewStructureModal\').modal(\'show\');
        });
    });
});
</script>';
?>
<?php endif; ?>
<?php $content = ob_get_clean(); $pageTitle = 'View Profile'; $currentPage = 'profile'; require __DIR__ . '/../layout/main.php'; ?>
