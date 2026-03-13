<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation d'inscription - SGEE</title>
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
        .header {
            background: linear-gradient(135deg, #00b894, #fdcb6e, #e17055);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header .flag {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .success-badge {
            background: #00b894;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
            font-size: 18px;
            font-weight: bold;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #00b894;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .reference {
            background: #e17055;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            font-weight: bold;
            font-size: 16px;
            margin: 15px 0;
        }
        .next-steps {
            background: #fdcb6e;
            color: #333;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .next-steps h3 {
            margin-top: 0;
            color: #333;
        }
        .next-steps ul {
            margin: 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin-bottom: 8px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
            color: #666;
        }
        .contact-info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .header, .content {
                padding: 20px;
            }
            .info-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="flag">🇨🇲</div>
        <h1>SGEE - Cameroun</h1>
        <p>Système de Gestion des Examens et Évaluations</p>
    </div>

    <div class="content">
        <div class="success-badge">
            ✅ INSCRIPTION CONFIRMÉE AUTOMATIQUEMENT
        </div>

        <h2>Bonjour {{ $candidate_name }},</h2>

        <p>Félicitations ! Votre inscription au concours <strong>{{ $exam_title }}</strong> de <strong>{{ $school_name }} ({{ $school_sigle }})</strong> a été <strong>validée automatiquement</strong> par notre système.</p>

        <div class="reference">
            📋 Référence : {{ $reference }}
        </div>

        <div class="info-box">
            <h3>📋 Détails de votre inscription</h3>
            <div class="info-row">
                <span class="info-label">École :</span>
                <span class="info-value">{{ $school_name }} ({{ $school_sigle }})</span>
            </div>
            <div class="info-row">
                <span class="info-label">Concours :</span>
                <span class="info-value">{{ $exam_title }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Filière :</span>
                <span class="info-value">{{ $filiere_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Date d'inscription :</span>
                <span class="info-value">{{ $registration_date }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Date d'examen :</span>
                <span class="info-value">{{ $exam_date }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Statut :</span>
                <span class="info-value" style="color: #00b894; font-weight: bold;">{{ strtoupper($status) }}</span>
            </div>
        </div>

        <div class="next-steps">
            <h3>📋 Prochaines étapes</h3>
            <ul>
                <li><strong>Téléchargez votre fiche d'inscription</strong> (en pièce jointe de cet email)</li>
                <li><strong>Imprimez votre fiche</strong> et conservez-la précieusement</li>
                <li><strong>Préparez vos documents</strong> pour le jour de l'examen</li>
                <li><strong>Consultez régulièrement</strong> votre tableau de bord candidat</li>
                <li><strong>Arrivez à l'heure</strong> le jour de l'examen avec votre fiche et une pièce d'identité</li>
            </ul>
        </div>

        <div class="contact-info">
            <h3>📞 Besoin d'aide ?</h3>
            <p>
                <strong>Support SGEE :</strong> +237 6XX XXX XXX<br>
                <strong>Email :</strong> support@sgee-cameroun.cm<br>
                <strong>Horaires :</strong> Lundi - Vendredi, 8h - 17h
            </p>
        </div>

        <p><strong>Important :</strong> Conservez cet email et votre fiche d'inscription. Ils vous seront demandés le jour de l'examen.</p>

        <p>Bonne chance pour votre préparation !</p>

        <p>
            Cordialement,<br>
            <strong>L'équipe SGEE Cameroun</strong>
        </p>
    </div>

    <div class="footer">
        <p>
            <strong>SGEE - Système de Gestion des Examens et Évaluations</strong><br>
            République du Cameroun 🇨🇲<br>
            Cet email a été généré automatiquement, merci de ne pas y répondre.
        </p>
        <p style="font-size: 12px; color: #999; margin-top: 15px;">
            Email envoyé le {{ date('d/m/Y à H:i') }} (Heure du Cameroun)
        </p>
    </div>
</body>
</html>