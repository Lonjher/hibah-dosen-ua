<?php

namespace Database\Factories;

use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Period>
 */
class PeriodFactory extends Factory
{
    protected $model = Period::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2024, 2030);

        return [
            'periode'   => "Ganjil {$year}/".($year + 1),
            'is_active' => false,
            'open_from' => now()->subMonth(),
            'open_to'   => now()->addMonths(2),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
