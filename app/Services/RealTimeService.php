<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\Exam;
use App\Models\Payment;
use App\Models\ExamCenter;
use App\Models\DocumentSubmissionCenter;

class RealTimeService
{
    /**
     * Synchroniser toutes les données en temps réel
     */
    public function syncAllData()
    {
        try {
            $data = [
                'timestamp' => now()->toISOString(),
                'status' => 'success',
                'data' => [
                    'statistics' => $this->getRealtimeStatistics(),
                    'recent_activities' => $this->getRecentActivities(),
                    'system_status' => $this->getSystemStatus(),
                    'notifications' => $this->getActiveNotifications()
                ]
            ];

            // Mettre en cache pour accès rapide
            Cache::put('realtime_data', $data, 60); // Cache 1 minute

            return $data;
        } catch (\Exception $e) {
            Log::error('Erreur synchronisation temps réel: ' . $e->getMessage());
            return [
                'timestamp' => now()->toISOString(),
                'status' => 'error',
                'message' => 'Erreur de synchronisation',
                'data' => $this->getFallbackData()
            ];
        }
    }

    /**
     * Obtenir les statistiques en temps réel
     */
    public function getRealtimeStatistics()
    {
        return [
            'general' => [
                'total_students' => Student::count(),
                'active_students' => Student::where('status', 'active')->count(),
                'total_schools' => School::count(),
                'total_exams' => Exam::count(),
                'active_exams' => Exam::where('status', 'active')->count(),
                'total_payments' => Payment::count(),
                'validated_payments' => Payment::where('status', 'validated')->count(),
                'pending_payments' => Payment::where('status', 'pending')->count(),
                'total_exam_centers' => ExamCenter::count(),
                'total_submission_centers' => DocumentSubmissionCenter::count(),
                'total_users' => User::count(),
                'online_users' => User::where('last_login_at', '>=', now()->subMinutes(15))->count()
            ],
            'today' => [
                'new_registrations' => Student::whereDate('created_at', today())->count(),
                'new_payments' => Payment::whereDate('created_at', today())->count(),
                'new_users' => User::whereDate('created_at', today())->count(),
                'login_count' => User::whereDate('last_login_at', today())->count()
            ],
            'this_week' => [
                'registrations' => Student::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'payments' => Payment::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count()
            ],
            'financial' => [
                'total_revenue' => Payment::where('status', 'validated')->sum('amount'),
                'pending_revenue' => Payment::where('status', 'pending')->sum('amount'),
                'today_revenue' => Payment::where('status', 'validated')->whereDate('created_at', today())->sum('amount')
            ]
        ];
    }

    /**
     * Obtenir les activités récentes
     */
    public function getRecentActivities()
    {
        $activities = [];

        // Nouvelles inscriptions
        $newStudents = Student::with('user')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($newStudents as $student) {
            $activities[] = [
                'id' => 'student_' . $student->id,
                'type' => 'registration',
                'description' => "Nouvelle inscription: {$student->user->name}",
                'user' => 'Système',
                'timestamp' => $student->created_at->toISOString(),
                'icon' => '👨‍🎓',
                'priority' => 'normal'
            ];
        }

        // Nouveaux paiements
        $newPayments = Payment::with('user')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($newPayments as $payment) {
            $activities[] = [
                'id' => 'payment_' . $payment->id,
                'type' => 'payment',
                'description' => "Paiement {$payment->status}: {$payment->amount} FCFA",
                'user' => $payment->user->name ?? 'Utilisateur',
                'timestamp' => $payment->created_at->toISOString(),
                'icon' => $payment->status === 'validated' ? '✅' : '⏳',
                'priority' => $payment->status === 'pending' ? 'high' : 'normal'
            ];
        }

        // Connexions récentes
        $recentLogins = User::whereNotNull('last_login_at')
            ->orderBy('last_login_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentLogins as $user) {
            $activities[] = [
                'id' => 'login_' . $user->id,
                'type' => 'login',
                'description' => "Connexion: {$user->name}",
                'user' => $user->name,
                'timestamp' => $user->last_login_at->toISOString(),
                'icon' => '🔐',
                'priority' => 'low'
            ];
        }

        // Trier par timestamp décroissant
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Obtenir le statut du système
     */
    public function getSystemStatus()
    {
        return [
            'database' => [
                'status' => $this->checkDatabaseConnection(),
                'last_check' => now()->toISOString()
            ],
            'cache' => [
                'status' => $this->checkCacheConnection(),
                'last_check' => now()->toISOString()
            ],
            'storage' => [
                'status' => $this->checkStorageAccess(),
                'last_check' => now()->toISOString()
            ],
            'email' => [
                'status' => 'active', // Simplifié pour cette démo
                'last_check' => now()->toISOString()
            ]
        ];
    }

    /**
     * Obtenir les notifications actives
     */
    public function getActiveNotifications()
    {
        $notifications = [];

        // Paiements en attente
        $pendingPayments = Payment::where('status', 'pending')->count();
        if ($pendingPayments > 0) {
            $notifications[] = [
                'id' => 'pending_payments',
                'type' => 'warning',
                'title' => 'Paiements en attente',
                'message' => "{$pendingPayments} paiement(s) nécessitent une validation",
                'action_url' => '/admin/payments',
                'priority' => 'high'
            ];
        }

        // Nouvelles inscriptions
        $todayRegistrations = Student::whereDate('created_at', today())->count();
        if ($todayRegistrations > 0) {
            $notifications[] = [
                'id' => 'new_registrations',
                'type' => 'info',
                'title' => 'Nouvelles inscriptions',
                'message' => "{$todayRegistrations} nouvelle(s) inscription(s) aujourd'hui",
                'action_url' => '/admin/students',
                'priority' => 'normal'
            ];
        }

        // Examens à venir
        $upcomingExams = Exam::where('exam_date', '>=', now())
            ->where('exam_date', '<=', now()->addDays(7))
            ->count();

        if ($upcomingExams > 0) {
            $notifications[] = [
                'id' => 'upcoming_exams',
                'type' => 'info',
                'title' => 'Examens à venir',
                'message' => "{$upcomingExams} examen(s) dans les 7 prochains jours",
                'action_url' => '/admin/exams',
                'priority' => 'normal'
            ];
        }

        return $notifications;
    }

    /**
     * Vérifier la connexion à la base de données
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return 'active';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Vérifier la connexion au cache
     */
    private function checkCacheConnection()
    {
        try {
            Cache::put('health_check', 'ok', 10);
            $result = Cache::get('health_check');
            return $result === 'ok' ? 'active' : 'error';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Vérifier l'accès au stockage
     */
    private function checkStorageAccess()
    {
        try {
            $testFile = storage_path('app/health_check.txt');
            file_put_contents($testFile, 'ok');
            $content = file_get_contents($testFile);
            unlink($testFile);
            return $content === 'ok' ? 'active' : 'error';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Données de secours en cas d'erreur
     */
    private function getFallbackData()
    {
        return [
            'general' => [
                'total_students' => 0,
                'active_students' => 0,
                'total_schools' => 0,
                'total_exams' => 0,
                'active_exams' => 0,
                'total_payments' => 0,
                'validated_payments' => 0,
                'pending_payments' => 0
            ],
            'recent_activities' => [],
            'system_status' => [
                'database' => ['status' => 'error'],
                'cache' => ['status' => 'error'],
                'storage' => ['status' => 'error'],
                'email' => ['status' => 'error']
            ],
            'notifications' => [
                [
                    'id' => 'system_error',
                    'type' => 'error',
                    'title' => 'Erreur système',
                    'message' => 'Impossible de charger les données en temps réel',
                    'priority' => 'high'
                ]
            ]
        ];
    }

    /**
     * Forcer la synchronisation des données
     */
    public function forceSyncDatabase()
    {
        try {
            // Vider le cache
            Cache::flush();
            
            // Reconnecter à la base de données
            DB::reconnect();
            
            // Vérifier la connexion
            DB::connection()->getPdo();
            
            // Synchroniser les données
            $data = $this->syncAllData();
            
            Log::info('Synchronisation forcée réussie');
            
            return [
                'success' => true,
                'message' => 'Synchronisation réussie',
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur synchronisation forcée: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur de synchronisation: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Obtenir les données mises en cache
     */
    public function getCachedData()
    {
        return Cache::get('realtime_data', $this->getFallbackData());
    }

    /**
     * Vérifier la santé du système
     */
    public function healthCheck()
    {
        $checks = [
            'database' => $this->checkDatabaseConnection() === 'active',
            'cache' => $this->checkCacheConnection() === 'active',
            'storage' => $this->checkStorageAccess() === 'active'
        ];

        $allHealthy = array_reduce($checks, function($carry, $check) {
            return $carry && $check;
        }, true);

        return [
            'healthy' => $allHealthy,
            'checks' => $checks,
            'timestamp' => now()->toISOString()
        ];
    }
}