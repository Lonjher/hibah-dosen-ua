<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    public string $nidn = '';
    public string $full_name = '';
    public ?string $birthday = null;
    public string $gender = '';
    public string $address = '';
    public ?string $phone_number = null;
    public ?string $email = '';
    public ?string $password = null;
    public ?string $raw_password = null;
    public ?string $password_confirmation = null;
    public ?int $role_id = null;

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        $userId = $this->user?->id ?? 'NULL';

        return [
            'nidn' => [
                'required',
                'string',
                'max:255',
                'unique:users,nidn,' . $userId,
            ],

            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'birthday' => [
                'required',
                'date',
            ],

            'gender' => [
                'required',
                'string',
                'in:laki-laki,perempuan',
            ],

            'address' => [
                'required',
                'string',
                'max:255',
            ],

            'phone_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $userId,
            ],

            'password' => $this->user
                ? [
                    'nullable',
                    'string',
                    'min:8',
                    'confirmed',
                ]
                : [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],

            'role_id' => [
                'required',
                'exists:roles,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nidn.required' => 'NIDN is required.',
            'nidn.unique' => 'NIDN has already been taken.',
            'nidn.max' => 'NIDN must not exceed 255 characters.',

            'full_name.required' => 'Full name is required.',
            'full_name.max' => 'Full name must not exceed 255 characters.',

            'birthday.required' => 'Date of birth is required.',
            'birthday.date' => 'Date of birth must be a valid date.',

            'gender.required' => 'Gender is required.',
            'gender.in' => 'Gender must be either male or female.',

            'address.required' => 'Address is required.',
            'address.max' => 'Address must not exceed 255 characters.',

            'phone_number.max' => 'Phone number must not exceed 255 characters.',

            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'Email has already been taken.',
            'email.max' => 'Email must not exceed 255 characters.',

            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',

            'role_id.required' => 'Role is required.',
            'role_id.exists' => 'Invalid role.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setUser(User $user): void
    {
        $this->user = $user;

        $this->nidn = $user->nidn;
        $this->full_name = $user->full_name;
        $this->birthday = $user->birthday?->format('Y-m-d');
        $this->gender = $user->gender;
        $this->address = $user->address;
        $this->phone_number = $user->phone_number;
        $this->email = $user->email;
        $this->raw_password = $user->raw_password;
        $this->role_id = $user->role_id;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): User
    {
        $this->validate();

        $user = User::create([
            'nidn' => $this->nidn,
            'full_name' => $this->full_name,
            'birthday' => $this->birthday,
            'gender' => $this->gender,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'raw_password' => $this->password,
            'role_id' => $this->role_id,
        ]);

        $this->reset();

        return $user;
    }

    public function update(): User
    {
        if (!$this->user) {
            throw new \RuntimeException(
                'No user loaded. Call setUser() before update().'
            );
        }

        $this->validate();

        $data = [
            'nidn' => $this->nidn,
            'full_name' => $this->full_name,
            'birthday' => $this->birthday,
            'gender' => $this->gender,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'role_id' => $this->role_id,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
            $data['raw_password'] = $this->password;
        }

        $this->user->update($data);

        $updated = $this->user;

        $this->reset();

        return $updated;
    }
}
