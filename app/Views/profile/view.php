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
            <dd class="col-sm-9"><?= (int)($profile->age ?? 0) ?: '-' ?></dd>
            <dt class="col-sm-3">Contact Number</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($profile->contact_number ?? '-') ?></dd>
            <dt class="col-sm-3">Project</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($profile->project_name ?? '-') ?></dd>
        </dl>
    </div>
</div>

<?php if (\Core\Auth::can('view_structure')): ?>
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
                                <?php foreach (array_slice($allImgs, 0, 6) as $img): $imgUrl = \App\Controllers\StructureController::imageUrl($img); ?>
                                <a href="#" class="profile-view-struct-img" data-src="<?= htmlspecialchars($imgUrl) ?>" style="cursor:pointer;display:inline-block;"><img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="rounded" style="width:36px;height:36px;object-fit:cover;" onerror="this.style.display='none'"></a>
                                <?php endforeach; ?>
                                <?php if (count($allImgs) > 6): ?><span class="text-muted small">+<?= count($allImgs) - 6 ?></span><?php endif; ?>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Image Preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center"><img id="profileViewStructImgSrc" src="" alt="" class="img-fluid" style="max-height:80vh;"></div>
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
$scripts = "<script>
var baseUrl = ".json_encode($baseUrl).";
function imgUrl(path) {
    if (!path) return '';
    if (/^\\/uploads\\/structure\\/(tagging|images)\\/([a-zA-Z0-9_.-]+)$/.test(path)) {
        var m = path.match(/(tagging|images)\\/([a-zA-Z0-9_.-]+)/);
        return baseUrl + '/serve/structure?subdir=' + encodeURIComponent(m[1]) + '&file=' + encodeURIComponent(m[2]);
    }
    return baseUrl + (path[0]==='/' ? path : '/'+path);
}
function escapeHtml(t){ return $('<div>').text(t||'').html(); }
function renderImgs(arr) {
    if (!arr || !arr.length) return '-';
    var h = '';
    arr.forEach(function(p){ var u=imgUrl(p); if(u) h += '<a href=\"#\" class=\"profile-view-struct-img\" data-src=\"'+u.replace(/\"/g,'&quot;')+'\" style=\"cursor:pointer;margin:2px;\"><img src=\"'+u+'\" alt=\"\" class=\"rounded\" style=\"width:60px;height:60px;object-fit:cover;\" onerror=\"this.style.display=none\"></a>'; });
    return h || '-';
}
$(function(){
    $(document).on('click','.profile-view-struct-img',function(e){ e.preventDefault(); var s=$(this).attr('data-src')||$(this).find('img').attr('src'); if(s){ $('#profileViewStructImgSrc').attr('src',s); $('#profileViewStructImgModal').modal('show'); }});
    $(document).on('click','.profile-view-structure-btn',function(){
        var id=$(this).data('id');
        $.get('/api/structure/'+id,function(s){
            var tagImgs=[]; try{ tagImgs=JSON.parse(s.tagging_images||'[]'); }catch(e){}
            var structImgs=[]; try{ structImgs=JSON.parse(s.structure_images||'[]'); }catch(e){}
            var html='<dl class=\"row mb-0\"><dt class=\"col-sm-4\">Structure ID</dt><dd class=\"col-sm-8\">'+escapeHtml(s.strid)+'</dd>';
            html+='<dt class=\"col-sm-4\">Paps/Owner</dt><dd class=\"col-sm-8\">'+escapeHtml(s.owner_name||s.owner_id||'-')+'</dd>';
            html+='<dt class=\"col-sm-4\">Structure Tag #</dt><dd class=\"col-sm-8\">'+escapeHtml(s.structure_tag)+'</dd>';
            html+='<dt class=\"col-sm-4\">Description</dt><dd class=\"col-sm-8\">'+escapeHtml(s.description).replace(/\\n/g,'<br>')+'</dd>';
            html+='<dt class=\"col-sm-4\">Tagging Images</dt><dd class=\"col-sm-8\">'+renderImgs(tagImgs)+'</dd>';
            html+='<dt class=\"col-sm-4\">Structure Images</dt><dd class=\"col-sm-8\">'+renderImgs(structImgs)+'</dd>';
            html+='<dt class=\"col-sm-4\">Other Details</dt><dd class=\"col-sm-8\">'+escapeHtml(s.other_details).replace(/\\n/g,'<br>')+'</dd></dl>';
            $('#profileViewStructureBody').html(html);
            $('#profileViewStructureModal').modal('show');
        });
    });
});
</script>";
?>
<?php endif; ?>
<?php $content = ob_get_clean(); $pageTitle = 'View Profile'; $currentPage = 'profile'; require __DIR__ . '/../layout/main.php'; ?>
