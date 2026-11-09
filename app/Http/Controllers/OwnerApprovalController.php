<?php

namespace App\Http\Controllers;

use App\Actions\Bookings\ApproveBooking;
use App\Actions\Bookings\RejectBooking;
use App\Exceptions\BookingException;
use App\Http\Requests\OwnerDecisionRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Stateless page where the property owner approves or rejects a visit request.
 * Access is granted by the 64-character random token sent to the owner.
 */
class OwnerApprovalController extends Controller
{
    public function __invoke(string $token): View
    {
        $booking = $this->findBooking($token);

        return view('approval.show', ['booking' => $booking]);
    }

    public function decide(
        OwnerDecisionRequest $request,
        string $token,
        ApproveBooking $approve,
        RejectBooking $reject,
    ): RedirectResponse {
        $booking = $this->findBooking($token);

        try {
            $request->approves()
                ? $approve->execute($booking, notifyOwner: false)
                : $reject->execute($booking);
        } catch (BookingException $e) {
            return redirect()->route('owner.approve', $token)->with('error', $e->getMessage());
        }

        return redirect()->route('owner.approve', $token)
            ->with('success', $request->approves() ? 'La visite est confirmée. Merci !' : 'La demande a été refusée.');
    }

    private function findBooking(string $token): Booking
    {
        abort_unless(strlen($token) === 64, 404);

        return Booking::query()
            ->with(['property', 'slot'])
            ->where('token', $token)
            ->firstOrFail();
    }
}
