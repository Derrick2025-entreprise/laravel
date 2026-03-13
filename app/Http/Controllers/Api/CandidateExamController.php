<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamFiliere;
use App\Models\CandidateExam;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CandidateExamController extends Controller
{
    /**
     * Lister les concours publiés
     */
    public function index()
    {
        try {
            $exams = Exam::where('status', 'published')
                ->where('is_public', true)
                ->with(['school:id,name', 'filieres:id,exam_id,filiere_name,quota,registered'])
                ->select('id', 'title', 'description', 'school_id', 'registration_start_date', 'registration_end_date', 'registration_fee')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Liste des concours disponibles',
                'data' => $exams
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des concours: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * S'inscrire à un concours
     */
    public function register(Request $request, string $examId)
    {
        try {
            $user = auth('sanctum')->user();

            // Valider les données
            $request->validate([
                'exam_filiere_id' => 'sometimes|exists:exam_filieres,id',
            ]);

            // Vérifier que l'examen existe et est ouvert
            $exam = Exam::where('id', $examId)
                ->where('status', 'published')
                ->where('registration_end_date', '>', now())
                ->firstOrFail();

            // Vérifier que l'inscription n'existe pas déjà
            $existingRegistration = CandidateExam::where('user_id', $user->id)
                ->where('exam_id', $examId)
                ->first();

            if ($existingRegistration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous êtes déjà inscrit à ce concours'
                ], Response::HTTP_CONFLICT);
            }

            // Utiliser la première filière disponible si non spécifiée
            $examFiliereId = $request->exam_filiere_id ?? $exam->filieres->first()?->id;

            if (!$examFiliereId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune filière disponible pour ce concours'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Créer l'inscription
            $registration = CandidateExam::create([
                'user_id' => $user->id,
                'exam_id' => $examId,
                'exam_filiere_id' => $examFiliereId,
                'statut' => 'inscrit',
                'registered_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription au concours réussie',
                'data' => [
                    'registration_id' => $registration->id,
                    'status' => 'inscrit',
                    'registered_at' => $registration->registered_at,
                    'exam_title' => $exam->title,
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
     * Voir mes inscriptions
     */
    public function myRegistrations()
    {
        try {
            $user = auth('sanctum')->user();

            $registrations = CandidateExam::where('user_id', $user->id)
                ->with(['exam:id,title,school_id,registration_fee', 'exam.school:id,name', 'filiere:id,filiere_name'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Vos inscriptions',
                'data' => $registrations
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des inscriptions: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Détails d'une inscription
     */
    public function show(string $id)
    {
        try {
            $user = auth('sanctum')->user();
            $registration = CandidateExam::where('id', $id)
                ->where('user_id', $user->id)
                ->with(['exam:id,title,description,school_id,registration_fee', 'exam.school:id,name', 'filiere:id,filiere_name'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $registration
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription non trouvée: ' . $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Confirmer l'inscription
     */
    public function confirm(string $id)
    {
        try {
            $user = auth('sanctum')->user();
            $registration = CandidateExam::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $registration->update([
                'statut' => 'confirme',
                'confirmed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription confirmée avec succès',
                'data' => $registration
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}