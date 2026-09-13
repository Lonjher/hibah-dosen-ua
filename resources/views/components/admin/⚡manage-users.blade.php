<?php

use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $full_name = '';
    public $email = '';
    public $nidn = '';
    public $phone_number = '';
    public $birthday = '';
    public $gender = '';
    public $address = '';
    public $password = '';
    public $editingId = null;
    public $showModal = false;

    protected $rules = [
        'full_name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'nidn' => 'required|string|max:30',
        'phone_number' => 'required|string|max:20',
        'birthday' => 'required|date',
        'gender' => 'required|in:laki-laki,perempuan',
        'address' => 'required|string|max:500',
        'password' => 'required|string|min:8',
    ];

    public function render()
    {
        $userRole = Role::where('role_code', 'USER')->firstOrFail();
        $users = User::where('role_id', $userRole->id)->withCount('proposals')->orderBy('full_name')->paginate(10);

        return $this->view([
            'users' => $users
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(User $user)
    {
        $this->editingId = $user->id;
        $this->full_name = $user->full_name;
        $this->email = $user->email;
        $this->nidn = $user->nidn ?? '';
        $this->phone_number = $user->phone_number ?? '';
        $this->birthday = $user->birthday?->format('Y-m-d') ?? '';
        $this->gender = $user->gender ?? '';
        $this->address = $user->address ?? '';
        $this->password = '';
        $this->showModal = true;
    }

    public function save()
    {
        $this->rules['email'] = Rule::unique('users', 'email')->ignore($this->editingId);
        $this->validate();

        $userRole = Role::where('role_code', 'USER')->firstOrFail();

        $data = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'nidn' => $this->nidn ?: null,
            'phone_number' => $this->phone_number ?: null,
            'birthday' => $this->birthday ?: null,
            'gender' => $this->gender ?: null,
            'address' => $this->address ?: null,
            'role_id' => $userRole->id,
        ];

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($data);
            if ($this->password) {
                $user->update(['password' => Hash::make($this->password)]);
            }
            Flux::toast('Data user berhasil diperbarui.');
        } else {
            $data['password'] = Hash::make($this->password);
            User::create($data);
            Flux::toast('User baru berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(User $user)
    {
        if ($user->proposals()->exists()) {
            Flux::toast('Tidak dapat menghapus user yang memiliki proposal.', variant: 'danger');
            return;
        }

        $user->delete();
        Flux::toast('User dihapus.');
    }

    private function resetForm()
    {
        $this->reset(['full_name', 'email', 'nidn', 'phone_number', 'birthday', 'gender', 'address', 'password', 'editingId']);
    }
};
?>

<div class="p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <x-dashboard-header icon="users" title="Kelola Users" leading="Manajemen pengguna dengan peran user/dosen." />
        <flux:button icon="plus" wire:click="create" variant="primary" size="sm" class="shrink-0">Tambah User
        </flux:button>
    </div>

    <!-- Tabel User -->
    <div
        class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl border border-white/80 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-xs">
                <thead
                    class="bg-emerald-50/50 dark:bg-emerald-900/20 text-left text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Nama</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">NIDN</th>
                        <th class="px-4 py-3 font-semibold">No. HP</th>
                        <th class="px-4 py-3 font-semibold">Gender</th>
                        <th class="px-4 py-3 font-semibold text-center">Proposal</th>
                        <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @forelse($users as $user)
                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-[10px] shrink-0">
                                        {{ $user->initials() }}
                                    </div>
                                    <span
                                        class="font-medium text-slate-900 dark:text-zinc-100">{{ $user->full_name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $user->nidn ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $user->phone_number ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $user->gender ?: '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ $user->proposals_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                        title="Menu aksi"
                                        class="rounded-lg text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-700/60" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $user->id }})">
                                            Edit
                                        </flux:menu.item>

                                        <flux:menu.separator />

                                        <flux:menu.item variant="danger" icon="trash"
                                            wire:click="delete({{ $user->id }})"
                                            wire:confirm="Yakin ingin menghapus User ini?">
                                            Hapus
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon.users class="size-8 text-slate-300 dark:text-zinc-700" />
                                    <span>Belum ada user. Klik "Tambah User" untuk membuat.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-zinc-800">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" :title="$editingId ? 'Edit User' : 'Tambah User'"
        description="Lengkapi data akun dan profil user." size="lg">
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
                <flux:input wire:model="email" id="email" type="email" placeholder="user@domain.com"
                    size="sm" required />
                @error('email')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:label for="nidn">NIDN</flux:label>
                    <flux:input wire:model="nidn" id="nidn" placeholder="xxxx.." size="sm" />
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
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:label for="birthday">Tanggal Lahir</flux:label>
                    <flux:input type="date" wire:model="birthday" id="birthday" size="sm" />
                    @error('birthday')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <flux:label for="gender">Jenis Kelamin</flux:label>
                    <flux:select wire:model="gender" id="gender" size="sm">
                        <option value="">Pilih...</option>
                        <option value="laki-laki">Laki-laki</option>
                        <option value="perempuan">Perempuan</option>
                    </flux:select>
                    @error('gender')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div>
                <flux:label for="address">Alamat</flux:label>
                <flux:textarea wire:model="address" id="address" rows="2" placeholder="Alamat domisili..."
                    size="sm" />
                @error('address')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <flux:label for="password">{{ $editingId ? 'Password Baru (opsional)' : 'Password' }}</flux:label>
                <flux:input wire:model="password" id="password" type="password" placeholder="Minimal 8 karakter"
                    size="sm" {{ $editingId ? '' : 'required' }} />
                @error('password')
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
