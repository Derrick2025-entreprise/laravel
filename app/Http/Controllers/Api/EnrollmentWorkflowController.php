<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateExam;
use App\Models\Candidate;
use App\Models\Exam;
use App\Models\ExamFiliere;
use App\Models\ExamCenter;
use App\Services\EnrollmentValidationService;
use App\Services\PdfEmailService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class EnrollmentWorkflowController extends Controller
{
    protected $validationService;
    protected $pdfEmailService;

    public function __construct(
        EnrollmentValidationService $validationService,
        PdfEmailService $pdfEmailService
    ) {
        $this->validationService = $validationService;
        $this->pdfEmailService = $pdfEmailService;
    }

    /**
     * Inscription à un examen avec validation automatique
     */
    public function enroll(Request $request)
    {
        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'filiere_id' => 'required|exists:exam_filieres,id',
                'prenom' => 'required|string|max:255',
                'nom' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'telephone' => 'required|string|max:20',
                'date_naissance' => 'required|date|before:today',
                'lieu_naissance' => 'required|string|max:255',
                'sexe' => 'required|in:M,F',
                'nationalite' => 'required|string|max:100',
                'adresse' => 'required|string',
                'niveau_etude' => 'required|string|max:100',
                'etablissement_origine' => 'required|string|max:255',
                'annee_obtention' => 'required|integer|min:2000|max:' . date('Y'),
                'moyenne_generale' => 'required|numeric|min:0|max:20',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'pieces_jointes' => 'nullable|array',
                'pieces_jointes.*' => 'file|mimes:pdf,jpeg,png,jpg|max:5120'
            ]);

            $user = Auth::guard('sanctum')->user();

            DB::beginTransaction();

            // Créer ou mettre à jour le candidat
            $candidate = Candidate::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'prenom' => $request->prenom,
                    'nom' => $request->nom,
                    'email' => $request->email,
                    'telephone' => $request->telephone,
                    'date_naissance' => $request->date_naissance,
                    'lieu_naissance' => $request->lieu_naissance,
                    'sexe' => $request->sexe,
                    'nationalite' => $request->nationalite,
                    'adresse' => $request->adresse,
                    'niveau_etude' => $request->niveau_etude,
                    'etablissement_origine' => $request->etablissement_origine,
                    'annee_obtention' => $request->annee_obtention,
                    'moyenne_generale' => $request->moyenne_generale,
                    'statut' => 'en_attente'
                ]
            );

            // Gérer l'upload de la photo
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('candidates/photos', 'public');
                $candidate->update(['photo_path' => $photoPath]);
            }

            // Créer l'inscription à l'examen
            $candidateExam = CandidateExam::create([
                'candidate_id' => $candidate->id,
                'exam_id' => $request->exam_id,
                'filiere_id' => $request->filiere_id,
                'user_id' => $user->id,
                'registered_at' => now(),
                'statut' => 'en_attente'
            ]);

            // Validation automatique
            $validationResult = $this->validationService->validateEnrollment($candidateExam);

            if ($validationResult['valid']) {
                $candidateExam->update(['statut' => 'confirme']);
                $candidate->update(['statut' => 'confirme']);

                // Générer et envoyer le PDF par email
                $pdfPath = $this->pdfEmailService->generateAndSendRegistrationCard($candidateExam);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Inscription validée automatiquement et confirmée par email',
                    'data' => [
                        'candidate_exam_id' => $candidateExam->id,
                        'status' => 'confirme',
                        'pdf_generated' => !is_null($pdfPath)
                    ]
                ], Response::HTTP_CREATED);
            } else {
                $candidateExam->update([
                    'statut' => 'rejete',
                    'motif_rejet' => $validationResult['reason']
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Inscription rejetée automatiquement',
                    'reason' => $validationResult['reason'],
                    'data' => [
                        'candidate_exam_id' => $candidateExam->id,
                        'status' => 'rejete'
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur inscription workflow', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir la liste des inscriptions de l'utilisateur
     */
    public function getMyRegistrations()
    {
        try {
            $user = Auth::guard('sanctum')->user();
            
            $registrations = CandidateExam::where('user_id', $user->id)
                ->with(['exam.school', 'filiere', 'candidate'])
                ->orderBy('registered_at', 'desc')
                ->get();

            $data = $registrations->map(function($registration) {
                return [
                    'id' => $registration->id,
                    'exam' => [
                        'id' => $registration->exam->id,
                        'title' => $registration->exam->title,
                        'school' => $registration->exam->school->name,
                        'date_debut' => $registration->exam->date_debut,
                        'date_fin' => $registration->exam->date_fin
                    ],
                    'filiere' => [
                        'id' => $registration->filiere->id,
                        'name' => $registration->filiere->filiere_name,
                        'code' => $registration->filiere->filiere_code
                    ],
                    'status' => $registration->statut,
                    'registered_at' => $registration->registered_at,
                    'motif_rejet' => $registration->motif_rejet
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération inscriptions', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des inscriptions'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir les détails d'une inscription
     */
    public function getRegistrationDetails($registrationId)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            
            $registration = CandidateExam::where('id', $registrationId)
                ->where('user_id', $user->id)
                ->with(['exam.school', 'filiere', 'candidate'])
                ->firstOrFail();

            $data = [
                'id' => $registration->id,
                'exam' => [
                    'id' => $registration->exam->id,
                    'title' => $registration->exam->title,
                    'description' => $registration->exam->description,
                    'school' => [
                        'id' => $registration->exam->school->id,
                        'name' => $registration->exam->school->name,
                        'sigle' => $registration->exam->school->sigle
                    ],
                    'date_debut' => $registration->exam->date_debut,
                    'date_fin' => $registration->exam->date_fin,
                    'date_limite_inscription' => $registration->exam->date_limite_inscription
                ],
                'filiere' => [
                    'id' => $registration->filiere->id,
                    'name' => $registration->filiere->filiere_name,
                    'code' => $registration->filiere->filiere_code,
                    'description' => $registration->filiere->description,
                    'quota' => $registration->filiere->quota
                ],
                'candidate' => [
                    'prenom' => $registration->candidate->prenom,
                    'nom' => $registration->candidate->nom,
                    'email' => $registration->candidate->email,
                    'telephone' => $registration->candidate->telephone,
                    'date_naissance' => $registration->candidate->date_naissance,
                    'lieu_naissance' => $registration->candidate->lieu_naissance,
                    'sexe' => $registration->candidate->sexe,
                    'nationalite' => $registration->candidate->nationalite,
                    'adresse' => $registration->candidate->adresse,
                    'niveau_etude' => $registration->candidate->niveau_etude,
                    'etablissement_origine' => $registration->candidate->etablissement_origine,
                    'annee_obtention' => $registration->candidate->annee_obtention,
                    'moyenne_generale' => $registration->candidate->moyenne_generale,
                    'photo_url' => $registration->candidate->photo_path ? 
                        asset('storage/' . $registration->candidate->photo_path) : null
                ],
                'status' => $registration->statut,
                'registered_at' => $registration->registered_at,
                'motif_rejet' => $registration->motif_rejet,
                'can_download_card' => $registration->statut === 'confirme'
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur détails inscription', [
                'registration_id' => $registrationId,
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Inscription non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}