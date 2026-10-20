<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'doctor_full_name' => fake()->name(),
            'start_time' => fake()->numberBetween(0, 23) * 100 + fake()->numberBetween(0, 59),
            'date' => fake()->dateTimeBetween('tomorrow', '+1 month')->format('Y-m-d'),
            'confirmed' => false,
        ];
    }
}
