<?php

namespace App\Livewire\Forms;

use App\Models\Output;
use Livewire\Form;

class OutputForm extends Form
{
    public ?Output $output = null;

    public ?int   $proposal_id = null;
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
            'journal_link' => ['required', 'string', 'max:255'],
            'edition'      => ['required', 'string', 'max:255'],
            'volume'       => ['required', 'string', 'max:255'],
            'level'        => ['required', 'string', 'max:255'],
            'status'       => ['required', 'string', 'in:pending,revised,accepted,rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_id.required'  => 'Proposal is required.',
            'proposal_id.exists'    => 'Selected proposal is invalid.',

            'journal_name.required' => 'Journal name is required.',
            'journal_link.required' => 'Journal link is required.',
            'edition.required'      => 'Edition is required.',
            'volume.required'       => 'Volume is required.',
            'level.required'        => 'Level is required.',

            'status.required'       => 'Status is required.',
            'status.in'             => 'Selected status is invalid.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setOutput(Output $output): void
    {
        $this->output       = $output;
        $this->proposal_id  = $output->proposal_id;
        $this->journal_name = $output->journal_name;
        $this->journal_link = $output->journal_link;
        $this->edition      = $output->edition;
        $this->volume       = $output->volume;
        $this->level        = $output->level;
        $this->status       = $output->status;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): Output
    {
        $this->validate();

        $output = Output::create([
            'proposal_id'  => $this->proposal_id,
            'journal_name' => $this->journal_name,
            'journal_link' => $this->journal_link,
            'edition'      => $this->edition,
            'volume'       => $this->volume,
            'level'        => $this->level,
            'status'       => $this->status,
        ]);

        $this->reset();

        return $output;
    }

    public function update(): Output
    {
        if (! $this->output) {
            throw new \RuntimeException(
                'No output loaded. Call setOutput() before update().'
            );
        }

        $this->validate();

        $this->output->update([
            'proposal_id'  => $this->proposal_id,
            'journal_name' => $this->journal_name,
            'journal_link' => $this->journal_link,
            'edition'      => $this->edition,
            'volume'       => $this->volume,
            'level'        => $this->level,
            'status'       => $this->status,
        ]);

        $updated = $this->output;

        $this->reset();

        return $updated;
    }
}
