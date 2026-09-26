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
    public ?string $file_path = null;
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
            'summary'            => ['required', 'string', 'min:20'],
            'keywords'           => ['required', 'string', 'max:255'],
            'is_research'        => ['boolean'],
            'status'             => ['required', 'string', 'in:pending,revised,submitted,rejected,under_review,accepted'],
            'period_id'          => ['required', 'exists:periods,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'research_scheme_id.required' => 'Skema wajib dipilih.',
            'research_scheme_id.exists'   => 'Skema tidak valid.',

            'user_id.required'            => 'Author wajib diisi.',
            'user_id.exists'              => 'Author tidak valid.',

            'reviewer_id.exists'          => 'Reviewer tidak valid.',

            'title.required'              => 'Judul wajib diisi.',
            'title.max'                   => 'Judul maksimal 255 karakter.',

            'summary.required'            => 'Ringkasan wajib diisi.',
            'summary.min'                 => 'Ringkasan minimal 20 karakter.',

            'keywords.required'           => 'Kata kunci wajib diisi.',
            'keywords.max'                => 'Kata kunci maksimal 255 karakter.',

            'status.required'             => 'Status wajib diisi.',
            'status.in'                   => 'Status tidak valid.',

            'period_id.required'          => 'Periode wajib dipilih.',
            'period_id.exists'            => 'Periode tidak valid.',
        ];
    }

    /**
     * Validasi khusus Step 1 (Metadata).
     */
    public function validateStep1(): void
    {
        $this->validateOnly('research_scheme_id');
        $this->validateOnly('title');
        $this->validateOnly('summary');
        $this->validateOnly('keywords');
        $this->validateOnly('period_id');
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
