<?php

namespace App\Livewire\Forms;

use App\Models\Proposal;
use Livewire\Form;

class ProposalForm extends Form
{
    public ?Proposal $proposal = null;

    public ?int    $research_scheme_id = null;
    public ?int    $user_id = null;
    public ?int    $reviewer_id = null;
    public string  $title = '';
    public string  $summary = '';
    public string  $keywords = '';
    public bool    $is_research = true;
    public ?string $file_path = null;  // untuk upload (handle di component)
    public string  $status = 'pending';
    public ?int    $period_id = null;

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'research_scheme_id' => ['required', 'exists:research_schemes,id'],
            'user_id'            => ['required', 'exists:users,id'],
            'reviewer_id'        => ['nullable', 'exists:users,id'],
            'title'              => ['required', 'string', 'max:255'],
            'summary'            => ['required', 'string'],
            'keywords'           => ['required', 'string', 'max:255'],
            'is_research'        => ['boolean'],
            'status'             => ['required', 'string', 'in:pending,revised,submitted,rejected,under_review,accepted'],
            'period_id'          => ['required', 'exists:periods,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'research_scheme_id.required' => 'Research scheme is required.',
            'research_scheme_id.exists'   => 'Selected research scheme is invalid.',

            'user_id.required'            => 'Author is required.',
            'user_id.exists'              => 'Selected author is invalid.',

            'reviewer_id.exists'          => 'Selected reviewer is invalid.',

            'title.required'              => 'Title is required.',
            'title.max'                   => 'Title must not exceed 255 characters.',

            'summary.required'            => 'Summary is required.',

            'keywords.required'           => 'Keywords are required.',

            'status.required'             => 'Status is required.',
            'status.in'                   => 'Selected status is invalid.',

            'period_id.required'          => 'Period is required.',
            'period_id.exists'            => 'Selected period is invalid.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setProposal(Proposal $proposal): void
    {
        $this->proposal           = $proposal;
        $this->research_scheme_id = $proposal->research_scheme_id;
        $this->user_id            = $proposal->user_id;
        $this->reviewer_id        = $proposal->reviewer_id;
        $this->title              = $proposal->title;
        $this->summary            = $proposal->summary;
        $this->keywords           = $proposal->keywords;
        $this->is_research        = (bool) $proposal->is_research;
        $this->file_path          = $proposal->file_path;
        $this->status             = $proposal->status;
        $this->period_id          = $proposal->period_id;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): Proposal
    {
        $this->validate();

        $proposal = Proposal::create([
            'research_scheme_id' => $this->research_scheme_id,
            'user_id'            => $this->user_id,
            'reviewer_id'        => $this->reviewer_id,
            'title'              => $this->title,
            'summary'            => $this->summary,
            'keywords'           => $this->keywords,
            'is_research'        => $this->is_research,
            'file_path'          => $this->file_path ?? '',
            'status'             => $this->status,
            'period_id'          => $this->period_id,
        ]);

        $this->reset();

        return $proposal;
    }

    public function update(): Proposal
    {
        if (! $this->proposal) {
            throw new \RuntimeException(
                'No proposal loaded. Call setProposal() before update().'
            );
        }

        $this->validate();

        $this->proposal->update([
            'research_scheme_id' => $this->research_scheme_id,
            'user_id'            => $this->user_id,
            'reviewer_id'        => $this->reviewer_id,
            'title'              => $this->title,
            'summary'            => $this->summary,
            'keywords'           => $this->keywords,
            'is_research'        => $this->is_research,
            'file_path'          => $this->file_path ?? $this->proposal->file_path,
            'status'             => $this->status,
            'period_id'          => $this->period_id,
        ]);

        $updated = $this->proposal;

        $this->reset();

        return $updated;
    }
}
