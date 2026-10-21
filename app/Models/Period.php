<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $periode
 * @property bool $is_active
 * @property Carbon|null $open_from
 * @property Carbon|null $open_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method HasMany<Proposal> proposals()
 */
#[Guarded(['id'])]
class Period extends Model
{
    use HasFactory, HasActivity;
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'open_from' => 'date',
            'open_to'   => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }
}
