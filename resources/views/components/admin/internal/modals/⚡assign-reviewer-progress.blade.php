<?php

use App\Models\ProgressReport;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $progress_report_id = null;
    public ?int $reviewer_id = null;
    public string $proposal_title = '';
    public string $keyword = '';

    public string $search = '';
    public bool $hasSearched = false;

    /* ============================================================
     |  LOAD
     ============================================================ */

    #[On('open-assign-reviewer-progress')]
    public function load(int $id): void
    {
        $report = ProgressReport::with('proposal')->find($id);

        if (! $report) {
            Flux::toast('Progress report not found.', variant: 'danger');
            return;
        }

        // Uncomment kalau mau guard status
        // if ($report->status !== 'submitted') {
        //     Flux::toast('Reviewer can only be assigned to submitted progress reports.', variant: 'danger');
        //     return;
        // }

        $this->progress_report_id = $report->id;
        $this->proposal_title     = $report->proposal?->title ?? '—';
        $this->keyword            = $report->keyword ?? '';
        $this->reviewer_id        = $report->reviewer_id;

        $this->search      = '';
        $this->hasSearched = false;

        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('show-assign-reviewer-progress');
    }

    /* ============================================================
     |  SEARCH & SELECTION
     ============================================================ */

    public function updatedSearch(): void
    {
        $this->hasSearched = true;
    }

    public function selectReviewer(int $id): void
    {
        $this->reviewer_id = $id;
        $this->search      = '';
        $this->hasSearched = false;
        $this->resetErrorBag('reviewer_id');
        $this->resetValidation('reviewer_id');
    }

    public function clearReviewer(): void
    {
        $this->reviewer_id = null;
        $this->search      = '';
        $this->hasSearched = false;
    }

    /* ============================================================
     |  SAVE
     ============================================================ */

    public function save(): void
    {
        $this->validate([
            'reviewer_id' => ['required', 'exists:users,id'],
        ], [
            'reviewer_id.required' => 'Reviewer is required.',
            'reviewer_id.exists'   => 'Reviewer is invalid.',
        ]);

        $report = ProgressReport::find($this->progress_report_id);

        if (! $report) {
            Flux::toast('Progress report not found.', variant: 'danger');
            return;
        }

        ProgressReport::where('id', $report->id)->update([
            'reviewer_id' => $this->reviewer_id,
            'status'      => 'under_review',
        ]);

        Flux::toast('Reviewer assigned. Status changed to Under Review.', variant: 'success');

        $this->dispatch('reviewer-assigned');
        $this->dispatch('close-assign-reviewer-progress-modal');
        $this->resetAll();
    }

    public function resetAll(): void
    {
        $this->reset([
            'progress_report_id',
            'reviewer_id',
            'proposal_title',
            'keyword',
            'search',
            'hasSearched',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    /* ============================================================
     |  RENDER DATA
     ============================================================ */

    public function with(): array
    {
        $selectedReviewer = $this->reviewer_id
            ? User::find($this->reviewer_id)
            : null;

        $suggestions = collect();

        if (! $selectedReviewer) {
            $suggestions = User::query()
                ->whereHas('role', fn ($q) => $q->where('role_code', 'REVIEWER'))
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('full_name', 'like', "%{$this->search}%")
                      ->orWhere('nidn', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%");
                }))
                ->orderBy('full_name')
                ->limit(5)
                ->get();
        }

        return [
            'selectedReviewer' => $selectedReviewer,
            'suggestions'      => $suggestions,
        ];
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('show-assign-reviewer-progress', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('close-assign-reviewer-progress-modal', () => {
                this.show = false;
            });
            window.addEventListener('assign-reviewer-error', (e) => {
                this.errorMessage = e.detail.message;
            });
        }
    }"
    x-show="show"
    x-transition.opacity
    x-cloak
    x-on:keydown.escape.window="show = false"
    class="fixed inset-0 z-[110] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
               bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
               border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500 px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.user-plus class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight">
                                Assign Reviewer
                            </h3>
                            <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                                Select a reviewer for this progress report
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        aria-label="Close"
                        class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">

                {{-- Global Error --}}
                <template x-if="errorMessage">
                    <div class="flex items-start gap-2 p-2.5 rounded-md
                                bg-rose-50 dark:bg-rose-900/20
                                border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle
                            class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[10.5px] leading-relaxed
                                  text-rose-700 dark:text-rose-300"
                            x-text="errorMessage"></p>
                    </div>
                </template>

                {{-- Progress Report Info --}}
                <div class="rounded-md border border-slate-200 dark:border-zinc-700/70
                            bg-slate-50 dark:bg-zinc-800/40 px-2.5 py-2">
                    <p class="text-[9.5px] uppercase tracking-wider font-semibold
                              text-slate-500 dark:text-zinc-400">
                        Progress Report
                    </p>
                    <p class="mt-1 text-[11px] font-semibold leading-tight line-clamp-2
                              text-slate-900 dark:text-zinc-100">
                        {{ $proposal_title }}
                    </p>

                    @if ($keyword)
                        <div class="mt-1.5">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full
                                         text-[9.5px] font-semibold
                                         bg-violet-100 text-violet-700
                                         dark:bg-violet-900/40 dark:text-violet-300">
                                <flux:icon.tag class="size-2.5" />
                                {{ $keyword }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Select Reviewer --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                        Select Reviewer <span class="text-rose-500">*</span>
                    </label>

                    @if ($selectedReviewer)
                        {{-- ══════════ SELECTED STATE ══════════ --}}
                        <div class="rounded-md border border-violet-300 dark:border-violet-700
                                    bg-violet-50 dark:bg-violet-900/20 px-2.5 py-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-md shrink-0
                                            bg-violet-200 dark:bg-violet-800/50
                                            flex items-center justify-center
                                            text-[11px] font-bold
                                            text-violet-700 dark:text-violet-300">
                                    {{ strtoupper(substr($selectedReviewer->full_name, 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-semibold truncate
                                              text-violet-900 dark:text-violet-100">
                                        {{ $selectedReviewer->full_name }}
                                    </p>
                                    <p class="text-[9.5px] text-violet-600 dark:text-violet-400">
                                        {{ $selectedReviewer->nidn }}
                                    </p>
                                </div>
                                <button type="button" wire:click="clearReviewer"
                                    aria-label="Change reviewer"
                                    class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center
                                           text-violet-600 dark:text-violet-400
                                           hover:bg-violet-100 dark:hover:bg-violet-900/40
                                           transition-colors">
                                    <flux:icon.x-mark class="size-3.5" />
                                </button>
                            </div>
                        </div>

                        <button type="button" wire:click="clearReviewer"
                            class="mt-1.5 text-[10.5px] font-medium
                                   text-violet-600 dark:text-violet-400 hover:underline">
                            Change reviewer?
                        </button>
                    @else
                        {{-- ══════════ SEARCH STATE ══════════ --}}
                        <div class="relative">
                            <flux:icon.magnifying-glass
                                class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5
                                       text-slate-400 pointer-events-none" />
                            <input type="text"
                                wire:model.live.debounce.200ms="search"
                                placeholder="Search by name, NIDN, or email..."
                                autofocus
                                class="block w-full rounded-md shadow-sm text-[11px] pl-8 pr-2.5
                                       border-slate-300 dark:border-zinc-600
                                       bg-white dark:bg-zinc-800
                                       text-slate-900 dark:text-zinc-100
                                       placeholder:text-slate-400 dark:placeholder:text-zinc-500
                                       focus:ring-1 focus:ring-violet-500 focus:border-violet-500
                                       py-2 transition-colors" />
                        </div>

                        {{-- Suggestions --}}
                        <div class="mt-2 rounded-md border border-slate-200 dark:border-zinc-700
                                    bg-white dark:bg-zinc-800 overflow-hidden">

                            @forelse ($suggestions as $reviewer)
                                <button type="button"
                                    wire:key="suggestion-{{ $reviewer->id }}"
                                    wire:click="selectReviewer({{ $reviewer->id }})"
                                    class="w-full flex items-center gap-2.5 px-2.5 py-2
                                           text-left transition-colors
                                           hover:bg-violet-50 dark:hover:bg-violet-900/20
                                           {{ ! $loop->last ? 'border-b border-slate-100 dark:border-zinc-700/70' : '' }}">
                                    <div class="w-7 h-7 rounded-md shrink-0
                                                bg-violet-100 dark:bg-violet-900/40
                                                flex items-center justify-center
                                                text-[10px] font-bold
                                                text-violet-700 dark:text-violet-300">
                                        {{ strtoupper(substr($reviewer->full_name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-medium truncate
                                                  text-slate-900 dark:text-zinc-100">
                                            {{ $reviewer->full_name }}
                                        </p>
                                        <p class="text-[9.5px] truncate
                                                  text-slate-500 dark:text-zinc-500">
                                            {{ $reviewer->nidn }} · {{ $reviewer->email }}
                                        </p>
                                    </div>
                                    <flux:icon.chevron-right class="size-3 text-slate-400 shrink-0" />
                                </button>
                            @empty
                                <div class="px-3 py-5 text-center">
                                    @if ($search)
                                        <flux:icon.user-minus
                                            class="size-6 mx-auto mb-1.5 text-slate-300 dark:text-zinc-600" />
                                        <p class="text-[10.5px] text-slate-500 dark:text-zinc-400">
                                            No reviewer found
                                        </p>
                                        <p class="text-[9.5px] text-slate-400 dark:text-zinc-500 mt-0.5">
                                            Try a different keyword
                                        </p>
                                    @else
                                        <flux:icon.user-group
                                            class="size-6 mx-auto mb-1.5 text-slate-300 dark:text-zinc-600" />
                                        <p class="text-[10.5px] text-slate-500 dark:text-zinc-400">
                                            Type to search reviewers
                                        </p>
                                    @endif
                                </div>
                            @endforelse

                            @if ($suggestions->isNotEmpty())
                                <div class="px-2.5 py-1.5 bg-slate-50 dark:bg-zinc-900/50
                                            border-t border-slate-100 dark:border-zinc-700/70">
                                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 text-center">
                                        Showing up to 5 results
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif

                    @error('reviewer_id')
                        <p class="mt-1.5 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                        px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-md
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           transition-colors">
                    Cancel
                </button>

                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-md text-white
                           bg-violet-600 hover:bg-violet-700
                           disabled:opacity-60 disabled:cursor-wait
                           transition-colors">
                    <svg wire:loading wire:target="save"
                         class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">Assign Reviewer</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
