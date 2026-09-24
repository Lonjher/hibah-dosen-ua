<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $proposal_id
 * @property int $reviewer_id
 * @property string $summary
 * @property string $keyword
 * @property string $report_path
 * @property string $ppt_path
 * @property string $is_approved
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<Proposal> proposal()
 * @method BelongsTo<User> reviewer()
 */
#[Guarded(['id'])]
class ProgressReport extends Model
{
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
    public function isPending(): bool
    {
        return is_null($this->reviewer_id);
    }

    public function isApproved(): bool
    {
        return !is_null($this->reviewer_id) && $this->is_approved === true;
    }

    public function isRejected(): bool
    {
        return !is_null($this->reviewer_id) && $this->is_approved === false;
    }

    public function canBeEdited(): bool
    {
        return $this->isPending();
    }

    // ─── Status label untuk badge ───
    public function statusMeta(): array
    {
        if ($this->isPending()) {
            return ['label' => 'Pending Review', 'class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300'];
        }
        if ($this->isApproved()) {
            return ['label' => 'Approved', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'];
        }
        return ['label' => 'Rejected', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'];
    }
}
