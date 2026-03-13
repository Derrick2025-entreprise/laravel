<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\School;
use App\Models\Department;
use App\Models\Filiere;
use App\Models\ExamCenter;
use App\Models\Student;
use App\Models\Payment;

class DatabaseSeeder extends Seeder
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

        // Créer le compte administrateur pour Derrick Development
        $derrickPassword = \Illuminate\Support\Str::random(12);
        $derrickAdmin = User::firstOrCreate(
            ['email' => 'derrick.development@sgee-cameroun.cm'],
            [
                'name' => 'Derrick Development',
                'password' => Hash::make($derrickPassword),
                'role' => 'admin',
                'phone' => '+237 6XX XXX XXX',
                'address' => 'Cameroun',
                'email_verified_at' => now()
            ]
        );

        // Afficher le mot de passe généré pour Derrick
        $this->command->info("=== COMPTE ADMINISTRATEUR DERRICK DEVELOPMENT ===");
        $this->command->info("Email: derrick.development@sgee-cameroun.cm");
        $this->command->info("Mot de passe: {$derrickPassword}");
        $this->command->info("===============================================");

        // Créer un utilisateur étudiant de test
        $student_user = User::firstOrCreate(
            ['email' => 'jean.dupont@example.com'],
            [
                'name' => 'Jean Dupont',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'phone' => '+237 6XX XXX XXX',
                'address' => 'Douala, Cameroun',
                'email_verified_at' => now()
            ]
        );

        // Créer les écoles
        $ensp = School::firstOrCreate(
            ['sigle' => 'ENSP'],
            [
                'name' => 'École Nationale Supérieure Polytechnique',
                'address' => 'Yaoundé, Cameroun',
                'city' => 'Yaoundé',
                'region' => 'Centre',
                'telephone' => '+237 222 223 406',
                'email' => 'info@ensp-cameroun.com',
                'website' => 'https://ensp-cameroun.com',
                'description' => 'Formation d\'ingénieurs de haut niveau',
                'status' => 'validee'
            ]
        );

        $fmsb = School::firstOrCreate(
            ['sigle' => 'FMSB'],
            [
                'name' => 'Faculté de Médecine et des Sciences Biomédicales',
                'address' => 'Yaoundé, Cameroun',
                'city' => 'Yaoundé',
                'region' => 'Centre',
                'telephone' => '+237 222 201 369',
                'email' => 'info@fmsb-uy1.cm',
                'website' => 'https://fmsb-uy1.cm',
                'description' => 'Formation médicale et sciences de la santé',
                'status' => 'validee'
            ]
        );

        $essec = School::firstOrCreate(
            ['sigle' => 'ESSEC'],
            [
                'name' => 'École Supérieure des Sciences Économiques et Commerciales',
                'address' => 'Douala, Cameroun',
                'city' => 'Douala',
                'region' => 'Littoral',
                'telephone' => '+237 233 427 134',
                'email' => 'info@essec-cameroun.com',
                'website' => 'https://essec-cameroun.com',
                'description' => 'Formation en sciences économiques et commerciales',
                'status' => 'validee'
            ]
        );

        $iut = School::firstOrCreate(
            ['sigle' => 'IUT'],
            [
                'name' => 'Institut Universitaire de Technologie',
                'address' => 'Douala, Cameroun',
                'city' => 'Douala',
                'region' => 'Littoral',
                'telephone' => '+237 233 401 234',
                'email' => 'info@iut-douala.cm',
                'website' => 'https://iut-douala.cm',
                'description' => 'Formation technologique et professionnelle',
                'status' => 'validee'
            ]
        );

        // Créer les centres d'examens
        $centers = [
            [
                'name' => 'Centre d\'Examens de Yaoundé',
                'sigle' => 'CE-YDE',
                'address' => 'Quartier Nlongkak, Yaoundé',
                'city' => 'Yaoundé',
                'region' => 'Centre',
                'capacity' => 1000,
                'facilities' => ['Salles climatisées', 'Équipements informatiques', 'Parking', 'Restauration']
            ],
            [
                'name' => 'Centre d\'Examens de Douala',
                'sigle' => 'CE-DLA',
                'address' => 'Akwa, Douala',
                'city' => 'Douala',
                'region' => 'Littoral',
                'capacity' => 800,
                'facilities' => ['Salles climatisées', 'Équipements informatiques', 'Parking']
            ],
            [
                'name' => 'Centre d\'Examens de Bafoussam',
                'sigle' => 'CE-BFS',
                'address' => 'Centre-ville, Bafoussam',
                'city' => 'Bafoussam',
                'region' => 'Ouest',
                'capacity' => 500,
                'facilities' => ['Salles équipées', 'Parking']
            ],
            [
                'name' => 'Centre d\'Examens de Garoua',
                'sigle' => 'CE-GRA',
                'address' => 'Centre-ville, Garoua',
                'city' => 'Garoua',
                'region' => 'Nord',
                'capacity' => 400,
                'facilities' => ['Salles équipées']
            ],
            [
                'name' => 'Centre d\'Examens de Bamenda',
                'sigle' => 'CE-BMD',
                'address' => 'Commercial Avenue, Bamenda',
                'city' => 'Bamenda',
                'region' => 'Nord-Ouest',
                'capacity' => 600,
                'facilities' => ['Salles climatisées', 'Parking']
            ]
        ];

        foreach ($centers as $centerData) {
            ExamCenter::firstOrCreate(
                ['sigle' => $centerData['sigle']],
                $centerData
            );
        }

        // Créer les départements
        $dept_genie_civil = Department::firstOrCreate(
            ['code' => 'GC'],
            [
                'name' => 'Génie Civil',
                'description' => 'Formation en génie civil et travaux publics',
                'school_id' => $ensp->id,
                'head_of_department' => 'Prof. Martin KOUAM',
                'contact_email' => 'gc@ensp-cameroun.com',
                'contact_phone' => '+237 222 223 407',
                'created_by' => $admin->id
            ]
        );

        $dept_informatique = Department::firstOrCreate(
            ['code' => 'GI'],
            [
                'name' => 'Génie Informatique',
                'description' => 'Formation en informatique et nouvelles technologies',
                'school_id' => $ensp->id,
                'head_of_department' => 'Prof. Marie TCHUENTE',
                'contact_email' => 'gi@ensp-cameroun.com',
                'contact_phone' => '+237 222 223 408',
                'created_by' => $admin->id
            ]
        );

        $dept_medecine = Department::firstOrCreate(
            ['code' => 'MG'],
            [
                'name' => 'Médecine Générale',
                'description' => 'Formation médicale générale',
                'school_id' => $fmsb->id,
                'head_of_department' => 'Prof. Paul NDOM',
                'contact_email' => 'mg@fmsb-uy1.cm',
                'contact_phone' => '+237 222 201 370',
                'created_by' => $admin->id
            ]
        );

        $dept_gestion = Department::firstOrCreate(
            ['code' => 'GE'],
            [
                'name' => 'Gestion des Entreprises',
                'description' => 'Formation en gestion et management',
                'school_id' => $essec->id,
                'head_of_department' => 'Prof. Claire MBALLA',
                'contact_email' => 'ge@essec-cameroun.com',
                'contact_phone' => '+237 233 427 135',
                'created_by' => $admin->id
            ]
        );

        $dept_tech_info = Department::firstOrCreate(
            ['code' => 'TI'],
            [
                'name' => 'Technologies de l\'Information',
                'description' => 'Formation technologique en informatique',
                'school_id' => $iut->id,
                'head_of_department' => 'Dr. Jean FOTSO',
                'contact_email' => 'ti@iut-douala.cm',
                'contact_phone' => '+237 233 401 235',
                'created_by' => $admin->id
            ]
        );

        // Créer les filières
        $filieres = [
            // ENSP - Génie Civil
            [
                'name' => 'Ingénieur en Génie Civil',
                'code' => 'IGC',
                'description' => 'Formation d\'ingénieurs en génie civil',
                'department_id' => $dept_genie_civil->id,
                'school_id' => $ensp->id,
                'duration_years' => 5,
                'enrollment_fee' => 50000,
                'tuition_fee' => 200000,
                'capacity' => 80,
                'requirements' => ['Baccalauréat C ou D', 'Moyenne >= 12/20', 'Âge <= 23 ans']
            ],
            // ENSP - Informatique
            [
                'name' => 'Ingénieur en Informatique',
                'code' => 'II',
                'description' => 'Formation d\'ingénieurs informaticiens',
                'department_id' => $dept_informatique->id,
                'school_id' => $ensp->id,
                'duration_years' => 5,
                'enrollment_fee' => 50000,
                'tuition_fee' => 200000,
                'capacity' => 100,
                'requirements' => ['Baccalauréat C ou D', 'Moyenne >= 12/20', 'Âge <= 23 ans']
            ],
            // FMSB - Médecine
            [
                'name' => 'Docteur en Médecine',
                'code' => 'DM',
                'description' => 'Formation de médecins généralistes',
                'department_id' => $dept_medecine->id,
                'school_id' => $fmsb->id,
                'duration_years' => 7,
                'enrollment_fee' => 75000,
                'tuition_fee' => 300000,
                'capacity' => 120,
                'requirements' => ['Baccalauréat C ou D', 'Moyenne >= 14/20', 'Âge <= 25 ans']
            ],
            // ESSEC - Gestion
            [
                'name' => 'Master en Gestion des Entreprises',
                'code' => 'MGE',
                'description' => 'Formation en management et gestion',
                'department_id' => $dept_gestion->id,
                'school_id' => $essec->id,
                'duration_years' => 3,
                'enrollment_fee' => 40000,
                'tuition_fee' => 150000,
                'capacity' => 150,
                'requirements' => ['Baccalauréat A4 ou G', 'Moyenne >= 10/20', 'Âge <= 25 ans']
            ],
            // IUT - Technologies
            [
                'name' => 'DUT en Technologies de l\'Information',
                'code' => 'DUTTI',
                'description' => 'Formation technologique en informatique',
                'department_id' => $dept_tech_info->id,
                'school_id' => $iut->id,
                'duration_years' => 2,
                'enrollment_fee' => 30000,
                'tuition_fee' => 100000,
                'capacity' => 200,
                'requirements' => ['Baccalauréat C, D ou F', 'Moyenne >= 10/20', 'Âge <= 25 ans']
            ]
        ];

        foreach ($filieres as $filiereData) {
            Filiere::firstOrCreate(
                ['code' => $filiereData['code']],
                [
                    ...$filiereData,
                    'created_by' => $admin->id
                ]
            );
        }

        // Créer quelques étudiants de test
        $filiere_informatique = Filiere::where('code', 'II')->first();
        $filiere_medecine = Filiere::where('code', 'DM')->first();

        if ($filiere_informatique) {
            $student1 = Student::firstOrCreate(
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

            // Créer un paiement pour cet étudiant seulement s'il n'existe pas
            if (!Payment::where('student_id', $student1->id)->exists()) {
                Payment::create([
                    'student_id' => $student1->id,
                    'user_id' => $student_user->id,
                    'payment_type' => 'enrollment_fee',
                    'amount' => 50000,
                    'payment_method' => 'bank_transfer',
                    'reference_number' => Payment::generateReferenceNumber(),
                    'status' => 'validated',
                    'payment_date' => now(),
                    'validated_at' => now(),
                    'validated_by' => $admin->id,
                    'academic_year' => '2026-2027'
                ]);
            }
        }

        // Créer d'autres étudiants
        $other_students = [
            [
                'name' => 'Marie NGONO',
                'email' => 'marie.ngono@example.com',
                'first_name' => 'Marie',
                'last_name' => 'NGONO',
                'phone' => '+237 677 234 567',
                'filiere' => $filiere_medecine
            ],
            [
                'name' => 'Paul BIYA',
                'email' => 'paul.biya@example.com',
                'first_name' => 'Paul',
                'last_name' => 'BIYA',
                'phone' => '+237 677 345 678',
                'filiere' => $filiere_informatique
            ]
        ];

        foreach ($other_students as $studentData) {
            if ($studentData['filiere']) {
                $user = User::firstOrCreate(
                    ['email' => $studentData['email']],
                    [
                        'name' => $studentData['name'],
                        'password' => Hash::make('password123'),
                        'role' => 'student',
                        'phone' => $studentData['phone'],
                        'email_verified_at' => now()
                    ]
                );

                Student::firstOrCreate(
                    ['email' => $studentData['email']],
                    [
                        'user_id' => $user->id,
                        'student_number' => Student::generateStudentNumber($studentData['filiere']->school_id),
                        'first_name' => $studentData['first_name'],
                        'last_name' => $studentData['last_name'],
                        'phone' => $studentData['phone'],
                        'date_of_birth' => '2001-03-20',
                        'place_of_birth' => 'Yaoundé',
                        'gender' => $studentData['first_name'] === 'Marie' ? 'F' : 'M',
                        'nationality' => 'Camerounaise',
                        'address' => 'Yaoundé, Cameroun',
                        'filiere_id' => $studentData['filiere']->id,
                        'department_id' => $studentData['filiere']->department_id,
                        'school_id' => $studentData['filiere']->school_id,
                        'enrollment_date' => now()->subDays(rand(1, 30)),
                        'academic_year' => '2026-2027',
                        'status' => 'enrolled'
                    ]
                );
            }
        }

        $this->command->info('Base de données initialisée avec succès !');
        $this->command->info('Utilisateur admin: admin@sgee-cameroun.cm / admin123');
        $this->command->info('Utilisateur étudiant: jean.dupont@example.com / password123');
        $this->command->info("Utilisateur Derrick Development: derrick.development@sgee-cameroun.cm / {$derrickPassword}");
    }
}