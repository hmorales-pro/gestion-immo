<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum BookingMode: string implements HasColor, HasDescription, HasLabel
{
    /** The property owner validates each request through an emailed / texted link. */
    case OwnerApproval = 'OWNER_APPROVAL';

    /** The agent validates each request from the admin panel. */
    case AgentApproval = 'AGENT_APPROVAL';

    /** Requests are confirmed instantly. */
    case Instant = 'INSTANT';

    public function getLabel(): string
    {
        return match ($this) {
            self::OwnerApproval => 'Validation propriétaire',
            self::AgentApproval => 'Validation agent',
            self::Instant => 'Confirmation immédiate',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OwnerApproval => 'warning',
            self::AgentApproval => 'info',
            self::Instant => 'success',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::OwnerApproval => 'Le propriétaire reçoit un lien pour accepter ou refuser chaque demande.',
            self::AgentApproval => "L'agent accepte ou refuse les demandes depuis l'espace d'administration.",
            self::Instant => 'Les créneaux sont réservés sans validation.',
        };
    }
}
