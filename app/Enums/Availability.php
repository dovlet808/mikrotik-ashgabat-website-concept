<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Availability: string implements HasColor, HasLabel
{
    case InStock = 'in_stock';
    case OnOrder = 'on_order';
    case OutOfStock = 'out_of_stock';
    case Confirm = 'confirm';

    public function getLabel(): string
    {
        return __('site.availability.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::InStock => 'success',
            self::OnOrder => 'info',
            self::OutOfStock => 'danger',
            self::Confirm => 'gray',
        };
    }

    public function schemaOrg(): ?string
    {
        return match ($this) {
            self::InStock => 'https://schema.org/InStock',
            self::OnOrder => 'https://schema.org/PreOrder',
            self::OutOfStock => 'https://schema.org/OutOfStock',
            self::Confirm => null,
        };
    }
}
