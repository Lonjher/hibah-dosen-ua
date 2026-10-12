<?php

namespace Database\Seeders;

use App\Models\AdminNote;
use App\Models\BudgetProposal;
use App\Models\FinalReport;
use App\Models\Outcome;
use App\Models\Period;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\ProposalStudent;
use App\Models\ResearchScheme;
use App\Models\ReviewerNote;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProposalSeeder extends Seeder
{
    public function run(): void
    {
        $users      = User::whereHas('role', fn ($q) => $q->where('role_code', 'USER'))->get();
        $reviewers  = User::whereHas('role', fn ($q) => $q->where('role_code', 'REVIEWER'))->get();
        $admins     = User::whereHas('role', fn ($q) => $q->whereIn('role_code', ['ADMIN', 'SUPERADMIN']))->get();
        $schemes    = ResearchScheme::all();
        $periods    = Period::all();

        if ($users->isEmpty() || $reviewers->isEmpty() || $schemes->isEmpty() || $periods->isEmpty()) {
            $this->command->warn('⚠️  Base data belum lengkap. Jalankan RoleSeeder, UserSeeder, PeriodSeeder, ResearchSchemeSeeder dulu.');
            return;
        }

        $schemesResearch   = $schemes->filter(fn ($s) => str_contains($s->code, 'PD') || str_contains($s->code, 'PT') || str_contains($s->code, 'PKPT'));
        $schemesDedication = $schemes->filter(fn ($s) => str_contains($s->code, 'PKM'));

        if ($schemesResearch->isEmpty()) {
            $schemesResearch = $schemes;
        }
        if ($schemesDedication->isEmpty()) {
            $schemesDedication = $schemes;
        }

        // Distribusi per tahun (research, dedication)
        $distribution = [
            2023 => ['research' => 8,  'dedication' => 5],
            2024 => ['research' => 10, 'dedication' => 6],
            2025 => ['research' => 12, 'dedication' => 8],
            2026 => ['research' => 14, 'dedication' => 9],
        ];

        $totalProposals = 0;

        foreach ($distribution as $year => $counts) {
            // ── Penelitian ──
            for ($i = 0; $i < $counts['research']; $i++) {
                $this->createFullProposal(
                    isResearch: true,
                    year:       $year,
                    scheme:     $schemesResearch->random(),
                    user:       $users->random(),
                    reviewer:   $reviewers->random(),
                    admin:      $admins->random(),
                    period:     $periods->first(fn ($p) => str_contains($p->periode, (string) $year)) ?? $periods->first(),
                );
                $totalProposals++;
            }

            // ── Pengabdian ──
            for ($i = 0; $i < $counts['dedication']; $i++) {
                $this->createFullProposal(
                    isResearch: false,
                    year:       $year,
                    scheme:     $schemesDedication->random(),
                    user:       $users->random(),
                    reviewer:   $reviewers->random(),
                    admin:      $admins->random(),
                    period:     $periods->first(fn ($p) => str_contains($p->periode, (string) $year)) ?? $periods->first(),
                );
                $totalProposals++;
            }
        }

        $this->command->info("✅ Proposals seeded: {$totalProposals}");
        $this->command->info('   - Progress Reports: ' . ProgressReport::count());
        $this->command->info('   - Final Reports: '    . FinalReport::count());
        $this->command->info('   - Outcomes: '          . Outcome::count());
        $this->command->info('   - Reviewer Notes: '   . ReviewerNote::count());
        $this->command->info('   - Admin Notes: '      . AdminNote::count());
        $this->command->info('   - Members: '          . ProposalMember::count());
        $this->command->info('   - Students: '         . ProposalStudent::count());
    }

    /**
     * Buat proposal lengkap dengan semua relasinya (sesuai alur status baru).
     */
    protected function createFullProposal(
        bool $isResearch,
        int $year,
        ResearchScheme $scheme,
        User $user,
        User $reviewer,
        User $admin,
        Period $period,
    ): void {
        // ── 1. Tentukan status akhir ──
        $status = $this->randomStatus();

        // Reviewer di-set hanya kalau status >= UNDER_REVIEW
        $hasReviewer = in_array($status, [
            Proposal::STATUS_UNDER_REVIEW,
            Proposal::STATUS_REVIEWER_REVISION,
            Proposal::STATUS_REVIEWER_APPROVED,
            Proposal::STATUS_ACCEPTED,
            Proposal::STATUS_REJECTED,
        ], true);

        // ── 2. Buat proposal ──
        $proposal = Proposal::factory()
            ->createdIn($year)
            ->state([
                'research_scheme_id' => $scheme->id,
                'user_id'            => $user->id,
                'reviewer_id'        => $hasReviewer ? $reviewer->id : null,
                'is_research'        => $isResearch,
                'status'             => $status,
                'period_id'          => $period->id,
            ])
            ->create();

        // ── 3. Buat anggota (dosen + mahasiswa) ──
        $otherUsers = User::where('id', '!=', $user->id)
            ->whereHas('role', fn ($q) => $q->where('role_code', 'USER'))
            ->inRandomOrder()
            ->limit(rand(1, 3))
            ->get();

        foreach ($otherUsers as $memberUser) {
            ProposalMember::factory()->create([
                'proposal_id' => $proposal->id,
                'user_id'     => $memberUser->id,
                'role'        => 'member',
            ]);
        }

        ProposalStudent::factory()
            ->count(rand(0, 3))
            ->create(['proposal_id' => $proposal->id]);

        // ── 4. Buat budget items ──
        $budgetLimit = (int) $scheme->budget_limit;
        $remaining   = $budgetLimit;
        $itemsCount  = rand(3, 5);

        for ($i = 0; $i < $itemsCount; $i++) {
            if ($remaining <= 0) break;

            $maxAmount = (int) ($remaining / max(1, $itemsCount - $i));
            $amount    = rand(500_000, max(500_001, $maxAmount));
            $amount    = min($amount, $remaining);

            BudgetProposal::factory()->create([
                'proposal_id' => $proposal->id,
                'amount'      => $amount,
            ]);

            $remaining -= $amount;
        }

        // ── 5. Notes sesuai status ──

        // Admin Notes: muncul kalau sudah lewat SUBMITTED (artinya admin sudah pernah review)
        if (in_array($status, [
            Proposal::STATUS_ADMIN_REVISION,
            Proposal::STATUS_ADMIN_APPROVED,
            Proposal::STATUS_UNDER_REVIEW,
            Proposal::STATUS_REVIEWER_REVISION,
            Proposal::STATUS_REVIEWER_APPROVED,
            Proposal::STATUS_ACCEPTED,
            Proposal::STATUS_REJECTED,
        ], true) && fake()->boolean(70)) {
            AdminNote::factory()
                ->forProposal($proposal, $admin)
                ->count(rand(1, 2))
                ->create();
        }

        // Reviewer Notes: muncul kalau status sudah sampai reviewer
        if ($hasReviewer && fake()->boolean(70)) {
            $isApproved = in_array($status, [
                Proposal::STATUS_REVIEWER_APPROVED,
                Proposal::STATUS_ACCEPTED,
            ], true);

            ReviewerNote::factory()
                ->forProposal($proposal, $reviewer)
                ->count(rand(1, 3))
                ->state(fn () => ['is_approved' => $isApproved])
                ->create();
        }

        // ── 6. Kalau ACCEPTED → buat ProgressReport ──
        if ($status !== Proposal::STATUS_ACCEPTED) return;
        if (! fake()->boolean(80)) return;

        $this->createProgressReport($proposal, $reviewer, $admin);
    }

    /**
     * Buat progress report + relasi downstream.
     */
    protected function createProgressReport(
        Proposal $proposal,
        User $reviewer,
        User $admin,
    ): void {
        $status      = $this->randomProgressStatus();
        $hasReviewer = in_array($status, ['under_review', 'accepted', 'rejected']);

        $progressReport = ProgressReport::factory()
            ->state([
                'proposal_id' => $proposal->id,
                'reviewer_id' => $hasReviewer ? $reviewer->id : null,
                'status'      => $status,
                'reviewed_at' => in_array($status, ['accepted', 'rejected']) ? now() : null,
            ])
            ->create();

        // Set created_at setelah proposal
        $createdAt = $proposal->created_at->copy()->addMonths(rand(2, 6));
        $progressReport->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Notes untuk progress report
        if ($hasReviewer) {
            ReviewerNote::factory()
                ->forProgressReport($progressReport, $reviewer)
                ->count(rand(1, 2))
                ->state(fn () => ['is_approved' => $status === 'accepted' ? true : fake()->boolean(60)])
                ->create();
        }

        if (fake()->boolean(50)) {
            AdminNote::factory()
                ->forProgressReport($progressReport, $admin)
                ->create();
        }

        // ── Kalau accepted → buat FinalReport ──
        if ($status !== 'accepted') return;
        if (! fake()->boolean(80)) return;

        $this->createFinalReport($proposal, $progressReport, $admin);
    }

    /**
     * Buat final report + Outcome.
     */
    protected function createFinalReport(
        Proposal $proposal,
        ProgressReport $progressReport,
        User $admin,
    ): void {
        $status = $this->randomFinalStatus();

        $finalReport = FinalReport::factory()
            ->state([
                'proposal_id' => $proposal->id,
                'status'      => $status,
            ])
            ->create();

        $createdAt = $progressReport->created_at->copy()->addMonths(rand(2, 5));
        $finalReport->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        if (fake()->boolean(50)) {
            AdminNote::factory()
                ->forFinalReport($finalReport, $admin)
                ->create();
        }

        // ── Kalau accepted → buat Outcome ──
        if ($status !== 'accepted') return;
        if (! fake()->boolean(75)) return;

        $outcome = Outcome::factory()
            ->state([
                'proposal_id' => $proposal->id,
                'status'      => $this->randomOutcomeStatus(),
            ])
            ->create();

        $createdAt = $finalReport->created_at->copy()->addMonths(rand(2, 6));
        $outcome->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        if (fake()->boolean(50)) {
            AdminNote::factory()
                ->forOutcome($outcome, $admin)
                ->create();
        }
    }

    /* ============================================================
     |  WEIGHTED RANDOM HELPERS
     ============================================================ */

    /**
     * Distribusi status proposal.
     *
     * - SUBMITTED        : 20% (status awal)
     * - ADMIN_REVISION   : 10% (dalam proses revisi admin)
     * - ADMIN_APPROVED   : 10% (siap assign reviewer)
     * - UNDER_REVIEW     : 10% (sedang direview)
     * - REVIEWER_REVISION: 5%  (revisi dari reviewer)
     * - REVIEWER_APPROVED: 10% (menunggu keputusan final admin)
     * - ACCEPTED         : 25% (final)
     * - REJECTED         : 10% (final)
     */
    protected function randomStatus(): string
    {
        $pool = [
            Proposal::STATUS_SUBMITTED         => 20,
            Proposal::STATUS_ADMIN_REVISION    => 10,
            Proposal::STATUS_ADMIN_APPROVED    => 10,
            Proposal::STATUS_UNDER_REVIEW      => 10,
            Proposal::STATUS_REVIEWER_REVISION => 5,
            Proposal::STATUS_REVIEWER_APPROVED => 10,
            Proposal::STATUS_ACCEPTED          => 25,
            Proposal::STATUS_REJECTED          => 10,
        ];
        return $this->weightedPick($pool);
    }

    protected function randomProgressStatus(): string
    {
        $pool = [
            'pending'      => 5,
            'submitted'    => 8,
            'under_review' => 5,
            'revised'      => 5,
            'accepted'     => 15,
            'rejected'     => 2,
        ];
        return $this->weightedPick($pool);
    }

    protected function randomFinalStatus(): string
    {
        $pool = [
            'pending'  => 5,
            'revised'  => 3,
            'accepted' => 15,
            'rejected' => 2,
        ];
        return $this->weightedPick($pool);
    }

    protected function randomOutcomeStatus(): string
    {
        $pool = [
            'pending'  => 3,
            'revised'  => 2,
            'accepted' => 15,
            'rejected' => 1,
        ];
        return $this->weightedPick($pool);
    }

    protected function weightedPick(array $pool): string
    {
        $expanded = [];
        foreach ($pool as $key => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $expanded[] = $key;
            }
        }
        return $expanded[array_rand($expanded)];
    }
}
