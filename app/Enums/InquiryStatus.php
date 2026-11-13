<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InquiryStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Answered = 'answered';
    case Closed = 'closed';
    case Spam = 'spam';

    public function getLabel(): string
    {
        return __('admin.inquiry_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::InProgress => 'info',
            self::Answered => 'success',
            self::Closed => 'gray',
            self::Spam => 'danger',
        };
    }
}
