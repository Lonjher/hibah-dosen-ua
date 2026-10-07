<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $proposal_id
 * @property string $nim
 * @property string $name
 * @property string $program_study
 * @property string $role // leader, member
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<Proposal> proposal()
 */
#[Guarded(['id'])]
class ProposalStudent extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => 'string',
        ];
    }

    /* ============================================================
     |  RELATIONS
     ============================================================ */

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
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

    /**
     * @return array{label: string, class: string, icon: string}
     */
    public function roleMeta(): array
    {
        return match ($this->role) {
            'leader' => [
                'label' => 'Ketua',
                'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                'icon'  => 'user-circle',
            ],
            default => [
                'label' => 'Anggota',
                'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                'icon'  => 'user',
            ],
        };
    }
}
