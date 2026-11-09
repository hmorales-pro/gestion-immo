<?php

namespace App\Filament\Tables;

use App\Actions\Bookings\ApproveBooking;
use App\Actions\Bookings\RejectBooking;
use App\Exceptions\BookingException;
use App\Models\Booking;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

/** "Approve" / "Reject" manual overrides, shared by the booking tables. */
class BookingActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approuver')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Confirmer la visite ?')
            ->modalDescription('Le créneau sera réservé et le visiteur, le propriétaire et vous recevrez une confirmation.')
            ->visible(fn (Booking $record): bool => $record->isPending())
            ->authorize(fn (Booking $record): bool => auth()->user()->can('update', $record))
            ->action(function (Booking $record, ApproveBooking $approve): void {
                try {
                    $approve->execute($record);
                    Notification::make()->title('Visite confirmée')->success()->send();
                } catch (BookingException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Refuser')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Refuser la demande ?')
            ->modalDescription('Le créneau redevient disponible et le visiteur est prévenu.')
            ->visible(fn (Booking $record): bool => $record->isPending())
            ->authorize(fn (Booking $record): bool => auth()->user()->can('update', $record))
            ->action(function (Booking $record, RejectBooking $reject): void {
                try {
                    $reject->execute($record);
                    Notification::make()->title('Demande refusée')->success()->send();
                } catch (BookingException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
