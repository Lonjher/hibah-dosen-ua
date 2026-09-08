<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $proposal_id
 * @property string $item_name
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method BelongsTo<Proposal> proposal()
 */

#[Guarded(['id'])]
class BudgetProposal extends Model
{
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
