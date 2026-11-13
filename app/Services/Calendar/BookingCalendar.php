<?php

namespace App\Services\Calendar;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

/**
 * Turns bookings into calendar invitations, "add to calendar" links and the agent feed.
 */
class BookingCalendar
{
    /**
     * Invitation for one booking. Confirmed → REQUEST, cancelled / rejected → CANCEL,
     * pending → REQUEST with STATUS:TENTATIVE.
     */
    /**
     * @param  array{name: string, email: string}|null  $attendee  the recipient only: participants never see
     *                                                             each other's e-mail address.
     */
    public function invitation(Booking $booking, ?array $attendee = null): IcsCalendar
    {
        $method = $booking->isActive() ? IcsCalendar::METHOD_REQUEST : IcsCalendar::METHOD_CANCEL;

        return (new IcsCalendar($method))->addEvent($this->event($booking, attendees: $attendee ? [$attendee] : []));
    }

    /** Subscribable feed of an agent's upcoming and recent visits. */
    public function agentFeed(User $agent): IcsCalendar
    {
        $calendar = new IcsCalendar(IcsCalendar::METHOD_PUBLISH, config('app.name').' — '.$agent->name);

        Booking::query()
            ->with(['property', 'slot'])
            ->whereHas('property', fn ($q) => $q->ownedBy($agent))
            ->whereIn('status', [...BookingStatus::active(), BookingStatus::Cancelled])
            ->whereHas('slot', fn ($q) => $q->where('start_time', '>=', now()->subDays(30)))
            ->limit(1000)
            ->get()
            ->each(fn (Booking $booking) => $calendar->addEvent($this->event($booking, forAgent: true)));

        return $calendar;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  list<array{name: string, email: string}>  $attendees
     */
    public function event(Booking $booking, bool $forAgent = false, array $attendees = []): array
    {
        $property = $booking->property;
        $agent = $property->agent;

        $description = collect([
            'Visite : '.$property->title,
            'Adresse : '.$property->address,
            $forAgent ? 'Visiteur : '.$booking->prospect_name.' — '.$booking->prospect_phone : null,
            'Agent : '.$agent->name.' — '.$agent->email,
            $forAgent ? 'Statut : '.$booking->status->getLabel() : null,
        ])->filter()->implode("\n");

        return [
            'uid' => $booking->calendarUid(),
            'start' => $booking->slot->start_time,
            'end' => $booking->slot->end_time,
            'summary' => ($forAgent ? $booking->prospect_name.' · ' : '').'Visite — '.$property->title,
            'location' => $property->address,
            'description' => $description,
            'url' => null,
            'sequence' => $booking->sequence,
            'updated' => $booking->updated_at,
            'status' => match ($booking->status) {
                BookingStatus::Confirmed => 'CONFIRMED',
                BookingStatus::Pending => 'TENTATIVE',
                default => 'CANCELLED',
            },
            'organizer' => ['name' => $agent->name, 'email' => $agent->email],
            'attendees' => $attendees,
        ];
    }

    public function googleUrl(Booking $booking): string
    {
        $event = $this->event($booking);

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $event['summary'],
            'dates' => IcsCalendar::date($event['start']).'/'.IcsCalendar::date($event['end']),
            'details' => $event['description'],
            'location' => $event['location'],
        ]);
    }

    public function outlookUrl(Booking $booking): string
    {
        $event = $this->event($booking);

        return 'https://outlook.live.com/calendar/0/deeplink/compose?'.http_build_query([
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => $event['summary'],
            'startdt' => $event['start']->copy()->utc()->toIso8601ZuluString(),
            'enddt' => $event['end']->copy()->utc()->toIso8601ZuluString(),
            'body' => $event['description'],
            'location' => $event['location'],
        ]);
    }

    /** "Subscribe" link: Google Calendar adds a webcal feed from this URL. */
    public static function googleSubscribeUrl(string $feedUrl): string
    {
        return 'https://calendar.google.com/calendar/r?cid='.urlencode(self::webcal($feedUrl));
    }

    public static function webcal(string $url): string
    {
        return preg_replace('#^https?://#', 'webcal://', $url);
    }
}
