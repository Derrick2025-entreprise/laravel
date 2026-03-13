<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche d'Inscription - {{ $student->student_number }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #6f42c1;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #6f42c1;
            margin-bottom: 5px;
        }
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .document-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-top: 10px;
        }
        .card-container {
            border: 2px solid #6f42c1;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            background: #f8f9fa;
        }
        .student-photo {
            float: right;
            width: 100px;
            height: 120px;
            border: 2px solid #6f42c1;
            border-radius: 5px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #666;
            margin-left: 20px;
            margin-bottom: 20px;
        }
        .student-info {
            overflow: hidden;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #6f42c1;
            border-bottom: 1px solid #6f42c1;
            padding-bottom: 3px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #495057;
            padding: 4px 15px 4px 0;
            width: 35%;
            vertical-align: top;
        }
        .info-value {
            display: table-cell;
            color: #212529;
            padding: 4px 0;
            vertical-align: top;
        }
        .highlight-box {
            background: #6f42c1;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 15px 0;
        }
        .student-number {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .qr-section {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .qr-code {
            width: 80px;
            height: 80px;
            border: 1px solid #ccc;
            margin: 10px auto;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #666;
        }
        .payment-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .payment-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 10px;
        }
        .payment-amount {
            font-size: 16px;
            font-weight: bold;
            color: #6f42c1;
            text-align: center;
            margin: 10px 0;
        }
        .instructions {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .instructions-title {
            font-weight: bold;
            color: #0c5460;
            margin-bottom: 10px;
        }
        .instructions ul {
            margin: 0;
            padding-left: 20px;
        }
        .instructions li {
            margin-bottom: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #6c757d;
        }
        .signature-section {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px 10px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 40px;
        }
        .signature-label {
            font-size: 10px;
            color: #666;
        }
        .cameroon-colors {
            background: linear-gradient(to right, #009639 33%, #ce1126 33%, #ce1126 66%, #fcd116 66%);
            height: 5px;
            margin: 10px 0;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🎓 SGEE CAMEROUN</div>
        <div class="subtitle">République du Cameroun - Paix, Travail, Patrie</div>
        <div class="subtitle">Système de Gestion des Examens d'Entrée</div>
        <div class="cameroon-colors"></div>
        <div class="document-title">FICHE D'INSCRIPTION OFFICIELLE</div>
    </div>

    <div class="card-container clearfix">
        <div class="student-photo">
            PHOTO<br>
            ÉTUDIANT<br>
            4x5 cm
        </div>

        <div class="student-info">
            <div class="highlight-box">
                <div class="student-number">{{ $student->student_number }}</div>
                <div>Numéro d'étudiant</div>
            </div>

            <div class="info-section">
                <div class="section-title">👤 INFORMATIONS PERSONNELLES</div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Nom complet :</div>
                        <div class="info-value">{{ strtoupper($student->last_name) }} {{ ucwords($student->first_name) }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date de naissance :</div>
                        <div class="info-value">{{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : 'Non renseignée' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Lieu de naissance :</div>
                        <div class="info-value">{{ $student->place_of_birth ?? 'Non renseigné' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Sexe :</div>
                        <div class="info-value">{{ $student->gender == 'M' ? 'Masculin' : ($student->gender == 'F' ? 'Féminin' : 'Non renseigné') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nationalité :</div>
                        <div class="info-value">{{ $student->nationality ?? 'Camerounaise' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Téléphone :</div>
                        <div class="info-value">{{ $student->phone ?? 'Non renseigné' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email :</div>
                        <div class="info-value">{{ $student->email }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Adresse :</div>
                        <div class="info-value">{{ $student->address ?? 'Non renseignée' }}</div>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <div class="section-title">🏫 INFORMATIONS ACADÉMIQUES</div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">École :</div>
                        <div class="info-value">{{ $school->name }} ({{ $school->sigle }})</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Département :</div>
                        <div class="info-value">{{ $department->name }} ({{ $department->code }})</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Filière :</div>
                        <div class="info-value">{{ $filiere->name }} ({{ $filiere->code }})</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Durée :</div>
                        <div class="info-value">{{ $filiere->duration_years }} an(s)</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date d'inscription :</div>
                        <div class="info-value">{{ $student->enrollment_date->format('d/m/Y') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Année académique :</div>
                        <div class="info-value">{{ $student->academic_year }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="payment-info">
        <div class="payment-title">💰 INFORMATIONS DE PAIEMENT</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Frais d'inscription :</div>
                <div class="info-value">{{ number_format($filiere->enrollment_fee, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="info-row">
                <div class="info-label">Frais de scolarité :</div>
                <div class="info-value">{{ number_format($filiere->tuition_fee, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
        <div class="payment-amount">
            TOTAL : {{ number_format($filiere->enrollment_fee + $filiere->tuition_fee, 0, ',', ' ') }} FCFA
        </div>
    </div>

    <div class="qr-section">
        <div class="qr-code">
            QR CODE<br>
            {{ $qr_code }}
        </div>
        <div style="font-size: 10px; color: #666;">
            Code QR pour vérification d'authenticité
        </div>
    </div>

    <div class="instructions">
        <div class="instructions-title">📋 INSTRUCTIONS IMPORTANTES</div>
        <ul>
            <li>Cette fiche doit être présentée lors de toute démarche administrative</li>
            <li>Collez votre photo d'identité récente (4x5 cm) dans l'espace prévu</li>
            <li>Conservez précieusement cette fiche pendant toute la durée de vos études</li>
            <li>En cas de perte, une demande de duplicata sera facturée 5 000 FCFA</li>
            <li>Cette fiche n'est valide qu'après paiement complet des frais de scolarité</li>
        </ul>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Signature de l'étudiant</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Cachet et signature de l'administration</div>
        </div>
    </div>

    <div class="footer">
        <div class="cameroon-colors"></div>
        <p>
            <strong>SGEE Cameroun</strong> - Système de Gestion des Examens d'Entrée<br>
            République du Cameroun - Ensemble pour l'excellence académique<br>
            Document généré le {{ $generated_at }} - Numéro : {{ $student->student_number }}
        </p>
        <p style="font-size: 8px; margin-top: 10px;">
            Ce document est officiel et ne peut être reproduit sans autorisation.<br>
            Pour vérification d'authenticité, contactez : verification@sgee-cameroun.cm
        </p>
    </div>
</body>
</html>