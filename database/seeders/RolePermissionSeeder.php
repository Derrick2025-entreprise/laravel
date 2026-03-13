<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Créer les permissions
        $permissions = [
            // Permissions Écoles
            ['name' => 'Créer une école', 'slug' => 'create_school', 'category' => 'schools'],
            ['name' => 'Modifier une école', 'slug' => 'edit_school', 'category' => 'schools'],
            ['name' => 'Valider une école', 'slug' => 'validate_school', 'category' => 'schools'],
            ['name' => 'Lister les écoles', 'slug' => 'view_schools', 'category' => 'schools'],

            // Permissions Concours
            ['name' => 'Créer un concours', 'slug' => 'create_exam', 'category' => 'exams'],
            ['name' => 'Modifier un concours', 'slug' => 'edit_exam', 'category' => 'exams'],
            ['name' => 'Publier un concours', 'slug' => 'publish_exam', 'category' => 'exams'],
            ['name' => 'Lister les concours', 'slug' => 'view_exams', 'category' => 'exams'],

            // Permissions Candidats
            ['name' => 'Voir les candidats', 'slug' => 'view_candidates', 'category' => 'candidates'],
            ['name' => 'Valider candidat', 'slug' => 'validate_candidate', 'category' => 'candidates'],
            ['name' => 'Rejeter candidat', 'slug' => 'reject_candidate', 'category' => 'candidates'],

            // Permissions Inscriptions
            ['name' => 'S\'inscrire à un concours', 'slug' => 'register_exam', 'category' => 'registrations'],
            ['name' => 'Voir ses inscriptions', 'slug' => 'view_registrations', 'category' => 'registrations'],

            // Permissions Dossiers
            ['name' => 'Créer dossier', 'slug' => 'create_dossier', 'category' => 'dossiers'],
            ['name' => 'Modifier dossier', 'slug' => 'edit_dossier', 'category' => 'dossiers'],
            ['name' => 'Valider dossier', 'slug' => 'validate_dossier', 'category' => 'dossiers'],
            ['name' => 'Rejeter dossier', 'slug' => 'reject_dossier', 'category' => 'dossiers'],

            // Permissions Paiements
            ['name' => 'Voir paiements', 'slug' => 'view_payments', 'category' => 'payments'],
            ['name' => 'Valider paiement', 'slug' => 'validate_payment', 'category' => 'payments'],

            // Permissions Résultats
            ['name' => 'Saisir résultats', 'slug' => 'enter_results', 'category' => 'results'],
            ['name' => 'Publier résultats', 'slug' => 'publish_results', 'category' => 'results'],
            ['name' => 'Voir résultats', 'slug' => 'view_results', 'category' => 'results'],

            // Permissions Documents
            ['name' => 'Générer documents', 'slug' => 'generate_documents', 'category' => 'documents'],
            ['name' => 'Télécharger documents', 'slug' => 'download_documents', 'category' => 'documents'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Créer les rôles
        $roleData = [
            [
                'name' => 'Super Administrateur',
                'slug' => 'super_admin',
                'description' => 'Administrateur complet de la plateforme',
                'permissions' => ['create_school', 'edit_school', 'validate_school', 'view_schools', 
                                 'create_exam', 'edit_exam', 'publish_exam', 'view_exams',
                                 'view_candidates', 'validate_candidate', 'reject_candidate',
                                 'view_registrations', 'create_dossier', 'edit_dossier', 'validate_dossier', 'reject_dossier',
                                 'view_payments', 'validate_payment', 'enter_results', 'publish_results', 'view_results',
                                 'generate_documents', 'download_documents']
            ],
            [
                'name' => 'Administrateur École',
                'slug' => 'admin_ecole',
                'description' => 'Administrateur d\'une école partenaire',
                'permissions' => ['create_exam', 'edit_exam', 'publish_exam', 'view_exams',
                                 'view_candidates', 'validate_candidate', 'reject_candidate',
                                 'view_registrations', 'validate_dossier', 'reject_dossier',
                                 'view_payments', 'validate_payment', 'enter_results', 'publish_results', 'view_results',
                                 'generate_documents']
            ],
            [
                'name' => 'Agent Administratif',
                'slug' => 'agent_admin',
                'description' => 'Agent administratif',
                'permissions' => ['view_candidates', 'view_registrations', 'view_payments', 'validate_payment',
                                 'validate_dossier', 'reject_dossier', 'view_results', 'generate_documents']
            ],
            [
                'name' => 'Candidat',
                'slug' => 'candidat',
                'description' => 'Candidat à un concours',
                'permissions' => ['register_exam', 'view_registrations', 'create_dossier', 'edit_dossier',
                                 'download_documents', 'view_results']
            ],
            [
                'name' => 'Correcteur',
                'slug' => 'correcteur',
                'description' => 'Jury ou correcteur',
                'permissions' => ['view_candidates', 'view_registrations', 'enter_results', 'view_results']
            ],
        ];

        foreach ($roleData as $data) {
            $permissions = $data['permissions'];
            unset($data['permissions']);

            $role = Role::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            // Attacher les permissions au rôle
            foreach ($permissions as $permissionSlug) {
                $permission = Permission::where('slug', $permissionSlug)->first();
                if ($permission && !$role->permissions()->where('permission_id', $permission->id)->exists()) {
                    $role->permissions()->attach($permission->id);
                }
            }
        }

        $this->command->info('Rôles et permissions créés avec succès !');
    }
}
