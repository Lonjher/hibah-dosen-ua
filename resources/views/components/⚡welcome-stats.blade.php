<?php

use App\Models\Period;
use App\Models\Proposal;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

new class extends Component {
    public int $currentYear;
    public int $startYear = 2023;

    public function mount(): void
    {
        $this->currentYear = (int) now()->year;
    }

    /**
     * Ambil agregat SEMUA tahun dari startYear s.d. currentYear.
     * Return array primitif supaya aman di-cache.
     */
    protected function aggregateByYear(): array
    {
        $years = range($this->startYear, $this->currentYear);

        $rows = Proposal::query()
            ->where(function ($q) use ($years) {
                foreach ($years as $y) {
                    $q->orWhereBetween('proposals.created_at', [
                        "{$y}-01-01 00:00:00",
                        "{$y}-12-31 23:59:59",
                    ]);
                }
            })
            ->selectRaw('YEAR(proposals.created_at) as year')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN proposals.status = 'accepted' THEN 1 ELSE 0 END) as accepted")
            ->selectRaw('SUM(CASE WHEN proposals.is_research = 1 THEN 1 ELSE 0 END) as riset')
            ->selectRaw('SUM(CASE WHEN proposals.is_research = 0 THEN 1 ELSE 0 END) as pengabdian')
            ->selectRaw('COUNT(DISTINCT proposals.user_id) as dosen')
            ->groupByRaw('YEAR(proposals.created_at)')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row->year] = [
                'total'      => (int) $row->total,
                'accepted'   => (int) $row->accepted,
                'riset'      => (int) $row->riset,
                'pengabdian' => (int) $row->pengabdian,
                'dosen'      => (int) $row->dosen,
            ];
        }

        return $result;
    }

    protected function monevAggregate(): array
    {
        $year = $this->currentYear;

        $base = Proposal::query()
            ->whereBetween('created_at', [
                "{$year}-01-01 00:00:00",
                "{$year}-12-31 23:59:59",
            ])
            ->where('status', 'accepted');

        $total = (clone $base)->count();

        $akhir = (clone $base)
            ->whereHas('finalReport', fn ($q) => $q->where('status', 'accepted'))
            ->count();

        $kemajuan = (clone $base)
            ->whereDoesntHave('finalReport', fn ($q) => $q->where('status', 'accepted'))
            ->where(function ($q) {
                $q->whereHas('progressReport', fn ($qq) =>
                        $qq->whereIn('status', ['submitted', 'under_review', 'revised']))
                  ->orWhereHas('output', fn ($qq) =>
                        $qq->whereIn('status', ['pending', 'revised']));
            })
            ->count();

        $kontrak = max($total - $akhir - $kemajuan, 0);

        return compact('total', 'akhir', 'kemajuan', 'kontrak');
    }

    protected function buildStats(array $agg): array
    {
        $curYear  = $this->currentYear;
        $lastYear = $curYear - 1;

        $current = $agg[(string) $curYear] ?? null;
        $last    = $agg[(string) $lastYear] ?? null;

        $totalCurrent = (int) ($current['total'] ?? 0);
        $totalLast    = (int) ($last['total'] ?? 0);
        $approved     = (int) ($current['accepted'] ?? 0);
        $dosen        = (int) ($current['dosen'] ?? 0);

        $yoyPercent = $totalLast > 0
            ? round((($totalCurrent - $totalLast) / $totalLast) * 100, 1)
            : 0;

        $ratio = $totalCurrent > 0
            ? round(($approved / $totalCurrent) * 100, 1)
            : 0;

        return [
            [
                'label'      => 'Usulan Masuk ' . $curYear,
                'value'      => $totalCurrent,
                'unit'       => 'Usulan',
                'note'       => 'Total pengajuan tahun berjalan',
                'trend'      => ($yoyPercent >= 0 ? '+' : '') . $yoyPercent . '% vs ' . $lastYear,
                'trendColor' => $yoyPercent >= 0 ? 'emerald' : 'rose',
                'icon'       => 'users',
                'color'      => 'emerald',
            ],
            [
                'label'      => 'Didanai & Lolos Seleksi',
                'value'      => $approved,
                'unit'       => 'Judul',
                'note'       => 'Status: accepted',
                'trend'      => 'Rasio ' . $ratio . '%',
                'trendColor' => 'slate',
                'icon'       => 'award',
                'color'      => 'teal',
            ],
            [
                'label'      => 'Usulan Tahun ' . $lastYear,
                'value'      => $totalLast,
                'unit'       => 'Usulan',
                'note'       => 'Baseline komparasi',
                'trend'      => 'Realisasi periode sebelumnya',
                'trendColor' => 'slate',
                'icon'       => 'document',
                'color'      => 'cyan',
            ],
            [
                'label'      => 'Dosen Pengusul Aktif',
                'value'      => $dosen,
                'unit'       => 'Dosen',
                'note'       => 'Pengusul unik tahun ini',
                'trend'      => 'Dari berbagai program studi',
                'trendColor' => 'slate',
                'icon'       => 'academic',
                'color'      => 'amber',
            ],
        ];
    }

    protected function buildFundingChart(array $agg): array
    {
        $years = range($this->startYear, $this->currentYear);

        $riset = [];
        $pkm   = [];
        $total = [];
        $labels = [];

        foreach ($years as $year) {
            $key = (string) $year;
            $row = $agg[$key] ?? null;

            $r = (int) ($row['riset'] ?? 0);
            $p = (int) ($row['pengabdian'] ?? 0);

            $labels[] = $key;
            $riset[]  = $r;
            $pkm[]    = $p;
            $total[$key] = $r + $p;
        }

        return [
            'labels' => $labels,
            'riset'  => $riset,
            'pkm'    => $pkm,
            'total'  => $total,
        ];
    }

    protected function buildYoy(array $chart): array
    {
        $rows = [];
        $prev = null;

        foreach ($chart['labels'] as $year) {
            $current = $chart['total'][$year] ?? 0;
            $delta = $prev !== null && $prev > 0
                ? round((($current - $prev) / $prev) * 100, 1)
                : null;

            $rows[] = [
                'year'      => $year,
                'total'     => $current,
                'delta'     => $delta,
                'isCurrent' => (int) $year === $this->currentYear,
            ];

            $prev = $current;
        }

        return $rows;
    }

    protected function buildMonev(): array
    {
        $agg   = $this->monevAggregate();
        $total = $agg['total'];

        $pct = fn (int $n) => $total > 0 ? (int) round(($n / $total) * 100) : 0;

        return [
            'total' => $total,
            'segments' => [
                ['label' => 'Laporan Akhir & Luaran', 'percent' => $pct($agg['akhir']),    'count' => $agg['akhir'],    'color' => '#005d42'],
                ['label' => 'Laporan Kemajuan',       'percent' => $pct($agg['kemajuan']), 'count' => $agg['kemajuan'], 'color' => '#00776b'],
                ['label' => 'Tahap Kontrak & RAB',    'percent' => $pct($agg['kontrak']),  'count' => $agg['kontrak'],  'color' => '#99efe5'],
            ],
        ];
    }

    public function with(): array
    {
        $agg = Cache::remember(
            'lppm:statistik:' . $this->currentYear,
            now()->addMinute(),
            fn () => $this->aggregateByYear()
        );

        $chart = $this->buildFundingChart($agg);

        return [
            'stats'        => $this->buildStats($agg),
            'fundingChart' => $chart,
            'yoy'          => $this->buildYoy($chart),
            'monev'        => $this->buildMonev(),
            'currentYear'  => $this->currentYear,
            'startYear'    => $this->startYear,
        ];
    }
};
?>

<div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" id="statistik">
    {{-- Header --}}
    <div
        class="flex flex-col md:flex-row md:items-end justify-between mb-5 pb-3 border-b border-slate-200/60 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 mb-1">
                <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                    <path d="M4 16 8 12l4 4 6-8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <span class="font-bold tracking-wider uppercase text-[10px]">DATA &amp; METRIK KINERJA</span>
            </div>
            <h2 class="font-heading text-slate-900 dark:text-white font-bold text-base leading-[1.4]">
                Statistik &amp; Capaian Hibah Litabmas Universitas Annuqayah
            </h2>
            <p class="text-slate-500 dark:text-zinc-400 font-normal mt-0.5 text-xs">
                Rekapitulasi berbasis publikasi resmi LPPM UA. Angka diperbarui sesuai pengumuman terbaru.
            </p>
        </div>
        <div class="mt-3 md:mt-0">
            <span
                class="px-3 py-1.5 rounded-lg bg-white/80 border border-slate-200 text-slate-600 dark:bg-zinc-900 dark:border-zinc-800 dark:text-zinc-400 font-medium text-[11px]">
                Sumber: lppm.ua.ac.id &amp; Berita Resmi UA
            </span>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mb-5">
        @foreach ($stats as $i => $stat)
            @php
                $bgClass = match ($stat['color']) {
                    'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400',
                    'teal' => 'bg-teal-50 text-teal-700 dark:bg-teal-900/50 dark:text-teal-400',
                    'cyan' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-900/50 dark:text-cyan-400',
                    'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400',
                    default => 'bg-slate-100 text-slate-700',
                };
                $iconPath = match ($stat['icon']) {
                    'users' => 'M12 3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z M5 20.5c0-3.5 3-6 7-6s7 2.5 7 6',
                    'award' => 'M12 3.5 4.5 7v5c0 4.5 3 8 7.5 10 4.5-2 7.5-5.5 7.5-10V7L12 3.5Z',
                    'document' => 'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                    'academic'
                        => 'M5 19.5A1.5 1.5 0 0 1 3.5 18V6A1.5 1.5 0 0 1 5 4.5h5L14 8v10a1.5 1.5 0 0 1-1.5 1.5H5Z M15 7.5h4.5a1 1 0 0 1 1 1v9.5',
                    default => 'M12 3v18',
                };
            @endphp
            <div class="p-3.5 rounded-xl bg-white/75 backdrop-blur-lg border border-white/90 shadow-sm
                        hover:border-emerald-300 dark:bg-zinc-900/75 dark:border-zinc-800
                        dark:hover:border-emerald-700 transition-all reveal-up"
                style="transition-delay: {{ $i * 80 }}ms">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-slate-500 dark:text-zinc-400 font-medium text-xs">{{ $stat['label'] }}</span>
                    <div class="w-7 h-7 rounded-md {{ $bgClass }} flex items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                            <path d="{{ $iconPath }}" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-slate-900 dark:text-white font-bold text-base">
                        <span data-counter="{{ $stat['value'] }}">0</span> {{ $stat['unit'] }}
                    </span>
                </div>
                <span class="text-slate-400 dark:text-zinc-500 block mt-1 text-[10px]">{{ $stat['note'] }}</span>
                <span class="block mt-1.5 text-[9px] text-slate-400 dark:text-zinc-600 italic leading-tight">
                    {{ $stat['trend'] }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- Chart Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        {{-- Bar Chart --}}
<div class="lg:col-span-8 p-4 rounded-2xl bg-white/75 backdrop-blur-xl border border-white/90
            shadow-sm flex flex-col justify-between dark:bg-zinc-900/75 dark:border-zinc-800 reveal-up">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h3 class="font-heading text-slate-900 dark:text-white font-bold text-sm">
                Distribusi Skema Hibah Internal ({{ $startYear }} – {{ $currentYear }})
            </h3>
            <p class="text-slate-500 dark:text-zinc-400 font-normal text-[11px]">
                Perbandingan jumlah judul riset &amp; pengabdian (PkM) LPPM UA
            </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-sm bg-emerald-700"></span>
                <span class="text-slate-600 dark:text-zinc-300 font-medium text-[10px]">Riset</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-sm bg-teal-600"></span>
                <span class="text-slate-600 dark:text-zinc-300 font-medium text-[10px]">Pengabdian (PkM)</span>
            </div>
        </div>
    </div>

    {{-- wire:ignore WAJIB agar Livewire tidak morph container ApexCharts --}}
    <div class="h-52" wire:ignore>
        <div id="fundingChartUA" class="w-full h-full"></div>
    </div>
</div>

        {{-- Monev Donut --}}
        <div
            class="lg:col-span-4 p-4 rounded-2xl bg-white/75 backdrop-blur-xl border border-white/90
                    shadow-sm flex flex-col justify-between dark:bg-zinc-900/75 dark:border-zinc-800 reveal-up">
            <div>
                <h3 class="font-heading text-slate-900 dark:text-white font-bold text-sm">
                    Monitoring &amp; Evaluasi (Monev)
                </h3>
                <p class="text-slate-500 dark:text-zinc-400 font-normal text-[11px]">
                    Progres penerima hibah internal TA 2025
                </p>
            </div>

            @php
                $total = $monev['total'];
                $segments = $monev['segments'];
                $circumference = 2 * M_PI * 40;
                $offset = 0;
            @endphp

            <div class="relative flex items-center justify-center my-3">
                <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" fill="transparent" r="40" stroke="#f1f5f9" stroke-width="12">
                    </circle>
                    @foreach ($segments as $seg)
                        @php
                            $dash = ($seg['percent'] / 100) * $circumference;
                        @endphp
                        <circle cx="50" cy="50" fill="transparent" r="40" stroke="{{ $seg['color'] }}"
                            stroke-dasharray="{{ $dash }} {{ $circumference - $dash }}"
                            stroke-dashoffset="{{ -$offset }}" stroke-width="12"></circle>
                        @php $offset += $dash; @endphp
                    @endforeach
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-slate-900 dark:text-white font-bold text-base">{{ $total }}</span>
                    <span class="text-slate-500 dark:text-zinc-400 font-medium text-[10px]">Judul Hibah</span>
                </div>
            </div>

            <div class="flex flex-col gap-1.5 pt-1 border-t border-slate-100 dark:border-zinc-800 text-[11px]">
                @foreach ($segments as $seg)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" style="background-color: {{ $seg['color'] }}"></span>
                            <span class="text-slate-600 dark:text-zinc-300">{{ $seg['label'] }}</span>
                        </div>
                        <span class="font-semibold text-slate-800 dark:text-zinc-200">
                            {{ $seg['percent'] }}% ({{ $seg['count'] }})
                        </span>
                    </div>
                @endforeach
            </div>

            <p
                class="mt-2 pt-2 border-t border-slate-100 dark:border-zinc-800 text-[9px] text-slate-400 dark:text-zinc-600 leading-tight">
                Berdasarkan timeline LPPM UA — pencairan Tahap I &amp; kewajiban laporan kemajuan/akhir.
            </p>
        </div>
    </div>
</div>
@script
<script>
    (function () {
        var containerId = 'fundingChartUA';
        var chartData   = @json($fundingChart);
        var currentYear = @json($currentYear);

        function destroyExisting() {
            var el = document.getElementById(containerId);
            if (!el) return;
            if (el.__apexChart) {
                try { el.__apexChart.destroy(); } catch (e) {}
                el.__apexChart = null;
            }
            el.innerHTML = '';
        }

        function buildChart() {
            var el = document.getElementById(containerId);
            if (!el || typeof ApexCharts === 'undefined') return false;

            destroyExisting();

            var isDark    = document.documentElement.classList.contains('dark');
            var textColor = isDark ? '#a1a1aa' : '#475569';
            var gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

            var options = {
                chart: {
                    type: 'bar',
                    height: '100%',
                    fontFamily: "'DM Sans', system-ui, sans-serif",
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeout',
                        speed: 700
                    }
                },
                series: [
                    { name: 'Riset', data: chartData.riset },
                    { name: 'Pengabdian (PkM)', data: chartData.pkm }
                ],
                colors: ['#005d42', '#00776b'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 4,
                        borderRadiusApplication: 'end'
                    }
                },
                dataLabels: { enabled: false },
                stroke: { show: false },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 3,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } }
                },
                xaxis: {
                    categories: chartData.labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: {
                            colors: textColor,
                            fontSize: '11px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: textColor,
                            fontSize: '11px'
                        },
                        formatter: function (val) { return Math.round(val); }
                    }
                },
                legend: { show: false },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: {
                        formatter: function (val) { return val + ' judul'; }
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        shadeIntensity: 0.2,
                        opacityFrom: 1,
                        opacityTo: 0.85,
                        stops: [0, 100]
                    }
                }
            };

            var chart = new ApexCharts(el, options);
            chart.render();
            el.__apexChart = chart;

            return true;
        }

        // Retry singkat kalau DOM belum siap
        var tries = 0;
        (function tryInit() {
            if (buildChart()) return;
            if (tries++ < 30) setTimeout(tryInit, 100);
        })();

        // Update warna saat dark mode toggle
        var observer = new MutationObserver(function () {
            var el = document.getElementById(containerId);
            if (!el || !el.__apexChart) return;

            var isDark = document.documentElement.classList.contains('dark');
            var textColor = isDark ? '#a1a1aa' : '#475569';
            var gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

            el.__apexChart.updateOptions({
                grid: { borderColor: gridColor },
                xaxis: { labels: { style: { colors: textColor } } },
                yaxis: { labels: { style: { colors: textColor } } },
                tooltip: { theme: isDark ? 'dark' : 'light' }
            }, false, false);
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        return function () {
            observer.disconnect();
            destroyExisting();
        };
    })();
</script>
@endscript
