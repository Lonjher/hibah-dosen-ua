<?php

use App\Models\ProgressReport;
use App\Models\Proposal;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public function with(): array
    {
        $userId = auth()->id();

        /* ============================================================
         |  PROPOSAL STATS
         |  Reviewer hanya lihat proposal yang di-assign ke mereka.
         |  Status yang relevan untuk reviewer:
         |  - UNDER_REVIEW       : perlu direview
         |  - REVIEWER_REVISION  : sudah direview, user sedang revisi
         |  - REVIEWER_APPROVED  : sudah approve semua, tunggu admin
         |  - ACCEPTED / REJECTED: final
         ============================================================ */

        $proposalStats = [
            'under_review' => Proposal::where('reviewer_id', $userId)
                ->where('status', Proposal::STATUS_UNDER_REVIEW)
                ->count(),

            'reviewer_revision' => Proposal::where('reviewer_id', $userId)
                ->where('status', Proposal::STATUS_REVIEWER_REVISION)
                ->count(),

            'reviewer_approved' => Proposal::where('reviewer_id', $userId)
                ->where('status', Proposal::STATUS_REVIEWER_APPROVED)
                ->count(),

            'accepted' => Proposal::where('reviewer_id', $userId)
                ->where('status', Proposal::STATUS_ACCEPTED)
                ->count(),

            'rejected' => Proposal::where('reviewer_id', $userId)
                ->where('status', Proposal::STATUS_REJECTED)
                ->count(),
        ];

        /* ============================================================
         |  PROGRESS REPORT STATS
         |  Status internal ProgressReport tetap 6 status lama:
         |  pending, submitted, under_review, revised, accepted, rejected
         ============================================================ */

        $progressStats = [
            'under_review' => ProgressReport::where('reviewer_id', $userId)
                ->where('status', 'under_review')
                ->count(),

            'revised' => ProgressReport::where('reviewer_id', $userId)
                ->where('status', 'revised')
                ->count(),

            'accepted' => ProgressReport::where('reviewer_id', $userId)
                ->where('status', 'accepted')
                ->count(),

            'rejected' => ProgressReport::where('reviewer_id', $userId)
                ->where('status', 'rejected')
                ->count(),
        ];

        /* ============================================================
         |  PENDING ITEMS — yang perlu segera direview
         ============================================================ */

        $pendingProposals = Proposal::with(['author', 'researchScheme'])
            ->where('reviewer_id', $userId)
            ->where('status', Proposal::STATUS_UNDER_REVIEW)
            ->latest()
            ->take(5)
            ->get();

        $pendingProgress = ProgressReport::with(['proposal.author'])
            ->where('reviewer_id', $userId)
            ->where('status', 'under_review')
            ->latest()
            ->take(5)
            ->get();

        return [
            'proposalStats'    => $proposalStats,
            'progressStats'    => $progressStats,
            'pendingProposals' => $pendingProposals,
            'pendingProgress'  => $pendingProgress,
        ];
    }
};
?>

<div class="p-3 sm:p-4 space-y-3">

    <x-dashboard-header icon="clipboard-document-check" title="Reviewer Dashboard"
        leading="Overview of your review tasks." />

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- PROPOSAL STATS                                             --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div>
        <div class="flex items-center gap-2 mb-2">
            <div class="w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-900/40
                        flex items-center justify-center shrink-0">
                <flux:icon.clipboard-document-check class="size-3 text-violet-600 dark:text-violet-400" />
            </div>
            <h3 class="font-heading text-[12px] font-semibold text-slate-900 dark:text-white">
                Proposal
            </h3>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-2">
            @php
                $cards = [
                    [
                        'label' => 'Under Review',
                        'short' => 'REV',
                        'value' => $proposalStats['under_review'],
                        'color' => 'violet',
                        'icon'  => 'eye',
                    ],
                    [
                        'label' => 'Reviewer Revision',
                        'short' => 'RVR',
                        'value' => $proposalStats['reviewer_revision'],
                        'color' => 'orange',
                        'icon'  => 'arrow-path',
                    ],
                    [
                        'label' => 'Reviewer Approved',
                        'short' => 'RVA',
                        'value' => $proposalStats['reviewer_approved'],
                        'color' => 'cyan',
                        'icon'  => 'check-badge',
                    ],
                    [
                        'label' => 'Accepted',
                        'short' => 'ACC',
                        'value' => $proposalStats['accepted'],
                        'color' => 'emerald',
                        'icon'  => 'check-circle',
                    ],
                    [
                        'label' => 'Rejected',
                        'short' => 'REJ',
                        'value' => $proposalStats['rejected'],
                        'color' => 'rose',
                        'icon'  => 'x-circle',
                    ],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="rounded-xl border border-slate-200 dark:border-zinc-800
                            bg-white dark:bg-zinc-900 p-3 shadow-sm
                            hover:shadow-md hover:border-{{ $card['color'] }}-300
                            dark:hover:border-{{ $card['color'] }}-700
                            transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div class="w-7 h-7 rounded-full bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
                                    flex items-center justify-center">
                            <flux:icon :name="$card['icon']"
                                class="size-3.5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                        </div>
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                     bg-{{ $card['color'] }}-100 text-{{ $card['color'] }}-700
                                     dark:bg-{{ $card['color'] }}-900/40 dark:text-{{ $card['color'] }}-300"
                              title="{{ $card['label'] }}">
                            {{ $card['short'] }}
                        </span>
                    </div>
                    <p class="mt-2 text-xl font-bold text-slate-900 dark:text-white leading-none tabular-nums">
                        {{ number_format($card['value']) }}
                    </p>
                    <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 leading-tight truncate"
                       title="{{ $card['label'] }}">
                        {{ $card['label'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- PROGRESS REPORT STATS                                      --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div>
        <div class="flex items-center gap-2 mb-2">
            <div class="w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-900/40
                        flex items-center justify-center shrink-0">
                <flux:icon.document-chart-bar class="size-3 text-violet-600 dark:text-violet-400" />
            </div>
            <h3 class="font-heading text-[12px] font-semibold text-slate-900 dark:text-white">
                Progress Report
            </h3>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
            @php
                $progressCards = [
                    [
                        'label' => 'Under Review',
                        'value' => $progressStats['under_review'],
                        'color' => 'violet',
                        'icon'  => 'eye',
                    ],
                    [
                        'label' => 'Revision',
                        'value' => $progressStats['revised'],
                        'color' => 'amber',
                        'icon'  => 'arrow-path',
                    ],
                    [
                        'label' => 'Accepted',
                        'value' => $progressStats['accepted'],
                        'color' => 'emerald',
                        'icon'  => 'check-circle',
                    ],
                    [
                        'label' => 'Rejected',
                        'value' => $progressStats['rejected'],
                        'color' => 'rose',
                        'icon'  => 'x-circle',
                    ],
                ];
            @endphp

            @foreach ($progressCards as $card)
                <div class="rounded-xl border border-slate-200 dark:border-zinc-800
                            bg-white dark:bg-zinc-900 p-3 shadow-sm
                            hover:shadow-md hover:border-{{ $card['color'] }}-300
                            dark:hover:border-{{ $card['color'] }}-700
                            transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div class="w-7 h-7 rounded-full bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
                                    flex items-center justify-center">
                            <flux:icon :name="$card['icon']"
                                class="size-3.5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                        </div>
                        <flux:icon.arrow-up-right class="size-3 text-slate-300 dark:text-zinc-600" />
                    </div>
                    <p class="mt-2 text-xl font-bold text-slate-900 dark:text-white leading-none tabular-nums">
                        {{ number_format($card['value']) }}
                    </p>
                    <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 leading-tight truncate">
                        {{ $card['label'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- PENDING ITEMS                                              --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

        {{-- ── Pending Proposals ── --}}
        <div class="rounded-2xl border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden flex flex-col">
            <div class="px-3 py-2 border-b border-slate-200 dark:border-zinc-800
                        flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon.clipboard-document-check class="size-3 text-violet-600 dark:text-violet-400" />
                </div>
                <h3 class="font-heading text-[12px] font-semibold text-slate-900 dark:text-white flex-1">
                    Pending Proposals
                </h3>
                <a href="{{ route('reviewer.review-proposal') }}"
                    class="text-[10px] font-medium text-violet-600 dark:text-violet-400 hover:underline shrink-0">
                    View all →
                </a>
            </div>
            <div class="p-1.5 space-y-1 flex-1 overflow-y-auto">
                @forelse ($pendingProposals as $proposal)
                    <div class="rounded-full bg-slate-50/60 dark:bg-zinc-800/40
                                border border-transparent
                                hover:border-slate-200 dark:hover:border-zinc-700
                                hover:bg-white dark:hover:bg-zinc-900
                                pl-1 pr-1 py-1 flex items-center gap-2.5
                                transition-all duration-200">
                        <div class="w-7 h-7 rounded-full shrink-0
                                    {{ $proposal->is_research
                                        ? 'bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/40 dark:to-teal-900/20'
                                        : 'bg-gradient-to-br from-rose-100 to-pink-50 dark:from-rose-900/40 dark:to-pink-900/20' }}
                                    flex items-center justify-center">
                            <flux:icon :name="$proposal->is_research ? 'beaker' : 'heart'"
                                class="size-3 {{ $proposal->is_research ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10.5px] font-semibold text-slate-900 dark:text-white line-clamp-1 leading-tight">
                                {{ $proposal->title }}
                            </p>
                            <p class="text-[9px] text-slate-500 dark:text-zinc-400 mt-0.5 line-clamp-1 leading-tight">
                                {{ $proposal->author?->full_name ?? '—' }}
                            </p>
                        </div>
                        <a href="{{ route('reviewer.review-proposal') }}"
                            class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                                   text-[9.5px] font-semibold text-white
                                   bg-violet-600/90 hover:bg-violet-600
                                   shadow-sm shadow-violet-500/20 hover:shadow-sm hover:shadow-violet-500/30
                                   hover:scale-[1.02] active:scale-[0.97]
                                   transition-all duration-150">
                            <flux:icon.arrow-right class="size-2.5" />
                            Review
                        </a>
                    </div>
                @empty
                    <div class="px-3 py-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                                    flex items-center justify-center mx-auto mb-2">
                            <flux:icon.check-circle class="size-5 text-emerald-500 dark:text-emerald-400" />
                        </div>
                        <p class="text-[10.5px] text-slate-500 dark:text-zinc-400">
                            No pending proposals. 🎉
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── Pending Progress Reports ── --}}
        <div class="rounded-2xl border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden flex flex-col">
            <div class="px-3 py-2 border-b border-slate-200 dark:border-zinc-800
                        flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon.document-chart-bar class="size-3 text-violet-600 dark:text-violet-400" />
                </div>
                <h3 class="font-heading text-[12px] font-semibold text-slate-900 dark:text-white flex-1">
                    Pending Progress Reports
                </h3>
                <a href="{{ route('reviewer.review-progress-report') }}"
                    class="text-[10px] font-medium text-violet-600 dark:text-violet-400 hover:underline shrink-0">
                    View all →
                </a>
            </div>
            <div class="p-1.5 space-y-1 flex-1 overflow-y-auto">
                @forelse ($pendingProgress as $report)
                    <div class="rounded-full bg-slate-50/60 dark:bg-zinc-800/40
                                border border-transparent
                                hover:border-slate-200 dark:hover:border-zinc-700
                                hover:bg-white dark:hover:bg-zinc-900
                                pl-1 pr-1 py-1 flex items-center gap-2.5
                                transition-all duration-200">
                        <div class="w-7 h-7 rounded-full shrink-0
                                    bg-violet-100 dark:bg-violet-900/40
                                    flex items-center justify-center">
                            <flux:icon.document-chart-bar class="size-3 text-violet-600 dark:text-violet-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10.5px] font-semibold text-slate-900 dark:text-white line-clamp-1 leading-tight">
                                {{ $report->proposal?->title ?? '—' }}
                            </p>
                            <p class="text-[9px] text-slate-500 dark:text-zinc-400 mt-0.5 line-clamp-1 leading-tight">
                                Keyword: {{ $report->keyword }}
                            </p>
                        </div>
                        <a href="{{ route('reviewer.review-progress-report') }}"
                            class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                                   text-[9.5px] font-semibold text-white
                                   bg-violet-600/90 hover:bg-violet-600
                                   shadow-sm shadow-violet-500/20 hover:shadow-sm hover:shadow-violet-500/30
                                   hover:scale-[1.02] active:scale-[0.97]
                                   transition-all duration-150">
                            <flux:icon.arrow-right class="size-2.5" />
                            Review
                        </a>
                    </div>
                @empty
                    <div class="px-3 py-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                                    flex items-center justify-center mx-auto mb-2">
                            <flux:icon.check-circle class="size-5 text-emerald-500 dark:text-emerald-400" />
                        </div>
                        <p class="text-[10.5px] text-slate-500 dark:text-zinc-400">
                            No pending progress reports. 🎉
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
