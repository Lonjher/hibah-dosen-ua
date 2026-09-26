<?php

namespace App\Livewire\Forms;

use App\Models\ProgressReport;
use Livewire\Form;

class ProgressReportForm extends Form
{
    public ?ProgressReport $progressReport = null;

    public ?int    $proposal_id = null;
    public ?int    $reviewer_id = null;
    public string  $summary = '';
    public string  $keyword = '';
    public ?string $report_path = null;
    public ?string $ppt_path = null;
    public string  $status = 'pending';
    public ?string $reviewed_at = null;

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'proposal_id' => ['required', 'exists:proposals,id'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
            'summary'     => ['required', 'string', 'min:20'],
            'keyword'     => ['required', 'string', 'max:255'],
            'status'      => ['required', 'string', 'in:pending,revised,submitted,rejected,under_review,accepted'],
            'reviewed_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_id.required' => 'Proposal wajib diisi.',
            'proposal_id.exists'   => 'Proposal tidak valid.',

            'reviewer_id.exists'   => 'Reviewer tidak valid.',

            'summary.required'     => 'Ringkasan wajib diisi.',
            'summary.min'          => 'Ringkasan minimal 20 karakter.',

            'keyword.required'     => 'Kata kunci wajib diisi.',
            'keyword.max'          => 'Kata kunci maksimal 255 karakter.',

            'status.required'      => 'Status wajib diisi.',
            'status.in'            => 'Status tidak valid.',

            'reviewed_at.date'     => 'Reviewed At harus tanggal valid.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setProgressReport(ProgressReport $progressReport): void
    {
        $this->progressReport = $progressReport;
        $this->proposal_id    = $progressReport->proposal_id;
        $this->reviewer_id    = $progressReport->reviewer_id;
        $this->summary        = $progressReport->summary;
        $this->keyword        = $progressReport->keyword;
        $this->report_path    = $progressReport->report_path;
        $this->ppt_path       = $progressReport->ppt_path;
        $this->status         = $progressReport->status;
        $this->reviewed_at    = $progressReport->reviewed_at?->format('Y-m-d\TH:i');
    }

    // ═══════════════ Actions ═══════════════

    public function create(): ProgressReport
    {
        $this->validate();

        $report = ProgressReport::create([
            'proposal_id' => $this->proposal_id,
            'reviewer_id' => $this->reviewer_id,
            'summary'     => $this->summary,
            'keyword'     => $this->keyword,
            'report_path' => $this->report_path ?? '',
            'ppt_path'    => $this->ppt_path ?? '',
            'status'      => $this->status,
            'reviewed_at' => $this->reviewed_at,
        ]);

        $this->reset();

        return $report;
    }

    public function update(): ProgressReport
    {
        if (! $this->progressReport) {
            throw new \RuntimeException(
                'No progress report loaded. Call setProgressReport() before update().'
            );
        }

        $this->validate();

        $this->progressReport->update([
            'proposal_id' => $this->proposal_id,
            'reviewer_id' => $this->reviewer_id,
            'summary'     => $this->summary,
            'keyword'     => $this->keyword,
            'report_path' => $this->report_path ?? $this->progressReport->report_path,
            'ppt_path'    => $this->ppt_path ?? $this->progressReport->ppt_path,
            'status'      => $this->status,
            'reviewed_at' => $this->reviewed_at,
        ]);

        $updated = $this->progressReport;

        $this->reset();

        return $updated;
    }
}
