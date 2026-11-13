<?php

namespace App\Actions\Bookings;

use App\Enums\BookingMode;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\PropertySlot;
use App\Notifications\BookingRescheduled;
use App\Notifications\OwnerApprovalRequested;
use Illuminate\Support\Facades\DB;

/**
 * Moves an active visit to another free slot of the same property.
 *
 * - By the agent: the status is kept (a confirmed visit stays confirmed).
 * - By the prospect: the new slot goes back through validation, unless the property
 *   confirms instantly.
 */
class RescheduleBooking
{
    public function __construct(
        private readonly EnsureProspectIsFree $ensureProspectIsFree,
        private readonly NotifyWaitlist $notifyWaitlist,
    ) {}

    /**
     * @throws BookingException
     */
    public function execute(Booking $booking, int $newSlotId, bool $byProspect = false): Booking
    {
        $previousLabel = $booking->slot->label();
        $wasConfirmed = $booking->isConfirmed();

        DB::transaction(function () use ($booking, $newSlotId, $byProspect): void {
            $locked = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isActive()) {
                throw BookingException::alreadyDecided();
            }

            /** @var PropertySlot|null $newSlot */
            $newSlot = PropertySlot::query()
                ->where('property_id', $locked->property_id)
                ->whereKey($newSlotId)
                ->lockForUpdate()
                ->first();

            if ($newSlot === null || $newSlot->id === $locked->property_slot_id || ! $newSlot->isAvailable()) {
                throw BookingException::slotUnavailable();
            }

            $this->ensureProspectIsFree->execute($newSlot, $locked->prospect_email, except: $locked);

            $needsApproval = $byProspect && $booking->property->booking_mode !== BookingMode::Instant;
            $status = $needsApproval ? BookingStatus::Pending : $locked->status;

            PropertySlot::query()->whereKey($locked->property_slot_id)->update(['is_booked' => false]);
            $newSlot->update(['is_booked' => $status === BookingStatus::Confirmed]);

            $locked->update([
                'property_slot_id' => $newSlot->id,
                'status' => $status,
                'decided_at' => $needsApproval ? null : $locked->decided_at,
                'sequence' => $locked->sequence + 1,
            ]);
        });

        $booking->refresh()->load(['property.agent', 'slot']);
        $property = $booking->property;
        $notification = new BookingRescheduled($booking, $previousLabel);

        $booking->notify($notification);
        $property->agent->notify($notification);

        $owner = $property->ownerRoute();

        if ($booking->isPending() && $property->booking_mode === BookingMode::OwnerApproval) {
            $owner->notify(new OwnerApprovalRequested($booking, rescheduled: true));
        } else {
            $owner->notify($notification);
        }

        if ($wasConfirmed || $booking->isConfirmed()) {
            $property->occupantRoute()?->notify($notification);
        }

        // The previous slot is free again.
        $this->notifyWaitlist->execute($property);

        return $booking;
    }
}
