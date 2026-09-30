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
        $year = fake()->numberBetween(2021, 2025);
        $isGanjil = fake()->boolean();

        $periode = ($isGanjil ? 'Ganjil' : 'Genap') . " {$year}/" . ($year + 1);

        $openFrom = $isGanjil
            ? "{$year}-08-01"
            : ($year + 1) . "-02-01";

        $openTo = $isGanjil
            ? "{$year}-10-31"
            : ($year + 1) . "-04-30";

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
    public function forYear(int $year, bool $ganjil = true): static
    {
        return $this->state(fn () => [
            'periode'   => ($ganjil ? 'Ganjil' : 'Genap') . " {$year}/" . ($year + 1),
            'open_from' => $ganjil ? "{$year}-08-01" : ($year + 1) . "-02-01",
            'open_to'   => $ganjil ? "{$year}-10-31" : ($year + 1) . "-04-30",
        ]);
    }
}
