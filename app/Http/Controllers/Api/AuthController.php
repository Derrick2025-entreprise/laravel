<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    /**
     * Inscription d'un candidat
     */
    public function register(RegisterRequest $request)
    {
        try {
            // Créer l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'password' => Hash::make($request->password),
                'status' => 'actif',
            ]);

            // Attribuer le rôle candidat
            $candidateRole = Role::where('slug', 'candidat')->first();
            if ($candidateRole) {
                $user->roles()->attach($candidateRole->id);
            }

            // Créer le profil candidat
            Candidate::create([
                'user_id' => $user->id,
                'prenom' => $request->prenom,
                'nom' => $request->nom,
                'telephone' => $request->telephone,
                'statut' => 'en_attente',
            ]);

            // Générer le token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'telephone' => $user->telephone,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
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
     * Connexion utilisateur
     */
    public function login(LoginRequest $request)
    {
        try {
            // Chercher l'utilisateur par email
            $user = User::where('email', $request->email)->first();

            // Vérifier le mot de passe
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Vérifier le statut
            if ($user->status !== 'actif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte désactivé ou suspendu'
                ], Response::HTTP_FORBIDDEN);
            }

            // Générer le token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Récupérer les rôles
            $roles = $user->roles()->pluck('slug')->toArray();

            // Envoyer notification de connexion
            $loginNotificationService = new \App\Services\LoginNotificationService();
            $loginNotificationService->sendLoginNotification($user);
            $loginNotificationService->logLoginDetails($user);

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'telephone' => $user->telephone,
                        'status' => $user->status,
                    ],
                    'roles' => $roles,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Demander une réinitialisation de mot de passe
     */
    public function requestPasswordReset(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $passwordResetService = new \App\Services\PasswordResetService();
        $result = $passwordResetService->sendResetEmail($request->email);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed'
        ]);

        $passwordResetService = new \App\Services\PasswordResetService();
        $result = $passwordResetService->resetPassword(
            $request->token,
            $request->email,
            $request->password
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Vérifier la validité d'un token de réinitialisation
     */
    public function verifyResetToken(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email'
        ]);

        $passwordResetService = new \App\Services\PasswordResetService();
        $isValid = $passwordResetService->verifyResetToken($request->token, $request->email);

        return response()->json([
            'success' => true,
            'valid' => $isValid,
            'message' => $isValid ? 'Token valide' : 'Token invalide ou expiré'
        ]);
    }

    /**
     * Récupérer les infos de l'utilisateur connecté
     */
    public function me()
    {
        try {
            $user = auth('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Récupérer les rôles et permissions
            $roles = $user->roles()->with('permissions')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'telephone' => $user->telephone,
                        'status' => $user->status,
                    ],
                    'roles' => $roles->map(fn($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                        'permissions' => $role->permissions->map(fn($perm) => $perm->slug)->toArray()
                    ])->toArray(),
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
     * Déconnexion
     */
    public function logout()
    {
        try {
            auth('sanctum')->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
