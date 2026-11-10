<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Services\Calendar\BookingCalendar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Brevo tag, used to filter statistics in the Brevo dashboard. */
    protected const TAG = 'booking';

    public function __construct(public readonly Booking $booking)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

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

    /** Booking = the prospect, User = the agent, on-demand route = the owner. */
    protected function recipient(object $notifiable): string
    {
        return match (true) {
            $notifiable instanceof Booking => 'prospect',
            $notifiable instanceof User => 'agent',
            default => 'owner',
        };
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
    protected function withInvitation(MailMessage $mail): MailMessage
    {
        $ics = app(BookingCalendar::class)->invitation($this->booking);

        return $mail->attachData($ics->render(), 'invitation.ics', [
            'mime' => 'text/calendar; charset=UTF-8; method='.$ics->method(),
        ]);
    }

    /** "Add to my calendar" links, for clients that ignore attachments. */
    protected function withCalendarLinks(MailMessage $mail): MailMessage
    {
        $calendar = app(BookingCalendar::class);

        return $mail->line('Ajouter à votre agenda : [Google Agenda]('.$calendar->googleUrl($this->booking).')'
            .' · [Outlook]('.$calendar->outlookUrl($this->booking).')'
            .' · [Apple / autre (.ics)]('.$this->booking->icsUrl().')');
    }
}
