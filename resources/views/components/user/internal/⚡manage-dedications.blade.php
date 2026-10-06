<?php

use App\Models\Proposal;
use App\Models\Period;
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
    public bool $isResearch = false;

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
    public function updatingPeriodFilter(): void
    {
        $this->resetPage();
    }

    /* ============================================================
     |  DELETE FLOW
     ============================================================ */

    public function confirmDelete(int $id): void
    {
        $proposal = Proposal::where('user_id', auth()->id())->find($id);

        if (!$proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        if (!$this->canDelete($proposal->status)) {
            Flux::toast('This proposal cannot be deleted.', variant: 'danger');
            return;
        }

        $this->dispatch('confirm-delete', title: 'Delete Proposal?', message: 'You are about to delete:', subject: $proposal->title, note: 'This action cannot be undone.', confirmLabel: 'Delete', cancelLabel: 'Cancel', action: 'deleteProposal', payload: ['id' => $proposal->id]);
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
        if (!$id) {
            Flux::toast('Invalid proposal ID.', variant: 'danger');
            return;
        }

        $proposal = Proposal::where('user_id', auth()->id())->find($id);

        if (!$proposal) {
            Flux::toast('Proposal not found or not yours.', variant: 'danger');
            return;
        }

        if (!$this->canDelete($proposal->status)) {
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

    public function canEdit(string $status): bool
    {
        return in_array($status, ['pending', 'revised'], true);
    }

    public function canDelete(string $status): bool
    {
        return in_array($status, ['pending', 'revised'], true);
    }

    public function canAddProgressReport(Proposal $p): bool
    {
        return $p->status === 'accepted' && !$p->progressReport;
    }

    public function canAddFinalReport(Proposal $p): bool
    {
        return $p->progressReport?->status === 'accepted' && !$p->finalReport;
    }

    public function canAddOutput(Proposal $p): bool
    {
        return $p->finalReport?->status === 'accepted' && !$p->output;
    }

    /* ============================================================
     |  PERIOD HELPERS
     ============================================================ */

    public function activeOpenPeriod(): ?Period
    {
        return Period::query()->where('is_active', true)->whereDate('open_from', '<=', now())->whereDate('open_to', '>=', now())->first();
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

        $proposals = Proposal::query()
            ->with(['researchScheme', 'period', 'reviewer', 'progressReport', 'finalReport', 'output', 'adminNotes', 'reviewerNotes'])
            ->where('user_id', $userId)
            ->where('is_research', false)
            ->when($selectedPeriod, fn($q) => $q->where('period_id', $selectedPeriod->id))
            ->when(
                $this->search,
                fn($q) => $q->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")->orWhere('keywords', 'like', "%{$this->search}%");
                }),
            )
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        return [
            'proposals' => $proposals,
            'canCreate' => $this->canCreateProposal(),
            'openPeriod' => $this->activeOpenPeriod(),
            'activePeriod' => $this->activePeriod(),
            'periods' => Period::orderByDesc('open_from')->orderByDesc('id')->get(),
            'selectedPeriod' => $selectedPeriod,
            'theme' => [
                'icon' => 'heart',
                'color' => 'rose',
                'label' => 'Community Service',
            ],
        ];
    }
};
?>

<div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- ══════════ HEADER ══════════ --}}
        <x-dashboard-header icon="heart" title="Community Service Proposals"
            leading="Manage your community service proposals." />

        {{-- ══════════ MAIN CARD ══════════ --}}
        <div
            class="bg-white dark:bg-zinc-900 rounded-2xl
            shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50
            border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- ─────── TOOLBAR (RESPONSIVE) ─────── --}}
            <div
                class="px-2 sm:px-3 py-2
                border-b border-slate-200 dark:border-zinc-800
                bg-slate-50/50 dark:bg-zinc-900/50">

                {{-- Flex-wrap: mobile 2 baris, desktop 1 baris --}}
                <div class="flex flex-wrap items-center gap-1.5 min-w-0">

                    {{-- ─────── STAT PILL ─────── --}}
                    <div
                        class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                        bg-{{ $theme['color'] }}-50 dark:bg-{{ $theme['color'] }}-900/20
                        border border-{{ $theme['color'] }}-100 dark:border-{{ $theme['color'] }}-900/40">
                        <flux:icon :name="$theme['icon']"
                            class="size-3 text-{{ $theme['color'] }}-600 dark:text-{{ $theme['color'] }}-400" />
                        <span
                            class="text-[10.5px] font-semibold
                             text-{{ $theme['color'] }}-700 dark:text-{{ $theme['color'] }}-300">
                            {{ $proposals->total() }}
                        </span>
                        <span
                            class="text-[10px] text-{{ $theme['color'] }}-600/70 dark:text-{{ $theme['color'] }}-400/70
                             hidden sm:inline">
                            proposals
                        </span>
                    </div>

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
                       bg-{{ $theme['color'] }}-100 dark:bg-{{ $theme['color'] }}-900/40
                       flex items-center justify-center">
                        <svg class="animate-spin size-2.5 text-{{ $theme['color'] }}-600 dark:text-{{ $theme['color'] }}-400"
                            viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                opacity=".25" />
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                        </svg>
                    </div>

                    {{-- ─────── NEW BUTTON (mobile: kanan, desktop: paling kanan) ─────── --}}
                    <div class="order-4 sm:order-5 ml-auto sm:ml-0 shrink-0">
                        <flux:button variant="primary" x-data x-on:click="$dispatch('open-add-proposal')"
                            class="!text-[10.5px]">
                            New
                        </flux:button>
                    </div>

                    {{-- ─────── SEARCH (mobile: baris 2, desktop: sebelum New) ─────── --}}
                    <div
                        class="order-5 sm:order-4
                        w-full sm:w-auto
                        sm:ml-auto
                        md:w-44 lg:w-56
                        min-w-0">
                        <x-input-search name="search" id="search-dedication" wire:model.live.debounce.300ms="search"
                            placeholder="Search proposals..." class="w-full !text-[10.5px]" />
                    </div>
                </div>
            </div>

            {{-- ─────── TABLE ─────── --}}
            <div class="relative">
                {{-- Swipe hint --}}
                <div x-data="{ showHint: true }" x-init="setTimeout(() => showHint = false, 3500)" x-show="showHint"
                    x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                   z-20 md:hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                   bg-slate-900/85 dark:bg-zinc-700/90 backdrop-blur-sm
                   text-white text-[10.5px] font-medium shadow-lg">
                    <flux:icon.arrows-right-left class="size-3" />
                    Swipe to see more
                </div>

                <div
                    class="overflow-x-auto overscroll-x-contain scroll-smooth
                    [scrollbar-width:thin]
                    [&::-webkit-scrollbar]:h-1.5
                    [&::-webkit-scrollbar-thumb]:bg-slate-300
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">
                    <table class="w-full min-w-[560px] sm:min-w-[640px]">
                        <thead>
                            <tr
                                class="bg-slate-50/80 dark:bg-zinc-900/50
                               border-b border-slate-200 dark:border-zinc-800">
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Title & Scheme
                                </th>
                                <th
                                    class="hidden md:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Period
                                </th>
                                <th
                                    class="hidden md:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Reviewer
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Status
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                            @forelse ($proposals as $proposal)
                                @php $meta = $proposal->statusMeta(); @endphp

                                <tr wire:key="dedication-{{ $proposal->id }}"
                                    class="group transition-colors duration-150
                                   hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                    {{-- TITLE & SCHEME --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <div class="flex items-start gap-2">
                                            <div
                                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-full shrink-0
                                                bg-gradient-to-br from-rose-100 to-pink-50
                                                dark:from-rose-900/30 dark:to-pink-900/20
                                                flex items-center justify-center
                                                ring-1 ring-white/40 dark:ring-zinc-800/40
                                                group-hover:scale-105 transition-transform">
                                                <flux:icon.heart
                                                    class="size-3 sm:size-3.5 text-rose-600 dark:text-rose-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] sm:text-[11.5px] font-semibold leading-tight truncate
                                                  text-slate-900 dark:text-white max-w-[200px] sm:max-w-[280px]"
                                                    title="{{ $proposal->title }}">
                                                    {{ Str::limit($proposal->title, 42) }}
                                                </p>
                                                <p
                                                    class="mt-0.5 text-[9.5px] truncate
                                                  text-slate-500 dark:text-zinc-500">
                                                    {{ $proposal->researchScheme?->name ?? '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- PERIOD --}}
                                    <td class="hidden md:table-cell px-2 sm:px-3 py-2">
                                        <span class="text-[10.5px] text-slate-600 dark:text-zinc-400 whitespace-nowrap">
                                            {{ $proposal->period?->periode ?? '—' }}
                                        </span>
                                    </td>

                                    {{-- REVIEWER --}}
                                    <td class="hidden md:table-cell px-2 sm:px-3 py-2">
                                        @if ($proposal->reviewer)
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div
                                                    class="w-6 h-6 rounded-full shrink-0
                                                    bg-violet-100 text-violet-700
                                                    dark:bg-violet-900/40 dark:text-violet-300
                                                    flex items-center justify-center
                                                    text-[9px] font-bold">
                                                    {{ strtoupper(substr($proposal->reviewer->full_name, 0, 1)) }}
                                                </div>
                                                <span
                                                    class="text-[10.5px] text-slate-700 dark:text-zinc-300 truncate max-w-[140px]">
                                                    {{ $proposal->reviewer->full_name }}
                                                </span>
                                            </div>
                                        @else
                                            <span
                                                class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic whitespace-nowrap">
                                                Not assigned
                                            </span>
                                        @endif
                                    </td>

                                    {{-- STATUS --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <span
                                            class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-full
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

                                                @if ($this->canAddProgressReport($proposal))
                                                    <flux:menu.item icon="document-chart-bar" x-data
                                                        x-on:click="$dispatch('open-add-progress-report', { proposalId: {{ $proposal->id }} })"
                                                        class="text-emerald-600 dark:text-emerald-400">
                                                        Upload Progress Report
                                                    </flux:menu.item>
                                                @endif

                                                @if ($proposal->progressReport && $this->canEdit($proposal->progressReport->status))
                                                    <flux:menu.item icon="pencil-square" x-data
                                                        x-on:click="$dispatch('open-edit-progress-report', { id: {{ $proposal->progressReport->id }} })">
                                                        Edit Progress Report
                                                    </flux:menu.item>
                                                @endif

                                                @if ($this->canAddFinalReport($proposal))
                                                    <flux:menu.item icon="document-check" x-data
                                                        x-on:click="$dispatch('open-add-final-report', { proposalId: {{ $proposal->id }} })"
                                                        class="text-blue-600 dark:text-blue-400">
                                                        Upload Final Report
                                                    </flux:menu.item>
                                                @endif

                                                @if ($proposal->finalReport && $this->canEdit($proposal->finalReport->status))
                                                    <flux:menu.item icon="pencil-square" x-data
                                                        x-on:click="$dispatch('open-edit-final-report', { id: {{ $proposal->finalReport->id }} })">
                                                        Edit Final Report
                                                    </flux:menu.item>
                                                @endif

                                                @if ($this->canAddOutput($proposal))
                                                    <flux:menu.item icon="trophy" x-data
                                                        x-on:click="$dispatch('open-add-output', { proposalId: {{ $proposal->id }} })"
                                                        class="text-amber-600 dark:text-amber-400">
                                                        Upload Output
                                                    </flux:menu.item>
                                                @endif

                                                @if ($proposal->output && $this->canEdit($proposal->output->status))
                                                    <flux:menu.item icon="pencil-square" x-data
                                                        x-on:click="$dispatch('open-edit-output', { id: {{ $proposal->output->id }} })">
                                                        Edit Output
                                                    </flux:menu.item>
                                                @endif

                                                @if ($proposal->adminNotes->count() > 0 || $proposal->reviewerNotes->count() > 0)
                                                    <flux:menu.separator />
                                                @endif

                                                @if ($proposal->adminNotes->count() > 0)
                                                    <flux:menu.item icon="chat-bubble-left-right" x-data
                                                        x-on:click="$dispatch('open-user-view-admin-notes', { id: {{ $proposal->id }}, type: 'proposal' })"
                                                        class="text-amber-600 dark:text-amber-400">
                                                        View Admin Notes
                                                        <span
                                                            class="ml-auto text-[9px] font-bold px-1.5 py-0.5 rounded-full
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
                                                        View Reviewer Notes
                                                        <span
                                                            class="ml-auto text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                                             bg-violet-100 text-violet-700
                                                             dark:bg-violet-900/40 dark:text-violet-300">
                                                            {{ $proposal->reviewerNotes->count() }}
                                                        </span>
                                                    </flux:menu.item>
                                                @endif

                                                @if ($this->canEdit($proposal->status))
                                                    <flux:menu.separator />
                                                    <flux:menu.item icon="pencil-square" x-data
                                                        x-on:click="$dispatch('open-edit-proposal', { id: {{ $proposal->id }} })">
                                                        Edit Proposal
                                                    </flux:menu.item>
                                                @endif

                                                @if ($this->canDelete($proposal->status))
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
                                    <td colspan="5" class="px-4 py-10">
                                        <div class="flex flex-col items-center gap-2 text-center">
                                            <div
                                                class="w-11 h-11 rounded-full bg-slate-100 dark:bg-zinc-800
                                                flex items-center justify-center">
                                                <flux:icon.heart class="size-5 text-slate-400 dark:text-zinc-600" />
                                            </div>
                                            <div>
                                                <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                    No proposals yet
                                                </p>
                                                <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                    @if ($search || $statusFilter !== 'all' || ($periodFilter ?? 'all') !== 'all')
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
    </div>

    {{-- ══════════ MODALS ══════════ --}}
    <x-confirm-delete />

    <livewire:user.internal.proposals.add-proposal :is-research="false" wire:key="add-proposal-dedication" />
    <livewire:user.internal.proposals.edit-proposal wire:key="edit-proposal-dedication" />
    <livewire:user.internal.proposals.add-progress-report wire:key="add-progress-dedication" />
    <livewire:user.internal.proposals.edit-progress-report wire:key="edit-progress-dedication" />
    <livewire:user.internal.proposals.add-final-report wire:key="add-final-dedication" />
    <livewire:user.internal.proposals.edit-final-report wire:key="edit-final-dedication" />
    <livewire:user.internal.proposals.add-output wire:key="add-output-dedication" />
    <livewire:user.internal.proposals.edit-output wire:key="edit-output-dedication" />

    <livewire:user.internal.modals.view-admin-notes />
    <livewire:user.internal.modals.view-reviewer-notes />
    <livewire:user.internal.modals.view-submission />
</div>
