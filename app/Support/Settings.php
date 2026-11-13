<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Website settings editable in the admin panel (Settings page).
 * Values are cached forever and the cache is cleared on save.
 */
class Settings
{
    public const CACHE_KEY = 'site.settings.v1';

    /**
     * Keys whose value is an array of translations {"ru": "...", "tk": "...", "en": "..."}.
     */
    public const TRANSLATABLE = ['address', 'working_hours', 'delivery_text', 'delivery_note', 'price_note'];

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $instagram = config('site.contact.instagram_handle');

        return [
            'phone' => config('site.contact.phone'),
            'whatsapp' => config('site.contact.whatsapp'),
            'instagram_handle' => $instagram,
            'instagram_url' => $instagram ? 'https://www.instagram.com/'.$instagram.'/' : null,
            'email' => null,
            'city' => 'Ашхабад',
            'address' => null,
            'working_hours' => null,
            'delivery_text' => null,
            'delivery_note' => null,
            'price_note' => null,
            'show_prices' => true,
            'notification_email' => null,
        ];
    }

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $loaded = null;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            $stored = [];
        }

        return $this->loaded = array_replace(static::defaults(), $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return $value ?? $default;
    }

    public function text(string $key, ?string $locale = null): ?string
    {
        $value = $this->get($key);

        if (! is_array($value)) {
            return blank($value) ? null : (string) $value;
        }

        $locale ??= app()->getLocale();
        $text = $value[$locale] ?? null;

        if (blank($text)) {
            $text = $value[config('site.default_locale')] ?? null;
        }

        return blank($text) ? null : (string) $text;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, static::defaults())) {
                continue;
            }
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    public function whatsappUrl(?string $message = null): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->get('whatsapp'));

        if ($digits === '') {
            return null;
        }

        return 'https://wa.me/'.$digits.($message ? '?text='.rawurlencode($message) : '');
    }

    public function phoneHref(): ?string
    {
        $phone = preg_replace('/[^\d+]/', '', (string) $this->get('phone'));

        return $phone === '' ? null : 'tel:'.$phone;
    }

    public function phoneDisplay(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->get('phone'));

        if (preg_match('/^993(\d{2})(\d{2})(\d{2})(\d{2})$/', $digits, $m)) {
            return '+993 '.$m[1].' '.$m[2].'-'.$m[3].'-'.$m[4];
        }

        return $this->get('phone');
    }
}
