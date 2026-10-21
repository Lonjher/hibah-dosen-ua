<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $research_scheme_id
 * @property int $user_id
 * @property int|null $reviewer_id
 * @property string $title
 * @property string $summary
 * @property string $keywords
 * @property bool $is_research
 * @property string $file_path
 * @property string $rab_path
 * @property string $status // submitted, admin_revision, admin_approved, under_review, reviewer_revision, reviewer_approved, accepted, rejected
 * @property int $period_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<ResearchScheme> researchScheme()
 * @method BelongsTo<User> author()
 * @method BelongsTo<User> reviewer()
 * @method BelongsTo<Period> period()
 * @method HasMany<BudgetProposal> budgetProposals()
 * @method HasMany<ProposalMember> proposalMembers()
 * @method HasMany<ProposalStudent> proposalStudents()
 * @method HasOne<ProgressReport> progressReport()
 * @method HasOne<FinalReport> finalReport()
 * @method HasOne<Outcome> outcome()
 * @method MorphMany<ReviewerNote> reviewerNotes()
 * @method MorphMany<AdminNote> adminNotes()
 */
#[Guarded(['id'])]
class Proposal extends Model
{
    use HasFactory, HasActivity;
    public const STATUS_SUBMITTED = 'submitted';          // SBM
    public const STATUS_ADMIN_REVISION = 'admin_revision';     // ADR
    public const STATUS_ADMIN_APPROVED = 'admin_approved';     // ADA
    public const STATUS_UNDER_REVIEW = 'under_review';       // REV
    public const STATUS_REVIEWER_REVISION = 'reviewer_revision';  // RVR
    public const STATUS_REVIEWER_APPROVED = 'reviewer_approved';  // RVA
    public const STATUS_ACCEPTED = 'accepted';           // ACC
    public const STATUS_REJECTED = 'rejected';           // REJ

    protected function casts(): array
    {
        return [
            'is_research' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function researchScheme(): BelongsTo
    {
        return $this->belongsTo(ResearchScheme::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function budgetProposals(): HasMany
    {
        return $this->hasMany(BudgetProposal::class);
    }

    // === Anggota ===
    public function proposalMembers(): HasMany
    {
        return $this->hasMany(ProposalMember::class);
    }

    public function proposalStudents(): HasMany
    {
        return $this->hasMany(ProposalStudent::class);
    }

    // === Satu Anak per Tahap ===
    public function progressReport(): HasOne
    {
        return $this->hasOne(ProgressReport::class);
    }

    public function finalReport(): HasOne
    {
        return $this->hasOne(FinalReport::class);
    }

    public function outcome(): HasOne
    {
        return $this->hasOne(Outcome::class);
    }

    // === Polymorphic Notes ===
    public function reviewerNotes(): MorphMany
    {
        return $this->morphMany(ReviewerNote::class, 'noteable');
    }

    public function adminNotes(): MorphMany
    {
        return $this->morphMany(AdminNote::class, 'noteable');
    }

    /* ============================================================
     |  MEMBER HELPERS
     ============================================================ */

    /**
     * Ambil semua anggota (user internal + mahasiswa) dalam satu collection.
     * Setiap item dinormalisasi ke array dengan struktur seragam.
     *
     * @return Collection<int, array{
     *     id: int,
     *     type: 'user'|'student',
     *     name: string,
     *     identifier: string|null,
     *     role: string,
     *     source: Model
     * }>
     */
    public function allMembers(): Collection
    {
        $users = $this->proposalMembers()
            ->with('user')
            ->get()
            ->map(fn(ProposalMember $m) => [
                'id' => $m->id,
                'type' => 'user',
                'name' => $m->user?->full_name ?? '—',
                'identifier' => $m->user?->nidn,
                'role' => $m->role,
                'source' => $m,
            ]);

        $students = $this->proposalStudents()
            ->get()
            ->map(fn(ProposalStudent $s) => [
                'id' => $s->id,
                'type' => 'student',
                'name' => $s->name,
                'identifier' => $s->nim,
                'role' => $s->role,
                'source' => $s,
            ]);

        return $users->concat($students)->values();
    }

    public function totalMembers(): int
    {
        return $this->proposalMembers()->count()
            + $this->proposalStudents()->count();
    }

    /* ============================================================
     |  STATUS HELPERS
     ============================================================ */
    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isAdminRevision(): bool
    {
        return $this->status === self::STATUS_ADMIN_REVISION;
    }

    public function isAdminApproved(): bool
    {
        return $this->status === self::STATUS_ADMIN_APPROVED;
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function isReviewerRevision(): bool
    {
        return $this->status === self::STATUS_REVIEWER_REVISION;
    }

    public function isReviewerApproved(): bool
    {
        return $this->status === self::STATUS_REVIEWER_APPROVED;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Status final: tidak bisa diubah lagi.
     */
    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
        ], true);
    }

    /**
     * Status yang masih bisa diedit oleh user (pemilik / ketua).
     * Hanya saat submitted atau admin_revision.
     */
    public function isEditableByUser(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_ADMIN_REVISION,
        ], true);
    }

    /**
     * Status yang masih bisa diedit/dihapus secara umum.
     */
    public function isMutable(): bool
    {
        return !$this->isFinal();
    }

    /**
     * Cek apakah anggota masih bisa diedit.
     */
    public function canEditMembers(): bool
    {
        return $this->isEditableByUser();
    }

    /**
     * Apakah statusnya sedang di tahap review (bukan final, bukan submitted).
     */
    public function isInReviewProcess(): bool
    {
        return in_array($this->status, [
            self::STATUS_UNDER_REVIEW,
            self::STATUS_REVIEWER_REVISION,
            self::STATUS_REVIEWER_APPROVED,
        ], true);
    }

    /**
     * Semua status yang valid.
     *
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_SUBMITTED,
            self::STATUS_ADMIN_REVISION,
            self::STATUS_ADMIN_APPROVED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_REVIEWER_REVISION,
            self::STATUS_REVIEWER_APPROVED,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
        ];
    }

    /**
     * @return array{label:string, full:string, class:string, code:string}
     */
    public function statusMeta(): array
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED => [
                'label' => 'SBM',
                'full' => 'Submitted',
                'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                'code' => 'SBM',
            ],
            self::STATUS_ADMIN_REVISION => [
                'label' => 'ADR',
                'full' => 'Admin Revision',
                'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                'code' => 'ADR',
            ],
            self::STATUS_ADMIN_APPROVED => [
                'label' => 'ADA',
                'full' => 'Admin Approved',
                'class' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                'code' => 'ADA',
            ],
            self::STATUS_UNDER_REVIEW => [
                'label' => 'REV',
                'full' => 'Under Review',
                'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                'code' => 'REV',
            ],
            self::STATUS_REVIEWER_REVISION => [
                'label' => 'RVR',
                'full' => 'Reviewer Revision',
                'class' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                'code' => 'RVR',
            ],
            self::STATUS_REVIEWER_APPROVED => [
                'label' => 'RVA',
                'full' => 'Reviewer Approved',
                'class' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
                'code' => 'RVA',
            ],
            self::STATUS_ACCEPTED => [
                'label' => 'ACC',
                'full' => 'Accepted',
                'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                'code' => 'ACC',
            ],
            self::STATUS_REJECTED => [
                'label' => 'REJ',
                'full' => 'Rejected',
                'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                'code' => 'REJ',
            ],
            default => [
                'label' => '—',
                'full' => ucfirst(str_replace('_', ' ', (string) $this->status)),
                'class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300',
                'code' => '—',
            ],
        };
    }

    /**
     * Cek apakah proposal bisa di-edit & re-upload oleh user.
     *
     * - SUBMITTED → user edit
     * - ADMIN_REVISION → user edit → balik ke SUBMITTED
     * - REVIEWER_REVISION → user edit → balik ke UNDER_REVIEW
     */
    public function canUserResubmit(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_ADMIN_REVISION,
            self::STATUS_REVIEWER_REVISION,
        ], true);
    }

    /**
     * Status berikutnya saat user re-submit.
     * Menentukan apakah balik ke SUBMITTED atau UNDER_REVIEW.
     */
    public function nextStatusOnResubmit(): string
    {
        return match ($this->status) {
            self::STATUS_ADMIN_REVISION => self::STATUS_SUBMITTED,
            self::STATUS_REVIEWER_REVISION => self::STATUS_UNDER_REVIEW,
            default => self::STATUS_SUBMITTED,
        };
    }
    /**
     * Cek apakah user masih memiliki proposal yang belum selesai.
     *
     * Proposal dianggap "belum selesai" jika:
     * - Status BUKAN rejected (final), DAN
     * - Belum punya outcome dengan status 'accepted'
     */
    public static function blockingProposalFor(int $userId, bool $isResearch): ?self
    {
        return self::query()
            ->where('user_id', $userId)
            ->where('is_research', $isResearch)
            // Kecualikan yang sudah final rejected
            ->where('status', '!=', self::STATUS_REJECTED)
            // Kecualikan yang sudah punya outcome accepted
            ->whereDoesntHave('outcome', fn($q) => $q->where('status', 'accepted'))
            ->latest()
            ->first();
    }

    /**
     * Cek apakah user boleh membuat proposal baru.
     */
    public static function canUserCreateNew(int $userId, bool $isResearch): bool
    {
        return self::blockingProposalFor($userId, $isResearch) === null;
    }
}
