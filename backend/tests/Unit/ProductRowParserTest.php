<?php

namespace Tests\Unit;

use App\Exceptions\InvalidSpreadsheetException;
use App\Exceptions\RowValidationException;
use App\Services\Import\ProductRowParser;
use Tests\TestCase;

class ProductRowParserTest extends TestCase
{
    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function row(array $overrides = []): array
    {
        return [
            'Внешний код' => 'ABC-1',
            'Наименование' => 'Бермуды мужские',
            'Описание' => 'Легкие и удобные',
            'Цена: Цена продажи' => '1320,00',
            'Закупочная цена' => '880,00',
            'Доп. поле: Цвет' => 'Grigio',
            'Доп. поле: Размер' => '46(M)',
            'Доп. поле: Пустое' => '',
            'Доп. поле: Ссылки на фото' => 'http://example.com/a.jpg, http://example.com/b.jpg',
            'Доп. поле: Ссылка на упаковку' => 'http://example.com/p.jpg',
            'Штрихкод EAN13' => '8000000784582',
            ...$overrides,
        ];
    }

    public function test_maps_valid_row(): void
    {
        $data = (new ProductRowParser)->parse($this->row());

        $this->assertSame('ABC-1', $data->externalCode);
        $this->assertSame('Бермуды мужские', $data->name);
        $this->assertSame(1320.0, $data->price);
        $this->assertSame(880.0, $data->purchasePrice);
        $this->assertSame(33.33, $data->discount());
        $this->assertSame(['Цвет' => 'Grigio', 'Размер' => '46(M)'], $data->attributes);
        $this->assertSame(
            ['http://example.com/a.jpg', 'http://example.com/b.jpg', 'http://example.com/p.jpg'],
            $data->imageUrls,
        );
    }

    public function test_collects_all_row_errors(): void
    {
        try {
            (new ProductRowParser)->parse($this->row([
                'Внешний код' => '',
                'Наименование' => '',
                'Цена: Цена продажи' => 'abc',
                'Закупочная цена' => '-5',
            ]));
            $this->fail('Exception expected');
        } catch (RowValidationException $e) {
            $this->assertCount(4, $e->messages);
            $this->assertNull($e->externalCode);
        }
    }

    public function test_price_is_required(): void
    {
        $this->expectException(RowValidationException::class);

        (new ProductRowParser)->parse($this->row(['Цена: Цена продажи' => '']));
    }

    public function test_purchase_price_is_optional(): void
    {
        $data = (new ProductRowParser)->parse($this->row(['Закупочная цена' => '']));

        $this->assertNull($data->purchasePrice);
        $this->assertNull($data->discount());
    }

    public function test_rejects_file_without_required_columns(): void
    {
        $this->expectException(InvalidSpreadsheetException::class);
        $this->expectExceptionMessage('Внешний код');

        (new ProductRowParser)->assertHeaders(['Наименование', 'Цена: Цена продажи']);
    }
}
