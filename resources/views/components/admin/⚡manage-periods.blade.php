<?php

use App\Models\Period;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Periods')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /* ============================================================
     |  ACTIONS
     ============================================================ */

    public function setActive(int $id): void
    {
        $period = Period::find($id);
        if (! $period) return;

        if (! $this->canActivate($period)) {
            Flux::toast('This period is locked and cannot be activated.', variant: 'danger');
            return;
        }

        if ($period->is_active) {
            Flux::toast('This period is already active.', variant: 'info');
            return;
        }

        Period::where('is_active', true)->update(['is_active' => false]);
        $period->update(['is_active' => true]);

        Flux::toast("Period '{$period->periode}' activated.", variant: 'success');
    }

    /* ============================================================
     |  DELETE FLOW (modal confirmation)
     ============================================================ */

    public function confirmDelete(int $id): void
    {
        $period = Period::find($id);
        if (! $period) {
            Flux::toast('Period not found.', variant: 'danger');
            return;
        }

        if (! $this->canDelete($period)) {
            Flux::toast('This period cannot be deleted.', variant: 'danger');
            return;
        }

        $this->dispatch(
            'confirm-delete',
            title: 'Delete Period?',
            message: 'You are about to delete:',
            subject: $period->periode,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
            cancelLabel: 'Cancel',
            action: 'deletePeriod',
            payload: ['id' => $period->id],
        );
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deletePeriod') {
            $this->deletePeriod($payload['id'] ?? null);
        }
    }

    public function deletePeriod(?int $id): void
    {
        if (! $id) return;

        $period = Period::find($id);
        if (! $period) return;

        if ($this->isLocked($period)) {
            Flux::toast('This period is locked and cannot be deleted.', variant: 'danger');
            return;
        }

        if ($this->hasProposals($period)) {
            Flux::toast('Cannot delete a period that has proposals.', variant: 'danger');
            return;
        }

        try {
            $name = $period->periode;
            $period->delete();
            Flux::toast("Period '{$name}' deleted.", variant: 'success');
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to delete period.', variant: 'danger');
        }
    }

    /* ============================================================
     |  HELPERS
     ============================================================ */

    public function hasProposals(Period $period): bool
    {
        return $period->proposals()->exists();
    }

    public function isLocked(Period $period): bool
    {
        if ($this->isFutureYearWithActiveCurrent($period)) {
            return true;
        }
        if ($this->hasProposals($period)) {
            return true;
        }
        return false;
    }

    protected function isFutureYearWithActiveCurrent(Period $period): bool
    {
        if (! $period->open_to) return false;

        $currentYear = now()->year;
        $openToYear  = $period->open_to->year;

        if ($openToYear <= $currentYear) return false;

        return Period::query()
            ->where('is_active', true)
            ->where('id', '!=', $period->id)
            ->whereYear('open_from', $currentYear)
            ->exists();
    }

    public function canEdit(Period $period): bool
    {
        return ! $this->isLocked($period);
    }

    public function canDelete(Period $period): bool
    {
        return ! $this->isLocked($period) && ! $this->hasProposals($period);
    }

    public function canActivate(Period $period): bool
    {
        return ! $this->isLocked($period);
    }

    public function isOngoing(Period $period): bool
    {
        if (! $period->open_from || ! $period->open_to) return false;

        return $period->open_from->copy()->startOfDay()->isPast()
            && $period->open_to->copy()->endOfDay()->isFuture();
    }

    /* ============================================================
     |  QUERY
     ============================================================ */

    public function with(): array
    {
        $periods = Period::query()
            ->when($this->search, fn ($q) => $q->where('periode', 'like', '%' . $this->search . '%'))
            ->orderByDesc('open_from')
            ->paginate(10);

        return ['periods' => $periods];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- ══════════ HEADER ══════════ --}}
        <x-dashboard-header icon="calendar-days" title="Manage Periods"
            leading="Manage all the periods of your system." />

        {{-- ══════════ MAIN CARD ══════════ --}}
        <div class="bg-white dark:bg-zinc-900 rounded-full
                    shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50
                    border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- ─────── TOOLBAR ─────── --}}
            <div class="px-2.5 sm:px-3 py-2
                        border-b border-slate-200 dark:border-zinc-800
                        bg-slate-50/50 dark:bg-zinc-900/50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">

                    {{-- Left: stat pill --}}
                    <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md
                                bg-emerald-50 dark:bg-emerald-900/20
                                border border-emerald-100 dark:border-emerald-900/40
                                w-fit">
                        <flux:icon.calendar-days class="size-3 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-[10.5px] font-semibold text-emerald-700 dark:text-emerald-300">
                            {{ $periods->total() }}
                        </span>
                        <span class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70">
                            periods
                        </span>
                    </div>

                    {{-- Right: search + new --}}
                    <div class="flex items-center gap-1.5 w-full sm:w-auto">
                        <div class="flex-1 sm:flex-none sm:w-56">
                            <x-input-search name="q" wire:model.live="search" id="search-periode"
                                placeholder="Search periods..."
                                class="w-full text-[10.5px]!" />
                        </div>
                        <flux:button variant="primary"
                            x-data x-on:click="$dispatch('add-period-modal')"
                            class="shrink-0 text-[10.5px]!">
                            New Item
                        </flux:button>
                    </div>
                </div>
            </div>

            {{-- ─────── TABLE ─────── --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px]">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-zinc-900/50
                                   border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Period
                            </th>
                            <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Status
                            </th>
                            <th class="hidden lg:table-cell px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Open From
                            </th>
                            <th class="hidden lg:table-cell px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Open To
                            </th>
                            <th class="px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                        @forelse ($periods as $period)
                            @php $locked = $this->isLocked($period); @endphp

                            <tr wire:key="period-{{ $period->id }}"
                                class="group transition-colors duration-150
                                {{ $locked
                                    ? 'bg-slate-50/40 dark:bg-zinc-900/30 opacity-80'
                                    : 'hover:bg-slate-50/70 dark:hover:bg-zinc-800/40' }}">

                                {{-- PERIOD --}}
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-md shrink-0
                                                    bg-gradient-to-br from-emerald-100 to-teal-50
                                                    dark:from-emerald-900/30 dark:to-teal-900/20
                                                    flex items-center justify-center
                                                    ring-1 ring-white/40 dark:ring-zinc-800/40">
                                            <flux:icon.calendar
                                                class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[11.5px] font-semibold leading-tight
                                                      text-slate-900 dark:text-white
                                                      flex items-center gap-1.5">
                                                <span class="truncate">{{ $period->periode }}</span>
                                                @if ($locked)
                                                    <flux:icon.lock-closed
                                                        class="size-3 text-rose-500 dark:text-rose-400 shrink-0" />
                                                @endif
                                            </p>
                                            {{-- Mobile-only dates --}}
                                            <div class="lg:hidden mt-0.5 flex items-center gap-1.5 flex-wrap
                                                        text-[9.5px] text-slate-500 dark:text-zinc-500">
                                                <span>{{ $period->open_from?->format('d M Y') ?? '—' }}</span>
                                                <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                                                <span>{{ $period->open_to?->format('d M Y') ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- STATUS --}}
                                <td class="px-3 py-2">
                                    <div class="flex flex-col gap-1 items-start">
                                        @if ($period->is_active)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full
                                                         bg-emerald-100 dark:bg-emerald-900/30
                                                         text-emerald-700 dark:text-emerald-300
                                                         text-[9.5px] font-semibold w-fit">
                                                <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full
                                                         bg-slate-100 dark:bg-zinc-800
                                                         text-slate-600 dark:text-zinc-400
                                                         text-[9.5px] font-medium w-fit">
                                                <span class="w-1 h-1 rounded-full bg-slate-400 dark:bg-zinc-600"></span>
                                                Inactive
                                            </span>
                                        @endif

                                        @if ($locked)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full
                                                         bg-rose-100 dark:bg-rose-900/30
                                                         text-rose-700 dark:text-rose-300
                                                         text-[9.5px] font-semibold w-fit">
                                                <flux:icon.lock-closed class="size-2.5" />
                                                Locked
                                            </span>
                                        @elseif ($this->hasProposals($period))
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full
                                                         bg-sky-100 dark:bg-sky-900/30
                                                         text-sky-700 dark:text-sky-300
                                                         text-[9.5px] font-medium w-fit">
                                                <flux:icon.document-text class="size-2.5" />
                                                {{ $period->proposals()->count() }} proposal
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- OPEN FROM --}}
                                <td class="hidden lg:table-cell px-3 py-2">
                                    <div class="flex items-center gap-1.5 text-[10.5px]
                                                text-slate-600 dark:text-zinc-400">
                                        <flux:icon.clock class="size-3 text-slate-400 dark:text-zinc-500" />
                                        <span>{{ $period->open_from?->format('d M Y') ?? '—' }}</span>
                                    </div>
                                </td>

                                {{-- OPEN TO --}}
                                <td class="hidden lg:table-cell px-3 py-2">
                                    <div class="flex items-center gap-1.5 text-[10.5px]
                                                text-slate-600 dark:text-zinc-400">
                                        <flux:icon.clock class="size-3 text-slate-400 dark:text-zinc-500" />
                                        <span>{{ $period->open_to?->format('d M Y') ?? '—' }}</span>
                                    </div>
                                </td>

                                {{-- ACTIONS --}}
                                <td class="px-3 py-2 text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                        :disabled="$locked"
                                        class="{{ $locked
                                            ? 'text-slate-300 dark:text-zinc-600 cursor-not-allowed'
                                            : 'text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300' }}" />

                                        <flux:menu class="!text-[11px]">

                                            @if ($locked)
                                                <flux:menu.item icon="lock-closed" disabled>
                                                    Period Locked
                                                </flux:menu.item>
                                                <flux:menu.item icon="eye" disabled>
                                                    View Only
                                                </flux:menu.item>
                                            @else
                                                @if (! $period->is_active)
                                                    <flux:menu.item icon="check-circle"
                                                        wire:click="setActive({{ $period->id }})">
                                                        Activate Period
                                                    </flux:menu.item>
                                                @endif

                                                <flux:menu.item icon="pencil-square" x-data
                                                    x-on:click="$dispatch('open-edit-period', { id: {{ $period->id }} })">
                                                    Edit Period
                                                </flux:menu.item>

                                                @if (! $this->hasProposals($period))
                                                    <flux:menu.separator />
                                                    <flux:menu.item variant="danger" icon="trash"
                                                        wire:click="confirmDelete({{ $period->id }})">
                                                        Delete Period
                                                    </flux:menu.item>
                                                @else
                                                    <flux:menu.separator />
                                                    <flux:menu.item variant="danger" icon="trash" disabled>
                                                        Delete Period
                                                    </flux:menu.item>
                                                @endif
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>

                        @empty
                            {{-- EMPTY STATE ── --}}
                            <tr>
                                <td colspan="5" class="px-4 py-10">
                                    <div class="flex flex-col items-center gap-2 text-center">
                                        <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-zinc-800
                                                    flex items-center justify-center">
                                            <flux:icon.calendar-days
                                                class="size-5 text-slate-400 dark:text-zinc-600" />
                                        </div>
                                        <div>
                                            <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                No periods yet
                                            </p>
                                            <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                Click "New" to create your first period.
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
            @if ($periods->hasPages())
                <div class="px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                            bg-slate-50/50 dark:bg-zinc-900/50">
                    {{ $periods->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ MODALS ══════════ --}}
    <x-confirm-delete />
    <livewire:admin.periods.add-period />
    <livewire:admin.periods.edit-period />
</div>
