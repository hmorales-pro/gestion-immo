<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasLabel
{
    case Pending = 'PENDING';
    case Confirmed = 'CONFIRMED';
    case Rejected = 'REJECTED';
    case Cancelled = 'CANCELLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmée',
            self::Rejected => 'Refusée',
            self::Cancelled => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
        };
    }

    /** Statuses that occupy the slot and the prospect's agenda. */
    public function isActive(): bool
    {
        return $this === self::Pending || $this === self::Confirmed;
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Pending, self::Confirmed];
    }
}
