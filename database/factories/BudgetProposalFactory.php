<?php

namespace Database\Factories;

use App\Models\BudgetProposal;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetProposal>
 */
class BudgetProposalFactory extends Factory
{
    protected $model = BudgetProposal::class;

    public function definition(): array
    {
        $items = [
            'Honorarium Ketua', 'Honorarium Anggota', 'Bahan Habis Pakai',
            'Perjalanan Dinas', 'Sewa Alat', 'Publikasi',
            'Konsumsi Rapat', 'ATK', 'Lain-lain',
        ];

        return [
            'proposal_id' => Proposal::factory(),
            'item_name'   => fake()->randomElement($items),
            'amount'      => fake()->numberBetween(500_000, 5_000_000),
        ];
    }
}
