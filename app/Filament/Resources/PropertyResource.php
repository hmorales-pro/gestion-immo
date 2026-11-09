<?php

namespace App\Filament\Resources;

use App\Enums\BookingMode;
use App\Filament\Resources\PropertyResource\Pages;
use App\Filament\Resources\PropertyResource\RelationManagers;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Js;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $modelLabel = 'bien';

    protected static ?string $pluralModelLabel = 'biens';

    protected static ?string $navigationLabel = 'Biens';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $breadcrumb = 'Biens';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    /** Tenant isolation: an agent only ever sees their own properties. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ownedBy(auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Le bien')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Titre')
                        ->placeholder('T3 lumineux — Croix-Rousse')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('address')
                        ->label('Adresse')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->label('Description publique')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('slot_duration')
                        ->label('Durée d\'un créneau')
                        ->options([15 => '15 min', 20 => '20 min', 30 => '30 min', 45 => '45 min', 60 => '1 h', 90 => '1 h 30'])
                        ->default(30)
                        ->required()
                        ->native(false),
                    Forms\Components\Select::make('booking_mode')
                        ->label('Mode de réservation')
                        ->options(BookingMode::class)
                        ->default(BookingMode::OwnerApproval)
                        ->required()
                        ->native(false)
                        ->live()
                        ->helperText(fn (Forms\Get $get): ?string => ($mode = $get('booking_mode')) instanceof BookingMode
                            ? $mode->getDescription()
                            : BookingMode::tryFrom((string) $mode)?->getDescription()),
                ]),

            Forms\Components\Section::make('Le propriétaire')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('owner_name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('owner_email')
                        ->label('E-mail')
                        ->email()
                        ->rule('email:rfc,strict,filter')
                        ->required()
                        ->maxLength(190),
                    Forms\Components\TextInput::make('owner_phone')
                        ->label('Téléphone')
                        ->tel()
                        ->rule('phone:FR,INTERNATIONAL')
                        ->validationMessages(['phone' => 'Ce numéro de téléphone ne semble pas valide.'])
                        ->helperText('Reçoit le lien de validation par SMS.')
                        ->maxLength(32),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Bien')
                    ->description(fn (Property $record): string => $record->address)
                    ->searchable(['title', 'address'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner_name')
                    ->label('Propriétaire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('booking_mode')
                    ->label('Mode')
                    ->badge(),
                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Demandes')
                    ->counts('bookings')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('booking_mode')
                    ->label('Mode')
                    ->options(BookingMode::class),
            ])
            ->actions([
                Tables\Actions\Action::make('copyPublicLink')
                    ->label('Copier le lien')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->alpineClickHandler(fn (Property $record): string => static::copyToClipboardJs($record->publicUrl())),
                Tables\Actions\Action::make('openPublicLink')
                    ->label('Voir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Property $record): string => $record->publicUrl(), shouldOpenInNewTab: true)
                    ->iconButton()
                    ->tooltip('Ouvrir la page publique'),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Runs inside the click gesture so the Clipboard API is allowed by every browser. */
    public static function copyToClipboardJs(string $text): string
    {
        $text = Js::from($text);

        return <<<JS
            window.navigator.clipboard.writeText({$text})
                .then(() => new FilamentNotification().title('Lien public copié').success().send())
                .catch(() => window.prompt('Copiez le lien :', {$text}))
            JS;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SlotsRelationManager::class,
            RelationManagers\BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
