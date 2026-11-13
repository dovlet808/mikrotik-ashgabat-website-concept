<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Generates resized WebP copies next to an uploaded image on the public uploads disk:
 *   products/hap-ax2/01-front.jpg  ->  products/hap-ax2/01-front-480.webp, -960.webp, -1600.webp
 *
 * Uses GD only, so it works on standard shared hosting.
 */
class ImageVariants
{
    public static function disk(): string
    {
        return config('site.images.disk', 'uploads');
    }

    /**
     * @return array<int, int>
     */
    public static function widths(): array
    {
        return config('site.images.widths', [480, 960, 1600]);
    }

    public static function variantPath(string $path, int $width): string
    {
        $info = pathinfo($path);
        $dir = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'].'/';

        return $dir.$info['filename'].'-'.$width.'.webp';
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    public static function dimensions(string $path): ?array
    {
        $absolute = Storage::disk(static::disk())->path($path);
        if (! is_file($absolute)) {
            return null;
        }
        $size = @getimagesize($absolute);
        if (! $size) {
            return null;
        }
        [$width, $height] = $size;
        if (static::exifRotation($absolute) % 180 !== 0) {
            [$width, $height] = [$height, $width];
        }

        return [$width, $height];
    }

    public static function generate(string $path): void
    {
        $storage = Storage::disk(static::disk());
        $absolute = $storage->path($path);

        if (! is_file($absolute) || ! function_exists('imagewebp')) {
            return;
        }

        try {
            $source = static::load($absolute);
            if (! $source) {
                return;
            }
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);

            foreach (static::widths() as $width) {
                $targetWidth = min($width, $sourceWidth);
                $targetHeight = (int) round($sourceHeight * $targetWidth / $sourceWidth);
                $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

                $target = $storage->path(static::variantPath($path, $width));
                imagewebp($canvas, $target, (int) config('site.images.webp_quality', 80));
                imagedestroy($canvas);
            }

            imagedestroy($source);
        } catch (Throwable $e) {
            Log::warning('Image variant generation failed', ['path' => $path, 'error' => $e->getMessage()]);
        }
    }

    public static function delete(string $path): void
    {
        $storage = Storage::disk(static::disk());
        $storage->delete(array_merge([$path], array_map(fn ($w) => static::variantPath($path, $w), static::widths())));
    }

    public static function url(string $path, ?int $width = null): string
    {
        $storage = Storage::disk(static::disk());

        if ($width !== null) {
            $variant = static::variantPath($path, static::closestWidth($width));
            if ($storage->exists($variant)) {
                return static::publicUrl($variant);
            }
        }

        return static::publicUrl($path);
    }

    /**
     * Absolute URL on the host the visitor actually used (needed as-is for og:image and JSON-LD). The disk itself
     * hands out host-relative URLs, see config/filesystems.php; outside a request url() falls back to APP_URL.
     */
    public static function publicUrl(string $path): string
    {
        return url(Storage::disk(static::disk())->url($path));
    }

    public static function srcset(string $path, ?int $originalWidth = null): string
    {
        $storage = Storage::disk(static::disk());
        $entries = [];

        foreach (static::widths() as $width) {
            $variant = static::variantPath($path, $width);
            if ($storage->exists($variant)) {
                $entries[] = static::publicUrl($variant).' '.min($width, $originalWidth ?: $width).'w';
            }
            if ($originalWidth && $width >= $originalWidth) {
                break;
            }
        }

        return implode(', ', $entries);
    }

    protected static function closestWidth(int $width): int
    {
        foreach (static::widths() as $candidate) {
            if ($candidate >= $width) {
                return $candidate;
            }
        }

        return max(static::widths());
    }

    /**
     * @return \GdImage|false
     */
    protected static function load(string $absolute)
    {
        $type = @exif_imagetype($absolute) ?: (getimagesize($absolute)[2] ?? null);

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolute),
            IMAGETYPE_PNG => @imagecreatefrompng($absolute),
            IMAGETYPE_WEBP => @imagecreatefromwebp($absolute),
            default => false,
        };

        if ($image && ($rotation = static::exifRotation($absolute))) {
            $image = imagerotate($image, -$rotation, 0);
        }

        return $image;
    }

    protected static function exifRotation(string $absolute): int
    {
        if (! function_exists('exif_read_data') || @exif_imagetype($absolute) !== IMAGETYPE_JPEG) {
            return 0;
        }

        $orientation = @exif_read_data($absolute)['Orientation'] ?? 1;

        return match ((int) $orientation) {
            3 => 180,
            6 => 90,
            8 => 270,
            default => 0,
        };
    }
}
