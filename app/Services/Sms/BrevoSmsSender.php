<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

/** Brevo transactional SMS API. */
class BrevoSmsSender implements SmsSender
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $sender,
    ) {}

    public function send(string $to, string $message): void
    {
        Http::withHeaders(['api-key' => $this->apiKey])
            ->acceptJson()
            ->post('https://api.brevo.com/v3/transactionalSMS/sms', [
                'type' => 'transactional',
                'sender' => mb_substr($this->sender, 0, 11),
                'recipient' => ltrim($to, '+'),
                'content' => $message,
                'tag' => 'immobooking',
            ])
            ->throw();
    }
}
