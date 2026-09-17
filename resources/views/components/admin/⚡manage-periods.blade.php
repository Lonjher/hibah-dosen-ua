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
                ->latest()->paginate(10);
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
<div class="p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <x-dashboard-header icon="calendar-days" title="Kelola Periode Hibah"
            leading="Atur periode pengajuan hibah penelitian dan pengabdian." />
        <flux:button icon="plus" wire:click="create" variant="primary" size="sm" class="shrink-0 text-xs">Tambah
            Periode</flux:button>
    </div>

    {{-- Tabel Periode + Search terintegrasi --}}
    <div
        class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl border border-white/80 dark:border-zinc-800 shadow-sm overflow-hidden">

        {{-- ═══════════ Header: Info Record + Search ═══════════ --}}
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between
               gap-3 p-4 border-b border-slate-100 dark:border-zinc-800">

            {{-- Info jumlah record --}}
            <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-zinc-400 min-w-0">
                @if ($periods->total() > 0)
                    <span class="truncate">
                        Menampilkan
                        <span class="font-semibold text-slate-700 dark:text-zinc-200">
                            {{ $periods->firstItem() }}–{{ $periods->lastItem() }}
                        </span>
                        dari
                        <span class="font-semibold text-slate-700 dark:text-zinc-200">
                            {{ $periods->total() }}
                        </span>
                    </span>
                @else
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                           bg-slate-100 text-slate-500 font-semibold
                           dark:bg-zinc-800 dark:text-zinc-400">
                        <flux:icon.list-bullet class="size-3" />
                        Tidak ada periode
                    </span>
                @endif
            </div>

            {{-- Search input (component tetap, tidak diubah) --}}
            <x-input-search name="q" wire:model.live="search" id="search-periode" placeholder="Cari periode..." class="!flex w-full" />
        </div>

        {{-- ═══════════ Tabel ═══════════ --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-190 text-xs">
                <thead
                    class="bg-emerald-50/50 dark:bg-emerald-900/20 text-left text-[11px]
                       uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Periode</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Waktu Buka</th>
                        <th class="px-4 py-3 font-semibold">Waktu Tutup</th>
                        <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @forelse ($periods as $period)
                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-zinc-100">
                                {{ $period->periode }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($period->is_active)
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                           bg-emerald-100 text-emerald-700 text-[11px] font-semibold
                                           dark:bg-emerald-900/40 dark:text-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full
                                           bg-slate-100 text-slate-500 text-[11px] font-medium
                                           dark:bg-zinc-800 dark:text-zinc-400">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                {{ $period->open_from?->format('d M Y H:i') ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                {{ $period->open_to?->format('d M Y H:i') ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                        title="Menu aksi"
                                        class="rounded-lg text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-700/60" />
                                    <flux:menu>
                                        @if (!$period->is_active)
                                            <flux:menu.item icon="check-circle"
                                                wire:click="setActive({{ $period->id }})">
                                                Aktifkan
                                            </flux:menu.item>
                                        @endif

                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $period->id }})">
                                            Edit
                                        </flux:menu.item>

                                        <flux:menu.separator />

                                        <flux:menu.item variant="danger" icon="trash"
                                            wire:click="delete({{ $period->id }})"
                                            wire:confirm="Yakin ingin menghapus periode ini?">
                                            Hapus
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon.calendar-days class="size-8 text-slate-300 dark:text-zinc-700" />
                                    <span>Belum ada periode. Klik "Tambah Periode" untuk membuat.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ═══════════ Pagination ═══════════ --}}
        @if ($periods->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-zinc-800">
                {{ $periods->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" :title="$editingId ? 'Edit Periode' : 'Tambah Periode'"
        description="Lengkapi informasi periode hibah." size="lg">
        <form wire:submit="save" class="space-y-4">
            {{-- Nama Periode --}}
            <div>
                <flux:label for="periode">Nama Periode</flux:label>
                <flux:input wire:model="periode" id="periode" placeholder="Contoh: 2025/2026 Gel. I" required
                    size="sm" icon="calendar-days" />
                @error('periode')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Waktu Buka & Tutup --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:label for="open_from">Waktu Buka</flux:label>
                    <flux:input type="datetime-local" wire:model="open_from" id="open_from" size="sm"
                        icon="clock" />
                </div>
                <div>
                    <flux:label for="open_to">Waktu Tutup</flux:label>
                    <flux:input type="datetime-local" wire:model="open_to" id="open_to" size="sm"
                        icon="clock" />
                </div>
            </div>

            {{-- Checkbox Aktif --}}
            <div>
                <flux:checkbox wire:model="is_active" label="Jadikan periode aktif" />
                @error('is_active')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" size="sm" wire:click="$set('showModal', false)">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm">
                    {{ $editingId ? 'Simpan' : 'Tambah' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
