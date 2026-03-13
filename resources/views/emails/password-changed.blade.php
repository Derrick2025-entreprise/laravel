<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe modifié - SGEE</title>
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
        .success-badge {
            background: #00b894;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
            font-size: 16px;
            font-weight: bold;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #00b894;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
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
        <p>Confirmation de Sécurité</p>
    </div>

    <div class="content">
        <div class="success-badge">
            ✅ MOT DE PASSE MODIFIÉ AVEC SUCCÈS
        </div>

        <h2>Bonjour {{ $user_name }},</h2>

        <p>Nous vous confirmons que le mot de passe de votre compte SGEE a été modifié avec succès.</p>

        <div class="info-box">
            <h3>📋 Détails de la modification</h3>
            <p><strong>Date et heure :</strong> {{ $change_time }}</p>
            <p><strong>Adresse IP :</strong> {{ $change_ip }}</p>
            <p><strong>Compte concerné :</strong> {{ $user_email }}</p>
        </div>

        <p style="color: #00b894; font-weight: bold;">✅ Votre compte est maintenant sécurisé avec votre nouveau mot de passe.</p>

        <div class="security-tips">
            <h3>🛡️ Prochaines étapes recommandées</h3>
            <ul>
                <li><strong>Connectez-vous</strong> avec votre nouveau mot de passe pour vérifier qu'il fonctionne</li>
                <li><strong>Mettez à jour</strong> vos gestionnaires de mots de passe si vous en utilisez</li>
                <li><strong>Déconnectez-vous</strong> de tous les autres appareils si nécessaire</li>
                <li><strong>Vérifiez</strong> l'activité récente de votre compte</li>
            </ul>
        </div>

        <div class="warning-box">
            <h4>🚨 Si vous n'avez pas effectué cette modification :</h4>
            <ol style="margin: 0; color: #856404;">
                <li><strong>Contactez immédiatement</strong> notre support technique</li>
                <li><strong>Changez à nouveau</strong> votre mot de passe</li>
                <li><strong>Vérifiez</strong> vos informations personnelles</li>
                <li><strong>Surveillez</strong> votre compte dans les prochains jours</li>
            </ol>
        </div>

        <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <h3>📞 Support technique</h3>
            <p>
                <strong>Téléphone :</strong> +237 6XX XXX XXX<br>
                <strong>Email :</strong> support@sgee-cameroun.cm<br>
                <strong>Horaires :</strong> Lundi - Vendredi, 8h - 17h
            </p>
            <p><em>Notre équipe est disponible pour vous aider en cas de problème.</em></p>
        </div>

        <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h4 style="color: #155724; margin: 0 0 10px 0;">💡 Rappel de sécurité :</h4>
            <ul style="margin: 0; color: #155724;">
                <li>Ne partagez jamais votre mot de passe</li>
                <li>Utilisez un mot de passe unique pour SGEE</li>
                <li>Déconnectez-vous après chaque session</li>
                <li>Signalez toute activité suspecte</li>
            </ul>
        </div>

        <p>Merci de faire confiance au système SGEE pour vos examens et évaluations.</p>

        <p>
            Cordialement,<br>
            <strong>L'équipe Sécurité SGEE Cameroun</strong>
        </p>
    </div>

    <div class="footer">
        <p>
            <strong>SGEE - Système de Gestion des Examens et Évaluations</strong><br>
            République du Cameroun 🇨🇲<br>
            Cet email a été généré automatiquement pour votre sécurité.
        </p>
        <p style="font-size: 12px; color: #999; margin-top: 15px;">
            Email envoyé le {{ date('d/m/Y à H:i') }} (Heure du Cameroun)<br>
            Si vous avez des questions, contactez notre support technique.
        </p>
    </div>
</body>
</html>