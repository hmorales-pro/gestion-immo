<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRejected;
use App\Notifications\OwnerApprovalRequested;
use App\Notifications\VisitRequested;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_notification_renders_for_every_recipient(): void
    {
        $booking = Booking::factory()->create()->load(['property.agent', 'slot']);
        $owner = (new AnonymousNotifiable)->route('mail', 'owner@example.com')->route('sms', '+33600000000');

        foreach ([OwnerApprovalRequested::class, VisitRequested::class, BookingConfirmed::class, BookingRejected::class] as $class) {
            foreach ([$booking, $booking->property->agent, $owner] as $notifiable) {
                $notification = new $class($booking);
                $mail = $notification->toMail($notifiable);
                $this->assertStringContainsString($booking->property->title, $mail->subject, $class);
                $this->assertNotEmpty((string) $mail->render());

                if (method_exists($notification, 'toSms')) {
                    $this->assertNotEmpty($notification->toSms($notifiable));
                }
            }
        }
    }

    public function test_owner_receives_approval_link_by_sms(): void
    {
        $sent = [];
        $this->app->instance(SmsSender::class, new class($sent) implements SmsSender
        {
            public function __construct(private array &$sent) {}

            public function send(string $to, string $message): void
            {
                $this->sent[] = [$to, $message];
            }
        });

        $booking = Booking::factory()->create();

        Notification::route('mail', 'owner@example.com')
            ->route('sms', '+33600000000')
            ->notifyNow(new OwnerApprovalRequested($booking));

        $this->assertCount(1, $sent);
        $this->assertSame('+33600000000', $sent[0][0]);
        $this->assertStringContainsString($booking->approvalUrl(), $sent[0][1]);
    }
}
