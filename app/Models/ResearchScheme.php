<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $scheme_name
 * @property string $scheme_code
 * @property string $scheme_description
 * @property string $budget_limit
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method HasMany<Proposal> proposals()
 */

#[Guarded(['id'])]
class ResearchScheme extends Model
{
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }
}
