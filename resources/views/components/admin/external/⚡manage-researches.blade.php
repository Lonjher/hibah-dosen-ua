<?php

use App\Models\ExternalProposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage External Researches')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $verificationFilter = '';
    public string $roleFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }
    public function updatingVerificationFilter(): void
    {
        $this->resetPage();
    }
    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    protected function baseQuery()
    {
        return ExternalProposal::query()->where('is_research', true);
    }

    public function toggleVerify(int $id): void
    {
        $item = $this->baseQuery()->find($id);

        if (!$item) {
            Flux::toast('Record not found.', variant: 'danger');
            return;
        }

        $item->update(['is_verified' => !$item->is_verified]);
        $label = $item->fresh()->is_verified ? 'verified' : 'unverified';
        Flux::toast("Record {$label}.", variant: 'success');
    }

    public function downloadProposal(int $id)
    {
        return $this->downloadFile($id, 'proposal_document_path', 'Proposal');
    }

    public function downloadReport(int $id)
    {
        return $this->downloadFile($id, 'report_document_path', 'Report');
    }

    protected function downloadFile(int $id, string $column, string $label)
    {
        $item = $this->baseQuery()->find($id);

        if (!$item || !$item->{$column}) {
            Flux::toast("{$label} document not found.", variant: 'danger');
            return;
        }

        $path = storage_path('app/public/' . $item->{$column});
        if (!file_exists($path)) {
            Flux::toast('File not available on server.', variant: 'danger');
            return;
        }

        return response()->download($path, basename($item->{$column}));
    }

    public function confirmDelete(int $id): void
    {
        $item = $this->baseQuery()->find($id);

        if (!$item) {
            Flux::toast('Record not found.', variant: 'danger');
            return;
        }

        $this->dispatch('confirm-delete', title: 'Delete External Research?', message: 'You are about to delete:', subject: $item->title, note: 'This action cannot be undone.', confirmLabel: 'Delete', cancelLabel: 'Cancel', action: 'deleteExternal', payload: ['id' => $item->id]);
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deleteExternal') {
            $this->deleteExternal($payload['id'] ?? null);
        }
    }

    public function deleteExternal(?int $id): void
    {
        if (!$id) {
            return;
        }

        $item = $this->baseQuery()->find($id);
        if (!$item) {
            return;
        }

        try {
            // Delete proposal document
            if ($item->proposal_document_path && \Storage::disk('public')->exists($item->proposal_document_path)) {
                \Storage::disk('public')->delete($item->proposal_document_path);
            }

            // Delete report document
            if ($item->report_document_path && \Storage::disk('public')->exists($item->report_document_path)) {
                \Storage::disk('public')->delete($item->report_document_path);
            }

            $title = $item->title;
            $item->delete();

            Flux::toast("External research \"{$title}\" deleted.", variant: 'success');
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to delete external research.', variant: 'danger');
        }
    }

    public function with(): array
    {
        $items = $this->baseQuery()
            ->with('user')
            ->when(
                $this->search,
                fn($q) => $q->where(
                    fn($q) => $q
                        ->where('title', 'like', "%{$this->search}%")
                        ->orWhere('funding_source', 'like', "%{$this->search}%")
                        ->orWhere('scheme', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn($q) => $q->where('full_name', 'like', "%{$this->search}%")),
                ),
            )
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->roleFilter, fn($q) => $q->where('role', $this->roleFilter))
            ->when($this->verificationFilter === 'verified', fn($q) => $q->where('is_verified', true))
            ->when($this->verificationFilter === 'unverified', fn($q) => $q->where('is_verified', false))
            ->orderByDesc('created_at')
            ->paginate(10);

        $stats = [
            'total' => $this->baseQuery()->count(),
            'verified' => $this->baseQuery()->where('is_verified', true)->count(),
            'unverified' => $this->baseQuery()->where('is_verified', false)->count(),
            'funds' => (int) $this->baseQuery()->sum('fund_amount'),
        ];

        return [
            'items' => $items,
            'stats' => $stats,
        ];
    }
};
?>

<div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- HEADER --}}
        <x-dashboard-header icon="globe-alt" title="Manage External Researches"
            leading="Manage and verify all externally funded research records from users." />

        {{-- MAIN CARD --}}
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
                        bg-indigo-50 dark:bg-indigo-900/20
                        border border-indigo-100 dark:border-indigo-900/40">
                        <flux:icon.globe-alt class="size-3 text-indigo-600 dark:text-indigo-400" />
                        <span class="text-[10.5px] font-semibold text-indigo-700 dark:text-indigo-300">
                            {{ $items->total() }}
                        </span>
                        <span class="text-[10px] text-indigo-600/70 dark:text-indigo-400/70 hidden sm:inline">
                            records
                        </span>
                    </div>

                    {{-- ─────── FILTER: VERIFICATION ─────── --}}
                    <div class="shrink-0">
                        <x-select wire:model.live="verificationFilter" size="sm" color="indigo" maxWidth="w-auto">
                            <option value="">All Verification</option>
                            <option value="verified">Verified</option>
                            <option value="unverified">Unverified</option>
                        </x-select>
                    </div>

                    {{-- ─────── FILTER: STATUS ─────── --}}
                    <div class="shrink-0">
                        <x-select wire:model.live="statusFilter" size="sm" color="indigo" maxWidth="w-auto">
                            <option value="">All Status</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </x-select>
                    </div>

                    {{-- ─────── FILTER: ROLE ─────── --}}
                    <div class="shrink-0">
                        <x-select wire:model.live="roleFilter" size="sm" color="indigo" maxWidth="w-auto">
                            <option value="">All Roles</option>
                            <option value="leader">Leader</option>
                            <option value="member">Member</option>
                        </x-select>
                    </div>

                    {{-- ─────── NEW BUTTON ─────── --}}
                    <div class="order-4 sm:order-5 ml-auto sm:ml-0 shrink-0">
                        <flux:button variant="primary" x-data x-on:click="$dispatch('open-add-admin-external')"
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
                        <x-input-search name="q" wire:model.live="search" id="search-admin-ext"
                            placeholder="Search title, user, source..." class="w-full !text-[10.5px]" />
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
                    <table class="w-full min-w-[560px] sm:min-w-[640px] lg:min-w-[820px]">
                        <thead>
                            <tr
                                class="bg-slate-50/80 dark:bg-zinc-900/50
                               border-b border-slate-200 dark:border-zinc-800">
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Research
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Submitted By
                                </th>
                                <th
                                    class="hidden md:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Funding
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Verification
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                            @forelse ($items as $item)
                                @php
                                    $status = $item->statusMeta();
                                    $role = $item->roleMeta();
                                    $vMeta = $item->verificationMeta();
                                @endphp
                                <tr wire:key="admin-ext-{{ $item->id }}"
                                    class="group transition-colors duration-150
                                   hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                    {{-- RESEARCH --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <div class="flex items-start gap-2">
                                            <div
                                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-full shrink-0
                                                bg-gradient-to-br from-indigo-100 to-violet-50
                                                dark:from-indigo-900/30 dark:to-violet-900/20
                                                flex items-center justify-center
                                                ring-1 ring-white/40 dark:ring-zinc-800/40
                                                group-hover:scale-105 transition-transform">
                                                <flux:icon.globe-alt
                                                    class="size-3 sm:size-3.5 text-indigo-600 dark:text-indigo-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] sm:text-[11.5px] font-semibold leading-tight truncate
                                                  text-slate-900 dark:text-white max-w-[200px] sm:max-w-[220px]"
                                                    title="{{ $item->title }}">
                                                    {{ Str::limit($item->title, 45) }}
                                                </p>
                                                <div class="mt-0.5 flex items-center gap-1.5 flex-wrap text-[9.5px]">
                                                    <span
                                                        class="px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap
                                                         bg-indigo-100 text-indigo-700
                                                         dark:bg-indigo-900/30 dark:text-indigo-300">
                                                        External
                                                    </span>
                                                    <span
                                                        class="px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap
                                                         {{ $role['class'] }}">
                                                        {{ $role['label'] }}
                                                    </span>
                                                    <span
                                                        class="px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap
                                                         {{ $status['class'] }}">
                                                        {{ $status['label'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- SUBMITTED BY --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        @if ($item->user)
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div
                                                    class="w-6 h-6 rounded-full shrink-0
                                                    bg-indigo-100 text-indigo-700
                                                    dark:bg-indigo-900/40 dark:text-indigo-300
                                                    flex items-center justify-center
                                                    text-[9px] font-bold">
                                                    {{ strtoupper(substr($item->user->full_name, 0, 1)) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10.5px] font-medium truncate
                                                      text-slate-700 dark:text-zinc-300 max-w-[150px]"
                                                        title="{{ $item->user->full_name }}">
                                                        {{ $item->user->full_name }}
                                                    </p>
                                                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 truncate max-w-[150px]"
                                                        title="{{ $item->user->nidn ?? $item->user->email }}">
                                                        {{ $item->user->nidn ?? $item->user->email }}
                                                    </p>
                                                </div>
                                            </div>
                                        @else
                                            <span
                                                class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic">—</span>
                                        @endif
                                    </td>

                                    {{-- FUNDING --}}
                                    <td class="hidden md:table-cell px-2 sm:px-3 py-2">
                                        <p class="text-[10.5px] font-medium text-slate-700 dark:text-zinc-300 truncate max-w-[160px]"
                                            title="{{ $item->funding_source }}">
                                            {{ $item->funding_source }}
                                        </p>
                                        <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 whitespace-nowrap">
                                            {{ $item->fund_amount_formatted }}
                                        </p>
                                    </td>

                                    {{-- VERIFICATION --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <button type="button" wire:click="toggleVerify({{ $item->id }})"
                                            aria-label="Toggle verification"
                                            title="{{ $item->is_verified ? 'Click to unverify' : 'Click to verify' }}"
                                            class="inline-flex items-center gap-1 px-1.5 sm:px-2 py-0.5 rounded-full
                                           text-[9.5px] font-semibold whitespace-nowrap
                                           hover:ring-1 hover:ring-offset-1
                                           {{ $item->is_verified
                                               ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 hover:ring-emerald-300 dark:hover:ring-emerald-700'
                                               : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 hover:ring-amber-300 dark:hover:ring-amber-700' }}
                                           hover:scale-[1.02] active:scale-[0.97]
                                           transition-all duration-150">
                                            <flux:icon :name="$item->is_verified ? 'check-badge' : 'clock'"
                                                class="size-2.5" />
                                            {{ $vMeta['label'] }}
                                        </button>
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

                                            <flux:menu class="!text-[11px]">
                                                {{-- Document downloads --}}
                                                @if ($item->proposal_document_path)
                                                    <flux:menu.item icon="document-text"
                                                        wire:click="downloadProposal({{ $item->id }})">
                                                        Download Proposal
                                                    </flux:menu.item>
                                                @endif

                                                @if ($item->report_document_path)
                                                    <flux:menu.item icon="document-check"
                                                        wire:click="downloadReport({{ $item->id }})">
                                                        Download Report
                                                    </flux:menu.item>
                                                @endif

                                                @if ($item->proposal_document_path || $item->report_document_path)
                                                    <flux:menu.separator />
                                                @endif

                                                <flux:menu.item icon="pencil-square" x-data
                                                    x-on:click="$dispatch('open-edit-admin-external', { id: {{ $item->id }} })">
                                                    Edit Record
                                                </flux:menu.item>

                                                <flux:menu.separator />

                                                <flux:menu.item variant="danger" icon="trash"
                                                    wire:click="confirmDelete({{ $item->id }})">
                                                    Delete Record
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
                                                <flux:icon.globe-alt
                                                    class="size-5 text-slate-400 dark:text-zinc-600" />
                                            </div>
                                            <div>
                                                <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                    No external researches yet
                                                </p>
                                                <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                    @if ($search || $verificationFilter || $statusFilter || $roleFilter)
                                                        No results match your filters.
                                                    @else
                                                        Click "New" to add a record on behalf of a user.
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
            @if ($items->hasPages())
                <div
                    class="px-2 sm:px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                    bg-slate-50/50 dark:bg-zinc-900/50
                    overflow-x-auto
                    [scrollbar-width:thin]
                    [&::-webkit-scrollbar]:h-1
                    [&::-webkit-scrollbar-thumb]:bg-slate-300
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">
                    {{ $items->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>

    <x-confirm-delete />
    <livewire:admin.external.researches.add-research />
    <livewire:admin.external.researches.edit-research />
</div>
