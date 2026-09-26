<?php

namespace Database\Factories;

use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ReviewerNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewerNote>
 */
class ReviewerNoteFactory extends Factory
{
    protected $model = ReviewerNote::class;

    public function definition(): array
    {
        return [
            'noteable_id'    => Proposal::factory(),
            'noteable_type'  => 'proposal',
            'reviewer_id'    => User::factory()->reviewer(),
            'comment'        => fake()->paragraph(4),
            'is_approved'    => fake()->boolean(60),
            'recommendation' => fake()->sentence(10),
        ];
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

    public function approved(): static
    {
        return $this->state(fn () => ['is_approved' => true]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['is_approved' => false]);
    }
}
