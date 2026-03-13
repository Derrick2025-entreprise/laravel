<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\School;
use App\Models\Filiere;
use App\Models\Exam;
use App\Models\ExamFiliere;
use App\Models\Candidate;
use App\Models\CandidateExam;
use App\Models\Dossier;
use App\Models\Payment;
use App\Models\GeneratedDocument;
use App\Models\CompositionCenter;

class RealDataSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Création des données réelles pour SGEE...');

        // 1. Créer l'utilisateur admin spécifique
        $this->createSpecificAdmin();

        // 2. Créer des écoles
        $schools = $this->createSchools();

        // 3. Créer des filières
        $filieres = $this->createFilieres();

        // 4. Créer des centres de composition
        $centers = $this->createCompositionCenters();

        // 5. Créer des examens réalistes
        $exams = $this->createExams($schools);

        // 6. Associer filières aux examens
        $this->associateFilieresToExams($exams, $filieres);

        // 7. Créer des candidats réalistes
        $candidates = $this->createCandidates();

        // 8. Créer des inscriptions
        $this->createRegistrations($candidates, $exams);

        // 9. Créer des paiements
        $this->createPayments($candidates);

        // 10. Créer des documents
        $this->createDocuments();

        $this->command->info('✅ Données réelles créées avec succès !');
    }

    private function createSpecificAdmin()
    {
        $this->command->info('👤 Création de l\'utilisateur admin...');

        // Créer l'utilisateur admin spécifique
        $admin = User::create([
            'name' => 'Derrick Development',
            'email' => 'derrickdevelopment@gmail.com',
            'password' => Hash::make('Derrickdev123'),
            'email_verified_at' => now(),
        ]);

        // Assigner le rôle super_admin via la table user_roles
        DB::table('user_roles')->insert([
            'user_id' => $admin->id,
            'role_id' => 1, // Supposons que 1 = super_admin
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->command->info("✅ Admin créé : {$admin->email}");

        // Créer quelques autres admins
        $admins = [
            [
                'name' => 'Dr NKONJOH NGOMADE Armel',
                'email' => 'armel.nkonjoh@sgee.cm',
                'role_id' => 1 // super_admin
            ],
            [
                'name' => 'M. KEMKENG Aurélien',
                'email' => 'aurelien.kemkeng@sgee.cm',
                'role_id' => 2 // admin_ecole
            ],
            [
                'name' => 'Admin Système',
                'email' => 'admin@sgee.cm',
                'role_id' => 2 // admin_ecole
            ]
        ];

        foreach ($admins as $adminData) {
            $user = User::create([
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);

            // Assigner le rôle
            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role_id' => $adminData['role_id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    private function createSchools()
    {
        $this->command->info('🏫 Création des écoles...');

        $schoolsData = [
            [
                'nom' => 'École Supérieure de Technologie',
                'code' => 'EST',
                'adresse' => 'Douala, Cameroun',
                'telephone' => '+237 233 42 15 67',
                'email' => 'contact@est.cm',
                'directeur' => 'Prof. Jean MBALLA'
            ],
            [
                'nom' => 'Institut Universitaire de Technologie',
                'code' => 'IUT',
                'adresse' => 'Yaoundé, Cameroun',
                'telephone' => '+237 222 31 45 89',
                'email' => 'info@iut.cm',
                'directeur' => 'Dr. Marie FOUDA'
            ],
            [
                'nom' => 'École Nationale Supérieure Polytechnique',
                'code' => 'ENSP',
                'adresse' => 'Yaoundé, Cameroun',
                'telephone' => '+237 222 23 40 72',
                'email' => 'contact@ensp.cm',
                'directeur' => 'Prof. Paul NDJOMO'
            ]
        ];

        $schools = collect();
        foreach ($schoolsData as $data) {
            $school = School::create($data);
            $schools->push($school);
        }

        return $schools;
    }

    private function createFilieres()
    {
        $this->command->info('📚 Création des filières...');

        $filieresData = [
            [
                'nom' => 'Génie Informatique',
                'code' => 'GI',
                'description' => 'Formation en développement logiciel et systèmes informatiques',
                'duree_formation' => 3,
                'niveau_requis' => 'Baccalauréat C, D, E'
            ],
            [
                'nom' => 'Génie Civil',
                'code' => 'GC',
                'description' => 'Formation en construction et travaux publics',
                'duree_formation' => 3,
                'niveau_requis' => 'Baccalauréat C, D, E'
            ],
            [
                'nom' => 'Génie Électrique',
                'code' => 'GE',
                'description' => 'Formation en électricité et électronique',
                'duree_formation' => 3,
                'niveau_requis' => 'Baccalauréat C, D, E'
            ],
            [
                'nom' => 'Génie Mécanique',
                'code' => 'GM',
                'description' => 'Formation en mécanique et maintenance industrielle',
                'duree_formation' => 3,
                'niveau_requis' => 'Baccalauréat C, D, E'
            ],
            [
                'nom' => 'Gestion des Entreprises',
                'code' => 'GE',
                'description' => 'Formation en management et gestion d\'entreprise',
                'duree_formation' => 2,
                'niveau_requis' => 'Baccalauréat A, B, C, D'
            ],
            [
                'nom' => 'Comptabilité et Finance',
                'code' => 'CF',
                'description' => 'Formation en comptabilité et finances',
                'duree_formation' => 2,
                'niveau_requis' => 'Baccalauréat A, B, C, D'
            ]
        ];

        $filieres = collect();
        foreach ($filieresData as $data) {
            $filiere = Filiere::create($data);
            $filieres->push($filiere);
        }

        return $filieres;
    }

    private function createCompositionCenters()
    {
        $this->command->info('🏢 Création des centres de composition...');

        $centersData = [
            [
                'nom' => 'Centre de Douala',
                'adresse' => 'Lycée de Deido, Douala',
                'ville' => 'Douala',
                'capacite' => 500,
                'responsable' => 'M. TALLA Pierre'
            ],
            [
                'nom' => 'Centre de Yaoundé',
                'adresse' => 'Lycée Général Leclerc, Yaoundé',
                'ville' => 'Yaoundé',
                'capacite' => 600,
                'responsable' => 'Mme NGONO Marie'
            ],
            [
                'nom' => 'Centre de Bafoussam',
                'adresse' => 'Lycée Classique de Bafoussam',
                'ville' => 'Bafoussam',
                'capacite' => 300,
                'responsable' => 'M. KAMGA Jean'
            ]
        ];

        $centers = collect();
        foreach ($centersData as $data) {
            $center = CompositionCenter::create($data);
            $centers->push($center);
        }

        return $centers;
    }

    private function createExams($schools)
    {
        $this->command->info('📝 Création des examens...');

        $examsData = [
            [
                'nom' => 'Concours d\'Entrée 2024-2025',
                'description' => 'Concours d\'entrée en première année pour l\'année académique 2024-2025',
                'date_debut' => '2024-12-01',
                'date_fin' => '2024-12-31',
                'date_examen' => '2025-01-15',
                'heure_debut' => '08:00:00',
                'heure_fin' => '12:00:00',
                'duree_minutes' => 240,
                'montant_inscription' => 25000,
                'nombre_places' => 200,
                'status' => 'published',
                'is_public' => true,
                'publie' => true
            ],
            [
                'nom' => 'Concours Professionnel 2024',
                'description' => 'Concours pour les formations professionnelles courtes',
                'date_debut' => '2024-11-15',
                'date_fin' => '2024-12-15',
                'date_examen' => '2025-01-20',
                'heure_debut' => '14:00:00',
                'heure_fin' => '17:00:00',
                'duree_minutes' => 180,
                'montant_inscription' => 20000,
                'nombre_places' => 150,
                'status' => 'published',
                'is_public' => true,
                'publie' => true
            ],
            [
                'nom' => 'Concours Spécial Informatique',
                'description' => 'Concours spécialisé pour les filières informatiques',
                'date_debut' => '2024-12-15',
                'date_fin' => '2025-01-15',
                'date_examen' => '2025-02-01',
                'heure_debut' => '09:00:00',
                'heure_fin' => '13:00:00',
                'duree_minutes' => 240,
                'montant_inscription' => 30000,
                'nombre_places' => 100,
                'status' => 'published',
                'is_public' => true,
                'publie' => true
            ]
        ];

        $exams = collect();
        foreach ($examsData as $data) {
            $data['school_id'] = $schools->random()->id;
            $exam = Exam::create($data);
            $exams->push($exam);
        }

        return $exams;
    }

    private function associateFilieresToExams($exams, $filieres)
    {
        $this->command->info('🔗 Association des filières aux examens...');

        foreach ($exams as $exam) {
            // Associer 2-4 filières par examen
            $selectedFilieres = $filieres->random(rand(2, 4));
            
            foreach ($selectedFilieres as $filiere) {
                ExamFiliere::create([
                    'exam_id' => $exam->id,
                    'filiere_id' => $filiere->id,
                    'nombre_places' => rand(20, 50),
                    'montant_inscription' => $exam->montant_inscription
                ]);
            }
        }
    }

    private function createCandidates()
    {
        $this->command->info('👥 Création des candidats...');

        $candidatesData = [
            [
                'name' => 'MBALLA Jean Claude',
                'email' => 'jean.mballa@email.com',
                'telephone' => '+237 677 12 34 56',
                'date_naissance' => '2002-03-15',
                'lieu_naissance' => 'Douala',
                'sexe' => 'M',
                'niveau_etude' => 'Baccalauréat C'
            ],
            [
                'name' => 'FOUDA Marie Claire',
                'email' => 'marie.fouda@email.com',
                'telephone' => '+237 655 98 76 54',
                'date_naissance' => '2001-07-22',
                'lieu_naissance' => 'Yaoundé',
                'sexe' => 'F',
                'niveau_etude' => 'Baccalauréat D'
            ],
            [
                'name' => 'NKOMO Paul Brice',
                'email' => 'paul.nkomo@email.com',
                'telephone' => '+237 699 11 22 33',
                'date_naissance' => '2002-11-08',
                'lieu_naissance' => 'Bafoussam',
                'sexe' => 'M',
                'niveau_etude' => 'Baccalauréat E'
            ],
            [
                'name' => 'TALLA Sandrine',
                'email' => 'sandrine.talla@email.com',
                'telephone' => '+237 678 44 55 66',
                'date_naissance' => '2001-12-03',
                'lieu_naissance' => 'Bamenda',
                'sexe' => 'F',
                'niveau_etude' => 'Baccalauréat A'
            ],
            [
                'name' => 'KAMGA Rodrigue',
                'email' => 'rodrigue.kamga@email.com',
                'telephone' => '+237 654 77 88 99',
                'date_naissance' => '2002-05-18',
                'lieu_naissance' => 'Dschang',
                'sexe' => 'M',
                'niveau_etude' => 'Baccalauréat C'
            ]
        ];

        $candidates = collect();
        foreach ($candidatesData as $data) {
            // Créer l'utilisateur
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password123'),
                'role' => 'candidat',
                'email_verified_at' => now(),
            ]);

            // Créer le profil candidat
            $candidate = Candidate::create([
                'user_id' => $user->id,
                'telephone' => $data['telephone'],
                'date_naissance' => $data['date_naissance'],
                'lieu_naissance' => $data['lieu_naissance'],
                'sexe' => $data['sexe'],
                'niveau_etude' => $data['niveau_etude'],
                'statut' => 'actif'
            ]);

            $candidates->push($candidate);
        }

        return $candidates;
    }

    private function createRegistrations($candidates, $exams)
    {
        $this->command->info('📋 Création des inscriptions...');

        foreach ($candidates as $candidate) {
            // Chaque candidat s'inscrit à 1-2 examens
            $selectedExams = $exams->random(rand(1, 2));
            
            foreach ($selectedExams as $exam) {
                $examFilieres = ExamFiliere::where('exam_id', $exam->id)->get();
                if ($examFilieres->isNotEmpty()) {
                    $selectedFiliere = $examFilieres->random();
                    
                    $registration = CandidateExam::create([
                        'candidate_id' => $candidate->id,
                        'exam_id' => $exam->id,
                        'exam_filiere_id' => $selectedFiliere->id,
                        'statut' => collect(['inscrit', 'confirme', 'en_attente'])->random(),
                        'registered_at' => now()->subDays(rand(1, 30)),
                        'numero_candidat' => 'SGEE' . str_pad($candidate->id, 4, '0', STR_PAD_LEFT) . rand(100, 999)
                    ]);

                    // Créer le dossier correspondant
                    Dossier::create([
                        'candidate_id' => $candidate->id,
                        'candidate_exam_id' => $registration->id,
                        'etat_dossier' => collect(['complet', 'incomplet', 'en_cours'])->random(),
                        'progress_percentage' => rand(50, 100),
                        'date_soumission' => rand(0, 1) ? now()->subDays(rand(1, 15)) : null
                    ]);
                }
            }
        }
    }

    private function createPayments($candidates)
    {
        $this->command->info('💳 Création des paiements...');

        $registrations = CandidateExam::with('candidate')->get();
        
        foreach ($registrations as $registration) {
            Payment::create([
                'candidate_id' => $registration->candidate_id,
                'candidate_exam_id' => $registration->id,
                'montant' => rand(20000, 30000),
                'mode_paiement' => collect(['mobile_money', 'virement', 'especes'])->random(),
                'reference_paiement' => 'PAY' . time() . rand(1000, 9999),
                'statut_paiement' => collect(['valide', 'en_attente', 'rejete'])->random(),
                'date_paiement' => now()->subDays(rand(1, 20)),
                'validateur_id' => User::where('role', 'super_admin')->first()->id ?? null,
                'date_validation' => rand(0, 1) ? now()->subDays(rand(1, 10)) : null
            ]);
        }
    }

    private function createDocuments()
    {
        $this->command->info('📄 Création des documents...');

        $registrations = CandidateExam::with('candidate.user')->get();
        
        foreach ($registrations as $registration) {
            // Créer 1-3 documents par inscription
            $documentTypes = ['fiche_enrolement', 'convocation', 'quitus'];
            $selectedTypes = collect($documentTypes)->random(rand(1, 3));
            
            foreach ($selectedTypes as $type) {
                GeneratedDocument::create([
                    'candidate_exam_id' => $registration->id,
                    'type_document' => $type,
                    'nom_fichier' => ucfirst(str_replace('_', ' ', $type)) . '_' . $registration->candidate->user->name . '.pdf',
                    'chemin_fichier' => 'documents/' . $type . '/' . $registration->id . '.pdf',
                    'taille_fichier' => rand(100000, 500000),
                    'statut' => 'genere',
                    'qr_code' => 'SGEE-' . strtoupper($type) . '-' . $registration->id . '-' . time(),
                    'hash_verification' => hash('sha256', $registration->id . $type . time())
                ]);
            }
        }
    }
}