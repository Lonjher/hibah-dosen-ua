<?php

use App\Livewire\Forms\FinalReportForm;
use App\Livewire\Forms\OutcomeForm;
use App\Livewire\Forms\ProgressReportForm;
use App\Models\FinalReport;
use App\Models\Outcome;
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
    public ?outcome $outcome = null;

    // Forms
    public ProgressReportForm $progressForm;
    public FinalReportForm $finalForm;
    public outcomeForm $outcomeForm;

    // Files untuk upload
    public $progress_report_file = null;
    public $progress_ppt_file = null;

    public $final_report_file = null;
    public $final_ppt_file = null;
    public $final_research_outcome_file = null;
    public $final_submission_proof_file = null;

    // Mode form: show/hide upload form per tab
    public bool $showProgressForm = false;
    public bool $showFinalForm = false;
    public bool $showoutcomeForm = false;

    #[On('open-view-submission-user')]
    public function load(int $proposalId): void
    {
        $this->proposal_id = $proposalId;
        $this->reload();

        $this->activeTab = 'progress_report';
        $this->showProgressForm = false;
        $this->showFinalForm = false;
        $this->showoutcomeForm = false;

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

        if ($tab === 'outcome' && $this->finalReport?->status !== 'accepted') {
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

        $this->outcome = outcome::with([
            'adminNotes' => fn($q) => $q->latest()->with('admin'),
        ])
            ->where('proposal_id', $this->proposal_id)
            ->first();
    }

    protected function setupForms(): void
    {
        $this->progressForm->reset();
        $this->finalForm->reset();
        $this->outcomeForm->reset();

        $this->reset(['progress_report_file', 'progress_ppt_file', 'final_report_file', 'final_ppt_file', 'final_research_outcome_file', 'final_submission_proof_file']);
    }

    // ═══════════════ TAB UNLOCK CHECK ═══════════════

    public function isFinalReportUnlocked(): bool
    {
        return $this->progressReport?->status === 'accepted';
    }

    public function isoutcomeUnlocked(): bool
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
        $this->reset(['final_report_file', 'final_ppt_file', 'final_research_outcome_file', 'final_submission_proof_file']);
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
        $this->reset(['final_report_file', 'final_ppt_file', 'final_research_outcome_file', 'final_submission_proof_file']);
        $this->showFinalForm = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function cancelFinalForm(): void
    {
        $this->showFinalForm = false;
        $this->finalForm->reset();
        $this->reset(['final_report_file', 'final_ppt_file', 'final_research_outcome_file', 'final_submission_proof_file']);
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
                'final_research_outcome_file' => [$isEdit ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
                'final_submission_proof_file' => [$isEdit ? 'nullable' : 'required', 'image', 'max:5120'],
            ],
            [
                'final_report_file.required' => 'File laporan wajib diunggah.',
                'final_ppt_file.required' => 'File presentasi wajib diunggah.',
                'final_research_outcome_file.required' => 'File outcome penelitian wajib diunggah.',
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
        if ($this->final_research_outcome_file) {
            $this->finalForm->research_outcome = $this->final_research_outcome_file->store('final-reports/outcome', 'public');
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

    // ═══════════════ outcome ACTIONS ═══════════════

    public function showUploadoutcome(): void
    {
        if ($this->outcome) {
            return;
        }
        if (!$this->isoutcomeUnlocked()) {
            Flux::toast('Final Report harus di-accept dulu.', variant: 'danger');
            return;
        }

        $this->outcomeForm->reset();
        $this->outcomeForm->proposal_id = $this->proposal_id;
        $this->outcomeForm->status = 'pending';
        $this->showoutcomeForm = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function showEditoutcome(): void
    {
        if (!$this->outcome) {
            return;
        }
        $this->outcomeForm->setoutcome($this->outcome);
        $this->showoutcomeForm = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function canceloutcomeForm(): void
    {
        $this->showoutcomeForm = false;
        $this->outcomeForm->reset();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function saveoutcome(): void
    {
        $this->outcomeForm->validate();

        $isEdit = $this->outcome !== null;

        if (!$isEdit) {
            if ($this->finalReport?->status !== 'accepted') {
                Flux::toast('Final Report harus di-accept dulu.', variant: 'danger');
                return;
            }
            if ($this->proposal->outcome) {
                Flux::toast('outcome sudah ada.', variant: 'danger');
                return;
            }
        }

        if ($isEdit) {
            $this->outcomeForm->status = 'pending';
            $this->outcomeForm->update();
            Flux::toast('outcome berhasil diperbarui.', variant: 'success');
        } else {
            $this->outcomeForm->create();
            Flux::toast('outcome berhasil diunggah.', variant: 'success');
        }

        $this->showoutcomeForm = false;
        $this->reload();
    }
    public function levelMeta(?string $level): array
    {
        return match ($level) {
            'Scopus' => ['class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300', 'icon' => 'star'],
            'Sinta 1' => ['class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'icon' => 'star'],
            'Sinta 2' => ['class' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300', 'icon' => 'star'],
            'Sinta 3' => ['class' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300', 'icon' => 'star'],
            'Sinta 4' => ['class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', 'icon' => 'star'],
            'Sinta 5' => ['class' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300', 'icon' => 'star'],
            'Sinta 6' => ['class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'icon' => 'star'],
            default => ['class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300', 'icon' => 'star'],
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
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
                border border-slate-200 dark:border-zinc-700
                hover:shadow-slate-500/15 transition-shadow duration-300"
        @click.stop>

        @if ($proposal)
            {{-- ══════════ HEADER ══════════ --}}
            <div
                class="shrink-0 bg-gradient-to-r from-slate-700 to-slate-600
                        px-5 sm:px-6 py-4 rounded-t-3xl sm:rounded-t-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
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
                    <button type="button" @click="show = false" aria-label="Close"
                        class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               hover:scale-110 active:scale-95
                               transition-all duration-150">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- ══════════ TABS ══════════ --}}
            <div class="shrink-0 bg-slate-50 dark:bg-zinc-800/40 border-b border-slate-200 dark:border-zinc-700">
                <div class="flex items-center gap-1.5 px-3 sm:px-4 py-2.5 overflow-x-auto">

                    {{-- Tab: Progress Report --}}
                    <button type="button" wire:click="switchTab('progress_report')" @class([
                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold transition-all whitespace-nowrap',
                        'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 shadow-sm shadow-violet-500/20' =>
                            $activeTab === 'progress_report',
                        'text-slate-500 dark:text-zinc-400 hover:text-slate-700 dark:hover:text-zinc-200 hover:bg-slate-100 dark:hover:bg-zinc-800/60' =>
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
                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold transition-all whitespace-nowrap',
                        'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 shadow-sm shadow-blue-500/20' =>
                            $activeTab === 'final_report',
                        'text-slate-500 dark:text-zinc-400 hover:text-slate-700 dark:hover:text-zinc-200 hover:bg-slate-100 dark:hover:bg-zinc-800/60' =>
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

                    {{-- Tab: outcome --}}
                    <button type="button" wire:click="switchTab('outcome')" @class([
                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold transition-all whitespace-nowrap',
                        'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 shadow-sm shadow-amber-500/20' =>
                            $activeTab === 'outcome',
                        'text-slate-500 dark:text-zinc-400 hover:text-slate-700 dark:hover:text-zinc-200 hover:bg-slate-100 dark:hover:bg-zinc-800/60' =>
                            $activeTab !== 'outcome',
                        'opacity-40 cursor-not-allowed' => !$this->isoutcomeUnlocked(),
                    ])>
                        <span
                            class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-bold
                         bg-amber-100 text-amber-700
                         dark:bg-amber-900/40 dark:text-amber-300">3</span>
                        <span>outcome</span>
                        @if (!$this->isoutcomeUnlocked())
                            <flux:icon.lock-closed class="size-3" />
                        @elseif ($outcome)
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ $outcome->status === 'accepted' ? 'bg-emerald-500' : ($outcome->status === 'rejected' ? 'bg-rose-500' : 'bg-amber-500') }}"></span>
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
                        {{-- Empty --}}
                        <div class="flex flex-col items-center justify-center py-12">
                            <div
                                class="w-16 h-16 rounded-3xl bg-violet-100 dark:bg-violet-900/30
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
                                <button type="button" wire:click="showUploadProgress"
                                    class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                                           bg-violet-600/90 hover:bg-violet-600
                                           shadow-sm shadow-violet-500/20 hover:shadow-sm hover:shadow-violet-500/30
                                           hover:scale-[1.02] active:scale-[0.97]
                                           transition-all duration-150">
                                    <flux:icon.plus class="size-3.5" />
                                    Upload Progress Report
                                </button>
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
                                <x-textarea wire:model="progressForm.summary" label="Ringkasan" required rows="3"
                                    rounded="full" color="violet" placeholder="Ringkasan progres..." />
                                @error('progressForm.summary')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-input wire:model="progressForm.keyword" label="Kata Kunci" required rounded="full" />
                                @error('progressForm.keyword')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <x-input type="file" wire:model="progress_report_file" label="File Laporan (PDF)"
                                        accept=".pdf" rounded="full" :required="!$progressReport" />

                                    @if ($progressReport)
                                        <p
                                            class="mt-1 text-[10px] text-slate-500 dark:text-zinc-500 flex items-center gap-1">
                                            <flux:icon.document-text class="size-3 shrink-0" />
                                            File: {{ basename($progressReport->report_path) }}
                                        </p>
                                    @endif
                                    @error('progress_report_file')
                                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <x-input type="file" wire:model="progress_ppt_file" label="File Presentasi (PPT)"
                                        accept=".ppt,.pptx" rounded="full" :required="!$progressReport" />

                                    @if ($progressReport)
                                        <p
                                            class="mt-1 text-[10px] text-slate-500 dark:text-zinc-500 flex items-center gap-1">
                                            <flux:icon.document-text class="size-3 shrink-0" />
                                            File: {{ basename($progressReport->ppt_path) }}
                                        </p>
                                    @endif
                                    @error('progress_ppt_file')
                                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex justify-end gap-1.5 pt-2">
                                <button type="button" wire:click="cancelProgressForm"
                                    class="px-3 py-1.5 text-[11px] font-medium rounded-full
                                           text-slate-700 dark:text-zinc-300
                                           bg-white dark:bg-zinc-800
                                           border border-slate-300 dark:border-zinc-600
                                           hover:bg-slate-50 dark:hover:bg-zinc-700
                                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-violet-500/15
                                           hover:scale-[1.02] active:scale-[0.97]
                                           transition-all duration-150">
                                    Batal
                                </button>
                                <button type="button" wire:click="saveProgress" wire:loading.attr="disabled"
                                    wire:target="saveProgress"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                                           bg-violet-600/90 hover:bg-violet-600
                                           shadow-sm shadow-violet-500/20 hover:shadow-sm hover:shadow-violet-500/30
                                           disabled:opacity-60 disabled:cursor-wait
                                           hover:scale-[1.02] active:scale-[0.97] disabled:hover:scale-100
                                           transition-all duration-150">
                                    <svg wire:loading wire:target="saveProgress" class="animate-spin size-3"
                                        viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4" opacity=".25" />
                                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4"
                                            stroke-linecap="round" />
                                    </svg>
                                    <span wire:loading.remove wire:target="saveProgress">
                                        {{ $progressReport ? 'Update' : 'Upload' }}
                                    </span>
                                    <span wire:loading wire:target="saveProgress">Menyimpan...</span>
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- Detail view --}}
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
                                <button type="button" wire:click="showEditProgress"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[10.5px] font-medium rounded-full
                                           text-slate-600 dark:text-zinc-300
                                           bg-white dark:bg-zinc-800
                                           border border-slate-200 dark:border-zinc-700
                                           hover:bg-slate-50 dark:hover:bg-zinc-700
                                           hover:scale-[1.02] active:scale-[0.97]
                                           transition-all duration-150">
                                    <flux:icon.pencil-square class="size-3" />
                                    Edit
                                </button>
                            @endif
                        </div>

                        {{-- Meta --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                        bg-slate-50 dark:bg-zinc-800/40 p-3">
                                <p
                                    class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                    Reviewer</p>
                                <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-0.5">
                                    {{ $progressReport->reviewer?->full_name ?? 'Belum di-assign' }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-200 dark:border-zinc-700
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
                                class="p-3 rounded-2xl bg-slate-50 dark:bg-zinc-800/40
                                        border border-slate-200 dark:border-zinc-700
                                        text-[11px] leading-relaxed
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
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-full
                                               bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/30
                                               border border-rose-200 dark:border-rose-800
                                               text-rose-700 dark:text-rose-300 text-[11px] font-semibold
                                               hover:scale-[1.02] active:scale-[0.97]
                                               transition-all duration-150">
                                        <flux:icon.document-text class="size-4" />
                                        Report PDF
                                    </a>
                                @endif
                                @if ($progressReport->ppt_path)
                                    <a href="{{ Storage::disk('public')->url($progressReport->ppt_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-full
                                               bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:hover:bg-orange-900/30
                                               border border-orange-200 dark:border-orange-800
                                               text-orange-700 dark:text-orange-300 text-[11px] font-semibold
                                               hover:scale-[1.02] active:scale-[0.97]
                                               transition-all duration-150">
                                        <flux:icon.presentation-chart-bar class="size-4" />
                                        Presentation
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- REVIEWER NOTES --}}
                        @if ($progressReport->reviewerNotes->count() > 0)
                            <div class="rounded-2xl border border-violet-200 dark:border-violet-800 overflow-hidden">
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
                                            class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                     bg-violet-200 text-violet-800
                                                     dark:bg-violet-800 dark:text-violet-200">
                                            {{ $progressReport->reviewerNotes->count() }}
                                        </span>
                                    </span>
                                </div>
                                <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                                    @foreach ($progressReport->reviewerNotes as $note)
                                        <div wire:key="rn-user-{{ $note->id }}"
                                            class="rounded-xl border p-2.5
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
                                                    class="text-[9px] font-bold px-1.5 py-0.5 rounded-full uppercase
                                                             {{ $note->is_approved
                                                                 ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                                                                 : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' }}">
                                                    {{ $note->is_approved ? 'Approved' : 'Revision' }}
                                                </span>
                                            </div>
                                            @if ($note->comment)
                                                <p
                                                    class="text-[11px] mt-1
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

                        {{-- ADMIN NOTES --}}
                        @if ($progressReport->adminNotes->count() > 0)
                            <div class="rounded-2xl border border-amber-200 dark:border-amber-800 overflow-hidden">
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
                                            class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                     bg-amber-200 text-amber-800
                                                     dark:bg-amber-800 dark:text-amber-200">
                                            {{ $progressReport->adminNotes->count() }}
                                        </span>
                                    </span>
                                </div>
                                <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                                    @foreach ($progressReport->adminNotes as $note)
                                        <div wire:key="an-prog-{{ $note->id }}"
                                            class="rounded-xl border border-amber-200 dark:border-amber-800
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
                                                <p class="text-[11px] text-amber-900 dark:text-amber-100">
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
                {{-- TAB 2: FINAL REPORT                             --}}
                {{-- ═══════════════════════════════════════════════ --}}
                @if ($activeTab === 'final_report')

                    @if (!$this->isFinalReportUnlocked())
                        <div class="flex flex-col items-center justify-center py-12">
                            <div
                                class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-zinc-800
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
                        <div class="flex flex-col items-center justify-center py-12">
                            <div
                                class="w-16 h-16 rounded-3xl bg-blue-100 dark:bg-blue-900/30
                                        flex items-center justify-center mb-4">
                                <flux:icon.document-check class="size-8 text-blue-600 dark:text-blue-400" />
                            </div>
                            <p class="text-[13px] font-semibold text-slate-700 dark:text-zinc-300">
                                Final Report Belum Diunggah
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1 text-center max-w-xs">
                                Silakan unggah laporan akhir penelitian Anda.
                            </p>
                            <button type="button" wire:click="showUploadFinal"
                                class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                                       bg-blue-600/90 hover:bg-blue-600
                                       shadow-sm shadow-blue-500/20 hover:shadow-sm hover:shadow-blue-500/30
                                       hover:scale-[1.02] active:scale-[0.97]
                                       transition-all duration-150">
                                <flux:icon.plus class="size-3.5" />
                                Upload Final Report
                            </button>
                        </div>
                    @elseif ($showFinalForm)
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-[13px] font-heading font-semibold text-slate-900 dark:text-white">
                                    {{ $finalReport ? 'Edit Final Report' : 'Upload Final Report' }}
                                </h4>
                            </div>

                            <div>
                                <x-textarea wire:model="finalForm.summary" label="Ringkasan" required rows="3"
                                    rounded="full" color="blue" />
                                @error('finalForm.summary')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-input wire:model="finalForm.keyword" label="Kata Kunci" required rounded="full" />
                                @error('finalForm.keyword')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <x-input type="file" wire:model="final_report_file" label="File Laporan (PDF)"
                                        accept=".pdf" rounded="full" :required="!$finalReport" />
                                    @if ($finalReport)
                                        <p
                                            class="mt-1 text-[10px] text-slate-500 dark:text-zinc-500 flex items-center gap-1">
                                            <flux:icon.document-text class="size-3 shrink-0" />
                                            File: {{ basename($finalReport->report_path) }}
                                        </p>
                                    @endif
                                    @error('final_report_file')
                                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <x-input type="file" wire:model="final_ppt_file" label="File Presentasi (PPT)"
                                        accept=".ppt,.pptx" rounded="full" :required="!$finalReport" />
                                    @if ($finalReport)
                                        <p
                                            class="mt-1 text-[10px] text-slate-500 dark:text-zinc-500 flex items-center gap-1">
                                            <flux:icon.document-text class="size-3 shrink-0" />
                                            File: {{ basename($finalReport->ppt_path) }}
                                        </p>
                                    @endif
                                    @error('final_ppt_file')
                                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <x-input type="file" wire:model="final_research_outcome_file"
                                        label="Research outcome" accept=".pdf,.doc,.docx" rounded="full"
                                        :required="!$finalReport" />
                                    @if ($finalReport)
                                        <p
                                            class="mt-1 text-[10px] text-slate-500 dark:text-zinc-500 flex items-center gap-1">
                                            <flux:icon.document-text class="size-3 shrink-0" />
                                            File: {{ basename($finalReport->research_outcome) }}
                                        </p>
                                    @endif
                                    @error('final_research_outcome_file')
                                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <x-input type="file" wire:model="final_submission_proof_file"
                                        label="Bukti Submit (Image)" accept="image/*" rounded="full"
                                        :required="!$finalReport" />
                                    @if ($finalReport)
                                        <p
                                            class="mt-1 text-[10px] text-slate-500 dark:text-zinc-500 flex items-center gap-1">
                                            <flux:icon.document-text class="size-3 shrink-0" />
                                            File: {{ basename($finalReport->submission_proof) }}
                                        </p>
                                    @endif
                                    @error('final_submission_proof_file')
                                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex justify-end gap-1.5 pt-2">
                                <button type="button" wire:click="cancelFinalForm"
                                    class="px-3 py-1.5 text-[11px] font-medium rounded-full
                                           text-slate-700 dark:text-zinc-300
                                           bg-white dark:bg-zinc-800
                                           border border-slate-300 dark:border-zinc-600
                                           hover:bg-slate-50 dark:hover:bg-zinc-700
                                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-blue-500/15
                                           hover:scale-[1.02] active:scale-[0.97]
                                           transition-all duration-150">
                                    Batal
                                </button>
                                <button type="button" wire:click="saveFinal" wire:loading.attr="disabled"
                                    wire:target="saveFinal"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                                           bg-blue-600/90 hover:bg-blue-600
                                           shadow-sm shadow-blue-500/20 hover:shadow-sm hover:shadow-blue-500/30
                                           disabled:opacity-60 disabled:cursor-wait
                                           hover:scale-[1.02] active:scale-[0.97] disabled:hover:scale-100
                                           transition-all duration-150">
                                    <svg wire:loading wire:target="saveFinal" class="animate-spin size-3"
                                        viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4" opacity=".25" />
                                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4"
                                            stroke-linecap="round" />
                                    </svg>
                                    <span wire:loading.remove wire:target="saveFinal">
                                        {{ $finalReport ? 'Update' : 'Upload' }}
                                    </span>
                                    <span wire:loading wire:target="saveFinal">Menyimpan...</span>
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- Detail --}}
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
                                <button type="button" wire:click="showEditFinal"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[10.5px] font-medium rounded-full
                                           text-slate-600 dark:text-zinc-300
                                           bg-white dark:bg-zinc-800
                                           border border-slate-200 dark:border-zinc-700
                                           hover:bg-slate-50 dark:hover:bg-zinc-700
                                           hover:scale-[1.02] active:scale-[0.97]
                                           transition-all duration-150">
                                    <flux:icon.pencil-square class="size-3" />
                                    Edit
                                </button>
                            @endif
                        </div>

                        <div>
                            <p
                                class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400 mb-1.5">
                                Ringkasan</p>
                            <div
                                class="p-3 rounded-2xl bg-slate-50 dark:bg-zinc-800/40
                                        border border-slate-200 dark:border-zinc-700
                                        text-[11px] leading-relaxed
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
                                    <a href="{{ Storage::disk('public')->url($finalReport->report_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-full
                                               bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/30
                                               border border-rose-200 dark:border-rose-800
                                               text-rose-700 dark:text-rose-300 text-[11px] font-semibold
                                               hover:scale-[1.02] active:scale-[0.97] transition-all duration-150">
                                        <flux:icon.document-text class="size-4" />
                                        Report
                                    </a>
                                @endif
                                @if ($finalReport->ppt_path)
                                    <a href="{{ Storage::disk('public')->url($finalReport->ppt_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-full
                                               bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:hover:bg-orange-900/30
                                               border border-orange-200 dark:border-orange-800
                                               text-orange-700 dark:text-orange-300 text-[11px] font-semibold
                                               hover:scale-[1.02] active:scale-[0.97] transition-all duration-150">
                                        <flux:icon.presentation-chart-bar class="size-4" />
                                        Presentation
                                    </a>
                                @endif
                                @if ($finalReport->research_outcome)
                                    <a href="{{ Storage::disk('public')->url($finalReport->research_outcome) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-full
                                               bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30
                                               border border-blue-200 dark:border-blue-800
                                               text-blue-700 dark:text-blue-300 text-[11px] font-semibold
                                               hover:scale-[1.02] active:scale-[0.97] transition-all duration-150">
                                        <flux:icon.document-arrow-down class="size-4" />
                                        Research outcome
                                    </a>
                                @endif
                                @if ($finalReport->submission_proof)
                                    <a href="{{ Storage::disk('public')->url($finalReport->submission_proof) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-full
                                               bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30
                                               border border-emerald-200 dark:border-emerald-800
                                               text-emerald-700 dark:text-emerald-300 text-[11px] font-semibold
                                               hover:scale-[1.02] active:scale-[0.97] transition-all duration-150">
                                        <flux:icon.photo class="size-4" />
                                        Bukti Submit
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if ($finalReport->adminNotes->count() > 0)
                            <div class="rounded-2xl border border-amber-200 dark:border-amber-800 overflow-hidden">
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
                                            class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                     bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200">
                                            {{ $finalReport->adminNotes->count() }}
                                        </span>
                                    </span>
                                </div>
                                <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                                    @foreach ($finalReport->adminNotes as $note)
                                        <div wire:key="an-final-{{ $note->id }}"
                                            class="rounded-xl border border-amber-200 dark:border-amber-800
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
                                                <p class="text-[11px] text-amber-900 dark:text-amber-100">
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
                {{-- TAB 3: outcome                                   --}}
                {{-- ═══════════════════════════════════════════════ --}}
                @if ($activeTab === 'outcome')

                    @if (!$this->isoutcomeUnlocked())
                        <div class="flex flex-col items-center justify-center py-12">
                            <div
                                class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-zinc-800
                                        flex items-center justify-center mb-4">
                                <flux:icon.lock-closed class="size-8 text-slate-400 dark:text-zinc-600" />
                            </div>
                            <p class="text-[13px] font-semibold text-slate-700 dark:text-zinc-300">
                                outcome Terkunci
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1 text-center max-w-xs">
                                outcome dapat diunggah setelah Final Report di-accept oleh admin.
                            </p>
                        </div>
                    @elseif (!$outcome && !$showoutcomeForm)
                        <div class="flex flex-col items-center justify-center py-12">
                            <div
                                class="w-16 h-16 rounded-3xl bg-amber-100 dark:bg-amber-900/30
                                        flex items-center justify-center mb-4">
                                <flux:icon.trophy class="size-8 text-amber-600 dark:text-amber-400" />
                            </div>
                            <p class="text-[13px] font-semibold text-slate-700 dark:text-zinc-300">
                                outcome Belum Diunggah
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1 text-center max-w-xs">
                                Silakan unggah luaran penelitian Anda.
                            </p>
                            <button type="button" wire:click="showUploadoutcome"
                                class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                                       bg-amber-600/90 hover:bg-amber-600
                                       shadow-sm shadow-amber-500/20 hover:shadow-sm hover:shadow-amber-500/30
                                       hover:scale-[1.02] active:scale-[0.97]
                                       transition-all duration-150">
                                <flux:icon.plus class="size-3.5" />
                                Upload outcome
                            </button>
                        </div>
                    @elseif ($showoutcomeForm)
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-[13px] font-heading font-semibold text-slate-900 dark:text-white">
                                    {{ $outcome ? 'Edit outcome' : 'Upload outcome' }}
                                </h4>
                            </div>

                            <div>
                                <x-input wire:model="outcomeForm.journal_name" label="Journal Name" required
                                    rounded="full" />
                                @error('outcomeForm.journal_name')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-input type="url" wire:model="outcomeForm.journal_link" label="Journal Link"
                                    required rounded="full" placeholder="https://..." />
                                @error('outcomeForm.journal_link')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <x-input wire:model="outcomeForm.edition" label="Edition" required
                                        rounded="full" />
                                </div>

                                <div>
                                    <x-input wire:model="outcomeForm.volume" label="Volume" required rounded="full" />
                                </div>

                                <div>
                                    <x-select wire:model="outcomeForm.level" label="Level" required size="lg"
                                        color="amber">
                                        <option value="">— Pilih —</option>
                                        <option value="Scopus">Scopus</option>
                                        <option value="Sinta 1">Sinta 1</option>
                                        <option value="Sinta 2">Sinta 2</option>
                                        <option value="Sinta 3">Sinta 3</option>
                                        <option value="Sinta 4">Sinta 4</option>
                                        <option value="Sinta 5">Sinta 5</option>
                                    </x-select>
                                </div>
                            </div>

                            <div class="flex justify-end gap-1.5 pt-2">
                                <button type="button" wire:click="canceloutcomeForm"
                                    class="px-3 py-1.5 text-[11px] font-medium rounded-full
                                           text-slate-700 dark:text-zinc-300
                                           bg-white dark:bg-zinc-800
                                           border border-slate-300 dark:border-zinc-600
                                           hover:bg-slate-50 dark:hover:bg-zinc-700
                                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-amber-500/15
                                           hover:scale-[1.02] active:scale-[0.97]
                                           transition-all duration-150">
                                    Batal
                                </button>
                                <button type="button" wire:click="saveoutcome" wire:loading.attr="disabled"
                                    wire:target="saveoutcome"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                                           bg-amber-600/90 hover:bg-amber-600
                                           shadow-sm shadow-amber-500/20 hover:shadow-sm hover:shadow-amber-500/30
                                           disabled:opacity-60 disabled:cursor-wait
                                           hover:scale-[1.02] active:scale-[0.97] disabled:hover:scale-100
                                           transition-all duration-150">
                                    <svg wire:loading wire:target="saveoutcome" class="animate-spin size-3"
                                        viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4" opacity=".25" />
                                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4"
                                            stroke-linecap="round" />
                                    </svg>
                                    <span wire:loading.remove wire:target="saveoutcome">
                                        {{ $outcome ? 'Update' : 'Upload' }}
                                    </span>
                                    <span wire:loading wire:target="saveoutcome">Menyimpan...</span>
                                </button>
                            </div>
                        </div>
                    @else
                        @php $meta = $outcome->statusMeta(); @endphp

                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="text-[10px] text-slate-500 dark:text-zinc-400">
                                    {{ $outcome->created_at?->format('d M Y, H:i') }}
                                </span>
                            </div>

                            @if (in_array($outcome->status, ['pending', 'revised']))
                                <button type="button" wire:click="showEditoutcome"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[10.5px] font-medium rounded-full
                                           text-slate-600 dark:text-zinc-300
                                           bg-white dark:bg-zinc-800
                                           border border-slate-200 dark:border-zinc-700
                                           hover:bg-slate-50 dark:hover:bg-zinc-700
                                           hover:scale-[1.02] active:scale-[0.97]
                                           transition-all duration-150">
                                    <flux:icon.pencil-square class="size-3" />
                                    Edit
                                </button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                        bg-slate-50 dark:bg-zinc-800/40 p-3">
                                <p
                                    class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                    Journal</p>
                                <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-0.5">
                                    {{ $outcome->journal_name }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                        bg-slate-50 dark:bg-zinc-800/40 p-3">
                                <p
                                    class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                    Level</p>
                                @php $levelMeta = $this->levelMeta($outcome->level); @endphp
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold mt-0.5
                                             {{ $levelMeta['class'] }}">
                                    <flux:icon.star class="size-2.5" />
                                    {{ $outcome->level }}
                                </span>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                        bg-slate-50 dark:bg-zinc-800/40 p-3">
                                <p
                                    class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                    Edition</p>
                                <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-0.5">
                                    {{ $outcome->edition }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                        bg-slate-50 dark:bg-zinc-800/40 p-3">
                                <p
                                    class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                    Volume</p>
                                <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-0.5">
                                    {{ $outcome->volume }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <p
                                class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400 mb-2">
                                Link</p>
                            <a href="{{ $outcome->journal_link }}" target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-full
                                       bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30
                                       border border-blue-200 dark:border-blue-800
                                       text-blue-700 dark:text-blue-300 text-[11px] font-semibold
                                       hover:scale-[1.02] active:scale-[0.97] transition-all duration-150">
                                <flux:icon.arrow-up-right class="size-4" />
                                View Journal
                            </a>
                        </div>

                        @if ($outcome->adminNotes->count() > 0)
                            <div class="rounded-2xl border border-amber-200 dark:border-amber-800 overflow-hidden">
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
                                            class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                     bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200">
                                            {{ $outcome->adminNotes->count() }}
                                        </span>
                                    </span>
                                </div>
                                <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                                    @foreach ($outcome->adminNotes as $note)
                                        <div wire:key="an-outcome-{{ $note->id }}"
                                            class="rounded-xl border border-amber-200 dark:border-amber-800
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
                                                <p class="text-[11px] text-amber-900 dark:text-amber-100">
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
                        bg-slate-50/50 dark:bg-zinc-900/50 rounded-b-3xl sm:rounded-b-3xl">
                <button type="button" @click="show = false"
                    class="px-3 py-1.5 text-[11px] font-medium rounded-full
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-slate-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Tutup
                </button>
            </div>

        @endif
    </div>
</div>
