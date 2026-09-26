<?php

namespace App\Livewire\Forms;

use App\Models\AdminNote;
use Livewire\Form;

class AdminNoteForm extends Form
{
    public ?AdminNote $adminNote = null;

    public ?int    $noteable_id = null;
    public string  $noteable_type = 'proposal'; // proposal | progress_report | final_report | output
    public ?int    $admin_id = null;
    public ?string $comment = null;
    public ?string $recommendation = null;

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'noteable_id'    => ['required', 'integer', 'min:1'],
            'noteable_type'  => ['required', 'string', 'in:proposal,progress_report,final_report,output'],
            'admin_id'       => ['required', 'exists:users,id'],
            'comment'        => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'noteable_id.required'   => 'Noteable is required.',
            'noteable_type.required' => 'Noteable type is required.',
            'noteable_type.in'       => 'Noteable type is invalid.',

            'admin_id.required'      => 'Admin is required.',
            'admin_id.exists'        => 'Selected admin is invalid.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setAdminNote(AdminNote $adminNote): void
    {
        $this->adminNote      = $adminNote;
        $this->noteable_id    = $adminNote->noteable_id;
        $this->noteable_type  = $adminNote->noteable_type;
        $this->admin_id       = $adminNote->admin_id;
        $this->comment        = $adminNote->comment;
        $this->recommendation = $adminNote->recommendation;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): AdminNote
    {
        $this->validate();

        $note = AdminNote::create([
            'noteable_id'    => $this->noteable_id,
            'noteable_type'  => $this->noteable_type,
            'admin_id'       => $this->admin_id,
            'comment'        => $this->comment,
            'recommendation' => $this->recommendation,
        ]);

        $this->reset();

        return $note;
    }

    public function update(): AdminNote
    {
        if (! $this->adminNote) {
            throw new \RuntimeException(
                'No admin note loaded. Call setAdminNote() before update().'
            );
        }

        $this->validate();

        $this->adminNote->update([
            'noteable_id'    => $this->noteable_id,
            'noteable_type'  => $this->noteable_type,
            'admin_id'       => $this->admin_id,
            'comment'        => $this->comment,
            'recommendation' => $this->recommendation,
        ]);

        $updated = $this->adminNote;

        $this->reset();

        return $updated;
    }
}
