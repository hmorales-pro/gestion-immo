<?php

namespace App\Notifications;

use App\Filament\Resources\BookingResource;
use Illuminate\Notifications\Messages\MailMessage;

/** A visitor gave their opinion: the agent and the owner are told right away. */
class FeedbackReceived extends BookingNotification
{
    protected const TAG = 'visit-feedback';

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;
        $property = $booking->property;
        $isAgent = $this->recipient($notifiable) === 'agent';

        $mail = $this->mail()
            ->subject("Avis de visite : {$property->title}")
            ->line(($isAgent ? $booking->prospect_name : 'Un visiteur')." a donné son avis sur la visite du {$this->slotLabel()} :")
            ->line('Intérêt : '.$booking->feedback_interest->getLabel())
            ->line('Prix : '.($booking->feedback_price?->getLabel() ?? '—'));

        if (filled($booking->feedback_comment)) {
            $mail->line('« '.$booking->feedback_comment.' »');
        }

        return $isAgent
            ? $mail->action('Voir la visite', BookingResource::getUrl('view', ['record' => $booking], panel: 'admin'))
            : $mail->action('Voir toutes les visites de votre bien', $property->ownerReportUrl());
    }
}
