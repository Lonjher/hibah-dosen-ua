<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $proposal_id
 * @property string $item_name
 * @property int $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<Proposal> proposal()
 */
#[Guarded(['id'])]
class BudgetProposal extends Model
{
    use HasFactory, HasActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
