<?php

namespace App\Livewire\Forms;

use App\Models\Informations;
use Livewire\Form;

class InformationForm extends Form
{
    public ?Informations $information = null;

    public string $title = '';
    public string $content = '';
    public string $type = 'info';
    public bool $is_published = true;
    public ?string $published_at = null;
    public ?string $expires_at = null;
    public int $priority = 0;

    /* ============================================================
     |  VALIDATION RULES
     ============================================================ */

    protected function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:180'],
            'content'      => ['required', 'string', 'max:5000'],
            'type'         => ['required', 'in:info,success,warning,danger'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at'   => ['nullable', 'date', 'after:published_at'],
            'priority'     => ['integer', 'min:0', 'max:99'],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required'        => 'Title is required.',
            'title.max'             => 'Title must not exceed 180 characters.',
            'content.required'      => 'Content is required.',
            'content.max'           => 'Content must not exceed 5000 characters.',
            'type.required'         => 'Type is required.',
            'type.in'               => 'Type is invalid.',
            'expires_at.after'      => 'The expiry date must be after the publish date.',
            'priority.integer'      => 'Priority must be a number.',
            'priority.min'          => 'Priority must be at least 0.',
            'priority.max'          => 'Priority must not exceed 99.',
        ];
    }

    /* ============================================================
     |  SETUP
     ============================================================ */

    public function setInformation(Informations $information): void
    {
        $this->information  = $information;
        $this->title        = $information->title;
        $this->content      = $information->content;
        $this->type         = $information->type;
        $this->is_published = (bool) $information->is_published;
        $this->published_at = $information->published_at?->format('Y-m-d\TH:i');
        $this->expires_at   = $information->expires_at?->format('Y-m-d\TH:i');
        $this->priority     = (int) $information->priority;
    }

    /* ============================================================
     |  PERSIST
     ============================================================ */

    public function create(): Informations
    {
        return Informations::create([
            'user_id'      => auth()->id(),
            'title'        => $this->title,
            'content'      => $this->content,
            'type'         => $this->type,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at ?: now(),
            'expires_at'   => $this->expires_at ?: null,
            'priority'     => $this->priority,
        ]);
    }

    public function update(): void
    {
        if (! $this->information) {
            return;
        }

        $this->information->update([
            'title'        => $this->title,
            'content'      => $this->content,
            'type'         => $this->type,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at ?: $this->information->published_at,
            'expires_at'   => $this->expires_at ?: null,
            'priority'     => $this->priority,
        ]);
    }
}
