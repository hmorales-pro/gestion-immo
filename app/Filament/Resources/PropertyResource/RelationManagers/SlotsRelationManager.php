<?php

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use App\Actions\Slots\GenerateSlots;
use App\Models\Property;
use App\Models\PropertySlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class SlotsRelationManager extends RelationManager
{
    protected static string $relationship = 'slots';

    protected static ?string $title = 'Créneaux de visite';

    protected static ?string $modelLabel = 'créneau';

    protected static ?string $pluralModelLabel = 'créneaux';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DateTimePicker::make('start_time')
                ->label('Début')
                ->seconds(false)
                ->minDate(now())
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                    if ($state) {
                        $set('end_time', Carbon::parse($state)->addMinutes($this->getOwnerRecord()->slot_duration)->format('Y-m-d H:i'));
                    }
                }),
            Forms\Components\DateTimePicker::make('end_time')
                ->label('Fin')
                ->seconds(false)
                ->required()
                ->after('start_time')
                ->rule(fn (Forms\Get $get, ?PropertySlot $record) => function (string $attribute, mixed $value, \Closure $fail) use ($get, $record): void {
                    if (blank($get('start_time')) || blank($value)) {
                        return;
                    }

                    $overlaps = $this->getOwnerRecord()->slots()->getQuery()
                        ->overlapping(Carbon::parse($get('start_time')), Carbon::parse($value))
                        ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                        ->exists();

                    if ($overlaps) {
                        $fail('Ce créneau chevauche un créneau existant.');
                    }
                }),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (PropertySlot $record): string => $record->label())
            ->defaultSort('start_time')
            ->columns([
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Date')
                    ->formatStateUsing(fn (Carbon $state): string => ucfirst($state->locale(app()->getLocale())->isoFormat('ddd D MMM YYYY')))
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_range')
                    ->label('Horaire')
                    ->state(fn (PropertySlot $record): string => $record->start_time->format('H:i').' – '.$record->end_time->format('H:i')),
                Tables\Columns\IconColumn::make('is_booked')
                    ->label('Réservé')
                    ->boolean(),
                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Demandes')
                    ->counts('bookings')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\Filter::make('upcoming')
                    ->label('À venir uniquement')
                    ->default()
                    ->query(fn (Builder $query) => $query->where('start_time', '>=', now()->startOfDay())),
                Tables\Filters\TernaryFilter::make('is_booked')->label('Réservé'),
            ])
            ->headerActions([
                $this->generateSlotsAction(),
                Tables\Actions\CreateAction::make()->label('Créneau unique'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->action(function (Collection $records): void {
                        $deletable = $records->filter(fn (PropertySlot $slot) => ! $slot->bookings()->exists());
                        $deletable->each->delete();

                        if ($deletable->count() < $records->count()) {
                            Notification::make()
                                ->title(($records->count() - $deletable->count()).' créneau(x) avec des demandes ont été conservés.')
                                ->warning()
                                ->send();
                        }
                    }),
            ]);
    }

    protected function generateSlotsAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generateSlots')
            ->label('Générer des créneaux')
            ->icon('heroicon-o-sparkles')
            ->modalDescription(fn (): string => 'La plage horaire est découpée en créneaux de '.$this->getOwnerRecord()->slot_duration.' min. Les créneaux qui chevauchent un créneau existant sont ignorés.')
            ->form([
                Forms\Components\DatePicker::make('date')
                    ->label('Jour')
                    ->minDate(today())
                    ->default(today()->next(Carbon::SATURDAY))
                    ->native(false)
                    ->displayFormat('l d/m/Y')
                    ->required(),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TimePicker::make('from')
                        ->label('De')
                        ->seconds(false)
                        ->default('10:00')
                        ->required(),
                    Forms\Components\TimePicker::make('to')
                        ->label('À')
                        ->seconds(false)
                        ->default('12:00')
                        ->after('from')
                        ->required(),
                ]),
                Forms\Components\TextInput::make('weeks')
                    ->label('Répéter pendant (semaines)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(12)
                    ->default(1)
                    ->required(),
            ])
            ->action(function (array $data, GenerateSlots $generate): void {
                /** @var Property $property */
                $property = $this->getOwnerRecord();

                try {
                    $result = $generate->execute(
                        $property,
                        Carbon::parse($data['date']),
                        substr($data['from'], 0, 5),
                        substr($data['to'], 0, 5),
                        (int) $data['weeks'],
                    );
                } catch (InvalidArgumentException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()
                    ->title($result['created'].' créneau(x) créé(s)')
                    ->body($result['skipped'] ? $result['skipped'].' ignoré(s) (chevauchement ou passé).' : null)
                    ->success()
                    ->send();
            });
    }
}
