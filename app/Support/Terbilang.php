<?php

namespace App\Support;

class Terbilang
{
    /**
     * @var array<int, string>
     */
    private static array $words = [
        '',
        'Satu',
        'Dua',
        'Tiga',
        'Empat',
        'Lima',
        'Enam',
        'Tujuh',
        'Delapan',
        'Sembilan',
        'Sepuluh',
        'Sebelas',
    ];

    public static function make(int|float $number): string
    {
        $number = (int) round($number);

        if ($number === 0) {
            return 'Nol';
        }

        return trim(self::spell($number));
    }

    private static function spell(int $number): string
    {
        if ($number < 12) {
            return self::$words[$number];
        }

        if ($number < 20) {
            return self::spell($number - 10).' Belas';
        }

        if ($number < 100) {
            $remainder = $number % 10;

            return trim(self::spell(intdiv($number, 10)).' Puluh '.self::spell($remainder));
        }

        if ($number < 200) {
            return trim('Seratus '.self::spell($number - 100));
        }

        if ($number < 1000) {
            $remainder = $number % 100;

            return trim(self::spell(intdiv($number, 100)).' Ratus '.self::spell($remainder));
        }

        if ($number < 2000) {
            return trim('Seribu '.self::spell($number - 1000));
        }

        if ($number < 1000000) {
            $remainder = $number % 1000;

            return trim(self::spell(intdiv($number, 1000)).' Ribu '.self::spell($remainder));
        }

        if ($number < 1000000000) {
            $remainder = $number % 1000000;

            return trim(self::spell(intdiv($number, 1000000)).' Juta '.self::spell($remainder));
        }

        if ($number < 1000000000000) {
            $remainder = $number % 1000000000;

            return trim(self::spell(intdiv($number, 1000000000)).' Miliar '.self::spell($remainder));
        }

        $remainder = $number % 1000000000000;

        return trim(self::spell(intdiv($number, 1000000000000)).' Triliun '.self::spell($remainder));
    }
}
