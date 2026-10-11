<?php

namespace App\Services\Import;

use App\DTO\ProductData;
use App\Exceptions\InvalidSpreadsheetException;
use App\Exceptions\RowValidationException;
use App\Support\NumberParser;

class ProductRowParser
{
    /** @var array{external_code: string, name: string, description: string, price: string, purchase_price: string} */
    private array $columns;

    private string $attributePrefix;

    /** @var list<string> */
    private array $imageColumns;

    public function __construct()
    {
        /** @var array{external_code: string, name: string, description: string, price: string, purchase_price: string} $columns */
        $columns = config('import.columns');
        /** @var list<string> $imageColumns */
        $imageColumns = config('import.image_columns', []);

        $this->columns = $columns;
        $this->attributePrefix = (string) config('import.attribute_prefix');
        $this->imageColumns = $imageColumns;
    }

    /**
     * @param  list<string>  $headers
     * @throws InvalidSpreadsheetException
     */
    public function assertHeaders(array $headers): void
    {
        $required = [$this->columns['external_code'], $this->columns['name'], $this->columns['price']];
        $missing = array_values(array_diff($required, $headers));

        if ($missing !== []) {
            throw new InvalidSpreadsheetException(
                'В файле отсутствуют обязательные колонки: '.implode(', ', $missing).'.',
            );
        }
    }

    /**
     * @param  array<string, string>  $row  header => value
     * @throws RowValidationException
     */
    public function parse(array $row): ProductData
    {
        $errors = [];

        $externalCode = $row[$this->columns['external_code']] ?? '';
        $name = $row[$this->columns['name']] ?? '';
        $description = $row[$this->columns['description']] ?? '';

        if ($externalCode === '') {
            $errors[] = 'Не заполнен внешний код.';
        } elseif (mb_strlen($externalCode) > 255) {
            $errors[] = 'Внешний код длиннее 255 символов.';
        }

        if ($name === '') {
            $errors[] = 'Не заполнено наименование.';
        } elseif (mb_strlen($name) > 512) {
            $errors[] = 'Наименование длиннее 512 символов.';
        }

        $price = $this->parseMoney($row[$this->columns['price']] ?? '', 'Цена продажи', $errors, required: true);
        $purchasePrice = $this->parseMoney($row[$this->columns['purchase_price']] ?? '', 'Закупочная цена', $errors, required: false);

        if ($errors !== [] || $price === null) {
            throw new RowValidationException($errors, $externalCode !== '' ? $externalCode : null);
        }

        return new ProductData(
            externalCode: $externalCode,
            name: $name,
            description: $description !== '' ? $description : null,
            price: $price,
            purchasePrice: $purchasePrice,
            attributes: $this->extractAttributes($row),
            imageUrls: $this->extractImageUrls($row),
        );
    }

    /**
     * @param  list<string>  $errors
     */
    private function parseMoney(string $raw, string $label, array &$errors, bool $required): ?float
    {
        try {
            $value = NumberParser::parse($raw);
        } catch (\InvalidArgumentException) {
            $errors[] = sprintf('%s: "%s" не является числом.', $label, $raw);

            return null;
        }

        if ($value === null) {
            if ($required) {
                $errors[] = sprintf('%s: не заполнена.', $label);
            }

            return null;
        }

        if ($value < 0) {
            $errors[] = sprintf('%s не может быть отрицательной.', $label);

            return null;
        }

        if ($value >= 1e10) {
            $errors[] = sprintf('%s слишком большая.', $label);

            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private function extractAttributes(array $row): array
    {
        $attributes = [];

        foreach ($row as $header => $value) {
            if ($value === ''
                || ! str_starts_with($header, $this->attributePrefix)
                || in_array($header, $this->imageColumns, true)
            ) {
                continue;
            }

            $key = trim(mb_substr($header, mb_strlen($this->attributePrefix)));

            if ($key !== '') {
                $attributes[mb_substr($key, 0, 255)] = $value;
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, string>  $row
     * @return list<string>
     */
    private function extractImageUrls(array $row): array
    {
        $urls = [];

        foreach ($this->imageColumns as $column) {
            $value = $row[$column] ?? '';
            if ($value === '') {
                continue;
            }

            foreach (preg_split('/[\s,;]+/u', $value) ?: [] as $url) {
                $url = trim($url);
                if ($url !== '') {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }
}
