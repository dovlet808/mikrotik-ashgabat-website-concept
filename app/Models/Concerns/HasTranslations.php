<?php

namespace App\Models\Concerns;

/**
 * Translatable attributes are stored as {"ru": "...", "tk": "...", "en": "..."}
 * in text columns cast to arrays. Reading falls back to the default locale.
 */
trait HasTranslations
{
    public function translate(string $attribute, ?string $locale = null): ?string
    {
        $values = $this->getAttribute($attribute);

        if (! is_array($values)) {
            return blank($values) ? null : (string) $values;
        }

        $locale ??= app()->getLocale();
        $value = $values[$locale] ?? null;

        if (blank($value)) {
            $value = $values[config('site.default_locale')] ?? null;
        }

        return blank($value) ? null : (string) $value;
    }

    public function hasTranslation(string $attribute, string $locale): bool
    {
        $values = $this->getAttribute($attribute);

        return is_array($values) && filled($values[$locale] ?? null);
    }

    /**
     * Keep Cyrillic and Turkmen characters readable in the database so they are searchable.
     */
    protected function asJson($value, $flags = 0)
    {
        return parent::asJson($value, $flags | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
