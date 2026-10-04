<?php

use App\Models\Informations;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Informations')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';
    public string $statusFilter = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function togglePublish(int $id): void
    {
        $info = Informations::find($id);
        if (! $info) return;
        $info->update([
            'is_published' => ! $info->is_published,
            'published_at' => ! $info->is_published ? now() : $info->published_at,
        ]);
        Flux::toast($info->is_published ? 'Informasi dipublikasikan.' : 'Informasi disembunyikan.', variant: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $info = Informations::find($id);
        if (! $info) {
            Flux::toast('Informasi tidak ditemukan.', variant: 'danger');
            return;
        }
        $this->dispatch(
            'confirm-delete',
            title: 'Hapus Informasi?',
            message: 'Anda akan menghapus:',
            subject: $info->title,
            note: 'Tindakan ini tidak dapat dibatalkan.',
            confirmLabel: 'Hapus',
            cancelLabel: 'Batal',
            action: 'deleteInformation',
            payload: ['id' => $info->id],
        );
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deleteInformation') {
            $this->deleteInformation($payload['id'] ?? null);
        }
    }

    public function deleteInformation(?int $id): void
    {
        if (! $id) return;
        $info = Informations::find($id);
        if (! $info) return;
        try {
            $info->delete();
            Flux::toast('Informasi berhasil dihapus.', variant: 'success');
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Gagal menghapus informasi.', variant: 'danger');
        }
    }

    public function with(): array
    {
        $informations = Informations::query()
            ->with('author')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('content', 'like', "%{$this->search}%")))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter === 'published', fn ($q) => $q->where('is_published', true))
            ->when($this->statusFilter === 'draft',     fn ($q) => $q->where('is_published', false))
            ->when($this->statusFilter === 'expired',   fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<', now()))
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->paginate(10);

        return ['informations' => $informations];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- ══════════ HEADER ══════════ --}}
        <x-dashboard-header icon="megaphone" title="Manage Informations"
            leading="Kelola informasi untuk ditampilkan ke dashboard user." />

        {{-- ══════════ MAIN CARD ══════════ --}}
        <div class="bg-white dark:bg-zinc-900 rounded-full
                    shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50
                    border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- ─────── TOOLBAR ─────── --}}
            <div class="px-2.5 sm:px-3 py-2
                        border-b border-slate-200 dark:border-zinc-800
                        bg-slate-50/50 dark:bg-zinc-900/50">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">

                    {{-- Left: stats + filters --}}
                    <div class="flex flex-wrap items-center gap-1.5">
                        <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md
                                    bg-sky-50 dark:bg-sky-900/20
                                    border border-sky-100 dark:border-sky-900/40">
                            <flux:icon.megaphone class="size-3 text-sky-600 dark:text-sky-400" />
                            <span class="text-[10.5px] font-semibold text-sky-700 dark:text-sky-300">
                                {{ $informations->total() }}
                            </span>
                            <span class="text-[10px] text-sky-600/70 dark:text-sky-400/70">
                                info
                            </span>
                        </div>

                        <x-select name="typeFilter" wire:model.live="typeFilter">
                            <option value="">Semua Tipe</option>
                            <option value="info">Info</option>
                            <option value="success">Sukses</option>
                            <option value="warning">Peringatan</option>
                            <option value="danger">Penting</option>
                        </x-select>

                        <x-select name="statusFilter" wire:model.live="statusFilter" placeholder="Semua Status">
                            <option value="">Semua Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="expired">Kadaluarsa</option>
                        </x-select>
                    </div>

                    {{-- Right: search + new --}}
                    <div class="flex items-center gap-1.5 w-full lg:w-auto">
                        <div class="flex-1 lg:flex-none lg:w-56">
                            <x-input-search name="q" wire:model.live="search" id="search-info"
                                placeholder="Cari informasi..."
                                class="w-full !text-[10.5px]" />
                        </div>
                        <flux:button variant="primary"
                            x-data x-on:click="$dispatch('open-add-information')"
                            class="shrink-0 !text-[10.5px]">
                            New Item
                        </flux:button>
                    </div>
                </div>
            </div>

            {{-- ─────── LIST ─────── --}}
            <div class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                @forelse ($informations as $info)
                    @php
                        $type = $info->typeMeta();
                        $status = $info->statusMeta();
                    @endphp

                    <div wire:key="info-{{ $info->id }}"
                         class="group px-2.5 sm:px-3 py-2
                                hover:bg-slate-50/70 dark:hover:bg-zinc-800/40
                                transition-colors">

                        <div class="flex items-start gap-2.5">

                            {{-- Icon --}}
                            <div class="shrink-0 w-7 h-7 rounded-md
                                        {{ $type['bg'] }}
                                        flex items-center justify-center
                                        ring-1 ring-white/40 dark:ring-zinc-800/40">
                                <flux:icon :name="$type['icon']"
                                    class="size-3.5 {{ $type['icon_class'] }}" />
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">

                                {{-- Title row --}}
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">

                                            <h3 class="text-[12px] font-semibold leading-tight
                                                       text-slate-900 dark:text-white
                                                       line-clamp-1">
                                                {{ $info->title }}
                                            </h3>

                                            <span class="text-[9px] px-1.5 py-px rounded-full font-semibold
                                                         {{ $status['class'] }}">
                                                {{ $status['label'] }}
                                            </span>

                                            @if ($info->priority > 0)
                                                <span class="text-[9px] px-1.5 py-px rounded-full
                                                             bg-violet-100 text-violet-700
                                                             dark:bg-violet-900/30 dark:text-violet-300
                                                             font-semibold">
                                                    P{{ $info->priority }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Preview --}}
                                        <p class="mt-0.5 text-[10.5px] leading-snug
                                                  text-slate-500 dark:text-zinc-400
                                                  line-clamp-2">
                                            {{ Str::limit(strip_tags($info->content), 140) }}
                                        </p>

                                        {{-- Meta --}}
                                        <div class="mt-1 flex items-center gap-1.5 flex-wrap
                                                    text-[9.5px] text-slate-400 dark:text-zinc-500">
                                            <span class="font-medium text-slate-500 dark:text-zinc-400">
                                                {{ $info->author?->full_name ?? '—' }}
                                            </span>
                                            <span class="w-0.5 h-0.5 rounded-full bg-current"></span>
                                            <span>{{ $info->created_at->diffForHumans() }}</span>
                                            @if ($info->expires_at)
                                                <span class="w-0.5 h-0.5 rounded-full bg-current"></span>
                                                <span class="inline-flex items-center gap-0.5">
                                                    <flux:icon.clock class="size-2.5" />
                                                    {{ $info->expires_at->format('d M Y') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Menu --}}
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                            class="!p-1 rounded-md text-slate-400
                                                   hover:bg-slate-100 hover:text-slate-600
                                                   dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300
                                                   opacity-60 group-hover:opacity-100
                                                   transition-opacity" />

                                        <flux:menu class="min-w-[190px] !text-[11px]">
                                            <flux:menu.item icon="pencil-square" x-data
                                                x-on:click="$dispatch('open-edit-information', { id: {{ $info->id }} })">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.item
                                                icon="{{ $info->is_published ? 'eye-slash' : 'eye' }}"
                                                wire:click="togglePublish({{ $info->id }})">
                                                {{ $info->is_published ? 'Sembunyikan' : 'Publikasikan' }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item variant="danger" icon="trash"
                                                wire:click="confirmDelete({{ $info->id }})">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>
                        </div>
                    </div>

                @empty
                    {{-- EMPTY STATE ── --}}
                    <div class="px-4 py-10 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-zinc-800
                                        flex items-center justify-center">
                                <flux:icon.megaphone class="size-5 text-slate-400 dark:text-zinc-600" />
                            </div>
                            <div>
                                <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                    Belum ada informasi
                                </p>
                                <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                    Klik "New" untuk menambahkan informasi pertama.
                                </p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- ─────── PAGINATION ─────── --}}
            @if ($informations->hasPages())
                <div class="px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                            bg-slate-50/50 dark:bg-zinc-900/50">
                    {{ $informations->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ MODALS ══════════ --}}
    <x-confirm-delete />
    <livewire:admin.informations.add-information />
    <livewire:admin.informations.edit-information />
</div>
