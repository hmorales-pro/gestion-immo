<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PropertyResource;
use App\Services\Calendar\BookingCalendar;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/** Lets the agent subscribe to their visits from Google Calendar, Apple Calendar or Outlook. */
class CalendarSync extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Synchroniser mon agenda';

    protected static ?string $title = 'Synchroniser mon agenda';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.calendar-sync';

    protected function getViewData(): array
    {
        $feed = auth()->user()->calendarFeedUrl();

        return [
            'feedUrl' => $feed,
            'webcalUrl' => BookingCalendar::webcal($feed),
            'googleUrl' => BookingCalendar::googleSubscribeUrl($feed),
            'copyJs' => PropertyResource::copyToClipboardJs($feed),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate')
                ->label('Régénérer le lien')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('L\'ancien lien cessera de fonctionner : il faudra réabonner vos agendas.')
                ->action(function (): void {
                    auth()->user()->regenerateCalendarToken();
                    Notification::make()->title('Nouveau lien généré')->success()->send();
                }),
        ];
    }
}
