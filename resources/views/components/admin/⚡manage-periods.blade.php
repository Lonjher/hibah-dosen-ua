<?php

use App\Models\Period;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Periods')] class extends Component {
    use WithPagination;

    public $search;
    public $periode = '';
    public $is_active = false;
    public $open_from = '';
    public $open_to = '';
    public $editingId = null;
    public $showModal = false;

    protected $rules = [
        'periode' => 'required|string|max:255',
        'open_from' => 'nullable|date',
        'open_to' => 'nullable|date|after_or_equal:open_from',
        'is_active' => 'boolean',
    ];

    public function with()
    {
        $periods = null;
        if ($this->search) {
            $periods = Period::where('periode', 'like', '%' . $this->search . '%')
                ->latest()
                ->paginate(10);
        } else {
            $periods = Period::orderByDesc('open_from')->paginate(10);
        }
        return [
            'periods' => $periods,
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(Period $period)
    {
        $this->editingId = $period->id;
        $this->periode = $period->periode;
        $this->is_active = (bool) $period->is_active;
        $this->open_from = $period->open_from?->format('Y-m-d\TH:i');
        $this->open_to = $period->open_to?->format('Y-m-d\TH:i');
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->is_active) {
            Period::where('is_active', true)->update(['is_active' => false]);
        }

        if ($this->editingId) {
            $period = Period::findOrFail($this->editingId);
            $period->update([
                'periode' => $this->periode,
                'is_active' => $this->is_active,
                'open_from' => $this->open_from ?: null,
                'open_to' => $this->open_to ?: null,
            ]);
            Flux::toast('Periode berhasil diperbarui.');
        } else {
            Period::create([
                'periode' => $this->periode,
                'is_active' => $this->is_active,
                'open_from' => $this->open_from ?: null,
                'open_to' => $this->open_to ?: null,
            ]);
            Flux::toast('Periode baru berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(Period $period)
    {
        if ($period->proposals()->exists()) {
            Flux::toast('Tidak dapat menghapus periode yang memiliki proposal.', variant: 'danger');
            return;
        }

        $period->delete();
        Flux::toast('Periode dihapus.');
    }

    public function setActive(Period $period)
    {
        Period::where('is_active', true)->update(['is_active' => false]);
        $period->update(['is_active' => true]);
        Flux::toast("Periode '{$period->periode}' diaktifkan.");
    }

    private function resetForm()
    {
        $this->reset(['periode', 'is_active', 'open_from', 'open_to', 'editingId']);
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
                                    {{ $periods->total() }} found</p>
                            </div>
                        </div>

                    </div>

                    {{-- Right: Search & Action --}}
                    <div class="flex gap-2">
                        <div class="flex-1 sm:flex-none sm:w-64">
                            <x-input-search name="q" wire:model.live="search" id="search-periode"
                                placeholder="Cari periode..." class="w-full text-xs" />
                        </div>
                        <flux:button icon="plus" x-data x-on:click="$dispatch('add-period-modal')" variant="primary" size="xs"
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
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Period
                                </span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Status
                                </span>
                            </th>
                            <th class="hidden lg:table-cell px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Open From
                                </span>
                            </th>
                            <th class="hidden lg:table-cell px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Open To
                                </span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-right">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Action
                                </span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                        @forelse ($periods as $period)
                            <tr
                                class="group hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors duration-150">

                                {{-- Periode --}}
                                <td class="px-3 sm:px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/20 group-hover:scale-105 transition-transform">
                                            <flux:icon.calendar
                                                class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-900 dark:text-white">
                                                {{ $period->periode }}
                                            </p>
                                            {{-- Mobile: Show dates here --}}
                                            <div class="lg:hidden mt-0.5 space-y-0.5">
                                                <p class="text-[10px] text-slate-500 dark:text-zinc-400">
                                                    <span class="font-medium">Buka:</span>
                                                    {{ $period->open_from?->format('d M Y H:i') ?? '—' }}
                                                </p>
                                                <p class="text-[10px] text-slate-500 dark:text-zinc-400">
                                                    <span class="font-medium">Tutup:</span>
                                                    {{ $period->open_to?->format('d M Y H:i') ?? '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-3 sm:px-4 py-2.5">
                                    @if ($period->is_active)
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-[10px] font-semibold shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 text-[10px] font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-zinc-600"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                {{-- Waktu Buka (Desktop) --}}
                                <td class="hidden lg:table-cell px-3 sm:px-4 py-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon.clock class="size-3 text-slate-400 dark:text-zinc-500" />
                                        <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                                            {{ $period->open_from?->format('d M Y H:i') ?? '—' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Waktu Tutup (Desktop) --}}
                                <td class="hidden lg:table-cell px-3 sm:px-4 py-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon.clock class="size-3 text-slate-400 dark:text-zinc-500" />
                                        <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                                            {{ $period->open_to?->format('d M Y H:i') ?? '—' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-3 sm:px-4 py-2.5 text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                            class="rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800" />
                                        <flux:menu class="min-w-[160px] text-xs">
                                            @if (!$period->is_active)
                                                <flux:menu.item icon="check-circle"
                                                    wire:click="setActive({{ $period->id }})">
                                                    Aktifkan Periode
                                                </flux:menu.item>
                                            @endif

                                            <flux:menu.item icon="pencil-square" wire:click="edit({{ $period->id }})">
                                                Edit Periode
                                            </flux:menu.item>

                                            <flux:menu.separator />

                                            <flux:menu.item variant="danger" icon="trash"
                                                wire:click="delete({{ $period->id }})"
                                                wire:confirm="Yakin ingin menghapus periode ini?">
                                                Hapus Periode
                                            </flux:menu.item>
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
                                            <flux:icon.calendar-days class="size-7 text-slate-400 dark:text-zinc-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                                Belum ada periode
                                            </p>
                                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">
                                                Klik tombol "Tambah Periode" untuk membuat periode baru
                                            </p>
                                        </div>
                                        <flux:button icon="plus" wire:click="create" variant="primary" size="xs"
                                            class="mt-1 text-xs">
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

    {{-- Modal Form --}}
    <livewire:admin.periods.add-period/>
</div>
