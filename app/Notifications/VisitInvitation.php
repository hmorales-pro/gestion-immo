<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent by the agent to a prospect: "pick a time slot to visit this property". */
class VisitInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Property $property,
        public readonly ?string $recipientName = null,
        public readonly ?string $personalMessage = null,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $agent = $this->property->agent;

        $mail = (new MailMessage)
            ->tag('visit-invitation')
            ->replyTo($agent->email, $agent->name)
            ->subject("Visite : {$this->property->title} — choisissez votre créneau")
            ->greeting($this->recipientName ? "Bonjour {$this->recipientName}," : 'Bonjour,')
            ->line("{$agent->name} vous invite à visiter « {$this->property->title} » ({$this->property->address}).");

        if (filled($this->personalMessage)) {
            $mail->line('« '.$this->personalMessage.' »');
        }

        return $mail
            ->action('Choisir mon créneau', $this->property->publicUrl())
            ->line('La réservation prend moins d\'une minute.');
    }
}
