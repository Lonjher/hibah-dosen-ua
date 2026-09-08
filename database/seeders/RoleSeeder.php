<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['role_name' => 'Super Admin', 'role_code' => 'SUPERADMIN'],
            ['role_name' => 'Admin', 'role_code' => 'ADMIN'],
            ['role_name' => 'Reviewer', 'role_code' => 'REVIEWER'],
            ['role_name' => 'User', 'role_code' => 'USER'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
