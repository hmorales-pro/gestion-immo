<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/** Sent to everyone involved when a visit is cancelled. Removes the event from their calendars. */
class BookingCancelled extends BookingNotification
{
    protected const TAG = 'booking-cancelled';

    protected function wantsSms(object $notifiable): bool
    {
        return ! $notifiable instanceof User;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;

        $mail = $this->mail()
            ->subject("Visite annulée : {$property->title}")
            ->line("La visite de « {$property->title} » du {$this->slotLabel()} est annulée.");

        if (filled($this->booking->cancellation_reason)) {
            $mail->line('Motif : '.$this->booking->cancellation_reason);
        }

        if ($this->recipient($notifiable) === 'prospect') {
            $mail->greeting("Bonjour {$this->booking->prospect_name},")
                ->action('Choisir un autre créneau', $property->publicUrl());
        } else {
            $mail->line("Visiteur : {$this->booking->prospect_name} — {$this->booking->prospect_phone}");
        }

        return $this->withInvitation($mail);
    }

    public function toSms(object $notifiable): string
    {
        return "Visite {$this->booking->property->title} du {$this->slotLabel()} annulée.";
    }
}
