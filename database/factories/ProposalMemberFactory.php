<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalMember>
 */
class ProposalMemberFactory extends Factory
{
    protected $model = ProposalMember::class;

    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'user_id'     => User::factory()->user(),
            'role'        => 'member',
        ];
    }

    public function leader(): static
    {
        return $this->state(fn () => ['role' => 'leader']);
    }

    public function member(): static
    {
        return $this->state(fn () => ['role' => 'member']);
    }
}
