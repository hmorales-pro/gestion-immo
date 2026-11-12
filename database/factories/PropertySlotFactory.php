<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertySlot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PropertySlot>
 */
class PropertySlotFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 14))->setTime(fake()->numberBetween(9, 18), 0);

        return [
            'property_id' => Property::factory(),
            'start_time' => $start,
            'end_time' => $start->copy()->addMinutes(30),
            'is_booked' => false,
        ];
    }

    public function at(\DateTimeInterface $start, int $minutes = 30): static
    {
        return $this->state([
            'start_time' => $start,
            'end_time' => Carbon::instance($start)->addMinutes($minutes),
        ]);
    }
}
