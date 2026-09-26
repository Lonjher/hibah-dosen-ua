<?php

namespace Database\Seeders;

use App\Models\ResearchScheme;
use Illuminate\Database\Seeder;

class ResearchSchemeSeeder extends Seeder
{
    public function run(): void
    {
        $schemes = [
            [
                'name'         => 'Penelitian Dasar',
                'code'         => 'PD-001',
                'description'  => 'Skema penelitian untuk dosen pemula dengan luaran minimal 1 artikel nasional.',
                'budget_limit' => '10000000',
                'is_active'    => true,
            ],
            [
                'name'         => 'Penelitian Terapan',
                'code'         => 'PT-001',
                'description'  => 'Skema penelitian terapan dengan luaran artikel internasional.',
                'budget_limit' => '20000000',
                'is_active'    => true,
            ],
            [
                'name'         => 'Pengabdian Masyarakat',
                'code'         => 'PM-001',
                'description'  => 'Skema pengabdian kepada masyarakat berbasis potensi lokal.',
                'budget_limit' => '15000000',
                'is_active'    => true,
            ],
            [
                'name'         => 'Penelitian Kolaboratif',
                'code'         => 'PK-001',
                'description'  => 'Skema penelitian kolaborasi antar institusi.',
                'budget_limit' => '25000000',
                'is_active'    => true,
            ],
        ];

        foreach ($schemes as $scheme) {
            ResearchScheme::updateOrCreate(['code' => $scheme['code']], $scheme);
        }

        $this->command->info('✅ Research schemes seeded: '.count($schemes));
    }
}
