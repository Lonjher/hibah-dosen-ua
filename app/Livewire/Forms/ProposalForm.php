<?php

namespace App\Livewire\Forms;

use App\Models\Proposal;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ProposalForm extends Form
{
    public ?Proposal $proposal = null;

    public ?int $research_scheme_id = null;
    public ?int $user_id = null;
    public ?int $reviewer_id = null;

    public string $title = '';
    public string $summary = '';
    public string $keywords = '';

    public bool $is_research = true;

    public ?string $file_path = null;
    public ?string $rab_path = null;

    public string $status = 'pending';
    public ?int $period_id = null;

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    /** @var TemporaryUploadedFile|null */
    public $rab_file = null;

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

            'status'             => [
                'required',
                'string',
                'in:pending,revised,submitted,rejected,under_review,accepted',
            ],

            'period_id'          => ['required', 'exists:periods,id'],

            'file' => [
                // File is required only when no file is currently stored (new record).
                // When editing an existing proposal, the file is optional.
                ($this->proposal?->file_path ? 'nullable' : 'required'),
                'file',
                'max:10240',
                'mimes:pdf,doc,docx',
            ],

            'rab_file' => [
                // File is required only when no RAB file is currently stored (new record).
                // When editing an existing proposal, the RAB file is optional.
                ($this->proposal?->rab_path ? 'nullable' : 'required'),
                'file',
                'max:10240',
                'mimes:xlsx',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'research_scheme_id.required' => 'Research scheme is required.',
            'research_scheme_id.exists'   => 'Invalid research scheme.',

            'user_id.required'            => 'Author is required.',
            'user_id.exists'              => 'Invalid author.',

            'reviewer_id.exists'          => 'Invalid reviewer.',

            'title.required'              => 'Title is required.',
            'title.max'                   => 'Title must not exceed 255 characters.',

            'summary.required'            => 'Summary is required.',
            'summary.min'                 => 'Summary must be at least 20 characters.',

            'keywords.required'           => 'Keywords are required.',
            'keywords.max'                => 'Keywords must not exceed 255 characters.',

            'status.required'             => 'Status is required.',
            'status.in'                   => 'Invalid status.',

            'period_id.required'          => 'Period is required.',
            'period_id.exists'            => 'Invalid period.',

            'file.required'               => 'Proposal file is required.',
            'file.file'                   => 'Proposal file must be a valid file.',
            'file.max'                    => 'Proposal file must not exceed 10 MB.',
            'file.mimes'                  => 'Proposal file must be in PDF, DOC, or DOCX format.',

            'rab_file.required'           => 'RAB file is required.',
            'rab_file.file'               => 'RAB file must be a valid file.',
            'rab_file.max'                => 'RAB file must not exceed 10 MB.',
            'rab_file.mimes'              => 'RAB file must be in XLSX format.',
        ];
    }

    public function validateStep1(): void
    {
        $this->validateOnly('research_scheme_id');
        $this->validateOnly('title');
        $this->validateOnly('summary');
        $this->validateOnly('keywords');
        $this->validateOnly('period_id');
        $this->validateOnly('file');
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
        $this->rab_path           = $proposal->rab_path;

        $this->status             = $proposal->status;
        $this->period_id          = $proposal->period_id;

        $this->file               = null;
        $this->rab_file           = null;
    }

    // ═══════════════ Actions ═══════════════

    /**
     * Store a new file and return its path.
     * Returns null if no new file is provided.
     */
    public function storeFile(): ?string
    {
        if (! $this->file) {
            return null;
        }

        return $this->file->store('proposals', 'public');
    }

    /**
     * Store a new RAB file and return its path.
     * Returns null if no new RAB file is provided.
     */
    public function storeRabFile(): ?string
    {
        if (! $this->rab_file) {
            return null;
        }

        return $this->rab_file->store('proposals/rab', 'public');
    }

    public function create(): Proposal
    {
        $this->validate();

        $filePath = $this->storeFile() ?? '';
        $rabPath  = $this->storeRabFile() ?? '';

        $proposal = Proposal::create([
            'research_scheme_id' => $this->research_scheme_id,
            'user_id'            => $this->user_id,
            'reviewer_id'        => $this->reviewer_id,
            'title'              => $this->title,
            'summary'            => $this->summary,
            'keywords'           => $this->keywords,
            'is_research'        => $this->is_research,
            'file_path'          => $filePath,
            'rab_path'           => $rabPath,
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

        $newFilePath = $this->storeFile();
        $newRabPath  = $this->storeRabFile();

        // Delete the old proposal file when it is replaced.
        if (
            $newFilePath &&
            $this->proposal->file_path &&
            \Storage::disk('public')->exists($this->proposal->file_path)
        ) {
            \Storage::disk('public')->delete($this->proposal->file_path);
        }

        // Delete the old RAB file when it is replaced.
        if (
            $newRabPath &&
            $this->proposal->rab_path &&
            \Storage::disk('public')->exists($this->proposal->rab_path)
        ) {
            \Storage::disk('public')->delete($this->proposal->rab_path);
        }

        $this->proposal->update([
            'research_scheme_id' => $this->research_scheme_id,
            'user_id'            => $this->user_id,
            'reviewer_id'        => $this->reviewer_id,
            'title'              => $this->title,
            'summary'            => $this->summary,
            'keywords'           => $this->keywords,
            'is_research'        => $this->is_research,
            'file_path'          => $newFilePath ?? $this->proposal->file_path,
            'rab_path'           => $newRabPath ?? $this->proposal->rab_path,
            'status'             => $this->status,
            'period_id'          => $this->period_id,
        ]);

        $updated = $this->proposal;

        $this->reset();

        return $updated;
    }
}
