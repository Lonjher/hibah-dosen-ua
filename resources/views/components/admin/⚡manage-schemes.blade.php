<?php

use App\Models\ResearchScheme;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Schemes')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /* ============================================================
     |  DELETE FLOW (modal confirmation)
     ============================================================ */

    public function confirmDelete(int $id): void
    {
        $scheme = ResearchScheme::find($id);

        if (! $scheme) {
            Flux::toast('Scheme not found.', variant: 'danger');
            return;
        }

        if ($scheme->proposals()->exists()) {
            Flux::toast('Cannot delete a scheme that has proposals.', variant: 'danger');
            return;
        }

        $this->dispatch(
            'confirm-delete',
            title: 'Delete Scheme?',
            message: 'You are about to delete:',
            subject: $scheme->name,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
            cancelLabel: 'Cancel',
            action: 'deleteScheme',
            payload: ['id' => $scheme->id],
        );
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deleteScheme') {
            $this->deleteScheme($payload['id'] ?? null);
        }
    }

    public function deleteScheme(?int $id): void
    {
        if (! $id) return;

        $scheme = ResearchScheme::find($id);
        if (! $scheme) return;

        if ($scheme->proposals()->exists()) {
            Flux::toast('Cannot delete a scheme that has proposals.', variant: 'danger');
            return;
        }

        try {
            $name = $scheme->name;
            $scheme->delete();
            Flux::toast("Scheme '{$name}' deleted.", variant: 'success');
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to delete scheme.', variant: 'danger');
        }
    }

    /* ============================================================
     |  QUERY
     ============================================================ */

    public function with(): array
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
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- ══════════ HEADER ══════════ --}}
        <x-dashboard-header icon="beaker" title="Manage Schemes"
            leading="Manage all research & community service schemes." />

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
                        <flux:icon.beaker class="size-3 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-[10.5px] font-semibold text-emerald-700 dark:text-emerald-300">
                            {{ $schemes->total() }}
                        </span>
                        <span class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70">
                            schemes
                        </span>
                    </div>

                    {{-- Right: search + new --}}
                    <div class="flex items-center gap-1.5 w-full sm:w-auto">
                        <div class="flex-1 sm:flex-none sm:w-56">
                            <x-input-search name="q" wire:model.live="search" id="search-scheme"
                                placeholder="Search schemes..."
                                class="w-full !text-[10.5px]" />
                        </div>
                        <flux:button variant="primary"
                            x-data x-on:click="$dispatch('open-add-scheme')"
                            class="shrink-0 !text-[10.5px]">
                            New Item
                        </flux:button>
                    </div>
                </div>
            </div>

            {{-- ─────── TABLE ─────── --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px]">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-zinc-900/50
                                   border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Scheme
                            </th>
                            <th class="hidden md:table-cell px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Budget Limit
                            </th>
                            <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Status
                            </th>
                            <th class="px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                        @forelse ($schemes as $scheme)
                            <tr wire:key="scheme-{{ $scheme->id }}"
                                class="group transition-colors duration-150
                                       hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                {{-- SCHEME --}}
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-md shrink-0
                                                    bg-gradient-to-br from-emerald-100 to-teal-50
                                                    dark:from-emerald-900/30 dark:to-teal-900/20
                                                    flex items-center justify-center
                                                    ring-1 ring-white/40 dark:ring-zinc-800/40
                                                    group-hover:scale-105 transition-transform">
                                            <flux:icon.beaker
                                                class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[11.5px] font-semibold leading-tight truncate
                                                      text-slate-900 dark:text-white">
                                                {{ $scheme->name }}
                                            </p>
                                            <div class="mt-0.5 flex items-center gap-1.5
                                                        text-[9.5px] text-slate-500 dark:text-zinc-500">
                                                <span class="px-1 rounded bg-slate-100 dark:bg-zinc-800
                                                             text-slate-600 dark:text-zinc-400 font-semibold">
                                                    {{ $scheme->code }}
                                                </span>
                                                {{-- Budget limit on mobile --}}
                                                <span class="md:hidden">·</span>
                                                <span class="md:hidden">
                                                    Rp {{ number_format((int) $scheme->budget_limit, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- BUDGET --}}
                                <td class="hidden md:table-cell px-3 py-2">
                                    <span class="text-[10.5px] font-medium
                                                 text-slate-700 dark:text-zinc-300">
                                        Rp {{ number_format((int) $scheme->budget_limit, 0, ',', '.') }}
                                    </span>
                                </td>

                                {{-- STATUS --}}
                                <td class="px-3 py-2">
                                    @if ($scheme->is_active)
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
                                </td>

                                {{-- ACTION --}}
                                <td class="px-3 py-2 text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                            class="!p-1 rounded-md text-slate-400
                                                   hover:bg-slate-100 hover:text-slate-600
                                                   dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300
                                                   opacity-60 group-hover:opacity-100
                                                   transition-opacity" />

                                        <flux:menu class="!text-[11px]">
                                            <flux:menu.item icon="pencil-square" x-data
                                                x-on:click="$dispatch('open-edit-scheme', { id: {{ $scheme->id }} })">
                                                Edit Scheme
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item variant="danger" icon="trash"
                                                wire:click="confirmDelete({{ $scheme->id }})">
                                                Delete Scheme
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>

                        @empty
                            {{-- EMPTY STATE ── --}}
                            <tr>
                                <td colspan="4" class="px-4 py-10">
                                    <div class="flex flex-col items-center gap-2 text-center">
                                        <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-zinc-800
                                                    flex items-center justify-center">
                                            <flux:icon.beaker class="size-5 text-slate-400 dark:text-zinc-600" />
                                        </div>
                                        <div>
                                            <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                No schemes yet
                                            </p>
                                            <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                Click "New" to create your first scheme.
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
            @if ($schemes->hasPages())
                <div class="px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                            bg-slate-50/50 dark:bg-zinc-900/50">
                    {{ $schemes->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ MODALS ══════════ --}}
    <x-confirm-delete />
    <livewire:admin.schemes.add-scheme />
    <livewire:admin.schemes.edit-scheme />
</div>
