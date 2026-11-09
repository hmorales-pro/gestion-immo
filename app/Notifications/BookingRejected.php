<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;

/** Sent to the agent and the prospect when a request is turned down. */
class BookingRejected extends BookingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;

        if ($notifiable instanceof Booking) {
            return (new MailMessage)
                ->subject("Votre demande de visite : {$property->title}")
                ->greeting("Bonjour {$this->booking->prospect_name},")
                ->line("Le créneau du {$this->slotLabel()} n'a malheureusement pas pu être retenu.")
                ->action('Choisir un autre créneau', $property->publicUrl());
        }

        return (new MailMessage)
            ->subject("Demande de visite refusée : {$property->title}")
            ->line("La demande de {$this->booking->prospect_name} pour le {$this->slotLabel()} a été refusée.");
    }
}
