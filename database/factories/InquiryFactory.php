<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\PreferredContact;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    public function definition(): array
    {
        return [
            'type' => InquiryType::Consultation,
            'status' => InquiryStatus::New,
            'name' => 'Тест Клиент',
            'phone' => '+993 00 000000',
            'email' => null,
            'preferred_contact' => PreferredContact::WhatsApp,
            'message' => 'Тестовое сообщение',
            'locale' => 'ru',
        ];
    }
}
