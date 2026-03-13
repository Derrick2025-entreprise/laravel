<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateExam;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class RegistrationCardController extends Controller
{
    /**
     * Télécharger le PDF de la fiche d'inscription
     */
    public function downloadPdf($registrationId)
    {
        try {
            $user = auth('sanctum')->user();
            
            $registration = CandidateExam::with(['candidate', 'exam.school', 'filiere'])
                ->where('id', $registrationId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Vérifier que l'inscription est confirmée
            if ($registration->statut !== 'confirme') {
                return response()->json([
                    'success' => false,
                    'message' => 'Le PDF n\'est disponible que pour les inscriptions confirmées'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Générer le PDF
            $pdfData = $this->preparePdfData($registration);
            $pdf = Pdf::loadView('pdf.registration-card-complete', $pdfData);
            $pdf->setPaper('A4', 'portrait');

            // Nom du fichier
            $fileName = 'Fiche_Inscription_' . $registration->id . '_' . date('Y-m-d') . '.pdf';

            Log::info('PDF téléchargé', [
                'user_id' => $user->id,
                'registration_id' => $registrationId,
                'file_name' => $fileName
            ]);

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error('Erreur téléchargement PDF', [
                'user_id' => auth('sanctum')->id(),
                'registration_id' => $registrationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du PDF'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Générer la fiche d'inscription (ancienne méthode pour compatibilité)
     */
    public function generateCard($registrationId)
    {
        return $this->downloadPdf($registrationId);
    }

    /**
     * Préparer les données pour le PDF
     */
    private function preparePdfData(CandidateExam $registration): array
    {
        return [
            'registration' => $registration,
            'candidate' => $registration->candidate,
            'exam' => $registration->exam,
            'school' => $registration->exam->school,
            'filiere' => $registration->filiere,
            'qr_code_data' => $this->generateQrCodeData($registration),
            'reference' => 'SGEE-' . str_pad($registration->id, 6, '0', STR_PAD_LEFT),
            'generated_at' => now()->format('d/m/Y H:i'),
            'status_text' => $this->getStatusText($registration->statut),
            'payment_status_text' => $this->getPaymentStatusText($registration->payment_status ?? 'validated')
        ];
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
            'status' => $registration->statut,
            'verified' => true
        ];
        
        return json_encode($data);
    }

    /**
     * Obtenir le texte du statut
     */
    private function getStatusText(string $status): string
    {
        switch ($status) {
            case 'confirme': return 'CONFIRMÉ';
            case 'inscrit': return 'EN ATTENTE';
            case 'rejete': return 'REJETÉ';
            default: return strtoupper($status);
        }
    }

    /**
     * Obtenir le texte du statut de paiement
     */
    private function getPaymentStatusText(string $paymentStatus): string
    {
        switch ($paymentStatus) {
            case 'validated': return 'VALIDÉ';
            case 'paid': return 'PAYÉ';
            case 'pending': return 'EN ATTENTE';
            default: return strtoupper($paymentStatus);
        }
    }
}