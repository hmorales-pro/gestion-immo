<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** Sent to the agent and the prospect when a request is turned down. */
class BookingRejected extends BookingNotification
{
    protected const TAG = 'booking-rejected';

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;

        if ($this->recipient($notifiable) === 'prospect') {
            return $this->mail()
                ->subject("Votre demande de visite : {$property->title}")
                ->greeting("Bonjour {$this->booking->prospect_name},")
                ->line("Le créneau du {$this->slotLabel()} n'a malheureusement pas pu être retenu.")
                ->action('Choisir un autre créneau', $property->publicUrl());
        }

        return $this->mail()
            ->subject("Demande de visite refusée : {$property->title}")
            ->line("La demande de {$this->booking->prospect_name} pour le {$this->slotLabel()} a été refusée.");
    }
}
