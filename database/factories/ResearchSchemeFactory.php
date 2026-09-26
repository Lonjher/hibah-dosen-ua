<?php

namespace Database\Factories;

use App\Models\ResearchScheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchScheme>
 */
class ResearchSchemeFactory extends Factory
{
    protected $model = ResearchScheme::class;

    public function definition(): array
    {
        $schemes = [
            ['name' => 'Penelitian Dasar',          'code' => 'PD', 'budget' => 10_000_000],
            ['name' => 'Penelitian Terapan',        'code' => 'PT', 'budget' => 20_000_000],
            ['name' => 'Pengabdian Masyarakat',     'code' => 'PM', 'budget' => 15_000_000],
            ['name' => 'Penelitian Kolaboratif',    'code' => 'PK', 'budget' => 25_000_000],
        ];

        $scheme = fake()->randomElement($schemes);

        return [
            'name'         => $scheme['name'],
            'code'         => $scheme['code'].'-'.fake()->unique()->numerify('###'),
            'description'  => fake()->sentence(12),
            'budget_limit' => (string) $scheme['budget'],
            'is_active'    => true,
        ];
    }
}
