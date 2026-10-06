<?php

use App\Models\ExternalProposal;
use App\Models\Period;
use App\Models\Proposal;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public string $periodFilter = 'all';

    public function mount(): void
    {
        $active = Period::where('is_active', true)->first();
        if ($active) {
            $this->periodFilter = (string) $active->id;
        } else {
            $latest = Period::orderByDesc('open_from')->orderByDesc('id')->first();
            $this->periodFilter = $latest ? (string) $latest->id : 'all';
        }
    }

    protected function getSelectedPeriod(): ?Period
    {
        return $this->periodFilter === 'all' ? null : Period::find((int) $this->periodFilter);
    }

    public function with(): array
    {
        $userId = auth()->id();
        $period = $this->getSelectedPeriod();

        $proposalQuery = Proposal::where('user_id', $userId)->when($period, fn($q) => $q->where('period_id', $period->id));

        $externalQuery = ExternalProposal::where('user_id', $userId)->when($period, fn($q) => $q->whereBetween('start_date', [$period->open_from, $period->open_to]));

        $myStats = [
            'total' => (clone $proposalQuery)->count(),
            'research' => (clone $proposalQuery)->where('is_research', true)->count(),
            'dedication' => (clone $proposalQuery)->where('is_research', false)->count(),
            'accepted' => (clone $proposalQuery)->where('status', 'accepted')->count(),
            'pending' => (clone $proposalQuery)->whereIn('status', ['pending', 'submitted', 'under_review'])->count(),
            'external_research' => (clone $externalQuery)->where('is_research', true)->count(),
            'external_dedication' => (clone $externalQuery)->where('is_research', false)->count(),
        ];

        $activePeriod = Period::where('is_active', true)->first();

        $recentProposals = (clone $proposalQuery)
            ->with(['researchScheme', 'reviewer'])
            ->latest()
            ->take(5)
            ->get();

        $needsAction = (clone $proposalQuery)
            ->with(['progressReport', 'finalReport', 'output'])
            ->where('status', 'accepted')
            ->get()
            ->filter(function ($p) {
                if ($p->progressReport === null) {
                    return true;
                }
                if ($p->progressReport->status === 'accepted' && $p->finalReport === null) {
                    return true;
                }
                if ($p->finalReport?->status === 'accepted' && $p->output === null) {
                    return true;
                }
                if ($p->progressReport?->status === 'revised') {
                    return true;
                }
                if ($p->finalReport?->status === 'revised') {
                    return true;
                }
                if ($p->output?->status === 'revised') {
                    return true;
                }
                return false;
            })
            ->take(5);

        // Chart data
        $allProposals = Proposal::where('user_id', $userId)->when($period, fn($q) => $q->where('period_id', $period->id))->selectRaw('YEAR(created_at) as year')->selectRaw('SUM(CASE WHEN is_research = 1 THEN 1 ELSE 0 END) as research')->selectRaw('SUM(CASE WHEN is_research = 0 THEN 1 ELSE 0 END) as dedication')->groupByRaw('YEAR(created_at)')->orderBy('year')->get();

        $allExternal = ExternalProposal::where('user_id', $userId)->when($period, fn($q) => $q->whereBetween('start_date', [$period->open_from, $period->open_to]))->whereNotNull('start_date')->selectRaw('YEAR(start_date) as year')->selectRaw('SUM(CASE WHEN is_research = 1 THEN 1 ELSE 0 END) as research')->selectRaw('SUM(CASE WHEN is_research = 0 THEN 1 ELSE 0 END) as dedication')->groupByRaw('YEAR(start_date)')->orderBy('year')->get();

        $years = $allProposals->pluck('year')->merge($allExternal->pluck('year'))->filter()->unique()->sort()->values();

        $chartData = [
            'labels' => $years,
            'research' => $years->map(fn($y) => (int) (optional($allProposals->firstWhere('year', $y))->research ?? 0))->values(),
            'dedication' => $years->map(fn($y) => (int) (optional($allProposals->firstWhere('year', $y))->dedication ?? 0))->values(),
            'ext_research' => $years->map(fn($y) => (int) (optional($allExternal->firstWhere('year', $y))->research ?? 0))->values(),
            'ext_dedication' => $years->map(fn($y) => (int) (optional($allExternal->firstWhere('year', $y))->dedication ?? 0))->values(),
        ];

        return [
            'myStats' => $myStats,
            'activePeriod' => $activePeriod,
            'recentProposals' => $recentProposals,
            'needsAction' => $needsAction,
            'chartData' => $chartData,
            'periods' => Period::orderByDesc('open_from')->orderByDesc('id')->get(),
            'selectedPeriod' => $period,
        ];
    }
};
?>

<div class="p-3 sm:p-4 space-y-3">

    {{-- ══════════ HEADER + PERIOD FILTER ══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
        <x-dashboard-header icon="home" title="Dashboard"
            leading="Welcome! Here is a summary of your grants." />

        <div class="flex items-center gap-1.5 shrink-0 sm:mt-1">
            <div
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                        bg-white dark:bg-zinc-900
                        border border-slate-200 dark:border-zinc-800
                        shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50">
                <flux:icon.calendar-days class="size-3 text-slate-400 dark:text-zinc-500 shrink-0" />
                <select wire:model.live="periodFilter"
                    class="text-[10.5px] font-medium bg-transparent border-0
                           text-slate-700 dark:text-zinc-200
                           focus:outline-none focus:ring-0 cursor-pointer
                           pr-5 pl-0 py-0
                           [&>option]:text-slate-700 dark:[&>option]:text-zinc-200
                           [&>option]:bg-white dark:[&>option]:bg-zinc-900">
                    <option value="all">All Periods</option>
                    @foreach ($periods as $p)
                        <option value="{{ $p->id }}">{{ $p->periode }}</option>
                    @endforeach
                </select>
            </div>

            <div wire:loading wire:target="periodFilter"
                class="w-3.5 h-3.5 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                       flex items-center justify-center shrink-0">
                <svg class="animate-spin size-2.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24"
                    fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                        opacity=".25" />
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                </svg>
            </div>
        </div>
    </div>

    {{-- ══════════ ACTIVE PERIOD BANNER ══════════ --}}
    @if ($activePeriod)
        <div
            class="rounded-2xl border border-emerald-200 dark:border-emerald-800
                bg-gradient-to-r from-emerald-50 to-teal-50
                dark:from-emerald-900/20 dark:to-teal-900/20 p-3
                flex items-center gap-3">
            <div
                class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center shrink-0">
                <flux:icon.calendar-days class="size-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="flex-1 min-w-0">
                <p
                    class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider leading-none">
                    Active Period
                </p>
                <p class="text-xs font-semibold text-emerald-900 dark:text-emerald-100 mt-0.5 truncate">
                    {{ $activePeriod->periode }}
                    <span class="text-[10px] font-normal text-emerald-600 dark:text-emerald-400 ml-1">
                        {{ $activePeriod->open_from?->format('d M Y') ?? '—' }}
                        to
                        {{ $activePeriod->open_to?->format('d M Y') ?? '—' }}
                    </span>
                </p>
            </div>
            <a href="{{ route('user.internal.manage-researches') }}" wire:navigate
                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                   text-[10.5px] font-medium text-white
                   bg-emerald-600/90 hover:bg-emerald-600
                   shadow-sm shadow-emerald-500/20 hover:shadow-sm hover:shadow-emerald-500/30
                   hover:scale-[1.02] active:scale-[0.97]
                   transition-all duration-150">
                New Proposal
            </a>
        </div>
    @endif

    {{-- ══════════ STAT CARDS ══════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2">
        @php
            $cards = [
                [
                    'label' => 'Total Proposals',
                    'value' => $myStats['total'],
                    'color' => 'slate',
                    'icon' => 'document-text',
                ],
                ['label' => 'Research', 'value' => $myStats['research'], 'color' => 'emerald', 'icon' => 'beaker'],
                ['label' => 'Community Service', 'value' => $myStats['dedication'], 'color' => 'rose', 'icon' => 'heart'],
                ['label' => 'Accepted', 'value' => $myStats['accepted'], 'color' => 'violet', 'icon' => 'check-circle'],
                [
                    'label' => 'External Research',
                    'value' => $myStats['external_research'],
                    'color' => 'indigo',
                    'icon' => 'globe-alt',
                ],
                [
                    'label' => 'External Community Service',
                    'value' => $myStats['external_dedication'],
                    'color' => 'amber',
                    'icon' => 'gift',
                ],
            ];
        @endphp

        @foreach ($cards as $card)
            <div
                class="rounded-xl border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 p-3 shadow-sm
                    hover:shadow-md hover:border-{{ $card['color'] }}-300
                    dark:hover:border-{{ $card['color'] }}-700
                    transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div
                        class="w-7 h-7 rounded-lg bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
                            flex items-center justify-center">
                        <flux:icon :name="$card['icon']"
                            class="size-3.5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                    </div>
                    <flux:icon.arrow-up-right class="size-3 text-slate-300 dark:text-zinc-600" />
                </div>
                <p class="mt-2 text-xl font-bold text-slate-900 dark:text-white leading-none">
                    {{ number_format($card['value']) }}
                </p>
                <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 leading-tight truncate">
                    {{ $card['label'] }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- ══════════ NEEDS ACTION ══════════ --}}
    @if ($needsAction->isNotEmpty())
        <div
            class="rounded-2xl border border-amber-200 dark:border-amber-800
                    bg-amber-50 dark:bg-amber-900/20 overflow-hidden">
            <div class="px-3 py-2 border-b border-amber-200 dark:border-amber-800 flex items-center gap-2">
                <div
                    class="w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="size-3 text-amber-600 dark:text-amber-400" />
                </div>
                <h3 class="font-heading text-[12px] font-semibold text-amber-900 dark:text-amber-100 flex-1">
                    Action Required
                </h3>
                <span
                    class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                             bg-amber-200 text-amber-800
                             dark:bg-amber-800 dark:text-amber-200">
                    {{ $needsAction->count() }}
                </span>
            </div>
            <div class="p-1.5 space-y-1">
                @foreach ($needsAction as $proposal)
                    @php
                        $detail = '';
                        if ($proposal->progressReport === null) {
                            $detail = 'Upload Progress Report';
                        } elseif ($proposal->progressReport->status === 'revised') {
                            $detail = 'Revise Progress Report';
                        } elseif ($proposal->progressReport->status === 'accepted' && $proposal->finalReport === null) {
                            $detail = 'Upload Final Report';
                        } elseif ($proposal->finalReport?->status === 'revised') {
                            $detail = 'Revise Final Report';
                        } elseif ($proposal->finalReport?->status === 'accepted' && $proposal->output === null) {
                            $detail = 'Upload Output';
                        } elseif ($proposal->output?->status === 'revised') {
                            $detail = 'Revise Output';
                        }
                    @endphp
                    <div
                        class="rounded-full bg-white dark:bg-zinc-900/60
                                border border-amber-200/70 dark:border-amber-800/60
                                pl-1 pr-1 py-1 flex items-center gap-2.5">
                        <div
                            class="w-7 h-7 rounded-full shrink-0
                                    {{ $proposal->is_research
                                        ? 'bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/40 dark:to-teal-900/20'
                                        : 'bg-gradient-to-br from-rose-100 to-pink-50 dark:from-rose-900/40 dark:to-pink-900/20' }}
                                    flex items-center justify-center">
                            <flux:icon :name="$proposal->is_research ? 'beaker' : 'heart'"
                                class="size-3 {{ $proposal->is_research ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10.5px] font-semibold text-slate-900 dark:text-white line-clamp-1">
                                {{ $proposal->title }}
                            </p>
                            <p class="text-[9px] text-amber-700 dark:text-amber-300 mt-0.5 leading-none">
                                {{ $detail }}
                            </p>
                        </div>
                        <button type="button" x-data
                            x-on:click="$dispatch('open-view-submission-user', { proposalId: {{ $proposal->id }} })"
                            class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                                   text-[9.5px] font-semibold text-white
                                   bg-amber-500/90 hover:bg-amber-500
                                   shadow-sm shadow-amber-500/20 hover:shadow-sm hover:shadow-amber-500/30
                                   hover:scale-[1.02] active:scale-[0.97]
                                   transition-all duration-150">
                            <flux:icon.arrow-right class="size-2.5" />
                            Work on it
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══════════ CHART + RECENT ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

        {{-- Chart --}}
        <div wire:key="user-chart-{{ $periodFilter }}" x-data="userChartComponent(@js($chartData))" x-init="init()"
            class="lg:col-span-2 rounded-2xl border border-slate-200 dark:border-zinc-800
                   bg-white dark:bg-zinc-900 p-3 shadow-sm">

            <div class="flex items-center justify-between mb-2 gap-2">
                <div class="min-w-0">
                    <h3 class="font-heading text-[13px] font-semibold text-slate-900 dark:text-white">
                        My Grant Activity
                    </h3>
                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                        {{ $selectedPeriod ? 'Period ' . $selectedPeriod->periode : 'All periods' }}
                        — internal & external
                    </p>
                </div>
            </div>

            {{-- Legend as pills --}}
            <div class="flex flex-wrap items-center gap-1.5 mb-2">
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                             bg-emerald-50 dark:bg-emerald-900/30
                             border border-emerald-200/60 dark:border-emerald-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span class="text-emerald-700 dark:text-emerald-300 font-medium text-[9px]">Research</span>
                </span>
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                             bg-rose-50 dark:bg-rose-900/30
                             border border-rose-200/60 dark:border-rose-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    <span class="text-rose-700 dark:text-rose-300 font-medium text-[9px]">Community Service</span>
                </span>
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                             bg-indigo-50 dark:bg-indigo-900/30
                             border border-indigo-200/60 dark:border-indigo-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    <span class="text-indigo-700 dark:text-indigo-300 font-medium text-[9px]">Research Ext</span>
                </span>
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                             bg-amber-50 dark:bg-amber-900/30
                             border border-amber-200/60 dark:border-amber-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span class="text-amber-700 dark:text-amber-300 font-medium text-[9px]">Community Service Ext</span>
                </span>
            </div>

            <div class="h-48">
                <div x-ref="chartEl" class="w-full h-full"></div>
            </div>
        </div>

        {{-- Recent Proposals --}}
        <div
            class="rounded-2xl border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden flex flex-col">
            <div class="px-3 py-2 border-b border-slate-200 dark:border-zinc-800 flex items-center gap-2">
                <div
                    class="w-6 h-6 rounded-full bg-slate-100 dark:bg-zinc-800
                            flex items-center justify-center shrink-0">
                    <flux:icon.document-text class="size-3 text-slate-500 dark:text-zinc-400" />
                </div>
                <h3 class="font-heading text-[13px] font-semibold text-slate-900 dark:text-white flex-1">
                    Recent Proposals
                </h3>
                <a href="{{ route('user.internal.manage-researches') }}"
                    class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline shrink-0">
                    View all →
                </a>
            </div>
            <div class="p-1.5 space-y-1 flex-1 overflow-y-auto">
                @forelse ($recentProposals as $proposal)
                    @php $meta = $proposal->statusMeta(); @endphp
                    <div
                        class="rounded-full bg-slate-50/60 dark:bg-zinc-800/40
                                border border-transparent
                                hover:border-slate-200 dark:hover:border-zinc-700
                                hover:bg-white dark:hover:bg-zinc-900
                                pl-1 pr-2 py-1 flex items-center gap-2.5
                                transition-all duration-200">
                        <div
                            class="w-7 h-7 rounded-full shrink-0
                                    {{ $proposal->is_research
                                        ? 'bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/40 dark:to-teal-900/20'
                                        : 'bg-gradient-to-br from-rose-100 to-pink-50 dark:from-rose-900/40 dark:to-pink-900/20' }}
                                    flex items-center justify-center">
                            <flux:icon :name="$proposal->is_research ? 'beaker' : 'heart'"
                                class="size-3 {{ $proposal->is_research ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-[10.5px] font-semibold text-slate-900 dark:text-white line-clamp-1 leading-tight">
                                {{ $proposal->title }}
                            </p>
                            <p class="text-[9px] text-slate-500 dark:text-zinc-400 mt-0.5 line-clamp-1 leading-tight">
                                {{ $proposal->researchScheme?->name ?? '—' }}
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8.5px] font-semibold shrink-0 {{ $meta['class'] }}">
                            {{ $meta['label'] }}
                        </span>
                    </div>
                @empty
                    <div class="px-3 py-6 text-center">
                        <div
                            class="w-10 h-10 rounded-full bg-slate-100 dark:bg-zinc-800
                                    flex items-center justify-center mx-auto mb-2">
                            <flux:icon.document-text class="size-5 text-slate-300 dark:text-zinc-600" />
                        </div>
                        <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mb-2">
                            No proposals yet.
                        </p>
                        <a href="{{ route('user.internal.manage-researches') }}"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                                   bg-emerald-600/90 hover:bg-emerald-600 text-white text-[10px] font-semibold
                                   shadow-sm shadow-emerald-500/20 hover:shadow-sm hover:shadow-emerald-500/30
                                   hover:scale-[1.02] active:scale-[0.97] transition-all duration-150">
                            Create Proposal
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ══════════ INFORMATION + DOWNLOAD ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

        {{-- Latest Information --}}
        <div
            class="rounded-full border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden flex flex-col">
            {{-- Section header --}}
            <div
                class="px-3 py-2 border-b border-slate-200 dark:border-zinc-800
                        bg-gradient-to-r from-sky-50/50 to-transparent
                        dark:from-sky-950/20 dark:to-transparent
                        flex items-center gap-2">
                <div
                    class="w-7 h-7 rounded-full bg-sky-100 dark:bg-sky-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon.megaphone class="size-3.5 text-sky-600 dark:text-sky-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-heading text-[13px] font-semibold text-slate-900 dark:text-white leading-tight">
                        Latest Information
                    </h3>
                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 leading-tight">
                        Announcements & news from LPPM
                    </p>
                </div>
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                             bg-sky-100 text-sky-700
                             dark:bg-sky-900/40 dark:text-sky-300
                             text-[9px] font-bold shrink-0">
                    <span class="relative flex h-1.5 w-1.5">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-sky-500"></span>
                    </span>
                    Live
                </span>
            </div>

            {{-- Component --}}
            <div class="p-3 flex-1">
                <livewire:user.information-feed :limit="3" :refresh-interval="30"
                    storage-key="dismissed_important_info" wire:key="info-{{ $periodFilter }}" />
            </div>
        </div>

        {{-- Downloads & Guides --}}
        <div
            class="rounded-full border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden flex flex-col">
            {{-- Section header --}}
            <div
                class="px-3 py-2 border-b border-slate-200 dark:border-zinc-800
                        bg-gradient-to-r from-emerald-50/50 to-transparent
                        dark:from-emerald-950/20 dark:to-transparent
                        flex items-center gap-2">
                <div
                    class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon.arrow-down-tray class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-heading text-[13px] font-semibold text-slate-900 dark:text-white leading-tight">
                        Downloads & Guides
                    </h3>
                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 leading-tight">
                        Templates & supporting documents
                    </p>
                </div>
                <a href=""
                    class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline shrink-0">
                    View all
                </a>
            </div>

            {{-- Component --}}
            <div class="p-3 flex-1">
                <livewire:user.download-list scope="dashboard" layout="grouped" :limit="6"
                    wire:key="dl-{{ $periodFilter }}" />
            </div>
        </div>
    </div>
</div>

@script
    <script>
        Alpine.data('userChartComponent', (chartData) => ({
            chart: null,

            init() {
                if (typeof ApexCharts === 'undefined') {
                    console.error('[User Chart] ApexCharts not loaded');
                    return;
                }
                if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                    return;
                }
                this.$nextTick(() => setTimeout(() => this.render(), 100));

                this.$watch(() => document.documentElement.classList.contains('dark'), () => {
                    this.updateTheme();
                });
            },

            render() {
                if (typeof ApexCharts === 'undefined') return;

                const el = this.$refs.chartEl;
                if (!el) return;

                if (this.chart) {
                    try {
                        this.chart.destroy();
                    } catch (e) {}
                    this.chart = null;
                }
                el.innerHTML = '';

                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#a1a1aa' : '#475569';
                const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

                const options = {
                    chart: {
                        type: 'bar',
                        height: '100%',
                        fontFamily: 'inherit',
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeout',
                            speed: 700
                        },
                    },
                    series: [{
                            name: 'Research',
                            data: chartData.research
                        },
                        {
                            name: 'Community Service',
                            data: chartData.dedication
                        },
                        {
                            name: 'Research Ext',
                            data: chartData.ext_research
                        },
                        {
                            name: 'Community Service Ext',
                            data: chartData.ext_dedication
                        },
                    ],
                    colors: ['#059669', '#f43f5e', '#6366f1', '#f59e0b'],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '60%',
                            borderRadius: 3,
                            borderRadiusApplication: 'end',
                        },
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        show: false
                    },
                    grid: {
                        borderColor: gridColor,
                        strokeDashArray: 3,
                        xaxis: {
                            lines: {
                                show: false
                            }
                        },
                        yaxis: {
                            lines: {
                                show: true
                            }
                        },
                    },
                    xaxis: {
                        categories: chartData.labels,
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                        labels: {
                            style: {
                                colors: textColor,
                                fontSize: '10px'
                            }
                        },
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: textColor,
                                fontSize: '10px'
                            },
                            formatter: v => Math.round(v),
                        },
                    },
                    legend: {
                        show: false
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: v => v + ' titles'
                        },
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shade: 'light',
                            type: 'vertical',
                            shadeIntensity: 0.2,
                            opacityFrom: 1,
                            opacityTo: 0.85,
                            stops: [0, 100],
                        },
                    },
                };

                this.chart = new ApexCharts(el, options);
                this.chart.render();
            },

            updateTheme() {
                if (!this.chart) return;

                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#a1a1aa' : '#475569';
                const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

                this.chart.updateOptions({
                    grid: {
                        borderColor: gridColor
                    },
                    xaxis: {
                        labels: {
                            style: {
                                colors: textColor
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: textColor
                            }
                        }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light'
                    },
                }, false, false);
            },

            destroy() {
                if (this.chart) {
                    try {
                        this.chart.destroy();
                    } catch (e) {}
                    this.chart = null;
                }
            },
        }));
    </script>
@endscript
