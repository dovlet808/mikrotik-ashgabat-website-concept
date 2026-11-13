<?php

namespace Database\Seeders;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\PreferredContact;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Optional sample inquiries for presenting the admin panel. Not run by default.
 * All names and numbers are fictitious and clearly marked as demo data.
 *
 *   php artisan db:seed --class=DemoInquirySeeder
 */
class DemoInquirySeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::query()->where('slug', 'hap-ax2')->first();
        $service = Service::query()->where('slug', 'installation')->first();

        $rows = [
            [
                'type' => InquiryType::Product, 'status' => InquiryStatus::New, 'name' => 'Демо-клиент 1',
                'phone' => '+993 00 000001', 'preferred_contact' => PreferredContact::WhatsApp,
                'message' => '[ДЕМО] Нужен роутер для квартиры, около 15 устройств.', 'product_id' => $product?->getKey(),
                'product_snapshot' => $product ? 'MikroTik hAP ax² (C52iG-5HaxD2HaxD-TC) — 2 850 манат' : null,
                'created_at' => now()->subHours(3),
            ],
            [
                'type' => InquiryType::Service, 'status' => InquiryStatus::InProgress, 'name' => 'Демо-клиент 2',
                'phone' => '+993 00 000002', 'preferred_contact' => PreferredContact::Phone,
                'message' => '[ДЕМО] Монтаж Wi‑Fi в офисе на двух этажах.', 'service_id' => $service?->getKey(),
                'created_at' => now()->subDay(),
            ],
            [
                'type' => InquiryType::Consultation, 'status' => InquiryStatus::Answered, 'name' => 'Демо-клиент 3',
                'phone' => '+993 00 000003', 'email' => 'demo@example.com', 'preferred_contact' => PreferredContact::Email,
                'message' => '[ДЕМО] Какое оборудование выбрать для склада и двора?', 'created_at' => now()->subDays(3),
            ],
        ];

        foreach ($rows as $row) {
            Inquiry::query()->create($row + ['locale' => 'ru', 'page_url' => url('/')]);
        }
    }
}
