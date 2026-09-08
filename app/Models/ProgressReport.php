<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $proposal_id
 * @property string $summary
 * @property string $keyword
 * @property string $report_path
 * @property string $ppt_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method BelongsTo<Proposal> proposal()
 */

#[Guarded(['id'])]
class ProgressReport extends Model
{
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
