<?php

namespace App\Livewire\Forms;

use App\Models\ExternalProposal;
use App\Models\User;
use Livewire\Form;

class ExternalResearchForm extends Form
{
    public ?ExternalProposal $externalResearch = null;

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
    public $document = null;
    public bool $is_verified = false;

    protected function rules(): array
    {
        return [
            'user_id'        => $this->externalResearch
                                    ? ['nullable', 'exists:users,id']
                                    : ['required', 'exists:users,id'],
            'title'          => ['required', 'string', 'max:255'],
            'scheme'         => ['nullable', 'string', 'max:255'],
            'funding_source' => ['required', 'string', 'max:255'],
            'role'           => ['required', 'in:leader,member'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
            'status'         => ['required', 'in:ongoing,completed,cancelled'],
            'fund_amount'    => ['required', 'integer', 'min:0'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'document'       => ['nullable', 'file', 'max:10240',
                                  'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'is_verified'    => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'user_id.required'        => 'Please select a user.',
            'user_id.exists'          => 'Selected user is invalid.',
            'title.required'          => 'Title is required.',
            'funding_source.required' => 'Funding source is required.',
            'end_date.after_or_equal' => 'End date must be on or after start date.',
            'document.mimes'          => 'Document must be PDF, DOC, DOCX, JPG, JPEG, or PNG.',
            'document.max'            => 'Document size must not exceed 10 MB.',
        ];
    }

    public function setExternalResearch(ExternalProposal $externalResearch): void
    {
        $this->externalResearch  = $externalResearch;
        $this->user_id           = $externalResearch->user_id;
        $this->title             = $externalResearch->title;
        $this->scheme            = $externalResearch->scheme ?? '';
        $this->funding_source    = $externalResearch->funding_source;
        $this->role              = $externalResearch->role;
        $this->start_date        = $externalResearch->start_date?->format('Y-m-d') ?? '';
        $this->end_date          = $externalResearch->end_date?->format('Y-m-d');
        $this->status            = $externalResearch->status;
        $this->fund_amount       = (int) $externalResearch->fund_amount;
        $this->description       = $externalResearch->description ?? '';
        $this->is_verified       = (bool) $externalResearch->is_verified;
    }

    public function create(): ExternalProposal
    {
        $documentPath = $this->document
            ? $this->document->store('external-proposals', 'public')
            : null;

        return ExternalProposal::create([
            'user_id'        => $this->user_id,
            'is_research'    => true,              // ← discriminator
            'title'          => $this->title,
            'scheme'         => $this->scheme,
            'funding_source' => $this->funding_source,
            'role'           => $this->role,
            'start_date'     => $this->start_date,
            'end_date'       => $this->end_date,
            'status'         => $this->status,
            'fund_amount'    => $this->fund_amount,
            'description'    => $this->description,
            'document_path'  => $documentPath,
            'is_verified'    => $this->is_verified,
        ]);
    }

    public function update(): void
    {
        if (! $this->externalResearch) {
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

        if ($this->document) {
            if ($this->externalResearch->document_path
                && \Storage::disk('public')->exists($this->externalResearch->document_path)) {
                \Storage::disk('public')->delete($this->externalResearch->document_path);
            }

            $payload['document_path'] = $this->document->store('external-proposals', 'public');
        }

        $this->externalResearch->update($payload);
    }

    public function selectedUser(): ?User
    {
        return $this->user_id ? User::find($this->user_id) : null;
    }
}
