<?php

namespace Database\Seeders;

use App\Models\ResearchScheme;
use Illuminate\Database\Seeder;

class ResearchSchemeSeeder extends Seeder
{
    public function run(): void
    {
        // Skema penelitian (4)
        ResearchScheme::factory()->count(4)->research()->create();

        // Skema pengabdian (2)
        ResearchScheme::factory()->count(2)->dedication()->create();

        $this->command->info('✅ Research schemes seeded: ' . ResearchScheme::count());
    }
}
