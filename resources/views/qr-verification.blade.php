<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #006400 0%, #228B22 50%, #32CD32 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }

        .header {
            background: linear-gradient(135deg, #006400 0%, #228B22 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .cameroon-flag {
            display: inline-block;
            width: 40px;
            height: 25px;
            background: linear-gradient(to right, #006400 33%, #FFD700 33%, #FFD700 66%, #FF0000 66%);
            margin-bottom: 15px;
            border-radius: 3px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .verification-form {
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #006400;
            box-shadow: 0 0 0 3px rgba(0,100,0,0.1);
        }

        .verify-btn {
            width: 100%;
            background: linear-gradient(135deg, #006400 0%, #228B22 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .verify-btn:hover {
            transform: translateY(-2px);
        }

        .verify-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .result-section {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }

        .result-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .result-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .result-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .result-details {
            font-size: 14px;
            line-height: 1.6;
        }

        .detail-item {
            margin-bottom: 8px;
        }

        .detail-label {
            font-weight: bold;
            color: #666;
        }

        .loading {
            text-align: center;
            padding: 20px;
            display: none;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #006400;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .instructions {
            background: #f8f9fa;
            border-left: 4px solid #006400;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 0 8px 8px 0;
        }

        .instructions h3 {
            color: #006400;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .instructions ul {
            list-style: none;
            padding-left: 0;
        }

        .instructions li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }

        .instructions li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #006400;
            font-weight: bold;
        }

        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
        }

        .footer a {
            color: #006400;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .footer {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <div class="cameroon-flag"></div>
            <h1>SGEE Cameroun</h1>
            <p>Vérification d'Authenticité des Documents</p>
        </div>

        <!-- Contenu principal -->
        <div class="content">
            <!-- Instructions -->
            <div class="instructions">
                <h3>🔍 Comment vérifier votre document ?</h3>
                <ul>
                    <li>Localisez le QR Code sur votre document officiel</li>
                    <li>Saisissez l'ID unique affiché sous le QR Code</li>
                    <li>Cliquez sur "Vérifier" pour valider l'authenticité</li>
                    <li>Consultez les détails de vérification affichés</li>
                </ul>
            </div>

            <!-- Formulaire de vérification -->
            <div class="verification-form">
                <form id="verificationForm">
                    <div class="form-group">
                        <label for="uniqueId" class="form-label">
                            ID de Vérification du Document
                        </label>
                        <input 
                            type="text" 
                            id="uniqueId" 
                            name="unique_id" 
                            class="form-input"
                            placeholder="Ex: SGEE-A1B2C3D4E5F6-1640995200"
                            required
                            maxlength="100"
                        >
                    </div>
                    <button type="submit" class="verify-btn" id="verifyBtn">
                        🔍 Vérifier l'Authenticité
                    </button>
                </form>
            </div>

            <!-- Section de chargement -->
            <div class="loading" id="loadingSection">
                <div class="spinner"></div>
                <p>Vérification en cours...</p>
            </div>

            <!-- Section des résultats -->
            <div class="result-section" id="resultSection">
                <div class="result-title" id="resultTitle"></div>
                <div class="result-details" id="resultDetails"></div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p><strong>SGEE Cameroun</strong> - Système de Gestion des Examens et Enrôlements</p>
            <p>
                <a href="https://sgee-cameroun.cm">www.sgee-cameroun.cm</a> | 
                <a href="mailto:verification@sgee-cameroun.cm">verification@sgee-cameroun.cm</a>
            </p>
            <p>Service de vérification disponible 24h/24, 7j/7</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('verificationForm');
            const uniqueIdInput = document.getElementById('uniqueId');
            const verifyBtn = document.getElementById('verifyBtn');
            const loadingSection = document.getElementById('loadingSection');
            const resultSection = document.getElementById('resultSection');
            const resultTitle = document.getElementById('resultTitle');
            const resultDetails = document.getElementById('resultDetails');

            // Nettoyer l'input automatiquement
            uniqueIdInput.addEventListener('input', function() {
                let value = this.value.toUpperCase();
                // Enlever les caractères non autorisés
                value = value.replace(/[^A-Z0-9\-]/g, '');
                this.value = value;
            });

            // Gérer la soumission du formulaire
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const uniqueId = uniqueIdInput.value.trim();
                
                if (!uniqueId) {
                    showError('Veuillez saisir un ID de vérification');
                    return;
                }

                if (uniqueId.length < 10) {
                    showError('L\'ID de vérification semble trop court');
                    return;
                }

                verifyDocument(uniqueId);
            });

            function verifyDocument(uniqueId) {
                // Afficher le chargement
                showLoading();

                // Faire la requête de vérification
                fetch('/api/verify-qr/check', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        unique_id: uniqueId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    if (data.success) {
                        showSuccess(data);
                    } else {
                        showError(data.message || 'Erreur de vérification');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Erreur:', error);
                    showError('Erreur de connexion. Veuillez réessayer.');
                });
            }

            function showLoading() {
                verifyBtn.disabled = true;
                verifyBtn.textContent = 'Vérification...';
                loadingSection.style.display = 'block';
                resultSection.style.display = 'none';
            }

            function hideLoading() {
                verifyBtn.disabled = false;
                verifyBtn.textContent = '🔍 Vérifier l\'Authenticité';
                loadingSection.style.display = 'none';
            }

            function showSuccess(data) {
                resultSection.className = 'result-section result-success';
                resultSection.style.display = 'block';
                
                resultTitle.textContent = '✅ Document Authentique Vérifié';
                
                let details = '<div class="detail-item"><span class="detail-label">Statut:</span> Document officiel valide</div>';
                
                if (data.data) {
                    if (data.data.document_type) {
                        const typeLabels = {
                            'enrollment': 'Fiche d\'Enrôlement',
                            'payment': 'Quitus de Paiement'
                        };
                        details += `<div class="detail-item"><span class="detail-label">Type:</span> ${typeLabels[data.data.document_type] || data.data.document_type}</div>`;
                    }
                    
                    if (data.data.created_at) {
                        details += `<div class="detail-item"><span class="detail-label">Créé le:</span> ${data.data.created_at}</div>`;
                    }
                    
                    if (data.data.verified_at) {
                        details += `<div class="detail-item"><span class="detail-label">Vérifié le:</span> ${data.data.verified_at}</div>`;
                    }
                    
                    if (data.data.document_data) {
                        const docData = data.data.document_data;
                        if (docData.student_name || docData.full_name) {
                            details += `<div class="detail-item"><span class="detail-label">Nom:</span> ${docData.student_name || docData.full_name}</div>`;
                        }
                        if (docData.school) {
                            details += `<div class="detail-item"><span class="detail-label">École:</span> ${docData.school}</div>`;
                        }
                        if (docData.amount) {
                            details += `<div class="detail-item"><span class="detail-label">Montant:</span> ${docData.amount} FCFA</div>`;
                        }
                    }
                }
                
                details += '<div class="detail-item" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #c3e6cb;"><strong>Ce document est authentique et a été généré par le système officiel SGEE Cameroun.</strong></div>';
                
                resultDetails.innerHTML = details;
            }

            function showError(message) {
                resultSection.className = 'result-section result-error';
                resultSection.style.display = 'block';
                
                resultTitle.textContent = '❌ Vérification Échouée';
                
                let details = `<div class="detail-item">${message}</div>`;
                
                if (message.includes('invalide') || message.includes('expiré')) {
                    details += '<div class="detail-item" style="margin-top: 15px;"><strong>Causes possibles:</strong></div>';
                    details += '<div class="detail-item">• ID de vérification incorrect ou mal saisi</div>';
                    details += '<div class="detail-item">• Document expiré ou invalidé</div>';
                    details += '<div class="detail-item">• Document non officiel ou falsifié</div>';
                }
                
                details += '<div class="detail-item" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f5c6cb;"><strong>Contactez le support SGEE si vous pensez qu\'il y a une erreur.</strong></div>';
                
                resultDetails.innerHTML = details;
            }
        });
    </script>
</body>
</html>