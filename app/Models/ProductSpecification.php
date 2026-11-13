<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecification extends Model
{
    use HasTranslations;

    protected $fillable = ['product_id', 'group', 'label', 'value', 'sort_order'];

    protected function casts(): array
    {
        return [
            'group' => 'array',
            'label' => 'array',
            'value' => 'array',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Load the parent explicitly: Filament's repeater saves specification rows without their `product`
        // relation, and reading `$spec->product` there is a lazy load, which strict mode turns into an error.
        $refresh = fn (ProductSpecification $spec) => Product::query()->find($spec->product_id)?->refreshSearchText();

        static::saved($refresh);
        static::deleted($refresh);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
