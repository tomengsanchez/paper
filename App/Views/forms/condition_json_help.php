<?php
ob_start();
?>
<div class="mb-4">
    <h2>Condition (JSON) Guide</h2>
    <p class="text-muted mb-1">This describes how to write the Condition (JSON) used to show or hide fields in a form.</p>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Basic structure</strong></div>
    <div class="card-body">
        <p class="mb-2">Each condition is a JSON object with three main parts:</p>
<pre class="mb-0"><code>{
  "logic": "AND",
  "action": "show",
  "rules": [
    {
      "field": "other_field_key",
      "operator": "equals",
      "value": "yes"
    }
  ]
}</code></pre>
        <ul class="mt-2 mb-0 small">
            <li><strong>logic</strong>: how multiple rules are combined. Allowed values: <code>AND</code>, <code>OR</code>.</li>
            <li><strong>action</strong>: what to do when the rules evaluate to true. Allowed values:
                <code>show</code> (show this field when rules are true) and
                <code>hide</code> (hide this field when rules are true).</li>
            <li><strong>rules</strong>: list of rule objects described below.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Rule object</strong></div>
    <div class="card-body">
        <p class="mb-2">Each rule checks one other field:</p>
<pre class="mb-0"><code>{
  "field": "other_field_key",
  "operator": "equals",
  "value": "yes"
}</code></pre>
        <ul class="mt-2 mb-0 small">
            <li><strong>field</strong>: the <em>Field name (key)</em> of another field in the same form (not the label).</li>
            <li><strong>operator</strong>: comparison operator (see list below).</li>
            <li><strong>value</strong>: the value to compare to (string/number/boolean/array depending on operator).</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Allowed operators</strong></div>
    <div class="card-body small">
        <ul class="mb-1">
            <li><strong>equals</strong>: field value is exactly equal to <code>value</code>.</li>
            <li><strong>not_equals</strong>: field value is not equal to <code>value</code>.</li>
            <li><strong>&gt;</strong>, <strong>&lt;</strong>, <strong>&gt;=</strong>, <strong>&lt;=</strong>: numeric comparisons (for number fields).</li>
            <li><strong>contains</strong>: for arrays or text, true if the value contains the given <code>value</code>.</li>
            <li><strong>in</strong>: value is one of a list. Example:
<pre class="mb-0"><code>{
  "field": "status",
  "operator": "in",
  "value": ["open", "in_progress"]
}</code></pre>
            </li>
            <li><strong>not_in</strong>: value is not in the given list.</li>
            <li><strong>is_empty</strong>: field has no value (no <code>value</code> needed).</li>
            <li><strong>is_not_empty</strong>: field has some value (no <code>value</code> needed).</li>
        </ul>
        <p class="mb-0 text-muted">Note: the backend will interpret and implement these operators; keep the JSON valid.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Examples</strong></div>
    <div class="card-body small">
        <p class="mb-1"><strong>1. Show field only when a checkbox/select is "yes"</strong></p>
<pre><code>{
  "logic": "AND",
  "action": "show",
  "rules": [
    {
      "field": "has_dependents",
      "operator": "equals",
      "value": "yes"
    }
  ]
}</code></pre>

        <p class="mb-1"><strong>2. Show when income is greater than 10,000</strong></p>
<pre><code>{
  "logic": "AND",
  "action": "show",
  "rules": [
    {
      "field": "household_income",
      "operator": ">",
      "value": 10000
    }
  ]
}</code></pre>

        <p class="mb-1"><strong>3. Show when status is "open" or "in_progress"</strong></p>
<pre><code>{
  "logic": "OR",
  "action": "show",
  "rules": [
    {
      "field": "status",
      "operator": "equals",
      "value": "open"
    },
    {
      "field": "status",
      "operator": "equals",
      "value": "in_progress"
    }
  ]
}</code></pre>

        <p class="mb-1"><strong>4. Hide field when consent_given is "no"</strong></p>
<pre class="mb-0"><code>{
  "logic": "AND",
  "action": "hide",
  "rules": [
    {
      "field": "consent_given",
      "operator": "equals",
      "value": "no"
    }
  ]
}</code></pre>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Condition (JSON) Guide';
$currentPage = 'forms-socio-economic';
require __DIR__ . '/../layout/main.php';

