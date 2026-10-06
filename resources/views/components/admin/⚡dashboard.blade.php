<?php

use App\Models\ExternalProposal;
use App\Models\FinalReport;
use App\Models\Output;
use App\Models\Period;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public string $periodFilter = 'all';

    public function mount(): void
    {
        // Default: aktif → kalau tidak ada → periode terbaru → kalau tidak ada juga → 'all'
        $active = Period::where('is_active', true)->first();

        if ($active) {
            $this->periodFilter = (string) $active->id;
        } else {
            $latest = Period::orderByDesc('open_from')->orderByDesc('id')->first();
            $this->periodFilter = $latest ? (string) $latest->id : 'all';
        }
    }

    public function with(): array
    {
        $selectedPeriod = $this->periodFilter === 'all'
            ? null
            : Period::find((int) $this->periodFilter);

        $periodId   = $selectedPeriod?->id;
        $periodFrom = $selectedPeriod?->open_from;
        $periodTo   = $selectedPeriod?->open_to;
        $yearFrom   = $periodFrom?->year;
        $yearTo     = $periodTo?->year;

        // ── Base queries (with period filter) ──
        $proposalQuery = Proposal::query()
            ->when($periodId, fn ($q) => $q->where('period_id', $periodId));

        $externalQuery = ExternalProposal::query()
            ->when(
                $periodFrom && $periodTo,
                fn ($q) => $q->whereBetween('start_date', [$periodFrom, $periodTo])
            );

        // ── Stat cards ──
        $stats = [
            'total_users'               => User::whereHas('role', fn ($q) => $q->where('role_code', 'USER'))->count(),
            'total_reviewers'           => User::whereHas('role', fn ($q) => $q->where('role_code', 'REVIEWER'))->count(),
            'total_research'            => (clone $proposalQuery)->where('is_research', true)->count(),
            'total_dedications'         => (clone $proposalQuery)->where('is_research', false)->count(),
            'total_external_research'   => (clone $externalQuery)->where('is_research', true)->count(),
            'total_external_dedication' => (clone $externalQuery)->where('is_research', false)->count(),
            'active_period'             => Period::where('is_active', true)->first(),
        ];

        // ── Status breakdown proposal ──
        $statusBreakdown = (clone $proposalQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // ── Per year: internal proposals ──
        $proposalsPerYear = Proposal::query()
            ->when($yearFrom, fn ($q) => $q->whereYear('created_at', '>=', $yearFrom))
            ->when($yearTo, fn ($q) => $q->whereYear('created_at', '<=', $yearTo))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(CASE WHEN is_research = 1 THEN 1 ELSE 0 END) as research_count'),
                DB::raw('SUM(CASE WHEN is_research = 0 THEN 1 ELSE 0 END) as dedication_count'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        // ── Per year: external proposals ──
        $externalPerYear = ExternalProposal::query()
            ->when($yearFrom, fn ($q) => $q->whereYear('start_date', '>=', $yearFrom))
            ->when($yearTo, fn ($q) => $q->whereYear('start_date', '<=', $yearTo))
            ->select(
                DB::raw('YEAR(start_date) as year'),
                DB::raw('SUM(CASE WHEN is_research = 1 THEN 1 ELSE 0 END) as research_count'),
                DB::raw('SUM(CASE WHEN is_research = 0 THEN 1 ELSE 0 END) as dedication_count')
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        // ── Merge years (proposal + external) ──
        $allYears = $proposalsPerYear->pluck('year')
            ->merge($externalPerYear->pluck('year'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $chartPerYear = [
            'labels'         => $allYears,
            'research'       => $allYears->map(fn ($y) => (int) (optional($proposalsPerYear->firstWhere('year', $y))->research_count ?? 0))->values(),
            'dedication'     => $allYears->map(fn ($y) => (int) (optional($proposalsPerYear->firstWhere('year', $y))->dedication_count ?? 0))->values(),
            'ext_research'   => $allYears->map(fn ($y) => (int) (optional($externalPerYear->firstWhere('year', $y))->research_count ?? 0))->values(),
            'ext_dedication' => $allYears->map(fn ($y) => (int) (optional($externalPerYear->firstWhere('year', $y))->dedication_count ?? 0))->values(),
        ];

        // ── Output by level per year ──
        $outputsByLevel = Output::query()
            ->whereNotNull('level')
            ->when($yearFrom, fn ($q) => $q->whereYear('created_at', '>=', $yearFrom))
            ->when($yearTo, fn ($q) => $q->whereYear('created_at', '<=', $yearTo))
            ->select(DB::raw('YEAR(created_at) as year'), 'level', DB::raw('count(*) as total'))
            ->groupBy('year', 'level')
            ->orderBy('year')
            ->get()
            ->groupBy('year');

        // ── Recent proposals ──
        $recentProposals = (clone $proposalQuery)
            ->with(['author', 'researchScheme'])
            ->latest()
            ->take(5)
            ->get();

        // ── Pipeline (filter via proposal.period_id) ──
        $pipeProposal = fn ($q) => $q->when($periodId, fn ($q) => $q->where('period_id', $periodId));

        $pipeline = [
            'progress_pending'  => ProgressReport::whereHas('proposal', $pipeProposal)->whereIn('status', ['pending', 'submitted', 'under_review'])->count(),
            'progress_accepted' => ProgressReport::whereHas('proposal', $pipeProposal)->where('status', 'accepted')->count(),
            'final_pending'     => FinalReport::whereHas('proposal', $pipeProposal)->whereIn('status', ['pending', 'revised'])->count(),
            'final_accepted'    => FinalReport::whereHas('proposal', $pipeProposal)->where('status', 'accepted')->count(),
            'output_pending'    => Output::whereHas('proposal', $pipeProposal)->whereIn('status', ['pending', 'revised'])->count(),
            'output_accepted'   => Output::whereHas('proposal', $pipeProposal)->where('status', 'accepted')->count(),
        ];

        // ── Chart data JSON ──
        $chartData = [
            'perYear' => $chartPerYear,
            'byLevel' => $outputsByLevel
                ->map(fn ($items, $year) => [
                    'year'  => $year,
                    'items' => $items->map(fn ($i) => ['level' => $i->level, 'total' => $i->total]),
                ])
                ->values(),
        ];

        return [
            'stats'            => $stats,
            'statusBreakdown'  => $statusBreakdown,
            'proposalsPerYear' => $proposalsPerYear,
            'externalPerYear'  => $externalPerYear,
            'outputsByLevel'   => $outputsByLevel,
            'recentProposals'  => $recentProposals,
            'pipeline'         => $pipeline,
            'chartData'        => $chartData,
            'periods'          => Period::orderByDesc('open_from')->orderByDesc('id')->get(),
            'selectedPeriod'   => $selectedPeriod,
        ];
    }
};
?>

<div class="p-3 sm:p-4 space-y-3">

    {{-- ══════════ HEADER + PERIOD FILTER ══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <x-dashboard-header icon="home" title="Admin Dashboard"
            leading="Ringkasan seluruh aktivitas hibah penelitian dan pengabdian." />

        {{-- Period Filter --}}
        <div class="flex items-center gap-1.5 shrink-0">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
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
                    <option value="all">Semua Periode</option>
                    @foreach ($periods as $p)
                        <option value="{{ $p->id }}">{{ $p->periode }}</option>
                    @endforeach
                </select>
            </div>

            <div wire:loading wire:target="periodFilter"
                class="w-3.5 h-3.5 rounded-full bg-slate-100 dark:bg-zinc-800
                       flex items-center justify-center">
                <svg class="animate-spin size-2.5 text-emerald-500" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- ══════════ STAT CARDS ══════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2">
        @php
            $cards = [
                ['icon' => 'users',        'label' => 'Users',              'value' => $stats['total_users'],                'color' => 'emerald', ],
                ['icon' => 'check',        'label' => 'Reviewers',          'value' => $stats['total_reviewers'],            'color' => 'violet',  ],
                ['icon' => 'beaker',       'label' => 'Penelitian',         'value' => $stats['total_research'],             'color' => 'blue',    ],
                ['icon' => 'heart',        'label' => 'Pengabdian',         'value' => $stats['total_dedications'],          'color' => 'rose',    ],
                ['icon' => 'globe-alt',    'label' => 'Penelitian External','value' => $stats['total_external_research'],    'color' => 'indigo',  ],
                ['icon' => 'gift',         'label' => 'Pengabdian External','value' => $stats['total_external_dedication'],  'color' => 'amber',   ],
            ];
        @endphp

        @foreach ($cards as $card)
            <a href=""
                class="group rounded-full border border-slate-200 dark:border-zinc-800
                       bg-white dark:bg-zinc-900 p-3 shadow-sm
                       hover:shadow-md hover:border-{{ $card['color'] }}-300
                       dark:hover:border-{{ $card['color'] }}-700
                       transition-all">
                <div class="flex items-center justify-between">
                    <div class="w-8 h-8 rounded-lg bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
                                flex items-center justify-center group-hover:scale-105 transition-transform">
                        <flux:icon :name="$card['icon']"
                            class="size-3.5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                    </div>
                    <flux:icon.arrow-up-right
                        class="size-3 text-slate-400 group-hover:text-{{ $card['color'] }}-500 transition-colors" />
                </div>
                <p class="mt-2 text-xl font-bold text-slate-900 dark:text-white leading-none">
                    {{ number_format($card['value']) }}
                </p>
                <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 leading-tight">
                    {{ $card['label'] }}
                </p>
            </a>
        @endforeach
    </div>

    {{-- ══════════ ACTIVE PERIOD BANNER ══════════ --}}
    @if ($stats['active_period'])
        <div class="rounded-full border border-emerald-200 dark:border-emerald-800
                    bg-gradient-to-r from-emerald-50 to-teal-50
                    dark:from-emerald-900/20 dark:to-teal-900/20 p-3
                    flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40
                        flex items-center justify-center shrink-0">
                <flux:icon.calendar-days class="size-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">
                    Periode Aktif
                </p>
                <p class="text-xs font-semibold text-emerald-900 dark:text-emerald-100 mt-0.5">
                    {{ $stats['active_period']->periode }}
                    <span class="text-[10px] font-normal text-emerald-600 dark:text-emerald-400 ml-1">
                        {{ $stats['active_period']->open_from?->format('d M Y') ?? '—' }}
                        s/d
                        {{ $stats['active_period']->open_to?->format('d M Y') ?? '—' }}
                    </span>
                </p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                         bg-emerald-500 text-white text-[9px] font-semibold shrink-0">
                <span class="w-1 h-1 rounded-full bg-white animate-pulse"></span>
                Aktif
            </span>
        </div>
    @endif

    {{-- ══════════ CHARTS ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

        {{-- Chart 1: Komparasi Per Tahun --}}
        <div wire:ignore wire:key="chart-compare-year-{{ $periodFilter }}"
            x-data="yearComparisonChart(@js($chartData['perYear']))" x-init="init()"
            class="rounded-full border border-slate-200 dark:border-zinc-800
                   bg-white dark:bg-zinc-900 p-3 shadow-sm">

            <div class="flex items-center justify-between mb-2 gap-2">
                <div class="min-w-0">
                    <h3 class="font-heading text-[13px] font-semibold text-slate-900 dark:text-white">
                        Komparasi Per Tahun
                    </h3>
                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                        Internal vs External
                    </p>
                </div>

                <select x-model="chartType" @change="render()"
                    class="text-[10px] rounded-full border-slate-200 dark:border-zinc-700
                           bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200
                           focus:border-emerald-500 focus:ring-emerald-500 py-0.5 px-2
                           cursor-pointer shrink-0">
                    <option value="line">Line</option>
                    <option value="bar">Bar</option>
                    <option value="candlestick">Candle</option>
                </select>
            </div>

            <div x-ref="chartEl" style="min-height: 220px;"></div>

            {{-- Legend --}}
            <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 mt-2 text-[9.5px]">
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-sm bg-emerald-500"></span>
                    <span class="text-slate-600 dark:text-zinc-400">Penelitian</span>
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-sm bg-rose-500"></span>
                    <span class="text-slate-600 dark:text-zinc-400">Pengabdian</span>
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-sm bg-violet-500"></span>
                    <span class="text-slate-600 dark:text-zinc-400">Penelitian Ext</span>
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-sm bg-amber-500"></span>
                    <span class="text-slate-600 dark:text-zinc-400">Pengabdian Ext</span>
                </span>
            </div>
        </div>

        {{-- Chart 2: Output by Level --}}
        <div wire:ignore wire:key="chart-output-level-{{ $periodFilter }}"
            x-data="outputLevelChart(@js($chartData['byLevel']))" x-init="init()"
            class="rounded-full border border-slate-200 dark:border-zinc-800
                   bg-white dark:bg-zinc-900 p-3 shadow-sm">

            <div class="flex items-center justify-between mb-2 gap-2">
                <div class="min-w-0">
                    <h3 class="font-heading text-[13px] font-semibold text-slate-900 dark:text-white">
                        Luaran Berdasarkan Level
                    </h3>
                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                        Scopus & Sinta 1-6 per tahun
                    </p>
                </div>

                <select x-model="chartType" @change="render()"
                    class="text-[10px] rounded-full border-slate-200 dark:border-zinc-700
                           bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200
                           focus:border-emerald-500 focus:ring-emerald-500 py-0.5 px-2
                           cursor-pointer shrink-0">
                    <option value="line">Line</option>
                    <option value="bar">Bar</option>
                    <option value="candlestick">Candle</option>
                </select>
            </div>

            <div x-ref="chartEl" style="min-height: 220px;"></div>
        </div>
    </div>

    {{-- ══════════ PIPELINE + RECENT ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

        {{-- Pipeline --}}
        <div class="rounded-full border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 p-3 shadow-sm">
            <h3 class="font-heading text-[13px] font-semibold text-slate-900 dark:text-white mb-2">
                Pipeline Submission
            </h3>

            <div class="space-y-2">
                @php
                    $pipelineData = [
                        ['label' => 'Progress Report', 'pending' => $pipeline['progress_pending'], 'accepted' => $pipeline['progress_accepted']],
                        ['label' => 'Final Report',    'pending' => $pipeline['final_pending'],    'accepted' => $pipeline['final_accepted']],
                        ['label' => 'Output',          'pending' => $pipeline['output_pending'],   'accepted' => $pipeline['output_accepted']],
                    ];
                @endphp

                @foreach ($pipelineData as $p)
                    @php
                        $total = $p['pending'] + $p['accepted'];
                        $pendingPct  = $total > 0 ? ($p['pending'] / $total) * 100 : 0;
                        $acceptedPct = $total > 0 ? ($p['accepted'] / $total) * 100 : 0;
                    @endphp
                    <div class="rounded-full border border-slate-200 dark:border-zinc-700 p-2.5">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10.5px] font-semibold text-slate-700 dark:text-zinc-300">
                                {{ $p['label'] }}
                            </span>
                            <span class="text-[9.5px] font-bold text-slate-500 dark:text-zinc-400">
                                {{ $total }} total
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[8.5px] text-amber-600 dark:text-amber-400 font-semibold uppercase tracking-wider">
                                        Pending
                                    </span>
                                    <span class="text-[9.5px] font-bold text-amber-700 dark:text-amber-300">
                                        {{ $p['pending'] }}
                                    </span>
                                </div>
                                <div class="h-1 rounded-full bg-slate-100 dark:bg-zinc-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-amber-500 transition-all"
                                        style="width: {{ $pendingPct }}%"></div>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[8.5px] text-emerald-600 dark:text-emerald-400 font-semibold uppercase tracking-wider">
                                        Accepted
                                    </span>
                                    <span class="text-[9.5px] font-bold text-emerald-700 dark:text-emerald-300">
                                        {{ $p['accepted'] }}
                                    </span>
                                </div>
                                <div class="h-1 rounded-full bg-slate-100 dark:bg-zinc-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-emerald-500 transition-all"
                                        style="width: {{ $acceptedPct }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent Proposals --}}
        <div class="lg:col-span-2 rounded-full border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <div class="px-3 py-2 border-b border-slate-200 dark:border-zinc-800 flex items-center justify-between">
                <h3 class="font-heading text-[13px] font-semibold text-slate-900 dark:text-white">
                    Proposal Terbaru
                </h3>
                <a href="{{ route('admin.internal.manage-researches') }}"
                    class="text-[10.5px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                    Lihat semua →
                </a>
            </div>

            <div class="divide-y divide-slate-200 dark:divide-zinc-800">
                @forelse ($recentProposals as $proposal)
                    @php $meta = $proposal->statusMeta(); @endphp
                    <div class="px-3 py-2 flex items-center gap-2.5 hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div class="w-7 h-7 rounded-lg shrink-0
                                    {{ $proposal->is_research
                                        ? 'bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/20'
                                        : 'bg-gradient-to-br from-rose-100 to-pink-50 dark:from-rose-900/30 dark:to-pink-900/20' }}
                                    flex items-center justify-center">
                            <flux:icon :name="$proposal->is_research ? 'beaker' : 'heart'"
                                class="size-3 {{ $proposal->is_research ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-semibold text-slate-900 dark:text-white line-clamp-1">
                                {{ $proposal->title }}
                            </p>
                            <p class="text-[9.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                {{ $proposal->author?->full_name ?? '—' }} ·
                                {{ $proposal->researchScheme?->name ?? '—' }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-semibold shrink-0 {{ $meta['class'] }}">
                            {{ $meta['label'] }}
                        </span>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center">
                        <p class="text-[10.5px] text-slate-500 dark:text-zinc-400">
                            Belum ada proposal.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('outputLevelChart', (data) => ({
        chart: null,
        chartType: 'bar',
        data: data,

        levelColors: {
            'Scopus':  '#6366f1',
            'Sinta 1': '#10b981',
            'Sinta 2': '#14b8a6',
            'Sinta 3': '#06b6d4',
            'Sinta 4': '#3b82f6',
            'Sinta 5': '#8b5cf6',
            'Sinta 6': '#f59e0b',
        },

        allLevels: ['Scopus', 'Sinta 1', 'Sinta 2', 'Sinta 3', 'Sinta 4', 'Sinta 5', 'Sinta 6'],

        init() {
            if (typeof ApexCharts === 'undefined') {
                console.error('[Chart Output] ApexCharts not loaded');
                return;
            }
            if (!this.data || this.data.length === 0) {
                console.warn('[Chart Output] No data');
                return;
            }
            this.$nextTick(() => setTimeout(() => this.render(), 100));
        },

        render() {
            if (typeof ApexCharts === 'undefined') return;
            if (this.chart) { this.chart.destroy(); this.chart = null; }

            const el = this.$refs.chartEl;
            if (!el) return;
            el.innerHTML = '';

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#a1a1aa' : '#64748b';
            const gridColor = isDark ? '#27272a' : '#e2e8f0';

            const options = this.chartType === 'candlestick'
                ? this.buildCandlestick(isDark, textColor, gridColor)
                : this.buildLineBar(isDark, textColor, gridColor);

            this.chart = new ApexCharts(el, options);
            this.chart.render();
        },

        buildLineBar(isDark, textColor, gridColor) {
            const years = this.data.map(d => d.year);

            const series = this.allLevels.map(level => ({
                name: level,
                data: years.map(year => {
                    const yearData = this.data.find(d => d.year == year);
                    const item = yearData?.items?.find(i => i.level === level);
                    return item ? Number(item.total) : 0;
                }),
            })).filter(s => s.data.some(v => v > 0));

            const colors = series.map(s => this.levelColors[s.name] || '#64748b');
            const isLine = this.chartType === 'line';

            return {
                chart: {
                    type: isLine ? 'line' : 'bar',
                    height: 220,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                series: series,
                xaxis: {
                    categories: years,
                    labels: { style: { colors: textColor, fontSize: '10px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: { style: { colors: textColor, fontSize: '10px' } },
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4,
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '55%',
                    },
                },
                stroke: { width: isLine ? 2.5 : 0, curve: 'smooth' },
                markers: { size: isLine ? 3 : 0 },
                colors: colors,
                dataLabels: { enabled: false },
                legend: {
                    position: 'bottom',
                    fontSize: '9px',
                    labels: { colors: textColor },
                    markers: { size: 5 },
                    itemMargin: { horizontal: 6, vertical: 2 },
                },
                tooltip: { theme: isDark ? 'dark' : 'light' },
            };
        },

        buildCandlestick(isDark, textColor, gridColor) {
            const years = this.data.map(d => d.year);

            const totals = years.map(year => {
                const yearData = this.data.find(d => d.year == year);
                if (!yearData) return 0;
                return (yearData.items || []).reduce((sum, item) => sum + Number(item.total), 0);
            });

            let prevTotal = 0;
            const candleData = totals.map(total => {
                const open = prevTotal;
                const close = total;
                const high = Math.max(open, close) + Math.floor(Math.random() * 2);
                const low  = Math.max(0, Math.min(open, close) - Math.floor(Math.random() * 2));
                prevTotal = close;
                return [open, high, low, close];
            });

            return {
                chart: {
                    type: 'candlestick',
                    height: 220,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                series: [{
                    name: 'Total Luaran',
                    data: years.map((year, i) => ({ x: String(year), y: candleData[i] })),
                }],
                plotOptions: {
                    candlestick: {
                        colors: { upward: '#10b981', downward: '#f43f5e' },
                        wick: { useFillColor: true },
                    },
                },
                xaxis: {
                    type: 'category',
                    labels: { style: { colors: textColor, fontSize: '10px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: { labels: { style: { colors: textColor, fontSize: '10px' } } },
                grid: { borderColor: gridColor, strokeDashArray: 4 },
                tooltip: { theme: isDark ? 'dark' : 'light' },
            };
        },
    }));
</script>
@endscript

@script
<script>
    Alpine.data('yearComparisonChart', (data) => ({
        chart: null,
        chartType: 'line',
        data: data,

        init() {
            if (typeof ApexCharts === 'undefined') {
                console.error('[Chart Year] ApexCharts not loaded');
                return;
            }
            if (!this.data || !this.data.labels || this.data.labels.length === 0) {
                console.warn('[Chart Year] No data');
                return;
            }
            this.$nextTick(() => setTimeout(() => this.render(), 100));
        },

        render() {
            if (typeof ApexCharts === 'undefined') return;
            if (this.chart) { this.chart.destroy(); this.chart = null; }

            const el = this.$refs.chartEl;
            if (!el) return;
            el.innerHTML = '';

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#a1a1aa' : '#64748b';
            const gridColor = isDark ? '#27272a' : '#e2e8f0';

            const options = this.chartType === 'candlestick'
                ? this.buildCandlestick(isDark, textColor, gridColor)
                : this.buildLineBar(isDark, textColor, gridColor);

            this.chart = new ApexCharts(el, options);
            this.chart.render();
        },

        buildLineBar(isDark, textColor, gridColor) {
            const isLine = this.chartType === 'line';

            return {
                chart: {
                    type: isLine ? 'line' : 'bar',
                    height: 220,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                    animations: { enabled: true, speed: 300 },
                },
                series: [
                    { name: 'Penelitian',        data: this.data.research },
                    { name: 'Pengabdian',        data: this.data.dedication },
                    { name: 'Penelitian Ext',    data: this.data.ext_research },
                    { name: 'Pengabdian Ext',    data: this.data.ext_dedication },
                ],
                xaxis: {
                    categories: this.data.labels,
                    labels: { style: { colors: textColor, fontSize: '10px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: { labels: { style: { colors: textColor, fontSize: '10px' } } },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4,
                    yaxis: { lines: { show: true } },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '65%',
                    },
                },
                colors: ['#10b981', '#f43f5e', '#8b5cf6', '#f59e0b'],
                dataLabels: { enabled: false },
                legend: { show: false },
                stroke: { width: isLine ? 2.5 : 0, curve: 'smooth' },
                markers: { size: isLine ? 3 : 0 },
                tooltip: { theme: isDark ? 'dark' : 'light' },
            };
        },

        buildCandlestick(isDark, textColor, gridColor) {
            const research      = this.data.research || [];
            const dedication    = this.data.dedication || [];
            const extResearch   = this.data.ext_research || [];
            const extDedication = this.data.ext_dedication || [];
            const labels        = this.data.labels || [];

            const totals = labels.map((_, i) =>
                (research[i] || 0) + (dedication[i] || 0) +
                (extResearch[i] || 0) + (extDedication[i] || 0)
            );

            let prevTotal = 0;
            const candleData = totals.map(total => {
                const open = prevTotal;
                const close = total;
                const high = Math.max(open, close) + Math.floor(Math.random() * 2);
                const low  = Math.max(0, Math.min(open, close) - Math.floor(Math.random() * 2));
                prevTotal = close;
                return [open, high, low, close];
            });

            return {
                chart: {
                    type: 'candlestick',
                    height: 220,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                series: [{
                    name: 'Total',
                    data: labels.map((label, i) => ({ x: String(label), y: candleData[i] })),
                }],
                plotOptions: {
                    candlestick: {
                        colors: { upward: '#10b981', downward: '#f43f5e' },
                        wick: { useFillColor: true },
                    },
                },
                xaxis: {
                    type: 'category',
                    labels: { style: { colors: textColor, fontSize: '10px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: { labels: { style: { colors: textColor, fontSize: '10px' } } },
                grid: { borderColor: gridColor, strokeDashArray: 4 },
                tooltip: { theme: isDark ? 'dark' : 'light' },
            };
        },
    }));
</script>
@endscript
