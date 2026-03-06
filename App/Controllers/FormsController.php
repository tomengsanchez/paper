<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\Models\SocioEconomicForm;
use App\Models\SocioEconomicField;
use App\Models\SocioEconomicEntry;

class FormsController extends Controller
{
    public function __construct()
    {
        $this->requireCapability('view_forms');
    }

    /**
     * Socio Economic: list all forms with basic search.
     */
    public function socioEconomic(): void
    {
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_GET['per_page'] ?? 15)));

        $pagination = SocioEconomicForm::listPaginated($search, $page, $perPage);

        $this->view('forms/socio_economic', [
            'forms' => $pagination['items'],
            'search' => $search,
            'pagination' => $pagination,
        ]);
    }

    public function socioEconomicCreate(): void
    {
        $this->view('forms/socio_economic_form', [
            'form' => null,
            'fields' => [],
        ]);
    }

    public function socioEconomicStore(): void
    {
        $this->validateCsrf();
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $_SESSION['flash_error'] = 'Title is required.';
            $this->redirect('/forms/socio-economic/create');
        }

        $projectId = $_POST['project_id'] ?? '';
        $description = $_POST['description'] ?? '';

        $id = SocioEconomicForm::create([
            'project_id' => $projectId,
            'title' => $title,
            'description' => $description,
        ]);

        // Save fields if any were posted
        $fields = $_POST['fields'] ?? [];
        if (is_array($fields) && !empty($fields)) {
            $fields = self::prepareSocioFieldSettings($fields);
            SocioEconomicField::replaceForForm($id, $fields);
        }

        $this->redirect('/forms/socio-economic/edit/' . $id);
    }

    public function socioEconomicEdit(int $id): void
    {
        $form = SocioEconomicForm::find($id);
        if (!$form) {
            $this->redirect('/forms/socio-economic');
            return;
        }

        $fields = SocioEconomicField::forForm($id);

        $this->view('forms/socio_economic_form', [
            'form' => $form,
            'fields' => $fields,
        ]);
    }

    public function socioEconomicUpdate(int $id): void
    {
        $this->validateCsrf();

        $form = SocioEconomicForm::find($id);
        if (!$form) {
            $this->redirect('/forms/socio-economic');
            return;
        }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $_SESSION['flash_error'] = 'Title is required.';
            $this->redirect('/forms/socio-economic/edit/' . $id);
        }

        $projectId = $_POST['project_id'] ?? '';
        $description = $_POST['description'] ?? '';

        SocioEconomicForm::update($id, [
            'project_id' => $projectId,
            'title' => $title,
            'description' => $description,
        ]);

        $fields = $_POST['fields'] ?? [];
        if (!is_array($fields)) {
            $fields = [];
        }
        $fields = self::prepareSocioFieldSettings($fields);
        SocioEconomicField::replaceForForm($id, $fields);

        $this->redirect('/forms/socio-economic/edit/' . $id);
    }

    public function socioEconomicDelete(int $id): void
    {
        $this->validateCsrf();
        SocioEconomicForm::delete($id);
        $this->redirect('/forms/socio-economic');
    }

    /**
     * List submitted entries for a given Socio Economic form.
     */
    public function socioEconomicEntries(int $id): void
    {
        $this->requireAuth();
        $form = SocioEconomicForm::find($id);
        if (!$form) {
            $this->redirect('/forms/socio-economic');
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_GET['per_page'] ?? 15)));

        $pagination = SocioEconomicEntry::listByForm($id, $page, $perPage);

        $this->view('forms/socio_economic_entries', [
            'form' => $form,
            'entries' => $pagination['items'],
            'pagination' => $pagination,
        ]);
    }

    /**
     * Render a runtime Socio Economic form for data entry.
     */
    public function socioEconomicFill(int $id): void
    {
        $this->requireAuth();
        $form = SocioEconomicForm::find($id);
        if (!$form) {
            $this->redirect('/forms/socio-economic');
            return;
        }
        $fields = SocioEconomicField::forForm($id);

        $this->view('forms/socio_economic_fill', [
            'form' => $form,
            'fields' => $fields,
        ]);
    }

    /**
     * Handle submission of a Socio Economic form.
     */
    public function socioEconomicFillStore(int $id): void
    {
        $this->validateCsrf();
        $this->requireAuth();

        $form = SocioEconomicForm::find($id);
        if (!$form) {
            $this->redirect('/forms/socio-economic');
            return;
        }

        $fields = SocioEconomicField::forForm($id);
        $errors = [];
        $data = [];
        $projectId = isset($_POST['project_id']) && $_POST['project_id'] !== '' ? (int)$_POST['project_id'] : ($form->project_id ?? null);
        $profileId = isset($_POST['profile_id']) && $_POST['profile_id'] !== '' ? (int)$_POST['profile_id'] : null;

        foreach ($fields as $field) {
            $key = $field->name;
            if ($key === '' || $key === null) {
                continue;
            }

            // Input names follow pattern: values[field_key] or values[field_key][] for repeatable.
            if (!empty($field->is_repeatable)) {
                $value = $_POST['values'][$key] ?? [];
                if (!is_array($value)) {
                    $value = $value !== '' ? [$value] : [];
                }
            } else {
                $value = $_POST['values'][$key] ?? null;
            }

            // Basic required validation
            if (!empty($field->is_required)) {
                $isEmpty = false;
                if (!empty($field->is_repeatable)) {
                    $clean = array_filter(is_array($value) ? $value : [], static function ($v) {
                        return $v !== '' && $v !== null;
                    });
                    $isEmpty = empty($clean);
                } else {
                    $isEmpty = ($value === '' || $value === null);
                }

                if ($isEmpty) {
                    $errors[$key] = 'This field is required.';
                }
            }

            $data[$key] = $value;
        }

        if (!empty($errors)) {
            $_SESSION['socio_form_errors'] = $errors;
            $_SESSION['socio_form_old'] = $_POST;
            $this->redirect('/forms/socio-economic/fill/' . $id);
        }

        SocioEconomicEntry::create((int)$form->id, $projectId ? (int)$projectId : null, $profileId, $data);

        $_SESSION['socio_form_success'] = 'Form submitted successfully.';
        $this->redirect('/forms/socio-economic/fill/' . $id);
    }

    /**
     * Static help page for Condition (JSON) format.
     */
    public function conditionJsonHelp(): void
    {
        $this->requireAuth();
        $this->view('forms/condition_json_help');
    }

    /**
     * Normalize field options from simple textarea format into settings_json.
     *
     * @param array<int, array<string,mixed>> $fields
     * @return array<int, array<string,mixed>>
     */
    private static function prepareSocioFieldSettings(array $fields): array
    {
        foreach ($fields as &$row) {
            $raw = trim($row['options_raw'] ?? '');
            if ($raw !== '') {
                $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
                $options = [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') continue;
                    $value = $line;
                    $label = $line;
                    if (strpos($line, '|') !== false) {
                        [$v, $lab] = explode('|', $line, 2);
                        $value = trim($v);
                        $label = trim($lab) !== '' ? trim($lab) : $value;
                    }
                    if ($value === '') continue;
                    $options[] = ['value' => $value, 'label' => $label];
                }
                if (!empty($options)) {
                    $row['settings_json'] = json_encode(['options' => $options], JSON_UNESCAPED_UNICODE);
                }
            }
            unset($row['options_raw']);
        }
        return $fields;
    }

    public function perception(): void
    {
        $this->view('forms/perception');
    }
}

