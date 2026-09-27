<?php

use App\Models\Proposal;
use App\Models\ReviewerNote;
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

    #[On('open-reviewer-notes')]
    public function load(int $id, string $type = 'proposal'): void
    {
        $this->noteable_id   = $id;
        $this->noteable_type = $type;

        $this->subject = match ($type) {
            'proposal'        => Proposal::find($id)?->title ?? '',
            'progress_report' => \App\Models\ProgressReport::find($id)
                ?->proposal?->title ?? '',
            default           => '',
        };

        // Ambil reviewer yang di-assign (untuk info)
        if ($type === 'proposal') {
            $this->reviewer_id = Proposal::find($id)?->reviewer_id;
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
                ? \App\Models\User::find($this->reviewer_id)
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
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-lg flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <div class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.clipboard-document-check class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                Reviewer Notes
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5 leading-snug">
                                Riwayat catatan reviewer.
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

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                {{-- Subject --}}
                @if ($subject)
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                            Subjek
                        </p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1 line-clamp-2">
                            {{ $subject }}
                        </p>
                        @if ($reviewer)
                            <div class="flex items-center gap-2 mt-2 pt-2
                                        border-t border-slate-200 dark:border-zinc-700">
                                <div class="w-6 h-6 rounded-full
                                            bg-violet-100 dark:bg-violet-900/40
                                            flex items-center justify-center text-[9px] font-bold
                                            text-violet-700 dark:text-violet-300">
                                    {{ strtoupper(substr($reviewer->full_name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-slate-500 dark:text-zinc-400">Reviewer</p>
                                    <p class="text-[11px] font-semibold text-slate-700 dark:text-zinc-200 truncate">
                                        {{ $reviewer->full_name }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Notes List --}}
                <div class="space-y-2">
                    <p class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                        Total Catatan ({{ $notes->count() }})
                    </p>

                    @forelse ($notes as $note)
                        <div wire:key="reviewer-note-{{ $note->id }}"
                            class="rounded-lg border border-violet-200 dark:border-violet-800
                                   bg-violet-50 dark:bg-violet-900/20 p-3">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-6 h-6 rounded-full
                                            bg-violet-200 dark:bg-violet-800/50
                                            flex items-center justify-center text-[9px] font-bold
                                            text-violet-800 dark:text-violet-300">
                                    {{ strtoupper(substr($note->reviewer?->full_name ?? 'R', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] font-semibold text-violet-800 dark:text-violet-300 truncate">
                                        {{ $note->reviewer?->full_name ?? 'Reviewer' }}
                                    </p>
                                    <p class="text-[9px] text-violet-600 dark:text-violet-400">
                                        {{ $note->created_at?->format('d M Y, H:i') }}
                                        · {{ $note->created_at?->diffForHumans() }}
                                    </p>
                                </div>
                                @if ($note->is_approved)
                                    <span class="text-[9px] px-2 py-0.5 rounded-full font-semibold shrink-0
                                                 bg-emerald-100 text-emerald-700
                                                 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        ✓ Approved
                                    </span>
                                @else
                                    <span class="text-[9px] px-2 py-0.5 rounded-full font-semibold shrink-0
                                                 bg-rose-100 text-rose-700
                                                 dark:bg-rose-900/40 dark:text-rose-300">
                                        ✗ Rejected
                                    </span>
                                @endif
                            </div>

                            @if ($note->comment)
                                <p class="text-[11px] text-violet-900 dark:text-violet-100 whitespace-pre-wrap">
                                    {{ $note->comment }}
                                </p>
                            @endif

                            @if ($note->recommendation)
                                <div class="mt-2 pt-2 border-t border-violet-200 dark:border-violet-800">
                                    <p class="text-[10px] text-violet-700 dark:text-violet-300">
                                        <span class="font-semibold">Rekomendasi:</span> {{ $note->recommendation }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center gap-2 py-8">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-zinc-800
                                        flex items-center justify-center">
                                <flux:icon.clipboard-document-check class="size-6 text-slate-400 dark:text-zinc-600" />
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                                Belum ada catatan reviewer.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex justify-end gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">
                <flux:button type="button" @click="show = false" variant="ghost" size="sm">Tutup</flux:button>
            </div>
        </div>
    </div>
</div>
