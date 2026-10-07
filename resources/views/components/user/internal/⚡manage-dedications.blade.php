<?php

use App\Models\Proposal;
use App\Models\Period;
use App\Models\ProposalMember;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Community Service Proposals')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $periodFilter = 'all';
    public string $roleFilter = 'all'; // all | leader | member
    public bool $isResearch = false;

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

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingPeriodFilter(): void { $this->resetPage(); }
    public function updatingRoleFilter(): void { $this->resetPage(); }

    /* ============================================================
     |  ROLE HELPERS
     ============================================================ */

    public function isLeader(Proposal $proposal): bool
    {
        return (int) $proposal->user_id === (int) auth()->id();
    }

    public function isMember(Proposal $proposal): bool
    {
        return ! $this->isLeader($proposal);
    }

    public function roleMeta(Proposal $proposal): array
    {
        return $this->isLeader($proposal)
            ? ['label' => 'Ketua', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300']
            : ['label' => 'Anggota', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300'];
    }

    /* ============================================================
     |  DELETE FLOW
     ============================================================ */

    public function confirmDelete(int $id): void
    {
        $proposal = $this->baseQuery()->find($id);

        if (! $proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        if (! $this->isLeader($proposal)) {
            Flux::toast('Hanya ketua yang dapat menghapus proposal.', variant: 'danger');
            return;
        }

        if (! $this->canDelete($proposal->status)) {
            Flux::toast('This proposal cannot be deleted.', variant: 'danger');
            return;
        }

        $this->dispatch('confirm-delete',
            title: 'Delete Proposal?',
            message: 'You are about to delete:',
            subject: $proposal->title,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
            cancelLabel: 'Cancel',
            action: 'deleteProposal',
            payload: ['id' => $proposal->id],
        );
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deleteProposal') {
            $this->deleteProposal($payload['id'] ?? null);
        }
    }

    public function deleteProposal(?int $id): void
    {
        if (! $id) {
            Flux::toast('Invalid proposal ID.', variant: 'danger');
            return;
        }

        $proposal = $this->baseQuery()->find($id);

        if (! $proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        if (! $this->isLeader($proposal)) {
            Flux::toast('Hanya ketua yang dapat menghapus proposal.', variant: 'danger');
            return;
        }

        if (! $this->canDelete($proposal->status)) {
            Flux::toast('This proposal cannot be deleted.', variant: 'danger');
            return;
        }

        try {
            $title = $proposal->title;
            $proposal->delete();

            Flux::toast("Proposal \"{$title}\" deleted.", variant: 'success');
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to delete proposal.', variant: 'danger');
        }
    }

    /* ============================================================
     |  PERMISSIONS
     ============================================================ */

    public function canEdit(Proposal $proposal): bool
    {
        return $this->isLeader($proposal)
            && in_array($proposal->status, ['pending', 'revised'], true);
    }

    public function canDelete(string $status): bool
    {
        return in_array($status, ['pending', 'revised'], true);
    }

    public function canAddProgressReport(Proposal $p): bool
    {
        return $this->isLeader($p)
            && $p->status === 'accepted'
            && ! $p->progressReport;
    }

    public function canAddFinalReport(Proposal $p): bool
    {
        return $this->isLeader($p)
            && $p->progressReport?->status === 'accepted'
            && ! $p->finalReport;
    }

    public function canAddoutcome(Proposal $p): bool
    {
        return $this->isLeader($p)
            && $p->finalReport?->status === 'accepted'
            && ! $p->outcome;
    }

    /* ============================================================
     |  BASE QUERY — user sebagai ketua ATAU anggota
     ============================================================ */

    protected function baseQuery()
    {
        $userId = auth()->id();

        return Proposal::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('proposalMembers', fn ($q) => $q->where('user_id', $userId));
            })
            ->where('is_research', $this->isResearch);
    }

    /* ============================================================
     |  PERIOD HELPERS
     ============================================================ */

    public function activeOpenPeriod(): ?Period
    {
        return Period::query()
            ->where('is_active', true)
            ->whereDate('open_from', '<=', now())
            ->whereDate('open_to', '>=', now())
            ->first();
    }

    public function activePeriod(): ?Period
    {
        return Period::query()->where('is_active', true)->first();
    }

    public function canCreateProposal(): bool
    {
        return $this->activeOpenPeriod() !== null;
    }

    /* ============================================================
     |  QUERY
     ============================================================ */

    public function with(): array
    {
        $userId = auth()->id();
        $selectedPeriod = $this->periodFilter === 'all' ? null : Period::find((int) $this->periodFilter);

        $proposals = $this->baseQuery()
            ->with([
                'researchScheme',
                'period',
                'reviewer',
                'progressReport',
                'finalReport',
                'outcome',
                'adminNotes',
                'reviewerNotes',
                'proposalMembers',
            ])
            ->when($selectedPeriod, fn ($q) => $q->where('period_id', $selectedPeriod->id))
            ->when(
                $this->search,
                fn ($q) => $q->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                      ->orWhere('keywords', 'like', "%{$this->search}%");
                }),
            )
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            // ── Filter by role ──
            ->when($this->roleFilter === 'leader', fn ($q) => $q->where('user_id', $userId))
            ->when($this->roleFilter === 'member', fn ($q) => $q
                ->where('user_id', '!=', $userId)
                ->whereHas('proposalMembers', fn ($q) => $q->where('user_id', $userId)))
            ->latest()
            ->paginate(10);

        $theme = $this->isResearch
            ? ['icon' => 'beaker', 'color' => 'emerald', 'label' => 'Research']
            : ['icon' => 'heart', 'color' => 'rose', 'label' => 'Community Service'];

        return [
            'proposals'     => $proposals,
            'canCreate'     => $this->canCreateProposal(),
            'openPeriod'    => $this->activeOpenPeriod(),
            'activePeriod'  => $this->activePeriod(),
            'periods'       => Period::orderByDesc('open_from')->orderByDesc('id')->get(),
            'selectedPeriod'=> $selectedPeriod,
            'theme'         => $theme,
        ];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- ══════════ HEADER ══════════ --}}
        <x-dashboard-header :icon="$theme['icon']" title="Research Proposals"
            leading="Manage your research proposals (as leader or member)." />

        {{-- ══════════ PERIOD INFO BANNER ══════════ --}}
        @if ($activePeriod)
            @php
                $isOpen = $canCreate;
                $bannerColor = $isOpen ? 'emerald' : 'amber';
            @endphp

            <div class="rounded-full border border-{{ $bannerColor }}-200 dark:border-{{ $bannerColor }}-800/60
                        bg-{{ $bannerColor }}-50/60 dark:bg-{{ $bannerColor }}-900/15
                        px-3 py-2 flex items-center gap-2.5">

                <div class="w-7 h-7 rounded-md
                            bg-{{ $bannerColor }}-100 dark:bg-{{ $bannerColor }}-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon :name="$isOpen ? 'calendar-days' : 'lock-closed'"
                        class="size-3.5 text-{{ $bannerColor }}-600 dark:text-{{ $bannerColor }}-400" />
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <p class="text-[10px] font-semibold uppercase tracking-wider
                                  text-{{ $bannerColor }}-700 dark:text-{{ $bannerColor }}-300">
                            {{ $isOpen ? 'Registration Open' : 'Registration Closed' }}
                        </p>
                        <span class="text-[10px] font-semibold text-slate-900 dark:text-white">
                            · {{ $activePeriod->periode }}
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5 truncate">
                        @if ($isOpen)
                            Opens until
                            <span class="font-semibold text-slate-700 dark:text-zinc-200">
                                {{ $activePeriod->open_to?->format('d M Y') ?? '—' }}
                            </span>
                        @elseif ($activePeriod->open_from && $activePeriod->open_from->isFuture())
                            Will open on
                            <span class="font-semibold text-slate-700 dark:text-zinc-200">
                                {{ $activePeriod->open_from->format('d M Y') }}
                            </span>
                        @elseif ($activePeriod->open_to && $activePeriod->open_to->isPast())
                            Closed on
                            <span class="font-semibold text-slate-700 dark:text-zinc-200">
                                {{ $activePeriod->open_to->format('d M Y') }}
                            </span>
                        @endif
                    </p>
                </div>

                @if ($isOpen)
                    <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                 bg-{{ $bannerColor }}-500 text-white text-[9.5px] font-semibold shrink-0">
                        <span class="w-1 h-1 rounded-full bg-white animate-pulse"></span>
                        Open
                    </span>
                @endif
            </div>
        @else
            <div class="rounded-lg border border-rose-200 dark:border-rose-800/60
                        bg-rose-50/60 dark:bg-rose-900/15
                        px-3 py-2 flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-md bg-rose-100 dark:bg-rose-900/40
                            flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="size-3.5 text-rose-600 dark:text-rose-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wider
                              text-rose-700 dark:text-rose-300">
                        No Active Period
                    </p>
                    <p class="text-[10px] text-rose-600 dark:text-rose-400 mt-0.5">
                        You cannot add proposals right now. Contact admin.
                    </p>
                </div>
            </div>
        @endif

        {{-- ══════════ MAIN CARD ══════════ --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl
                    shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50
                    border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- ─────── TOOLBAR ─────── --}}
            <div class="px-2 sm:px-3 py-2
                        border-b border-slate-200 dark:border-zinc-800
                        bg-slate-50/50 dark:bg-zinc-900/50">
                <div class="flex flex-wrap items-center gap-1.5 min-w-0">

                    {{-- STAT PILL --}}
                    <div class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                bg-{{ $theme['color'] }}-50 dark:bg-{{ $theme['color'] }}-900/20
                                border border-{{ $theme['color'] }}-100 dark:border-{{ $theme['color'] }}-900/40">
                        <flux:icon :name="$theme['icon']"
                            class="size-3 text-{{ $theme['color'] }}-600 dark:text-{{ $theme['color'] }}-400" />
                        <span class="text-[10.5px] font-semibold
                                     text-{{ $theme['color'] }}-700 dark:text-{{ $theme['color'] }}-300">
                            {{ $proposals->total() }}
                        </span>
                        <span class="text-[10px] text-{{ $theme['color'] }}-600/70 dark:text-{{ $theme['color'] }}-400/70 hidden sm:inline">
                            proposals
                        </span>
                    </div>

                    {{-- ROLE FILTER ── NEW --}}
                    <div class="shrink-0">
                        <x-select wire:model.live="roleFilter" size="sm" color="{{ $theme['color'] }}" maxWidth="w-auto">
                            <option value="all">All Roles</option>
                            <option value="leader">Ketua</option>
                            <option value="member">Anggota</option>
                        </x-select>
                    </div>

                    {{-- PERIOD FILTER --}}
                    <div class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
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

                    {{-- NEW BUTTON --}}
                    <div class="order-4 sm:order-5 ml-auto sm:ml-0 shrink-0">
                        @if ($canCreate)
                            <flux:button variant="primary" x-data x-on:click="$dispatch('open-add-proposal')"
                                class="!text-[10.5px]">
                                New
                            </flux:button>
                        @else
                            <div class="relative group">
                                <flux:button icon="plus" disabled variant="primary" size="xs"
                                    class="!text-[10.5px] cursor-not-allowed opacity-50">
                                    New
                                </flux:button>
                            </div>
                        @endif
                    </div>

                    {{-- SEARCH --}}
                    <div class="order-5 sm:order-4 w-full sm:w-auto sm:ml-auto
                                md:w-44 lg:w-56 min-w-0">
                        <x-input-search name="search" id="search-proposal"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search proposals..." class="w-full !text-[10.5px]" />
                    </div>
                </div>
            </div>

            {{-- ─────── TABLE ─────── --}}
            <div class="relative">
                <div x-data="{ showHint: true }" x-init="setTimeout(() => showHint = false, 3500)" x-show="showHint"
                    x-transition:leave="transition ease-in duration-500"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                           z-20 md:hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                           bg-slate-900/85 dark:bg-zinc-700/90 backdrop-blur-sm
                           text-white text-[10.5px] font-medium shadow-lg">
                    <flux:icon.arrows-right-left class="size-3" />
                    Swipe to see more
                </div>

                <div class="overflow-x-auto overscroll-x-contain scroll-smooth
                            [scrollbar-width:thin]
                            [&::-webkit-scrollbar]:h-1.5
                            [&::-webkit-scrollbar-thumb]:bg-slate-300
                            [&::-webkit-scrollbar-thumb]:rounded-full
                            dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">
                    <table class="w-full min-w-[620px] sm:min-w-[700px]">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-zinc-900/50
                                       border-b border-slate-200 dark:border-zinc-800">
                                <th class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Title & Scheme
                                </th>
                                <th class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    My Role
                                </th>
                                <th class="hidden md:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Period
                                </th>
                                <th class="hidden md:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Leader
                                </th>
                                <th class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Status
                                </th>
                                <th class="px-2 sm:px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                            @forelse ($proposals as $proposal)
                                @php
                                    $meta = $proposal->statusMeta();
                                    $role = $this->roleMeta($proposal);
                                    $isLeader = $this->isLeader($proposal);
                                @endphp

                                <tr wire:key="proposal-{{ $proposal->id }}"
                                    class="group transition-colors duration-150
                                           hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                    {{-- TITLE & SCHEME --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <div class="flex items-start gap-2">
                                            <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full shrink-0
                                                        bg-gradient-to-br
                                                        from-{{ $theme['color'] }}-100 to-{{ $theme['color'] }}-50
                                                        dark:from-{{ $theme['color'] }}-900/30 dark:to-{{ $theme['color'] }}-900/20
                                                        flex items-center justify-center
                                                        ring-1 ring-white/40 dark:ring-zinc-800/40
                                                        group-hover:scale-105 transition-transform">
                                                <flux:icon :name="$theme['icon']"
                                                    class="size-3 sm:size-3.5 text-{{ $theme['color'] }}-600 dark:text-{{ $theme['color'] }}-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] sm:text-[11.5px] font-semibold leading-tight truncate
                                                          text-slate-900 dark:text-white max-w-[200px] sm:max-w-[280px]"
                                                    title="{{ $proposal->title }}">
                                                    {{ Str::limit($proposal->title, 42) }}
                                                </p>
                                                <p class="mt-0.5 text-[9.5px] truncate text-slate-500 dark:text-zinc-500">
                                                    {{ $proposal->researchScheme?->name ?? '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- MY ROLE ── NEW --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                                     text-[9.5px] font-semibold whitespace-nowrap
                                                     {{ $role['class'] }}">
                                            <flux:icon :name="$isLeader ? 'star' : 'user-group'" class="size-2.5" />
                                            {{ $role['label'] }}
                                        </span>
                                    </td>

                                    {{-- PERIOD --}}
                                    <td class="hidden md:table-cell px-2 sm:px-3 py-2">
                                        <span class="text-[10.5px] text-slate-600 dark:text-zinc-400 whitespace-nowrap">
                                            {{ $proposal->period?->periode ?? '—' }}
                                        </span>
                                    </td>

                                    {{-- LEADER (author) --}}
                                    <td class="hidden md:table-cell px-2 sm:px-3 py-2">
                                        @if ($proposal->author)
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div class="w-6 h-6 rounded-full shrink-0
                                                            bg-emerald-100 text-emerald-700
                                                            dark:bg-emerald-900/40 dark:text-emerald-300
                                                            flex items-center justify-center
                                                            text-[9px] font-bold">
                                                    {{ strtoupper(substr($proposal->author->full_name, 0, 1)) }}
                                                </div>
                                                <span class="text-[10.5px] text-slate-700 dark:text-zinc-300 truncate max-w-[140px]">
                                                    {{ $proposal->author->full_name }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic">—</span>
                                        @endif
                                    </td>

                                    {{-- STATUS --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-full
                                                     text-[9.5px] font-semibold whitespace-nowrap
                                                     {{ $meta['class'] }}">
                                            {{ $meta['label'] }}
                                        </span>
                                    </td>

                                    {{-- ACTIONS --}}
                                    <td class="px-2 sm:px-3 py-2 text-right">
                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                                class="!p-1 rounded-full text-slate-400
                                                       hover:bg-slate-100 hover:text-slate-600
                                                       dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300
                                                       hover:scale-110 active:scale-95
                                                       opacity-60 group-hover:opacity-100 transition-all duration-150" />

                                            <flux:menu class="!text-[11px]">
                                                @if ($this->canEdit($proposal))
                                                    <flux:menu.item icon="pencil-square" x-data
                                                        x-on:click="$dispatch('open-edit-proposal', { id: {{ $proposal->id }} })">
                                                        Edit Proposal
                                                    </flux:menu.item>
                                                @endif

                                                <flux:menu.item icon="document-duplicate" x-data
                                                    x-on:click="$dispatch('open-view-submission-user', { proposalId: {{ $proposal->id }} })">
                                                    View Submissions
                                                </flux:menu.item>

                                                @if ($proposal->adminNotes->count() > 0)
                                                    <flux:menu.item icon="chat-bubble-left-right" x-data
                                                        x-on:click="$dispatch('open-user-view-admin-notes', { id: {{ $proposal->id }}, type: 'proposal' })"
                                                        class="text-amber-600 dark:text-amber-400">
                                                        Admin Notes
                                                        <span class="ml-auto text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                                     bg-amber-100 text-amber-700
                                                                     dark:bg-amber-900/40 dark:text-amber-300">
                                                            {{ $proposal->adminNotes->count() }}
                                                        </span>
                                                    </flux:menu.item>
                                                @endif

                                                @if ($proposal->reviewerNotes->count() > 0)
                                                    <flux:menu.item icon="clipboard-document-check" x-data
                                                        x-on:click="$dispatch('open-user-view-reviewer-notes', { id: {{ $proposal->id }}, type: 'proposal' })"
                                                        class="text-violet-600 dark:text-violet-400">
                                                        Reviewer Notes
                                                        <span class="ml-auto text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                                     bg-violet-100 text-violet-700
                                                                     dark:bg-violet-900/40 dark:text-violet-300">
                                                            {{ $proposal->reviewerNotes->count() }}
                                                        </span>
                                                    </flux:menu.item>
                                                @endif

                                                @if ($isLeader && $this->canDelete($proposal->status))
                                                    <flux:menu.separator />
                                                    <flux:menu.item variant="danger" icon="trash"
                                                        wire:click="confirmDelete({{ $proposal->id }})">
                                                        Delete Proposal
                                                    </flux:menu.item>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10">
                                        <div class="flex flex-col items-center gap-2 text-center">
                                            <div class="w-11 h-11 rounded-full bg-slate-100 dark:bg-zinc-800
                                                        flex items-center justify-center">
                                                <flux:icon :name="$theme['icon']"
                                                    class="size-5 text-slate-400 dark:text-zinc-600" />
                                            </div>
                                            <div>
                                                <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                    No proposals yet
                                                </p>
                                                <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                    @if ($search || $statusFilter !== 'all' || $periodFilter !== 'all' || $roleFilter !== 'all')
                                                        No results match your filters.
                                                    @else
                                                        Click "New" to create your first proposal.
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
            </div>

            {{-- PAGINATION --}}
            @if ($proposals->hasPages())
                <div class="px-2 sm:px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                            bg-slate-50/50 dark:bg-zinc-900/50
                            overflow-x-auto">
                    {{ $proposals->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>

    {{-- MODALS --}}
    <x-confirm-delete />
    <livewire:user.internal.proposals.add-proposal :is-research="false" wire:key="add-proposal-dedication" />
    <livewire:user.internal.proposals.edit-proposal wire:key="edit-proposal-dedication" />
    <livewire:user.internal.proposals.add-progress-report wire:key="add-progress-dedication" />
    <livewire:user.internal.proposals.edit-progress-report wire:key="edit-progress-dedication" />
    <livewire:user.internal.proposals.add-final-report wire:key="add-final-dedication" />
    <livewire:user.internal.proposals.edit-final-report wire:key="edit-final-dedication" />
    <livewire:user.internal.proposals.add-outcome wire:key="add-outcome-dedication" />
    <livewire:user.internal.proposals.edit-outcome wire:key="edit-outcome-dedication" />
    <livewire:user.internal.modals.view-admin-notes />
    <livewire:user.internal.modals.view-reviewer-notes />
    <livewire:user.internal.modals.view-submission />
</div>
