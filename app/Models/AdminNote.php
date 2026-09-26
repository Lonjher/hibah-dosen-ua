<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $noteable_id
 * @property string $noteable_type // Morph map: proposal, progress_report, final_report, output
 * @property int $admin_id
 * @property string|null $comment
 * @property string|null $recommendation
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method MorphTo noteable()
 * @method BelongsTo<User> admin()
 */
#[Guarded(['id'])]
class AdminNote extends Model
{
    use HasFactory;
    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
