<?php
declare(strict_types=1);

namespace Payroll;

use RuntimeException;

final class CsvReader
{
    /** @return list<array<string, string>> */
    public static function read(string $path, array $requiredColumns): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Required CSV file is unavailable.');
        }
        $file = fopen($path, 'rb');
        if ($file === false) {
            throw new RuntimeException('Cannot open required CSV file.');
        }
        try {
            $headers = fgetcsv($file, 0, ',', '"', '');
            if ($headers === false) { throw new RuntimeException('CSV has no header.'); }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0] ?? '');
            if (count(array_unique($headers)) !== count($headers) || array_diff($requiredColumns, $headers)) {
                throw new RuntimeException('CSV columns do not match the required schema.');
            }
            $rows = [];
            while (($values = fgetcsv($file, 0, ',', '"', '')) !== false) {
                if ($values === [null]) { continue; }
                if (count($values) !== count($headers)) {
                    throw new RuntimeException('Malformed CSV row.');
                }
                $rows[] = array_combine($headers, $values);
            }
            return $rows;
        } finally {
            fclose($file);
        }
    }
}
