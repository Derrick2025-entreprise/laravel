<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Étudiants - {{ $filiere->name }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #6f42c1;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #6f42c1;
            margin-bottom: 5px;
        }
        .subtitle {
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .document-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-top: 10px;
        }
        .info-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #495057;
            padding: 3px 15px 3px 0;
            width: 30%;
        }
        .info-value {
            display: table-cell;
            color: #212529;
            padding: 3px 0;
        }
        .stats-section {
            margin-bottom: 20px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .stats-row {
            display: table-row;
        }
        .stats-cell {
            display: table-cell;
            text-align: center;
            padding: 10px;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        .stats-number {
            font-size: 18px;
            font-weight: bold;
            color: #6f42c1;
        }
        .stats-label {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .students-table th {
            background: #6f42c1;
            color: white;
            padding: 8px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #5a32a3;
        }
        .students-table td {
            padding: 6px 4px;
            border: 1px solid #dee2e6;
            font-size: 9px;
            vertical-align: top;
        }
        .students-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
        }
        .status-enrolled {
            background: #d4edda;
            color: #155724;
        }
        .status-suspended {
            background: #f8d7da;
            color: #721c24;
        }
        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-partial {
            background: #fff3cd;
            color: #856404;
        }
        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 8px;
            color: #6c757d;
        }
        .cameroon-colors {
            background: linear-gradient(to right, #009639 33%, #ce1126 33%, #ce1126 66%, #fcd116 66%);
            height: 3px;
            margin: 10px 0;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🎓 SGEE CAMEROUN</div>
        <div class="subtitle">République du Cameroun - Paix, Travail, Patrie</div>
        <div class="subtitle">Système de Gestion des Examens d'Entrée</div>
        <div class="cameroon-colors"></div>
        <div class="document-title">LISTE DES ÉTUDIANTS PAR FILIÈRE</div>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">École :</div>
                <div class="info-value">{{ $school->name }} ({{ $school->sigle }})</div>
            </div>
            <div class="info-row">
                <div class="info-label">Département :</div>
                <div class="info-value">{{ $department->name }} ({{ $department->code }})</div>
            </div>
            <div class="info-row">
                <div class="info-label">Filière :</div>
                <div class="info-value">{{ $filiere->name }} ({{ $filiere->code }})</div>
            </div>
            <div class="info-row">
                <div class="info-label">Capacité :</div>
                <div class="info-value">{{ number_format($filiere->capacity) }} places</div>
            </div>
            <div class="info-row">
                <div class="info-label">Durée :</div>
                <div class="info-value">{{ $filiere->duration_years }} an(s)</div>
            </div>
            <div class="info-row">
                <div class="info-label">Frais total :</div>
                <div class="info-value">{{ number_format($filiere->enrollment_fee + $filiere->tuition_fee, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>

    <div class="stats-section">
        <div class="stats-grid">
            <div class="stats-row">
                <div class="stats-cell">
                    <div class="stats-number">{{ $total_students }}</div>
                    <div class="stats-label">Total étudiants</div>
                </div>
                <div class="stats-cell">
                    <div class="stats-number">{{ $stats['enrolled'] }}</div>
                    <div class="stats-label">Inscrits actifs</div>
                </div>
                <div class="stats-cell">
                    <div class="stats-number">{{ $stats['paid'] }}</div>
                    <div class="stats-label">Paiements complets</div>
                </div>
                <div class="stats-cell">
                    <div class="stats-number">{{ $stats['partial'] }}</div>
                    <div class="stats-label">Paiements partiels</div>
                </div>
                <div class="stats-cell">
                    <div class="stats-number">{{ $stats['unpaid'] }}</div>
                    <div class="stats-label">Non payés</div>
                </div>
            </div>
        </div>
    </div>

    <table class="students-table">
        <thead>
            <tr>
                <th style="width: 12%;">N° Étudiant</th>
                <th style="width: 20%;">Nom Complet</th>
                <th style="width: 18%;">Email</th>
                <th style="width: 12%;">Téléphone</th>
                <th style="width: 8%;">Sexe</th>
                <th style="width: 10%;">Date Inscription</th>
                <th style="width: 8%;">Statut</th>
                <th style="width: 12%;">Paiement</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
            <tr>
                <td>{{ $student->student_number }}</td>
                <td>{{ $student->last_name }} {{ $student->first_name }}</td>
                <td>{{ $student->email }}</td>
                <td>{{ $student->phone ?? '-' }}</td>
                <td>
                    @if($student->gender === 'M')
                        M
                    @elseif($student->gender === 'F')
                        F
                    @else
                        -
                    @endif
                </td>
                <td>{{ $student->enrollment_date->format('d/m/Y') }}</td>
                <td>
                    <span class="status-badge status-{{ $student->status }}">
                        @switch($student->status)
                            @case('enrolled')
                                Inscrit
                                @break
                            @case('suspended')
                                Suspendu
                                @break
                            @case('graduated')
                                Diplômé
                                @break
                            @case('dropped')
                                Abandonné
                                @break
                            @default
                                {{ ucfirst($student->status) }}
                        @endswitch
                    </span>
                </td>
                <td>
                    <span class="status-badge status-{{ $student->payment_status }}">
                        @switch($student->payment_status)
                            @case('paid')
                                Payé
                                @break
                            @case('partial')
                                Partiel
                                @break
                            @case('unpaid')
                                Non payé
                                @break
                            @default
                                {{ ucfirst($student->payment_status) }}
                        @endswitch
                    </span>
                    <br>
                    <small>{{ number_format($student->paid_amount, 0, ',', ' ') }} FCFA</small>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($students->count() === 0)
    <div style="text-align: center; padding: 40px; color: #666;">
        <p style="font-size: 14px;">Aucun étudiant inscrit dans cette filière</p>
    </div>
    @endif

    <div class="footer">
        <div class="cameroon-colors"></div>
        <p>
            <strong>SGEE Cameroun</strong> - Système de Gestion des Examens d'Entrée<br>
            République du Cameroun - Ensemble pour l'excellence académique<br>
            Document généré le {{ $generated_at }}
        </p>
        <p style="font-size: 7px; margin-top: 10px;">
            Ce document est confidentiel et ne peut être reproduit sans autorisation.<br>
            Total des étudiants listés : {{ $total_students }} - 
            Taux de remplissage : {{ $filiere->capacity > 0 ? round(($total_students / $filiere->capacity) * 100, 1) : 0 }}%
        </p>
    </div>
</body>
</html>