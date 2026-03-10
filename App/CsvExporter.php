<?php
namespace App;

class CsvExporter
{
    /**
     * Stream a CSV download.
     *
     * @param string   $filename  Base filename without extension.
     * @param string[] $headers   Header labels in display order.
     * @param iterable $rows      Rows to export (array of objects/arrays).
     * @param string[] $keys      Keys to read from each row, aligned with $headers.
     */
    public static function stream(string $filename, array $headers, iterable $rows, array $keys): void
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $filename);
        if ($safeName === '') {
            $safeName = 'export';
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $safeName . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        // UTF-8 BOM so Excel behaves
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, $headers);

        foreach ($rows as $row) {
            $line = [];
            foreach ($keys as $key) {
                if (is_array($row)) {
                    $val = $row[$key] ?? '';
                } else {
                    $val = $row->$key ?? '';
                }
                if (is_bool($val)) {
                    $val = $val ? 'Yes' : 'No';
                } elseif (is_array($val)) {
                    $val = implode(', ', $val);
                }
                $line[] = (string) $val;
            }
            fputcsv($out, $line);
        }

        fclose($out);
        exit;
    }
}

