<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Paie</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 5mm;
            padding: 0;
            font-size: 9px;
            line-height: 1.1;
        }

        /* Header Section */
        .bulletin-container {
            border: 2px solid #000;
            width: 100%;
        }

        .header-title {
            background-color: #99ccff;
            color: white;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            padding: 8px;
            border-bottom: 1px solid #000;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .logo-cell {
            display: table-cell;
            width: 120px;
            vertical-align: top;
            border-right: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        .logo-box {
            width: 100px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .logo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .info-cell {
            display: table-cell;
            vertical-align: top;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 9px;
            height: 16px;
            vertical-align: middle;
        }

        .info-label {
            background-color: #E8E8E8;
            font-weight: bold;
            width: 120px;
        }

        .info-value {
            background-color: white;
            width: 150px;
        }

        .info-label2 {
            background-color: #E8E8E8;
            font-weight: bold;
            width: 100px;
        }

        .info-value2 {
            background-color: white;
        }

        /* Payroll Table */
        .payroll-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
        }

        .payroll-table th {
            background-color: #99ccff;
            color: white;
            padding: 3px 3px;
            border: 1px solid #000;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }

        .payroll-table td {
            padding: 2px 5px;
            border-right: 1px solid #000;
            border-left: 1px solid #000;
            border-top: none;
            border-bottom: none;
            font-size: 9px;
            vertical-align: middle;
            height: 14px;
        }

        .payroll-table .td_ls {
            background-color: #99ccff !important;
            border-top: 1px solid #000 !important;
        }

        .payroll-table .td_tr {
            border-top: 1px solid #000 !important;
        }

        .category-row {
            background-color: #D0D0D0;
            font-weight: bold;
            color: #000080;
        }

        .amount-cell {
            text-align: right;
            font-weight: normal;
        }

        .total-row {
            background-color: #F0F0F0;
            font-weight: bold;
        }

        .net-payer-row {
            background-color: #99ccff;
            color: white;
            font-weight: bold;
        }

        .empty-row {
            background-color: #F8F8F8;
            height: 20px;
        }

        .signature-table {
            width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .signature-table td {
            font-size: 10px;
            font-weight: bold;
            padding: 5px;
        }

        .signature-left {
            text-align: left;
            width: 50%;
        }

        .signature-right {
            text-align: right;
            width: 50%;
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
    </style>
</head>
<body>
    <div class="bulletin-container">
        <div class="header-title">BULLETIN DE PAIE</div>
        <div class="header-content">
            <div class="logo-cell">
                <div class="logo-box">
                    @if (!empty($companySettings['logo_path']))
                        <img src="{{ $companySettings['logo_path'] }}" alt="Logo">
                    @else
                        <div>No Logo Available</div>
                    @endif
                </div>
            </div>
            <div class="info-cell">
                <table class="info-table">
                    <tr>
                        <td class="info-label">Nom et Prenom</td>
                        <td class="info-value">{{ $employee['name'] }}{{ $employee['first_name'] }}</td>
                        <td class="info-label2">Période</td>
                        <td class="info-value2">DU: {{ $period['start'] }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">C.I.N</td>
                        <td class="info-value">{{ $employee['cin'] }}</td>
                        <td class="info-label2"> Date d'embauche</td>
                        <td class="info-value2">{{ $employee['hire_date'] }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">N° Matricule CNSS</td>
                        <td class="info-value">{{ $employee['cnss_number'] }}</td>
                        <td class="info-label2">N° ESE</td>
                        <td class="info-value2">{{ $employee['ese_number'] }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Situation familiale</td>
                        <td class="info-value">{{ $employee['marital_status'] }}</td>
                        <td class="info-label2">Nombre d'enfants</td>
                        <td class="info-value2">{{ $employee['children_count'] }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="payroll-section">
        <table class="payroll-table" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th></th>
                    <th>Désignation</th>
                    <th>Base</th>
                    <th>Les Taux</th>
                    <th>ToTal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>=</td>
                    <td>SALAIRE DE BASE</td>
                    <td class="amount-cell">{{ number_format($payroll['base_salary'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell"></td>
                    <td></td>
                </tr>
                <tr> 
                    <td>=</td>
                    <td>JOURS DE TRAVAIL</td>
                    <td class="amount-cell">{{ $payroll['num_jours'] }}</td>
                    <td class="amount-cell"></td>
                    <td></td>
                </tr>
                <tr class="category-row p-2">
                    <td>=</td>
                    <td>SALAIRE DE BASE IMPOSABLE</td>
                    <td class="amount-cell"></td>
                    <td class="amount-cell">{{ number_format($payroll['daily_salary'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell">{{ number_format($payroll['salaired'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>+</td>
                    <td>Jours Fériés</td>
                    <td class="amount-cell">{{ $payroll['joursFeriesCount'] ?? 0 }}</td>
                     <td class="amount-cell">
        @if(($payroll['joursFeriesCount'] ?? 0) > 0 && ($payroll['daily_salary'] ?? 0) > 0)
            {{ number_format($payroll['daily_salary'], 2, ',', ' ') }}
        @else
            0,00
        @endif
    </td>
                    <td class="amount-cell">{{ number_format($payroll['jour_ferie'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>+</td>
                    <td>Jours Fériés Travaillés</td>
                    <td class="amount-cell">{{ $payroll['joursFeriesTravaillesCount'] ?? 0 }}</td>
                    <td class="amount-cell">{{ $payroll['joursFeriesTravaillesCount'] > 0 ? number_format($payroll['daily_salary'] ?? 0, 2, ',', ' ') : '0' }}</td>
                    <td class="amount-cell">{{ number_format($payroll['jours_feries_travailles'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
              <tr>
    <td>+</td>
    <td>Congés à payer</td>
    <td class="amount-cell">{{ $payroll['conges_paye_non_travailles_count'] ?? 0 }}</td>
    <td class="amount-cell">
        {{ ($payroll['conges_paye_non_travailles_count'] ?? 0) > 0
            ? number_format($payroll['daily_salary'] ?? 0, 2, ',', ' ')
            : '0,00' }}
    </td>
    <td class="amount-cell">
        {{ ($payroll['conges_paye_non_travailles'] ?? 0) > 0
            ? number_format($payroll['conges_paye_non_travailles'], 2, ',', ' ')
            : '0,00' }}
    </td>
</tr>
     
                <tr class="category-row">
                    <td></td>
                    <td><strong>TOTAL DES JOURS CALCULES</strong></td>
                    <td class="amount-cell">{{ $payroll['jours_calcules'] ?? $payroll['num_jours'] ?? '-' }}</td>
                    <td class="amount-cell"></td>
                    <td></td>
                </tr>
                <tr>
                    <td>+</td>
                    <td>Heure supplémentaire à 25%</td>
                   <td class="amount-cell">{{ number_format($payroll['heurSuppPresencematin'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell">{{ $payroll['heurSuppPresencematin'] > 0 && isset($payroll['tauxHeureSupp']) ? number_format($payroll['tauxHeureSupp'] , 2, ',', ' ') : '0' }}</td>
                     <td class="amount-cell">{{ $payroll['heurSuppPresencematin'] > 0 ? number_format($payroll['heurSupp25'] ?? 0, 2, ',', ' ') : '0' }}</td>
                </tr>
              <tr>
    <td>+</td>
    <td>Heure supplémentaire à 50%</td>
    <td class="amount-cell">{{ number_format(($payroll['heurSuppPresencematinF'] ?? 0) + ($payroll['heurSuppPresenceNuit'] ?? 0), 2, ',', ' ') }}</td>
    <td class="amount-cell">{{ (($payroll['heurSuppPresencematinF'] ?? 0) + ($payroll['heurSuppPresenceNuit'] ?? 0)) > 0 && isset($payroll['tauxHeureSupp']) ? number_format($payroll['tauxHeureSupp'] , 2, ',', ' ') : '0' }}</td>
    <td class="amount-cell">{{ (($payroll['heurSuppPresencematinF'] ?? 0) + ($payroll['heurSuppPresenceNuit'] ?? 0)) > 0 ? number_format($payroll['heurSupp50'] ?? 0, 2, ',', ' ') : '0' }}</td>
</tr>
               <tr>
    <td>+</td>
    <td>Heure supplémentaire à 100%</td>
    <td class="amount-cell">{{ number_format($payroll['heurSuppPresenceNuitF'] ?? 0, 2, ',', ' ') }}</td>
    <td class="amount-cell">{{ $payroll['heurSuppPresenceNuitF'] > 0 && isset($payroll['tauxHeureSupp']) ? number_format($payroll['tauxHeureSupp'] , 2, ',', ' ') : '0' }}</td>
    <td class="amount-cell">{{ $payroll['heurSuppPresenceNuitF'] > 0 ? number_format($payroll['heurSupp100'] ?? 0, 2, ',', ' ') : '0' }}</td>
</tr>
                <tr class="category-row">
                    <td>=</td>
                    <td><strong>SALAIRE DE BASE IMPOSABLE</strong></td>
                    <td class="amount-cell"></td>
                    <td class="amount-cell"></td>
                    <td class="amount-cell">{{ number_format($payroll['salaireBaseImposable'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>+</td>
                    <td>PRIME D'ANCIENNETÉ</td>
                    <td></td>
                    <td class="amount-cell">{{ $payroll['seniority_rate'] ?? '0%' }}</td>
                    <td class="amount-cell">{{ number_format($payroll['prime_anciennete'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>+</td>
                    <td>Prime de Rendement Journalière</td>
                    <td class="amount-cell">{{ $payroll['base_prime_journaliere'] ?? 0 }}</td>
                    <td class="amount-cell">{{ number_format($payroll['taux_prime_journaliere'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell">{{ number_format($payroll['prime_journaliere'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
              <tr>
    <td>+</td>
    <td>Autres Primes Imposables</td>
    <td class="amount-cell">{{ number_format($payroll['autresPrimesImposables'] ?? 0, 2, ',', ' ') }}</td>
    <td class="amount-cell"></td>
    <td class="amount-cell">{{ number_format($payroll['autresPrimesImposablesCalculated'] ?? 0, 2, ',', ' ') }}</td>
</tr>
               <tr>
                    <td>+</td>
                    <td>Prime de Panier</td>
                    <td class="amount-cell">{{ number_format($payroll['primPanierInput'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell"></td>
                    <td class="amount-cell">{{ number_format($payroll['prime_panier'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                    <tr>
            <td>+</td>
            <td>Prime Représentation</td>
            <td class="amount-cell">{{ number_format($payroll['representation_bonus_input'], 2, ',', ' ') }} </td>
            <td class="amount-cell"></td>
            <td class="amount-cell">{{ number_format($payroll['representation_bonus'], 2, ',', ' ') }} </td>
        </tr>
                    <tr>
                    <td>+</td>
                    <td>Prime de Déplacement</td>
                    <td class="amount-cell">{{ number_format($payroll['PrimeDeplacementInput'] ?? 0, 2, ',', ' ') }} </td>
                    <td class="amount-cell"></td>
                    <td class="amount-cell">{{ number_format($payroll['PrimeDeplacement'] ?? 0, 2, ',', ' ') }} </td>
                </tr>
                <tr>
                    <td>+</td>
                    <td>Prime de Transport</td>
                    <td class="amount-cell">{{ number_format($payroll['ind_transUrbainInput'] ?? 0, 2, ',', ' ') }} </td>
                    <td class="amount-cell"></td>
                    <td class="amount-cell">{{ number_format($payroll['ind_trans_urbain'] ?? 0, 2, ',', ' ') }} </td>
                </tr>
               <tr>
                    <td>+</td>
                    <td>Primes divers</td>
                    <td class="amount-cell">{{ number_format($payroll['primesDiversInput'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell"></td>
                    <td class="amount-cell">{{ number_format($payroll['primesDivers'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                  <tr class="total-row">
                    <td>=</td>
                    <td><strong>SALAIRE BRUT GLOBAL</strong></td>
                    <td></td>
                    <td></td>
                    <td class="amount-cell">
                        <strong>{{ number_format($payroll['salaireBG'] ?? 0, 2, ',', ' ') }}</strong>
                    </td>
                </tr>
                  <tr>
                    <td>-</td>
                    <td>Total des Primes non imposables</td>
                    <td></td>
                    <td class="amount-cell"></td>
                    <td class="amount-cell">{{ number_format($payroll['primNonimposables'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                
                {{-- Congés travaillés uniquement --}}
@if(($payroll['joursCongesAvecPresence'] ?? 0) > 0)
<tr>
    <td>+</td>
    <td>Congés payés (travaillés)</td>
    <td class="amount-cell">{{ $payroll['joursCongesAvecPresence'] ?? 0 }}</td>
    <td class="amount-cell">
        {{ ($payroll['joursCongesAvecPresence'] ?? 0) > 0
            ? number_format($payroll['daily_salary'] ?? 0, 2, ',', ' ')
            : '0,00' }}
    </td>
    <td class="amount-cell">{{ number_format($payroll['congesPayeTravailles'] ?? 0, 2, ',', ' ') }}</td>
</tr>
@endif
                
                <tr class="total-row">
                    <td>=</td>
                    <td><strong>SALAIRE BRUT IMPOSABLE</strong></td>
                    <td></td>
                    <td></td>
                    <td class="amount-cell">
                        <strong>{{ number_format($payroll['gross_salary'] ?? 0, 2, ',', ' ') }}</strong>
                    </td>
                </tr>
                <tr>
                    <td>-</td>
                    <td>COTISATIONS CNSS</td>
                    <td class="amount-cell">{{ number_format($payroll['gross_salary'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell">{{ $payroll['cnss_rate'] ?? '0' }}</td>
                    <td class="amount-cell">{{ number_format($payroll['cnss_deduction'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>-</td>
                    <td>COTISATION AMO</td>
                    <td class="amount-cell">{{ number_format($payroll['gross_salary'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell">{{ $payroll['amo_rate'] ?? '0' }}</td>
                    <td class="amount-cell">{{ number_format($payroll['amo_deduction'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>-</td>
                    <td>COTISATION CMIR</td>
                    <td class="amount-cell">{{ number_format($payroll['gross_salary'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell">{{ $payroll['taux_cimr'] ? sprintf("%.2f%%", $payroll['taux_cimr']) : '0' }}</td>
                    <td class="amount-cell">{{ number_format($payroll['cotisation_cimr'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                        <tr>
                <td>-</td>
                <td>COTISATION Mutuelle</td>
                <td class="amount-cell">{{ number_format($payroll['gross_salary'] ?? 0, 2, ',', ' ') }}</td>
                <td class="amount-cell">{{ $payroll['taux_mutuelle'] ? sprintf("%.2f%%", $payroll['taux_mutuelle']) : '0' }}</td>
                <td class="amount-cell">{{ number_format($payroll['cotisation_mutuelle'] ?? 0, 2, ',', ' ') }}</td>
            </tr>
                 <tr>
                    <td>-</td>
                    <td>INDEMNITÉ DE PERTE D'EMPLOI</td>
                    <td class="amount-cell">{{ number_format($payroll['gross_salary'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell">{{ $payroll['employment_loss_rate'] ?? '0' }}</td>
                    <td class="amount-cell">{{ number_format($payroll['employment_loss_deduction'] ?? 0, 2, ',', ' ') }}</td>
                </tr> 
                <tr>
                    <td>-</td>
                    <td>FRAIS PROFESSIONNELS</td>
                    <td class="amount-cell">{{ number_format($payroll['gross_salary'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="amount-cell">{{ $payroll['professional_tax_rate'] ?? '0,00 %' }}</td>
                    <td class="amount-cell">{{ number_format($payroll['professional_tax_deduction'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr class="category-row">
                    <td>=</td>
                    <td><strong>SALAIRE NET IMPOSABLE</strong></td>
                    <td></td>
                    <td></td>
                    <td class="amount-cell">
                        <strong>{{ number_format($payroll['taxable_salary'] ?? 0, 2, ',', ' ') }}</strong>
                    </td>
                </tr>
                 <tr>
                    <td>-</td>
                    <td>IR BRUT</td>
                    <td class="amount-cell">{{ sprintf("%.2f%%", $payroll['irData'] ?? 0) }}</td>
                    <td class="amount-cell">{{ sprintf("%.2f%%", $payroll['irData'] ?? 0) }}</td>
                    <td class="amount-cell">{{ number_format($payroll['gross_ir'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>-</td>
                    <td>CHARGE DE FAMILLE</td>
                    <td></td>
                    <td></td>
                    <td class="amount-cell">{{ number_format($payroll['charge_familiale'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr class="category-row">
                    <td>=</td>
                    <td><strong>IR NET</strong></td>
                    <td></td>
                    <td></td>
                    <td class="amount-cell">{{ number_format($payroll['net_ir'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>-</td>
                    <td>Avancements de salaire</td>
                    <td></td>
                    <td></td>
                    <td class="amount-cell">{{ number_format($payroll['avancements_sal'] ?? 0, 2, ',', ' ') }}</td>
                    
                </tr>
            
             
              <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>
                        <!-- <strong>{{ number_format($payroll['gross_salary'] ?? 0, 2, ',', ' ') }}</strong> -->
                    </td>
                    <td>
                       <!--  <strong>{{ number_format($payroll['total_deductions'] ?? 0, 2, ',', ' ') }}</strong> -->
                    </td>
                </tr> 
                <tr class="net-payer-row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;"><strong>Net à payer</strong></td>
                    <td class="amount-cell">
                        <strong>{{ number_format($payroll['net_to_pay'] ?? 0, 2, ',', ' ') }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <table class="signature-table">
        <tr>
            <td class="signature-left">Signature: {{ $companySettings['nom_etreprise'] }}</td>
            <td class="signature-right">Signature du bénéficiaire: {{ $employee['name'] }}{{ $employee['first_name'] }}</td>
        </tr>
    </table>
  <div class="company-info">
        <p>Siège social: {{ $companySettings['address'] }} | Capital: {{ $companySettings['capital'] }} MAD | Tél: {{ $companySettings['phone_number'] }}</p>
        <p>R.C.: {{ $companySettings['commercial_register'] }} | CNSS: {{ $companySettings['cnss_number'] }} | IF: {{ $companySettings['tax_id'] }} | TP: {{ $companySettings['patent_number'] }} | ICE: {{ $companySettings['ice'] }}</p>
        <p>C.B.: {{ $companySettings['account_number'] }} | Email: {{ $companySettings['email'] }}</p>
    </div> 
</body>
</html>