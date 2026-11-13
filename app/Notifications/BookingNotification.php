<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Services\Calendar\BookingCalendar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Brevo tag, used to filter statistics in the Brevo dashboard. */
    protected const TAG = 'booking';

    // Not readonly: queued notifications are unserialized from the child class scope.
    public function __construct(public Booking $booking)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = filled($notifiable->routeNotificationFor('mail', $this)) ? ['mail'] : [];

        if ($this->wantsSms($notifiable) && filled($notifiable->routeNotificationFor('sms', $this))) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    protected function wantsSms(object $notifiable): bool
    {
        return false;
    }

    protected function slotLabel(): string
    {
        return $this->booking->slot->label();
    }

    /** Booking = the prospect, User = the agent, on-demand route = the owner or the occupant. */
    protected function recipient(object $notifiable): string
    {
        return match (true) {
            $notifiable instanceof Booking => 'prospect',
            $notifiable instanceof User => 'agent',
            $notifiable instanceof AnonymousNotifiable && isset($notifiable->routes['occupant']) => 'occupant',
            default => 'owner',
        };
    }

    /** Mail for the current occupant: when someone comes, nothing about the visitor. */
    protected function occupantMail(string $subject, string $intro): MailMessage
    {
        $property = $this->booking->property;
        $agent = $property->agent;

        return $this->mail()
            ->subject($subject.' : '.$property->address)
            ->greeting(filled($property->occupant_name) ? "Bonjour {$property->occupant_name}," : 'Bonjour,')
            ->line($intro)
            ->line('Adresse : '.$property->address)
            ->line('Créneau : '.$this->slotLabel())
            ->line("Votre contact à l'agence : {$agent->name} ({$agent->email}). Vous pouvez répondre directement à cet e-mail.");
    }

    protected function mail(): MailMessage
    {
        $agent = $this->booking->property->agent;

        return (new MailMessage)
            ->replyTo($agent->email, $agent->name)
            ->tag(static::TAG)
            ->metadata('booking_id', (string) $this->booking->id);
    }

    /**
     * Attaches an iCalendar invitation (REQUEST or CANCEL) that Google Calendar, Apple Calendar
     * and Outlook display as an event with "Add / Update / Remove" buttons.
     */
    protected function withInvitation(MailMessage $mail, ?object $notifiable = null): MailMessage
    {
        $property = $this->booking->property;
        $attendee = match ($notifiable ? $this->recipient($notifiable) : null) {
            'prospect' => ['name' => $this->booking->prospect_name, 'email' => $this->booking->prospect_email],
            'owner' => ['name' => $property->owner_name, 'email' => $property->owner_email],
            'occupant' => filled($property->occupant_email) ? ['name' => $property->occupant_name ?: 'Occupant', 'email' => $property->occupant_email] : null,
            default => null,
        };

        $ics = app(BookingCalendar::class)->invitation($this->booking, $attendee);

        return $mail->attachData($ics->render(), 'invitation.ics', [
            'mime' => 'text/calendar; charset=UTF-8; method='.$ics->method(),
        ]);
    }

    /** The agent gets the visitor's phone number; the owner only their name. */
    protected function visitorLine(object $notifiable): string
    {
        return $this->recipient($notifiable) === 'agent'
            ? "Visiteur : {$this->booking->prospect_name} — {$this->booking->prospect_phone}"
            : "Visiteur : {$this->booking->prospect_name}";
    }

    /** "Add to my calendar" links, for clients that ignore attachments. */
    protected function withCalendarLinks(MailMessage $mail, object $notifiable): MailMessage
    {
        $calendar = app(BookingCalendar::class);

        // The .ics link carries the prospect's private token: only for the prospect.
        $ics = $this->recipient($notifiable) === 'prospect'
            ? ' · [Apple / autre (.ics)]('.$this->booking->icsUrl().')'
            : '';

        return $mail->line('Ajouter à votre agenda : [Google Agenda]('.$calendar->googleUrl($this->booking).')'
            .' · [Outlook]('.$calendar->outlookUrl($this->booking).')'.$ics);
    }
}
