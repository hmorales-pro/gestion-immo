<?php

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Filament\Tables\BookingActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Demandes de visite';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('slot'))
            ->defaultSort('created_at', 'desc')
            ->columns(BookingResource::tableColumns(withProperty: false))
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(BookingStatus::class),
            ])
            ->actions([
                BookingActions::approve()->iconButton()->tooltip('Approuver'),
                BookingActions::reject()->iconButton()->tooltip('Refuser'),
                Tables\Actions\ActionGroup::make([
                    BookingActions::reschedule(),
                    BookingActions::requestInformation(),
                    BookingActions::cancel(),
                ]),
            ]);
    }
}
