<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ResearchScheme;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public $scheme_name = '';
    public $scheme_code = '';
    public $scheme_description = '';
    public $budget_limit = '';
    public $is_active = false;
    public $editingId = null;
    public $showModal = false;

    protected $rules = [
        'scheme_name' => 'required|string|max:255',
        'scheme_code' => 'required|string|max:50|unique:research_schemes,scheme_code',
        'scheme_description' => 'nullable|string|max:1000',
        'budget_limit' => 'required|numeric|min:0',
        'is_active' => 'boolean',
    ];

    public function render()
    {
        $schemes = ResearchScheme::orderBy('scheme_code')->paginate(10);
        return $this->view([
            'schemes' => $schemes,
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(ResearchScheme $scheme)
    {
        $this->editingId = $scheme->id;
        $this->scheme_name = $scheme->scheme_name;
        $this->scheme_code = $scheme->scheme_code;
        $this->scheme_description = $scheme->scheme_description;
        $this->budget_limit = $scheme->budget_limit;
        $this->is_active = (bool) $scheme->is_active;
        $this->showModal = true;
    }

    public function save()
    {
        // Untuk edit, pengecualian unique
        $this->rules['scheme_code'] = 'required|string|max:50|unique:research_schemes,scheme_code,' . ($this->editingId ?? 'NULL');
        $this->validate();

        if ($this->editingId) {
            $scheme = ResearchScheme::findOrFail($this->editingId);
            $scheme->update([
                'scheme_name' => $this->scheme_name,
                'scheme_code' => $this->scheme_code,
                'scheme_description' => $this->scheme_description,
                'budget_limit' => $this->budget_limit,
                'is_active' => $this->is_active,
            ]);
            Flux::toast('Skema berhasil diperbarui.');
        } else {
            ResearchScheme::create([
                'scheme_name' => $this->scheme_name,
                'scheme_code' => $this->scheme_code,
                'scheme_description' => $this->scheme_description,
                'budget_limit' => $this->budget_limit,
                'is_active' => $this->is_active,
            ]);
            Flux::toast('Skema baru berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(ResearchScheme $scheme)
    {
        if ($scheme->proposals()->exists()) {
            Flux::toast('Tidak dapat menghapus skema yang memiliki proposal.', variant: 'danger');
            return;
        }

        $scheme->delete();
        Flux::toast('Skema dihapus.');
    }

    public function toggleActive(ResearchScheme $scheme)
    {
        $scheme->update(['is_active' => !$scheme->is_active]);
        $status = $scheme->is_active ? 'diaktifkan' : 'dinonaktifkan';
        Flux::toast("Skema '{$scheme->scheme_name}' {$status}.");
    }

    private function resetForm()
    {
        $this->reset(['scheme_name', 'scheme_code', 'scheme_description', 'budget_limit', 'is_active', 'editingId']);
    }
};
?>

<div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950 p-3 sm:p-4 lg:p-6">

    <div class="max-w-7xl mx-auto space-y-4">
        <x-dashboard-header icon="rectangle-group" title="Kelola Skema Hibah"
            leading="Atur skema penelitian dan pengabdian yang tersedia." />

        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-zinc-950/50 border border-slate-200 dark:border-zinc-800 overflow-hidden">
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
                                    {{ $schemes->total() }} found</p>
                            </div>
                        </div>

                    </div>

                    {{-- Right: Search & Action --}}
                    <div class="flex gap-2">
                        <div class="flex-1 sm:flex-none sm:w-64">
                            <x-input-search name="q" wire:model.live="search" id="search-periode"
                                placeholder="Cari periode..." class="w-full text-xs" />
                        </div>
                        <flux:button icon="plus" wire:click="create" variant="primary" size="xs"
                            class="shrink-0 shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 transition-shadow text-xs rounded-full">
                            <span class="hidden sm:inline">Tambah Periode</span>
                            <span class="sm:hidden">Tambah</span>
                        </flux:button>
                    </div>
                </div>
            </div>

            <!-- Tabel Skema -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead
                        class="bg-emerald-50/50 dark:bg-emerald-900/20 text-left text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <tr class="bg-slate-50 dark:bg-zinc-900/50 border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Scheme Name
                                </span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Code
                                </span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Description
                                </span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Max. Cost
                                </span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Status
                                </span>
                            </th>
                            <th class="px-4 py-3 font-semibold text-right">
                                <span
                                    class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                                    Action
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                        @forelse($schemes as $scheme)
                            <tr class="group hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors duration-150">
                                <td class="px-3 sm:px-4 py-2.5 text-xs">
                                    {{ $scheme->scheme_name }}
                                </td>
                                <td class="px-3 sm:px-4 py-2.5">
                                    <span
                                        class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ $scheme->scheme_code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300 max-w-[250px] truncate">
                                    {{ $scheme->scheme_description ?: '—' }}
                                </td>
                                <td class="px-3 sm:px-4 py-2.5 text-xs">
                                    Rp {{ number_format((float) $scheme->budget_limit, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($scheme->is_active)
                                        <span
                                            class="text-[10px] inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="text-[10px] inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-zinc-800 dark:text-zinc-400">
                                            Non-active
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($scheme->is_active)
                                            <flux:button size="sm" icon="x-circle"
                                                wire:click="toggleActive({{ $scheme->id }})" title="Nonaktifkan"
                                                class="text-slate-500 hover:bg-slate-100 dark:hover:bg-zinc-800" />
                                        @else
                                            <flux:button size="sm" icon="check-circle"
                                                wire:click="toggleActive({{ $scheme->id }})" title="Aktifkan"
                                                class="text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/30" />
                                        @endif
                                        <flux:button size="sm" icon="pencil-square"
                                            wire:click="edit({{ $scheme->id }})"
                                            class="text-slate-500 hover:bg-slate-100 dark:hover:bg-zinc-800" />
                                        <flux:button size="sm" icon="trash" wire:click="delete({{ $scheme->id }})"
                                            wire:confirm="Yakin ingin menghapus skema ini?"
                                            class="text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-900/30" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                        <tr>
                                <td colspan="6" class="px-3 sm:px-4 py-12">
                                    <div class="flex flex-col items-center justify-center gap-3 text-center">
                                        <div
                                            class="flex items-center justify-center w-14 h-14 rounded-xl bg-slate-100 dark:bg-zinc-800">
                                            <flux:icon.rectangle-group class="size-7 text-slate-400 dark:text-zinc-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                                No Scheme Available
                                            </p>
                                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">
                                                Click "Add Period" button to create new period
                                            </p>
                                        </div>
                                        <flux:button icon="plus" wire:click="create" variant="primary" size="xs"
                                            class="mt-1 text-xs">
                                            Add New Period
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($schemes->hasPages())
                <div class="px-4 py-3 border-t border-slate-100 dark:border-zinc-800">
                    {{ $schemes->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" :title="$editingId ? 'Edit Skema' : 'Tambah Skema'"
        description="Lengkapi informasi skema hibah." size="lg">
        <form wire:submit="save" class="space-y-4">
            <div>
                <flux:label for="scheme_name">Nama Skema</flux:label>
                <flux:input wire:model="scheme_name" id="scheme_name" placeholder="Contoh: Penelitian Dasar"
                    size="sm" required />
                @error('scheme_name')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:label for="scheme_code">Kode Skema</flux:label>
                    <flux:input wire:model="scheme_code" id="scheme_code" placeholder="Contoh: PD-01" size="sm"
                        required />
                    @error('scheme_code')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <flux:label for="budget_limit">Batas Anggaran (Rp)</flux:label>
                    <flux:input type="number" step="0.01" min="0" wire:model="budget_limit" id="budget_limit"
                        placeholder="0" size="sm" required />
                    @error('budget_limit')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div>
                <flux:label for="scheme_description">Deskripsi</flux:label>
                <flux:textarea wire:model="scheme_description" id="scheme_description" rows="3"
                    placeholder="Jelaskan skema ini..." size="sm" />
                @error('scheme_description')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <flux:checkbox wire:model="is_active" label="Skema aktif" />
                @error('is_active')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" size="sm" wire:click="$set('showModal', false)">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm">{{ $editingId ? 'Simpan' : 'Tambah' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
