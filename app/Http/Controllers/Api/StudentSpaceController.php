<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\Department;
use App\Models\Filiere;
use App\Models\Payment;
use App\Models\StudentDocument;
use App\Services\QRCodeService;
use App\Services\SecureDocumentService;
use App\Mail\StudentEnrollmentConfirmation;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentSpaceController extends Controller
{
    protected $qrCodeService;
    protected $secureDocumentService;

    public function __construct(QRCodeService $qrCodeService, SecureDocumentService $secureDocumentService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->secureDocumentService = $secureDocumentService;
    }

    /**
     * Dashboard étudiant avec données temps réel
     */
    public function dashboard(): JsonResponse
    {
        try {
            $user = Auth::user();
            $student = Student::where('user_id', $user->id)->first();

            // Statistiques personnelles temps réel
            $personalStats = [
                'total_enrollments' => $student ? $student->enrollments()->count() : 0,
                'confirmed_enrollments' => $student ? $student->enrollments()->where('status', 'confirmed')->count() : 0,
                'pending_enrollments' => $student ? $student->enrollments()->where('status', 'pending')->count() : 0,
                'validated_documents' => $student ? $student->studentDocuments()->where('status', 'validated')->count() : 0,
                'pending_documents' => $student ? $student->studentDocuments()->where('status', 'pending')->count() : 0,
                'completed_payments' => $student ? $student->payments()->where('status', 'validated')->count() : 0,
                'pending_payments' => $student ? $student->payments()->where('status', 'pending')->count() : 0,
                'profile_completion' => $this->calculateProfileCompletion($student),
                'academic_year' => '2025-2026',
                'student_id' => $student->student_number ?? null
            ];

            // Inscriptions récentes avec relations
            $recentEnrollments = $student ? $student->enrollments()
                ->with(['school', 'department', 'filiere'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($enrollment) {
                    return [
                        'id' => $enrollment->id,
                        'school_name' => $enrollment->school->name,
                        'school_sigle' => $enrollment->school->sigle,
                        'department_name' => $enrollment->department->name,
                        'filiere_name' => $enrollment->filiere->name,
                        'status' => $enrollment->status,
                        'enrollment_date' => $enrollment->created_at->format('d/m/Y'),
                        'academic_year' => $enrollment->academic_year,
                        'tuition_fees' => $enrollment->tuition_fees,
                        'registration_fees' => $enrollment->registration_fees
                    ];
                }) : collect();

            // Documents récents
            $recentDocuments = $student ? $student->studentDocuments()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($document) {
                    return [
                        'id' => $document->id,
                        'document_name' => $document->document_name,
                        'document_type' => $document->document_type,
                        'status' => $document->status,
                        'uploaded_at' => $document->created_at->format('d/m/Y H:i'),
                        'file_size' => $document->file_size,
                        'verified_at' => $document->verified_at ? $document->verified_at->format('d/m/Y H:i') : null
                    ];
                }) : collect();

            // Paiements récents
            $recentPayments = $student ? $student->payments()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'status' => $payment->status,
                        'payment_date' => $payment->created_at->format('d/m/Y H:i'),
                        'reference' => $payment->reference,
                        'description' => $payment->description
                    ];
                }) : collect();

            // Notifications importantes
            $notifications = $this->getStudentNotifications($student);

            // Prochaines échéances
            $upcomingDeadlines = $this->getUpcomingDeadlines($student);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard étudiant chargé avec succès',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar ?? null
                    ],
                    'student' => $student ? [
                        'id' => $student->id,
                        'student_number' => $student->student_number,
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'phone' => $student->phone,
                        'date_of_birth' => $student->date_of_birth,
                        'place_of_birth' => $student->place_of_birth,
                        'status' => $student->status
                    ] : null,
                    'statistics' => $personalStats,
                    'recent_enrollments' => $recentEnrollments,
                    'recent_documents' => $recentDocuments,
                    'recent_payments' => $recentPayments,
                    'notifications' => $notifications,
                    'upcoming_deadlines' => $upcomingDeadlines,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enrôlement en ligne complet
     */
    public function enroll(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'school_id' => 'required|exists:schools,id',
                'department_id' => 'required|exists:departments,id',
                'filiere_id' => 'required|exists:filieres,id',
                'academic_year' => 'required|string',
                'personal_info' => 'required|array',
                'documents' => 'required|array',
                'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120' // 5MB max
            ]);

            DB::beginTransaction();

            $user = Auth::user();
            
            // Créer ou mettre à jour le profil étudiant
            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                array_merge($request->personal_info, [
                    'school_id' => $request->school_id,
                    'department_id' => $request->department_id,
                    'filiere_id' => $request->filiere_id,
                    'academic_year' => $request->academic_year,
                    'enrollment_date' => now(),
                    'status' => 'enrolled',
                    'student_number' => $this->generateStudentNumber()
                ])
            );

            // Traitement des documents uploadés
            $uploadedDocuments = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $key => $file) {
                    $documentPath = $file->store('student_documents/' . $student->id, 'private');
                    
                    $document = StudentDocument::create([
                        'student_id' => $student->id,
                        'document_name' => $file->getClientOriginalName(),
                        'document_type' => $key,
                        'file_path' => $documentPath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'status' => 'pending',
                        'uploaded_at' => now()
                    ]);

                    $uploadedDocuments[] = $document;
                }
            }

            // Génération de la fiche d'enrôlement avec QR Code
            $enrollmentCard = $this->generateEnrollmentCard($student);

            // Envoi de l'email de confirmation
            Mail::to($user->email)->send(new StudentEnrollmentConfirmation($student, $enrollmentCard));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Enrôlement effectué avec succès',
                'data' => [
                    'student' => $student,
                    'documents' => $uploadedDocuments,
                    'enrollment_card_url' => $enrollmentCard['url'],
                    'qr_code' => $enrollmentCard['qr_code']
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enrôlement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestion du quitus de paiement
     */
    public function managePaymentReceipt(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'action' => 'required|in:upload,update,download',
                'payment_id' => 'required_if:action,update,download|exists:payments,id',
                'receipt_file' => 'required_if:action,upload|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'amount' => 'required_if:action,upload|numeric|min:0',
                'payment_method' => 'required_if:action,upload|string',
                'description' => 'nullable|string'
            ]);

            $user = Auth::user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            switch ($request->action) {
                case 'upload':
                    return $this->uploadPaymentReceipt($request, $student);
                case 'update':
                    return $this->updatePaymentReceipt($request, $student);
                case 'download':
                    return $this->downloadValidatedReceipt($request, $student);
                default:
                    throw new \InvalidArgumentException('Action non supportée');
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la gestion du quitus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les filières disponibles
     */
    public function getAvailableFilieres(): JsonResponse
    {
        try {
            $filieres = Filiere::with(['department', 'school'])
                ->where('is_active', true)
                ->get()
                ->map(function($filiere) {
                    return [
                        'id' => $filiere->id,
                        'name' => $filiere->name,
                        'code' => $filiere->code,
                        'description' => $filiere->description,
                        'duration_years' => $filiere->duration_years,
                        'tuition_fees' => $filiere->tuition_fees,
                        'registration_fees' => $filiere->registration_fees,
                        'department' => [
                            'id' => $filiere->department->id,
                            'name' => $filiere->department->name
                        ],
                        'school' => [
                            'id' => $filiere->school->id,
                            'name' => $filiere->school->name,
                            'sigle' => $filiere->school->sigle
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Filières disponibles récupérées',
                'data' => $filieres
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des filières',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Méthodes privées utilitaires
     */
    private function calculateProfileCompletion($student): int
    {
        if (!$student) return 0;

        $fields = [
            'first_name', 'last_name', 'phone', 'date_of_birth', 
            'place_of_birth', 'school_id', 'department_id', 'filiere_id'
        ];

        $completedFields = 0;
        foreach ($fields as $field) {
            if (!empty($student->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($fields)) * 100);
    }

    private function generateStudentNumber(): string
    {
        $year = date('Y');
        $lastStudent = Student::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastStudent ? (int)substr($lastStudent->student_number, -4) + 1 : 1;
        
        return 'STU' . $year . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    private function generateEnrollmentCard($student): array
    {
        $qrData = [
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'verification_url' => url('/api/verify-qr/' . $student->student_number),
            'generated_at' => now()->toISOString()
        ];

        $qrCode = $this->qrCodeService->generateQRCode(json_encode($qrData));
        
        $pdfPath = $this->secureDocumentService->generateEnrollmentCard($student, $qrCode);

        return [
            'url' => Storage::url($pdfPath),
            'qr_code' => $qrCode,
            'path' => $pdfPath
        ];
    }

    private function getStudentNotifications($student): array
    {
        $notifications = [];

        if (!$student) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Profil incomplet',
                'message' => 'Veuillez compléter votre profil étudiant',
                'action_url' => '/student/profile'
            ];
            return $notifications;
        }

        // Vérifier les documents en attente
        $pendingDocs = $student->studentDocuments()->where('status', 'pending')->count();
        if ($pendingDocs > 0) {
            $notifications[] = [
                'type' => 'info',
                'title' => 'Documents en attente',
                'message' => "{$pendingDocs} document(s) en cours de vérification",
                'action_url' => '/student/documents'
            ];
        }

        // Vérifier les paiements en attente
        $pendingPayments = $student->payments()->where('status', 'pending')->count();
        if ($pendingPayments > 0) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Paiements en attente',
                'message' => "{$pendingPayments} paiement(s) nécessitent une validation",
                'action_url' => '/student/payments'
            ];
        }

        return $notifications;
    }

    private function getUpcomingDeadlines($student): array
    {
        // Simuler des échéances importantes
        return [
            [
                'title' => 'Date limite inscription',
                'date' => '2026-03-15',
                'days_remaining' => 45,
                'type' => 'enrollment'
            ],
            [
                'title' => 'Paiement frais de scolarité',
                'date' => '2026-02-28',
                'days_remaining' => 28,
                'type' => 'payment'
            ]
        ];
    }

    private function uploadPaymentReceipt(Request $request, $student): JsonResponse
    {
        $receiptPath = $request->file('receipt_file')->store('payment_receipts/' . $student->id, 'private');

        $payment = Payment::create([
            'student_id' => $student->id,
            'user_id' => $student->user_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'description' => $request->description,
            'receipt_file' => $receiptPath,
            'status' => 'pending',
            'reference' => 'PAY' . time() . rand(1000, 9999)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quitus de paiement téléversé avec succès',
            'data' => $payment
        ]);
    }

    private function updatePaymentReceipt(Request $request, $student): JsonResponse
    {
        $payment = Payment::where('id', $request->payment_id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        if ($request->hasFile('receipt_file')) {
            // Supprimer l'ancien fichier
            if ($payment->receipt_file) {
                Storage::disk('private')->delete($payment->receipt_file);
            }

            $receiptPath = $request->file('receipt_file')->store('payment_receipts/' . $student->id, 'private');
            $payment->receipt_file = $receiptPath;
        }

        $payment->update($request->only(['amount', 'payment_method', 'description']));

        return response()->json([
            'success' => true,
            'message' => 'Quitus de paiement mis à jour avec succès',
            'data' => $payment
        ]);
    }

    private function downloadValidatedReceipt(Request $request, $student): JsonResponse
    {
        $payment = Payment::where('id', $request->payment_id)
            ->where('student_id', $student->id)
            ->where('status', 'validated')
            ->firstOrFail();

        if (!$payment->validated_receipt_path) {
            // Générer le quitus validé
            $validatedReceiptPath = $this->secureDocumentService->generateValidatedPaymentReceipt($payment);
            $payment->update(['validated_receipt_path' => $validatedReceiptPath]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quitus validé disponible',
            'data' => [
                'download_url' => Storage::url($payment->validated_receipt_path),
                'payment' => $payment
            ]
        ]);
    }
}