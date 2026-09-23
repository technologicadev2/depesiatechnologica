<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande de résiliation - Contrat d'assurance</title>
    <style>
        @page { 
            margin: 1.2cm; 
            size: A4 portrait;
        }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11.5pt; 
            line-height: 1.5; 
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            position: relative;
        }
        .logo {
            max-width: 140px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        .title { 
            color: #c8102e; 
            font-size: 17pt; 
            font-weight: bold; 
            margin: 0;
        }
        .sender { 
            float: left; 
            width: 45%; 
            line-height: 1.35;
            font-size: 11pt;
        }
        .recipient { 
            float: right; 
            width: 45%; 
            text-align: right; 
            line-height: 1.35;
            font-size: 11pt;
        }
        .clear { clear: both; }
        .date { 
            text-align: right; 
            margin: 18px 0 15px 0; 
            font-size: 11pt;
        }
        .content { 
            margin-top: 20px; 
            text-align: justify; 
        }
        .content p { 
            margin-bottom: 12px; 
        }

        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            page-break-inside: avoid;
        }
        .signature { 
            text-align: center; 
            width: 48%; 
        }
        .stamp { 
            text-align: center; 
            width: 48%; 
        }
        .stamp img {
            max-width: 155px;
            max-height: 155px;
            opacity: 0.93;
        }
    </style>
</head>
<body>
    <!-- ENTÊTE avec Logo -->
    <div class="header">
        @if(isset($logoPath) && file_exists($logoPath))
            <img src="{{ $logoPath }}" class="logo" alt="Logo de l'entreprise">
        @endif
        <h1 class="title">Demande de résiliation  d’assurance</h1>
    </div>

    <div class="sender">
        <strong>{{ $companySettings->nom_etreprise ?? 'Votre Société' }}</strong><br>
        {{ $companySettings->address ?? 'Adresse' }}<br>
        {{ $companySettings->ville ?? '' }}<br>
        Tél : {{ $companySettings->phone_number ?? '' }}
    </div>

    <div class="recipient">
        <strong>{{ $insuranceCompany }}</strong><br>
        {{ $insuranceAddress }}<br>
        {{ $companySettings->ville ?? '' }}
    </div>

    <div class="clear"></div>

    <div class="date">
        {{ now()->format('d/m/Y') }}<br>
        <strong>Lettre recommandée avec accusé de réception</strong>
    </div>

    <p><strong>Objet :</strong> Demande de résiliation.<br>
       <strong>Réf :</strong> Police N° <strong>{{ $vehicle->matricule ?? '………………' }}</strong> – Véhicule {{ $vehicle->marque ?? '' }}</p>

    <div class="content">
        <p>Madame, Monsieur,</p>
        <p>Par la présente demande, nous vous informons que nous souhaitons mettre un terme à notre assurance portant les références <strong>({{ $vehicle->matricule ?? '' }})</strong> et qui date du <strong>{{ $vehicle->assurance_expires_at ? $vehicle->assurance_expires_at->format('d/m/Y') : '………………' }}</strong>.</p>
        <p>Dans ces conditions, veuillez noter notre respect du délai de préavis de <strong>90 jours</strong> auquel nous sommes tenu(es) en vertu de l’article 6 de la loi 17-99.</p>
        <p>Nous vous prions d'agréer, Madame, Monsieur, l'expression de nos salutations distinguées.</p>
    </div>

    <!-- Signature + Cachet -->
    <div class="signature-section">
        <div class="signature">
            <p style="margin-bottom: 40px;">Signature</p>
            <p><strong>{{ $companySettings->gerant ?? 'Le Gérant' }}</strong></p>
            <p>{{ $companySettings->nom_etreprise ?? '' }}</p>
        </div>

        <div class="stamp">
            @if(isset($stampPath) && file_exists($stampPath))
                <img src="{{ $stampPath }}" alt="Cachet de l'entreprise">
            @else
                <p style="color: #777; font-style: italic; border: 2px dashed #ccc; padding: 15px;">
                    Cachet de l'entreprise
                </p>
            @endif
        </div>
    </div>
</body>
</html>