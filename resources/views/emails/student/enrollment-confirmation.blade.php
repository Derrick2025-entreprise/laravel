<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation d'inscription - SGEE Cameroun</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #6f42c1;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #6f42c1;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            font-size: 16px;
        }
        .welcome {
            background: linear-gradient(135deg, #6f42c1, #8b5cf6);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        .welcome h1 {
            margin: 0;
            font-size: 24px;
        }
        .info-section {
            margin-bottom: 25px;
        }
        .info-title {
            font-size: 18px;
            font-weight: bold;
            color: #6f42c1;
            margin-bottom: 15px;
            border-left: 4px solid #6f42c1;
            padding-left: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid #6f42c1;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .info-value {
            color: #212529;
            font-size: 16px;
        }
        .payment-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .payment-title {
            color: #856404;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .payment-amount {
            font-size: 24px;
            font-weight: bold;
            color: #6f42c1;
            text-align: center;
            margin: 15px 0;
        }
        .next-steps {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .next-steps h3 {
            color: #0c5460;
            margin-top: 0;
        }
        .steps-list {
            list-style: none;
            padding: 0;
        }
        .steps-list li {
            padding: 8px 0;
            border-bottom: 1px solid #bee5eb;
        }
        .steps-list li:last-child {
            border-bottom: none;
        }
        .step-number {
            background: #6f42c1;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
            font-size: 12px;
        }
        .contact-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
        }
        .contact-title {
            color: #6f42c1;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        .cameroon-flag {
            display: inline-block;
            margin: 0 5px;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🎓 SGEE CAMEROUN</div>
            <div class="subtitle">Système de Gestion des Examens d'Entrée</div>
        </div>

        <div class="welcome">
            <h1>🎉 Félicitations {{ $student->first_name }} !</h1>
            <p>Votre inscription a été confirmée avec succès</p>
        </div>

        <div class="info-section">
            <div class="info-title">📋 Informations de l'étudiant</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Numéro d'étudiant</div>
                    <div class="info-value">{{ $student_number }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nom complet</div>
                    <div class="info-value">{{ $student->first_name }} {{ $student->last_name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date d'inscription</div>
                    <div class="info-value">{{ $enrollment_date }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Année académique</div>
                    <div class="info-value">{{ $academic_year }}</div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-title">🏫 Informations académiques</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">École</div>
                    <div class="info-value">{{ $school->name }} ({{ $school->sigle }})</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Département</div>
                    <div class="info-value">{{ $department->name }}</div>
                </div>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <div class="info-label">Filière</div>
                    <div class="info-value">{{ $filiere->name }} ({{ $filiere->code }})</div>
                </div>
            </div>
        </div>

        <div class="payment-info">
            <div class="payment-title">💰 Informations de paiement</div>
            <p>Montant total des frais de scolarité :</p>
            <div class="payment-amount">{{ number_format($total_fees, 0, ',', ' ') }} FCFA</div>
            <p style="text-align: center; margin: 0;">
                <small>Frais d'inscription : {{ number_format($filiere->enrollment_fee, 0, ',', ' ') }} FCFA</small><br>
                <small>Frais de scolarité : {{ number_format($filiere->tuition_fee, 0, ',', ' ') }} FCFA</small>
            </p>
        </div>

        <div class="next-steps">
            <h3>📝 Prochaines étapes</h3>
            <ul class="steps-list">
                <li>
                    <span class="step-number">1</span>
                    Connectez-vous à votre espace étudiant pour compléter votre profil
                </li>
                <li>
                    <span class="step-number">2</span>
                    Téléchargez et imprimez votre fiche d'inscription (en pièce jointe)
                </li>
                <li>
                    <span class="step-number">3</span>
                    Uploadez vos documents requis (acte de naissance, photo, relevés de notes)
                </li>
                <li>
                    <span class="step-number">4</span>
                    Effectuez le paiement des frais et uploadez le reçu
                </li>
                <li>
                    <span class="step-number">5</span>
                    Attendez la validation de vos documents et paiements
                </li>
            </ul>
        </div>

        <div class="contact-info">
            <div class="contact-title">📞 Besoin d'aide ?</div>
            <p>Notre équipe support est là pour vous accompagner :</p>
            <p>
                📧 Email : <strong>support@sgee-cameroun.cm</strong><br>
                📱 Téléphone : <strong>+237 6XX XXX XXX</strong><br>
                🕒 Horaires : Lundi - Vendredi, 8h - 17h
            </p>
        </div>

        <div class="footer">
            <p>
                <span class="cameroon-flag">🇨🇲</span>
                <strong>SGEE Cameroun</strong> - Système de Gestion des Examens d'Entrée
                <span class="cameroon-flag">🇨🇲</span>
            </p>
            <p>
                Ensemble pour l'excellence académique au Cameroun<br>
                <small>Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.</small>
            </p>
        </div>
    </div>
</body>
</html>