<?php

use App\Models\Proposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Research Proposals')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public bool $isResearch = true;
    public ?int $highlight = null;

    public function mount(): void
    {
        $this->highlight = (int) request()->query('highlight');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $proposal = Proposal::where('user_id', auth()->id())->findOrFail($id);

        if (!in_array($proposal->status, ['pending', 'revised'])) {
            Flux::toast('Proposal ini tidak dapat dihapus.', variant: 'danger');
            return;
        }

        $this->dispatch('confirm-delete', subject: $proposal->title, action: 'deleteProposal', payload: ['id' => $proposal->id], title: 'Hapus Proposal?', note: 'Tindakan ini tidak dapat dibatalkan.');
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deleteProposal') {
            $this->deleteProposal($payload['id'] ?? null);
        }
    }

    protected function deleteProposal(?int $id): void
    {
        if (!$id) {
            return;
        }
        Proposal::where('user_id', auth()->id())
            ->findOrFail($id)
            ->delete();
        Flux::toast('Proposal berhasil dihapus.', variant: 'success');
    }

    public function canEdit(string $status): bool
    {
        return in_array($status, ['pending', 'revised']);
    }

    public function canDelete(string $status): bool
    {
        return in_array($status, ['pending', 'revised']);
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

    /**
     * Ambil periode aktif yang masih dalam rentang open_from - open_to.
     */
    public function activeOpenPeriod(): ?\App\Models\Period
    {
        return \App\Models\Period::query()->where('is_active', true)->whereDate('open_from', '<=', now())->whereDate('open_to', '>=', now())->first();
    }

    /**
     * Cek apakah user bisa menambah proposal sekarang.
     */
    public function canCreateProposal(): bool
    {
        return $this->activeOpenPeriod() !== null;
    }

    /**
     * Ambil periode aktif (apapun status rentangnya).
     */
    public function activePeriod(): ?\App\Models\Period
    {
        return \App\Models\Period::query()->where('is_active', true)->first();
    }

    public function with(): array
    {
        $proposals = Proposal::query()
            ->with(['researchScheme', 'period', 'reviewer', 'progressReport', 'finalReport', 'output', 'adminNotes', 'reviewerNotes'])
            ->where('user_id', auth()->id())
            ->where('is_research', $this->isResearch)
            ->when(
                $this->search,
                fn($q) => $q->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")->orWhere('keywords', 'like', "%{$this->search}%");
                }),
            )
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        $canCreate = $this->canCreateProposal();
        $openPeriod = $this->activeOpenPeriod();
        $activePeriod = $this->activePeriod();

        return [
            'proposals' => $proposals,
            'canCreate' => $canCreate,
            'openPeriod' => $openPeriod,
            'activePeriod' => $activePeriod,
            'theme' => $this->isResearch ? ['icon' => 'beaker', 'color' => 'emerald', 'label' => 'Penelitian'] : ['icon' => 'heart', 'color' => 'rose', 'label' => 'Pengabdian'],
        ];
    }
};
?>

<div class="p-4 sm:p-6 space-y-6">
    {{-- ══════════ PERIOD INFO BANNER ══════════ --}}
    @if ($activePeriod)
        @php
            $isOpen = $canCreate;
            $bannerColor = $isOpen ? 'emerald' : 'amber';
        @endphp

        <div
            class="rounded-2xl border border-{{ $bannerColor }}-200 dark:border-{{ $bannerColor }}-800
                bg-gradient-to-r from-{{ $bannerColor }}-50 to-{{ $bannerColor }}-50/50
                dark:from-{{ $bannerColor }}-900/20 dark:to-{{ $bannerColor }}-900/10
                p-3.5 flex items-start sm:items-center gap-3">
            <div
                class="w-9 h-9 rounded-xl bg-{{ $bannerColor }}-100 dark:bg-{{ $bannerColor }}-900/40
                    flex items-center justify-center shrink-0">
                <flux:icon :name="$isOpen ? 'calendar-days' : 'lock-closed'"
                    class="size-4 text-{{ $bannerColor }}-600 dark:text-{{ $bannerColor }}-400" />
            </div>
            <div class="flex-1 min-w-0">
                <p
                    class="text-[10px] font-semibold uppercase tracking-wider
                      text-{{ $bannerColor }}-700 dark:text-{{ $bannerColor }}-300">
                    {{ $isOpen ? 'Pendaftaran Dibuka' : 'Pendaftaran Ditutup' }}
                </p>
                <p class="text-[12px] font-semibold text-slate-900 dark:text-white mt-0.5">
                    {{ $activePeriod->periode }}
                </p>
                <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                    @if ($isOpen)
                        Dibuka sampai <span
                            class="font-semibold">{{ $activePeriod->open_to?->format('d M Y') ?? '—' }}</span>
                    @elseif ($activePeriod->open_from && $activePeriod->open_from->isFuture())
                        Akan dibuka pada <span
                            class="font-semibold">{{ $activePeriod->open_from->format('d M Y') }}</span>
                    @elseif ($activePeriod->open_to && $activePeriod->open_to->isPast())
                        Ditutup pada <span class="font-semibold">{{ $activePeriod->open_to->format('d M Y') }}</span>
                    @endif
                </p>
            </div>
            @if ($isOpen)
                <span
                    class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                         bg-{{ $bannerColor }}-500 text-white text-[10px] font-semibold shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                    Buka
                </span>
            @endif
        </div>
    @else
        {{-- Tidak ada periode aktif --}}
        <div
            class="rounded-2xl border border-rose-200 dark:border-rose-800
                bg-rose-50 dark:bg-rose-900/20 p-3.5
                flex items-start sm:items-center gap-3">
            <div
                class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/40
                    flex items-center justify-center shrink-0">
                <flux:icon.exclamation-triangle class="size-4 text-rose-600 dark:text-rose-400" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-300">
                    Tidak Ada Periode Aktif
                </p>
                <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5">
                    Anda tidak dapat menambah proposal saat ini. Silakan hubungi admin.
                </p>
            </div>
        </div>
    @endif
    <x-dashboard-header :icon="$theme['icon']" title="Research Proposals" leading="Manage your research proposals." />

    <div
        class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl
                border border-white/80 dark:border-zinc-800 shadow-sm overflow-hidden">

        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                    gap-3 p-4 border-b border-slate-100 dark:border-zinc-800">

            <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-zinc-400 min-w-0">
                @if ($proposals->total() > 0)
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                                 bg-{{ $theme['color'] }}-50 text-{{ $theme['color'] }}-700 font-semibold
                                 dark:bg-{{ $theme['color'] }}-900/30 dark:text-{{ $theme['color'] }}-300">
                        <flux:icon.list-bullet class="size-3" />
                        {{ $proposals->total() }} {{ Str::plural('proposal', $proposals->total()) }}
                    </span>
                    <span class="text-slate-400 dark:text-zinc-600 hidden sm:inline">·</span>
                    <span class="truncate hidden sm:inline">
                        Showing
                        <span class="font-semibold text-slate-700 dark:text-zinc-200">
                            {{ $proposals->firstItem() }}–{{ $proposals->lastItem() }}
                        </span>
                        of
                        <span class="font-semibold text-slate-700 dark:text-zinc-200">
                            {{ $proposals->total() }}
                        </span>
                    </span>
                @else
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                                 bg-slate-100 text-slate-500 font-semibold
                                 dark:bg-zinc-800 dark:text-zinc-400">
                        <flux:icon.list-bullet class="size-3" />
                        No proposals
                    </span>
                @endif
            </div>

            <div class="flex gap-3">
                <x-input-search name="search" id="search-proposal" wire:model.live.debounce.300ms="search"
                    placeholder="Search title, keywords..." max-width="max-w-sm" class="w-full sm:w-md" />

                {{-- Tombol New Proposal --}}
                @if ($canCreate)
                    <flux:button icon="plus" x-data x-on:click="$dispatch('open-add-proposal')" variant="primary"
                        size="sm" class="shrink-0 w-full sm:w-auto justify-center">
                        New Proposal
                    </flux:button>
                @else
                    <div class="relative group shrink-0 w-full sm:w-auto">
                        <flux:button icon="plus" disabled variant="primary" size="sm"
                            class="w-full sm:w-auto justify-center cursor-not-allowed opacity-50">
                            New Proposal
                        </flux:button>

                        {{-- Tooltip --}}
                        <div
                            class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2
                    opacity-0 group-hover:opacity-100 transition-opacity duration-200
                    bg-slate-900 dark:bg-zinc-700 text-white text-[10px] rounded-md
                    px-2.5 py-1.5 whitespace-nowrap z-50 shadow-lg">
                            @if (!$activePeriod)
                                Tidak ada periode aktif. Hubungi admin.
                            @elseif ($activePeriod->open_from && $activePeriod->open_from->isFuture())
                                Pendaftaran dibuka pada
                                {{ $activePeriod->open_from->format('d M Y') }}
                            @elseif ($activePeriod->open_to && $activePeriod->open_to->isPast())
                                Pendaftaran sudah ditutup pada
                                {{ $activePeriod->open_to->format('d M Y') }}
                            @else
                                Pendaftaran proposal sedang tidak dibuka.
                            @endif
                            <div
                                class="absolute top-full left-1/2 -translate-x-1/2
                        border-4 border-transparent border-t-slate-900 dark:border-t-zinc-700">
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-xs">
                <thead
                    class="bg-{{ $theme['color'] }}-50/50 dark:bg-{{ $theme['color'] }}-900/20 text-left text-[11px]
                              uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Title & Scheme</th>
                        <th class="px-4 py-3 font-semibold">Period</th>
                        <th class="px-4 py-3 font-semibold">Reviewer</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @forelse ($proposals as $proposal)
                        @php $meta = $proposal->statusMeta(); @endphp
                        <tr class="{{ $highlight === $proposal->id ? 'bg-emerald-50 dark:bg-emerald-900/20 ring-2 ring-emerald-400' : '' }} hover:bg-{{ $theme['color'] }}-50/30 dark:hover:bg-zinc-800/50 transition-colors">

                            <td class="px-4 py-3 max-w-md">
                                <div class="flex flex-col gap-1 min-w-0">
                                    <span class="font-medium text-slate-900 dark:text-zinc-100 truncate"
                                        title="{{ $proposal->title }}">
                                        {{ Str::limit($proposal->title, 40) }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 dark:text-zinc-400 truncate">
                                        {{ $proposal->researchScheme?->name ?? '—' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-slate-600 dark:text-zinc-300 whitespace-nowrap">
                                {{ $proposal->period?->periode ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-slate-600 dark:text-zinc-300">
                                @if ($proposal->reviewer)
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded-full bg-violet-100 text-violet-700
                                                    flex items-center justify-center text-[10px] font-bold
                                                    dark:bg-violet-900/40 dark:text-violet-300">
                                            {{ strtoupper(substr($proposal->reviewer->full_name, 0, 1)) }}
                                        </div>
                                        <span class="truncate max-w-[140px]">
                                            {{ $proposal->reviewer->full_name }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-zinc-500 italic">Not assigned</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                             text-[11px] font-semibold whitespace-nowrap {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                        class="rounded-lg text-slate-500 hover:bg-slate-100
                                               dark:text-zinc-400 dark:hover:bg-zinc-700/60" />

                                    <flux:menu>
                                        <flux:menu.item icon="document-duplicate" x-data
                                            x-on:click="$dispatch('open-view-submission-user', { proposalId: {{ $proposal->id }} })"
                                            class="text-slate-700 dark:text-zinc-300">
                                            View Submissions
                                        </flux:menu.item>
                                        {{-- View Admin Notes --}}
                                        @if ($proposal->adminNotes->count() > 0)
                                            <flux:menu.item icon="chat-bubble-left-right" x-data
                                                x-on:click="$dispatch('open-user-view-admin-notes', { id: {{ $proposal->id }}, type: 'proposal' })"
                                                class="text-amber-600 dark:text-amber-400">
                                                Admin Notes
                                            </flux:menu.item>
                                        @endif

                                        {{-- View Reviewer Notes --}}
                                        @if ($proposal->reviewerNotes->count() > 0)
                                            <flux:menu.item icon="clipboard-document-check" x-data
                                                x-on:click="$dispatch('open-user-view-reviewer-notes', { id: {{ $proposal->id }}, type: 'proposal' })"
                                                class="text-violet-600 dark:text-violet-400">
                                                Reviewer Notes
                                            </flux:menu.item>
                                        @endif

                                        {{-- Delete --}}
                                        @if ($this->canDelete($proposal->status))
                                            <flux:menu.separator />
                                            <flux:menu.item variant="danger" icon="trash"
                                                wire:click="confirmDelete({{ $proposal->id }})">
                                                Delete
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon :name="$theme['icon']"
                                        class="size-10 text-slate-300 dark:text-zinc-700" />
                                    <div class="flex flex-col gap-1">
                                        <span class="font-medium text-slate-700 dark:text-zinc-300">
                                            No proposals yet
                                        </span>
                                        <span class="text-xs">
                                            @if ($search || $statusFilter !== 'all')
                                                No results match your current filters.
                                            @else
                                                Click "New Proposal" to get started.
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($proposals->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-zinc-800">
                {{ $proposals->links() }}
            </div>
        @endif
    </div>

    {{-- ══════════ MODALS ══════════ --}}
    <livewire:user.internal.proposals.add-proposal :is-research="true" wire:key="add-proposal-research" />
    <livewire:user.internal.proposals.edit-proposal wire:key="edit-proposal-research" />
    <livewire:user.internal.proposals.add-progress-report wire:key="add-progress-research" />
    <livewire:user.internal.proposals.edit-progress-report wire:key="edit-progress-research" />
    <livewire:user.internal.proposals.add-final-report wire:key="add-final-research" />
    <livewire:user.internal.proposals.edit-final-report wire:key="edit-final-research" />
    <livewire:user.internal.proposals.add-output wire:key="add-output-research" />
    <livewire:user.internal.proposals.edit-output wire:key="edit-output-research" />
    {{-- NEW: View Notes Modals --}}
    <livewire:user.internal.modals.view-admin-notes />
    <livewire:user.internal.modals.view-reviewer-notes />
    <livewire:user.internal.modals.view-submission />
</div>
