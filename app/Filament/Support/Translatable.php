<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Builds form fields for attributes stored as {"ru": ..., "tk": ..., "en": ...}.
 * Russian (the default locale) is required where the attribute is required.
 */
class Translatable
{
    /**
     * @return array<string, string>
     */
    public static function locales(): array
    {
        return collect(config('site.locales'))->map(fn ($l, $code) => strtoupper($code === 'tk' ? 'TM' : $code))->all();
    }

    /**
     * One input per locale side by side, for short values (names, titles).
     *
     * @param  Closure(string $statePath): Field  $make
     */
    public static function inline(string $attribute, string $label, Closure $make, bool $required = false, int $columns = 3): Grid
    {
        $fields = [];
        foreach (array_keys(config('site.locales')) as $locale) {
            $field = $make("{$attribute}.{$locale}")
                ->label($label.' · '.static::locales()[$locale]);
            if ($required && $locale === config('site.default_locale')) {
                $field->required();
            }
            $fields[] = $field;
        }

        return Grid::make(['default' => 1, 'md' => $columns])->schema($fields);
    }

    /**
     * Language tabs, each containing the given attributes, for longer texts.
     *
     * @param  array<string, array{0: string, 1: Closure(string $statePath): Field, 2?: bool}>  $attributes  attribute => [label, factory, required]
     */
    public static function tabs(array $attributes): Tabs
    {
        $tabs = [];
        foreach (array_keys(config('site.locales')) as $locale) {
            $fields = [];
            foreach ($attributes as $attribute => $definition) {
                [$label, $make] = $definition;
                $required = $definition[2] ?? false;
                $field = $make("{$attribute}.{$locale}")->label($label);
                if ($required && $locale === config('site.default_locale')) {
                    $field->required();
                }
                $fields[] = $field;
            }
            $tabs[] = Tab::make(__('admin.locales.'.$locale))->schema($fields);
        }

        return Tabs::make()->tabs($tabs)->columnSpanFull();
    }
}
