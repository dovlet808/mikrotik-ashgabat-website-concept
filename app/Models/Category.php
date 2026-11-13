<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class Category extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['slug', 'name', 'description', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->translate('name', 'en') ?? $category->translate('name', 'ru') ?? 'category');
            }
        });

        static::deleting(function (Category $category) {
            if ($category->products()->withTrashed()->exists()) {
                throw new LogicException('Category still contains products. Reassign them before deleting.');
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function publishedProducts(): HasMany
    {
        return $this->products()->published();
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function canBeDeleted(): bool
    {
        return ! $this->products()->withTrashed()->exists();
    }
}
