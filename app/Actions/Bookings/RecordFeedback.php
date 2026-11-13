<?php

namespace App\Actions\Bookings;

use App\Enums\FeedbackInterest;
use App\Enums\FeedbackPrice;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Notifications\FeedbackReceived;
use Illuminate\Support\Facades\Notification;

/** Stores the visitor's opinion after the visit and tells the agent and the owner. */
class RecordFeedback
{
    /**
     * @throws BookingException
     */
    public function execute(Booking $booking, FeedbackInterest $interest, ?FeedbackPrice $price, ?string $comment): Booking
    {
        if (! $booking->hasHappened()) {
            throw new BookingException('Vous pourrez donner votre avis après la visite.');
        }

        if ($booking->hasFeedback()) {
            throw new BookingException('Merci, votre avis a déjà été enregistré.');
        }

        $booking->update([
            'feedback_interest' => $interest,
            'feedback_price' => $price,
            'feedback_comment' => filled($comment) ? trim($comment) : null,
            'feedback_at' => now(),
        ]);

        $booking->loadMissing(['property.agent', 'slot']);
        $notification = new FeedbackReceived($booking);

        $booking->property->agent->notify($notification);
        Notification::route('mail', $booking->property->owner_email)->notify($notification);

        return $booking;
    }
}
