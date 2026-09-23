<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Ordre de Virement</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            margin: 0;
            padding: 15px;
            color: black;
        }

        .header {
    text-align: center;
    margin-bottom: 8px;
}
.header h3 {
    margin: 4px 0;
    font-size: 12pt;
    font-weight: bold;
}

       .head {
    text-align: center;
    margin: 6px 0;
    font-weight: bold;
}

      .head p {
    margin: 1px 0;
    font-size: 10pt;
}

        .ref {
            margin: 15px 0;
            text-align: left;
        }

        .ref p {
            margin: 3px 0;
            font-weight: bold;
            font-size: 11pt;
        }

        .content-text {
            margin: 12px 0;
            text-align: justify;
            font-size: 11pt;
        }

        .table-rib {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
            font-size: 10pt;
        }

        .table-rib td {
            border: 1px solid black;
            text-align: center;
            padding: 4px 2px;
            font-weight: normal;
        }

        .table-rib .label {
            font-weight: bold;
            background-color: white;
            padding: 4px;
        }

        .table-rib .digit-cell {
            width: auto;
            min-width: 12px;
            font-size: 10pt;
        }

        .amount-text {
            margin: 12px 0;
            
        }

        .motif-text {
            margin: 8px 0;
            
        }

        .account-text {
            margin: 8px 0;
           
        }

        .bank-text {
            margin: 8px 0;
            
        }

        .closing-text {
            margin: 12px 0;
            
        }

        .date-text {
            
            font-size: 11pt;
            text-align: right;
            font-weight: bold;
        }

        .signatures {
            
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
    width: 50px;
    height: auto;
    margin-bottom: 4px;
}

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
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
  
    <h3>SOCIETE {{ $company->nom_etreprise ?? 'N/A' }}</h3>
</div>

    <div class="head">
        <p>Monsieur le directeur</p>
        <p>Du Crédit Agricole</p>
    </div>

  <div class="ref">
    <p><strong>REF :</strong> {{ $ordre->reference }}</p>
    <p><strong>Objet :</strong> Ordre de virement</p>
    <p style="margin: 8px 0; font-weight: bold;">
        <strong>Type de virement :</strong>&nbsp;&nbsp;
        <span style="margin-right: 20px;">
            <span style="
                display: inline-block;
                width: 14px;
                height: 14px;
                border: 2px solid black;
                text-align: center;
                line-height: 13px;
                font-size: 11px;
                font-weight: bold;
                vertical-align: middle;
            ">{!! $isInstantane ? '&#10003;' : '' !!}</span>
            &nbsp;Virement Instantané
        </span>
        <span>
            <span style="
                display: inline-block;
                width: 14px;
                height: 14px;
                border: 2px solid black;
                text-align: center;
                line-height: 13px;
                font-size: 11px;
                font-weight: bold;
                vertical-align: middle;
            ">{!! !$isInstantane ? '&#10003;' : '' !!}</span>
            &nbsp;Virement Normal
        </span>
    </p>
    <p><strong>Monsieur le Directeur</strong></p>
</div>

    <p class="content-text">Nous avons l'honneur de vous demander de procéder au virement par le débit de notre compte,
        dont l'identité bancaire est définie par :</p>

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
                return ['error' => 'RIB invalide', 'bank' => [], 'ville' => [], 'compte' => [], 'cle' => []];
            }
            $bank = substr($rib, 0, 3);
            $ville = substr($rib, 3, 3);
            $compte = substr($rib, 6, 16);
            $cle = substr($rib, 22, 2);
            return [
                'bank' => str_split($bank),
                'ville' => str_split($ville),
                'compte' => str_split($compte),
                'cle' => str_split($cle),
            ];
        }
        $ribEmetteurFormatted = formatRib($ribEmetteur);
        $ribDestFormatted = formatRib($ordre->rib_virement  ?? '');
        $bankDestCode = $ordre->rib_destinataire ? substr($ordre->rib_destinataire, 0, 3) : '';
        $bankDestName = $bankCodes[$bankDestCode] ?? 'Inconnue';
        $motifText =
            $ordre->motif ??
            ($ordre->type_destinataire === 'societe'
                ? 'Au titre du paiement des Factures' . ($ordre->reference ? ' (Réf: ' . $ordre->reference . ')' : '')
                : 'Au titre du paiement du salaire');
        $destinataireName = $ordre->entite->raison_sociale ?? ($ordre->name ?? 'N/A');


        // ✅ AJOUTEZ ICI — après toutes les variables existantes :
  $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
          'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept',
          'dix-huit', 'dix-neuf'];
$tens  = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante',
          'soixante', 'quatre-vingt', 'quatre-vingt'];

$convertGroup = null;
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
            $sub = 10 + $u;
            $result .= $tens[$d] . '-' . $units[$sub];
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

$montantEnLettres = $nombreEnLettres($ordre->montant);
    @endphp

    @if ($ribEmetteurFormatted['error'] ?? false)
        <p class="content-text"><strong>Erreur :</strong> RIB émetteur invalide.</p>
    @else
        <table class="table-rib">
            <tr>
                <td class="label">Code<br>Banque</td>
                <td class="label">Code<br>Ville</td>
                <td class="label">N° Compte</td>
                <td class="label">Clé<br>RIB</td>
            </tr>
            <tr>
                <td class="digit-cell">
                    @foreach ($ribEmetteurFormatted['bank'] as $digit)
                        {{ $digit }}
                    @endforeach
                </td>
                <td class="digit-cell">
                    @foreach ($ribEmetteurFormatted['ville'] as $digit)
                        {{ $digit }}
                    @endforeach
                </td>
                <td class="digit-cell">
                    @foreach ($ribEmetteurFormatted['compte'] as $digit)
                        {{ $digit }}
                    @endforeach
                </td>
                <td class="digit-cell">
                    @foreach ($ribEmetteurFormatted['cle'] as $digit)
                        {{ $digit }}
                    @endforeach
                </td>
            </tr>
        </table>
    @endif

    <p class="amount-text">
    Le montant en chifre : {{ number_format($ordre->montant, 2, '.', '') }} 
    <br>
    Le montant en Lettre :  <strong>{{ $montantEnLettres }}</strong>
</p>

    <p class="motif-text">{{ $motifText }}</p>

    <p class="account-text">Au compte bancaire <strong>{{ strtoupper($destinataireName) }}</strong></p>

    @if ($ribDestFormatted['error'] ?? false)
        <p class="content-text"><strong>Erreur :</strong> RIB destinataire invalide.</p>
    @else
        <table class="table-rib">
            <tr>
                <td class="label">Code<br>Banque</td>
                <td class="label">Code<br>Ville</td>
                <td class="label">N° Compte</td>
                <td class="label">Clé<br>RIB</td>
            </tr>
            <tr>
                <td class="digit-cell">
                    @foreach ($ribDestFormatted['bank'] as $digit)
                        {{ $digit }}
                    @endforeach
                </td>
                <td class="digit-cell">
                    @foreach ($ribDestFormatted['ville'] as $digit)
                        {{ $digit }}
                    @endforeach
                </td>
                <td class="digit-cell">
                    @foreach ($ribDestFormatted['compte'] as $digit)
                        {{ $digit }}
                    @endforeach
                </td>
                <td class="digit-cell">
                    @foreach ($ribDestFormatted['cle'] as $digit)
                        {{ $digit }}
                    @endforeach
                </td>
            </tr>
        </table>
    @endif

    <p class="bank-text">Domicilié chez : <strong>{{ $bankDestName }}</strong></p>

    <p class="closing-text">Veuillez agréer, Monsieur le Directeur, l'expression de nos salutations distinguées.</p>

    <p class="date-text">BERKANE LE {{ date('d/m/Y', strtotime($ordre->date_virement)) }}</p>

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

<div class="company-info" style="margin-top: 60px;">
    <p>Siège social : {{ $company->address ?? 'N/A' }} Capital : {{ number_format($company->capital ?? 0, 2) }}DH
        Tél : {{ $company->phone_number ?? 'N/A' }}</p>
    <p>R.C {{ $company->commercial_register ?? 'N/A' }}. Fax: {{ $company->fax ?? 'N/A' }}. CNSS :
        {{ $company->cnss_number ?? 'N/A' }}. IF : {{ $company->tax_id ?? 'N/A' }}. TP :
        {{ $company->patent_number ?? 'N/A' }} ICE : {{ $company->ice ?? 'N/A' }}</p>
    <p>C.B : {{ $company->account_number ?? 'N/A' }} {{ $company->bank_name ?? 'N/A' }}. Email :
        {{ $company->email ?? 'N/A' }}</p>
</div>
</body>
<footer></footer>

</html>
