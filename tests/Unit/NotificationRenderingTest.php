<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Property;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingReceived;
use App\Notifications\BookingRejected;
use App\Notifications\BookingRescheduled;
use App\Notifications\InformationProvided;
use App\Notifications\InformationRequested;
use App\Notifications\OwnerApprovalRequested;
use App\Notifications\VisitInvitation;
use App\Notifications\VisitRequested;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_notification_renders_for_every_recipient(): void
    {
        $booking = Booking::factory()->create()->load(['property.agent', 'slot']);
        $owner = (new AnonymousNotifiable)->route('mail', 'owner@example.com')->route('sms', '+33600000000');

        $notifications = [
            new OwnerApprovalRequested($booking),
            new OwnerApprovalRequested($booking, rescheduled: true),
            new VisitRequested($booking),
            new BookingReceived($booking),
            new BookingConfirmed($booking),
            new BookingRejected($booking),
            new BookingRescheduled($booking, 'lundi 1 décembre 2026, 10:00 – 10:30'),
            new BookingCancelled($booking),
            new InformationRequested($booking, 'Date d\'emménagement ?'),
            new InformationProvided($booking),
        ];

        foreach ($notifications as $notification) {
            $class = $notification::class;
            foreach ([$booking, $booking->property->agent, $owner] as $notifiable) {
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

    public function test_visit_invitation_renders(): void
    {
        $property = Property::factory()->create(['title' => 'Loft Canal']);

        $mail = (new VisitInvitation($property, 'Camille', 'À bientôt'))->toMail(new AnonymousNotifiable);

        $this->assertStringContainsString('Loft Canal', $mail->subject);
        $this->assertSame($property->publicUrl(), $mail->actionUrl);
        $this->assertNotEmpty((string) $mail->render());
    }

    public function test_mail_layout_is_in_french(): void
    {
        $booking = Booking::factory()->create();

        $html = (string) (new BookingConfirmed($booking))->toMail($booking)->render();

        $this->assertStringContainsString('Cordialement', $html);
        $this->assertStringContainsString('copiez et collez ce lien', $html);
        $this->assertStringNotContainsString('Regards', $html);
    }

    public function test_every_notification_survives_the_queue(): void
    {
        $booking = Booking::factory()->create()->load(['property.agent', 'slot']);

        foreach ([
            new OwnerApprovalRequested($booking, rescheduled: true),
            new VisitRequested($booking),
            new BookingReceived($booking),
            new BookingConfirmed($booking),
            new BookingRejected($booking),
            new BookingRescheduled($booking, 'avant'),
            new BookingCancelled($booking),
            new InformationRequested($booking, 'question'),
            new InformationProvided($booking),
            new VisitInvitation($booking->property, 'Camille', 'message'),
        ] as $notification) {
            // Same round-trip as a queued job (SerializesModels).
            $restored = unserialize(serialize(new SendQueuedNotifications(collect([$booking]), $notification)));
            $this->assertInstanceOf($notification::class, $restored->notification);
        }
    }
}
