<?php

namespace App\Services;

use App\Models\CandidateExam;
use App\Models\ExamResult;
use App\Models\Payment;
use App\Models\GeneratedDocument;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PdfGeneratorService
{
    /**
     * Générer la fiche d'enrôlement PDF
     */
    public function generateFicheEnrolement(CandidateExam $candidateExam): GeneratedDocument
    {
        // Données pour le PDF
        $data = [
            'candidate' => $candidateExam->candidate,
            'exam' => $candidateExam->exam,
            'filiere' => $candidateExam->filiere,
            'school' => $candidateExam->exam->school,
            'generated_at' => now(),
            'qr_code' => $this->generateQRCode($candidateExam->id),
        ];

        // Générer le contenu HTML (simulation)
        $htmlContent = $this->generateFicheEnrolementHTML($data);
        
        // Nom du fichier
        $filename = 'fiche_enrolement_' . $candidateExam->id . '_' . time() . '.pdf';
        
        // Simuler la génération PDF (en production, utiliser DomPDF ou Snappy)
        $pdfContent = $this->convertHTMLToPDF($htmlContent);
        
        // Sauvegarder le fichier
        Storage::disk('public')->put('documents/' . $filename, $pdfContent);

        // Enregistrer en base
        $document = GeneratedDocument::create([
            'candidate_exam_id' => $candidateExam->id,
            'type_document' => 'fiche_enrolement',
            'nom_fichier' => $filename,
            'chemin_fichier' => 'documents/' . $filename,
            'taille_fichier' => strlen($pdfContent),
            'qr_code' => $data['qr_code'],
            'statut' => 'genere',
        ]);

        return $document;
    }

    /**
     * Générer la convocation PDF
     */
    public function generateConvocation(CandidateExam $candidateExam): GeneratedDocument
    {
        $data = [
            'candidate' => $candidateExam->candidate,
            'exam' => $candidateExam->exam,
            'filiere' => $candidateExam->filiere,
            'school' => $candidateExam->exam->school,
            'generated_at' => now(),
            'qr_code' => $this->generateQRCode($candidateExam->id . '_convocation'),
        ];

        $htmlContent = $this->generateConvocationHTML($data);
        $filename = 'convocation_' . $candidateExam->id . '_' . time() . '.pdf';
        $pdfContent = $this->convertHTMLToPDF($htmlContent);
        
        Storage::disk('public')->put('documents/' . $filename, $pdfContent);

        $document = GeneratedDocument::create([
            'candidate_exam_id' => $candidateExam->id,
            'type_document' => 'convocation',
            'nom_fichier' => $filename,
            'chemin_fichier' => 'documents/' . $filename,
            'taille_fichier' => strlen($pdfContent),
            'qr_code' => $data['qr_code'],
            'statut' => 'genere',
        ]);

        return $document;
    }

    /**
     * Générer l'attestation PDF
     */
    public function generateAttestation(ExamResult $result): GeneratedDocument
    {
        $data = [
            'candidate' => $result->candidateExam->candidate,
            'exam' => $result->candidateExam->exam,
            'result' => $result,
            'school' => $result->candidateExam->exam->school,
            'generated_at' => now(),
            'qr_code' => $this->generateQRCode($result->id . '_attestation'),
        ];

        $htmlContent = $this->generateAttestationHTML($data);
        $filename = 'attestation_' . $result->id . '_' . time() . '.pdf';
        $pdfContent = $this->convertHTMLToPDF($htmlContent);
        
        Storage::disk('public')->put('documents/' . $filename, $pdfContent);

        $document = GeneratedDocument::create([
            'candidate_exam_id' => $result->candidate_exam_id,
            'type_document' => 'attestation',
            'nom_fichier' => $filename,
            'chemin_fichier' => 'documents/' . $filename,
            'taille_fichier' => strlen($pdfContent),
            'qr_code' => $data['qr_code'],
            'statut' => 'genere',
        ]);

        return $document;
    }

    /**
     * Générer le quitus de paiement PDF
     */
    public function generateQuitus(Payment $payment): GeneratedDocument
    {
        $data = [
            'payment' => $payment,
            'candidate' => $payment->candidate,
            'exam' => $payment->candidateExam->exam ?? null,
            'generated_at' => now(),
            'qr_code' => $this->generateQRCode($payment->id . '_quitus'),
        ];

        $htmlContent = $this->generateQuitusHTML($data);
        $filename = 'quitus_' . $payment->id . '_' . time() . '.pdf';
        $pdfContent = $this->convertHTMLToPDF($htmlContent);
        
        Storage::disk('public')->put('documents/' . $filename, $pdfContent);

        $document = GeneratedDocument::create([
            'candidate_exam_id' => $payment->candidate_exam_id,
            'type_document' => 'quitus',
            'nom_fichier' => $filename,
            'chemin_fichier' => 'documents/' . $filename,
            'taille_fichier' => strlen($pdfContent),
            'qr_code' => $data['qr_code'],
            'statut' => 'genere',
        ]);

        return $document;
    }

    /**
     * Générer un QR Code unique
     */
    private function generateQRCode(string $data): string
    {
        // Simulation d'un QR Code (en production, utiliser SimpleSoftwareIO/simple-qrcode)
        return 'QR_' . hash('sha256', $data . config('app.key'));
    }

    /**
     * Générer le HTML pour la fiche d'enrôlement
     */
    private function generateFicheEnrolementHTML(array $data): string
    {
        return "
        <html>
        <head>
            <title>Fiche d'Enrôlement</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .info { margin: 10px 0; }
                .qr-code { text-align: center; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>RÉPUBLIQUE DU CAMEROUN</h1>
                <h2>FICHE D'ENRÔLEMENT</h2>
                <h3>{$data['school']->name}</h3>
            </div>
            
            <div class='info'>
                <p><strong>Nom:</strong> {$data['candidate']->nom}</p>
                <p><strong>Prénom:</strong> {$data['candidate']->prenom}</p>
                <p><strong>Email:</strong> {$data['candidate']->user->email}</p>
                <p><strong>Téléphone:</strong> {$data['candidate']->telephone}</p>
                <p><strong>Formation:</strong> {$data['exam']->title}</p>
                <p><strong>Filière:</strong> " . ($data['filiere']->nom_filiere ?? 'Non spécifiée') . "</p>
                <p><strong>Date d'enrôlement:</strong> {$data['generated_at']->format('d/m/Y H:i')}</p>
            </div>
            
            <div class='qr-code'>
                <p><strong>Code de vérification:</strong> {$data['qr_code']}</p>
                <p>Ce document est authentifié par QR Code</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Générer le HTML pour la convocation
     */
    private function generateConvocationHTML(array $data): string
    {
        return "
        <html>
        <head>
            <title>Convocation</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .info { margin: 10px 0; }
                .important { background: #f0f0f0; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>RÉPUBLIQUE DU CAMEROUN</h1>
                <h2>CONVOCATION</h2>
                <h3>{$data['school']->name}</h3>
            </div>
            
            <div class='info'>
                <p><strong>Candidat:</strong> {$data['candidate']->prenom} {$data['candidate']->nom}</p>
                <p><strong>Formation:</strong> {$data['exam']->title}</p>
                <p><strong>Date d'examen:</strong> {$data['exam']->exam_date}</p>
                <p><strong>Lieu:</strong> À définir</p>
            </div>
            
            <div class='important'>
                <h3>INSTRUCTIONS IMPORTANTES</h3>
                <ul>
                    <li>Se présenter 30 minutes avant l'heure</li>
                    <li>Apporter une pièce d'identité valide</li>
                    <li>Matériel autorisé : stylos, calculatrice simple</li>
                </ul>
            </div>
            
            <p><strong>Code de vérification:</strong> {$data['qr_code']}</p>
        </body>
        </html>";
    }

    /**
     * Générer le HTML pour l'attestation
     */
    private function generateAttestationHTML(array $data): string
    {
        return "
        <html>
        <head>
            <title>Attestation</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .content { text-align: center; margin: 30px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>RÉPUBLIQUE DU CAMEROUN</h1>
                <h2>ATTESTATION DE RÉUSSITE</h2>
                <h3>{$data['school']->name}</h3>
            </div>
            
            <div class='content'>
                <p>Il est attesté que</p>
                <h2>{$data['candidate']->prenom} {$data['candidate']->nom}</h2>
                <p>a réussi l'examen de</p>
                <h3>{$data['exam']->title}</h3>
                <p>avec la note de <strong>{$data['result']->note}/20</strong></p>
                <p>Rang: {$data['result']->rang}</p>
            </div>
            
            <p><strong>Code de vérification:</strong> {$data['qr_code']}</p>
        </body>
        </html>";
    }

    /**
     * Générer le HTML pour le quitus
     */
    private function generateQuitusHTML(array $data): string
    {
        return "
        <html>
        <head>
            <title>Quitus de Paiement</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .info { margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>RÉPUBLIQUE DU CAMEROUN</h1>
                <h2>QUITUS DE PAIEMENT</h2>
            </div>
            
            <div class='info'>
                <p><strong>Candidat:</strong> {$data['candidate']->prenom} {$data['candidate']->nom}</p>
                <p><strong>Montant:</strong> {$data['payment']->montant} FCFA</p>
                <p><strong>Type:</strong> {$data['payment']->type_paiement}</p>
                <p><strong>Date de paiement:</strong> {$data['payment']->date_paiement}</p>
                <p><strong>Statut:</strong> VALIDÉ</p>
            </div>
            
            <p><strong>Code de vérification:</strong> {$data['qr_code']}</p>
        </body>
        </html>";
    }

    /**
     * Convertir HTML en PDF (simulation)
     */
    private function convertHTMLToPDF(string $html): string
    {
        // En production, utiliser DomPDF ou Snappy
        // Pour la simulation, on retourne le HTML
        return "PDF_SIMULATION: " . base64_encode($html);
    }
}