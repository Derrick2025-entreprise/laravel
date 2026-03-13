<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quitus de paiement validé - SGEE Cameroun</title>
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
            border-bottom: 3px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            font-size: 16px;
        }
        .success-banner {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        .success-banner h1 {
            margin: 0;
            font-size: 24px;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .payment-details {
            background: #f8f9fa;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
        }
        .payment-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .payment-title {
            color: #28a745;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .payment-reference {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .payment-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        .payment-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #28a745;
        }
        .payment-label {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .payment-value {
            color: #212529;
            font-size: 16px;
        }
        .amount-highlight {
            text-align: center;
            margin: 25px 0;
        }
        .amount-value {
            font-size: 36px;
            font-weight: bold;
            color: #28a745;
            margin: 10px 0;
        }
        .amount-label {
            color: #666;
            font-size: 14px;
        }
        .student-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .student-info h3 {
            color: #1976d2;
            margin-top: 0;
        }
        .student-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .student-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
        }
        .remaining-balance {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .balance-title {
            color: #856404;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .balance-amount {
            font-size: 24px;
            font-weight: bold;
            color: #856404;
        }
        .validation-info {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .validation-title {
            color: #155724;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .validation-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .validation-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
        }
        .next-steps {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .next-steps h3 {
            color: #6f42c1;
            margin-top: 0;
        }
        .steps-list {
            list-style: none;
            padding: 0;
        }
        .steps-list li {
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .steps-list li:last-child {
            border-bottom: none;
        }
        .step-number {
            background: #28a745;
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
            color: #28a745;
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
            .payment-info, .student-grid, .validation-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">💳 SGEE CAMEROUN</div>
            <div class="subtitle">Système de Gestion des Examens d'Entrée</div>
        </div>

        <div class="success-banner">
            <div class="success-icon">✅</div>
            <h1>Paiement Validé avec Succès !</h1>
            <p>Votre quitus de paiement est maintenant disponible</p>
        </div>

        <div class="payment-details">
            <div class="payment-header">
                <div class="payment-title">QUITUS DE PAIEMENT OFFICIEL</div>
                <div class="payment-reference">{{ $reference }}</div>
            </div>

            <div class="amount-highlight">
                <div class="amount-label">Montant validé</div>
                <div class="amount-value">{{ $amount }} FCFA</div>
            </div>

            <div class="payment-info">
                <div class="payment-item">
                    <div class="payment-label">Type de paiement</div>
                    <div class="payment-value">
                        @switch($payment->payment_type)
                            @case('enrollment_fee')
                                Frais d'inscription
                                @break
                            @case('tuition_fee')
                                Frais de scolarité
                                @break
                            @default
                                {{ ucfirst($payment->payment_type) }}
                        @endswitch
                    </div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Méthode de paiement</div>
                    <div class="payment-value">
                        @switch($payment->payment_method)
                            @case('bank_transfer')
                                Virement bancaire
                                @break
                            @case('mobile_money')
                                Mobile Money
                                @break
                            @case('cash')
                                Espèces
                                @break
                            @case('check')
                                Chèque
                                @break
                            @default
                                {{ ucfirst($payment->payment_method) }}
                        @endswitch
                    </div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Date de paiement</div>
                    <div class="payment-value">{{ $payment_date }}</div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Date de validation</div>
                    <div class="payment-value">{{ $validated_date }}</div>
                </div>
            </div>
        </div>

        <div class="student-info">
            <h3>👨‍🎓 Informations de l'étudiant</h3>
            <div class="student-grid">
                <div class="student-item">
                    <div class="payment-label">Nom complet</div>
                    <div class="payment-value">{{ $student->first_name }} {{ $student->last_name }}</div>
                </div>
                <div class="student-item">
                    <div class="payment-label">Numéro d'étudiant</div>
                    <div class="payment-value">{{ $student->student_number }}</div>
                </div>
                <div class="student-item">
                    <div class="payment-label">École</div>
                    <div class="payment-value">{{ $school->name }}</div>
                </div>
                <div class="student-item">
                    <div class="payment-label">Filière</div>
                    <div class="payment-value">{{ $filiere->name }}</div>
                </div>
            </div>
        </div>

        @if($remaining_amount > 0)
        <div class="remaining-balance">
            <div class="balance-title">⚠️ Solde restant à payer</div>
            <div class="balance-amount">{{ number_format($remaining_amount, 0, ',', ' ') }} FCFA</div>
            <p style="margin: 10px 0 0 0; font-size: 14px;">
                Veuillez effectuer le paiement du solde restant pour finaliser votre inscription.
            </p>
        </div>
        @else
        <div class="remaining-balance" style="background: #d4edda; border-color: #c3e6cb;">
            <div class="balance-title" style="color: #155724;">🎉 Paiement complet</div>
            <div class="balance-amount" style="color: #155724;">Tous les frais ont été payés !</div>
            <p style="margin: 10px 0 0 0; font-size: 14px; color: #155724;">
                Félicitations ! Votre inscription est maintenant complète.
            </p>
        </div>
        @endif

        <div class="validation-info">
            <div class="validation-title">✅ Informations de validation</div>
            <div class="validation-details">
                <div class="validation-item">
                    <div class="payment-label">Validé le</div>
                    <div class="payment-value">{{ $validated_date }}</div>
                </div>
                <div class="validation-item">
                    <div class="payment-label">Statut</div>
                    <div class="payment-value" style="color: #28a745; font-weight: bold;">VALIDÉ</div>
                </div>
            </div>
        </div>

        <div class="next-steps">
            <h3>📝 Prochaines étapes</h3>
            <ul class="steps-list">
                @if($remaining_amount > 0)
                <li>
                    <span class="step-number">1</span>
                    Effectuez le paiement du solde restant ({{ number_format($remaining_amount, 0, ',', ' ') }} FCFA)
                </li>
                <li>
                    <span class="step-number">2</span>
                    Téléchargez et conservez ce quitus de paiement
                </li>
                @else
                <li>
                    <span class="step-number">1</span>
                    Téléchargez et conservez ce quitus de paiement
                </li>
                <li>
                    <span class="step-number">2</span>
                    Votre inscription est maintenant complète
                </li>
                @endif
                <li>
                    <span class="step-number">{{ $remaining_amount > 0 ? '3' : '3' }}</span>
                    Consultez régulièrement votre espace étudiant pour les mises à jour
                </li>
                <li>
                    <span class="step-number">{{ $remaining_amount > 0 ? '4' : '4' }}</span>
                    Préparez-vous pour le début des cours selon le calendrier académique
                </li>
            </ul>
        </div>

        <div class="contact-info">
            <div class="contact-title">📞 Service comptabilité</div>
            <p>Pour toute question concernant vos paiements :</p>
            <p>
                📧 Email : <strong>comptabilite@sgee-cameroun.cm</strong><br>
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
                <small>Ce quitus de paiement fait foi et doit être conservé précieusement.</small>
            </p>
        </div>
    </div>
</body>
</html>