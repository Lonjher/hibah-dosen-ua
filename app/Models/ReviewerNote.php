<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $noteable_id
 * @property string $noteable_type // Morph map: proposal, progress_report
 * @property int $reviewer_id
 * @property string|null $comment
 * @property bool $is_approved
 * @property string|null $recommendation
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method MorphTo noteable()
 * @method BelongsTo<User> reviewer()
 */
#[Guarded(['id'])]
class ReviewerNote extends Model
{
    use HasFactory, HasActivity;
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
        ];
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
