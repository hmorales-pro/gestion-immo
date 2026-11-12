<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\PropertySlot;
use App\Models\User;

class PropertySlotPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PropertySlot $slot): bool
    {
        return $slot->property->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PropertySlot $slot): bool
    {
        return $this->view($user, $slot) && ! $this->hasActiveRequest($slot);
    }

    /** A slot with a request (pending or confirmed) is kept for the history. */
    public function delete(User $user, PropertySlot $slot): bool
    {
        return $this->view($user, $slot) && ! $slot->bookings()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }

    private function hasActiveRequest(PropertySlot $slot): bool
    {
        return $slot->is_booked
            || $slot->bookings()->where('status', BookingStatus::Pending)->exists();
    }
}
