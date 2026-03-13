<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class QRVerificationController extends Controller
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Vérifier un QR Code via son ID unique
     */
    public function verify($uniqueId)
    {
        try {
            // Log de la tentative de vérification
            Log::info('Tentative de vérification QR Code', [
                'unique_id' => $uniqueId,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()
            ]);

            // Vérifier le QR Code
            $result = $this->qrCodeService->verifyQRCode($uniqueId);

            // Retourner le résultat avec le bon code de statut
            $statusCode = $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification QR Code', [
                'unique_id' => $uniqueId,
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de la vérification',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Vérifier un QR Code via POST (pour les formulaires web)
     */
    public function verifyPost(Request $request)
    {
        try {
            $request->validate([
                'unique_id' => 'required|string|min:10|max:100'
            ]);

            $uniqueId = $request->input('unique_id');

            // Nettoyer l'ID (enlever espaces, tirets supplémentaires, etc.)
            $uniqueId = strtoupper(trim($uniqueId));
            $uniqueId = preg_replace('/[^A-Z0-9\-]/', '', $uniqueId);

            if (empty($uniqueId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de vérification invalide',
                    'status' => 'invalid'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Log de la tentative de vérification
            Log::info('Vérification QR Code via formulaire', [
                'unique_id' => $uniqueId,
                'original_input' => $request->input('unique_id'),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            // Vérifier le QR Code
            $result = $this->qrCodeService->verifyQRCode($uniqueId);

            // Ajouter des informations supplémentaires pour l'interface web
            if ($result['success'] && isset($result['data'])) {
                $result['data']['verification_method'] = 'form_submission';
                $result['data']['verified_from_ip'] = request()->ip();
            }

            $statusCode = $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

            return response()->json($result, $statusCode);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données de vérification invalides',
                'errors' => $e->errors(),
                'status' => 'validation_error'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification QR Code via POST', [
                'input' => $request->all(),
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de la vérification',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir les statistiques de vérification (admin seulement)
     */
    public function getVerificationStats()
    {
        try {
            // Vérifier que l'utilisateur est admin
            $user = auth('sanctum')->user();
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], Response::HTTP_FORBIDDEN);
            }

            // Compter les vérifications récentes (dernières 24h, 7 jours, 30 jours)
            $stats = [
                'total_qr_codes' => $this->countQRCodes(),
                'verifications_24h' => $this->countVerifications(24),
                'verifications_7d' => $this->countVerifications(168), // 7 * 24
                'verifications_30d' => $this->countVerifications(720), // 30 * 24
                'top_verified_documents' => $this->getTopVerifiedDocuments(),
                'verification_by_type' => $this->getVerificationsByType()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération stats vérification', [
                'error' => $e->getMessage(),
                'user_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Invalider un QR Code (admin seulement)
     */
    public function invalidateQRCode(Request $request, $uniqueId)
    {
        try {
            // Vérifier que l'utilisateur est admin
            $user = auth('sanctum')->user();
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'reason' => 'required|string|max:500'
            ]);

            $reason = $request->input('reason');

            // Invalider le QR Code
            $success = $this->qrCodeService->invalidateQRCode($uniqueId, $reason);

            if ($success) {
                // Log de l'invalidation
                Log::warning('QR Code invalidé par admin', [
                    'unique_id' => $uniqueId,
                    'reason' => $reason,
                    'admin_id' => $user->id,
                    'admin_name' => $user->name,
                    'ip' => request()->ip()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'QR Code invalidé avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code non trouvé ou déjà invalidé'
                ], Response::HTTP_NOT_FOUND);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Exception $e) {
            Log::error('Erreur invalidation QR Code', [
                'unique_id' => $uniqueId,
                'error' => $e->getMessage(),
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'invalidation'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Page de vérification publique (interface web)
     */
    public function verificationPage()
    {
        return view('qr-verification', [
            'title' => 'Vérification de Documents - SGEE Cameroun',
            'description' => 'Vérifiez l\'authenticité de vos documents officiels SGEE'
        ]);
    }

    /**
     * Compter le nombre total de QR Codes générés
     */
    private function countQRCodes(): int
    {
        try {
            $qrDir = storage_path('app/qr_verification');
            if (!is_dir($qrDir)) {
                return 0;
            }
            
            $files = glob($qrDir . '/*.json');
            return count($files);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Compter les vérifications dans une période donnée
     */
    private function countVerifications(int $hours): int
    {
        try {
            // En production, utiliser une vraie base de données pour les logs
            // Pour l'instant, simulation basée sur les logs Laravel
            $logFile = storage_path('logs/laravel.log');
            if (!file_exists($logFile)) {
                return 0;
            }

            $since = now()->subHours($hours);
            $count = 0;

            // Lecture simplifiée des logs (en production, utiliser une solution plus robuste)
            $handle = fopen($logFile, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    if (strpos($line, 'QR Code vérifié avec succès') !== false) {
                        // Extraire la date du log et comparer
                        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                            $logDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);
                            if ($logDate->gte($since)) {
                                $count++;
                            }
                        }
                    }
                }
                fclose($handle);
            }

            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtenir les documents les plus vérifiés
     */
    private function getTopVerifiedDocuments(): array
    {
        // Simulation - en production, utiliser une vraie base de données
        return [
            ['type' => 'enrollment', 'count' => 45, 'label' => 'Fiches d\'enrôlement'],
            ['type' => 'payment', 'count' => 32, 'label' => 'Quitus de paiement']
        ];
    }

    /**
     * Obtenir les vérifications par type de document
     */
    private function getVerificationsByType(): array
    {
        // Simulation - en production, utiliser une vraie base de données
        return [
            'enrollment' => 65,
            'payment' => 35
        ];
    }
}