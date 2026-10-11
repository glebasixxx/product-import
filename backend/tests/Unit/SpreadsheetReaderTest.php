<?php

namespace Tests\Unit;

use App\Exceptions\InvalidSpreadsheetException;
use App\Services\Import\SpreadsheetReader;
use PHPUnit\Framework\TestCase;
use Tests\Support\XlsxBuilder;

class SpreadsheetReaderTest extends TestCase
{
    public function test_reads_example_file(): void
    {
        $reader = new SpreadsheetReader;
        $path = __DIR__.'/../Fixtures/import_example.xlsx';

        $headers = $reader->readHeaders($path);
        $this->assertContains('Внешний код', $headers);
        $this->assertContains('Доп. поле: Ссылки на фото', $headers);
        $this->assertSame(40, $reader->countRows($path));

        $rows = iterator_to_array($reader->rows($path));
        $this->assertArrayHasKey(2, $rows, 'Row numbers match the spreadsheet (header is row 1)');
        $this->assertSame('3UHfAid1jaMiwgBuNvnsf3', $rows[2]['Внешний код']);
        $this->assertSame('1320,00', $rows[2]['Цена: Цена продажи']);
    }

    public function test_skips_empty_rows_and_keeps_row_numbers(): void
    {
        $path = XlsxBuilder::make([
            ['A', 'First', 100],
            [null, null, null],
            ['B', 'Second', 200.5],
        ]);

        $rows = iterator_to_array((new SpreadsheetReader)->rows($path));

        $this->assertSame([2, 4], array_keys($rows));
        $this->assertSame('100', $rows[2]['Цена: Цена продажи']);
        $this->assertSame('200.5', $rows[4]['Цена: Цена продажи']);
    }

    public function test_throws_on_unreadable_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bad');
        file_put_contents($path, 'not a spreadsheet');

        $this->expectException(InvalidSpreadsheetException::class);

        (new SpreadsheetReader)->readHeaders($path);
    }
}
