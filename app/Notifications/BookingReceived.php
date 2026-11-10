<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** Sent to the prospect when the request awaits a decision. */
class BookingReceived extends BookingNotification
{
    protected const TAG = 'prospect-request-received';

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;

        return $this->mail()
            ->subject("Demande de visite reçue : {$property->title}")
            ->greeting("Bonjour {$this->booking->prospect_name},")
            ->line("Nous avons bien reçu votre demande de visite pour « {$property->title} ».")
            ->line('Créneau demandé : '.$this->slotLabel())
            ->line('Elle doit encore être validée : vous recevrez un e-mail de confirmation.')
            ->action('Modifier ou annuler ma demande', $this->booking->manageUrl());
    }
}
