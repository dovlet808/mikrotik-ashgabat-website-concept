<?php

namespace App\Models;

use App\Enums\Availability;
use App\Enums\Segment;
use App\Models\Concerns\HasTranslations;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'slug', 'name', 'product_code', 'brand', 'summary', 'purpose', 'description',
        'price', 'currency', 'availability', 'segments', 'is_featured', 'is_published', 'sort_order',
        'source_reference',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'purpose' => 'array',
            'description' => 'array',
            'segments' => 'array',
            'price' => 'decimal:2',
            'availability' => Availability::class,
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (blank($product->slug)) {
                $product->slug = static::uniqueSlug($product->name);
            }
            $product->currency = strtoupper($product->currency ?: config('site.currency'));
            $product->search_text = $product->buildSearchText();
        });

        static::forceDeleting(function (Product $product) {
            $product->images()->get()->each->delete();
        });
    }

    public static function slugFromName(string $name): string
    {
        return Str::slug(str_replace(['+', '/', '.'], ' ', $name)) ?: 'product';
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = static::slugFromName($name);
        $slug = $base;
        $i = 2;
        while (static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany(['sort_order' => 'min', 'id' => 'min']);
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order')->orderBy('id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('category_id')->orWhereHas('category', fn (Builder $c) => $c->where('is_visible', true)));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function hasPrice(): bool
    {
        return $this->price !== null && (float) $this->price > 0;
    }

    public function formattedPrice(?string $locale = null): ?string
    {
        return $this->hasPrice() ? Money::format((float) $this->price, $this->currency, $locale) : null;
    }

    /**
     * @return array<int, Segment>
     */
    public function segmentEnums(): array
    {
        return collect($this->segments ?? [])
            ->map(fn ($value) => Segment::tryFrom($value))
            ->filter()
            ->values()
            ->all();
    }

    public function displayName(): string
    {
        return trim(($this->brand ? $this->brand.' ' : '').$this->name);
    }

    public function buildSearchText(): string
    {
        $parts = [$this->name, $this->product_code, $this->brand];

        foreach (['summary', 'purpose', 'description'] as $attribute) {
            foreach ((array) $this->getAttribute($attribute) as $text) {
                $parts[] = $text;
            }
        }

        if ($this->exists) {
            foreach ($this->specifications()->get() as $spec) {
                foreach (['label', 'value'] as $attribute) {
                    foreach ((array) $spec->getAttribute($attribute) as $text) {
                        $parts[] = $text;
                    }
                }
            }
        }

        if ($this->category_id && $category = Category::find($this->category_id)) {
            foreach ((array) $category->name as $text) {
                $parts[] = $text;
            }
        }

        return Str::lower(Str::limit(trim(preg_replace('/\s+/u', ' ', implode(' ', array_filter($parts)))), 60000, ''));
    }

    public function refreshSearchText(): void
    {
        $this->forceFill(['search_text' => $this->buildSearchText()])->saveQuietly();
    }
}
