<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\School;
use App\Models\Exam;
use App\Models\ExamCenter;
use App\Models\ExamFiliere;
use App\Models\CandidateExam;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Filiere;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    /**
     * Tableau de bord principal administrateur avec données dynamiques
     */
    public function dashboard()
    {
        try {
            $currentYear = date('Y') . '-' . (date('Y') + 1);
            
            $stats = [
                // Statistiques générales dynamiques
                'general' => [
                    'total_students' => Student::count(),
                    'active_students' => Student::where('status', 'enrolled')->count(),
                    'total_schools' => School::count(),
                    'total_departments' => Department::count(),
                    'total_filieres' => Filiere::count(),
                    'total_exam_centers' => ExamCenter::count(),
                    'active_exams' => Exam::where('status', 'published')->count(),
                    'total_registrations' => CandidateExam::count(),
                    'confirmed_registrations' => CandidateExam::where('statut', 'confirme')->count(),
                    'total_payments' => Payment::sum('amount'),
                    'validated_payments' => Payment::where('status', 'validated')->sum('amount'),
                    'pending_payments' => Payment::where('status', 'pending')->count()
                ],

                // Statistiques par école avec relations ORM
                'schools_stats' => School::withCount([
                    'students',
                    'departments',
                    'filieres',
                    'exams',
                    'exams as active_exams_count' => function($q) {
                        $q->where('status', 'published');
                    }
                ])->with(['students.payments' => function($q) {
                    $q->where('status', 'validated');
                }])->get()->map(function($school) {
                    $totalRevenue = $school->students->sum(function($student) {
                        return $student->payments->sum('amount');
                    });
                    
                    $registrations = CandidateExam::whereHas('exam', function($q) use ($school) {
                        $q->where('school_id', $school->id);
                    })->count();
                    
                    $confirmed = CandidateExam::whereHas('exam', function($q) use ($school) {
                        $q->where('school_id', $school->id);
                    })->where('statut', 'confirme')->count();

                    return [
                        'id' => $school->id,
                        'name' => $school->name,
                        'sigle' => $school->sigle,
                        'students_count' => $school->students_count,
                        'departments_count' => $school->departments_count,
                        'filieres_count' => $school->filieres_count,
                        'total_exams' => $school->exams_count,
                        'active_exams' => $school->active_exams_count,
                        'total_registrations' => $registrations,
                        'confirmed_registrations' => $confirmed,
                        'confirmation_rate' => $registrations > 0 ? round(($confirmed / $registrations) * 100, 2) : 0,
                        'total_revenue' => $totalRevenue,
                        'average_revenue_per_student' => $school->students_count > 0 ? round($totalRevenue / $school->students_count, 2) : 0
                    ];
                }),

                // Statistiques par département avec ORM
                'departments_stats' => Department::withCount(['students', 'filieres'])
                    ->with(['school', 'students.payments' => function($q) {
                        $q->where('status', 'validated');
                    }])
                    ->get()
                    ->map(function($department) {
                        $paymentStats = $department->payment_stats;
                        
                        return [
                            'id' => $department->id,
                            'name' => $department->name,
                            'code' => $department->code,
                            'school_name' => $department->school->name,
                            'students_count' => $department->students_count,
                            'filieres_count' => $department->filieres_count,
                            'payment_stats' => $paymentStats
                        ];
                    }),

                // Statistiques par filière avec ORM
                'filieres_stats' => Filiere::withCount(['students'])
                    ->with(['department.school', 'students.payments' => function($q) {
                        $q->where('status', 'validated');
                    }])
                    ->get()
                    ->map(function($filiere) {
                        $paymentStats = $filiere->payment_stats;
                        
                        return [
                            'id' => $filiere->id,
                            'name' => $filiere->name,
                            'code' => $filiere->code,
                            'department_name' => $filiere->department->name,
                            'school_name' => $filiere->department->school->name,
                            'capacity' => $filiere->capacity,
                            'enrolled_students' => $filiere->enrolled_students_count,
                            'fill_rate' => $filiere->fill_rate,
                            'available_spots' => $filiere->available_spots,
                            'enrollment_fee' => $filiere->enrollment_fee,
                            'tuition_fee' => $filiere->tuition_fee,
                            'payment_stats' => $paymentStats
                        ];
                    }),

                // Statistiques par centre d'examen avec ORM
                'centers_stats' => ExamCenter::withCount('exams')->get()->map(function($center) {
                    $registrations = CandidateExam::where('exam_center_id', $center->id)->count();
                    
                    return [
                        'id' => $center->id,
                        'name' => $center->name,
                        'sigle' => $center->sigle,
                        'city' => $center->city,
                        'region' => $center->region,
                        'capacity' => $center->capacity,
                        'assigned_exams' => $center->exams_count,
                        'registrations' => $registrations,
                        'utilization_rate' => $center->capacity > 0 ? round(($registrations / $center->capacity) * 100, 2) : 0,
                        'available_capacity' => $center->available_capacity
                    ];
                }),

                // Évolution des inscriptions par mois avec ORM
                'enrollments_by_month' => Student::selectRaw('
                    YEAR(enrollment_date) as year,
                    MONTH(enrollment_date) as month,
                    COUNT(*) as total_students,
                    COUNT(CASE WHEN status = "enrolled" THEN 1 END) as active_students
                ')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),

                // Statistiques des paiements par mois avec ORM
                'payments_by_month' => Payment::selectRaw('
                    YEAR(payment_date) as year,
                    MONTH(payment_date) as month,
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = "validated" THEN amount ELSE 0 END) as validated_amount,
                    SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as pending_amount,
                    COUNT(CASE WHEN status = "validated" THEN 1 END) as validated_count,
                    COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count
                ')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),

                // Top filières par inscriptions avec ORM
                'top_filieres' => Filiere::withCount('students')
                ->with(['department.school'])
                ->orderBy('students_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function($filiere) {
                    return [
                        'id' => $filiere->id,
                        'name' => $filiere->name,
                        'code' => $filiere->code,
                        'department' => $filiere->department->name,
                        'school' => $filiere->department->school->name,
                        'students_count' => $filiere->students_count,
                        'capacity' => $filiere->capacity,
                        'fill_rate' => $filiere->fill_rate,
                        'enrollment_fee' => $filiere->enrollment_fee,
                        'tuition_fee' => $filiere->tuition_fee
                    ];
                }),

                // Répartition par statut de paiement avec ORM
                'payment_status_distribution' => [
                    'paid_students' => Student::whereHas('payments', function($q) {
                        $q->where('status', 'validated');
                    })->count(),
                    'partial_payment_students' => Student::whereHas('payments', function($q) {
                        $q->where('status', 'validated');
                    })->whereHas('payments', function($q) {
                        $q->where('status', 'pending');
                    })->count(),
                    'unpaid_students' => Student::whereDoesntHave('payments', function($q) {
                        $q->where('status', 'validated');
                    })->count()
                ],

                // Statistiques par région avec ORM
                'regional_stats' => ExamCenter::selectRaw('
                    region,
                    COUNT(*) as centers_count,
                    SUM(capacity) as total_capacity
                ')
                ->groupBy('region')
                ->get()
                ->map(function($region) {
                    $registrations = CandidateExam::whereHas('examCenter', function($q) use ($region) {
                        $q->where('region', $region->region);
                    })->count();
                    
                    return [
                        'region' => $region->region,
                        'centers_count' => $region->centers_count,
                        'total_capacity' => $region->total_capacity,
                        'registrations' => $registrations,
                        'utilization_rate' => $region->total_capacity > 0 ? 
                            round(($registrations / $region->total_capacity) * 100, 2) : 0
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'generated_at' => now()->toISOString(),
                'academic_year' => $currentYear
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dashboard admin dynamique', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du tableau de bord dynamique'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gestion des départements
     */
    public function getDepartments(Request $request)
    {
        try {
            $query = Department::with(['school', 'filieres'])
                ->withCount(['students', 'filieres']);

            // Filtres
            if ($request->has('school_id')) {
                $query->where('school_id', $request->school_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $departments = $query->get()->map(function($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'description' => $department->description,
                    'school' => [
                        'id' => $department->school->id,
                        'name' => $department->school->name,
                        'sigle' => $department->school->sigle
                    ],
                    'head_of_department' => $department->head_of_department,
                    'contact_email' => $department->contact_email,
                    'contact_phone' => $department->contact_phone,
                    'status' => $department->status,
                    'students_count' => $department->students_count,
                    'filieres_count' => $department->filieres_count,
                    'payment_stats' => $department->payment_stats,
                    'created_at' => $department->created_at->format('d/m/Y H:i')
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $departments
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération départements', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des départements'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Créer un nouveau département
     */
    public function createDepartment(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:20|unique:departments',
                'description' => 'nullable|string',
                'school_id' => 'required|exists:schools,id',
                'head_of_department' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:20'
            ]);

            $department = Department::create([
                ...$request->all(),
                'created_by' => auth('sanctum')->id(),
                'status' => 'active'
            ]);

            $department->load(['school']);

            Log::info('Département créé', [
                'department_id' => $department->id,
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Département créé avec succès',
                'data' => $department
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Erreur création département', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du département'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mettre à jour un département
     */
    public function updateDepartment(Request $request, $departmentId)
    {
        try {
            $department = Department::findOrFail($departmentId);

            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:20|unique:departments,code,' . $departmentId,
                'description' => 'nullable|string',
                'head_of_department' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:20',
                'status' => 'required|in:active,inactive'
            ]);

            $department->update($request->all());
            $department->load(['school']);

            Log::info('Département mis à jour', [
                'department_id' => $departmentId,
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Département mis à jour avec succès',
                'data' => $department
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour département', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du département'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprimer un département
     */
    public function deleteDepartment($departmentId)
    {
        try {
            $department = Department::findOrFail($departmentId);

            // Vérifier s'il y a des étudiants ou filières liés
            if ($department->students()->count() > 0 || $department->filieres()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce département car il contient des étudiants ou des filières'
                ], Response::HTTP_BAD_REQUEST);
            }

            $department->delete();

            Log::info('Département supprimé', [
                'department_id' => $departmentId,
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Département supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression département', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du département'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    {
        try {
            $schools = School::withCount(['exams', 'candidates'])->get();
            
            return response()->json([
                'success' => true,
                'data' => $schools
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des écoles'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Créer une nouvelle école
     */
    public function createSchool(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'sigle' => 'required|string|max:10|unique:schools',
                'address' => 'required|string',
                'city' => 'required|string|max:100',
                'region' => 'required|string|max:100',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'website' => 'nullable|url|max:255',
                'description' => 'nullable|string'
            ]);

            $school = School::create($request->all());

            Log::info('École créée', [
                'school_id' => $school->id,
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'École créée avec succès',
                'data' => $school
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Erreur création école', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'école'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gestion des centres d'examens
     */
    public function getExamCenters()
    {
        try {
            $centers = ExamCenter::withCount('exams')->get();
            
            return response()->json([
                'success' => true,
                'data' => $centers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des centres d\'examens'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Créer un nouveau centre d'examen
     */
    public function createExamCenter(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'sigle' => 'required|string|max:10|unique:exam_centers',
                'address' => 'required|string',
                'city' => 'required|string|max:100',
                'region' => 'required|string|max:100',
                'capacity' => 'required|integer|min:1',
                'facilities' => 'nullable|array',
                'contact_phone' => 'nullable|string|max:20',
                'contact_email' => 'nullable|email|max:255',
                'coordinates' => 'nullable|array',
                'description' => 'nullable|string'
            ]);

            $center = ExamCenter::create($request->all());

            Log::info('Centre d\'examen créé', [
                'center_id' => $center->id,
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Centre d\'examen créé avec succès',
                'data' => $center
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Erreur création centre', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du centre d\'examen'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gestion des filières
     */
    public function getFilieres()
    {
        try {
            $filieres = ExamFiliere::with(['exam.school'])
                ->withCount('candidateExams')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $filieres
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des filières'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Créer une nouvelle filière
     */
    public function createFiliere(Request $request)
    {
        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'filiere_name' => 'required|string|max:255',
                'filiere_code' => 'required|string|max:20',
                'quota' => 'required|integer|min:1',
                'description' => 'nullable|string',
                'requirements' => 'nullable|string'
            ]);

            $filiere = ExamFiliere::create($request->all());

            Log::info('Filière créée', [
                'filiere_id' => $filiere->id,
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Filière créée avec succès',
                'data' => $filiere->load('exam.school')
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Erreur création filière', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la filière'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assigner des centres d'examens à un examen
     */
    public function assignExamCenters(Request $request, $examId)
    {
        try {
            $request->validate([
                'centers' => 'required|array',
                'centers.*.center_id' => 'required|exists:exam_centers,id',
                'centers.*.capacity_allocated' => 'required|integer|min:1'
            ]);

            $exam = Exam::findOrFail($examId);

            DB::beginTransaction();

            // Supprimer les anciennes assignations
            $exam->examCenters()->detach();

            // Créer les nouvelles assignations
            foreach ($request->centers as $centerData) {
                $exam->examCenters()->attach($centerData['center_id'], [
                    'capacity_allocated' => $centerData['capacity_allocated'],
                    'status' => 'assigned'
                ]);
            }

            DB::commit();

            Log::info('Centres assignés à l\'examen', [
                'exam_id' => $examId,
                'centers_count' => count($request->centers),
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Centres d\'examens assignés avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur assignation centres', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation des centres'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir les candidats avec filtres
     */
    public function getCandidates(Request $request)
    {
        try {
            $query = Candidate::with(['user', 'candidateExams.exam.school']);

            // Filtres
            if ($request->has('status')) {
                $query->where('statut', $request->status);
            }

            if ($request->has('school_id')) {
                $query->whereHas('candidateExams.exam', function($q) use ($request) {
                    $q->where('school_id', $request->school_id);
                });
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('prenom', 'like', "%$search%")
                      ->orWhere('nom', 'like', "%$search%")
                      ->orWhereHas('user', function($subQ) use ($search) {
                          $subQ->where('email', 'like', "%$search%");
                      });
                });
            }

            $candidates = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $candidates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des candidats'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Convertir IUC en centre d'examen
     */
    public function convertIucToExamCenter()
    {
        try {
            DB::beginTransaction();

            // Trouver l'IUC dans les écoles
            $iuc = School::where('sigle', 'IUC')->first();
            
            if (!$iuc) {
                return response()->json([
                    'success' => false,
                    'message' => 'École IUC non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            // Créer le centre d'examen IUC
            $examCenter = ExamCenter::create([
                'name' => 'Institut Universitaire de la Côte - Centre d\'Examens',
                'sigle' => 'IUC-CE',
                'address' => $iuc->address ?? 'Douala, Cameroun',
                'city' => 'Douala',
                'region' => 'Littoral',
                'capacity' => 500,
                'facilities' => [
                    'Salles climatisées',
                    'Équipements informatiques',
                    'Système de surveillance',
                    'Parking',
                    'Restauration'
                ],
                'contact_phone' => $iuc->phone,
                'contact_email' => $iuc->email,
                'status' => 'active',
                'description' => 'Centre d\'examens de l\'Institut Universitaire de la Côte, équipé pour accueillir les concours d\'entrée dans les grandes écoles.'
            ]);

            // Supprimer l'IUC des écoles
            $iuc->delete();

            DB::commit();

            Log::info('IUC converti en centre d\'examen', [
                'center_id' => $examCenter->id,
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IUC converti en centre d\'examen avec succès',
                'data' => $examCenter
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur conversion IUC', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la conversion de l\'IUC'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}