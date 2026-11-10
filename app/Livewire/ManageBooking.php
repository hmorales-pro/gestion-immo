<?php

namespace App\Livewire;

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\RescheduleBooking;
use App\Actions\Bookings\UpdateProspectInformation;
use App\Enums\EmploymentStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\PropertySlot;
use App\Services\Calendar\BookingCalendar;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Prospect's private page (/rdv/{token}): add to calendar, reschedule, cancel, complete details.
 */
#[Layout('components.layouts.public')]
class ManageBooking extends Component
{
    #[Locked]
    public string $token;

    /** '' | 'reschedule' | 'cancel' | 'info' */
    public string $panel = '';

    public ?int $newSlotId = null;

    public string $cancelReason = '';

    public string $name = '';

    public string $phone = '';

    public string $employmentStatus = '';

    public string $message = '';

    public ?string $flash = null;

    public function mount(string $token): void
    {
        abort_unless(strlen($token) === 64, 404);
        $this->token = $token;

        $booking = $this->booking;
        $this->name = $booking->prospect_name;
        $this->phone = $booking->prospect_phone;
        $this->employmentStatus = $booking->employment_status->value;
        $this->message = (string) $booking->message;

        if ($booking->information_requested_at && $booking->isActive()) {
            $this->panel = 'info';
        }
    }

    #[Computed]
    public function booking(): Booking
    {
        return Booking::query()
            ->with(['property.agent', 'slot'])
            ->where('manage_token', $this->token)
            ->firstOrFail();
    }

    /**
     * @return Collection<string, Collection<int, PropertySlot>>
     */
    #[Computed]
    public function slotsByDay(): Collection
    {
        return $this->booking->property->slots()->getQuery()
            ->available()
            ->limit(200)
            ->get()
            ->groupBy(fn (PropertySlot $slot) => $slot->start_time->toDateString());
    }

    public function open(string $panel): void
    {
        $this->panel = in_array($panel, ['reschedule', 'cancel', 'info'], true) ? $panel : '';
        $this->flash = null;
        $this->resetErrorBag();
    }

    public function reschedule(RescheduleBooking $reschedule): void
    {
        $this->validate(['newSlotId' => ['required', 'integer']], ['newSlotId.required' => 'Choisissez un nouveau créneau.']);

        try {
            $reschedule->execute($this->booking, (int) $this->newSlotId, byProspect: true);
        } catch (BookingException $e) {
            unset($this->slotsByDay);
            throw ValidationException::withMessages(['newSlotId' => $e->getMessage()]);
        }

        $this->done($this->booking->fresh()->isPending()
            ? 'Votre demande de changement est envoyée. Vous recevrez une confirmation.'
            : 'Votre rendez-vous a été déplacé. Votre agenda sera mis à jour.');
    }

    public function cancel(CancelBooking $cancel): void
    {
        $this->validate(['cancelReason' => ['nullable', 'string', 'max:500']]);

        try {
            $cancel->execute($this->booking, $this->cancelReason ?: 'Annulé par le visiteur.');
        } catch (BookingException $e) {
            throw ValidationException::withMessages(['cancelReason' => $e->getMessage()]);
        }

        $this->done('Votre rendez-vous est annulé.');
    }

    public function saveInformation(UpdateProspectInformation $update): void
    {
        abort_unless($this->booking->isActive(), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'max:32', 'phone:FR,INTERNATIONAL,mobile,fixed_line,fixed_line_or_mobile'],
            'employmentStatus' => ['required', Rule::enum(EmploymentStatus::class)],
            'message' => ['nullable', 'string', 'max:1000'],
        ], ['phone.phone' => 'Ce numéro de téléphone ne semble pas valide.'], [
            'name' => 'nom', 'phone' => 'téléphone', 'employmentStatus' => 'situation professionnelle',
        ]);

        $update->execute($this->booking, [
            'prospect_name' => trim($data['name']),
            'prospect_phone' => $data['phone'],
            'employment_status' => $data['employmentStatus'],
            'message' => filled($data['message']) ? trim($data['message']) : null,
        ]);

        $this->done("Merci, vos informations ont été transmises à l'agence.");
    }

    private function done(string $message): void
    {
        unset($this->booking, $this->slotsByDay);
        $this->reset('panel', 'newSlotId', 'cancelReason');
        $this->flash = $message;
    }

    public function render()
    {
        $booking = $this->booking;
        $calendar = app(BookingCalendar::class);

        return view('livewire.manage-booking', [
            'employmentStatuses' => EmploymentStatus::cases(),
            'canChange' => $booking->isActive() && $booking->slot->start_time->isFuture(),
            'googleUrl' => $booking->isConfirmed() ? $calendar->googleUrl($booking) : null,
            'outlookUrl' => $booking->isConfirmed() ? $calendar->outlookUrl($booking) : null,
        ])->title('Mon rendez-vous — '.$booking->property->title);
    }
}
