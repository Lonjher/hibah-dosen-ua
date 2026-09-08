<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $periode
 * @property bool $is_active
 * @property Carbon|null $open_from
 * @property Carbon|null $open_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method HasMany<Proposal> proposals()
 */

#[Guarded(['id'])]
class Period extends Model
{
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }
}
