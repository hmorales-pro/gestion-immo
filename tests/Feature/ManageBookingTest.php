<?php

namespace Tests\Feature;

use App\Enums\BookingMode;
use App\Enums\BookingStatus;
use App\Livewire\ManageBooking;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertySlot;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingRescheduled;
use App\Notifications\InformationProvided;
use App\Notifications\OwnerApprovalRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ManageBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function confirmedBooking(BookingMode $mode = BookingMode::OwnerApproval): Booking
    {
        $property = Property::factory()->mode($mode)->create();
        $slot = PropertySlot::factory()->for($property)->at(now()->addDays(2)->setTime(10, 0))->create(['is_booked' => true]);

        return Booking::factory()->create([
            'property_id' => $property->id,
            'property_slot_id' => $slot->id,
            'status' => BookingStatus::Confirmed,
        ]);
    }

    public function test_page_shows_booking_and_calendar_links(): void
    {
        $booking = $this->confirmedBooking();

        $this->get($booking->manageUrl())
            ->assertOk()
            ->assertSee($booking->property->title)
            ->assertSee('Ajouter à mon agenda')
            ->assertSee('calendar.google.com', escape: false)
            ->assertSee($booking->icsUrl(), escape: false);
    }

    public function test_owner_token_does_not_open_prospect_page(): void
    {
        $booking = $this->confirmedBooking();

        $this->get(route('booking.manage', $booking->token))->assertNotFound();
        $this->get(route('booking.manage', 'short'))->assertNotFound();
    }

    public function test_prospect_reschedule_goes_back_to_owner_approval(): void
    {
        $booking = $this->confirmedBooking(BookingMode::OwnerApproval);
        $oldSlot = $booking->slot;
        $newSlot = PropertySlot::factory()->for($booking->property)->at(now()->addDays(3)->setTime(15, 0))->create();

        Livewire::test(ManageBooking::class, ['token' => $booking->manage_token])
            ->call('open', 'reschedule')
            ->set('newSlotId', $newSlot->id)
            ->call('reschedule')
            ->assertHasNoErrors()
            ->assertSee('demande de changement');

        $booking->refresh();
        $this->assertSame($newSlot->id, $booking->property_slot_id);
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(1, $booking->sequence);
        $this->assertFalse($oldSlot->fresh()->is_booked);
        $this->assertFalse($newSlot->fresh()->is_booked);

        Notification::assertSentTo($booking, BookingRescheduled::class);
        Notification::assertSentOnDemand(OwnerApprovalRequested::class, fn ($n) => $n->rescheduled);
    }

    public function test_prospect_reschedule_is_immediate_in_instant_mode(): void
    {
        $booking = $this->confirmedBooking(BookingMode::Instant);
        $newSlot = PropertySlot::factory()->for($booking->property)->at(now()->addDays(4)->setTime(9, 0))->create();

        Livewire::test(ManageBooking::class, ['token' => $booking->manage_token])
            ->set('newSlotId', $newSlot->id)
            ->call('reschedule')
            ->assertHasNoErrors();

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertTrue($newSlot->fresh()->is_booked);
        Notification::assertSentOnDemand(BookingRescheduled::class);
    }

    public function test_cannot_reschedule_to_a_slot_of_another_property_or_a_taken_slot(): void
    {
        $booking = $this->confirmedBooking();
        $foreign = PropertySlot::factory()->create();
        $taken = PropertySlot::factory()->for($booking->property)->create(['is_booked' => true]);

        foreach ([$foreign, $taken] as $slot) {
            Livewire::test(ManageBooking::class, ['token' => $booking->manage_token])
                ->set('newSlotId', $slot->id)
                ->call('reschedule')
                ->assertHasErrors('newSlotId');
        }

        $this->assertSame(0, $booking->fresh()->sequence);
    }

    public function test_prospect_can_cancel(): void
    {
        $booking = $this->confirmedBooking();

        Livewire::test(ManageBooking::class, ['token' => $booking->manage_token])
            ->call('open', 'cancel')
            ->set('cancelReason', 'Bien trouvé ailleurs')
            ->call('cancel')
            ->assertHasNoErrors()
            ->assertSee('Choisir un nouveau créneau');

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame('Bien trouvé ailleurs', $booking->cancellation_reason);
        $this->assertFalse($booking->slot->is_booked);
        Notification::assertSentTo($booking->property->agent, BookingCancelled::class);
        Notification::assertSentOnDemand(BookingCancelled::class);
    }

    public function test_prospect_can_complete_information(): void
    {
        $booking = $this->confirmedBooking();
        $booking->update(['information_requested_at' => now()]);

        Livewire::test(ManageBooking::class, ['token' => $booking->manage_token])
            ->assertSet('panel', 'info')
            ->set('phone', '07 81 22 33 44')
            ->set('employmentStatus', 'FREELANCE')
            ->set('message', 'Revenus : 3 200 € nets / mois')
            ->call('saveInformation')
            ->assertHasNoErrors();

        $booking->refresh();
        $this->assertSame('+33781223344', $booking->prospect_phone);
        $this->assertNull($booking->information_requested_at);
        Notification::assertSentTo($booking->property->agent, InformationProvided::class);
    }

    public function test_information_form_shows_errors_with_red_border(): void
    {
        $booking = $this->confirmedBooking();

        Livewire::test(ManageBooking::class, ['token' => $booking->manage_token])
            ->call('open', 'info')
            ->set('phone', '12')
            ->call('saveInformation')
            ->assertHasErrors('phone')
            ->assertSeeHtml('border-red-500');
    }

    public function test_cancelled_booking_cannot_be_changed(): void
    {
        $booking = $this->confirmedBooking();
        $booking->update(['status' => BookingStatus::Cancelled]);

        Livewire::test(ManageBooking::class, ['token' => $booking->manage_token])
            ->assertDontSee('Changer de créneau')
            ->call('saveInformation')
            ->assertForbidden();
    }
}
