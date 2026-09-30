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

    /**
     * Skema untuk penelitian
     */
    public function research(): static
    {
        $schemes = [
            ['name' => 'Penelitian Dosen Pemula',              'code' => 'PDP',  'budget' => 10_000_000],
            ['name' => 'Penelitian Dasar Unggulan',           'code' => 'PDU',  'budget' => 15_000_000],
            ['name' => 'Penelitian Terapan',                  'code' => 'PT',   'budget' => 25_000_000],
            ['name' => 'Penelitian Kerjasama Antar PT',       'code' => 'PKPT', 'budget' => 30_000_000],
        ];

        $scheme = fake()->randomElement($schemes);

        return $this->state(fn () => [
            'name'         => $scheme['name'],
            'code'         => $scheme['code'] . '-' . fake()->unique()->numerify('###'),
            'description'  => 'Skema ' . $scheme['name'] . ' untuk dosen.',
            'budget_limit' => (string) $scheme['budget'],
            'is_active'    => true,
        ]);
    }

    /**
     * Skema untuk pengabdian
     */
    public function dedication(): static
    {
        $schemes = [
            ['name' => 'Pengabdian kepada Masyarakat Pemula',    'code' => 'PKMP', 'budget' => 8_000_000],
            ['name' => 'Pengabdian kepada Masyarakat Unggulan', 'code' => 'PKMU', 'budget' => 20_000_000],
        ];

        $scheme = fake()->randomElement($schemes);

        return $this->state(fn () => [
            'name'         => $scheme['name'],
            'code'         => $scheme['code'] . '-' . fake()->unique()->numerify('###'),
            'description'  => 'Skema ' . $scheme['name'] . ' untuk dosen.',
            'budget_limit' => (string) $scheme['budget'],
            'is_active'    => true,
        ]);
    }

    public function definition(): array
    {
        // default = research
        $schemes = [
            ['name' => 'Penelitian Dosen Pemula', 'code' => 'PDP', 'budget' => 10_000_000],
        ];
        $scheme = $schemes[0];

        return [
            'name'         => $scheme['name'],
            'code'         => $scheme['code'] . '-' . fake()->unique()->numerify('###'),
            'description'  => 'Skema penelitian.',
            'budget_limit' => (string) $scheme['budget'],
            'is_active'    => true,
        ];
    }
}
