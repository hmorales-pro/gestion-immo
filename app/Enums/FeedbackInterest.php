<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FeedbackInterest: string implements HasColor, HasLabel
{
    case Yes = 'yes';
    case Maybe = 'maybe';
    case No = 'no';

    public function getLabel(): string
    {
        return match ($this) {
            self::Yes => 'Intéressé',
            self::Maybe => 'Hésitant',
            self::No => 'Pas intéressé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Yes => 'success',
            self::Maybe => 'warning',
            self::No => 'gray',
        };
    }
}
