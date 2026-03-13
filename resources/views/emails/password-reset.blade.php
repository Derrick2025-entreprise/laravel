<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe - SGEE</title>
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
            font-size: 24px;
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
        .reset-badge {
            background: #fdcb6e;
            color: #333;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
            font-size: 16px;
            font-weight: bold;
        }
        .reset-button {
            display: block;
            width: 80%;
            max-width: 300px;
            margin: 25px auto;
            padding: 15px 25px;
            background: #00b894;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            transition: background 0.3s;
        }
        .reset-button:hover {
            background: #00a085;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #fdcb6e;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .warning-box h4 {
            color: #856404;
            margin: 0 0 10px 0;
        }
        .security-tips {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .security-tips h3 {
            margin-top: 0;
            color: #2c5aa0;
        }
        .security-tips ul {
            margin: 0;
            padding-left: 20px;
        }
        .security-tips li {
            margin-bottom: 8px;
        }
        .token-box {
            background: #f8f9fa;
            border: 2px dashed #6c757d;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            font-family: 'Courier New', monospace;
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
            .reset-button {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="flag">🇨🇲</div>
        <h1>SGEE - Cameroun</h1>
        <p>Réinitialisation de Mot de Passe</p>
    </div>

    <div class="content">
        <div class="reset-badge">
            🔑 DEMANDE DE RÉINITIALISATION DE MOT DE PASSE
        </div>

        <h2>Bonjour {{ $user_name }},</h2>

        <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte SGEE associé à l'adresse email <strong>{{ $user_email }}</strong>.</p>

        <div class="info-box">
            <h3>📋 Détails de la demande</h3>
            <p><strong>Date et heure :</strong> {{ $request_time }}</p>
            <p><strong>Adresse IP :</strong> {{ $request_ip }}</p>
            <p><strong>Expiration :</strong> {{ $expires_at }}</p>
        </div>

        <p>Pour réinitialiser votre mot de passe, cliquez sur le bouton ci-dessous :</p>

        <a href="{{ $reset_url }}" class="reset-button">
            🔑 RÉINITIALISER MON MOT DE PASSE
        </a>

        <div class="warning-box">
            <h4>⚠️ Important :</h4>
            <ul style="margin: 0; color: #856404;">
                <li>Ce lien est valide pendant <strong>1 heure seulement</strong></li>
                <li>Il ne peut être utilisé qu'<strong>une seule fois</strong></li>
                <li>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email</li>
                <li>Votre mot de passe actuel reste inchangé tant que vous n'utilisez pas ce lien</li>
            </ul>
        </div>

        <p>Si le bouton ne fonctionne pas, vous pouvez copier et coller le lien suivant dans votre navigateur :</p>

        <div class="token-box">
            {{ $reset_url }}
        </div>

        <div class="security-tips">
            <h3>🛡️ Conseils pour un mot de passe sécurisé</h3>
            <ul>
                <li><strong>Au moins 8 caractères</strong> avec des majuscules, minuscules, chiffres et symboles</li>
                <li><strong>Évitez les informations personnelles</strong> (nom, date de naissance, etc.)</li>
                <li><strong>N'utilisez pas le même mot de passe</strong> sur plusieurs sites</li>
                <li><strong>Changez régulièrement</strong> vos mots de passe importants</li>
                <li><strong>Utilisez un gestionnaire de mots de passe</strong> si possible</li>
            </ul>
        </div>

        <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <h3>📞 Besoin d'aide ?</h3>
            <p>
                <strong>Support SGEE :</strong> +237 6XX XXX XXX<br>
                <strong>Email :</strong> support@sgee-cameroun.cm<br>
                <strong>Horaires :</strong> Lundi - Vendredi, 8h - 17h
            </p>
        </div>

        <p><strong>Si vous n'avez pas demandé cette réinitialisation :</strong></p>
        <ul>
            <li>Ignorez simplement cet email</li>
            <li>Votre mot de passe reste inchangé</li>
            <li>Contactez-nous si vous recevez plusieurs emails de ce type</li>
        </ul>

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
            Email envoyé le {{ date('d/m/Y à H:i') }} (Heure du Cameroun)<br>
            Token valide jusqu'au {{ $expires_at }}
        </p>
    </div>
</body>
</html>