<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\ProposalStudent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalStudent>
 */
class ProposalStudentFactory extends Factory
{
    protected $model = ProposalStudent::class;

    protected array $programs = [
        'Teknik Informatika',
        'Sistem Informasi',
        'Teknik Elektro',
        'Manajemen',
        'Akuntansi',
        'Pendidikan Bahasa Inggris',
        'Pendidikan Matematika',
        'Kesehatan Masyarakat',
    ];

    public function definition(): array
    {
        return [
            'proposal_id'   => Proposal::factory(),
            'nim'           => fake()->unique()->numerify('20########'),
            'name'          => fake()->name(),
            'program_study' => fake()->randomElement($this->programs),
            'role'          => 'member',
        ];
    }

    public function leader(): static
    {
        return $this->state(fn () => ['role' => 'leader']);
    }

    public function member(): static
    {
        return $this->state(fn () => ['role' => 'member']);
    }
}
