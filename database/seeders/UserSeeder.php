<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'nidn' => '0000000000',
            'full_name' => 'Super Admin',
            'birthday' => '1990-01-01',
            'gender' => 'laki-laki',
            'address' => 'Jl. Makan Pahlawan Guluk-guluk Sumenep',
            'phone_number' => '6285156752475',
            'email' => 'super.admin@ua.ac.id',
            'email_verified_at' => now(),
            'password' => Hash::make('19900101'),
            'role_id' => 1,
        ]);
    }
}
