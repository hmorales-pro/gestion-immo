<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class VonageSmsSender implements SmsSender
{
    public function __construct(
        private readonly string $key,
        private readonly string $secret,
        private readonly string $from,
    ) {}

    public function send(string $to, string $message): void
    {
        Http::asForm()
            ->post('https://rest.nexmo.com/sms/json', [
                'api_key' => $this->key,
                'api_secret' => $this->secret,
                'from' => $this->from,
                'to' => ltrim($to, '+'),
                'text' => $message,
            ])
            ->throw();
    }
}
