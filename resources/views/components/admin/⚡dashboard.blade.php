<?php

use App\Models\FinalReport;
use App\Models\Output;
use App\Models\Period;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin Dashboard')] class extends Component {
    public string $chartYear = 'all'; // all | specific year

    public function with(): array
    {
        // ── Stat cards ──
        $stats = [
            'total_users' => User::whereHas('role', fn($q) => $q->where('role_code', 'USER'))->count(),
            'total_reviewers' => User::whereHas('role', fn($q) => $q->where('role_code', 'REVIEWER'))->count(),
            'total_proposals' => Proposal::count(),
            'total_research' => Proposal::where('is_research', true)->count(),
            'total_dedications' => Proposal::where('is_research', false)->count(),
            'active_period' => Period::where('is_active', true)->first(),
        ];

        // ── Status breakdown proposal ──
        $statusBreakdown = Proposal::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status')->toArray();

        // ── Proposals per year (comparison chart) ──
        $proposalsPerYear = Proposal::select(DB::raw('YEAR(created_at) as year'), DB::raw('SUM(CASE WHEN is_research = 1 THEN 1 ELSE 0 END) as research_count'), DB::raw('SUM(CASE WHEN is_research = 0 THEN 1 ELSE 0 END) as dedication_count'), DB::raw('COUNT(*) as total'))->groupBy('year')->orderBy('year')->get();

        // ── Output per level per year (chart by level) ──
        $outputsByLevel = Output::query()->whereNotNull('level')->select(DB::raw('YEAR(created_at) as year'), 'level', DB::raw('count(*) as total'))->groupBy('year', 'level')->orderBy('year')->get()->groupBy('year');

        // ── Recent proposals ──
        $recentProposals = Proposal::with(['author', 'researchScheme'])
            ->latest()
            ->take(5)
            ->get();

        // ── Pipeline summary ──
        $pipeline = [
            'progress_pending' => ProgressReport::whereIn('status', ['pending', 'submitted', 'under_review'])->count(),
            'progress_accepted' => ProgressReport::where('status', 'accepted')->count(),
            'final_pending' => FinalReport::whereIn('status', ['pending', 'revised'])->count(),
            'final_accepted' => FinalReport::where('status', 'accepted')->count(),
            'output_pending' => Output::whereIn('status', ['pending', 'revised'])->count(),
            'output_accepted' => Output::where('status', 'accepted')->count(),
        ];

        // ── Chart data JSON ──
        $chartData = [
            'perYear' => [
                'labels' => $proposalsPerYear->pluck('year')->values(),
                'research' => $proposalsPerYear->pluck('research_count')->values(),
                'dedication' => $proposalsPerYear->pluck('dedication_count')->values(),
            ],
            'byLevel' => $outputsByLevel
                ->map(function ($items, $year) {
                    return [
                        'year' => $year,
                        'items' => $items->map(fn($i) => ['level' => $i->level, 'total' => $i->total]),
                    ];
                })
                ->values(),
        ];

        return [
            'stats' => $stats,
            'statusBreakdown' => $statusBreakdown,
            'proposalsPerYear' => $proposalsPerYear,
            'outputsByLevel' => $outputsByLevel,
            'recentProposals' => $recentProposals,
            'pipeline' => $pipeline,
            'chartData' => $chartData,
        ];
    }
};
?>

<div class="p-4 sm:p-6 space-y-6">

    <x-dashboard-header icon="home" title="Admin Dashboard"
        leading="Ringkasan seluruh aktivitas hibah penelitian dan pengabdian." />

    {{-- ══════════ STAT CARDS ══════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @php
            $cards = [
                [
                    'icon' => 'users',
                    'label' => 'Total Users',
                    'value' => $stats['total_users'],
                    'color' => 'emerald',
                    'route' => 'admin.manage-users',
                ],
                [
                    'icon' => 'check',
                    'label' => 'Total Reviewers',
                    'value' => $stats['total_reviewers'],
                    'color' => 'violet',
                    'route' => 'admin.manage-reviewers',
                ],
                [
                    'icon' => 'beaker',
                    'label' => 'Penelitian',
                    'value' => $stats['total_research'],
                    'color' => 'blue',
                    'route' => 'admin.internal.manage-researches',
                ],
                [
                    'icon' => 'heart',
                    'label' => 'Pengabdian',
                    'value' => $stats['total_dedications'],
                    'color' => 'rose',
                    'route' => 'admin.internal.manage-dedications',
                ],
            ];
        @endphp

        @foreach ($cards as $card)
            <a href="{{ route($card['route']) }}"
                class="group rounded-2xl border border-slate-200 dark:border-zinc-800
                       bg-white dark:bg-zinc-900 p-4 shadow-sm
                       hover:shadow-md hover:border-{{ $card['color'] }}-300
                       dark:hover:border-{{ $card['color'] }}-700
                       transition-all">
                <div class="flex items-center justify-between">
                    <div
                        class="w-9 h-9 rounded-xl bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
                                flex items-center justify-center group-hover:scale-105 transition-transform">
                        <flux:icon :name="$card['icon']"
                            class="size-4 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                    </div>
                    <flux:icon.arrow-up-right
                        class="size-3.5 text-slate-400 group-hover:text-{{ $card['color'] }}-500 transition-colors" />
                </div>
                <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">
                    {{ number_format($card['value']) }}
                </p>
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-0.5">
                    {{ $card['label'] }}
                </p>
            </a>
        @endforeach
    </div>

    {{-- ══════════ ACTIVE PERIOD BANNER ══════════ --}}
    @if ($stats['active_period'])
        <div
            class="rounded-2xl border border-emerald-200 dark:border-emerald-800
                    bg-gradient-to-r from-emerald-50 to-teal-50
                    dark:from-emerald-900/20 dark:to-teal-900/20 p-4
                    flex items-start sm:items-center gap-3">
            <div
                class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center shrink-0">
                <flux:icon.calendar-days class="size-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[11px] font-medium text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">
                    Periode Aktif
                </p>
                <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-100 mt-0.5">
                    {{ $stats['active_period']->periode }}
                </p>
                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                    {{ $stats['active_period']->open_from?->format('d M Y') ?? '—' }}
                    s/d
                    {{ $stats['active_period']->open_to?->format('d M Y') ?? '—' }}
                </p>
            </div>
            <span
                class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                         bg-emerald-500 text-white text-[10px] font-semibold shrink-0">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                Aktif
            </span>
        </div>
    @endif

    {{-- ══════════ CHARTS ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Chart 1: Comparison Per Year --}}
        <div wire:ignore wire:key="chart-compare-year-wrapper" x-data="yearComparisonChart(@js($chartData['perYear']))" x-init="init()"
            class="rounded-2xl border border-slate-200 dark:border-zinc-800
           bg-white dark:bg-zinc-900 p-4 shadow-sm">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-3 gap-3">
                <div class="min-w-0">
                    <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white">
                        Komparasi Per Tahun
                    </h3>
                    <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                        Penelitian vs Pengabdian
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    {{-- Legend --}}
                    <div class="hidden sm:flex items-center gap-2 text-[10px]">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-sm bg-emerald-500"></span>
                            <span class="text-slate-600 dark:text-zinc-400">Penelitian</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-sm bg-rose-500"></span>
                            <span class="text-slate-600 dark:text-zinc-400">Pengabdian</span>
                        </span>
                    </div>

                    {{-- Select Chart Type --}}
                    <select x-model="chartType" @change="render()"
                        class="text-[10px] rounded-lg border-slate-200 dark:border-zinc-700
                       bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200
                       focus:border-emerald-500 focus:ring-emerald-500 py-1 px-2
                       cursor-pointer shrink-0">
                        <option value="line">Line</option>
                        <option value="bar">Bar</option>
                        <option value="candlestick">Candlestick</option>
                    </select>
                </div>
            </div>

            {{-- Chart Container --}}
            <div x-ref="chartEl" style="min-height: 260px;"></div>
        </div>

        @if ($proposalsPerYear->isEmpty())
            <div class="flex flex-col items-center justify-center py-8">
                <flux:icon.chart-bar class="size-8 text-slate-300 dark:text-zinc-600 mb-2" />
                <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                    Belum ada data.
                </p>
            </div>
        @endif

        {{-- Chart 2: Output by Level --}}
        <div wire:ignore wire:key="chart-output-level-wrapper" x-data="outputLevelChart(@js($chartData['byLevel']))" x-init="init()"
            class="rounded-2xl border border-slate-200 dark:border-zinc-800
                bg-white dark:bg-zinc-900 p-4 shadow-sm">

            <div class="flex items-center justify-between mb-3 gap-3">
                <div class="min-w-0">
                    <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white">
                        Luaran Berdasarkan Level
                    </h3>
                    <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                        Scopus & Sinta 1-6 per tahun
                    </p>
                </div>

                {{-- Select Chart Type --}}
                <select x-model="chartType" @change="render()"
                    class="text-[10px] rounded-lg border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200
                        focus:border-emerald-500 focus:ring-emerald-500 py-1 px-2
                        cursor-pointer shrink-0">
                    <option value="line">Line</option>
                    <option value="bar">Bar</option>
                    <option value="candlestick">Candlestick</option>
                </select>
            </div>

            {{-- Chart Container --}}
            <div x-ref="chartEl" style="min-height: 260px;"></div>
        </div>

        @if ($outputsByLevel->isEmpty())
            <div class="flex flex-col items-center justify-center py-8">
                <flux:icon.trophy class="size-8 text-slate-300 dark:text-zinc-600 mb-2" />
                <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                    Belum ada luaran.
                </p>
            </div>
        @endif

    </div>
    {{-- ══════════ PIPELINE + RECENT ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Pipeline --}}
        <div
            class="rounded-2xl border border-slate-200 dark:border-zinc-800
                bg-white dark:bg-zinc-900 p-4 shadow-sm">
            <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white mb-3">
                Pipeline Submission
            </h3>

            <div class="space-y-3">
                @php
                    $pipelineData = [
                        [
                            'label' => 'Progress Report',
                            'pending' => $pipeline['progress_pending'],
                            'accepted' => $pipeline['progress_accepted'],
                            'color' => 'violet',
                        ],
                        [
                            'label' => 'Final Report',
                            'pending' => $pipeline['final_pending'],
                            'accepted' => $pipeline['final_accepted'],
                            'color' => 'blue',
                        ],
                        [
                            'label' => 'Output',
                            'pending' => $pipeline['output_pending'],
                            'accepted' => $pipeline['output_accepted'],
                            'color' => 'amber',
                        ],
                    ];
                @endphp

                @foreach ($pipelineData as $p)
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700 p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                                {{ $p['label'] }}
                            </span>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-zinc-400">
                                {{ $p['pending'] + $p['accepted'] }} total
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span
                                        class="text-[9px] text-amber-600 dark:text-amber-400 font-semibold uppercase tracking-wider">
                                        Pending
                                    </span>
                                    <span class="text-[10px] font-bold text-amber-700 dark:text-amber-300">
                                        {{ $p['pending'] }}
                                    </span>
                                </div>
                                <div class="h-1 rounded-full bg-slate-100 dark:bg-zinc-800 overflow-hidden">
                                    @php
                                        $total = $p['pending'] + $p['accepted'];
                                        $pct = $total > 0 ? ($p['pending'] / $total) * 100 : 0;
                                    @endphp
                                    <div class="h-full rounded-full bg-amber-500 transition-all"
                                        style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span
                                        class="text-[9px] text-emerald-600 dark:text-emerald-400 font-semibold uppercase tracking-wider">
                                        Accepted
                                    </span>
                                    <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300">
                                        {{ $p['accepted'] }}
                                    </span>
                                </div>
                                <div class="h-1 rounded-full bg-slate-100 dark:bg-zinc-800 overflow-hidden">
                                    @php
                                        $pct = $total > 0 ? ($p['accepted'] / $total) * 100 : 0;
                                    @endphp
                                    <div class="h-full rounded-full bg-emerald-500 transition-all"
                                        style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent Proposals --}}
        <div
            class="lg:col-span-2 rounded-2xl border border-slate-200 dark:border-zinc-800
                bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-zinc-800 flex items-center justify-between">
                <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white">
                    Proposal Terbaru
                </h3>
                <a href="{{ route('admin.internal.manage-researches') }}"
                    class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                    Lihat semua →
                </a>
            </div>

            <div class="divide-y divide-slate-200 dark:divide-zinc-800">
                @forelse ($recentProposals as $proposal)
                    @php $meta = $proposal->statusMeta(); @endphp
                    <div
                        class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div
                            class="w-8 h-8 rounded-lg shrink-0
                                {{ $proposal->is_research
                                    ? 'bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/20'
                                    : 'bg-gradient-to-br from-rose-100 to-pink-50 dark:from-rose-900/30 dark:to-pink-900/20' }}
                                flex items-center justify-center">
                            <flux:icon :name="$proposal->is_research ? 'beaker' : 'heart'"
                                class="size-3.5 {{ $proposal->is_research ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-900 dark:text-white line-clamp-1">
                                {{ $proposal->title }}
                            </p>
                            <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                {{ $proposal->author?->full_name ?? '—' }} ·
                                {{ $proposal->researchScheme?->name ?? '—' }}
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-semibold shrink-0 {{ $meta['class'] }}">
                            {{ $meta['label'] }}
                        </span>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <p class="text-[11px] text-slate-500 dark:text-zinc-400">
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
                'Scopus': '#6366f1',
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
                    console.error('[Chart] ApexCharts not loaded');
                    return;
                }
                if (!this.data || this.data.length === 0) {
                    console.warn('[Chart] No data available');
                    return;
                }
                this.$nextTick(() => {
                    setTimeout(() => this.render(), 100);
                });
            },

            render() {
                if (typeof ApexCharts === 'undefined') return;
                if (this.chart) {
                    this.chart.destroy();
                    this.chart = null;
                }

                const el = this.$refs.chartEl;
                if (!el) return;
                el.innerHTML = '';

                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#a1a1aa' : '#64748b';
                const gridColor = isDark ? '#27272a' : '#e2e8f0';

                const options = this.chartType === 'candlestick' ?
                    this.buildCandlestick(isDark, textColor, gridColor) :
                    this.buildLineBar(isDark, textColor, gridColor);

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
                        height: 260,
                        toolbar: {
                            show: false
                        },
                        fontFamily: 'inherit',
                    },
                    series: series,
                    xaxis: {
                        categories: years,
                        labels: {
                            style: {
                                colors: textColor,
                                fontSize: '11px'
                            }
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: textColor,
                                fontSize: '11px'
                            }
                        },
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
                    stroke: {
                        width: isLine ? 3 : 0,
                        curve: 'smooth',
                    },
                    markers: {
                        size: isLine ? 4 : 0,
                    },
                    colors: colors,
                    dataLabels: {
                        enabled: false
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '10px',
                        labels: {
                            colors: textColor
                        },
                        markers: {
                            size: 6
                        },
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                    },
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
                    const low = Math.max(0, Math.min(open, close) - Math.floor(Math.random() * 2));
                    prevTotal = close;
                    return [open, high, low, close];
                });

                return {
                    chart: {
                        type: 'candlestick',
                        height: 260,
                        toolbar: {
                            show: false
                        },
                        fontFamily: 'inherit',
                    },
                    series: [{
                        name: 'Total Luaran',
                        data: years.map((year, i) => ({
                            x: String(year),
                            y: candleData[i],
                        })),
                    }],
                    plotOptions: {
                        candlestick: {
                            colors: {
                                upward: '#10b981',
                                downward: '#f43f5e',
                            },
                            wick: {
                                useFillColor: true
                            },
                        },
                    },
                    xaxis: {
                        type: 'category',
                        labels: {
                            style: {
                                colors: textColor,
                                fontSize: '11px'
                            }
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: textColor,
                                fontSize: '11px'
                            }
                        },
                    },
                    grid: {
                        borderColor: gridColor,
                        strokeDashArray: 4,
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                    },
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
            console.error('[Chart 1] ApexCharts not loaded');
            return;
        }
        if (!this.data || !this.data.labels || this.data.labels.length === 0) {
            console.warn('[Chart 1] No data available');
            return;
        }

        this.$nextTick(() => {
            setTimeout(() => this.render(), 100);
        });

        // Watch chartType change (redundant dengan @change, tapi aman)
        this.$watch('chartType', () => this.render());
    },

    render() {
        if (typeof ApexCharts === 'undefined') return;
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }

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
                height: 260,
                toolbar: { show: false },
                fontFamily: 'inherit',
                animations: { enabled: true, speed: 300 },
            },
            series: [
                { name: 'Penelitian', data: this.data.research },
                { name: 'Pengabdian', data: this.data.dedication },
            ],
            xaxis: {
                categories: this.data.labels,
                labels: { style: { colors: textColor, fontSize: '11px' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: { style: { colors: textColor, fontSize: '11px' } },
            },
            grid: {
                borderColor: gridColor,
                strokeDashArray: 4,
                yaxis: { lines: { show: true } },
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '55%',
                },
            },
            colors: ['#10b981', '#f43f5e'],
            dataLabels: { enabled: false },
            legend: { show: false },
            stroke: {
                width: isLine ? 3 : 0,
                curve: 'smooth',
            },
            markers: {
                size: isLine ? 4 : 0,
            },
            tooltip: {
                theme: isDark ? 'dark' : 'light',
            },
        };
    },

    buildCandlestick(isDark, textColor, gridColor) {
        const research = this.data.research || [];
        const dedication = this.data.dedication || [];
        const labels = this.data.labels || [];

        // Total per tahun
        const totals = labels.map((_, i) => (research[i] || 0) + (dedication[i] || 0));

        // Simulasi OHLC dari data tahunan
        let prevTotal = 0;
        const candleData = totals.map((total) => {
            const open = prevTotal;
            const close = total;
            const high = Math.max(open, close) + Math.floor(Math.random() * 2);
            const low = Math.max(0, Math.min(open, close) - Math.floor(Math.random() * 2));
            prevTotal = close;
            return [open, high, low, close];
        });

        return {
            chart: {
                type: 'candlestick',
                height: 260,
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            series: [{
                name: 'Total Proposal',
                data: labels.map((label, i) => ({
                    x: String(label),
                    y: candleData[i],
                })),
            }],
            plotOptions: {
                candlestick: {
                    colors: {
                        upward: '#10b981',
                        downward: '#f43f5e',
                    },
                    wick: { useFillColor: true },
                },
            },
            xaxis: {
                type: 'category',
                labels: { style: { colors: textColor, fontSize: '11px' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: { style: { colors: textColor, fontSize: '11px' } },
            },
            grid: {
                borderColor: gridColor,
                strokeDashArray: 4,
            },
            tooltip: {
                theme: isDark ? 'dark' : 'light',
            },
        };
    },
}));
</script>
@endscript
