<?php

namespace Database\Factories;

use App\Models\Output;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Output>
 */
class OutputFactory extends Factory
{
    protected $model = Output::class;

    /**
     * Distribusi level yang realistis:
     * Sinta 2-4 lebih banyak
     */
    protected array $levelWeights = [
        'Scopus'  => 3,
        'Sinta 1' => 4,
        'Sinta 2' => 6,
        'Sinta 3' => 7,
        'Sinta 4' => 5,
        'Sinta 5' => 3,
        'Sinta 6' => 2,
    ];

    public function definition(): array
    {
        $journals = [
            'Jurnal Teknologi Informasi dan Pendidikan',
            'Indonesian Journal of Computing',
            'Jurnal Sains dan Teknologi Terapan',
            'Jurnal Pengabdian Kepada Masyarakat',
            'International Journal of Science',
            'Jurnal Ilmiah Teknik Informatika',
            'Jurnal Riset dan Inovasi',
            'Jurnal Edukasi dan Teknologi',
        ];

        return [
            'proposal_id'  => Proposal::factory()->accepted(),
            'journal_name' => fake()->randomElement($journals),
            'journal_link' => 'https://journal.example.com/article/' . fake()->uuid(),
            'edition'      => 'Vol. ' . rand(1, 12),
            'volume'       => (string) rand(1, 20),
            'level'        => $this->weightedRandomLevel(),
            'status'       => 'pending',
        ];
    }

    public function pending(): static  { return $this->state(fn () => ['status' => 'pending']); }
    public function revised(): static  { return $this->state(fn () => ['status' => 'revised']); }
    public function accepted(): static { return $this->state(fn () => ['status' => 'accepted']); }
    public function rejected(): static { return $this->state(fn () => ['status' => 'rejected']); }

    /**
     * Pilih level dengan bobot
     */
    protected function weightedRandomLevel(): string
    {
        $pool = [];
        foreach ($this->levelWeights as $level => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $level;
            }
        }
        return $pool[array_rand($pool)];
    }

    /**
     * Force level spesifik
     */
    public function level(string $level): static
    {
        return $this->state(fn () => ['level' => $level]);
    }
}
