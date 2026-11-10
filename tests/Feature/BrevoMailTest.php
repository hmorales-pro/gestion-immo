<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;
use Tests\TestCase;

class BrevoMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_brevo_mailer_is_configured(): void
    {
        config(['mail.mailers.brevo.key' => 'xkeysib-test']);

        $this->assertInstanceOf(BrevoApiTransport::class, Mail::mailer('brevo')->getSymfonyTransport());
    }

    public function test_confirmation_is_sent_to_brevo_with_invitation_tag_and_reply_to(): void
    {
        $requests = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests) {
            $requests[] = ['url' => $url, 'headers' => $options['headers'], 'body' => json_decode($options['body'], true)];

            return new MockResponse('{"messageId":"<123@brevo>"}', ['http_code' => 201]);
        });

        Mail::extend('brevo-test', fn () => new BrevoApiTransport('xkeysib-test', $client));
        config(['mail.default' => 'brevo-test', 'mail.mailers.brevo-test' => ['transport' => 'brevo-test']]);

        $booking = Booking::factory()->create(['status' => BookingStatus::Confirmed]);
        $booking->notifyNow(new BookingConfirmed($booking));

        $this->assertCount(1, $requests);
        $body = $requests[0]['body'];

        $this->assertSame('https://api.brevo.com/v3/smtp/email', $requests[0]['url']);
        $this->assertContains('api-key: xkeysib-test', $requests[0]['headers']);
        $this->assertSame($booking->prospect_email, $body['to'][0]['email']);
        $this->assertSame($booking->property->agent->email, $body['replyTo']['email']);
        $this->assertSame(['booking-confirmed'], $body['tags']);
        $this->assertSame('invitation.ics', $body['attachment'][0]['name']);
        $this->assertStringContainsString('BEGIN:VCALENDAR', base64_decode($body['attachment'][0]['content']));
    }
}
