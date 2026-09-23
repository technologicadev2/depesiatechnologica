<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande de virements ponctuels - {{ ucfirst($month) }} {{ $year }}</title>
    <style>
        @page { size: A4; margin: 25mm 20mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
        }

        /* Page 1 - Lettre */
        .lettre-page {
            text-align: justify;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo {
            max-width: 180px;
            margin-bottom: 10px;
        }
        .date-lieu {
            text-align: right;
            margin-bottom: 30px;
            font-size: 11pt;
        }
        .objet {
            font-weight: bold;
            text-align: center;
            margin: 35px 0 40px;
            text-transform: uppercase;
            font-size: 14pt;
        }
        .signature {
            margin-top: 70px;
            text-align: left;
            font-style: italic;
        }
        .bank {
            
            text-align: right;
           
        }
        .texte1{
           max-width: 200px;
            margin-bottom: 10px;
        }

        /* Saut de page forcé */
        .tableau-page {
            page-break-before: always;
            margin-top: 20px;
        }

        /* Page 2 - Tableau */
        h1 {
            text-align: center;
            background: #0d6efd;
            color: white;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 16pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        th, td {
            border: 1px solid #666;
            padding: 8px;
            text-align: center;
        }
        th {
            background: #e9ecef;
            font-weight: bold;
        }
        .total {
            background: #d4edda;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 10mm;
            width: 100%;
            text-align: center;
            font-size: 9pt;
            color: #555;
        }
    </style>
</head>
<body>

    <!-- ==================== PAGE 1 : LETTRE OFFICIELLE ==================== -->
    <div class="lettre-page">

        <div class="header">
            <!-- Logo comme dans ton bulletin de paie -->
            @if (!empty($companySettings['logo_path']))
                <img src="{{ $companySettings['logo_path'] }}" alt="Logo Groupe Scolaire Benalmanara" class="logo">
            @else
                <div style="width:180px; height:80px; background:#eee; margin:0 auto; display:flex; align-items:center; justify-content:center;">
                    Logo non disponible
                </div>
            @endif


        </div>

     <div class="texte1">
    @if (!empty($companySettings->stamp_path))
        <img src="{{ $companySettings->stamp_path }}" alt="Cachet officiel" class="logo">
    @else
        <div style="width:180px; height:80px; background:#fff3cd; margin:0 auto; 
                    display:flex; align-items:center; justify-content:center; 
                    font-size:11px; color:#856404; border:1px solid #ffeeba; padding:10px;">
            Cachet introuvable
        </div>
    @endif
</div>
<div class="texte2">
        <p class="bank">
    À l'attention de Monsieur le Directeur<br>
    {{ $companySettings->bank_name  }}
    
        - Agence {{ $companySettings->agency_name }}
   <br>
</p>
</div>

        <div class="objet">
            Objet : Demande pour effectuer des virements ponctuels
        </div>

        <p style="text-indent: 40px;">
           J'ai l'honneur de solliciter de votre haute bienveillance de bien vouloir effectuer les virements ponctuels 
            qui figurent sur le tableau joint à ma demande  de puis le compte du  <strong>{{ $companySettings->nom_etreprise }}</strong> numéro <strong>{{ $companySettings->account_number }}</strong> Aujourd'hui.
        </p>

        <p style="margin-top: 30px;">
            Veuillez agréer, Monsieur le Directeur, l'expression de mes salutations distinguées.
        </p>

        <div class="signature">
            <p>Mr {{ $companySettings->gerant }}</p>
            <p style="margin-top: 5px;">Responsable administratif</p>
        </div>
         <div class="date-lieu">
            {{ $companySettings->ville }}, le {{ now()->format('d/m/Y') }}
        </div>

    </div>

    <!-- ==================== PAGE 2 : TABLEAU DES VIREMENTS ==================== -->
    <div class="tableau-page">

        <h1>LISTE DES VIREMENTS – {{ strtoupper($month) }} {{ $year }}</h1>

        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Mois</th>
                    <th>Salaire net viré</th>
                    <th>RIB / IBAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                <tr>
                    <td style="text-align:left;">{{ $emp->nom }}</td>
                    <td style="text-align:left;">{{ $emp->prenom }}</td>
                    <td>{{ ucfirst($month) }}</td>
                    <td style="text-align:right;">{{ number_format($emp->salaire, 2) }} DH</td>
                    <td style="font-family: monospace; text-align:left;">{{ $emp->rib ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="3" style="text-align:right;">TOTAL VIRÉ</td>
                    <td style="text-align:right;">{{ number_format($total, 2) }} DH</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

      

    </div>

</body>
</html>