<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CandidateProfileController extends Controller
{
    /**
     * Mettre à jour le profil complet du candidat
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth('sanctum')->user();
            
            $request->validate([
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'date_naissance' => 'required|date',
                'lieu_naissance' => 'required|string|max:255',
                'sexe' => 'required|in:M,F',
                'nationalite' => 'required|string|max:255',
                'region_origine' => 'required|string|max:255',
                'departement_origine' => 'required|string|max:255',
                'numero_identite' => 'required|string|max:255',
                'telephone' => 'required|string|max:20',
                'adresse' => 'required|string',
                'premiere_langue' => 'required|string|max:50',
                'diplome_admission' => 'required|string|max:255',
                'serie_diplome' => 'required|string|max:100',
                'mention' => 'required|string|max:100',
                'annee_diplome' => 'required|string|max:4',
                'centre_examen' => 'required|string|max:255',
                'centre_depot' => 'required|string|max:255',
                'nom_pere' => 'required|string|max:255',
                'telephone_pere' => 'nullable|string|max:20',
                'nom_mere' => 'required|string|max:255',
                'telephone_mere' => 'nullable|string|max:20'
            ]);

            // Récupérer ou créer le profil candidat
            $candidate = Candidate::where('user_id', $user->id)->first();
            
            if (!$candidate) {
                $candidate = new Candidate();
                $candidate->user_id = $user->id;
                $candidate->statut = 'valide';
            }

            // Mettre à jour les informations
            $candidate->fill([
                'prenom' => $request->prenom,
                'nom' => $request->nom,
                'telephone' => $request->telephone,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'sexe' => $request->sexe,
                'nationalite' => $request->nationalite,
                'numero_identite' => $request->numero_identite,
                'adresse' => $request->adresse
            ]);

            // Ajouter les nouvelles informations dans un champ JSON
            $candidate->additional_info = [
                'region_origine' => $request->region_origine,
                'departement_origine' => $request->departement_origine,
                'premiere_langue' => $request->premiere_langue,
                'diplome_admission' => $request->diplome_admission,
                'serie_diplome' => $request->serie_diplome,
                'mention' => $request->mention,
                'annee_diplome' => $request->annee_diplome,
                'centre_examen' => $request->centre_examen,
                'centre_depot' => $request->centre_depot,
                'nom_pere' => $request->nom_pere,
                'telephone_pere' => $request->telephone_pere,
                'nom_mere' => $request->nom_mere,
                'telephone_mere' => $request->telephone_mere
            ];

            $candidate->save();

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => $candidate
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir le profil complet du candidat
     */
    public function getProfile()
    {
        try {
            $user = auth('sanctum')->user();
            
            $candidate = Candidate::where('user_id', $user->id)->first();
            
            if (!$candidate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil candidat non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'data' => $candidate
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}