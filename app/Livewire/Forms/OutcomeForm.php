<?php

namespace App\Livewire\Forms;

use App\Models\Outcome;
use Livewire\Form;

class OutcomeForm extends Form
{
    public ?Outcome $outcome = null;

    public ?int $proposal_id = null;
    public string $journal_name = '';
    public string $journal_link = '';
    public string $edition = '';
    public string $volume = '';
    public string $level = '';
    public string $status = 'pending';

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'proposal_id'  => ['required', 'exists:proposals,id'],
            'journal_name' => ['required', 'string', 'max:255'],
            'journal_link' => ['required', 'url', 'max:255'],
            'edition'     => ['required', 'string', 'max:255'],
            'volume'      => ['required', 'string', 'max:255'],
            'level'       => [
                'required',
                'string',
                'in:Scopus,Sinta 1,Sinta 2,Sinta 3,Sinta 4,Sinta 5,Sinta 6',
            ],
            'status'      => [
                'required',
                'string',
                'in:pending,revised,accepted,rejected',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_id.required'  => 'Proposal is required.',
            'proposal_id.exists'    => 'Invalid proposal.',

            'journal_name.required' => 'Journal name is required.',
            'journal_name.max'      => 'Journal name must not exceed 255 characters.',

            'journal_link.required' => 'Journal link is required.',
            'journal_link.url'      => 'Journal link must be a valid URL.',
            'journal_link.max'      => 'Journal link must not exceed 255 characters.',

            'edition.required'     => 'Edition is required.',
            'edition.max'          => 'Edition must not exceed 255 characters.',

            'volume.required'      => 'Volume is required.',
            'volume.max'           => 'Volume must not exceed 255 characters.',

            'level.required'       => 'Level is required.',
            'level.in'             => 'Level must be one of: Scopus or Sinta 1–6.',

            'status.required'      => 'Status is required.',
            'status.in'            => 'Invalid status.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setOutcome(Outcome $outcome): void
    {
        $this->outcome       = $outcome;
        $this->proposal_id   = $outcome->proposal_id;
        $this->journal_name  = $outcome->journal_name;
        $this->journal_link  = $outcome->journal_link;
        $this->edition       = $outcome->edition;
        $this->volume        = $outcome->volume;
        $this->level         = $outcome->level;
        $this->status        = $outcome->status;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): Outcome
    {
        $this->validate();

        $outcome = Outcome::create([
            'proposal_id'  => $this->proposal_id,
            'journal_name' => $this->journal_name,
            'journal_link' => $this->journal_link,
            'edition'      => $this->edition,
            'volume'       => $this->volume,
            'level'        => $this->level,
            'status'       => $this->status,
        ]);

        $this->reset();

        return $outcome;
    }

    public function update(): Outcome
    {
        if (! $this->outcome) {
            throw new \RuntimeException(
                'No outcome loaded. Call setOutcome() before update().'
            );
        }

        $this->validate();

        $this->outcome->update([
            'proposal_id'  => $this->proposal_id,
            'journal_name' => $this->journal_name,
            'journal_link' => $this->journal_link,
            'edition'      => $this->edition,
            'volume'       => $this->volume,
            'level'        => $this->level,
            'status'       => $this->status,
        ]);

        $updated = $this->outcome;

        $this->reset();

        return $updated;
    }
}
