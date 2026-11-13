<?php

/*
 | MikroTik Ashgabat — hosting compatibility probe.
 |
 | 1. Set CHECK_TOKEN below to a long random string.
 | 2. Upload this file to public_html/ of the TARGET (demo) domain.
 | 3. Open https://YOUR-DEMO-DOMAIN/hosting-check.php?token=YOUR_TOKEN
 | 4. Save the report, then DELETE this file from the server.
 |
 | It only reads PHP configuration; it does not change anything.
 */

const CHECK_TOKEN = 'CHANGE-ME-TO-A-LONG-RANDOM-STRING';

if (CHECK_TOKEN === 'CHANGE-ME-TO-A-LONG-RANDOM-STRING' || ! hash_equals(CHECK_TOKEN, (string) ($_GET['token'] ?? ''))) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex');

$ok = fn (bool $v) => $v ? 'OK  ' : 'FAIL';
$disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
$required = ['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'gd', 'intl', 'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'session', 'tokenizer', 'xml', 'zip', 'exif'];
$gd = function_exists('gd_info') ? gd_info() : [];

echo "MikroTik Ashgabat — hosting check\n", str_repeat('=', 40), "\n\n";
printf("%s PHP version %s (Laravel 13 needs >= 8.3)\n", $ok(version_compare(PHP_VERSION, '8.3.0', '>=')), PHP_VERSION);
printf("     SAPI: %s · OS: %s\n\n", PHP_SAPI, PHP_OS_FAMILY);

echo "Extensions\n";
foreach ($required as $ext) {
    printf("%s %s\n", $ok(extension_loaded($ext)), $ext);
}
printf("%s GD WebP support (image variants)\n", $ok(! empty($gd['WebP Support'])));
printf("%s OPcache enabled\n\n", $ok((bool) ini_get('opcache.enable')));

echo "Functions Laravel / Composer use\n";
foreach (['proc_open', 'symlink', 'exec', 'putenv', 'escapeshellarg', 'fpassthru'] as $fn) {
    printf("%s %s%s\n", $ok(function_exists($fn) && ! in_array($fn, $disabled, true)), $fn, in_array($fn, ['symlink', 'exec'], true) ? ' (optional)' : '');
}

echo "\nLimits\n";
foreach (['memory_limit', 'upload_max_filesize', 'post_max_size', 'max_execution_time', 'max_input_vars'] as $key) {
    printf("     %-20s %s\n", $key, ini_get($key));
}
printf("%s upload_max_filesize >= 8M (product photos)\n", $ok((int) ini_get('upload_max_filesize') >= 8 || ini_get('upload_max_filesize') === '0'));

echo "\nFilesystem\n";
printf("     document root: %s\n", $_SERVER['DOCUMENT_ROOT'] ?? '?');
printf("     this file:     %s\n", __FILE__);
printf("%s parent of document root is writable (place the app outside public_html)\n", $ok(is_writable(dirname((string) ($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), 2))));
printf("%s HTTPS on this request\n", $ok((! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')));
printf("     server software: %s\n", $_SERVER['SERVER_SOFTWARE'] ?? '?');

echo "\nDelete this file now.\n";
