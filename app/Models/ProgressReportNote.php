<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $progress_report_id
 * @property int $reviewer_id
 * @property string $decision
 * @property string $comment
 * @property string|null $recommendation
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<ProgressReport> progressReport()
 * @method BelongsTo<User> reviewer()
 */
#[Guarded(['id'])]
class ProgressReportNote extends Model
{
    public function progressReport(): BelongsTo
    {
        return $this->belongsTo(ProgressReport::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isApproval(): bool
    {
        return $this->decision === 'approve';
    }

    public function isRevisionRequest(): bool
    {
        return $this->decision === 'revise';
    }

    public function decisionMeta(): array
    {
        return match ($this->decision) {
            'approve' => [
                'label' => 'Approved',
                'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                'icon'  => 'check-circle',
            ],
            'revise' => [
                'label' => 'Revision Requested',
                'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                'icon'  => 'arrow-path',
            ],
            default => [
                'label' => ucfirst($this->decision),
                'class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300',
                'icon'  => 'question-mark-circle',
            ],
        };
    }
}
