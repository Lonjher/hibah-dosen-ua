<?php

namespace App\Livewire\Forms;

use App\Models\FinalReport;
use Livewire\Form;

class FinalReportForm extends Form
{
    public ?FinalReport $finalReport = null;

    public ?int $proposal_id = null;
    public string $summary = '';
    public string $keyword = '';

    public ?string $report_path = null;
    public ?string $sptb_path = null;
    public ?string $submission_proof = null;

    public string $status = 'pending';

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'proposal_id' => [
                'required',
                'exists:proposals,id',
            ],

            'summary' => [
                'required',
                'string',
                'min:20',
            ],

            'keyword' => [
                'required',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'string',
                'in:pending,revised,accepted,rejected',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_id.required' => 'Proposal is required.',
            'proposal_id.exists' => 'Invalid proposal.',

            'summary.required' => 'Summary is required.',
            'summary.min' => 'Summary must be at least 20 characters.',

            'keyword.required' => 'Keyword is required.',
            'keyword.max' => 'Keyword must not exceed 255 characters.',

            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setFinalReport(FinalReport $finalReport): void
    {
        $this->finalReport = $finalReport;

        $this->proposal_id = $finalReport->proposal_id;
        $this->summary = $finalReport->summary;
        $this->keyword = $finalReport->keyword;

        $this->report_path = $finalReport->report_path;
        $this->sptb_path = $finalReport->sptb_path;
        $this->submission_proof = $finalReport->submission_proof;

        $this->status = $finalReport->status;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): FinalReport
    {
        $this->validate();

        $report = FinalReport::create([
            'proposal_id' => $this->proposal_id,
            'summary' => $this->summary,
            'keyword' => $this->keyword,

            'report_path' => $this->report_path,
            'sptb_path' => $this->sptb_path,
            'submission_proof' => $this->submission_proof,

            'status' => $this->status,
        ]);

        $this->reset();

        return $report;
    }

    public function update(): FinalReport
    {
        if (! $this->finalReport) {
            throw new \RuntimeException(
                'No final report loaded. Call setFinalReport() before update().'
            );
        }

        $this->validate();

        $this->finalReport->update([
            'proposal_id' => $this->proposal_id,
            'summary' => $this->summary,
            'keyword' => $this->keyword,

            'report_path' => $this->report_path
                ?? $this->finalReport->report_path,

            'sptb_path' => $this->sptb_path
                ?? $this->finalReport->sptb_path,

            'submission_proof' => $this->submission_proof
                ?? $this->finalReport->submission_proof,

            'status' => $this->status,
        ]);

        $updated = $this->finalReport;

        $this->reset();

        return $updated;
    }
}
