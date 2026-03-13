<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateExam;
use App\Models\ExamResult;
use App\Models\Payment;
use App\Models\GeneratedDocument;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DocumentGeneratorController extends Controller
{

    /**
     * Générer la fiche d'enrôlement
     */
    public function generateFicheEnrolement(string $candidateExamId)
    {
        try {
            $user = auth('sanctum')->user();
            $candidateExam = CandidateExam::where('id', $candidateExamId)
                ->where('user_id', $user->id)
                ->with(['exam', 'filiere'])
                ->firstOrFail();

            // Créer un document de test
            $document = [
                'id' => rand(1000, 9999),
                'type_document' => 'fiche_enrolement',
                'nom_fichier' => 'Fiche_Enrolement_' . $candidateExam->exam->title . '.pdf',
                'qr_code' => 'SGEE-FE-' . $candidateExam->id . '-' . time(),
                'created_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Fiche d\'enrôlement générée avec succès',
                'data' => $document
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Générer la convocation
     */
    public function generateConvocation(string $candidateExamId)
    {
        try {
            $user = auth('sanctum')->user();
            $candidateExam = CandidateExam::where('id', $candidateExamId)
                ->where('user_id', $user->id)
                ->with(['exam', 'filiere'])
                ->firstOrFail();

            $document = [
                'id' => rand(1000, 9999),
                'type_document' => 'convocation',
                'nom_fichier' => 'Convocation_' . $candidateExam->exam->title . '.pdf',
                'qr_code' => 'SGEE-CONV-' . $candidateExam->id . '-' . time(),
                'created_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Convocation générée avec succès',
                'data' => $document
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Générer l'attestation
     */
    public function generateAttestation(string $resultId)
    {
        try {
            $user = auth('sanctum')->user();
            
            // Pour le moment, générer une attestation de test
            $document = [
                'id' => rand(1000, 9999),
                'type_document' => 'attestation',
                'nom_fichier' => 'Attestation_' . $user->name . '.pdf',
                'qr_code' => 'SGEE-ATT-' . $user->id . '-' . time(),
                'created_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Attestation générée avec succès',
                'data' => $document
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Générer le quitus de paiement
     */
    public function generateQuitus(string $paymentId)
    {
        try {
            $user = auth('sanctum')->user();
            
            // Pour le moment, générer un quitus de test
            $document = [
                'id' => rand(1000, 9999),
                'type_document' => 'quitus',
                'nom_fichier' => 'Quitus_Paiement_' . $user->name . '.pdf',
                'qr_code' => 'SGEE-QUIT-' . $user->id . '-' . time(),
                'created_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Quitus généré avec succès',
                'data' => $document
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Télécharger un document généré
     */
    public function download(string $documentId)
    {
        try {
            $user = auth('sanctum')->user();
            
            // Pour le moment, générer un PDF de test
            $documents = [
                1 => [
                    'filename' => 'Fiche_Enrolement_' . $user->name . '.pdf',
                    'content' => 'PDF content for fiche enrolement'
                ],
                2 => [
                    'filename' => 'Quitus_Paiement_' . $user->name . '.pdf', 
                    'content' => 'PDF content for quitus'
                ]
            ];

            if (!isset($documents[$documentId])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $doc = $documents[$documentId];

            // Générer un PDF simple pour la démonstration
            $pdfContent = $this->generateSimplePdf($doc['content'], $user);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $doc['filename'] . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Générer un PDF simple pour la démonstration
     */
    private function generateSimplePdf(string $content, $user): string
    {
        // Contenu PDF basique (pour la démonstration)
        $pdfHeader = "%PDF-1.4\n";
        $pdfBody = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdfBody .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdfBody .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>\nendobj\n";
        $pdfBody .= "4 0 obj\n<< /Length 44 >>\nstream\nBT\n/F1 12 Tf\n100 700 Td\n({$content} - {$user->name}) Tj\nET\nendstream\nendobj\n";
        $pdfFooter = "xref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000207 00000 n \ntrailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n301\n%%EOF";
        
        return $pdfHeader . $pdfBody . $pdfFooter;
    }

    /**
     * Lister les documents générés
     */
    public function myDocuments()
    {
        try {
            $user = auth('sanctum')->user();
            
            // Pour le moment, retourner des documents de test
            $documents = [
                [
                    'id' => 1,
                    'type_document' => 'fiche_enrolement',
                    'nom_fichier' => 'Fiche_Enrolement_' . $user->name . '.pdf',
                    'taille_fichier' => 245760,
                    'statut' => 'genere',
                    'qr_code' => 'SGEE-' . $user->id . '-' . time(),
                    'created_at' => now()->toISOString(),
                ],
                [
                    'id' => 2,
                    'type_document' => 'quitus',
                    'nom_fichier' => 'Quitus_Paiement_' . $user->name . '.pdf',
                    'taille_fichier' => 189440,
                    'statut' => 'genere',
                    'qr_code' => 'SGEE-PAY-' . $user->id . '-' . time(),
                    'created_at' => now()->subDays(2)->toISOString(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $documents,
                    'current_page' => 1,
                    'total' => count($documents)
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
