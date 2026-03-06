<?php
/** @var ?object $form @var array $fields */
$isEdit = !empty($form) && !empty($form->id);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><?= $isEdit ? 'Edit Socio Economic Form' : 'Add Socio Economic Form' ?></h2>
        <?php if ($isEdit): ?>
        <div class="text-muted small">Form ID: <?= (int)$form->id ?></div>
        <?php endif; ?>
    </div>
    <a href="/forms/socio-economic" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>
<?php if (!empty($_SESSION['flash_error'] ?? '')): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
<form method="post" action="<?= $isEdit ? '/forms/socio-economic/update/' . (int)$form->id : '/forms/socio-economic/store' ?>">
    <?= \Core\Csrf::field() ?>
    <div class="card mb-4">
        <div class="card-header">
            <strong>Form Details</strong>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           value="<?= htmlspecialchars($form->title ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Project</label>
                    <select id="projectSelect" name="project_id" class="form-select">
                        <?php if (!empty($form->project_id) && !empty($form->project_name)): ?>
                        <option value="<?= (int)$form->project_id ?>" selected><?= htmlspecialchars($form->project_name) ?></option>
                        <?php else: ?>
                        <option value="">-- Search Project --</option>
                        <?php endif; ?>
                    </select>
                    <div class="form-text">Searchable dropdown of projects (Select2 + /api/projects).</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-control" placeholder="Optional description of this Socio Economic form"><?= htmlspecialchars($form->description ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Form Fields</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addFieldRow">Add Field</button>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Define the fields that make up this Socio Economic form. Each field can have its own type, description,
                condition JSON, and can be marked as repeatable. Use the <em>Custom HTML</em> type to inject arbitrary HTML blocks.
            </p>
            <div id="fieldsContainer">
                <?php
                $index = 0;
                foreach ($fields as $f):
                    $idx = $index++;
                ?>
                <div class="border rounded p-3 mb-3 field-row">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Field</strong>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-field-row">Remove</button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Field name (key)</label>
                            <input type="text" name="fields[<?= $idx ?>][name]" class="form-control form-control-sm"
                                   placeholder="e.g. household_income"
                                   value="<?= htmlspecialchars($f->name ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Description / label</label>
                            <input type="text" name="fields[<?= $idx ?>][description]" class="form-control form-control-sm"
                                   placeholder="Label shown to users"
                                   value="<?= htmlspecialchars($f->description ?? '') ?>">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label class="form-label small">Field type</label>
                            <select name="fields[<?= $idx ?>][type]" class="form-select form-select-sm">
                                <?php
                                $type = $f->type ?? 'text';
                                $types = [
                                    'text' => 'Text',
                                    'textarea' => 'Textarea',
                                    'number' => 'Number',
                                    'date' => 'Date',
                                    'select' => 'Select',
                                    'checkbox' => 'Checkbox',
                                    'paps' => 'PAPS Profile (searchable)',
                                    'custom_html' => 'Custom HTML',
                                ];
                                foreach ($types as $k => $label):
                                ?>
                                <option value="<?= htmlspecialchars($k) ?>" <?= $type === $k ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small d-block">Flags</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox"
                                       name="fields[<?= $idx ?>][is_required]" value="1"
                                       <?= !empty($f->is_required) ? 'checked' : '' ?>>
                                <label class="form-check-label small">Required</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox"
                                       name="fields[<?= $idx ?>][is_repeatable]" value="1"
                                       <?= !empty($f->is_repeatable) ? 'checked' : '' ?>>
                                <label class="form-check-label small">Repeatable</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Sort order</label>
                            <input type="number" name="fields[<?= $idx ?>][sort_order]" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars((string)($f->sort_order ?? 0)) ?>">
                        </div>
                        <?php
                        $optionsRaw = '';
                        if (!empty($f->settings_json)) {
                            $cfg = json_decode($f->settings_json, true);
                            if (!empty($cfg['options']) && is_array($cfg['options'])) {
                                $lines = [];
                                foreach ($cfg['options'] as $opt) {
                                    $val = isset($opt['value']) ? (string)$opt['value'] : '';
                                    $lab = isset($opt['label']) ? (string)$opt['label'] : $val;
                                    if ($val === '') {
                                        continue;
                                    }
                                    $lines[] = $val === $lab ? $val : ($val . '|' . $lab);
                                }
                                $optionsRaw = implode("\n", $lines);
                            }
                        }
                        ?>
                        <div class="col-md-4 mt-2">
                            <label class="form-label small">
                                Options (for Select/Combo)
                            </label>
                            <textarea name="fields[<?= $idx ?>][options_raw]" rows="3" class="form-control form-control-sm"
                                      placeholder="One per line, e.g.&#10;yes|Yes&#10;no|No"><?= htmlspecialchars($optionsRaw) ?></textarea>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label small d-flex justify-content-between align-items-center">
                                <span>
                                    Condition (JSON)
                                    <span class="text-muted">(optional)</span>
                                </span>
                                <a href="/forms/condition-json-help" target="_blank" class="small text-decoration-none">Guide</a>
                            </label>
                            <textarea name="fields[<?= $idx ?>][condition_json]" rows="4" class="form-control form-control-sm code-json-editor"
                                      placeholder='e.g. {"logic":"AND","rules":[{"field":"has_dependents","operator":"equals","value":"yes"}]}'><?= htmlspecialchars($f->condition_json ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label small">
                                Custom HTML (for Custom HTML type)
                            </label>
                            <textarea name="fields[<?= $idx ?>][custom_html]" rows="2" class="form-control form-control-sm"
                                      placeholder="Optional raw HTML block"><?= htmlspecialchars($f->custom_html ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <template id="fieldRowTemplate">
                <div class="border rounded p-3 mb-3 field-row">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Field</strong>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-field-row">Remove</button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Field name (key)</label>
                            <input type="text" name="__NAME__[name]" class="form-control form-control-sm" placeholder="e.g. household_income">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Description / label</label>
                            <input type="text" name="__NAME__[description]" class="form-control form-control-sm" placeholder="Label shown to users">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label class="form-label small">Field type</label>
                            <select name="__NAME__[type]" class="form-select form-select-sm">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="select">Select</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="paps">PAPS Profile (searchable)</option>
                                <option value="custom_html">Custom HTML</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small d-block">Flags</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="__NAME__[is_required]" value="1">
                                <label class="form-check-label small">Required</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="__NAME__[is_repeatable]" value="1">
                                <label class="form-check-label small">Repeatable</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Sort order</label>
                            <input type="number" name="__NAME__[sort_order]" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-md-4 mt-2">
                            <label class="form-label small">
                                Options (for Select/Combo)
                            </label>
                            <textarea name="__NAME__[options_raw]" rows="3" class="form-control form-control-sm"
                                      placeholder="One per line, e.g.&#10;yes|Yes&#10;no|No"></textarea>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label small d-flex justify-content-between align-items-center">
                                <span>Condition (JSON)</span>
                                <a href="/forms/condition-json-help" target="_blank" class="small text-decoration-none">Guide</a>
                            </label>
                            <textarea name="__NAME__[condition_json]" rows="4" class="form-control form-control-sm code-json-editor"
                                      placeholder='e.g. {\"logic\":\"AND\",\"rules\":[{\"field\":\"has_dependents\",\"operator\":\"equals\",\"value\":\"yes\"}]}'>{
  "logic": "AND",
  "action": "show",
  "rules": [
    {
      "field": "other_field_key",
      "operator": "equals",
      "value": "yes"
    }
  ]
}</textarea>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label small">Custom HTML (for Custom HTML type)</label>
                            <textarea name="__NAME__[custom_html]" rows="2" class="form-control form-control-sm"
                                      placeholder="Optional raw HTML block"></textarea>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Form' ?></button>
        </div>
    </div>
</form>
<?php
$scripts = "
<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.css\">
<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/theme/material.min.css\">
<script src=\"https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.js\"></script>
<script src=\"https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/javascript/javascript.min.js\"></script>
<script>
$(function(){
    // Project search (Select2)
    $('#projectSelect').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '/api/projects',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data){
                return {
                    results: data.map(function(r){
                        return { id: r.id, text: r.name };
                    })
                };
            }
        },
        minimumInputLength: 0,
        placeholder: 'Search project...',
        allowClear: true
    });

    // Dynamic field rows
    var fieldIndex = " . (int)$index . ";
    $('#addFieldRow').on('click', function(){
        var t = document.getElementById('fieldRowTemplate');
        if (!t) return;
        var clone = t.innerHTML;
        var namePrefix = 'fields[' + fieldIndex + ']';
        clone = clone.replace(/__NAME__/g, namePrefix);
        var container = $('#fieldsContainer');
        container.append(clone);
        fieldIndex++;

        // Turn any new Condition (JSON) textarea into a CodeMirror instance
        if (typeof window.initConditionEditors === 'function') {
            window.initConditionEditors(container[0]);
        }
    });

    $(document).on('click', '.remove-field-row', function(){
        $(this).closest('.field-row').remove();
    });
});

// Helper to initialize CodeMirror JSON editors under a root node
window.initConditionEditors = function (root) {
    if (typeof CodeMirror === 'undefined') return;
    var nodes = (root || document).querySelectorAll('textarea.code-json-editor');
    nodes.forEach(function (textarea) {
        if (textarea._cmInstance) return; // avoid double-init
        textarea._cmInstance = CodeMirror.fromTextArea(textarea, {
            mode: { name: 'javascript', json: true },
            lineNumbers: true,
            theme: 'material',
            tabSize: 2,
            indentUnit: 2,
            extraKeys: {
                Tab: function (cm) {
                    if (cm.somethingSelected()) {
                        cm.indentSelection('add');
                    } else {
                        cm.replaceSelection('  ', 'end');
                    }
                },
                'Shift-Tab': function (cm) {
                    cm.indentSelection('subtract');
                }
            }
        });
    });
};

// Initialize on first load
window.addEventListener('load', function () {
    window.initConditionEditors(document);
});
</script>
";
$content = ob_get_clean();
$pageTitle = $isEdit ? 'Edit Socio Economic Form' : 'Add Socio Economic Form';
$currentPage = 'forms-socio-economic';
require __DIR__ . '/../layout/main.php';

