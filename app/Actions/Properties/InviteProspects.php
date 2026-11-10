<?php

namespace App\Actions\Properties;

use App\Models\Property;
use App\Notifications\VisitInvitation;
use Illuminate\Support\Facades\Notification;

/** Emails one or more prospects the property's booking link. */
class InviteProspects
{
    /**
     * @param  list<string>  $emails
     * @return int number of invitations sent
     */
    public function execute(Property $property, array $emails, ?string $name = null, ?string $message = null): int
    {
        $emails = collect($emails)
            ->map(fn (string $email) => mb_strtolower(trim($email)))
            ->filter()
            ->unique()
            ->values();

        $property->loadMissing('agent');

        foreach ($emails as $email) {
            Notification::route('mail', $email)->notify(new VisitInvitation(
                $property,
                $emails->count() === 1 ? $name : null,
                $message,
            ));
        }

        return $emails->count();
    }
}
