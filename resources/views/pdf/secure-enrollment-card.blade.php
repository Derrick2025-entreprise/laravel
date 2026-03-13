<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche d'Enrôlement Sécurisée - SGEE Cameroun</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 portrait;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
            background: linear-gradient(45deg, rgba(0,128,0,0.05) 0%, rgba(255,215,0,0.05) 100%);
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 48px;
            color: rgba(0,128,0,0.1);
            font-weight: bold;
            z-index: -1;
            white-space: nowrap;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #006400;
            padding-bottom: 15px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #006400 0%, #228B22 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .header h2 {
            margin: 5px 0 0 0;
            font-size: 14px;
            font-weight: normal;
        }

        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #FFD700;
        }

        .document-number {
            font-weight: bold;
            color: #006400;
        }

        .generated-date {
            color: #666;
            font-size: 10px;
        }

        .student-section {
            display: flex;
            margin-bottom: 20px;
        }

        .student-photo {
            width: 120px;
            margin-right: 20px;
        }

        .photo-placeholder {
            width: 120px;
            height: 150px;
            border: 2px solid #006400;
            background: #f0f8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #666;
            text-align: center;
        }

        .student-details {
            flex: 1;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            color: #006400;
            font-size: 10px;
            text-transform: uppercase;
        }

        .info-value {
            margin-top: 2px;
            font-size: 12px;
        }

        .academic-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #006400;
        }

        .academic-title {
            font-weight: bold;
            color: #006400;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .qr-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border: 2px solid #006400;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .qr-code {
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            background: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            text-align: center;
        }

        .qr-info {
            flex: 1;
            margin-left: 20px;
        }

        .qr-title {
            font-weight: bold;
            color: #006400;
            margin-bottom: 5px;
        }

        .qr-url {
            font-size: 9px;
            color: #666;
            word-break: break-all;
        }

        .security-features {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .security-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .security-item {
            font-size: 9px;
            color: #856404;
            margin-bottom: 3px;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 10px;
        }

        .footer {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .cameroon-flag {
            display: inline-block;
            width: 20px;
            height: 15px;
            background: linear-gradient(to right, #006400 33%, #FFD700 33%, #FFD700 66%, #FF0000 66%);
            margin-right: 5px;
            vertical-align: middle;
        }

        .urgent-notice {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .status-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Filigrane de sécurité -->
    <div class="watermark">{{ $security_features['watermark'] }}</div>

    <!-- En-tête officiel -->
    <div class="header">
        <div class="cameroon-flag"></div>
        <h1>RÉPUBLIQUE DU CAMEROUN</h1>
        <h2>Système de Gestion des Examens et Enrôlements</h2>
        <h2>FICHE D'ENRÔLEMENT OFFICIELLE</h2>
    </div>

    <!-- Informations du document -->
    <div class="document-info">
        <div>
            <div class="document-number">N° Document: {{ $document_number }}</div>
            <div class="document-number">N° Étudiant: {{ $student->student_number }}</div>
        </div>
        <div class="generated-date">
            Généré le {{ $generated_at }}<br>
            <span class="status-badge">VALIDÉ</span>
        </div>
    </div>

    <!-- Avis important -->
    <div class="urgent-notice">
        <strong>DOCUMENT OFFICIEL :</strong> Cette fiche d'enrôlement est un document officiel du SGEE Cameroun. 
        Toute falsification est passible de sanctions pénales. Vérifiez l'authenticité via le QR Code ci-dessous.
    </div>

    <!-- Section étudiant avec photo -->
    <div class="student-section">
        <div class="student-photo">
            <div class="photo-placeholder">
                PHOTO<br>
                ÉTUDIANT<br>
                4x4 cm
            </div>
        </div>
        
        <div class="student-details">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nom complet</div>
                    <div class="info-value">{{ $student->full_name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date de naissance</div>
                    <div class="info-value">{{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Lieu de naissance</div>
                    <div class="info-value">{{ $student->place_of_birth ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Sexe</div>
                    <div class="info-value">{{ $student->gender == 'M' ? 'Masculin' : 'Féminin' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nationalité</div>
                    <div class="info-value">{{ $student->nationality ?? 'Camerounaise' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value">{{ $student->phone ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value">{{ $student->email }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Adresse</div>
                    <div class="info-value">{{ $student->address ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section académique -->
    <div class="academic-section">
        <div class="academic-title">INFORMATIONS ACADÉMIQUES</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">École/Université</div>
                <div class="info-value">{{ $school->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Département</div>
                <div class="info-value">{{ $department->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Filière</div>
                <div class="info-value">{{ $filiere->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Date d'enrôlement</div>
                <div class="info-value">{{ $student->enrollment_date ? $student->enrollment_date->format('d/m/Y') : 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Section QR Code de vérification -->
    <div class="qr-section">
        <div class="qr-code">
            QR CODE<br>
            VÉRIFICATION<br>
            {{ $qr_code['unique_id'] }}
        </div>
        <div class="qr-info">
            <div class="qr-title">VÉRIFICATION D'AUTHENTICITÉ</div>
            <p>Scannez ce QR Code ou visitez l'URL ci-dessous pour vérifier l'authenticité de ce document :</p>
            <div class="qr-url">{{ $qr_code['verification_url'] }}</div>
            <p><strong>ID Unique :</strong> {{ $qr_code['unique_id'] }}</p>
        </div>
    </div>

    <!-- Caractéristiques de sécurité -->
    <div class="security-features">
        <div class="security-title">CARACTÉRISTIQUES DE SÉCURITÉ</div>
        <div class="security-item">• Numéro de série : {{ $security_features['serial_number'] }}</div>
        <div class="security-item">• Signature numérique : {{ substr($security_features['digital_signature'], 0, 16) }}...</div>
        <div class="security-item">• QR Code unique avec vérification en ligne</div>
        <div class="security-item">• Filigrane de sécurité intégré</div>
        <div class="security-item">• Document généré automatiquement par le système SGEE</div>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                Signature de l'Étudiant
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                Cachet et Signature de l'Administration
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <p><strong>SGEE Cameroun</strong> - Système de Gestion des Examens et Enrôlements</p>
        <p>Document officiel généré électroniquement - Aucune signature manuscrite requise</p>
        <p>Pour toute vérification : www.sgee-cameroun.cm | Email : verification@sgee-cameroun.cm</p>
        <p><em>Ce document est valide uniquement avec le QR Code de vérification ci-dessus</em></p>
    </div>
</body>
</html>