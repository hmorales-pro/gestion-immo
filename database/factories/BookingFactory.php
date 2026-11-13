<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\EmploymentStatus;
use App\Models\Booking;
use App\Models\PropertySlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_slot_id' => PropertySlot::factory(),
            'property_id' => fn (array $attributes) => PropertySlot::find($attributes['property_slot_id'])->property_id,
            'prospect_name' => fake()->name(),
            'prospect_email' => fake()->unique()->safeEmail(),
            'prospect_phone' => '+3361'.fake()->numerify('#######'),
            'employment_status' => fake()->randomElement(EmploymentStatus::cases()),
            'status' => BookingStatus::Pending,
        ];
    }
}
