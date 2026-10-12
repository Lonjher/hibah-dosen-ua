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

    public function approve(int $id): void
    {
        $proposal = Proposal::find($id);

        if (! $proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        // ── Admin approve setelah SUBMITTED → ADMIN_APPROVED ──
        if ($proposal->isSubmitted()) {
            $proposal->update(['status' => Proposal::STATUS_ADMIN_APPROVED]);
            Flux::toast('Proposal di-approve. Silakan assign reviewer.', variant: 'success');
            return;
        }

        // ── Admin final accept setelah REVIEWER_APPROVED → ACCEPTED ──
        if ($proposal->isReviewerApproved()) {
            $proposal->update(['status' => Proposal::STATUS_ACCEPTED]);
            Flux::toast('Proposal diterima (Accepted).', variant: 'success');
            return;
        }

        Flux::toast('Proposal tidak dapat di-approve pada status ini.', variant: 'danger');
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

    public function with(): array
    {
        $selectedPeriod = $this->periodFilter === 'all' ? null : Period::find((int) $this->periodFilter);

        $proposals = Proposal::query()
            ->with(['author', 'researchScheme', 'period', 'reviewer', 'proposalMembers.user', 'proposalStudents'])
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
        <x-dashboard-header icon="beaker" title="Manage Researches"
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
                        <flux:icon.beaker class="size-3 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-[10.5px] font-semibold text-emerald-700 dark:text-emerald-300">
                            {{ $proposals->total() }}
                        </span>
                        <span class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70 hidden sm:inline">
                            researches
                        </span>
                    </div>

                    {{-- ─────── PERIOD FILTER ─────── --}}
                    <flux:select wire:model.live="periodFilter">
                        <flux:select.option value="all">All Periods</flux:select.option>
                        @foreach ($periods as $p)
                            <flux:select.option value="{{ $p->id }}">{{ $p->periode }}</flux:select.option>
                        @endforeach
                    </flux:select>

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
                        <flux:select wire:model.live="statusFilter" ...>
                            <flux:select.option>All Status</flux:select.option>
                            <flux:select.option value="submitted">Submitted</flux:select.option>
                            <flux:select.option value="admin_revision">Admin Revision</flux:select.option>
                            <flux:select.option value="admin_approved">Admin Approved</flux:select.option>
                            <flux:select.option value="under_review">Under Review</flux:select.option>
                            <flux:select.option value="reviewer_revision">Reviewer Revision</flux:select.option>
                            <flux:select.option value="reviewer_approved">Reviewer Approved</flux:select.option>
                            <flux:select.option value="accepted">Accepted</flux:select.option>
                            <flux:select.option value="rejected">Rejected</flux:select.option>
                        </flux:select>
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
            <div class="relativ">

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
                                <th class="hidden lg:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                        text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Members
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
                                                <flux:icon.beaker
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

                                    {{-- MEMBERS --}}
                                    <td class="hidden lg:table-cell px-2 sm:px-3 py-2">
                                        <button type="button" x-data
                                            x-on:click="$dispatch('open-view-members', { id: {{ $proposal->id }} })"
                                            class="cursor-pointer inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full
                                                bg-slate-100 dark:bg-zinc-800
                                                hover:bg-emerald-100 dark:hover:bg-emerald-900/30
                                                text-[9.5px] font-medium
                                                text-slate-600 dark:text-zinc-400
                                                hover:text-emerald-700 dark:hover:text-emerald-300
                                                hover:scale-[1.02] active:scale-[0.97]
                                                transition-all duration-150">
                                            <flux:icon.user-group class="size-2.5" />
                                            <span>{{ $proposal->proposalMembers->count() }}</span>
                                            <flux:icon.academic-cap class="size-2.5 ml-0.5" />
                                            <span>{{ $proposal->proposalStudents->count() }}</span>
                                        </button>
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
                                                    @if ($proposal->isSubmitted())
                                                        <flux:menu.item icon="check-badge"
                                                            wire:click="approve({{ $proposal->id }})"
                                                            class="text-teal-600 dark:text-teal-400">
                                                            Approve Proposal
                                                        </flux:menu.item>
                                                        <flux:menu.item icon="arrow-path" x-data
                                                            x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                            Request Revision
                                                        </flux:menu.item>
                                                    @endif

                                                    @if ($proposal->isAdminRevision())
                                                        <flux:menu.item icon="arrow-path" x-data
                                                            x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                            Update Revision Note
                                                        </flux:menu.item>
                                                        <flux:menu.item icon="check-badge"
                                                            wire:click="approve({{ $proposal->id }})"
                                                            class="text-teal-600 dark:text-teal-400">
                                                            Approve Proposal
                                                        </flux:menu.item>
                                                    @endif

                                                    @if ($proposal->isAdminApproved())
                                                        <flux:menu.item icon="user-plus" x-data
                                                            x-on:click="$dispatch('open-assign-reviewer', { id: {{ $proposal->id }} })"
                                                            class="text-violet-600 dark:text-violet-400">
                                                            Assign Reviewer
                                                        </flux:menu.item>
                                                        {{-- <flux:menu.separator />
                                                        <flux:menu.item variant="danger" icon="x-circle"
                                                            wire:click="reject({{ $proposal->id }})">
                                                            Reject Proposal
                                                        </flux:menu.item> --}}
                                                    @endif

                                                    @if ($proposal->isUnderReview())
                                                        <flux:menu.item icon="user-plus" x-data
                                                            x-on:click="$dispatch('open-assign-reviewer', { id: {{ $proposal->id }} })">
                                                            Change Reviewer
                                                        </flux:menu.item>
                                                        {{-- Berikan Tombol untuk Reviewer Approved --}}
                                                    @endif

                                                    @if ($proposal->isReviewerRevision())
                                                        {{-- Berikan Tombol untuk Reviewer Approved --}}
                                                    @endif

                                                    @if ($proposal->isReviewerApproved())
                                                        <flux:menu.item icon="check-circle"
                                                            wire:click="approve({{ $proposal->id }})"
                                                            class="text-emerald-600 dark:text-emerald-400">
                                                            Final Accept
                                                        </flux:menu.item>
                                                        <flux:menu.item variant="danger" icon="x-circle"
                                                            wire:click="reject({{ $proposal->id }})">
                                                            Reject Proposal
                                                        </flux:menu.item>
                                                    @endif

                                                    @if ($proposal->progressReport || $proposal->finalReport || $proposal->outcome)
                                                        <flux:menu.separator />
                                                        <flux:menu.item icon="eye" x-data
                                                            x-on:click="$dispatch('open-view-submission', { proposalId: {{ $proposal->id }} })"
                                                            class="text-slate-700 dark:text-zinc-300">
                                                            View Submissions
                                                        </flux:menu.item>
                                                    @endif

                                                    {{-- Admin Notes — hanya muncul kalau sudah pernah di-review admin --}}
                                                    @if (!empty($proposal->adminNotes))
                                                        <flux:menu.item icon="chat-bubble-left-right" x-data
                                                            x-on:click="$dispatch('open-admin-notes', { id: {{ $proposal->id }}, type: 'proposal' })">
                                                            Admin Notes
                                                        </flux:menu.item>
                                                    @endif

                                                    {{-- Reviewer Notes — hanya muncul kalau sudah ada reviewer --}}
                                                    @if (!empty($proposal->reviewerNotes))
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


                                                    @if ($proposal->status === 'submitted')
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
                                            <div
                                                class="w-11 h-11 rounded-full bg-slate-100 dark:bg-zinc-800
                                                flex items-center justify-center">
                                                <flux:icon.beaker
                                                    class="size-5 text-slate-400 dark:text-zinc-600" />
                                            </div>
                                            <div>
                                                <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                    {{ __("No researches yet") }}
                                                </p>
                                                <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                    @if ($search || $statusFilter || ($periodFilter ?? 'all') !== 'all')
                                                        {{ __("No results match your filters.") }}
                                                    @else
                                                        {{ __("Researches will appear after users submit them.") }}
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
    <livewire:admin.internal.modals.view-members />
    <livewire:admin.internal.modals.assign-reviewer />
    <livewire:admin.internal.modals.admin-note-revision />
    <livewire:admin.internal.modals.reviewer-notes />
    <livewire:admin.internal.modals.admin-notes />
    <livewire:admin.internal.modals.view-proposal />
    <livewire:admin.internal.modals.view-submission />
    <livewire:admin.internal.modals.assign-reviewer-progress />
</div>
