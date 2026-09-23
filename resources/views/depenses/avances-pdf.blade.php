<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Ordre de Virement des Avances</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            margin: 20px;
        }

        .header {
            text-align: center;
            
        }

        .head {
            text-align: center;
           
            font-weight: bold;
        }

        .ref {
            font-weight: bold;
        }

        .info {
            font-weight: bold;
        }

        .table-rib {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
        }

        .table-rib td {
            border: 1px solid black;
            text-align: center;
            padding: 5px;
            width: 20px;
        }

        .table-rib .label {
            width: 80px;
            text-align: left;
            font-weight: bold;
        }

        .signatures {
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 11pt;
        }

        .signature-left {
            text-align: left;
        }

        .signature-right {
            text-align: right;
        }

        .company-info {
            font-size: 9pt;
            text-align: center;
            border-top: 1px solid black;
            padding-top: 8px;
            margin-top: 30px;
            line-height: 1.2;
        }

        .company-info p {
            margin: 2px 0;
        }

        .logo {
            width: 100px;
            height: auto;
            
        }

        .benef-table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }

        .benef-table th,
        .benef-table td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        .rib-error {
            color: red;
            font-weight: bold;
        }

        @media print {
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}

.checkbox {
    display: inline-block;
    width: 15px;
    height: 15px;
    border: 2px solid black;
    text-align: center;
    line-height: 13px;
    font-size: 12px;
    font-weight: bold;
    vertical-align: middle;
}
    </style>
</head>

<body>
    <div class="header">
        @if ($company->logo)
            <img src="{{ public_path('storage/logos/' . $company->logo) }}" alt="Logo" class="logo">
        @else
            <img src="{{ public_path('assets/img/favicon/anassi2.jpg') }}" alt="Logo" class="logo">
        @endif
        <h2>SOCIETE {{ $company->nom_etreprise ?? '' }}</h2>
    </div>
    <div class="head">
        <p>Monsieur le directeur</p>
        <p>Du Crédit Agricole</p>
    </div>
    <div class="ref">
        <p>REF : {{ $ref }}</p>
        <p>Objet : Ordre de Virement des Avances</p>

<!-- Après la ligne Objet -->
<p style="margin: 8px 0; font-weight: bold;">
    <strong>Type de virement :</strong>&nbsp;&nbsp;
    
    <span style="margin-right: 25px;">
        <span style="
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 2px solid black;
            text-align: center;
            line-height: 13px;
            font-size: 12px;
            font-weight: bold;
            vertical-align: middle;
        ">{!! $type === 'instantane' ? '&#10003;' : '' !!}</span>
        &nbsp;Virement Instantané
    </span>
    
    <span>
        <span style="
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 2px solid black;
            text-align: center;
            line-height: 13px;
            font-size: 12px;
            font-weight: bold;
            vertical-align: middle;
        ">{!! $type !== 'instantane' ? '&#10003;' : '' !!}</span>
        &nbsp;Virement Normal
    </span>
</p>

<p>Nous avons l'honneur de vous demander de procéder au virement 
    <strong>{{ $type === 'instantane' ? 'INSTANTANÉ' : 'NORMAL' }}</strong> 
    par le débit de notre compte...</p>
        <p>Monsieur le Directeur</p>
    </div>
    <p>Nous avons l'honneur de vous demander de procéder au virement par le débit de notre compte, dont l'identité
        bancaire est définie par :</p>

    @php
        $ribEmetteur = $company->account_number ?? '';
        $bankCodes = [
            '007' => 'Attijariwafa Bank',
            '011' => 'Bank of Africa (BMCE)',
            '013' => 'BMCI',
            '021' => 'Credit du Maroc',
            '022' => 'Societe Generale Marocaine de Banques (SGMB)',
            '157' => 'Banque Centrale Populaire (BCP)',
            '225' => 'Credit Agricole du Maroc',
            '230' => 'CIH',
        ];
        function formatRib($rib)
        {
            $rib = preg_replace('/\D/', '', $rib);
            if (strlen($rib) != 24) {
                return ['error' => 'RIB invalide'];
            }
            return [
                'bank' => str_split(substr($rib, 0, 3)),
                'ville' => str_split(substr($rib, 3, 3)),
                'compte' => str_split(substr($rib, 6, 16)),
                'cle' => str_split(substr($rib, 22, 2)),
            ];
        }
        $ribEmetteurFormatted = formatRib($ribEmetteur);


         // Ajouter à la fin du @php, avant la fermeture :
    $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
              'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept',
              'dix-huit', 'dix-neuf'];
    $tens  = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante',
              'soixante', 'quatre-vingt', 'quatre-vingt'];

    $convertGroup = function($n) use ($units, $tens, &$convertGroup) {
        $result = '';
        if ($n >= 100) {
            $centaines = intdiv($n, 100);
            $reste = $n % 100;
            $result .= $centaines == 1 ? 'cent' : $units[$centaines] . ' cent';
            if ($reste == 0 && $centaines > 1) $result .= 's';
            if ($reste > 0) $result .= ' ' . $convertGroup($reste);
            return $result;
        }
        if ($n >= 20) {
            $d = intdiv($n, 10);
            $u = $n % 10;
            if ($d == 7 || $d == 9) {
                $result .= $tens[$d] . '-' . $units[10 + $u];
            } elseif ($d == 8) {
                $result .= 'quatre-vingt';
                if ($u > 0) $result .= '-' . $units[$u];
                else $result .= 's';
            } else {
                $result .= $tens[$d];
                if ($u == 1) $result .= ' et un';
                elseif ($u > 0) $result .= '-' . $units[$u];
            }
            return $result;
        }
        return $units[$n];
    };

    $nombreEnLettres = function($nombre) use ($units, $convertGroup) {
        $nombre = round($nombre, 2);
        $entier = intval($nombre);
        $centimes = round(($nombre - $entier) * 100);
        $milliers = intdiv($entier, 1000);
        $reste = $entier % 1000;

        $lettres = '';
        if ($milliers > 0) {
            $lettres .= $milliers == 1 ? 'mille' : $convertGroup($milliers) . ' mille';
            if ($reste > 0) $lettres .= ' ';
        }
        if ($reste > 0) $lettres .= $convertGroup($reste);
        if ($entier == 0) $lettres = 'zéro';

        $lettres .= ' dirham' . ($entier > 1 ? 's' : '');
        if ($centimes > 0) {
            $lettres .= ' et ' . $convertGroup($centimes) . ' centime' . ($centimes > 1 ? 's' : '');
        }
        return ucfirst($lettres);
    };
    @endphp

    <table class="table-rib">
        <tr>
            <td class="label">Code Banque</td>
            <td class="label">Code Ville</td>
            <td class="label">N° Compte</td>
            <td class="label">Clé RIB</td>
        </tr>
        <tr>
            <td>
                @foreach ($ribEmetteurFormatted['bank'] as $digit)
                    {{ $digit }}
                @endforeach
            </td>
            <td>
                @foreach ($ribEmetteurFormatted['ville'] as $digit)
                    {{ $digit }}
                @endforeach
            </td>
            <td>
                @foreach ($ribEmetteurFormatted['compte'] as $digit)
                    {{ $digit }}
                @endforeach
            </td>
            <td>
                @foreach ($ribEmetteurFormatted['cle'] as $digit)
                    {{ $digit }}
                @endforeach
            </td>
        </tr>
    </table>

    <table class="benef-table">
        <tr>
            <th>BENEFICIAIRE</th>
            <th>RIB</th>
            <th>Montant</th>
            <th>MOTIF</th>
        </tr>
        @foreach ($avances as $avance)
            @php
                $ribDest = $avance->employee->rib ?? null;
                $name = strtoupper($avance->employee->nom . ' ' . $avance->employee->prenom ?? '');
                $montant = number_format($avance->montant, 2, '.', '') . ' DHS';
                $montantEnLettres = $nombreEnLettres($avance->montant);
                $motif = $avance->description ?? 'Avance sur Salaire Mois ' . $avance->mois_depenses;
            @endphp
            <tr>
                <td style="font-size: 9pt; ">{{ $name }}</td>
                <td>
                    @if ($ribDest)
                        {{ $ribDest }}
                    @else
                        <span class="rib-error">RIB manquant pour {{ $name }}</span>
                    @endif
                </td>
                <td>
    {{ $montant }}<br>
    <em style="font-size: 9pt; color: #333;">{{ $montantEnLettres }}</em>
</td>
                <td>{{ $motif }}</td>
            </tr>
        @endforeach
    </table>

    <p>Au titre du paiement des Avances</p>

    <p>Veuillez agréer, Monsieur le Directeur, l'expression de nos salutations distinguées.</p>

    <p>BERKANE LE {{ $date_virement }}</p>
<table style="width: 100%; margin-top: 20px; font-weight: bold; font-size: 11pt; margin-bottom: 40px;">
    <tr>
        <td style="text-align: left; width: 50%;">
            <p style="margin: 2px 0;">Signé par :</p>
            <p style="margin: 2px 0;"><strong>{{ $company->director_name ?? 'N/A' }}</strong></p>
        </td>
        <td style="text-align: right; width: 50%;">
            <p style="margin: 2px 0;">Signé par :</p>
            <p style="margin: 2px 0;"><strong>{{ $company->gerant ?? 'N/A' }}</strong></p>
        </td>
    </tr>
</table>

    <div class="company-info"  >
        <p>Siège social : {{ $company->address ?? '' }} Capital : {{ number_format($company->capital ?? 0, 2) }}DH Tél
            : {{ $company->phone_number ?? '' }}</p>
        <p>R.C {{ $company->commercial_register ?? '' }}. Fax: {{ $company->fax ?? '' }}. CNSS :
            {{ $company->cnss_number ?? '' }}. IF : {{ $company->tax_id ?? '' }}. TP :
            {{ $company->patent_number ?? '' }} ICE : {{ $company->ice ?? '' }}</p>
        <p>C.B : {{ $company->account_number ?? '' }} {{ $company->bank_name ?? '' }}. Email :
            {{ $company->email ?? '' }}</p>
    </div>
</body>

</html>
