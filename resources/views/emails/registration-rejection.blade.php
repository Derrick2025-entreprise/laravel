<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription rejetée - SGEE</title>
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
        .rejection-badge {
            background: #e17055;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
            font-size: 18px;
            font-weight: bold;
        }
        .error-box {
            background: #fff5f5;
            border-left: 4px solid #e17055;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .error-list {
            margin: 0;
            padding-left: 20px;
        }
        .error-list li {
            margin-bottom: 8px;
            color: #c53030;
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
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .contact-info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
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
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .header, .content {
                padding: 20px;
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
        <div class="rejection-badge">
            ❌ INSCRIPTION REJETÉE AUTOMATIQUEMENT
        </div>

        <h2>Bonjour {{ $candidate_name }},</h2>

        <p>Nous regrettons de vous informer que votre inscription au concours <strong>{{ $exam_title }}</strong> de <strong>{{ $school_name }}</strong> a été <strong>rejetée automatiquement</strong> par notre système de validation.</p>

        <div class="reference">
            📋 Référence : {{ $reference }}
        </div>

        <div class="error-box">
            <h3>❌ Raisons du rejet</h3>
            <p>Votre inscription a été rejetée pour les raisons suivantes :</p>
            <ul class="error-list">
                @foreach($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>

        <div class="next-steps">
            <h3>🔄 Que faire maintenant ?</h3>
            <ul>
                <li><strong>Corrigez les erreurs mentionnées</strong> ci-dessus</li>
                <li><strong>Vérifiez vos informations personnelles</strong> (nom, prénom, téléphone, etc.)</li>
                <li><strong>Assurez-vous que tous les champs obligatoires</strong> sont correctement remplis</li>
                <li><strong>Soumettez une nouvelle inscription</strong> avec les informations corrigées</li>
                <li><strong>Contactez notre support</strong> si vous avez besoin d'aide</li>
            </ul>
        </div>

        <div class="info-box">
            <h3>ℹ️ Informations importantes</h3>
            <ul>
                <li>Vous pouvez soumettre une nouvelle inscription à tout moment</li>
                <li>Assurez-vous de respecter les critères d'éligibilité</li>
                <li>Vérifiez que vos documents sont conformes aux exigences</li>
                <li>La validation est automatique et immédiate</li>
            </ul>
        </div>

        <div class="contact-info">
            <h3>📞 Besoin d'aide ?</h3>
            <p>
                <strong>Support SGEE :</strong> +237 6XX XXX XXX<br>
                <strong>Email :</strong> support@sgee-cameroun.cm<br>
                <strong>Horaires :</strong> Lundi - Vendredi, 8h - 17h
            </p>
            <p><em>Notre équipe est là pour vous aider à corriger votre dossier.</em></p>
        </div>

        <p>N'hésitez pas à nous contacter si vous avez des questions concernant les raisons du rejet ou si vous avez besoin d'assistance pour votre nouvelle inscription.</p>

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