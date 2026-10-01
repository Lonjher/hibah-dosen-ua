<?php

use App\Models\Period;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Periods')] class extends Component {
    use WithPagination;

    public $search;

    public function with(): array
    {
        $periods = Period::query()->when($this->search, fn($q) => $q->where('periode', 'like', '%' . $this->search . '%'))->orderByDesc('open_from')->paginate(10);

        return [
            'periods' => $periods,
        ];
    }

    public function delete(Period $period): void
    {
        if ($this->isLocked($period)) {
            Flux::toast('Periode ini terkunci dan tidak dapat dihapus.', variant: 'danger');
            return;
        }

        if ($this->hasProposals($period)) {
            Flux::toast('Tidak dapat menghapus periode yang memiliki proposal.', variant: 'danger');
            return;
        }

        $period->delete();
        Flux::toast('Periode dihapus.', variant: 'success');
    }

    public function setActive(Period $period): void
    {
        if (!$this->canActivate($period)) {
            Flux::toast('Periode ini terkunci dan tidak dapat diaktifkan.', variant: 'danger');
            return;
        }

        if ($period->is_active) {
            Flux::toast('Periode ini sudah aktif.', variant: 'info');
            return;
        }

        Period::where('is_active', true)->update(['is_active' => false]);
        $period->update(['is_active' => true]);

        Flux::toast("Periode '{$period->periode}' diaktifkan.", variant: 'success');
    }

    public function hasProposals(Period $period): bool
    {
        return $period->proposals()->exists();
    }

    /**
     * Cek apakah periode dianggap "locked" karena kondisinya.
     *
     * Locked jika:
     * 1. open_to tahun > tahun sekarang DAN ada periode aktif tahun sekarang
     * 2. Punya proposal
     */
    public function isLocked(Period $period): bool
    {
        // Kondisi 1: open_to tahun depan+ dan ada periode aktif tahun ini
        if ($this->isFutureYearWithActiveCurrent($period)) {
            return true;
        }

        // Kondisi 2: punya proposal
        if ($this->hasProposals($period)) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah open_to tahun > tahun sekarang DAN ada periode aktif tahun ini.
     */
    protected function isFutureYearWithActiveCurrent(Period $period): bool
    {
        if (!$period->open_to) {
            return false;
        }

        $currentYear = now()->year;
        $openToYear = $period->open_to->year;

        // Kalau open_to tahun <= tahun sekarang, tidak locked
        if ($openToYear <= $currentYear) {
            return false;
        }

        // Cek apakah ada periode AKTIF di tahun sekarang
        $hasActiveCurrentYear = Period::query()
            ->where('is_active', true)
            ->where('id', '!=', $period->id) // exclude diri sendiri
            ->whereYear('open_from', $currentYear)
            ->exists();

        return $hasActiveCurrentYear;
    }

    /**
     * Cek apakah periode bisa di-EDIT.
     * Bisa edit kalau tidak locked.
     */
    public function canEdit(Period $period): bool
    {
        return !$this->isLocked($period);
    }

    /**
     * Cek apakah periode bisa di-DELETE.
     * Bisa hapus kalau tidak locked DAN tidak punya proposal.
     */
    public function canDelete(Period $period): bool
    {
        return !$this->isLocked($period) && !$this->hasProposals($period);
    }

    /**
     * Cek apakah periode bisa di-AKTIFKAN.
     * Bisa aktifkan kalau tidak locked.
     */
    public function canActivate(Period $period): bool
    {
        return !$this->isLocked($period);
    }

    /**
     * Cek apakah periode sedang dalam rentang.
     */
    public function isOngoing(Period $period): bool
    {
        if (!$period->open_from || !$period->open_to) {
            return false;
        }

        return $period->open_from->copy()->startOfDay()->isPast() && $period->open_to->copy()->endOfDay()->isFuture();
    }
};
?>

<div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950 p-3 sm:p-4 lg:p-6">
    <div class="max-w-7xl mx-auto space-y-4">

        {{-- Header Section --}}
        <x-dashboard-header icon="calendar-days" title="Manage Periods" leading="Manage all the periods of your system." />

        {{-- Main Card --}}
        <div
            class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-zinc-950/50 border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- Toolbar --}}
            <div
                class="p-3 sm:p-4 border-b border-slate-200 dark:border-zinc-800 bg-gradient-to-r from-slate-50 to-white dark:from-zinc-900 dark:to-zinc-900/50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                    {{-- Left: Stats --}}
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex items-center justify-center w-5 h-5 rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                                <flux:icon.list-bullet class="size-4 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-[10px] font-medium text-slate-900 dark:text-zinc-400">
                                    {{ $periods->total() }} found
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Search & Action --}}
                    <div class="flex gap-2">
                        <div class="flex-1 sm:flex-none sm:w-64">
                            <x-input-search name="q" wire:model.live="search" id="search-periode"
                                placeholder="Cari periode..." class="w-full text-xs" />
                        </div>
                        <flux:button icon="plus" x-data x-on:click="$dispatch('add-period-modal')" variant="primary"
                            size="xs"
                            class="shrink-0 shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 transition-shadow text-xs">
                            <span class="hidden sm:inline">Tambah Periode</span>
                            <span class="sm:hidden">Tambah</span>
                        </flux:button>
                    </div>
                </div>
            </div>

            {{-- Table Container --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-zinc-900/50 border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Period</span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Status</span>
                            </th>
                            <th class="hidden lg:table-cell px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Open
                                    From</span>
                            </th>
                            <th class="hidden lg:table-cell px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Open
                                    To</span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-right">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Action</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                        @forelse ($periods as $period)
                            <tr
                                class="group transition-colors duration-150
                                {{ $this->isLocked($period)
                                    ? 'bg-slate-50/50 dark:bg-zinc-900/40 opacity-80'
                                    : 'hover:bg-slate-50 dark:hover:bg-zinc-800/50' }}">

                                {{-- Periode --}}
                                <td class="px-3 sm:px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex items-center justify-center w-8 h-8 rounded-lg
                                                    bg-gradient-to-br from-emerald-100 to-teal-50
                                                    dark:from-emerald-900/30 dark:to-teal-900/20
                                                    group-hover:scale-105 transition-transform">
                                            <flux:icon.calendar
                                                class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-xs font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                {{ $period->periode }}
                                                @if ($this->isLocked($period))
                                                    <flux:icon.lock-closed
                                                        class="size-3 text-rose-500 dark:text-rose-400 shrink-0" />
                                                @endif
                                            </p>
                                            <div class="lg:hidden mt-0.5 space-y-0.5">
                                                <p class="text-[10px] text-slate-500 dark:text-zinc-400">
                                                    <span class="font-medium">Buka:</span>
                                                    {{ $period->open_from?->format('d M Y') ?? '—' }}
                                                </p>
                                                <p class="text-[10px] text-slate-500 dark:text-zinc-400">
                                                    <span class="font-medium">Tutup:</span>
                                                    {{ $period->open_to?->format('d M Y') ?? '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-3 sm:px-4 py-2.5">
                                    <div class="flex flex-col gap-1 items-start">

                                        {{-- Status Aktif / Nonaktif --}}
                                        @if ($period->is_active)
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full
                                                        bg-emerald-100 dark:bg-emerald-900/30
                                                        text-emerald-700 dark:text-emerald-300
                                                        text-[10px] font-semibold shadow-sm w-fit">
                                                <span
                                                    class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full
                                                        bg-slate-100 dark:bg-zinc-800
                                                        text-slate-600 dark:text-zinc-400
                                                        text-[10px] font-medium w-fit">
                                                <span
                                                    class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-zinc-600"></span>
                                                Nonaktif
                                            </span>
                                        @endif

                                        {{-- Badge Locked / Proposal --}}
                                        @if ($this->isLocked($period))
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full
                                                        bg-rose-100 dark:bg-rose-900/30
                                                        text-rose-700 dark:text-rose-300
                                                        text-[10px] font-semibold w-fit">
                                                <flux:icon.lock-closed class="size-2.5" />
                                                Terkunci
                                            </span>
                                        @elseif ($this->hasProposals($period))
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full
                                                        bg-blue-100 dark:bg-blue-900/30
                                                        text-blue-700 dark:text-blue-300
                                                        text-[10px] font-medium w-fit">
                                                <flux:icon.document-text class="size-2.5" />
                                                {{ $period->proposals()->count() }} Proposal
                                            </span>
                                        @endif

                                    </div>
                                </td>

                                {{-- Open From (Desktop) --}}
                                <td class="hidden lg:table-cell px-3 sm:px-4 py-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon.clock class="size-3 text-slate-400 dark:text-zinc-500" />
                                        <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                                            {{ $period->open_from?->format('d M Y') ?? '—' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Open To (Desktop) --}}
                                <td class="hidden lg:table-cell px-3 sm:px-4 py-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon.clock class="size-3 text-slate-400 dark:text-zinc-500" />
                                        <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                                            {{ $period->open_to?->format('d M Y') ?? '—' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-3 sm:px-4 py-2.5 text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                            @class([
                                                'rounded-lg',
                                                'text-slate-300 dark:text-zinc-600 cursor-not-allowed' => $this->isLocked(
                                                    $period),
                                                'text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' => !$this->isLocked(
                                                    $period),
                                            ]) :disabled="$this->isLocked($period)" />

                                        <flux:menu class="min-w-[200px] text-xs">

                                            {{-- ══════════ LOCKED ══════════ --}}
                                            @if ($this->isLocked($period))
                                                <flux:menu.item icon="lock-closed" disabled
                                                    class="opacity-50 cursor-not-allowed">
                                                    <span class="flex items-center justify-between w-full">
                                                        Periode Terkunci
                                                        <flux:icon.lock-closed class="size-3" />
                                                    </span>
                                                </flux:menu.item>
                                                <flux:menu.item icon="eye" disabled
                                                    class="opacity-50 cursor-not-allowed">
                                                    Hanya Lihat
                                                </flux:menu.item>

                                                {{-- ══════════ UNLOCKED ══════════ --}}
                                            @else
                                                {{-- Aktifkan (hanya kalau belum aktif) --}}
                                                @if (!$period->is_active)
                                                    <flux:menu.item icon="check-circle"
                                                        wire:click="setActive({{ $period->id }})">
                                                        Aktifkan Periode
                                                    </flux:menu.item>
                                                @endif

                                                {{-- Edit --}}
                                                <flux:menu.item icon="pencil-square" x-data
                                                    x-on:click="$dispatch('open-edit-period', { id: {{ $period->id }} })">
                                                    Edit Periode
                                                </flux:menu.item>

                                                {{-- Hapus (hanya kalau tidak punya proposal) --}}
                                                @if (!$this->hasProposals($period))
                                                    <flux:menu.separator />
                                                    <flux:menu.item variant="danger" icon="trash"
                                                        wire:click="delete({{ $period->id }})"
                                                        wire:confirm="Yakin ingin menghapus periode ini?">
                                                        Hapus Periode
                                                    </flux:menu.item>
                                                @else
                                                    <flux:menu.separator />
                                                    <flux:menu.item variant="danger" icon="trash" disabled
                                                        class="opacity-50 cursor-not-allowed">
                                                        <span class="flex items-center justify-between w-full">
                                                            Hapus Periode
                                                            <flux:icon.lock-closed class="size-3" />
                                                        </span>
                                                    </flux:menu.item>
                                                @endif
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 sm:px-4 py-12">
                                    <div class="flex flex-col items-center justify-center gap-3 text-center">
                                        <div
                                            class="flex items-center justify-center w-14 h-14 rounded-xl bg-slate-100 dark:bg-zinc-800">
                                            <flux:icon.calendar-days
                                                class="size-7 text-slate-400 dark:text-zinc-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                                Belum ada periode
                                            </p>
                                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">
                                                Klik tombol "Tambah Periode" untuk membuat periode baru
                                            </p>
                                        </div>
                                        {{-- FIXED: wire:click dispatch event, bukan create() --}}
                                        <flux:button icon="plus" x-data x-on:click="$dispatch('add-period-modal')"
                                            variant="primary" size="xs" class="mt-1 text-xs">
                                            Tambah Periode Pertama
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($periods->hasPages())
                <div
                    class="px-3 sm:px-4 py-3 border-t border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900/50">
                    {{ $periods->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modals --}}
    <livewire:admin.periods.add-period />
    <livewire:admin.periods.edit-period />
</div>
