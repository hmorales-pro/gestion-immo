<?php

namespace App\Exceptions;

use RuntimeException;

/** A business rule prevented a booking operation. The message is safe to show to users. */
class BookingException extends RuntimeException
{
    public static function slotUnavailable(): self
    {
        return new self("Ce créneau n'est plus disponible. Merci d'en choisir un autre.");
    }

    public static function overlapping(): self
    {
        return new self('Vous avez déjà une visite prévue sur un créneau qui chevauche celui-ci.');
    }

    public static function alreadyDecided(): self
    {
        return new self('Cette demande a déjà été traitée.');
    }

    public static function slotInPast(): self
    {
        return new self('Ce créneau est déjà passé.');
    }
}
