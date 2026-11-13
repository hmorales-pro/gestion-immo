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

        if ($this->recipient($notifiable) === 'occupant') {
            return $this->withInvitation(notifiable: $notifiable, mail: $this->occupantMail('Visite prévue', 'Une visite du logement que vous occupez est prévue.'));
        }

        $mail = $this->mail()
            ->subject("Visite confirmée : {$property->title}")
            ->line('La visite suivante est confirmée :')
            ->line('Bien : '.$property->title)
            ->line('Adresse : '.$property->address)
            ->line('Créneau : '.$this->slotLabel());

        if ($this->recipient($notifiable) === 'prospect') {
            $mail->greeting("Bonjour {$this->booking->prospect_name},")
                ->line('Votre interlocuteur : '.$property->agent->name.' ('.$property->agent->email.').')
                ->action('Modifier ou annuler le rendez-vous', $this->booking->manageUrl())
                ->line('Après la visite, ce même lien vous permettra de donner votre avis en 30 secondes.');
        } else {
            $mail->line($this->visitorLine($notifiable));
        }

        if ($this->recipient($notifiable) === 'owner') {
            $mail->line('Suivez toutes les visites et les avis des visiteurs : ['.'votre page de suivi]('.$property->ownerReportUrl().')');
        }

        return $this->withCalendarLinks($this->withInvitation($mail, $notifiable), $notifiable);
    }

    public function toSms(object $notifiable): string
    {
        return "Visite confirmée : {$this->booking->property->title}, {$this->booking->property->address}, le {$this->slotLabel()}.";
    }
}
