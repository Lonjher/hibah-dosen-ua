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
            'user_id' => User::factory()->user(),
            'reviewer_id' => null,
            'title' => fake()->sentence(6),
            'summary' => fake()->paragraph(6),
            'keywords' => implode(', ', fake()->words(4)),
            'is_research' => true,
            'file_path' => 'proposals/dummy-' . fake()->uuid() . '.pdf',
            'status' => 'pending',
            'period_id' => Period::factory(),
        ];
    }

    // ═══════════════ TYPE ═══════════════

    public function research(): static
    {
        return $this->state(fn() => ['is_research' => true]);
    }

    public function dedication(): static
    {
        return $this->state(fn() => ['is_research' => false]);
    }

    // ═══════════════ STATUS ═══════════════

    public function pending(): static
    {
        return $this->state(fn() => ['status' => 'pending', 'reviewer_id' => null]);
    }

    public function submitted(): static
    {
        return $this->state(fn() => ['status' => 'submitted', 'reviewer_id' => null]);
    }

    public function underReview(): static
    {
        return $this->state(fn() => [
            'status' => 'under_review',
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    public function revised(): static
    {
        return $this->state(fn() => ['status' => 'revised']);
    }

    public function accepted(): static
    {
        return $this->state(fn() => [
            'status' => 'accepted',
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'status' => 'rejected',
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    // ═══════════════ DATE ═══════════════

    /**
     * Set created_at ke tahun spesifik (dengan waktu random).
     */
    public function createdIn(int $year): static
    {
        $date = \Carbon\Carbon::create(
            year: $year,
            month: rand(1, 12),
            day: rand(1, 28),
            hour: rand(8, 17),
            minute: rand(0, 59),
            second: rand(0, 59),
        );

        return $this->state(fn() => [
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
