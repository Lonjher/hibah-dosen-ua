<?php

namespace Database\Seeders;

use App\Models\Period;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        Period::updateOrCreate(
            ['periode' => 'Ganjil 2025/2026'],
            [
                'is_active' => true,
                'open_from' => now()->subMonth(),
                'open_to'   => now()->addMonths(2),
            ],
        );

        Period::updateOrCreate(
            ['periode' => 'Genap 2024/2025'],
            [
                'is_active' => false,
                'open_from' => now()->subYear()->subMonths(6),
                'open_to'   => now()->subYear()->subMonths(3),
            ],
        );

        $this->command->info('✅ Periods seeded: 2');
    }
}
