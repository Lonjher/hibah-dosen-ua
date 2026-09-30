<?php

use App\Models\ProgressReport;
use App\Models\Proposal;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reviewer Dashboard')] class extends Component {
    public function with(): array
    {
        $userId = auth()->id();

        // ── Proposal stats ──
        $proposalStats = [
            'under_review' => Proposal::where('reviewer_id', $userId)->where('status', 'under_review')->count(),
            'revised'      => Proposal::where('reviewer_id', $userId)->where('status', 'revised')->count(),
            'accepted'     => Proposal::where('reviewer_id', $userId)->where('status', 'accepted')->count(),
            'rejected'     => Proposal::where('reviewer_id', $userId)->where('status', 'rejected')->count(),
            'research'     => Proposal::where('reviewer_id', $userId)->where('is_research', true)->count(),
            'dedication'   => Proposal::where('reviewer_id', $userId)->where('is_research', false)->count(),
        ];

        // ── Progress Report stats ──
        $progressStats = [
            'under_review' => ProgressReport::where('reviewer_id', $userId)->where('status', 'under_review')->count(),
            'revised'      => ProgressReport::where('reviewer_id', $userId)->where('status', 'revised')->count(),
            'accepted'     => ProgressReport::where('reviewer_id', $userId)->where('status', 'accepted')->count(),
            'rejected'     => ProgressReport::where('reviewer_id', $userId)->where('status', 'rejected')->count(),
        ];

        // ── Pending reviews ──
        $pendingProposals = Proposal::with(['author', 'researchScheme'])
            ->where('reviewer_id', $userId)
            ->where('status', 'under_review')
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

<div class="p-4 sm:p-6 space-y-6">

    <x-dashboard-header icon="clipboard-document-check" title="Reviewer Dashboard"
        leading="Ringkasan tugas review Anda." />

    {{-- ══════════ PROPOSAL STATS ══════════ --}}
    <div>
        <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white mb-3">
            Proposal
        </h3>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @php
                $cards = [
                    ['label' => 'Under Review', 'value' => $proposalStats['under_review'], 'color' => 'violet', 'icon' => 'eye'],
                    ['label' => 'Revision',     'value' => $proposalStats['revised'],      'color' => 'amber',  'icon' => 'arrow-path'],
                    ['label' => 'Accepted',     'value' => $proposalStats['accepted'],     'color' => 'emerald','icon' => 'check-circle'],
                    ['label' => 'Rejected',     'value' => $proposalStats['rejected'],     'color' => 'rose',   'icon' => 'x-circle'],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="rounded-2xl border border-slate-200 dark:border-zinc-800
                            bg-white dark:bg-zinc-900 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="w-8 h-8 rounded-xl bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
                                    flex items-center justify-center">
                            <flux:icon :name="$card['icon']" class="size-4 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                        </div>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">
                        {{ number_format($card['value']) }}
                    </p>
                    <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-0.5">
                        {{ $card['label'] }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- Research vs Dedication --}}
        <div class="mt-3 grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-emerald-200 dark:border-emerald-800
                        bg-emerald-50 dark:bg-emerald-900/20 p-4">
                <div class="flex items-center gap-2">
                    <flux:icon.beaker class="size-4 text-emerald-600 dark:text-emerald-400" />
                    <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">
                        Penelitian
                    </span>
                </div>
                <p class="mt-2 text-xl font-bold text-emerald-700 dark:text-emerald-300">
                    {{ $proposalStats['research'] }}
                </p>
            </div>
            <div class="rounded-2xl border border-rose-200 dark:border-rose-800
                        bg-rose-50 dark:bg-rose-900/20 p-4">
                <div class="flex items-center gap-2">
                    <flux:icon.heart class="size-4 text-rose-600 dark:text-rose-400" />
                    <span class="text-[11px] font-semibold text-rose-700 dark:text-rose-300 uppercase tracking-wider">
                        Pengabdian
                    </span>
                </div>
                <p class="mt-2 text-xl font-bold text-rose-700 dark:text-rose-300">
                    {{ $proposalStats['dedication'] }}
                </p>
            </div>
        </div>
    </div>

    {{-- ══════════ PROGRESS REPORT STATS ══════════ --}}
    <div>
        <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white mb-3">
            Progress Report
        </h3>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @php
                $progressCards = [
                    ['label' => 'Under Review', 'value' => $progressStats['under_review'], 'color' => 'violet', 'icon' => 'eye'],
                    ['label' => 'Revision',     'value' => $progressStats['revised'],      'color' => 'amber',  'icon' => 'arrow-path'],
                    ['label' => 'Accepted',     'value' => $progressStats['accepted'],     'color' => 'emerald','icon' => 'check-circle'],
                    ['label' => 'Rejected',     'value' => $progressStats['rejected'],     'color' => 'rose',   'icon' => 'x-circle'],
                ];
            @endphp

            @foreach ($progressCards as $card)
                <div class="rounded-2xl border border-slate-200 dark:border-zinc-800
                            bg-white dark:bg-zinc-900 p-4 shadow-sm">
                    <div class="w-8 h-8 rounded-xl bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
                                flex items-center justify-center">
                        <flux:icon :name="$card['icon']" class="size-4 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                    </div>
                    <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">
                        {{ number_format($card['value']) }}
                    </p>
                    <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-0.5">
                        {{ $card['label'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════ PENDING ITEMS ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Pending Proposals --}}
        <div class="rounded-2xl border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-zinc-800 flex items-center justify-between">
                <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white">
                    Proposal Menunggu Review
                </h3>
                <a href="{{ route('reviewer.review-proposal') }}"
                    class="text-[11px] font-medium text-violet-600 dark:text-violet-400 hover:underline">
                    Lihat semua →
                </a>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-zinc-800">
                @forelse ($pendingProposals as $proposal)
                    <div class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div class="w-8 h-8 rounded-lg shrink-0
                                    {{ $proposal->is_research
                                        ? 'bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/20'
                                        : 'bg-gradient-to-br from-rose-100 to-pink-50 dark:from-rose-900/30 dark:to-pink-900/20' }}
                                    flex items-center justify-center">
                            <flux:icon :name="$proposal->is_research ? 'beaker' : 'heart'"
                                class="size-3.5 {{ $proposal->is_research
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-rose-600 dark:text-rose-400' }}" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-900 dark:text-white line-clamp-1">
                                {{ $proposal->title }}
                            </p>
                            <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                {{ $proposal->author?->full_name ?? '—' }}
                            </p>
                        </div>
                        <a href="{{ route('reviewer.review-proposal') }}"
                            class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-md
                                   text-[10px] font-semibold text-white
                                   bg-gradient-to-r from-violet-600 to-violet-500
                                   hover:from-violet-700 hover:to-violet-600">
                            Review
                        </a>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <flux:icon.check-circle class="size-8 text-emerald-400 mx-auto mb-2" />
                        <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                            Tidak ada proposal pending. 🎉
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Pending Progress Reports --}}
        <div class="rounded-2xl border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-zinc-800 flex items-center justify-between">
                <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white">
                    Progress Report Menunggu Review
                </h3>
                <a href="{{ route('reviewer.review-progress-report') }}"
                    class="text-[11px] font-medium text-violet-600 dark:text-violet-400 hover:underline">
                    Lihat semua →
                </a>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-zinc-800">
                @forelse ($pendingProgress as $report)
                    <div class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div class="w-8 h-8 rounded-lg shrink-0 bg-violet-100 dark:bg-violet-900/30
                                    flex items-center justify-center">
                            <flux:icon.document-chart-bar class="size-3.5 text-violet-600 dark:text-violet-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-900 dark:text-white line-clamp-1">
                                {{ $report->proposal?->title ?? '—' }}
                            </p>
                            <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                Keyword: {{ $report->keyword }}
                            </p>
                        </div>
                        <a href="{{ route('reviewer.review-progress-report') }}"
                            class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-md
                                   text-[10px] font-semibold text-white
                                   bg-gradient-to-r from-violet-600 to-violet-500
                                   hover:from-violet-700 hover:to-violet-600">
                            Review
                        </a>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <flux:icon.check-circle class="size-8 text-emerald-400 mx-auto mb-2" />
                        <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                            Tidak ada progress report pending. 🎉
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
