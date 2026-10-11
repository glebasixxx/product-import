<?php

namespace App\Services\Import;

use App\Exceptions\InvalidSpreadsheetException;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;

class SpreadsheetReader
{
    /**
     * @return list<string>
     */
    public function readHeaders(string $path): array
    {
        foreach ($this->rawRows($path) as $values) {
            return array_map(trim(...), $values);
        }

        throw new InvalidSpreadsheetException('Файл не содержит данных.');
    }

    public function countRows(string $path): int
    {
        $count = 0;

        foreach ($this->rows($path) as $ignored) {
            $count++;
        }

        return $count;
    }

    /**
     * @return \Generator<int, array<string, string>> spreadsheet row number => [header => value]
     */
    public function rows(string $path): \Generator
    {
        $headers = null;

        foreach ($this->rawRows($path) as $rowNumber => $values) {
            if ($headers === null) {
                $headers = array_map(trim(...), $values);

                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = trim($values[$index] ?? '');
            }

            yield $rowNumber => $row;
        }
    }

    /**
     * @return \Generator<int, list<string>>
     */
    private function rawRows(string $path): \Generator
    {
        $reader = new Reader(new Options(SHOULD_PRESERVE_EMPTY_ROWS: true));

        try {
            $reader->open($path);
        } catch (\Throwable $e) {
            throw new InvalidSpreadsheetException('Не удалось прочитать xlsx файл: '.$e->getMessage(), previous: $e);
        }

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $rowNumber = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;
                    $values = array_map($this->stringify(...), $row->toArray());

                    if (implode('', $values) === '') {
                        continue;
                    }

                    yield $rowNumber => $values;
                }

                break;
            }
        } finally {
            $reader->close();
        }
    }

    private function stringify(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_string($value) => $value,
            is_bool($value) => $value ? '1' : '0',
            is_int($value) => (string) $value,
            is_float($value) => $this->formatFloat($value),
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof \DateInterval => $value->format('%H:%I:%S'),
            default => '',
        };
    }

    private function formatFloat(float $value): string
    {
        if (floor($value) === $value && abs($value) < 1e15) {
            return sprintf('%.0f', $value);
        }

        return rtrim(rtrim(sprintf('%.6F', $value), '0'), '.');
    }
}
