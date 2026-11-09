<?php

namespace App\Filament\Resources;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Tables\BookingActions;
use App\Models\Booking;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $modelLabel = 'demande de visite';

    protected static ?string $pluralModelLabel = 'demandes de visite';

    protected static ?string $navigationLabel = 'Demandes de visite';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $breadcrumb = 'Demandes de visite';

    protected static ?int $navigationSort = 2;

    /** Tenant isolation: only bookings on the current agent's properties. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('property', fn (Builder $q) => $q->ownedBy(auth()->id()))
            ->with(['property', 'slot']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', BookingStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * @return list<Tables\Columns\Column>
     */
    public static function tableColumns(bool $withProperty = true): array
    {
        return array_values(array_filter([
            Tables\Columns\TextColumn::make('slot.start_time')
                ->label('Créneau')
                ->dateTime('d/m/Y H:i')
                ->description(fn (Booking $record): string => $record->slot->start_time->format('H:i').' – '.$record->slot->end_time->format('H:i'))
                ->sortable(),
            $withProperty ? Tables\Columns\TextColumn::make('property.title')
                ->label('Bien')
                ->limit(40)
                ->searchable() : null,
            Tables\Columns\TextColumn::make('prospect_name')
                ->label('Visiteur')
                ->description(fn (Booking $record): string => $record->prospect_email)
                ->searchable(['prospect_name', 'prospect_email']),
            Tables\Columns\TextColumn::make('prospect_phone')
                ->label('Téléphone')
                ->copyable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('employment_status')
                ->label('Situation')
                ->badge()
                ->color('gray')
                ->toggleable(),
            Tables\Columns\TextColumn::make('status')
                ->label('Statut')
                ->badge()
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Reçue le')
                ->since()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(static::tableColumns())
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(BookingStatus::class),
                Tables\Filters\SelectFilter::make('property')
                    ->label('Bien')
                    ->relationship('property', 'title', fn (Builder $query) => $query->ownedBy(auth()->id()))
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('upcoming')
                    ->label('Visites à venir')
                    ->query(fn (Builder $query) => $query->whereHas('slot', fn (Builder $q) => $q->where('start_time', '>=', now()))),
            ])
            ->actions([
                BookingActions::approve()->iconButton()->tooltip('Approuver'),
                BookingActions::reject()->iconButton()->tooltip('Refuser'),
                Tables\Actions\ViewAction::make()->iconButton(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Visite')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('property.title')->label('Bien'),
                    Infolists\Components\TextEntry::make('property.address')->label('Adresse'),
                    Infolists\Components\TextEntry::make('status')->label('Statut')->badge(),
                    Infolists\Components\TextEntry::make('slot.start_time')->label('Début')->dateTime('d/m/Y H:i'),
                    Infolists\Components\TextEntry::make('slot.end_time')->label('Fin')->dateTime('d/m/Y H:i'),
                    Infolists\Components\TextEntry::make('decided_at')->label('Traitée le')->dateTime('d/m/Y H:i')->placeholder('—'),
                ]),
            Infolists\Components\Section::make('Visiteur')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('prospect_name')->label('Nom'),
                    Infolists\Components\TextEntry::make('employment_status')->label('Situation professionnelle'),
                    Infolists\Components\TextEntry::make('prospect_email')->label('E-mail')->copyable(),
                    Infolists\Components\TextEntry::make('prospect_phone')->label('Téléphone')->copyable(),
                    Infolists\Components\TextEntry::make('message')->label('Message')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }
}
