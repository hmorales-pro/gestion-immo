<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\FeedbackInterest;
use App\Models\Booking;
use App\Models\Property;
use Illuminate\View\View;

/**
 * Owner's private follow-up page (/suivi/{token}): visits of their property and what
 * visitors thought, always up to date. Nothing to schedule, nothing to send.
 */
class OwnerReportController extends Controller
{
    public function __invoke(string $token): View
    {
        abort_unless(strlen($token) === 64, 404);

        $property = Property::query()->with('agent')->where('owner_token', $token)->firstOrFail();

        $bookings = $property->bookings()
            ->with('slot')
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->get()
            ->sortByDesc(fn (Booking $b) => $b->slot->start_time);

        $past = $bookings->filter(fn (Booking $b) => $b->hasHappened());
        $feedback = $past->filter(fn (Booking $b) => $b->hasFeedback());

        return view('owner.report', [
            'property' => $property,
            'upcoming' => $bookings->reject(fn (Booking $b) => $b->slot->start_time->isPast())->sortBy(fn (Booking $b) => $b->slot->start_time),
            'past' => $past,
            'feedback' => $feedback,
            'stats' => [
                'visits' => $past->count(),
                'upcoming' => $bookings->filter(fn (Booking $b) => $b->slot->start_time->isFuture())->count(),
                'interested' => $feedback->where('feedback_interest', FeedbackInterest::Yes)->count(),
                'feedback' => $feedback->count(),
            ],
        ]);
    }
}
