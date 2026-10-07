<?php

namespace Database\Factories;

use App\Models\AdminNote;
use App\Models\FinalReport;
use App\Models\Outcome;
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
            'comment'        => fake()->paragraph(3),
            'recommendation' => fake()->sentence(8),
        ];
    }

    public function forProposal(?Proposal $proposal = null, ?User $admin = null): static
    {
        return $this->state(fn () => [
            'noteable_id'   => $proposal?->id ?? Proposal::factory(),
            'noteable_type' => 'proposal',
            'admin_id'      => $admin?->id ?? User::factory()->admin(),
        ]);
    }

    public function forProgressReport(?ProgressReport $report = null, ?User $admin = null): static
    {
        return $this->state(fn () => [
            'noteable_id'   => $report?->id ?? ProgressReport::factory(),
            'noteable_type' => 'progress_report',
            'admin_id'      => $admin?->id ?? User::factory()->admin(),
        ]);
    }

    public function forFinalReport(?FinalReport $report = null, ?User $admin = null): static
    {
        return $this->state(fn () => [
            'noteable_id'   => $report?->id ?? FinalReport::factory(),
            'noteable_type' => 'final_report',
            'admin_id'      => $admin?->id ?? User::factory()->admin(),
        ]);
    }

    public function forOutcome(?Outcome $Outcome = null, ?User $admin = null): static
    {
        return $this->state(fn () => [
            'noteable_id'   => $Outcome?->id ?? Outcome::factory(),
            'noteable_type' => 'Outcome',
            'admin_id'      => $admin?->id ?? User::factory()->admin(),
        ]);
    }
}
