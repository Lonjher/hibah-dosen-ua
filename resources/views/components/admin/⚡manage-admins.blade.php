<?php

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Admins')] class extends Component {
    use WithPagination;

    public $search = '';

    public function mount(): void
    {
        // 🔒 Guard: hanya Super Admin
        abort_unless(
            auth()->user()?->role?->role_code === 'SUPERADMIN',
            403,
            'Hanya Super Admin yang dapat mengakses halaman ini.'
        );
    }

    public function with()
    {
        $admins = User::query()
            ->with('role')
            ->whereHas('role', fn ($q) => $q->where('role_code', 'ADMIN'))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('full_name', 'like', "%{$this->search}%")
                  ->orWhere('nidn', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(10);

        return ['admins' => $admins];
    }

    public function delete(User $user)
    {
        // Cegah Super Admin hapus dirinya sendiri
        if ($user->id === auth()->id()) {
            Flux::toast('Tidak dapat menghapus akun sendiri.', variant: 'danger');
            return;
        }

        // Cegah hapus Super Admin
        if ($user->role?->role_code === 'SUPERADMIN') {
            Flux::toast('Tidak dapat menghapus Super Admin.', variant: 'danger');
            return;
        }

        $user->delete();
        Flux::toast('Admin dihapus.');
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950 p-3 sm:p-4 lg:p-6">
    <div class="max-w-7xl mx-auto space-y-4">

        <x-dashboard-header icon="shield-check" title="Manage Admins"
            leading="Kelola akun administrator sistem. Hanya Super Admin yang dapat mengakses." />

        {{-- Warning Banner --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3 flex items-start gap-2">
            <flux:icon.shield-exclamation class="size-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
            <div>
                <p class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                    Akses Terbatas — Super Admin Only
                </p>
                <p class="text-[10px] text-amber-700 dark:text-amber-400 mt-0.5">
                    Halaman ini hanya dapat diakses oleh Super Admin. Admin biasa tidak dapat mengelola akun admin lain.
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-zinc-950/50 border border-slate-200 dark:border-zinc-800 overflow-hidden">

            <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-zinc-800 bg-gradient-to-r from-slate-50 to-white dark:from-zinc-900 dark:to-zinc-900/50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center justify-center w-5 h-5 rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                            <flux:icon.list-bullet class="size-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <p class="text-[10px] font-medium text-slate-900 dark:text-zinc-400">
                            {{ $admins->total() }} found
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1 sm:flex-none sm:w-64">
                            <x-input-search name="q" wire:model.live="search" id="search-admin"
                                placeholder="Cari admin..." class="w-full text-xs" />
                        </div>
                        <flux:button icon="plus" x-data
                            x-on:click="$dispatch('open-add-admin')"
                            variant="primary" size="xs"
                            class="shrink-0 shadow-lg shadow-emerald-500/20 text-xs">
                            <span class="hidden sm:inline">Tambah Admin</span>
                            <span class="sm:hidden">Tambah</span>
                        </flux:button>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-zinc-900/50 border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Admin</span>
                            </th>
                            <th class="hidden md:table-cell px-3 sm:px-4 py-2.5 text-left">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">NIDN</span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Notes Given</span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-right">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Action</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                        @forelse ($admins as $admin)
                            <tr class="group hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-3 sm:px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/20 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 group-hover:scale-105 transition-transform">
                                            {{ $admin->initials() }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-900 dark:text-white">
                                                {{ $admin->full_name }}
                                            </p>
                                            <p class="text-[10px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                {{ $admin->email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="hidden md:table-cell px-3 sm:px-4 py-2.5">
                                    <span class="text-[11px] text-slate-700 dark:text-zinc-300 font-mono">
                                        {{ $admin->nidn }}
                                    </span>
                                </td>

                                <td class="px-3 sm:px-4 py-2.5">
                                    <span class="inline-flex items-center gap-1 text-[10px] text-slate-600 dark:text-zinc-400">
                                        <flux:icon.chat-bubble-left class="size-3" />
                                        {{ $admin->adminNotes()->count() }} catatan
                                    </span>
                                </td>

                                <td class="px-3 sm:px-4 py-2.5 text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                            class="rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800" />
                                        <flux:menu class="min-w-[160px] text-xs">
                                            <flux:menu.item icon="pencil-square" x-data
                                                x-on:click="$dispatch('open-edit-admin', { id: {{ $admin->id }} })">
                                                Edit Admin
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item variant="danger" icon="trash"
                                                wire:click="delete({{ $admin->id }})"
                                                wire:confirm="Yakin ingin menghapus admin ini?">
                                                Hapus Admin
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
                                            <flux:icon.shield-check class="size-7 text-slate-400 dark:text-zinc-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Belum ada admin</p>
                                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">
                                                Klik "Tambah Admin" untuk membuat admin baru
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($admins->hasPages())
                <div class="px-3 sm:px-4 py-3 border-t border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900/50">
                    {{ $admins->links() }}
                </div>
            @endif
        </div>
    </div>

    <livewire:admin.admins.add-admin />
    <livewire:admin.admins.edit-admin />
</div>
