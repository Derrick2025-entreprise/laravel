<?php

namespace App\Services;

use App\Models\CandidateExam;
use App\Models\Candidate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EnrollmentValidationService
{
    /**
     * Valider automatiquement les données d'enrollment
     */
    public function validateEnrollmentData(array $candidateInfo, array $examInfo): array
    {
        $errors = [];
        $warnings = [];
        
        // 1. Validation des informations personnelles obligatoires
        $personalValidation = $this->validatePersonalInfo($candidateInfo);
        if (!$personalValidation['valid']) {
            $errors = array_merge($errors, $personalValidation['errors']);
        }
        
        // 2. Validation de l'âge
        $ageValidation = $this->validateAge($candidateInfo);
        if (!$ageValidation['valid']) {
            if ($ageValidation['severity'] === 'error') {
                $errors[] = $ageValidation['message'];
            } else {
                $warnings[] = $ageValidation['message'];
            }
        }
        
        // 3. Validation du téléphone
        $phoneValidation = $this->validatePhone($candidateInfo['telephone'] ?? '');
        if (!$phoneValidation['valid']) {
            $errors[] = $phoneValidation['message'];
        }
        
        // 4. Validation de l'email
        $emailValidation = $this->validateEmail($candidateInfo['email'] ?? '');
        if (!$emailValidation['valid']) {
            $errors[] = $emailValidation['message'];
        }
        
        // 5. Validation spécifique à l'école
        $schoolValidation = $this->validateSchoolRequirements($candidateInfo, $examInfo);
        if (!$schoolValidation['valid']) {
            $errors = array_merge($errors, $schoolValidation['errors']);
        }
        
        // Déterminer le statut final
        $status = 'rejected';
        $message = 'Candidature rejetée automatiquement';
        
        if (empty($errors)) {
            if (empty($warnings)) {
                $status = 'approved';
                $message = 'Candidature validée automatiquement';
            } else {
                $status = 'approved_with_warnings';
                $message = 'Candidature validée avec réserves';
            }
        }
        
        return [
            'valid' => empty($errors),
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
            'warnings' => $warnings,
            'validation_details' => [
                'personal_info' => $personalValidation,
                'age' => $ageValidation,
                'phone' => $phoneValidation,
                'email' => $emailValidation,
                'school_requirements' => $schoolValidation
            ]
        ];
    }
    
    /**
     * Valider les informations personnelles
     */
    private function validatePersonalInfo(array $candidateInfo): array
    {
        $errors = [];
        
        // Prénom obligatoire
        if (empty($candidateInfo['prenom']) || strlen(trim($candidateInfo['prenom'])) < 2) {
            $errors[] = 'Le prénom est obligatoire et doit contenir au moins 2 caractères';
        }
        
        // Nom obligatoire
        if (empty($candidateInfo['nom']) || strlen(trim($candidateInfo['nom'])) < 2) {
            $errors[] = 'Le nom est obligatoire et doit contenir au moins 2 caractères';
        }
        
        // Vérifier les caractères spéciaux
        if (!empty($candidateInfo['prenom']) && !preg_match('/^[a-zA-ZÀ-ÿ\s\-\']+$/u', $candidateInfo['prenom'])) {
            $errors[] = 'Le prénom contient des caractères non autorisés';
        }
        
        if (!empty($candidateInfo['nom']) && !preg_match('/^[a-zA-ZÀ-ÿ\s\-\']+$/u', $candidateInfo['nom'])) {
            $errors[] = 'Le nom contient des caractères non autorisés';
        }
        
        // Sexe obligatoire
        if (empty($candidateInfo['sexe']) || !in_array($candidateInfo['sexe'], ['M', 'F'])) {
            $errors[] = 'Le sexe doit être spécifié (M ou F)';
        }
        
        // Nationalité
        if (empty($candidateInfo['nationalite'])) {
            $errors[] = 'La nationalité est obligatoire';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Valider l'âge du candidat
     */
    private function validateAge(array $candidateInfo): array
    {
        if (empty($candidateInfo['date_naissance'])) {
            return [
                'valid' => false,
                'severity' => 'error',
                'message' => 'La date de naissance est obligatoire'
            ];
        }
        
        try {
            $birthDate = new \DateTime($candidateInfo['date_naissance']);
            $today = new \DateTime();
            $age = $today->diff($birthDate)->y;
            
            // Âge minimum : 16 ans
            if ($age < 16) {
                return [
                    'valid' => false,
                    'severity' => 'error',
                    'message' => 'Le candidat doit avoir au moins 16 ans'
                ];
            }
            
            // Âge maximum : 25 ans (avec avertissement à partir de 23 ans)
            if ($age > 25) {
                return [
                    'valid' => false,
                    'severity' => 'error',
                    'message' => 'Le candidat ne peut pas avoir plus de 25 ans'
                ];
            }
            
            if ($age >= 23) {
                return [
                    'valid' => true,
                    'severity' => 'warning',
                    'message' => 'Candidat proche de la limite d\'âge (23+ ans)'
                ];
            }
            
            return [
                'valid' => true,
                'severity' => 'info',
                'message' => "Âge validé : $age ans"
            ];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'severity' => 'error',
                'message' => 'Format de date de naissance invalide'
            ];
        }
    }
    
    /**
     * Valider le numéro de téléphone
     */
    private function validatePhone(string $phone): array
    {
        if (empty($phone)) {
            return [
                'valid' => false,
                'message' => 'Le numéro de téléphone est obligatoire'
            ];
        }
        
        // Nettoyer le numéro
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Formats acceptés pour le Cameroun
        $patterns = [
            '/^\+237[0-9]{9}$/',           // +237XXXXXXXXX
            '/^237[0-9]{9}$/',             // 237XXXXXXXXX
            '/^[0-9]{9}$/',                // XXXXXXXXX
            '/^6[0-9]{8}$/',               // 6XXXXXXXX (mobile)
            '/^2[0-9]{8}$/'                // 2XXXXXXXX (fixe)
        ];
        
        $isValid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleanPhone)) {
                $isValid = true;
                break;
            }
        }
        
        if (!$isValid) {
            return [
                'valid' => false,
                'message' => 'Format de numéro de téléphone invalide pour le Cameroun'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Numéro de téléphone validé'
        ];
    }
    
    /**
     * Valider l'email
     */
    private function validateEmail(string $email): array
    {
        if (empty($email)) {
            return [
                'valid' => false,
                'message' => 'L\'adresse email est obligatoire'
            ];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => 'Format d\'email invalide'
            ];
        }
        
        // Vérifier les domaines suspects
        $suspiciousDomains = ['tempmail.org', '10minutemail.com', 'guerrillamail.com'];
        $domain = substr(strrchr($email, "@"), 1);
        
        if (in_array($domain, $suspiciousDomains)) {
            return [
                'valid' => false,
                'message' => 'Adresses email temporaires non autorisées'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Email validé'
        ];
    }
    
    /**
     * Valider les exigences spécifiques à l'école
     */
    private function validateSchoolRequirements(array $candidateInfo, array $examInfo): array
    {
        $errors = [];
        $schoolSigle = $examInfo['school_sigle'] ?? '';
        
        switch ($schoolSigle) {
            case 'ENSP':
                // Exigences pour l'ENSP
                if (empty($candidateInfo['lieu_naissance'])) {
                    $errors[] = 'Le lieu de naissance est obligatoire pour l\'ENSP';
                }
                break;
                
            case 'FMSB':
                // Exigences pour la FMSB
                if (empty($candidateInfo['lieu_naissance'])) {
                    $errors[] = 'Le lieu de naissance est obligatoire pour la FMSB';
                }
                // Vérification médicale simulée
                break;
                
            case 'ESSEC':
                // Exigences pour l'ESSEC
                break;
                
            default:
                // Exigences générales
                break;
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Appliquer automatiquement le statut de validation
     */
    public function applyValidationStatus(CandidateExam $registration, array $validationResult): void
    {
        $status = 'inscrit'; // Par défaut
        $notes = 'Inscription en attente de validation';
        
        switch ($validationResult['status']) {
            case 'approved':
                $status = 'confirme';
                $notes = 'Candidature validée automatiquement - Toutes les conditions remplies';
                break;
                
            case 'approved_with_warnings':
                $status = 'confirme';
                $notes = 'Candidature validée avec réserves : ' . implode(', ', $validationResult['warnings']);
                break;
                
            case 'rejected':
                $status = 'rejete';
                $notes = 'Candidature rejetée automatiquement : ' . implode(', ', $validationResult['errors']);
                break;
        }
        
        $registration->update([
            'statut' => $status,
            'notes' => $notes,
            'validation_details' => json_encode($validationResult),
            'validated_at' => now(),
            'validated_by' => 'system_auto'
        ]);
        
        Log::info('Validation automatique appliquée', [
            'registration_id' => $registration->id,
            'status' => $status,
            'validation_result' => $validationResult
        ]);
    }
}