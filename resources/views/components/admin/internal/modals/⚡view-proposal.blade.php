<?php

use App\Models\Proposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $proposal_id = null;
    public ?Proposal $proposal = null;

    #[On('open-view-proposal')]
    public function load(int $id): void
    {
        $proposal = Proposal::with([
            'author',
            'researchScheme',
            'period',
            'reviewer',
            'budgetProposals',
            'proposalMembers.user',      // ← NEW
            'proposalStudents',          // ← NEW
            'adminNotes'    => fn ($q) => $q->latest()->with('admin'),
            'reviewerNotes' => fn ($q) => $q->latest()->with('reviewer'),
        ])->find($id);

        if (! $proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        $this->proposal_id = $id;
        $this->proposal    = $proposal;

        $this->dispatch('show-view-proposal');
    }

    public function with(): array
    {
        $budgetTotal = 0;
        $budgetLimit = 0;

        if ($this->proposal) {
            $budgetTotal = (int) $this->proposal->budgetProposals->sum('amount');
            $budgetLimit = (int) ($this->proposal->researchScheme?->budget_limit ?? 0);
        }

        return [
            'budgetTotal'     => $budgetTotal,
            'budgetLimit'     => $budgetLimit,
            'budgetRemaining' => $budgetLimit - $budgetTotal,
            'budgetPercent'   => $budgetLimit > 0
                ? min(round(($budgetTotal / $budgetLimit) * 100, 1), 100)
                : 0,
        ];
    }
};
?>

<div
    x-data="{
        show: false,
        init() {
            window.addEventListener('show-view-proposal', () => { this.show = true; });
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
        class="flex max-h-[92vh] w-full sm:max-w-2xl flex-col overflow-hidden
               bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
               border border-slate-200 dark:border-zinc-700
               hover:shadow-indigo-500/15 transition-shadow duration-300"
        @click.stop>

        @if ($proposal)
            @php $meta = $proposal->statusMeta(); @endphp

            {{-- ══════════ HEADER (indigo → violet) ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-indigo-600 to-violet-500
                        px-4 py-3 rounded-t-3xl sm:rounded-t-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.eye class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-[13px] font-semibold text-white leading-tight line-clamp-2">
                                {{ $proposal->title }}
                            </h3>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span class="text-[9.5px] font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="text-[9.5px] text-white/75">
                                    {{ $proposal->is_research ? 'Research' : 'Dedication' }}
                                </span>
                            </div>
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

                {{-- ─────── METADATA ─────── --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div class="rounded-2xl border border-indigo-100 dark:border-indigo-900/40
                                bg-indigo-50/50 dark:bg-indigo-900/10 px-3 py-2.5">
                        <div class="flex items-center gap-1 mb-0.5">
                            <flux:icon.user class="size-2.5 text-indigo-500 dark:text-indigo-400" />
                            <p class="text-[9px] uppercase tracking-wider font-semibold
                                      text-indigo-600 dark:text-indigo-400">
                                Author
                            </p>
                        </div>
                        <p class="text-[11px] font-semibold truncate
                                  text-slate-900 dark:text-zinc-100">
                            {{ $proposal->author?->full_name ?? '—' }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-violet-100 dark:border-violet-900/40
                                bg-violet-50/50 dark:bg-violet-900/10 px-3 py-2.5">
                        <div class="flex items-center gap-1 mb-0.5">
                            <flux:icon.clipboard-document-check class="size-2.5 text-violet-500 dark:text-violet-400" />
                            <p class="text-[9px] uppercase tracking-wider font-semibold
                                      text-violet-600 dark:text-violet-400">
                                Reviewer
                            </p>
                        </div>
                        <p class="text-[11px] font-semibold truncate
                                  text-slate-900 dark:text-zinc-100">
                            {{ $proposal->reviewer?->full_name ?? 'Not assigned' }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-sky-100 dark:border-sky-900/40
                                bg-sky-50/50 dark:bg-sky-900/10 px-3 py-2.5">
                        <div class="flex items-center gap-1 mb-0.5">
                            <flux:icon.beaker class="size-2.5 text-sky-500 dark:text-sky-400" />
                            <p class="text-[9px] uppercase tracking-wider font-semibold
                                      text-sky-600 dark:text-sky-400">
                                Scheme
                            </p>
                        </div>
                        <p class="text-[11px] font-semibold truncate
                                  text-slate-900 dark:text-zinc-100">
                            {{ $proposal->researchScheme?->name ?? '—' }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-amber-100 dark:border-amber-900/40
                                bg-amber-50/50 dark:bg-amber-900/10 px-3 py-2.5">
                        <div class="flex items-center gap-1 mb-0.5">
                            <flux:icon.calendar-days class="size-2.5 text-amber-500 dark:text-amber-400" />
                            <p class="text-[9px] uppercase tracking-wider font-semibold
                                      text-amber-600 dark:text-amber-400">
                                Period
                            </p>
                        </div>
                        <p class="text-[11px] font-semibold truncate
                                  text-slate-900 dark:text-zinc-100">
                            {{ $proposal->period?->periode ?? '—' }}
                        </p>
                    </div>
                </div>

                {{-- ─────── KEYWORDS ─────── --}}
                @php
                    $keywords = array_filter(array_map('trim', explode(',', $proposal->keywords ?? '')));
                @endphp

                @if (count($keywords) > 0)
                    <div>
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <flux:icon.tag class="size-3 text-indigo-500 dark:text-indigo-400" />
                            <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                      text-slate-500 dark:text-zinc-400">
                                Keywords
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($keywords as $kw)
                                <span class="text-[9.5px] px-2 py-0.5 rounded-full font-medium
                                             bg-indigo-50 dark:bg-indigo-900/20
                                             text-indigo-700 dark:text-indigo-300
                                             border border-indigo-100 dark:border-indigo-900/40">
                                    {{ $kw }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ═══════════════════════════════════════════════════ --}}
                {{-- ─────── PROPOSAL MEMBERS (NEW) ─────── --}}
                {{-- ═══════════════════════════════════════════════════ --}}
                <div>
                    <div class="flex items-center gap-1.5 mb-1.5">
                        <flux:icon.user-group class="size-3 text-emerald-500 dark:text-emerald-400" />
                        <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                  text-slate-500 dark:text-zinc-400">
                            Proposal Members
                        </p>
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                     bg-emerald-100 text-emerald-700
                                     dark:bg-emerald-900/40 dark:text-emerald-300">
                            {{ 1 + $proposal->proposalMembers->count() + $proposal->proposalStudents->count() }}
                        </span>
                    </div>

                    <div class="space-y-2">

                        {{-- ─────── KETUA ─────── --}}
                        @if ($proposal->author)
                            <div class="rounded-2xl border border-emerald-200 dark:border-emerald-800/60
                                        bg-emerald-50/60 dark:bg-emerald-900/15 px-3 py-2.5">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <flux:icon.star class="size-2.5 text-emerald-600 dark:text-emerald-400" />
                                    <p class="text-[8.5px] uppercase tracking-wider font-bold
                                              text-emerald-700 dark:text-emerald-400">
                                        Ketua
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full shrink-0
                                                bg-emerald-200 dark:bg-emerald-800/50
                                                flex items-center justify-center
                                                text-[10px] font-bold
                                                text-emerald-700 dark:text-emerald-300">
                                        {{ strtoupper(substr($proposal->author->full_name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-semibold truncate
                                                  text-slate-900 dark:text-zinc-100">
                                            {{ $proposal->author->full_name }}
                                        </p>
                                        <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 truncate">
                                            NIDN: {{ $proposal->author->nidn ?? '—' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ─────── ANGGOTA DOSEN ─────── --}}
                        @if ($proposal->proposalMembers->isNotEmpty())
                            <div class="rounded-2xl border border-blue-200 dark:border-blue-800/60
                                        bg-blue-50/60 dark:bg-blue-900/15 px-3 py-2.5">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <flux:icon.user-group class="size-2.5 text-blue-600 dark:text-blue-400" />
                                    <p class="text-[8.5px] uppercase tracking-wider font-bold
                                              text-blue-700 dark:text-blue-400">
                                        Anggota Dosen
                                    </p>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                 bg-blue-100 text-blue-700
                                                 dark:bg-blue-900/40 dark:text-blue-300">
                                        {{ $proposal->proposalMembers->count() }}
                                    </span>
                                </div>
                                <div class="space-y-1.5">
                                    @foreach ($proposal->proposalMembers as $m)
                                        <div wire:key="pm-{{ $m->id }}"
                                            class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full shrink-0
                                                        bg-blue-200 dark:bg-blue-800/50
                                                        flex items-center justify-center
                                                        text-[9px] font-bold
                                                        text-blue-700 dark:text-blue-300">
                                                {{ strtoupper(substr($m->user?->full_name ?? '?', 0, 1)) }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[10.5px] font-medium truncate
                                                          text-slate-900 dark:text-zinc-100">
                                                    {{ $m->user?->full_name ?? '—' }}
                                                </p>
                                                <p class="text-[9px] text-slate-500 dark:text-zinc-500 truncate">
                                                    NIDN: {{ $m->user?->nidn ?? '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- ─────── ANGGOTA MAHASISWA ─────── --}}
                        @if ($proposal->proposalStudents->isNotEmpty())
                            <div class="rounded-2xl border border-violet-200 dark:border-violet-800/60
                                        bg-violet-50/60 dark:bg-violet-900/15 px-3 py-2.5">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <flux:icon.academic-cap class="size-2.5 text-violet-600 dark:text-violet-400" />
                                    <p class="text-[8.5px] uppercase tracking-wider font-bold
                                              text-violet-700 dark:text-violet-400">
                                        Anggota Mahasiswa
                                    </p>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                 bg-violet-100 text-violet-700
                                                 dark:bg-violet-900/40 dark:text-violet-300">
                                        {{ $proposal->proposalStudents->count() }}
                                    </span>
                                </div>
                                <div class="space-y-1.5">
                                    @foreach ($proposal->proposalStudents as $s)
                                        <div wire:key="ps-{{ $s->id }}"
                                            class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full shrink-0
                                                        bg-violet-200 dark:bg-violet-800/50
                                                        flex items-center justify-center
                                                        text-[9px] font-bold
                                                        text-violet-700 dark:text-violet-300">
                                                {{ strtoupper(substr($s->name, 0, 1)) }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[10.5px] font-medium truncate
                                                          text-slate-900 dark:text-zinc-100">
                                                    {{ $s->name }}
                                                </p>
                                                <p class="text-[9px] text-slate-500 dark:text-zinc-500 truncate">
                                                    NIM: {{ $s->nim }} · {{ $s->program_study }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- ─────── EMPTY STATE ─────── --}}
                        @if ($proposal->proposalMembers->isEmpty() && $proposal->proposalStudents->isEmpty())
                            <div class="rounded-2xl border border-dashed border-slate-200 dark:border-zinc-700
                                        bg-slate-50/60 dark:bg-zinc-800/30 px-3 py-4 text-center">
                                <p class="text-[10px] text-slate-400 dark:text-zinc-500 italic">
                                    Tidak ada anggota — hanya ketua.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ─────── SUMMARY ─────── --}}
                <div>
                    <div class="flex items-center gap-1.5 mb-1.5">
                        <flux:icon.document-text class="size-3 text-indigo-500 dark:text-indigo-400" />
                        <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                  text-slate-500 dark:text-zinc-400">
                            Summary
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 dark:border-zinc-700/70
                                bg-slate-50 dark:bg-zinc-800/40
                                px-3 py-2.5
                                text-[10.5px] leading-relaxed
                                text-slate-700 dark:text-zinc-300
                                max-h-40 overflow-y-auto">
                        {{ $proposal->summary }}
                    </div>
                </div>

                {{-- ─────── BUDGET ─────── --}}
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <flux:icon.banknotes class="size-3 text-emerald-500 dark:text-emerald-400" />
                            <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                      text-slate-500 dark:text-zinc-400">
                                Budget
                            </p>
                        </div>
                        <p class="text-[10.5px] font-bold text-slate-900 dark:text-zinc-100">
                            Rp {{ number_format($budgetTotal, 0, ',', '.') }}
                            <span class="text-slate-400 dark:text-zinc-500 font-normal text-[9.5px]">
                                / Rp {{ number_format($budgetLimit, 0, ',', '.') }}
                            </span>
                        </p>
                    </div>

                    <div class="h-1.5 rounded-full bg-slate-200 dark:bg-zinc-700 overflow-hidden mb-2">
                        <div class="h-full rounded-full
                                    bg-gradient-to-r from-indigo-500 to-violet-500
                                    transition-all duration-500"
                            style="width: {{ $budgetPercent }}%"></div>
                    </div>

                    <div class="space-y-1.5">
                        @forelse ($proposal->budgetProposals as $item)
                            <div class="flex items-center justify-between gap-3 px-3 py-1.5 rounded-full
                                        bg-slate-50 dark:bg-zinc-800/40
                                        border border-slate-100 dark:border-zinc-800/60">
                                <span class="text-[10.5px] text-slate-700 dark:text-zinc-300 truncate pl-0.5">
                                    {{ $item->item_name }}
                                </span>
                                <span class="text-[10.5px] font-semibold whitespace-nowrap
                                             text-slate-900 dark:text-zinc-100 pr-0.5">
                                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                                </span>
                            </div>
                        @empty
                            <p class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic text-center py-3">
                                No budget items yet.
                            </p>
                        @endforelse
                    </div>
                </div>

                {{-- ─────── ADMIN NOTES ─────── --}}
                @if ($proposal->adminNotes->isNotEmpty())
                    <div>
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <flux:icon.chat-bubble-left-right class="size-3 text-amber-500 dark:text-amber-400" />
                            <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                      text-slate-500 dark:text-zinc-400">
                                Admin Notes
                            </p>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                         bg-amber-100 text-amber-700
                                         dark:bg-amber-900/40 dark:text-amber-300">
                                {{ $proposal->adminNotes->count() }}
                            </span>
                        </div>
                        <div class="space-y-1.5">
                            @foreach ($proposal->adminNotes as $note)
                                <div class="rounded-2xl border border-amber-200 dark:border-amber-800/60
                                            bg-amber-50 dark:bg-amber-900/15 px-3 py-2.5">
                                    <div class="flex items-center gap-1.5 mb-1 text-[9.5px]">
                                        <span class="font-semibold text-amber-800 dark:text-amber-300">
                                            {{ $note->admin?->full_name ?? 'Admin' }}
                                        </span>
                                        <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                                        <span class="text-amber-600 dark:text-amber-400">
                                            {{ $note->created_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                    @if ($note->comment)
                                        <p class="text-[10.5px] leading-relaxed
                                                  text-amber-900 dark:text-amber-100">
                                            {{ $note->comment }}
                                        </p>
                                    @endif
                                    @if ($note->recommendation)
                                        <p class="mt-1 text-[9.5px] text-amber-700 dark:text-amber-300">
                                            <span class="font-semibold">Recommendation:</span>
                                            {{ $note->recommendation }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ─────── REVIEWER NOTES ─────── --}}
                @if ($proposal->reviewerNotes->isNotEmpty())
                    <div>
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <flux:icon.clipboard-document-check class="size-3 text-violet-500 dark:text-violet-400" />
                            <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                      text-slate-500 dark:text-zinc-400">
                                Reviewer Notes
                            </p>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                         bg-violet-100 text-violet-700
                                         dark:bg-violet-900/40 dark:text-violet-300">
                                {{ $proposal->reviewerNotes->count() }}
                            </span>
                        </div>
                        <div class="space-y-1.5">
                            @foreach ($proposal->reviewerNotes as $note)
                                <div class="rounded-2xl border border-violet-200 dark:border-violet-800/60
                                            bg-violet-50 dark:bg-violet-900/15 px-3 py-2.5">
                                    <div class="flex items-center gap-1.5 mb-1 flex-wrap text-[9.5px]">
                                        <span class="font-semibold text-violet-800 dark:text-violet-300">
                                            {{ $note->reviewer?->full_name ?? 'Reviewer' }}
                                        </span>
                                        <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                                        <span class="text-violet-600 dark:text-violet-400">
                                            {{ $note->created_at?->diffForHumans() }}
                                        </span>
                                        @if ($note->is_approved)
                                            <span class="ml-auto inline-flex items-center gap-0.5 text-[9px] px-2 py-0.5 rounded-full font-semibold
                                                         bg-emerald-100 text-emerald-700
                                                         dark:bg-emerald-900/40 dark:text-emerald-300">
                                                <flux:icon.check class="size-2.5" />
                                                Approved
                                            </span>
                                        @else
                                            <span class="ml-auto inline-flex items-center gap-0.5 text-[9px] px-2 py-0.5 rounded-full font-semibold
                                                         bg-amber-100 text-amber-700
                                                         dark:bg-amber-900/40 dark:text-amber-300">
                                                <flux:icon.arrow-path class="size-2.5" />
                                                Revision
                                            </span>
                                        @endif
                                    </div>
                                    @if ($note->comment)
                                        <p class="text-[10.5px] leading-relaxed
                                                  text-violet-900 dark:text-violet-100">
                                            {{ $note->comment }}
                                        </p>
                                    @endif
                                    @if ($note->recommendation)
                                        <p class="mt-1 text-[9.5px] text-violet-700 dark:text-violet-300">
                                            <span class="font-semibold">Recommendation:</span>
                                            {{ $note->recommendation }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
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
                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-indigo-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Close
                </button>
            </div>
        @endif
    </div>
</div>
