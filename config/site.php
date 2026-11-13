<?php

return [

    /*
    | Public website languages. The first entry is the default locale and is
    | served without a URL prefix (/catalog). The others are prefixed (/tk/catalog).
    */
    'locales' => [
        'ru' => ['name' => 'Русский', 'short' => 'RU', 'hreflang' => 'ru'],
        'tk' => ['name' => 'Türkmençe', 'short' => 'TM', 'hreflang' => 'tk'],
        'en' => ['name' => 'English', 'short' => 'EN', 'hreflang' => 'en'],
    ],

    'default_locale' => 'ru',

    /*
    | Admin panel languages (per-user setting on the profile page).
    */
    'admin_locales' => [
        'ru' => 'Русский',
        'en' => 'English',
    ],

    /*
    | ISO 4217 code of the Turkmen manat. The source price list quotes prices
    | as "manat" / "манат"; the localized label lives in lang/{locale}/site.php.
    */
    'currency' => env('SITE_CURRENCY', 'TMT'),

    /*
    | While this site is an unofficial concept it must not be indexed by search
    | engines. Set SITE_INDEXABLE=true only after the business approves a launch.
    */
    'indexable' => (bool) env('SITE_INDEXABLE', false),

    /*
    | Shows the "independent concept" notice in the footer.
    */
    'concept_mode' => (bool) env('SITE_CONCEPT_MODE', true),

    /*
    | Default contact channels, used until the owner saves their own values on
    | the admin Settings page. They are deployment configuration, so the
    | repository only carries obviously fictional placeholders.
    */
    'contact' => [
        'phone' => env('SITE_PHONE') ?: '+99300000000',
        'whatsapp' => env('SITE_WHATSAPP') ?: '99300000000',
        'instagram_handle' => env('SITE_INSTAGRAM_HANDLE') ?: null,
    ],

    'catalog_per_page' => 12,

    'inquiries' => [
        'max_attempts' => (int) env('INQUIRY_RATE_LIMIT', 5),
        'decay_minutes' => 10,
        'min_seconds_to_submit' => 3,
    ],

    /*
    | The catalogue seeder copies the source photographs into the uploads disk.
    | Tests switch this off to stay fast.
    */
    'seed_images' => (bool) env('SITE_SEED_IMAGES', true),

    'images' => [
        'disk' => 'uploads',
        'widths' => [480, 960, 1600],
        'webp_quality' => 80,
        'max_upload_kb' => 8192,
    ],

];
