<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $reviewer_id
 * @property string $title
 * @property string $summary
 * @property string $keywords
 * @property bool $is_research
 * @property string $status_proposal
 * @property int $period_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<ResearchScheme> researchScheme()
 * @method BelongsTo<User> author()
 * @method BelongsTo<User> reviewer()
 * @method BelongsTo<Period> period()
 * @method HasOne<BudgetProposal> budgetProposal()
 * @method HasOne<ProgressReport> progressReport()
 * @method HasOne<FinalReport> finalReport()
 * @method HasOne<Output> output()
 * @method HasMany<AdminNotes> adminNotes()
 * @method HasMany<ReviewerNote> reviewerNotes()
 */
#[Guarded(['id'])]
class Proposal extends Model
{
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

    public function budgetProposal(): HasMany
    {
        return $this->hasMany(BudgetProposal::class);
    }

    public function progressReport(): HasOne
    {
        return $this->hasOne(ProgressReport::class);
    }

    public function finalReport(): HasOne
    {
        return $this->hasOne(FinalReport::class);
    }

    public function output(): HasOne
    {
        return $this->hasOne(Output::class);
    }

    public function adminNotes(): HasMany
    {
        return $this->hasMany(AdminNotes::class);
    }

    public function reviewerNotes(): HasMany
    {
        return $this->hasMany(ReviewerNote::class);
    }
}
