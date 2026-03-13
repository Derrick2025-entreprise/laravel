<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents Officiels SGEE Cameroun</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        
        .email-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #006400 0%, #228B22 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        .header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .cameroon-flag {
            display: inline-block;
            width: 30px;
            height: 20px;
            background: linear-gradient(to right, #006400 33%, #FFD700 33%, #FFD700 66%, #FF0000 66%);
            margin-bottom: 10px;
            border-radius: 3px;
        }
        
        .content {
            padding: 30px 20px;
        }
        
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #006400;
            font-weight: bold;
        }
        
        .message {
            margin-bottom: 25px;
            line-height: 1.8;
        }
        
        .documents-section {
            background: #f8f9fa;
            border-left: 4px solid #006400;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 5px 5px 0;
        }
        
        .documents-title {
            font-weight: bold;
            color: #006400;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .document-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .document-icon {
            width: 40px;
            height: 40px;
            background: #006400;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 15px;
            font-size: 14px;
        }
        
        .document-info {
            flex: 1;
        }
        
        .document-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }
        
        .document-details {
            font-size: 12px;
            color: #666;
        }
        
        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 25px 0;
        }
        
        .security-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .security-item {
            font-size: 13px;
            color: #856404;
            margin-bottom: 5px;
            padding-left: 15px;
            position: relative;
        }
        
        .security-item:before {
            content: "🔒";
            position: absolute;
            left: 0;
        }
        
        .verification-section {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        
        .verification-title {
            font-weight: bold;
            color: #155724;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .verification-url {
            background: white;
            border: 1px solid #c3e6cb;
            border-radius: 3px;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
            color: #155724;
            word-break: break-all;
            margin: 10px 0;
        }
        
        .instructions {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin: 25px 0;
        }
        
        .instructions-title {
            font-weight: bold;
            color: #0c5460;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .instruction-item {
            font-size: 13px;
            color: #0c5460;
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        
        .instruction-item:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        
        .contact-info {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        
        .contact-title {
            font-weight: bold;
            color: #006400;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .contact-item {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .footer {
            background: #006400;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }
        
        .footer p {
            margin: 5px 0;
        }
        
        .footer a {
            color: #FFD700;
            text-decoration: none;
        }
        
        .timestamp {
            font-size: 11px;
            color: #666;
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- En-tête -->
        <div class="header">
            <div class="cameroon-flag"></div>
            <h1>SGEE Cameroun</h1>
            <p>Système de Gestion des Examens et Enrôlements</p>
        </div>
        
        <!-- Contenu principal -->
        <div class="content">
            <div class="greeting">
                Bonjour {{ $student_name }},
            </div>
            
            <div class="message">
                Nous avons le plaisir de vous transmettre vos documents officiels générés par le système SGEE Cameroun. 
                Ces documents ont été créés automatiquement suite à vos démarches d'inscription et de paiement.
            </div>
            
            <!-- Section documents -->
            <div class="documents-section">
                <div class="documents-title">📄 Documents en pièce jointe</div>
                
                @foreach($documents as $document)
                <div class="document-item">
                    <div class="document-icon">
                        @if(str_contains($document['file_name'], 'enrollment'))
                            📋
                        @elseif(str_contains($document['file_name'], 'payment'))
                            💰
                        @else
                            📄
                        @endif
                    </div>
                    <div class="document-info">
                        <div class="document-name">{{ $document['file_name'] }}</div>
                        <div class="document-details">
                            @if(isset($document['document_number']))
                                N° Document: {{ $document['document_number'] }} | 
                            @endif
                            @if(isset($document['qr_unique_id']))
                                ID Vérification: {{ $document['qr_unique_id'] }}
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Avis de sécurité -->
            <div class="security-notice">
                <div class="security-title">🔐 Sécurité et Authenticité</div>
                <div class="security-item">Chaque document contient un QR Code unique pour vérification</div>
                <div class="security-item">Les documents sont protégés par signature numérique</div>
                <div class="security-item">Filigrane de sécurité intégré pour éviter la falsification</div>
                <div class="security-item">Numéros de série uniques et traçables</div>
            </div>
            
            <!-- Section vérification -->
            <div class="verification-section">
                <div class="verification-title">🔍 Vérification d'Authenticité</div>
                <p>Pour vérifier l'authenticité de vos documents, scannez le QR Code présent sur chaque document ou visitez :</p>
                <div class="verification-url">https://sgee-cameroun.cm/verification</div>
                <p><small>Saisissez l'ID unique présent sur votre document</small></p>
            </div>
            
            <!-- Instructions -->
            <div class="instructions">
                <div class="instructions-title">📋 Instructions importantes</div>
                <div class="instruction-item">Conservez précieusement ces documents officiels</div>
                <div class="instruction-item">Présentez-les lors de vos démarches administratives</div>
                <div class="instruction-item">Vérifiez que tous vos documents sont bien joints à cet email</div>
                <div class="instruction-item">En cas de problème, contactez immédiatement notre support</div>
                <div class="instruction-item">Ne partagez pas vos documents avec des tiers non autorisés</div>
            </div>
            
            <!-- Informations de contact -->
            <div class="contact-info">
                <div class="contact-title">📞 Besoin d'aide ?</div>
                <div class="contact-item"><strong>Email :</strong> support@sgee-cameroun.cm</div>
                <div class="contact-item"><strong>Téléphone :</strong> +237 222 223 400</div>
                <div class="contact-item"><strong>Site web :</strong> www.sgee-cameroun.cm</div>
                <div class="contact-item"><strong>Horaires :</strong> Lun-Ven 7h30-15h30</div>
            </div>
            
            <div class="message">
                Nous vous remercions de votre confiance et vous souhaitons plein succès dans vos démarches académiques.
            </div>
            
            <div class="message">
                <strong>L'équipe SGEE Cameroun</strong><br>
                <em>Excellence • Transparence • Innovation</em>
            </div>
            
            <!-- Horodatage -->
            <div class="timestamp">
                Email généré automatiquement le {{ $sent_at }}<br>
                Système SGEE Cameroun - Ne pas répondre à cet email
            </div>
        </div>
        
        <!-- Pied de page -->
        <div class="footer">
            <p><strong>SGEE Cameroun</strong> - Système de Gestion des Examens et Enrôlements</p>
            <p>République du Cameroun - Ministère de l'Enseignement Supérieur</p>
            <p>
                <a href="https://sgee-cameroun.cm">www.sgee-cameroun.cm</a> | 
                <a href="mailto:info@sgee-cameroun.cm">info@sgee-cameroun.cm</a>
            </p>
            <p><small>Cet email et ses pièces jointes sont confidentiels et destinés uniquement au destinataire mentionné.</small></p>
        </div>
    </div>
</body>
</html>