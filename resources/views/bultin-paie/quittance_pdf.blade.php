<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 5mm;
            padding: 0;
            font-size: 10px;
        }
        .page-container {
            height: 90vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .receipt-container {
            margin-bottom: 10mm;
            border: 2px solid #000;
            padding: 3mm;
            height: 45%;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0mm;
        }
        .header img {
            max-width: 80px;
            height: auto;
        }
        .header .date {
            font-size: 11px;
            font-weight: bold;
        }
        .company-name {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            flex-grow: 1;
            margin: 0 10mm;
        }
        .title-section table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        .title-section td {
            padding: 2mm;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }
        .title-left {
            background-color: #f0f0f0;
            border-right: 1px solid #000;
        }
        .title-right {
            background-color: #e6f3ff;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
        }
        .main-table td {
            border: 1px solid #000;
            padding: 1.5mm;
            vertical-align: middle;
            font-size: 9px;
        }
        .label-cell {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
            padding-left: 3mm;
        }
        .value-cell {
            text-align: center;
        }
        .amount-cell {
            background-color: #e6f3ff;
            font-weight: bold;
            text-align: center;
        }
        .full-width-label {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
            padding-left: 3mm;
        }
        .signature-section {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            height: 25mm;
            margin-top: 2mm;
        }
        .signature-section td {
            border-right: 1px solid #000;
            padding: 2mm;
            vertical-align: top;
            width: 33.33%;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
        .signature-section td:last-child {
            border-right: none;
        }
        .signature-space {
            height: 20mm;
            margin-top: 2mm;
        }
        .page-break {
            page-break-before: always;
        }
        .copy-label {
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    @foreach ($receipts as $pageIndex => $receipt)
        @php
            $netAmount = $receipt['net_amount'] ?? 0;
            $amountInWords = $numberToFrenchWords($netAmount);
            Log::debug('Receipt Data', ['receipt' => $receipt]);
        @endphp

        @if ($pageIndex > 0)
            <div class="page-break"></div>
        @endif

        <div class="page-container">
            <!-- Premier reçu - Copie Eses -->
            <div class="receipt-container">
                <div class="copy-label">Copie Eses</div>
                <div class="header">
                    @if (!empty($companySettings['logo_path']))
                        <img src="{{ $companySettings['logo_path'] }}" alt="Logo">
                    @else
                        <div>No Logo Available</div>
                    @endif
                    <div class="company-name">{{ $companySettings['nom_etreprise'] ?? 'Non défini' }}</div>
                    <div class="date">{{ $receipt['payment_date'] }}</div>
                </div>
                <div class="title-section">
                    <table>
                        <tr>
                            <td class="title-left">REÇU DE PAIEMENT N°: {{ $receipt['receipt_number'] }}</td>
                            <td class="title-right">{{ Str::upper($receipt['month']) }}</td>
                        </tr>
                    </table>
                </div>
                <table class="main-table">
                    <tr>
                        <td class="label-cell">PAYÉ PAR: {{ $companySettings['nom_etreprise'] ?? 'Non défini' }}</td>
                        <td class="label-cell">PAYÉ À: M/Ma</td>
                        <td class="value-cell" colspan="2">{{ $receipt['employee_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">EN QUALITÉ DU:</td>
                        <td class="value-cell">{{ $receipt['function'] }}</td>
                        <td class="label-cell">N° C.I.N</td>
                        <td class="value-cell">{{ $receipt['cin'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">NOMBRE DE JOURS:</td>
                        <td class="value-cell">{{ $receipt['work_days'] }}</td>
                        <td class="label-cell">N°C.N.S.S</td>
                        <td class="value-cell">{{ $receipt['cnss'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Édité par:</td>
                        <td class="value-cell">{{ $receipt['username'] }}</td>
                        <td class="label-cell amount-cell">NET À PAYER</td>
                        <td class="value-cell amount-cell" style="font-size: 11px; font-weight: bold;">
                            {{ number_format($netAmount, 2) }} MAD
                        </td>
                    </tr>
                    <tr>
                        <td class="full-width-label" colspan="4">MONTANT TOTAL EN TOUTES LETTRES: {{ $amountInWords }}</td>
                    </tr>
                    <tr>
                        <td class="full-width-label" colspan="2">Mode de paiement</td>
                        <td class="full-width-label" colspan="2">{{ $receipt['payment_method'] }}</td>
                    </tr>
                </table>
                <table class="signature-section">
                    <tr>
                        <td>BERKANE LE: {{ $receipt['payment_date'] }}<div class="signature-space"></div></td>
                        <td>Signature: {{ $companySettings['nom_etreprise'] ?? 'Non défini' }}<div class="signature-space"></div></td>
                        <td>Signature du bénéficiaire:<div class="signature-space"></div></td>
                    </tr>
                </table>
            </div>

            <!-- Deuxième reçu - Copie Client -->
            <div class="receipt-container">
                <div class="copy-label">Copie Client</div>
                <div class="header">
                    @if (!empty($companySettings['logo_path']))
                        <img src="{{ $companySettings['logo_path'] }}" alt="Logo">
                    @else
                        <div>No Logo Available</div>
                    @endif
                    <div class="company-name">{{ $companySettings['nom_etreprise'] ?? 'Non défini' }}</div>
                    <div class="date">{{ $receipt['payment_date'] }}</div>
                </div>
                <div class="title-section">
                    <table>
                        <tr>
                            <td class="title-left">REÇU DE PAIEMENT N°: {{ $receipt['receipt_number'] }}</td>
                            <td class="title-right">{{ Str::upper($receipt['month']) }}</td>
                        </tr>
                    </table>
                </div>
                <table class="main-table">
                    <tr>
                        <td class="label-cell">PAYÉ PAR: {{ $companySettings['nom_etreprise'] ?? 'Non défini' }}</td>
                        <td class="label-cell">PAYÉ À: M/Ma</td>
                        <td class="value-cell" colspan="2">{{ $receipt['employee_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">EN QUALITÉ DU:</td>
                        <td class="value-cell">{{ $receipt['function'] }}</td>
                        <td class="label-cell">N° C.I.N</td>
                        <td class="value-cell">{{ $receipt['cin'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">NOMBRE DE JOURS:</td>
                        <td class="value-cell">{{ $receipt['work_days'] }}</td>
                        <td class="label-cell">N°C.N.S.S</td>
                        <td class="value-cell">{{ $receipt['cnss'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Édité par:</td>
                        <td class="value-cell">{{ $receipt['username'] }}</td>
                        <td class="label-cell amount-cell">NET À PAYER</td>
                        <td class="value-cell amount-cell" style="font-size: 11px; font-weight: bold;">
                            {{ number_format($netAmount, 2) }} MAD
                        </td>
                    </tr>
                    <tr>
                        <td class="full-width-label" colspan="4">MONTANT TOTAL EN TOUTES LETTRES: {{ $amountInWords }}</td>
                    </tr>
                    <tr>
                        <td class="full-width-label" colspan="2">Mode de paiement</td>
                        <td class="full-width-label" colspan="2">{{ $receipt['payment_method'] }}</td>
                    </tr>
                </table>
                <table class="signature-section">
                    <tr>
                        <td>BERKANE LE: {{ $receipt['payment_date'] }}<div class="signature-space"></div></td>
                        <td>Signature: {{ $companySettings['nom_etreprise'] ?? 'Non défini' }}<div class="signature-space"></div></td>
                        <td>Signature du bénéficiaire:<div class="signature-space"></div></td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach
</body>
</html>