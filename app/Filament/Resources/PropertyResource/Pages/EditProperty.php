<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('copyPublicLink')
                ->label('Copier le lien public')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->alpineClickHandler(fn (): string => PropertyResource::copyToClipboardJs($this->getRecord()->publicUrl())),
            Actions\DeleteAction::make(),
        ];
    }
}
