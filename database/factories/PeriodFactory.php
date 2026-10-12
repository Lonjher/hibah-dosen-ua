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
        $year = fake()->numberBetween(2021, 2026);

        $periode = "{$year}/" . ($year + 1);

        $openFrom = "{$year}-08-01";
        $openTo = "{$year}-10-31";

        return [
            'periode'   => $periode,
            'is_active' => false,
            'open_from' => $openFrom,
            'open_to'   => $openTo,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    /**
     * Set periode ke tahun spesifik
     */
    public function forYear(int $year): static
    {
        return $this->state(fn () => [
            'periode'   => "{$year}/" . ($year + 1),
            'open_from' =>"{$year}-08-01",
            'open_to'   => "{$year}-10-31",
        ]);
    }
}
