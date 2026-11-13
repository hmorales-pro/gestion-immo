<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FeedbackPrice: string implements HasLabel
{
    case High = 'high';
    case Fair = 'fair';
    case Low = 'low';

    public function getLabel(): string
    {
        return match ($this) {
            self::High => 'Trop cher',
            self::Fair => 'Prix correct',
            self::Low => 'Bon prix',
        };
    }
}
