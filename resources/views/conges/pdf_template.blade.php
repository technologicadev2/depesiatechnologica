<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Demande de Congé</title>
    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding-bottom: 60px;
            /* Espace pour le pied de page */
        }

        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 80px;
            margin-right: 20px;
        }

        .company-info {
            font-size: 12px;
            line-height: 1.5;
            text-align: center;
            position: fixed;
            bottom: 10mm;
            width: 100%;
        }

        .company-info p {
            margin: 2px 0;
        }

        .title-section {
            margin-bottom: 20px;
        }

        .title-section h1 {
            font-size: 20px;
            margin: 0;
        }

        .title-section p {
            font-size: 12px;
            margin: 2px 0;
        }

        .content {
            font-size: 14px;
            line-height: 1.6;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .content th,
        .content td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .content th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .content tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .signature {
            margin-top: 20px;
            text-align: right;
        }

        .signature img {
            max-width: 200px;
        }
    </style>
</head>

<body>
    <div class="header">
  @if($companySettings && $companySettings->logo)
            <img src="{{ public_path('storage/' . $companySettings->logo) }}" alt="Logo">
        @else
            <img src="{{ public_path('assets/img/favicon/anassi2.jpg') }}" alt="Logo par défaut">
        @endif
    </div>
    <div class="title-section">
        <h1>Demande de Congé</h1>
        <p>Date d'approbation: {{ $date_approbation }}</p>
    </div>
    <div class="content">
        <table>
            <tr>
                <th>Champ</th>
                <th>Valeur</th>
            </tr>
            <tr>
                <td>Nom</td>
                <td>{{ $nom }}</td>
            </tr>
            <tr>
                <td>Prénom</td>
                <td>{{ $prenom }}</td>
            </tr>
            <tr>
                <td>Matricule</td>
                <td>{{ $matricule }}</td>
            </tr>
            <tr>
                <td>Date de début</td>
                <td>{{ $date_debut }}</td>
            </tr>
             <tr>
                <td>Date de fin</td>
                <td>{{ $date_fin }}</td>
            </tr>
            <tr>    
                <td>Nombre de jours</td>
                <td>{{ $nombre_jours }}</td>
            </tr>
            <tr>
                <td>Raison</td>
                <td>{{ $conge->raison ?? 'Aucune raison enregistrée' }}</td>
            </tr>
            <tr>
                <td>Jours restants</td>
                <td>{{ $n_jours_reste  }}</td>
            </tr>
        </table>
        <div class="signature">
            <p><strong>Signature</strong></p>
            <img src="{{ $signature_path }}" alt="Signature">
        </div>
    </div>
    <!-- Replace static company info with dynamic data from company_settings -->
    <div class="company-info">
        <p>Siège social: {{ $companySettings->address ?? 'Non spécifié' }} | Capital:
            {{ $companySettings->capital ? number_format($companySettings->capital, 2, ',', ' ') . ' MAD' : 'Non spécifié' }}
            | Tél: {{ $companySettings->phone_number ?? 'Non spécifié' }}</p>
        <p>R.C.: {{ $companySettings->commercial_register ?? 'Non spécifié' }} | CNSS:
            {{ $companySettings->cnss_number ?? 'Non spécifié' }} | IF:
            {{ $companySettings->tax_id ?? 'Non spécifié' }} | TP:
            {{ $companySettings->patent_number ?? 'Non spécifié' }} | ICE:
            {{ $companySettings->ice ?? 'Non spécifié' }}</p>
        <p>C.B.: {{ $companySettings->account_number ?? 'Non spécifié' }},
            {{ $companySettings->bank_name ?? 'Non spécifié' }} | Email:
            {{ $companySettings->email ?? 'Non spécifié' }}</p>
    </div>
</body>

</html>
