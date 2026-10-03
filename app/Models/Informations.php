<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $content
 * @property string $type
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property int $priority
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method BelongsTo<Proposal> proposal()
 */
#[Guarded(['id'])]
class Informations extends Model
{
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('priority')->orderByDesc('published_at');
    }
    public function typeMeta(): array
    {
        return match ($this->type) {
            'success' => [
                'icon'       => 'check-circle',
                'label'      => 'Sukses',
                'bg'         => 'bg-emerald-50 dark:bg-emerald-900/20',
                'border'     => 'border-emerald-200 dark:border-emerald-800',
                'text'       => 'text-emerald-800 dark:text-emerald-300',
                'icon_class' => 'text-emerald-600 dark:text-emerald-400',
            ],
            'warning' => [
                'icon'       => 'exclamation-triangle',
                'label'      => 'Peringatan',
                'bg'         => 'bg-amber-50 dark:bg-amber-900/20',
                'border'     => 'border-amber-200 dark:border-amber-800',
                'text'       => 'text-amber-800 dark:text-amber-300',
                'icon_class' => 'text-amber-600 dark:text-amber-400',
            ],
            'danger' => [
                'icon'       => 'x-circle',
                'label'      => 'Penting',
                'bg'         => 'bg-rose-50 dark:bg-rose-900/20',
                'border'     => 'border-rose-200 dark:border-rose-800',
                'text'       => 'text-rose-800 dark:text-rose-300',
                'icon_class' => 'text-rose-600 dark:text-rose-400',
            ],
            default => [
                'icon'       => 'information-circle',
                'label'      => 'Info',
                'bg'         => 'bg-sky-50 dark:bg-sky-900/20',
                'border'     => 'border-sky-200 dark:border-sky-800',
                'text'       => 'text-sky-800 dark:text-sky-300',
                'icon_class' => 'text-sky-600 dark:text-sky-400',
            ],
        };
    }

    public function statusMeta(): array
    {
        if (! $this->is_published) {
            return [
                'label' => 'Draft',
                'class' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
            ];
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return [
                'label' => 'Kadaluarsa',
                'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
            ];
        }

        if ($this->published_at && $this->published_at->isFuture()) {
            return [
                'label' => 'Terjadwal',
                'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
            ];
        }

        return [
            'label' => 'Aktif',
            'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        ];
    }
}
