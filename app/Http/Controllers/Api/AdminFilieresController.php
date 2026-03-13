<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Filiere;
use App\Models\Department;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminFilieresController extends Controller
{
    /**
     * Obtenir toutes les filières avec statistiques
     */
    public function index(Request $request)
    {
        try {
            $query = Filiere::with(['department.school', 'students'])
                ->withCount(['students']);

            // Filtres
            if ($request->has('school_id')) {
                $query->where('school_id', $request->school_id);
            }

            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('code', 'like', "%$search%")
                      ->orWhereHas('department', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%$search%");
                      });
                });
            }

            $filieres = $query->get()->map(function($filiere) {
                $paymentStats = $filiere->payment_stats;
                
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
                    'enrolled_students' => $filiere->enrolled_students_count,
                    'fill_rate' => $filiere->fill_rate,
                    'available_spots' => $filiere->available_spots,
                    'status' => $filiere->status,
                    'requirements' => $filiere->requirements,
                    'payment_stats' => $paymentStats,
                    'can_accept_students' => $filiere->canAcceptNewStudents(),
                    'created_at' => $filiere->created_at->format('d/m/Y H:i'),
                    'updated_at' => $filiere->updated_at->format('d/m/Y H:i')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $filieres,
                'total' => $filieres->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération filières admin', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des filières'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Créer une nouvelle filière
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:20|unique:filieres',
                'description' => 'nullable|string',
                'department_id' => 'required|exists:departments,id',
                'duration_years' => 'required|integer|min:1|max:10',
                'enrollment_fee' => 'required|numeric|min:0',
                'tuition_fee' => 'required|numeric|min:0',
                'capacity' => 'required|integer|min:1',
                'requirements' => 'nullable|array'
            ]);

            $department = Department::findOrFail($request->department_id);

            $filiere = Filiere::create([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'department_id' => $request->department_id,
                'school_id' => $department->school_id,
                'duration_years' => $request->duration_years,
                'enrollment_fee' => $request->enrollment_fee,
                'tuition_fee' => $request->tuition_fee,
                'capacity' => $request->capacity,
                'requirements' => $request->requirements,
                'status' => 'active',
                'created_by' => auth('sanctum')->id()
            ]);

            $filiere->load(['department.school']);

            Log::info('Filière créée', [
                'filiere_id' => $filiere->id,
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Filière créée avec succès',
                'data' => $filiere
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
     * Afficher une filière spécifique
     */
    public function show($id)
    {
        try {
            $filiere = Filiere::with(['department.school', 'students.payments'])
                ->withCount(['students'])
                ->findOrFail($id);

            $paymentStats = $filiere->payment_stats;
            
            $data = [
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
                'enrolled_students' => $filiere->enrolled_students_count,
                'fill_rate' => $filiere->fill_rate,
                'available_spots' => $filiere->available_spots,
                'status' => $filiere->status,
                'requirements' => $filiere->requirements,
                'payment_stats' => $paymentStats,
                'can_accept_students' => $filiere->canAcceptNewStudents(),
                'recent_students' => $filiere->students()
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function($student) {
                        return [
                            'id' => $student->id,
                            'student_number' => $student->student_number,
                            'full_name' => $student->full_name,
                            'email' => $student->email,
                            'enrollment_date' => $student->enrollment_date->format('d/m/Y'),
                            'status' => $student->status,
                            'payment_status' => $student->payment_status
                        ];
                    }),
                'created_at' => $filiere->created_at->format('d/m/Y H:i'),
                'updated_at' => $filiere->updated_at->format('d/m/Y H:i')
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération filière', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Filière non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Mettre à jour une filière
     */
    public function update(Request $request, $id)
    {
        try {
            $filiere = Filiere::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:20|unique:filieres,code,' . $id,
                'description' => 'nullable|string',
                'duration_years' => 'required|integer|min:1|max:10',
                'enrollment_fee' => 'required|numeric|min:0',
                'tuition_fee' => 'required|numeric|min:0',
                'capacity' => 'required|integer|min:1',
                'requirements' => 'nullable|array',
                'status' => 'required|in:active,inactive'
            ]);

            $filiere->update($request->all());
            $filiere->load(['department.school']);

            Log::info('Filière mise à jour', [
                'filiere_id' => $id,
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Filière mise à jour avec succès',
                'data' => $filiere
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour filière', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la filière'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprimer une filière
     */
    public function destroy($id)
    {
        try {
            $filiere = Filiere::findOrFail($id);

            // Vérifier s'il y a des étudiants inscrits
            if ($filiere->students()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette filière car elle contient des étudiants inscrits'
                ], Response::HTTP_BAD_REQUEST);
            }

            $filiere->delete();

            Log::info('Filière supprimée', [
                'filiere_id' => $id,
                'admin_id' => auth('sanctum')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Filière supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression filière', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la filière'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtenir les étudiants d'une filière
     */
    public function getStudents($id, Request $request)
    {
        try {
            $filiere = Filiere::findOrFail($id);

            $query = $filiere->students()->with(['payments']);

            // Filtres
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_status')) {
                $paymentStatus = $request->payment_status;
                $query->whereHas('payments', function($q) use ($paymentStatus) {
                    if ($paymentStatus === 'paid') {
                        $q->where('status', 'validated');
                    } elseif ($paymentStatus === 'unpaid') {
                        $q->where('status', 'pending')->orWhereDoesntExist();
                    }
                });
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
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'date_of_birth' => $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : null,
                    'gender' => $student->gender,
                    'nationality' => $student->nationality,
                    'enrollment_date' => $student->enrollment_date->format('d/m/Y'),
                    'academic_year' => $student->academic_year,
                    'status' => $student->status,
                    'payment_status' => $student->payment_status,
                    'paid_amount' => $student->paid_amount,
                    'remaining_amount' => $student->remaining_amount,
                    'profile_completion' => $student->profile_completion,
                    'created_at' => $student->created_at->format('d/m/Y H:i')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $students
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération étudiants filière', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des étudiants'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Exporter la liste des étudiants en PDF
     */
    public function exportStudentsPdf($id)
    {
        try {
            $filiere = Filiere::with(['department.school', 'students.payments'])
                ->findOrFail($id);

            $students = $filiere->students()->orderBy('last_name')->get();

            $data = [
                'filiere' => $filiere,
                'school' => $filiere->department->school,
                'department' => $filiere->department,
                'students' => $students,
                'generated_at' => now()->format('d/m/Y H:i'),
                'total_students' => $students->count(),
                'stats' => [
                    'enrolled' => $students->where('status', 'enrolled')->count(),
                    'paid' => $students->filter(function($s) { return $s->payment_status === 'paid'; })->count(),
                    'partial' => $students->filter(function($s) { return $s->payment_status === 'partial'; })->count(),
                    'unpaid' => $students->filter(function($s) { return $s->payment_status === 'unpaid'; })->count()
                ]
            ];

            $pdf = Pdf::loadView('pdf.filiere-students-list', $data);
            $pdf->setPaper('A4', 'portrait');

            $fileName = 'Liste_Etudiants_' . $filiere->code . '_' . date('Y-m-d') . '.pdf';

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error('Erreur export PDF étudiants', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export PDF'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Exporter la liste des étudiants en CSV
     */
    public function exportStudentsCsv($id)
    {
        try {
            $filiere = Filiere::with(['department.school', 'students.payments'])
                ->findOrFail($id);

            $students = $filiere->students()->orderBy('last_name')->get();

            $fileName = 'Liste_Etudiants_' . $filiere->code . '_' . date('Y-m-d') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ];

            $callback = function() use ($students) {
                $file = fopen('php://output', 'w');
                
                // En-têtes CSV
                fputcsv($file, [
                    'Numéro étudiant',
                    'Nom',
                    'Prénom',
                    'Email',
                    'Téléphone',
                    'Date de naissance',
                    'Sexe',
                    'Nationalité',
                    'Date d\'inscription',
                    'Année académique',
                    'Statut',
                    'Statut paiement',
                    'Montant payé',
                    'Montant restant'
                ], ';');

                // Données
                foreach ($students as $student) {
                    fputcsv($file, [
                        $student->student_number,
                        $student->last_name,
                        $student->first_name,
                        $student->email,
                        $student->phone,
                        $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '',
                        $student->gender === 'M' ? 'Masculin' : ($student->gender === 'F' ? 'Féminin' : ''),
                        $student->nationality,
                        $student->enrollment_date->format('d/m/Y'),
                        $student->academic_year,
                        $student->status,
                        $student->payment_status,
                        number_format($student->paid_amount, 0, ',', ' '),
                        number_format($student->remaining_amount, 0, ',', ' ')
                    ], ';');
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Erreur export CSV étudiants', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export CSV'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Statistiques détaillées d'une filière
     */
    public function getStatistics($id)
    {
        try {
            $filiere = Filiere::with(['students.payments'])->findOrFail($id);

            $students = $filiere->students;
            $currentYear = date('Y');

            $stats = [
                'general' => [
                    'total_students' => $students->count(),
                    'capacity' => $filiere->capacity,
                    'fill_rate' => $filiere->fill_rate,
                    'available_spots' => $filiere->available_spots
                ],
                'enrollment_by_status' => [
                    'enrolled' => $students->where('status', 'enrolled')->count(),
                    'suspended' => $students->where('status', 'suspended')->count(),
                    'graduated' => $students->where('status', 'graduated')->count(),
                    'dropped' => $students->where('status', 'dropped')->count()
                ],
                'payment_stats' => $filiere->payment_stats,
                'gender_distribution' => [
                    'male' => $students->where('gender', 'M')->count(),
                    'female' => $students->where('gender', 'F')->count(),
                    'not_specified' => $students->whereNull('gender')->count()
                ],
                'age_distribution' => [
                    'under_20' => $students->filter(function($s) { return $s->age && $s->age < 20; })->count(),
                    '20_25' => $students->filter(function($s) { return $s->age && $s->age >= 20 && $s->age <= 25; })->count(),
                    '26_30' => $students->filter(function($s) { return $s->age && $s->age >= 26 && $s->age <= 30; })->count(),
                    'over_30' => $students->filter(function($s) { return $s->age && $s->age > 30; })->count()
                ],
                'enrollment_by_month' => $students->groupBy(function($student) {
                    return $student->enrollment_date->format('Y-m');
                })->map(function($group) {
                    return $group->count();
                })->sortKeys(),
                'nationality_distribution' => $students->groupBy('nationality')
                    ->map(function($group) {
                        return $group->count();
                    })
                    ->sortDesc()
                    ->take(10)
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur statistiques filière', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}