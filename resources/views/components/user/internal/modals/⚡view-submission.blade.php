<?php

use App\Livewire\Forms\FinalReportForm;
use App\Livewire\Forms\OutputForm;
use App\Livewire\Forms\ProgressReportForm;
use App\Models\FinalReport;
use App\Models\Output;
use App\Models\ProgressReport;
use App\Models\Proposal;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?int $proposal_id = null;
    public ?Proposal $proposal = null;

    public string $activeTab = 'progress_report';

    // Submissions
    public ?ProgressReport $progressReport = null;
    public ?FinalReport $finalReport = null;
    public ?Output $output = null;

    // Forms
    public ProgressReportForm $progressForm;
    public FinalReportForm $finalForm;
    public OutputForm $outputForm;

    // Files untuk upload
    public $progress_report_file = null;
    public $progress_ppt_file = null;

    public $final_report_file = null;
    public $final_ppt_file = null;
    public $final_research_output_file = null;
    public $final_submission_proof_file = null;

    // Mode form: show/hide upload form per tab
    public bool $showProgressForm = false;
    public bool $showFinalForm = false;
    public bool $showOutputForm = false;

    #[On('open-view-submission-user')]
    public function load(int $proposalId): void
    {
        $this->proposal_id = $proposalId;
        $this->reload();

        $this->activeTab = 'progress_report';
        $this->showProgressForm = false;
        $this->showFinalForm = false;
        $this->showOutputForm = false;

        $this->setupForms();
        $this->dispatch('show-view-submission-user');
    }

    // ═══════════════ TAB SWITCH ═══════════════

    public function switchTab(string $tab): void
    {
        // Guard: jangan switch ke tab yang belum unlock
        if ($tab === 'final_report' && $this->progressReport?->status !== 'accepted') {
            Flux::toast('Progress Report harus di-accept dulu.', variant: 'danger');
            return;
        }

        if ($tab === 'output' && $this->finalReport?->status !== 'accepted') {
            Flux::toast('Final Report harus di-accept dulu.', variant: 'danger');
            return;
        }

        $this->activeTab = $tab;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // ═══════════════ HELPERS ═══════════════

    protected function reload(): void
    {
        $this->proposal = Proposal::with(['researchScheme', 'period', 'reviewer'])
            ->where('user_id', Auth::id())
            ->findOrFail($this->proposal_id);

        $this->progressReport = ProgressReport::with(['reviewer', 'reviewerNotes' => fn($q) => $q->latest()->with('reviewer'), 'adminNotes' => fn($q) => $q->latest()->with('admin')])
            ->where('proposal_id', $this->proposal_id)
            ->first();

        $this->finalReport = FinalReport::with([
            'adminNotes' => fn($q) => $q->latest()->with('admin'),
        ])
            ->where('proposal_id', $this->proposal_id)
            ->first();

        $this->output = Output::with([
            'adminNotes' => fn($q) => $q->latest()->with('admin'),
        ])
            ->where('proposal_id', $this->proposal_id)
            ->first();
    }

    protected function setupForms(): void
    {
        $this->progressForm->reset();
        $this->finalForm->reset();
        $this->outputForm->reset();

        $this->reset(['progress_report_file', 'progress_ppt_file', 'final_report_file', 'final_ppt_file', 'final_research_output_file', 'final_submission_proof_file']);
    }

    // ═══════════════ TAB UNLOCK CHECK ═══════════════

    public function isFinalReportUnlocked(): bool
    {
        return $this->progressReport?->status === 'accepted';
    }

    public function isOutputUnlocked(): bool
    {
        return $this->finalReport?->status === 'accepted';
    }

    // ═══════════════ PROGRESS REPORT ACTIONS ═══════════════

    public function showUploadProgress(): void
    {
        if ($this->progressReport) {
            return;
        }

        $this->progressForm->reset();
        $this->progressForm->proposal_id = $this->proposal_id;
        $this->progressForm->status = 'pending';
        $this->progressForm->reviewer_id = null;
        $this->reset(['progress_report_file', 'progress_ppt_file']);
        $this->showProgressForm = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function showEditProgress(): void
    {
        if (!$this->progressReport) {
            return;
        }

        $this->progressForm->setProgressReport($this->progressReport);
        $this->reset(['progress_report_file', 'progress_ppt_file']);
        $this->showProgressForm = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function cancelProgressForm(): void
    {
        $this->showProgressForm = false;
        $this->progressForm->reset();
        $this->reset(['progress_report_file', 'progress_ppt_file']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function saveProgress(): void
    {
        // Validasi Form
        $this->progressForm->validate();

        // Validasi file
        $isEdit = $this->progressReport !== null;

        $this->validate(
            [
                'progress_report_file' => [$isEdit ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:10240'],
                'progress_ppt_file' => [$isEdit ? 'nullable' : 'required', 'file', 'mimes:ppt,pptx', 'max:20480'],
            ],
            [
                'progress_report_file.required' => 'File laporan wajib diunggah.',
                'progress_ppt_file.required' => 'File presentasi wajib diunggah.',
            ],
        );

        // Cek kondisi
        if (!$isEdit) {
            if ($this->proposal->status !== 'accepted') {
                Flux::toast('Proposal belum accepted.', variant: 'danger');
                return;
            }
            if ($this->proposal->progressReport) {
                Flux::toast('Progress report sudah ada.', variant: 'danger');
                return;
            }
        }

        // Simpan file jika di-upload
        if ($this->progress_report_file) {
            $this->progressForm->report_path = $this->progress_report_file->store('progress-reports/reports', 'public');
        }
        if ($this->progress_ppt_file) {
            $this->progressForm->ppt_path = $this->progress_ppt_file->store('progress-reports/ppt', 'public');
        }

        if ($isEdit) {
            $this->progressForm->status = 'pending';
            $this->progressForm->update();
            Flux::toast('Progress Report berhasil diperbarui.', variant: 'success');
        } else {
            $this->progressForm->create();
            Flux::toast('Progress Report berhasil diunggah.', variant: 'success');
        }

        $this->showProgressForm = false;
        $this->reload();
    }

    // ═══════════════ FINAL REPORT ACTIONS ═══════════════

    public function showUploadFinal(): void
    {
        if ($this->finalReport) {
            return;
        }
        if (!$this->isFinalReportUnlocked()) {
            Flux::toast('Progress Report harus di-accept dulu.', variant: 'danger');
            return;
        }

        $this->finalForm->reset();
        $this->finalForm->proposal_id = $this->proposal_id;
        $this->finalForm->status = 'pending';
        $this->reset(['final_report_file', 'final_ppt_file', 'final_research_output_file', 'final_submission_proof_file']);
        $this->showFinalForm = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function showEditFinal(): void
    {
        if (!$this->finalReport) {
            return;
        }

        $this->finalForm->setFinalReport($this->finalReport);
        $this->reset(['final_report_file', 'final_ppt_file', 'final_research_output_file', 'final_submission_proof_file']);
        $this->showFinalForm = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function cancelFinalForm(): void
    {
        $this->showFinalForm = false;
        $this->finalForm->reset();
        $this->reset(['final_report_file', 'final_ppt_file', 'final_research_output_file', 'final_submission_proof_file']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function saveFinal(): void
    {
        $this->finalForm->validate();

        $isEdit = $this->finalReport !== null;

        $this->validate(
            [
                'final_report_file' => [$isEdit ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:10240'],
                'final_ppt_file' => [$isEdit ? 'nullable' : 'required', 'file', 'mimes:ppt,pptx', 'max:20480'],
                'final_research_output_file' => [$isEdit ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
                'final_submission_proof_file' => [$isEdit ? 'nullable' : 'required', 'image', 'max:5120'],
            ],
            [
                'final_report_file.required' => 'File laporan wajib diunggah.',
                'final_ppt_file.required' => 'File presentasi wajib diunggah.',
                'final_research_output_file.required' => 'File output penelitian wajib diunggah.',
                'final_submission_proof_file.required' => 'Bukti submit wajib diunggah.',
            ],
        );

        if (!$isEdit) {
            if ($this->progressReport?->status !== 'accepted') {
                Flux::toast('Progress Report harus di-accept dulu.', variant: 'danger');
                return;
            }
            if ($this->proposal->finalReport) {
                Flux::toast('Final report sudah ada.', variant: 'danger');
                return;
            }
        }

        if ($this->final_report_file) {
            $this->finalForm->report_path = $this->final_report_file->store('final-reports/reports', 'public');
        }
        if ($this->final_ppt_file) {
            $this->finalForm->ppt_path = $this->final_ppt_file->store('final-reports/ppt', 'public');
        }
        if ($this->final_research_output_file) {
            $this->finalForm->research_output = $this->final_research_output_file->store('final-reports/output', 'public');
        }
        if ($this->final_submission_proof_file) {
            $this->finalForm->submission_proof = $this->final_submission_proof_file->store('final-reports/proofs', 'public');
        }

        if ($isEdit) {
            $this->finalForm->status = 'pending';
            $this->finalForm->update();
            Flux::toast('Final Report berhasil diperbarui.', variant: 'success');
        } else {
            $this->finalForm->create();
            Flux::toast('Final Report berhasil diunggah.', variant: 'success');
        }

        $this->showFinalForm = false;
        $this->reload();
    }

    // ═══════════════ OUTPUT ACTIONS ═══════════════

    public function showUploadOutput(): void
    {
        if ($this->output) {
            return;
        }
        if (!$this->isOutputUnlocked()) {
            Flux::toast('Final Report harus di-accept dulu.', variant: 'danger');
            return;
        }

        $this->outputForm->reset();
        $this->outputForm->proposal_id = $this->proposal_id;
        $this->outputForm->status = 'pending';
        $this->showOutputForm = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function showEditOutput(): void
    {
        if (!$this->output) {
            return;
        }
        $this->outputForm->setOutput($this->output);
        $this->showOutputForm = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function cancelOutputForm(): void
    {
        $this->showOutputForm = false;
        $this->outputForm->reset();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function saveOutput(): void
    {
        $this->outputForm->validate();

        $isEdit = $this->output !== null;

        if (!$isEdit) {
            if ($this->finalReport?->status !== 'accepted') {
                Flux::toast('Final Report harus di-accept dulu.', variant: 'danger');
                return;
            }
            if ($this->proposal->output) {
                Flux::toast('Output sudah ada.', variant: 'danger');
                return;
            }
        }

        if ($isEdit) {
            $this->outputForm->status = 'pending';
            $this->outputForm->update();
            Flux::toast('Output berhasil diperbarui.', variant: 'success');
        } else {
            $this->outputForm->create();
            Flux::toast('Output berhasil diunggah.', variant: 'success');
        }

        $this->showOutputForm = false;
        $this->reload();
    }
    public function levelMeta(?string $level): array
    {
        return match ($level) {
            'Scopus'  => ['class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300', 'icon' => 'star'],
            'Sinta 1' => ['class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'icon' => 'star'],
            'Sinta 2' => ['class' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300', 'icon' => 'star'],
            'Sinta 3' => ['class' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300', 'icon' => 'star'],
            'Sinta 4' => ['class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', 'icon' => 'star'],
            'Sinta 5' => ['class' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300', 'icon' => 'star'],
            'Sinta 6' => ['class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'icon' => 'star'],
            default   => ['class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300', 'icon' => 'star'],
        };
    }
};
?>

<div x-data="{
    show: false,
    init() {
        window.addEventListener('show-view-submission-user', () => { this.show = true; });
    }
}" x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-3xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        @if ($proposal)
            {{-- ══════════ HEADER ══════════ --}}
            <div
                class="shrink-0 bg-gradient-to-r from-slate-700 to-slate-600
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.document-duplicate class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight line-clamp-2">
                                {{ $proposal->title }}
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5">
                                Submission Progress
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- ══════════ TABS ══════════ --}}
            <div class="shrink-0 bg-slate-50 dark:bg-zinc-800/40 border-b border-slate-200 dark:border-zinc-700">
                <div class="flex items-center gap-1 px-3 sm:px-4 pt-2 overflow-x-auto">

                    {{-- Tab: Progress Report --}}
                    <button type="button" wire:click="switchTab('progress_report')" @class([
                        'inline-flex items-center gap-1.5 px-3 py-2 rounded-t-lg text-[11px] font-semibold transition-all whitespace-nowrap',
                        'bg-white dark:bg-zinc-900 text-violet-700 dark:text-violet-300 border-x border-t border-slate-200 dark:border-zinc-700' =>
                            $activeTab === 'progress_report',
                        'text-slate-500 dark:text-zinc-400 hover:text-slate-700 dark:hover:text-zinc-200' =>
                            $activeTab !== 'progress_report',
                    ])>
                        <span
                            class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-bold
                                     bg-violet-100 text-violet-700
                                     dark:bg-violet-900/40 dark:text-violet-300">1</span>
                        <span>Progress Report</span>
                        @if ($progressReport)
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ $progressReport->status === 'accepted' ? 'bg-emerald-500' : ($progressReport->status === 'rejected' ? 'bg-rose-500' : 'bg-amber-500') }}"></span>
                        @endif
                    </button>

                    {{-- Tab: Final Report --}}
                    <button type="button" wire:click="switchTab('final_report')" @class([
                        'inline-flex items-center gap-1.5 px-3 py-2 rounded-t-lg text-[11px] font-semibold transition-all whitespace-nowrap',
                        'bg-white dark:bg-zinc-900 text-blue-700 dark:text-blue-300 border-x border-t border-slate-200 dark:border-zinc-700' =>
                            $activeTab === 'final_report',
                        'text-slate-500 dark:text-zinc-400 hover:text-slate-700 dark:hover:text-zinc-200' =>
                            $activeTab !== 'final_report',
                        'opacity-40 cursor-not-allowed' => !$this->isFinalReportUnlocked(),
                    ])>
                        <span
                            class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-bold
                                     bg-blue-100 text-blue-700
                                     dark:bg-blue-900/40 dark:text-blue-300">2</span>
                        <span>Final Report</span>
                        @if (!$this->isFinalReportUnlocked())
                            <flux:icon.lock-closed class="size-3" />
                        @elseif ($finalReport)
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ $finalReport->status === 'accepted' ? 'bg-emerald-500' : ($finalReport->status === 'rejected' ? 'bg-rose-500' : 'bg-amber-500') }}"></span>
                        @endif
                    </button>

                    {{-- Tab: Output --}}
                    <button type="button" wire:click="switchTab('output')" @class([
                        'inline-flex items-center gap-1.5 px-3 py-2 rounded-t-lg text-[11px] font-semibold transition-all whitespace-nowrap',
                        'bg-white dark:bg-zinc-900 text-amber-700 dark:text-amber-300 border-x border-t border-slate-200 dark:border-zinc-700' =>
                            $activeTab === 'output',
                        'text-slate-500 dark:text-zinc-400 hover:text-slate-700 dark:hover:text-zinc-200' =>
                            $activeTab !== 'output',
                        'opacity-40 cursor-not-allowed' => !$this->isOutputUnlocked(),
                    ])>
                        <span
                            class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-bold
                                     bg-amber-100 text-amber-700
                                     dark:bg-amber-900/40 dark:text-amber-300">3</span>
                        <span>Output</span>
                        @if (!$this->isOutputUnlocked())
                            <flux:icon.lock-closed class="size-3" />
                        @elseif ($output)
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ $output->status === 'accepted' ? 'bg-emerald-500' : ($output->status === 'rejected' ? 'bg-rose-500' : 'bg-amber-500') }}"></span>
                        @endif
                    </button>

                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                {{-- ═══════════════════════════════════════════════ --}}
                {{-- TAB 1: PROGRESS REPORT                          --}}
                {{-- ═══════════════════════════════════════════════ --}}
                @if ($activeTab === 'progress_report')
                    @if (!$progressReport && !$showProgressForm)
                        {{-- Empty: belum ada, belum upload --}}
                        <div class="flex flex-col items-center justify-center py-12">
                            <div
                                class="w-16 h-16 rounded-2xl bg-violet-100 dark:bg-violet-900/30
                                        flex items-center justify-center mb-4">
                                <flux:icon.document-chart-bar class="size-8 text-violet-600 dark:text-violet-400" />
                            </div>
                            <p class="text-[13px] font-semibold text-slate-700 dark:text-zinc-300">
                                Progress Report Belum Diunggah
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1 text-center max-w-xs">
                                @if ($proposal->status === 'accepted')
                                    Silakan unggah progress report penelitian Anda.
                                @else
                                    Progress report dapat diunggah setelah proposal di-accept oleh reviewer.
                                @endif
                            </p>

                            @if ($proposal->status === 'accepted')
                                <flux:button type="button" wire:click="showUploadProgress" variant="primary"
                                    size="sm" icon="plus" class="mt-4">
                                    Upload Progress Report
                                </flux:button>
                            @endif
                        </div>
                    @elseif ($showProgressForm)
                        {{-- Form upload/edit --}}
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-[13px] font-heading font-semibold text-slate-900 dark:text-white">
                                    {{ $progressReport ? 'Edit Progress Report' : 'Upload Progress Report' }}
                                </h4>
                            </div>

                            <div>
                                <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                    Ringkasan <span class="text-rose-500">*</span>
                                </label>
                                <textarea wire:model="progressForm.summary" rows="3" placeholder="Ringkasan progres..."
                                    class="block w-full rounded-md shadow-sm text-[12px]
                                           border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:border-violet-500 focus:ring-violet-500 py-2 px-3"></textarea>
                                @error('progressForm.summary')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                    Kata Kunci <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" wire:model="progressForm.keyword"
                                    class="block w-full rounded-md shadow-sm text-[12px]
                                           border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:border-violet-500 focus:ring-violet-500 py-2 px-3" />
                                @error('progressForm.keyword')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                        File Laporan (PDF) <span
                                            class="text-rose-500">{{ $progressReport ? '' : '*' }}</span>
                                    </label>
                                    <input type="file" wire:model="progress_report_file" accept=".pdf"
                                        class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                               file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                               file:text-[11px] file:font-medium file:bg-violet-50 file:text-violet-700" />
                                    @if ($progressReport)
                                        <p class="mt-1 text-[10px] text-slate-500">File:
                                            {{ basename($progressReport->report_path) }}</p>
                                    @endif
                                    @error('progress_report_file')
                                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                        File Presentasi (PPT) <span
                                            class="text-rose-500">{{ $progressReport ? '' : '*' }}</span>
                                    </label>
                                    <input type="file" wire:model="progress_ppt_file" accept=".ppt,.pptx"
                                        class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                               file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                               file:text-[11px] file:font-medium file:bg-violet-50 file:text-violet-700" />
                                    @if ($progressReport)
                                        <p class="mt-1 text-[10px] text-slate-500">File:
                                            {{ basename($progressReport->ppt_path) }}</p>
                                    @endif
                                    @error('progress_ppt_file')
                                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <flux:button type="button" wire:click="cancelProgressForm" variant="ghost"
                                    size="sm">
                                    Batal
                                </flux:button>
                                <flux:button type="button" wire:click="saveProgress" variant="primary" size="sm"
                                    wire:loading.attr="disabled" wire:target="saveProgress">
                                    <span wire:loading.remove wire:target="saveProgress">
                                        {{ $progressReport ? 'Update' : 'Upload' }}
                                    </span>
                                    <span wire:loading.flex wire:target="saveProgress">Menyimpan...</span>
                                </flux:button>
                            </div>
                        </div>
                    @else
                        {{-- Detail view (read-only / bisa edit) --}}
                        @php $meta = $progressReport->statusMeta(); @endphp

                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="text-[10px] text-slate-500 dark:text-zinc-400">
                                    {{ $progressReport->created_at?->format('d M Y, H:i') }}
                                </span>
                            </div>

                            @if (in_array($progressReport->status, ['pending', 'revised']))
                                <flux:button type="button" wire:click="showEditProgress" size="xs" variant="ghost"
                                    icon="pencil-square">
                                    Edit
                                </flux:button>
                            @endif
                        </div>

                        {{-- Meta --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="rounded-lg border border-slate-200 dark:border-zinc-700
                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                                <p
                                    class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                    Reviewer</p>
                                <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-0.5">
                                    {{ $progressReport->reviewer?->full_name ?? 'Belum di-assign' }}
                                </p>
                            </div>
                            <div
                                class="rounded-lg border border-slate-200 dark:border-zinc-700
                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                                <p
                                    class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                    Keyword</p>
                                <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-0.5 truncate">
                                    {{ $progressReport->keyword }}
                                </p>
                            </div>
                        </div>

                        {{-- Summary --}}
                        <div>
                            <p
                                class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400 mb-1.5">
                                Ringkasan</p>
                            <div
                                class="p-3 rounded-lg bg-slate-50 dark:bg-zinc-800/40
                    border border-slate-200 dark:border-zinc-700
                    text-[11px] leading-relaxed whitespace-pre-wrap
                    text-slate-700 dark:text-zinc-300">
                                {{ $progressReport->summary }}
                            </div>
                        </div>

                        {{-- Files --}}
                        <div>
                            <p
                                class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400 mb-2">
                                Files</p>
                            <div class="flex flex-wrap gap-2">
                                @if ($progressReport->report_path)
                                    <a href="{{ Storage::disk('public')->url($progressReport->report_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                           bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20
                           border border-rose-200 dark:border-rose-800
                           text-rose-700 dark:text-rose-300 text-[11px] font-semibold">
                                        <flux:icon.document-text class="size-4" />
                                        Report PDF
                                    </a>
                                @endif
                                @if ($progressReport->ppt_path)
                                    <a href="{{ Storage::disk('public')->url($progressReport->ppt_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                           bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20
                           border border-orange-200 dark:border-orange-800
                           text-orange-700 dark:text-orange-300 text-[11px] font-semibold">
                                        <flux:icon.presentation-chart-bar class="size-4" />
                                        Presentation
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- ══════════ REVIEWER NOTES ══════════ --}}
                        @if ($progressReport->reviewerNotes->count() > 0)
                            <div class="rounded-lg border border-violet-200 dark:border-violet-800 overflow-hidden">
                                <div
                                    class="px-3 py-2.5 bg-violet-50 dark:bg-violet-900/20
                        border-b border-violet-200 dark:border-violet-800
                        flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <flux:icon.clipboard-document-check
                                            class="size-3.5 text-violet-600 dark:text-violet-400" />
                                        <span class="text-[11px] font-semibold text-violet-800 dark:text-violet-300">
                                            Reviewer Notes
                                        </span>
                                        <span
                                            class="text-[9px] font-bold px-1.5 py-0.5 rounded
                                 bg-violet-200 text-violet-800
                                 dark:bg-violet-800 dark:text-violet-200">
                                            {{ $progressReport->reviewerNotes->count() }}
                                        </span>
                                    </span>
                                </div>
                                <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                                    @foreach ($progressReport->reviewerNotes as $note)
                                        <div wire:key="rn-user-{{ $note->id }}"
                                            class="rounded-lg border p-2.5
                            {{ $note->is_approved
                                ? 'bg-emerald-50 dark:bg-emerald-900/15 border-emerald-200 dark:border-emerald-800'
                                : 'bg-amber-50 dark:bg-amber-900/15 border-amber-200 dark:border-amber-800' }}">
                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] font-bold
                                            {{ $note->is_approved
                                                ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                                                : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' }}">
                                                        {{ strtoupper(substr($note->reviewer?->full_name ?? 'R', 0, 1)) }}
                                                    </div>
                                                    <p
                                                        class="text-[10px] font-semibold
                                        {{ $note->is_approved ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-300' }}">
                                                        {{ $note->reviewer?->full_name ?? 'Reviewer' }}
                                                    </p>
                                                    <span
                                                        class="text-[9px] {{ $note->is_approved ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                        {{ $note->created_at?->diffForHumans() }}
                                                    </span>
                                                </div>
                                                <span
                                                    class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase
                                        {{ $note->is_approved
                                            ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                                            : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' }}">
                                                    {{ $note->is_approved ? 'Approved' : 'Revision' }}
                                                </span>
                                            </div>
                                            @if ($note->comment)
                                                <p
                                                    class="text-[11px] whitespace-pre-wrap mt-1
                                    {{ $note->is_approved ? 'text-emerald-900 dark:text-emerald-100' : 'text-amber-900 dark:text-amber-100' }}">
                                                    {{ $note->comment }}
                                                </p>
                                            @endif
                                            @if ($note->recommendation)
                                                <div
                                                    class="mt-1.5 pt-1.5 border-t
                                        {{ $note->is_approved ? 'border-emerald-200 dark:border-emerald-800' : 'border-amber-200 dark:border-amber-800' }}">
                                                    <p
                                                        class="text-[10px]
                                        {{ $note->is_approved ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                                                        <span class="font-semibold">Rekomendasi:</span>
                                                        {{ $note->recommendation }}
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- ══════════ ADMIN NOTES ══════════ --}}
                        @if ($progressReport->adminNotes->count() > 0)
                            <div class="rounded-lg border border-amber-200 dark:border-amber-800 overflow-hidden">
                                <div
                                    class="px-3 py-2.5 bg-amber-50 dark:bg-amber-900/20
                        border-b border-amber-200 dark:border-amber-800
                        flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <flux:icon.chat-bubble-left-right
                                            class="size-3.5 text-amber-600 dark:text-amber-400" />
                                        <span class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                                            Admin Notes
                                        </span>
                                        <span
                                            class="text-[9px] font-bold px-1.5 py-0.5 rounded
                                 bg-amber-200 text-amber-800
                                 dark:bg-amber-800 dark:text-amber-200">
                                            {{ $progressReport->adminNotes->count() }}
                                        </span>
                                    </span>
                                </div>
                                <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                                    @foreach ($progressReport->adminNotes as $note)
                                        <div wire:key="an-prog-{{ $note->id }}"
                                            class="rounded-lg border border-amber-200 dark:border-amber-800
                               bg-amber-50 dark:bg-amber-900/20 p-2.5">
                                            <div class="flex items-center gap-2 mb-1">
                                                <div
                                                    class="w-5 h-5 rounded-full bg-amber-200 dark:bg-amber-800/50
                                        flex items-center justify-center text-[8px] font-bold
                                        text-amber-800 dark:text-amber-300">
                                                    {{ strtoupper(substr($note->admin?->full_name ?? 'A', 0, 1)) }}
                                                </div>
                                                <p
                                                    class="text-[10px] font-semibold text-amber-800 dark:text-amber-300">
                                                    {{ $note->admin?->full_name ?? 'Admin' }}
                                                </p>
                                                <span class="text-[9px] text-amber-600 dark:text-amber-400">
                                                    {{ $note->created_at?->diffForHumans() }}
                                                </span>
                                            </div>
                                            @if ($note->comment)
                                                <p
                                                    class="text-[11px] text-amber-900 dark:text-amber-100 whitespace-pre-wrap">
                                                    {{ $note->comment }}
                                                </p>
                                            @endif
                                            @if ($note->recommendation)
                                                <p class="text-[10px] text-amber-700 dark:text-amber-300 mt-1">
                                                    <span class="font-semibold">Rekomendasi:</span>
                                                    {{ $note->recommendation }}
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                @endif
        @endif

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- TAB 2: FINAL REPORT                             --}}
        {{-- ═══════════════════════════════════════════════ --}}
        @if ($activeTab === 'final_report')

            @if (!$this->isFinalReportUnlocked())
                {{-- Locked --}}
                <div class="flex flex-col items-center justify-center py-12">
                    <div
                        class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-zinc-800
                                        flex items-center justify-center mb-4">
                        <flux:icon.lock-closed class="size-8 text-slate-400 dark:text-zinc-600" />
                    </div>
                    <p class="text-[13px] font-semibold text-slate-700 dark:text-zinc-300">
                        Final Report Terkunci
                    </p>
                    <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1 text-center max-w-xs">
                        Final Report dapat diunggah setelah Progress Report di-accept oleh reviewer.
                    </p>
                </div>
            @elseif (!$finalReport && !$showFinalForm)
                {{-- Empty: unlocked, belum upload --}}
                <div class="flex flex-col items-center justify-center py-12">
                    <div
                        class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/30
                                        flex items-center justify-center mb-4">
                        <flux:icon.document-check class="size-8 text-blue-600 dark:text-blue-400" />
                    </div>
                    <p class="text-[13px] font-semibold text-slate-700 dark:text-zinc-300">
                        Final Report Belum Diunggah
                    </p>
                    <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1 text-center max-w-xs">
                        Silakan unggah laporan akhir penelitian Anda.
                    </p>
                    <flux:button type="button" wire:click="showUploadFinal" variant="primary" size="sm"
                        icon="plus" class="mt-4">
                        Upload Final Report
                    </flux:button>
                </div>
            @elseif ($showFinalForm)
                {{-- Form upload/edit --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-[13px] font-heading font-semibold text-slate-900 dark:text-white">
                            {{ $finalReport ? 'Edit Final Report' : 'Upload Final Report' }}
                        </h4>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Ringkasan <span class="text-rose-500">*</span>
                        </label>
                        <textarea wire:model="finalForm.summary" rows="3"
                            class="block w-full rounded-md shadow-sm text-[12px]
                                           border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:border-blue-500 focus:ring-blue-500 py-2 px-3"></textarea>
                        @error('finalForm.summary')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Kata Kunci <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" wire:model="finalForm.keyword"
                            class="block w-full rounded-md shadow-sm text-[12px]
                                           border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:border-blue-500 focus:ring-blue-500 py-2 px-3" />
                        @error('finalForm.keyword')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                File Laporan (PDF) <span class="text-rose-500">{{ $finalReport ? '' : '*' }}</span>
                            </label>
                            <input type="file" wire:model="final_report_file" accept=".pdf"
                                class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                               file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                               file:text-[11px] file:font-medium file:bg-blue-50 file:text-blue-700" />
                            @if ($finalReport)
                                <p class="mt-1 text-[10px] text-slate-500">File:
                                    {{ basename($finalReport->report_path) }}</p>
                            @endif
                            @error('final_report_file')
                                <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                File Presentasi (PPT) <span class="text-rose-500">{{ $finalReport ? '' : '*' }}</span>
                            </label>
                            <input type="file" wire:model="final_ppt_file" accept=".ppt,.pptx"
                                class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                               file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                               file:text-[11px] file:font-medium file:bg-blue-50 file:text-blue-700" />
                            @if ($finalReport)
                                <p class="mt-1 text-[10px] text-slate-500">File:
                                    {{ basename($finalReport->ppt_path) }}</p>
                            @endif
                            @error('final_ppt_file')
                                <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                Research Output <span class="text-rose-500">{{ $finalReport ? '' : '*' }}</span>
                            </label>
                            <input type="file" wire:model="final_research_output_file" accept=".pdf,.doc,.docx"
                                class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                               file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                               file:text-[11px] file:font-medium file:bg-blue-50 file:text-blue-700" />
                            @if ($finalReport)
                                <p class="mt-1 text-[10px] text-slate-500">File:
                                    {{ basename($finalReport->research_output) }}</p>
                            @endif
                            @error('final_research_output_file')
                                <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                Bukti Submit (Image) <span class="text-rose-500">{{ $finalReport ? '' : '*' }}</span>
                            </label>
                            <input type="file" wire:model="final_submission_proof_file" accept="image/*"
                                class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                               file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                               file:text-[11px] file:font-medium file:bg-blue-50 file:text-blue-700" />
                            @if ($finalReport)
                                <p class="mt-1 text-[10px] text-slate-500">File:
                                    {{ basename($finalReport->submission_proof) }}</p>
                            @endif
                            @error('final_submission_proof_file')
                                <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <flux:button type="button" wire:click="cancelFinalForm" variant="ghost" size="sm">
                            Batal
                        </flux:button>
                        <flux:button type="button" wire:click="saveFinal" variant="primary" size="sm"
                            wire:loading.attr="disabled" wire:target="saveFinal">
                            <span wire:loading.remove wire:target="saveFinal">
                                {{ $finalReport ? 'Update' : 'Upload' }}
                            </span>
                            <span wire:loading.flex wire:target="saveFinal">Menyimpan...</span>
                        </flux:button>
                    </div>
                </div>
            @else
                {{-- Detail view --}}
                @php $meta = $finalReport->statusMeta(); @endphp

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                            {{ $meta['label'] }}
                        </span>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-400">
                            {{ $finalReport->created_at?->format('d M Y, H:i') }}
                        </span>
                    </div>

                    @if (in_array($finalReport->status, ['pending', 'revised']))
                        <flux:button type="button" wire:click="showEditFinal" size="xs" variant="ghost"
                            icon="pencil-square">
                            Edit
                        </flux:button>
                    @endif
                </div>

                <div>
                    <p
                        class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400 mb-1.5">
                        Ringkasan</p>
                    <div
                        class="p-3 rounded-lg bg-slate-50 dark:bg-zinc-800/40
                    border border-slate-200 dark:border-zinc-700
                    text-[11px] leading-relaxed whitespace-pre-wrap
                    text-slate-700 dark:text-zinc-300">
                        {{ $finalReport->summary }}
                    </div>
                </div>

                <div>
                    <p
                        class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400 mb-2">
                        Files</p>
                    <div class="flex flex-wrap gap-2">
                        @if ($finalReport->report_path)
                            <a href="{{ Storage::disk('public')->url($finalReport->report_path) }}" target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                           bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20
                           border border-rose-200 dark:border-rose-800
                           text-rose-700 dark:text-rose-300 text-[11px] font-semibold">
                                <flux:icon.document-text class="size-4" />
                                Report
                            </a>
                        @endif
                        @if ($finalReport->ppt_path)
                            <a href="{{ Storage::disk('public')->url($finalReport->ppt_path) }}" target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                           bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20
                           border border-orange-200 dark:border-orange-800
                           text-orange-700 dark:text-orange-300 text-[11px] font-semibold">
                                <flux:icon.presentation-chart-bar class="size-4" />
                                Presentation
                            </a>
                        @endif
                        @if ($finalReport->research_output)
                            <a href="{{ Storage::disk('public')->url($finalReport->research_output) }}"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                           bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20
                           border border-blue-200 dark:border-blue-800
                           text-blue-700 dark:text-blue-300 text-[11px] font-semibold">
                                <flux:icon.document-arrow-down class="size-4" />
                                Research Output
                            </a>
                        @endif
                        @if ($finalReport->submission_proof)
                            <a href="{{ Storage::disk('public')->url($finalReport->submission_proof) }}"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                           bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20
                           border border-emerald-200 dark:border-emerald-800
                           text-emerald-700 dark:text-emerald-300 text-[11px] font-semibold">
                                <flux:icon.photo class="size-4" />
                                Bukti Submit
                            </a>
                        @endif
                    </div>
                </div>

                {{-- ══════════ ADMIN NOTES ══════════ --}}
                @if ($finalReport->adminNotes->count() > 0)
                    <div class="rounded-lg border border-amber-200 dark:border-amber-800 overflow-hidden">
                        <div
                            class="px-3 py-2.5 bg-amber-50 dark:bg-amber-900/20
                        border-b border-amber-200 dark:border-amber-800
                        flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <flux:icon.chat-bubble-left-right
                                    class="size-3.5 text-amber-600 dark:text-amber-400" />
                                <span class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                                    Admin Notes
                                </span>
                                <span
                                    class="text-[9px] font-bold px-1.5 py-0.5 rounded
                                 bg-amber-200 text-amber-800
                                 dark:bg-amber-800 dark:text-amber-200">
                                    {{ $finalReport->adminNotes->count() }}
                                </span>
                            </span>
                        </div>
                        <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                            @foreach ($finalReport->adminNotes as $note)
                                <div wire:key="an-final-{{ $note->id }}"
                                    class="rounded-lg border border-amber-200 dark:border-amber-800
                               bg-amber-50 dark:bg-amber-900/20 p-2.5">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div
                                            class="w-5 h-5 rounded-full bg-amber-200 dark:bg-amber-800/50
                                        flex items-center justify-center text-[8px] font-bold
                                        text-amber-800 dark:text-amber-300">
                                            {{ strtoupper(substr($note->admin?->full_name ?? 'A', 0, 1)) }}
                                        </div>
                                        <p class="text-[10px] font-semibold text-amber-800 dark:text-amber-300">
                                            {{ $note->admin?->full_name ?? 'Admin' }}
                                        </p>
                                        <span class="text-[9px] text-amber-600 dark:text-amber-400">
                                            {{ $note->created_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                    @if ($note->comment)
                                        <p class="text-[11px] text-amber-900 dark:text-amber-100 whitespace-pre-wrap">
                                            {{ $note->comment }}
                                        </p>
                                    @endif
                                    @if ($note->recommendation)
                                        <p class="text-[10px] text-amber-700 dark:text-amber-300 mt-1">
                                            <span class="font-semibold">Rekomendasi:</span>
                                            {{ $note->recommendation }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        @endif

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- TAB 3: OUTPUT                                    --}}
        {{-- ═══════════════════════════════════════════════ --}}
        @if ($activeTab === 'output')

            @if (!$this->isOutputUnlocked())
                {{-- Locked --}}
                <div class="flex flex-col items-center justify-center py-12">
                    <div
                        class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-zinc-800
                                        flex items-center justify-center mb-4">
                        <flux:icon.lock-closed class="size-8 text-slate-400 dark:text-zinc-600" />
                    </div>
                    <p class="text-[13px] font-semibold text-slate-700 dark:text-zinc-300">
                        Output Terkunci
                    </p>
                    <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1 text-center max-w-xs">
                        Output dapat diunggah setelah Final Report di-accept oleh admin.
                    </p>
                </div>
            @elseif (!$output && !$showOutputForm)
                {{-- Empty: unlocked, belum upload --}}
                <div class="flex flex-col items-center justify-center py-12">
                    <div
                        class="w-16 h-16 rounded-2xl bg-amber-100 dark:bg-amber-900/30
                                        flex items-center justify-center mb-4">
                        <flux:icon.trophy class="size-8 text-amber-600 dark:text-amber-400" />
                    </div>
                    <p class="text-[13px] font-semibold text-slate-700 dark:text-zinc-300">
                        Output Belum Diunggah
                    </p>
                    <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1 text-center max-w-xs">
                        Silakan unggah luaran penelitian Anda.
                    </p>
                    <flux:button type="button" wire:click="showUploadOutput" variant="primary" size="sm"
                        icon="plus" class="mt-4">
                        Upload Output
                    </flux:button>
                </div>
            @elseif ($showOutputForm)
                {{-- Form upload/edit --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-[13px] font-heading font-semibold text-slate-900 dark:text-white">
                            {{ $output ? 'Edit Output' : 'Upload Output' }}
                        </h4>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Journal Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" wire:model="outputForm.journal_name"
                            class="block w-full rounded-md shadow-sm text-[12px]
                                           border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:border-amber-500 focus:ring-amber-500 py-2 px-3" />
                        @error('outputForm.journal_name')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Journal Link <span class="text-rose-500">*</span>
                        </label>
                        <input type="url" wire:model="outputForm.journal_link"
                            class="block w-full rounded-md shadow-sm text-[12px]
                                           border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:border-amber-500 focus:ring-amber-500 py-2 px-3" />
                        @error('outputForm.journal_link')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                Edition <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="outputForm.edition"
                                class="block w-full rounded-md shadow-sm text-[12px]
                                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                               text-slate-900 dark:text-zinc-100
                                               focus:border-amber-500 focus:ring-amber-500 py-2 px-3" />
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                Volume <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="outputForm.volume"
                                class="block w-full rounded-md shadow-sm text-[12px]
                                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                               text-slate-900 dark:text-zinc-100
                                               focus:border-amber-500 focus:ring-amber-500 py-2 px-3" />
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                Level <span class="text-rose-500">*</span>
                            </label>
                            <select wire:model="outputForm.level"
                                class="block w-full rounded-md shadow-sm text-[12px]
                                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                               text-slate-900 dark:text-zinc-100
                                               focus:border-amber-500 focus:ring-amber-500 py-2 px-3">
                                <option value="">— Pilih —</option>
                                <option value="Scopus">Scopus</option>
                                <option value="Sinta 1">Sinta 1</option>
                                <option value="Sinta 2">Sinta 2</option>
                                <option value="Sinta 3">Sinta 3</option>
                                <option value="Sinta 4">Sinta 4</option>
                                <option value="Sinta 5">Sinta 5</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <flux:button type="button" wire:click="cancelOutputForm" variant="ghost" size="sm">
                            Batal
                        </flux:button>
                        <flux:button type="button" wire:click="saveOutput" variant="primary" size="sm"
                            wire:loading.attr="disabled" wire:target="saveOutput">
                            <span wire:loading.remove wire:target="saveOutput">
                                {{ $output ? 'Update' : 'Upload' }}
                            </span>
                            <span wire:loading.flex wire:target="saveOutput">Menyimpan...</span>
                        </flux:button>
                    </div>
                </div>
            @else
                {{-- Detail view --}}
                @php $meta = $output->statusMeta(); @endphp

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                            {{ $meta['label'] }}
                        </span>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-400">
                            {{ $output->created_at?->format('d M Y, H:i') }}
                        </span>
                    </div>

                    @if (in_array($output->status, ['pending', 'revised']))
                        <flux:button type="button" wire:click="showEditOutput" size="xs" variant="ghost"
                            icon="pencil-square">
                            Edit
                        </flux:button>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div
                        class="rounded-lg border border-slate-200 dark:border-zinc-700
                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                            Journal</p>
                        <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-0.5">
                            {{ $output->journal_name }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-slate-200 dark:border-zinc-700
                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                            Level</p>
                        @php $levelMeta = $this->levelMeta($output->level); @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold mt-0.5
                                    {{ $levelMeta['class'] }}">
                            <flux:icon.star class="size-2.5" />
                            {{ $output->level }}
                        </span>
                    </div>
                    <div
                        class="rounded-lg border border-slate-200 dark:border-zinc-700
                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                            Edition</p>
                        <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-0.5">
                            {{ $output->edition }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-slate-200 dark:border-zinc-700
                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                            Volume</p>
                        <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-0.5">
                            {{ $output->volume }}
                        </p>
                    </div>
                </div>

                <div>
                    <p
                        class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400 mb-2">
                        Link</p>
                    <a href="{{ $output->journal_link }}" target="_blank"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                   bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20
                   border border-blue-200 dark:border-blue-800
                   text-blue-700 dark:text-blue-300 text-[11px] font-semibold">
                        <flux:icon.arrow-up-right class="size-4" />
                        View Journal
                    </a>
                </div>

                {{-- ══════════ ADMIN NOTES ══════════ --}}
                @if ($output->adminNotes->count() > 0)
                    <div class="rounded-lg border border-amber-200 dark:border-amber-800 overflow-hidden">
                        <div
                            class="px-3 py-2.5 bg-amber-50 dark:bg-amber-900/20
                        border-b border-amber-200 dark:border-amber-800
                        flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <flux:icon.chat-bubble-left-right
                                    class="size-3.5 text-amber-600 dark:text-amber-400" />
                                <span class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                                    Admin Notes
                                </span>
                                <span
                                    class="text-[9px] font-bold px-1.5 py-0.5 rounded
                                 bg-amber-200 text-amber-800
                                 dark:bg-amber-800 dark:text-amber-200">
                                    {{ $output->adminNotes->count() }}
                                </span>
                            </span>
                        </div>
                        <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                            @foreach ($output->adminNotes as $note)
                                <div wire:key="an-output-{{ $note->id }}"
                                    class="rounded-lg border border-amber-200 dark:border-amber-800
                               bg-amber-50 dark:bg-amber-900/20 p-2.5">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div
                                            class="w-5 h-5 rounded-full bg-amber-200 dark:bg-amber-800/50
                                        flex items-center justify-center text-[8px] font-bold
                                        text-amber-800 dark:text-amber-300">
                                            {{ strtoupper(substr($note->admin?->full_name ?? 'A', 0, 1)) }}
                                        </div>
                                        <p class="text-[10px] font-semibold text-amber-800 dark:text-amber-300">
                                            {{ $note->admin?->full_name ?? 'Admin' }}
                                        </p>
                                        <span class="text-[9px] text-amber-600 dark:text-amber-400">
                                            {{ $note->created_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                    @if ($note->comment)
                                        <p class="text-[11px] text-amber-900 dark:text-amber-100 whitespace-pre-wrap">
                                            {{ $note->comment }}
                                        </p>
                                    @endif
                                    @if ($note->recommendation)
                                        <p class="text-[10px] text-amber-700 dark:text-amber-300 mt-1">
                                            <span class="font-semibold">Rekomendasi:</span>
                                            {{ $note->recommendation }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        @endif
    </div>

    {{-- ══════════ FOOTER ══════════ --}}
    <div
        class="shrink-0 flex justify-end
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">
        <flux:button type="button" @click="show = false" variant="ghost" size="sm">
            Tutup
        </flux:button>
    </div>

</div>
</div>
