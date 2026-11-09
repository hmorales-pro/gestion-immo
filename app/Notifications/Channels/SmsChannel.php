<?php

namespace App\Notifications\Channels;

use App\Services\Sms\SmsSender;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(private readonly SmsSender $sender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationFor('sms', $notification);

        if (blank($to) || ! method_exists($notification, 'toSms')) {
            return;
        }

        $this->sender->send($to, $notification->toSms($notifiable));
    }
}
