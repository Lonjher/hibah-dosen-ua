<?php

use App\Models\ResearchScheme;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function activeSchemesCount(): int
    {
        return ResearchScheme::query()
            ->where('is_active', true)
            ->count();
    }

    #[Computed]
    public function totalBudgetLimit(): int
    {
        return (int) ResearchScheme::query()
            ->where('is_active', true)
            ->sum('budget_limit');
    }

    #[Computed]
    public function totalBudgetLimitFormatted(): string
    {
        return $this->formatRupiahShort($this->totalBudgetLimit);
    }

    #[Computed]
    public function reviewersCount(): int
    {
        return User::query()
            ->whereHas('role', fn ($q) => $q->where('role_code', 'REVIEWER'))
            ->count();
    }

    /**
     * Format angka ke bentuk singkat (Juta / Miliar).
     * Contoh: 1_800_000_000 → "1.8 Billion"
     *         250_000_000   → "250 Million"
     *         25_000_000    → "25 Million"
     *         1_500_000     → "1.5 Million"
     */
    protected function formatRupiahShort(int $value): string
    {
        if ($value <= 0) {
            return 'IDR 0';
        }

        if ($value >= 1_000_000_000) {
            $n = $value / 1_000_000_000;
            return 'IDR ' . $this->trimNumber($n) . ' Billion';
        }

        if ($value >= 1_000_000) {
            $n = $value / 1_000_000;
            return 'IDR ' . $this->trimNumber($n) . ' Million';
        }

        if ($value >= 1_000) {
            $n = $value / 1_000;
            return 'IDR ' . $this->trimNumber($n) . 'K';
        }

        return 'IDR ' . number_format($value, 0, ',', '.');
    }

    protected function trimNumber(float $n): string
    {
        // 1.80 → "1.8", 1.00 → "1", 250.00 → "250"
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
};
?>

<div>
    <div class="reveal-up w-full mt-2.5 p-3 rounded-full bg-white/70 backdrop-blur-lg border border-white/80 shadow-sm grid grid-cols-3 gap-2 divide-x divide-slate-200/60 dark:bg-zinc-900/70 dark:border-zinc-800 dark:divide-zinc-800"
        style="transition-delay: 320ms">

        {{-- ───── Active Schemes ───── --}}
        <div class="flex items-center gap-2 px-2 transition-transform duration-300 hover:-translate-y-0.5">
            <div class="w-7 h-7 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0 dark:bg-emerald-900/50">
                <svg viewBox="0 0 24 24" fill="none"
                    class="w-5 h-5 text-emerald-700 dark:text-emerald-400">
                    <path d="M4 6.5 12 3l8 3.5v5c0 4-2.5 7-8 9-5.5-2-8-5-8-9v-5Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="flex flex-col min-w-0">
                <span class="text-slate-900 dark:text-white font-bold truncate text-xs">
                    {{ $this->activeSchemesCount }} Active {{ Str::plural('Scheme', $this->activeSchemesCount) }}
                </span>
                <span class="text-slate-500 dark:text-zinc-400 font-medium truncate text-[10px]">
                    Research; Community Service
                </span>
            </div>
        </div>

        {{-- ───── Total Budget ───── --}}
        <div class="flex items-center gap-2 px-2 transition-transform duration-300 hover:-translate-y-0.5">
            <div class="w-7 h-7 rounded-xl bg-teal-100 flex items-center justify-center shrink-0 dark:bg-teal-900/50">
                <svg viewBox="0 0 24 24" fill="none"
                    class="w-5 h-5 text-teal-700 dark:text-teal-400">
                    <path
                        d="M3.5 8h17M3.5 8a1.5 1.5 0 0 1 1.5-1.5h14A1.5 1.5 0 0 1 20.5 8v9A1.5 1.5 0 0 1 19 18.5H5A1.5 1.5 0 0 1 3.5 17V8Z M8 14h2"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="flex flex-col min-w-0">
                <span class="text-slate-900 dark:text-white font-bold truncate text-xs">
                    {{ $this->totalBudgetLimitFormatted }}
                </span>
                <span class="text-slate-500 dark:text-zinc-400 font-medium truncate text-[10px]">
                    UA Grant Allocation
                </span>
            </div>
        </div>

        {{-- ───── Reviewers ───── --}}
        <div class="flex items-center gap-2 px-2 transition-transform duration-300 hover:-translate-y-0.5">
            <div class="w-7 h-7 rounded-xl bg-cyan-100 flex items-center justify-center shrink-0 dark:bg-cyan-900/50">
                <svg viewBox="0 0 24 24" fill="none"
                    class="w-5 h-5 text-cyan-700 dark:text-cyan-400">
                    <path d="M12 3.5 4.5 7v5c0 4.5 3 8 7.5 10 4.5-2 7.5-5.5 7.5-10V7L12 3.5Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="flex flex-col min-w-0">
                <span class="text-slate-900 dark:text-white font-bold truncate text-xs">
                    {{ $this->reviewersCount }}+ {{ Str::plural('Reviewer', $this->reviewersCount) }}
                </span>
                <span class="text-slate-500 dark:text-zinc-400 font-medium truncate text-[10px]">
                    Nationally Certified
                </span>
            </div>
        </div>
    </div>
</div>
