<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $is_research
 * @property string $title
 * @property string|null $scheme
 * @property string $funding_source
 * @property string $role // leader, member
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string $status // ongoing, completed, cancelled
 * @property int $fund_amount
 * @property string|null $description
 * @property string|null $document_path
 * @property bool $is_verified
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<User> user()
 */
#[Guarded(['id'])]
class ExternalProposal extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_research' => 'boolean',
            'start_date'  => 'date',
            'end_date'    => 'date',
            'fund_amount' => 'integer',
            'is_verified' => 'boolean',
        ];
    }

    /* ============================================================
     |  RELATIONS
     ============================================================ */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ============================================================
     |  SCOPES — discriminator
     ============================================================ */

    public function scopeResearch($q)
    {
        return $q->where('is_research', true);
    }

    public function scopeDedication($q)
    {
        return $q->where('is_research', false);
    }

    public function scopeVerified($q)
    {
        return $q->where('is_verified', true);
    }

    public function scopeUnverified($q)
    {
        return $q->where('is_verified', false);
    }

    /* ============================================================
     |  TYPE HELPERS
     ============================================================ */

    public function isResearch(): bool
    {
        return (bool) $this->is_research;
    }

    public function isDedication(): bool
    {
        return ! $this->is_research;
    }

    /**
     * @return array{icon: string, color: string, label: string}
     */
    public function typeMeta(): array
    {
        return $this->is_research
            ? ['icon' => 'globe-alt', 'color' => 'indigo', 'label' => 'Research']
            : ['icon' => 'gift',      'color' => 'rose',   'label' => 'Community Service'];
    }

    /* ============================================================
     |  ROLE HELPERS
     ============================================================ */

    public function isLeader(): bool
    {
        return $this->role === 'leader';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function roleMeta(): array
    {
        return match ($this->role) {
            'leader' => [
                'label' => 'Leader',
                'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            ],
            default => [
                'label' => 'Member',
                'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
            ],
        };
    }

    /* ============================================================
     |  STATUS HELPERS
     ============================================================ */

    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function statusMeta(): array
    {
        return match ($this->status) {
            'ongoing'   => ['label' => 'Ongoing',   'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',           'dot' => 'bg-blue-500'],
            'completed' => ['label' => 'Completed', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'dot' => 'bg-emerald-500'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',             'dot' => 'bg-rose-500'],
            default     => ['label' => ucfirst((string) $this->status), 'class' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-500'],
        };
    }

    /* ============================================================
     |  VERIFICATION HELPERS
     ============================================================ */

    public function isVerified(): bool
    {
        return (bool) $this->is_verified;
    }

    public function isUnverified(): bool
    {
        return ! $this->is_verified;
    }

    public function verificationMeta(): array
    {
        return $this->is_verified
            ? ['label' => 'Verified',   'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300']
            : ['label' => 'Unverified', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'];
    }

    /* ============================================================
     |  FORMATTERS
     ============================================================ */

    public function getFundAmountFormattedAttribute(): string
    {
        return 'Rp ' . number_format((int) $this->fund_amount, 0, ',', '.');
    }

    public function getDurationAttribute(): string
    {
        if (! $this->start_date) {
            return '—';
        }

        $start = $this->start_date->format('M Y');

        if (! $this->end_date) {
            return $start . ' — present';
        }

        return $start . ' — ' . $this->end_date->format('M Y');
    }
}
