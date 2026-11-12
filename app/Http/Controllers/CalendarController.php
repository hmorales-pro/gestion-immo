<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Services\Calendar\BookingCalendar;
use Illuminate\Http\Response;

class CalendarController extends Controller
{
    public function __construct(private readonly BookingCalendar $calendar) {}

    /** Agent feed, subscribed from Google Calendar / Apple Calendar / Outlook (read-only sync). */
    public function feed(string $token): Response
    {
        abort_unless(strlen($token) === 48, 404);

        $agent = User::query()->where('calendar_token', $token)->firstOrFail();

        return $this->ics($this->calendar->agentFeed($agent)->render(), 'immobooking.ics', inline: true);
    }

    /** Single visit, downloaded from the prospect's booking page or an email link. */
    public function booking(string $manageToken): Response
    {
        abort_unless(strlen($manageToken) === 64, 404);

        $booking = Booking::query()->with(['property.agent', 'slot'])->where('manage_token', $manageToken)->firstOrFail();

        return $this->ics($this->calendar->invitation($booking)->render(), 'visite.ics');
    }

    private function ics(string $content, string $filename, bool $inline = false): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
