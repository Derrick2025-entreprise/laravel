<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Payment;
use App\Models\StudentDocument;
use App\Models\Filiere;
use App\Models\Department;
use App\Models\School;
use App\Mail\StudentEnrollmentConfirmation;
use App\Mail\PaymentReceiptMail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentController extends Controller
{
    /**
     * Espace étudiant - Tableau de bord
     */
    public function dashboard()
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $student = Student::where('user_id', $user->id)->with([
                'school', 'department', 'filiere', 'payments', 'studentDocuments'
            ])->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil étudiant non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $dashboardData = [
                'student' => [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'full_name' => $student->full_name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'profile_photo' => $student->profile_photo,
                    'enrollment_date' => $student->enrollment_date->format('d/m/Y'),
                    'academic_year' => $student->academic_year,
                    'status' => $student->status,
                    'profile_completion' => $student->profile_completion
                ],
                'academic_info' => [
                    'school' => [
                        'id' => $student->school->id,
                        'name' => $student->school->name,
                        'sigle' => $student->school->sigle
                    ],
                    'department' => [
                        'id' => $student->department->id,
                        'name' => $student->department->name,
                        'code' => $student->department->code
                    ],
                    'filiere' => [
                        'id' => $student->filiere->id,
                        'name' => $student->filiere->name,
                        'code' => $student->filiere->code,
                        'duration_years' => $student->filiere->duration_years,
                        'enrollment_fee' => $student->filiere->enrollment_fee,
                        'tuition_fee' => $student->filiere->tuition_fee
                    ]
                ],
                'payment_info' => [
                    'total_required' => $student->filiere->enrollment_fee + $student->filiere->tuition_fee,
                    'paid_amount' => $student->paid_amount,
                    'remaining_amount' => $student->remaining_amount,
                    'payment_status' => $student->payment_status,
                    'payments_count' => $student->payments->count(),
                    'validated_payments' => $student->payments->where('status', 'validated')->count(),
                    'pending_payments' => $student->payments->where('status', 'pending')->count()
                ],
                'documents_info' => [
                    'total_documents' => $student->studentDocuments->count(),
                    'verified_documents' => $student->studentDocuments->where('status', 'verified')->count(),
                    'pending_documents' => $student->studentDocuments->where('status', 'pending')->count(),
                    'has_required_documents' => $student->hasRequiredDocuments()
                ],
                'recent_activities' => [
                    'recent_payments' => $student->payments()->orderBy('created_at', 'desc')->limit(3)->get(),
                    'recent_documents' => $student->studentDocuments()->orderBy('created_at', 'desc')->limit(3)->get()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dashboard étudiant', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du tableau de bord'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Inscription d'un nouvel étudiant
     */
    public function enroll(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:students,email',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'required|date|before:today',
                'place_of_birth' => 'required|string|max:255',
                'gender' => 'required|in:M,F',
                'nationality' => 'required|string|max:100',
                'address' => 'required|string',
                'emergency_contact_name' => 'required|string|max:255',
                'emergency_contact_phone' => 'required|string|max:20',
                'filiere_id' => 'required|exists:filieres,id',
                'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            $user = Auth::guard('sanctum')->user();
            $filiere = Filiere::with(['department', 'school'])->findOrFail($request->filiere_id);

            // Vérifier la capacité
            if (!$filiere->canAcceptNewStudents()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette filière a atteint sa capacité maximale ou n\'est plus active'
                ], Response::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            // Gérer la photo de profil
            $profilePhotoPath = null;
            if ($request->hasFile('profile_photo')) {
                $profilePhotoPath = $request->file('profile_photo')->store('students/photos', 'public');
            }

            // Générer le numéro d'étudiant
            $studentNumber = Student::generateStudentNumber($filiere->school_id);

            // Créer l'étudiant
            $student = Student::create([
                'user_id' => $user->id,
                'student_number' => $studentNumber,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'place_of_birth' => $request->place_of_birth,
                'gender' => $request->gender,
                'nationality' => $request->nationality,
                'address' => $request->address,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'filiere_id' => $filiere->id,
                'department_id' => $filiere->department_id,
                'school_id' => $filiere->school_id,
                'enrollment_date' => now(),
                'academic_year' => date('Y') . '-' . (date('Y') + 1),
                'profile_photo' => $profilePhotoPath,
                'status' => 'enrolled'
            ]);

            // Générer la fiche d'inscription PDF
            $enrollmentCardPath = $this->generateEnrollmentCard($student);

            DB::commit();

            // Envoyer l'email de confirmation
            try {
                Mail::to($student->email)->send(new StudentEnrollmentConfirmation($student, $enrollmentCardPath));
            } catch (\Exception $e) {
                Log::warning('Erreur envoi email inscription', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Étudiant inscrit avec succès', [
                'student_id' => $student->id,
                'student_number' => $studentNumber,
                'filiere_id' => $filiere->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription réalisée avec succès',
                'data' => [
                    'student' => $student->load(['school', 'department', 'filiere']),
                    'enrollment_card_url' => $enrollmentCardPath ? asset('storage/' . $enrollmentCardPath) : null
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur inscription étudiant', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload de documents
     */
    public function uploadDocument(Request $request)
    {
        try {
            $request->validate([
                'document_type' => 'required|in:birth_certificate,photo,academic_transcript,id_card,medical_certificate',
                'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
                'document_name' => 'nullable|string|max:255'
            ]);

            $user = Auth::guard('sanctum')->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            // Vérifier si le document existe déjà
            $existingDoc = StudentDocument::where('student_id', $student->id)
                ->where('document_type', $request->document_type)
                ->first();

            if ($existingDoc) {
                // Supprimer l'ancien fichier
                if ($existingDoc->file_path && Storage::exists($existingDoc->file_path)) {
                    Storage::delete($existingDoc->file_path);
                }
                $existingDoc->delete();
            }

            // Stocker le nouveau fichier
            $file = $request->file('document_file');
            $fileName = $student->student_number . '_' . $request->document_type . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('students/documents', $fileName, 'private');

            // Créer l'enregistrement du document
            $document = StudentDocument::create([
                'student_id' => $student->id,
                'document_type' => $request->document_type,
                'document_name' => $request->document_name ?? $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'pending',
                'uploaded_at' => now()
            ]);

            Log::info('Document uploadé', [
                'student_id' => $student->id,
                'document_id' => $document->id,
                'document_type' => $request->document_type
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'data' => $document
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Erreur upload document', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du document'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gestion des paiements
     */
    public function submitPayment(Request $request)
    {
        try {
            $request->validate([
                'payment_type' => 'required|in:enrollment_fee,tuition_fee,other',
                'amount' => 'required|numeric|min:1',
                'payment_method' => 'required|in:bank_transfer,mobile_money,cash,check',
                'receipt_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'notes' => 'nullable|string|max:500'
            ]);

            $user = Auth::guard('sanctum')->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            // Stocker le reçu
            $receiptFile = $request->file('receipt_file');
            $receiptFileName = $student->student_number . '_payment_' . time() . '.' . $receiptFile->getClientOriginalExtension();
            $receiptPath = $receiptFile->storeAs('students/payments', $receiptFileName, 'private');

            // Créer le paiement
            $payment = Payment::create([
                'student_id' => $student->id,
                'user_id' => $user->id,
                'payment_type' => $request->payment_type,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_number' => Payment::generateReferenceNumber(),
                'receipt_file' => $receiptPath,
                'status' => 'pending',
                'payment_date' => now(),
                'notes' => $request->notes,
                'academic_year' => $student->academic_year
            ]);

            Log::info('Paiement soumis', [
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'amount' => $request->amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement soumis avec succès. Il sera validé sous peu.',
                'data' => $payment
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Erreur soumission paiement', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la soumission du paiement'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir les paiements de l'étudiant
     */
    public function getPayments()
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            $payments = Payment::where('student_id', $student->id)
                ->with('validator')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'reference_number' => $payment->reference_number,
                        'payment_type' => $payment->payment_type,
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'status' => $payment->status,
                        'payment_date' => $payment->payment_date->format('d/m/Y H:i'),
                        'validated_at' => $payment->validated_at ? $payment->validated_at->format('d/m/Y H:i') : null,
                        'validator_name' => $payment->validator ? $payment->validator->name : null,
                        'notes' => $payment->notes,
                        'receipt_url' => $payment->receipt_file ? route('student.payment.receipt', $payment->id) : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération paiements', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des paiements'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir les documents de l'étudiant
     */
    public function getDocuments()
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            $documents = StudentDocument::where('student_id', $student->id)
                ->with('verifier')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($document) {
                    return [
                        'id' => $document->id,
                        'document_type' => $document->document_type,
                        'document_name' => $document->document_name,
                        'status' => $document->status,
                        'file_size' => $document->formatted_size,
                        'uploaded_at' => $document->uploaded_at->format('d/m/Y H:i'),
                        'verified_at' => $document->verified_at ? $document->verified_at->format('d/m/Y H:i') : null,
                        'verifier_name' => $document->verifier ? $document->verifier->name : null,
                        'notes' => $document->notes,
                        'download_url' => route('student.document.download', $document->id)
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération documents', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des documents'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Générer la fiche d'inscription PDF
     */
    private function generateEnrollmentCard(Student $student)
    {
        try {
            $data = [
                'student' => $student,
                'school' => $student->school,
                'department' => $student->department,
                'filiere' => $student->filiere,
                'qr_code' => base64_encode('SGEE-' . $student->student_number . '-' . $student->school->sigle),
                'generated_at' => now()->format('d/m/Y H:i')
            ];

            $pdf = Pdf::loadView('pdf.enrollment-card', $data);
            $pdf->setPaper('A4', 'portrait');

            $fileName = 'enrollment_card_' . $student->student_number . '.pdf';
            $filePath = 'students/enrollment_cards/' . $fileName;

            Storage::put($filePath, $pdf->output());

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Erreur génération fiche inscription', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Télécharger la fiche d'inscription
     */
    public function downloadEnrollmentCard()
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            $filePath = $this->generateEnrollmentCard($student);
            
            if (!$filePath || !Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fiche d\'inscription non disponible'
                ], Response::HTTP_NOT_FOUND);
            }

            return Storage::download($filePath, 'Fiche_Inscription_' . $student->student_number . '.pdf');

        } catch (\Exception $e) {
            Log::error('Erreur téléchargement fiche', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir les filières disponibles
     */
    public function getAvailableFilieres()
    {
        try {
            $filieres = Filiere::active()
                ->with(['department.school'])
                ->get()
                ->filter(function($filiere) {
                    return $filiere->canAcceptNewStudents();
                })
                ->map(function($filiere) {
                    return [
                        'id' => $filiere->id,
                        'name' => $filiere->name,
                        'code' => $filiere->code,
                        'description' => $filiere->description,
                        'department' => [
                            'id' => $filiere->department->id,
                            'name' => $filiere->department->name,
                            'code' => $filiere->department->code
                        ],
                        'school' => [
                            'id' => $filiere->department->school->id,
                            'name' => $filiere->department->school->name,
                            'sigle' => $filiere->department->school->sigle
                        ],
                        'duration_years' => $filiere->duration_years,
                        'enrollment_fee' => $filiere->enrollment_fee,
                        'tuition_fee' => $filiere->tuition_fee,
                        'total_fee' => $filiere->enrollment_fee + $filiere->tuition_fee,
                        'capacity' => $filiere->capacity,
                        'available_spots' => $filiere->available_spots,
                        'fill_rate' => $filiere->fill_rate,
                        'requirements' => $filiere->requirements
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $filieres
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération filières', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des filières'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}