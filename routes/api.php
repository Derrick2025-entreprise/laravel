<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SimpleExamController;
use App\Http\Controllers\Api\RegistrationCardController;
use App\Http\Controllers\Api\EnrollmentWorkflowController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AdminFilieresController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Routes d'authentification
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

// Routes de vérification QR Code (publiques)
Route::prefix('verify-qr')->group(function () {
    Route::get('/{uniqueId}', [App\Http\Controllers\Api\QRVerificationController::class, 'verify']);
    Route::post('/check', [App\Http\Controllers\Api\QRVerificationController::class, 'verifyPost']);
    Route::get('/page/verification', [App\Http\Controllers\Api\QRVerificationController::class, 'verificationPage']);
});

// Routes d'enrôlement et paiement sécurisés
Route::middleware(['auth:sanctum', 'secure'])->prefix('secure')->group(function () {
    Route::post('/enroll', [App\Http\Controllers\Api\SecureEnrollmentController::class, 'secureEnroll']);
    Route::post('/payment', [App\Http\Controllers\Api\SecureEnrollmentController::class, 'securePayment']);
    Route::get('/security-status', [App\Http\Controllers\Api\SecureEnrollmentController::class, 'getSecurityStatus']);
    Route::get('/download/{documentType}/{documentId}', [App\Http\Controllers\Api\SecureEnrollmentController::class, 'downloadSecureDocument']);
});

// Routes administrateur pour QR Codes
Route::middleware(['auth:sanctum', 'secure'])->prefix('admin/qr')->group(function () {
    Route::get('/stats', [App\Http\Controllers\Api\QRVerificationController::class, 'getVerificationStats']);
    Route::post('/{uniqueId}/invalidate', [App\Http\Controllers\Api\QRVerificationController::class, 'invalidateQRCode']);
});

// Routes publiques
Route::get('/schools', [SimpleExamController::class, 'getSchools']);
Route::get('/schools/{schoolId}/exams', [SimpleExamController::class, 'getExamsBySchool']);
Route::get('/exams/{examId}/filieres', [SimpleExamController::class, 'getExamFilieres']);
// Routes pour les centres
Route::get('/exam-centers', [SimpleExamController::class, 'getExamCenters']);
Route::get('/submission-centers', [SimpleExamController::class, 'getSubmissionCenters']);
Route::get('/centers/by-region/{region}', [SimpleExamController::class, 'getCentersByRegion']);

// Routes protégées pour les candidats avec sécurité renforcée
Route::middleware(['auth:sanctum', 'secure'])->group(function () {
    // Inscription aux examens avec validation stricte
    Route::post('/enroll', [EnrollmentWorkflowController::class, 'enroll']);
    Route::get('/my-registrations', [EnrollmentWorkflowController::class, 'getMyRegistrations']);
    Route::get('/registration/{registrationId}', [EnrollmentWorkflowController::class, 'getRegistrationDetails']);
    
    // Génération de cartes d'inscription sécurisées
    Route::post('/generate-registration-card', [RegistrationCardController::class, 'generateCard']);
    Route::get('/download-registration-card/{candidateExamId}', [RegistrationCardController::class, 'downloadCard']);
});

// Routes pour les paiements avec sécurité renforcée
Route::middleware(['auth:sanctum', 'secure'])->prefix('payments')->group(function () {
    Route::get('/methods', [App\Http\Controllers\Api\PaymentController::class, 'getPaymentMethods']);
    Route::post('/orange-money', [App\Http\Controllers\Api\PaymentController::class, 'initiateOrangeMoneyPayment']);
    Route::post('/mtn-money', [App\Http\Controllers\Api\PaymentController::class, 'initiateMtnMoneyPayment']);
    Route::post('/bank-transfer', [App\Http\Controllers\Api\PaymentController::class, 'initiateBankPayment']);
    Route::get('/{paymentId}/status', [App\Http\Controllers\Api\PaymentController::class, 'checkPaymentStatus']);
});

// Callbacks pour les paiements (sans authentification)
Route::prefix('payments')->group(function () {
    Route::post('/orange/callback', [App\Http\Controllers\Api\PaymentController::class, 'orangeMoneyCallback']);
    Route::post('/orange/cancel', function() {
        return response()->json(['message' => 'Paiement annulé']);
    });
    Route::post('/orange/notify', [App\Http\Controllers\Api\PaymentController::class, 'orangeMoneyCallback']);
    Route::post('/mtn/callback', [App\Http\Controllers\Api\PaymentController::class, 'mtnMoneyCallback']);
});

// Routes pour l'espace étudiant avec sécurité renforcée
Route::middleware(['auth:sanctum', 'secure'])->prefix('student')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard']);
    Route::post('/enroll', [StudentController::class, 'enroll']);
    Route::post('/upload-document', [StudentController::class, 'uploadDocument']);
    Route::post('/submit-payment', [StudentController::class, 'submitPayment']);
    Route::get('/payments', [StudentController::class, 'getPayments']);
    Route::get('/documents', [StudentController::class, 'getDocuments']);
    Route::get('/download-enrollment-card', [StudentController::class, 'downloadEnrollmentCard']);
    Route::get('/available-filieres', [StudentController::class, 'getAvailableFilieres']);
    
    // Routes pour télécharger les fichiers
    Route::get('/payment/receipt/{paymentId}', function($paymentId) {
        $payment = \App\Models\Payment::where('id', $paymentId)
            ->where('user_id', auth('sanctum')->id())
            ->firstOrFail();
        
        if (!$payment->receipt_file || !\Illuminate\Support\Facades\Storage::exists($payment->receipt_file)) {
            abort(404);
        }
        
        return \Illuminate\Support\Facades\Storage::download($payment->receipt_file);
    })->name('student.payment.receipt');
    
    Route::get('/document/download/{documentId}', function($documentId) {
        $document = \App\Models\StudentDocument::whereHas('student', function($q) {
            $q->where('user_id', auth('sanctum')->id());
        })->findOrFail($documentId);
        
        if (!$document->file_path || !\Illuminate\Support\Facades\Storage::exists($document->file_path)) {
            abort(404);
        }
        
        return \Illuminate\Support\Facades\Storage::download($document->file_path, $document->document_name);
    })->name('student.document.download');
});

// Routes administrateur avec sécurité maximale
Route::middleware(['auth:sanctum', 'secure', 'role:admin'])->prefix('admin')->group(function () {
    // Tableau de bord administrateur
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard']);
    
    // Gestion des départements
    Route::get('/departments', [AdminDashboardController::class, 'getDepartments']);
    Route::post('/departments', [AdminDashboardController::class, 'createDepartment']);
    Route::put('/departments/{departmentId}', [AdminDashboardController::class, 'updateDepartment']);
    Route::delete('/departments/{departmentId}', [AdminDashboardController::class, 'deleteDepartment']);
    
    // Gestion des écoles
    Route::get('/schools', [AdminDashboardController::class, 'getSchools']);
    Route::post('/schools', [AdminDashboardController::class, 'createSchool']);
    
    // Gestion des centres d'examens
    Route::get('/exam-centers', [AdminDashboardController::class, 'getExamCenters']);
    Route::post('/exam-centers', [AdminDashboardController::class, 'createExamCenter']);
    
    // Gestion des filières (examens)
    Route::get('/filieres', [AdminDashboardController::class, 'getFilieres']);
    Route::post('/filieres', [AdminDashboardController::class, 'createFiliere']);
    
    // Assignation des centres aux examens
    Route::post('/exams/{examId}/assign-centers', [AdminDashboardController::class, 'assignExamCenters']);
    
    // Gestion des candidats
    Route::get('/candidates', [AdminDashboardController::class, 'getCandidates']);
    
    // Conversion IUC en centre d'examen
    Route::post('/convert-iuc-to-center', [AdminDashboardController::class, 'convertIucToExamCenter']);
    
    // Gestion avancée des filières académiques
    Route::prefix('academic-filieres')->group(function () {
        Route::get('/', [AdminFilieresController::class, 'index']);
        Route::post('/', [AdminFilieresController::class, 'store']);
        Route::get('/{id}', [AdminFilieresController::class, 'show']);
        Route::put('/{id}', [AdminFilieresController::class, 'update']);
        Route::delete('/{id}', [AdminFilieresController::class, 'destroy']);
        Route::get('/{id}/students', [AdminFilieresController::class, 'getStudents']);
        Route::get('/{id}/statistics', [AdminFilieresController::class, 'getStatistics']);
        Route::get('/{id}/export/pdf', [AdminFilieresController::class, 'exportStudentsPdf']);
        Route::get('/{id}/export/csv', [AdminFilieresController::class, 'exportStudentsCsv']);
    });
    
    // Gestion des paiements
    Route::prefix('payments')->group(function () {
        Route::get('/', function(Request $request) {
            $query = \App\Models\Payment::with(['student', 'user', 'validator']);
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('student', function($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                      ->orWhere('last_name', 'like', "%$search%")
                      ->orWhere('student_number', 'like', "%$search%");
                });
            }
            
            $payments = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));
            
            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        });
        
        Route::put('/{paymentId}/validate', function($paymentId, Request $request) {
            $payment = \App\Models\Payment::findOrFail($paymentId);
            $payment->validate(auth('sanctum')->id(), $request->notes);
            
            // Envoyer l'email de confirmation
            try {
                \Illuminate\Support\Facades\Mail::to($payment->student->email)
                    ->send(new \App\Mail\PaymentReceiptMail($payment));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Erreur envoi email paiement validé', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Paiement validé avec succès'
            ]);
        });
        
        Route::put('/{paymentId}/reject', function($paymentId, Request $request) {
            $request->validate(['notes' => 'required|string']);
            
            $payment = \App\Models\Payment::findOrFail($paymentId);
            $payment->reject(auth('sanctum')->id(), $request->notes);
            
            return response()->json([
                'success' => true,
                'message' => 'Paiement rejeté'
            ]);
        });
    });
    
    // Gestion des documents étudiants
    Route::prefix('documents')->group(function () {
        Route::get('/', function(Request $request) {
            $query = \App\Models\StudentDocument::with(['student']);
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('document_type')) {
                $query->where('document_type', $request->document_type);
            }
            
            $documents = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));
            
            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        });
        
        Route::put('/{documentId}/verify', function($documentId, Request $request) {
            $document = \App\Models\StudentDocument::findOrFail($documentId);
            $document->verify(auth('sanctum')->id(), $request->notes);
            
            return response()->json([
                'success' => true,
                'message' => 'Document vérifié avec succès'
            ]);
        });
        
        Route::put('/{documentId}/reject', function($documentId, Request $request) {
            $request->validate(['notes' => 'required|string']);
            
            $document = \App\Models\StudentDocument::findOrFail($documentId);
            $document->reject(auth('sanctum')->id(), $request->notes);
            
            return response()->json([
                'success' => true,
                'message' => 'Document rejeté'
            ]);
        });
        
        Route::get('/{documentId}/download', function($documentId) {
            $document = \App\Models\StudentDocument::findOrFail($documentId);
            
            if (!$document->file_path || !\Illuminate\Support\Facades\Storage::exists($document->file_path)) {
                abort(404);
            }
            
            return \Illuminate\Support\Facades\Storage::download($document->file_path, $document->document_name);
        });
    });
    
    // Gestion des étudiants
    Route::prefix('students')->group(function () {
        Route::get('/', function(Request $request) {
            $query = \App\Models\Student::with(['school', 'department', 'filiere', 'payments']);
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('school_id')) {
                $query->where('school_id', $request->school_id);
            }
            
            if ($request->has('filiere_id')) {
                $query->where('filiere_id', $request->filiere_id);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                      ->orWhere('last_name', 'like', "%$search%")
                      ->orWhere('student_number', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
                });
            }
            
            $students = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));
            
            $students->getCollection()->transform(function($student) {
                return [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'full_name' => $student->full_name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'school' => $student->school->name,
                    'department' => $student->department->name,
                    'filiere' => $student->filiere->name,
                    'enrollment_date' => $student->enrollment_date->format('d/m/Y'),
                    'status' => $student->status,
                    'payment_status' => $student->payment_status,
                    'paid_amount' => $student->paid_amount,
                    'remaining_amount' => $student->remaining_amount,
                    'profile_completion' => $student->profile_completion
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $students
            ]);
        });
        
        Route::get('/{studentId}', function($studentId) {
            $student = \App\Models\Student::with([
                'school', 'department', 'filiere', 'payments', 'studentDocuments'
            ])->findOrFail($studentId);
            
            return response()->json([
                'success' => true,
                'data' => $student
            ]);
        });
        
        Route::put('/{studentId}/status', function($studentId, Request $request) {
            $request->validate([
                'status' => 'required|in:enrolled,suspended,graduated,dropped'
            ]);
            
            $student = \App\Models\Student::findOrFail($studentId);
            $student->update(['status' => $request->status]);
            
            return response()->json([
                'success' => true,
                'message' => 'Statut étudiant mis à jour'
            ]);
        });
    });
    
    // Export des données
    Route::prefix('exports')->group(function () {
        Route::get('/students/pdf', function(Request $request) {
            $students = \App\Models\Student::with(['school', 'department', 'filiere'])
                ->when($request->school_id, function($q, $schoolId) {
                    return $q->where('school_id', $schoolId);
                })
                ->when($request->filiere_id, function($q, $filiereId) {
                    return $q->where('filiere_id', $filiereId);
                })
                ->orderBy('last_name')
                ->get();
            
            $data = [
                'students' => $students,
                'generated_at' => now()->format('d/m/Y H:i'),
                'filters' => $request->only(['school_id', 'filiere_id'])
            ];
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.students-list', $data);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf->download('Liste_Etudiants_' . date('Y-m-d') . '.pdf');
        });
        
        Route::get('/payments/pdf', function(Request $request) {
            $payments = \App\Models\Payment::with(['student.school', 'student.filiere'])
                ->when($request->status, function($q, $status) {
                    return $q->where('status', $status);
                })
                ->orderBy('created_at', 'desc')
                ->get();
            
            $data = [
                'payments' => $payments,
                'generated_at' => now()->format('d/m/Y H:i'),
                'total_amount' => $payments->where('status', 'validated')->sum('amount')
            ];
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.payments-list', $data);
            $pdf->setPaper('A4', 'landscape');
            
            return $pdf->download('Liste_Paiements_' . date('Y-m-d') . '.pdf');
        });
    });
});

// Routes de statistiques publiques (pour les graphiques)
Route::get('/stats/public', function() {
    return response()->json([
        'success' => true,
        'data' => [
            'total_schools' => \App\Models\School::count(),
            'total_students' => \App\Models\Student::count(),
            'total_filieres' => \App\Models\Filiere::count(),
            'total_exam_centers' => \App\Models\ExamCenter::count(),
            'active_exams' => \App\Models\Exam::where('status', 'published')->count()
        ]
    ]);
});

// Routes API temps réel pour communication avec base de données XAMPP
Route::prefix('realtime')->group(function () {
    // Routes publiques pour vérification de connectivité
    Route::get('/ping', [App\Http\Controllers\Api\RealTimeController::class, 'ping']);
    Route::get('/health', [App\Http\Controllers\Api\RealTimeController::class, 'healthCheck']);
    Route::get('/metrics', [App\Http\Controllers\Api\RealTimeController::class, 'getMetrics']);
    
    // Routes pour données en temps réel (authentification requise)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/data', [App\Http\Controllers\Api\RealTimeController::class, 'getAllData']);
        Route::get('/statistics', [App\Http\Controllers\Api\RealTimeController::class, 'getStatistics']);
        Route::get('/activities', [App\Http\Controllers\Api\RealTimeController::class, 'getRecentActivities']);
        Route::get('/notifications', [App\Http\Controllers\Api\RealTimeController::class, 'getNotifications']);
        Route::get('/system-status', [App\Http\Controllers\Api\RealTimeController::class, 'getSystemStatus']);
        Route::get('/cached', [App\Http\Controllers\Api\RealTimeController::class, 'getCachedData']);
        
        // Routes administrateur pour synchronisation forcée
        Route::middleware(['role:admin'])->group(function () {
            Route::post('/sync', [App\Http\Controllers\Api\RealTimeController::class, 'forceSync']);
        });
    });
});