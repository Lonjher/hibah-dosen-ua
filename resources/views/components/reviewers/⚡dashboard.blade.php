<?php

use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Download;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public function with(): array
    {
        $userId = auth()->id();

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

        $proposalTotal = array_sum($proposalStats);
        $progressTotal = array_sum($progressStats);


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
        $reviewGuidelines = Download::query()
            ->reviewGuidelines()  // scope: active + category = review_guideline
            ->ordered()
            ->take(6)
            ->get();

        return [
            'proposalStats'    => $proposalStats,
            'progressStats'    => $progressStats,
            'proposalTotal'    => $proposalTotal,
            'progressTotal'    => $progressTotal,
            'pendingProposals' => $pendingProposals,
            'pendingProgress'  => $pendingProgress,
            'reviewGuidelines'  => $reviewGuidelines,
        ];
    }
};
?>

<div class="p-3 sm:p-4 space-y-3">

    {{-- ══════════ HEADER ══════════ --}}
    <x-dashboard-header
        icon="clipboard-document-check"
        title="Reviewer Dashboard"
        leading="Overview of your review tasks." />

    {{-- ══════════ REVIEW GUIDELINES ══════════ --}}
    @if ($reviewGuidelines->isNotEmpty())
        <div class="rounded-2xl border border-violet-200 dark:border-violet-800/60
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <div class="px-3 py-2.5 border-b border-violet-200 dark:border-violet-800/60
                        bg-gradient-to-r from-violet-50/60 to-fuchsia-50/40
                        dark:from-violet-950/30 dark:to-fuchsia-950/20
                        flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-violet-100 dark:bg-violet-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon.book-open class="size-3.5 text-violet-600 dark:text-violet-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-heading text-[12.5px] font-semibold leading-tight
                            text-slate-900 dark:text-white">
                        {{ __("Review Guidelines") }}
                    </h3>
                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 leading-tight">
                        {{ __("Official documents from Admin to guide your review process") }}
                    </p>
                </div>
                <span class="shrink-0 text-[9px] font-bold px-1.5 py-0.5 rounded-full
                            bg-violet-100 text-violet-700
                            dark:bg-violet-900/40 dark:text-violet-300">
                    {{ $reviewGuidelines->count() }}
                </span>
            </div>

            <div class="p-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach ($reviewGuidelines as $doc)
                    @php $cat = $doc->categoryMeta(); @endphp
                    <a href="{{ $doc->public_url }}"
                    target="_blank"
                    rel="noopener"
                    wire:key="rg-{{ $doc->id }}"
                    class="group flex items-center gap-2.5 p-2 rounded-xl
                            border border-violet-100 dark:border-violet-900/40
                            bg-violet-50/40 dark:bg-violet-900/10
                            hover:bg-violet-100/60 dark:hover:bg-violet-900/25
                            hover:border-violet-300 dark:hover:border-violet-700
                            hover:scale-[1.01] active:scale-[0.99]
                            transition-all duration-200">

                        {{-- File icon --}}
                        <div class="w-9 h-9 rounded-xl shrink-0
                                    bg-white dark:bg-zinc-800
                                    ring-1 ring-violet-200 dark:ring-violet-800/60
                                    flex items-center justify-center
                                    group-hover:scale-110
                                    transition-transform duration-200">
                            <flux:icon.document-text class="size-4 text-violet-600 dark:text-violet-400" />
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-semibold leading-tight truncate
                                    text-slate-900 dark:text-white"
                            title="{{ $doc->title }}">
                                {{ $doc->title }}
                            </p>
                            @if ($doc->description)
                                <p class="text-[9.5px] leading-tight truncate mt-0.5
                                        text-slate-500 dark:text-zinc-400"
                                title="{{ $doc->description }}">
                                    {{ $doc->description }}
                                </p>
                            @endif
                            <div class="flex items-center gap-1.5 mt-0.5 text-[9px]
                                        text-slate-400 dark:text-zinc-500">
                                <span class="font-semibold">{{ $doc->file_extension }}</span>
                                <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                                <span>{{ $doc->file_size_human }}</span>
                                @if ($doc->download_count > 0)
                                    <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                                    <span>{{ $doc->download_count }}×</span>
                                @endif
                            </div>
                        </div>

                        {{-- Download icon --}}
                        <div class="shrink-0 w-7 h-7 rounded-full
                                    flex items-center justify-center
                                    bg-white dark:bg-zinc-800
                                    ring-1 ring-violet-200 dark:ring-violet-800/60
                                    group-hover:bg-violet-500
                                    group-hover:ring-violet-500
                                    transition-all duration-200">
                            <flux:icon.arrow-down-tray
                                class="size-3.5 text-violet-600 dark:text-violet-400
                                    group-hover:text-white
                                    transition-colors duration-200" />
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
    {{-- ══════════ SUMMARY BANNER ══════════ --}}
    @php
        $needsAction = $proposalStats['under_review'] + $progressStats['under_review'];
    @endphp

    @if ($needsAction > 0)
        <div class="rounded-2xl border border-violet-200 dark:border-violet-800/60
                    bg-gradient-to-r from-violet-50 to-fuchsia-50
                    dark:from-violet-900/20 dark:to-fuchsia-900/20
                    p-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/40
                        flex items-center justify-center shrink-0">
                <flux:icon.clock class="size-4 text-violet-600 dark:text-violet-400" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-semibold text-violet-700 dark:text-violet-300 uppercase tracking-wider leading-none">
                    Pending Reviews
                </p>
                <p class="text-xs font-semibold text-violet-900 dark:text-violet-100 mt-0.5 truncate">
                    You have
                    <span class="font-bold">{{ $needsAction }}</span>
                    {{ Str::plural('item', $needsAction) }}
                    waiting to be reviewed.
                </p>
            </div>
            <a href="{{ route('reviewer.review-proposal') }}" wire:navigate
                class="hidden sm:inline-flex shrink-0 items-center gap-1.5 px-3 py-1.5 rounded-full
                       text-[10.5px] font-medium text-white
                       bg-violet-600/90 hover:bg-violet-600
                       shadow-sm shadow-violet-500/20 hover:shadow-sm hover:shadow-violet-500/30
                       hover:scale-[1.02] active:scale-[0.97]
                       transition-all duration-150">
                Review Now
                <flux:icon.arrow-right class="size-3" />
            </a>
        </div>
    @else
        <div class="rounded-2xl border border-emerald-200 dark:border-emerald-800/60
                    bg-gradient-to-r from-emerald-50 to-teal-50
                    dark:from-emerald-900/20 dark:to-teal-900/20
                    p-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                        flex items-center justify-center shrink-0">
                <flux:icon.check-circle class="size-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider leading-none">
                    All Caught Up
                </p>
                <p class="text-xs font-semibold text-emerald-900 dark:text-emerald-100 mt-0.5 truncate">
                    No pending reviews at the moment. 🎉
                </p>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- PROPOSAL STATS                                             --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div>
        <div class="flex items-center gap-2 mb-2">
            <div class="w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-900/40
                        flex items-center justify-center shrink-0">
                <flux:icon.clipboard-document-check class="size-3 text-violet-600 dark:text-violet-400" />
            </div>
            <h3 class="font-heading text-[12px] font-semibold text-slate-900 dark:text-white flex-1">
                Proposal Reviews
            </h3>
            <span class="text-[10px] font-medium text-slate-500 dark:text-zinc-400">
                {{ $proposalTotal }} {{ Str::plural('proposal', $proposalTotal) }}
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
            @php
                $cards = [
                    [
                        'label' => 'Under Review',
                        'short' => 'REV',
                        'value' => $proposalStats['under_review'],
                        'color' => 'violet',
                        'icon'  => 'eye',
                        'href'  => route('reviewer.review-proposal') . '?statusFilter=under_review',
                    ],
                    [
                        'label' => 'Reviewer Revision',
                        'short' => 'RVR',
                        'value' => $proposalStats['reviewer_revision'],
                        'color' => 'orange',
                        'icon'  => 'arrow-path',
                        'href'  => route('reviewer.review-proposal') . '?statusFilter=reviewer_revision',
                    ],
                    [
                        'label' => 'Reviewer Approved',
                        'short' => 'RVA',
                        'value' => $proposalStats['reviewer_approved'],
                        'color' => 'cyan',
                        'icon'  => 'check-badge',
                        'href'  => route('reviewer.review-proposal') . '?statusFilter=reviewer_approved',
                    ],
                    [
                        'label' => 'Accepted',
                        'short' => 'ACC',
                        'value' => $proposalStats['accepted'],
                        'color' => 'emerald',
                        'icon'  => 'check-circle',
                        'href'  => route('reviewer.review-proposal') . '?statusFilter=accepted',
                    ],
                    [
                        'label' => 'Rejected',
                        'short' => 'REJ',
                        'value' => $proposalStats['rejected'],
                        'color' => 'rose',
                        'icon'  => 'x-circle',
                        'href'  => route('reviewer.review-proposal') . '?statusFilter=rejected',
                    ],
                ];
            @endphp

            @foreach ($cards as $card)
                <a href="{{ $card['href'] }}" wire:navigate
                   class="group rounded-xl border border-slate-200 dark:border-zinc-800
                          bg-white dark:bg-zinc-900 p-3 shadow-sm
                          hover:shadow-md hover:border-{{ $card['color'] }}-300
                          dark:hover:border-{{ $card['color'] }}-700
                          hover:scale-[1.02] active:scale-[0.98]
                          transition-all duration-200">

                    <div class="flex items-center justify-between">
                        <div class="w-7 h-7 rounded-lg
                                    bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
                                    flex items-center justify-center
                                    group-hover:scale-110 transition-transform">
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

                    <p class="mt-2 text-xl font-bold leading-none tabular-nums
                              text-slate-900 dark:text-white">
                        {{ number_format($card['value']) }}
                    </p>
                    <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 leading-tight truncate"
                       title="{{ $card['label'] }}">
                        {{ $card['label'] }}
                    </p>
                </a>
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
            <h3 class="font-heading text-[12px] font-semibold text-slate-900 dark:text-white flex-1">
                Progress Report Reviews
            </h3>
            <span class="text-[10px] font-medium text-slate-500 dark:text-zinc-400">
                {{ $progressTotal }} {{ Str::plural('report', $progressTotal) }}
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            @php
                $progressCards = [
                    [
                        'label' => 'Under Review',
                        'value' => $progressStats['under_review'],
                        'color' => 'violet',
                        'icon'  => 'eye',
                        'href'  => route('reviewer.review-progress-report') . '?statusFilter=under_review',
                    ],
                    [
                        'label' => 'Revision',
                        'value' => $progressStats['revised'],
                        'color' => 'amber',
                        'icon'  => 'arrow-path',
                        'href'  => route('reviewer.review-progress-report') . '?statusFilter=revised',
                    ],
                    [
                        'label' => 'Accepted',
                        'value' => $progressStats['accepted'],
                        'color' => 'emerald',
                        'icon'  => 'check-circle',
                        'href'  => route('reviewer.review-progress-report') . '?statusFilter=accepted',
                    ],
                    [
                        'label' => 'Rejected',
                        'value' => $progressStats['rejected'],
                        'color' => 'rose',
                        'icon'  => 'x-circle',
                        'href'  => route('reviewer.review-progress-report') . '?statusFilter=rejected',
                    ],
                ];
            @endphp

            @foreach ($progressCards as $card)
                <a href="{{ $card['href'] }}" wire:navigate
                   class="group rounded-xl border border-slate-200 dark:border-zinc-800
                          bg-white dark:bg-zinc-900 p-3 shadow-sm
                          hover:shadow-md hover:border-{{ $card['color'] }}-300
                          dark:hover:border-{{ $card['color'] }}-700
                          hover:scale-[1.02] active:scale-[0.98]
                          transition-all duration-200">

                    <div class="flex items-center justify-between">
                        <div class="w-7 h-7 rounded-lg
                                    bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
                                    flex items-center justify-center
                                    group-hover:scale-110 transition-transform">
                            <flux:icon :name="$card['icon']"
                                class="size-3.5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                        </div>
                        <flux:icon.arrow-up-right
                            class="size-3 text-slate-300 dark:text-zinc-600
                                   group-hover:text-{{ $card['color'] }}-500
                                   group-hover:translate-x-0.5 group-hover:-translate-y-0.5
                                   transition-all duration-200" />
                    </div>

                    <p class="mt-2 text-xl font-bold leading-none tabular-nums
                              text-slate-900 dark:text-white">
                        {{ number_format($card['value']) }}
                    </p>
                    <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 leading-tight truncate">
                        {{ $card['label'] }}
                    </p>
                </a>
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
            <div class="px-3 py-2.5 border-b border-slate-200 dark:border-zinc-800
                        flex items-center gap-2
                        bg-gradient-to-r from-violet-50/50 to-transparent
                        dark:from-violet-950/20 dark:to-transparent">
                <div class="w-7 h-7 rounded-full bg-violet-100 dark:bg-violet-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon.clipboard-document-check class="size-3.5 text-violet-600 dark:text-violet-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-heading text-[12.5px] font-semibold leading-tight
                               text-slate-900 dark:text-white">
                        Pending Proposals
                    </h3>
                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 leading-tight">
                        Waiting for your review
                    </p>
                </div>
                @if ($pendingProposals->isNotEmpty())
                    <span class="shrink-0 text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                 bg-violet-100 text-violet-700
                                 dark:bg-violet-900/40 dark:text-violet-300">
                        {{ $pendingProposals->count() }}
                    </span>
                @endif
                <a href="{{ route('reviewer.review-proposal') }}" wire:navigate
                    class="shrink-0 text-[10px] font-medium
                           text-violet-600 dark:text-violet-400 hover:underline">
                    View all →
                </a>
            </div>

            <div class="p-1.5 space-y-1 flex-1 overflow-y-auto">
                @forelse ($pendingProposals as $proposal)
                    <div class="group rounded-full bg-slate-50/60 dark:bg-zinc-800/40
                                border border-transparent
                                hover:border-violet-200 dark:hover:border-violet-800/60
                                hover:bg-white dark:hover:bg-zinc-900
                                pl-1 pr-1 py-1 flex items-center gap-2.5
                                transition-all duration-200">
                        <div class="w-7 h-7 rounded-full shrink-0
                                    {{ $proposal->is_research
                                        ? 'bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/40 dark:to-teal-900/20'
                                        : 'bg-gradient-to-br from-rose-100 to-pink-50 dark:from-rose-900/40 dark:to-pink-900/20' }}
                                    flex items-center justify-center
                                    group-hover:scale-105 transition-transform">
                            <flux:icon :name="$proposal->is_research ? 'beaker' : 'heart'"
                                class="size-3 {{ $proposal->is_research ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10.5px] font-semibold leading-tight truncate
                                      text-slate-900 dark:text-white"
                               title="{{ $proposal->title }}">
                                {{ $proposal->title }}
                            </p>
                            <p class="text-[9px] mt-0.5 truncate leading-tight
                                      text-slate-500 dark:text-zinc-400"
                               title="{{ $proposal->author?->full_name }}">
                                {{ $proposal->author?->full_name ?? '—' }}
                            </p>
                        </div>
                        <a href="{{ route('reviewer.review-proposal') }}" wire:navigate
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
                    <div class="px-3 py-8 text-center">
                        <div class="w-11 h-11 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                                    flex items-center justify-center mx-auto mb-2">
                            <flux:icon.check-circle class="size-5 text-emerald-500 dark:text-emerald-400" />
                        </div>
                        <p class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                            All caught up
                        </p>
                        <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                            No pending proposals to review.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── Pending Progress Reports ── --}}
        <div class="rounded-2xl border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden flex flex-col">
            <div class="px-3 py-2.5 border-b border-slate-200 dark:border-zinc-800
                        flex items-center gap-2
                        bg-gradient-to-r from-violet-50/50 to-transparent
                        dark:from-violet-950/20 dark:to-transparent">
                <div class="w-7 h-7 rounded-full bg-violet-100 dark:bg-violet-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon.document-chart-bar class="size-3.5 text-violet-600 dark:text-violet-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-heading text-[12.5px] font-semibold leading-tight
                               text-slate-900 dark:text-white">
                        Pending Progress Reports
                    </h3>
                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 leading-tight">
                        Waiting for your review
                    </p>
                </div>
                @if ($pendingProgress->isNotEmpty())
                    <span class="shrink-0 text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                 bg-violet-100 text-violet-700
                                 dark:bg-violet-900/40 dark:text-violet-300">
                        {{ $pendingProgress->count() }}
                    </span>
                @endif
                <a href="{{ route('reviewer.review-progress-report') }}" wire:navigate
                    class="shrink-0 text-[10px] font-medium
                           text-violet-600 dark:text-violet-400 hover:underline">
                    View all →
                </a>
            </div>

            <div class="p-1.5 space-y-1 flex-1 overflow-y-auto">
                @forelse ($pendingProgress as $report)
                    <div class="group rounded-full bg-slate-50/60 dark:bg-zinc-800/40
                                border border-transparent
                                hover:border-violet-200 dark:hover:border-violet-800/60
                                hover:bg-white dark:hover:bg-zinc-900
                                pl-1 pr-1 py-1 flex items-center gap-2.5
                                transition-all duration-200">
                        <div class="w-7 h-7 rounded-full shrink-0
                                    bg-violet-100 dark:bg-violet-900/40
                                    flex items-center justify-center
                                    group-hover:scale-105 transition-transform">
                            <flux:icon.document-chart-bar class="size-3 text-violet-600 dark:text-violet-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10.5px] font-semibold leading-tight truncate
                                      text-slate-900 dark:text-white"
                               title="{{ $report->proposal?->title }}">
                                {{ $report->proposal?->title ?? '—' }}
                            </p>
                            <p class="text-[9px] mt-0.5 truncate leading-tight
                                      text-slate-500 dark:text-zinc-400"
                               title="{{ $report->keyword }}">
                                Keyword: {{ $report->keyword }}
                            </p>
                        </div>
                        <a href="{{ route('reviewer.review-progress-report') }}" wire:navigate
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
                    <div class="px-3 py-8 text-center">
                        <div class="w-11 h-11 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                                    flex items-center justify-center mx-auto mb-2">
                            <flux:icon.check-circle class="size-5 text-emerald-500 dark:text-emerald-400" />
                        </div>
                        <p class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                            All caught up
                        </p>
                        <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                            No pending progress reports to review.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
