<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QRCodeService
{
    /**
     * Générer un QR Code unique pour un document
     */
    public function generateDocumentQRCode(string $documentType, int $documentId, array $data = []): array
    {
        try {
            // Générer un identifiant unique sécurisé
            $uniqueId = $this->generateSecureId($documentType, $documentId);
            
            // Créer les données du QR Code
            $qrData = [
                'type' => $documentType,
                'id' => $documentId,
                'unique_id' => $uniqueId,
                'timestamp' => now()->timestamp,
                'checksum' => $this->generateChecksum($documentType, $documentId, $uniqueId),
                'data' => $data
            ];

            // Encoder les données
            $qrContent = base64_encode(json_encode($qrData));
            
            // Générer l'URL de vérification
            $verificationUrl = config('app.url') . '/api/verify-qr/' . $uniqueId;
            
            // Créer le QR Code avec une librairie simple (simulation)
            $qrCodeData = $this->createQRCodeData($verificationUrl);
            
            // Stocker les informations de vérification
            $this->storeVerificationData($uniqueId, $qrData);
            
            return [
                'success' => true,
                'unique_id' => $uniqueId,
                'qr_content' => $qrContent,
                'verification_url' => $verificationUrl,
                'qr_code_data' => $qrCodeData,
                'expires_at' => now()->addYears(5) // QR Code valide 5 ans
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur génération QR Code', [
                'document_type' => $documentType,
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la génération du QR Code'
            ];
        }
    }

    /**
     * Vérifier un QR Code
     */
    public function verifyQRCode(string $uniqueId): array
    {
        try {
            // Récupérer les données de vérification
            $verificationData = $this->getVerificationData($uniqueId);
            
            if (!$verificationData) {
                return [
                    'success' => false,
                    'message' => 'QR Code invalide ou expiré',
                    'status' => 'invalid'
                ];
            }

            // Vérifier l'intégrité
            $expectedChecksum = $this->generateChecksum(
                $verificationData['type'],
                $verificationData['id'],
                $verificationData['unique_id']
            );

            if ($verificationData['checksum'] !== $expectedChecksum) {
                Log::warning('Tentative de vérification QR Code avec checksum invalide', [
                    'unique_id' => $uniqueId,
                    'ip' => request()->ip()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'QR Code corrompu ou falsifié',
                    'status' => 'corrupted'
                ];
            }

            // Vérifier l'expiration
            $createdAt = \Carbon\Carbon::createFromTimestamp($verificationData['timestamp']);
            if ($createdAt->addYears(5)->isPast()) {
                return [
                    'success' => false,
                    'message' => 'QR Code expiré',
                    'status' => 'expired'
                ];
            }

            // Log de la vérification réussie
            Log::info('QR Code vérifié avec succès', [
                'unique_id' => $uniqueId,
                'document_type' => $verificationData['type'],
                'document_id' => $verificationData['id'],
                'ip' => request()->ip()
            ]);

            return [
                'success' => true,
                'message' => 'QR Code valide',
                'status' => 'valid',
                'data' => [
                    'document_type' => $verificationData['type'],
                    'document_id' => $verificationData['id'],
                    'created_at' => $createdAt->format('d/m/Y H:i'),
                    'verified_at' => now()->format('d/m/Y H:i'),
                    'document_data' => $verificationData['data'] ?? []
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur vérification QR Code', [
                'unique_id' => $uniqueId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'status' => 'error'
            ];
        }
    }

    /**
     * Générer un identifiant sécurisé unique
     */
    private function generateSecureId(string $documentType, int $documentId): string
    {
        $timestamp = now()->timestamp;
        $random = Str::random(16);
        $hash = hash('sha256', $documentType . $documentId . $timestamp . $random . config('app.key'));
        
        return 'SGEE-' . strtoupper(substr($hash, 0, 12)) . '-' . $timestamp;
    }

    /**
     * Générer un checksum pour vérifier l'intégrité
     */
    private function generateChecksum(string $documentType, int $documentId, string $uniqueId): string
    {
        return hash('sha256', $documentType . $documentId . $uniqueId . config('app.key'));
    }

    /**
     * Créer les données du QR Code (simulation - en production utiliser une vraie librairie)
     */
    private function createQRCodeData(string $content): string
    {
        // En production, utiliser une librairie comme endroid/qr-code
        // Pour la simulation, on retourne un placeholder
        return "data:image/png;base64," . base64_encode("QR_CODE_PLACEHOLDER_FOR_" . $content);
    }

    /**
     * Stocker les données de vérification
     */
    private function storeVerificationData(string $uniqueId, array $data): void
    {
        // Stocker dans un fichier sécurisé (en production, utiliser Redis ou base de données)
        $filePath = 'qr_verification/' . $uniqueId . '.json';
        Storage::disk('local')->put($filePath, json_encode($data));
    }

    /**
     * Récupérer les données de vérification
     */
    private function getVerificationData(string $uniqueId): ?array
    {
        try {
            $filePath = 'qr_verification/' . $uniqueId . '.json';
            
            if (!Storage::disk('local')->exists($filePath)) {
                return null;
            }
            
            $content = Storage::disk('local')->get($filePath);
            return json_decode($content, true);
            
        } catch (\Exception $e) {
            Log::error('Erreur récupération données QR', [
                'unique_id' => $uniqueId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Générer un QR Code pour une fiche d'enrôlement
     */
    public function generateEnrollmentQRCode(int $studentId, array $studentData): array
    {
        return $this->generateDocumentQRCode('enrollment', $studentId, [
            'student_number' => $studentData['student_number'] ?? null,
            'full_name' => $studentData['full_name'] ?? null,
            'school' => $studentData['school'] ?? null,
            'filiere' => $studentData['filiere'] ?? null,
            'enrollment_date' => $studentData['enrollment_date'] ?? null
        ]);
    }

    /**
     * Générer un QR Code pour un quitus de paiement
     */
    public function generatePaymentQRCode(int $paymentId, array $paymentData): array
    {
        return $this->generateDocumentQRCode('payment', $paymentId, [
            'reference_number' => $paymentData['reference_number'] ?? null,
            'amount' => $paymentData['amount'] ?? null,
            'payment_method' => $paymentData['payment_method'] ?? null,
            'student_name' => $paymentData['student_name'] ?? null,
            'payment_date' => $paymentData['payment_date'] ?? null,
            'validated_by' => $paymentData['validated_by'] ?? null
        ]);
    }

    /**
     * Invalider un QR Code (en cas de fraude ou d'erreur)
     */
    public function invalidateQRCode(string $uniqueId, string $reason = 'Manual invalidation'): bool
    {
        try {
            $filePath = 'qr_verification/' . $uniqueId . '.json';
            
            if (Storage::disk('local')->exists($filePath)) {
                // Marquer comme invalide au lieu de supprimer (pour audit)
                $data = json_decode(Storage::disk('local')->get($filePath), true);
                $data['invalidated'] = true;
                $data['invalidated_at'] = now()->timestamp;
                $data['invalidation_reason'] = $reason;
                
                Storage::disk('local')->put($filePath, json_encode($data));
                
                Log::info('QR Code invalidé', [
                    'unique_id' => $uniqueId,
                    'reason' => $reason
                ]);
                
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('Erreur invalidation QR Code', [
                'unique_id' => $uniqueId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}