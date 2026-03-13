<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Exam;
use App\Models\CandidateExam;
use App\Models\Payment;
use App\Models\GeneratedDocument;
use App\Models\School;
use App\Models\ExamFiliere;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Statistiques générales du système (Admin Dashboard)
     */
    public function dashboard()
    {
        try {
            // Statistiques de base avec des valeurs par défaut si les tables sont vides
            $stats = [
                'total_candidates' => User::whereHas('roles', function($query) {
                    $query->where('name', 'candidat');
                })->count(),
                'active_exams' => Exam::where('status', 'published')->count(),
                'total_schools' => School::count(),
                'total_filieres' => ExamFiliere::count(),
                'total_registrations' => CandidateExam::count(),
                'pending_registrations' => CandidateExam::where('statut', 'en_attente')->count(),
                'confirmed_registrations' => CandidateExam::where('statut', 'confirme')->count(),
                'total_payments' => Payment::sum('montant') ?? 0,
                'validated_payments' => Payment::where('statut_paiement', 'valide')->count(),
                'pending_payments' => Payment::where('statut_paiement', 'en_attente')->count(),
                'generated_documents' => GeneratedDocument::count(),
                
                // Statistiques par école
                'schools_stats' => $this->getSchoolsStats(),
                
                // Examens récents
                'recent_exams' => $this->getRecentExams(),
                
                // Inscriptions récentes
                'recent_registrations' => $this->getRecentRegistrations(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Statistiques pour les candidats
     */
    public function candidateStats()
    {
        try {
            $user = auth('sanctum')->user();
            
            // Statistiques simplifiées pour les candidats
            $stats = [
                'total_registrations' => CandidateExam::where('user_id', $user->id)->count(),
                'confirmed_registrations' => CandidateExam::where('user_id', $user->id)
                    ->where('statut', 'confirme')->count(),
                'pending_registrations' => CandidateExam::where('user_id', $user->id)
                    ->where('statut', 'en_attente')->count(),
                'available_exams' => Exam::where('status', 'published')
                    ->where('registration_end_date', '>', now())->count(),
                'total_payments' => Payment::whereHas('candidateExam', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->sum('montant') ?? 0,
                'validated_payments' => Payment::whereHas('candidateExam', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('statut_paiement', 'valide')->count(),
                'my_documents' => GeneratedDocument::whereHas('candidateExam', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->count(),
                
                // Mes inscriptions récentes
                'my_registrations' => $this->getCandidateRegistrations($user->id),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Statistiques des examens (Admin)
     */
    public function examStats()
    {
        try {
            $stats = [
                'total_exams' => Exam::count(),
                'published_exams' => Exam::where('status', 'published')->count(),
                'draft_exams' => Exam::where('status', 'draft')->count(),
                'upcoming_exams' => Exam::where('exam_date', '>', now())->count(),
                'past_exams' => Exam::where('exam_date', '<', now())->count(),
                
                // Examens par école
                'exams_by_school' => $this->getExamsBySchool(),
                
                // Examens populaires
                'popular_exams' => $this->getPopularExams(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getSchoolsStats()
    {
        return School::select('schools.name', 'schools.city')
            ->withCount(['exams as total_exams'])
            ->limit(5)
            ->get();
    }

    private function getRecentExams()
    {
        return Exam::with('school:id,name')
            ->select('id', 'title', 'school_id', 'status', 'created_at')
            ->latest()
            ->limit(5)
            ->get();
    }

    private function getRecentRegistrations()
    {
        return CandidateExam::with(['exam:id,title', 'user:id,name'])
            ->select('id', 'exam_id', 'user_id', 'statut', 'created_at')
            ->latest()
            ->limit(5)
            ->get();
    }

    private function getCandidateRegistrations($userId)
    {
        return CandidateExam::with(['exam:id,title,school_id', 'exam.school:id,name'])
            ->where('user_id', $userId)
            ->select('id', 'exam_id', 'statut', 'created_at')
            ->latest()
            ->limit(5)
            ->get();
    }

    private function getExamsBySchool()
    {
        return School::select('schools.name')
            ->withCount(['exams as total_exams'])
            ->having('total_exams', '>', 0)
            ->orderBy('total_exams', 'desc')
            ->limit(5)
            ->get();
    }

    private function getPopularExams()
    {
        return Exam::select('exams.title', 'exams.id')
            ->withCount(['candidateExams as registrations_count'])
            ->orderBy('registrations_count', 'desc')
            ->limit(5)
            ->get();
    }
}