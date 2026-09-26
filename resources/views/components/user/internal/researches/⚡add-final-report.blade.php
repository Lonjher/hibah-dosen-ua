<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Proposal;
use App\Models\FinalReport;
use Illuminate\Support\Facades\Storage;
use Flux\Flux;

new class extends Component {
    use WithFileUploads;

    // ── Proposal context ──
    public ?Proposal $proposal = null;
    public string $proposal_title  = '';
    public string $proposal_scheme = '';

    // ── View state ──
    public bool $showFinalForm = false;
    public bool $editMode      = false;
    public ?int $editingId     = null;

    // ── Form fields ──
    public string $finalKeyword = '';
    public string $finalSummary = '';
    public $reportFile        = null;  // PDF
    public $pptFile           = null;  // PPT/PPTX
    public $researchOutputFile = null; // PDF/DOC/DOCX
    public $submissionProofFile = null; // Image

    // ── Existing files ──
    public ?string $existingReportPath          = null;
    public ?string $existingPptPath             = null;
    public ?string $existingResearchOutputPath  = null;
    public ?string $existingSubmissionProofPath = null;

    // ═══════════════ Validation ═══════════════
    protected function rules(): array
    {
        $req = fn(array $rules) => $this->editMode
            ? array_merge(['nullable'], array_slice($rules, 1))
            : $rules;

        return [
            'finalKeyword'        => ['required', 'string', 'max:255'],
            'finalSummary'        => ['required', 'string', 'min:20'],
            'reportFile'          => $req(['required', 'file', 'mimes:pdf', 'max:10240']),
            'pptFile'             => $req(['required', 'file', 'mimes:ppt,pptx', 'max:20480']),
            'researchOutputFile'  => $req(['required', 'file', 'mimes:pdf,doc,docx', 'max:20480']),
            'submissionProofFile' => $req(['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120']),
        ];
    }

    protected function messages(): array
    {
        return [
            'finalKeyword.required'        => 'Keyword is required.',
            'finalSummary.required'        => 'Summary is required.',
            'finalSummary.min'             => 'Summary must be at least 20 characters.',
            'reportFile.required'          => 'Please upload the final report (PDF).',
            'reportFile.mimes'             => 'Report must be a PDF file.',
            'reportFile.max'               => 'Report file must not exceed 10 MB.',
            'pptFile.required'             => 'Please upload the presentation.',
            'pptFile.mimes'                => 'Presentation must be PPT or PPTX.',
            'pptFile.max'                  => 'Presentation file must not exceed 20 MB.',
            'researchOutputFile.required'  => 'Please upload the research output document.',
            'researchOutputFile.mimes'     => 'Research output must be PDF, DOC, or DOCX.',
            'researchOutputFile.max'       => 'Research output must not exceed 20 MB.',
            'submissionProofFile.required' => 'Please upload the submission proof.',
            'submissionProofFile.mimes'    => 'Submission proof must be an image (JPG, PNG, WEBP).',
            'submissionProofFile.max'      => 'Submission proof must not exceed 5 MB.',
        ];
    }

    // ═══════════════ Mount dari Alpine event ═══════════════
    public function addFinalReport(int $proposalId): void
    {
        $proposal = Proposal::with(['researchScheme', 'finalReport'])
            ->where('user_id', auth()->id())
            ->findOrFail($proposalId);

        // Guard: hanya kalau proposal accepted + ada progress approved
        if ($proposal->status_proposal !== 'accepted') {
            Flux::toast(
                text: 'Final report is only available for accepted proposals.',
                variant: 'danger'
            );
            return;
        }

        if (!$proposal->hasApprovedProgressReport()) {
            Flux::toast(
                text: 'Your progress report must be approved first before submitting the final report.',
                variant: 'danger'
            );
            return;
        }

        $this->proposal = $proposal;
        $this->proposal_title  = $proposal->title;
        $this->proposal_scheme = $proposal->researchScheme?->scheme_name ?? '—';

        $this->closeFinalForm();
    }

    public function with(): array
    {
        $finalReport = null;

        if ($this->proposal) {
            $finalReport = FinalReport::where('proposal_id', $this->proposal->id)->first();
        }

        return compact('finalReport');
    }

    // ═══════════════ Form open / close ═══════════════
    public function openFinalForm(): void
    {
        if (!$this->proposal) {
            return;
        }

        $report = FinalReport::where('proposal_id', $this->proposal->id)->first();

        $this->resetFinalForm();

        if ($report) {
            $this->editMode    = true;
            $this->editingId   = $report->id;
            $this->finalKeyword = $report->keyword;
            $this->finalSummary = $report->summary;
            $this->existingReportPath          = $report->report_path;
            $this->existingPptPath             = $report->ppt_path;
            $this->existingResearchOutputPath  = $report->research_output;
            $this->existingSubmissionProofPath = $report->submission_proof;
        }

        $this->showFinalForm = true;
    }

    public function closeFinalForm(): void
    {
        $this->showFinalForm = false;
        $this->resetFinalForm();
    }

    public function closeFinalReport(): void
    {
        $this->closeFinalForm();
        $this->reset(['proposal', 'proposal_title', 'proposal_scheme']);
    }

    // ═══════════════ Store — create or update ═══════════════
    public function store(): void
    {
        if (!$this->proposal) {
            return;
        }

        $this->validate();

        $data = [
            'summary' => $this->finalSummary,
            'keyword' => $this->finalKeyword,
        ];

        if ($this->reportFile) {
            $data['report_path'] = $this->reportFile->store(
                "final-reports/{$this->proposal->id}", 'public'
            );
        }
        if ($this->pptFile) {
            $data['ppt_path'] = $this->pptFile->store(
                "final-reports/{$this->proposal->id}", 'public'
            );
        }
        if ($this->researchOutputFile) {
            $data['research_output'] = $this->researchOutputFile->store(
                "final-reports/{$this->proposal->id}", 'public'
            );
        }
        if ($this->submissionProofFile) {
            $data['submission_proof'] = $this->submissionProofFile->store(
                "final-reports/{$this->proposal->id}", 'public'
            );
        }

        // ── UPDATE MODE ──
        if ($this->editMode && $this->editingId) {
            $report = FinalReport::where('proposal_id', $this->proposal->id)
                ->findOrFail($this->editingId);

            // Hapus file lama kalau diganti
            if (isset($data['report_path']) && $report->report_path) {
                Storage::disk('public')->delete($report->report_path);
            }
            if (isset($data['ppt_path']) && $report->ppt_path) {
                Storage::disk('public')->delete($report->ppt_path);
            }
            if (isset($data['research_output']) && $report->research_output) {
                Storage::disk('public')->delete($report->research_output);
            }
            if (isset($data['submission_proof']) && $report->submission_proof) {
                Storage::disk('public')->delete($report->submission_proof);
            }

            $report->update($data);

            Flux::toast(text: 'Final report updated.', variant: 'success');
            $this->dispatch('final-report-saved', message: 'Final report updated.');
        }
        // ── CREATE MODE ──
        else {
            // Cegah duplikat
            if (FinalReport::where('proposal_id', $this->proposal->id)->exists()) {
                Flux::toast(text: 'A final report already exists for this proposal.', variant: 'danger');
                return;
            }

            $data['proposal_id'] = $this->proposal->id;

            FinalReport::create($data);

            Flux::toast(text: 'Final report submitted.', variant: 'success');
            $this->dispatch('final-report-saved', message: 'Final report submitted.');
        }

        $this->closeFinalForm();
    }

    // ═══════════════ Reset ═══════════════
    protected function resetFinalForm(): void
    {
        $this->finalKeyword = '';
        $this->finalSummary = '';
        $this->reportFile          = null;
        $this->pptFile             = null;
        $this->researchOutputFile  = null;
        $this->submissionProofFile = null;
        $this->editMode    = false;
        $this->editingId   = null;
        $this->existingReportPath          = null;
        $this->existingPptPath             = null;
        $this->existingResearchOutputPath  = null;
        $this->existingSubmissionProofPath = null;
        $this->resetErrorBag();
        $this->resetValidation();
    }
};
?>

<div x-data="{
        show: false,
        init() {
            window.addEventListener('add-final-report', (e) => {
                $wire.addFinalReport(e.detail.proposalId);
                this.show = true;
            });
            window.addEventListener('final-report-saved', () => {
                this.show = false;
            });
        },
        close() {
            this.show = false;
            $wire.closeFinalReport();
        },
    }"
    x-show="show"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-100 flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="close()">

    {{-- Backdrop --}}
    <div x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] bg-inverse-surface/50 dark:bg-black/60
               backdrop-blur-xl backdrop-saturate-150"
        @click="close()"></div>

    {{-- Panel --}}
    <div x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative z-[101] bg-surface-container-lowest dark:bg-zinc-900 shadow-2xl
               w-full max-w-2xl rounded-t-2xl sm:rounded-xl
               max-h-[92vh] sm:max-h-[90vh] flex flex-col
               border border-outline-variant/50 dark:border-zinc-700">

        {{-- Header --}}
        <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500
                    px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.document-check class="size-4 text-white" />
                        </div>
                        <h3 class="font-heading text-base sm:text-lg font-semibold text-white">
                            Final Report
                        </h3>
                    </div>
                    <p class="text-[11px] text-white/75 mt-1 truncate" title="{{ $proposal_title }}">
                        {{ $proposal_title }}
                    </p>
                    <p class="text-[10px] text-white/60 mt-0.5 truncate">
                        {{ $proposal_scheme }}
                    </p>
                </div>
                <button type="button" @click="close()"
                    class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                           text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4">

            {{-- ══════ LIST VIEW ══════ --}}
            @if (!$showFinalForm)
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[11px] text-outline dark:text-zinc-500">
                        {{ $finalReport ? 'Submitted' : 'Not submitted yet' }}
                    </span>
                    <button type="button" wire:click="openFinalForm"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md
                               text-[11px] font-heading font-semibold text-white
                               bg-gradient-to-r from-blue-600 to-blue-500
                               hover:from-blue-700 hover:to-blue-600
                               shadow-sm shadow-blue-600/20 transition-colors duration-150">
                        @if ($finalReport)
                            <flux:icon.pencil-square class="size-3.5" />
                            Edit Final Report
                        @else
                            <flux:icon.plus class="size-3.5" />
                            Submit Final Report
                        @endif
                    </button>
                </div>

                @if (!$finalReport)
                    <div class="p-8 rounded-xl text-center
                                bg-surface-container-low dark:bg-zinc-800/40
                                border border-outline-variant/60 dark:border-zinc-700">
                        <flux:icon.document-check class="size-8 mx-auto text-outline-variant dark:text-zinc-700" />
                        <p class="mt-2 text-sm font-medium text-on-surface dark:text-zinc-200">
                            No final report yet
                        </p>
                        <p class="mt-0.5 text-[11px] text-outline dark:text-zinc-500">
                            Click "Submit Final Report" to upload your final deliverables.
                        </p>
                    </div>
                @else
                    <div class="p-4 rounded-xl border
                                bg-surface-container-low dark:bg-zinc-800/40
                                border-outline-variant/60 dark:border-zinc-700">

                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                         text-[10px] font-bold uppercase tracking-wider
                                         bg-blue-100 text-blue-700
                                         dark:bg-blue-900/40 dark:text-blue-300">
                                <flux:icon.check-circle class="size-3" />
                                Submitted
                            </span>
                            <span class="text-[10px] text-outline dark:text-zinc-500 font-mono">
                                {{ $finalReport->created_at?->format('d M Y, H:i') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1 mb-1">
                            <flux:icon.tag class="size-3 text-outline dark:text-zinc-500" />
                            <span class="text-[11px] font-medium text-on-surface dark:text-zinc-100">
                                {{ $finalReport->keyword }}
                            </span>
                        </div>

                        <p class="text-[12px] leading-relaxed whitespace-pre-line line-clamp-3
                                  text-on-surface-variant dark:text-zinc-400">
                            {{ $finalReport->summary }}
                        </p>

                        <div class="mt-3 pt-3 flex flex-wrap items-center gap-3
                                    border-t border-outline-variant/40 dark:border-zinc-700/60">
                            @if ($finalReport->report_path)
                                <a href="{{ Storage::disk('public')->url($finalReport->report_path) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-[10px]
                                           text-rose-600 hover:text-rose-700
                                           dark:text-rose-400 dark:hover:text-rose-300
                                           hover:underline font-medium">
                                    <flux:icon.document-text class="size-3" />
                                    Final Report PDF
                                </a>
                            @endif

                            @if ($finalReport->ppt_path)
                                <a href="{{ Storage::disk('public')->url($finalReport->ppt_path) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-[10px]
                                           text-orange-600 hover:text-orange-700
                                           dark:text-orange-400 dark:hover:text-orange-300
                                           hover:underline font-medium">
                                    <flux:icon.presentation-chart-bar class="size-3" />
                                    Presentation
                                </a>
                            @endif

                            @if ($finalReport->research_output)
                                <a href="{{ Storage::disk('public')->url($finalReport->research_output) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-[10px]
                                           text-blue-600 hover:text-blue-700
                                           dark:text-blue-400 dark:hover:text-blue-300
                                           hover:underline font-medium">
                                    <flux:icon.document-arrow-down class="size-3" />
                                    Research Output
                                </a>
                            @endif

                            @if ($finalReport->submission_proof)
                                <a href="{{ Storage::disk('public')->url($finalReport->submission_proof) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-[10px]
                                           text-emerald-600 hover:text-emerald-700
                                           dark:text-emerald-400 dark:hover:text-emerald-300
                                           hover:underline font-medium">
                                    <flux:icon.photo class="size-3" />
                                    Submission Proof
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @endif

            {{-- ══════ FORM VIEW ══════ --}}
            @if ($showFinalForm)
                <form wire:submit.prevent="store" class="space-y-4">

                    <button type="button" wire:click="closeFinalForm"
                        class="inline-flex items-center gap-1 text-[11px]
                               text-outline hover:text-on-surface
                               dark:text-zinc-500 dark:hover:text-zinc-300 transition-colors">
                        <flux:icon.arrow-left class="size-3" />
                        Back to overview
                    </button>

                    {{-- Banner edit --}}
                    @if ($editMode)
                        <div class="flex items-start gap-2 p-3 rounded-lg
                                    bg-blue-50 dark:bg-blue-900/20
                                    border border-blue-200/80 dark:border-blue-800/60">
                            <flux:icon.pencil-square
                                class="size-4 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                            <div class="text-[11px] leading-relaxed
                                        text-blue-800 dark:text-blue-300">
                                <span class="font-semibold">Editing final report.</span>
                                Leave files empty to keep the existing ones.
                            </div>
                        </div>
                    @endif

                    {{-- Keyword --}}
                    <div>
                        <label for="finalKeyword"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Keyword <span class="text-error">*</span>
                        </label>
                        <input type="text" id="finalKeyword" wire:model="finalKeyword"
                            placeholder="e.g. mangrove-restoration-final"
                            class="mt-1 block w-full rounded-md shadow-sm
                                   border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-100
                                   placeholder:text-outline dark:placeholder-zinc-500
                                   focus:border-blue-500 focus:ring-blue-500
                                   sm:text-sm py-2 px-3" />
                        @error('finalKeyword')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Summary --}}
                    <div>
                        <label for="finalSummary"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Summary <span class="text-error">*</span>
                        </label>
                        <textarea id="finalSummary" wire:model="finalSummary" rows="5"
                            placeholder="Comprehensive summary of the research outcomes, contributions, and final results..."
                            class="mt-1 block w-full rounded-md shadow-sm resize-none
                                   border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-100
                                   placeholder:text-outline dark:placeholder-zinc-500
                                   focus:border-blue-500 focus:ring-blue-500
                                   sm:text-sm py-2 px-3"></textarea>
                        <div class="flex items-center justify-between mt-1">
                            @error('finalSummary')
                                <span class="text-error text-xs">{{ $message }}</span>
                            @else
                                <span class="text-[11px] text-outline dark:text-zinc-500">
                                    Minimum 20 characters.
                                </span>
                            @enderror
                            <span class="text-[11px] text-outline dark:text-zinc-500 font-mono">
                                {{ strlen($finalSummary ?? '') }}/2000
                            </span>
                        </div>
                    </div>

                    {{-- Report File (PDF) --}}
                    <div>
                        <label for="reportFile"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Final Report (PDF)
                            @if (!$editMode)
                                <span class="text-error">*</span>
                            @else
                                <span class="text-outline dark:text-zinc-500 text-xs font-normal">
                                    (leave empty to keep current)
                                </span>
                            @endif
                        </label>

                        @if ($editMode && $existingReportPath)
                            <div class="mt-1 mb-2 flex items-center gap-2 px-3 py-2 rounded-md
                                        bg-rose-50 dark:bg-rose-900/20
                                        border border-rose-200/80 dark:border-rose-800/60">
                                <flux:icon.document-text
                                    class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0" />
                                <span class="text-[11px] text-rose-800 dark:text-rose-300 truncate flex-1">
                                    Current: {{ basename($existingReportPath) }}
                                </span>
                                <a href="{{ Storage::disk('public')->url($existingReportPath) }}"
                                    target="_blank"
                                    class="text-[10px] font-semibold text-rose-700 dark:text-rose-300
                                           hover:underline shrink-0">
                                    View
                                </a>
                            </div>
                        @endif

                        <input type="file" id="reportFile" wire:model="reportFile"
                            accept=".pdf,application/pdf"
                            class="mt-1 block w-full text-xs
                                   file:mr-3 file:py-2 file:px-3 file:rounded-md
                                   file:border-0 file:text-xs file:font-medium
                                   file:bg-rose-50 file:text-rose-700
                                   dark:file:bg-rose-900/30 dark:file:text-rose-300
                                   rounded-md border border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-300 cursor-pointer" />

                        <div wire:loading wire:target="reportFile"
                            class="mt-1.5 flex items-center gap-1.5 text-[11px] text-outline dark:text-zinc-500">
                            <svg class="animate-spin size-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Uploading...
                        </div>

                        @error('reportFile')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- PPT File --}}
                    <div>
                        <label for="pptFile"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Presentation (PPT/PPTX)
                            @if (!$editMode)
                                <span class="text-error">*</span>
                            @else
                                <span class="text-outline dark:text-zinc-500 text-xs font-normal">
                                    (leave empty to keep current)
                                </span>
                            @endif
                        </label>

                        @if ($editMode && $existingPptPath)
                            <div class="mt-1 mb-2 flex items-center gap-2 px-3 py-2 rounded-md
                                        bg-orange-50 dark:bg-orange-900/20
                                        border border-orange-200/80 dark:border-orange-800/60">
                                <flux:icon.presentation-chart-bar
                                    class="size-3.5 text-orange-600 dark:text-orange-400 shrink-0" />
                                <span class="text-[11px] text-orange-800 dark:text-orange-300 truncate flex-1">
                                    Current: {{ basename($existingPptPath) }}
                                </span>
                                <a href="{{ Storage::disk('public')->url($existingPptPath) }}"
                                    target="_blank"
                                    class="text-[10px] font-semibold text-orange-700 dark:text-orange-300
                                           hover:underline shrink-0">
                                    View
                                </a>
                            </div>
                        @endif

                        <input type="file" id="pptFile" wire:model="pptFile"
                            accept=".ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                            class="mt-1 block w-full text-xs
                                   file:mr-3 file:py-2 file:px-3 file:rounded-md
                                   file:border-0 file:text-xs file:font-medium
                                   file:bg-orange-50 file:text-orange-700
                                   dark:file:bg-orange-900/30 dark:file:text-orange-300
                                   rounded-md border border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-300 cursor-pointer" />

                        <div wire:loading wire:target="pptFile"
                            class="mt-1.5 flex items-center gap-1.5 text-[11px] text-outline dark:text-zinc-500">
                            <svg class="animate-spin size-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Uploading...
                        </div>

                        @error('pptFile')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Research Output --}}
                    <div>
                        <label for="researchOutputFile"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Research Output (PDF/DOC/DOCX)
                            @if (!$editMode)
                                <span class="text-error">*</span>
                            @else
                                <span class="text-outline dark:text-zinc-500 text-xs font-normal">
                                    (leave empty to keep current)
                                </span>
                            @endif
                        </label>

                        @if ($editMode && $existingResearchOutputPath)
                            <div class="mt-1 mb-2 flex items-center gap-2 px-3 py-2 rounded-md
                                        bg-blue-50 dark:bg-blue-900/20
                                        border border-blue-200/80 dark:border-blue-800/60">
                                <flux:icon.document-arrow-down
                                    class="size-3.5 text-blue-600 dark:text-blue-400 shrink-0" />
                                <span class="text-[11px] text-blue-800 dark:text-blue-300 truncate flex-1">
                                    Current: {{ basename($existingResearchOutputPath) }}
                                </span>
                                <a href="{{ Storage::disk('public')->url($existingResearchOutputPath) }}"
                                    target="_blank"
                                    class="text-[10px] font-semibold text-blue-700 dark:text-blue-300
                                           hover:underline shrink-0">
                                    View
                                </a>
                            </div>
                        @endif

                        <input type="file" id="researchOutputFile" wire:model="researchOutputFile"
                            accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            class="mt-1 block w-full text-xs
                                   file:mr-3 file:py-2 file:px-3 file:rounded-md
                                   file:border-0 file:text-xs file:font-medium
                                   file:bg-blue-50 file:text-blue-700
                                   dark:file:bg-blue-900/30 dark:file:text-blue-300
                                   rounded-md border border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-300 cursor-pointer" />

                        <div wire:loading wire:target="researchOutputFile"
                            class="mt-1.5 flex items-center gap-1.5 text-[11px] text-outline dark:text-zinc-500">
                            <svg class="animate-spin size-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Uploading...
                        </div>

                        @error('researchOutputFile')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Submission Proof (Image) --}}
                    <div>
                        <label for="submissionProofFile"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Submission Proof (Image)
                            @if (!$editMode)
                                <span class="text-error">*</span>
                            @else
                                <span class="text-outline dark:text-zinc-500 text-xs font-normal">
                                    (leave empty to keep current)
                                </span>
                            @endif
                        </label>

                        @if ($editMode && $existingSubmissionProofPath)
                            <div class="mt-1 mb-2 flex items-center gap-2 px-3 py-2 rounded-md
                                        bg-emerald-50 dark:bg-emerald-900/20
                                        border border-emerald-200/80 dark:border-emerald-800/60">
                                <flux:icon.photo
                                    class="size-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                <span class="text-[11px] text-emerald-800 dark:text-emerald-300 truncate flex-1">
                                    Current: {{ basename($existingSubmissionProofPath) }}
                                </span>
                                <a href="{{ Storage::disk('public')->url($existingSubmissionProofPath) }}"
                                    target="_blank"
                                    class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-300
                                           hover:underline shrink-0">
                                    View
                                </a>
                            </div>
                        @endif

                        <input type="file" id="submissionProofFile" wire:model="submissionProofFile"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            class="mt-1 block w-full text-xs
                                   file:mr-3 file:py-2 file:px-3 file:rounded-md
                                   file:border-0 file:text-xs file:font-medium
                                   file:bg-emerald-50 file:text-emerald-700
                                   dark:file:bg-emerald-900/30 dark:file:text-emerald-300
                                   rounded-md border border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-300 cursor-pointer" />

                        <div wire:loading wire:target="submissionProofFile"
                            class="mt-1.5 flex items-center gap-1.5 text-[11px] text-outline dark:text-zinc-500">
                            <svg class="animate-spin size-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Uploading...
                        </div>

                        @error('submissionProofFile')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-3
                                border-t border-outline-variant/40 dark:border-zinc-700">

                        <button type="button" wire:click="closeFinalForm"
                            class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-md
                                   text-on-surface-variant dark:text-zinc-300
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   border border-outline-variant dark:border-zinc-600
                                   hover:bg-surface-container-low dark:hover:bg-zinc-700
                                   transition-colors duration-150">
                            Cancel
                        </button>

                        <button type="submit" wire:loading.attr="disabled"
                            wire:target="store,reportFile,pptFile,researchOutputFile,submissionProofFile"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                                   px-5 py-2 text-sm font-medium text-white rounded-md
                                   bg-gradient-to-r from-blue-600 to-blue-500
                                   hover:from-blue-700 hover:to-blue-600
                                   focus:outline-none focus:ring-2 focus:ring-blue-500
                                   transition-all duration-200
                                   disabled:opacity-60 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="store"
                                class="inline-flex items-center gap-1.5">
                                @if ($editMode)
                                    <flux:icon.pencil-square class="size-3.5" />
                                    Update Final Report
                                @else
                                    <flux:icon.paper-airplane class="size-3.5" />
                                    Submit Final Report
                                @endif
                            </span>
                            <span wire:loading.flex wire:target="store" class="items-center gap-1.5">
                                <svg class="animate-spin size-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                {{ $editMode ? 'Updating...' : 'Submitting...' }}
                            </span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
