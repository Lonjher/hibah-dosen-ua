<?php

namespace Database\Factories;

use App\Models\Period;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        return [
            'research_scheme_id' => ResearchScheme::factory(),
            'user_id'            => User::factory()->user(),
            'reviewer_id'        => null,
            'title'              => fake()->sentence(8),
            'summary'            => fake()->paragraph(5),
            'keywords'           => implode(', ', fake()->words(4)),
            'is_research'        => fake()->boolean(70),
            'file_path'          => 'proposals/'.fake()->uuid().'.pdf',
            'status'             => 'pending',
            'period_id'          => Period::factory()->active(),
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

    public function revised(): static
    {
        return $this->state(fn () => ['status' => 'revised']);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status'      => 'accepted',
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status'      => 'rejected',
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    public function withReviewer(?User $reviewer = null): static
    {
        return $this->state(fn () => [
            'reviewer_id' => $reviewer?->id ?? User::factory()->reviewer(),
        ]);
    }
}
