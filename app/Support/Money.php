<?php

namespace App\Support;

class Money
{
    /**
     * 2850 -> "2 850 манат" (ru), "2 850 manat" (tk), "2,850 manat" (en).
     */
    public static function format(float $amount, ?string $currency = null, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $currency ??= config('site.currency');
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;

        $number = $locale === 'en'
            ? number_format($amount, $decimals, '.', ',')
            : number_format($amount, $decimals, ',', "\u{202F}");

        return $number."\u{00A0}".static::label($currency, $locale);
    }

    public static function number(float $amount, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;

        return $locale === 'en'
            ? number_format($amount, $decimals, '.', ',')
            : number_format($amount, $decimals, ',', "\u{202F}");
    }

    public static function label(?string $currency = null, ?string $locale = null): string
    {
        $currency ??= config('site.currency');
        $key = 'site.currency.'.strtolower($currency);
        $label = __($key, [], $locale);

        return $label === $key ? $currency : $label;
    }
}
