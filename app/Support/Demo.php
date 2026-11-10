<?php

namespace App\Support;

use App\Actions\Slots\GenerateSlots;
use App\Enums\BookingMode;
use App\Enums\BookingStatus;
use App\Enums\EmploymentStatus;
use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Public demo: a demo agent with sample properties, reset automatically.
 */
class Demo
{
    private const RESET_KEY = 'demo:last_reset';

    public static function enabled(): bool
    {
        return (bool) config('immobooking.demo.enabled');
    }

    public static function agent(): ?User
    {
        return static::enabled()
            ? User::query()->where('email', config('immobooking.demo.email'))->first()
            : null;
    }

    public static function isDemoUser(?User $user): bool
    {
        return static::enabled() && $user !== null && $user->email === config('immobooking.demo.email');
    }

    public static function ownsProperty(?Property $property): bool
    {
        return static::enabled() && $property !== null
            && $property->agent()->where('email', config('immobooking.demo.email'))->exists();
    }

    /** Resets the demo when it is older than the configured lifetime (lazy, no cron). */
    public static function resetIfStale(): void
    {
        if (! static::enabled()) {
            return;
        }

        $last = Cache::get(self::RESET_KEY);
        $lifetime = config('immobooking.demo.reset_every_minutes') * 60;

        if ($last === null || now()->timestamp - $last > $lifetime || static::agent() === null) {
            Cache::lock('demo:resetting', 60)->get(fn () => static::reset());
        }
    }

    public static function reset(): User
    {
        $agent = DB::transaction(function (): User {
            $agent = User::query()->firstOrNew(['email' => config('immobooking.demo.email')]);
            $agent->forceFill([
                'name' => 'Agence Démo',
                'password' => Hash::make(config('immobooking.demo.password')),
            ])->save();

            // Cascades to slots and bookings.
            $agent->properties()->each(fn (Property $property) => $property->delete());

            $properties = collect([
                [
                    'title' => 'T3 lumineux avec balcon — Croix-Rousse',
                    'address' => '12 rue d\'Austerlitz, 69004 Lyon',
                    'description' => "68 m², 3e étage avec ascenseur, balcon plein sud.\nLoyer 1 150 € CC. Disponible le 1er du mois.",
                    'booking_mode' => BookingMode::OwnerApproval,
                    'owner_name' => 'Mme Lefèvre (propriétaire démo)',
                ],
                [
                    'title' => 'Studio meublé — Part-Dieu',
                    'address' => '45 boulevard Vivier-Merle, 69003 Lyon',
                    'description' => "24 m² entièrement meublé, idéal étudiant ou jeune actif.\nLoyer 620 € CC.",
                    'booking_mode' => BookingMode::AgentApproval,
                    'owner_name' => 'M. Garnier (propriétaire démo)',
                ],
                [
                    'title' => 'Maison 5 pièces avec jardin — Écully',
                    'address' => '8 chemin des Mouilles, 69130 Écully',
                    'description' => "140 m² sur 600 m² de terrain, garage double.\nPrix 690 000 €.",
                    'booking_mode' => BookingMode::Instant,
                    'owner_name' => 'Famille Roux (propriétaires démo)',
                ],
            ])->map(fn (array $data) => $agent->properties()->create([
                ...$data,
                'slot_duration' => 30,
                'owner_email' => 'proprietaire@immobooking.test',
                'owner_phone' => '+33600000000',
            ]));

            $generate = app(GenerateSlots::class);
            foreach ($properties as $property) {
                $generate->execute($property, today()->addDay(), '18:00', '19:30', weeks: 1);
                $generate->execute($property, today()->next('Saturday'), '10:00', '12:30', weeks: 2);
                $generate->execute($property, today()->next('Wednesday'), '12:30', '14:00', weeks: 2);
            }

            // A few existing requests so the admin is not empty.
            $samples = [
                [$properties[0], 'Camille Martin', EmploymentStatus::Cdi, BookingStatus::Pending],
                [$properties[0], 'Léo Bernard', EmploymentStatus::Freelance, BookingStatus::Confirmed],
                [$properties[1], 'Inès Moreau', EmploymentStatus::Student, BookingStatus::Pending],
                [$properties[2], 'Famille Petit', EmploymentStatus::CivilServant, BookingStatus::Confirmed],
            ];

            foreach ($samples as $i => [$property, $name, $employment, $status]) {
                $slot = $property->slots()->getQuery()->available()->skip(2)->first();
                $slot->update(['is_booked' => $status === BookingStatus::Confirmed]);

                Booking::query()->create([
                    'property_id' => $property->id,
                    'property_slot_id' => $slot->id,
                    'prospect_name' => $name,
                    'prospect_email' => 'visiteur'.($i + 1).'@immobooking.test',
                    'prospect_phone' => '+3361234567'.$i,
                    'employment_status' => $employment,
                    'status' => $status,
                    'decided_at' => $status === BookingStatus::Confirmed ? now() : null,
                ]);
            }

            return $agent;
        });

        Cache::forever(self::RESET_KEY, now()->timestamp);

        return $agent;
    }
}
