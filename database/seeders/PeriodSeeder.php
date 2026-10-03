<?php

namespace Database\Seeders;

use App\Models\Period;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        $startYear = 2023;
        $endYear   = (int) now()->year;   // 2026

        // 4 tahun × 2 semester = 8 period (2023 s.d. 2026)
        for ($year = $startYear; $year <= $endYear; $year++) {
            Period::factory()->forYear($year, true)->create();   // Ganjil YYYY/YYYY+1
            Period::factory()->forYear($year, false)->create();  // Genap  YYYY/YYYY+1
        }

        // Set periode ganjil tahun berjalan sebagai active
        $activePeriod = 'Ganjil ' . $endYear . '/' . ($endYear + 1);
        Period::where('periode', $activePeriod)->update(['is_active' => true]);

        $this->command->info('✅ Periods seeded: ' . Period::count() . " (aktif: {$activePeriod})");
    }
}
