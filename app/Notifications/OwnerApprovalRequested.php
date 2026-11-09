<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** Sent to the property owner: a prospect asks to visit, please approve or reject. */
class OwnerApprovalRequested extends BookingNotification
{
    protected function wantsSms(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;

        return (new MailMessage)
            ->subject("Demande de visite : {$property->title}")
            ->greeting("Bonjour {$property->owner_name},")
            ->line("{$this->booking->prospect_name} souhaite visiter votre bien « {$property->title} ».")
            ->line('Créneau demandé : '.$this->slotLabel())
            ->line('Situation professionnelle : '.$this->booking->employment_status->getLabel())
            ->action('Accepter ou refuser la visite', $this->booking->approvalUrl())
            ->line('Ce lien est personnel : ne le transférez pas.');
    }

    public function toSms(object $notifiable): string
    {
        return "Demande de visite pour {$this->booking->property->title} le {$this->slotLabel()}. "
            .'Répondez ici : '.$this->booking->approvalUrl();
    }
}
