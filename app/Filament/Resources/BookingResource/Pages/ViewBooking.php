<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Actions\Bookings\ApproveBooking;
use App\Actions\Bookings\RejectBooking;
use App\Exceptions\BookingException;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approuver')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn (Booking $record): bool => $record->isPending())
                ->action(fn (Booking $record, ApproveBooking $approve) => $this->decide(fn () => $approve->execute($record), 'Visite confirmée')),
            Action::make('reject')
                ->label('Refuser')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->visible(fn (Booking $record): bool => $record->isPending())
                ->action(fn (Booking $record, RejectBooking $reject) => $this->decide(fn () => $reject->execute($record), 'Demande refusée')),
        ];
    }

    private function decide(callable $callback, string $successTitle): void
    {
        try {
            $callback();
            $this->refreshFormData(['status', 'decided_at']);
            Notification::make()->title($successTitle)->success()->send();
        } catch (BookingException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
