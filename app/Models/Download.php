<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *@property int $id
 *@property int $user_id
 *@property string $title
 *@property string $description
 *@property string $category'
 *@property string $file_path
 *@property string $file_name
 *@property int $file_size
 *@property string $mime_type
 *@property bool $is_active
 *@property bool $show_on_welcome
 *@property bool $show_on_dashboard
 *@property int $sort_order
 *@property int $download_count
 *@property Carbon $created_at
 *@property Carbon $updated_at
 */
#[Guarded(['id'])]
class Download extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
        'show_on_welcome' => 'boolean',
        'show_on_dashboard' => 'boolean',
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnWelcome($query)
    {
        return $query->active()->where('show_on_welcome', true);
    }

    public function scopeOnDashboard($query)
    {
        return $query->active()->where('show_on_dashboard', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    protected function fileSizeHuman(): Attribute
    {
        return Attribute::get(function () {
            $bytes = (int) $this->file_size;
            if ($bytes <= 0)
                return '—';

            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = 0;
            while ($bytes >= 1024 && $i < count($units) - 1) {
                $bytes /= 1024;
                $i++;
            }
            return round($bytes, 2) . ' ' . $units[$i];
        });
    }

    protected function fileExtension(): Attribute
    {
        return Attribute::get(fn() => strtoupper(pathinfo($this->file_name, PATHINFO_EXTENSION) ?: 'FILE'));
    }

    protected function publicUrl(): Attribute
    {
        return Attribute::get(fn() => \Storage::disk('public')->url($this->file_path));
    }

    public function categoryMeta(): array
    {
        return match ($this->category) {
            'guideline' => [
                'icon' => 'book-open',
                'color' => 'sky',
                'label' => 'Pedoman',
            ],
            'template' => [
                'icon' => 'document-duplicate',
                'color' => 'violet',
                'label' => 'Template',
            ],
            'form' => [
                'icon' => 'clipboard-document',
                'color' => 'amber',
                'label' => 'Formulir',
            ],
            default => [
                'icon' => 'document',
                'color' => 'zinc',
                'label' => 'Umum',
            ],
        };
    }
    public static function categoryOptions(): array
    {
        return [
            'guideline' => 'Pedoman',
            'template' => 'Template',
            'form' => 'Formulir',
            'general' => 'Umum',
        ];
    }
}
