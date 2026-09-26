<?php

namespace App\Livewire\Forms;

use App\Models\FinalReport;
use Livewire\Form;

class FinalReportForm extends Form
{
    public ?FinalReport $finalReport = null;

    public ?int    $proposal_id = null;
    public string  $summary = '';
    public string  $keyword = '';
    public ?string $report_path = null;
    public ?string $ppt_path = null;
    public ?string $research_output = null;
    public ?string $submission_proof = null;
    public string  $status = 'pending';

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'proposal_id' => ['required', 'exists:proposals,id'],
            'summary'     => ['required', 'string', 'min:20'],
            'keyword'     => ['required', 'string', 'max:255'],
            'status'      => ['required', 'string', 'in:pending,revised,accepted,rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_id.required' => 'Proposal wajib diisi.',
            'proposal_id.exists'   => 'Proposal tidak valid.',

            'summary.required'     => 'Ringkasan wajib diisi.',
            'summary.min'          => 'Ringkasan minimal 20 karakter.',

            'keyword.required'     => 'Kata kunci wajib diisi.',
            'keyword.max'          => 'Kata kunci maksimal 255 karakter.',

            'status.required'      => 'Status wajib diisi.',
            'status.in'            => 'Status tidak valid.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setFinalReport(FinalReport $finalReport): void
    {
        $this->finalReport      = $finalReport;
        $this->proposal_id      = $finalReport->proposal_id;
        $this->summary          = $finalReport->summary;
        $this->keyword          = $finalReport->keyword;
        $this->report_path      = $finalReport->report_path;
        $this->ppt_path         = $finalReport->ppt_path;
        $this->research_output  = $finalReport->research_output;
        $this->submission_proof = $finalReport->submission_proof;
        $this->status           = $finalReport->status;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): FinalReport
    {
        $this->validate();

        $report = FinalReport::create([
            'proposal_id'      => $this->proposal_id,
            'summary'          => $this->summary,
            'keyword'          => $this->keyword,
            'report_path'      => $this->report_path ?? '',
            'ppt_path'         => $this->ppt_path ?? '',
            'research_output'  => $this->research_output ?? '',
            'submission_proof' => $this->submission_proof ?? '',
            'status'           => $this->status,
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
            'proposal_id'      => $this->proposal_id,
            'summary'          => $this->summary,
            'keyword'          => $this->keyword,
            'report_path'      => $this->report_path ?? $this->finalReport->report_path,
            'ppt_path'         => $this->ppt_path ?? $this->finalReport->ppt_path,
            'research_output'  => $this->research_output ?? $this->finalReport->research_output,
            'submission_proof' => $this->submission_proof ?? $this->finalReport->submission_proof,
            'status'           => $this->status,
        ]);

        $updated = $this->finalReport;

        $this->reset();

        return $updated;
    }
}
