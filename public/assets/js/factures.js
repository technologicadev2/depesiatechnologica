document.addEventListener("DOMContentLoaded", function () {
    if (!window.jQuery || !$.fn.DataTable) {
        console.error("jQuery ou DataTables n'est pas chargé.");
        return;
    }

    // Configurer le jeton CSRF pour les requêtes AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Variable globale pour le type de facture
    let currentType = 'achat';
    let isAutoCalcEnabled = true;

    // Fonction pour ajouter la recherche par colonne
    function addColumnSearch(tableSelector, dataTable, nonSearchableColumns = []) {
        $(`${tableSelector} thead tr`).clone(true).appendTo(`${tableSelector} thead`);
        $(`${tableSelector} thead tr:eq(1) th`).each(function (i) {
            if (nonSearchableColumns.includes(i)) {
                $(this).html("");
                return;
            }
            var title = $(this).text();
            $(this).html(
                `<input type="text" class="form-control form-control-sm" placeholder="Rechercher ${title}" />`
            );
            $("input", this).on("keyup change", function () {
                if (dataTable.column(i).search() !== this.value) {
                    dataTable.column(i).search(this.value).draw();
                }
            });
        });
    }

    // Fonction pour calculer les montants
    function calculateAmounts() {
        if (!isAutoCalcEnabled) return;

        const tauxTVA = parseFloat($("#taux_tva").val()) || 0;
        const montantHTT = parseFloat($("#montant_htt").val()) || 0;
        const montantTTC = parseFloat($("#montant_ttc").val()) || 0;
        const activeField = document.activeElement ? document.activeElement.id : null;

        if (montantHTT > 0 && activeField === "montant_htt") {
            const montantTVA = (montantHTT * tauxTVA) / 100;
            const newMontantTTC = montantHTT + montantTVA;
            $("#montant_tva").val(montantTVA.toFixed(2));
            $("#montant_ttc").val(newMontantTTC.toFixed(2));
        } else if (montantTTC > 0 && activeField === "montant_ttc") {
            const montantHTT = montantTTC / (1 + tauxTVA / 100);
            const montantTVA = montantTTC - montantHTT;
            $("#montant_htt").val(montantHTT.toFixed(2));
            $("#montant_tva").val(montantTVA.toFixed(2));
        } else if (activeField === "taux_tva") {
            if (montantHTT > 0) {
                const montantTVA = (montantHTT * tauxTVA) / 100;
                const newMontantTTC = montantHTT + montantTVA;
                $("#montant_tva").val(montantTVA.toFixed(2));
                $("#montant_ttc").val(newMontantTTC.toFixed(2));
            } else if (montantTTC > 0) {
                const montantHTT = montantTTC / (1 + tauxTVA / 100);
                const montantTVA = montantTTC - montantHTT;
                $("#montant_htt").val(montantHTT.toFixed(2));
                $("#montant_tva").val(montantTVA.toFixed(2));
            }
        }
    }

    $("#taux_tva, #montant_htt, #montant_ttc").on("input", function () {
        if (isAutoCalcEnabled) {
            calculateAmounts();
        }
    });

    // Mettre à jour le bouton de calcul automatique
    function updateToggleButton() {
        const $toggleButton = $("#toggleAutoCalc");
        const $calcModeText = $("#calcModeText");
        const $calcModeIcon = $("#calcModeInfo .bx");

        if (isAutoCalcEnabled) {
            $toggleButton.removeClass("btn-outline-primary").addClass("btn-primary");
            $calcModeText.text("Mode calcul automatique activé");
            $calcModeIcon.removeClass("bx-calculator-off").addClass("bx-calculator text-success");
            $("#montant_tva").prop("readonly", true);
        } else {
            $toggleButton.removeClass("btn-primary").addClass("btn-outline-primary");
            $calcModeText.text("Mode calcul automatique désactivé");
            $calcModeIcon.removeClass("bx-calculator text-success").addClass("bx-calculator-off text-muted");
            $("#montant_tva").prop("readonly", false);
        }
    }

    // Générer le contenu pour l'impression des tables
    function generatePrintContent(table, title, columns) {
        const data = table.rows({ search: "applied" }).data().toArray();
        const logoUrl = companySettings && companySettings.logo
            ? `${window.location.origin}/storage/${companySettings.logo}`
            : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

        const rowsPerPage = 12;
        const pageData = [];
        for (let i = 0; i < data.length; i += rowsPerPage) {
            pageData.push(data.slice(i, i + rowsPerPage));
        }

        const tableContentPages = pageData.map((pageRows) => {
            const tableContent = pageRows
                .map(
                    (row) => `
                    <tr class="table-tr">
                        ${columns.cols
                            .map((col) => `<td>${row[col] || "-"}</td>`)
                            .join("")}
                    </tr>
                `
                )
                .join("");

            return `
                <div class="print-body-2" style="margin-bottom:8px; page-break-after: always;">
                    <table class="table table-bordered">
                        <tr style="background-color:rgb(233, 233, 233);">
                            ${columns.headers
                                .map((header) => `<td class="td-bold">${header}</td>`)
                                .join("")}
                        </tr>
                        ${tableContent}
                    </table>
                </div>
            `;
        }).join("");

        return `
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Impression - ${title}</title>
                <style>
                    html, body, * { padding: 0; margin: 0; box-sizing: border-box; font-family: sans-serif; }
                    @page { size: landscape; margin: 0mm; }
                    body * { -webkit-print-color-adjust: exact !important; }
                    .print { width: 100%; height: auto; padding: 18px 18px 0 18px; }
                    .print-header { width: 100%; height: auto; margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
                    .print-header-box { position: relative; }
                    .print-header-title { font-size: 15px; font-weight: bold; line-height: 1.45; margin-left: 10px; }
                    .print-header-type { width: 100%; height: 23px; line-height: 21px; text-align: center; background-color: #efefef; border: 1px solid black; margin-bottom: 10px; font-size: 13px; font-weight: bold; letter-spacing: 0.5px; }
                    .print-body-2 table { width: 100%; }
                    .print-body-2 table, th, td { border: 1px solid black; border-collapse: collapse; padding: 6px; text-align: center; font-size: 12px; font-weight: 500; vertical-align: middle; }
                    .table-tr td { padding: 6px; }
                    .print-footer { width: 100%; display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; margin-top: 10px; }
                    .print-footer h4 { display: inline-block; margin-right: 20px; font-size: 13px; }
                    .td-bold { font-size: 13px; font-weight: bold; }
                    .logo-title-container { display: flex; align-items: center; }
                    .logo-title-container img { max-width: 50px; height: auto; }
                    .company-info { position: absolute; bottom: 10px; width: 100%; text-align: center; font-size: 12px; padding: 10px 0; border-top: 1px solid #ddd; }
                    @media print {
                        body { margin: 2mm !important; }
                        .print { margin: 0; padding: 18px 18px 0 18px; }
                        .print-body-2 { margin-bottom: 8px !important; }
                        .print-body-2 table { page-break-outside: auto !important; break-outside: auto !important; }
                        .table-tr { page-break-inside: avoid !important; break-inside: avoid !important; page-break-after: auto !important; break-after: auto !important; }
                        .company-info { position: fixed !important; bottom: 0 !important; width: 100% !important; padding: 10px 0 !important; border-top: 1px solid #ddd !important; font-size: 12px !important; z-index: 1000 !important; page-break-outside: avoid !important; break-outside: avoid !important; }
                    }
                </style>
            </head>
            <body>
                <div class="print">
                    <div class="print-header">
                        <div class="logo-title-container">
                            <img src="${logoUrl}" alt="Logo" />
                            <h1 class="print-header-title">${title}</h1>
                        </div>
                        <div class="print-header-box">
                            <div class="print-header-type">${title}</div>
                        </div>
                    </div>
                    ${tableContentPages}
                    <div class="print-footer">
                        <div>
                            <h4>A: Oujda</h4>
                            <h4>Le: ${new Date().toLocaleDateString("fr-FR")}</h4>
                        </div>
                    </div>
                </div>
                <div class="company-info">
                    <p>Siège social: ${companySettings.address || "Non spécifié"} | Capital: ${companySettings.capital ? companySettings.capital.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD" : "Non spécifié"} | Tél: ${companySettings.phone_number || "Non spécifié"}</p>
                    <p>R.C.: ${companySettings.commercial_register || "Non spécifié"} | CNSS: ${companySettings.cnss_number || "Non spécifié"} | IF: ${companySettings.tax_id || "Non spécifié"} | TP: ${companySettings.tax_id || "Non spécifié"} | ICE: ${companySettings.patent_number || "Non spécifié"}</p>
                    <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${companySettings.bank_name || "Non spécifié"} | Email: ${companySettings.email || "Non spécifié"}</p>
                </div>
            </body>
            </html>
        `;
    }

    // Fonction pour imprimer une table
    function printTable(tableId, title, columns) {
        const table = $(`#${tableId}`).DataTable();
        const iframe = $("<iframe/>", {
            id: "printIframe",
            style: "position: absolute; width: 0; height: 0; border: none;",
        }).appendTo("body");

        const page_html = generatePrintContent(table, title, columns);
        const iframeDoc = iframe[0].contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(page_html);
        iframeDoc.close();

        setTimeout(function () {
            iframe[0].contentWindow.focus();
            iframe[0].contentWindow.print();
        }, 500);

        iframe[0].contentWindow.onafterprint = function () {
            iframe.remove();
        };
        setTimeout(function () {
            if (iframe[0]) {
                iframe.remove();
            }
        }, 5000);
    }

    // Générer le contenu pour l'impression du rapport TVA
    function generateVatReportPrintContent(data) {
        const periode = data.tva_declaration === 'mensuelle'
            ? `Mensuelle (${data.start_date} - ${data.end_date})`
            : `Trimestrielle (${data.start_date} - ${data.end_date})`;

        const totalFactures = data.achat.count + data.vente.count;
        const caTotal = parseFloat(data.vente.total_ht);
        const totalTva = parseFloat(data.vente.total_tva);
        const tauxMoyen = caTotal > 0 ? ((totalTva / caTotal) * 100).toFixed(1) : 0;

        return `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Rapport TVA - ${periode}</title>
                <style>
                    @media print {
                        @page {
                            margin: 15mm;
                            size: A4 portrait;
                        }
                        body {
                            font-family: Arial, sans-serif;
                            font-size: 11px;
                            line-height: 1.3;
                            color: #000;
                            margin: 0;
                        }
                    }
                    body {
                        font-family: Arial, sans-serif;
                        margin: 15mm;
                        color: #333;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 15px;
                        border-bottom: 1px solid #333;
                        padding-bottom: 10px;
                    }
                    .company-name {
                        font-size: 18px;
                        font-weight: bold;
                        color: #2c3e50;
                        margin-bottom: 5px;
                    }
                    .report-title {
                        font-size: 14px;
                        color: #34495e;
                        margin-bottom: 5px;
                    }
                    .periode {
                        font-size: 12px;
                        color: #7f8c8d;
                        background-color: #ecf0f1;
                        padding: 5px;
                        border-radius: 3px;
                    }
                    .content {
                        margin-top: 10px;
                    }
                    .section {
                        margin-bottom: 15px;
                    }
                    .section-title {
                        font-size: 13px;
                        font-weight: bold;
                        color: #2c3e50;
                        margin-bottom: 8px;
                        border-bottom: 1px solid #bdc3c7;
                        padding-bottom: 3px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 10px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }
                    th, td {
                        border: 1px solid #bdc3c7;
                        padding: 8px;
                        text-align: left;
                    }
                    th {
                        background-color: #34495e;
                        color: white;
                        font-weight: bold;
                        text-align: center;
                    }
                    .achat-header {
                        background-color: #e74c3c !important;
                    }
                    .vente-header {
                        background-color: #3498db !important;
                    }
                    .net-header {
                        background-color: #27ae60 !important;
                    }
                    .amount {
                        text-align: right;
                        font-weight: bold;
                    }
                    .total-row {
                        background-color: #f8f9fa;
                        font-weight: bold;
                    }
                    .net-tva {
                        background-color: #d5f4e6;
                        font-size: 14px;
                        font-weight: bold;
                        color: #27ae60;
                        text-align: center;
                    }
                    .summary {
                        background-color: #f8f9fa;
                        padding: 10px;
                        border-radius: 3px;
                        margin-top: 10px;
                    }
                    .summary-item {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 5px;
                    }
                    .footer {
                        margin-top: 15px;
                        text-align: center;
                        font-size: 9px;
                        color: #7f8c8d;
                        border-top: 1px solid #bdc3c7;
                        padding-top: 8px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="company-name">RAPPORT DE DÉCLARATION TVA</div>
                    <div class="report-title">Synthèse des Opérations Imposables</div>
                    <div class="periode">${periode}</div>
                </div>

                <div class="content">
                    <div class="section">
                        <table>
                            <thead>
                                <tr>
                                    <th colspan="2" class="achat-header">🛒 FACTURES D'ACHAT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total Hors Taxes</td>
                                    <td class="amount">${parseFloat(data.achat.total_ht).toLocaleString('fr-FR', {minimumFractionDigits: 2})} DH</td>
                                </tr>
                                <tr>
                                    <td>TVA Déductible</td>
                                    <td class="amount">${parseFloat(data.achat.total_tva).toLocaleString('fr-FR', {minimumFractionDigits: 2})} DH</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Total Toutes Taxes Comprises</td>
                                    <td class="amount">${parseFloat(data.achat.total_ttc).toLocaleString('fr-FR', {minimumFractionDigits: 2})} DH</td>
                                </tr>
                                <tr>
                                    <td>Nombre de factures</td>
                                    <td class="amount">${data.achat.count}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="section">
                        <table>
                            <thead>
                                <tr>
                                    <th colspan="2" class="vente-header">💰 FACTURES DE VENTE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total Hors Taxes</td>
                                    <td class="amount">${parseFloat(data.vente.total_ht).toLocaleString('fr-FR', {minimumFractionDigits: 2})} DH</td>
                                </tr>
                                <tr>
                                    <td>TVA Collectée</td>
                                    <td class="amount">${parseFloat(data.vente.total_tva).toLocaleString('fr-FR', {minimumFractionDigits: 2})} DH</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Total Toutes Taxes Comprises</td>
                                    <td class="amount">${parseFloat(data.vente.total_ttc).toLocaleString('fr-FR', {minimumFractionDigits: 2})} DH</td>
                                </tr>
                                <tr>
                                    <td>Nombre de factures</td>
                                    <td class="amount">${data.vente.count}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th class="net-header">🧮 TVA NETTE À DÉCLARER</th okresu:                             </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="net-tva">
                                    ${parseFloat(data.net_tva).toLocaleString('fr-FR', {minimumFractionDigits: 2})} DH
                                    <br><small style="font-size: 10px; font-weight: normal; color: #666;">
                                        TVA Collectée (${parseFloat(data.vente.total_tva).toLocaleString('fr-FR', {minimumFractionDigits: 2})} DH) -
                                        TVA Déductible (${parseFloat(data.achat.total_tva).toLocaleString('fr-FR', {minimumFractionDigits: 2})} DH)
                                    </small>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="summary">
                        <div class="section-title">📊 RÉSUMÉ STATISTIQUE</div>
                        <div class="summary-item">
                            <span>Total des factures traitées:</span>
                            <span><strong>${totalFactures}</strong></span>
                        </div>
                        <div class="summary-item">
                            <span>Chiffre d'affaires HT:</span>
                            <span><strong>${caTotal.toLocaleString('fr-FR', {minimumFractionDigits: 2})} DH</strong></span>
                        </div>
                        <div class="summary-item">
                            <span>Taux TVA moyen:</span>
                            <span><strong>${tauxMoyen}%</strong></span>
                        </div>
                    </div>
                </div>

                <div class="footer">
                    <p>Rapport généré automatiquement le ${new Date().toLocaleDateString('fr-FR')} à ${new Date().toLocaleTimeString('fr-FR')}</p>
                    <p>Ce document constitue un support de déclaration TVA et doit être conservé selon les obligations légales.</p>
                </div>
            </body>
            </html>
        `;
    }

    // Fonction pour imprimer le rapport TVA
    function printVatReport(data) {
        const iframe = $("<iframe/>", {
            id: "printVatIframe",
            style: "position: absolute; width: 0; height: 0; border: none;",
        }).appendTo("body");

        const page_html = generateVatReportPrintContent(data);
        const iframeDoc = iframe[0].contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(page_html);
        iframeDoc.close();

        setTimeout(function () {
            iframe[0].contentWindow.focus();
            iframe[0].contentWindow.print();
        }, 500);

        iframe[0].contentWindow.onafterprint = function () {
            iframe.remove();
        };
        setTimeout(function () {
            if (iframe[0]) {
                iframe.remove();
            }
        }, 5000);
    }
// ===============================================
// GÉNÉRATION PDF DÉCLARATION (releve_ex = 1)
// ===============================================
function generateDeclarationPDF(tableId) {
    const isAchat = tableId === 'facturesAchatTable';
    const type    = isAchat ? 'achat' : 'vente';
    const title   = isAchat ? "Déclaration — Factures d'Achat" : "Déclaration — Factures de Vente";
    const dateField = isAchat ? 'date_paiement' : 'date_encaissement';

    // Récupérer les lignes filtrées depuis le DOM (toutes, pas seulement visibles)
    const rows = [];
    $(`#${tableId} tbody tr`).each(function () {
        const $row    = $(this);
        const $cells  = $row.find('td');

        // Lire le releve_ex depuis l'attribut data ou depuis la couleur de fond
        // On va récupérer les données via AJAX pour avoir releve_ex + date_paiement
    });

    // Appel AJAX pour récupérer les données filtrées avec releve_ex=1
    $.ajax({
        url: `/factures/declaration?type=${type}`,
        type: 'GET',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (response) {
            if (!response.success || !response.data || response.data.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Aucune donnée',
                    text: 'Aucune facture rapprochée (releve_ex = 1) trouvée.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            printDeclarationPDF(response.data, title, type);
        },
        error: function (xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: xhr.responseJSON?.message || 'Impossible de charger les données.',
                confirmButtonText: 'OK'
            });
        }
    });
}

function printDeclarationPDF(data, title, type) {
    const logoUrl = companySettings && companySettings.logo
        ? `${window.location.origin}/storage/${companySettings.logo}`
        : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

    // Grouper par mois de déclaration
    const grouped = {};
    data.forEach(function (facture) {
        const dateStr = type === 'achat' ? facture.date_paiement : facture.date_encaissement;
        let mois = '—';
        if (dateStr) {
            const d = new Date(dateStr);
            if (!isNaN(d)) {
                mois = d.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
                mois = mois.charAt(0).toUpperCase() + mois.slice(1);
            }
        }
        if (!grouped[mois]) grouped[mois] = [];
        grouped[mois].push({ ...facture, _mois: mois });
    });

    // Construire les lignes HTML
    let tableRows = '';
    let grandTotal = 0;

    Object.keys(grouped).sort().forEach(function (mois) {
        const lignes = grouped[mois];
        const sousTotal = lignes.reduce((s, f) => s + (parseFloat(f.montant_ttc) || 0), 0);
        grandTotal += sousTotal;

        // Ligne de séparateur de mois
        tableRows += `
            <tr style="background-color:#34495e; color:white;">
                <td colspan="4" style="padding:6px 8px; font-weight:bold; font-size:12px;">
                    📅 ${mois}
                </td>
            </tr>
        `;

        lignes.forEach(function (f) {
            const montant = parseFloat(f.montant_ttc) || 0;
            tableRows += `
                <tr>
                    <td style="padding:5px 8px; border:1px solid #ddd; font-size:11px;">${f.numero_facture || '—'}</td>
                    <td style="padding:5px 8px; border:1px solid #ddd; font-size:11px;">${f.date_facture || '—'}</td>
                    <td style="padding:5px 8px; border:1px solid #ddd; font-size:11px; text-align:right; font-weight:bold;">
                        ${montant.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} DH
                    </td>
                    <td style="padding:5px 8px; border:1px solid #ddd; font-size:11px;">${f._mois}</td>
                </tr>
            `;
        });

        // Sous-total du mois
        tableRows += `
            <tr style="background-color:#ecf0f1;">
                <td colspan="2" style="padding:5px 8px; border:1px solid #ddd; font-size:11px; font-weight:bold; text-align:right;">
                    Sous-total ${mois} :
                </td>
                <td style="padding:5px 8px; border:1px solid #ddd; font-size:11px; font-weight:bold; text-align:right; color:#e74c3c;">
                    ${sousTotal.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} DH
                </td>
                <td style="padding:5px 8px; border:1px solid #ddd;"></td>
            </tr>
        `;
    });

    const htmlContent = `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>${title}</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
                @page { size: A4 portrait; margin: 15mm; }
                body { padding: 15mm; color: #333; }
                @media print {
                    body { padding: 0; margin: 0; }
                    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                }
                .header { display: flex; align-items: center; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 15px; }
                .header img { max-width: 60px; margin-right: 15px; }
                .header-info h1 { font-size: 16px; color: #2c3e50; margin-bottom: 4px; }
                .header-info p  { font-size: 11px; color: #7f8c8d; }
                .badge-type {
                    display: inline-block;
                    padding: 3px 10px;
                    border-radius: 12px;
                    font-size: 11px;
                    font-weight: bold;
                    color: white;
                    background-color: ${type === 'achat' ? '#e74c3c' : '#3498db'};
                    margin-bottom: 12px;
                }
                table { width: 100%; border-collapse: collapse; margin-top: 5px; }
                thead th {
                    background-color: #2c3e50;
                    color: white;
                    padding: 8px;
                    font-size: 12px;
                    text-align: left;
                    border: 1px solid #2c3e50;
                }
                thead th:nth-child(3) { text-align: right; }
                tbody tr:hover { background-color: #f8f9fa; }
                .grand-total-row {
                    background-color: #2c3e50;
                    color: white;
                    font-weight: bold;
                }
                .grand-total-row td { padding: 8px; font-size: 12px; border: 1px solid #2c3e50; }
                .footer {
                    margin-top: 20px;
                    border-top: 1px solid #ddd;
                    padding-top: 8px;
                    font-size: 9px;
                    color: #95a5a6;
                    text-align: center;
                }
                .company-footer {
                    font-size: 9px;
                    color: #7f8c8d;
                    text-align: center;
                    margin-top: 8px;
                    border-top: 1px solid #ddd;
                    padding-top: 6px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="${logoUrl}" alt="Logo" onerror="this.src='${window.location.origin}/assets/img/favicon/anassi2.jpg';">
                <div class="header-info">
                    <h1>${title}</h1>
                    <p>Imprimé le ${new Date().toLocaleDateString('fr-FR')} à ${new Date().toLocaleTimeString('fr-FR')}</p>
                    <p>Factures rapprochées avec le relevé bancaire</p>
                </div>
            </div>

            <div class="badge-type">
                ${type === 'achat' ? '🛒 Factures d\'Achat' : '💰 Factures de Vente'} — ${data.length} facture(s)
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:25%;">N° Facture</th>
                        <th style="width:20%;">Date Facture</th>
                        <th style="width:25%; text-align:right;">Montant TTC</th>
                        <th style="width:30%;">Mois de Déclaration</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRows}
                    <tr class="grand-total-row">
                        <td colspan="2" style="text-align:right;">TOTAL GÉNÉRAL :</td>
                        <td style="text-align:right;">${grandTotal.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} DH</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>


            <script>window.onload = function() { window.print(); };<\/script>
        </body>
        </html>
    `;

    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:absolute;width:0;height:0;border:none;';
    document.body.appendChild(iframe);

    const doc = iframe.contentDocument || iframe.contentWindow.document;
    doc.open();
    doc.write(htmlContent);
    doc.close();

    iframe.contentWindow.onafterprint = function () {
        document.body.removeChild(iframe);
    };
    setTimeout(function () {
        if (iframe.parentNode) document.body.removeChild(iframe);
    }, 8000);
}
    // Initialiser DataTable
    function initDataTable(tableId, title, columns) {
        return $(`#${tableId}`).DataTable({
            dom:
                '<"row ms-2 me-3"' +
                '<"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls"l<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3"B>>' +
                '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter"f<"dt-filter mb-3 mb-md-0">>' +
                ">t" +
                '<"row mx-2"' +
                '<"col-sm-12 col-md-6"i>' +
                '<"col-sm-12 col-md-6"p>' +
                ">",
            displayLength: 10,
            lengthMenu: [10, 25, 50, 75, 100],
            ordering: true, // Activer le tri
            order: [[tableId === 'facturesAchatTable' ? 1 : 2, 'desc']], // Trier par Date Facture (index 1 pour achats, 2 pour ventes)
            buttons: [
                {
                    text: '<i class="bx bx-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Ajouter Facture</span>',
                    className: "btn btn-primary",
                    action: function () {
                        const activeTab = $("#factureTabs .nav-link.active").attr("id");
                        currentType = activeTab === "achat-tab" ? "achat" : "vente";
                        $("#factureModal").modal("show");
                        $("#factureModalLabel").text("Ajouter une Facture " + (currentType === "achat" ? "d'Achat" : "de Vente"));
                        $("#isEdit").val("false");
                        $("#factureId").val("");
                        $("#factureForm")[0].reset();
                        $("#filePreview").empty();
                        isAutoCalcEnabled = true;
                        updateToggleButton();
                        $("#montant_tva").prop("readonly", true);
                        $("#montant_htt").prop("readonly", false);
                        $("#montant_ttc").prop("readonly", false);
                        $("#taux_tva").val("20");
                        $("#type").val(currentType);

                        // Show or hide the Objet field based on invoice type
                        if (currentType === "vente") {
                            $("#objet_field").css("display", "block");
                        } else {
                            $("#objet_field").css("display", "none");
                        }

                        console.log("Type de facture sélectionné:", currentType);
                    }
                },
                {
                    extend: "collection",
                    className: "btn btn-label-primary dropdown-toggle me-2 export-btn",
                    text: '<i class="bx bx-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Exporter</span>',
                    buttons: [
                        {
                            text: '<i class="bx bx-printer me-1"></i>Imprimer',
                            className: "dropdown-item",
                            action: function () {
                                printTable(tableId, title, columns);
                            }
                        },
                        {
                            extend: "excel",
                            text: '<i class="bx bxs-file-export me-1"></i>Excel',
                            className: "dropdown-item",
                            exportOptions: { columns: columns.cols }
                        },
                        {
    text: '<i class="bx bxs-file-pdf me-1"></i>PDF Déclaration',
    className: "dropdown-item",
    action: function () {
        generateDeclarationPDF(tableId);
    }
}
                    ]
                }
            ],
            responsive: true,
            orderCellsTop: true,
            language: {
                search: "Rechercher:",
                lengthMenu: "Afficher _MENU_ ",
                info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
                infoEmpty: "Aucun élément à afficher",
                infoFiltered: "(filtré à partir de _MAX_ éléments au total)",
                paginate: {
                    first: "Premier",
                    last: "Dernier",
                    next: "Suivant",
                    previous: "Précédent"
                }
            },
            initComplete: function () {
                if (typeof addColumnSearch === 'function') {
                    const nonSearchableColumns = tableId === 'facturesAchatTable' ? [8] : [9]; // Actions column
                    addColumnSearch(`#${tableId}`, this.api(), nonSearchableColumns);
                }
            }
        });
    }

    // Initialiser DataTables
    const achatTable = initDataTable(
        "facturesAchatTable",
        "Factures d'Achat",
        {
            cols: [0, 1, 2, 3, 4, 5, 6, 7],
            headers: [
                "Numéro Facture",
                "Date Facture",
                "Raison Sociale",
                "ICE",
                "Montant HT",
                "Taux TVA",
                "Montant TVA",
                "Montant TTC"
            ]
        }
    );

    const venteTable = initDataTable(
        "facturesVenteTable",
        "Factures de Vente",
        {
            cols: [0, 1, 2, 3, 4, 5, 6, 7, 8],
            headers: [
                "Numéro Facture",
                "Objet",
                "Date Facture",
                "Raison Sociale",
                "ICE",
                "Montant HT",
                "Taux TVA",
                "Montant TVA",
                "Montant TTC"
            ]
        }
    );

    // Mettre à jour les totaux
    function updateTotals(type) {
        $.ajax({
            url: `/factures/totals?type=${type}`,
            type: "GET",
            success: function (response) {
                if (type === "achat") {
                    $("#totalHTAchat").text(parseFloat(response.total_ht).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + " DH");
                    $("#totalTVAAchat").text(parseFloat(response.total_tva).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + " DH");
                    $("#totalTTCAchat").text(parseFloat(response.total_ttc).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + " DH");
                    $("#countAchat").text(response.count);
                } else {
                    $("#totalHTVente").text(parseFloat(response.total_ht).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + " DH");
                    $("#totalTVAVente").text(parseFloat(response.total_tva).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + " DH");
                    $("#totalTTCVente").text(parseFloat(response.total_ttc).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + " DH");
                    $("#countVente").text(response.count);
                }
            },
            error: function (xhr) {
                console.error("Erreur lors de la mise à jour des totaux:", xhr.responseText);
            }
        });
    }

    // Générer le rapport TVA
    $("#generateVatReportBtn").on("click", function () {
    const startDate  = $('#start_date').val();
    const endDate    = $('#end_date').val();
    const filterType = $('#vat_filter_type').val();   // ← NOUVEAU
 
    if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "La date de début doit être antérieure à la date de fin.",
            position: "center",
            confirmButtonText: "OK"
        });
        return;
    }
 
    $.ajax({
        url: '/factures/vat-report',
        method: 'GET',
        dataType: 'json',
        data: {
            start_date:  startDate  || null,
            end_date:    endDate    || null,
            filter_type: filterType          // ← NOUVEAU
        },
        success: function (response) {
            if (response.success) {
                if (response.achat.count === 0 && response.vente.count === 0) {
                    Swal.fire({
                        icon: "info",
                        title: "Aucun résultat",
                        text: "Aucune facture trouvée pour la période et le filtre sélectionnés.",
                        position: "center",
                        confirmButtonText: "OK"
                    });
                    return;
                }
 
                // ── Libellé du filtre pour l'affichage ─────────────────────
                const filterLabels = {
                    'releve_existe'     : 'Relevé existant',
                    'releve_non_existe' : 'Relevé non existant',
                    'toutes'            : 'Toutes les factures'
                };
                const filterLabel = filterLabels[response.filter_type] || '';
 
                // ── Période ─────────────────────────────────────────────────
                const periode = response.tva_declaration === 'mensuelle'
                    ? `Mensuelle (${response.start_date} → ${response.end_date})`
                    : `Trimestrielle (${response.start_date} → ${response.end_date})`;
 
                // Afficher période + filtre dans le titre de la modale
                $('#vatPeriod').text(`${periode} — ${filterLabel}`);
 
                // ── Remplissage des champs achat ────────────────────────────
                $('#vatAchatHt').text(
                    parseFloat(response.achat.total_ht).toLocaleString('fr-FR', { minimumFractionDigits: 2 })
                );
                $('#vatAchatTva').text(
                    parseFloat(response.achat.total_tva).toLocaleString('fr-FR', { minimumFractionDigits: 2 })
                );
                $('#vatAchatTtc').text(
                    parseFloat(response.achat.total_ttc).toLocaleString('fr-FR', { minimumFractionDigits: 2 })
                );
                $('#vatAchatCount').text(response.achat.count);
 
                // ── Remplissage des champs vente ────────────────────────────
                $('#vatVenteHt').text(
                    parseFloat(response.vente.total_ht).toLocaleString('fr-FR', { minimumFractionDigits: 2 })
                );
                $('#vatVenteTva').text(
                    parseFloat(response.vente.total_tva).toLocaleString('fr-FR', { minimumFractionDigits: 2 })
                );
                $('#vatVenteTtc').text(
                    parseFloat(response.vente.total_ttc).toLocaleString('fr-FR', { minimumFractionDigits: 2 })
                );
                $('#vatVenteCount').text(response.vente.count);
 
                // ── TVA nette ───────────────────────────────────────────────
                $('#netTva').text(
                    parseFloat(response.net_tva).toLocaleString('fr-FR', { minimumFractionDigits: 2 })
                );
 
                $('#vatReportModal').modal('show');
 
                // Bouton d'impression (passe response pour inclure le filtre dans le PDF)
                $('#printVatReportBtn').off('click').on('click', function () {
                    printVatReport(response);
                });
 
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: response.message || "Erreur lors de la génération du rapport TVA",
                    position: "center",
                    confirmButtonText: "OK"
                });
            }
        },
        error: function (xhr) {
            console.error('Erreur AJAX:', xhr.status, xhr.responseText);
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: xhr.responseJSON?.message || "Erreur lors de la génération du rapport TVA",
                position: "center",
                confirmButtonText: "OK"
            });
        }
    });
});
 

    $(document).on("click", ".print-facture", function () {
        const $button = $(this);
        const factureId = $button.data("id");
        const type = $button.data("type");

        $.ajax({
            url: `/factures/${factureId}?type=${type}`,
            type: "GET",
            success: function (response) {
                if (!response.facture) {
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: "Impossible de récupérer les données de la facture.",
                        position: "center",
                        confirmButtonText: "OK"
                    });
                    return;
                }

                const facture = response.facture;
                const data = {
                    numero_facture: facture.numero_facture || "-",
                    date_facture: facture.date_facture || "-",
                    raison_sociale: facture.raison_sociale || "-",
                    ice: facture.ice || "-",
                    montant_ht: parseFloat(facture.montant_ht) ? parseFloat(facture.montant_ht).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + " DH" : "-",
                    taux_tva: parseFloat(facture.taux_tva) ? parseFloat(facture.taux_tva).toFixed(2) + "%" : "-",
                    montant_tva: parseFloat(facture.montant_tva) ? parseFloat(facture.montant_tva).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + " DH" : "-",
                    montant_ttc: parseFloat(facture.montant_ttc) ? parseFloat(facture.montant_ttc).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + " DH" : "-",
                    objet: type === "vente" ? (facture.objet || "-") : undefined
                };

               // const companySettings = window.companySettings || {};
                const logoUrl = companySettings.logo
                    ? `${window.location.origin}/storage/${companySettings.logo}`
                    : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

                const iframe = document.createElement("iframe");
                iframe.style.display = "none";
                document.body.appendChild(iframe);
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

                iframeDoc.open();
                iframeDoc.write(`
                    <html>
                    <head>
                        <title>Impression - Détails de la Facture</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20mm; padding-bottom: 40px; box-sizing: border-box; }
                            @page { size: A4 portrait; margin: 20mm; }
                            .print-container { max-width: 100%; margin: 0 auto; page-break-after: avoid; }
                            .header { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 15px; }
                            .header img { max-width: 80px; margin-right: 15px; }
                            .header h1 { font-size: 20px; margin: 0; }
                            .header p { font-size: 12px; margin: 5px 0 0; }
                            table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
                            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                            th { background-color: #f2f2f2; font-weight: bold; width: 30%; }
                            td { width: 70%; word-break: break-all; }
                            .company-info { position: fixed; bottom: 10mm; left: 20mm; right: 20mm; text-align: center; font-size: 10px; padding-top: 5px; border-top: 1px solid #ddd; }
                            @media print {
                                body { margin: 0 !important; }
                                .print-container { margin: 20mm; page-break-after: avoid; }
                                .company-info { width: calc(100% - 40mm); }
                                * { -webkit-print-color-adjust: exact !important; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="print-container">
                            <div class="header">
                                <img src="${logoUrl}" alt="Logo" onerror="this.src='${window.location.origin}/assets/img/favicon/anassi2.jpg';" />
                                <div>
                                    <h1>Détails de la Facture (${type === "achat" ? "Achat" : "Vente"})</h1>
                                    <p>Date d'impression: ${new Date().toLocaleDateString("fr-FR")}</p>
                                </div>
                            </div>
                            <table>
                                <tr><th>Numéro Facture</th><td>${data.numero_facture}</td></tr>
                                ${type === "vente" ? `<tr><th>Objet</th><td>${data.objet}</td></tr>` : ""}
                                <tr><th>Date Facture</th><td>${data.date_facture}</td></tr>
                                <tr><th>Raison Sociale</th><td>${data.raison_sociale}</td></tr>
                                <tr><th>ICE</th><td>${data.ice}</td></tr>
                                <tr><th>Montant HT</th><td>${data.montant_ht}</td></tr>
                                <tr><th>Taux TVA</th><td>${data.taux_tva}</td></tr>
                                <tr><th>Montant TVA</th><td>${data.montant_tva}</td></tr>
                                <tr><th>Montant TTC</th><td>${data.montant_ttc}</td></tr>
                            </table>
                        </div>
                        <div class="company-info">
                            <p>Siège social: ${companySettings.address || "Non spécifié"} | Capital: ${companySettings.capital ? companySettings.capital.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD" : "Non spécifié"} | Tél: ${companySettings.phone_number || "Non spécifié"}</p>
                            <p>R.C.: ${companySettings.commercial_register || "Non spécifié"} | CNSS: ${companySettings.cnss_number || "Non spécifié"} | IF: ${companySettings.tax_id || "Non spécifié"} | TP: ${companySettings.tax_id || "Non spécifié"} | ICE: ${companySettings.patent_number || "Non spécifié"}</p>
                            <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${companySettings.bank_name || "Non spécifié"} | Email: ${companySettings.email || "Non spécifié"}</p>
                        </div>
                    </body>
                    </html>
                `);
                iframeDoc.close();

                setTimeout(function () {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    iframe.contentWindow.onafterprint = function () {
                        document.body.removeChild(iframe);
                    };
                }, 500);

                setTimeout(function () {
                    if (iframe.parentNode) {
                        document.body.removeChild(iframe);
                    }
                }, 5000);
            },
            error: function (xhr) {
                console.error("Erreur AJAX:", xhr.responseText);
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: xhr.responseJSON?.message || "Impossible de récupérer les données de la facture.",
                    position: "center",
                    confirmButtonText: "OK"
                });
            }
        });
    });

    // Supprimer une facture
    $(document).on("click", ".delete-facture", function () {
        const $button = $(this);
        const factureId = $button.data("id");
        const type = $button.data("type");
        const table = type === "achat" ? achatTable : venteTable;

        Swal.fire({
            title: "Confirmer la suppression",
            text: "Êtes-vous sûr de vouloir supprimer cette facture ?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Oui, supprimer",
            cancelButtonText: "Annuler",
            position: "center"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/factures/${factureId}`,
                    type: "DELETE",
                    data: { type: type },
                    success: function (response) {
                        Swal.fire({
                            icon: "success",
                            title: "Succès",
                            text: response.message || "Facture supprimée avec succès",
                            position: "center",
                            confirmButtonText: "OK"
                        });
                        updateTotals(type);
                        window.location.reload();
                    },
                    error: function (xhr) {
                        console.error("Erreur:", xhr.responseText);
                        let errorMessage = "Une erreur s'est produite lors de la suppression.";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            text: errorMessage,
                            position: "center",
                            confirmButtonText: "OK"
                        });
                    }
                });
            }
        });
    });

    // Télécharger une facture
    $(document).on("click", ".download-facture:not(:disabled)", function () {
        const $button = $(this);
        const factureId = $button.data("id");
        const type = $button.data("type");

        console.log(`Tentative de téléchargement de la facture ID: ${factureId}, Type: ${type}`);

        $.ajax({
            url: `/factures/${factureId}/download`,
            type: "GET",
            data: { type: type },
            xhrFields: {
                responseType: 'blob'
            },
            beforeSend: function () {
                $button.prop("disabled", true).find("i").removeClass("bx-download").addClass("bx-loader bx-spin");
            },
            success: function (response, status, xhr) {
                const disposition = xhr.getResponseHeader('Content-Disposition');
                let filename = `facture_${factureId}.${type === 'achat' ? 'pdf' : 'pdf'}`;
                if (disposition && disposition.includes('filename')) {
                    const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    const matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }

                const blob = new Blob([response]);
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(link.href);

                console.log(`Fichier téléchargé avec succès pour la facture ID: ${factureId}`);
            },
            error: function (xhr) {
                console.error("Erreur lors du téléchargement de la facture:", xhr.status, xhr.responseText);
                let errorMessage = "Une erreur s'est produite lors du téléchargement.";
                if (xhr.status === 404) {
                    errorMessage = xhr.responseJSON?.message || "Fichier non trouvé.";
                } else if (xhr.status === 400) {
                    errorMessage = xhr.responseJSON?.message || "Type de facture invalide.";
                } else if (xhr.responseJSON?.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: errorMessage,
                    position: "center",
                    confirmButtonText: "OK"
                });
            },
            complete: function () {
                $button.prop("disabled", false).find("i").removeClass("bx-loader bx-spin").addClass("bx-download");
            }
        });
    });

    // Modifier une facture
    $(document).on("click", ".edit-facture", function () {
        const $button = $(this);
        const factureId = $button.data("id");
        const type = $button.data("type");
        currentType = type;

        $.ajax({
            url: `/factures/${factureId}?type=${type}`,
            type: "GET",
            success: function (response) {
                console.log("Données de la facture récupérées:", response);
                if (!response.facture) {
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: "Impossible de récupérer les données de la facture.",
                        position: "center",
                        confirmButtonText: "OK",
                    });
                    return;
                }

                const facture = response.facture;
                $("#factureModalLabel").text(`Modifier une Facture ${type === "achat" ? "d'Achat" : "de Vente"}`);
                $("#isEdit").val("true");
                $("#factureId").val(facture.id);
                $("#type").val(type);
                $("#numero_facture").val(facture.numero_facture || "");
                $("#date_facture").val(facture.date_facture || "");
                $("#raison_sociale").val(facture.raison_sociale || "");
                $("#ice").val(facture.ice || "");
                $("#montant_htt").val(facture.montant_ht ? parseFloat(facture.montant_ht).toFixed(2) : "");
                $("#taux_tva").val(facture.taux_tva ? parseFloat(facture.taux_tva).toFixed(2) : "");
                $("#montant_tva").val(facture.montant_tva ? parseFloat(facture.montant_tva).toFixed(2) : "");
                $("#montant_ttc").val(facture.montant_ttc ? parseFloat(facture.montant_ttc).toFixed(2) : "");
                $("#objet").val(type === "vente" ? (facture.objet || "") : "");
                $("#objet_field").toggle(type === "vente");
                $("#existingFilePath").val(facture.file_path || "");

                const filePreview = $("#filePreview");
                filePreview.empty();
                if (facture.file_path) {
                    const fileExtension = facture.file_path.split(".").pop().toLowerCase();
                    if (["jpg", "jpeg", "png"].includes(fileExtension)) {
                        filePreview.html(
                            `<img src="/storage/${facture.file_path}" alt="Aperçu" style="max-width: 100%; max-height: 150px;" />`
                        );
                    } else if (fileExtension === "pdf") {
                        filePreview.html(
                            `<p class="text-muted">Fichier PDF : <a href="/storage/${facture.file_path}" target="_blank">${facture.file_path.split("/").pop()}</a></p>`
                        );
                    }
                }

                $("#editEntityBtn").prop("disabled", !facture.raison_sociale || !facture.ice);
                $("#montant_htt").prop("readonly", false);
                $("#montant_ttc").prop("readonly", false);
                $("#montant_tva").prop("readonly", isAutoCalcEnabled);
                $("#factureModal").modal("show");

                if (isAutoCalcEnabled) {
                    calculateAmounts();
                }
            },
            error: function (xhr) {
                console.error("Erreur lors de la récupération de la facture:", xhr.responseText);
                let errorMessage = "Une erreur s'est produite lors de la récupération des données.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: errorMessage,
                    position: "center",
                    confirmButtonText: "OK",
                });
            },
        });
    });

    // Changement du fichier
    $("#file").on("change", function (e) {
        const fileInput = this;
        const filePreview = $("#filePreview");
        filePreview.empty();

        if (fileInput.files && fileInput.files[0]) {
            const file = fileInput.files[0];
            const fileExtension = file.name.split('.').pop().toLowerCase();

            if (['jpg', 'jpeg', 'png'].includes(fileExtension)) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    filePreview.html(`<img src="${e.target.result}" alt="Aperçu" style="max-width: 100%; max-height: 150px;" />`);
                };
                reader.readAsDataURL(file);
            } else if (fileExtension === 'pdf') {
                filePreview.html(`<p class="text-muted">Fichier PDF sélectionné : ${file.name}</p>`);
            } else {
                filePreview.html(`<p class="text-danger">Format de fichier non supporté. Veuillez sélectionner un fichier PDF, JPG ou PNG.</p>`);
                fileInput.value = '';
            }
        }
    });

    // Calcul automatique des montants
    $("#montant_htt, #taux_tva, #montant_ttc").on("input", function () {
        if (isAutoCalcEnabled) {
            calculateAmounts();
        }
    });

    // Basculer le mode de calcul automatique
    $("#toggleAutoCalc").on("click", function () {
        isAutoCalcEnabled = !isAutoCalcEnabled;
        updateToggleButton();
        if (isAutoCalcEnabled) {
            calculateAmounts();
        }
    });

    // Soumettre le formulaire de facture
    $("#factureForm").on("submit", function (e) {
        e.preventDefault();
        const isEdit = $("#isEdit").val() === "true";
        const factureId = $("#factureId").val();
        const type = $("#type").val() || currentType;
        const formData = new FormData(this);
        formData.set("type", type);
        formData.set("manual_mode", isAutoCalcEnabled ? "0" : "1");

        // Debug: Log the form data
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }

        const requiredFields = [
            "numero_facture",
            "date_facture",
            "raison_sociale",
            "ice",
            "taux_tva",
            "montant_htt",
            "montant_tva",
            "montant_ttc",
        ];
        let hasEmptyFields = false;
        let emptyFields = [];
        requiredFields.forEach((field) => {
            const value = formData.get(field);
            if (!value || value.trim() === "") {
                console.error(`Champ ${field} est vide`);
                hasEmptyFields = true;
                emptyFields.push(field);
            }
        });

        if (hasEmptyFields) {
            Swal.fire({
                icon: "error",
                title: "Erreur de validation",
                text: `Veuillez remplir tous les champs obligatoires: ${emptyFields.join(", ")}`,
                position: "center",
                confirmButtonText: "OK",
            });
            return;
        }

        const montantHTT = parseFloat($("#montant_htt").val()) || 0;
        const tauxTVA = parseFloat($("#taux_tva").val()) || 0;
        const montantTVA = parseFloat($("#montant_tva").val()) || 0;
        const montantTTC = parseFloat($("#montant_ttc").val()) || 0;

        formData.set("montant_htt", montantHTT.toFixed(2));
        formData.set("taux_tva", tauxTVA.toFixed(2));
        formData.set("montant_tva", montantTVA.toFixed(2));
        formData.set("montant_ttc", montantTTC.toFixed(2));

        const submitBtn = $("#submitBtn");
        submitBtn
            .prop("disabled", true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...');

        const ajaxConfig = {
            data: formData,
            processData: false,
            contentType: false,
        };

        if (isEdit) {
            ajaxConfig.url = `/factures/${factureId}`;
            ajaxConfig.type = "POST";
            formData.append("_method", "PUT");
        } else {
            ajaxConfig.url = "/factures";
            ajaxConfig.type = "POST";
        }

        $.ajax({
            ...ajaxConfig,
            success: function (response) {
                submitBtn.prop("disabled", false).html("Enregistrer");
                $("#factureModal").modal("hide");
                Swal.fire({
                    icon: "success",
                    title: "Succès",
                    text: response.message || (isEdit ? "Facture modifiée avec succès" : "Facture créée avec succès"),
                    position: "center",
                    confirmButtonText: "OK",
                }).then(() => {
                    updateTotals(type);
                    window.location.reload();
                });
            },
           error: function (xhr) {
    submitBtn.prop("disabled", false).html("Enregistrer");
    console.error("Erreur AJAX:", xhr);
    let errorMessage = "Une erreur s'est produite lors de l'enregistrement.";
    
    if (xhr.responseJSON) {
        // ✅ Priorité aux erreurs de champs (doublon ICE + numéro facture)
        if (xhr.responseJSON.errors && Object.keys(xhr.responseJSON.errors).length > 0) {
            let errors = [];
            Object.keys(xhr.responseJSON.errors).forEach((field) => {
                errors.push(...xhr.responseJSON.errors[field]);
            });
            errorMessage = errors.join("<br>");
        } else if (xhr.responseJSON.message) {
            // Message générique uniquement si pas d'erreurs de champs
            errorMessage = xhr.responseJSON.message;
        }
    }
    
    Swal.fire({
        icon: "error",
        title: "Erreur de validation",
        html: errorMessage,   // ✅ html pour supporter les sauts de ligne
        position: "center",
        confirmButtonText: "OK",
    });
    console.log("Erreur complète:", xhr.responseText);
},
        });
    });

    // Afficher le modal pour ajouter une entité
    $("#addEntityBtn").on("click", function () {
        $("#entityModal").modal("show");
        $("#entityForm")[0].reset();
        $("#entityForm .is-invalid").removeClass("is-invalid");
        $("#entityForm .invalid-feedback").empty();
    });

    // Soumettre le formulaire d'entité
    $("#entityForm").on("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = $("#submitEntityBtn");
        submitBtn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...');

        $.ajax({
            url: '/entities',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                submitBtn.prop("disabled", false).html("Enregistrer");
                $("#entityModal").modal("hide");
                Swal.fire({
                    icon: "success",
                    title: "Succès",
                    text: response.message,
                    position: "center",
                    confirmButtonText: "OK"
                }).then(() => {
                    $("#raison_sociale").append(`<option value="${response.raison_sociale}" data-ice="${response.ice}">${response.raison_sociale}</option>`);
                    $("#ice").append(`<option value="${response.ice}">${response.ice}</option>`);
                    $("#factureModal").modal("show");
                });
            },
            error: function (xhr) {
                submitBtn.prop("disabled", false).html("Enregistrer");
                let errorMessage = "Une erreur s'est produite lors de l'ajout de l'entité.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 422) {
                    errorMessage = "Validation échouée. Vérifiez les champs.";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        console.log("Erreurs de validation :", xhr.responseJSON.errors);
                    }
                }
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: errorMessage,
                    position: "center",
                    confirmButtonText: "OK"
                });
                console.log("Réponse complète :", xhr.responseText);
            }
        });
    });
    // Gérer l'ouverture du modal d'importation
$(document).on("click", "[data-bs-target='#importExcelModal']", function () {
    const type = $(this).data("type");
    $("#importType").val(type);
    $("#importExcelModalLabel").text(`Importer un fichier Excel - Factures ${type === "achat" ? "d'Achat" : "de Vente"}`);
    $("#importExcelForm")[0].reset();
    $("#excelFile").removeClass("is-invalid");
    $("#excelFile").next(".invalid-feedback").empty();
});

// Soumettre le formulaire d'importation Excel
$(document).on("click", "[data-bs-target='#importExcelModal']", function () {
    const type = $(this).data("type");
    $("#importType").val(type);
    $("#importExcelModalLabel").text(`Importer un fichier Excel - Factures ${type === "achat" ? "d'Achat" : "de Vente"}`);
    $("#importExcelForm")[0].reset();
    $("#excelFile").removeClass("is-invalid");
    $("#excelFile").next(".invalid-feedback").empty();
});

$("#importExcelForm").on("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitBtn = $("#submitExcelBtn");

    submitBtn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Importation...');

    $.ajax({
        url: "/factures/import-excel",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            submitBtn.prop("disabled", false).html("Importer");
            $("#importExcelModal").modal("hide");
            Swal.fire({
                icon: "success",
                title: "Succès",
                text: response.message || "Factures importées avec succès",
                position: "center",
                confirmButtonText: "OK",
            }).then(() => {
                window.location.reload();
            });
        },
        error: function (xhr) {
            submitBtn.prop("disabled", false).html("Importer");
            let errorMessage = "Une erreur s'est produite lors de l'importation.";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                errorMessage = Object.values(xhr.responseJSON.errors).join("<br>");
            }
            Swal.fire({
                icon: "error",
                title: "Erreur",
                html: errorMessage,
                position: "center",
                confirmButtonText: "OK",
            });
            if (xhr.status === 422) {
                $("#excelFile").addClass("is-invalid");
                $("#excelFile").next(".invalid-feedback").html(errorMessage);
            }
        },
    });
});
// Download Excel Template
$(document).on("click", "#downloadTemplateAchat, #downloadTemplateVente", function () {
    const type = $(this).attr("id") === "downloadTemplateAchat" ? "achat" : "vente";
    const $button = $(this);

    console.log(`Tentative de téléchargement du modèle Excel pour type: ${type}`);

    $.ajax({
        url: `/factures/download-template/${type}`,
        type: "GET",
        xhrFields: {
            responseType: 'blob' // Important for handling binary data
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            $button.prop("disabled", true).find("i").removeClass("bx-download").addClass("bx-loader bx-spin");
        },
        success: function (response, status, xhr) {
            const disposition = xhr.getResponseHeader('Content-Disposition');
            let filename = `template_${type}.xlsx`;
            if (disposition && disposition.includes('filename')) {
                const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                const matches = filenameRegex.exec(disposition);
                if (matches != null && matches[1]) {
                    filename = matches[1].replace(/['"]/g, '');
                }
            }

            const blob = new Blob([response]);
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(link.href);

            console.log(`Modèle Excel téléchargé avec succès pour type: ${type}`);
        },
        error: function (xhr) {
            console.error("Erreur lors du téléchargement du modèle Excel:", xhr.status, xhr.responseText);
            let errorMessage = "Une erreur s'est produite lors du téléchargement du modèle.";
            if (xhr.status === 404) {
                errorMessage = xhr.responseJSON?.message || "Modèle Excel non trouvé.";
            } else if (xhr.responseJSON?.message) {
                errorMessage = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: errorMessage,
                position: "center",
                confirmButtonText: "OK"
            });
        },
        complete: function () {
            $button.prop("disabled", false).find("i").removeClass("bx-loader bx-spin").addClass("bx-download");
        }
    });
});


// ===============================================
// GESTION DES RELEVÉS
// ===============================================

// Initialiser DataTable pour relevés (même style que achat/vente)
if ($.fn.DataTable && $('#relevesTable').length) {
    const relevesTable = $('#relevesTable').DataTable({
        dom:
            '<"row ms-2 me-3"' +
            '<"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls"l<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3"B>>' +
            '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter"f<"dt-filter mb-3 mb-md-0">>' +
            ">t" +
            '<"row mx-2"' +
            '<"col-sm-12 col-md-6"i>' +
            '<"col-sm-12 col-md-6"p>' +
            ">",
        displayLength: 10,
        lengthMenu: [10, 25, 50, 75, 100],
        ordering: true,
        order: [[0, 'desc']],
        buttons: [
            {
                text: '<i class="bx bx-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Ajouter Relevé</span>',
                className: "btn btn-primary",
                action: function () {
                    $("#releveForm")[0].reset();
                    $("#releveFilePreview").empty();
                    $("#releveModal").modal("show");
                }
            },
            {
                extend: "collection",
                className: "btn btn-label-primary dropdown-toggle me-2 export-btn",
                text: '<i class="bx bx-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Exporter</span>',
                buttons: [
                    {
                        extend: "excel",
                        text: '<i class="bx bxs-file-export me-1"></i>Excel',
                        className: "dropdown-item",
                        exportOptions: { columns: [0, 1, 2, 3,4] }
                    },
                    {
                        extend: "pdf",
                        text: '<i class="bx bxs-file-pdf me-1"></i>PDF',
                        className: "dropdown-item",
                        exportOptions: { columns: [0, 1, 2, 3,4] }
                    }
                ]
            }
        ],
        responsive: true,
        orderCellsTop: true,
        language: {
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ ",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            infoEmpty: "Aucun élément à afficher",
            infoFiltered: "(filtré à partir de _MAX_ éléments au total)",
            paginate: {
                first: "Premier",
                last: "Dernier",
                next: "Suivant",
                previous: "Précédent"
            }
        },
        initComplete: function () {
            // Ajouter la recherche par colonne (colonne 4 = Actions → non searchable)
            addColumnSearch('#relevesTable', this.api(), [5]);
        }
    });
}

// Ouvrir modal ajout relevé
$(document).on("click", "#addReleveBtn", function () {
    $("#releveForm")[0].reset();
    $("#releveFilePreview").empty();
    $("#releveModal").modal("show");
});

// Aperçu fichier relevé
$("#releve_file").on("change", function () {
    const file = this.files[0];
    const preview = $("#releveFilePreview");
    preview.empty();
    if (!file) return;
    const ext = file.name.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'png'].includes(ext)) {
        const reader = new FileReader();
        reader.onload = e => preview.html(`<img src="${e.target.result}" style="max-width:100%;max-height:150px;" />`);
        reader.readAsDataURL(file);
    } else if (ext === 'pdf') {
        preview.html(`<p class="text-muted">Fichier PDF : ${file.name}</p>`);
    }
});

// Soumettre le formulaire relevé
$("#releveForm").on("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitBtn = $("#submitReleveBtn");

    submitBtn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Enregistrement...');

    $.ajax({
        url: "/releves",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (response) {
            submitBtn.prop("disabled", false).html("Enregistrer");
            $("#releveModal").modal("hide");
            Swal.fire({
                icon: "success",
                title: "Succès",
                text: response.message || "Relevé ajouté avec succès",
                confirmButtonText: "OK"
            }).then(() => window.location.reload());
        },
        error: function (xhr) {
            submitBtn.prop("disabled", false).html("Enregistrer");
            let errorMessage = "Une erreur s'est produite.";
            if (xhr.responseJSON && xhr.responseJSON.errors && Object.keys(xhr.responseJSON.errors).length > 0) {
                errorMessage = Object.values(xhr.responseJSON.errors).flat().join("<br>");
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            Swal.fire({ icon: "error", title: "Erreur", html: errorMessage, confirmButtonText: "OK" });
        }
    });
});

// Supprimer un relevé
$(document).on("click", ".delete-releve", function () {
    const id = $(this).data("id");
    Swal.fire({
        title: "Confirmer la suppression",
        text: "Êtes-vous sûr de vouloir supprimer ce relevé ?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Oui, supprimer",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/releves/${id}`,
                type: "DELETE",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Swal.fire({ icon: "success", title: "Succès", text: response.message, confirmButtonText: "OK" })
                        .then(() => window.location.reload());
                },
                error: function () {
                    Swal.fire({ icon: "error", title: "Erreur", text: "Erreur lors de la suppression.", confirmButtonText: "OK" });
                }
            });
        }
    });
});

// Télécharger un relevé
$(document).on("click", ".download-releve:not(:disabled)", function () {
    const id = $(this).data("id");
    const $btn = $(this);
    $btn.prop("disabled", true).find("i").removeClass("bx-download").addClass("bx-loader bx-spin");

    $.ajax({
        url: `/releves/${id}/download`,
        type: "GET",
        xhrFields: { responseType: 'blob' },
        success: function (response, status, xhr) {
            const disposition = xhr.getResponseHeader('Content-Disposition');
            let filename = `releve_${id}.pdf`;
            if (disposition) {
                const match = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                if (match && match[1]) filename = match[1].replace(/['"]/g, '');
            }
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(new Blob([response]));
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        error: function () {
            Swal.fire({ icon: "error", title: "Erreur", text: "Erreur lors du téléchargement.", confirmButtonText: "OK" });
        },
        complete: function () {
            $btn.prop("disabled", false).find("i").removeClass("bx-loader bx-spin").addClass("bx-download");
        }
    });
});

// ===============================================
// GESTION DU PAIEMENT
// ===============================================

// Afficher/masquer la date selon la case cochée
$(document).on("change", "#payee_check", function () {
    if ($(this).is(":checked")) {
        $("#date_paiement_field").show();
        // Mettre la date d'aujourd'hui par défaut si vide
        if (!$("#date_paiement_input").val()) {
            $("#date_paiement_input").val(new Date().toISOString().split('T')[0]);
        }
    } else {
        $("#date_paiement_field").hide();
        $("#date_paiement_input").val("");
    }
});

// Ouvrir le modal paiement
$(document).on("click", ".paiement-facture", function () {
    const id      = $(this).data("id");
    const payee   = $(this).data("payee");
    const date    = $(this).data("date");

    $("#paiementFactureId").val(id);

    // Remplir les champs selon l'état actuel
    if (payee == 1) {
        $("#payee_check").prop("checked", true);
        $("#date_paiement_field").show();
        $("#date_paiement_input").val(date || "");
    } else {
        $("#payee_check").prop("checked", false);
        $("#date_paiement_field").hide();
        $("#date_paiement_input").val("");
    }

    $("#paiementModal").modal("show");
});

// Soumettre le formulaire paiement
$("#paiementForm").on("submit", function (e) {
    e.preventDefault();

    const id        = $("#paiementFactureId").val();
    const payee     = $("#payee_check").is(":checked") ? 1 : 0;
    const datePaie  = $("#date_paiement_input").val();
    const submitBtn = $("#submitPaiementBtn");

    // Validation : si payée, la date est obligatoire
    if (payee === 1 && !datePaie) {
        Swal.fire({
            icon: "warning",
            title: "Date requise",
            text: "Veuillez saisir la date de paiement.",
            confirmButtonText: "OK"
        });
        return;
    }

    submitBtn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Enregistrement...');

    $.ajax({
        url: `/factures/${id}/paiement`,
        type: "POST",
        data: {
            _method:        "PUT",
            payee:          payee,
            date_paiement:  datePaie,
            _token:         $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            submitBtn.prop("disabled", false).html("Enregistrer");
            $("#paiementModal").modal("hide");
            Swal.fire({
                icon: "success",
                title: "Succès",
                text: response.message,
                confirmButtonText: "OK",
                timer: 2000,
                timerProgressBar: true
            }).then(() => window.location.reload());
        },
        error: function (xhr) {
            submitBtn.prop("disabled", false).html("Enregistrer");
            let errorMessage = "Une erreur s'est produite.";
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                errorMessage = Object.values(xhr.responseJSON.errors).flat().join("<br>");
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: "error",
                title: "Erreur",
                timer: 8000,
                html: errorMessage,
                allowOutsideClick: false,
                confirmButtonText: "OK"
            });
        }
    });
});

// ===============================================
// GESTION DU PAIEMENT / ENCAISSEMENT (ACHAT + VENTE)
// ===============================================

$(document).on("click", ".paiement-facture, .encaissement-facture", function () {
    const $btn = $(this);
    const id   = $btn.data("id");
    const type = $btn.data("type");           // achat ou vente
    const etat = $btn.data("payee") || $btn.data("encaisser") || 0;
    const date = $btn.data("date") || "";

    $("#paiementFactureId").val(id);
    $("#paiementFactureType").val(type);

    // Adapter le libellé selon le type
    if (type === "achat") {
        $("#payeeLabel").text("Facture payée");
        $("#paiementModalLabel").text("Statut de Paiement (Achat)");
    } else {
        $("#payeeLabel").text("Facture encaissée");
        $("#paiementModalLabel").text("Statut d'Encaissement (Vente)");
    }

    $("#payee_check").prop("checked", etat == 1);
    $("#date_paiement_input").val(date);

    if (etat == 1) {
        $("#date_paiement_field").show();
    } else {
        $("#date_paiement_field").hide();
    }

    $("#paiementModal").modal("show");
});

// Afficher/masquer la date
$(document).on("change", "#payee_check", function () {
    if ($(this).is(":checked")) {
        $("#date_paiement_field").show();
        if (!$("#date_paiement_input").val()) {
            $("#date_paiement_input").val(new Date().toISOString().split('T')[0]);
        }
    } else {
        $("#date_paiement_field").hide();
        $("#date_paiement_input").val("");
    }
});

// Soumission
$("#paiementForm").on("submit", function (e) {
    e.preventDefault();

    const id     = $("#paiementFactureId").val();
    const type   = $("#paiementFactureType").val();
    const payee  = $("#payee_check").is(":checked") ? 1 : 0;
    const date   = $("#date_paiement_input").val();
    const btn    = $("#submitPaiementBtn");

    if (payee === 1 && !date) {
        Swal.fire("Attention", "Veuillez indiquer la date de paiement/encaissement", "warning");
        return;
    }

    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');

    let url = type === "achat" 
        ? `/factures/${id}/paiement` 
        : `/factures/${id}/encaissement`;

    $.ajax({
        url: url,
        type: "POST",
        data: {
            _method: "PUT",
            payee: payee,               // on garde le même nom pour compatibilité
            date_paiement: date,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            btn.prop("disabled", false).html("Enregistrer");
            $("#paiementModal").modal("hide");
            Swal.fire("Succès", res.message || "Statut mis à jour", "success")
                .then(() => location.reload());
        },
        error: function (xhr) {
            btn.prop("disabled", false).html("Enregistrer");
            Swal.fire("Erreur", xhr.responseJSON?.message || "Erreur lors de la mise à jour", "error");
        }
    });
});


// REMPLACER le bloc $("#scan_file").on("change", ...) par :
$("#scan_file").on("change", function () {
    const file = this.files[0];
    const previewBox  = $("#scanPreview");
    const previewImg  = $("#scanPreviewImg");
    const previewPdf  = $("#scanPreviewPdf");
    const previewName = $("#scanPreviewPdfName");

    if (!file) {
        previewBox.hide();
        return;
    }

    const ext = file.name.split('.').pop().toLowerCase();
    previewBox.show();

    if (['jpg', 'jpeg', 'png'].includes(ext)) {
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.attr('src', e.target.result).show();
            previewPdf.hide();
        };
        reader.readAsDataURL(file);
    } else if (ext === 'pdf') {
        previewImg.hide();
        previewPdf.show();
        previewName.text(file.name);
    }
});
// --- Bouton Scanner ---
$("#scanFactureBtn").on("click", function () {
    const fileInput = document.getElementById('scan_file');
    const file = fileInput.files[0];

    if (!file) {
        showScanAlert('warning', 'Veuillez sélectionner un fichier à scanner.');
        return;
    }

    // UI : état chargement
    const $btn = $("#scanFactureBtn");
    const $txt = $("#scanBtnText");
    $btn.prop("disabled", true);
    $txt.html('<span class="spinner-border spinner-border-sm me-1"></span>Analyse en cours...');
    hideScanAlert();

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

    $.ajax({
        url: '/factures/scan',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            $btn.prop("disabled", false);
            $txt.html('<i class="bx bx-analyse me-1"></i>Scanner la facture');

            if (!response.success) {
                showScanAlert('danger', response.message || 'Erreur lors du scan.');
                return;
            }

            const d = response.data;

            // ── Remplissage des champs du formulaire ──────────────────

            // Numéro de facture
            if (d.numero_facture) {
                $("#numero_facture").val(d.numero_facture);
            }

            // Date de facture
            if (d.date_facture) {
                $("#date_facture").val(d.date_facture);
            }

            // Montant HT
            if (d.montant_ht !== null && d.montant_ht !== undefined) {
                $("#montant_htt").val(parseFloat(d.montant_ht).toFixed(2));
            }

            // Taux TVA
            if (d.taux_tva !== null && d.taux_tva !== undefined) {
                $("#taux_tva").val(parseFloat(d.taux_tva).toFixed(2));
            }

            // Montant TVA (débloquer temporairement pour écrire)
            if (d.montant_tva !== null && d.montant_tva !== undefined) {
                $("#montant_tva").prop("readonly", false)
                                 .val(parseFloat(d.montant_tva).toFixed(2));
                // Remettre readonly si mode auto activé
                if (isAutoCalcEnabled) {
                    $("#montant_tva").prop("readonly", true);
                }
            }

            // Montant TTC
            if (d.montant_ttc !== null && d.montant_ttc !== undefined) {
                $("#montant_ttc").val(parseFloat(d.montant_ttc).toFixed(2));
            }

            // ── ICE + Raison Sociale ──────────────────────────────────
            let iceFound    = false;
            let raisonFound = false;

            if (d.ice) {
                const iceClean = d.ice.replace(/\D/g, ''); // garder seulement les chiffres

                // Chercher l'ICE dans le select #ice
                $("#ice option").each(function () {
                    const optIce = $(this).val().replace(/\D/g, '');
                    if (optIce && optIce === iceClean) {
                        $("#ice").val($(this).val());
                        iceFound = true;
                        return false; // break
                    }
                });

                // Si ICE trouvé → synchroniser Raison Sociale
                if (iceFound) {
                    const matchingRaison = $("#raison_sociale option").filter(function () {
                        const optIce = ($(this).data("ice") || "").toString().replace(/\D/g, '');
                        return optIce === iceClean;
                    });
                    if (matchingRaison.length) {
                        $("#raison_sociale").val(matchingRaison.val());
                        raisonFound = true;
                    }
                }
            }

            // ── Raison Sociale (si ICE non trouvé, essayer par nom) ──
            if (!raisonFound && d.raison_sociale) {
                const raisonLower = d.raison_sociale.toLowerCase().trim();
                $("#raison_sociale option").each(function () {
                    if ($(this).val().toLowerCase().trim() === raisonLower) {
                        $("#raison_sociale").val($(this).val());
                        raisonFound = true;
                        // Synchroniser ICE
                        const iceVal = $(this).data("ice");
                        if (iceVal) $("#ice").val(iceVal);
                        return false; // break
                    }
                });
            }

            // ── Message de résultat ───────────────────────────────────
            if (!iceFound && d.ice) {
                // ICE scanné mais absent dans la liste → avertissement avec info
                showScanAlert('warning',
                    `✅ Champs financiers remplis avec succès.<br>
                     ⚠️ <strong>ICE ${d.ice}</strong> (${d.raison_sociale || 'fournisseur inconnu'}) non trouvé dans la liste.<br>
                     Veuillez ajouter ce fournisseur via le bouton <i class="bx bx-building"></i> ou saisir manuellement.`
                );
            } else if (iceFound) {
                showScanAlert('success',
                    `✅ Scan réussi ! Tous les champs ont été remplis automatiquement.<br>
                     <small class="text-muted">Vérifiez les valeurs avant d'enregistrer.</small>`
                );
            } else {
                showScanAlert('success',
                    `✅ Champs financiers remplis. Veuillez sélectionner le fournisseur manuellement.`
                );
            }
        },
        error: function (xhr) {
            $btn.prop("disabled", false);
            $txt.html('<i class="bx bx-analyse me-1"></i>Scanner la facture');

            let errorMessage = "Erreur lors du scan de la facture.";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showScanAlert('danger', '❌ ' + errorMessage);
        }
    });
});

// --- Fonctions utilitaires pour les alertes du scan ---
function showScanAlert(type, message) {
    const $alert = $("#scanAlert");
    $alert.removeClass('alert-success alert-danger alert-warning alert-info')
          .addClass('alert-' + type)
          .html(message)
          .show();
}

function hideScanAlert() {
    $("#scanAlert").hide().html('');
}

// Réinitialiser la zone scan à l'ouverture du modal
$("#factureModal").on("show.bs.modal", function () {
    $("#scan_file").val('');
    $("#scanPreview").hide();
    $("#scanPreviewImg").attr('src', '').hide();
    $("#scanPreviewPdf").hide();
    hideScanAlert();
    $("#scanBtnText").html('<i class="bx bx-analyse me-1"></i>Scanner la facture');
    $("#scanFactureBtn").prop("disabled", false);
});


// ===============================================
// SCAN IA RELEVÉ BANCAIRE — MULTI-IMAGES
// ===============================================

let releveScanData = { debit: [], credit: [] };


// REMPLACER le bloc existant par :
$("#releve_scan_file").on("change", function () {
    const files = Array.from(this.files);
    const $grid = $("#releveScamPreviewGrid");
    $grid.empty();

    if (files.length === 0) {
        $grid.hide();
        return;
    }

    $grid.css('display', 'flex');

    files.forEach(function (file, idx) {
        const ext = file.name.split('.').pop().toLowerCase();

        if (['jpg', 'jpeg', 'png'].includes(ext)) {
            // ── Image : aperçu réel ──
            const reader = new FileReader();
            reader.onload = function (e) {
                $grid.append(`
                    <div class="position-relative border rounded overflow-hidden"
                         style="width:90px;height:90px;flex-shrink:0;">
                        <img src="${e.target.result}"
                             style="width:100%;height:100%;object-fit:cover;"
                             title="${file.name}">
                        <span class="position-absolute bottom-0 start-0 w-100 text-center
                                     bg-dark bg-opacity-50 text-white"
                              style="font-size:9px;padding:1px 0;">
                            Page ${idx + 1}
                        </span>
                    </div>
                `);
            };
            reader.readAsDataURL(file);

        } else if (ext === 'pdf') {
            // ── PDF : icône générique ──
            const shortName = file.name.length > 12
                ? file.name.substring(0, 10) + '…'
                : file.name;
            $grid.append(`
                <div class="position-relative border rounded overflow-hidden d-flex flex-column
                             align-items-center justify-content-center bg-light"
                     style="width:90px;height:90px;flex-shrink:0;" title="${file.name}">
                    <i class="bx bxs-file-pdf text-danger" style="font-size:2.2rem;"></i>
                    <small class="text-muted text-center px-1" style="font-size:9px;word-break:break-all;">
                        ${shortName}
                    </small>
                    <span class="position-absolute bottom-0 start-0 w-100 text-center
                                 bg-dark bg-opacity-50 text-white"
                          style="font-size:9px;padding:1px 0;">
                        Fichier ${idx + 1}
                    </span>
                </div>
            `);
        }
    });
});

// ── Fonctions utilitaires ──
function showReleveScanAlert(type, message) {
    $("#releveScamAlert")
        .removeClass('alert-success alert-danger alert-warning alert-info')
        .addClass('alert-' + type)
        .html(message)
        .show();
}

function calcTotal(lignes) {
    return (lignes || []).reduce((s, l) => s + (parseFloat(l.montant) || 0), 0);
}

function buildReleveTable(bodyId, lignes, type) {
    const $body = $("#" + bodyId);
    $body.empty();

    if (!lignes || lignes.length === 0) {
        $body.html('<tr><td colspan="6" class="text-center text-muted py-2">Aucune opération détectée</td></tr>');
        return;
    }

    lignes.forEach(function (ligne, idx) {
        const rowClass = type === 'debit' ? 'table-danger' : 'table-success';
        const pageBadge = ligne._page
            ? `<span class="badge bg-secondary ms-1" style="font-size:9px;">p.${ligne._page}</span>`
            : '';
        $body.append(`
            <tr class="${rowClass}" data-idx="${idx}" data-type="${type}">
                <td><input type="date"   class="form-control form-control-sm releve-row-date"
                           value="${ligne.date || ''}" style="min-width:120px;"></td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <input type="text" class="form-control form-control-sm releve-row-libelle"
                               value="${ligne.libelle || ''}" style="min-width:150px;">
                        ${pageBadge}
                    </div>
                </td>
                <td><input type="number" class="form-control form-control-sm releve-row-montant"
                           value="${ligne.montant !== null && ligne.montant !== undefined ? ligne.montant : ''}"
                           step="0.01" min="0" style="min-width:100px;"></td>
                <td><input type="text"   class="form-control form-control-sm releve-row-reference"
                           value="${ligne.reference || ''}" style="min-width:100px;"></td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger releve-delete-row"
                            title="Supprimer"><i class="bx bx-trash"></i></button>
                </td>
            </tr>
        `);
    });
}

function readReleveTableData(bodyId) {
    const lignes = [];
    $("#" + bodyId + " tr").each(function () {
        const date      = $(this).find('.releve-row-date').val()      || null;
        const libelle   = $(this).find('.releve-row-libelle').val()   || null;
        const montant   = $(this).find('.releve-row-montant').val();
        const reference = $(this).find('.releve-row-reference').val() || null;
        if (libelle || montant) {
            lignes.push({
                date,
                libelle,
                montant : montant !== '' ? parseFloat(montant) : null,
                reference
            });
        }
    });
    return lignes;
}

function updateReleveTotals() {
    const debitLignes  = readReleveTableData('releveDebitBody');
    const creditLignes = readReleveTableData('releveCreditBody');

    $("#releveDebitCount").text(debitLignes.length);
    $("#releveCreditCount").text(creditLignes.length);
    $("#releveDebitTotal").text(
        calcTotal(debitLignes).toLocaleString('fr-FR', { minimumFractionDigits: 2 })
    );
    $("#releveCreditTotal").text(
        calcTotal(creditLignes).toLocaleString('fr-FR', { minimumFractionDigits: 2 })
    );

    $("#releve_debit_json").val(debitLignes.length  > 0 ? JSON.stringify(debitLignes)  : '');
    $("#releve_credit_json").val(creditLignes.length > 0 ? JSON.stringify(creditLignes) : '');
}

// Supprimer une ligne
$(document).on("click", ".releve-delete-row", function () {
    $(this).closest('tr').remove();
    updateReleveTotals();
});

// Recalcul en temps réel
$(document).on("input", ".releve-row-montant, .releve-row-libelle, .releve-row-date, .releve-row-reference",
    function () { updateReleveTotals(); }
);

// ── Bouton Scanner ──
$("#scanReleveBtn").on("click", function () {
    const fileInput = document.getElementById('releve_scan_file');
    const files     = fileInput ? Array.from(fileInput.files) : [];

    if (files.length === 0) {
        showReleveScanAlert('warning', 'Veuillez sélectionner au moins un fichier à scanner.');
        return;
    }

    const $btn      = $("#scanReleveBtn");
    const $label    = $("#releveScanProgressLabel");
    const $count    = $("#releveScanProgressCount");
    const $bar      = $("#releveScanProgressBar");
    const $wrapper  = $("#releveScanProgressWrapper");

    $btn.prop("disabled", true);
    $("#scanReleveBtnText").html('<span class="spinner-border spinner-border-sm me-1"></span>Analyse...');
    $("#releveScamAlert").hide();
    $wrapper.show();

    const total     = files.length;
    let   done      = 0;
    let   allDebit  = [];
    let   allCredit = [];
    let   errors    = [];

    // Réinitialiser les tableaux
    $("#releveDebitBody").empty();
    $("#releveCreditBody").empty();
    $("#releveDebitSection").hide();
    $("#releveCreditSection").hide();

    function updateProgress() {
        const pct = Math.round((done / total) * 100);
        $bar.css('width', pct + '%');
        $count.text(`${done} / ${total}`);
        $label.text(done < total ? `Analyse du fichier ${done + 1}...` : 'Finalisation...');
    }

    // Scanner les fichiers séquentiellement
    function scanNext(index) {
        if (index >= total) {
            // ── Tous les fichiers traités ──
            $btn.prop("disabled", false);
            $("#scanReleveBtnText").html('<i class="bx bx-analyse me-1"></i>Scanner le relevé');
            $wrapper.hide();

            releveScanData = { debit: allDebit, credit: allCredit };

            buildReleveTable('releveDebitBody',  allDebit,  'debit');
            buildReleveTable('releveCreditBody', allCredit, 'credit');

            if (allDebit.length > 0 || allCredit.length > 0) {
                $("#releveDebitSection").show();
                $("#releveCreditSection").show();
            }

            updateReleveTotals();

            const totalDebitAmt  = calcTotal(allDebit);
            const totalCreditAmt = calcTotal(allCredit);

            let msg = `✅ <strong>${total}</strong> fichier(s) analysé(s).<br>
                       <strong>${allDebit.length}</strong> débit(s) — Total : <strong>${totalDebitAmt.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} DH</strong><br>
                       <strong>${allCredit.length}</strong> crédit(s) — Total : <strong>${totalCreditAmt.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} DH</strong>`;

            if (errors.length > 0) {
                msg += `<br><span class="text-warning">⚠️ ${errors.length} fichier(s) non lu(s) : ${errors.join(', ')}</span>`;
            }
            msg += `<br><small class="text-muted">Vous pouvez modifier les valeurs avant d'enregistrer.</small>`;

            showReleveScanAlert(errors.length === total ? 'danger' : 'success', msg);
            return;
        }

        updateProgress();

        const file = files[index];
        const ext  = file.name.split('.').pop().toLowerCase();
        const isPdf = ext === 'pdf';

        const formData = new FormData();
        formData.append('files[]', file);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        // Mise à jour du label selon le type de fichier
        $label.text(isPdf
            ? `Analyse du PDF "${file.name}" (peut prendre quelques secondes)...`
            : `Analyse de l'image ${index + 1} / ${total}...`
        );

        $.ajax({
            url: '/releves/scan',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            // Timeout plus long pour les PDFs multi-pages
            timeout: isPdf ? 120000 : 60000,
            success: function (response) {
                done++;
                if (response.success && response.data) {
                    // Numéroter les pages selon l'index du fichier
                    const pageOffset = index + 1;
                    const pageDebit  = (response.data.debit  || []).map(l => ({
                        ...l,
                        _page: l._page ? `F${pageOffset}-P${l._page}` : pageOffset
                    }));
                    const pageCredit = (response.data.credit || []).map(l => ({
                        ...l,
                        _page: l._page ? `F${pageOffset}-P${l._page}` : pageOffset
                    }));
                    allDebit  = allDebit.concat(pageDebit);
                    allCredit = allCredit.concat(pageCredit);

                    // Info sur les pages scannées (surtout utile pour PDFs)
                    if (isPdf && response.pages_scanned) {
                        $label.text(`PDF analysé : ${response.pages_scanned} page(s) traitée(s)`);
                    }
                } else {
                    errors.push(file.name);
                }
                scanNext(index + 1);
            },
            error: function (xhr) {
                done++;
                const errMsg = xhr.responseJSON?.message || 'Erreur serveur';
                errors.push(`${file.name} (${errMsg})`);
                scanNext(index + 1);
            }
        });
    }

    scanNext(0);
});

// ── Réinitialiser à l'ouverture du modal ──
$("#releveModal").on("show.bs.modal", function () {
    const fi = document.getElementById('releve_scan_file');
    if (fi) fi.value = '';

    $("#releveScamPreviewGrid").empty().hide();
    $("#releveScamAlert").hide().html('');
    $("#releveScanProgressWrapper").hide();
    $("#releveDebitSection").hide();
    $("#releveCreditSection").hide();
    $("#releveDebitBody").empty();
    $("#releveCreditBody").empty();
    $("#releve_debit_json").val('');
    $("#releve_credit_json").val('');
    $("#scanReleveBtnText").html('<i class="bx bx-analyse me-1"></i>Scanner le relevé');
    $("#scanReleveBtn").prop("disabled", false);
    releveScanData = { debit: [], credit: [] };
});

// ── Avant soumission : synchroniser les JSON cachés ──
$("#releveForm").on("submit", function () {
    const debitLignes  = readReleveTableData('releveDebitBody');
    const creditLignes = readReleveTableData('releveCreditBody');
    $("#releve_debit_json").val(debitLignes.length  > 0 ? JSON.stringify(debitLignes)  : '');
    $("#releve_credit_json").val(creditLignes.length > 0 ? JSON.stringify(creditLignes) : '');
});



// ── Fonction pour ajouter une ligne vide manuellement ──
function addEmptyReleveRow(bodyId, type) {
    const today = new Date().toISOString().split('T')[0];
    const rowClass = type === 'debit' ? 'table-danger' : 'table-success';
    const newIdx = $("#" + bodyId + " tr").length;

    const newRow = `
        <tr class="${rowClass}" data-idx="${newIdx}" data-type="${type}">
            <td><input type="date"   class="form-control form-control-sm releve-row-date"
                       value="${today}" style="min-width:120px;"></td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <input type="text" class="form-control form-control-sm releve-row-libelle"
                           placeholder="Libellé..." style="min-width:150px;">
                </div>
            </td>
            <td><input type="number" class="form-control form-control-sm releve-row-montant"
                       placeholder="0.00" step="0.01" min="0" style="min-width:100px;"></td>
            <td><input type="text"   class="form-control form-control-sm releve-row-reference"
                       placeholder="Référence..." style="min-width:100px;"></td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger releve-delete-row"
                        title="Supprimer"><i class="bx bx-trash"></i></button>
            </td>
        </tr>
    `;

    $("#" + bodyId).append(newRow);

    // Afficher la section si elle était cachée
    if (bodyId === 'releveDebitBody') {
        $("#releveDebitSection").show();
    } else {
        $("#releveCreditSection").show();
    }

    updateReleveTotals();

    // Scroll vers la nouvelle ligne
    const $container = $("#" + bodyId).closest('.table-responsive');
    $container.scrollTop($container[0].scrollHeight);
}

// ── Event listeners pour les boutons d'ajout ──
$(document).on("click", "#addDebitRowBtn", function () {
    addEmptyReleveRow('releveDebitBody', 'debit');
});

$(document).on("click", "#addCreditRowBtn", function () {
    addEmptyReleveRow('releveCreditBody', 'credit');
});

});

// ====================== FILTRE PAR ANNÉE - VERSION CORRIGÉE ======================
function buildFacturesYearFilter() {
    const currentYear = new Date().getFullYear();

    const selectHTML = (id) => `
        <select id="${id}" class="form-select form-select-sm year-filter-select me-2" 
                style="width: 170px; display: inline-block; vertical-align: middle;">
            <option value="${currentYear}" selected>${currentYear} (en cours)</option>
            ${Array.from({length: 6}, (_, i) => currentYear - i - 1)
                .map(y => `<option value="${y}">${y}</option>`).join('')}
        </select>`;

    function injectYearFilters() {
        // Sauvegarder uniquement si le select existe déjà
        const achatVal = $('#facture-year-filter-achat').length ? $('#facture-year-filter-achat').val() : String(currentYear);
        const venteVal = $('#facture-year-filter-vente').length ? $('#facture-year-filter-vente').val() : String(currentYear);

        $('.year-filter-select').remove();

        $('#achat .dt-action-buttons').first().prepend(selectHTML('facture-year-filter-achat'));
        $('#vente .dt-action-buttons').first().prepend(selectHTML('facture-year-filter-vente'));

        // Restaurer les valeurs sauvegardées
        $('#facture-year-filter-achat').val(achatVal);
        $('#facture-year-filter-vente').val(venteVal);
    }

    // Injection initiale + draw
    setTimeout(function () {
        injectYearFilters();
        setTimeout(function () {
            $('#facturesAchatTable').DataTable().draw();
        }, 100);
    }, 800);

    // Changement d'onglet
    $('#factureTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        injectYearFilters();
        setTimeout(function () {
            if (target === '#achat') {
                $('#facturesAchatTable').DataTable().draw();
            } else if (target === '#vente') {
                $('#facturesVenteTable').DataTable().draw();
            }
        }, 100);
    });

    // Filtre personnalisé DataTables
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const tableId = settings.nTable.id;
        let selectedYear = null;

        if (tableId === 'facturesAchatTable') {
            selectedYear = $('#facture-year-filter-achat').val();
        } else if (tableId === 'facturesVenteTable') {
            selectedYear = $('#facture-year-filter-vente').val();
        } else {
            return true;
        }

        // Select pas encore dans le DOM → utiliser année en cours
        if (selectedYear === null || selectedYear === undefined || selectedYear === '') {
            selectedYear = String(currentYear);
        }

        let yearInDate = null;

        if (tableId === 'facturesAchatTable') {
            const statutCell = data[9] || '';
            const dateMatch = statutCell.match(/(\d{4}-\d{2}-\d{2})/);
            if (dateMatch) {
                const dateStr = dateMatch[1];
                if (!dateStr || dateStr === '0000-00-00') {
                    yearInDate = String(currentYear);
                } else {
                    yearInDate = dateStr.substring(0, 4);
                }
            } else {
                yearInDate = String(currentYear);
            }
        } 
        else if (tableId === 'facturesVenteTable') {
            const statutCell = data[10] || '';
            const dateMatch = statutCell.match(/(\d{4}-\d{2}-\d{2})/);
            if (dateMatch) {
                const dateStr = dateMatch[1];
                if (!dateStr || dateStr === '0000-00-00') {
                    yearInDate = String(currentYear);
                } else {
                    yearInDate = dateStr.substring(0, 4);
                }
            } else {
                yearInDate = String(currentYear);
            }
        }

        return yearInDate === String(selectedYear);
    });

    // Events de changement
    $(document).on('change', '#facture-year-filter-achat', function() {
        $('#facturesAchatTable').DataTable().draw();
    });

    $(document).on('change', '#facture-year-filter-vente', function() {
        $('#facturesVenteTable').DataTable().draw();
    });
}

buildFacturesYearFilter();