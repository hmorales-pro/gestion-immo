<?php

namespace App\Filament\Pages\Auth;

use App\Support\Demo;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

/** The shared demo account cannot change its e-mail or password. */
class EditProfile extends BaseEditProfile
{
    public function save(): void
    {
        if (Demo::isDemoUser(auth()->user())) {
            Notification::make()->title('Modification du profil désactivée dans la démo')->warning()->send();

            return;
        }

        parent::save();
    }
}
