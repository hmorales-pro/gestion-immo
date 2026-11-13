<?php

namespace App\Filament\Tables;

use App\Actions\Bookings\ApproveBooking;
use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\RejectBooking;
use App\Actions\Bookings\RequestInformation;
use App\Actions\Bookings\RescheduleBooking;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\PropertySlot;
use Filament\Actions\Action as PageAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;

/**
 * Agent actions on a visit request, shared by the booking tables and the booking page.
 * Every action emails the people involved (and updates their calendars when relevant).
 *
 * @template T of TableAction|PageAction
 */
class BookingActions
{
    /**
     * @param  class-string<TableAction|PageAction>  $class
     * @return list<TableAction|PageAction>
     */
    public static function all(string $class = TableAction::class): array
    {
        return [
            static::approve($class),
            static::reject($class),
            static::reschedule($class),
            static::voucher($class),
            static::requestInformation($class),
            static::cancel($class),
        ];
    }

    /**
     * Visit voucher signed by the visitor on the agent's phone.
     *
     * @param  class-string<TableAction|PageAction>  $class
     */
    public static function voucher(string $class = TableAction::class): TableAction|PageAction
    {
        return static::base($class, 'voucher')
            ->label(fn (Booking $record): string => $record->hasSignedVoucher() ? 'Bon de visite signé' : 'Faire signer le bon de visite')
            ->icon('heroicon-o-pencil-square')
            ->color(fn (Booking $record): string => $record->hasSignedVoucher() ? 'success' : 'gray')
            ->visible(fn (Booking $record): bool => $record->isConfirmed())
            ->url(fn (Booking $record): string => route('voucher.sign', $record));
    }

    /** @param class-string<TableAction|PageAction> $class */
    public static function approve(string $class = TableAction::class): TableAction|PageAction
    {
        return static::base($class, 'approve')
            ->label('Approuver')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Confirmer la visite ?')
            ->modalDescription('Le créneau sera réservé. Le visiteur, le propriétaire et vous recevrez une confirmation avec l\'invitation d\'agenda.')
            ->visible(fn (Booking $record): bool => $record->isPending())
            ->action(fn (Booking $record, ApproveBooking $approve) => static::run(fn () => $approve->execute($record), 'Visite confirmée'));
    }

    /** @param class-string<TableAction|PageAction> $class */
    public static function reject(string $class = TableAction::class): TableAction|PageAction
    {
        return static::base($class, 'reject')
            ->label('Refuser')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Refuser la demande ?')
            ->modalDescription('Le créneau redevient disponible et le visiteur est invité à en choisir un autre.')
            ->visible(fn (Booking $record): bool => $record->isPending())
            ->action(fn (Booking $record, RejectBooking $reject) => static::run(fn () => $reject->execute($record), 'Demande refusée'));
    }

    /** @param class-string<TableAction|PageAction> $class */
    public static function reschedule(string $class = TableAction::class): TableAction|PageAction
    {
        return static::base($class, 'reschedule')
            ->label('Déplacer')
            ->icon('heroicon-o-arrows-right-left')
            ->color('gray')
            ->modalHeading('Déplacer la visite')
            ->modalDescription('Le visiteur et le propriétaire sont prévenus et leur agenda est mis à jour.')
            ->modalSubmitActionLabel('Déplacer')
            ->visible(fn (Booking $record): bool => static::isUpcomingAndActive($record))
            ->form(fn (Booking $record): array => [
                Forms\Components\Select::make('slot_id')
                    ->label('Nouveau créneau')
                    ->options(fn () => $record->property->slots()->getQuery()->available()->limit(200)->get()
                        ->mapWithKeys(fn (PropertySlot $slot) => [$slot->id => ucfirst($slot->label())]))
                    ->searchable()
                    ->required()
                    ->helperText('Seuls les créneaux libres de ce bien sont proposés.'),
            ])
            ->action(fn (Booking $record, array $data, RescheduleBooking $reschedule) => static::run(
                fn () => $reschedule->execute($record, (int) $data['slot_id']),
                'Visite déplacée',
            ));
    }

    /** @param class-string<TableAction|PageAction> $class */
    public static function requestInformation(string $class = TableAction::class): TableAction|PageAction
    {
        return static::base($class, 'requestInformation')
            ->label('Demander des infos')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->color('gray')
            ->modalHeading('Demander des informations au visiteur')
            ->modalDescription('Le visiteur reçoit un e-mail avec votre question et un lien pour compléter ses informations. Il peut aussi vous répondre directement.')
            ->modalSubmitActionLabel('Envoyer')
            ->visible(fn (Booking $record): bool => $record->isActive())
            ->form([
                Forms\Components\Textarea::make('question')
                    ->label('Votre question')
                    ->placeholder('Pouvez-vous préciser votre date d\'emménagement souhaitée et le montant de vos revenus ?')
                    ->rows(4)
                    ->required()
                    ->maxLength(1000),
            ])
            ->action(function (Booking $record, array $data, RequestInformation $request): void {
                $request->execute($record, $data['question']);
                Notification::make()->title('Demande envoyée à '.$record->prospect_email)->success()->send();
            });
    }

    /** @param class-string<TableAction|PageAction> $class */
    public static function cancel(string $class = TableAction::class): TableAction|PageAction
    {
        return static::base($class, 'cancel')
            ->label('Annuler')
            ->icon('heroicon-o-calendar-days')
            ->color('danger')
            ->modalHeading('Annuler la visite ?')
            ->modalDescription('Le créneau redevient disponible. Le visiteur et le propriétaire sont prévenus et l\'événement est retiré de leur agenda.')
            ->modalSubmitActionLabel('Annuler la visite')
            ->visible(fn (Booking $record): bool => static::isUpcomingAndActive($record))
            ->form([
                Forms\Components\Textarea::make('reason')
                    ->label('Motif (transmis au visiteur)')
                    ->rows(3)
                    ->maxLength(500),
            ])
            ->action(fn (Booking $record, array $data, CancelBooking $cancel) => static::run(
                fn () => $cancel->execute($record, $data['reason'] ?? null),
                'Visite annulée',
            ));
    }

    /**
     * @param  class-string<TableAction|PageAction>  $class
     */
    private static function base(string $class, string $name): TableAction|PageAction
    {
        return $class::make($name)
            ->authorize(fn (Booking $record): bool => auth()->user()->can('update', $record));
    }

    private static function isUpcomingAndActive(Booking $record): bool
    {
        return $record->isActive() && $record->slot->start_time->isFuture();
    }

    private static function run(callable $callback, string $successTitle): void
    {
        try {
            $callback();
            Notification::make()->title($successTitle)->success()->send();
        } catch (BookingException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
