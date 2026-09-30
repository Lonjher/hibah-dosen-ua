<?php

namespace Database\Seeders;

use App\Models\Period;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        // 5 tahun x 2 semester = 10 period
        for ($year = 2021; $year <= 2025; $year++) {
            Period::factory()->forYear($year, true)->create();   // Ganjil
            Period::factory()->forYear($year, false)->create();  // Genap
        }

        // Set Ganjil 2025/2026 sebagai active
        Period::where('periode', 'Ganjil 2025/2026')
            ->update(['is_active' => true]);

        $this->command->info('✅ Periods seeded: ' . Period::count());
    }
}
