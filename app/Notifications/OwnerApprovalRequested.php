<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;

/** Sent to the property owner: a prospect asks to visit (or to move a visit), please approve or reject. */
class OwnerApprovalRequested extends BookingNotification
{
    protected const TAG = 'owner-approval';

    public function __construct(Booking $booking, public readonly bool $rescheduled = false)
    {
        parent::__construct($booking);
    }

    protected function wantsSms(object $notifiable): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;

        return $this->mail()
            ->subject(($this->rescheduled ? 'Changement de créneau' : 'Demande de visite')." : {$property->title}")
            ->greeting("Bonjour {$property->owner_name},")
            ->line($this->rescheduled
                ? "{$this->booking->prospect_name} souhaite déplacer sa visite de « {$property->title} »."
                : "{$this->booking->prospect_name} souhaite visiter votre bien « {$property->title} ».")
            ->line('Créneau demandé : '.$this->slotLabel())
            ->line('Situation professionnelle : '.$this->booking->employment_status->getLabel())
            ->action('Accepter ou refuser la visite', $this->booking->approvalUrl())
            ->line('Ce lien est personnel : ne le transférez pas.');
    }

    public function toSms(object $notifiable): string
    {
        return ($this->rescheduled ? 'Changement de créneau' : 'Demande de visite')
            ." pour {$this->booking->property->title} le {$this->slotLabel()}. "
            .'Répondez ici : '.$this->booking->approvalUrl();
    }
}
