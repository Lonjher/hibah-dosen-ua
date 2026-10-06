<?php

use App\Models\Period;
use App\Models\Proposal;
use App\Models\ReviewerNote;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Review Proposals')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';
    public string $periodFilter = 'all';

    public function mount(): void
    {
        // Default: periode aktif → terbaru → all
        $active = Period::where('is_active', true)->first();

        if ($active) {
            $this->periodFilter = (string) $active->id;
        } else {
            $latest = Period::orderByDesc('open_from')->orderByDesc('id')->first();
            $this->periodFilter = $latest ? (string) $latest->id : 'all';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }
    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }
    public function updatingPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function isFinalized(Proposal $proposal): bool
    {
        return in_array($proposal->status, ['accepted', 'rejected']);
    }

    public function hasReviewerNotes(Proposal $proposal): bool
    {
        return ReviewerNote::where('noteable_id', $proposal->id)->where('noteable_type', 'proposal')->where('reviewer_id', Auth::id())->exists();
    }

    public function with(): array
    {
        $selectedPeriod = $this->periodFilter === 'all' ? null : Period::find((int) $this->periodFilter);

        $proposals = Proposal::query()
            ->with(['researchScheme', 'period', 'author', 'reviewer'])
            ->where('reviewer_id', Auth::id())
            ->when($selectedPeriod, fn($q) => $q->where('period_id', $selectedPeriod->id))
            ->when(
                $this->search,
                fn($q) => $q->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('keywords', 'like', "%{$this->search}%")
                        ->orWhereHas('author', fn($q) => $q->where('full_name', 'like', "%{$this->search}%"));
                }),
            )
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter === 'research', fn($q) => $q->where('is_research', true))
            ->when($this->typeFilter === 'dedication', fn($q) => $q->where('is_research', false))
            ->latest()
            ->paginate(10);

        return [
            'proposals' => $proposals,
            'periods' => Period::orderByDesc('open_from')->orderByDesc('id')->get(),
            'selectedPeriod' => $selectedPeriod,
        ];
    }
};
?>

<div class="p-3 sm:p-4 space-y-3">

    <x-dashboard-header icon="clipboard-document-check" title="Review Proposals"
        leading="Review research proposals assigned to you." />

    {{-- Table Card --}}
    <div
        class="bg-white dark:bg-zinc-900 rounded-2xl
        border border-slate-200 dark:border-zinc-800 shadow-sm overflow-hidden">

        {{-- ─────── TOOLBAR (RESPONSIVE) ─────── --}}
        <div
            class="px-2 sm:px-3 py-2
            border-b border-slate-200 dark:border-zinc-800
            bg-slate-50/50 dark:bg-zinc-900/50">

            <div class="flex flex-wrap items-center gap-1.5 min-w-0">

                {{-- ─────── STATS BADGE ─────── --}}
                @if ($proposals->total() > 0)
                    <div
                        class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                        bg-violet-50 dark:bg-violet-900/20
                        border border-violet-100 dark:border-violet-900/40">
                        <flux:icon.clipboard-document-check class="size-3 text-violet-600 dark:text-violet-400" />
                        <span class="text-[10.5px] font-semibold text-violet-700 dark:text-violet-300">
                            {{ $proposals->total() }}
                        </span>
                        <span class="text-[10px] text-violet-600/70 dark:text-violet-400/70 hidden sm:inline">
                            proposals
                        </span>
                    </div>
                @else
                    <div
                        class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                        bg-slate-50 dark:bg-zinc-800/40
                        border border-slate-100 dark:border-zinc-800">
                        <flux:icon.clipboard-document-check class="size-3 text-slate-500 dark:text-zinc-400" />
                        <span class="text-[10.5px] font-medium text-slate-500 dark:text-zinc-400">
                            No proposals
                        </span>
                    </div>
                @endif

                {{-- ─────── PERIOD FILTER ─────── --}}
                <div
                    class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                    bg-white dark:bg-zinc-900
                    border border-slate-200 dark:border-zinc-800
                    shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50">
                    <flux:icon.calendar-days class="size-3 text-slate-400 dark:text-zinc-500 shrink-0" />
                    <select wire:model.live="periodFilter"
                        class="text-[10.5px] font-medium bg-transparent border-0
                       text-slate-700 dark:text-zinc-200
                       focus:outline-none focus:ring-0 cursor-pointer
                       pr-4 pl-0 py-0 max-w-[90px] sm:max-w-none
                       [&>option]:text-slate-700 dark:[&>option]:text-zinc-200
                       [&>option]:bg-white dark:[&>option]:bg-zinc-900">
                        <option value="all">All Periods</option>
                        @foreach ($periods as $p)
                            <option value="{{ $p->id }}">{{ $p->periode }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ─────── LOADING SPINNER ─────── --}}
                <div wire:loading wire:target="periodFilter"
                    class="shrink-0 w-3.5 h-3.5 rounded-full
                   bg-violet-100 dark:bg-violet-900/40
                   flex items-center justify-center">
                    <svg class="animate-spin size-2.5 text-violet-600 dark:text-violet-400" viewBox="0 0 24 24"
                        fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            opacity=".25" />
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                    </svg>
                </div>

                {{-- ─────── TYPE FILTER ─────── --}}
                <div class="shrink-0">
                    <x-select wire:model.live="typeFilter" size="sm" color="violet" maxWidth="w-auto">
                        <option value="all">All Types</option>
                        <option value="research">Research</option>
                        <option value="dedication">Community Service</option>
                    </x-select>
                </div>

                {{-- ─────── STATUS FILTER ─────── --}}
                <div class="shrink-0">
                    <x-select wire:model.live="statusFilter" size="sm" color="violet" maxWidth="w-auto">
                        <option value="all">All Status</option>
                        <option value="under_review">Under Review</option>
                        <option value="revised">Revised</option>
                        <option value="accepted">Accepted</option>
                    </x-select>
                </div>

                {{-- ─────── SEARCH ─────── --}}
                <div
                    class="order-5 sm:order-4
                    w-full sm:w-auto
                    sm:ml-auto
                    md:w-44 lg:w-56
                    min-w-0">
                    <x-input-search name="search" id="search-reviewer" wire:model.live.debounce.300ms="search"
                        placeholder="Search title, author..." class="w-full !text-[10.5px]" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════ MOBILE VIEW (cards) ═══════════════════ --}}
        <div class="block md:hidden divide-y divide-slate-100 dark:divide-zinc-800/70">
            @forelse ($proposals as $proposal)
                @php
                    $meta = $proposal->statusMeta();
                    $hasNotes = $this->hasReviewerNotes($proposal);
                    $finalized = $this->isFinalized($proposal);
                @endphp
                <div wire:key="prop-m-{{ $proposal->id }}"
                    class="p-3 active:bg-slate-50 dark:active:bg-zinc-800/40 transition-colors">

                    {{-- Header: icon + title + status --}}
                    <div class="flex items-start gap-2.5">
                        <div
                            class="w-8 h-8 rounded-full shrink-0
                            {{ $proposal->is_research
                                ? 'bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/20'
                                : 'bg-gradient-to-br from-rose-100 to-pink-50 dark:from-rose-900/30 dark:to-pink-900/20' }}
                            flex items-center justify-center">
                            <flux:icon :name="$proposal->is_research ? 'beaker' : 'heart'"
                                class="size-4 {{ $proposal->is_research ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-[12px] font-semibold leading-snug
                                      text-slate-900 dark:text-white
                                      line-clamp-2"
                                    title="{{ $proposal->title }}">
                                    {{ $proposal->title }}
                                </p>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full
                                         text-[9.5px] font-semibold whitespace-nowrap shrink-0
                                         {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </div>

                            {{-- Type + Scheme --}}
                            <div class="mt-1 flex items-center gap-1.5 flex-wrap text-[10px]">
                                <span
                                    class="px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap
                                    {{ $proposal->is_research
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                        : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' }}">
                                    {{ $proposal->is_research ? 'Research' : 'Dedication' }}
                                </span>
                                <span class="text-slate-500 dark:text-zinc-500 truncate"
                                    title="{{ $proposal->researchScheme?->name }}">
                                    {{ $proposal->researchScheme?->name ?? '—' }}
                                </span>
                            </div>

                            {{-- Author + Period --}}
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    @if ($proposal->author)
                                        <div
                                            class="w-5 h-5 rounded-full shrink-0
                                            bg-violet-100 text-violet-700
                                            dark:bg-violet-900/40 dark:text-violet-300
                                            flex items-center justify-center
                                            text-[8.5px] font-bold">
                                            {{ strtoupper(substr($proposal->author->full_name, 0, 1)) }}
                                        </div>
                                        <p class="text-[10.5px] font-medium truncate
                                              text-slate-700 dark:text-zinc-300"
                                            title="{{ $proposal->author->full_name }}">
                                            {{ $proposal->author->full_name }}
                                        </p>
                                    @else
                                        <span class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic">—</span>
                                    @endif
                                </div>
                                <span
                                    class="inline-flex items-center gap-1 text-[9.5px] font-mono
                                         text-slate-500 dark:text-zinc-500 whitespace-nowrap shrink-0">
                                    <flux:icon.calendar-days class="size-2.5" />
                                    {{ $proposal->period?->periode ?? '—' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Action --}}
                    <div class="mt-2.5 flex justify-end">
                        <flux:button size="xs" variant="primary" icon="eye" class="!text-[10.5px]" x-data
                            x-on:click="$dispatch('open-view-proposal', { id: {{ $proposal->id }} })">
                            View & Review
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="px-4 py-10">
                    <div class="flex flex-col items-center gap-2 text-center">
                        <div
                            class="w-11 h-11 rounded-full bg-slate-100 dark:bg-zinc-800
                            flex items-center justify-center">
                            <flux:icon.clipboard-document-check class="size-5 text-slate-400 dark:text-zinc-600" />
                        </div>
                        <div>
                            <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                No proposals assigned to you
                            </p>
                            <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                @if ($search || $statusFilter !== 'all' || $typeFilter !== 'all' || ($periodFilter ?? 'all') !== 'all')
                                    No results match your filters.
                                @else
                                    You'll see proposals here once the admin assigns them.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- ═══════════════════ DESKTOP VIEW (table) ═══════════════════ --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[720px] lg:min-w-[820px]">
                <thead>
                    <tr
                        class="bg-slate-50/80 dark:bg-zinc-900/50
                       border-b border-slate-200 dark:border-zinc-800">
                        <th
                            class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                            Title & Scheme
                        </th>
                        <th
                            class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                            Author
                        </th>
                        <th
                            class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                            Period
                        </th>
                        <th
                            class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                            Status
                        </th>
                        <th
                            class="px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                    @forelse ($proposals as $proposal)
                        @php
                            $meta = $proposal->statusMeta();
                            $hasNotes = $this->hasReviewerNotes($proposal);
                            $finalized = $this->isFinalized($proposal);
                        @endphp
                        <tr wire:key="prop-{{ $proposal->id }}"
                            class="group transition-colors duration-150
                           hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                            {{-- Title & Scheme --}}
                            <td class="px-3 py-2 max-w-md">
                                <div class="flex items-start gap-2">
                                    <div
                                        class="w-7 h-7 rounded-full shrink-0
                                        {{ $proposal->is_research
                                            ? 'bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/20'
                                            : 'bg-gradient-to-br from-rose-100 to-pink-50 dark:from-rose-900/30 dark:to-pink-900/20' }}
                                        flex items-center justify-center
                                        group-hover:scale-105 transition-transform">
                                        <flux:icon :name="$proposal->is_research ? 'beaker' : 'heart'"
                                            class="size-3.5 {{ $proposal->is_research ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11.5px] font-semibold leading-tight truncate
                                          text-slate-900 dark:text-white max-w-[260px]"
                                            title="{{ $proposal->title }}">
                                            {{ Str::limit($proposal->title, 55) }}
                                        </p>
                                        <div class="mt-0.5 flex items-center gap-1.5 flex-wrap text-[9.5px]">
                                            <span
                                                class="px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap
                                                {{ $proposal->is_research
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' }}">
                                                {{ $proposal->is_research ? 'Research' : 'Dedication' }}
                                            </span>
                                            <span class="text-slate-500 dark:text-zinc-500 truncate max-w-[180px]">
                                                {{ $proposal->researchScheme?->name ?? '—' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Author --}}
                            <td class="px-3 py-2">
                                @if ($proposal->author)
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div
                                            class="w-6 h-6 rounded-full shrink-0
                                            bg-violet-100 text-violet-700
                                            dark:bg-violet-900/40 dark:text-violet-300
                                            flex items-center justify-center
                                            text-[9px] font-bold">
                                            {{ strtoupper(substr($proposal->author->full_name, 0, 1)) }}
                                        </div>
                                        <p class="text-[10.5px] font-medium truncate
                                          text-slate-700 dark:text-zinc-300 max-w-[140px]"
                                            title="{{ $proposal->author->full_name }}">
                                            {{ $proposal->author->full_name }}
                                        </p>
                                    </div>
                                @else
                                    <span class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic">—</span>
                                @endif
                            </td>

                            {{-- Period --}}
                            <td class="px-3 py-2">
                                <span class="text-[10.5px] text-slate-600 dark:text-zinc-400 whitespace-nowrap">
                                    {{ $proposal->period?->periode ?? '—' }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-3 py-2">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full
                                     text-[9.5px] font-semibold whitespace-nowrap
                                     {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>

                            {{-- Action --}}
                            <td class="px-3 py-2 text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                        class="!p-1 rounded-full text-slate-400
                                       hover:bg-slate-100 hover:text-slate-600
                                       dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300
                                       hover:scale-110 active:scale-95
                                       opacity-60 group-hover:opacity-100 transition-all duration-150" />

                                    <flux:menu class="!text-[11px]">
                                        <flux:menu.item icon="eye" x-data
                                            x-on:click="$dispatch('open-view-proposal', { id: {{ $proposal->id }} })">
                                            View & Review
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10">
                                <div class="flex flex-col items-center gap-2 text-center">
                                    <div
                                        class="w-11 h-11 rounded-full bg-slate-100 dark:bg-zinc-800
                                        flex items-center justify-center">
                                        <flux:icon.clipboard-document-check
                                            class="size-5 text-slate-400 dark:text-zinc-600" />
                                    </div>
                                    <div>
                                        <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                            No proposals assigned to you
                                        </p>
                                        <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                            @if ($search || $statusFilter !== 'all' || $typeFilter !== 'all' || ($periodFilter ?? 'all') !== 'all')
                                                No results match your filters.
                                            @else
                                                You'll see proposals here once the admin assigns them.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ─────── PAGINATION ─────── --}}
        @if ($proposals->hasPages())
            <div
                class="px-2 sm:px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                bg-slate-50/50 dark:bg-zinc-900/50
                overflow-x-auto
                [scrollbar-width:thin]
                [&::-webkit-scrollbar]:h-1
                [&::-webkit-scrollbar-thumb]:bg-slate-300
                [&::-webkit-scrollbar-thumb]:rounded-full
                dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">
                {{ $proposals->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>

    {{-- Modal --}}
    <livewire:reviewers.modals.view-proposal />
</div>
