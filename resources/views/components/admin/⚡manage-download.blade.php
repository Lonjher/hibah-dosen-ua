<?php

use App\Models\Download;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Downloads')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }
    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $dl = Download::find($id);
        if (!$dl) {
            return;
        }
        $dl->update(['is_active' => !$dl->is_active]);
        Flux::toast($dl->is_active ? 'File activated.' : 'File deactivated.', variant: 'success');
    }

    /**
     * Admin preview/download file (increment count).
     */
    public function downloadFile(int $id)
    {
        $dl = Download::find($id);

        if (!$dl) {
            Flux::toast('File not found.', variant: 'danger');
            return;
        }

        $path = storage_path('app/public/' . $dl->file_path);

        if (!file_exists($path)) {
            Flux::toast('File not available on server.', variant: 'danger');
            return;
        }

        $dl->incrementQuietly('download_count');

        return response()->download($path, $dl->file_name);
    }

    public function confirmDelete(int $id): void
    {
        $dl = Download::find($id);
        if (!$dl) {
            Flux::toast('File not found.', variant: 'danger');
            return;
        }
        $this->dispatch('confirm-delete', title: 'Delete File?', message: 'You are about to delete:', subject: $dl->title, note: 'File will be permanently deleted from storage.', confirmLabel: 'Delete', cancelLabel: 'Cancel', action: 'deleteDownload', payload: ['id' => $dl->id]);
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deleteDownload') {
            $this->deleteDownload($payload['id'] ?? null);
        }
    }

    public function deleteDownload(?int $id): void
    {
        if (!$id) {
            return;
        }
        $dl = Download::find($id);
        if (!$dl) {
            return;
        }

        try {
            if ($dl->file_path && \Storage::disk('public')->exists($dl->file_path)) {
                \Storage::disk('public')->delete($dl->file_path);
            }
            $dl->delete();
            Flux::toast('File deleted successfully.', variant: 'success');
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to delete file.', variant: 'danger');
        }
    }

    public function with(): array
    {
        $downloads = Download::query()
            ->with('author')
            ->when($this->search, fn($q) => $q->where(fn($q) => $q->where('title', 'like', "%{$this->search}%")->orWhere('description', 'like', "%{$this->search}%")))
            ->when($this->categoryFilter, fn($q) => $q->where('category', $this->categoryFilter))
            ->when($this->statusFilter === 'active', fn($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderByDesc('created_at')
            ->paginate(10);

        return ['downloads' => $downloads];
    }
};
?>

<div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- ══════════ HEADER ══════════ --}}
        <x-dashboard-header icon="arrow-down-tray" title="Manage Downloads"
            leading="Manage downloadable files for users (guidelines, templates, forms)." />

        {{-- ══════════ MAIN CARD ══════════ --}}
        <div
            class="bg-white dark:bg-zinc-900 rounded-2xl
            shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50
            border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- ─────── TOOLBAR (RESPONSIVE) ─────── --}}
            <div
                class="px-2 sm:px-3 py-2
                border-b border-slate-200 dark:border-zinc-800
                bg-slate-50/50 dark:bg-zinc-900/50">

                <div class="flex flex-wrap items-center gap-1.5 min-w-0">

                    {{-- ─────── STATS BADGE ─────── --}}
                    <div
                        class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                        bg-violet-50 dark:bg-violet-900/20
                        border border-violet-100 dark:border-violet-900/40">
                        <flux:icon.arrow-down-tray class="size-3 text-violet-600 dark:text-violet-400" />
                        <span class="text-[10.5px] font-semibold text-violet-700 dark:text-violet-300">
                            {{ $downloads->total() }}
                        </span>
                        <span class="text-[10px] text-violet-600/70 dark:text-violet-400/70 hidden sm:inline">
                            files
                        </span>
                    </div>

                    {{-- ─────── FILTER: CATEGORY ─────── --}}
                    <div class="shrink-0">
                        <x-select wire:model.live="categoryFilter" size="sm" color="violet" maxWidth="w-auto">
                            <option value="">All Categories</option>
                            <option value="guideline">Guideline</option>
                            <option value="template">Template</option>
                            <option value="form">Form</option>
                            <option value="general">General</option>
                        </x-select>
                    </div>

                    {{-- ─────── FILTER: STATUS ─────── --}}
                    <div class="shrink-0">
                        <x-select wire:model.live="statusFilter" size="sm" color="violet" maxWidth="w-auto">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </x-select>
                    </div>

                    {{-- ─────── NEW BUTTON ─────── --}}
                    <div class="order-4 sm:order-5 ml-auto sm:ml-0 shrink-0">
                        <flux:button variant="primary" x-data x-on:click="$dispatch('open-add-download')"
                            class="!text-[10.5px]">
                            New
                        </flux:button>
                    </div>

                    {{-- ─────── SEARCH ─────── --}}
                    <div
                        class="order-5 sm:order-4
                        w-full sm:w-auto
                        sm:ml-auto
                        md:w-44 lg:w-56
                        min-w-0">
                        <x-input-search name="q" wire:model.live="search" id="search-download"
                            placeholder="Search files..." class="w-full !text-[10.5px]" />
                    </div>
                </div>
            </div>

            {{-- ─────── TABLE (RESPONSIVE) ─────── --}}
            <div class="relative">
                {{-- Swipe hint --}}
                <div x-data="{ showHint: true }" x-init="setTimeout(() => showHint = false, 3500)" x-show="showHint"
                    x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                   z-20 md:hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                   bg-slate-900/85 dark:bg-zinc-700/90 backdrop-blur-sm
                   text-white text-[10.5px] font-medium shadow-lg">
                    <flux:icon.arrows-right-left class="size-3" />
                    Swipe to see more
                </div>

                <div
                    class="overflow-x-auto overscroll-x-contain scroll-smooth
                    [scrollbar-width:thin]
                    [&::-webkit-scrollbar]:h-1.5
                    [&::-webkit-scrollbar-thumb]:bg-slate-300
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">
                    <table class="w-full min-w-[560px] sm:min-w-[640px] lg:min-w-[720px]">
                        <thead>
                            <tr
                                class="bg-slate-50/80 dark:bg-zinc-900/50
                               border-b border-slate-200 dark:border-zinc-800">
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    File
                                </th>
                                <th
                                    class="hidden md:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Category
                                </th>
                                <th
                                    class="hidden lg:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Visibility
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Status
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                            @forelse ($downloads as $dl)
                                @php $cat = $dl->categoryMeta(); @endphp

                                <tr wire:key="dl-{{ $dl->id }}"
                                    class="group transition-colors duration-150
                                   hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                    {{-- FILE --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <div class="flex items-start gap-2">
                                            <div
                                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-full shrink-0
                                                bg-{{ $cat['color'] }}-100 dark:bg-{{ $cat['color'] }}-900/30
                                                flex items-center justify-center
                                                ring-1 ring-white/40 dark:ring-zinc-800/40
                                                group-hover:scale-105 transition-transform">
                                                <flux:icon :name="$cat['icon']"
                                                    class="size-3 sm:size-3.5 text-{{ $cat['color'] }}-600 dark:text-{{ $cat['color'] }}-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] sm:text-[11.5px] font-semibold leading-tight
                                                  text-slate-900 dark:text-white line-clamp-1 max-w-[200px] sm:max-w-none"
                                                    title="{{ $dl->title }}">
                                                    {{ $dl->title }}
                                                </p>
                                                <div
                                                    class="mt-0.5 flex items-center gap-1 flex-wrap
                                                    text-[9.5px] text-slate-500 dark:text-zinc-500">
                                                    <span
                                                        class="px-1.5 py-0.5 rounded-full
                                                         bg-slate-100 dark:bg-zinc-800
                                                         text-slate-600 dark:text-zinc-400
                                                         font-semibold">
                                                        {{ $dl->file_extension }}
                                                    </span>
                                                    <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                                                    <span>{{ $dl->file_size_human }}</span>
                                                    <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                                                    <span>{{ $dl->download_count }}×</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- CATEGORY --}}
                                    <td class="hidden md:table-cell px-2 sm:px-3 py-2">
                                        <span
                                            class="text-[9.5px] px-1.5 sm:px-2 py-0.5 rounded-full font-semibold
                                             bg-{{ $cat['color'] }}-100 text-{{ $cat['color'] }}-700
                                             dark:bg-{{ $cat['color'] }}-900/30 dark:text-{{ $cat['color'] }}-300">
                                            {{ $cat['label'] }}
                                        </span>
                                    </td>

                                    {{-- VISIBILITY --}}
                                    <td class="hidden lg:table-cell px-2 sm:px-3 py-2">
                                        <div class="flex items-center gap-1 flex-wrap">
                                            @if ($dl->show_on_welcome)
                                                <span
                                                    class="text-[9px] px-1.5 py-px rounded-full font-medium
                                                     bg-emerald-100 text-emerald-700
                                                     dark:bg-emerald-900/30 dark:text-emerald-300">
                                                    Welcome
                                                </span>
                                            @endif
                                            @if ($dl->show_on_dashboard)
                                                <span
                                                    class="text-[9px] px-1.5 py-px rounded-full font-medium
                                                     bg-sky-100 text-sky-700
                                                     dark:bg-sky-900/30 dark:text-sky-300">
                                                    Dashboard
                                                </span>
                                            @endif
                                            @if (!$dl->show_on_welcome && !$dl->show_on_dashboard)
                                                <span
                                                    class="text-[9.5px] text-slate-400 dark:text-zinc-600 italic">—</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- STATUS --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <span
                                            class="inline-flex items-center gap-1 px-1.5 sm:px-2 py-0.5 rounded-full
                                             text-[9.5px] font-semibold whitespace-nowrap
                                    {{ $dl->is_active
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                        : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                            <span
                                                class="w-1 h-1 rounded-full
                                        {{ $dl->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                            {{ $dl->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>

                                    {{-- ACTION --}}
                                    <td class="px-2 sm:px-3 py-2 text-right">
                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                                class="!p-1 rounded-full text-slate-400
                                               hover:bg-slate-100 hover:text-slate-600
                                               dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300
                                               hover:scale-110 active:scale-95
                                               opacity-60 group-hover:opacity-100 transition-all duration-150" />

                                            <flux:menu class="min-w-[190px] !text-[11px]">
                                                <flux:menu.item icon="arrow-down-tray"
                                                    wire:click="downloadFile({{ $dl->id }})">
                                                    Preview / Download
                                                </flux:menu.item>
                                                <flux:menu.item icon="pencil-square" x-data
                                                    x-on:click="$dispatch('open-edit-download', { id: {{ $dl->id }} })">
                                                    Edit
                                                </flux:menu.item>
                                                <flux:menu.item icon="{{ $dl->is_active ? 'eye-slash' : 'eye' }}"
                                                    wire:click="toggleActive({{ $dl->id }})">
                                                    {{ $dl->is_active ? 'Deactivate' : 'Activate' }}
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item variant="danger" icon="trash"
                                                    wire:click="confirmDelete({{ $dl->id }})">
                                                    Delete
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10">
                                        <div class="flex flex-col items-center gap-2 text-center">
                                            <div
                                                class="w-11 h-11 rounded-full bg-slate-100 dark:bg-zinc-800
                                                flex items-center justify-center">
                                                <flux:icon.arrow-down-tray
                                                    class="size-5 text-slate-400 dark:text-zinc-600" />
                                            </div>
                                            <div>
                                                <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                    No files yet
                                                </p>
                                                <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                    @if ($search || $categoryFilter || $statusFilter)
                                                        No results match your filters.
                                                    @else
                                                        Click "New" to add your first file.
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ─────── PAGINATION ─────── --}}
            @if ($downloads->hasPages())
                <div
                    class="px-2 sm:px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                    bg-slate-50/50 dark:bg-zinc-900/50
                    overflow-x-auto
                    [scrollbar-width:thin]
                    [&::-webkit-scrollbar]:h-1
                    [&::-webkit-scrollbar-thumb]:bg-slate-300
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">
                    {{ $downloads->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ MODALS ══════════ --}}
    <x-confirm-delete />
    <livewire:admin.downloads.add-download />
    <livewire:admin.downloads.edit-download />
</div>
