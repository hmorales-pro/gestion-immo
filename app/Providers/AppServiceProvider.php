<?php

namespace App\Providers;

use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Sms\TwilioSmsSender;
use App\Services\Sms\VonageSmsSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

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
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
    }
}
