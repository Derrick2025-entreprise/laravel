<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamFiliere;
use App\Models\CompositionCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExamController extends Controller
{
    /**
     * Lister tous les concours (public - exams publiés)
     */
    public function index(Request $request)
    {
        try {
            $query = Exam::where('is_public', true)
                ->where('status', 'published')
                ->with('school', 'filieres');

            // Filtrer par école si fourni
            if ($request->school_id) {
                $query->where('school_id', $request->school_id);
            }

            $exams = $query->latest()->paginate(15);

            // Mapper les données pour correspondre au frontend
            $mappedExams = $exams->getCollection()->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'titre' => $exam->title,
                    'description' => $exam->description,
                    'date_debut' => $exam->registration_start_date->format('Y-m-d'),
                    'date_fin' => $exam->registration_end_date->format('Y-m-d'),
                    'montant_inscription' => $exam->registration_fee,
                    'frais_dossier' => $exam->registration_fee * 0.1, // 10% du montant d'inscription
                    'statut' => $exam->status,
                    'school_id' => $exam->school_id,
                    'school' => [
                        'id' => $exam->school->id,
                        'nom' => $exam->school->name,
                    ],
                    'filieres' => $exam->filieres->map(function ($filiere) {
                        return [
                            'id' => $filiere->id,
                            'libelle' => $filiere->nom_filiere,
                            'places_disponibles' => $filiere->places_disponibles,
                        ];
                    }),
                    'composition_centers' => $exam->compositionCenters->map(function ($center) {
                        return [
                            'id' => $center->id,
                            'nom' => $center->nom_centre,
                            'lieu' => $center->adresse_centre,
                            'capacite' => $center->capacite,
                        ];
                    }),
                ];
            });

            $exams->setCollection($mappedExams);

            return response()->json([
                'success' => true,
                'data' => $exams
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Créer un nouvel examen (admin)
     */
    public function store(Request $request)
    {
        try {
            // Vérifier permission
            if (!auth('sanctum')->user()->hasPermission('create_exam')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'school_id' => 'required|exists:schools,id',
                'nom_exam' => 'required|string|max:255',
                'annee_academique' => 'required|string',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after:date_debut',
                'date_limite_inscription' => 'required|date|before:date_debut',
                'montant_inscription' => 'required|numeric|min:0',
                'frais_dossier' => 'required|numeric|min:0',
                'nombre_places' => 'required|integer|min:1',
                'lieu_composition' => 'required|string',
                'description' => 'nullable|string',
                'publie' => 'boolean'
            ]);

            $exam = Exam::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Examen créé',
                'data' => $exam
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Voir les détails d'un examen
     */
    public function show(string $examId)
    {
        try {
            $exam = Exam::with('school', 'filieres', 'candidateExams', 'compositionCenters')
                ->findOrFail($examId);

            return response()->json([
                'success' => true,
                'data' => $exam
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mettre à jour un examen (admin)
     */
    public function update(Request $request, string $examId)
    {
        try {
            // Vérifier permission
            if (!auth('sanctum')->user()->hasPermission('edit_exam')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $exam = Exam::findOrFail($examId);

            $validated = $request->validate([
                'nom_exam' => 'string|max:255',
                'annee_academique' => 'string',
                'date_debut' => 'date',
                'date_fin' => 'date|after:date_debut',
                'date_limite_inscription' => 'date|before:date_debut',
                'montant_inscription' => 'numeric|min:0',
                'frais_dossier' => 'numeric|min:0',
                'nombre_places' => 'integer|min:1',
                'lieu_composition' => 'string',
                'description' => 'nullable|string',
                'statut' => 'in:en_preparation,en_cours,clos,annule',
                'publie' => 'boolean'
            ]);

            $exam->update(array_filter($validated));

            return response()->json([
                'success' => true,
                'message' => 'Examen mis à jour',
                'data' => $exam
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprimer un examen (admin)
     */
    public function destroy(string $examId)
    {
        try {
            // Vérifier permission
            if (!auth('sanctum')->user()->hasPermission('delete_exam')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $exam = Exam::findOrFail($examId);
            $exam->delete();

            return response()->json([
                'success' => true,
                'message' => 'Examen supprimé'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Ajouter une filière à un examen
     */
    public function addFiliere(Request $request, string $examId)
    {
        try {
            if (!auth('sanctum')->user()->hasPermission('edit_exam')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'nom_filiere' => 'required|string',
                'code_filiere' => 'required|string|unique:exam_filieres,code_filiere',
                'nombre_places' => 'required|integer|min:1'
            ]);

            $filiere = ExamFiliere::create([
                'exam_id' => $examId,
                'nom_filiere' => $request->nom_filiere,
                'code_filiere' => $request->code_filiere,
                'nombre_places' => $request->nombre_places,
                'places_disponibles' => $request->nombre_places
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Filière ajoutée',
                'data' => $filiere
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Ajouter un centre de composition
     */
    public function addCompositionCenter(Request $request, string $examId)
    {
        try {
            if (!auth('sanctum')->user()->hasPermission('edit_exam')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'nom_centre' => 'required|string',
                'adresse_centre' => 'required|string',
                'capacite' => 'required|integer|min:1',
                'contact_centre' => 'required|string'
            ]);

            $center = CompositionCenter::create([
                'exam_id' => $examId,
                'nom_centre' => $request->nom_centre,
                'adresse_centre' => $request->adresse_centre,
                'capacite' => $request->capacite,
                'contact_centre' => $request->contact_centre,
                'places_disponibles' => $request->capacite
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Centre ajouté',
                'data' => $center
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Publier un examen
     */
    public function publish(string $examId)
    {
        try {
            if (!auth('sanctum')->user()->hasPermission('edit_exam')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $exam = Exam::findOrFail($examId);
            $exam->update([
                'publie' => true,
                'statut' => 'en_cours'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Examen publié',
                'data' => $exam
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
