<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Models\Document;
use App\Models\CandidateExam;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DossierController extends Controller
{
    /**
     * Voir mon dossier
     */
    public function show(string $dossierId)
    {
        try {
            $user = auth('sanctum')->user();
            $dossier = Dossier::findOrFail($dossierId);

            if ($dossier->candidate->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            return response()->json([
                'success' => true,
                'data' => $dossier->load('documents', 'candidateExam.exam')
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload un document au dossier
     */
    public function uploadDocument(Request $request, string $dossierId)
    {
        try {
            $user = auth('sanctum')->user();
            $dossier = Dossier::findOrFail($dossierId);

            if ($dossier->candidate->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'type' => 'required|string',
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
            ]);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('dossiers/' . $dossierId, 'public');

                $document = Document::create([
                    'dossier_id' => $dossier->id,
                    'type' => $request->type,
                    'nom_fichier' => $file->getClientOriginalName(),
                    'chemin_fichier' => $path,
                    'mime_type' => $file->getMimeType(),
                    'taille_fichier' => $file->getSize(),
                    'statut' => 'en_attente',
                ]);

                // Calculer le pourcentage de complétude
                $this->updateDossierProgress($dossier);

                return response()->json([
                    'success' => true,
                    'message' => 'Document uploadé avec succès',
                    'data' => $document
                ], Response::HTTP_CREATED);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload'
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lister les documents d'un dossier
     */
    public function documents(string $dossierId)
    {
        try {
            $user = auth('sanctum')->user();
            $dossier = Dossier::findOrFail($dossierId);

            if ($dossier->candidate->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $documents = Document::where('dossier_id', $dossierId)->get();

            return response()->json([
                'success' => true,
                'data' => $documents
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprimer un document
     */
    public function deleteDocument(string $documentId)
    {
        try {
            $user = auth('sanctum')->user();
            $document = Document::findOrFail($documentId);
            $dossier = $document->dossier;

            if ($dossier->candidate->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            $document->delete();

            // Recalculer le pourcentage
            $this->updateDossierProgress($dossier);

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mettre à jour le pourcentage de complétude du dossier
     */
    private function updateDossierProgress(Dossier $dossier)
    {
        $totalDocs = Document::where('dossier_id', $dossier->id)->count();
        $validDocs = Document::where('dossier_id', $dossier->id)
            ->where('statut', 'valide')->count();

        $progress = $totalDocs > 0 ? round(($validDocs / $totalDocs) * 100) : 0;

        $dossier->update(['progress_percentage' => $progress]);

        // Marquer comme complet si tous les docs sont uploadés
        if ($totalDocs > 0 && $progress > 80) {
            $dossier->update(['etat_dossier' => 'complet']);
        }
    }

    public function store(Request $request) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}
