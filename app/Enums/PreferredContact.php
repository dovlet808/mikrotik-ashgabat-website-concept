<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PreferredContact: string implements HasLabel
{
    case WhatsApp = 'whatsapp';
    case Phone = 'phone';
    case Email = 'email';

    public function getLabel(): string
    {
        return __('site.form.contact_via.'.$this->value);
    }
}
