<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Proposal;
use App\Models\ProgressReport;
use Illuminate\Support\Facades\Storage;
use Flux\Flux;

new class extends Component {
    use WithFileUploads;

    // ── Proposal context (di-set lewat addReport) ──
    public ?Proposal $proposal = null;
    public string $proposal_title  = '';
    public string $proposal_scheme = '';

    // ── View state ──
    public bool $showProgressForm = false;

    // ── Form fields (ADD ONLY) ──
    public string $progressKeyword = '';
    public string $progressSummary = '';
    public $progressReportFile = null;
    public $progressPptFile    = null;

    protected function rules(): array
    {
        return [
            'progressSummary'    => ['required', 'string', 'min:20'],
            'progressKeyword'    => ['required', 'string', 'max:255'],
            'progressReportFile' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'progressPptFile'    => ['required', 'file', 'mimes:ppt,pptx', 'max:20480'],
        ];
    }

    protected function messages(): array
    {
        return [
            'progressSummary.required'    => 'Summary is required.',
            'progressSummary.min'         => 'Summary must be at least 20 characters.',
            'progressKeyword.required'    => 'Keyword is required.',
            'progressReportFile.required' => 'Please upload the report file (PDF).',
            'progressReportFile.mimes'    => 'Report must be a PDF file.',
            'progressReportFile.max'      => 'Report file must not exceed 10 MB.',
            'progressPptFile.required'    => 'Please upload the presentation file.',
            'progressPptFile.mimes'       => 'Presentation must be PPT or PPTX.',
            'progressPptFile.max'         => 'Presentation file must not exceed 20 MB.',
        ];
    }

    // ═══════════════════════════════════════════════════════
    //  Dipanggil dari Alpine saat window event 'add-progress-report'
    // ═══════════════════════════════════════════════════════
    public function addReport(int $proposalId): void
    {
        $this->proposal = Proposal::with('researchScheme')
            ->where('user_id', auth()->id())
            ->findOrFail($proposalId);

        $this->proposal_title  = $this->proposal->title;
        $this->proposal_scheme = $this->proposal->researchScheme?->scheme_name ?? '—';

        $this->closeProgressForm();       // reset form + view
    }

    public function with(): array
    {
        $progressReports = collect();

        if ($this->proposal) {
            $progressReports = ProgressReport::where('proposal_id', $this->proposal->id)
                ->with('reviewer')
                ->latest()
                ->get();
        }

        return [
            'progressReports' => $progressReports,
        ];
    }

    // ── Form open/close ──
    public function openProgressForm(): void
    {
        $this->resetProgressForm();
        $this->showProgressForm = true;
    }

    public function closeProgressForm(): void
    {
        $this->showProgressForm = false;
        $this->resetProgressForm();
    }

    // Dipanggil Alpine saat modal ditutup (klik backdrop / X)
    public function closeProgressReports(): void
    {
        $this->closeProgressForm();
        $this->reset(['proposal', 'proposal_title', 'proposal_scheme']);
    }

    // ═══════════════════════════════════════════════════════
    //  ADD ONLY — tidak ada update
    // ═══════════════════════════════════════════════════════
    public function store(): void
    {
        if (!$this->proposal) {
            return;
        }

        $this->validate();

        ProgressReport::create([
            'proposal_id' => $this->proposal->id,
            'reviewer_id' => null,
            'is_approved' => false,
            'summary'     => $this->progressSummary,
            'keyword'     => $this->progressKeyword,
            'report_path' => $this->progressReportFile->store(
                "progress-reports/{$this->proposal->id}", 'public'
            ),
            'ppt_path'    => $this->progressPptFile->store(
                "progress-reports/{$this->proposal->id}", 'public'
            ),
        ]);

        Flux::toast(text: 'Progress report submitted.', variant: 'success');

        // Notify Alpine untuk menutup modal
        $this->dispatch('added-success', message: 'Progress report submitted.');

        $this->closeProgressForm();
    }

    // ── Delete (hanya untuk report pending) ──
    public function deleteReport(int $id): void
    {
        if (!$this->proposal) {
            return;
        }

        $report = ProgressReport::where('proposal_id', $this->proposal->id)
            ->findOrFail($id);

        if (!$report->isPending()) {
            Flux::toast(text: 'This report has been reviewed and can no longer be deleted.', variant: 'danger');
            return;
        }

        if ($report->report_path) Storage::disk('public')->delete($report->report_path);
        if ($report->ppt_path)    Storage::disk('public')->delete($report->ppt_path);

        $report->delete();

        Flux::toast(text: 'Progress report deleted.', variant: 'success');
    }

    protected function resetProgressForm(): void
    {
        $this->progressKeyword    = '';
        $this->progressSummary    = '';
        $this->progressReportFile = null;
        $this->progressPptFile    = null;
        $this->resetErrorBag();
        $this->resetValidation();
    }
};
?>

<div x-data="{
        show: false,
        init() {
            window.addEventListener('add-progress-report', (e) => {
                $wire.addReport(e.detail.proposalId);
                this.show = true;
            });

            window.addEventListener('added-success', () => {
                this.show = false;
            });
        },
        close() {
            this.show = false;
            $wire.closeProgressReports();
        },
    }"
    x-show="show"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-100 flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="close()">

    {{-- ════════ Backdrop ════════ --}}
    <div x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] bg-inverse-surface/50 dark:bg-black/60
               backdrop-blur-xl backdrop-saturate-150"
        @click="close()"></div>

    {{-- ════════ Panel ════════ --}}
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

        {{-- ── Header ── --}}
        <div class="shrink-0 bg-gradient-to-r from-emerald-600 to-emerald-500
                    px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.document-chart-bar class="size-4 text-white" />
                        </div>
                        <h3 class="font-heading text-base sm:text-lg font-semibold text-white">
                            Progress Reports
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

        {{-- ── Body ── --}}
        <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4">

            {{-- ══════ LIST VIEW ══════ --}}
            @if (!$showProgressForm)
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[11px] text-outline dark:text-zinc-500">
                        {{ $progressReports->count() }}
                        {{ Str::plural('report', $progressReports->count()) }}
                    </span>
                    <button type="button" wire:click="openProgressForm"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md
                               text-[11px] font-heading font-semibold text-white
                               bg-gradient-to-r from-emerald-600 to-emerald-500
                               hover:from-emerald-700 hover:to-emerald-600
                               shadow-sm shadow-emerald-600/20 transition-colors duration-150">
                        <flux:icon.plus class="size-3.5" />
                        Submit Report
                    </button>
                </div>

                @if ($progressReports->isEmpty())
                    <div class="p-8 rounded-xl text-center
                                bg-surface-container-low dark:bg-zinc-800/40
                                border border-outline-variant/60 dark:border-zinc-700">
                        <flux:icon.document-chart-bar class="size-8 mx-auto text-outline-variant dark:text-zinc-700" />
                        <p class="mt-2 text-sm font-medium text-on-surface dark:text-zinc-200">
                            No progress reports yet
                        </p>
                        <p class="mt-0.5 text-[11px] text-outline dark:text-zinc-500">
                            Click "Submit Report" to upload your first progress report.
                        </p>
                    </div>
                @else
                    <div class="flex flex-col gap-2">
                        @foreach ($progressReports as $report)
                            @php $meta = $report->statusMeta(); @endphp
                            <div class="p-3 rounded-xl border
                                        bg-surface-container-low dark:bg-zinc-800/40
                                        border-outline-variant/60 dark:border-zinc-700">

                                <div class="flex items-start justify-between gap-2 mb-1.5">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                                     text-[10px] font-bold uppercase tracking-wider
                                                     {{ $meta['class'] }}">
                                            @if ($report->isPending())
                                                <flux:icon.clock class="size-3" />
                                            @elseif ($report->isApproved())
                                                <flux:icon.check-circle class="size-3" />
                                            @else
                                                <flux:icon.x-circle class="size-3" />
                                            @endif
                                            {{ $meta['label'] }}
                                        </span>
                                        <span class="text-[10px] text-outline dark:text-zinc-500 font-mono">
                                            {{ $report->created_at?->format('d M Y, H:i') }}
                                        </span>
                                    </div>

                                    @if ($report->isPending())
                                        <button type="button"
                                            wire:click="deleteReport({{ $report->id }})"
                                            wire:confirm="Delete this progress report? This cannot be undone."
                                            title="Delete"
                                            class="w-6 h-6 rounded-md flex items-center justify-center
                                                   text-outline hover:text-rose-600 hover:bg-rose-100
                                                   dark:text-zinc-500 dark:hover:text-rose-400
                                                   dark:hover:bg-rose-900/30 transition-colors">
                                            <flux:icon.trash class="size-3.5" />
                                        </button>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1 mb-1">
                                    <flux:icon.tag class="size-3 text-outline dark:text-zinc-500" />
                                    <span class="text-[11px] font-medium text-on-surface dark:text-zinc-100">
                                        {{ $report->keyword }}
                                    </span>
                                </div>

                                <p class="text-[12px] leading-relaxed whitespace-pre-line line-clamp-3
                                          text-on-surface-variant dark:text-zinc-400">
                                    {{ $report->summary }}
                                </p>

                                <div class="mt-2 pt-2 flex flex-wrap items-center gap-3
                                            border-t border-outline-variant/40 dark:border-zinc-700/60">
                                    @if ($report->report_path)
                                        <a href="{{ Storage::disk('public')->url($report->report_path) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-[10px]
                                                   text-rose-600 hover:text-rose-700
                                                   dark:text-rose-400 dark:hover:text-rose-300
                                                   hover:underline font-medium">
                                            <flux:icon.document-text class="size-3" />
                                            Report PDF
                                        </a>
                                    @endif
                                    @if ($report->ppt_path)
                                        <a href="{{ Storage::disk('public')->url($report->ppt_path) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-[10px]
                                                   text-orange-600 hover:text-orange-700
                                                   dark:text-orange-400 dark:hover:text-orange-300
                                                   hover:underline font-medium">
                                            <flux:icon.presentation-chart-bar class="size-3" />
                                            Presentation
                                        </a>
                                    @endif
                                    @if ($report->reviewer)
                                        <div class="ml-auto flex items-center gap-1.5 text-[10px]
                                                    text-outline dark:text-zinc-500">
                                            <flux:icon.user-circle class="size-3" />
                                            {{ $report->reviewer->full_name }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            {{-- ══════ FORM VIEW (ADD ONLY) ══════ --}}
            @if ($showProgressForm)
                <form wire:submit.prevent="store" class="space-y-4">

                    <button type="button" wire:click="closeProgressForm"
                        class="inline-flex items-center gap-1 text-[11px]
                               text-outline hover:text-on-surface
                               dark:text-zinc-500 dark:hover:text-zinc-300 transition-colors">
                        <flux:icon.arrow-left class="size-3" />
                        Back to list
                    </button>

                    {{-- Keyword --}}
                    <div>
                        <label for="progressKeyword"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Keyword <span class="text-error">*</span>
                        </label>
                        <input type="text" id="progressKeyword" wire:model="progressKeyword"
                            placeholder="e.g. mangrove-restoration-phase-1"
                            class="mt-1 block w-full rounded-md shadow-sm
                                   border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-100
                                   placeholder:text-outline dark:placeholder-zinc-500
                                   focus:border-emerald-500 focus:ring-emerald-500
                                   sm:text-sm py-2 px-3" />
                        @error('progressKeyword')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Summary --}}
                    <div>
                        <label for="progressSummary"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Summary <span class="text-error">*</span>
                        </label>
                        <textarea id="progressSummary" wire:model="progressSummary" rows="5"
                            placeholder="Brief summary of the progress made, milestones achieved, and remaining tasks..."
                            class="mt-1 block w-full rounded-md shadow-sm resize-none
                                   border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-100
                                   placeholder:text-outline dark:placeholder-zinc-500
                                   focus:border-emerald-500 focus:ring-emerald-500
                                   sm:text-sm py-2 px-3"></textarea>
                        <div class="flex items-center justify-between mt-1">
                            @error('progressSummary')
                                <span class="text-error text-xs">{{ $message }}</span>
                            @else
                                <span class="text-[11px] text-outline dark:text-zinc-500">
                                    Minimum 20 characters.
                                </span>
                            @enderror
                            <span class="text-[11px] text-outline dark:text-zinc-500 font-mono">
                                {{ strlen($progressSummary ?? '') }}/2000
                            </span>
                        </div>
                    </div>

                    {{-- Report File --}}
                    <div>
                        <label for="progressReportFile"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Report File (PDF) <span class="text-error">*</span>
                        </label>
                        <input type="file" id="progressReportFile" wire:model="progressReportFile"
                            accept=".pdf,application/pdf"
                            class="mt-1 block w-full text-xs
                                   file:mr-3 file:py-2 file:px-3 file:rounded-md
                                   file:border-0 file:text-xs file:font-medium
                                   file:bg-rose-50 file:text-rose-700
                                   dark:file:bg-rose-900/30 dark:file:text-rose-300
                                   rounded-md border border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-300 cursor-pointer" />

                        <div wire:loading wire:target="progressReportFile"
                            class="mt-1.5 flex items-center gap-1.5 text-[11px] text-outline dark:text-zinc-500">
                            <svg class="animate-spin size-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Uploading...
                        </div>

                        @error('progressReportFile')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- PPT File --}}
                    <div>
                        <label for="progressPptFile"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Presentation (PPT/PPTX) <span class="text-error">*</span>
                        </label>
                        <input type="file" id="progressPptFile" wire:model="progressPptFile"
                            accept=".ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                            class="mt-1 block w-full text-xs
                                   file:mr-3 file:py-2 file:px-3 file:rounded-md
                                   file:border-0 file:text-xs file:font-medium
                                   file:bg-orange-50 file:text-orange-700
                                   dark:file:bg-orange-900/30 dark:file:text-orange-300
                                   rounded-md border border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-300 cursor-pointer" />

                        <div wire:loading wire:target="progressPptFile"
                            class="mt-1.5 flex items-center gap-1.5 text-[11px] text-outline dark:text-zinc-500">
                            <svg class="animate-spin size-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Uploading...
                        </div>

                        @error('progressPptFile')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-3
                                border-t border-outline-variant/40 dark:border-zinc-700">
                        <button type="button" wire:click="closeProgressForm"
                            class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-md
                                   text-on-surface-variant dark:text-zinc-300
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   border border-outline-variant dark:border-zinc-600
                                   hover:bg-surface-container-low dark:hover:bg-zinc-700
                                   transition-colors duration-150">
                            Cancel
                        </button>

                        <button type="submit" wire:loading.attr="disabled"
                            wire:target="store,progressReportFile,progressPptFile"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                                   px-5 py-2 text-sm font-medium text-white rounded-md
                                   bg-gradient-to-r from-emerald-600 to-emerald-500
                                   hover:from-emerald-700 hover:to-emerald-600
                                   focus:outline-none focus:ring-2 focus:ring-emerald-500
                                   transition-all duration-200
                                   disabled:opacity-60 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="store">Submit</span>
                            <span wire:loading.flex wire:target="store" class="items-center gap-1.5">
                                <svg class="animate-spin size-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
