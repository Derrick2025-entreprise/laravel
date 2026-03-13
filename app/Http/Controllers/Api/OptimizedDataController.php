<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Exam;
use App\Models\ExamFiliere;
use App\Models\CandidateExam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OptimizedDataController extends Controller
{
    /**
     * Obtenir toutes les données optimisées pour le dashboard
     */
    public function getDashboardData()
    {
        try {
            $user = auth('sanctum')->user();
            
            // Cache pour 5 minutes
            $cacheKey = $user ? "dashboard_data_{$user->id}" : "public_dashboard_data";
            
            $data = Cache::remember($cacheKey, 300, function () use ($user) {
                return $this->buildDashboardData($user);
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'cached' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Construire les données du dashboard
     */
    private function buildDashboardData($user = null)
    {
        // Requêtes optimisées avec eager loading
        $exams = Exam::with(['school:id,name,sigle,city', 'filieres:id,exam_id,filiere_name,filiere_code,quota,registered'])
            ->where('status', 'published')
            ->where('registration_end_date', '>', now())
            ->select('id', 'school_id', 'title', 'description', 'registration_start_date', 'registration_end_date', 'exam_date', 'registration_fee', 'year')
            ->get();

        // Statistiques globales optimisées
        $stats = [
            'total_schools' => School::count(),
            'total_exams' => Exam::where('status', 'published')->count(),
            'total_filieres' => ExamFiliere::count(),
            'total_candidates' => User::whereHas('roles', function($query) {
                $query->where('slug', 'candidat');
            })->count(),
            'total_registrations' => CandidateExam::count()
        ];

        $result = [
            'exams' => $this->formatExamsData($exams),
            'stats' => $stats,
            'schools' => $this->getSchoolsData(),
            'performance' => [
                'query_time' => microtime(true),
                'cached' => true
            ]
        ];

        // Données spécifiques à l'utilisateur connecté
        if ($user) {
            $result['user_data'] = $this->getUserSpecificData($user);
        }

        return $result;
    }

    /**
     * Formater les données des examens
     */
    private function formatExamsData($exams)
    {
        return $exams->map(function ($exam) {
            return [
                'id' => $exam->id,
                'titre' => $exam->title,
                'description' => $exam->description,
                'date_debut' => $exam->registration_start_date->format('Y-m-d'),
                'date_fin' => $exam->registration_end_date->format('Y-m-d'),
                'date_examen' => $exam->exam_date ? $exam->exam_date->format('Y-m-d') : null,
                'montant_inscription' => $exam->registration_fee,
                'frais_dossier' => round($exam->registration_fee * 0.1),
                'status' => 'published',
                'school' => [
                    'id' => $exam->school->id,
                    'nom' => $exam->school->name,
                    'sigle' => $exam->school->sigle,
                    'ville' => $exam->school->city
                ],
                'filieres' => $exam->filieres->map(function ($filiere) {
                    return [
                        'id' => $filiere->id,
                        'libelle' => $filiere->filiere_name,
                        'code' => $filiere->filiere_code,
                        'quota' => $filiere->quota,
                        'inscrits' => $filiere->registered
                    ];
                })
            ];
        });
    }

    /**
     * Obtenir les données des écoles
     */
    private function getSchoolsData()
    {
        return Cache::remember('schools_data', 600, function () {
            return School::select('id', 'name', 'sigle', 'city', 'region')
                ->withCount(['exams' => function($query) {
                    $query->where('status', 'published');
                }])
                ->get()
                ->map(function ($school) {
                    return [
                        'id' => $school->id,
                        'nom' => $school->name,
                        'sigle' => $school->sigle,
                        'ville' => $school->city,
                        'region' => $school->region,
                        'nombre_examens' => $school->exams_count
                    ];
                });
        });
    }

    /**
     * Données spécifiques à l'utilisateur
     */
    private function getUserSpecificData($user)
    {
        $registrations = CandidateExam::where('user_id', $user->id)
            ->with(['exam:id,title,exam_date', 'exam.school:id,name,sigle', 'filiere:id,filiere_name'])
            ->select('id', 'exam_id', 'exam_filiere_id', 'statut', 'registered_at', 'confirmed_at')
            ->get();

        return [
            'registrations' => $registrations->map(function ($registration) {
                return [
                    'id' => $registration->id,
                    'statut' => $registration->statut,
                    'date_inscription' => $registration->registered_at->format('Y-m-d H:i'),
                    'date_confirmation' => $registration->confirmed_at ? $registration->confirmed_at->format('Y-m-d H:i') : null,
                    'exam' => [
                        'id' => $registration->exam->id,
                        'titre' => $registration->exam->title,
                        'date_examen' => $registration->exam->exam_date ? $registration->exam->exam_date->format('Y-m-d') : null,
                        'school' => [
                            'nom' => $registration->exam->school->name,
                            'sigle' => $registration->exam->school->sigle
                        ]
                    ],
                    'filiere' => [
                        'id' => $registration->filiere->id,
                        'nom' => $registration->filiere->filiere_name
                    ]
                ];
            }),
            'stats' => [
                'total_inscriptions' => $registrations->count(),
                'inscriptions_confirmees' => $registrations->where('statut', 'confirme')->count(),
                'inscriptions_en_attente' => $registrations->where('statut', 'inscrit')->count(),
                'examens_passes' => $registrations->where('statut', 'present')->count()
            ]
        ];
    }

    /**
     * Vider le cache pour forcer le rechargement
     */
    public function clearCache()
    {
        try {
            $user = auth('sanctum')->user();
            
            // Vider les caches spécifiques
            $cacheKeys = [
                'schools_data',
                'public_dashboard_data'
            ];
            
            if ($user) {
                $cacheKeys[] = "dashboard_data_{$user->id}";
            }
            
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cache vidé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de performance
     */
    public function getPerformanceStats()
    {
        try {
            $startTime = microtime(true);
            
            // Test de requêtes
            $queryStats = [
                'schools_count' => School::count(),
                'exams_count' => Exam::count(),
                'registrations_count' => CandidateExam::count(),
                'cache_hits' => Cache::get('cache_hits', 0),
                'database_queries' => DB::getQueryLog()
            ];
            
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // en millisecondes

            return response()->json([
                'success' => true,
                'data' => [
                    'execution_time_ms' => round($executionTime, 2),
                    'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'query_stats' => $queryStats,
                    'cache_status' => [
                        'enabled' => config('cache.default') !== 'array',
                        'driver' => config('cache.default')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}