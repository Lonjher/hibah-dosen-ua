<?php

use App\Models\Proposal;
use App\Models\ProgressReport;
use App\Models\ReviewerNote;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $noteable_id = null;
    public string $noteable_type = 'proposal';
    public string $subject = '';
    public Collection $notes;
    public ?int $reviewer_id = null;

    public function mount(): void
    {
        $this->notes = collect();
    }

    /* ============================================================
     |  LOAD
     ============================================================ */

    #[On('open-reviewer-notes')]
    public function load(int $id, string $type = 'proposal'): void
    {
        $this->noteable_id   = $id;
        $this->noteable_type = $type;

        // Resolve subject berdasarkan type
        $this->subject = match ($type) {
            'proposal'        => Proposal::find($id)?->title ?? '',
            'progress_report' => ProgressReport::find($id)?->proposal?->title ?? '',
            default           => '',
        };

        // Ambil reviewer yang di-assign (untuk info)
        if ($type === 'proposal') {
            $this->reviewer_id = Proposal::find($id)?->reviewer_id;
        } else {
            $this->reviewer_id = null;
        }

        $this->loadNotes();
        $this->dispatch('show-reviewer-notes');
    }

    public function loadNotes(): void
    {
        $this->notes = ReviewerNote::query()
            ->where('noteable_id', $this->noteable_id)
            ->where('noteable_type', $this->noteable_type)
            ->with('reviewer')
            ->latest()
            ->get();
    }

    public function with(): array
    {
        return [
            'reviewer' => $this->reviewer_id
                ? User::find($this->reviewer_id)
                : null,
        ];
    }
};
?>

<div
    x-data="{
        show: false,
        init() {
            window.addEventListener('show-reviewer-notes', () => { this.show = true; });
        }
    }"
    x-show="show"
    x-transition.opacity
    x-cloak
    x-on:keydown.escape.window="show = false"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
               bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
               border border-slate-200 dark:border-zinc-700
               hover:shadow-violet-500/15 transition-shadow duration-300"
        @click.stop>

        {{-- ══════════ HEADER ══════════ --}}
        <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500
                    px-4 py-3 rounded-t-3xl sm:rounded-t-3xl">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.clipboard-document-check class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold text-white leading-tight">
                            Reviewer Notes
                        </h3>
                        <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                            History of reviewer feedback
                        </p>
                    </div>
                </div>
                <button type="button" @click="show = false"
                    aria-label="Close"
                    class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                           text-white/80 hover:text-white hover:bg-white/10
                           hover:scale-110 active:scale-95
                           transition-all duration-150">
                    <flux:icon.x-mark class="size-3.5" />
                </button>
            </div>
        </div>

        {{-- ══════════ BODY ══════════ --}}
        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">

            {{-- ─────── SUBJECT CARD ─────── --}}
            @if ($subject)
                <div class="rounded-2xl border border-slate-200 dark:border-zinc-700/70
                            bg-slate-50 dark:bg-zinc-800/40 px-3 py-2.5">
                    <p class="text-[9.5px] uppercase tracking-wider font-semibold
                              text-slate-500 dark:text-zinc-400">
                        Subject
                    </p>
                    <p class="mt-1 text-[11px] font-semibold leading-tight line-clamp-2
                              text-slate-900 dark:text-zinc-100">
                        {{ $subject }}
                    </p>

                    @if ($reviewer)
                        <div class="mt-2 pt-2 flex items-center gap-2
                                    border-t border-slate-200 dark:border-zinc-700/70">
                            <div class="w-6 h-6 rounded-full shrink-0
                                        bg-violet-100 dark:bg-violet-900/40
                                        flex items-center justify-center
                                        text-[9px] font-bold
                                        text-violet-700 dark:text-violet-300">
                                {{ strtoupper(substr($reviewer->full_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] uppercase tracking-wider font-semibold
                                          text-slate-500 dark:text-zinc-400">
                                    Assigned Reviewer
                                </p>
                                <p class="text-[10.5px] font-semibold truncate
                                          text-slate-700 dark:text-zinc-200">
                                    {{ $reviewer->full_name }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ─────── NOTES LIST ─────── --}}
            <div class="space-y-2">

                {{-- List header --}}
                <div class="flex items-center gap-1.5">
                    <flux:icon.chat-bubble-left-right class="size-3 text-violet-500 dark:text-violet-400" />
                    <p class="text-[9.5px] uppercase tracking-wider font-semibold
                              text-slate-500 dark:text-zinc-400">
                        All Notes
                    </p>
                    @if ($notes->count() > 0)
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                     bg-violet-100 text-violet-700
                                     dark:bg-violet-900/40 dark:text-violet-300">
                            {{ $notes->count() }}
                        </span>
                    @endif
                </div>

                @forelse ($notes as $note)
                    <div wire:key="reviewer-note-{{ $note->id }}"
                        class="rounded-2xl border border-violet-200 dark:border-violet-800/60
                               bg-violet-50 dark:bg-violet-900/15 px-3 py-2.5">

                        {{-- Note header --}}
                        <div class="flex items-center gap-1.5 mb-1.5 flex-wrap">
                            <div class="w-6 h-6 rounded-full shrink-0
                                        bg-violet-200 dark:bg-violet-800/50
                                        flex items-center justify-center
                                        text-[9px] font-bold
                                        text-violet-800 dark:text-violet-300">
                                {{ strtoupper(substr($note->reviewer?->full_name ?? 'R', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10.5px] font-semibold truncate
                                          text-violet-800 dark:text-violet-300">
                                    {{ $note->reviewer?->full_name ?? 'Reviewer' }}
                                </p>
                                <div class="flex items-center gap-1 text-[9px]
                                            text-violet-600 dark:text-violet-400">
                                    <span>{{ $note->created_at?->format('d M Y, H:i') }}</span>
                                    <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                                    <span>{{ $note->created_at?->diffForHumans() }}</span>
                                </div>
                            </div>

                            {{-- Status badge --}}
                            @if ($note->is_approved)
                                <span class="shrink-0 inline-flex items-center gap-0.5
                                             text-[9px] px-2 py-0.5 rounded-full font-semibold
                                             bg-emerald-100 text-emerald-700
                                             dark:bg-emerald-900/40 dark:text-emerald-300">
                                    <flux:icon.check class="size-2.5" />
                                    Approved
                                </span>
                            @else
                                <span class="shrink-0 inline-flex items-center gap-0.5
                                             text-[9px] px-2 py-0.5 rounded-full font-semibold
                                             bg-amber-100 text-amber-700
                                             dark:bg-amber-900/40 dark:text-amber-300">
                                    <flux:icon.arrow-path class="size-2.5" />
                                    Revision
                                </span>
                            @endif
                        </div>

                        {{-- Comment --}}
                        @if ($note->comment)
                            <p class="text-[10.5px] leading-relaxed
                                      text-violet-900 dark:text-violet-100">
                                {{ $note->comment }}
                            </p>
                        @endif

                        {{-- Recommendation --}}
                        @if ($note->recommendation)
                            <div class="mt-1.5 pt-1.5 border-t border-violet-200 dark:border-violet-800/60">
                                <p class="text-[9.5px] text-violet-700 dark:text-violet-300">
                                    <span class="font-semibold">Recommendation:</span>
                                    {{ $note->recommendation }}
                                </p>
                            </div>
                        @endif
                    </div>
                @empty
                    {{-- Empty state --}}
                    <div class="flex flex-col items-center gap-2 py-8 text-center">
                        <div class="w-11 h-11 rounded-2xl bg-slate-100 dark:bg-zinc-800
                                    flex items-center justify-center">
                            <flux:icon.clipboard-document-check
                                class="size-5 text-slate-400 dark:text-zinc-600" />
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                                No reviewer notes yet
                            </p>
                            <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                Notes will appear after the reviewer submits feedback.
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ══════════ FOOTER ══════════ --}}
        <div class="shrink-0 flex justify-end
                    px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                    bg-slate-50/50 dark:bg-zinc-900/50 rounded-b-3xl sm:rounded-b-3xl">
            <button type="button" @click="show = false"
                class="px-3 py-1.5 text-[11px] font-medium rounded-full
                       text-slate-700 dark:text-zinc-300
                       bg-white dark:bg-zinc-800
                       border border-slate-300 dark:border-zinc-600
                       hover:bg-slate-50 dark:hover:bg-zinc-700
                       shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-violet-500/15
                       hover:scale-[1.02] active:scale-[0.97]
                       transition-all duration-150">
                Close
            </button>
        </div>
    </div>
</div>
