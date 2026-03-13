<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\Department;
use App\Models\Filiere;
use App\Models\Payment;
use App\Models\StudentDocument;
use App\Models\ExamCenter;
use App\Models\DocumentSubmissionCenter;
use App\Services\RealTimeService;
use App\Services\QRCodeService;
use App\Services\SecureDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class AdminSpaceController extends Controller
{
    protected $realTimeService;
    protected $qrCodeService;
    protected $secureDocumentService;

    public function __construct(
        RealTimeService $realTimeService,
        QRCodeService $qrCodeService,
        SecureDocumentService $secureDocumentService
    ) {
        $this->realTimeService = $realTimeService;
        $this->qrCodeService = $qrCodeService;
        $this->secureDocumentService = $secureDocumentService;
        
        // Middleware d'authentification admin
        $this->middleware('auth:sanctum');
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || Auth::user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé - Privilèges administrateur requis'
                ], 403);
            }
            return $next($request);
        });
    }

    /**
     * Dashboard administrateur avec données temps réel ORM Eloquent
     */
    public function dashboard(): JsonResponse
    {
        try {
            // Statistiques académiques temps réel avec relations ORM
            $academicStats = [
                'total_students' => Student::count(),
                'active_students' => Student::where('status', 'active')->count(),
                'enrolled_students' => Student::where('status', 'enrolled')->count(),
                'graduated_students' => Student::where('status', 'graduated')->count(),
                'suspended_students' => Student::where('status', 'suspended')->count(),
                
                'total_schools' => School::count(),
                'active_schools' => School::where('is_active', true)->count(),
                
                'total_departments' => Department::count(),
                'active_departments' => Department::where('is_active', true)->count(),
                
                'total_filieres' => Filiere::count(),
                'active_filieres' => Filiere::where('is_active', true)->count(),
                
                'total_exam_centers' => ExamCenter::count(),
                'active_exam_centers' => ExamCenter::where('is_active', true)->count(),
                
                'total_submission_centers' => DocumentSubmissionCenter::count(),
                'active_submission_centers' => DocumentSubmissionCenter::where('is_active', true)->count()
            ];

            // Statistiques financières avec agrégations ORM
            $financialStats = [
                'total_payments' => Payment::count(),
                'validated_payments' => Payment::where('status', 'validated')->count(),
                'pending_payments' => Payment::where('status', 'pending')->count(),
                'rejected_payments' => Payment::where('status', 'rejected')->count(),
                
                'total_revenue' => Payment::where('status', 'validated')->sum('amount'),
                'pending_revenue' => Payment::where('status', 'pending')->sum('amount'),
                'today_revenue' => Payment::where('status', 'validated')
                    ->whereDate('created_at', today())->sum('amount'),
                'this_month_revenue' => Payment::where('status', 'validated')
                    ->whereMonth('created_at', now()->month)->sum('amount'),
                
                'average_payment' => Payment::where('status', 'validated')->avg('amount'),
                'highest_payment' => Payment::where('status', 'validated')->max('amount'),
                'lowest_payment' => Payment::where('status', 'validated')->min('amount')
            ];

            // Statistiques par filière avec relations ORM Eloquent
            $filiereStats = Filiere::with(['students', 'department', 'school'])
                ->withCount(['students as total_students', 'students as active_students' => function($query) {
                    $query->where('status', 'active');
                }])
                ->get()
                ->map(function($filiere) {
                    return [
                        'id' => $filiere->id,
                        'name' => $filiere->name,
                        'code' => $filiere->code,
                        'department_name' => $filiere->department->name,
                        'school_name' => $filiere->school->name,
                        'school_sigle' => $filiere->school->sigle,
                        'total_students' => $filiere->total_students,
                        'active_students' => $filiere->active_students,
                        'tuition_fees' => $filiere->tuition_fees,
                        'registration_fees' => $filiere->registration_fees,
                        'duration_years' => $filiere->duration_years,
                        'capacity' => $filiere->capacity,
                        'enrollment_rate' => $filiere->capacity > 0 ? 
                            round(($filiere->total_students / $filiere->capacity) * 100, 2) : 0
                    ];
                });

            // Statistiques par département avec relations ORM
            $departmentStats = Department::with(['students', 'filieres', 'school'])
                ->withCount(['students', 'filieres'])
                ->get()
                ->map(function($department) {
                    return [
                        'id' => $department->id,
                        'name' => $department->name,
                        'code' => $department->code,
                        'school_name' => $department->school->name,
                        'school_sigle' => $department->school->sigle,
                        'total_students' => $department->students_count,
                        'total_filieres' => $department->filieres_count,
                        'head_of_department' => $department->head_of_department,
                        'contact_email' => $department->contact_email,
                        'contact_phone' => $department->contact_phone
                    ];
                });

            // Activités récentes avec relations ORM
            $recentActivities = $this->getRecentAdminActivities();

            // Notifications administrateur
            $adminNotifications = $this->getAdminNotifications();

            // Métriques de performance système
            $systemMetrics = $this->realTimeService->getSystemStatus();

            // Données temps réel synchronisées
            $realTimeData = $this->realTimeService->syncAllData();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard administrateur chargé avec données temps réel',
                'data' => [
                    'admin_user' => [
                        'id' => Auth::user()->id,
                        'name' => Auth::user()->name,
                        'email' => Auth::user()->email,
                        'role' => Auth::user()->role,
                        'last_login' => Auth::user()->last_login_at
                    ],
                    'academic_statistics' => $academicStats,
                    'financial_statistics' => $financialStats,
                    'filiere_statistics' => $filiereStats,
                    'department_statistics' => $departmentStats,
                    'recent_activities' => $recentActivities,
                    'notifications' => $adminNotifications,
                    'system_metrics' => $systemMetrics,
                    'realtime_data' => $realTimeData,
                    'timestamp' => now()->toISOString(),
                    'academic_year' => '2025-2026',
                    'semester' => 'Semestre 2'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard administrateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestion académique - CRUD Départements avec ORM Eloquent
     */
    public function manageDepartments(Request $request): JsonResponse
    {
        try {
            switch ($request->method()) {
                case 'GET':
                    return $this->getDepartments($request);
                case 'POST':
                    return $this->createDepartment($request);
                case 'PUT':
                case 'PATCH':
                    return $this->updateDepartment($request);
                case 'DELETE':
                    return $this->deleteDepartment($request);
                default:
                    return response()->json(['success' => false, 'message' => 'Méthode non supportée'], 405);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur gestion départements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestion académique - CRUD Filières avec ORM Eloquent
     */
    public function manageFilieres(Request $request): JsonResponse
    {
        try {
            switch ($request->method()) {
                case 'GET':
                    return $this->getFilieres($request);
                case 'POST':
                    return $this->createFiliere($request);
                case 'PUT':
                case 'PATCH':
                    return $this->updateFiliere($request);
                case 'DELETE':
                    return $this->deleteFiliere($request);
                default:
                    return response()->json(['success' => false, 'message' => 'Méthode non supportée'], 405);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur gestion filières',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestion des utilisateurs et rôles avec authentification JWT
     */
    public function manageUsers(Request $request): JsonResponse
    {
        try {
            $users = User::with(['student'])
                ->when($request->role, function($query, $role) {
                    return $query->where('role', $role);
                })
                ->when($request->status, function($query, $status) {
                    return $query->where('status', $status);
                })
                ->when($request->search, function($query, $search) {
                    return $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateurs récupérés avec succès',
                'data' => $users,
                'statistics' => [
                    'total_users' => User::count(),
                    'admin_users' => User::where('role', 'admin')->count(),
                    'student_users' => User::where('role', 'student')->count(),
                    'active_users' => User::where('status', 'active')->count(),
                    'inactive_users' => User::where('status', 'inactive')->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur gestion utilisateurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export de données académiques (PDF/CSV) avec ORM
     */
    public function exportData(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'required|in:students,payments,departments,filieres',
                'format' => 'required|in:pdf,csv,excel',
                'filters' => 'nullable|array'
            ]);

            $exportData = $this->prepareExportData($request->type, $request->filters ?? []);
            
            switch ($request->format) {
                case 'pdf':
                    $filePath = $this->generatePdfExport($request->type, $exportData);
                    break;
                case 'csv':
                    $filePath = $this->generateCsvExport($request->type, $exportData);
                    break;
                case 'excel':
                    $filePath = $this->generateExcelExport($request->type, $exportData);
                    break;
                default:
                    throw new \InvalidArgumentException('Format d\'export non supporté');
            }

            return response()->json([
                'success' => true,
                'message' => 'Export généré avec succès',
                'data' => [
                    'download_url' => Storage::url($filePath),
                    'file_path' => $filePath,
                    'file_size' => Storage::size($filePath),
                    'record_count' => count($exportData),
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques interactives temps réel avec ORM Eloquent
     */
    public function getInteractiveStatistics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year
            $type = $request->get('type', 'all'); // students, payments, academic

            $statistics = [];

            if ($type === 'all' || $type === 'students') {
                $statistics['students'] = $this->getStudentStatistics($period);
            }

            if ($type === 'all' || $type === 'payments') {
                $statistics['payments'] = $this->getPaymentStatistics($period);
            }

            if ($type === 'all' || $type === 'academic') {
                $statistics['academic'] = $this->getAcademicStatistics($period);
            }

            // Données pour graphiques interactifs
            $chartData = $this->generateChartData($period, $type);

            return response()->json([
                'success' => true,
                'message' => 'Statistiques interactives générées',
                'data' => [
                    'statistics' => $statistics,
                    'chart_data' => $chartData,
                    'period' => $period,
                    'type' => $type,
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur génération statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validation et gestion des paiements avec Mobile Money
     */
    public function managePayments(Request $request): JsonResponse
    {
        try {
            $action = $request->get('action', 'list');

            switch ($action) {
                case 'list':
                    return $this->listPayments($request);
                case 'validate':
                    return $this->validatePayment($request);
                case 'reject':
                    return $this->rejectPayment($request);
                case 'bulk_validate':
                    return $this->bulkValidatePayments($request);
                default:
                    throw new \InvalidArgumentException('Action non supportée');
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur gestion paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestion des centres d'examen et de dépôt avec délégations MINESUP
     */
    public function manageCenters(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'exam'); // exam, submission

            if ($type === 'exam') {
                $centers = ExamCenter::with(['region', 'exams'])
                    ->withCount('exams')
                    ->get()
                    ->map(function($center) {
                        return [
                            'id' => $center->id,
                            'name' => $center->name,
                            'code' => $center->code,
                            'region' => $center->region,
                            'city' => $center->city,
                            'address' => $center->address,
                            'capacity' => $center->capacity,
                            'contact_phone' => $center->contact_phone,
                            'contact_email' => $center->contact_email,
                            'is_active' => $center->is_active,
                            'total_exams' => $center->exams_count,
                            'facilities' => $center->facilities
                        ];
                    });
            } else {
                $centers = DocumentSubmissionCenter::get()
                    ->map(function($center) {
                        return [
                            'id' => $center->id,
                            'name' => $center->name,
                            'type' => $center->type,
                            'region' => $center->region,
                            'city' => $center->city,
                            'address' => $center->address,
                            'contact_phone' => $center->contact_phone,
                            'contact_email' => $center->contact_email,
                            'opening_hours' => $center->opening_hours,
                            'is_active' => $center->is_active,
                            'services' => $center->services
                        ];
                    });
            }

            return response()->json([
                'success' => true,
                'message' => 'Centres récupérés avec succès',
                'data' => [
                    'centers' => $centers,
                    'type' => $type,
                    'total_count' => $centers->count(),
                    'active_count' => $centers->where('is_active', true)->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur gestion centres',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Méthodes privées utilitaires
     */
    private function getRecentAdminActivities(): array
    {
        $activities = [];

        // Nouvelles inscriptions avec relations ORM
        $newStudents = Student::with(['user', 'school', 'department', 'filiere'])
            ->latest()
            ->limit(5)
            ->get();

        foreach ($newStudents as $student) {
            $activities[] = [
                'id' => 'student_' . $student->id,
                'type' => 'enrollment',
                'description' => "Nouvelle inscription: {$student->user->name} - {$student->school->sigle}",
                'details' => [
                    'student_name' => $student->user->name,
                    'school' => $student->school->name,
                    'department' => $student->department->name,
                    'filiere' => $student->filiere->name
                ],
                'timestamp' => $student->created_at->toISOString(),
                'icon' => '👨‍🎓',
                'priority' => 'normal'
            ];
        }

        // Paiements récents avec relations ORM
        $newPayments = Payment::with(['user', 'student'])
            ->latest()
            ->limit(5)
            ->get();

        foreach ($newPayments as $payment) {
            $activities[] = [
                'id' => 'payment_' . $payment->id,
                'type' => 'payment',
                'description' => "Paiement {$payment->status}: {$payment->amount} FCFA - {$payment->user->name}",
                'details' => [
                    'amount' => $payment->amount,
                    'method' => $payment->payment_method,
                    'status' => $payment->status,
                    'reference' => $payment->reference
                ],
                'timestamp' => $payment->created_at->toISOString(),
                'icon' => $payment->status === 'validated' ? '✅' : '⏳',
                'priority' => $payment->status === 'pending' ? 'high' : 'normal'
            ];
        }

        // Trier par timestamp décroissant
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, 10);
    }

    private function getAdminNotifications(): array
    {
        $notifications = [];

        // Paiements en attente de validation
        $pendingPayments = Payment::where('status', 'pending')->count();
        if ($pendingPayments > 0) {
            $notifications[] = [
                'id' => 'pending_payments',
                'type' => 'warning',
                'title' => 'Paiements en attente',
                'message' => "{$pendingPayments} paiement(s) nécessitent une validation administrative",
                'action_url' => '/admin/payments?status=pending',
                'priority' => 'high',
                'count' => $pendingPayments
            ];
        }

        // Documents en attente de vérification
        $pendingDocuments = StudentDocument::where('status', 'pending')->count();
        if ($pendingDocuments > 0) {
            $notifications[] = [
                'id' => 'pending_documents',
                'type' => 'info',
                'title' => 'Documents à vérifier',
                'message' => "{$pendingDocuments} document(s) en attente de vérification",
                'action_url' => '/admin/documents?status=pending',
                'priority' => 'normal',
                'count' => $pendingDocuments
            ];
        }

        // Nouvelles inscriptions aujourd'hui
        $todayEnrollments = Student::whereDate('created_at', today())->count();
        if ($todayEnrollments > 0) {
            $notifications[] = [
                'id' => 'today_enrollments',
                'type' => 'success',
                'title' => 'Nouvelles inscriptions',
                'message' => "{$todayEnrollments} nouvelle(s) inscription(s) aujourd'hui",
                'action_url' => '/admin/students?date=' . today()->format('Y-m-d'),
                'priority' => 'normal',
                'count' => $todayEnrollments
            ];
        }

        return $notifications;
    }

    // Méthodes CRUD pour départements
    private function getDepartments(Request $request): JsonResponse
    {
        $departments = Department::with(['school', 'filieres', 'students'])
            ->withCount(['filieres', 'students'])
            ->when($request->school_id, function($query, $schoolId) {
                return $query->where('school_id', $schoolId);
            })
            ->when($request->search, function($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Départements récupérés',
            'data' => $departments
        ]);
    }

    private function createDepartment(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:departments',
            'school_id' => 'required|exists:schools,id',
            'description' => 'nullable|string',
            'head_of_department' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string'
        ]);

        $department = Department::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Département créé avec succès',
            'data' => $department->load('school')
        ]);
    }

    private function updateDepartment(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:departments,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:10|unique:departments,code,' . $request->id,
            'school_id' => 'sometimes|exists:schools,id',
            'description' => 'nullable|string',
            'head_of_department' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string'
        ]);

        $department = Department::findOrFail($request->id);
        $department->update($request->except('id'));

        return response()->json([
            'success' => true,
            'message' => 'Département mis à jour avec succès',
            'data' => $department->load('school')
        ]);
    }

    private function deleteDepartment(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|exists:departments,id']);

        $department = Department::findOrFail($request->id);
        
        // Vérifier s'il y a des filières ou étudiants associés
        if ($department->filieres()->count() > 0 || $department->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer: département contient des filières ou étudiants'
            ], 400);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Département supprimé avec succès'
        ]);
    }

    // Méthodes CRUD pour filières (similaires aux départements)
    private function getFilieres(Request $request): JsonResponse
    {
        $filieres = Filiere::with(['department', 'school', 'students'])
            ->withCount('students')
            ->when($request->department_id, function($query, $deptId) {
                return $query->where('department_id', $deptId);
            })
            ->when($request->school_id, function($query, $schoolId) {
                return $query->where('school_id', $schoolId);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Filières récupérées',
            'data' => $filieres
        ]);
    }

    private function createFiliere(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:filieres',
            'department_id' => 'required|exists:departments,id',
            'school_id' => 'required|exists:schools,id',
            'description' => 'nullable|string',
            'duration_years' => 'required|integer|min:1|max:10',
            'tuition_fees' => 'required|numeric|min:0',
            'registration_fees' => 'required|numeric|min:0',
            'capacity' => 'nullable|integer|min:1'
        ]);

        $filiere = Filiere::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Filière créée avec succès',
            'data' => $filiere->load(['department', 'school'])
        ]);
    }

    private function updateFiliere(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:filieres,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:10|unique:filieres,code,' . $request->id,
            'department_id' => 'sometimes|exists:departments,id',
            'school_id' => 'sometimes|exists:schools,id',
            'description' => 'nullable|string',
            'duration_years' => 'sometimes|integer|min:1|max:10',
            'tuition_fees' => 'sometimes|numeric|min:0',
            'registration_fees' => 'sometimes|numeric|min:0',
            'capacity' => 'nullable|integer|min:1'
        ]);

        $filiere = Filiere::findOrFail($request->id);
        $filiere->update($request->except('id'));

        return response()->json([
            'success' => true,
            'message' => 'Filière mise à jour avec succès',
            'data' => $filiere->load(['department', 'school'])
        ]);
    }

    private function deleteFiliere(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|exists:filieres,id']);

        $filiere = Filiere::findOrFail($request->id);
        
        if ($filiere->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer: filière contient des étudiants'
            ], 400);
        }

        $filiere->delete();

        return response()->json([
            'success' => true,
            'message' => 'Filière supprimée avec succès'
        ]);
    }

    // Méthodes pour les exports et statistiques
    private function prepareExportData(string $type, array $filters): array
    {
        switch ($type) {
            case 'students':
                return Student::with(['user', 'school', 'department', 'filiere'])
                    ->when(isset($filters['school_id']), function($query) use ($filters) {
                        return $query->where('school_id', $filters['school_id']);
                    })
                    ->when(isset($filters['status']), function($query) use ($filters) {
                        return $query->where('status', $filters['status']);
                    })
                    ->get()
                    ->toArray();

            case 'payments':
                return Payment::with(['user', 'student'])
                    ->when(isset($filters['status']), function($query) use ($filters) {
                        return $query->where('status', $filters['status']);
                    })
                    ->when(isset($filters['date_from']), function($query) use ($filters) {
                        return $query->whereDate('created_at', '>=', $filters['date_from']);
                    })
                    ->when(isset($filters['date_to']), function($query) use ($filters) {
                        return $query->whereDate('created_at', '<=', $filters['date_to']);
                    })
                    ->get()
                    ->toArray();

            default:
                return [];
        }
    }

    private function generatePdfExport(string $type, array $data): string
    {
        $pdf = Pdf::loadView('exports.pdf.' . $type, ['data' => $data]);
        $fileName = $type . '_export_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filePath = 'exports/' . $fileName;
        
        Storage::put($filePath, $pdf->output());
        
        return $filePath;
    }

    private function generateCsvExport(string $type, array $data): string
    {
        $fileName = $type . '_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filePath = 'exports/' . $fileName;
        
        $csv = fopen('php://temp', 'w+');
        
        if (!empty($data)) {
            // Headers
            fputcsv($csv, array_keys($data[0]));
            
            // Data
            foreach ($data as $row) {
                fputcsv($csv, $row);
            }
        }
        
        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);
        
        Storage::put($filePath, $csvContent);
        
        return $filePath;
    }

    private function generateExcelExport(string $type, array $data): string
    {
        // Implémentation Excel avec Maatwebsite/Excel
        $fileName = $type . '_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $fileName;
        
        // Ici vous pouvez utiliser Excel::store() avec une classe Export personnalisée
        
        return $filePath;
    }

    private function getStudentStatistics(string $period): array
    {
        $query = Student::query();
        
        switch ($period) {
            case 'day':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        return [
            'total' => $query->count(),
            'active' => $query->where('status', 'active')->count(),
            'enrolled' => $query->where('status', 'enrolled')->count(),
            'by_school' => $query->with('school')
                ->get()
                ->groupBy('school.name')
                ->map->count()
                ->toArray()
        ];
    }

    private function getPaymentStatistics(string $period): array
    {
        $query = Payment::query();
        
        switch ($period) {
            case 'day':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'validated_count' => $query->where('status', 'validated')->count(),
            'validated_amount' => $query->where('status', 'validated')->sum('amount'),
            'pending_count' => $query->where('status', 'pending')->count(),
            'pending_amount' => $query->where('status', 'pending')->sum('amount'),
            'by_method' => $query->groupBy('payment_method')
                ->selectRaw('payment_method, count(*) as count, sum(amount) as total')
                ->get()
                ->keyBy('payment_method')
                ->toArray()
        ];
    }

    private function getAcademicStatistics(string $period): array
    {
        return [
            'departments' => Department::count(),
            'filieres' => Filiere::count(),
            'schools' => School::count(),
            'exam_centers' => ExamCenter::count(),
            'submission_centers' => DocumentSubmissionCenter::count()
        ];
    }

    private function generateChartData(string $period, string $type): array
    {
        // Génération de données pour graphiques (Chart.js, etc.)
        return [
            'labels' => $this->getChartLabels($period),
            'datasets' => $this->getChartDatasets($period, $type)
        ];
    }

    private function getChartLabels(string $period): array
    {
        switch ($period) {
            case 'day':
                return ['00h', '04h', '08h', '12h', '16h', '20h'];
            case 'week':
                return ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
            case 'month':
                return range(1, now()->daysInMonth);
            case 'year':
                return ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
            default:
                return [];
        }
    }

    private function getChartDatasets(string $period, string $type): array
    {
        // Implémentation des datasets pour graphiques
        return [
            [
                'label' => 'Inscriptions',
                'data' => [10, 15, 8, 22, 18, 25, 30],
                'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                'borderColor' => 'rgba(34, 197, 94, 1)'
            ],
            [
                'label' => 'Paiements',
                'data' => [8, 12, 6, 18, 15, 20, 25],
                'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                'borderColor' => 'rgba(59, 130, 246, 1)'
            ]
        ];
    }

    // Méthodes pour gestion des paiements
    private function listPayments(Request $request): JsonResponse
    {
        $payments = Payment::with(['user', 'student'])
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->method, function($query, $method) {
                return $query->where('payment_method', $method);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Paiements récupérés',
            'data' => $payments
        ]);
    }

    private function validatePayment(Request $request): JsonResponse
    {
        $request->validate(['payment_id' => 'required|exists:payments,id']);

        $payment = Payment::findOrFail($request->payment_id);
        $payment->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement validé avec succès',
            'data' => $payment
        ]);
    }

    private function rejectPayment(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'rejection_reason' => 'required|string'
        ]);

        $payment = Payment::findOrFail($request->payment_id);
        $payment->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'rejected_at' => now(),
            'rejected_by' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement rejeté',
            'data' => $payment
        ]);
    }

    private function bulkValidatePayments(Request $request): JsonResponse
    {
        $request->validate(['payment_ids' => 'required|array']);

        $validated = Payment::whereIn('id', $request->payment_ids)
            ->where('status', 'pending')
            ->update([
                'status' => 'validated',
                'validated_at' => now(),
                'validated_by' => Auth::id()
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$validated} paiement(s) validé(s) en lot",
            'data' => ['validated_count' => $validated]
        ]);
    }
}