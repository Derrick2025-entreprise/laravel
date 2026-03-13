<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Créer les rôles de base
        $roles = [
            [
                'name' => 'Super Administrateur',
                'slug' => 'super_admin',
                'description' => 'Accès complet au système'
            ],
            [
                'name' => 'Administrateur École',
                'slug' => 'admin_ecole',
                'description' => 'Gestion des examens et candidats'
            ],
            [
                'name' => 'Candidat',
                'slug' => 'candidat',
                'description' => 'Candidat aux examens'
            ]
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }

        // Créer les permissions de base
        $permissions = [
            ['name' => 'Gérer les examens', 'slug' => 'manage_exams'],
            ['name' => 'Gérer les candidats', 'slug' => 'manage_candidates'],
            ['name' => 'Gérer les paiements', 'slug' => 'manage_payments'],
            ['name' => 'Gérer les résultats', 'slug' => 'manage_results'],
            ['name' => 'S\'inscrire aux examens', 'slug' => 'register_exams'],
            ['name' => 'Voir ses résultats', 'slug' => 'view_own_results'],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['slug' => $permData['slug']],
                $permData
            );
        }
    }
}