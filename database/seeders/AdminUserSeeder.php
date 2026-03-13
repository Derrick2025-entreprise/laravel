<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Créer les rôles s'ils n'existent pas
        $roles = [
            ['name' => 'super_admin', 'slug' => 'super-admin', 'description' => 'Super Administrateur'],
            ['name' => 'admin_ecole', 'slug' => 'admin-ecole', 'description' => 'Administrateur École'],
            ['name' => 'agent_admin', 'slug' => 'agent-admin', 'description' => 'Agent Administratif'],
            ['name' => 'candidat', 'slug' => 'candidat', 'description' => 'Candidat'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'slug' => $roleData['slug'],
                    'description' => $roleData['description']
                ]
            );
        }

        // Créer un utilisateur administrateur
        $admin = User::firstOrCreate(
            ['email' => 'admin@sgee.cm'],
            [
                'name' => 'Administrateur SGEE',
                'email' => 'admin@sgee.cm',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Assigner le rôle super_admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole && !$admin->roles()->where('role_id', $superAdminRole->id)->exists()) {
            $admin->roles()->attach($superAdminRole->id);
        }

        // Créer un candidat de test
        $candidat = User::firstOrCreate(
            ['email' => 'candidat@test.cm'],
            [
                'name' => 'Candidat Test',
                'email' => 'candidat@test.cm',
                'password' => Hash::make('candidat123'),
                'email_verified_at' => now(),
            ]
        );

        // Assigner le rôle candidat
        $candidatRole = Role::where('name', 'candidat')->first();
        if ($candidatRole && !$candidat->roles()->where('role_id', $candidatRole->id)->exists()) {
            $candidat->roles()->attach($candidatRole->id);
        }

        $this->command->info('Utilisateurs créés avec succès!');
        $this->command->info('Admin: admin@sgee.cm / admin123');
        $this->command->info('Candidat: candidat@test.cm / candidat123');
    }
}