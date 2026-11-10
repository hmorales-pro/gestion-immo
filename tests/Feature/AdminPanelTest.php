<?php

namespace Tests\Feature;

use App\Enums\BookingMode;
use App\Enums\BookingStatus;
use App\Filament\Pages\CalendarSync;
use App\Filament\Resources\BookingResource;
use App\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\PropertyResource\Pages\CreateProperty;
use App\Filament\Resources\PropertyResource\Pages\EditProperty;
use App\Filament\Resources\PropertyResource\Pages\ListProperties;
use App\Filament\Resources\PropertyResource\RelationManagers\SlotsRelationManager;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertySlot;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRescheduled;
use App\Notifications\InformationRequested;
use App\Notifications\VisitInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->agent = User::factory()->create();
        $this->actingAs($this->agent);
    }

    public function test_pages_render(): void
    {
        $property = Property::factory()->for($this->agent, 'agent')->create();
        $booking = Booking::factory()->create([
            'property_id' => $property->id,
            'property_slot_id' => PropertySlot::factory()->for($property)->create()->id,
        ]);

        $this->get('/admin')->assertOk();
        $this->get(PropertyResource::getUrl('index'))->assertOk();
        $this->get(PropertyResource::getUrl('create'))->assertOk();
        $this->get(PropertyResource::getUrl('edit', ['record' => $property]))->assertOk();
        $this->get(BookingResource::getUrl('index'))->assertOk();
        $this->get(BookingResource::getUrl('view', ['record' => $booking]))->assertOk();
    }

    public function test_agent_only_sees_own_properties(): void
    {
        $mine = Property::factory()->for($this->agent, 'agent')->create();
        $theirs = Property::factory()->create();

        Livewire::test(ListProperties::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);

        $this->get(PropertyResource::getUrl('edit', ['record' => $theirs]))->assertNotFound();
    }

    public function test_agent_only_sees_bookings_of_own_properties(): void
    {
        $mine = Booking::factory()->create([
            'property_slot_id' => $slot = PropertySlot::factory()->for(Property::factory()->for($this->agent, 'agent'))->create(),
            'property_id' => $slot->property_id,
        ]);
        $theirs = Booking::factory()->create();

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);

        $this->get(BookingResource::getUrl('view', ['record' => $theirs]))->assertNotFound();
    }

    public function test_property_is_created_for_current_agent(): void
    {
        Livewire::test(CreateProperty::class)
            ->fillForm([
                'title' => 'T2 Confluence',
                'address' => '1 cours Charlemagne, 69002 Lyon',
                'slot_duration' => 20,
                'booking_mode' => BookingMode::AgentApproval->value,
                'owner_name' => 'Jean Propriétaire',
                'owner_email' => 'jean@example.com',
                'owner_phone' => '06 98 76 54 32',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $property = Property::sole();
        $this->assertSame($this->agent->id, $property->user_id);
        $this->assertSame(BookingMode::AgentApproval, $property->booking_mode);
    }

    public function test_owner_contact_is_validated(): void
    {
        Livewire::test(CreateProperty::class)
            ->fillForm([
                'title' => 'T2',
                'address' => 'Lyon',
                'slot_duration' => 30,
                'booking_mode' => BookingMode::OwnerApproval->value,
                'owner_name' => 'Jean',
                'owner_email' => 'not-an-email',
                'owner_phone' => '12',
            ])
            ->call('create')
            ->assertHasFormErrors(['owner_email', 'owner_phone']);
    }

    public function test_slots_can_be_generated_from_a_range(): void
    {
        $property = Property::factory()->for($this->agent, 'agent')->create(['slot_duration' => 30]);
        $saturday = today()->next('Saturday');

        Livewire::test(SlotsRelationManager::class, ['ownerRecord' => $property, 'pageClass' => EditProperty::class])
            ->callTableAction('generateSlots', data: [
                'date' => $saturday->toDateString(),
                'from' => '10:00',
                'to' => '12:00',
                'weeks' => 2,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(8, $property->slots()->count());
        $this->assertEquals($saturday->copy()->setTime(10, 0), $property->slots()->first()->start_time);
        $this->assertEquals($saturday->copy()->addWeek()->setTime(11, 30), $property->slots()->get()->last()->start_time);
    }

    public function test_copy_public_link_action_targets_public_url(): void
    {
        $property = Property::factory()->for($this->agent, 'agent')->create();

        Livewire::test(ListProperties::class)
            ->assertTableActionExists('copyPublicLink')
            ->assertSee('/v/'.$property->id, escape: false);
    }

    public function test_agent_can_approve_manually(): void
    {
        $slot = PropertySlot::factory()->for(Property::factory()->for($this->agent, 'agent'))->create();
        $booking = Booking::factory()->create(['property_slot_id' => $slot->id, 'property_id' => $slot->property_id]);

        Livewire::test(ListBookings::class)->callTableAction('approve', $booking);

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertTrue($slot->fresh()->is_booked);
        Notification::assertSentOnDemand(BookingConfirmed::class);
    }

    public function test_agent_can_reject_manually(): void
    {
        $slot = PropertySlot::factory()->for(Property::factory()->for($this->agent, 'agent'))->create();
        $booking = Booking::factory()->create(['property_slot_id' => $slot->id, 'property_id' => $slot->property_id]);

        Livewire::test(ListBookings::class)->callTableAction('reject', $booking);

        $this->assertSame(BookingStatus::Rejected, $booking->fresh()->status);
    }

    public function test_status_filter(): void
    {
        $slot = PropertySlot::factory()->for(Property::factory()->for($this->agent, 'agent'))->count(2)->create();
        $pending = Booking::factory()->create(['property_slot_id' => $slot[0]->id, 'property_id' => $slot[0]->property_id]);
        $rejected = Booking::factory()->create(['property_slot_id' => $slot[1]->id, 'property_id' => $slot[1]->property_id, 'status' => BookingStatus::Rejected]);

        Livewire::test(ListBookings::class)
            ->filterTable('status', BookingStatus::Pending->value)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$rejected]);
    }

    private function confirmedBookingForAgent(): Booking
    {
        $slot = PropertySlot::factory()->for(Property::factory()->for($this->agent, 'agent'))
            ->at(now()->addDays(2)->setTime(10, 0))->create(['is_booked' => true]);

        return Booking::factory()->create([
            'property_slot_id' => $slot->id,
            'property_id' => $slot->property_id,
            'status' => BookingStatus::Confirmed,
        ]);
    }

    public function test_agent_can_reschedule_a_confirmed_visit(): void
    {
        $booking = $this->confirmedBookingForAgent();
        $oldSlot = $booking->slot;
        $newSlot = PropertySlot::factory()->for($booking->property)->at(now()->addDays(5)->setTime(14, 0))->create();

        Livewire::test(ListBookings::class)
            ->callTableAction('reschedule', $booking, data: ['slot_id' => $newSlot->id])
            ->assertHasNoTableActionErrors();

        $booking->refresh();
        $this->assertSame($newSlot->id, $booking->property_slot_id);
        $this->assertSame(BookingStatus::Confirmed, $booking->status, 'An agent move keeps the visit confirmed.');
        $this->assertTrue($newSlot->fresh()->is_booked);
        $this->assertFalse($oldSlot->fresh()->is_booked);
        Notification::assertSentTo($booking, BookingRescheduled::class);
        Notification::assertSentOnDemand(BookingRescheduled::class);
    }

    public function test_agent_can_cancel_a_visit(): void
    {
        $booking = $this->confirmedBookingForAgent();

        Livewire::test(ViewBooking::class, ['record' => $booking->getKey()])
            ->callAction('cancel', data: ['reason' => 'Bien loué'])
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame('Bien loué', $booking->cancellation_reason);
        $this->assertFalse($booking->slot->is_booked);
        Notification::assertSentTo($booking, BookingCancelled::class);
    }

    public function test_agent_can_request_information(): void
    {
        $booking = $this->confirmedBookingForAgent();

        Livewire::test(ListBookings::class)
            ->callTableAction('requestInformation', $booking, data: ['question' => 'Date d\'emménagement ?'])
            ->assertHasNoTableActionErrors();

        $this->assertNotNull($booking->fresh()->information_requested_at);
        Notification::assertSentTo($booking, InformationRequested::class,
            fn ($n) => $n->question === 'Date d\'emménagement ?');
    }

    public function test_agent_can_invite_prospects_to_pick_a_slot(): void
    {
        $property = Property::factory()->for($this->agent, 'agent')->create();

        Livewire::test(ListProperties::class)
            ->callTableAction('invite', $property, data: [
                'emails' => ['a@example.com', 'B@example.com', 'a@example.com'],
                'message' => 'Le bien part vite !',
            ])
            ->assertHasNoTableActionErrors();

        Notification::assertSentOnDemandTimes(VisitInvitation::class, 2);
        Notification::assertSentOnDemand(VisitInvitation::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'b@example.com' && $n->personalMessage === 'Le bien part vite !');
    }

    public function test_invite_rejects_invalid_emails(): void
    {
        $property = Property::factory()->for($this->agent, 'agent')->create();

        Livewire::test(ListProperties::class)
            ->callTableAction('invite', $property, data: ['emails' => ['pas-un-email']])
            ->assertHasTableActionErrors();

        Notification::assertNothingSent();
    }

    public function test_calendar_sync_page_shows_feed_link(): void
    {
        $this->get(CalendarSync::getUrl())
            ->assertOk()
            ->assertSee($this->agent->fresh()->calendarFeedUrl(), escape: false)
            ->assertSee('calendar.google.com', escape: false);
    }
}
