<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class BookingStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $bookings = fn () => Booking::query()->whereHas('property', fn (Builder $q) => $q->ownedBy(auth()->id()));

        return [
            Stat::make('Demandes en attente', $bookings()->where('status', BookingStatus::Pending)->count())
                ->color('warning'),
            Stat::make('Visites confirmées à venir', $bookings()
                ->where('status', BookingStatus::Confirmed)
                ->whereHas('slot', fn (Builder $q) => $q->where('start_time', '>=', now()))
                ->count())
                ->color('success'),
            Stat::make('Biens', Property::query()->ownedBy(auth()->id())->count()),
        ];
    }
}
