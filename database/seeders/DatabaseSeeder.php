<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // ── Base ──
            RoleSeeder::class,
            UserSeeder::class,
            PeriodSeeder::class,
            ResearchSchemeSeeder::class,

            // ── Transactional + Notes ──
            ProposalSeeder::class,
        ]);
    }
}
