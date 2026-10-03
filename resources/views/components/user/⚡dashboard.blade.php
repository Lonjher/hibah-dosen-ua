<?php

use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Period;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public function with(): array
    {
        $userId = auth()->id();

        // ── My proposals stats ──
        $myStats = [
            'total'      => Proposal::where('user_id', $userId)->count(),
            'research'   => Proposal::where('user_id', $userId)->where('is_research', true)->count(),
            'dedication' => Proposal::where('user_id', $userId)->where('is_research', false)->count(),
            'accepted'   => Proposal::where('user_id', $userId)->where('status', 'accepted')->count(),
            'pending'    => Proposal::where('user_id', $userId)->whereIn('status', ['pending', 'submitted', 'under_review'])->count(),
        ];

        // ── Active period ──
        $activePeriod = Period::where('is_active', true)->first();

        // ── Recent activity (proposal terbaru) ──
        $recentProposals = Proposal::with(['researchScheme', 'reviewer'])
            ->where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get();

        // ── Submissions needing action ──
        $needsAction = Proposal::with(['progressReport', 'finalReport', 'output'])
            ->where('user_id', $userId)
            ->where('status', 'accepted')
            ->get()
            ->filter(function ($p) {
                // Cek mana yang butuh di-upload
                if ($p->progressReport === null) return true;
                if ($p->progressReport->status === 'accepted' && $p->finalReport === null) return true;
                if ($p->finalReport?->status === 'accepted' && $p->output === null) return true;
                // Cek yang butuh revisi
                if ($p->progressReport?->status === 'revised') return true;
                if ($p->finalReport?->status === 'revised') return true;
                if ($p->output?->status === 'revised') return true;
                return false;
            })
            ->take(5);

        return [
            'myStats'         => $myStats,
            'activePeriod'    => $activePeriod,
            'recentProposals' => $recentProposals,
            'needsAction'     => $needsAction,
        ];
    }
};
?>

<div class="p-4 sm:p-6 space-y-6">

    <x-dashboard-header icon="home" title="Dashboard"
        leading="Selamat datang! Berikut ringkasan hibah Anda." />

    {{-- ══════════ ACTIVE PERIOD BANNER ══════════ --}}
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
                    Dibuka {{ $activePeriod->open_from?->format('d M Y') ?? '—' }}
                    s/d {{ $activePeriod->open_to?->format('d M Y') ?? '—' }}
                </p>
            </div>
            <a href="{{ route('user.internal.manage-researches') }}"
                class="hidden sm:inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                       bg-emerald-500 text-white text-[11px] font-semibold
                       hover:bg-emerald-600 transition-colors shrink-0">
                <flux:icon.plus class="size-3" />
                Ajukan Proposal
            </a>
        </div>
    @endif

    {{-- ══════════ STAT CARDS ══════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @php
            $cards = [
                ['label' => 'Total Proposal', 'value' => $myStats['total'],      'color' => 'slate',   'icon' => 'document-text'],
                ['label' => 'Penelitian',     'value' => $myStats['research'],   'color' => 'emerald', 'icon' => 'beaker'],
                ['label' => 'Pengabdian',     'value' => $myStats['dedication'], 'color' => 'rose',    'icon' => 'heart'],
                ['label' => 'Accepted',       'value' => $myStats['accepted'],   'color' => 'violet',  'icon' => 'check-circle'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="rounded-2xl border border-slate-200 dark:border-zinc-800
                        bg-white dark:bg-zinc-900 p-4 shadow-sm">
                <div class="w-9 h-9 rounded-xl bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30
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

    {{-- ══════════ NEEDS ACTION ══════════ --}}
    @if ($needsAction->isNotEmpty())
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800
                    bg-amber-50 dark:bg-amber-900/20 overflow-hidden">
            <div class="px-4 py-3 border-b border-amber-200 dark:border-amber-800 flex items-center gap-2">
                <flux:icon.exclamation-triangle class="size-4 text-amber-600 dark:text-amber-400" />
                <h3 class="font-heading text-sm font-semibold text-amber-900 dark:text-amber-100">
                    Butuh Tindakan Anda
                </h3>
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full
                             bg-amber-200 text-amber-800
                             dark:bg-amber-800 dark:text-amber-200">
                    {{ $needsAction->count() }}
                </span>
            </div>
            <div class="divide-y divide-amber-200 dark:divide-amber-800">
                @foreach ($needsAction as $proposal)
                    @php
                        // Tentukan aksi
                        $action = 'Upload';
                        $detail = '';
                        if ($proposal->progressReport === null) {
                            $detail = 'Upload Progress Report';
                        } elseif ($proposal->progressReport->status === 'revised') {
                            $detail = 'Revisi Progress Report';
                        } elseif ($proposal->progressReport->status === 'accepted' && $proposal->finalReport === null) {
                            $detail = 'Upload Final Report';
                        } elseif ($proposal->finalReport?->status === 'revised') {
                            $detail = 'Revisi Final Report';
                        } elseif ($proposal->finalReport?->status === 'accepted' && $proposal->output === null) {
                            $detail = 'Upload Output';
                        } elseif ($proposal->output?->status === 'revised') {
                            $detail = 'Revisi Output';
                        }
                    @endphp
                    <div class="px-4 py-3 flex items-center gap-3">
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
                            <p class="text-[10px] text-amber-700 dark:text-amber-300 mt-0.5">
                                {{ $detail }}
                            </p>
                        </div>
                        <button type="button" x-data
                            x-on:click="$dispatch('open-view-submission-user', { proposalId: {{ $proposal->id }} })"
                            class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-md
                                   text-[10px] font-semibold text-white
                                   bg-gradient-to-r from-amber-500 to-amber-400
                                   hover:from-amber-600 hover:to-amber-500">
                            <flux:icon.arrow-right class="size-3" />
                            Kerjakan
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══════════ 2-COL: RECENT + RESOURCES ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Recent Proposals --}}
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 dark:border-zinc-800
                    bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-zinc-800 flex items-center justify-between">
                <h3 class="font-heading text-sm font-semibold text-slate-900 dark:text-white">
                    Proposal Terbaru
                </h3>
                <a href="{{ route('user.internal.manage-researches') }}"
                    class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                    Lihat semua →
                </a>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-zinc-800">
                @forelse ($recentProposals as $proposal)
                    @php $meta = $proposal->statusMeta(); @endphp
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
                                {{ $proposal->researchScheme?->name ?? '—' }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-semibold shrink-0 {{ $meta['class'] }}">
                            {{ $meta['label'] }}
                        </span>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <flux:icon.document-text class="size-8 text-slate-300 dark:text-zinc-600 mx-auto mb-2" />
                        <p class="text-[11px] text-slate-500 dark:text-zinc-400 mb-3">
                            Belum ada proposal.
                        </p>
                        <a href="{{ route('user.internal.manage-researches') }}"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                                   bg-emerald-500 text-white text-[11px] font-semibold
                                   hover:bg-emerald-600 transition-colors">
                            <flux:icon.plus class="size-3" />
                            Buat Proposal Baru
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Resources / Info --}}
        <div class="space-y-4">
            <livewire:user.information-feed
                :limit="3"
                :refresh-interval="30"
                storage-key="dismissed_important_info"
                wire:key="info-all" />

            {{-- Download Resources --}}
            <livewire:user.download-list
                scope="dashboard"
                layout="grouped"
                :limit="6"
                wire:key="dl-side" />
        </div>
    </div>
</div>
