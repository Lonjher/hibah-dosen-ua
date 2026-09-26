<?php

namespace Database\Seeders;

use App\Models\AdminNote;
use App\Models\BudgetProposal;
use App\Models\FinalReport;
use App\Models\Output;
use App\Models\Period;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use App\Models\ReviewerNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class ProposalSeeder extends Seeder
{
    public function run(): void
    {
        $period   = Period::query()->where('is_active', true)->first();
        $schemes  = ResearchScheme::query()->where('is_active', true)->get();
        $users    = User::query()->whereHas('role', fn ($q) => $q->where('role_code', 'USER'))->get();
        $admins   = User::query()->whereHas('role', fn ($q) => $q->whereIn('role_code', ['ADMIN', 'SUPERADMIN']))->get();
        $reviewers = User::query()->whereHas('role', fn ($q) => $q->where('role_code', 'REVIEWER'))->get();

        // Validasi: minimal ada 1 user, 1 admin, 1 reviewer
        if ($users->isEmpty() || $admins->isEmpty() || $reviewers->isEmpty() || ! $period || $schemes->isEmpty()) {
            $this->command->warn('⚠️  Data dasar belum lengkap. Jalankan RoleSeeder, UserSeeder, PeriodSeeder, ResearchSchemeSeeder dulu.');
            return;
        }

        // === Skenario 1: Proposal Pending ===
        $this->createProposals($users, $schemes, $period, 5, 'pending', null);

        // === Skenario 2: Proposal Submitted ===
        $this->createProposals($users, $schemes, $period, 3, 'submitted', null);

        // === Skenario 3: Proposal Under Review ===
        $this->createProposals($users, $schemes, $period, 3, 'under_review', $reviewers->random());

        // === Skenario 4: Proposal Rejected ===
        $this->createProposals($users, $schemes, $period, 2, 'rejected', $reviewers->random());

        // === Skenario 5: Proposal Accepted → lanjut ProgressReport, dst ===
        $acceptedProposals = $this->createProposals($users, $schemes, $period, 4, 'accepted', $reviewers->random());

        foreach ($acceptedProposals as $proposal) {
            $this->seedDownstream($proposal, $reviewers, $admins);
        }

        $this->command->info('✅ Proposals & relasinya seeded.');
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, ResearchScheme>  $schemes
     * @return Collection<int, Proposal>
     */
    private function createProposals(
        Collection $users,
        Collection $schemes,
        Period $period,
        int $count,
        string $status,
        ?User $reviewer,
    ): Collection {
        $proposals = Proposal::factory()
            ->count($count)
            ->state([
                'research_scheme_id' => $schemes->random()->id,
                'user_id'            => $users->random()->id,
                'reviewer_id'        => $reviewer?->id,
                'status'             => $status,
                'period_id'          => $period->id,
            ])
            ->create();

        foreach ($proposals as $proposal) {
            BudgetProposal::factory()
                ->for($proposal)
                ->count(fake()->numberBetween(3, 6))
                ->create();
        }

        return $proposals;
    }

    /**
     * Seed downstream: notes, progress report, final report, output.
     *
     * @param  Collection<int, User>  $reviewers
     * @param  Collection<int, User>  $admins
     */
    private function seedDownstream(
        Proposal $proposal,
        Collection $reviewers,
        Collection $admins,
    ): void {
        // Reviewer notes untuk proposal accepted
        ReviewerNote::factory()
            ->forProposal($proposal)
            ->approved()
            ->count(2)
            ->create(['reviewer_id' => $proposal->reviewer_id ?? $reviewers->random()->id]);

        // Admin notes untuk proposal
        AdminNote::factory()
            ->forProposal($proposal)
            ->count(1)
            ->create(['admin_id' => $admins->random()->id]);

        // 70% proposal accepted lanjut ProgressReport
        if (! fake()->boolean(70)) {
            return;
        }

        $progressReport = ProgressReport::factory()
            ->for($proposal)
            ->submitted()
            ->create();

        AdminNote::factory()
            ->forProgressReport($progressReport)
            ->count(1)
            ->create(['admin_id' => $admins->random()->id]);

        // 50% ProgressReport di-assign reviewer
        if (! fake()->boolean(50)) {
            return;
        }

        $progressReport->update([
            'reviewer_id' => $reviewers->random()->id,
            'status'      => 'under_review',
        ]);

        ReviewerNote::factory()
            ->forProgressReport($progressReport)
            ->count(1)
            ->create(['reviewer_id' => $progressReport->reviewer_id]);

        // 40% ProgressReport accepted → lanjut FinalReport
        if (! fake()->boolean(40)) {
            return;
        }

        $progressReport->update([
            'status'      => 'accepted',
            'reviewed_at' => now(),
        ]);

        ReviewerNote::factory()
            ->forProgressReport($progressReport)
            ->approved()
            ->create(['reviewer_id' => $progressReport->reviewer_id]);

        $finalReport = FinalReport::factory()
            ->for($proposal)
            ->create(['status' => fake()->randomElement(['pending', 'accepted'])]);

        AdminNote::factory()
            ->forFinalReport($finalReport)
            ->count(1)
            ->create(['admin_id' => $admins->random()->id]);

        // FinalReport accepted → lanjut Output
        if ($finalReport->status !== 'accepted') {
            return;
        }

        $output = Output::factory()
            ->for($proposal)
            ->create(['status' => fake()->randomElement(['pending', 'accepted'])]);

        AdminNote::factory()
            ->forOutput($output)
            ->count(1)
            ->create(['admin_id' => $admins->random()->id]);
    }
}
