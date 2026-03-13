<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PasswordResetService
{
    /**
     * Envoyer un email de réinitialisation de mot de passe
     */
    public function sendResetEmail(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Aucun compte trouvé avec cette adresse email'
                ];
            }

            // Générer un token de réinitialisation
            $token = Str::random(64);
            
            // Supprimer les anciens tokens pour cet utilisateur
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            
            // Créer un nouveau token
            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]);

            // Données pour l'email
            $emailData = [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'reset_token' => $token,
                'reset_url' => url('/reset-password?token=' . $token . '&email=' . urlencode($email)),
                'expires_at' => now()->addHours(1)->format('d/m/Y à H:i'),
                'request_ip' => request()->ip(),
                'request_time' => now()->format('d/m/Y à H:i:s')
            ];

            // Envoyer l'email
            Mail::send('emails.password-reset', $emailData, function ($message) use ($user) {
                $message->to($user->email, $user->name)
                       ->subject('🔑 Réinitialisation de votre mot de passe SGEE');
            });

            Log::info('Email de réinitialisation envoyé', [
                'user_id' => $user->id,
                'email' => $email,
                'ip' => request()->ip()
            ]);

            return [
                'success' => true,
                'message' => 'Un email de réinitialisation a été envoyé à votre adresse'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur envoi email réinitialisation', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email de réinitialisation'
            ];
        }
    }

    /**
     * Réinitialiser le mot de passe avec le token
     */
    public function resetPassword(string $token, string $email, string $newPassword): array
    {
        try {
            // Vérifier si le token existe et est valide
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (!$resetRecord) {
                return [
                    'success' => false,
                    'message' => 'Token de réinitialisation invalide ou expiré'
                ];
            }

            // Vérifier si le token correspond
            if (!Hash::check($token, $resetRecord->token)) {
                return [
                    'success' => false,
                    'message' => 'Token de réinitialisation invalide'
                ];
            }

            // Vérifier si le token n'est pas expiré (1 heure)
            if (now()->diffInHours($resetRecord->created_at) > 1) {
                // Supprimer le token expiré
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                
                return [
                    'success' => false,
                    'message' => 'Token de réinitialisation expiré. Veuillez faire une nouvelle demande.'
                ];
            }

            // Trouver l'utilisateur
            $user = User::where('email', $email)->first();
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ];
            }

            // Mettre à jour le mot de passe
            $user->update([
                'password' => Hash::make($newPassword),
                'password_changed_at' => now()
            ]);

            // Supprimer le token utilisé
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Envoyer une confirmation par email
            $this->sendPasswordChangedNotification($user);

            Log::info('Mot de passe réinitialisé avec succès', [
                'user_id' => $user->id,
                'email' => $email,
                'ip' => request()->ip()
            ]);

            return [
                'success' => true,
                'message' => 'Votre mot de passe a été réinitialisé avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur réinitialisation mot de passe', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation du mot de passe'
            ];
        }
    }

    /**
     * Envoyer une notification de changement de mot de passe
     */
    private function sendPasswordChangedNotification(User $user): void
    {
        try {
            $emailData = [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'change_time' => now()->format('d/m/Y à H:i:s'),
                'change_ip' => request()->ip()
            ];

            Mail::send('emails.password-changed', $emailData, function ($message) use ($user) {
                $message->to($user->email, $user->name)
                       ->subject('✅ Mot de passe modifié - SGEE');
            });

        } catch (\Exception $e) {
            Log::error('Erreur envoi notification changement mot de passe', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vérifier la validité d'un token
     */
    public function verifyResetToken(string $token, string $email): bool
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord) {
            return false;
        }

        // Vérifier le token et l'expiration
        return Hash::check($token, $resetRecord->token) && 
               now()->diffInHours($resetRecord->created_at) <= 1;
    }

    /**
     * Nettoyer les tokens expirés
     */
    public function cleanExpiredTokens(): int
    {
        return DB::table('password_reset_tokens')
            ->where('created_at', '<', now()->subHours(1))
            ->delete();
    }
}