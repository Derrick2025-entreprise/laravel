<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RealTimeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RealTimeController extends Controller
{
    protected $realTimeService;

    public function __construct(RealTimeService $realTimeService)
    {
        $this->realTimeService = $realTimeService;
    }

    /**
     * Obtenir toutes les données en temps réel
     */
    public function getAllData(): JsonResponse
    {
        try {
            $data = $this->realTimeService->syncAllData();
            
            return response()->json([
                'success' => true,
                'message' => 'Données temps réel récupérées',
                'data' => $data,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur API temps réel: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir uniquement les statistiques
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->realTimeService->getRealtimeStatistics();
            
            return response()->json([
                'success' => true,
                'message' => 'Statistiques récupérées',
                'data' => $statistics,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur statistiques temps réel: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les activités récentes
     */
    public function getRecentActivities(): JsonResponse
    {
        try {
            $activities = $this->realTimeService->getRecentActivities();
            
            return response()->json([
                'success' => true,
                'message' => 'Activités récentes récupérées',
                'data' => $activities,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur activités temps réel: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des activités',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut du système
     */
    public function getSystemStatus(): JsonResponse
    {
        try {
            $status = $this->realTimeService->getSystemStatus();
            
            return response()->json([
                'success' => true,
                'message' => 'Statut système récupéré',
                'data' => $status,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur statut système: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les notifications actives
     */
    public function getNotifications(): JsonResponse
    {
        try {
            $notifications = $this->realTimeService->getActiveNotifications();
            
            return response()->json([
                'success' => true,
                'message' => 'Notifications récupérées',
                'data' => $notifications,
                'count' => count($notifications),
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur notifications temps réel: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Forcer la synchronisation
     */
    public function forceSync(): JsonResponse
    {
        try {
            $result = $this->realTimeService->forceSyncDatabase();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'],
                'timestamp' => now()->toISOString()
            ], $result['success'] ? 200 : 500);
            
        } catch (\Exception $e) {
            Log::error('Erreur synchronisation forcée: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation forcée',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérification de santé du système
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $health = $this->realTimeService->healthCheck();
            
            return response()->json([
                'success' => true,
                'message' => $health['healthy'] ? 'Système en bonne santé' : 'Problèmes détectés',
                'data' => $health,
                'timestamp' => now()->toISOString()
            ], $health['healthy'] ? 200 : 503);
            
        } catch (\Exception $e) {
            Log::error('Erreur vérification santé: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de santé',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les données mises en cache
     */
    public function getCachedData(): JsonResponse
    {
        try {
            $data = $this->realTimeService->getCachedData();
            
            return response()->json([
                'success' => true,
                'message' => 'Données en cache récupérées',
                'data' => $data,
                'cached' => true,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur données en cache: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données en cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint de test de connectivité
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Pong! API temps réel active',
            'timestamp' => now()->toISOString(),
            'server_time' => now()->format('Y-m-d H:i:s'),
            'database' => config('database.connections.mysql.database'),
            'environment' => app()->environment()
        ]);
    }

    /**
     * Obtenir les métriques de performance
     */
    public function getMetrics(): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            // Test de performance de la base de données
            $dbStart = microtime(true);
            $this->realTimeService->checkDatabaseConnection();
            $dbTime = (microtime(true) - $dbStart) * 1000;
            
            // Test de performance du cache
            $cacheStart = microtime(true);
            $this->realTimeService->checkCacheConnection();
            $cacheTime = (microtime(true) - $cacheStart) * 1000;
            
            $totalTime = (microtime(true) - $startTime) * 1000;
            
            return response()->json([
                'success' => true,
                'message' => 'Métriques de performance',
                'data' => [
                    'response_time_ms' => round($totalTime, 2),
                    'database_time_ms' => round($dbTime, 2),
                    'cache_time_ms' => round($cacheTime, 2),
                    'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
                ],
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur métriques: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des métriques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}