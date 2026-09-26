<?php

use App\Models\Period;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public function with(): array
    {
        return [
            'stats' => [
                'users'      => User::whereHas('role', fn($q) => $q->where('role_code', 'USER'))->count(),
                'reviewers'  => User::whereHas('role', fn($q) => $q->where('role_code', 'REVIEWER'))->count(),
                'schemes'    => ResearchScheme::count(),
                'proposals'  => Proposal::count(),
            ],
            'recentProposals' => Proposal::with(['author', 'researchScheme'])
                ->latest()
                ->take(5)
                ->get(),
            'activePeriod' => Period::where('is_active', true)->first(),
            'statusBreakdown' => [
                'pending'      => Proposal::where('status', 'pending')->count(),
                'submitted'    => Proposal::where('status', 'submitted')->count(),
                'under_review' => Proposal::where('status', 'under_review')->count(),
                'accepted'     => Proposal::where('status', 'accepted')->count(),
                'rejected'     => Proposal::where('status', 'rejected')->count(),
            ],
        ];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950 p-3 sm:p-4 lg:p-6">
    <div class="max-w-7xl mx-auto space-y-4">

        <x-dashboard-header icon="home" title="Dashboard"
            leading="Ringkasan aktivitas hibah penelitian dan pengabdian." />

        {{-- Active Period Banner --}}
        @if ($activePeriod)
            <div class="rounded-2xl border border-emerald-200 dark:border-emerald-800
                        bg-gradient-to-r from-emerald-50 to-teal-50
                        dark:from-emerald-900/20 dark:to-teal-900/20 p-4
                        flex items-start sm:items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center shrink-0">
                    <flux:icon.calendar-days class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[11px] font-medium text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">
                        Periode Aktif
                    </p>
                    <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-100 mt-0.5">
                        {{ $activePeriod->periode }}
                    </p>
                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                        Dibuka {{ $activePeriod->open_from?->format('d M Y') ?? '—' }} s/d {{ $activePeriod->open_to?->format('d M Y') ?? '—' }}
                    </p>
                </div>
                <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-500 text-white text-[10px] font-semibold shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                    Aktif
                </span>
            </div>
        @endif

        {{-- Stats Grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @php
                $cards = [
                    ['icon' => 'users',          'label' => 'Total Users',      'value' => $stats['users'],     'color' => 'emerald'],
                    ['icon' => 'user',     'label' => 'Total Reviewers',  'value' => $stats['reviewers'], 'color' => 'blue'],
                    ['icon' => 'beaker',         'label' => 'Total Schemes',    'value' => $stats['schemes'],   'color' => 'violet'],
                    ['icon' => 'document-text',  'label' => 'Total Proposals',  'value' => $stats['proposals'], 'color' => 'amber'],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="group rounded-2xl border border-slate-200 dark:border-zinc-800
                            bg-white dark:bg-zinc-900 p-4 shadow-sm
                            hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="w-9 h-9 rounded-xl bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
                                    flex items-center justify-center group-hover:scale-105 transition-transform">
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

        {{-- Two Column: Status Breakdown + Recent Proposals --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Status Breakdown --}}
            <div class="lg:col-span-1 rounded-2xl border border-slate-200 dark:border-zinc-800
                        bg-white dark:bg-zinc-900 p-4 shadow-sm">
                <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white mb-3">
                    Status Proposal
                </h3>

                @php
                    $total = array_sum($statusBreakdown);
                    $statuses = [
                        'pending'      => ['label' => 'Pending',      'color' => 'slate'],
                        'submitted'    => ['label' => 'Submitted',    'color' => 'blue'],
                        'under_review' => ['label' => 'Under Review', 'color' => 'violet'],
                        'accepted'     => ['label' => 'Accepted',     'color' => 'emerald'],
                        'rejected'     => ['label' => 'Rejected',     'color' => 'rose'],
                    ];
                @endphp

                <div class="space-y-2.5">
                    @foreach ($statuses as $key => $info)
                        @php
                            $count = $statusBreakdown[$key] ?? 0;
                            $pct = $total > 0 ? round(($count / $total) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[11px] font-medium text-slate-700 dark:text-zinc-300">
                                    {{ $info['label'] }}
                                </span>
                                <span class="text-[11px] font-semibold text-slate-900 dark:text-white">
                                    {{ $count }}
                                </span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-100 dark:bg-zinc-800 overflow-hidden">
                                <div class="h-full rounded-full bg-{{ $info['color'] }}-500 transition-all duration-500"
                                    style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Proposals --}}
            <div class="lg:col-span-2 rounded-2xl border border-slate-200 dark:border-zinc-800
                        bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-zinc-800 flex items-center justify-between">
                    <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white">
                        Proposal Terbaru
                    </h3>
                    <a href="{{ route('admin.internal.manage-dedications') ?? '#' }}"
                        class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                        Lihat semua →
                    </a>
                </div>

                <div class="divide-y divide-slate-200 dark:divide-zinc-800">
                    @forelse ($recentProposals as $proposal)
                        @php $meta = $proposal->statusMeta(); @endphp
                        <div class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/20 flex items-center justify-center shrink-0">
                                <flux:icon.document-text class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-900 dark:text-white line-clamp-1">
                                    {{ $proposal->title }}
                                </p>
                                <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                    {{ $proposal->author?->full_name ?? '—' }} · {{ $proposal->researchScheme?->code ?? '—' }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-semibold shrink-0 {{ $meta['class'] }}">
                                {{ $meta['label'] }}
                            </span>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center">
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                                Belum ada proposal terbaru
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
