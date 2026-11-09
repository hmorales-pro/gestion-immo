<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Booking $booking)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($this->wantsSms() && filled($notifiable->routeNotificationFor('sms', $this))) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    protected function wantsSms(): bool
    {
        return false;
    }

    protected function slotLabel(): string
    {
        return $this->booking->slot->label();
    }
}
