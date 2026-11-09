<?php

namespace Tests\Feature;

use App\Enums\BookingMode;
use App\Enums\BookingStatus;
use App\Livewire\PublicBooking;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertySlot;
use App\Notifications\BookingConfirmed;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\OwnerApprovalRequested;
use App\Notifications\VisitRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    private const PROSPECT = [
        'name' => 'Camille Martin',
        'email' => 'Camille.Martin@example.com',
        'phone' => '06 12 34 56 78',
        'employmentStatus' => 'CDI',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function fillForm(Testable $component, array $overrides = []): Testable
    {
        foreach ([...self::PROSPECT, ...$overrides] as $field => $value) {
            $component->set('form.'.$field, $value);
        }

        return $component;
    }

    public function test_page_shows_property_and_only_available_slots(): void
    {
        $property = Property::factory()->create(['title' => 'Loft Canal']);
        $free = PropertySlot::factory()->for($property)->at(now()->addDay()->setTime(10, 0))->create();
        $booked = PropertySlot::factory()->for($property)->at(now()->addDay()->setTime(11, 0))->create(['is_booked' => true]);
        $past = PropertySlot::factory()->for($property)->at(now()->subDay()->setTime(14, 0))->create();
        $held = PropertySlot::factory()->for($property)->at(now()->addDay()->setTime(15, 0))->create();
        Booking::factory()->create(['property_slot_id' => $held->id, 'property_id' => $property->id]);

        $this->get(route('public.booking', $property))
            ->assertOk()
            ->assertSee('Loft Canal')
            ->assertSee($property->address)
            ->assertSeeLivewire(PublicBooking::class);

        $ids = Livewire::test(PublicBooking::class, ['property' => $property])
            ->instance()->slotsByDay->flatten()->pluck('id')->all();

        $this->assertSame([$free->id], $ids);
    }

    public function test_unknown_property_returns_404(): void
    {
        $this->get('/v/01jzzzzzzzzzzzzzzzzzzzzzzz')->assertNotFound();
    }

    public function test_prospect_can_request_a_visit_and_owner_is_notified(): void
    {
        $property = Property::factory()->create(['booking_mode' => BookingMode::OwnerApproval]);
        $slot = PropertySlot::factory()->for($property)->create();

        $component = Livewire::test(PublicBooking::class, ['property' => $property])->call('selectSlot', $slot->id);
        $this->fillForm($component)->call('submit')->assertHasNoErrors()->assertSee('Demande envoyée');

        $booking = Booking::sole();
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame('camille.martin@example.com', $booking->prospect_email);
        $this->assertSame('+33612345678', $booking->prospect_phone);
        $this->assertSame(64, strlen($booking->token));
        $this->assertFalse($slot->fresh()->is_booked);

        Notification::assertSentTo($property->agent, VisitRequested::class);
        Notification::assertSentOnDemand(OwnerApprovalRequested::class, function ($notification, array $channels, AnonymousNotifiable $notifiable) use ($property) {
            return $notifiable->routes['mail'] === $property->owner_email
                && in_array(SmsChannel::class, $channels, true);
        });
    }

    public function test_instant_mode_confirms_immediately(): void
    {
        $property = Property::factory()->mode(BookingMode::Instant)->create();
        $slot = PropertySlot::factory()->for($property)->create();

        $component = Livewire::test(PublicBooking::class, ['property' => $property])->call('selectSlot', $slot->id);
        $this->fillForm($component)->call('submit')->assertHasNoErrors()->assertSee('Visite confirmée');

        $this->assertSame(BookingStatus::Confirmed, Booking::sole()->status);
        $this->assertTrue($slot->fresh()->is_booked);
        Notification::assertSentTo(Booking::sole(), BookingConfirmed::class);
        Notification::assertNotSentTo(new AnonymousNotifiable, OwnerApprovalRequested::class);
    }

    public function test_agent_approval_mode_does_not_contact_owner(): void
    {
        $property = Property::factory()->mode(BookingMode::AgentApproval)->create();
        $slot = PropertySlot::factory()->for($property)->create();

        $component = Livewire::test(PublicBooking::class, ['property' => $property])->call('selectSlot', $slot->id);
        $this->fillForm($component)->call('submit')->assertHasNoErrors();

        Notification::assertSentTo($property->agent, VisitRequested::class);
        Notification::assertSentOnDemandTimes(OwnerApprovalRequested::class, 0);
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function invalidProspects(): array
    {
        return [
            'invalid phone' => [['phone' => '1234'], 'form.phone'],
            'letters in phone' => [['phone' => '06 12 AB 56 78'], 'form.phone'],
            'invalid email' => [['email' => 'camille@'], 'form.email'],
            'missing name' => [['name' => ''], 'form.name'],
            'unknown employment status' => [['employmentStatus' => 'ASTRONAUT'], 'form.employmentStatus'],
            'honeypot filled' => [['website' => 'http://spam.example'], 'form.website'],
        ];
    }

    #[DataProvider('invalidProspects')]
    public function test_prospect_data_is_validated(array $overrides, string $errorKey): void
    {
        $property = Property::factory()->create();
        $slot = PropertySlot::factory()->for($property)->create();

        $component = Livewire::test(PublicBooking::class, ['property' => $property])->call('selectSlot', $slot->id);
        $this->fillForm($component, $overrides)->call('submit')->assertHasErrors([$errorKey]);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_cannot_book_slot_that_is_already_held(): void
    {
        $property = Property::factory()->create();
        $slot = PropertySlot::factory()->for($property)->create();
        Booking::factory()->create(['property_slot_id' => $slot->id, 'property_id' => $property->id]);

        $component = Livewire::test(PublicBooking::class, ['property' => $property])->set('form.slotId', $slot->id);
        $this->fillForm($component)->call('submit')->assertHasErrors(['form.slotId']);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_cannot_book_a_slot_of_another_property(): void
    {
        $property = Property::factory()->create();
        $foreignSlot = PropertySlot::factory()->create();

        $component = Livewire::test(PublicBooking::class, ['property' => $property])->set('form.slotId', $foreignSlot->id);
        $this->fillForm($component)->call('submit')->assertHasErrors(['form.slotId']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_same_prospect_cannot_hold_overlapping_visits(): void
    {
        $start = now()->addDays(2)->setTime(10, 0);
        $first = PropertySlot::factory()->at($start)->create();
        $overlapping = PropertySlot::factory()->at($start->copy()->addMinutes(15))->create();
        Booking::factory()->create([
            'property_slot_id' => $first->id,
            'property_id' => $first->property_id,
            'prospect_email' => 'camille.martin@example.com',
        ]);

        $component = Livewire::test(PublicBooking::class, ['property' => $overlapping->property])->call('selectSlot', $overlapping->id);
        $this->fillForm($component)->call('submit')->assertHasErrors(['form.slotId']);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_public_requests_are_rate_limited(): void
    {
        $property = Property::factory()->create();
        $slots = PropertySlot::factory()->for($property)->count(6)
            ->sequence(fn ($s) => ['start_time' => now()->addDays($s->index + 1), 'end_time' => now()->addDays($s->index + 1)->addMinutes(30)])
            ->create();

        foreach ($slots as $i => $slot) {
            $component = Livewire::test(PublicBooking::class, ['property' => $property])->call('selectSlot', $slot->id);
            $this->fillForm($component, ['email' => "p{$i}@example.com"])->call('submit');
        }

        $this->assertDatabaseCount('bookings', 5);
    }
}
