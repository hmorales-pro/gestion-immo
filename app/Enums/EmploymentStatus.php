<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmploymentStatus: string implements HasLabel
{
    case Cdi = 'CDI';
    case Cdd = 'CDD';
    case Freelance = 'FREELANCE';
    case CivilServant = 'CIVIL_SERVANT';
    case Student = 'STUDENT';
    case Retired = 'RETIRED';
    case JobSeeker = 'JOB_SEEKER';
    case Other = 'OTHER';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cdi => 'CDI',
            self::Cdd => 'CDD / Intérim',
            self::Freelance => 'Indépendant / Freelance',
            self::CivilServant => 'Fonctionnaire',
            self::Student => 'Étudiant',
            self::Retired => 'Retraité',
            self::JobSeeker => "En recherche d'emploi",
            self::Other => 'Autre',
        };
    }
}
