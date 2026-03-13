<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class SecureDocumentService
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Générer une fiche d'enrôlement sécurisée avec QR Code
     */
    public function generateEnrollmentCard(Student $student): array
    {
        try {
            // Générer le QR Code unique
            $qrResult = $this->qrCodeService->generateEnrollmentQRCode($student->id, [
                'student_number' => $student->student_number,
                'full_name' => $student->full_name,
                'school' => $student->school->name ?? 'N/A',
                'filiere' => $student->filiere->name ?? 'N/A',
                'enrollment_date' => $student->enrollment_date->format('d/m/Y')
            ]);

            if (!$qrResult['success']) {
                throw new \Exception('Erreur génération QR Code: ' . $qrResult['message']);
            }

            // Préparer les données pour le PDF
            $data = [
                'student' => $student,
                'school' => $student->school,
                'department' => $student->department,
                'filiere' => $student->filiere,
                'qr_code' => [
                    'unique_id' => $qrResult['unique_id'],
                    'verification_url' => $qrResult['verification_url'],
                    'qr_code_data' => $qrResult['qr_code_data']
                ],
                'generated_at' => now()->format('d/m/Y H:i'),
                'document_number' => $this->generateDocumentNumber('ENR', $student->id),
                'security_features' => [
                    'watermark' => 'SGEE CAMEROUN - DOCUMENT OFFICIEL',
                    'serial_number' => $this->generateSerialNumber(),
                    'digital_signature' => $this->generateDigitalSignature($student->id, 'enrollment')
                ]
            ];

            // Générer le PDF sécurisé
            $pdf = Pdf::loadView('pdf.secure-enrollment-card', $data);
            $pdf->setPaper('A4', 'portrait');
            
            // Ajouter des métadonnées de sécurité
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans'
            ]);

            // Nom du fichier sécurisé
            $fileName = 'enrollment_card_' . $student->student_number . '_' . now()->format('YmdHis') . '.pdf';
            $filePath = 'secure_documents/enrollment_cards/' . $fileName;

            // Sauvegarder le PDF
            Storage::put($filePath, $pdf->output());

            // Log de génération
            Log::info('Fiche d\'enrôlement générée', [
                'student_id' => $student->id,
                'student_number' => $student->student_number,
                'qr_unique_id' => $qrResult['unique_id'],
                'file_path' => $filePath
            ]);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'qr_unique_id' => $qrResult['unique_id'],
                'verification_url' => $qrResult['verification_url'],
                'document_number' => $data['document_number']
            ];

        } catch (\Exception $e) {
            Log::error('Erreur génération fiche enrôlement', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la génération de la fiche d\'enrôlement'
            ];
        }
    }

    /**
     * Générer un quitus de paiement sécurisé avec QR Code
     */
    public function generatePaymentReceipt(Payment $payment): array
    {
        try {
            // Générer le QR Code unique
            $qrResult = $this->qrCodeService->generatePaymentQRCode($payment->id, [
                'reference_number' => $payment->reference_number,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'student_name' => $payment->student->full_name ?? 'N/A',
                'payment_date' => $payment->payment_date->format('d/m/Y'),
                'validated_by' => $payment->validator->name ?? 'Système'
            ]);

            if (!$qrResult['success']) {
                throw new \Exception('Erreur génération QR Code: ' . $qrResult['message']);
            }

            // Préparer les données pour le PDF
            $data = [
                'payment' => $payment,
                'student' => $payment->student,
                'school' => $payment->student->school ?? null,
                'validator' => $payment->validator,
                'qr_code' => [
                    'unique_id' => $qrResult['unique_id'],
                    'verification_url' => $qrResult['verification_url'],
                    'qr_code_data' => $qrResult['qr_code_data']
                ],
                'generated_at' => now()->format('d/m/Y H:i'),
                'document_number' => $this->generateDocumentNumber('PAY', $payment->id),
                'security_features' => [
                    'watermark' => 'SGEE CAMEROUN - QUITUS OFFICIEL',
                    'serial_number' => $this->generateSerialNumber(),
                    'digital_signature' => $this->generateDigitalSignature($payment->id, 'payment')
                ],
                'amount_in_words' => $this->convertAmountToWords($payment->amount)
            ];

            // Générer le PDF sécurisé
            $pdf = Pdf::loadView('pdf.secure-payment-receipt', $data);
            $pdf->setPaper('A4', 'portrait');

            // Nom du fichier sécurisé
            $fileName = 'payment_receipt_' . $payment->reference_number . '_' . now()->format('YmdHis') . '.pdf';
            $filePath = 'secure_documents/payment_receipts/' . $fileName;

            // Sauvegarder le PDF
            Storage::put($filePath, $pdf->output());

            // Log de génération
            Log::info('Quitus de paiement généré', [
                'payment_id' => $payment->id,
                'reference_number' => $payment->reference_number,
                'amount' => $payment->amount,
                'qr_unique_id' => $qrResult['unique_id'],
                'file_path' => $filePath
            ]);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'qr_unique_id' => $qrResult['unique_id'],
                'verification_url' => $qrResult['verification_url'],
                'document_number' => $data['document_number']
            ];

        } catch (\Exception $e) {
            Log::error('Erreur génération quitus paiement', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la génération du quitus de paiement'
            ];
        }
    }

    /**
     * Envoyer les documents par email de manière sécurisée
     */
    public function sendDocumentsByEmail(string $email, array $documents, string $studentName): bool
    {
        try {
            // Vérifier que l'email est valide et sécurisé
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Adresse email invalide');
            }

            // Préparer les pièces jointes sécurisées
            $attachments = [];
            foreach ($documents as $document) {
                if (isset($document['file_path']) && Storage::exists($document['file_path'])) {
                    $attachments[] = [
                        'path' => Storage::path($document['file_path']),
                        'name' => $document['file_name'],
                        'mime' => 'application/pdf'
                    ];
                }
            }

            // Envoyer l'email avec les documents
            Mail::send('emails.secure-documents', [
                'student_name' => $studentName,
                'documents' => $documents,
                'sent_at' => now()->format('d/m/Y H:i')
            ], function ($message) use ($email, $studentName, $attachments) {
                $message->to($email, $studentName)
                        ->subject('SGEE Cameroun - Documents Officiels')
                        ->from(config('mail.from.address'), 'SGEE Cameroun');

                foreach ($attachments as $attachment) {
                    $message->attach($attachment['path'], [
                        'as' => $attachment['name'],
                        'mime' => $attachment['mime']
                    ]);
                }
            });

            // Log de l'envoi
            Log::info('Documents envoyés par email', [
                'email' => $email,
                'student_name' => $studentName,
                'documents_count' => count($documents)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur envoi documents par email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Générer un numéro de document unique
     */
    private function generateDocumentNumber(string $prefix, int $id): string
    {
        $year = date('Y');
        $month = date('m');
        return $prefix . $year . $month . str_pad($id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Générer un numéro de série sécurisé
     */
    private function generateSerialNumber(): string
    {
        return 'SN' . now()->format('YmdHis') . rand(1000, 9999);
    }

    /**
     * Générer une signature numérique
     */
    private function generateDigitalSignature(int $id, string $type): string
    {
        $data = $id . $type . now()->timestamp . config('app.key');
        return hash('sha256', $data);
    }

    /**
     * Convertir un montant en lettres (français)
     */
    private function convertAmountToWords(float $amount): string
    {
        // Implémentation simplifiée - en production utiliser une librairie dédiée
        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $teens = ['dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];

        $intAmount = (int) $amount;
        
        if ($intAmount == 0) {
            return 'zéro francs CFA';
        }

        // Implémentation basique pour les montants courants
        if ($intAmount < 1000) {
            return $intAmount . ' francs CFA';
        } elseif ($intAmount < 1000000) {
            $thousands = intval($intAmount / 1000);
            $remainder = $intAmount % 1000;
            $result = $thousands . ' mille';
            if ($remainder > 0) {
                $result .= ' ' . $remainder;
            }
            return $result . ' francs CFA';
        } else {
            return number_format($intAmount, 0, ',', ' ') . ' francs CFA';
        }
    }

    /**
     * Vérifier l'intégrité d'un document
     */
    public function verifyDocumentIntegrity(string $filePath): array
    {
        try {
            if (!Storage::exists($filePath)) {
                return [
                    'success' => false,
                    'message' => 'Document non trouvé'
                ];
            }

            $fileContent = Storage::get($filePath);
            $fileHash = hash('sha256', $fileContent);
            $fileSize = Storage::size($filePath);

            return [
                'success' => true,
                'file_hash' => $fileHash,
                'file_size' => $fileSize,
                'last_modified' => Storage::lastModified($filePath),
                'verified_at' => now()->timestamp
            ];

        } catch (\Exception $e) {
            Log::error('Erreur vérification intégrité document', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification'
            ];
        }
    }
}