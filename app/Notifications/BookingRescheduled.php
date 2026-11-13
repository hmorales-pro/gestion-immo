<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/** Sent to everyone involved when a visit moves to another slot. Updates the calendar event in place. */
class BookingRescheduled extends BookingNotification
{
    protected const TAG = 'booking-rescheduled';

    public function __construct(Booking $booking, public readonly string $previousSlotLabel)
    {
        parent::__construct($booking);
    }

    protected function wantsSms(object $notifiable): bool
    {
        return ! $notifiable instanceof User;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;

        if ($this->recipient($notifiable) === 'occupant') {
            return $this->withInvitation(notifiable: $notifiable, mail: $this->occupantMail('Visite déplacée', 'Une visite du logement que vous occupez a changé d\'horaire (ancien créneau : '.$this->previousSlotLabel.').'));
        }

        $mail = $this->mail()
            ->subject("Visite déplacée : {$property->title}")
            ->line("La visite de « {$property->title} » a changé de créneau.")
            ->line('Ancien créneau : '.$this->previousSlotLabel)
            ->line('Nouveau créneau : '.$this->slotLabel());

        if ($this->booking->isPending()) {
            $mail->line('Ce nouveau créneau doit encore être validé : une confirmation suivra.');
        }

        if ($this->recipient($notifiable) === 'prospect') {
            $mail->greeting("Bonjour {$this->booking->prospect_name},")
                ->action('Voir mon rendez-vous', $this->booking->manageUrl());
        } else {
            $mail->line($this->visitorLine($notifiable));
        }

        return $this->withInvitation($mail, $notifiable);
    }

    public function toSms(object $notifiable): string
    {
        return "Visite {$this->booking->property->title} déplacée au {$this->slotLabel()}"
            .($this->booking->isPending() ? ' (en attente de validation).' : '.');
    }
}
