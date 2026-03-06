<?php
namespace App\Models;

use Core\Database;

class SocioEconomicField
{
    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function forForm(int $formId): array
    {
        $stmt = self::db()->prepare('
            SELECT id, form_id, name, description, type, is_required, is_repeatable, sort_order, condition_json, custom_html, settings_json
            FROM socio_economic_fields
            WHERE form_id = ?
            ORDER BY sort_order ASC, id ASC
        ');
        $stmt->execute([$formId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Replace all fields for a form with the given rows.
     *
     * @param int $formId
     * @param array<int, array<string, mixed>> $rows
     */
    public static function replaceForForm(int $formId, array $rows): void
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            $del = $db->prepare('DELETE FROM socio_economic_fields WHERE form_id = ?');
            $del->execute([$formId]);

            if (!empty($rows)) {
                $ins = $db->prepare('
                    INSERT INTO socio_economic_fields
                        (form_id, name, description, type, is_required, is_repeatable, sort_order, condition_json, custom_html, settings_json)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');

                foreach ($rows as $row) {
                    $name = trim($row['name'] ?? '');
                    $type = trim($row['type'] ?? '');
                    $customHtml = trim($row['custom_html'] ?? '');

                    // Skip completely empty rows
                    if ($name === '' && $customHtml === '') {
                        continue;
                    }
                    if ($type === '') {
                        $type = 'text';
                    }

                    $description = trim($row['description'] ?? '');
                    $isRequired = !empty($row['is_required']) ? 1 : 0;
                    $isRepeatable = !empty($row['is_repeatable']) ? 1 : 0;
                    $sortOrder = (int)($row['sort_order'] ?? 0);
                    $conditionJson = trim($row['condition_json'] ?? '');
                    $settingsJson = trim($row['settings_json'] ?? '');

                    $ins->execute([
                        $formId,
                        $name,
                        $description !== '' ? $description : null,
                        $type,
                        $isRequired,
                        $isRepeatable,
                        $sortOrder,
                        $conditionJson !== '' ? $conditionJson : null,
                        $customHtml !== '' ? $customHtml : null,
                        $settingsJson !== '' ? $settingsJson : null,
                    ]);
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

