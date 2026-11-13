<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Support\Media\ImageVariants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasTranslations;

    protected $fillable = ['product_id', 'path', 'alt', 'width', 'height', 'sort_order'];

    protected function casts(): array
    {
        return [
            'alt' => 'array',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProductImage $image) {
            if ($image->isDirty('path') && filled($image->path)) {
                $dimensions = ImageVariants::dimensions($image->path);
                $image->width = $dimensions[0] ?? null;
                $image->height = $dimensions[1] ?? null;
            }
        });

        static::saved(function (ProductImage $image) {
            if ($image->wasChanged('path') || $image->wasRecentlyCreated) {
                $original = $image->getOriginal('path');
                if ($image->wasChanged('path') && filled($original) && $original !== $image->path) {
                    ImageVariants::delete($original);
                }
                ImageVariants::generate($image->path);
            }
        });

        static::deleted(function (ProductImage $image) {
            ImageVariants::delete($image->path);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(?int $width = null): string
    {
        return ImageVariants::url($this->path, $width);
    }

    public function srcset(): string
    {
        return ImageVariants::srcset($this->path, $this->width);
    }

    public function altText(Product $product): string
    {
        return $this->translate('alt') ?? $product->displayName();
    }
}
