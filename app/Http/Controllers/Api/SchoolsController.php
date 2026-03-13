<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Exam;
use App\Models\ExamFiliere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SchoolsController extends Controller
{
    /**
     * Obtenir toutes les écoles avec leurs informations complètes
     */
    public function getAllSchools()
    {
        try {
            $schools = Cache::remember('schools_complete_data', 300, function () {
                return School::with([
                    'exams' => function($query) {
                        $query->where('status', 'published')
                              ->where('registration_end_date', '>', now())
                              ->with(['filieres']);
                    }
                ])->get();
            });

            $schoolsData = $schools->map(function ($school) {
                return [
                    'id' => $school->id,
                    'name' => $school->name,
                    'sigle' => $school->sigle,
                    'city' => $school->city,
                    'region' => $school->region,
                    'description' => $this->getSchoolDescription($school->sigle),
                    'departments' => $this->getSchoolDepartments($school->sigle),
                    'exams_count' => $school->exams->count(),
                    'total_filieres' => $school->exams->sum(function($exam) {
                        return $exam->filieres->count();
                    }),
                    'exams' => $school->exams->map(function($exam) {
                        return [
                            'id' => $exam->id,
                            'title' => $exam->title,
                            'registration_start_date' => $exam->registration_start_date,
                            'registration_end_date' => $exam->registration_end_date,
                            'exam_date' => $exam->exam_date,
                            'registration_fee' => $exam->registration_fee,
                            'filieres_count' => $exam->filieres->count()
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $schoolsData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des écoles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les détails d'une école spécifique
     */
    public function getSchoolDetails($id)
    {
        try {
            $school = School::with([
                'exams' => function($query) {
                    $query->where('status', 'published')
                          ->with(['filieres']);
                }
            ])->findOrFail($id);

            $schoolData = [
                'id' => $school->id,
                'name' => $school->name,
                'sigle' => $school->sigle,
                'city' => $school->city,
                'region' => $school->region,
                'description' => $this->getSchoolDescription($school->sigle),
                'departments' => $this->getSchoolDepartments($school->sigle),
                'contact_info' => $this->getSchoolContactInfo($school->sigle),
                'exams' => $school->exams->map(function($exam) {
                    return [
                        'id' => $exam->id,
                        'title' => $exam->title,
                        'description' => $exam->description,
                        'registration_start_date' => $exam->registration_start_date,
                        'registration_end_date' => $exam->registration_end_date,
                        'exam_date' => $exam->exam_date,
                        'registration_fee' => $exam->registration_fee,
                        'conditions' => $this->getExamConditions($school->sigle),
                        'requirements' => $this->getExamRequirements(),
                        'filieres' => $exam->filieres->map(function($filiere) {
                            return [
                                'id' => $filiere->id,
                                'name' => $filiere->filiere_name,
                                'code' => $filiere->filiere_code,
                                'quota' => $filiere->quota,
                                'registered' => $filiere->registered,
                                'description' => $filiere->description
                            ];
                        })
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $schoolData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'École non trouvée: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Obtenir les statistiques globales
     */
    public function getGlobalStats()
    {
        try {
            $stats = Cache::remember('global_stats', 300, function () {
                return [
                    'total_schools' => School::count(),
                    'total_exams' => Exam::where('status', 'published')->count(),
                    'total_filieres' => ExamFiliere::count(),
                    'total_candidates' => \App\Models\User::whereHas('roles', function($query) {
                        $query->where('slug', 'candidat');
                    })->count(),
                    'total_registrations' => \App\Models\CandidateExam::count(),
                    'active_exams' => Exam::where('status', 'published')
                                         ->where('registration_end_date', '>', now())
                                         ->count(),
                    'total_quota' => ExamFiliere::sum('quota'),
                    'total_registered' => ExamFiliere::sum('registered')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les informations de contact
     */
    public function getContactInfo()
    {
        $contacts = [
            'support_technique' => [
                'title' => 'Support Technique',
                'description' => 'Problèmes de connexion et assistance technique',
                'phone' => '+237 677 123 456',
                'email' => 'support@sgee.cm',
                'hours' => 'Lun-Ven: 8h-17h',
                'icon' => 'headset'
            ],
            'orientation' => [
                'title' => 'Orientation Académique',
                'description' => 'Choix de filières et conseils d\'orientation',
                'phone' => '+237 677 234 567',
                'email' => 'orientation@sgee.cm',
                'hours' => 'Lun-Sam: 8h-18h',
                'icon' => 'graduation-cap'
            ],
            'urgence' => [
                'title' => 'Urgences',
                'description' => 'Problèmes urgents et assistance immédiate',
                'phone' => '+237 677 345 678',
                'email' => 'urgence@sgee.cm',
                'hours' => '24h/24 - 7j/7',
                'icon' => 'exclamation-triangle'
            ],
            'administration' => [
                'title' => 'Administration',
                'description' => 'Questions administratives et dossiers',
                'phone' => '+237 677 456 789',
                'email' => 'admin@sgee.cm',
                'hours' => 'Lun-Ven: 8h-16h',
                'icon' => 'building'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }

    /**
     * Descriptions des écoles
     */
    private function getSchoolDescription($sigle)
    {
        $descriptions = [
            'ENSP' => 'Formation d\'ingénieurs de haut niveau dans diverses spécialités techniques',
            'FMSB' => 'Formation médicale et biomédicale d\'excellence reconnue internationalement',
            'ESSEC' => 'Formation en sciences économiques et de gestion pour les leaders de demain',
            'IUT' => 'Formation technologique professionnalisante adaptée au marché du travail',
            'IUC' => 'Formation universitaire pluridisciplinaire de qualité supérieure'
        ];

        return $descriptions[$sigle] ?? 'École d\'excellence du système éducatif camerounais';
    }

    /**
     * Départements des écoles
     */
    private function getSchoolDepartments($sigle)
    {
        $departments = [
            'ENSP' => [
                [
                    'name' => 'Génie Civil',
                    'programs' => ['BTP', 'Hydraulique', 'Géotechnique', 'Structures']
                ],
                [
                    'name' => 'Génie Informatique',
                    'programs' => ['Logiciel', 'Réseaux', 'IA', 'Cybersécurité']
                ],
                [
                    'name' => 'Génie Électrique',
                    'programs' => ['Électronique', 'Télécommunications', 'Automatique', 'Énergie']
                ],
                [
                    'name' => 'Génie Mécanique',
                    'programs' => ['Mécanique Générale', 'Thermique', 'Matériaux']
                ]
            ],
            'FMSB' => [
                [
                    'name' => 'Médecine Générale',
                    'programs' => ['Médecine', 'Chirurgie', 'Pédiatrie', 'Gynécologie']
                ],
                [
                    'name' => 'Sciences Biomédicales',
                    'programs' => ['Biologie', 'Pharmacie', 'Biochimie', 'Microbiologie']
                ],
                [
                    'name' => 'Santé Publique',
                    'programs' => ['Épidémiologie', 'Nutrition', 'Hygiène']
                ]
            ],
            'ESSEC' => [
                [
                    'name' => 'Sciences Économiques',
                    'programs' => ['Économie', 'Finance', 'Économétrie', 'Développement']
                ],
                [
                    'name' => 'Gestion',
                    'programs' => ['Marketing', 'Comptabilité', 'GRH', 'Management']
                ],
                [
                    'name' => 'Commerce International',
                    'programs' => ['Import-Export', 'Logistique', 'Douanes']
                ]
            ],
            'IUT' => [
                [
                    'name' => 'Génie Industriel',
                    'programs' => ['Maintenance', 'Production', 'Qualité', 'Sécurité']
                ],
                [
                    'name' => 'Informatique',
                    'programs' => ['Développement', 'Systèmes', 'Réseaux', 'Multimédia']
                ],
                [
                    'name' => 'Électrotechnique',
                    'programs' => ['Électricité', 'Automatisme', 'Énergies Renouvelables']
                ]
            ],
            'IUC' => [
                [
                    'name' => 'Sciences et Technologies',
                    'programs' => ['Informatique', 'Mathématiques', 'Physique', 'Chimie']
                ],
                [
                    'name' => 'Sciences Humaines',
                    'programs' => ['Droit', 'Lettres', 'Sociologie', 'Psychologie']
                ],
                [
                    'name' => 'Sciences de Gestion',
                    'programs' => ['Management', 'Finance', 'Marketing', 'Entrepreneuriat']
                ]
            ]
        ];

        return $departments[$sigle] ?? [];
    }

    /**
     * Informations de contact des écoles
     */
    private function getSchoolContactInfo($sigle)
    {
        $contacts = [
            'ENSP' => [
                'address' => 'BP 8390, Yaoundé, Cameroun',
                'phone' => '+237 222 223 406',
                'email' => 'contact@ensp-uy1.cm',
                'website' => 'www.ensp.cm'
            ],
            'FMSB' => [
                'address' => 'BP 1364, Yaoundé, Cameroun',
                'phone' => '+237 222 231 427',
                'email' => 'contact@fmsb-uy1.cm',
                'website' => 'www.fmsb.cm'
            ],
            'ESSEC' => [
                'address' => 'BP 1931, Douala, Cameroun',
                'phone' => '+237 233 427 185',
                'email' => 'contact@essec.cm',
                'website' => 'www.essec.cm'
            ],
            'IUT' => [
                'address' => 'BP 8698, Douala, Cameroun',
                'phone' => '+237 233 401 234',
                'email' => 'contact@iut-douala.cm',
                'website' => 'www.iut-douala.cm'
            ],
            'IUC' => [
                'address' => 'BP 3132, Douala, Cameroun',
                'phone' => '+237 233 456 789',
                'email' => 'contact@iuc.cm',
                'website' => 'www.iuc.cm'
            ]
        ];

        return $contacts[$sigle] ?? [];
    }

    /**
     * Conditions d'accès par école
     */
    private function getExamConditions($sigle)
    {
        $commonConditions = [
            'Être titulaire du Baccalauréat ou équivalent',
            'Âge maximum : 23 ans au 31 décembre 2026',
            'Nationalité camerounaise ou titre de séjour valide'
        ];

        $specificConditions = [
            'ENSP' => [
                ...$commonConditions,
                'Baccalauréat série C, D, E, F ou équivalent',
                'Moyenne générale minimum : 12/20',
                'Bonne maîtrise des mathématiques et sciences'
            ],
            'FMSB' => [
                ...$commonConditions,
                'Baccalauréat série C ou D',
                'Moyenne générale minimum : 14/20 en sciences',
                'Excellente condition physique'
            ],
            'ESSEC' => [
                ...$commonConditions,
                'Baccalauréat toutes séries',
                'Bonne maîtrise du français et de l\'anglais',
                'Aptitude aux mathématiques'
            ],
            'IUT' => [
                ...$commonConditions,
                'Baccalauréat série C, D, E, F, G ou équivalent',
                'Aptitude aux études techniques',
                'Motivation pour les métiers techniques'
            ],
            'IUC' => [
                ...$commonConditions,
                'Baccalauréat toutes séries',
                'Motivation pour les études supérieures',
                'Capacité d\'adaptation'
            ]
        ];

        return $specificConditions[$sigle] ?? $commonConditions;
    }

    /**
     * Pièces requises
     */
    private function getExamRequirements()
    {
        return [
            'Acte de naissance (copie certifiée conforme)',
            'Diplôme du Baccalauréat (copie certifiée conforme)',
            'Relevé de notes du Baccalauréat (original)',
            'Certificat de nationalité ou titre de séjour valide',
            'Certificat médical de moins de 3 mois',
            '4 photos d\'identité récentes (4x4 cm)',
            'Reçu de paiement des frais d\'inscription',
            'Enveloppe timbrée à l\'adresse du candidat'
        ];
    }
}