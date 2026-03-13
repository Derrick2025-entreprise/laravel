<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class LoginNotificationService
{
    /**
     * Envoyer une notification de connexion par email
     */
    public function sendLoginNotification(User $user, array $loginDetails = []): array
    {
        try {
            $emailData = [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'login_time' => now()->format('d/m/Y à H:i:s'),
                'login_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'location' => $this->getLocationFromIP(request()->ip()),
                'device_info' => $this->parseUserAgent(request()->userAgent()),
                'is_suspicious' => $this->detectSuspiciousActivity($user, $loginDetails)
            ];

            Mail::send('emails.login-notification', $emailData, function ($message) use ($user) {
                $message->to($user->email, $user->name)
                       ->subject('🔐 Nouvelle connexion à votre compte SGEE');
            });

            Log::info('Notification de connexion envoyée', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip()
            ]);

            return [
                'success' => true,
                'message' => 'Notification de connexion envoyée'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur envoi notification connexion', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la notification'
            ];
        }
    }

    /**
     * Obtenir la localisation approximative depuis l'IP
     */
    private function getLocationFromIP(string $ip): string
    {
        // Pour les IPs locales
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return 'Connexion locale (développement)';
        }

        // Simulation de géolocalisation (en production, utiliser un service comme ipapi.co)
        $cameroonIPs = ['41.', '154.', '196.', '197.'];
        
        foreach ($cameroonIPs as $prefix) {
            if (strpos($ip, $prefix) === 0) {
                return 'Cameroun (approximatif)';
            }
        }

        return 'Localisation inconnue';
    }

    /**
     * Parser les informations du navigateur
     */
    private function parseUserAgent(string $userAgent): array
    {
        $info = [
            'browser' => 'Navigateur inconnu',
            'os' => 'Système inconnu',
            'device' => 'Appareil inconnu'
        ];

        // Détection du navigateur
        if (strpos($userAgent, 'Chrome') !== false) {
            $info['browser'] = 'Google Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $info['browser'] = 'Mozilla Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $info['browser'] = 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $info['browser'] = 'Microsoft Edge';
        }

        // Détection du système d'exploitation
        if (strpos($userAgent, 'Windows') !== false) {
            $info['os'] = 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            $info['os'] = 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $info['os'] = 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $info['os'] = 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            $info['os'] = 'iOS';
        }

        // Détection du type d'appareil
        if (strpos($userAgent, 'Mobile') !== false) {
            $info['device'] = 'Téléphone mobile';
        } elseif (strpos($userAgent, 'Tablet') !== false) {
            $info['device'] = 'Tablette';
        } else {
            $info['device'] = 'Ordinateur';
        }

        return $info;
    }

    /**
     * Détecter une activité suspecte
     */
    private function detectSuspiciousActivity(User $user, array $loginDetails): bool
    {
        // Vérifier si c'est une nouvelle IP
        $lastLogin = $user->last_login_ip ?? '';
        $currentIP = request()->ip();
        
        if ($lastLogin && $lastLogin !== $currentIP) {
            return true;
        }

        // Vérifier l'heure de connexion (connexions nocturnes suspectes)
        $hour = now()->hour;
        if ($hour >= 0 && $hour <= 5) {
            return true;
        }

        return false;
    }

    /**
     * Enregistrer les détails de connexion
     */
    public function logLoginDetails(User $user): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
            'last_login_user_agent' => request()->userAgent()
        ]);
    }
}