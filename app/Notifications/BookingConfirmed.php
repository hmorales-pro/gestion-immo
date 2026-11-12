<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/** Sent to the prospect, the agent and (unless they decided themselves) the owner. Carries a calendar invitation. */
class BookingConfirmed extends BookingNotification
{
    protected const TAG = 'booking-confirmed';

    /** Only the prospect and the owner get a text message; agents work from the panel. */
    protected function wantsSms(object $notifiable): bool
    {
        return ! $notifiable instanceof User;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;

        $mail = $this->mail()
            ->subject("Visite confirmée : {$property->title}")
            ->line('La visite suivante est confirmée :')
            ->line('Bien : '.$property->title)
            ->line('Adresse : '.$property->address)
            ->line('Créneau : '.$this->slotLabel());

        if ($this->recipient($notifiable) === 'prospect') {
            $mail->greeting("Bonjour {$this->booking->prospect_name},")
                ->line('Votre interlocuteur : '.$property->agent->name.' ('.$property->agent->email.').')
                ->action('Modifier ou annuler le rendez-vous', $this->booking->manageUrl());
        } else {
            $mail->line("Visiteur : {$this->booking->prospect_name} — {$this->booking->prospect_phone}");
        }

        return $this->withCalendarLinks($this->withInvitation($mail));
    }

    public function toSms(object $notifiable): string
    {
        return "Visite confirmée : {$this->booking->property->title}, {$this->booking->property->address}, le {$this->slotLabel()}.";
    }
}
