<?php

namespace Database\Seeders;

use App\Actions\Slots\GenerateSlots;
use App\Enums\BookingMode;
use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo data: log in with agent@example.com / password.
     */
    public function run(GenerateSlots $generateSlots): void
    {
        $agent = User::factory()->create([
            'name' => 'Agent Démo',
            'email' => 'agent@example.com',
        ]);

        $properties = collect([
            ['T3 lumineux — Croix-Rousse', '12 rue d\'Austerlitz, 69004 Lyon', BookingMode::OwnerApproval],
            ['Studio meublé — Part-Dieu', '45 boulevard Vivier-Merle, 69003 Lyon', BookingMode::AgentApproval],
            ['Maison avec jardin — Écully', '8 chemin des Mouilles, 69130 Écully', BookingMode::Instant],
        ])->map(fn (array $p) => Property::factory()->for($agent, 'agent')->create([
            'title' => $p[0],
            'address' => $p[1],
            'booking_mode' => $p[2],
        ]));

        foreach ($properties as $property) {
            $generateSlots->execute($property, today()->next('Saturday'), '10:00', '12:00', weeks: 3);
            $generateSlots->execute($property, today()->next('Wednesday'), '18:00', '19:30', weeks: 2);
        }

        Booking::factory()->count(2)->sequence(
            fn ($sequence) => ['property_slot_id' => $properties[0]->slots()->skip($sequence->index * 2)->first()->id],
        )->create(['property_id' => $properties[0]->id]);

        // A second agent, to check that tenants are isolated.
        Property::factory()->for(User::factory()->create(['email' => 'other@example.com']), 'agent')->create();
    }
}
