<?php

use App\Models\Period;
use App\Models\Proposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Researches')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
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
    public function updatingPeriodFilter(): void
    {
        $this->resetPage();
    }

    /* ============================================================
     |  ACTIONS
     ============================================================ */

    public function submit(int $id): void
    {
        $proposal = Proposal::find($id);

        if (!$proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        if (!in_array($proposal->status, ['pending', 'revised', 'rejected'], true)) {
            Flux::toast('Proposal cannot be submitted at this status.', variant: 'danger');
            return;
        }

        $proposal->update(['status' => 'submitted']);
        Flux::toast('Proposal submitted successfully.', variant: 'success');
    }

    public function reject(int $id): void
    {
        $proposal = Proposal::find($id);

        if (!$proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        if ($proposal->status === 'rejected') {
            Flux::toast('Proposal already rejected.', variant: 'danger');
            return;
        }

        $proposal->update(['status' => 'rejected']);
        Flux::toast('Proposal rejected.', variant: 'success');
    }

    /* ============================================================
     |  DELETE FLOW
     ============================================================ */

    public function confirmDelete(int $id): void
    {
        $proposal = Proposal::find($id);

        if (!$proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
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

        $proposal = Proposal::find($id);

        if (!$proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
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
     |  QUERY
     ============================================================ */

    public function with(): array
    {
        $selectedPeriod = $this->periodFilter === 'all' ? null : Period::find((int) $this->periodFilter);

        $proposals = Proposal::query()
            ->with(['author', 'researchScheme', 'period', 'reviewer'])
            ->where('is_research', true)
            ->when($selectedPeriod, fn($q) => $q->where('period_id', $selectedPeriod->id))
            ->when(
                $this->search,
                fn($q) => $q->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('keywords', 'like', "%{$this->search}%")
                        ->orWhereHas(
                            'author',
                            fn($q) => $q
                                ->where('full_name', 'like', "%{$this->search}%")
                                ->orWhere('nidn', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%"),
                        );
                }),
            )
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
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

<div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- ══════════ HEADER ══════════ --}}
        <x-dashboard-header icon="document-text" title="Manage Researches"
            leading="Manage research proposals. Verify, revise, or reject proposals." />

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

                <div class="flex flex-wrap items-center gap-1.5 min-w-0">

                    {{-- ─────── STATS BADGE ─────── --}}
                    <div
                        class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                        bg-emerald-50 dark:bg-emerald-900/20
                        border border-emerald-100 dark:border-emerald-900/40">
                        <flux:icon.document-text class="size-3 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-[10.5px] font-semibold text-emerald-700 dark:text-emerald-300">
                            {{ $proposals->total() }}
                        </span>
                        <span class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70 hidden sm:inline">
                            researches
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
                       bg-emerald-100 dark:bg-emerald-900/40
                       flex items-center justify-center">
                        <svg class="animate-spin size-2.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24"
                            fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                opacity=".25" />
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                        </svg>
                    </div>

                    {{-- ─────── STATUS FILTER ─────── --}}
                    <div class="shrink-0">
                        <x-select wire:model.live="statusFilter" size="sm" color="emerald" maxWidth="w-auto">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="submitted">Submitted</option>
                            <option value="under_review">Under Review</option>
                            <option value="revised">Revised</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                        </x-select>
                    </div>

                    {{-- ─────── SEARCH (mobile: baris 2, desktop: kanan) ─────── --}}
                    <div
                        class="order-5 sm:order-4
                        w-full sm:w-auto
                        sm:ml-auto
                        md:w-44 lg:w-56
                        min-w-0">
                        <x-input-search name="q" wire:model.live="search" id="search-research"
                            placeholder="Search researches..." class="w-full !text-[10.5px]" />
                    </div>
                </div>
            </div>

            {{-- ─────── TABLE (RESPONSIVE) ─────── --}}
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

                {{-- Scrollable table wrapper --}}
                <div
                    class="overflow-x-auto overscroll-x-contain scroll-smooth
                    [scrollbar-width:thin]
                    [&::-webkit-scrollbar]:h-1.5
                    [&::-webkit-scrollbar-thumb]:bg-slate-300
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">

                    <table class="w-full min-w-[560px] sm:min-w-[640px] lg:min-w-[720px]">
                        <thead>
                            <tr
                                class="bg-slate-50/80 dark:bg-zinc-900/50
                               border-b border-slate-200 dark:border-zinc-800">
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Research
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Author
                                </th>
                                <th
                                    class="hidden md:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Reviewer
                                </th>
                                <th
                                    class="hidden lg:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Scheme
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Status
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                            @forelse ($proposals as $proposal)
                                @php $meta = $proposal->statusMeta(); @endphp

                                <tr wire:key="research-{{ $proposal->id }}"
                                    class="group transition-colors duration-150
                                   hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                    {{-- ══════════ RESEARCH ══════════ --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <div class="flex items-start gap-2 min-w-0">
                                            <div
                                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-full shrink-0
                                                bg-gradient-to-br from-emerald-100 to-teal-50
                                                dark:from-emerald-900/30 dark:to-teal-900/20
                                                flex items-center justify-center
                                                ring-1 ring-white/40 dark:ring-zinc-800/40
                                                group-hover:scale-105 transition-transform">
                                                <flux:icon.document-text
                                                    class="size-3 sm:size-3.5 text-emerald-600 dark:text-emerald-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] sm:text-[11.5px] font-semibold leading-tight truncate
                                                  text-slate-900 dark:text-white max-w-[200px] sm:max-w-none"
                                                    title="{{ $proposal->title }}">
                                                    {{ Str::limit($proposal->title, 40) }}
                                                </p>
                                                <div class="mt-0.5 flex items-center gap-1.5 flex-wrap text-[9.5px]">
                                                    <span
                                                        class="px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap
                                                         bg-emerald-100 text-emerald-700
                                                         dark:bg-emerald-900/30 dark:text-emerald-300">
                                                        Research
                                                    </span>
                                                    <span
                                                        class="text-slate-500 dark:text-zinc-500 truncate max-w-[160px]">
                                                        {{ $proposal->keywords }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- ══════════ AUTHOR ══════════ --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        @if ($proposal->author)
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div
                                                    class="w-6 h-6 rounded-full shrink-0
                                                    bg-emerald-100 text-emerald-700
                                                    dark:bg-emerald-900/40 dark:text-emerald-300
                                                    flex items-center justify-center
                                                    text-[9px] font-bold">
                                                    {{ strtoupper(substr($proposal->author->full_name, 0, 1)) }}
                                                </div>
                                                <span
                                                    class="text-[10.5px] text-slate-700 dark:text-zinc-300 truncate whitespace-nowrap">
                                                    {{ $proposal->author->full_name }}
                                                </span>
                                            </div>
                                        @else
                                            <span
                                                class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic">—</span>
                                        @endif
                                    </td>

                                    {{-- ══════════ REVIEWER ══════════ --}}
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
                                                    class="text-[10.5px] text-slate-700 dark:text-zinc-300 truncate whitespace-nowrap">
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

                                    {{-- ══════════ SCHEME ══════════ --}}
                                    <td class="hidden lg:table-cell px-2 sm:px-3 py-2">
                                        <span
                                            class="text-[9.5px] px-1.5 py-0.5 rounded-full whitespace-nowrap
                                             bg-slate-100 dark:bg-zinc-800
                                             text-slate-600 dark:text-zinc-400">
                                            {{ $proposal->researchScheme?->code ?? '—' }}
                                        </span>
                                    </td>

                                    {{-- ══════════ STATUS ══════════ --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <span
                                            class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-full whitespace-nowrap
                                             text-[9.5px] font-semibold
                                             {{ $meta['class'] }}">
                                            {{ $meta['label'] }}
                                        </span>
                                    </td>

                                    {{-- ══════════ ACTION ══════════ --}}
                                    <td class="px-2 sm:px-3 py-2 text-right">
                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                                class="!p-1 rounded-full text-slate-400
                                               hover:bg-slate-100 hover:text-slate-600
                                               dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300
                                               hover:scale-110 active:scale-95
                                               opacity-60 group-hover:opacity-100 transition-all duration-150" />

                                            <flux:menu class="!text-[11px]">

                                                {{-- ══════════ PENDING ══════════ --}}
                                                @if ($proposal->status === 'pending')
                                                    <flux:menu.item icon="check-circle"
                                                        wire:click="submit({{ $proposal->id }})">
                                                        Submit Proposal
                                                    </flux:menu.item>
                                                    <flux:menu.item icon="arrow-path" x-data
                                                        x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                        Request Revision
                                                    </flux:menu.item>
                                                    <flux:menu.item variant="danger" icon="x-circle"
                                                        wire:click="reject({{ $proposal->id }})">
                                                        Reject Proposal
                                                    </flux:menu.item>
                                                @endif

                                                {{-- ══════════ REVISED ══════════ --}}
                                                @if ($proposal->status === 'revised')
                                                    <flux:menu.item icon="check-circle"
                                                        wire:click="submit({{ $proposal->id }})">
                                                        Submit Proposal
                                                    </flux:menu.item>
                                                    <flux:menu.item icon="arrow-path" x-data
                                                        x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                        Request Revision
                                                    </flux:menu.item>
                                                    <flux:menu.item variant="danger" icon="x-circle"
                                                        wire:click="reject({{ $proposal->id }})">
                                                        Reject Proposal
                                                    </flux:menu.item>
                                                @endif

                                                {{-- ══════════ SUBMITTED ══════════ --}}
                                                @if ($proposal->status === 'submitted')
                                                    <flux:menu.item icon="arrow-path" x-data
                                                        x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                        Request Revision
                                                    </flux:menu.item>
                                                    <flux:menu.item variant="danger" icon="x-circle"
                                                        wire:click="reject({{ $proposal->id }})">
                                                        Reject Proposal
                                                    </flux:menu.item>
                                                    <flux:menu.item icon="user-plus" x-data
                                                        x-on:click="$dispatch('open-assign-reviewer', { id: {{ $proposal->id }} })"
                                                        class="text-violet-600 dark:text-violet-400">
                                                        Assign Reviewer
                                                    </flux:menu.item>
                                                @endif

                                                {{-- ══════════ REJECTED ══════════ --}}
                                                @if ($proposal->status === 'rejected')
                                                    <flux:menu.item icon="arrow-path" x-data
                                                        x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                        Request Revision
                                                    </flux:menu.item>
                                                    <flux:menu.item icon="check-circle"
                                                        wire:click="submit({{ $proposal->id }})">
                                                        Submit Proposal
                                                    </flux:menu.item>
                                                @endif

                                                {{-- ══════════ VIEW SUBMISSIONS ══════════ --}}
                                                @if ($proposal->progressReport || $proposal->finalReport || $proposal->output)
                                                    <flux:menu.separator />
                                                    <flux:menu.item icon="eye" x-data
                                                        x-on:click="$dispatch('open-view-submission', { proposalId: {{ $proposal->id }} })"
                                                        class="text-slate-700 dark:text-zinc-300">
                                                        View Submissions
                                                    </flux:menu.item>
                                                @endif

                                                {{-- ══════════ UNDER REVIEW ══════════ --}}
                                                @if ($proposal->status === 'under_review')
                                                    <flux:menu.item icon="arrow-path" x-data
                                                        x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                        Request Revision
                                                    </flux:menu.item>
                                                    <flux:menu.separator />
                                                @endif

                                                {{-- ══════════ ACCEPTED ══════════ --}}
                                                @if ($proposal->status === 'accepted')
                                                    <flux:menu.item variant="danger" icon="x-circle"
                                                        wire:click="reject({{ $proposal->id }})">
                                                        Reject Proposal
                                                    </flux:menu.item>
                                                @endif

                                                {{-- ══════════ NOTES & VIEW ══════════ --}}
                                                <flux:menu.separator />

                                                @if (!in_array($proposal->status, ['pending']))
                                                    <flux:menu.item icon="chat-bubble-left-right" x-data
                                                        x-on:click="$dispatch('open-admin-notes', { id: {{ $proposal->id }}, type: 'proposal' })">
                                                        Admin Notes
                                                    </flux:menu.item>
                                                @endif

                                                @if (in_array($proposal->status, ['under_review', 'accepted', 'rejected']))
                                                    <flux:menu.item icon="clipboard-document-check" x-data
                                                        x-on:click="$dispatch('open-reviewer-notes', { id: {{ $proposal->id }}, type: 'proposal' })"
                                                        class="text-violet-600 dark:text-violet-400">
                                                        Reviewer Notes
                                                    </flux:menu.item>
                                                @endif

                                                <flux:menu.item icon="eye" x-data
                                                    x-on:click="$dispatch('open-view-proposal', { id: {{ $proposal->id }} })">
                                                    View Proposal
                                                </flux:menu.item>

                                                <flux:menu.separator />

                                                <flux:menu.item variant="danger" icon="trash"
                                                    wire:click="confirmDelete({{ $proposal->id }})">
                                                    Delete Proposal
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10">
                                        <div class="flex flex-col items-center gap-2 text-center">
                                            <div
                                                class="w-11 h-11 rounded-full bg-slate-100 dark:bg-zinc-800
                                                flex items-center justify-center">
                                                <flux:icon.document-text
                                                    class="size-5 text-slate-400 dark:text-zinc-600" />
                                            </div>
                                            <div>
                                                <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                    No researches yet
                                                </p>
                                                <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                    @if ($search || $statusFilter || ($periodFilter ?? 'all') !== 'all')
                                                        No results match your filters.
                                                    @else
                                                        Researches will appear after users submit them.
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

    <livewire:admin.internal.modals.assign-reviewer />
    <livewire:admin.internal.modals.admin-note-revision />
    <livewire:admin.internal.modals.reviewer-notes />
    <livewire:admin.internal.modals.admin-notes />
    <livewire:admin.internal.modals.view-proposal />
    <livewire:admin.internal.modals.view-submission />
    <livewire:admin.internal.modals.assign-reviewer-progress />
</div>
