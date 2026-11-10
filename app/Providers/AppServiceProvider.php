<?php

namespace App\Providers;

use App\Services\Sms\BrevoSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Sms\TwilioSmsSender;
use App\Services\Sms\VonageSmsSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsSender::class, function (): SmsSender {
            $config = config('sms');

            return match ($config['driver']) {
                'twilio' => new TwilioSmsSender(
                    (string) $config['twilio']['sid'],
                    (string) $config['twilio']['token'],
                    (string) ($config['twilio']['from'] ?? $config['from']),
                ),
                'brevo' => new BrevoSmsSender(
                    (string) config('services.brevo.key'),
                    (string) config('services.brevo.sms_sender'),
                ),
                'vonage' => new VonageSmsSender(
                    (string) $config['vonage']['key'],
                    (string) $config['vonage']['secret'],
                    (string) ($config['vonage']['from'] ?? $config['from']),
                ),
                default => new LogSmsSender,
            };
        });
    }

    public function boot(): void
    {
        // Brevo transactional e-mail API (symfony/brevo-mailer). Tags and metadata set on
        // notifications are forwarded to Brevo for statistics and webhooks.
        Mail::extend('brevo', fn (array $config) => (new BrevoTransportFactory(null, HttpClient::create()))->create(
            new Dsn('brevo+api', 'default', $config['key'] ?? config('services.brevo.key')),
        ));

        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
    }
}
