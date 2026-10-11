<?php

namespace Tests\Unit;

use App\Support\NumberParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NumberParserTest extends TestCase
{
    /**
     * @return array<string, array{mixed, float|null}>
     */
    public static function validValues(): array
    {
        return [
            'comma decimal' => ['1320,00', 1320.0],
            'dot decimal' => ['880.5', 880.5],
            'thousand space' => ['1 320,50', 1320.5],
            'non breaking space' => ["1\u{00A0}320,50", 1320.5],
            'european format' => ['1.234,56', 1234.56],
            'us format' => ['1,234.56', 1234.56],
            'integer' => [960, 960.0],
            'float' => [12.75, 12.75],
            'empty string' => ['', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('validValues')]
    public function test_parses_spreadsheet_numbers(mixed $input, ?float $expected): void
    {
        $this->assertSame($expected, NumberParser::parse($input));
    }

    public function test_rejects_garbage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        NumberParser::parse('сто рублей');
    }
}
