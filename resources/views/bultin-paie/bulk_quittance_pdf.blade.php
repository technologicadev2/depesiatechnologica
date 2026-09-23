<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CBS Livré de Paie</title>
    <style>
        @page {
            size: landscape;
            margin: 5mm;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 8px;
            line-height: 1.1;
        }

        .quittance-container {
            border: 2px solid #000;
            width: 100%;
            overflow-x: auto;
        }

        .header-title {
            background-color: #97bee2ff;
            color: white;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            padding: 8px;
            border-bottom: 1px solid #000;
        }

        .table-section {
            margin-top: 10px;
        }

        .payroll-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            table-layout: fixed;
        }

        .payroll-table th {
            background-color: #97bee2ff;
            color: white;
            padding: 4px 2px;
            border: 1px solid #000;
            font-weight: bold;
            text-align: center;
            font-size: 7px;
            vertical-align: bottom;
            word-wrap: break-word;
            height: 80px;
            position: relative;
        }

        .payroll-table th div {
            transform: rotate(-90deg);
            transform-origin: center;
            white-space: nowrap;
            position: absolute;
            bottom: 40px;
            left: 50%;
            margin-left: -50px;
            width: 100px;
            text-align: center;
        }

        .payroll-table td {
            padding: 2px 3px;
            border: 1px solid #000;
            font-size: 7px;
            vertical-align: middle;
            text-align: center;
            word-wrap: break-word;
        }

        .total-row {
            background-color: #97bee2ff;
            color: white;
            font-weight: bold;
        }

        .empty-row td {
            height: 15px;
        }

        .yellow-column {
            background-color: #FFFF00;
            font-weight: bold;
            color: #000;
        }

        .blue-header {
            background-color: #2b70b1ff;
        }

        .company-info {
            font-size: 10px;
            line-height: 1.5;
            text-align: center;
            position: fixed;
            bottom: 10mm;
            width: 100%;
        }
        .company-info p {
            margin: 2px 0;
        }

        /* Colonnes spécifiques */
        .col-mat { width: 3%; }
        .col-nom { width: 8%; }
        .col-date { width: 4%; }
        .col-fonction { width: 4%; }
        .col-situation { width: 4%; }
        .col-enfants { width: 3%; }
        .col-salaire { width: 4%; }
        .col-nbr { width: 3%; }
        .col-heures { width: 3%; }
        .col-taux { width: 3%; }
        .col-montant { width: 4%; }
        .col-prime { width: 3%; }
        .col-prime-split { width: 3%; } /* Increased width from 2% to 3% */
        .col-deduction { width: 3%; }
        .col-net { width: 4%; }

        /* Style pour affichage horizontal dans la ligne de total */
        .prime-anciennete-container {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            min-width: 0; /* Prevent overflow */
            padding: 0 2px;
            box-sizing: border-box;
        }

        .prime-anciennete-value {
            flex: 1;
            text-align: center;
            font-size: 6px; /* Slightly smaller to ensure fit */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="quittance-container">
        <div class="header-title">CBS LIVRÉ DE PAIE MOIS : {{ $data['month'] }} {{ $data['year'] }}</div>

        <div class="table-section">
            <table class="payroll-table">
                <thead>
                    <tr>
                       <!--  <th class="col-mat"><div>Mat N°</div></th> -->
                        <th class="col-nom"><div>NOM ET PRENOMS</div></th>
                        <th class="col-date"><div>Date embauche</div></th>
                        <!-- <th class="col-fonction"><div>Fonction</div></th> -->
                        <th class="col-situation"><div>Situation Familiale</div></th>
                        <th class="col-enfants"><div>Nbr Enfants</div></th>
                        <th class="col-salaire"><div>Salaire de Base</div></th>
                        <th class="col-nbr"><div>Nbre de Jrs</div></th>
                        <th class="col-heures"><div>J.F</div></th>
                        <th class="col-heures"><div>J.C</div></th>
                        <th class="col-heures"><div>J.C.T</div></th>
                        <th class="col-taux"><div>Total Jrs déclare</div></th>
                        <th class="col-taux"><div>Jrs Supp</div></th>
                        <th class="col-taux"><div>HS 25%</div></th>
                        <th class="col-taux"><div>HS 50%</div></th>
                        <th class="col-taux"><div>HS 100%</div></th>
                        <th class="col-prime"><div>Prime déplacement</div></th>
                        <th class="col-prime"><div>Prime Représentation</div></th>
                        <th class="col-prime"><div>Prime Panier</div></th>
                        <th class="col-prime"><div>Prime Transport</div></th>
                        <th class="col-prime"><div>Primes Divers</div></th>
                        <th class="col-prime"><div>Taux CIMR</div></th>
                        <th class="col-montant"><div>Taux Mutuelle</div></th>
                        <th class="col-montant"><div>Avance F</div></th>
                        <th class="col-taux"><div>Taux Jrs</div></th>
                        <th class="col-taux " style=" background-color: #3285d3ff"><div>SBM</div></th>
                        <th class="col-taux " style=" background-color: #3285d3ff"><div>SDBI</div></th>
                        <th class="col-prime" colspan="3"><div>Prime d'ancienneté</div></th>
                        <th class="col-prime"><div>Prime rendement JR</div></th>
                        <th class="col-prime" style=" background-color: #3285d3ff"><div>SBG</div></th>
                        <th class="col-prime"><div>Elements Exonérés</div></th>
                         <th class="col-prime" style=" background-color: #3285d3ff"><div>SBI</div></th>
                        <th class="col-deduction"><div>CNSS PS</div></th>
                        <th class="col-deduction"><div>AMO PS</div></th>
                        <th class="col-deduction"><div>CIMR</div></th>
                        <th class="col-deduction"><div>Assurance Complementaire</div></th>
                        <th class="col-deduction"><div>Taux F.P</div></th>
                        <th class="col-deduction"><div>FP</div></th>
                        <th class="col-deduction"  style=" background-color: #3285d3ff"><div>SNI</div></th>
                        <th class="col-deduction"><div>Taux IR</div></th>
                        <th class="col-deduction"><div>Deduction</div></th>
                        <th class="col-deduction"><div>IR Brut</div></th>
                        <th class="col-deduction"><div>CF</div></th>
                        <th class="col-deduction"><div>IR Net</div></th>
                        <th class="col-net yellow-column"><div>Salaire Net à Payer</div></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['employees'] as $employee)
                        <tr>
                           <!--  <td>{{ $employee['n_matricule_entreprise'] }}</td> -->
                            <td style="text-align: left;">{{ $employee['nom_prenom'] }}</td>
                            <td>{{ $employee['date_embauche'] }}</td>
                           <!--  <td>{{ $employee['function'] }}</td> -->
                            <td>
                            @php
                                $situation = strtolower(trim($employee['situation_familiale'] ?? ''));
                            @endphp
                            {{ $situation === 'marié' || $situation === 'marie' ? 'M' : 'C' }}
                        </td>
                            <td>{{ $employee['nbr_enfants'] ?? '0' }}</td>
                            <td>{{ number_format($employee['salaire_base'], 2) }}</td>
                            <td>{{ $employee['jours_travail'] ?? '26' }}</td>
                            <td>{{ $employee['jours_feries'] ?? '0' }}</td>
                            <td>{{ $employee['congesPayeNonTravaillesCount'] ?? '0' }}</td>
                            <td>{{ $employee['joursCongesAvecPresence'] ?? '0' }}</td>
                            <td>{{  '26' }}</td><!-- $employee['jours_travail'] ?? -->
                            <td>{{ $employee['jours_supp'] ?? '26' }}</td>
                            <td>{{ $employee['hs_25'] ?? '0' }}</td>
                            <td>{{ $employee['hs_50'] ?? '0' }}</td>
                            <td>{{ $employee['hs_100'] ?? '0' }}</td>
                            <td>{{ number_format($employee['prime_deplacement'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['prime_representation'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['prime_panier'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['prime_transport'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['prime_divers'] ?? 0, 2) }}</td>
                            <td>{{ $employee['taux_cimr'] ?? '0%' }}</td>
                            <td>{{ $employee['taux_mutuelle'] ?? '0%' }}</td>
                            <td>{{ number_format($employee['avance'] ?? 0, 2) }}</td>
                           <td>{{ number_format($employee['daily_salary'] ?? 0, 2) }}</td>
                            <td>{{ $employee['salaired'] ?? '0%' }}</td>
                            <td>{{ number_format($employee['salaireBaseImposable'] ?? 0, 2) }}</td>


                            <td class="col-prime-split">
                                <div class="prime-anciennete-container">
                                    <div class="prime-anciennete-value">
                                        {{ $employee['anciennete'] !== null && $employee['anciennete'] >= 1 ? number_format($employee['anciennete'], 2)  : '0' }}
                                    </div>
                                </div>
                            </td>
                            <td class="col-prime-split">
                                <div class="prime-anciennete-container">
                                    <div class="prime-anciennete-value">
                                        {{ $employee['tauxAnciennete'] !== null && $employee['tauxAnciennete'] >= 1 ? number_format($employee['tauxAnciennete'], 2) : '0' }}
                                    </div>
                                </div>
                            </td>
                            <td class="col-prime-split">
                                <div class="prime-anciennete-container">
                                    <div class="prime-anciennete-value">
                                        {{ $employee['prime_anciennete'] !== null && $employee['prime_anciennete'] >= 1 ? number_format($employee['prime_anciennete'], 2) : '0' }}
                                    </div>
                                </div>
                            </td>
                            <td>{{ number_format($employee['prime_journaliere'] ?? 0, 2) }}</td>
                            <td>{{ number_format(array_sum(array_column($data['employees'], 'salaireBG')), 2) }}</td>
                            <td>{{ number_format($employee['primNonimposables'] ?? 0, 2) }}</td>
                            <td >{{ number_format($employee['salaireBI'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['cotisation_cnss'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['cotisation_amo'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['cotisation_CIMR'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['cotisation_mutuelle'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['tauxFrais'] ?? 0, 2) }}</td>
                           <td>{{ number_format($employee['professional_tax_deduction'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['salaireNI'] ?? 0, 2) }}</td>
                           <td>{{ number_format($employee['irData'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['somme_a_deduire'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['ir_brut'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['charge_de_famille'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['ir_net'] ?? 0, 2) }}</td>
                          <td class="yellow-column">{{ number_format($employee['salaire'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="4"><strong>TOTAL</strong></td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'salaire_base')), 2) }}</td>
                        <td>{{ array_sum(array_column($data['employees'], 'jours_travail')) }}</td>
                        <td>-</td>

                        <td>-</td>
                        <td>-</td>
                        <td>{{ array_sum(array_column($data['employees'], 'jours_travail')) }}</td>
                        <td>{{ array_sum(array_column($data['employees'], 'jours_supp')) }}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'prime_deplacement')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'prime_representation')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'prime_panier')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'prime_transport')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'prime_divers')), 2) }}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'avance')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'daily_salary')), 2) }}</td>
                        <td style=" background-color: #3285d3ff">{{ number_format(array_sum(array_column($data['employees'], 'salaired')), 2) }}</td>
                        <td style=" background-color: #3285d3ff">{{ number_format(array_sum(array_column($data['employees'], 'salaireBaseImposable')), 2) }}</td>
                        <td class="col-prime-split">-</td>
                        <td class="col-prime-split">-</td>
                        <td class="col-prime-split">{{ number_format(array_sum(array_column($data['employees'], 'prime_anciennete')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'prime_journaliere')), 2) }}</td>
                        <td style=" background-color: #3285d3ff">{{ number_format(array_sum(array_column($data['employees'], 'salaireBG')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'primNonimposables')), 2) }}</td>
                       
                       <td style=" background-color: #3285d3ff">{{ number_format(array_sum(array_column($data['employees'], 'salaireBI')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'cotisation_cnss')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'cotisation_amo')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'cotisation_CIMR')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'cotisation_mutuelle')), 2) }}</td>
                        <td>-</td>
                        <td >{{ number_format(array_sum(array_column($data['employees'], 'professional_tax_deduction')), 2) }}</td>                 
                        <td style=" background-color: #3285d3ff">{{ number_format(array_sum(array_column($data['employees'], 'salaireNI')), 2) }}</td>
                        <td>-</td>
                       <td>{{ number_format(array_sum(array_column($data['employees'], 'somme_a_deduire')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'ir_brut')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'charge_de_famille')), 2) }}</td>
                        <td>{{ number_format(array_sum(array_column($data['employees'], 'ir_net')), 2) }}</td>
                        <td class="yellow-column">{{ number_format(array_sum(array_column($data['employees'], 'salaire')), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="company-info">
        <p>Siège social: {{ $companySettings['address'] }} | Capital: {{ $companySettings['capital'] }} MAD | Tél: {{ $companySettings['phone_number'] }}</p>
        <p>R.C.: {{ $companySettings['commercial_register'] }} | CNSS: {{ $companySettings['cnss_number'] }} | IF: {{ $companySettings['tax_id'] }} | TP: {{ $companySettings['patent_number'] }} | ICE: {{ $companySettings['ice'] }}</p>
        <p>C.B.: {{ $companySettings['account_number'] }} | Email: {{ $companySettings['email'] }}</p>
    </div>
</body>
</html>