<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertySlot;
use App\Models\User;
use App\Services\Calendar\BookingCalendar;
use App\Services\Calendar\IcsCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private function bookingFor(User $agent, BookingStatus $status = BookingStatus::Confirmed, string $title = 'T3 Croix-Rousse'): Booking
    {
        $slot = PropertySlot::factory()
            ->for(Property::factory()->for($agent, 'agent')->state(['title' => $title]))
            ->at(now()->addDays(2)->setTime(10, 0))
            ->create();

        return Booking::factory()->create([
            'property_id' => $slot->property_id,
            'property_slot_id' => $slot->id,
            'status' => $status,
            'prospect_name' => 'Camille Martin',
        ]);
    }

    public function test_agent_feed_lists_only_own_visits(): void
    {
        $agent = User::factory()->create();
        $mine = $this->bookingFor($agent);
        $this->bookingFor($agent, BookingStatus::Rejected, 'Refusé');
        $this->bookingFor(User::factory()->create(), title: 'Autre agence');

        $response = $this->get($agent->calendarFeedUrl())
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=UTF-8');

        $ics = $response->getContent();
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString('METHOD:PUBLISH', $ics);
        $this->assertStringContainsString('UID:'.$mine->calendarUid(), $ics);
        $this->assertStringContainsString('SUMMARY:Camille Martin · Visite — T3 Croix-Rousse', $ics);
        $this->assertStringContainsString('DTSTART:'.$mine->slot->start_time->copy()->utc()->format('Ymd\THis\Z'), $ics);
        $this->assertStringNotContainsString('Refusé', $ics);
        $this->assertStringNotContainsString('Autre agence', $ics);
    }

    public function test_feed_token_can_be_regenerated(): void
    {
        $agent = User::factory()->create();
        $old = $agent->calendarFeedUrl();

        $agent->regenerateCalendarToken();

        $this->get($old)->assertNotFound();
        $this->get($agent->calendarFeedUrl())->assertOk();
        $this->get(route('calendar.feed', 'nope'))->assertNotFound();
    }

    public function test_prospect_can_download_ics(): void
    {
        $booking = $this->bookingFor(User::factory()->create());

        $ics = $this->get($booking->icsUrl())
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="visite.ics"')
            ->getContent();

        $this->assertStringContainsString('METHOD:REQUEST', $ics);
        $this->assertStringContainsString('STATUS:CONFIRMED', $ics);
        $this->assertStringContainsString('ATTENDEE;CN="Camille Martin"', $ics);
    }

    public function test_cancelled_booking_produces_cancel_invitation(): void
    {
        $booking = $this->bookingFor(User::factory()->create(), BookingStatus::Cancelled);
        $booking->update(['sequence' => 2]);

        $ics = app(BookingCalendar::class)->invitation($booking->fresh())->render();

        $this->assertStringContainsString('METHOD:CANCEL', $ics);
        $this->assertStringContainsString('STATUS:CANCELLED', $ics);
        $this->assertStringContainsString('SEQUENCE:2', $ics);
        $this->assertStringNotContainsString('BEGIN:VALARM', $ics);
    }

    public function test_ics_escapes_and_folds_long_lines(): void
    {
        $ics = (new IcsCalendar)->addEvent([
            'uid' => 'x@test',
            'start' => now(),
            'end' => now()->addHour(),
            'summary' => 'Visite; T3, lumineux',
            'description' => str_repeat('Très long texte accentué ', 10)."\nLigne 2",
        ])->render();

        $this->assertStringContainsString('SUMMARY:Visite\; T3\, lumineux', $ics);
        $this->assertStringContainsString('\nLigne 2', $ics);

        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'), 'Folding must not split UTF-8 characters.');
        }
    }

    public function test_google_and_outlook_links(): void
    {
        $booking = $this->bookingFor(User::factory()->create());
        $calendar = app(BookingCalendar::class);

        $this->assertStringStartsWith('https://calendar.google.com/calendar/render?action=TEMPLATE', $calendar->googleUrl($booking));
        $this->assertStringStartsWith('https://outlook.live.com/calendar/0/deeplink/compose?', $calendar->outlookUrl($booking));
        $this->assertSame('webcal://localhost/calendar/abc.ics', BookingCalendar::webcal('http://localhost/calendar/abc.ics'));
    }
}
