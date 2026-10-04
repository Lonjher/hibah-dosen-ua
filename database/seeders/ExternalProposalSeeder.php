<?php

namespace Database\Seeders;

use App\Models\ExternalProposal;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExternalProposalSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil user biasa (bukan admin/superadmin/reviewer)
        $users = User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('role_code', ['USER', 'ADMIN']))
            ->get();

        // Fallback: kalau tidak ada user biasa, pakai semua user
        if ($users->isEmpty()) {
            $users = User::all();
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $this->command->info("Seeding external proposals for {$users->count()} user(s)...");

        foreach ($users as $user) {
            // Setiap user punya 3-8 external research
            $researchCount = rand(3, 8);
            ExternalProposal::factory()
                ->count($researchCount)
                ->research()
                ->for($user)
                ->create();

            // Setiap user punya 2-6 external dedication
            $dedicationCount = rand(2, 6);
            ExternalProposal::factory()
                ->count($dedicationCount)
                ->dedication()
                ->for($user)
                ->create();
        }

        // Statistik
        $total     = ExternalProposal::count();
        $research  = ExternalProposal::research()->count();
        $dedication = ExternalProposal::dedication()->count();
        $verified  = ExternalProposal::verified()->count();

        $this->command->info("✔ Created {$total} external proposals:");
        $this->command->info("  • Research   : {$research}");
        $this->command->info("  • Dedication : {$dedication}");
        $this->command->info("  • Verified   : {$verified}");
        $this->command->info("  • Unverified : " . ($total - $verified));
    }
}
