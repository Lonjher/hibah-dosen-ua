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
        'gender' => 'required',
        'address' => 'required',
        'password' => 'required|string|min:8',
    ];

    public function render()
    {
        $reviewerRole = Role::where('role_code', 'REVIEWER')->firstOrFail();
        $reviewers = User::where('role_id', $reviewerRole->id)->withCount('reviewerNotes')->orderBy('full_name')->paginate(10);

        return $this->view([
            'reviewers' => $reviewers,
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(User $reviewer)
    {
        $this->editingId = $reviewer->id;
        $this->full_name = $reviewer->full_name;
        $this->email = $reviewer->email;
        $this->nidn = $reviewer->nidn ?? '';
        $this->phone_number = $reviewer->phone_number ?? '';
        $this->birthday = $reviewer->birthday ?? '';
        $this->gender = $reviewer->gender ?? '';
        $this->address = $reviewer->address ?? '';
        $this->password = '';
        $this->showModal = true;
    }

    public function save()
    {
        $this->rules['email'] = Rule::unique('users', 'email')->ignore($this->editingId);
        $this->validate();

        $reviewerRole = Role::where('role_code', 'REVIEWER')->firstOrFail();

        if ($this->editingId) {
            $reviewer = User::findOrFail($this->editingId);
            $reviewer->update([
                'full_name' => $this->full_name,
                'email' => $this->email,
                'nidn' => $this->nidn,
                'phone_number' => $this->phone_number,
                'birthday' => $this->birthday,
                'gender' => $this->gender,
                'address' => $this->address,
                'role_id' => $reviewerRole->id,
            ]);
            if ($this->password) {
                $reviewer->update(['password' => Hash::make($this->password)]);
            }
            Flux::toast('Data reviewer berhasil diperbarui.');
        } else {
            User::create([
                'full_name' => $this->full_name,
                'email' => $this->email,
                'nidn' => $this->nidn,
                'phone_number' => $this->phone_number,
                'birthday' => $this->birthday,
                'gender' => $this->gender,
                'address' => $this->address,
                'password' => Hash::make($this->password),
                'role_id' => $reviewerRole->id,
            ]);
            Flux::toast('Reviewer baru berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(User $reviewer)
    {
        if ($reviewer->reviewerNotes()->exists()) {
            Flux::toast('Tidak dapat menghapus reviewer yang memiliki catatan review.', variant: 'danger');
            return;
        }

        $reviewer->delete();
        Flux::toast('Reviewer dihapus.');
    }

    private function resetForm()
    {
        $this->reset(['full_name', 'email', 'nidn', 'phone_number', 'password', 'editingId']);
    }
};
?>

<div class="p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <x-dashboard-header icon="clipboard-document-check" title="Kelola Reviewer" leading="Manajemen akun pengguna dengan peran reviewer." />
        <flux:button icon="plus" wire:click="create" variant="primary" size="sm" class="shrink-0">Tambah Reviewer
        </flux:button>
    </div>
    <div
        class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl border border-white/80 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-200 text-xs">
                <thead
                    class="bg-emerald-50/50 dark:bg-emerald-900/20 text-left text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Nama</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">NIDN</th>
                        <th class="px-4 py-3 font-semibold">No. HP</th>
                        <th class="px-4 py-3 font-semibold text-center">Total Review</th>
                        <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @forelse($reviewers as $reviewer)
                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-[10px] shrink-0">
                                        {{ $reviewer->initials() }}
                                    </div>
                                    <span
                                        class="font-medium text-slate-900 dark:text-zinc-100">{{ $reviewer->full_name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $reviewer->email }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $reviewer->nidn }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                {{ $reviewer->phone_number }}</td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ $reviewer->reviewer_notes_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                        title="Menu aksi"
                                        class="rounded-lg text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-700/60" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $reviewer->id }})">
                                            Edit
                                        </flux:menu.item>

                                        <flux:menu.separator />

                                        <flux:menu.item variant="danger" icon="trash"
                                            wire:click="delete({{ $reviewer->id }})"
                                            wire:confirm="Yakin ingin menghapus Reviewer ini?">
                                            Hapus
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon.clipboard-document-check
                                        class="size-8 text-slate-300 dark:text-zinc-700" />
                                    <span>Belum ada reviewer. Klik "Tambah Reviewer" untuk membuat.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reviewers->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-zinc-800">
                {{ $reviewers->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" :title="$editingId ? 'Edit Reviewer' : 'Tambah Reviewer'"
        description="Lengkapi data akun dan profil reviewer." size="lg">
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
