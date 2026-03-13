<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TestController extends Controller
{
    /**
     * Obtenir un token admin pour les tests
     */
    public function getAdminToken()
    {
        try {
            $admin = User::where('email', 'admin@sgee.cm')->first();
            
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $token = $admin->createToken('admin-test-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => $admin,
                    'roles' => $admin->roles->pluck('name')
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir un token candidat pour les tests
     */
    public function getCandidateToken()
    {
        try {
            $candidat = User::where('email', 'candidat@test.cm')->first();
            
            if (!$candidat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Candidat non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $token = $candidat->createToken('candidat-test-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => $candidat,
                    'roles' => $candidat->roles->pluck('name')
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}