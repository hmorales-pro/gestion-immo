<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/** Sent to the prospect, the agent and (unless they decided themselves) the owner. */
class BookingConfirmed extends BookingNotification
{
    protected function wantsSms(): bool
    {
        return true;
    }

    /**
     * Only the prospect and the owner get a text message; agents work from the panel.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User ? ['mail'] : parent::via($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;

        $mail = (new MailMessage)
            ->subject("Visite confirmée : {$property->title}")
            ->line('La visite suivante est confirmée :')
            ->line('Bien : '.$property->title)
            ->line('Adresse : '.$property->address)
            ->line('Créneau : '.$this->slotLabel());

        if ($notifiable instanceof Booking) {
            return $mail->greeting("Bonjour {$this->booking->prospect_name},")
                ->line('Votre interlocuteur : '.$property->agent->name.' ('.$property->agent->email.').');
        }

        return $mail->line("Visiteur : {$this->booking->prospect_name} — {$this->booking->prospect_phone}");
    }

    public function toSms(object $notifiable): string
    {
        return "Visite confirmée : {$this->booking->property->title}, {$this->booking->property->address}, le {$this->slotLabel()}.";
    }
}
