<?php
/** @var object $form @var array $fields */
$old = $_SESSION['socio_form_old'] ?? [];
$errors = $_SESSION['socio_form_errors'] ?? [];
$success = $_SESSION['socio_form_success'] ?? null;
unset($_SESSION['socio_form_old'], $_SESSION['socio_form_errors'], $_SESSION['socio_form_success']);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><?= htmlspecialchars($form->title ?? 'Socio Economic Form') ?></h2>
        <?php if (!empty($form->project_name)): ?>
        <div class="text-muted small">Project: <?= htmlspecialchars($form->project_name) ?></div>
        <?php endif; ?>
    </div>
    <a href="/forms/socio-economic" class="btn btn-outline-secondary btn-sm">Back to forms</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        Please fix the errors below.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form method="post" action="/forms/socio-economic/fill/<?= (int)$form->id ?>">
    <?= \Core\Csrf::field() ?>

    <div class="card mb-4">
        <div class="card-header">
            <strong>Context</strong>
        </div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Project</label>
                <select id="projectSelect" name="project_id" class="form-select">
                    <?php
                    $oldProject = $old['project_id'] ?? '';
                    $projectId = $oldProject !== '' ? $oldProject : ($form->project_id ?? '');
                    $projectName = $form->project_name ?? '';
                    ?>
                    <?php if ($projectId && $projectName): ?>
                    <option value="<?= (int)$projectId ?>" selected><?= htmlspecialchars($projectName) ?></option>
                    <?php else: ?>
                    <option value="">-- Search Project --</option>
                    <?php endif; ?>
                </select>
                <div class="form-text">Optional override of the form's default project.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">PAPS Profile</label>
                <select id="profileSelect" name="profile_id" class="form-select">
                    <option value="">-- Search PAPS Profile --</option>
                </select>
                <div class="form-text">Used for PAPS-type fields and linking this entry to a profile.</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <strong>Form Fields</strong>
        </div>
        <div class="card-body">
            <?php foreach ($fields as $field): ?>
                <?php
                $key = $field->name;
                if (!$key) continue;
                $label = $field->description ?: $field->name;
                $type = $field->type ?: 'text';
                $required = !empty($field->is_required);
                $repeatable = !empty($field->is_repeatable);
                $fieldError = $errors[$key] ?? null;
                $oldValue = $old['values'][$key] ?? null;
                $conditionJson = trim($field->condition_json ?? '');
                $conditionAttr = $conditionJson !== '' ? htmlspecialchars($conditionJson, ENT_QUOTES) : '';
                ?>
                <div class="mb-3" data-field-key="<?= htmlspecialchars($key) ?>"<?= $conditionAttr !== '' ? ' data-condition="' . $conditionAttr . '"' : '' ?>>
                    <label class="form-label">
                        <?= htmlspecialchars($label) ?>
                        <?php if ($required): ?><span class="text-danger">*</span><?php endif; ?>
                    </label>
                    <?php if ($fieldError): ?>
                        <div class="text-danger small mb-1"><?= htmlspecialchars($fieldError) ?></div>
                    <?php endif; ?>

                    <?php
                    $options = [];
                    if (!empty($field->settings_json)) {
                        $cfg = json_decode($field->settings_json, true);
                        if (!empty($cfg['options']) && is_array($cfg['options'])) {
                            $options = $cfg['options'];
                        }
                    }
                    ?>
                    <?php if ($type === 'custom_html'): ?>
                        <div class="border rounded p-2 bg-light-subtle">
                            <?= $field->custom_html ?? '' ?>
                        </div>
                        <?php continue; ?>
                    <?php endif; ?>

                    <?php if ($repeatable): ?>
                        <div class="repeatable-field" data-field-type="<?= htmlspecialchars($type) ?>">
                            <div class="repeatable-items">
                                <?php
                                $values = is_array($oldValue) ? $oldValue : ($oldValue !== null ? [$oldValue] : ['']);
                                if (empty($values)) $values = [''];
                                foreach ($values as $idx => $val):
                                ?>
                                <div class="input-group mb-2 repeatable-item">
                                    <?php if ($type === 'textarea'): ?>
                                        <textarea name="values[<?= htmlspecialchars($key) ?>][]" class="form-control form-control-sm" rows="2"><?= htmlspecialchars((string)$val) ?></textarea>
                                    <?php elseif ($type === 'number'): ?>
                                        <input type="number" name="values[<?= htmlspecialchars($key) ?>][]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$val) ?>">
                                    <?php elseif ($type === 'date'): ?>
                                        <input type="date" name="values[<?= htmlspecialchars($key) ?>][]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$val) ?>">
                                    <?php elseif ($type === 'select' && $options): ?>
                                        <select name="values[<?= htmlspecialchars($key) ?>][]" class="form-select form-select-sm">
                                            <option value="">-- Select --</option>
                                            <?php foreach ($options as $opt): ?>
                                                <?php $ov = (string)($opt['value'] ?? ''); $ol = (string)($opt['label'] ?? $ov); ?>
                                                <option value="<?= htmlspecialchars($ov) ?>" <?= (string)$val === $ov ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($ol) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($type === 'paps'): ?>
                                        <select name="values[<?= htmlspecialchars($key) ?>][]" class="form-select form-select-sm paps-select">
                                            <option value="<?= htmlspecialchars((string)$val) ?>" <?= $val ? 'selected' : '' ?>>
                                                <?= $val ? 'Selected PAPS (ID ' . htmlspecialchars((string)$val) . ')' : '-- Search PAPS Profile --' ?>
                                            </option>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" name="values[<?= htmlspecialchars($key) ?>][]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$val) ?>">
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-repeatable">×</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary add-repeatable">Add another</button>
                        </div>
                    <?php else: ?>
                        <?php
                        $val = $oldValue ?? '';
                        ?>
                        <?php if ($type === 'textarea'): ?>
                            <textarea name="values[<?= htmlspecialchars($key) ?>]" class="form-control" rows="3"><?= htmlspecialchars((string)$val) ?></textarea>
                        <?php elseif ($type === 'number'): ?>
                            <input type="number" name="values[<?= htmlspecialchars($key) ?>]" class="form-control" value="<?= htmlspecialchars((string)$val) ?>">
                        <?php elseif ($type === 'date'): ?>
                            <input type="date" name="values[<?= htmlspecialchars($key) ?>]" class="form-control" value="<?= htmlspecialchars((string)$val) ?>">
                        <?php elseif ($type === 'select' && $options): ?>
                            <select name="values[<?= htmlspecialchars($key) ?>]" class="form-select">
                                <option value="">-- Select --</option>
                                <?php foreach ($options as $opt): ?>
                                    <?php $ov = (string)($opt['value'] ?? ''); $ol = (string)($opt['label'] ?? $ov); ?>
                                    <option value="<?= htmlspecialchars($ov) ?>" <?= (string)$val === $ov ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ol) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'checkbox'): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="values[<?= htmlspecialchars($key) ?>]" value="1" <?= $val ? 'checked' : '' ?>>
                                <label class="form-check-label small">Yes</label>
                            </div>
                        <?php elseif ($type === 'paps'): ?>
                            <select name="values[<?= htmlspecialchars($key) ?>]" class="form-select paps-select">
                                <option value="<?= htmlspecialchars((string)$val) ?>" <?= $val ? 'selected' : '' ?>>
                                    <?= $val ? 'Selected PAPS (ID ' . htmlspecialchars((string)$val) . ')' : '-- Search PAPS Profile --' ?>
                                </option>
                            </select>
                        <?php else: ?>
                            <input type="text" name="values[<?= htmlspecialchars($key) ?>]" class="form-control" value="<?= htmlspecialchars((string)$val) ?>">
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>

<?php
$scripts = "
<script>
$(function(){
    // Project search (Select2)
    $('#projectSelect').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '/api/projects',
            dataType: 'json',
            delay: 250,
            data: function(params){ return { q: params.term }; },
            processResults: function(d){ return { results: d.map(function(r){ return { id: r.id, text: r.name }; }) }; }
        },
        minimumInputLength: 0,
        placeholder: 'Search project...',
        allowClear: true
    });

    // PAPS profile search (for context)
    $('#profileSelect').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '/api/profiles',
            dataType: 'json',
            delay: 250,
            data: function(params){ return { q: params.term }; },
            processResults: function(d){
                return {
                    results: d.map(function(r){
                        return {
                            id: r.id,
                            text: r.name || r.papsid || ('ID ' + r.id),
                            project_id: r.project_id,
                            project_name: r.project_name
                        };
                    })
                };
            }
        },
        minimumInputLength: 0,
        placeholder: 'Search profile (PAPSID or name)...',
        allowClear: true
    });

    // When a PAPS profile is selected, optionally set project if empty
    $('#profileSelect').on('select2:select', function(e){
        var d = e.params.data;
        if (d.project_id && d.project_name) {
            var projEl = $('#projectSelect');
            if (!projEl.val()) {
                projEl.empty()
                    .append(new Option('-- Search Project --', '', false, false))
                    .append(new Option(d.project_name, d.project_id, true, true))
                    .trigger('change');
            }
        }
    });

    // Initialize all PAPS field selects
    function initPapsSelect(el) {
        el.select2({
            theme: 'bootstrap-5',
            ajax: {
                url: '/api/profiles',
                dataType: 'json',
                delay: 250,
                data: function(params){ return { q: params.term }; },
                processResults: function(d){
                    return {
                        results: d.map(function(r){
                            return {
                                id: r.id,
                                text: r.name || r.papsid || ('ID ' + r.id),
                                project_id: r.project_id,
                                project_name: r.project_name
                            };
                        })
                    };
                }
            },
            minimumInputLength: 0,
            placeholder: 'Search PAPS Profile...',
            allowClear: true
        });
    }

    $('.paps-select').each(function(){ initPapsSelect($(this)); });

    // Repeatable fields
    $(document).on('click', '.add-repeatable', function(){
        var wrap = $(this).closest('.repeatable-field');
        var type = wrap.data('field-type');
        var listEl = wrap.find('.repeatable-items');
        var keyMatch = listEl.closest('[data-field-key]').data('field-key');
        var nameBase = 'values[' + keyMatch + '][]';

        var inputHtml;
        if (type === 'textarea') {
            inputHtml = '<textarea name=\"' + nameBase + '\" class=\"form-control form-control-sm\" rows=\"2\"></textarea>';
        } else if (type === 'number') {
            inputHtml = '<input type=\"number\" name=\"' + nameBase + '\" class=\"form-control form-control-sm\" />';
        } else if (type === 'date') {
            inputHtml = '<input type=\"date\" name=\"' + nameBase + '\" class=\"form-control form-control-sm\" />';
        } else if (type === 'paps') {
            inputHtml = '<select name=\"' + nameBase + '\" class=\"form-select form-select-sm paps-select\"><option value=\"\">-- Search PAPS Profile --</option></select>';
        } else {
            inputHtml = '<input type=\"text\" name=\"' + nameBase + '\" class=\"form-control form-control-sm\" />';
        }

        var html = '<div class=\"input-group mb-2 repeatable-item\">' +
            inputHtml +
            '<button type=\"button\" class=\"btn btn-outline-danger btn-sm remove-repeatable\">\u00d7</button>' +
            '</div>';
        var itemEl = $(html).appendTo(listEl);

        if (type === 'paps') {
            initPapsSelect(itemEl.find('.paps-select'));
        }
    });

    $(document).on('click', '.remove-repeatable', function(){
        $(this).closest('.repeatable-item').remove();
        applyFieldConditions();
    });

    // --- Conditional visibility based on Condition (JSON) ---

    function getFieldValueByKey(key) {
        var wrap = $('[data-field-key=\"' + key + '\"]');
        if (!wrap.length) return null;
        var inputs = wrap.find('[name^=\"values[' + key + ']\"]');
        if (!inputs.length) return null;

        // Detect repeatable by name pattern [] or multiple inputs
        var isRepeat = false;
        inputs.each(function () {
            if (this.name.indexOf('[]') !== -1) {
                isRepeat = true;
            }
        });
        if (inputs.length > 1) {
            isRepeat = true;
        }

        if (isRepeat) {
            var arr = [];
            inputs.each(function () {
                var el = $(this);
                var type = (el.attr('type') || '').toLowerCase();
                if (type === 'checkbox') {
                    if (el.is(':checked')) {
                        arr.push(el.val() || '1');
                    }
                } else {
                    var v = el.val();
                    if (v !== '' && v != null) {
                        arr.push(v);
                    }
                }
            });
            return arr;
        } else {
            var el0 = $(inputs[0]);
            var type0 = (el0.attr('type') || '').toLowerCase();
            if (type0 === 'checkbox') {
                return el0.is(':checked') ? (el0.val() || '1') : '';
            }
            return el0.val();
        }
    }

    function isEmptyValue(v) {
        if (v === null || v === undefined) return true;
        if (Array.isArray(v)) {
            return v.filter(function (x) { return x !== '' && x !== null && x !== undefined; }).length === 0;
        }
        return v === '';
    }

    function evaluateRule(rule) {
        if (!rule || !rule.field) return false;
        var v = getFieldValueByKey(rule.field);
        var op = rule.operator || 'equals';
        var target = rule.value;

        // Normalize
        var vArr = Array.isArray(v) ? v : null;
        var vStr = vArr ? null : (v != null ? String(v) : '');

        if (op === 'is_empty') {
            return isEmptyValue(v);
        }
        if (op === 'is_not_empty') {
            return !isEmptyValue(v);
        }

        if (op === 'in' || op === 'not_in') {
            var list = Array.isArray(target) ? target : (target != null ? [target] : []);
            var present;
            if (vArr) {
                present = vArr.some(function (item) {
                    return list.map(String).indexOf(String(item)) !== -1;
                });
            } else {
                present = list.map(String).indexOf(vStr) !== -1;
            }
            return op === 'in' ? present : !present;
        }

        if (op === 'contains') {
            if (vArr) {
                return vArr.map(String).indexOf(String(target)) !== -1;
            }
            return vStr != null && String(vStr).indexOf(String(target)) !== -1;
        }

        // Numeric ops
        if (op === '>' || op === '<' || op === '>=' || op === '<=') {
            var num = parseFloat(vStr);
            var tgt = parseFloat(target);
            if (isNaN(num) || isNaN(tgt)) return false;
            if (op === '>') return num > tgt;
            if (op === '<') return num < tgt;
            if (op === '>=') return num >= tgt;
            if (op === '<=') return num <= tgt;
        }

        // equals / not_equals
        if (vArr) {
            var has = vArr.map(String).indexOf(String(target)) !== -1;
            return op === 'equals' ? has : !has;
        } else {
            if (op === 'equals') {
                return String(vStr) === String(target);
            }
            if (op === 'not_equals') {
                return String(vStr) !== String(target);
            }
        }
        return false;
    }

    function evaluateConditionObject(cond) {
        if (!cond || !Array.isArray(cond.rules) || cond.rules.length === 0) {
            return true;
        }
        var logic = (cond.logic || 'AND').toUpperCase();
        if (logic === 'OR') {
            for (var i = 0; i < cond.rules.length; i++) {
                if (evaluateRule(cond.rules[i])) return true;
            }
            return false;
        } else {
            for (var j = 0; j < cond.rules.length; j++) {
                if (!evaluateRule(cond.rules[j])) return false;
            }
            return true;
        }
    }

    function applyFieldConditions() {
        $('[data-field-key]').each(function () {
            var wrap = $(this);
            var condStr = wrap.attr('data-condition');
            if (!condStr) {
                wrap.removeClass('d-none');
                return;
            }
            var cond;
            try {
                cond = JSON.parse(condStr);
            } catch (e) {
                // Invalid JSON: do not hide
                wrap.removeClass('d-none');
                return;
            }
            var rulesTrue = evaluateConditionObject(cond);
            var action = (cond.action || 'show').toLowerCase();
            var shouldShow;
            if (action === 'hide') {
                shouldShow = !rulesTrue;
            } else {
                shouldShow = rulesTrue;
            }
            if (shouldShow) {
                wrap.removeClass('d-none');
            } else {
                wrap.addClass('d-none');
            }
        });
    }

    // Re-evaluate whenever any form field changes
    $(document).on('change keyup', 'input[name^=\"values[\"], select[name^=\"values[\"], textarea[name^=\"values[\"]', function () {
        applyFieldConditions();
    });

    // Initial evaluation on load
    applyFieldConditions();
});
</script>
";
$content = ob_get_clean();
$pageTitle = $form->title ?? 'Socio Economic Form';
$currentPage = 'forms-socio-economic';
require __DIR__ . '/../layout/main.php';

