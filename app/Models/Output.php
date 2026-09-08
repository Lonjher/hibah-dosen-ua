<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class Output extends Model
{
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
