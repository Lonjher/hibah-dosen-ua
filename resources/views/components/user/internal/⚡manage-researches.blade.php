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

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function confirmDelete(int $id): void
    {
        $proposal = Proposal::where('user_id', auth()->id())->findOrFail($id);

        if (! in_array($proposal->status, ['pending', 'revised'])) {
            Flux::toast('Proposal ini tidak dapat dihapus.', variant: 'danger');
            return;
        }

        $this->dispatch('confirm-delete',
            subject: $proposal->title,
            action: 'deleteProposal',
            payload: ['id' => $proposal->id],
            title: 'Hapus Proposal?',
            note: 'Tindakan ini tidak dapat dibatalkan.',
        );
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
        if (! $id) return;
        Proposal::where('user_id', auth()->id())->findOrFail($id)->delete();
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
        return $p->status === 'accepted' && ! $p->progressReport;
    }

    public function canAddFinalReport(Proposal $p): bool
    {
        return $p->progressReport?->status === 'accepted' && ! $p->finalReport;
    }

    public function canAddOutput(Proposal $p): bool
    {
        return $p->finalReport?->status === 'accepted' && ! $p->output;
    }

    public function with(): array
    {
        $proposals = Proposal::query()
            ->with(['researchScheme', 'period', 'reviewer', 'progressReport', 'finalReport', 'output'])
            ->where('user_id', auth()->id())
            ->where('is_research', $this->isResearch)
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('keywords', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        return [
            'proposals' => $proposals,
            'theme' => $this->isResearch
                ? ['icon' => 'beaker', 'color' => 'emerald', 'label' => 'Penelitian']
                : ['icon' => 'heart',  'color' => 'rose',    'label' => 'Pengabdian'],
        ];
    }
};
?>

<div class="p-4 sm:p-6 space-y-6">

    <x-dashboard-header :icon="$theme['icon']" title="Research Proposals"
        leading="Manage your research proposals." />

    <div class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl
                border border-white/80 dark:border-zinc-800 shadow-sm overflow-hidden">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                    gap-3 p-4 border-b border-slate-100 dark:border-zinc-800">

            <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-zinc-400 min-w-0">
                @if ($proposals->total() > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
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
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                                 bg-slate-100 text-slate-500 font-semibold
                                 dark:bg-zinc-800 dark:text-zinc-400">
                        <flux:icon.list-bullet class="size-3" />
                        No proposals
                    </span>
                @endif
            </div>

            <div class="flex gap-3">
                <x-input-search name="search" id="search-proposal"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search title, keywords..." max-width="max-w-sm"
                    class="w-full sm:w-md" />

                <flux:button icon="plus" x-data
                    x-on:click="$dispatch('open-add-proposal')"
                    variant="primary" size="sm"
                    class="shrink-0 w-full sm:w-auto justify-center">
                    New Proposal
                </flux:button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-xs">
                <thead class="bg-{{ $theme['color'] }}-50/50 dark:bg-{{ $theme['color'] }}-900/20 text-left text-[11px]
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
                        <tr class="hover:bg-{{ $theme['color'] }}-50/30 dark:hover:bg-zinc-800/50 transition-colors">

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
                                        <div class="w-6 h-6 rounded-full bg-violet-100 text-violet-700
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
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
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
                                        {{-- Progress Report --}}
                                        @if ($this->canAddProgressReport($proposal))
                                            <flux:menu.item icon="document-chart-bar"
                                                x-data
                                                x-on:click="$dispatch('open-add-progress-report', { proposalId: {{ $proposal->id }} })"
                                                class="text-emerald-600 dark:text-emerald-400">
                                                Upload Progress Report
                                            </flux:menu.item>
                                        @endif

                                        @if ($proposal->progressReport && $this->canEdit($proposal->progressReport->status))
                                            <flux:menu.item icon="pencil-square"
                                                x-data
                                                x-on:click="$dispatch('open-edit-progress-report', { id: {{ $proposal->progressReport->id }} })">
                                                Edit Progress Report
                                            </flux:menu.item>
                                        @endif

                                        {{-- Final Report --}}
                                        @if ($this->canAddFinalReport($proposal))
                                            <flux:menu.item icon="document-check"
                                                x-data
                                                x-on:click="$dispatch('open-add-final-report', { proposalId: {{ $proposal->id }} })"
                                                class="text-blue-600 dark:text-blue-400">
                                                Upload Final Report
                                            </flux:menu.item>
                                        @endif

                                        @if ($proposal->finalReport && $this->canEdit($proposal->finalReport->status))
                                            <flux:menu.item icon="pencil-square"
                                                x-data
                                                x-on:click="$dispatch('open-edit-final-report', { id: {{ $proposal->finalReport->id }} })">
                                                Edit Final Report
                                            </flux:menu.item>
                                        @endif

                                        {{-- Output --}}
                                        @if ($this->canAddOutput($proposal))
                                            <flux:menu.item icon="trophy"
                                                x-data
                                                x-on:click="$dispatch('open-add-output', { proposalId: {{ $proposal->id }} })"
                                                class="text-amber-600 dark:text-amber-400">
                                                Upload Output
                                            </flux:menu.item>
                                        @endif

                                        @if ($proposal->output && $this->canEdit($proposal->output->status))
                                            <flux:menu.item icon="pencil-square"
                                                x-data
                                                x-on:click="$dispatch('open-edit-output', { id: {{ $proposal->output->id }} })">
                                                Edit Output
                                            </flux:menu.item>
                                        @endif

                                        {{-- Edit Proposal --}}
                                        @if ($this->canEdit($proposal->status))
                                            <flux:menu.separator />
                                            <flux:menu.item icon="pencil-square"
                                                x-data
                                                x-on:click="$dispatch('open-edit-proposal', { id: {{ $proposal->id }} })">
                                                Edit Proposal
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
                                    <flux:icon :name="$theme['icon']" class="size-10 text-slate-300 dark:text-zinc-700" />
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
</div>
