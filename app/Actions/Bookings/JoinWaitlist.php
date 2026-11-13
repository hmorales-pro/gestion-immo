<?php

namespace App\Actions\Bookings;

use App\Models\Property;
use App\Models\WaitlistEntry;

class JoinWaitlist
{
    public function execute(Property $property, string $name, string $email): WaitlistEntry
    {
        return $property->waitlist()->updateOrCreate(
            ['email' => mb_strtolower(trim($email))],
            ['name' => trim($name)],
        );
    }
}
