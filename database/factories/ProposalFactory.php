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
            'title'              => fake()->sentence(6),
            'summary'            => fake()->paragraph(6),
            'keywords'           => implode(', ', fake()->words(4)),
            'is_research'        => true,
            'file_path'          => 'proposals/dummy-' . fake()->uuid() . '.pdf',
            'rab_path'           => 'rab/dummy-' . fake()->uuid() . '.xlsx',
            'status'             => Proposal::STATUS_SUBMITTED,
            'period_id'          => Period::factory(),
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

    /**
     * User baru upload, belum diverifikasi admin.
     */
    public function submitted(): static
    {
        return $this->state(fn() => [
            'status'      => Proposal::STATUS_SUBMITTED,
            'reviewer_id' => null,
        ]);
    }

    /**
     * Admin minta revisi.
     */
    public function adminRevision(): static
    {
        return $this->state(fn() => [
            'status'      => Proposal::STATUS_ADMIN_REVISION,
            'reviewer_id' => null,
        ]);
    }

    /**
     * Admin sudah approve, siap di-assign reviewer.
     */
    public function adminApproved(): static
    {
        return $this->state(fn() => [
            'status'      => Proposal::STATUS_ADMIN_APPROVED,
            'reviewer_id' => null,
        ]);
    }

    /**
     * Sudah di-assign reviewer, sedang direview.
     */
    public function underReview(): static
    {
        return $this->state(fn() => [
            'status'      => Proposal::STATUS_UNDER_REVIEW,
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    /**
     * Reviewer minta revisi.
     */
    public function reviewerRevision(): static
    {
        return $this->state(fn() => [
            'status'      => Proposal::STATUS_REVIEWER_REVISION,
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    /**
     * Reviewer sudah approve semua notes, menunggu keputusan final admin.
     */
    public function reviewerApproved(): static
    {
        return $this->state(fn() => [
            'status'      => Proposal::STATUS_REVIEWER_APPROVED,
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    /**
     * Final — Diterima.
     */
    public function accepted(): static
    {
        return $this->state(fn() => [
            'status'      => Proposal::STATUS_ACCEPTED,
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    /**
     * Final — Ditolak.
     */
    public function rejected(): static
    {
        return $this->state(fn() => [
            'status'      => Proposal::STATUS_REJECTED,
            'reviewer_id' => User::factory()->reviewer(),
        ]);
    }

    // ═══════════════ DATE ═══════════════

    /**
     * Set created_at ke tahun spesifik (dengan waktu random).
     */
    public function createdIn(string $year): static
    {
        $year = (int) explode('/',$year)[0];
        $date = \Carbon\Carbon::create(
            year:   $year,
            month:  rand(1, 12),
            day:    rand(1, 28),
            hour:   rand(8, 17),
            minute: rand(0, 59),
            second: rand(0, 59),
        );

        return $this->state(fn() => [
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
