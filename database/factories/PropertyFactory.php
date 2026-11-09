<?php

namespace Database\Factories;

use App\Enums\BookingMode;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'T'.fake()->numberBetween(1, 5).' '.fake()->randomElement(['lumineux', 'rénové', 'avec balcon', 'calme']).' — '.fake()->city(),
            'address' => fake()->streetAddress().', '.fake()->postcode().' '.fake()->city(),
            'description' => fake()->paragraph(),
            'slot_duration' => 30,
            'booking_mode' => BookingMode::OwnerApproval,
            'owner_name' => fake()->name(),
            'owner_email' => fake()->unique()->safeEmail(),
            'owner_phone' => '+336'.fake()->numerify('########'),
        ];
    }

    public function mode(BookingMode $mode): static
    {
        return $this->state(['booking_mode' => $mode]);
    }
}
