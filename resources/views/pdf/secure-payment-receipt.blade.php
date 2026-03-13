<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quitus de Paiement Sécurisé - SGEE Cameroun</title>
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

        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #FFD700;
        }

        .receipt-number {
            font-weight: bold;
            color: #006400;
            font-size: 14px;
        }

        .receipt-date {
            color: #666;
            font-size: 10px;
        }

        .amount-section {
            background: #e8f5e8;
            border: 2px solid #006400;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .amount-title {
            font-size: 14px;
            font-weight: bold;
            color: #006400;
            margin-bottom: 10px;
        }

        .amount-value {
            font-size: 24px;
            font-weight: bold;
            color: #006400;
            margin-bottom: 5px;
        }

        .amount-words {
            font-style: italic;
            color: #666;
            font-size: 12px;
        }

        .payment-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #006400;
        }

        .section-title {
            font-weight: bold;
            color: #006400;
            margin-bottom: 10px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .info-item {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 10px;
        }

        .info-value {
            margin-top: 2px;
            font-size: 11px;
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

        .validation-section {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .validation-title {
            font-weight: bold;
            color: #155724;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .validation-item {
            font-size: 10px;
            color: #155724;
            margin-bottom: 3px;
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

        .status-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }

        .payment-method-badge {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
        }

        .urgent-notice {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 10px;
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
        <h2>QUITUS DE PAIEMENT OFFICIEL</h2>
    </div>

    <!-- Informations du reçu -->
    <div class="receipt-info">
        <div>
            <div class="receipt-number">N° Quitus: {{ $document_number }}</div>
            <div class="receipt-number">Référence: {{ $payment->reference_number }}</div>
        </div>
        <div class="receipt-date">
            Émis le {{ $generated_at }}<br>
            <span class="status-badge">VALIDÉ</span>
        </div>
    </div>

    <!-- Avis important -->
    <div class="urgent-notice">
        <strong>QUITUS OFFICIEL :</strong> Ce document certifie le paiement effectué dans le cadre du SGEE Cameroun. 
        Il fait foi devant toute autorité compétente. Vérifiez son authenticité via le QR Code ci-dessous.
    </div>

    <!-- Section montant -->
    <div class="amount-section">
        <div class="amount-title">MONTANT PAYÉ</div>
        <div class="amount-value">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</div>
        <div class="amount-words">{{ $amount_in_words }}</div>
    </div>

    <!-- Détails du paiement -->
    <div class="payment-details">
        <!-- Informations du payeur -->
        <div class="detail-section">
            <div class="section-title">Informations du Payeur</div>
            <div class="info-item">
                <div class="info-label">Nom complet</div>
                <div class="info-value">{{ $student->full_name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $student->email ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Téléphone</div>
                <div class="info-value">{{ $student->phone ?? 'N/A' }}</div>
            </div>
            @if($school)
            <div class="info-item">
                <div class="info-label">École/Université</div>
                <div class="info-value">{{ $school->name }}</div>
            </div>
            @endif
        </div>

        <!-- Détails du paiement -->
        <div class="detail-section">
            <div class="section-title">Détails du Paiement</div>
            <div class="info-item">
                <div class="info-label">Type de paiement</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Méthode de paiement</div>
                <div class="info-value">
                    <span class="payment-method-badge">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Date de paiement</div>
                <div class="info-value">{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y H:i') : 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Statut</div>
                <div class="info-value">
                    <span class="status-badge">{{ ucfirst($payment->status) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section validation -->
    <div class="validation-section">
        <div class="validation-title">VALIDATION ADMINISTRATIVE</div>
        <div class="validation-item">✓ Paiement vérifié et validé par l'administration</div>
        <div class="validation-item">✓ Montant conforme aux tarifs officiels</div>
        <div class="validation-item">✓ Transaction enregistrée dans le système SGEE</div>
        @if($validator)
        <div class="validation-item">✓ Validé par : {{ $validator->name }}</div>
        @endif
        <div class="validation-item">✓ Document généré automatiquement le {{ $generated_at }}</div>
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
            <p>Scannez ce QR Code ou visitez l'URL ci-dessous pour vérifier l'authenticité de ce quitus :</p>
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
        <div class="security-item">• Référence de paiement traçable : {{ $payment->reference_number }}</div>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                Signature du Payeur
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
        <p>Quitus officiel généré électroniquement - Valeur légale garantie</p>
        <p>Pour toute vérification : www.sgee-cameroun.cm | Email : verification@sgee-cameroun.cm</p>
        <p><em>Ce document est valide uniquement avec le QR Code de vérification ci-dessus</em></p>
        <p><strong>IMPORTANT :</strong> Conservez ce quitus comme preuve de paiement officielle</p>
    </div>
</body>
</html>