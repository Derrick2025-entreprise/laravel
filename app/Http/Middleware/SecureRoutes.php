<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SecureRoutes
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // 1. Rate Limiting par IP
        $key = 'secure_routes:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 60)) { // 60 requêtes par minute
            Log::warning('Rate limit dépassé', [
                'ip' => $request->ip(),
                'route' => $request->route()->getName(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez patienter.'
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // 2. Validation de l'User-Agent
        $userAgent = $request->userAgent();
        if (empty($userAgent) || $this->isSuspiciousUserAgent($userAgent)) {
            Log::warning('User-Agent suspect détecté', [
                'ip' => $request->ip(),
                'user_agent' => $userAgent,
                'route' => $request->route()->getName()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.'
            ], 403);
        }

        // 3. Vérification de l'origine (CORS)
        $origin = $request->header('Origin');
        $referer = $request->header('Referer');
        
        if ($this->isProductionEnvironment() && !$this->isValidOrigin($origin, $referer)) {
            Log::warning('Origine non autorisée', [
                'ip' => $request->ip(),
                'origin' => $origin,
                'referer' => $referer,
                'route' => $request->route()->getName()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Origine non autorisée.'
            ], 403);
        }

        // 4. Détection d'injection SQL basique
        if ($this->containsSqlInjection($request)) {
            Log::critical('Tentative d\'injection SQL détectée', [
                'ip' => $request->ip(),
                'user_id' => auth('sanctum')->id(),
                'route' => $request->route()->getName(),
                'parameters' => $request->all(),
                'user_agent' => $userAgent
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Requête invalide détectée.'
            ], 400);
        }

        // 5. Validation des tailles de fichiers avant traitement
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if ($file->getSize() > 10 * 1024 * 1024) { // 10MB max
                    return response()->json([
                        'success' => false,
                        'message' => 'Fichier trop volumineux (max 10MB).'
                    ], 413);
                }
            }
        }

        // 6. Log des accès aux routes sensibles
        if ($this->isSensitiveRoute($request)) {
            Log::info('Accès route sensible', [
                'user_id' => auth('sanctum')->id(),
                'ip' => $request->ip(),
                'route' => $request->route()->getName(),
                'method' => $request->method(),
                'timestamp' => now()
            ]);
        }

        return $next($request);
    }

    /**
     * Vérifier si l'User-Agent est suspect
     */
    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/java/i',
            '/go-http-client/i',
            '/postman/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si l'origine est valide
     */
    private function isValidOrigin(?string $origin, ?string $referer): bool
    {
        $allowedDomains = [
            'localhost',
            '127.0.0.1',
            'sgee-cameroun.cm',
            'www.sgee-cameroun.cm',
            'admin.sgee-cameroun.cm'
        ];

        if ($origin) {
            $parsedOrigin = parse_url($origin, PHP_URL_HOST);
            return in_array($parsedOrigin, $allowedDomains);
        }

        if ($referer) {
            $parsedReferer = parse_url($referer, PHP_URL_HOST);
            return in_array($parsedReferer, $allowedDomains);
        }

        return false;
    }

    /**
     * Vérifier si c'est l'environnement de production
     */
    private function isProductionEnvironment(): bool
    {
        return app()->environment('production');
    }

    /**
     * Détecter les tentatives d'injection SQL basiques
     */
    private function containsSqlInjection(Request $request): bool
    {
        $sqlPatterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',
            '/[\'";](\s*(\bOR\b|\bAND\b)\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+[\'"]?)/i',
            '/\b(exec|execute|sp_|xp_)\b/i',
            '/(\-\-|\#|\/\*|\*\/)/i'
        ];

        $allInput = json_encode($request->all());
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $allInput)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si la route est sensible
     */
    private function isSensitiveRoute(Request $request): bool
    {
        $sensitiveRoutes = [
            'admin.*',
            '*.payment.*',
            '*.enroll*',
            '*.upload*',
            '*.download*'
        ];

        $routeName = $request->route()->getName() ?? '';
        
        foreach ($sensitiveRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }
}