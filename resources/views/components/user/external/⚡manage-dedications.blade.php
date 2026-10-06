<?php

use App\Models\ExternalProposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('External Community Service')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $roleFilter = '';
    public string $verificationFilter = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingRoleFilter(): void { $this->resetPage(); }
    public function updatingVerificationFilter(): void { $this->resetPage(); }

    protected function baseQuery()
    {
        return ExternalProposal::query()
            ->where('user_id', auth()->id())
            ->where('is_research', false);
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

        if (! $item || ! $item->{$column}) {
            Flux::toast("{$label} document not found.", variant: 'danger');
            return;
        }

        $path = storage_path('app/public/' . $item->{$column});
        if (! file_exists($path)) {
            Flux::toast('File not available on server.', variant: 'danger');
            return;
        }

        return response()->download($path, basename($item->{$column}));
    }

    public function confirmDelete(int $id): void
    {
        $item = $this->baseQuery()->find($id);

        if (! $item) {
            Flux::toast('Community service not found.', variant: 'danger');
            return;
        }

        $this->dispatch(
            'confirm-delete',
            title: 'Delete Community Service?',
            message: 'You are about to delete:',
            subject: $item->title,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
            cancelLabel: 'Cancel',
            action: 'deleteExternalDedication',
            payload: ['id' => $item->id],
        );
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deleteExternalDedication') {
            $this->deleteExternalDedication($payload['id'] ?? null);
        }
    }

    public function deleteExternalDedication(?int $id): void
    {
        if (! $id) return;

        $item = $this->baseQuery()->find($id);
        if (! $item) {
            Flux::toast('Community service not found.', variant: 'danger');
            return;
        }

        try {
            if ($item->proposal_document_path
                && \Storage::disk('public')->exists($item->proposal_document_path)) {
                \Storage::disk('public')->delete($item->proposal_document_path);
            }

            if ($item->report_document_path
                && \Storage::disk('public')->exists($item->report_document_path)) {
                \Storage::disk('public')->delete($item->report_document_path);
            }

            $title = $item->title;
            $item->delete();

            Flux::toast("Community service \"{$title}\" deleted.", variant: 'success');
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to delete community service.', variant: 'danger');
        }
    }

    public function with(): array
    {
        $items = $this->baseQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('funding_source', 'like', "%{$this->search}%")
                ->orWhere('scheme', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->roleFilter, fn ($q) => $q->where('role', $this->roleFilter))
            ->when($this->verificationFilter === 'verified', fn ($q) => $q->where('is_verified', true))
            ->when($this->verificationFilter === 'unverified', fn ($q) => $q->where('is_verified', false))
            ->orderByDesc('start_date')
            ->paginate(10);

        return ['items' => $items];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        <x-dashboard-header icon="gift" title="External Community Service"
            leading="Record your community service activities funded by external sources." />

        <div class="bg-white dark:bg-zinc-900 rounded-2xl
                    shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50
                    border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- TOOLBAR --}}
            <div class="px-2.5 sm:px-3 py-2
                        border-b border-slate-200 dark:border-zinc-800
                        bg-slate-50/50 dark:bg-zinc-900/50">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">

                    <div class="flex flex-wrap items-center gap-1.5">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full
                                    bg-rose-50 dark:bg-rose-900/20
                                    border border-rose-100 dark:border-rose-900/40 w-fit">
                            <flux:icon.gift class="size-3 text-rose-600 dark:text-rose-400" />
                            <span class="text-[10.5px] font-semibold text-rose-700 dark:text-rose-300">
                                {{ $items->total() }}
                            </span>
                            <span class="text-[10px] text-rose-600/70 dark:text-rose-400/70">
                                records
                            </span>
                        </div>

                        <x-select wire:model.live="verificationFilter" size="sm" color="rose"
                            maxWidth="w-auto">
                            <option value="">All Verification</option>
                            <option value="verified">Verified</option>
                            <option value="unverified">Unverified</option>
                        </x-select>

                        <x-select wire:model.live="statusFilter" size="sm" color="rose"
                            maxWidth="w-auto">
                            <option value="">All Status</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </x-select>

                        <x-select wire:model.live="roleFilter" size="sm" color="rose"
                            maxWidth="w-auto">
                            <option value="">All Roles</option>
                            <option value="leader">Leader</option>
                            <option value="member">Member</option>
                        </x-select>
                    </div>

                    <div class="flex items-center gap-1.5 w-full lg:w-auto">
                        <div class="flex-1 lg:flex-none lg:w-56">
                            <x-input-search name="q" wire:model.live="search" id="search-ext-ded"
                                placeholder="Search dedications..."
                                class="w-full !text-[10.5px]" />
                        </div>
                        <flux:button variant="primary"
                            x-data x-on:click="$dispatch('open-add-external-dedication')"
                            class="shrink-0 !text-[10.5px]">
                            New Item
                        </flux:button>
                    </div>
                </div>
            </div>

            {{-- TABLE --}}
            <div class="relative">
                <div x-data="{ showHint: true }"
                    x-init="setTimeout(() => showHint = false, 3500)"
                    x-show="showHint"
                    x-transition:leave="transition ease-in duration-500"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                           z-20 md:hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                           bg-slate-900/85 dark:bg-zinc-700/90 backdrop-blur-sm
                           text-white text-[10.5px] font-medium shadow-lg">
                    <flux:icon.arrows-right-left class="size-3" />
                    Swipe to see more
                </div>

                <div class="overflow-x-auto overscroll-x-contain scroll-smooth
                            [scrollbar-width:thin]
                            [&::-webkit-scrollbar]:h-1.5
                            [&::-webkit-scrollbar-thumb]:bg-slate-300
                            [&::-webkit-scrollbar-thumb]:rounded-full
                            dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">
                    <table class="w-full min-w-[760px]">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-zinc-900/50
                                       border-b border-slate-200 dark:border-zinc-800">
                                <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">Community Service</th>
                                <th class="hidden md:table-cell px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">Funding</th>
                                <th class="hidden lg:table-cell px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">Period</th>
                                <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">Status</th>
                                <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">Verification</th>
                                <th class="px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                           text-slate-500 dark:text-zinc-400 whitespace-nowrap">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                            @forelse ($items as $item)
                                @php
                                    $status = $item->statusMeta();
                                    $role   = $item->roleMeta();
                                    $vMeta  = $item->verificationMeta();
                                @endphp

                                <tr wire:key="ext-ded-{{ $item->id }}"
                                    class="group transition-colors duration-150
                                           hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                    {{-- COMMUNITY SERVICE --}}
                                    <td class="px-3 py-2">
                                        <div class="flex items-start gap-2">
                                            <div class="w-7 h-7 rounded-full shrink-0
                                                        bg-gradient-to-br from-rose-100 to-pink-50
                                                        dark:from-rose-900/30 dark:to-pink-900/20
                                                        flex items-center justify-center
                                                        ring-1 ring-white/40 dark:ring-zinc-800/40
                                                        group-hover:scale-105 transition-transform">
                                                <flux:icon.gift
                                                    class="size-3.5 text-rose-600 dark:text-rose-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11.5px] font-semibold leading-tight truncate
                                                          text-slate-900 dark:text-white">
                                                    {{ Str::limit($item->title, 45) }}
                                                </p>
                                                <div class="mt-0.5 flex items-center gap-1.5 flex-wrap text-[9.5px]">
                                                    <span class="px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap
                                                                 bg-rose-100 text-rose-700
                                                                 dark:bg-rose-900/30 dark:text-rose-300">
                                                        External
                                                    </span>
                                                    <span class="px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap
                                                                 {{ $role['class'] }}">
                                                        {{ $role['label'] }}
                                                    </span>
                                                    @if ($item->scheme)
                                                        <span class="text-slate-500 dark:text-zinc-500 truncate max-w-[180px]">
                                                            {{ $item->scheme }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- FUNDING --}}
                                    <td class="hidden md:table-cell px-3 py-2">
                                        <p class="text-[10.5px] font-medium text-slate-700 dark:text-zinc-300 truncate">
                                            {{ $item->funding_source }}
                                        </p>
                                        <p class="text-[9.5px] text-slate-500 dark:text-zinc-500">
                                            {{ $item->fund_amount_formatted }}
                                        </p>
                                    </td>

                                    {{-- PERIOD --}}
                                    <td class="hidden lg:table-cell px-3 py-2">
                                        <span class="text-[10.5px] text-slate-600 dark:text-zinc-400 whitespace-nowrap">
                                            {{ $item->duration }}
                                        </span>
                                    </td>

                                    {{-- STATUS --}}
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                                     text-[9.5px] font-semibold whitespace-nowrap
                                                     {{ $status['class'] }}">
                                            <span class="w-1 h-1 rounded-full {{ $status['dot'] }}"></span>
                                            {{ $status['label'] }}
                                        </span>
                                    </td>

                                    {{-- VERIFICATION --}}
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                                     text-[9.5px] font-semibold whitespace-nowrap
                                                     {{ $vMeta['class'] }}">
                                            <flux:icon
                                                :name="$item->is_verified ? 'check-badge' : 'clock'"
                                                class="size-2.5" />
                                            {{ $vMeta['label'] }}
                                        </span>
                                    </td>

                                    {{-- ACTION --}}
                                    <td class="px-3 py-2 text-right">
                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                                class="!p-1 rounded-full text-slate-400
                                                       hover:bg-slate-100 hover:text-slate-600
                                                       dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300
                                                       hover:scale-110 active:scale-95
                                                       opacity-60 group-hover:opacity-100 transition-all duration-150" />

                                            <flux:menu class="!text-[11px]">

                                                {{-- ══════════ PROPOSAL DOCUMENT ══════════ --}}
                                                @if ($item->proposal_document_path)
                                                    <flux:menu.item icon="eye"
                                                        x-data
                                                        x-on:click="window.open('{{ Storage::disk('public')->url($item->proposal_document_path) }}', '_blank')">
                                                        View Proposal
                                                    </flux:menu.item>

                                                    <flux:menu.item icon="arrow-down-tray"
                                                        wire:click="downloadProposal({{ $item->id }})">
                                                        Download Proposal
                                                    </flux:menu.item>

                                                    <flux:menu.separator />
                                                @endif

                                                {{-- ══════════ REPORT DOCUMENT ══════════ --}}
                                                @if ($item->report_document_path)
                                                    <flux:menu.item icon="eye"
                                                        x-data
                                                        x-on:click="window.open('{{ Storage::disk('public')->url($item->report_document_path) }}', '_blank')">
                                                        View Report
                                                    </flux:menu.item>

                                                    <flux:menu.item icon="arrow-down-tray"
                                                        wire:click="downloadReport({{ $item->id }})">
                                                        Download Report
                                                    </flux:menu.item

                                                    <flux:menu.separator />
                                                @endif

                                                {{-- ══════════ EDIT / LOCKED ══════════ --}}
                                                @if ($item->is_verified)
                                                    <flux:menu.item icon="lock-closed" disabled>
                                                        <span class="flex items-center justify-between w-full gap-2">
                                                            Verified — Locked
                                                            <flux:icon.lock-closed class="size-3" />
                                                        </span>
                                                    </flux:menu.item>
                                                @else
                                                    <flux:menu.item icon="pencil-square" x-data
                                                        x-on:click="$dispatch('open-edit-external-dedication', { id: {{ $item->id }} })">
                                                        Edit Record
                                                    </flux:menu.item>
                                                @endif

                                                <flux:menu.separator />

                                                {{-- ══════════ DELETE ══════════ --}}
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
                                    <td colspan="6" class="px-4 py-12">
                                        <div class="flex flex-col items-center gap-2 text-center">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-zinc-800
                                                        flex items-center justify-center">
                                                <flux:icon.gift class="size-6 text-slate-400 dark:text-zinc-600" />
                                            </div>
                                            <div>
                                                <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                    No external community service yet
                                                </p>
                                                <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                    Click "New" to add your first external community service record.
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

            @if ($items->hasPages())
                <div class="px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                            bg-slate-50/50 dark:bg-zinc-900/50">
                    {{ $items->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>

    <x-confirm-delete />
    <livewire:user.external.dedications.add-dedication />
    <livewire:user.external.dedications.edit-dedication />
</div>
