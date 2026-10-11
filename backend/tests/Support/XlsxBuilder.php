<?php

namespace Tests\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

final class XlsxBuilder
{
    public const HEADERS = [
        'Внешний код',
        'Наименование',
        'Цена: Цена продажи',
        'Описание',
        'Закупочная цена',
        'Доп. поле: Цвет',
        'Доп. поле: Размер',
        'Доп. поле: Ссылки на фото',
    ];

    /**
     * @param  list<list<string|int|float|null>>  $rows  data rows (header is prepended unless $headers is given)
     * @param  list<string>|null  $headers
     */
    public static function make(array $rows, ?array $headers = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($headers ?? self::HEADERS));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }

    public static function png(): string
    {
        return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    }
}
