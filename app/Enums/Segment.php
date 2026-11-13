<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Segment: string implements HasLabel
{
    case Home = 'home';
    case SmallBusiness = 'small_business';
    case Office = 'office';
    case Commercial = 'commercial';
    case Enterprise = 'enterprise';
    case Outdoor = 'outdoor';

    public function getLabel(): string
    {
        return __('site.segments.'.$this->value.'.label');
    }

    public function image(): string
    {
        return match ($this) {
            self::Home => 'solution-home',
            self::SmallBusiness => 'solution-small-business',
            self::Office => 'solution-office',
            self::Commercial => 'solution-commercial',
            self::Enterprise => 'solution-enterprise',
            self::Outdoor => 'solution-outdoor',
        };
    }
}
