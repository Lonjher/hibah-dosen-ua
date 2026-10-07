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

            'report_path' => [
                'required',
                'file',
                'mimes:docx,pdf',
                'max:10240',
            ],

            'sptb_path' => [
                'required',
                'file',
                'mimes:docx,pdf',
                'max:10240',
            ],

            'submission_proof' => [
                'required',
                'image',
                'max:10240',
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

            'report_path.required' => 'Final report file is required.',
            'report_path.file' => 'Final report must be a valid file.',
            'report_path.mimes' => 'Final report must be a DOCX or PDF file.',
            'report_path.max' => 'Final report must not exceed 10 MB.',

            'sptb_path.required' => 'SPTB file is required.',
            'sptb_path.file' => 'SPTB must be a valid file.',
            'sptb_path.mimes' => 'SPTB must be a DOCX or PDF file.',
            'sptb_path.max' => 'SPTB file must not exceed 10 MB.',

            'submission_proof.required' => 'Submission proof is required.',
            'submission_proof.image' => 'Submission proof must be an image.',
            'submission_proof.max' => 'Submission proof must not exceed 10 MB.',

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
        if (!$this->finalReport) {
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
