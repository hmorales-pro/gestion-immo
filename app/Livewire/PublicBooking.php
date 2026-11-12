<?php

namespace App\Livewire;

use App\Actions\Bookings\CreateBooking;
use App\Enums\BookingStatus;
use App\Enums\EmploymentStatus;
use App\Exceptions\BookingException;
use App\Livewire\Forms\ProspectForm;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertySlot;
use App\Support\Demo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.public')]
class PublicBooking extends Component
{
    #[Locked]
    public Property $property;

    public ProspectForm $form;

    #[Locked]
    public ?int $bookingId = null;

    public function mount(Property $property): void
    {
        $this->property = $property;
    }

    /**
     * Available slots grouped by day (Y-m-d => slots).
     *
     * @return Collection<string, Collection<int, PropertySlot>>
     */
    #[Computed]
    public function slotsByDay(): Collection
    {
        return $this->property->slots()
            ->getQuery()
            ->available()
            ->limit(200)
            ->get()
            ->groupBy(fn (PropertySlot $slot) => $slot->start_time->toDateString());
    }

    #[Computed]
    public function selectedSlot(): ?PropertySlot
    {
        return $this->form->slotId
            ? $this->property->slots()->getQuery()->whereKey($this->form->slotId)->first()
            : null;
    }

    #[Computed]
    public function booking(): ?Booking
    {
        return $this->bookingId ? Booking::with('slot')->find($this->bookingId) : null;
    }

    public function selectSlot(int $slotId): void
    {
        $this->form->slotId = $slotId;
        $this->resetErrorBag('form.slotId');
    }

    public function submit(CreateBooking $createBooking): void
    {
        $key = 'public-booking:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'form.slotId' => 'Trop de demandes. Réessayez dans '.RateLimiter::availableIn($key).' secondes.',
            ]);
        }

        $this->form->validate();
        RateLimiter::hit($key, 600);

        try {
            $booking = $createBooking->execute($this->property, (int) $this->form->slotId, $this->form->toBookingData());
        } catch (BookingException $e) {
            unset($this->slotsByDay);
            $this->form->slotId = null;

            throw ValidationException::withMessages(['form.slotId' => $e->getMessage()]);
        }

        $this->bookingId = $booking->id;
        $this->form->reset();
    }

    public function render()
    {
        return view('livewire.public-booking', [
            'employmentStatuses' => EmploymentStatus::cases(),
            'confirmed' => $this->booking?->status === BookingStatus::Confirmed,
            'isDemo' => Demo::ownsProperty($this->property),
        ])->title($this->property->title.' — Réserver une visite');
    }
}
