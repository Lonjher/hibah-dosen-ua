<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    public string  $nidn = '';
    public string  $full_name = '';
    public ?string $birthday = null;
    public string  $gender = '';
    public string  $address = '';
    public ?string $phone_number = null;
    public ?string $email = '';
    public ?string $password = null;
    public ?string $password_confirmation = null;
    public ?int    $role_id = null;

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        $userId = $this->user?->id ?? 'NULL';

        return [
            'nidn'        => ['required', 'string', 'max:255', 'unique:users,nidn,'.$userId],
            'full_name'   => ['required', 'string', 'max:255'],
            'birthday'    => ['required', 'date'],
            'gender'      => ['required', 'string', 'in:laki-laki,perempuan'],
            'address'     => ['required', 'string', 'max:255'],
            'phone_number'=> ['nullable', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', 'unique:users,email,'.$userId],
            'password'    => $this->user
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
            'role_id'     => ['required', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nidn.required'      => 'NIDN wajib diisi.',
            'nidn.unique'        => 'NIDN sudah digunakan.',
            'nidn.max'           => 'NIDN maksimal 255 karakter.',

            'full_name.required' => 'Nama lengkap wajib diisi.',
            'full_name.max'      => 'Nama lengkap maksimal 255 karakter.',

            'birthday.required'  => 'Tanggal lahir wajib diisi.',
            'birthday.date'      => 'Tanggal lahir harus tanggal valid.',

            'gender.required'    => 'Jenis kelamin wajib dipilih.',
            'gender.in'          => 'Jenis kelamin harus laki-laki atau perempuan.',

            'address.required'   => 'Alamat wajib diisi.',
            'address.max'        => 'Alamat maksimal 255 karakter.',

            'phone_number.max'   => 'Nomor telepon maksimal 255 karakter.',

            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Email harus format email valid.',
            'email.unique'       => 'Email sudah digunakan.',
            'email.max'          => 'Email maksimal 255 karakter.',

            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',

            'role_id.required'   => 'Role wajib dipilih.',
            'role_id.exists'     => 'Role tidak valid.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setUser(User $user): void
    {
        $this->user        = $user;
        $this->nidn        = $user->nidn;
        $this->full_name   = $user->full_name;
        $this->birthday    = $user->birthday?->format('Y-m-d');
        $this->gender      = $user->gender;
        $this->address     = $user->address;
        $this->phone_number= $user->phone_number;
        $this->email       = $user->email;
        $this->role_id     = $user->role_id;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): User
    {
        $this->validate();

        $user = User::create([
            'nidn'         => $this->nidn,
            'full_name'    => $this->full_name,
            'birthday'     => $this->birthday,
            'gender'       => $this->gender,
            'address'      => $this->address,
            'phone_number' => $this->phone_number,
            'email'        => $this->email,
            'password'     => Hash::make($this->password),
            'role_id'      => $this->role_id,
        ]);

        $this->reset();

        return $user;
    }

    public function update(): User
    {
        if (! $this->user) {
            throw new \RuntimeException(
                'No user loaded. Call setUser() before update().'
            );
        }

        $this->validate();

        $data = [
            'nidn'         => $this->nidn,
            'full_name'    => $this->full_name,
            'birthday'     => $this->birthday,
            'gender'       => $this->gender,
            'address'      => $this->address,
            'phone_number' => $this->phone_number,
            'email'        => $this->email,
            'role_id'      => $this->role_id,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        $this->user->update($data);

        $updated = $this->user;

        $this->reset();

        return $updated;
    }
}
