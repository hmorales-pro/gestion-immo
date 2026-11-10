<?php

namespace App\Filament\Pages\Auth;

use App\Support\Demo;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

/** Pre-fills the demo credentials when the public demo is enabled. */
class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        if (Demo::enabled()) {
            Demo::resetIfStale();

            $this->form->fill([
                'email' => config('immobooking.demo.email'),
                'password' => config('immobooking.demo.password'),
                'remember' => false,
            ]);
        }
    }

    public function getSubheading(): string|Htmlable|null
    {
        return Demo::enabled()
            ? 'Démo publique : les identifiants sont pré-remplis. Les données sont réinitialisées régulièrement et aucun e-mail n\'est envoyé.'
            : null;
    }
}
