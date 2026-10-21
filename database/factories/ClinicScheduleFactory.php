<?php

namespace Database\Factories;

use App\Models\ClinicSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ClinicSchedule>
 */
class ClinicScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'weekday' => fake()->unique()->numberBetween(0, 6),
            'opens_at' => 900,
            'closes_at' => 1700,
            'is_closed' => false,
        ];
    }

    /**
     * Indicate that the clinic does not open on this weekday.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_closed' => true,
        ]);
    }

    /**
     * Set the weekday this schedule applies to, from a date the tests care about.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'weekday' => Carbon::parse($date)->dayOfWeek,
        ]);
    }
}
