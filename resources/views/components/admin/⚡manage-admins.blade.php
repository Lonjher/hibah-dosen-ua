<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    public $full_name = '';
    public $email = '';
    public $nidn = '';
    public $phone_number = '';
    public $password = '';
    public $editingId = null;
    public $showModal = false;

    protected $rules = [
        'full_name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'nidn' => 'nullable|string|max:30',
        'phone_number' => 'nullable|string|max:20',
        'password' => 'nullable|string|min:8',
    ];

    public function render()
    {
        $adminRole = Role::where('role_code', 'ADMIN')->firstOrFail();
        $admins = User::where('role_id', $adminRole->id)
            ->orderBy('full_name')
            ->paginate(10);

        return $this->view([
            'admins' => $admins
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(User $admin)
    {
        $this->editingId = $admin->id;
        $this->full_name = $admin->full_name;
        $this->email = $admin->email;
        $this->nidn = $admin->nidn ?? '';
        $this->phone_number = $admin->phone_number ?? '';
        $this->password = '';
        $this->showModal = true;
    }

    public function save()
    {
        // Untuk edit, pengecualian unique
        $this->rules['email'] = Rule::unique('users', 'email')->ignore($this->editingId);
        $this->validate();

        $adminRole = Role::where('code', 'ADMIN')->firstOrFail();

        if ($this->editingId) {
            $admin = User::findOrFail($this->editingId);
            $admin->update([
                'full_name' => $this->full_name,
                'email' => $this->email,
                'nidn' => $this->nidn ?: null,
                'phone_number' => $this->phone_number ?: null,
                'role_id' => $adminRole->id,
            ]);
            if ($this->password) {
                $admin->update(['password' => Hash::make($this->password)]);
            }
            Flux::toast('Data admin berhasil diperbarui.');
        } else {
            User::create([
                'full_name' => $this->full_name,
                'email' => $this->email,
                'nidn' => $this->nidn ?: null,
                'phone_number' => $this->phone_number ?: null,
                'password' => Hash::make($this->password),
                'role_id' => $adminRole->id,
            ]);
            Flux::toast('Admin baru berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(User $admin)
    {
        // Cegah admin menghapus dirinya sendiri
        if ($admin->id === auth()->id()) {
            Flux::toast('Anda tidak dapat menghapus akun sendiri.', variant: 'danger');
            return;
        }

        $admin->delete();
        Flux::toast('Admin dihapus.');
    }

    private function resetForm()
    {
        $this->reset(['full_name', 'email', 'nidn', 'phone_number', 'password', 'editingId']);
    }
};
?>

<div class="p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <x-dashboard-header icon="shield-check" title="Kelola Admin" leading="Manajemen akun pengguna dengan peran admin." />
        <flux:button icon="plus" wire:click="create" variant="primary" size="sm" class="shrink-0">Tambah Admin</flux:button>
    </div>

    <!-- Tabel Admin -->
    <div class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl border border-white/80 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-xs">
                <thead class="bg-emerald-50/50 dark:bg-emerald-900/20 text-left text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Nama</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">NIDN</th>
                        <th class="px-4 py-3 font-semibold">No. HP</th>
                        <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @forelse($admins as $admin)
                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-[10px] shrink-0">
                                        {{ $admin->initials() }}
                                    </div>
                                    <span class="font-medium text-slate-900 dark:text-zinc-100">{{ $admin->full_name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $admin->email }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $admin->nidn ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $admin->phone_number ?: '—' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $admin->id }})" class="text-slate-500 hover:bg-slate-100 dark:hover:bg-zinc-800" />
                                    <flux:button size="sm" icon="trash" wire:click="delete({{ $admin->id }})" wire:confirm="Yakin ingin menghapus admin ini?" class="text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-900/30" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon.shield-check class="size-8 text-slate-300 dark:text-zinc-700" />
                                    <span>Belum ada admin. Klik "Tambah Admin" untuk membuat.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($admins->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-zinc-800">
                {{ $admins->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" :title="$editingId ? 'Edit Admin' : 'Tambah Admin'"
        description="Lengkapi data akun admin." size="lg">
        <form wire:submit="save" class="space-y-4">
            <div>
                <flux:label for="full_name">Nama Lengkap</flux:label>
                <flux:input wire:model="full_name" id="full_name" placeholder="Sesuai KTP" size="sm" required />
                @error('full_name')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <flux:label for="email">Email</flux:label>
                <flux:input wire:model="email" id="email" type="email" placeholder="user@domain.com" size="sm" required />
                @error('email')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:label for="nidn">NIDN</flux:label>
                    <flux:input wire:model="nidn" id="nidn" placeholder="Opsional" size="sm" />
                    @error('nidn')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <flux:label for="phone_number">No. HP</flux:label>
                    <flux:input wire:model="phone_number" id="phone_number" placeholder="628xxxxxxxxx" size="sm" />
                    @error('phone_number')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div>
                <flux:label for="password">{{ $editingId ? 'Password Baru (opsional)' : 'Password' }}</flux:label>
                <flux:input wire:model="password" id="password" type="password" placeholder="Minimal 8 karakter" size="sm" {{ $editingId ? '' : 'required' }} />
                @error('password')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" size="sm" wire:click="$set('showModal', false)">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm">{{ $editingId ? 'Simpan' : 'Tambah' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
