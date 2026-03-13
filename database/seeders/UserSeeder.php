<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create roles first
        $candidatRole = Role::firstOrCreate(['name' => 'candidat'], ['slug' => 'candidat', 'description' => 'Candidat']);
        $adminRole = Role::firstOrCreate(['name' => 'admin_ecole'], ['slug' => 'admin_ecole', 'description' => 'Admin École']);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['slug' => 'super_admin', 'description' => 'Super Administrateur']);

        // Create test users with full name in 'name' field
        User::create([
            'name' => 'Jean Dupont',
            'email' => 'candidat@test.com',
            'telephone' => '+237690123456',
            'password' => Hash::make('Password123!'),
        ]);

        User::create([
            'name' => 'Pierre Martin',
            'email' => 'candidat2@test.com',
            'telephone' => '+237699999999',
            'password' => Hash::make('Password123!'),
        ]);

        User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'telephone' => '+237699888888',
            'password' => Hash::make('Password123!'),
        ]);

        User::create([
            'name' => 'System SuperAdmin',
            'email' => 'superadmin@test.com',
            'telephone' => '+237699777777',
            'password' => Hash::make('Password123!'),
        ]);
    }
}
