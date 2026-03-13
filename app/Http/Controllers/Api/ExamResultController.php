<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\CandidateExam;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExamResultController extends Controller
{
    /**
     * Entrer les résultats d'un candidat (correcteur/admin)
     */
    public function store(Request $request)
    {
        try {
            // Vérifier permission
            if (!auth('sanctum')->user()->hasPermission('enter_result')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'candidate_exam_id' => 'required|exists:candidate_exams,id',
                'note_obtenue' => 'required|numeric|min:0|max:20',
                'statut_resultat' => 'required|in:admis,rattrape,rejete',
                'remarques' => 'nullable|string'
            ]);

            $candidateExam = CandidateExam::findOrFail($request->candidate_exam_id);

            $result = ExamResult::updateOrCreate(
                ['candidate_exam_id' => $request->candidate_exam_id],
                [
                    'note_obtenue' => $request->note_obtenue,
                    'statut_resultat' => $request->statut_resultat,
                    'remarques' => $request->remarques,
                    'date_resultat' => now(),
                    'corrected_by' => auth('sanctum')->user()->id
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Résultat enregistré',
                'data' => $result
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Voir mon résultat
     */
    public function myResult(string $examId)
    {
        try {
            $user = auth('sanctum')->user();
            
            $candidateExam = CandidateExam::where('candidate_id', $user->candidate->id)
                ->where('exam_id', $examId)
                ->first();

            if (!$candidateExam) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas inscrit à cet examen'
                ], Response::HTTP_BAD_REQUEST);
            }

            $result = ExamResult::where('candidate_exam_id', $candidateExam->id)->first();

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les résultats ne sont pas encore disponibles'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lister tous les résultats d'un examen (admin)
     */
    public function index(Request $request)
    {
        try {
            if (!auth('sanctum')->user()->hasPermission('view_results')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $query = ExamResult::with('candidateExam.candidate.user', 'candidateExam.exam');

            // Filtrer par examen
            if ($request->exam_id) {
                $query->whereHas('candidateExam', function ($q) {
                    $q->where('exam_id', request('exam_id'));
                });
            }

            // Filtrer par statut
            if ($request->statut_resultat) {
                $query->where('statut_resultat', $request->statut_resultat);
            }

            $results = $query->latest()->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $results
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Voir un résultat spécifique
     */
    public function show(string $resultId)
    {
        try {
            $user = auth('sanctum')->user();
            $result = ExamResult::with('candidateExam.candidate.user', 'candidateExam.exam')
                ->findOrFail($resultId);

            // Vérifier permission
            if ($result->candidateExam->candidate->user_id !== $user->id && 
                !$user->hasPermission('view_results')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Publier les résultats d'un examen (admin)
     */
    public function publish(Request $request)
    {
        try {
            if (!auth('sanctum')->user()->hasPermission('publish_results')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'exam_id' => 'required|exists:exams,id'
            ]);

            // Publier tous les résultats de cet examen
            $exam = Exam::findOrFail($request->exam_id);
            
            ExamResult::whereHas('candidateExam', function ($q) use ($exam) {
                $q->where('exam_id', $exam->id);
            })->update(['publie' => true, 'date_publication' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Résultats publiés'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Marquer les admissions
     */
    public function markAdmission(Request $request, string $resultId)
    {
        try {
            if (!auth('sanctum')->user()->hasPermission('mark_admission')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'marque_admission' => 'required|boolean'
            ]);

            $result = ExamResult::findOrFail($resultId);
            $result->update(['marque_admission' => $request->marque_admission]);

            return response()->json([
                'success' => true,
                'message' => 'Admission marquée',
                'data' => $result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Importer les résultats par CSV (admin)
     */
    public function import(Request $request)
    {
        try {
            if (!auth('sanctum')->user()->hasPermission('import_results')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'file' => 'required|file|mimes:csv,txt',
                'exam_id' => 'required|exists:exams,id'
            ]);

            if (!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier manquant'
                ], Response::HTTP_BAD_REQUEST);
            }

            $file = $request->file('file');
            $handle = fopen($file, 'r');
            $count = 0;
            $errors = [];

            // Sauter l'en-tête
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    if (count($row) < 3) continue;

                    $matricule = $row[0];
                    $noteObtenue = (float)$row[1];
                    $statut = $row[2];

                    // Trouver le candidat
                    $candidateExam = CandidateExam::whereHas('candidate', function ($q) {
                        $q->where('matricule', request('matricule'));
                    })
                    ->where('exam_id', $request->exam_id)
                    ->first();

                    if ($candidateExam) {
                        ExamResult::updateOrCreate(
                            ['candidate_exam_id' => $candidateExam->id],
                            [
                                'note_obtenue' => $noteObtenue,
                                'statut_resultat' => $statut,
                                'date_resultat' => now(),
                                'corrected_by' => auth('sanctum')->user()->id
                            ]
                        );
                        $count++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Ligne " . ($count + 1) . ": " . $e->getMessage();
                }
            }

            fclose($handle);

            return response()->json([
                'success' => true,
                'message' => "$count résultats importés",
                'errors' => $errors
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}
