<?php

namespace Database\Factories;

use App\Models\AdminNote;
use App\Models\FinalReport;
use App\Models\Output;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminNote>
 */
class AdminNoteFactory extends Factory
{
    protected $model = AdminNote::class;

    public function definition(): array
    {
        return [
            'noteable_id'    => Proposal::factory(),
            'noteable_type'  => 'proposal',
            'admin_id'       => User::factory()->admin(),
            'comment'        => fake()->paragraph(4),
            'recommendation' => fake()->sentence(10),
        ];
    }

    public function bySuperAdmin(): static
    {
        return $this->state(fn () => [
            'admin_id' => User::factory()->superAdmin(),
        ]);
    }

    public function forProposal(?Proposal $proposal = null): static
    {
        return $this->state(fn () => [
            'noteable_id'   => $proposal?->id ?? Proposal::factory(),
            'noteable_type' => 'proposal',
        ]);
    }

    public function forProgressReport(?ProgressReport $report = null): static
    {
        return $this->state(fn () => [
            'noteable_id'   => $report?->id ?? ProgressReport::factory(),
            'noteable_type' => 'progress_report',
        ]);
    }

    public function forFinalReport(?FinalReport $report = null): static
    {
        return $this->state(fn () => [
            'noteable_id'   => $report?->id ?? FinalReport::factory(),
            'noteable_type' => 'final_report',
        ]);
    }

    public function forOutput(?Output $output = null): static
    {
        return $this->state(fn () => [
            'noteable_id'   => $output?->id ?? Output::factory(),
            'noteable_type' => 'output',
        ]);
    }
}
