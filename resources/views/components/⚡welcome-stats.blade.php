<?php

use App\Models\ExternalProposal;
use App\Models\Period;
use App\Models\Proposal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component {
    public int $currentYear;
    public int $startYear;
    public string $periodFilter = 'all';

    public function mount(): void
    {
        $this->currentYear = (int) now()->year;
        $this->startYear   = $this->computeStartYear();

        // Default: periode aktif → terbaru → all
        $active = Period::where('is_active', true)->first();
        if ($active) {
            $this->periodFilter = (string) $active->id;
        } else {
            $latest = Period::orderByDesc('open_from')->orderByDesc('id')->first();
            $this->periodFilter = $latest ? (string) $latest->id : 'all';
        }
    }

    /**
     * Hitung startYear dinamis dari data paling awal (proposal internal + external).
     * Fallback ke currentYear kalau belum ada data sama sekali.
     */
    protected function computeStartYear(): int
    {
        $minProposal = Proposal::query()
            ->selectRaw('MIN(YEAR(created_at)) as y')
            ->value('y');

        $minExternal = ExternalProposal::query()
            ->whereNotNull('start_date')
            ->selectRaw('MIN(YEAR(start_date)) as y')
            ->value('y');

        $candidates = array_filter(
            [$minProposal, $minExternal],
            fn ($v) => ! is_null($v) && (int) $v > 0
        );

        return ! empty($candidates)
            ? (int) min($candidates)
            : (int) now()->year;
    }

    protected function getSelectedPeriod(): ?Period
    {
        return $this->periodFilter === 'all'
            ? null
            : Period::find((int) $this->periodFilter);
    }

    protected function aggregateByYear(): array
    {
        $years = range($this->startYear, $this->currentYear);
        $period = $this->getSelectedPeriod();

        // ── Internal Proposals ──
        $proposalRows = Proposal::query()
            ->when($period, fn ($q) => $q->where('period_id', $period->id))
            ->where(function ($q) use ($years) {
                foreach ($years as $y) {
                    $q->orWhereBetween('proposals.created_at', ["{$y}-01-01 00:00:00", "{$y}-12-31 23:59:59"]);
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

        // ── External ──
        $externalRows = ExternalProposal::query()
            ->when($period, fn ($q) => $q
                ->whereBetween('start_date', [$period->open_from, $period->open_to]))
            ->where(function ($q) use ($years) {
                foreach ($years as $y) {
                    $q->orWhereBetween('external_proposals.start_date', ["{$y}-01-01", "{$y}-12-31"]);
                }
            })
            ->selectRaw('YEAR(external_proposals.start_date) as year')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN external_proposals.is_research = 1 THEN 1 ELSE 0 END) as riset')
            ->selectRaw('SUM(CASE WHEN external_proposals.is_research = 0 THEN 1 ELSE 0 END) as pengabdian')
            ->selectRaw('COUNT(DISTINCT external_proposals.user_id) as dosen')
            ->groupByRaw('YEAR(external_proposals.start_date)')
            ->get();

        $result = [];
        foreach ($proposalRows as $row) {
            $key = (string) $row->year;
            $result[$key] = [
                'total'          => (int) $row->total,
                'accepted'       => (int) $row->accepted,
                'riset'          => (int) $row->riset,
                'pengabdian'     => (int) $row->pengabdian,
                'dosen'          => (int) $row->dosen,
                'ext_riset'      => 0,
                'ext_pengabdian' => 0,
                'ext_total'      => 0,
                'ext_dosen'      => 0,
            ];
        }

        foreach ($externalRows as $row) {
            $key = (string) $row->year;
            if (! isset($result[$key])) {
                $result[$key] = [
                    'total' => 0, 'accepted' => 0, 'riset' => 0, 'pengabdian' => 0, 'dosen' => 0,
                    'ext_riset' => 0, 'ext_pengabdian' => 0, 'ext_total' => 0, 'ext_dosen' => 0,
                ];
            }
            $result[$key]['ext_riset']      = (int) $row->riset;
            $result[$key]['ext_pengabdian'] = (int) $row->pengabdian;
            $result[$key]['ext_total']      = (int) $row->total;
            $result[$key]['ext_dosen']      = (int) $row->dosen;
            $result[$key]['dosen']          = max($result[$key]['dosen'], (int) $row->dosen);
        }

        return $result;
    }

    protected function monevAggregate(): array
    {
        $year = $this->currentYear;
        $period = $this->getSelectedPeriod();

        $base = Proposal::query()
            ->when($period, fn ($q) => $q->where('period_id', $period->id))
            ->whereBetween('created_at', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"])
            ->where('status', 'accepted');

        $total = (clone $base)->count();

        $akhir = (clone $base)->whereHas('finalReport', fn ($q) => $q->where('status', 'accepted'))->count();

        $kemajuan = (clone $base)
            ->whereDoesntHave('finalReport', fn ($q) => $q->where('status', 'accepted'))
            ->where(function ($q) {
                $q->whereHas('progressReport', fn ($qq) => $qq->whereIn('status', ['submitted', 'under_review', 'revised']))
                  ->orWhereHas('outcome', fn ($qq) => $qq->whereIn('status', ['pending', 'revised']));
            })
            ->count();

        $kontrak = max($total - $akhir - $kemajuan, 0);

        return compact('total', 'akhir', 'kemajuan', 'kontrak');
    }

    protected function buildStats(array $agg): array
    {
        $period = $this->getSelectedPeriod();

        if ($period) {
            // ── Kalau periode dipilih: SUM semua data dalam periode ──
            $current = [
                'total'          => (int) array_sum(array_column($agg, 'total')),
                'accepted'       => (int) array_sum(array_column($agg, 'accepted')),
                'ext_riset'      => (int) array_sum(array_column($agg, 'ext_riset')),
                'ext_pengabdian' => (int) array_sum(array_column($agg, 'ext_pengabdian')),
                'dosen'          => (int) (max(array_column($agg, 'dosen') ?: [0])),
            ];

            $labelSuffix = ' (' . $period->periode . ')';
            $trendLabel  = 'Periode ' . $period->periode;
            $yoyTrend    = 'Data agregat periode';
        } else {
            // ── Kalau "Semua Periode": pakai currentYear vs lastYear ──
            $current = $agg[(string) $this->currentYear] ?? null;
            $last    = $agg[(string) ($this->currentYear - 1)] ?? null;

            $totalCurrent = (int) ($current['total'] ?? 0);
            $totalLast    = (int) ($last['total'] ?? 0);
            $yoyPercent   = $totalLast > 0
                ? round((($totalCurrent - $totalLast) / $totalLast) * 100, 1)
                : 0;

            $labelSuffix = ' ' . $this->currentYear;
            $trendLabel  = 'vs ' . ($this->currentYear - 1);
            $yoyTrend    = ($yoyPercent >= 0 ? '+' : '') . $yoyPercent . '% ' . $trendLabel;
        }

        $totalCurrent = (int) ($current['total'] ?? 0);
        $approved     = (int) ($current['accepted'] ?? 0);
        $extRiset     = (int) ($current['ext_riset'] ?? 0);
        $extPkm       = (int) ($current['ext_pengabdian'] ?? 0);
        $ratio        = $totalCurrent > 0 ? round(($approved / $totalCurrent) * 100, 1) : 0;

        return [
            [
                'label' => 'Usulan Internal' . $labelSuffix,
                'value' => $totalCurrent,
                'unit'  => 'Usulan',
                'note'  => 'Pengajuan hibah internal',
                'trend' => $period ? $yoyTrend : $yoyTrend,
                'icon'  => 'users',
                'color' => 'emerald',
            ],
            [
                'label' => 'Didanai & Lolos Seleksi',
                'value' => $approved,
                'unit'  => 'Judul',
                'note'  => 'Status: accepted',
                'trend' => 'Rasio ' . $ratio . '%',
                'icon'  => 'award',
                'color' => 'teal',
            ],
            [
                'label' => 'Penelitian External',
                'value' => $extRiset,
                'unit'  => 'Judul',
                'note'  => 'Sumber dana eksternal',
                'trend' => $period ? ('Periode ' . $period->periode) : 'DRTPM, BRIN, Industri',
                'icon'  => 'globe',
                'color' => 'indigo',
            ],
            [
                'label' => 'Pengabdian External',
                'value' => $extPkm,
                'unit'  => 'Judul',
                'note'  => 'Sumber dana eksternal',
                'trend' => $period ? ('Periode ' . $period->periode) : 'DRTPM, Pemda, CSR',
                'icon'  => 'gift',
                'color' => 'amber',
            ],
        ];
    }

    protected function buildFundingChart(array $agg): array
    {
        $years = range($this->startYear, $this->currentYear);

        $riset = [];
        $pkm = [];
        $extRiset = [];
        $extPkm = [];
        $total = [];
        $labels = [];

        foreach ($years as $year) {
            $key = (string) $year;
            $row = $agg[$key] ?? null;

            $r  = (int) ($row['riset'] ?? 0);
            $p  = (int) ($row['pengabdian'] ?? 0);
            $er = (int) ($row['ext_riset'] ?? 0);
            $ep = (int) ($row['ext_pengabdian'] ?? 0);

            $labels[]    = $key;
            $riset[]     = $r;
            $pkm[]       = $p;
            $extRiset[]  = $er;
            $extPkm[]    = $ep;
            $total[$key] = $r + $p + $er + $ep;
        }

        return [
            'labels'    => $labels,
            'riset'     => $riset,
            'pkm'       => $pkm,
            'ext_riset' => $extRiset,
            'ext_pkm'   => $extPkm,
            'total'     => $total,
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
                'year' => $year,
                'total' => $current,
                'delta' => $delta,
                'isCurrent' => (int) $year === $this->currentYear,
            ];

            $prev = $current;
        }

        return $rows;
    }

    protected function buildMonev(): array
    {
        $agg = $this->monevAggregate();
        $total = $agg['total'];

        $pct = fn (int $n) => $total > 0 ? (int) round(($n / $total) * 100) : 0;

        return [
            'total' => $total,
            'segments' => [
                ['label' => 'Laporan Akhir & Luaran', 'percent' => $pct($agg['akhir']), 'count' => $agg['akhir'], 'color' => '#005d42'],
                ['label' => 'Laporan Kemajuan', 'percent' => $pct($agg['kemajuan']), 'count' => $agg['kemajuan'], 'color' => '#00776b'],
                ['label' => 'Tahap Kontrak & RAB', 'percent' => $pct($agg['kontrak']), 'count' => $agg['kontrak'], 'color' => '#99efe5'],
            ],
        ];
    }

    public function with(): array
    {
        // cache key include startYear, currentYear, dan periodFilter
        $cacheKey = 'lppm:statistik:' . $this->startYear . ':' . $this->currentYear . ':' . $this->periodFilter;
        $agg = Cache::remember($cacheKey, now()->addMinute(), fn () => $this->aggregateByYear());

        $chart = $this->buildFundingChart($agg);

        return [
            'stats'          => $this->buildStats($agg),
            'fundingChart'   => $chart,
            'yoy'            => $this->buildYoy($chart),
            'monev'          => $this->buildMonev(),
            'currentYear'    => $this->currentYear,
            'startYear'      => $this->startYear,
            'periods'        => Period::orderByDesc('open_from')->orderByDesc('id')->get(),
            'selectedPeriod' => $this->getSelectedPeriod(),
        ];
    }
};
?>

<div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" id="statistik">

    {{-- ══════════ Header + Period Filter ══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <x-section-header label="Data & Metrik Kinerja" title="Statistik & Capaian Hibah Litabmas Universitas Annuqayah"
            description="Rekapitulasi berbasis publikasi resmi LPPM UA. Angka diperbarui sesuai pengumuman terbaru."
            icon="M4 16 8 12l4 4 6-8" />

        <div class="flex items-center gap-2 shrink-0 sm:mt-1">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full
                        bg-white/75 backdrop-blur-lg
                        border border-white/90 dark:border-zinc-800
                        dark:bg-zinc-900/75
                        shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50">
                <flux:icon.calendar-days class="size-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <select wire:model.live="periodFilter"
                    class="text-[11px] font-medium bg-transparent border-0
                           text-slate-700 dark:text-zinc-200
                           focus:outline-none focus:ring-0 cursor-pointer
                           pr-4 py-0 pl-0">
                    <option value="all">Semua Periode</option>
                    @foreach ($periods as $p)
                        <option value="{{ $p->id }}">{{ $p->periode }}</option>
                    @endforeach
                </select>
            </div>

            <div wire:loading wire:target="periodFilter"
                class="w-4 h-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                       flex items-center justify-center shrink-0">
                <svg class="animate-spin size-2.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- ══════════ KPI Cards ══════════ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mb-5">
        @foreach ($stats as $i => $stat)
            @php
                $bgClass = match ($stat['color']) {
                    'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400',
                    'teal'    => 'bg-teal-50 text-teal-700 dark:bg-teal-900/50 dark:text-teal-400',
                    'indigo'  => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400',
                    'amber'   => 'bg-amber-50 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400',
                    default   => 'bg-slate-100 text-slate-700',
                };
                $hoverBorder = match ($stat['color']) {
                    'emerald' => 'hover:border-emerald-300 dark:hover:border-emerald-700',
                    'teal'    => 'hover:border-teal-300 dark:hover:border-teal-700',
                    'indigo'  => 'hover:border-indigo-300 dark:hover:border-indigo-700',
                    'amber'   => 'hover:border-amber-300 dark:hover:border-amber-700',
                    default   => 'hover:border-slate-300',
                };
                $iconPath = match ($stat['icon']) {
                    'users'  => 'M12 3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z M5 20.5c0-3.5 3-6 7-6s7 2.5 7 6',
                    'award'  => 'M12 3.5 4.5 7v5c0 4.5 3 8 7.5 10 4.5-2 7.5-5.5 7.5-10V7L12 3.5Z',
                    'globe'  => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z M3 12h18 M12 3c2.5 3 2.5 15 0 18 M12 3c-2.5 3-2.5 15 0 18',
                    'gift'   => 'M3 8.5h18v3H3v-3Z M4.5 11.5h15v8a1.5 1.5 0 0 1-1.5 1.5H6a1.5 1.5 0 0 1-1.5-1.5v-8Z M12 8.5v12 M8 8.5A2.5 2.5 0 1 1 10.5 6 2.5 2.5 0 1 1 13 8.5',
                    default  => 'M12 3v18',
                };
            @endphp

            {{-- wire:key dinamis (include periodFilter) supaya Alpine counter re-init saat filter ganti --}}
            <div wire:key="kpi-{{ $i }}-{{ $periodFilter }}"
                x-data="{
                    display: 0,
                    target: {{ (int) $stat['value'] }},
                    init() {
                        if (this.target <= 0) { this.display = 0; return; }
                        const duration = 700;
                        const start = performance.now();
                        const step = (now) => {
                            const t = Math.min((now - start) / duration, 1);
                            const eased = 1 - Math.pow(1 - t, 2); // ease-out quad
                            this.display = Math.floor(eased * this.target);
                            if (t < 1) requestAnimationFrame(step);
                            else this.display = this.target;
                        };
                        requestAnimationFrame(step);
                    }
                }"
                style="transition-delay: {{ $i * 80 }}ms"
                class="group p-3.5 rounded-2xl bg-white/75 backdrop-blur-lg border border-white/90 shadow-sm
                    {{ $hoverBorder }}
                    hover:shadow-lg hover:-translate-y-0.5 hover:scale-[1.01]
                    dark:bg-zinc-900/75 dark:border-zinc-800
                    transition-all duration-300 ease-out cursor-pointer
                    reveal-up">

                <div class="flex items-center justify-between mb-2">
                    <span class="text-slate-500 dark:text-zinc-400 font-medium text-xs
                                transition-colors duration-300
                                group-hover:text-emerald-600 dark:group-hover:text-emerald-400">
                        {{ $stat['label'] }}
                    </span>
                    <div class="w-7 h-7 rounded-xl {{ $bgClass }} flex items-center justify-center
                                transition-all duration-300 ease-out
                                group-hover:scale-110 group-hover:rotate-6 group-hover:shadow-md">
                        <svg viewBox="0 0 24 24" fill="none"
                            class="w-3.5 h-3.5 transition-transform duration-300 ease-out group-hover:scale-110">
                            <path d="{{ $iconPath }}" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>

                <div class="flex items-baseline gap-2">
                    <span class="text-slate-900 dark:text-white font-bold text-base
                                transition-colors duration-300
                                group-hover:text-emerald-600 dark:group-hover:text-emerald-400">
                        <span x-text="display">0</span> {{ $stat['unit'] }}
                    </span>
                </div>

                <span class="text-slate-400 dark:text-zinc-500 block mt-1 text-[10px]
                            transition-colors duration-300 group-hover:text-slate-500">
                    {{ $stat['note'] }}
                </span>

                <span class="block mt-1.5 text-[9px] text-slate-400 dark:text-zinc-600 italic leading-tight
                            transition-colors duration-300 group-hover:text-slate-500">
                    {{ $stat['trend'] }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- ══════════ Chart Grid ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

        {{-- ══════════ Bar Chart — Alpine Component + wire:key dinamis ══════════ --}}
        <div wire:key="funding-chart-{{ $periodFilter }}"
            x-data="fundingChartComponent(@js($fundingChart))"
            x-init="init()"
            class="group lg:col-span-8 p-4 rounded-2xl bg-white/75 backdrop-blur-xl border border-white/90
                   shadow-sm flex flex-col justify-between dark:bg-zinc-900/75 dark:border-zinc-800 reveal-up
                   transition-all duration-300 ease-out
                   hover:scale-[1.01] hover:-translate-y-0.5
                   hover:border-emerald-300/80 hover:shadow-xl hover:shadow-emerald-200/40
                   dark:hover:border-emerald-700/70 dark:hover:shadow-emerald-900/30">

            <div class="flex items-center justify-between mb-3 gap-2 flex-wrap">
                <div class="min-w-0">
                    <h3 class="font-heading text-slate-900 dark:text-white font-bold text-sm
                               transition-colors duration-300
                               group-hover:text-emerald-700 dark:group-hover:text-emerald-400">
                        Distribusi Hibah
                        {{ $selectedPeriod ? $selectedPeriod->periode : $startYear . ' – ' . $currentYear }}
                    </h3>
                    <p class="text-slate-500 dark:text-zinc-400 font-normal text-[11px]">
                        Komparasi internal & external — riset vs pengabdian
                    </p>
                </div>
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mb-2">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-emerald-700 transition-transform duration-500 group-hover:scale-125"></span>
                    <span class="text-slate-600 dark:text-zinc-300 font-medium text-[10px]">Riset Internal</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-teal-600 transition-transform duration-500 group-hover:scale-125"></span>
                    <span class="text-slate-600 dark:text-zinc-300 font-medium text-[10px]">PkM Internal</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-indigo-500 transition-transform duration-500 group-hover:scale-125"></span>
                    <span class="text-slate-600 dark:text-zinc-300 font-medium text-[10px]">Riset External</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-amber-500 transition-transform duration-500 group-hover:scale-125"></span>
                    <span class="text-slate-600 dark:text-zinc-300 font-medium text-[10px]">PkM External</span>
                </div>
            </div>

            {{-- Chart container --}}
            <div class="h-52">
                <div x-ref="chartEl" class="w-full h-full"></div>
            </div>
        </div>

        {{-- Monev Donut --}}
        <div class="group lg:col-span-4 p-4 rounded-2xl bg-white/75 backdrop-blur-xl border border-white/90
                    shadow-sm flex flex-col justify-between dark:bg-zinc-900/75 dark:border-zinc-800 reveal-up
                    transition-all duration-300 ease-out
                    hover:scale-[1.01] hover:-translate-y-0.5
                    hover:border-emerald-300/80 hover:shadow-xl hover:shadow-emerald-200/40
                    dark:hover:border-emerald-700/70 dark:hover:shadow-emerald-900/30">
            <div>
                <h3 class="font-heading text-slate-900 dark:text-white font-bold text-sm
                           transition-colors duration-300
                           group-hover:text-emerald-700 dark:group-hover:text-emerald-400">
                    Monitoring & Evaluasi (Monev)
                </h3>
                <p class="text-slate-500 dark:text-zinc-400 font-normal text-[11px]">
                    Progres penerima hibah internal TA {{ $currentYear }}
                </p>
            </div>

            @php
                $total = $monev['total'];
                $segments = $monev['segments'];
                $circumference = 2 * M_PI * 40;
                $offset = 0;
            @endphp

            <div class="relative flex items-center justify-center my-3">
                <svg class="w-32 h-32 transform -rotate-90 transition-transform duration-500 ease-out group-hover:scale-105"
                    viewBox="0 0 100 100">
                    <circle cx="50" cy="50" fill="transparent" r="40" stroke="#f1f5f9" stroke-width="12"></circle>
                    @foreach ($segments as $seg)
                        @php $dash = ($seg['percent'] / 100) * $circumference; @endphp
                        <circle cx="50" cy="50" fill="transparent" r="40" stroke="{{ $seg['color'] }}"
                            stroke-dasharray="{{ $dash }} {{ $circumference - $dash }}"
                            stroke-dashoffset="{{ -$offset }}" stroke-width="12"
                            class="transition-opacity duration-500 group-hover:opacity-90"></circle>
                        @php $offset += $dash; @endphp
                    @endforeach
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-slate-900 dark:text-white font-bold text-base
                                 transition-transform duration-300 group-hover:scale-110">
                        {{ $total }}
                    </span>
                    <span class="text-slate-500 dark:text-zinc-400 font-medium text-[10px]">Judul Hibah</span>
                </div>
            </div>

            <div class="flex flex-col gap-1.5 pt-1 border-t border-slate-100 dark:border-zinc-800 text-[11px]">
                @foreach ($segments as $seg)
                    <div class="flex items-center justify-between transition-transform duration-300 group-hover:translate-x-0.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full transition-transform duration-300 group-hover:scale-125"
                                style="background-color: {{ $seg['color'] }}"></span>
                            <span class="text-slate-600 dark:text-zinc-300">{{ $seg['label'] }}</span>
                        </div>
                        <span class="font-semibold text-slate-800 dark:text-zinc-200">
                            {{ $seg['percent'] }}% ({{ $seg['count'] }})
                        </span>
                    </div>
                @endforeach
            </div>

            <p class="mt-2 pt-2 border-t border-slate-100 dark:border-zinc-800 text-[9px] text-slate-400 dark:text-zinc-600 leading-tight">
                Berdasarkan timeline LPPM UA — pencairan Tahap I & kewajiban laporan kemajuan/akhir.
            </p>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('fundingChartComponent', (chartData) => ({
        chart: null,

        init() {
            if (typeof ApexCharts === 'undefined') {
                console.error('[Funding Chart] ApexCharts not loaded');
                return;
            }
            this.$nextTick(() => setTimeout(() => this.render(), 100));

            // Watch dark mode
            this.$watch(() => document.documentElement.classList.contains('dark'), () => {
                this.updateTheme();
            });
        },

        render() {
            if (typeof ApexCharts === 'undefined') return;

            const el = this.$refs.chartEl;
            if (!el) return;

            if (this.chart) {
                try { this.chart.destroy(); } catch (e) {}
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
                    fontFamily: "'DM Sans', system-ui, sans-serif",
                    toolbar: { show: false },
                    animations: { enabled: true, easing: 'easeout', speed: 700 },
                },
                series: [
                    { name: 'Riset Internal', data: chartData.riset },
                    { name: 'PkM Internal',   data: chartData.pkm },
                    { name: 'Riset External', data: chartData.ext_riset },
                    { name: 'PkM External',   data: chartData.ext_pkm },
                ],
                colors: ['#005d42', '#00776b', '#6366f1', '#f59e0b'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '65%',
                        borderRadius: 3,
                        borderRadiusApplication: 'end',
                    },
                },
                dataLabels: { enabled: false },
                stroke: { show: false },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 3,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } },
                },
                xaxis: {
                    categories: chartData.labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { colors: textColor, fontSize: '10.5px' } },
                },
                yaxis: {
                    labels: {
                        style: { colors: textColor, fontSize: '10.5px' },
                        formatter: v => Math.round(v),
                    },
                },
                legend: { show: false },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: { formatter: v => v + ' judul' },
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
            if (! this.chart) return;

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#a1a1aa' : '#475569';
            const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

            this.chart.updateOptions({
                grid: { borderColor: gridColor },
                xaxis: { labels: { style: { colors: textColor } } },
                yaxis: { labels: { style: { colors: textColor } } },
                tooltip: { theme: isDark ? 'dark' : 'light' },
            }, false, false);
        },

        destroy() {
            if (this.chart) {
                try { this.chart.destroy(); } catch (e) {}
                this.chart = null;
            }
        },
    }));
</script>
@endscript
