<?php

use App\Models\Proposal;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $proposal_id = null;
    public ?Proposal $proposal = null;

    #[On('open-view-proposal')]
    public function load(int $id): void
    {
        $this->proposal_id = $id;
        $this->proposal = Proposal::with([
            'author',
            'researchScheme',
            'period',
            'reviewer',
            'budgetProposals',
            'adminNotes' => fn ($q) => $q->latest(),
            'reviewerNotes' => fn ($q) => $q->latest(),
        ])->findOrFail($id);

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
            'budgetPercent'   => $budgetLimit > 0 ? min(round(($budgetTotal / $budgetLimit) * 100, 1), 100) : 0,
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
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-3xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        @if ($proposal)
            @php $meta = $proposal->statusMeta(); @endphp

            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r from-slate-700 to-slate-600
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.eye class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight line-clamp-2">
                                {{ $proposal->title }}
                            </h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="text-[10px] text-white/70">
                                    {{ $proposal->is_research ? 'Penelitian' : 'Pengabdian' }}
                                </span>
                            </div>
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

                {{-- Metadata --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Author</p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->author?->full_name ?? '—' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Reviewer</p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->reviewer?->full_name ?? 'Belum di-assign' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Skema</p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->researchScheme?->name ?? '—' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Periode</p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->period?->periode ?? '—' }}
                        </p>
                    </div>
                </div>

                {{-- Keywords --}}
                <div>
                    <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Keywords</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (array_filter(array_map('trim', explode(',', $proposal->keywords ?? ''))) as $kw)
                            <span class="text-[10px] px-2 py-0.5 rounded-full
                                         bg-slate-100 dark:bg-zinc-800
                                         text-slate-700 dark:text-zinc-300">
                                {{ $kw }}
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Summary --}}
                <div>
                    <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Ringkasan</p>
                    <p class="text-[11px] text-slate-700 dark:text-zinc-300 leading-relaxed whitespace-pre-wrap">
                        {{ $proposal->summary }}
                    </p>
                </div>

                {{-- Budget --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Anggaran</p>
                        <p class="text-[11px] font-bold text-slate-900 dark:text-zinc-100">
                            Rp {{ number_format($budgetTotal, 0, ',', '.') }}
                            <span class="text-slate-400 font-normal">/ Rp {{ number_format($budgetLimit, 0, ',', '.') }}</span>
                        </p>
                    </div>
                    <div class="h-1.5 rounded-full bg-slate-200 dark:bg-zinc-700 overflow-hidden mb-2">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $budgetPercent }}%"></div>
                    </div>
                    <div class="space-y-1.5">
                        @forelse ($proposal->budgetProposals as $item)
                            <div class="flex items-center justify-between gap-3 p-2 rounded-md
                                        bg-slate-50 dark:bg-zinc-800/40">
                                <span class="text-[11px] text-slate-700 dark:text-zinc-300 truncate">
                                    {{ $item->item_name }}
                                </span>
                                <span class="text-[11px] font-medium text-slate-900 dark:text-zinc-100 whitespace-nowrap">
                                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                                </span>
                            </div>
                        @empty
                            <p class="text-[11px] text-slate-400 dark:text-zinc-500 italic">Belum ada item anggaran.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Admin Notes --}}
                @if ($proposal->adminNotes->isNotEmpty())
                    <div>
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-2">
                            Admin Notes ({{ $proposal->adminNotes->count() }})
                        </p>
                        <div class="space-y-2">
                            @foreach ($proposal->adminNotes as $note)
                                <div class="rounded-lg border border-amber-200 dark:border-amber-800
                                            bg-amber-50 dark:bg-amber-900/20 p-2.5">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] font-medium text-amber-800 dark:text-amber-300">
                                            {{ $note->admin?->full_name ?? 'Admin' }}
                                        </span>
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
                                            <span class="font-semibold">Rekomendasi:</span> {{ $note->recommendation }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Reviewer Notes --}}
                @if ($proposal->reviewerNotes->isNotEmpty())
                    <div>
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-2">
                            Reviewer Notes ({{ $proposal->reviewerNotes->count() }})
                        </p>
                        <div class="space-y-2">
                            @foreach ($proposal->reviewerNotes as $note)
                                <div class="rounded-lg border border-violet-200 dark:border-violet-800
                                            bg-violet-50 dark:bg-violet-900/20 p-2.5">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] font-medium text-violet-800 dark:text-violet-300">
                                            {{ $note->reviewer?->full_name ?? 'Reviewer' }}
                                        </span>
                                        <span class="text-[9px] text-violet-600 dark:text-violet-400">
                                            {{ $note->created_at?->diffForHumans() }}
                                        </span>
                                        @if ($note->is_approved)
                                            <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                                                Approved
                                            </span>
                                        @endif
                                    </div>
                                    @if ($note->comment)
                                        <p class="text-[11px] text-violet-900 dark:text-violet-100 whitespace-pre-wrap">
                                            {{ $note->comment }}
                                        </p>
                                    @endif
                                    @if ($note->recommendation)
                                        <p class="text-[10px] text-violet-700 dark:text-violet-300 mt-1">
                                            <span class="font-semibold">Rekomendasi:</span> {{ $note->recommendation }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex justify-end gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">
                <flux:button type="button" @click="show = false" variant="ghost" size="sm">Tutup</flux:button>
            </div>
        @endif
    </div>
</div>
