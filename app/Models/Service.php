<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Support\Media\ImageVariants;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasTranslations;

    public const ICONS = [
        'magnifying-glass' => 'Подбор / Selection',
        'shopping-bag' => 'Продажа / Sales',
        'cog-6-tooth' => 'Настройка / Configuration',
        'wrench-screwdriver' => 'Монтаж / Installation',
        'signal' => 'Диагностика / Diagnostics',
        'adjustments-horizontal' => 'Оптимизация / Optimization',
        'squares-plus' => 'Сеть с нуля / Turnkey',
        'lifebuoy' => 'Обслуживание / Maintenance',
        'shield-check' => 'Безопасность / Security',
        'truck' => 'Доставка / Delivery',
    ];

    protected $fillable = [
        'slug', 'title', 'summary', 'body', 'icon', 'image_path', 'is_confirmed', 'source_reference',
        'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'summary' => 'array',
            'body' => 'array',
            'is_confirmed' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Service $service) {
            if (blank($service->slug)) {
                $service->slug = Str::slug($service->translate('title', 'en') ?? $service->translate('title', 'ru') ?? 'service');
            }
        });

        static::saved(function (Service $service) {
            if ($service->wasChanged('image_path') || $service->wasRecentlyCreated) {
                $original = $service->getOriginal('image_path');
                if ($service->wasChanged('image_path') && filled($original)) {
                    ImageVariants::delete($original);
                }
                if (filled($service->image_path)) {
                    ImageVariants::generate($service->image_path);
                }
            }
        });

        static::deleted(function (Service $service) {
            if (filled($service->image_path)) {
                ImageVariants::delete($service->image_path);
            }
        });
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array<int, string>
     */
    public function bodyParagraphs(): array
    {
        return collect(preg_split('/\R{2,}/u', (string) $this->translate('body')))
            ->map(fn ($p) => trim($p))
            ->filter()
            ->values()
            ->all();
    }
}
