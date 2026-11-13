<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TranslationParityTest extends TestCase
{
    protected function flatten(array $values, string $prefix = ''): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value) && ! array_is_list($value)) {
                $out += $this->flatten($value, $path);
            } else {
                $out[$path] = is_array($value) ? count($value) : 1;
            }
        }

        return $out;
    }

    protected function keys(string $locale, string $file): array
    {
        return $this->flatten(require dirname(__DIR__, 2)."/lang/{$locale}/{$file}.php");
    }

    public function test_public_site_strings_exist_in_every_language(): void
    {
        $ru = $this->keys('ru', 'site');

        foreach (['tk', 'en'] as $locale) {
            $other = $this->keys($locale, 'site');
            $this->assertSame([], array_keys(array_diff_key($ru, $other)), "{$locale} is missing keys");
            $this->assertSame([], array_keys(array_diff_key($other, $ru)), "{$locale} has extra keys");
            $this->assertSame($ru, $other, "{$locale} list lengths differ");
        }
    }

    public function test_admin_strings_exist_in_russian_and_english(): void
    {
        $this->assertSame(array_keys($this->keys('ru', 'admin')), array_keys($this->keys('en', 'admin')));
    }
}
