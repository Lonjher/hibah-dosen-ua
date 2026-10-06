<?php

namespace App\Livewire\Forms;

use App\Models\ExternalProposal;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Form;

class ExternalDedicationForm extends Form
{
    public ?ExternalProposal $externalDedication = null;

    public ?int $user_id = null;

    public string $title = '';
    public string $scheme = '';
    public string $funding_source = '';
    public string $role = 'leader';
    public string $start_date = '';
    public ?string $end_date = null;
    public string $status = 'ongoing';
    public $fund_amount = 0;
    public string $description = '';

    // ── Supporting Documents ──
    public $proposal_document = null;   // UploadedFile
    public $report_document = null;     // UploadedFile

    public bool $is_verified = false;

    protected function rules(): array
    {
        return [
            'user_id'           => $this->externalDedication
                                        ? ['nullable', 'exists:users,id']
                                        : ['required', 'exists:users,id'],
            'title'             => ['required', 'string', 'max:255'],
            'scheme'            => ['nullable', 'string', 'max:255'],
            'funding_source'    => ['required', 'string', 'max:255'],
            'role'              => ['required', 'in:leader,member'],
            'start_date'        => ['required', 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],
            'status'            => ['required', 'in:ongoing,completed,cancelled'],
            'fund_amount'       => ['required', 'integer', 'min:0'],
            'description'       => ['nullable', 'string', 'max:2000'],
            'proposal_document' => ['nullable', 'file', 'max:10240',
                                     'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'report_document'   => ['nullable', 'file', 'max:10240',
                                     'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'is_verified'       => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'user_id.required'           => 'Please select a user.',
            'user_id.exists'             => 'Selected user is invalid.',
            'title.required'             => 'Title is required.',
            'funding_source.required'    => 'Funding source is required.',
            'end_date.after_or_equal'    => 'End date must be on or after start date.',
            'proposal_document.mimes'    => 'Proposal document must be PDF, DOC, DOCX, JPG, JPEG, or PNG.',
            'proposal_document.max'      => 'Proposal document size must not exceed 10 MB.',
            'report_document.mimes'      => 'Report document must be PDF, DOC, DOCX, JPG, JPEG, or PNG.',
            'report_document.max'        => 'Report document size must not exceed 10 MB.',
        ];
    }

    public function setExternalDedication(ExternalProposal $externalDedication): void
    {
        $this->externalDedication  = $externalDedication;
        $this->user_id             = $externalDedication->user_id;
        $this->title               = $externalDedication->title;
        $this->scheme              = $externalDedication->scheme ?? '';
        $this->funding_source      = $externalDedication->funding_source;
        $this->role                = $externalDedication->role;
        $this->start_date          = $externalDedication->start_date?->format('Y-m-d') ?? '';
        $this->end_date            = $externalDedication->end_date?->format('Y-m-d');
        $this->status              = $externalDedication->status;
        $this->fund_amount         = (int) $externalDedication->fund_amount;
        $this->description         = $externalDedication->description ?? '';
        $this->is_verified         = (bool) $externalDedication->is_verified;

        // Reset file inputs
        $this->proposal_document = null;
        $this->report_document   = null;
    }

    public function create(): ExternalProposal
    {
        $proposalPath = $this->proposal_document
            ? $this->proposal_document->store('external-dedications/proposals', 'public')
            : null;

        $reportPath = $this->report_document
            ? $this->report_document->store('external-dedications/reports', 'public')
            : null;

        return ExternalProposal::create([
            'user_id'                => $this->user_id,
            'is_research'            => false,             // ← discriminator
            'title'                  => $this->title,
            'scheme'                 => $this->scheme,
            'funding_source'         => $this->funding_source,
            'role'                   => $this->role,
            'start_date'             => $this->start_date,
            'end_date'               => $this->end_date,
            'status'                 => $this->status,
            'fund_amount'            => $this->fund_amount,
            'description'            => $this->description,
            'proposal_document_path' => $proposalPath,
            'report_document_path'   => $reportPath,
            'is_verified'            => $this->is_verified,
        ]);
    }

    public function update(): void
    {
        if (! $this->externalDedication) {
            return;
        }

        $payload = [
            'title'          => $this->title,
            'scheme'         => $this->scheme,
            'funding_source' => $this->funding_source,
            'role'           => $this->role,
            'start_date'     => $this->start_date,
            'end_date'       => $this->end_date,
            'status'         => $this->status,
            'fund_amount'    => $this->fund_amount,
            'description'    => $this->description,
            'is_verified'    => $this->is_verified,
        ];

        // ── Replace Proposal Document ──
        if ($this->proposal_document) {
            if ($this->externalDedication->proposal_document_path
                && Storage::disk('public')->exists($this->externalDedication->proposal_document_path)) {
                Storage::disk('public')->delete($this->externalDedication->proposal_document_path);
            }

            $payload['proposal_document_path'] = $this->proposal_document
                ->store('external-dedications/proposals', 'public');
        }

        // ── Replace Report Document ──
        if ($this->report_document) {
            if ($this->externalDedication->report_document_path
                && Storage::disk('public')->exists($this->externalDedication->report_document_path)) {
                Storage::disk('public')->delete($this->externalDedication->report_document_path);
            }

            $payload['report_document_path'] = $this->report_document
                ->store('external-dedications/reports', 'public');
        }

        $this->externalDedication->update($payload);
    }

    public function selectedUser(): ?User
    {
        return $this->user_id ? User::find($this->user_id) : null;
    }
}
