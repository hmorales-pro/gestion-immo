<?php

namespace App\Notifications;

use App\Filament\Resources\BookingResource;
use Illuminate\Notifications\Messages\MailMessage;

/** Sent to the agent when the prospect updates their details from their booking page. */
class InformationProvided extends BookingNotification
{
    protected const TAG = 'information-provided';

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->tag(static::TAG)
            ->replyTo($this->booking->prospect_email, $this->booking->prospect_name)
            ->subject("{$this->booking->prospect_name} a complété ses informations — {$this->booking->property->title}")
            ->line("Visite : {$this->booking->property->title} — {$this->slotLabel()}")
            ->line("Téléphone : {$this->booking->prospect_phone}")
            ->line('Situation professionnelle : '.$this->booking->employment_status->getLabel())
            ->line('Message : '.($this->booking->message ?: '—'))
            ->action('Voir la demande', BookingResource::getUrl('view', ['record' => $this->booking], panel: 'admin'));
    }
}
