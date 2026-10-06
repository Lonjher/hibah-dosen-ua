<?php

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Admins')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->role?->role_code === 'SUPERADMIN', 403, 'Only Super Admin can access this page.');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /* ============================================================
     |  DELETE FLOW
     ============================================================ */

    public function confirmDelete(int $id): void
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            Flux::toast('Admin not found.', variant: 'danger');
            return;
        }

        if ($user->id === auth()->id()) {
            Flux::toast('You cannot delete your own account.', variant: 'danger');
            return;
        }

        if ($user->role?->role_code === 'SUPERADMIN') {
            Flux::toast('Cannot delete a Super Admin.', variant: 'danger');
            return;
        }

        $this->dispatch('confirm-delete', title: 'Delete Admin?', message: 'You are about to delete:', subject: $user->full_name, note: 'This action cannot be undone.', confirmLabel: 'Delete', cancelLabel: 'Cancel', action: 'deleteAdmin', payload: ['id' => $user->id]);
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deleteAdmin') {
            $this->deleteAdmin($payload['id'] ?? null);
        }
    }

    public function deleteAdmin(?int $id): void
    {
        if (!$id) {
            return;
        }

        $user = User::with('role')->find($id);
        if (!$user) {
            return;
        }

        if ($user->id === auth()->id()) {
            Flux::toast('You cannot delete your own account.', variant: 'danger');
            return;
        }

        if ($user->role?->role_code === 'SUPERADMIN') {
            Flux::toast('Cannot delete a Super Admin.', variant: 'danger');
            return;
        }

        try {
            $name = $user->full_name;
            $user->delete();
            Flux::toast("Admin '{$name}' deleted.", variant: 'success');
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to delete admin.', variant: 'danger');
        }
    }

    /* ============================================================
     |  QUERY
     ============================================================ */

    public function with(): array
    {
        $admins = User::query()
            ->with('role')
            ->whereHas('role', fn($q) => $q->where('role_code', 'ADMIN'))
            ->when(
                $this->search,
                fn($q) => $q->where(function ($q) {
                    $q->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('nidn', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                }),
            )
            ->latest()
            ->paginate(10);

        return ['admins' => $admins];
    }
};
?>

<div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- ══════════ HEADER ══════════ --}}
        <x-dashboard-header icon="shield-check" title="Manage Admins"
            leading="Manage administrator accounts. Only Super Admin can access." />

        {{-- ══════════ RESTRICTED BANNER ══════════ --}}
        <div
            class="rounded-lg border border-amber-200 dark:border-amber-800/60
                    bg-amber-50/70 dark:bg-amber-900/15
                    px-3 py-2 flex items-start gap-2">
            <flux:icon.shield-exclamation class="size-3.5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
            <div class="min-w-0">
                <p class="text-[10.5px] font-semibold text-amber-800 dark:text-amber-300">
                    Restricted Access — Super Admin Only
                </p>
                <p class="text-[10px] text-amber-700/80 dark:text-amber-400/70 mt-0.5 leading-relaxed">
                    This page can only be accessed by Super Admin. Regular admins cannot manage other admin accounts.
                </p>
            </div>
        </div>

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
                        bg-emerald-50 dark:bg-emerald-900/20
                        border border-emerald-100 dark:border-emerald-900/40">
                        <flux:icon.shield-check class="size-3 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-[10.5px] font-semibold text-emerald-700 dark:text-emerald-300">
                            {{ $admins->total() }}
                        </span>
                        <span class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70 hidden sm:inline">
                            admins
                        </span>
                    </div>

                    {{-- ─────── NEW BUTTON ─────── --}}
                    <div class="order-4 sm:order-5 ml-auto sm:ml-0 shrink-0">
                        <flux:button variant="primary" x-data x-on:click="$dispatch('open-add-admin')"
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
                        <x-input-search name="q" wire:model.live="search" id="search-admin"
                            placeholder="Search admins..." class="w-full !text-[10.5px]" />
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
                    <table class="w-full min-w-[480px] sm:min-w-[560px] lg:min-w-[640px]">
                        <thead>
                            <tr
                                class="bg-slate-50/80 dark:bg-zinc-900/50
                               border-b border-slate-200 dark:border-zinc-800">
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Admin
                                </th>
                                <th
                                    class="hidden md:table-cell px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    NIDN
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Notes
                                </th>
                                <th
                                    class="px-2 sm:px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                            @forelse ($admins as $admin)
                                <tr wire:key="admin-{{ $admin->id }}"
                                    class="group transition-colors duration-150
                                   hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                    {{-- ADMIN --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-full shrink-0
                                                bg-gradient-to-br from-emerald-100 to-teal-50
                                                dark:from-emerald-900/30 dark:to-teal-900/20
                                                flex items-center justify-center
                                                text-[9px] sm:text-[10px] font-bold
                                                text-emerald-700 dark:text-emerald-300
                                                ring-1 ring-white/40 dark:ring-zinc-800/40
                                                group-hover:scale-105 transition-transform">
                                                {{ $admin->initials() }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] sm:text-[11.5px] font-semibold leading-tight truncate
                                                  text-slate-900 dark:text-white max-w-[200px] sm:max-w-none"
                                                    title="{{ $admin->full_name }}">
                                                    {{ $admin->full_name }}
                                                </p>
                                                <p class="mt-0.5 text-[9.5px] truncate
                                                  text-slate-500 dark:text-zinc-500"
                                                    title="{{ $admin->email }}">
                                                    {{ $admin->email }}
                                                </p>
                                                {{-- NIDN on mobile --}}
                                                @if ($admin->nidn)
                                                    <p
                                                        class="mt-0.5 md:hidden text-[9.5px] font-mono text-slate-500 dark:text-zinc-500">
                                                        {{ $admin->nidn }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- NIDN --}}
                                    <td class="hidden md:table-cell px-2 sm:px-3 py-2">
                                        <span
                                            class="text-[10.5px] font-mono whitespace-nowrap
                                             text-slate-600 dark:text-zinc-400">
                                            {{ $admin->nidn ?: '—' }}
                                        </span>
                                    </td>

                                    {{-- NOTES GIVEN --}}
                                    <td class="px-2 sm:px-3 py-2">
                                        <span
                                            class="inline-flex items-center gap-1 px-1.5 sm:px-2 py-0.5 rounded-full
                                             bg-slate-100 dark:bg-zinc-800
                                             text-slate-600 dark:text-zinc-400
                                             text-[9.5px] font-medium w-fit whitespace-nowrap">
                                            <flux:icon.chat-bubble-left class="size-2.5" />
                                            {{ $admin->adminNotes()->count() }}
                                            <span class="hidden sm:inline">notes</span>
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

                                            <flux:menu class="!text-[11px]">
                                                <flux:menu.item icon="pencil-square" x-data
                                                    x-on:click="$dispatch('open-edit-admin', { id: {{ $admin->id }} })">
                                                    Edit Admin
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item variant="danger" icon="trash"
                                                    wire:click="confirmDelete({{ $admin->id }})">
                                                    Delete Admin
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10">
                                        <div class="flex flex-col items-center gap-2 text-center">
                                            <div
                                                class="w-11 h-11 rounded-full bg-slate-100 dark:bg-zinc-800
                                                flex items-center justify-center">
                                                <flux:icon.shield-check
                                                    class="size-5 text-slate-400 dark:text-zinc-600" />
                                            </div>
                                            <div>
                                                <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                    No admins yet
                                                </p>
                                                <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                    @if ($search)
                                                        No results for "{{ $search }}".
                                                    @else
                                                        Click "New" to create the first admin.
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
            @if ($admins->hasPages())
                <div
                    class="px-2 sm:px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                    bg-slate-50/50 dark:bg-zinc-900/50
                    overflow-x-auto
                    [scrollbar-width:thin]
                    [&::-webkit-scrollbar]:h-1
                    [&::-webkit-scrollbar-thumb]:bg-slate-300
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">
                    {{ $admins->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ MODALS ══════════ --}}
    <x-confirm-delete />
    <livewire:admin.admins.add-admin />
    <livewire:admin.admins.edit-admin />
</div>
