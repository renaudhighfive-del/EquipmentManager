<!DOCTYPE html>
<html>
<head>
    <title>Rapport Dashboard - {{ $date }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
        }
        .header h1 {
            color: #3b82f6;
            margin: 0;
        }
        .header p {
            color: #666;
            margin: 5px 0 0;
        }
        .info {
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .stat-card .label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #3b82f6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #e2e8f0;
            padding: 10px;
            text-align: left;
        }
        table th {
            background: #f8fafc;
            font-weight: bold;
        }
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .alert-danger {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .alert-warning {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Rapport Dashboard</h1>
        <p>Généré le {{ $date }} par {{ $user->name }}</p>
    </div>

    <div class="info">
        <p><strong>Utilisateur :</strong> {{ $user->name }}</p>
        <p><strong>Rôle :</strong> {{ ucfirst($user->role) }}</p>
        <p><strong>Email :</strong> {{ $user->email }}</p>
    </div>

    @if($user->role !== 'agent')
        <!-- Statistiques par état -->
        <div class="section">
            <h2>📦 Statistiques par État</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Total Équipements</div>
                    <div class="value">{{ $stats['total_equipements'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Neuf</div>
                    <div class="value">{{ $stats['stats_etat']['neuf'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">En Service</div>
                    <div class="value">{{ $stats['stats_etat']['en_service'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">En Panne</div>
                    <div class="value">{{ $stats['stats_etat']['en_panne'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">En Maintenance</div>
                    <div class="value">{{ $stats['stats_etat']['en_maintenance'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Réformé</div>
                    <div class="value">{{ $stats['stats_etat']['reforme'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Perdu</div>
                    <div class="value">{{ $stats['stats_etat']['perdu'] }}</div>
                </div>
            </div>
        </div>

        <!-- Statistiques par catégorie -->
        <div class="section">
            <h2>📁 Statistiques par Catégorie</h2>
            <table>
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th>Nombre d'Équipements</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['stats_categorie'] as $cat)
                        <tr>
                            <td>{{ $cat['label'] }}</td>
                            <td>{{ $cat['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Alertes -->
        <div class="section">
            <h2>⚠️ Alertes</h2>
            @if($stats['alerts']['pannes_critiques'] > 0)
                <div class="alert alert-danger">
                    {{ $stats['alerts']['pannes_critiques'] }} panne(s) critique(s) en cours
                </div>
            @endif
            @if($stats['alerts']['maintenances_en_cours'] > 0)
                <div class="alert alert-warning">
                    {{ $stats['alerts']['maintenances_en_cours'] }} maintenance(s) en cours
                </div>
            @endif
        </div>

        <!-- Activité récente -->
        <div class="section">
            <h2>🕐 Activité Récente (10 dernières actions)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Équipement</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['recent_activity'] as $activity)
                        <tr>
                            <td>{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $activity->user->name ?? 'N/A' }}</td>
                            <td>{{ $activity->equipement->marque }} {{ $activity->equipement->modele }}</td>
                            <td>{{ $activity->type }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <!-- Stats Agent -->
        <div class="section">
            <h2>👤 Mes Statistiques</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Mes Équipements</div>
                    <div class="value">{{ $stats['mes_equipements'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Mes Pannes En Cours</div>
                    <div class="value">{{ $stats['mes_pannes'] }}</div>
                </div>
            </div>
        </div>
    @endif

</body>
</html>