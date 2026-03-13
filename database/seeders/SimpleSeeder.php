<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\School;
use App\Models\Department;
use App\Models\Filiere;
use App\Models\Student;
use App\Models\Payment;

class SimpleSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer un utilisateur administrateur
        $admin = User::firstOrCreate(
            ['email' => 'admin@sgee-cameroun.cm'],
            [
                'name' => 'Administrateur SGEE',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now()
            ]
        );

        // Créer un utilisateur étudiant de test
        $student_user = User::firstOrCreate(
            ['email' => 'jean.dupont@example.com'],
            [
                'name' => 'Jean Dupont',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'phone' => '+237 677 123 456',
                'address' => 'Douala, Cameroun',
                'email_verified_at' => now()
            ]
        );

        // Récupérer les écoles existantes
        $ensp = School::where('sigle', 'ENSP')->first();
        $fmsb = School::where('sigle', 'FMSB')->first();

        if ($ensp && $fmsb) {
            // Créer des départements si ils n'existent pas
            $dept_informatique = Department::firstOrCreate(
                ['code' => 'GI', 'school_id' => $ensp->id],
                [
                    'name' => 'Génie Informatique',
                    'description' => 'Formation en informatique et nouvelles technologies',
                    'head_of_department' => 'Prof. Marie TCHUENTE',
                    'contact_email' => 'gi@ensp-cameroun.com',
                    'contact_phone' => '+237 222 223 408',
                    'created_by' => $admin->id
                ]
            );

            $dept_medecine = Department::firstOrCreate(
                ['code' => 'MG', 'school_id' => $fmsb->id],
                [
                    'name' => 'Médecine Générale',
                    'description' => 'Formation médicale générale',
                    'head_of_department' => 'Prof. Paul NDOM',
                    'contact_email' => 'mg@fmsb-uy1.cm',
                    'contact_phone' => '+237 222 201 370',
                    'created_by' => $admin->id
                ]
            );

            // Créer des filières
            $filiere_informatique = Filiere::firstOrCreate(
                ['code' => 'II'],
                [
                    'name' => 'Ingénieur en Informatique',
                    'description' => 'Formation d\'ingénieurs informaticiens',
                    'department_id' => $dept_informatique->id,
                    'school_id' => $ensp->id,
                    'duration_years' => 5,
                    'enrollment_fee' => 50000,
                    'tuition_fee' => 200000,
                    'capacity' => 100,
                    'requirements' => ['Baccalauréat C ou D', 'Moyenne >= 12/20', 'Âge <= 23 ans'],
                    'created_by' => $admin->id
                ]
            );

            $filiere_medecine = Filiere::firstOrCreate(
                ['code' => 'DM'],
                [
                    'name' => 'Docteur en Médecine',
                    'description' => 'Formation de médecins généralistes',
                    'department_id' => $dept_medecine->id,
                    'school_id' => $fmsb->id,
                    'duration_years' => 7,
                    'enrollment_fee' => 75000,
                    'tuition_fee' => 300000,
                    'capacity' => 120,
                    'requirements' => ['Baccalauréat C ou D', 'Moyenne >= 14/20', 'Âge <= 25 ans'],
                    'created_by' => $admin->id
                ]
            );

            // Créer un étudiant de test
            $student = Student::firstOrCreate(
                ['email' => 'jean.dupont@example.com'],
                [
                    'user_id' => $student_user->id,
                    'student_number' => Student::generateStudentNumber($filiere_informatique->school_id),
                    'first_name' => 'Jean',
                    'last_name' => 'DUPONT',
                    'phone' => '+237 677 123 456',
                    'date_of_birth' => '2000-05-15',
                    'place_of_birth' => 'Douala',
                    'gender' => 'M',
                    'nationality' => 'Camerounaise',
                    'address' => 'Akwa, Douala',
                    'emergency_contact_name' => 'Marie DUPONT',
                    'emergency_contact_phone' => '+237 677 123 457',
                    'filiere_id' => $filiere_informatique->id,
                    'department_id' => $filiere_informatique->department_id,
                    'school_id' => $filiere_informatique->school_id,
                    'enrollment_date' => now(),
                    'academic_year' => '2026-2027',
                    'status' => 'enrolled'
                ]
            );

            // Créer un paiement de test
            Payment::firstOrCreate(
                ['student_id' => $student->id, 'payment_type' => 'enrollment_fee'],
                [
                    'user_id' => $student_user->id,
                    'amount' => 50000,
                    'payment_method' => 'bank_transfer',
                    'reference_number' => Payment::generateReferenceNumber(),
                    'status' => 'validated',
                    'payment_date' => now(),
                    'validated_at' => now(),
                    'validated_by' => $admin->id,
                    'academic_year' => '2026-2027'
                ]
            );
        }

        $this->command->info('Données de test créées avec succès !');
        $this->command->info('Admin: admin@sgee-cameroun.cm / admin123');
        $this->command->info('Étudiant: jean.dupont@example.com / password123');
    }
}