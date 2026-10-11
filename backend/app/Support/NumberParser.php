<?php

namespace App\Support;

final class NumberParser
{
    /**
     * @throws \InvalidArgumentException
     */
    public static function parse(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            throw new \InvalidArgumentException('Unsupported number value.');
        }

        $normalized = str_replace([' ', "\u{00A0}", "\u{202F}"], '', trim($value));

        if ($normalized === '') {
            return null;
        }

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
            $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';
            $normalized = str_replace($thousandSeparator, '', $normalized);
        }

        $normalized = str_replace(',', '.', $normalized);

        if (! preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid number.', $value));
        }

        return (float) $normalized;
    }
}
