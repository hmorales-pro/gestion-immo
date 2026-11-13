<?php

namespace Tests\Feature;

use App\Actions\Bookings\ApproveBooking;
use App\Actions\Bookings\CancelBooking;
use App\Actions\Slots\GenerateSlots;
use App\Enums\BookingStatus;
use App\Livewire\ManageBooking;
use App\Livewire\PublicBooking;
use App\Livewire\SignVisitVoucher;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertySlot;
use App\Models\User;
use App\Notifications\BookingConfirmed;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\FeedbackReceived;
use App\Notifications\WaitlistSlotAvailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class VisitServicesTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHgAAAAoCAIAAAC6iKlyAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABO0lEQVRoge2ZTQ6DIBSEsekBXHoH73+U3qHL3oAuTExjUXgwb9B2vqURGL4gfw4xxiD8ufUO8C9INAmJJiHRJCSahESTkGgSEk1CoklINAmJJiHRJO6cZsZpTj5/PR+cAEn2UgWHYIP37d1BZ1a66M4Gw6byFb12Zi909gUPShqFB3MUvWQtCcrUXd4WNpWX6HLLn++bilRgTVVXJImL6OpwqF5hK4ekwm/vWmItpUrWTyvdUyFHNOrzx04jJ0kFG9FAO2sN7UP7PKkwI9ppbm2s9lSpWkV77xZ+Zl3dTh2m74KwJ6tYiMZpdrVcl2o7oq2nDM6JrtcpA9hWYuq46Lm5b6psu+k5WjdBJpKpNkmOFsNr3W32TRW+ghlECyD6w0JCoklINAmJJiHRJCSahESTkGgSEk1Cokm8Af+W3sEMRqX5AAAAAElFTkSuQmCC';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function booking(array $slotState = [], array $bookingState = [], array $propertyState = []): Booking
    {
        $slot = PropertySlot::factory()
            ->for(Property::factory()->state($propertyState))
            ->at(now()->addDays(2)->setTime(10, 0))
            ->create($slotState);

        return Booking::factory()->create([
            'property_id' => $slot->property_id,
            'property_slot_id' => $slot->id,
            'status' => BookingStatus::Confirmed,
            'prospect_name' => 'Camille Martin',
            ...$bookingState,
        ]);
    }

    private function pastVisit(): Booking
    {
        return $this->booking(['start_time' => now()->subHours(3), 'end_time' => now()->subHours(2)]);
    }

    // --- Poster, QR code ---------------------------------------------------------

    public function test_agent_gets_a_printable_poster_and_qr_code(): void
    {
        $property = Property::factory()->create(['title' => 'Loft Canal']);
        $this->actingAs($property->agent);

        $this->get(route('property.poster', $property))
            ->assertOk()
            ->assertSee('Loft Canal')
            ->assertSee('Scannez pour réserver votre visite')
            ->assertSee('<svg', false);

        $this->get(route('property.qr', $property))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_poster_is_private(): void
    {
        $property = Property::factory()->create();

        $this->get(route('property.poster', $property))->assertRedirect(route('filament.admin.auth.login'));

        $this->actingAs(User::factory()->create());
        $this->get(route('property.poster', $property))->assertNotFound();
        $this->get(route('property.qr', $property))->assertNotFound();
    }

    // --- Owner follow-up page ---------------------------------------------------

    public function test_owner_follow_up_page_shows_visits_and_feedback_without_contact_details(): void
    {
        $booking = $this->pastVisit();
        $booking->update(['feedback_interest' => 'yes', 'feedback_price' => 'high', 'feedback_comment' => 'Très lumineux', 'feedback_at' => now()]);
        $upcoming = Booking::factory()->create([
            'property_id' => $booking->property_id,
            'property_slot_id' => PropertySlot::factory()->for($booking->property)->create()->id,
            'prospect_name' => 'Léo Bernard',
        ]);

        $this->get($booking->property->ownerReportUrl())
            ->assertOk()
            ->assertSee('Camille M.')
            ->assertSee('Léo B.')
            ->assertSee('Très lumineux')
            ->assertSee('Trop cher')
            ->assertDontSee($booking->prospect_email)
            ->assertDontSee($booking->prospect_phone)
            ->assertDontSee('Martin');

        $this->get(route('owner.report', str_repeat('x', 64)))->assertNotFound();
    }

    // --- Feedback ---------------------------------------------------------------

    public function test_feedback_is_only_asked_after_the_visit(): void
    {
        $booking = $this->booking();

        Livewire::test(ManageBooking::class, ['token' => $booking->manage_token])
            ->assertDontSee("Comment s'est passée la visite", false);
    }

    public function test_visitor_gives_feedback_after_the_visit(): void
    {
        $booking = $this->pastVisit();

        Livewire::test(ManageBooking::class, ['token' => $booking->manage_token])
            ->assertSee("Comment s'est passée la visite", false)
            ->set('feedbackInterest', 'maybe')
            ->set('feedbackPrice', 'fair')
            ->set('feedbackComment', 'Un peu sombre')
            ->call('saveFeedback')
            ->assertHasNoErrors()
            ->assertSee('Merci pour votre avis')
            ->assertDontSee("Comment s'est passée la visite", false);

        $booking->refresh();
        $this->assertSame('maybe', $booking->feedback_interest->value);
        Notification::assertSentTo($booking->property->agent, FeedbackReceived::class);
        Notification::assertSentOnDemand(FeedbackReceived::class, fn ($n, $c, AnonymousNotifiable $to) => $to->routes['mail'] === $booking->property->owner_email);
    }

    public function test_feedback_requires_an_interest(): void
    {
        $booking = $this->pastVisit();

        Livewire::test(ManageBooking::class, ['token' => $booking->manage_token])
            ->call('saveFeedback')
            ->assertHasErrors('feedbackInterest');
    }

    // --- Waiting list -----------------------------------------------------------

    public function test_visitor_can_join_the_waiting_list_when_everything_is_booked(): void
    {
        $property = Property::factory()->create();
        PropertySlot::factory()->for($property)->create(['is_booked' => true]);

        Livewire::test(PublicBooking::class, ['property' => $property])
            ->assertSee('Être prévenu')
            ->set('waitName', 'Inès Moreau')
            ->set('waitEmail', 'Ines@Example.com')
            ->call('joinWaitlist')
            ->assertHasNoErrors()
            ->assertSee("C'est noté", false);

        $this->assertDatabaseHas('waitlist_entries', ['property_id' => $property->id, 'email' => 'ines@example.com']);
    }

    public function test_waiting_list_is_notified_once_when_a_slot_is_freed(): void
    {
        $booking = $this->booking(['is_booked' => true]);
        $booking->property->waitlist()->create(['name' => 'Inès', 'email' => 'ines@example.com']);

        app(CancelBooking::class)->execute($booking);

        Notification::assertSentTimes(WaitlistSlotAvailable::class, 1);
        $this->assertDatabaseCount('waitlist_entries', 0);
    }

    public function test_new_slots_notify_the_waiting_list(): void
    {
        $property = Property::factory()->create();
        $property->waitlist()->create(['name' => 'Inès', 'email' => 'ines@example.com']);

        app(GenerateSlots::class)->execute($property, today()->addDay(), '10:00', '11:00');

        Notification::assertCount(1);
        $this->assertDatabaseCount('waitlist_entries', 0);
    }

    // --- Visit voucher ----------------------------------------------------------

    public function test_visitor_signs_the_voucher_on_the_agent_phone(): void
    {
        $booking = $this->booking();
        $this->actingAs($booking->property->agent);

        Livewire::test(SignVisitVoucher::class, ['booking' => $booking])
            ->assertSee('Bon de visite')
            ->assertSee($booking->property->address)
            ->set('signature', self::SIGNATURE)
            ->call('sign')
            ->assertHasNoErrors()
            ->assertSee('Signé le');

        $booking->refresh();
        $this->assertTrue($booking->hasSignedVoucher());
        $this->assertSame('Camille Martin', $booking->voucher_signer_name);

        $this->get(route('voucher.print', $booking))->assertOk()->assertSee('Signature du visiteur')->assertSee(self::SIGNATURE, false);
    }

    public function test_voucher_rejects_empty_or_forged_signatures(): void
    {
        $booking = $this->booking();
        $this->actingAs($booking->property->agent);

        foreach (['', 'data:image/png;base64,AAAA', 'data:image/svg+xml;base64,PHN2Zz4=', 'javascript:alert(1)'] as $signature) {
            Livewire::test(SignVisitVoucher::class, ['booking' => $booking])
                ->set('signature', $signature)
                ->call('sign')
                ->assertHasErrors('signature');
        }

        $this->assertFalse($booking->fresh()->hasSignedVoucher());
    }

    public function test_voucher_is_private_to_the_agent(): void
    {
        $booking = $this->booking();

        $this->get(route('voucher.sign', $booking))->assertRedirect(route('filament.admin.auth.login'));

        $this->actingAs(User::factory()->create());
        $this->get(route('voucher.sign', $booking))->assertNotFound();
        $this->get(route('voucher.print', $booking))->assertNotFound();
    }

    // --- Occupant and privacy ---------------------------------------------------

    public function test_occupant_is_told_about_confirmed_visits_without_visitor_details(): void
    {
        $booking = $this->booking(bookingState: ['status' => BookingStatus::Pending], propertyState: [
            'occupant_name' => 'M. Durand',
            'occupant_email' => 'durand@example.com',
            'occupant_phone' => '+33611111111',
        ]);

        app(ApproveBooking::class)->execute($booking);

        Notification::assertSentOnDemand(BookingConfirmed::class, function ($notification, $channels, AnonymousNotifiable $to) use ($booking) {
            if (! isset($to->routes['occupant'])) {
                return false;
            }
            $mail = $notification->toMail($to);
            $text = implode(' ', $mail->introLines);
            $ics = str_replace("\r\n ", '', $mail->rawAttachments[0]['data']); // unfold lines

            return $to->routes['mail'] === 'durand@example.com'
                && in_array(SmsChannel::class, $channels, true)
                && ! str_contains($text, $booking->prospect_name)
                && ! str_contains($ics, $booking->prospect_email)
                && str_contains($ics, 'durand@example.com');
        });
    }

    public function test_owner_never_receives_the_visitor_phone_or_private_links(): void
    {
        $booking = $this->booking();
        $owner = $booking->property->ownerRoute();

        $mail = (new BookingConfirmed($booking))->toMail($owner);
        $content = implode(' ', $mail->introLines).implode(' ', $mail->outroLines);
        $ics = str_replace("\r\n ", '', $mail->rawAttachments[0]['data']); // unfold lines

        $this->assertStringNotContainsString($booking->prospect_phone, $content);
        $this->assertStringNotContainsString($booking->manage_token, $content);
        $this->assertStringNotContainsString($booking->prospect_email, $ics);
        $this->assertStringContainsString($booking->property->owner_email, $ics);
        $this->assertStringContainsString($booking->property->ownerReportUrl(), $content);
    }
}
