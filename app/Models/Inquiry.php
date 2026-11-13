<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\PreferredContact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'status', 'name', 'phone', 'email', 'preferred_contact', 'message', 'product_id', 'service_id',
        'product_snapshot', 'locale', 'page_url', 'ip_hash', 'user_agent', 'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => InquiryType::class,
            'status' => InquiryStatus::class,
            'preferred_contact' => PreferredContact::class,
            'handled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Inquiry $inquiry) {
            if ($inquiry->isDirty('status') && $inquiry->status !== InquiryStatus::New && $inquiry->handled_at === null) {
                $inquiry->handled_at = now();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(InquiryNote::class)->latest();
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', InquiryStatus::New);
    }

    public function whatsappDigits(): string
    {
        return preg_replace('/\D+/', '', $this->phone);
    }
}
