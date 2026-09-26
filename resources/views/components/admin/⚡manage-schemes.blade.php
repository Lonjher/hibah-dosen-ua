<?php

use App\Models\ResearchScheme;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Schemes')] class extends Component {
    use WithPagination;

    public $search = '';

    public function with()
    {
        $schemes = ResearchScheme::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(10);

        return ['schemes' => $schemes];
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
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950 p-3 sm:p-4 lg:p-6">
    <div class="max-w-7xl mx-auto space-y-4">

        <x-dashboard-header icon="beaker" title="Manage Schemes"
            leading="Manage all research & community service schemes." />

        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-zinc-950/50 border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- Toolbar --}}
            <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-zinc-800 bg-gradient-to-r from-slate-50 to-white dark:from-zinc-900 dark:to-zinc-900/50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                    <div class="flex items-center gap-2">
                        <div class="flex items-center justify-center w-5 h-5 rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                            <flux:icon.list-bullet class="size-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <p class="text-[10px] font-medium text-slate-900 dark:text-zinc-400">
                            {{ $schemes->total() }} found
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1 sm:flex-none sm:w-64">
                            <x-input-search name="q" wire:model.live="search" id="search-scheme"
                                placeholder="Cari skema..." class="w-full text-xs" />
                        </div>
                        <flux:button icon="plus" x-data
                            x-on:click="$dispatch('open-add-scheme')"
                            variant="primary" size="xs"
                            class="shrink-0 shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 transition-shadow text-xs">
                            <span class="hidden sm:inline">Tambah Skema</span>
                            <span class="sm:hidden">Tambah</span>
                        </flux:button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-zinc-900/50 border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Scheme</span>
                            </th>
                            <th class="hidden md:table-cell px-3 sm:px-4 py-2.5 text-left">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Budget Limit</span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Status</span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-right">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Action</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                        @forelse ($schemes as $scheme)
                            <tr class="group hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors duration-150">

                                <td class="px-3 sm:px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/20 group-hover:scale-105 transition-transform">
                                            <flux:icon.beaker class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-900 dark:text-white">
                                                {{ $scheme->name }}
                                            </p>
                                            <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                {{ $scheme->code }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="hidden md:table-cell px-3 sm:px-4 py-2.5">
                                    <span class="text-[11px] font-medium text-slate-700 dark:text-zinc-300">
                                        Rp {{ number_format((int) $scheme->budget_limit, 0, ',', '.') }}
                                    </span>
                                </td>

                                <td class="px-3 sm:px-4 py-2.5">
                                    @if ($scheme->is_active)
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-[10px] font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 text-[10px] font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="px-3 sm:px-4 py-2.5 text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                            class="rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800" />

                                        <flux:menu class="min-w-[160px] text-xs">
                                            <flux:menu.item icon="pencil-square" x-data
                                                x-on:click="$dispatch('open-edit-scheme', { id: {{ $scheme->id }} })">
                                                Edit Skema
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item variant="danger" icon="trash"
                                                wire:click="delete({{ $scheme->id }})"
                                                wire:confirm="Yakin ingin menghapus skema ini?">
                                                Hapus Skema
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 sm:px-4 py-12">
                                    <div class="flex flex-col items-center justify-center gap-3 text-center">
                                        <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-slate-100 dark:bg-zinc-800">
                                            <flux:icon.beaker class="size-7 text-slate-400 dark:text-zinc-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Belum ada skema</p>
                                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">
                                                Klik "Tambah Skema" untuk membuat skema baru
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($schemes->hasPages())
                <div class="px-3 sm:px-4 py-3 border-t border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900/50">
                    {{ $schemes->links() }}
                </div>
            @endif
        </div>
    </div>

    <livewire:admin.schemes.add-scheme />
    <livewire:admin.schemes.edit-scheme />
</div>
