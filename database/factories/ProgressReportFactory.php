<?php

namespace Database\Factories;

use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressReport>
 */
class ProgressReportFactory extends Factory
{
    protected $model = ProgressReport::class;

    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory()->accepted(),
            'reviewer_id' => null,
            'summary'     => fake()->paragraph(5),
            'keyword'     => implode(', ', fake()->words(3)),
            'report_path' => 'progress-reports/'.fake()->uuid().'.pdf',
            'ppt_path'    => 'progress-reports/'.fake()->uuid().'.pptx',
            'status'      => 'pending',
            'reviewed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending', 'reviewer_id' => null]);
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => 'submitted']);
    }

    public function underReview(): static
    {
        return $this->state(fn () => [
            'status'      => 'under_review',
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status'      => 'accepted',
            'reviewer_id' => User::factory()->reviewer(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status'      => 'rejected',
            'reviewer_id' => User::factory()->reviewer(),
            'reviewed_at' => now(),
        ]);
    }

    public function revised(): static
    {
        return $this->state(fn () => ['status' => 'revised']);
    }
}
