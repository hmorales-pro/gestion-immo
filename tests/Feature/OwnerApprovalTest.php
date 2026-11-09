<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\PropertySlot;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OwnerApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_owner_sees_request_summary(): void
    {
        $booking = Booking::factory()->create(['prospect_name' => 'Léa Dupont']);

        $this->get(route('owner.approve', $booking->token))
            ->assertOk()
            ->assertSee($booking->property->title)
            ->assertSee('Léa Dupont')
            ->assertSee($booking->slot->start_time->format('H:i'))
            ->assertSee('Accepter la visite');
    }

    public function test_unknown_token_returns_404(): void
    {
        $this->get(route('owner.approve', str_repeat('a', 64)))->assertNotFound();
        $this->get(route('owner.approve', 'short'))->assertNotFound();
    }

    public function test_owner_can_approve(): void
    {
        $booking = Booking::factory()->create();

        $this->post(route('owner.approve.decide', $booking->token), ['decision' => 'approve'])
            ->assertRedirect(route('owner.approve', $booking->token))
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertNotNull($booking->decided_at);
        $this->assertTrue($booking->slot->is_booked);

        Notification::assertSentTo($booking->property->agent, BookingConfirmed::class);
        Notification::assertSentTo($booking, BookingConfirmed::class);
        // The owner made the decision: no confirmation sent back to them.
        Notification::assertSentOnDemandTimes(BookingConfirmed::class, 0);
    }

    public function test_owner_can_reject(): void
    {
        $booking = Booking::factory()->create();

        $this->post(route('owner.approve.decide', $booking->token), ['decision' => 'reject'])
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(BookingStatus::Rejected, $booking->status);
        $this->assertFalse($booking->slot->is_booked);
        $this->assertTrue($booking->slot->fresh()->isAvailable(), 'Rejected request releases the slot.');

        Notification::assertSentTo($booking->property->agent, BookingRejected::class);
    }

    public function test_decision_cannot_be_changed_afterwards(): void
    {
        $booking = Booking::factory()->create();

        $this->post(route('owner.approve.decide', $booking->token), ['decision' => 'reject']);
        $this->post(route('owner.approve.decide', $booking->token), ['decision' => 'approve'])
            ->assertSessionHas('error');

        $this->assertSame(BookingStatus::Rejected, $booking->fresh()->status);
        $this->get(route('owner.approve', $booking->token))->assertDontSee('Accepter la visite');
    }

    public function test_past_slot_cannot_be_approved(): void
    {
        $slot = PropertySlot::factory()->at(now()->subHour())->create();
        $booking = Booking::factory()->create(['property_slot_id' => $slot->id, 'property_id' => $slot->property_id]);

        $this->post(route('owner.approve.decide', $booking->token), ['decision' => 'approve'])
            ->assertSessionHas('error');

        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_decision_is_validated(): void
    {
        $booking = Booking::factory()->create();

        $this->post(route('owner.approve.decide', $booking->token), ['decision' => 'maybe'])
            ->assertSessionHasErrors('decision');

        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }
}
