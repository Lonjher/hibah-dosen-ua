<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // === Ambil role_id berdasarkan role_code (UPPERCASE) ===
        $superAdminRole = Role::query()->where('role_code', 'SUPERADMIN')->value('id');
        $adminRole      = Role::query()->where('role_code', 'ADMIN')->value('id');
        $reviewerRole   = Role::query()->where('role_code', 'REVIEWER')->value('id');
        $userRole       = Role::query()->where('role_code', 'USER')->value('id');

        // === Akun Tetap untuk Testing Manual ===

        // 1. Super Admin
        User::create([
            'nidn'              => '0000000000',
            'full_name'         => 'Super Admin',
            'birthday'          => '1990-01-01',
            'gender'            => 'laki-laki',
            'address'           => 'Jl. Makan Pahlawan Guluk-guluk Sumenep',
            'phone_number'      => '6285156752475',
            'email'             => 'walid.lonjer@gmail.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('19900101'),
            'raw_password'      => '19900101',
            'role_id'           => $superAdminRole,
        ]);

        // 2. Admin
        User::create([
            'nidn'              => '0000000001',
            'full_name'         => 'Admin Hibah',
            'birthday'          => '1991-02-02',
            'gender'            => 'laki-laki',
            'address'           => 'Jl. Makan Pahlawan Guluk-guluk Sumenep',
            'phone_number'      => '6285156752476',
            'email'             => 'siaprisetppmua@gmail.com',
            'password'          => Hash::make('19910102'),
            'raw_password'      => '19910102',
            'role_id'           => $adminRole,
        ]);

        // 3. Reviewer
        User::create([
            'nidn'              => '0000000002',
            'full_name'         => 'Reviewer Satu',
            'birthday'          => '1992-03-03',
            'gender'            => 'perempuan',
            'address'           => 'Jl. Makan Pahlawan Guluk-guluk Sumenep',
            'phone_number'      => '6285156752477',
            'email'             => 'reviewer@ua.ac.id',
            'password'          => Hash::make('19920303'),
            'raw_password'      => '19920303',
            'role_id'           => $reviewerRole,
        ]);

        // 4. User (Dosen Pengusul)
        User::create([
            'nidn'              => '0000000003',
            'full_name'         => 'Dosen Pengusul',
            'birthday'          => '1993-04-04',
            'gender'            => 'laki-laki',
            'address'           => 'Jl. Makan Pahlawan Guluk-guluk Sumenep',
            'phone_number'      => '6285156752478',
            'email'             => 'turnitinlppmua@gmail.com',
            'password'          => Hash::make('password'),
            'raw_password'      => 'password',
            'role_id'           => $userRole,
        ]);

        // === Akun Random untuk Testing (via Factory) ===
        // Comment/uncomment sesuai kebutuhan

        User::factory()->count(3)->admin()->create();
        User::factory()->count(5)->reviewer()->create();
        User::factory()->count(10)->user()->create();

        $this->command->info('✅ Users seeded: 4 fixed + 18 random');
    }
}
