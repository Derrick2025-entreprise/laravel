<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamFiliere;
use App\Models\CandidateExam;
use App\Models\Candidate;
use App\Models\ExamCenter;
use App\Models\DocumentSubmissionCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SimpleExamController extends Controller
{
    /**
     * Lister tous les examens disponibles (public)
     */
    public function index()
    {
        try {
            $exams = Exam::with(['school', 'filieres'])
                ->where('status', 'published')
                ->where('registration_end_date', '>', now())
                ->get();

            $formattedExams = $exams->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'titre' => $exam->title,
                    'description' => $exam->description,
                    'date_debut' => $exam->registration_start_date->format('Y-m-d'),
                    'date_fin' => $exam->registration_end_date->format('Y-m-d'),
                    'date_examen' => $exam->exam_date ? $exam->exam_date->format('Y-m-d') : null,
                    'montant_inscription' => $exam->registration_fee,
                    'frais_dossier' => round($exam->registration_fee * 0.1), // 10% du montant
                    'status' => $exam->status,
                    'school' => [
                        'id' => $exam->school->id,
                        'nom' => $exam->school->name,
                        'sigle' => $exam->school->sigle
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

            return response()->json([
                'success' => true,
                'data' => $formattedExams
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir les détails d'un examen spécifique
     */
    public function show($examId)
    {
        try {
            $exam = Exam::with(['school', 'filieres'])
                ->where('id', $examId)
                ->where('status', 'published')
                ->firstOrFail();

            $formattedExam = [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'registration_start_date' => $exam->registration_start_date->format('Y-m-d'),
                'registration_end_date' => $exam->registration_end_date->format('Y-m-d'),
                'exam_date' => $exam->exam_date ? $exam->exam_date->format('Y-m-d') : null,
                'registration_fee' => $exam->registration_fee,
                'status' => $exam->status,
                'school' => [
                    'id' => $exam->school->id,
                    'name' => $exam->school->name,
                    'sigle' => $exam->school->sigle
                ],
                'filieres' => $exam->filieres->map(function ($filiere) {
                    return [
                        'id' => $filiere->id,
                        'filiere_name' => $filiere->filiere_name,
                        'filiere_code' => $filiere->filiere_code,
                        'quota' => $filiere->quota,
                        'registered' => $filiere->registered,
                        'description' => $filiere->description
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedExam
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Examen non trouvé: ' . $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Obtenir les statistiques des examens pour un candidat
     */
    public function candidateStats()
    {
        try {
            $user = auth('sanctum')->user();
            
            $registrations = CandidateExam::where('user_id', $user->id)
                ->with(['exam', 'filiere'])
                ->get();

            $stats = [
                'total_inscriptions' => $registrations->count(),
                'inscriptions_confirmees' => $registrations->where('statut', 'confirme')->count(),
                'inscriptions_en_attente' => $registrations->where('statut', 'inscrit')->count(),
                'examens_passes' => $registrations->where('statut', 'present')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir les inscriptions d'un candidat
     */
    public function myRegistrations()
    {
        try {
            $user = auth('sanctum')->user();
            
            $registrations = CandidateExam::where('user_id', $user->id)
                ->with(['exam.school', 'filiere'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedRegistrations = $registrations->map(function ($registration) {
                return [
                    'id' => $registration->id,
                    'statut' => $registration->statut,
                    'registered_at' => $registration->registered_at->format('Y-m-d H:i'),
                    'confirmed_at' => $registration->confirmed_at ? $registration->confirmed_at->format('Y-m-d H:i') : null,
                    'exam' => [
                        'id' => $registration->exam->id,
                        'title' => $registration->exam->title,
                        'exam_date' => $registration->exam->exam_date ? $registration->exam->exam_date->format('Y-m-d') : null,
                        'school' => [
                            'name' => $registration->exam->school->name,
                            'sigle' => $registration->exam->school->sigle
                        ]
                    ],
                    'filiere' => [
                        'id' => $registration->filiere->id,
                        'filiere_name' => $registration->filiere->filiere_name,
                        'filiere_code' => $registration->filiere->filiere_code
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedRegistrations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * S'inscrire à un examen avec validation automatique complète
     */
    public function register(Request $request, $examId)
    {
        try {
            $user = auth('sanctum')->user();

            $request->validate([
                'filiere_id' => 'required|exists:exam_filieres,id'
            ]);

            // Vérifier si l'examen existe et est ouvert
            $exam = Exam::findOrFail($examId);
            
            if ($exam->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet examen n\'est pas ouvert aux inscriptions'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($exam->registration_end_date < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La période d\'inscription est terminée'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si déjà inscrit
            $existingRegistration = CandidateExam::where('user_id', $user->id)
                ->where('exam_id', $examId)
                ->first();

            if ($existingRegistration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous êtes déjà inscrit à cet examen',
                    'redirect_url' => '/candidate/registrations/' . $existingRegistration->id
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier la disponibilité de places dans la filière
            $filiere = ExamFiliere::findOrFail($request->filiere_id);
            if ($filiere->registered >= $filiere->quota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette filière a atteint son quota maximum d\'inscriptions'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Créer ou récupérer le profil candidat
            $candidate = $user->candidate;
            if (!$candidate) {
                // Créer automatiquement le profil candidat
                $nameParts = explode(' ', $user->name);
                $candidate = Candidate::create([
                    'user_id' => $user->id,
                    'prenom' => $nameParts[0] ?? '',
                    'nom' => $nameParts[1] ?? '',
                    'telephone' => $user->telephone,
                    'statut' => 'valide'
                ]);
            }

            // Créer l'inscription avec validation automatique
            $registration = CandidateExam::create([
                'candidate_id' => $candidate->id,
                'user_id' => $user->id,
                'exam_id' => $examId,
                'exam_filiere_id' => $request->filiere_id,
                'statut' => 'confirme', // Validation automatique
                'registered_at' => now(),
                'confirmed_at' => now(), // Confirmation automatique
                'payment_status' => 'validated', // Paiement validé automatiquement pour les tests
                'notes' => 'Inscription validée automatiquement'
            ]);

            // Mettre à jour le compteur d'inscrits
            $filiere->increment('registered');

            // Générer automatiquement la fiche d'inscription
            $cardUrl = route('candidate.registrations.card', $registration->id);

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie et validée automatiquement !',
                'data' => [
                    'registration_id' => $registration->id,
                    'status' => 'confirme',
                    'card_url' => $cardUrl,
                    'exam_title' => $exam->title,
                    'school_name' => $exam->school->name,
                    'filiere_name' => $filiere->filiere_name,
                    'registration_date' => $registration->registered_at->format('d/m/Y H:i'),
                    'confirmation_date' => $registration->confirmed_at->format('d/m/Y H:i')
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Vérifier si un candidat est déjà inscrit à un examen
     */
    public function checkRegistration($examId)
    {
        try {
            $user = auth('sanctum')->user();
            
            $existingRegistration = CandidateExam::where('user_id', $user->id)
                ->where('exam_id', $examId)
                ->with(['exam.school', 'filiere'])
                ->first();

            if ($existingRegistration) {
                return response()->json([
                    'success' => true,
                    'already_registered' => true,
                    'registration' => [
                        'id' => $existingRegistration->id,
                        'statut' => $existingRegistration->statut,
                        'date_inscription' => $existingRegistration->registered_at->format('Y-m-d H:i'),
                        'exam' => [
                            'titre' => $existingRegistration->exam->title,
                            'school' => $existingRegistration->exam->school->name
                        ],
                        'filiere' => [
                            'nom' => $existingRegistration->filiere->filiere_name
                        ]
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'already_registered' => false
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir les centres d'examens
     */
    public function getExamCenters()
    {
        try {
            $centers = ExamCenter::orderBy('region')
                ->orderBy('city')
                ->get()
                ->map(function($center) {
                    return [
                        'id' => $center->id,
                        'name' => $center->name,
                        'sigle' => $center->sigle,
                        'address' => $center->address,
                        'city' => $center->city,
                        'region' => $center->region,
                        'capacity' => $center->capacity,
                        'facilities' => $center->facilities ?? []
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $centers
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération centres examens', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des centres d\'examens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les centres de dépôt de dossier
     */
    public function getSubmissionCenters()
    {
        try {
            $centers = DocumentSubmissionCenter::where('is_active', true)
                ->orderBy('region')
                ->orderBy('city')
                ->get()
                ->map(function($center) {
                    return [
                        'id' => $center->id,
                        'name' => $center->name,
                        'sigle' => $center->sigle,
                        'address' => $center->address,
                        'city' => $center->city,
                        'region' => $center->region,
                        'phone' => $center->phone,
                        'email' => $center->email,
                        'opening_hours' => $center->opening_hours,
                        'formatted_hours' => $center->formatted_opening_hours,
                        'accepted_documents' => $center->accepted_documents ?? [],
                        'directions' => $center->directions,
                        'is_open_now' => $center->isOpenNow(),
                        'coordinates' => [
                            'latitude' => $center->latitude,
                            'longitude' => $center->longitude
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $centers
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération centres dépôt', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des centres de dépôt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les centres par région
     */
    public function getCentersByRegion($region)
    {
        try {
            $examCenters = ExamCenter::where('region', $region)
                ->get()
                ->map(function($center) {
                    return [
                        'id' => $center->id,
                        'name' => $center->name,
                        'sigle' => $center->sigle,
                        'address' => $center->address,
                        'city' => $center->city,
                        'capacity' => $center->capacity,
                        'facilities' => $center->facilities ?? [],
                        'type' => 'exam_center'
                    ];
                });

            $submissionCenters = DocumentSubmissionCenter::where('region', $region)
                ->where('is_active', true)
                ->get()
                ->map(function($center) {
                    return [
                        'id' => $center->id,
                        'name' => $center->name,
                        'sigle' => $center->sigle,
                        'address' => $center->address,
                        'city' => $center->city,
                        'phone' => $center->phone,
                        'email' => $center->email,
                        'opening_hours' => $center->opening_hours,
                        'formatted_hours' => $center->formatted_opening_hours,
                        'accepted_documents' => $center->accepted_documents ?? [],
                        'directions' => $center->directions,
                        'is_open_now' => $center->isOpenNow(),
                        'type' => 'submission_center'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'region' => $region,
                    'exam_centers' => $examCenters,
                    'submission_centers' => $submissionCenters
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération centres par région', [
                'region' => $region,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des centres pour cette région: ' . $e->getMessage()
            ], 500);
        }
    }
}