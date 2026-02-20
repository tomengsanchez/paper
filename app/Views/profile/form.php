<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $profile ? 'Edit Profile' : 'Add Profile' ?></h2>
    <a href="/profile" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $profile ? "/profile/update/{$profile->id}" : '/profile/store' ?>">
            <div class="mb-3">
                <label class="form-label">PAPSID</label>
                <input type="text" name="papsid" class="form-control" value="<?= htmlspecialchars(!empty($profile) ? $profile->papsid : ($papsid ?? '')) ?>" required readonly>
                <small class="text-muted">Auto-generated (PAPS-YEARMONTH0000000001)</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Control Number</label>
                <input type="text" name="control_number" class="form-control" value="<?= htmlspecialchars($profile->control_number ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($profile->full_name ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Age</label>
                <input type="number" name="age" class="form-control" value="<?= (int)($profile->age ?? 0) ?>" min="0">
            </div>
            <div class="mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($profile->contact_number ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Project</label>
                <select name="project_id" id="projectSelect" class="form-select" style="width:100%">
                    <option value="">-- Select Project --</option>
                    <?php if (!empty($profile->project_id)): ?>
                    <option value="<?= (int)$profile->project_id ?>" selected><?= htmlspecialchars($profile->project_name ?? '') ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>

<?php if (!empty($profile) && \Core\Auth::canAny(['view_structure', 'add_structure', 'edit_structure'])): ?>
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Structures</h6>
        <button type="button" class="btn btn-sm btn-primary" id="btnAddStructure">+ Add Structure</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Structure ID</th><th>Tag #</th><th>Description</th><th>Images</th><th>Actions</th></tr></thead>
                <tbody id="structuresListBody"></tbody>
            </table>
        </div>
        <p id="structuresEmpty" class="text-muted small mb-0 mt-2">No structures yet. Click Add Structure to create one.</p>
    </div>
</div>

<!-- Image preview modal -->
<div class="modal fade" id="profileStructImgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Image Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center position-relative">
                <img id="profileStructImgSrc" src="" alt="" class="img-fluid" style="max-height:80vh;">
            </div>
        </div>
    </div>
</div>

<!-- Structures Add/Edit lightbox modal -->
<div class="modal fade" id="structuresModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="structuresModalTitle">Add Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="structuresFormSection">
                    <?php
                    $structure = null;
                    $strid = '';
                    $fixedOwnerId = $profile->id;
                    $formId = 'structureFormLightbox';
                    $submitBtnId = 'structureFormSubmit';
                    require __DIR__ . '/../structure/_form_embed.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
$profileIdForStructures = !empty($profile) ? (int)$profile->id : 0;
$baseUrl = defined('BASE_URL') && BASE_URL ? BASE_URL : '';
$scripts = "
<script>
$(function(){
    $('#projectSelect').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '/api/projects',
            dataType: 'json',
            delay: 250,
            data: function(params){ return { q: params.term || '' }; },
            processResults: function(data){ return { results: data.map(function(r){ return { id: r.id, text: r.name }; }) }; }
        },
        minimumInputLength: 0,
        placeholder: 'Search project...',
        allowClear: true
    });
    var profileId = " . $profileIdForStructures . ";
    var baseUrl = " . json_encode($baseUrl) . ";
    function imgUrl(path) {
        if (!path) return '';
        if (/^\\/uploads\\/structure\\/(tagging|images)\\/([a-zA-Z0-9_.-]+)$/.test(path)) {
            var m = path.match(/(tagging|images)\\/([a-zA-Z0-9_.-]+)/);
            return baseUrl + '/serve/structure?subdir=' + encodeURIComponent(m[1]) + '&file=' + encodeURIComponent(m[2]);
        }
        return baseUrl + (path[0]==='/' ? path : '/'+path);
    }
    function loadStructures() {
        if (!profileId) return;
        $.get('/api/profile/' + profileId + '/structures', function(data) {
            var tbody = $('#structuresListBody');
            tbody.empty();
            $('#structuresEmpty').toggle(!data || data.length === 0);
            if (data && data.length) {
                data.forEach(function(s) {
                    var imgs = [];
                    try { imgs = imgs.concat(JSON.parse(s.tagging_images || '[]')); } catch(e){}
                    try { imgs = imgs.concat(JSON.parse(s.structure_images || '[]')); } catch(e){}
                    var urls = imgs.map(function(p){ return imgUrl(p); }).filter(Boolean);
                    var imgsHtml = '';
                    urls.slice(0,6).forEach(function(u){ var safe=u.replace(/\"/g,'&quot;').replace(/&/g,'&amp;'); imgsHtml += '<a href=\"#\" class=\"struct-img-thumb\" data-src=\"'+safe+'\" style=\"cursor:pointer;margin:1px;display:inline-block;\"><img src=\"'+safe+'\" alt=\"\" class=\"rounded\" style=\"width:36px;height:36px;object-fit:cover;\" onerror=\"this.style.display=\\'none\\'\"></a>'; });
                    if (urls.length > 6) imgsHtml += '<span class=\"text-muted small\" title=\"+'+(urls.length-6)+' more\">+'+(urls.length-6)+'</span>';
                    tbody.append('<tr><td>' + (s.strid || '') + '</td><td>' + $('<div>').text(s.structure_tag || '').html() + '</td><td>' + $('<div>').text((s.description || '').substring(0,60)).html() + ((s.description||'').length>60?'...':'') + '</td><td>' + (imgsHtml || '-') + '</td><td><button type=\"button\" class=\"btn btn-sm btn-outline-primary structure-edit\" data-id=\"'+s.id+'\">Edit</button> <button type=\"button\" class=\"btn btn-sm btn-outline-danger structure-delete\" data-id=\"'+s.id+'\">Delete</button></td></tr>');
                });
            }
        });
    }
    loadStructures();
    $('#btnAddStructure').on('click', function() {
        $('#structureFormLightbox')[0].reset();
        $('input[name=owner_id]').val(profileId);
        $('input[name=structure_id]').val('');
        $('input[name=strid]').val('').attr('placeholder', 'auto-generated');
        $('#embedTaggingImages').empty();
        $('#embedStructureImages').empty();
        $('#structuresModalTitle').text('Add Structure');
        $.get('/api/structure/next-strid', function(r) { $('input[name=strid]').val(r.strid || ''); });
        new bootstrap.Modal(document.getElementById('structuresModal')).show();
    });
    function renderImgThumbs(urls) {
        if (!urls || !urls.length) return '';
        var html = '';
        urls.forEach(function(u) {
            var safe = u.replace(/\"/g,'&quot;').replace(/&/g,'&amp;');
            html += '<a href=\"#\" class=\"struct-img-thumb\" data-src=\"'+safe+'\" style=\"cursor:pointer;\"><img src=\"'+safe+'\" alt=\"\" class=\"rounded\" style=\"width:40px;height:40px;object-fit:cover;\" onerror=\"this.style.display=\\'none\\'\"></a>';
        });
        return html;
    }
    $(document).on('click', '.structure-edit', function() {
        var id = $(this).data('id');
        $.get('/api/structure/' + id, function(s) {
            $('input[name=owner_id]').val(profileId);
            $('input[name=structure_id]').val(s.id);
            $('input[name=strid]').val(s.strid || '');
            $('input[name=structure_tag]').val(s.structure_tag || '');
            $('textarea[name=description]').val(s.description || '');
            $('textarea[name=other_details]').val(s.other_details || '');
            $('#structureFormLightbox input[type=file]').val('');
            var taggingImgs = []; try { taggingImgs = JSON.parse(s.tagging_images || '[]'); } catch(e){}
            var structureImgs = []; try { structureImgs = JSON.parse(s.structure_images || '[]'); } catch(e){}
            $('#embedTaggingImages').html(renderImgThumbs(taggingImgs.map(imgUrl)));
            $('#embedStructureImages').html(renderImgThumbs(structureImgs.map(imgUrl)));
            $('#structuresModalTitle').text('Edit Structure');
            new bootstrap.Modal(document.getElementById('structuresModal')).show();
        });
    });
    $('#structureFormCancel').on('click', function() { $('#structuresModal').modal('hide'); });
    $(document).on('click', '.structure-delete', function() {
        if (!confirm('Delete this structure?')) return;
        var id = $(this).data('id');
        $.post('/api/structure/delete/' + id).done(function() { loadStructures(); });
    });
    $(document).on('click', '.struct-img-thumb', function(e) {
        e.preventDefault();
        var src = $(this).attr('data-src') || $(this).find('img').attr('src');
        if (src) { $('#profileStructImgSrc').attr('src', src); $('#profileStructImgModal').modal('show'); }
    });
    $('#structureFormLightbox').on('submit', function(e) {
        e.preventDefault();
        var fid = $('input[name=structure_id]').val();
        var url = fid ? '/api/structure/update/' + fid : '/api/structure/store';
        var fd = new FormData(this);
        $.ajax({ url: url, type: 'POST', data: fd, processData: false, contentType: false })
            .done(function() { loadStructures(); $('#structuresModal').modal('hide'); $('#structureFormLightbox')[0].reset(); })
            .fail(function(x) { alert('Error: ' + (x.responseJSON && x.responseJSON.error || x.statusText || 'Failed')); });
    });
});
</script>";
$content = ob_get_clean();
$pageTitle = $profile ? 'Edit Profile' : 'Add Profile';
$currentPage = 'profile';
require __DIR__ . '/../layout/main.php';
?>
