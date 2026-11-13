<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaitlistSlotAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Property $property, public string $recipientName)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $agent = $this->property->agent;

        return (new MailMessage)
            ->tag('waitlist-slot-available')
            ->replyTo($agent->email, $agent->name)
            ->subject("Un créneau de visite s'est libéré : {$this->property->title}")
            ->greeting("Bonjour {$this->recipientName},")
            ->line("Vous attendiez un créneau pour visiter « {$this->property->title} » ({$this->property->address}). Des créneaux sont de nouveau disponibles.")
            ->action('Réserver ma visite', $this->property->publicUrl())
            ->line('Premier arrivé, premier servi : les autres personnes en attente ont reçu le même message.');
    }
}
