<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnrollmentRequest;
use App\Http\Requests\PaymentRequest;
use App\Services\SecureDocumentService;
use App\Services\QRCodeService;
use App\Models\Student;
use App\Models\Payment;
use App\Models\School;
use App\Models\Department;
use App\Models\Filiere;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SecureEnrollmentController extends Controller
{
    protected $secureDocumentService;
    protected $qrCodeService;

    public function __construct(SecureDocumentService $secureDocumentService, QRCodeService $qrCodeService)
    {
        $this->secureDocumentService = $secureDocumentService;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Enrôlement sécurisé avec validation stricte
     */
    public function secureEnroll(EnrollmentRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = auth('sanctum')->user();
            $validatedData = $request->validated();

            // Vérifier si l'étudiant existe déjà
            $existingStudent = Student::where('user_id', $user->id)->first();
            if ($existingStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous êtes déjà enrôlé dans le système'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Générer un numéro d'étudiant unique
            $studentNumber = $this->generateStudentNumber();

            // Créer l'étudiant avec toutes les validations
            $student = Student::create([
                'user_id' => $user->id,
                'student_number' => $studentNumber,
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'date_of_birth' => $validatedData['date_of_birth'],
                'place_of_birth' => $validatedData['place_of_birth'],
                'gender' => $validatedData['gender'],
                'nationality' => $validatedData['nationality'],
                'address' => $validatedData['address'],
                'filiere_id' => $validatedData['filiere_id'],
                'previous_education_level' => $validatedData['previous_education_level'],
                'previous_institution' => $validatedData['previous_institution'],
                'graduation_year' => $validatedData['graduation_year'],
                'average_grade' => $validatedData['average_grade'],
                'emergency_contact_name' => $validatedData['emergency_contact_name'],
                'emergency_contact_phone' => $validatedData['emergency_contact_phone'],
                'emergency_contact_relationship' => $validatedData['emergency_contact_relationship'],
                'preferred_submission_center_id' => $validatedData['preferred_submission_center_id'] ?? null,
                'enrollment_date' => now(),
                'status' => 'enrolled',
                'payment_status' => 'pending'
            ]);

            // Traiter la photo de profil si fournie
            if ($request->hasFile('profile_photo')) {
                $photoPath = $request->file('profile_photo')->store('student_photos', 'public');
                $student->update(['profile_photo' => $photoPath]);
            }

            // Traiter les documents supplémentaires
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $index => $file) {
                    $documentPath = $file->store('student_documents', 'private');
                    
                    $student->studentDocuments()->create([
                        'document_type' => 'enrollment_document_' . ($index + 1),
                        'document_name' => $file->getClientOriginalName(),
                        'file_path' => $documentPath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'status' => 'pending_verification',
                        'uploaded_at' => now()
                    ]);
                }
            }

            // Générer automatiquement la fiche d'enrôlement sécurisée
            $enrollmentCardResult = $this->secureDocumentService->generateEnrollmentCard($student);
            
            if (!$enrollmentCardResult['success']) {
                throw new \Exception('Erreur génération fiche enrôlement: ' . $enrollmentCardResult['message']);
            }

            // Envoyer les documents par email
            $emailSent = $this->secureDocumentService->sendDocumentsByEmail(
                $student->email,
                [$enrollmentCardResult],
                $student->full_name
            );

            DB::commit();

            // Log de l'enrôlement sécurisé
            Log::info('Enrôlement sécurisé réussi', [
                'student_id' => $student->id,
                'student_number' => $student->student_number,
                'user_id' => $user->id,
                'filiere_id' => $validatedData['filiere_id'],
                'qr_unique_id' => $enrollmentCardResult['qr_unique_id'],
                'email_sent' => $emailSent,
                'ip' => request()->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enrôlement sécurisé réussi ! Vos documents ont été générés.',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_number' => $student->student_number,
                        'full_name' => $student->full_name,
                        'email' => $student->email,
                        'enrollment_date' => $student->enrollment_date->format('d/m/Y H:i')
                    ],
                    'enrollment_card' => [
                        'document_number' => $enrollmentCardResult['document_number'],
                        'qr_unique_id' => $enrollmentCardResult['qr_unique_id'],
                        'verification_url' => $enrollmentCardResult['verification_url']
                    ],
                    'email_sent' => $emailSent,
                    'next_steps' => [
                        'Vérifiez votre email pour les documents officiels',
                        'Procédez au paiement des frais d\'inscription',
                        'Soumettez vos documents au centre de dépôt choisi'
                    ]
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur enrôlement sécurisé', [
                'user_id' => auth('sanctum')->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => request()->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enrôlement sécurisé',
                'error' => app()->environment('local') ? $e->getMessage() : 'Erreur interne'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Paiement sécurisé avec validation stricte
     */
    public function securePayment(PaymentRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = auth('sanctum')->user();
            $validatedData = $request->validated();

            // Vérifier que l'étudiant existe
            $student = Student::where('user_id', $user->id)->first();
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez d\'abord vous enrôler avant de faire un paiement'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Générer une référence de paiement unique
            $referenceNumber = $this->generatePaymentReference();

            // Créer le paiement avec validation
            $payment = Payment::create([
                'student_id' => $student->id,
                'user_id' => $user->id,
                'reference_number' => $referenceNumber,
                'payment_type' => $validatedData['payment_type'],
                'amount' => $validatedData['amount'],
                'payment_method' => $validatedData['payment_method'],
                'phone_number' => $validatedData['phone_number'] ?? null,
                'bank_name' => $validatedData['bank_name'] ?? null,
                'account_number' => $validatedData['account_number'] ?? null,
                'transaction_reference' => $validatedData['transaction_reference'] ?? null,
                'check_number' => $validatedData['check_number'] ?? null,
                'check_date' => $validatedData['check_date'] ?? null,
                'payment_location' => $validatedData['payment_location'] ?? null,
                'payment_date' => now(),
                'status' => 'pending_validation',
                'notes' => 'Paiement soumis via formulaire sécurisé'
            ]);

            // Traiter le fichier de reçu si fourni
            if ($request->hasFile('receipt_file')) {
                $receiptPath = $request->file('receipt_file')->store('payment_receipts', 'private');
                $payment->update(['receipt_file' => $receiptPath]);
            }

            // Validation automatique pour certaines méthodes (simulation)
            if (in_array($validatedData['payment_method'], ['orange_money', 'mtn_money'])) {
                // Simulation de validation automatique pour mobile money
                $payment->update([
                    'status' => 'validated',
                    'validated_at' => now(),
                    'validator_id' => 1 // ID admin système
                ]);

                // Générer le quitus de paiement sécurisé
                $receiptResult = $this->secureDocumentService->generatePaymentReceipt($payment);
                
                if ($receiptResult['success']) {
                    // Envoyer le quitus par email
                    $emailSent = $this->secureDocumentService->sendDocumentsByEmail(
                        $student->email,
                        [$receiptResult],
                        $student->full_name
                    );

                    // Mettre à jour le statut de paiement de l'étudiant
                    $student->updatePaymentStatus();
                }
            }

            DB::commit();

            // Log du paiement sécurisé
            Log::info('Paiement sécurisé soumis', [
                'payment_id' => $payment->id,
                'reference_number' => $payment->reference_number,
                'student_id' => $student->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'ip' => request()->ip()
            ]);

            $responseData = [
                'success' => true,
                'message' => 'Paiement sécurisé enregistré avec succès',
                'data' => [
                    'payment' => [
                        'id' => $payment->id,
                        'reference_number' => $payment->reference_number,
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'status' => $payment->status,
                        'payment_date' => $payment->payment_date->format('d/m/Y H:i')
                    ]
                ]
            ];

            // Ajouter les informations du quitus si généré
            if (isset($receiptResult) && $receiptResult['success']) {
                $responseData['data']['receipt'] = [
                    'document_number' => $receiptResult['document_number'],
                    'qr_unique_id' => $receiptResult['qr_unique_id'],
                    'verification_url' => $receiptResult['verification_url']
                ];
                $responseData['data']['email_sent'] = $emailSent ?? false;
            }

            return response()->json($responseData, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur paiement sécurisé', [
                'user_id' => auth('sanctum')->id(),
                'payment_data' => $request->except(['receipt_file']),
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement sécurisé',
                'error' => app()->environment('local') ? $e->getMessage() : 'Erreur interne'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Télécharger un document sécurisé
     */
    public function downloadSecureDocument(Request $request, $documentType, $documentId)
    {
        try {
            $user = auth('sanctum')->user();

            // Vérifier les permissions
            if ($documentType === 'enrollment') {
                $student = Student::where('id', $documentId)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
                
                // Générer la fiche d'enrôlement à la demande
                $result = $this->secureDocumentService->generateEnrollmentCard($student);
                
            } elseif ($documentType === 'payment') {
                $payment = Payment::where('id', $documentId)
                    ->where('user_id', $user->id)
                    ->where('status', 'validated')
                    ->firstOrFail();
                
                // Générer le quitus de paiement à la demande
                $result = $this->secureDocumentService->generatePaymentReceipt($payment);
                
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de document non reconnu'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            // Log du téléchargement
            Log::info('Téléchargement document sécurisé', [
                'user_id' => $user->id,
                'document_type' => $documentType,
                'document_id' => $documentId,
                'qr_unique_id' => $result['qr_unique_id'],
                'ip' => request()->ip()
            ]);

            // Retourner le fichier PDF
            return Storage::download($result['file_path'], $result['file_name']);

        } catch (\Exception $e) {
            Log::error('Erreur téléchargement document sécurisé', [
                'user_id' => auth('sanctum')->id(),
                'document_type' => $documentType,
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du document'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir le statut de sécurité d'un étudiant
     */
    public function getSecurityStatus()
    {
        try {
            $user = auth('sanctum')->user();
            $student = Student::where('user_id', $user->id)->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil étudiant non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Calculer le score de sécurité
            $securityScore = $this->calculateSecurityScore($student);

            return response()->json([
                'success' => true,
                'data' => [
                    'student_number' => $student->student_number,
                    'security_score' => $securityScore,
                    'enrollment_verified' => $student->status === 'enrolled',
                    'payment_status' => $student->payment_status,
                    'documents_count' => $student->studentDocuments()->count(),
                    'verified_documents' => $student->studentDocuments()->where('status', 'verified')->count(),
                    'qr_codes_generated' => $this->countQRCodesForStudent($student->id),
                    'last_activity' => $student->updated_at->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération statut sécurité', [
                'user_id' => auth('sanctum')->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Générer un numéro d'étudiant unique
     */
    private function generateStudentNumber(): string
    {
        $year = date('Y');
        $sequence = Student::whereYear('created_at', $year)->count() + 1;
        return 'SGEE' . $year . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Générer une référence de paiement unique
     */
    private function generatePaymentReference(): string
    {
        $year = date('Y');
        $month = date('m');
        $sequence = Payment::whereYear('created_at', $year)->count() + 1;
        return 'PAY' . $year . $month . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculer le score de sécurité d'un étudiant
     */
    private function calculateSecurityScore(Student $student): int
    {
        $score = 0;

        // Profil complet (30 points)
        if ($student->profile_completion >= 90) $score += 30;
        elseif ($student->profile_completion >= 70) $score += 20;
        elseif ($student->profile_completion >= 50) $score += 10;

        // Statut vérifié (25 points)
        if ($student->status === 'enrolled') $score += 25;

        // Paiements validés (25 points)
        if ($student->payment_status === 'completed') $score += 25;
        elseif ($student->payment_status === 'partial') $score += 15;

        // Documents vérifiés (20 points)
        $totalDocs = $student->studentDocuments()->count();
        $verifiedDocs = $student->studentDocuments()->where('status', 'verified')->count();
        if ($totalDocs > 0) {
            $score += intval(($verifiedDocs / $totalDocs) * 20);
        }

        return min($score, 100);
    }

    /**
     * Compter les QR Codes générés pour un étudiant
     */
    private function countQRCodesForStudent(int $studentId): int
    {
        // Simulation - en production, utiliser une vraie base de données
        return 2; // Fiche enrôlement + quitus paiement
    }
}