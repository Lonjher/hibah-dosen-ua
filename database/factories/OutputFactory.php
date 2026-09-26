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

    public function definition(): array
    {
        $journals = [
            'Jurnal Teknologi Informasi',
            'Jurnal Sains dan Teknologi',
            'Indonesian Journal of Science',
            'Jurnal Pengabdian Masyarakat',
            'Jurnal Ilmiah Universitas',
        ];

        return [
            'proposal_id'  => Proposal::factory()->accepted(),
            'journal_name' => fake()->randomElement($journals),
            'journal_link' => 'https://journal.example.com/article/'.fake()->uuid(),
            'edition'      => 'Vol. '.fake()->numberBetween(1, 12),
            'volume'       => (string) fake()->numberBetween(1, 20),
            'level'        => fake()->randomElement(['Lokal', 'Nasional', 'Internasional']),
            'status'       => 'pending',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function revised(): static
    {
        return $this->state(fn () => ['status' => 'revised']);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => 'accepted']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}
