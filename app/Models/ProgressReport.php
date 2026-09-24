<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $proposal_id
 * @property int|null $reviewer_id
 * @property string $summary
 * @property string $keyword
 * @property string $report_path
 * @property string $ppt_path
 * @property bool $is_approved
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<Proposal> proposal()
 * @method BelongsTo<User> reviewer()
 * @method HasMany<ProgressReportNote> notes()
 */
#[Guarded(['id'])]
class ProgressReport extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    // ═══════════════ Relations ═══════════════
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProgressReportNote::class)->with('reviewer')->latest();
    }

    public function latestNote(): ?ProgressReportNote
    {
        return $this->notes()->first();
    }

    // ═══════════════ State Helpers ═══════════════

    /** Belum di-assign reviewer sama sekali. */
    public function isPending(): bool
    {
        return is_null($this->reviewer_id);
    }

    /** Sudah di-assign reviewer tapi belum direview. */
    public function isUnderReview(): bool
    {
        return !is_null($this->reviewer_id) && is_null($this->reviewed_at);
    }

    /** Sudah direview dan di-approve. */
    public function isApproved(): bool
    {
        return !is_null($this->reviewed_at) && $this->is_approved === true;
    }

    /** Sudah direview tapi ditolak / minta revisi. */
    public function isRejected(): bool
    {
        return !is_null($this->reviewed_at) && $this->is_approved === false;
    }

    /** Author boleh edit hanya kalau masih pending atau butuh revisi. */
    public function canBeEdited(): bool
    {
        return $this->isPending() || $this->isRejected();
    }

    /** Author boleh delete hanya saat pending (belum di-assign). */
    public function canBeDeleted(): bool
    {
        return $this->isPending();
    }

    // ═══════════════ Status Badge ═══════════════
    public function statusMeta(): array
    {
        if ($this->isPending()) {
            return [
                'label' => 'Pending',
                'class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300',
                'icon'  => 'clock',
            ];
        }
        if ($this->isUnderReview()) {
            return [
                'label' => 'Under Review',
                'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                'icon'  => 'eye',
            ];
        }
        if ($this->isApproved()) {
            return [
                'label' => 'Approved',
                'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                'icon'  => 'check-circle',
            ];
        }
        return [
            'label' => 'Needs Revision',
            'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            'icon'  => 'arrow-path',
        ];
    }
}
