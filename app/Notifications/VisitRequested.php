<?php

namespace App\Notifications;

use App\Enums\BookingMode;
use App\Filament\Resources\BookingResource;
use Illuminate\Notifications\Messages\MailMessage;

/** Sent to the agent when a prospect submits a request. */
class VisitRequested extends BookingNotification
{
    protected const TAG = 'agent-new-request';

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;
        $url = BookingResource::getUrl('view', ['record' => $this->booking], panel: 'admin');

        $mail = (new MailMessage)
            ->tag(static::TAG)
            ->replyTo($this->booking->prospect_email, $this->booking->prospect_name)
            ->subject("Nouvelle demande de visite : {$property->title}")
            ->line("{$this->booking->prospect_name} ({$this->booking->prospect_email}, {$this->booking->prospect_phone}) demande une visite.")
            ->line('Bien : '.$property->title.' — '.$property->address)
            ->line('Créneau : '.$this->slotLabel());

        return match ($property->booking_mode) {
            BookingMode::OwnerApproval => $mail
                ->line('Le propriétaire a reçu un lien pour valider la visite.')
                ->action('Voir la demande', $url),
            BookingMode::AgentApproval => $mail
                ->line('Cette demande attend votre validation.')
                ->action('Traiter la demande', $url),
            BookingMode::Instant => $mail
                ->line('Ce bien est en confirmation immédiate : la visite est déjà confirmée.')
                ->action('Voir la visite', $url),
        };
    }
}
