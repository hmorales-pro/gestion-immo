<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Tables\BookingActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return array_map(
            fn (Action $action) => $action->after(fn () => $this->getRecord()->refresh()->load(['property', 'slot'])),
            BookingActions::all(Action::class),
        );
    }
}
