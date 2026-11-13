<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InquiryType: string implements HasLabel
{
    case Product = 'product';
    case Service = 'service';
    case Consultation = 'consultation';
    case Contact = 'contact';

    public function getLabel(): string
    {
        return __('admin.inquiry_type.'.$this->value);
    }
}
