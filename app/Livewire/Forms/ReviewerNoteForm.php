<?php

namespace App\Livewire\Forms;

use App\Models\ReviewerNote;
use Livewire\Form;

class ReviewerNoteForm extends Form
{
    public ?ReviewerNote $reviewerNote = null;

    public ?int    $noteable_id = null;
    public string  $noteable_type = 'proposal';
    public ?int    $reviewer_id = null;
    public ?string $comment = null;
    public bool    $is_approved = false;
    public ?string $recommendation = null;

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'noteable_id'    => ['required', 'integer', 'min:1'],
            'noteable_type'  => ['required', 'string', 'in:proposal,progress_report'],
            'reviewer_id'    => ['required', 'exists:users,id'],
            'comment'        => ['nullable', 'string'],
            'is_approved'    => ['boolean'],
            'recommendation' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'noteable_id.required'   => 'Noteable wajib diisi.',
            'noteable_id.min'        => 'Noteable tidak valid.',
            'noteable_type.required' => 'Tipe noteable wajib diisi.',
            'noteable_type.in'       => 'Tipe noteable harus proposal atau progress_report.',

            'reviewer_id.required'   => 'Reviewer wajib diisi.',
            'reviewer_id.exists'     => 'Reviewer tidak valid.',

            'is_approved.boolean'    => 'Status approve harus true atau false.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setReviewerNote(ReviewerNote $reviewerNote): void
    {
        $this->reviewerNote   = $reviewerNote;
        $this->noteable_id    = $reviewerNote->noteable_id;
        $this->noteable_type  = $reviewerNote->noteable_type;
        $this->reviewer_id    = $reviewerNote->reviewer_id;
        $this->comment        = $reviewerNote->comment;
        $this->is_approved    = (bool) $reviewerNote->is_approved;
        $this->recommendation = $reviewerNote->recommendation;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): ReviewerNote
    {
        $this->validate();

        $note = ReviewerNote::create([
            'noteable_id'    => $this->noteable_id,
            'noteable_type'  => $this->noteable_type,
            'reviewer_id'    => $this->reviewer_id,
            'comment'        => $this->comment,
            'is_approved'    => $this->is_approved,
            'recommendation' => $this->recommendation,
        ]);

        $this->reset();

        return $note;
    }

    public function update(): ReviewerNote
    {
        if (! $this->reviewerNote) {
            throw new \RuntimeException(
                'No reviewer note loaded. Call setReviewerNote() before update().'
            );
        }

        $this->validate();

        $this->reviewerNote->update([
            'noteable_id'    => $this->noteable_id,
            'noteable_type'  => $this->noteable_type,
            'reviewer_id'    => $this->reviewer_id,
            'comment'        => $this->comment,
            'is_approved'    => $this->is_approved,
            'recommendation' => $this->recommendation,
        ]);

        $updated = $this->reviewerNote;

        $this->reset();

        return $updated;
    }
}
