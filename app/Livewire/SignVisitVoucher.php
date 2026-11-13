<?php

namespace App\Livewire;

use App\Actions\Bookings\SignVoucher;
use App\Exceptions\BookingException;
use App\Models\Booking;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Visit voucher signed by the visitor on the agent's phone, at the end of the visit.
 * Agent-only page (/bon-de-visite/{booking}).
 */
#[Layout('components.layouts.public')]
class SignVisitVoucher extends Component
{
    #[Locked]
    public Booking $booking;

    public string $signerName = '';

    public string $signature = '';

    public function mount(Booking $booking): void
    {
        abort_unless(auth()->user()?->can('update', $booking), 404);

        $this->booking = $booking->load(['property.agent', 'slot']);
        $this->signerName = $booking->voucher_signer_name ?? $booking->prospect_name;
    }

    public function sign(SignVoucher $sign): void
    {
        abort_unless(auth()->user()?->can('update', $this->booking), 403);

        $this->validate([
            'signerName' => ['required', 'string', 'min:2', 'max:120'],
            'signature' => ['required', 'string'],
        ], ['signature.required' => 'Faites signer le visiteur dans le cadre.'], ['signerName' => 'nom']);

        try {
            $sign->execute($this->booking, $this->signerName, $this->signature);
        } catch (BookingException $e) {
            throw ValidationException::withMessages(['signature' => $e->getMessage()]);
        }

        $this->booking->refresh();
    }

    public function render()
    {
        return view('livewire.sign-visit-voucher')->title('Bon de visite — '.$this->booking->property->title);
    }
}
