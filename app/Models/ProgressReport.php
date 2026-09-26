<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $proposal_id
 * @property int|null $reviewer_id
 * @property string $summary
 * @property string $keyword
 * @property string $report_path
 * @property string $ppt_path
 * @property string $status // pending, revised, submitted, rejected, under_review, accepted
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<Proposal> proposal()
 * @method BelongsTo<User> reviewer()
 * @method MorphMany<ReviewerNote> reviewerNotes()
 * @method MorphMany<AdminNote> adminNotes()
 */
#[Guarded(['id'])]
class ProgressReport extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewerNotes(): MorphMany
    {
        return $this->morphMany(ReviewerNote::class, 'noteable');
    }

    public function adminNotes(): MorphMany
    {
        return $this->morphMany(AdminNote::class, 'noteable');
    }

    // === Status Helpers ===
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRevised(): bool
    {
        return $this->status === 'revised';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * @return array{label: string, class: string}
     */
    public function statusMeta(): array
    {
        return match ($this->status) {
            'pending'      => ['label' => 'Pending',      'class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300'],
            'submitted'    => ['label' => 'Submitted',    'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
            'under_review' => ['label' => 'Under Review', 'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300'],
            'revised'      => ['label' => 'Revised',      'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
            'accepted'     => ['label' => 'Accepted',     'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
            'rejected'     => ['label' => 'Rejected',     'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'],
            default        => ['label' => ucfirst((string) $this->status), 'class' => 'bg-slate-100 text-slate-600'],
        };
    }
}
