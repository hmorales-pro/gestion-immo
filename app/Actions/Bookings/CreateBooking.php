<?php

namespace App\Actions\Bookings;

use App\Enums\BookingMode;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertySlot;
use App\Notifications\BookingReceived;
use App\Notifications\OwnerApprovalRequested;
use App\Notifications\VisitRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Propaganistas\LaravelPhone\PhoneNumber;

class CreateBooking
{
    public function __construct(
        private readonly ApproveBooking $approve,
        private readonly EnsureProspectIsFree $ensureProspectIsFree,
    ) {}

    /**
     * @param  array{prospect_name: string, prospect_email: string, prospect_phone: string, employment_status: string, message?: string|null}  $prospect
     *
     * @throws BookingException
     */
    public function execute(Property $property, int $slotId, array $prospect): Booking
    {
        $prospect['prospect_email'] = mb_strtolower(trim($prospect['prospect_email']));
        $prospect['prospect_phone'] = (new PhoneNumber($prospect['prospect_phone'], 'FR'))->formatE164();

        $booking = DB::transaction(function () use ($property, $slotId, $prospect): Booking {
            /** @var PropertySlot|null $slot */
            $slot = $property->slots()->getQuery()->whereKey($slotId)->lockForUpdate()->first();

            if ($slot === null || ! $slot->isAvailable()) {
                throw BookingException::slotUnavailable();
            }

            $this->ensureProspectIsFree->execute($slot, $prospect['prospect_email']);

            return $property->bookings()->create([
                ...$prospect,
                'property_slot_id' => $slot->id,
                'status' => BookingStatus::Pending,
            ]);
        });

        $booking->load(['property.agent', 'slot']);

        $property->agent->notify(new VisitRequested($booking));

        match ($property->booking_mode) {
            BookingMode::OwnerApproval => Notification::route('mail', $property->owner_email)
                ->route('sms', $property->owner_phone)
                ->notify(new OwnerApprovalRequested($booking)),
            BookingMode::Instant => $this->approve->execute($booking, notifyOwner: true),
            BookingMode::AgentApproval => null,
        };

        if ($property->booking_mode !== BookingMode::Instant) {
            $booking->notify(new BookingReceived($booking));
        }

        return $booking->refresh();
    }
}
