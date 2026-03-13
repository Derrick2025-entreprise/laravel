<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification de connexion - SGEE</title>
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
        .security-badge {
            background: #00b894;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
            font-size: 16px;
            font-weight: bold;
        }
        .suspicious-badge {
            background: #e17055;
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
        .suspicious-box {
            background: #fff5f5;
            border-left: 4px solid #e17055;
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
            width: 40%;
        }
        .info-value {
            color: #333;
            width: 60%;
            text-align: right;
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
        .action-buttons {
            text-align: center;
            margin: 25px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
        }
        .btn-primary {
            background: #00b894;
            color: white;
        }
        .btn-danger {
            background: #e17055;
            color: white;
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
            .info-row {
                flex-direction: column;
            }
            .info-label, .info-value {
                width: 100%;
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="flag">🇨🇲</div>
        <h1>SGEE - Cameroun</h1>
        <p>Notification de Sécurité</p>
    </div>

    <div class="content">
        @if($is_suspicious)
            <div class="suspicious-badge">
                ⚠️ CONNEXION SUSPECTE DÉTECTÉE
            </div>
        @else
            <div class="security-badge">
                🔐 NOUVELLE CONNEXION DÉTECTÉE
            </div>
        @endif

        <h2>Bonjour {{ $user_name }},</h2>

        <p>Nous vous informons qu'une nouvelle connexion à votre compte SGEE a été détectée.</p>

        <div class="{{ $is_suspicious ? 'suspicious-box' : 'info-box' }}">
            <h3>📋 Détails de la connexion</h3>
            <div class="info-row">
                <span class="info-label">Date et heure :</span>
                <span class="info-value">{{ $login_time }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Adresse IP :</span>
                <span class="info-value">{{ $login_ip }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Localisation :</span>
                <span class="info-value">{{ $location }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Navigateur :</span>
                <span class="info-value">{{ $device_info['browser'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Système :</span>
                <span class="info-value">{{ $device_info['os'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Appareil :</span>
                <span class="info-value">{{ $device_info['device'] }}</span>
            </div>
        </div>

        @if($is_suspicious)
            <div class="suspicious-box">
                <h3>⚠️ Pourquoi cette connexion est-elle suspecte ?</h3>
                <ul>
                    <li>Nouvelle adresse IP détectée</li>
                    <li>Connexion à une heure inhabituelle</li>
                    <li>Localisation différente de vos connexions habituelles</li>
                </ul>
            </div>

            <div class="action-buttons">
                <a href="#" class="btn btn-primary">✅ C'était moi</a>
                <a href="#" class="btn btn-danger">❌ Ce n'était pas moi</a>
            </div>
        @else
            <p style="color: #00b894; font-weight: bold;">✅ Cette connexion semble normale et sécurisée.</p>
        @endif

        <div class="security-tips">
            <h3>🛡️ Conseils de sécurité</h3>
            <ul>
                <li><strong>Ne partagez jamais</strong> vos identifiants de connexion</li>
                <li><strong>Utilisez un mot de passe fort</strong> et unique pour votre compte SGEE</li>
                <li><strong>Déconnectez-vous</strong> toujours après utilisation sur un ordinateur partagé</li>
                <li><strong>Vérifiez régulièrement</strong> l'activité de votre compte</li>
                <li><strong>Contactez-nous immédiatement</strong> si vous détectez une activité suspecte</li>
            </ul>
        </div>

        @if($is_suspicious)
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h4 style="color: #856404; margin: 0 0 10px 0;">🚨 Si ce n'était pas vous :</h4>
                <ol style="margin: 0; color: #856404;">
                    <li>Changez immédiatement votre mot de passe</li>
                    <li>Vérifiez vos informations personnelles</li>
                    <li>Contactez notre support technique</li>
                    <li>Surveillez votre compte dans les prochains jours</li>
                </ol>
            </div>
        @endif

        <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <h3>📞 Besoin d'aide ?</h3>
            <p>
                <strong>Support SGEE :</strong> +237 6XX XXX XXX<br>
                <strong>Email :</strong> support@sgee-cameroun.cm<br>
                <strong>Horaires :</strong> Lundi - Vendredi, 8h - 17h
            </p>
        </div>

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
            Si vous recevez cet email par erreur, veuillez l'ignorer.
        </p>
    </div>
</body>
</html>