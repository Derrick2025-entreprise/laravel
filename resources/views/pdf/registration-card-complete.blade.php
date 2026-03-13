<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche d'Inscription - {{ $reference }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #00b894;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 5px 0;
            color: #00b894;
        }
        
        .header .subtitle {
            font-size: 12px;
            color: #666;
            margin: 0;
        }
        
        .flag-bar {
            height: 4px;
            background: linear-gradient(to right, #00b894 33%, #fdcb6e 33% 66%, #e17055 66%);
            margin-bottom: 15px;
        }
        
        .reference-box {
            background: #f8f9fa;
            border: 2px solid #00b894;
            padding: 8px;
            text-align: center;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        
        .reference-box .ref-number {
            font-size: 16px;
            font-weight: bold;
            color: #00b894;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
            margin-top: 5px;
        }
        
        .main-content {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .left-column {
            display: table-cell;
            width: 65%;
            vertical-align: top;
            padding-right: 15px;
        }
        
        .right-column {
            display: table-cell;
            width: 35%;
            vertical-align: top;
            text-align: center;
        }
        
        .section {
            margin-bottom: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .section-header {
            background: #00b894;
            color: white;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .section-content {
            padding: 8px 10px;
            background: white;
        }
        
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            display: table-cell;
            width: 35%;
            font-weight: bold;
            color: #666;
            vertical-align: top;
        }
        
        .info-value {
            display: table-cell;
            width: 65%;
            color: #333;
            vertical-align: top;
        }
        
        .school-logo {
            width: 80px;
            height: 80px;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: #00b894;
        }
        
        .qr-code {
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 10px auto;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
        
        .photo-placeholder {
            width: 100px;
            height: 120px;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin: 10px auto;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
        
        .instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 8px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 10px;
        }
        
        .instructions h4 {
            margin: 0 0 5px 0;
            color: #856404;
            font-size: 11px;
        }
        
        .instructions ul {
            margin: 0;
            padding-left: 15px;
        }
        
        .instructions li {
            margin-bottom: 2px;
        }
        
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 15px;
        }
        
        .signature-left {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding-right: 10px;
        }
        
        .signature-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding-left: 10px;
        }
        
        .signature-box {
            border: 1px solid #ddd;
            height: 60px;
            margin-top: 5px;
            border-radius: 3px;
            background: #fafafa;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 48px;
            color: rgba(0, 184, 148, 0.1);
            font-weight: bold;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="watermark">SGEE CAMEROUN</div>
    
    <div class="flag-bar"></div>
    
    <div class="header">
        <h1>🇨🇲 RÉPUBLIQUE DU CAMEROUN</h1>
        <p class="subtitle">SYSTÈME DE GESTION DES EXAMENS ET ÉVALUATIONS (SGEE)</p>
        <p class="subtitle"><strong>FICHE D'INSCRIPTION AU CONCOURS</strong></p>
    </div>
    
    <div class="reference-box">
        <div class="ref-number">{{ $reference }}</div>
        <div class="status-confirmed">✓ INSCRIPTION CONFIRMÉE</div>
    </div>
    
    <div class="main-content">
        <div class="left-column">
            <!-- Informations du concours -->
            <div class="section">
                <div class="section-header">📋 INFORMATIONS DU CONCOURS</div>
                <div class="section-content">
                    <div class="info-row">
                        <div class="info-label">École :</div>
                        <div class="info-value">{{ $school->name }} ({{ $school->sigle }})</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Concours :</div>
                        <div class="info-value">{{ $exam->title }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Filière :</div>
                        <div class="info-value">{{ $filiere->filiere_name }} ({{ $filiere->filiere_code }})</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date d'examen :</div>
                        <div class="info-value">{{ $exam->exam_date ? $exam->exam_date->format('d/m/Y') : 'À définir' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date d'inscription :</div>
                        <div class="info-value">{{ $registration->registered_at->format('d/m/Y à H:i') }}</div>
                    </div>
                </div>
            </div>
            
            <!-- Informations du candidat -->
            <div class="section">
                <div class="section-header">👤 INFORMATIONS DU CANDIDAT</div>
                <div class="section-content">
                    <div class="info-row">
                        <div class="info-label">Nom complet :</div>
                        <div class="info-value"><strong>{{ strtoupper($candidate->nom) }} {{ ucfirst($candidate->prenom) }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Téléphone :</div>
                        <div class="info-value">{{ $candidate->telephone ?: 'Non renseigné' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email :</div>
                        <div class="info-value">{{ $candidate->user->email }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date de naissance :</div>
                        <div class="info-value">{{ $candidate->date_naissance ? \Carbon\Carbon::parse($candidate->date_naissance)->format('d/m/Y') : 'Non renseignée' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Lieu de naissance :</div>
                        <div class="info-value">{{ $candidate->lieu_naissance ?: 'Non renseigné' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Sexe :</div>
                        <div class="info-value">{{ $candidate->sexe === 'M' ? 'Masculin' : ($candidate->sexe === 'F' ? 'Féminin' : 'Non renseigné') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nationalité :</div>
                        <div class="info-value">{{ $candidate->nationalite ?: 'Non renseignée' }}</div>
                    </div>
                    @if($candidate->adresse)
                    <div class="info-row">
                        <div class="info-label">Adresse :</div>
                        <div class="info-value">{{ $candidate->adresse }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="right-column">
            <!-- Logo de l'école -->
            <div class="school-logo">
                {{ $school->sigle }}
            </div>
            
            <!-- Photo du candidat -->
            <div class="photo-placeholder">
                PHOTO<br>
                DU<br>
                CANDIDAT<br>
                <small>(à coller)</small>
            </div>
            
            <!-- QR Code -->
            <div class="qr-code">
                QR CODE<br>
                <small>{{ substr($qr_code_data, 0, 20) }}...</small>
            </div>
            
            <!-- Informations de validation -->
            <div class="section">
                <div class="section-header">✓ VALIDATION</div>
                <div class="section-content">
                    <div class="info-row">
                        <div class="info-label">Statut :</div>
                        <div class="info-value" style="color: #00b894; font-weight: bold;">CONFIRMÉ</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Paiement :</div>
                        <div class="info-value" style="color: #00b894;">VALIDÉ</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Généré le :</div>
                        <div class="info-value">{{ $generated_at }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Instructions importantes -->
    <div class="instructions">
        <h4>📋 INSTRUCTIONS IMPORTANTES</h4>
        <ul>
            <li><strong>Conservez précieusement cette fiche</strong> - Elle vous sera demandée le jour de l'examen</li>
            <li><strong>Collez votre photo</strong> dans l'emplacement prévu à cet effet</li>
            <li><strong>Apportez une pièce d'identité valide</strong> le jour de l'examen</li>
            <li><strong>Arrivez 30 minutes avant l'heure</strong> indiquée pour l'examen</li>
            <li><strong>Vérifiez les informations</strong> - En cas d'erreur, contactez immédiatement le support</li>
        </ul>
    </div>
    
    <!-- Section signatures -->
    <div class="signature-section">
        <div class="signature-left">
            <strong>Signature du candidat</strong>
            <div class="signature-box"></div>
        </div>
        <div class="signature-right">
            <strong>Cachet de l'établissement</strong>
            <div class="signature-box"></div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p><strong>SGEE - Système de Gestion des Examens et Évaluations</strong></p>
        <p>République du Cameroun 🇨🇲 | Document généré automatiquement le {{ $generated_at }}</p>
        <p>Support : +237 6XX XXX XXX | Email : support@sgee-cameroun.cm</p>
        <p style="font-size: 8px; margin-top: 5px;">
            Référence : {{ $reference }} | QR Code : {{ substr(md5($qr_code_data), 0, 8) }}
        </p>
    </div>
</body>
</html>