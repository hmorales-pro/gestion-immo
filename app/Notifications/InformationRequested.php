<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;

/** The agent asks the prospect for missing details (documents, phone, situation…). */
class InformationRequested extends BookingNotification
{
    protected const TAG = 'information-requested';

    public function __construct(Booking $booking, public readonly string $question)
    {
        parent::__construct($booking);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->booking->property;

        return $this->mail()
            ->subject("Informations complémentaires : visite {$property->title}")
            ->greeting("Bonjour {$this->booking->prospect_name},")
            ->line("{$property->agent->name} a besoin de précisions au sujet de votre visite du {$this->slotLabel()} :")
            ->line('« '.$this->question.' »')
            ->action('Compléter mes informations', $this->booking->manageUrl())
            ->line('Vous pouvez aussi répondre directement à cet e-mail.');
    }
}
