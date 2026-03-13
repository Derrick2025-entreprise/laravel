<?php

namespace App\Services;

use App\Models\CandidateExam;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfEmailService
{
    /**
     * Générer et envoyer la fiche d'inscription par email
     */
    public function generateAndSendRegistrationCard(CandidateExam $registration): array
    {
        try {
            // 1. Générer le PDF
            $pdfResult = $this->generateRegistrationPdf($registration);
            
            if (!$pdfResult['success']) {
                return $pdfResult;
            }
            
            // 2. Envoyer par email
            $emailResult = $this->sendRegistrationEmail($registration, $pdfResult['pdf_path']);
            
            // 3. Nettoyer le fichier temporaire si nécessaire
            if (isset($pdfResult['temp_file']) && $pdfResult['temp_file']) {
                Storage::delete($pdfResult['pdf_path']);
            }
            
            return [
                'success' => $emailResult['success'],
                'message' => $emailResult['message'],
                'pdf_url' => $pdfResult['pdf_url'],
                'email_sent' => $emailResult['success']
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur génération/envoi PDF', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la génération/envoi du PDF : ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Générer le PDF de la fiche d'inscription
     */
    private function generateRegistrationPdf(CandidateExam $registration): array
    {
        try {
            $registration->load(['candidate', 'exam.school', 'filiere']);
            
            // Données pour le PDF
            $data = [
                'registration' => $registration,
                'candidate' => $registration->candidate,
                'exam' => $registration->exam,
                'school' => $registration->exam->school,
                'filiere' => $registration->filiere,
                'qr_code_data' => $this->generateQrCodeData($registration),
                'reference' => 'SGEE-' . str_pad($registration->id, 6, '0', STR_PAD_LEFT),
                'generated_at' => now()->format('d/m/Y H:i')
            ];
            
            // Générer le PDF avec template amélioré
            $pdf = Pdf::loadView('pdf.registration-card-complete', $data);
            $pdf->setPaper('A4', 'portrait');
            
            // Nom du fichier
            $fileName = 'fiche_inscription_' . $registration->id . '_' . time() . '.pdf';
            $filePath = 'public/registration_cards/' . $fileName;
            
            // Sauvegarder le PDF
            Storage::put($filePath, $pdf->output());
            
            return [
                'success' => true,
                'pdf_path' => $filePath,
                'pdf_url' => Storage::url($filePath),
                'file_name' => $fileName,
                'temp_file' => false
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur génération PDF', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Envoyer l'email avec le PDF en pièce jointe
     */
    private function sendRegistrationEmail(CandidateExam $registration, string $pdfPath): array
    {
        try {
            $candidate = $registration->candidate;
            $exam = $registration->exam;
            $school = $exam->school;
            
            // Données pour l'email
            $emailData = [
                'candidate_name' => $candidate->prenom . ' ' . $candidate->nom,
                'exam_title' => $exam->title,
                'school_name' => $school->name,
                'school_sigle' => $school->sigle,
                'filiere_name' => $registration->filiere->filiere_name,
                'registration_date' => $registration->registered_at->format('d/m/Y H:i'),
                'exam_date' => $exam->exam_date ? $exam->exam_date->format('d/m/Y') : 'À définir',
                'reference' => 'SGEE-' . str_pad($registration->id, 6, '0', STR_PAD_LEFT),
                'status' => $registration->statut
            ];
            
            // Envoyer l'email
            Mail::send('emails.registration-confirmation', $emailData, function ($message) use ($candidate, $exam, $pdfPath) {
                $message->to($candidate->user->email, $candidate->prenom . ' ' . $candidate->nom)
                       ->subject('Confirmation d\'inscription - ' . $exam->title)
                       ->attach(Storage::path($pdfPath), [
                           'as' => 'Fiche_Inscription_SGEE.pdf',
                           'mime' => 'application/pdf'
                       ]);
            });
            
            Log::info('Email d\'inscription envoyé', [
                'registration_id' => $registration->id,
                'email' => $candidate->user->email
            ]);
            
            return [
                'success' => true,
                'message' => 'Email envoyé avec succès à ' . $candidate->user->email
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi email', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Générer les données pour le QR code
     */
    private function generateQrCodeData(CandidateExam $registration): string
    {
        $data = [
            'ref' => 'SGEE-' . str_pad($registration->id, 6, '0', STR_PAD_LEFT),
            'candidate' => $registration->candidate->prenom . ' ' . $registration->candidate->nom,
            'exam' => $registration->exam->title,
            'school' => $registration->exam->school->sigle,
            'filiere' => $registration->filiere->filiere_code,
            'date' => $registration->registered_at->format('Y-m-d'),
            'status' => $registration->statut
        ];
        
        return json_encode($data);
    }
    
    /**
     * Envoyer un email de rejet avec les raisons
     */
    public function sendRejectionEmail(CandidateExam $registration, array $validationErrors): array
    {
        try {
            $candidate = $registration->candidate;
            $exam = $registration->exam;
            
            $emailData = [
                'candidate_name' => $candidate->prenom . ' ' . $candidate->nom,
                'exam_title' => $exam->title,
                'school_name' => $exam->school->name,
                'errors' => $validationErrors,
                'reference' => 'SGEE-' . str_pad($registration->id, 6, '0', STR_PAD_LEFT)
            ];
            
            Mail::send('emails.registration-rejection', $emailData, function ($message) use ($candidate, $exam) {
                $message->to($candidate->user->email, $candidate->prenom . ' ' . $candidate->nom)
                       ->subject('Inscription rejetée - ' . $exam->title);
            });
            
            return [
                'success' => true,
                'message' => 'Email de rejet envoyé'
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi email rejet', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email de rejet'
            ];
        }
    }
}