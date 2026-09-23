document.addEventListener("DOMContentLoaded", function () {
    // Configuration générale pour les DataTables
    const commonDataTableConfig = {
        dom: '<"row ms-2 me-3"<' +
            '"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls"l<' +
            '"dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3"B>>' +
            '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter"f<' +
            '"dt-filter mb-3 mb-md-0">>' +
            '>t<"row mx-2"<' +
            '"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        displayLength: 10,
        lengthMenu: [10, 25, 50, 75, 100],
        responsive: true,
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
                previous: "Précédent",
            },
        },
    };

    // Configurations spécifiques pour chaque type de dépense
    const tableConfigs = {
        varieTable: {
            tableId: "varieTable",
            title: "Dépenses Variées",
            dataSrc: "varieDepenses",
            totalDisplayId: "total-display-varie",
            modalAdd: "#addDepenseModal",
            modalEdit: "#editDepenseModal",
            columns: {
                cols: ["code", "date", "mois_depenses", "montant", "reglement_depense", "creator"],
                headers: ["Code", "Date", "Mois Création", "Montant", "Règlement", "Créé par"],
                montantKey: "montant",
            },
            dataTableColumns: [
                { data: null, defaultContent: "", orderable: false, className: "dtr-control" },
                { data: "code", visible: false },
                { data: "date" },
                { data: "mois_depenses" },
                { data: "montant" },
                
                { data: "reglement_depense" },
             
                { data: "creator" },
                
                {
                    data: "id",
                    render: function (data, type, row) {
                        return generateActionButtons(data, row);
                    },
                },
                { data: "epreuve", visible: false },
                { data: "description", visible: false },
            ],
        },
     avancementsTable: {
    tableId: "avancementsTable",
    title: "Avancements des Salaires",
    dataSrc: "avancementsDepenses",
    totalDisplayId: "total-display-avancements",
    modalAdd: "#addAvancementDepenseModal",
    modalEdit: "#editDepenseModal",
    order: [[2, "desc"]],
    columns: {
        cols: ["code", "date", "mois_depenses", "salarie", "employee", "montant", "reglement_depense", "reference", "creator"],
        headers: ["Code", "Date", "Mois Création", "Matricule", "Salarié", "Montant", "Règlement", "Référence", "Créé par"],
        montantKey: "montant",
    },
    dataTableColumns: [
        { 
            data: null, 
            orderable: false, 
            render: function (data, type, row) {
                return `<input type="checkbox" class="select-avance" value="${row.id}">`;
            }
        },
        { data: "code", visible: false },
        { data: "reference", defaultContent: "-" },
        { data: "date" },
        { data: "mois_depenses" },
        { data: "salarie" },
        { data: "employee" },
        { data: "montant" },
        { data: "reglement_depense" },
        { data: "creator" },
        {
            data: "id",
            render: function (data, type, row) {
                return generateActionButtons(data, row);
            },
        },
        { data: "epreuve", visible: false },
        { data: "description", visible: false },
    ],
},
        vehicleTable: {
            tableId: "vehicleTable",
            title: "Dépenses de Véhicule",
            dataSrc: "vehicleDepenses",
            totalDisplayId: "total-display-vehicle",
            modalAdd: "#addVehicleDepenseModal",
            modalEdit: "#editVehicleDepenseModal",
            columns: {
                cols: ["code", "date", "vehicle", "reglement_depense",  "type", "montant","kilometrage",'heures', "etat_vidange","etat_plaquettes","etat_pneus", "etat_courroie" , "etat_amortisseur","creator"],
                headers: ["Code", "Date", "Véhicule", "Type", "Montant","Kilométrage", "Heures", "Règlement", "État Vidange","État Plaquettes", "État Pneus","État Courroie", "État Amortisseur","Créé par"],
                montantKey: "montant",
            },
            dataTableColumns: [
                { data: null, defaultContent: "", orderable: false, className: "dtr-control" },
                { data: "code", visible: false },
                { data: "date" },
                { data: "vehicle" },
                  { data: "reglement_depense" },
                { data: "type" },
                { data: "montant" },
                { 
            data: "kilometrage",
            render: function (data, type, row) {
                if (type === 'display') {
                    return data ? Number(data).toLocaleString('fr-FR') + ' km' : 'N/A';
                }
                return data;
            }},

       { 
    data: "heures",
    defaultContent: null,   // ← ajouter cette ligne
    render: function (data, type, row) {
        if (type === 'display') {
            return data ? Number(data).toLocaleString('fr-FR') + ' h' : 'N/A';
        }
        return data;
    }
},
              
        { 
    data: "etat_vidange",
    render: function (data, type, row) {
        if (type === 'display') {
            if (data != null && data !== undefined) {
                const val = parseInt(data, 10);
                const isEngin = (window.vehiclesData || []).find(v => v.id == row.vehicle_id)?.type === 'engins';
                const seuil = isEngin ? 250 : 9550;
                if (val >= seuil) {
                    return `<i class="bx bx-droplet text-danger me-1"></i><span class="text-danger fw-bold">${val}</span>`;
                }
                return val;
            }
            return 'N/A';
        }
        return data;
    }
},
{ 
    data: "etat_plaquettes",
    render: function (data, type, row) {
        if (type === 'display') {
            if (data != null && data !== undefined && data !== '') {
                const val = parseInt(data, 10);
                const isEngin = (window.vehiclesData || []).find(v => v.id == row.vehicle_id)?.type === 'engins';
                const seuil = isEngin ? 2000 : 40000;
                if (!isNaN(val) && val >= seuil) {
                    return `<i class="bx bx-stop-circle text-danger me-1"></i><span class="text-danger fw-bold">${val}</span>`;
                }
                return val;
            }
            return 'N/A';
        }
        return data;
    }
},
{ 
    data: "etat_pneus",
    render: function (data, type, row) {
        if (type === 'display') {
            if (data != null && data !== undefined && data !== '') {
                const val = parseInt(data, 10);
                const isEngin = (window.vehiclesData || []).find(v => v.id == row.vehicle_id)?.type === 'engins';
                const seuil = isEngin ? 4000 : 80000;
                if (!isNaN(val) && val >= seuil) {
                    return `<i class="bx bx-radio-circle text-danger me-1"></i><span class="text-danger fw-bold">${val}</span>`;
                }
                return val;
            }
            return 'N/A';
        }
        return data;
    }
},
{ 
    data: "etat_courroie",
    render: function (data, type, row) {
        if (type === 'display') {
            if (data != null && data !== undefined && data !== '') {
                const val = parseInt(data, 10);
                const isEngin = (window.vehiclesData || []).find(v => v.id == row.vehicle_id)?.type === 'engins';
                const seuil = isEngin ? 5000 : 100000;
                if (!isNaN(val) && val >= seuil) {
                    return `<i class="bx bx-link text-danger me-1"></i><span class="text-danger fw-bold">${val}</span>`;
                }
                return val;
            }
            return 'N/A';
        }
        return data;
    }
},
{ 
    data: "etat_amortisseur",
    render: function (data, type, row) {
        if (type === 'display') {
            if (data != null && data !== undefined && data !== '') {
                const val = parseInt(data, 10);
                const isEngin = (window.vehiclesData || []).find(v => v.id == row.vehicle_id)?.type === 'engins';
                const seuil = isEngin ? 4000 : 80000;
                if (!isNaN(val) && val >= seuil) {
                    return `<i class="bx bx-transfer-alt text-danger me-1"></i><span class="text-danger fw-bold">${val}</span>`;
                }
                return val;
            }
            return 'N/A';
        }
        return data;
    }
},                     


                { data: "creator" },
                {
                    data: "id",
                    render: function (data, type, row) {
                        return generateActionButtons(data, row);
                    },
                },
                { data: "epreuve", visible: false },
                { data: "description", visible: false },
            ],
        },
    };


    // Bascule Kilométrage / Heures selon le type de véhicule sélectionné
$(document).on("change", "#vehicle_id", function () {
    const selectedId = parseInt(this.value);
    const vehicle = (window.vehiclesData || []).find(v => v.id === selectedId);
    const isEngin = vehicle && vehicle.type === 'engins';

    if (isEngin) {
        $("#kilometrage_field").hide();
        $("#vehicle_kilometrage").val("").removeAttr("required");
        $("#heures_field").show();
    } else {
        $("#heures_field").hide();
        $("#vehicle_heures").val("");
        $("#kilometrage_field").show();
    }
});

    // Fonction pour générer les boutons d'action
    function generateActionButtons(data, row) {
        return `
            <div class="d-flex gap-1 actions-row" style="flex-wrap: nowrap; white-space: nowrap;">
                <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#editDepenseModal" data-id="${data}"><i class="bx bx-edit"></i></a>
                <button type="button" class="show-details-btn" data-description="${row.description || '-'}"
                    data-epreuve="${row.epreuve || ''}"><i class="bx bx-info-circle"></i></button>
                <a href="javascript:;" class="print-depense" data-id="${data}"><i class="bx bx-printer"></i></a>
                <form action="/depenses/${data}" method="POST" class="delete-form" style="display:inline; margin: 0;">
                    <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}" />
                    <input type="hidden" name="_method" value="DELETE" />
                    <button type="submit" class="btn btn-sm btn-icon delete-btn" title="Supprimer">
                        <i class="bx bx-trash text-danger"></i>
                    </button>
                </form>
            </div>`;
    }

    // Fonction pour appliquer les styles à l'onglet actif
    function setActiveTabStyles() {
        document.querySelectorAll(".nav-tabs .nav-link").forEach((tab) => {
            tab.classList.remove("bg-success", "text-white");
        });
        const activeTab = document.querySelector(".nav-tabs .nav-link.active");
        if (activeTab) {
            activeTab.classList.add("bg-success", "text-white");
        }
    }

    // Fonction pour formater le contenu du child row
    function formatChildRow(rowData) {
        let description = "-";
        let epreuve = null;

        if (Array.isArray(rowData)) {
            description = rowData.description || rowData[4] || rowData[6] || "-";
            epreuve = rowData.epreuve || rowData[8] || rowData[10] || null;
        } else if (typeof rowData === "object" && rowData !== null) {
            description = rowData.description || "-";
            epreuve = rowData.epreuve || null;
        }

        const epreuveContent = epreuve && typeof epreuve === "string" && epreuve.includes("epreuve")
            ? `<a href="${epreuve}" class="btn btn-sm btn-primary" download>Télécharger</a>`
            : "Non disponible";

        return `
            <table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px; width: 100%">
                <tr>
                    <td style="width: 150px"><strong>Description:</strong></td>
                    <td>${description}</td>
                </tr>
                <tr>
                    <td><strong>Épreuve:</strong></td>
                    <td>${epreuveContent}</td>
                </tr>
            </table>
        `;
    }

    // Fonction pour calculer et mettre à jour le total des dépenses
    function updateTotalDisplay(table, totalDisplayId, montantKey = "montant") {
        const data = table.rows({ search: "applied" }).data().toArray();
        const total = data
            .reduce((sum, row) => {
                let montant = row[montantKey] || "0";
                montant = String(montant).replace(/[^\d.-]/g, "");
                const parsedMontant = parseFloat(montant);
                return sum + (isNaN(parsedMontant) ? 0 : parsedMontant);
            }, 0)
            .toFixed(2);
        const formattedTotal = total.replace(".", ",");
        $(`#${totalDisplayId}`).text(`Total: ${formattedTotal}`);
    }

    // Fonction pour gérer l'impression
    function printTable(tableId, title, columns) {
        const table = $(`#${tableId}`).DataTable();
        try {
            const iframe = document.createElement("iframe");
            iframe.id = "printIframe";
            iframe.style.position = "absolute";
            iframe.style.width = "0";
            iframe.style.height = "0";
            iframe.style.border = "none";
            document.body.appendChild(iframe);

            const page_html = generatePrintContent(table, title, columns);
            const iframeDoc = iframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(page_html);
            iframeDoc.close();

            iframe.onload = function () {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    console.error("Erreur lors de l'impression:", e);
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: "Impossible de lancer l'impression.",
                        position: "center",
                        confirmButtonText: "OK",
                    });
                }
            };

            iframe.contentWindow.onafterprint = function () {
                document.body.removeChild(iframe);
            };

            setTimeout(() => {
                if (iframe.parentNode) {
                    document.body.removeChild(iframe);
                }
            }, 5000);
        } catch (e) {
            console.error("Erreur dans printTable:", e);
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Impossible de générer le document d'impression: " + e.message,
                position: "center",
                confirmButtonText: "OK",
            });
        }
    }

    // Fonction pour générer le contenu HTML de l'impression
    function generatePrintContent(table, title, columns) {
        const data = table.rows({ search: "applied" }).data().toArray();
        if (!data.length) {
            return `
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <meta charset="UTF-8">
                    <title>Impression - ${title}</title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                        .no-data { font-size: 18px; color: red; }
                    </style>
                </head>
                <body>
                    <div class="no-data">Aucune donnée à imprimer pour ${title}</div>
                </body>
                </html>
            `;
        }

        const totalMontant = data
            .reduce((sum, row) => {
                const montant = parseFloat(
                    String(row[columns.montantKey] || 0).replace(/\s/g, "").replace(",", ".")
                );
                return sum + (isNaN(montant) ? 0 : montant);
            }, 0)
            .toFixed(2);

        const rowsPerPage = 12;
        const pageData = [];
        for (let i = 0; i < data.length; i += rowsPerPage) {
            pageData.push(data.slice(i, i + rowsPerPage));
        }

        const tableContentPages = pageData.map((pageRows, pageIndex) => {
            const tableContent = pageRows
                .map((row) => {
                    const rowData = columns.cols.map((key) => {
                        const value = row[key] !== undefined && row[key] !== null ? row[key] : "-";
                        return `<td>${value}</td>`;
                    }).join("");
                    return `
                        <tr class="table-tr">
                            ${rowData}
                            <td>${row.description || "-"}</td>
                            <td>${row.epreuve && row.epreuve.includes("epreuve") ? "Disponible" : "Non disponible"}</td>
                        </tr>
                    `;
                })
                .join("");

            const totalRow = pageIndex === pageData.length - 1 ? `
                <tr class="total-row">
                    ${columns.cols
                        .map((_, index) =>
                            index === columns.cols.indexOf(columns.montantKey)
                                ? `<td style="font-weight:bold">${totalMontant.replace(".", ",")}</td>`
                                : index === columns.cols.indexOf(columns.montantKey) - 1
                                ? `<td style="font-weight:bold">Total</td>`
                                : `<td></td>`
                        )
                        .join("")}
                    <td></td>
                    <td></td>
                </tr>
            ` : "";

            return `
                <div class="print-body-2" style="page-break-after: always;">
                    <table>
                        <thead>
                            <tr class="td-bold">
                                ${columns.headers
                                    .map((header) => `<th class="td-bold">${header}</th>`)
                                    .join("")}
                                <th class="td-bold">Description</th>
                                <th class="td-bold">Épreuve</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableContent}
                            ${totalRow}
                        </tbody>
                    </table>
                </div>
            `;
        }).join("");

        const logoUrl = companySettings?.logo
            ? `${window.location.origin}/storage/${companySettings.logo}`
            : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;
        const companyAddress = companySettings?.address || "Non spécifié";
        const companyCapital = companySettings?.capital
            ? companySettings.capital.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD"
            : "Non spécifié";
        const companyPhone = companySettings?.phone_number || "Non spécifié";
        const companyRC = companySettings?.commercial_register || "Non spécifié";
        const companyCNSS = companySettings?.cnss_number || "Non spécifié";
        const companyTaxId = companySettings?.tax_id || "Non spécifié";
        const companyPatent = companySettings?.patent_number || "Non spécifié";
        const companyAccount = companySettings?.account_number || "Non spécifié";
        const companyBank = companySettings?.bank_name || "Non spécifié";
        const companyEmail = companySettings?.email || "Non spécifié";

        return `
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Impression - ${title}</title>
                <style>
                    html, body, * {
                        padding: 0;
                        margin: 0;
                        box-sizing: border-box;
                        font-family: Arial, sans-serif;
                    }
                    @page {
                        size: landscape;
                        margin: 10mm 10mm 25mm 10mm;
                    }
                    body {
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                        margin: 10mm;
                    }
                    .print {
                        width: 100%;
                        height: auto;
                        padding: 18px;
                    }
                    .print-header {
                        width: 100%;
                        margin-bottom: 20px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        flex-wrap: wrap;
                        border-bottom: 2px solid #000;
                    }
                    .print-header-title {
                        font-size: 18px;
                        font-weight: bold;
                        line-height: 1.45;
                        margin-left: 10px;
                    }
                    .print-header-type {
                        width: 100%;
                        height: 25px;
                        line-height: 25px;
                        text-align: center;
                        background-color: #efefef;
                        border: 1px solid black;
                        margin-bottom: 10px;
                        font-size: 14px;
                        font-weight: bold;
                        letter-spacing: 0.5px;
                    }
                    .print-body-2 {
                        margin-bottom: 30mm;
                        padding-bottom: 30mm;
                    }
                    .print-body-2 table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    .print-body-2 table, th, td {
                        border: 1px solid black;
                        padding: 8px;
                        text-align: center;
                        font-size: 13px;
                        vertical-align: middle;
                    }
                    .table-tr td {
                        padding: 8px;
                    }
                    .print-footer {
                        font-size: 12px;
                        line-height: 1.5;
                        text-align: center;
                        position: fixed;
                        bottom: 0;
                        width: 100%;
                        height: 25mm;
                        background: white;
                        border-top: 1px solid #000;
                        z-index: 1000;
                    }
                    .print-footer p {
                        margin: 2px 0;
                    }
                    .td-bold {
                        font-size: 14px;
                        font-weight: bold;
                        background-color: #e9ecef;
                    }
                    .logo-title-container {
                        display: flex;
                        align-items: center;
                    }
                    .logo-title-container img {
                        max-width: 60px;
                        height: auto;
                    }
                    .total-row {
                        background-color: #f8f9fa;
                        font-weight: bold;
                    }
                    @media print {
                        body {
                            margin: 0 !important;
                        }
                        .print {
                            margin: 0;
                            padding: 10mm;
                        }
                        .print-footer {
                            position: fixed !important;
                            bottom: 0 !important;
                            width: 100% !important;
                            height: 25mm !important;
                            background: white !important;
                            border-top: 1px solid #000 !important;
                            padding: 5mm !important;
                            font-size: 10px !important;
                            z-index: 1000 !important;
                            page-break-outside: avoid !important;
                            break-outside: avoid !important;
                        }
                        .print-body-2 {
                            padding-bottom: 30mm !important;
                            margin-bottom: 30mm !important;
                        }
                        .print-body-2 table {
                            page-break-outside: auto !important;
                            break-outside: auto !important;
                        }
                        .table-tr {
                            page-break-inside: avoid !important;
                            break-inside: avoid !important;
                            page-break-after: auto !important;
                            break-after: auto !important;
                        }
                        thead {
                            display: table-header-group !important;
                        }
                        tbody {
                            display: table-row-group !important;
                        }
                        .total-row {
                            page-break-inside: avoid !important;
                            break-inside: avoid !important;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="print">
                    <div class="print-header">
                        <div class="logo-title-container">
                            <img src="${logoUrl}" alt="Logo" />
                            <h1 class="print-header-title">Fiche des dépenses</h1>
                        </div>
                        <div class="print-header-box">
                            <div class="print-header-type">${title}</div>
                        </div>
                    </div>
                    ${tableContentPages}
                    <div class="print-footer">
                        <p>Siège social: ${companyAddress} | Capital: ${companyCapital} | Tél: ${companyPhone}</p>
                        <p>R.C.: ${companyRC} | CNSS: ${companyCNSS} | IF: ${companyTaxId} | TP: ${companyTaxId} | ICE: ${companyPatent}</p>
                        <p>C.B.: ${companyAccount}, ${companyBank} | Email: ${companyEmail}</p>
                    </div>
                </div>
            </body>
            </html>
        `;
    }

    // Fonction pour initialiser un DataTable
    function initializeDataTable(config) {
        const table = $(`#${config.tableId}`).DataTable({
            ...commonDataTableConfig,
            buttons: [
                {
                    extend: "collection",
                    className: "btn btn-label-primary dropdown-toggle me-2 export-btn",
                    text: '<i class="bx bx-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Exporter</span>',
                    buttons: [
                        {
                            text: '<i class="bx bx-printer me-1"></i>Imprimer',
                            className: "dropdown-item",
                            action: function () {
                                printTable(config.tableId, config.title, config.columns);
                            },
                        },
                        {
                            extend: "excel",
                            text: '<i class="bx bxs-file-export me-1"></i>Excel',
                            className: "dropdown-item",
                            exportOptions: { columns: config.columns.cols.map((_, i) => i + 1) },
                        },
                        {
                            extend: "pdf",
                            text: '<i class="bx bxs-file-pdf me-1"></i>PDF',
                            className: "dropdown-item",
                            exportOptions: { columns: config.columns.cols.map((_, i) => i + 1) },
                        },
                    ],
                },
                {
                    text: '<i class="bx bx-plus me-sm-1"></i><span class="d-sm-inline-block">Ajouter</span>',
                    className: "create-new btn btn-primary add-btn",
                    action: function () {
                        $(config.modalAdd).modal("show");
                    },
                },
            ],
            order: [[2, "desc"]],
            ajax: {
                url: "/depenses",
                dataSrc: config.dataSrc,
            },
            columns: config.dataTableColumns,
            columnDefs: [{ targets: 0, orderable: false }],
            createdRow: function (row, data) {
                $(row).attr("data-description", data.description || "");
                $(row).attr("data-epreuve", data.epreuve || "");
            },
            initComplete: function () {
                updateTotalDisplay(this.api(), config.totalDisplayId, config.columns.montantKey);
            },
            drawCallback: function () {
                updateTotalDisplay(this.api(), config.totalDisplayId, config.columns.montantKey);
            },
        });

        // Gestion des détails
        $(`#${config.tableId} tbody`).on("click", ".show-details-btn", function () {
            const description = $(this).data("description");
            const epreuve = $(this).data("epreuve");

            $("#modal-description").text(description);
            $("#modal-epreuve").html(
                epreuve && epreuve.includes("epreuve")
                    ? `<a href="${epreuve}" class="btn btn-sm btn-primary" download>Télécharger</a>`
                    : "Non disponible"
            );

            $("#detailsModal").modal("show");
        });

        return table;
    }
    // Initialisation des DataTables en fonction de la page active
    const activeTableId = document.querySelector(".datatables-basic")?.id;
    if (activeTableId && tableConfigs[activeTableId]) {
        const config = tableConfigs[activeTableId];
        initializeDataTable(config);

        // Gestion des onglets
        if (activeTableId === "vehicleTable") {
            $('button[data-bs-target="#vehicle-tab-pane"]').on("shown.bs.tab", function () {
                initializeDataTable(config);
            });
        } else if (activeTableId === "avancementsTable") {
            $('button[data-bs-target="#profile-tab-pane"]').on("shown.bs.tab", function () {
                initializeDataTable(config);
            });
        }
    }

// Gestion des détails (version améliorée pour les avancements)
$(document).on("click", ".show-details-btn", function () {
    const $btn = $(this);
    const $row = $btn.closest("tr");
    const tableId = $row.closest("table").attr("id");
    const table = $(`#${tableId}`).DataTable();
    
    // Récupérer les données de la ligne
    let rowData = table.row($row).data();

    // Fallback si DataTables responsive
    if (!rowData) {
        rowData = {
            code: $row.find("td:eq(1)").text() || "-",
            reference: $row.find("td:eq(2)").text() || "-",
            date: $row.find("td:eq(3)").text() || "-",
            mois_depenses: $row.find("td:eq(4)").text() || "-",
            salarie: $row.find("td:eq(5)").text() || "-",
            employee: $row.find("td:eq(6)").text() || "-",
            montant: $row.find("td:eq(7)").text() || "-",
            reglement_depense: $row.find("td:eq(8)").text() || "-",
            description: $btn.data("description") || $row.data("description") || "-",
            epreuve: $btn.data("epreuve") || $row.data("epreuve") || ""
        };
    }

    // Remplir la modale
    $("#modal-code").text(rowData.code || "-");
    $("#modal-reference").text(rowData.reference || "-");
    $("#modal-date").text(rowData.date || "-");
    $("#modal-mois").text(rowData.mois_depenses || "-");
    $("#modal-matricule").text(rowData.salarie || "-");
    $("#modal-salarie").text(rowData.employee || "-");
    $("#modal-montant").text(rowData.montant ? rowData.montant + " DHS" : "-");
    $("#modal-reglement").text(rowData.reglement_depense || "-");
    $("#modal-description").text(rowData.description || "-");

    // Gestion de l'épreuve
    const epreuve = rowData.epreuve || "";
    if (epreuve && epreuve.includes("epreuve")) {
        $("#modal-epreuve").html(
            `<a href="${epreuve}" class="btn btn-sm btn-primary" target="_blank" download>
                <i class="bx bx-download me-1"></i> Télécharger le fichier
             </a>`
        );
    } else {
        $("#modal-epreuve").html(`<span class="text-muted">Non disponible</span>`);
    }

    // Afficher la modale
    $("#detailsModal").modal("show");
});


    // Gestion des modales d'édition
  $(document).on("click", "[data-bs-target='#editDepenseModal']", function () {
        const depenseId = $(this).data("id");
        const tableId = $(this).closest("table").attr("id") || "varieTable";
        $("#editDepenseModal").data("table-id", tableId);

        // Fetch expense data via AJAX
        $.ajax({
            url: `/depenses/${depenseId}/edit`,
            method: "GET",
            success: function (response) {
                // Populate form fields
                const depense = response.depense;
                $("#editDepenseForm").attr("action", `/depenses/${depenseId}`);
                $("#edit_nature_id").empty().append('<option value="">Sélectionner</option>');
                response.natureDepenses.forEach(nature => {
                    $("#edit_nature_id").append(`<option value="${nature.id}" ${nature.id == depense.nature_id ? 'selected' : ''}>${nature.designation}</option>`);
                });

                $("#edit_reglement_id").empty().append('<option value="">Sélectionner</option>');
                response.typeReglements.forEach(reglement => {
                    $("#edit_reglement_id").append(`<option value="${reglement.id}" ${reglement.id == depense.reglement_id ? 'selected' : ''}>${reglement.designation}</option>`);
                });

                $("#edit_salarie_id").empty().append('<option value="">Sélectionner un salarié</option>');
                response.salaries.forEach(salarie => {
                    $("#edit_salarie_id").append(`<option value="${salarie.id}" ${salarie.id == depense.salarie_id ? 'selected' : ''}>${salarie.n_matricule_entreprise} - ${salarie.nom} ${salarie.prenom}</option>`);
                });

                $("#edit_vehicle_id").empty().append('<option value="">Sélectionner un véhicule</option>');
                response.vehicles.forEach(vehicle => {
                    $("#edit_vehicle_id").append(`<option value="${vehicle.id}" ${vehicle.id == depense.vehicle_id ? 'selected' : ''}>${vehicle.matricule}</option>`);
                });

                $("#edit_date").val(depense.date);
                $("#edit_montant").val(depense.montant);
                $("#edit_description").val(depense.description);
                $("#edit_vehicle_type").val(depense.type || '');
                $("#edit_vehicle_kilometrage").val(depense.kilometrage || '');

                // Toggle salarie and vehicle fields based on nature_id
                const salarieField = $("#edit_salarie_field");
                const vehicleField = $("#edit_vehicle_field");
                const avancementId = response.avancementNatureId;
                const vehicleNatureId = response.vehicleNatureId;

                if (depense.nature_id == avancementId) {
                    salarieField.show();
                    vehicleField.hide();
                    $("#edit_salarie_id").attr("required", "required");
                    $("#edit_vehicle_id").removeAttr("required");
                    $("#edit_vehicle_type").removeAttr("required");
                    $("#edit_vehicle_kilometrage").removeAttr("required");
                } else if (depense.nature_id == vehicleNatureId) {
                    salarieField.hide();
                    vehicleField.show();
                    $("#edit_salarie_id").removeAttr("required");
                    $("#edit_vehicle_id").attr("required", "required");
                    $("#edit_vehicle_type").attr("required", "required");
                    $("#edit_vehicle_kilometrage").removeAttr("required");
                } else {
                    salarieField.hide();
                    vehicleField.hide();
                    $("#edit_salarie_id").removeAttr("required");
                    $("#edit_vehicle_id").removeAttr("required");
                    $("#edit_vehicle_type").removeAttr("required");
                    $("#edit_vehicle_kilometrage").removeAttr("required");
                }

                // Trigger change event to ensure UI updates
                $("#edit_nature_id").trigger("change");
            },
            error: function (xhr) {
                console.error("Error fetching expense data:", xhr);
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: "Impossible de charger les données de la dépense.",
                    position: "center",
                    confirmButtonText: "OK",
                });
            },
        });
    });

    // Fonction pour remplir les champs des modales
    function populateModalFields(modalId, formId, response, depenseId) {
        const depense = response.depense;
        $(`#${formId}`).attr("action", `/depenses/${depenseId}`);

        $(`#${modalId} #edit_nature_id`).empty().append('<option value="">Sélectionner</option>');
        response.natureDepenses.forEach(nature => {
            $(`#${modalId} #edit_nature_id`).append(
                `<option value="${nature.id}" ${nature.id == depense.nature_id ? 'selected' : ''}>${nature.designation}</option>`
            );
        });

        $(`#${modalId} #edit_reglement_id`).empty().append('<option value="">Sélectionner</option>');
        response.typeReglements.forEach(reglement => {
            $(`#${modalId} #edit_reglement_id`).append(
                `<option value="${reglement.id}" ${reglement.id == depense.reglement_id ? 'selected' : ''}>${reglement.designation}</option>`
            );
        });

        $(`#${modalId} #edit_salarie_id`).empty().append('<option value="">Sélectionner un salarié</option>');
        response.salaries.forEach(salarie => {
            $(`#${modalId} #edit_salarie_id`).append(
                `<option value="${salarie.id}" ${salarie.id == depense.salarie_id ? 'selected' : ''}>${salarie.n_matricule_entreprise} - ${salarie.nom} ${salarie.prenom}</option>`
            );
        });

        $(`#${modalId} #edit_vehicle_id`).empty().append('<option value="">Sélectionner un véhicule</option>');
        response.vehicles.forEach(vehicle => {
            $(`#${modalId} #edit_vehicle_id`).append(
                `<option value="${vehicle.id}" ${vehicle.id == depense.vehicle_id ? 'selected' : ''}>${vehicle.matricule}</option>`
            );
        });

        $(`#${modalId} #edit_date`).val(depense.date);
        $(`#${modalId} #edit_montant`).val(depense.montant);
        $(`#${modalId} #edit_description`).val(depense.description);
        $(`#${modalId} #edit_vehicle_type`).val(depense.type || '');
        $(`#${modalId} #edit_vehicle_kilometrage`).val(depense.kilometrage || '');

        const salarieField = $(`#${modalId} #edit_salarie_field`);
        const vehicleField = $(`#${modalId} #edit_vehicle_field`);
        const avancementId = response.avancementNatureId;
        const vehicleNatureId = response.vehicleNatureId;

        if (depense.nature_id == avancementId) {
            salarieField.show();
            vehicleField.hide();
            $(`#${modalId} #edit_salarie_id`).attr("required", "required");
            $(`#${modalId} #edit_vehicle_id`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_type`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_kilometrage`).removeAttr("required");
        } else if (depense.nature_id == vehicleNatureId) {
            salarieField.hide();
            vehicleField.show();
            $(`#${modalId} #edit_salarie_id`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_id`).attr("required", "required");
            $(`#${modalId} #edit_vehicle_type`).attr("required", "required");
            $(`#${modalId} #edit_vehicle_kilometrage`).removeAttr("required");
        } else {
            salarieField.hide();
            vehicleField.hide();
            $(`#${modalId} #edit_salarie_id`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_id`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_type`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_kilometrage`).removeAttr("required");
        }

        $(`#${modalId} #edit_nature_id`).trigger("change");
    }

    // Gestion du changement de nature_id
    $(document).on("change", "#edit_nature_id", function () {
        const modalId = $(this).closest(".modal").attr("id");
        const salarieField = $(`#${modalId} #edit_salarie_field`);
        const vehicleField = $(`#${modalId} #edit_vehicle_field`);
        const avancementId = window.avancementNatureId || null;
        const vehicleNatureId = window.vehicleNatureId || null;

        if (this.value == avancementId) {
            salarieField.show();
            vehicleField.hide();
            $(`#${modalId} #edit_salarie_id`).attr("required", "required");
            $(`#${modalId} #edit_vehicle_id`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_type`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_kilometrage`).removeAttr("required");
        } else if (this.value == vehicleNatureId) {
            salarieField.hide();
            vehicleField.show();
            $(`#${modalId} #edit_salarie_id`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_id`).attr("required", "required");
            $(`#${modalId} #edit_vehicle_type`).attr("required", "required");
            $(`#${modalId} #edit_vehicle_kilometrage`).removeAttr("required");
        } else {
            salarieField.hide();
            vehicleField.hide();
            $(`#${modalId} #edit_salarie_id`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_id`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_type`).removeAttr("required");
            $(`#${modalId} #edit_vehicle_kilometrage`).removeAttr("required");
        }
    });

    // Gestion de l'ajout
    $("#addDepenseForm, #addVehicleDepenseForm, #addAvancementDepenseForm").on("submit", function (e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: "Confirmer l'ajout",
            text: "Voulez-vous vraiment ajouter cette dépense ?",
            icon: "question",
            position: "center",
            showCancelButton: true,
            confirmButtonText: "Oui, ajouter",
            cancelButtonText: "Non, annuler",
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
        }).then((result) => {
            if (result.isConfirmed) {
                const submitButton = $(form).find('button[type="submit"]');
                submitButton.prop("disabled", true).text("Enregistrement...");
                $("#loadingModal").modal("show");

                $.ajax({
                    url: $(form).attr("action"),
                    method: $(form).attr("method"),
                    data: new FormData(form),
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $("#loadingModal").modal("hide");
                        submitButton.prop("disabled", false).text("Enregistrer");
                        $(form).closest(".modal").modal("hide");
                        Swal.fire({
                            icon: "success",
                            title: "Succès",
                            text: response.message || "Dépense ajoutée avec succès !",
                            position: "center",
                            confirmButtonText: "OK",
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function (xhr) {
    $("#loadingModal").modal("hide");
    submitButton.prop("disabled", false).text("Enregistrer");

    // ✅ Cas doublon (409 Conflict)
    if (xhr.status === 409 && xhr.responseJSON?.duplicate) {
        Swal.fire({
            icon: "warning",
            title: "Dépense déjà existante",
            html: xhr.responseJSON.error,
            position: "center",
            confirmButtonText: "OK",
            confirmButtonColor: "#f0ad4e",
        });
        return;
    }

    let errorMessage = "Erreur lors de l'ajout de la dépense.";
    if (xhr.responseJSON?.errors) {
        errorMessage = Object.values(xhr.responseJSON.errors).flat().join("<br>");
    } else if (xhr.responseJSON?.error) {
        errorMessage = xhr.responseJSON.error;
    }
    Swal.fire({
        icon: "error",
        title: "Erreur",
        html: errorMessage,
        position: "center",
        confirmButtonText: "OK",
    });
},
                });
            }
        });
    });

    // Gestion de la suppression
    $(document).on("click", ".delete-btn", function (e) {
        e.preventDefault();
        const $button = $(this);
        const $form = $button.closest("form");
        const depenseId = $form.attr("action").split("/").pop();
        const tableId = $form.closest("table").attr("id");
        const table = $(`#${tableId}`).DataTable();

        Swal.fire({
            title: "Êtes-vous sûr ?",
            text: "Vous ne pourrez pas annuler cette action !",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Oui, supprimer !",
            cancelButtonText: "Annuler",
            position: "center",
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: "Suppression en cours...",
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                });

                $.ajax({
                    url: $form.attr("action"),
                    method: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: "DELETE",
                    },
                    success: function (response) {
                        Swal.close();
                        Swal.fire({
                            icon: "success",
                            title: "Succès",
                            text: response.message || "Dépense supprimée avec succès !",
                            position: "center",
                            confirmButtonText: "OK",
                        }).then(() => {
                            table.ajax.reload(null, false);
                        });
                    },
                    error: function (xhr) {
                        Swal.close();
                        let errorMessage = "Erreur lors de la suppression de la dépense.";
                        if (xhr.responseJSON?.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join("<br>");
                        } else if (xhr.responseJSON?.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            html: errorMessage,
                            position: "center",
                            confirmButtonText: "OK",
                        });
                    },
                });
            }
        });
    });

    // Gestion de la modification
    $("#editDepenseForm, #editVehicleDepenseForm").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        const tableId = $(this).closest(".modal").data("table-id") || "varieTable";
        const table = $(`#${tableId}`).DataTable();

        Swal.fire({
            title: "Confirmer la modification",
            text: "Voulez-vous vraiment mettre à jour cette dépense ?",
            icon: "question",
            position: "center",
            showCancelButton: true,
            confirmButtonText: "Oui, modifier",
            cancelButtonText: "Non, annuler",
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: "Modification en cours...",
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                });

                const submitButton = $form.find('button[type="submit"]');
                submitButton.prop("disabled", true).text("Enregistrement...");

                const formData = new FormData($form[0]);
                formData.append("_method", "PUT");
                formData.append("_token", $('meta[name="csrf-token"]').attr('content'));

                $.ajax({
                    url: $form.attr("action"),
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        Swal.close();
                        submitButton.prop("disabled", false).text("Mettre à jour");
                        $form.closest(".modal").modal("hide");
                        Swal.fire({
                            icon: "success",
                            title: "Succès",
                            text: response.message || "Dépense modifiée avec succès !",
                            position: "center",
                            confirmButtonText: "OK",
                        }).then(() => {
                            table.ajax.reload(null, false);
                        });
                    },
                    error: function (xhr) {
                        Swal.close();
                        submitButton.prop("disabled", false).text("Mettre à jour");
                        let errorMessage = "Erreur lors de la modification de la dépense.";
                        if (xhr.responseJSON?.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join("<br>");
                        } else if (xhr.responseJSON?.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            html: errorMessage,
                            position: "center",
                            confirmButtonText: "OK",
                        });
                    },
                });
            }
        });
    });

    // Gestion du champ salarié pour l'ajout
    function toggleSalarieField(natureSelect, salarieField, salarieId) {
        const avancementId = window.avancementNatureId;
        if (natureSelect.value === avancementId) {
            salarieField.style.display = "block";
            salarieId.setAttribute("required", "required");
        } else {
            salarieField.style.display = "none";
            salarieId.removeAttribute("required");
        }
    }

    const natureSelect = document.getElementById("nature_id");
    const salarieField = document.getElementById("salarie_field");
    const salarieId = document.getElementById("salarie_id");

    if (natureSelect && salarieField && salarieId) {
        natureSelect.addEventListener("change", () => toggleSalarieField(natureSelect, salarieField, salarieId));
        toggleSalarieField(natureSelect, salarieField, salarieId);
    }

    // Gestion des filtres de date
    function applyDateFilter(dateDebut, dateFin) {
        if (dateDebut && dateFin) {
            const url = `/depenses?date_debut=${encodeURIComponent(dateDebut)}&date_fin=${encodeURIComponent(dateFin)}`;
            window.location.href = url;
        } else {
            Swal.fire({
                icon: "warning",
                title: "Attention",
                text: "Veuillez sélectionner une date de début et une date de fin.",
                position: "center",
                confirmButtonText: "OK",
            });
        }
    }

    function resetDateFilter() {
        window.location.href = "/depenses";
    }

    $("#date_debut_avancements, #date_fin_avancements").on("change", function () {
        const dateDebut = $("#date_debut_avancements").val();
        const dateFin = $("#date_fin_avancements").val();
        applyDateFilter(dateDebut, dateFin);
    });

    $("#reset-dates-avancements").on("click", function () {
        $("#date_debut_avancements").val("");
        $("#date_fin_avancements").val("");
        resetDateFilter();
    });

    // Appliquer les styles aux onglets
    setActiveTabStyles();
    document.querySelectorAll(".nav-tabs .nav-link").forEach((tab) => {
        tab.addEventListener("click", setActiveTabStyles);
    });

    // Configurer les en-têtes CSRF pour AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    // Fonction pour générer le contenu HTML de l'impression individuelle
function generateSinglePrintContent(depense, title, columns) {
    const logoUrl = companySettings?.logo
        ? `${window.location.origin}/storage/${companySettings.logo}`
        : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;
    const companyAddress = companySettings?.address || "Non spécifié";
    const companyCapital = companySettings?.capital
        ? companySettings.capital.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD"
        : "Non spécifié";
    const companyPhone = companySettings?.phone_number || "Non spécifié";
    const companyRC = companySettings?.commercial_register || "Non spécifié";
    const companyCNSS = companySettings?.cnss_number || "Non spécifié";
    const companyTaxId = companySettings?.tax_id || "Non spécifié";
    const companyPatent = companySettings?.patent_number || "Non spécifié";
    const companyAccount = companySettings?.account_number || "Non spécifié";
    const companyBank = companySettings?.bank_name || "Non spécifié";
    const companyEmail = companySettings?.email || "Non spécifié";

    const tableContent = `
        <tr class="table-tr">
            ${columns.cols.map(key => `<td>${depense[key] !== undefined && depense[key] !== null ? depense[key] : "-"}</td>`).join("")}
            <td>${depense.description || "-"}</td>
            <td>${depense.epreuve && depense.epreuve.includes("epreuve") ? "Disponible" : "Non disponible"}</td>
        </tr>
        <tr class="total-row">
            ${columns.cols
                .map((key, index) =>
                    index === columns.cols.indexOf(columns.montantKey)
                        ? `<td style="font-weight:bold">${parseFloat(depense[columns.montantKey] || 0).toFixed(2).replace(".", ",")}</td>`
                        : index === columns.cols.indexOf(columns.montantKey) - 1
                        ? `<td style="font-weight:bold">Total</td>`
                        : `<td></td>`
                )
                .join("")}
            <td></td>
            <td></td>
        </tr>
    `;

    return `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Impression - ${title}</title>
            <style>
                html, body, * {
                    padding: 0;
                    margin: 0;
                    box-sizing: border-box;
                    font-family: Arial, sans-serif;
                }
                @page {
                    size: portrait;
                    margin: 10mm;
                }
                body {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                    margin: 10mm;
                }
                .print {
                    width: 100%;
                    height: auto;
                    padding: 18px;
                }
                .print-header {
                    width: 100%;
                    margin-bottom: 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    border-bottom: 2px solid #000;
                }
                .print-header-title {
                    font-size: 18px;
                    font-weight: bold;
                    line-height: 1.45;
                    margin-left: 10px;
                }
                .print-header-type {
                    width: 100%;
                    height: 25px;
                    line-height: 25px;
                    text-align: center;
                    background-color: #efefef;
                    border: 1px solid black;
                    margin-bottom: 10px;
                    font-size: 14px;
                    font-weight: bold;
                    letter-spacing: 0.5px;
                }
                .print-body-2 table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .print-body-2 table, th, td {
                    border: 1px solid black;
                    padding: 8px;
                    text-align: center;
                    font-size: 13px;
                    vertical-align: middle;
                }
                .table-tr td {
                    padding: 8px;
                }
                .print-footer {
                    font-size: 12px;
                    line-height: 1.5;
                    text-align: center;
                    position: fixed;
                    bottom: 0;
                    width: 100%;
                    height: 25mm;
                    background: white;
                    border-top: 1px solid #000;
                    z-index: 1000;
                }
                .print-footer p {
                    margin: 2px 0;
                }
                .td-bold {
                    font-size: 14px;
                    font-weight: bold;
                    background-color: #e9ecef;
                }
                .logo-title-container {
                    display: flex;
                    align-items: center;
                }
                .logo-title-container img {
                    max-width: 60px;
                    height: auto;
                }
                .total-row {
                    background-color: #f8f9fa;
                    font-weight: bold;
                }
                @media print {
                    body {
                        margin: 0 !important;
                    }
                    .print {
                        margin: 0;
                        padding: 10mm;
                    }
                    .print-footer {
                        position: fixed !important;
                        bottom: 0 !important;
                        width: 100% !important;
                        height: 25mm !important;
                        background: white !important;
                        border-top: 1px solid #000 !important;
                        padding: 5mm !important;
                        font-size: 10px !important;
                        z-index: 1000 !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print">
                <div class="print-header">
                    <div class="logo-title-container">
                        <img src="${logoUrl}" alt="Logo" />
                        <h1 class="print-header-title">Fiche de dépense</h1>
                    </div>
                    <div class="print-header-box">
                        <div class="print-header-type">${title}</div>
                    </div>
                </div>
                <div class="print-body-2">
                    <table>
                        <thead>
                            <tr class="td-bold">
                                ${columns.headers.map(header => `<th class="td-bold">${header}</th>`).join("")}
                                <th class="td-bold">Description</th>
                                <th class="td-bold">Épreuve</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableContent}
                        </tbody>
                    </table>
                </div>
                <div class="print-footer">
                    <p>Siège social: ${companyAddress} | Capital: ${companyCapital} | Tél: ${companyPhone}</p>
                    <p>R.C.: ${companyRC} | CNSS: ${companyCNSS} | IF: ${companyTaxId} | TP: ${companyTaxId} | ICE: ${companyPatent}</p>
                    <p>C.B.: ${companyAccount}, ${companyBank} | Email: ${companyEmail}</p>
                </div>
            </div>
        </body>
        </html>
    `;
}
// Fonction pour imprimer une seule dépense
$(document).on("click", ".print-depense", function () {
    const $button = $(this);
    let $row = $button.closest("tr");
    const tableId = $row.closest("table").attr("id");
    const isAvancement = tableId === "avancementsTable";
    const isVehicle = tableId === "vehicleTable";
    const table = $(`#${tableId}`).DataTable();
    const depenseId = $button.data("id");

    // Handle responsive table data
    let rowData = table.row($row).data();
    if (!rowData) {
        // Fallback to raw DOM data for responsive tables
        let offset = isAvancement ? 2 : isVehicle ? 2 : 0; // +2 pour avancements et véhicule à cause des colonnes supplémentaires
        rowData = {
            code: $row.find("td:eq(1)").text() || "-",
            date: $row.find("td:eq(2)").text() || "-",
            mois_depenses: $row.find("td:eq(3)").text() || "-",
            salarie: isAvancement ? $row.find("td:eq(4)").text() || "-" : "",
            employee: isAvancement ? $row.find("td:eq(5)").text() || "-" : "",
            vehicle: isVehicle ? $row.find("td:eq(3)").text() || "-" : "",
            type: isVehicle ? $row.find("td:eq(4)").text() || "-" : "",
            montant: $row.find(`td:eq(${isVehicle ? 5 : 4 + offset})`).text().replace(/[^\d.,]/g, "") || "0",
            reglement_depense: $row.find(`td:eq(${isVehicle ? 6 : 5 + offset})`).text() || "-",
            creator: $row.find(`td:eq(${isVehicle ? 7 : 6 + offset})`).text() || "-",
            description: $row.data("description") || "-",
            epreuve: $row.data("epreuve") || ""
        };
        console.warn("Fallback row data used:", rowData);
    }

    if (!rowData) {
        console.error("Aucune donnée de ligne trouvée pour:", $row, "Tableau:", tableId, "ID Dépense:", depenseId);
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Impossible de récupérer les données de la ligne.",
            position: "center",
            confirmButtonText: "OK",
        });
        return;
    }

    // Adapter les données en fonction du type de tableau
    const data = {
        code: rowData.code || "-",
        date: rowData.date || "-",
        mois_depenses: rowData.mois_depenses || "-",
        salarie: isAvancement ? rowData.salarie || "-" : "",
        nomPrenom: isAvancement ? rowData.employee || "-" : "",
        vehicle: isVehicle ? rowData.vehicle || "-" : "",
        type: isVehicle ? rowData.type || "-" : "",
        montant: rowData.montant || "-",
        reglement: rowData.reglement_depense || "-",
        creator: rowData.creator || "-",
        description: rowData.description || "-",
        hasEpreuve: rowData.epreuve && rowData.epreuve.includes("epreuve"),
    };

    const logoUrl = companySettings && companySettings.logo
        ? `${window.location.origin}/storage/${companySettings.logo}`
        : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

    const iframe = document.createElement("iframe");
    iframe.style.display = "none";
    document.body.appendChild(iframe);
    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

    // Générer le contenu HTML pour l'impression
    iframeDoc.open();
    iframeDoc.write(`
        <html>
        <head>
            <title>Impression - Détails de la Dépense</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 10mm; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                .print-container { max-width: 800px; margin: 0 auto; }
                .header { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
                .header img { max-width: 100px; margin-right: 20px; }
                .header h1 { font-size: 24px; margin: 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .print-footer { font-size: 12px; line-height: 1.5; text-align: center; position: fixed; bottom: 10mm; width: 100%; }
                .print-footer p { margin: 2px 0; }
                @page { size: A4; margin: 10mm; }
                @media print { .no-print { display: none; } .print-footer { position: fixed; bottom: 0; width: 100%; } }
            </style>
        </head>
        <body>
            <div class="print-container">
                <div class="header">
                    <img src="${logoUrl}" alt="Logo" />
                    <div>
                        <h1>Détails de la Dépense</h1>
                        <p>Date d'impression: ${new Date().toLocaleDateString("fr-FR")}</p>
                    </div>
                </div>
                <table>
                    <tr><th>Code</th><td>${data.code}</td></tr>
                    <tr><th>Date</th><td>${data.date}</td></tr>
                    <tr><th>Mois Création</th><td>${data.mois_depenses}</td></tr>
                    ${isAvancement ? `
                        <tr><th>N_mat_salarié</th><td>${data.salarie}</td></tr>
                        <tr><th>Salarié</th><td>${data.nomPrenom}</td></tr>
                    ` : ""}
                    ${isVehicle ? `
                        <tr><th>Véhicule</th><td>${data.vehicle}</td></tr>
                        <tr><th>Type</th><td>${data.type}</td></tr>
                    ` : ""}
                    <tr><th>Description</th><td>${data.description}</td></tr>
                    <tr><th>Montant</th><td>${data.montant}</td></tr>
                    <tr><th>Règlement</th><td>${data.reglement}</td></tr>
                    <tr><th>Créé par</th><td>${data.creator}</td></tr>
                    <tr><th>Épreuve</th><td>${data.hasEpreuve ? "Disponible" : "Non disponible"}</td></tr>
                </table>
                <div class="print-footer">
                    <p>Siège social: ${companySettings.address || "Non spécifié"} | Capital: ${companySettings.capital ? companySettings.capital.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD" : "Non spécifié"} | Tél: ${companySettings.phone_number || "Non spécifié"}</p>
                    <p>R.C.: ${companySettings.commercial_register || "Non spécifié"} | CNSS: ${companySettings.cnss_number || "Non spécifié"} | IF: ${companySettings.tax_id || "Non spécifié"} | TP: ${companySettings.tax_id || "Non spécifié"} | ICE: ${companySettings.patent_number || "Non spécifié"}</p>
                    <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${companySettings.bank_name || "Non spécifié"} | Email: ${companySettings.email || "Non spécifié"}</p>
                </div>
                <button class="no-print" onclick="window.print()">Imprimer</button>
            </div>
        </body>
        </html>
    `);
    iframeDoc.close();

    iframe.onload = function () {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch (e) {
            console.error("Erreur lors de l'impression individuelle:", e);
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Impossible de lancer l'impression.",
                position: "center",
                confirmButtonText: "OK",
            });
        }
    };

    iframe.contentWindow.onafterprint = function () {
        document.body.removeChild(iframe);
    };

    setTimeout(() => {
        if (iframe.parentNode) {
            document.body.removeChild(iframe);
        }
    }, 5000);
});

// Gestion de la sélection des cases à cocher pour les avances
$(document).on("change", ".select-avance", function () {
    updateGenerateButtonState();
});

$("#select-all-avances").on("change", function () {
    const isChecked = this.checked;
    $(".select-avance").prop("checked", isChecked);
    updateGenerateButtonState();
});

// Fonction pour activer/désactiver le bouton de génération de PDF
function updateGenerateButtonState() {
    const checkedCount = $(".select-avance:checked").length;
    $("#generate-selected-pdf").prop("disabled", checkedCount === 0);
}

// Gestion du clic sur le bouton de génération de PDF pour les avances sélectionnées

$("#generate-selected-pdf").on("click", function () {
    const selectedIds = $(".select-avance:checked").map(function () {
        return $(this).val();
    }).get();

    if (selectedIds.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Aucune sélection",
            text: "Veuillez sélectionner au moins une avance pour générer le PDF.",
            position: "center",
            confirmButtonText: "OK",
        });
        return;
    }

    // Vérifier si toutes les avances sélectionnées ont un règlement par virement
    const table = $("#avancementsTable").DataTable();
    let hasNonVirement = false;

    selectedIds.forEach(id => {
        const row = table.row(function (idx, data) {
            return data.id == id;
        });
        const rowData = row.data();
        if (rowData && rowData.reglement_depense !== "Virement") {
            hasNonVirement = true;
        }
    });

    if (hasNonVirement) {
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Certaines avances sélectionnées n'ont pas un règlement par virement.",
            position: "center",
            confirmButtonText: "OK",
        });
        return;
    }

    // Ouvrir la modale de choix du type de virement
    $("#virementTypeModal").modal("show");
});

let referenceCheckTimeout = null;
let referenceIsValid = true;

// Pré-remplir la référence à l'ouverture de la modale
$("#virementTypeModal").on("show.bs.modal", function () {
    $("#reference-feedback").text("").removeClass("text-danger text-success");
    referenceIsValid = true;
    $("#confirm-virement-type").prop("disabled", false);

    $.ajax({
        url: "/depenses/avances/last-reference",
        method: "GET",
        success: function (res) {
            if (res.reference) {
                $("#virement_reference").val(res.reference);
            } else {
                $("#virement_reference").val("");
            }
        },
        error: function () {
            $("#virement_reference").val("");
        }
    });
});

// Vérification en temps réel de l'unicité de la référence
$(document).on("input", "#virement_reference", function () {
    const reference = $(this).val().trim();
    const $feedback = $("#reference-feedback");

    clearTimeout(referenceCheckTimeout);

    if (!reference) {
        $feedback.text("").removeClass("text-danger text-success");
        referenceIsValid = false;
        $("#confirm-virement-type").prop("disabled", false); // la validation "required" se fera au clic
        return;
    }

    referenceCheckTimeout = setTimeout(function () {
        $.ajax({
            url: "/depenses/check-reference",
            method: "GET",
            data: { reference: reference },
            success: function (res) {
                if (res.exists) {
                    $feedback
                        .text("⚠ Cette référence existe déjà, veuillez en choisir une autre.")
                        .removeClass("text-success")
                        .addClass("text-danger");
                    referenceIsValid = false;
                    $("#confirm-virement-type").prop("disabled", true);
                } else {
                    $feedback
                        .text("✓ Référence disponible")
                        .removeClass("text-danger")
                        .addClass("text-success");
                    referenceIsValid = true;
                    $("#confirm-virement-type").prop("disabled", false);
                }
            }
        });
    }, 400); // debounce 400ms
});

// Gestion de la confirmation du type de virement
$(document).on("click", "#confirm-virement-type", function () {
    const selectedIds = $(".select-avance:checked").map(function () {
        return $(this).val();
    }).get();

    const virementType = $('input[name="virement_type"]:checked').val();
    const reference = $("#virement_reference").val().trim();

    if (!reference) {
        Swal.fire({
            icon: "warning",
            title: "Référence manquante",
            text: "Veuillez saisir une référence avant de générer le PDF.",
            position: "center",
            confirmButtonText: "OK",
        });
        return;
    }

    if (selectedIds.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Aucune sélection",
            text: "Veuillez sélectionner au moins une avance.",
            position: "center",
            confirmButtonText: "OK",
        });
        return;
    }

    const $confirmBtn = $(this);
    $confirmBtn.prop("disabled", true).text("Vérification...");

    $.ajax({
        url: window.checkReferenceUrl,
        method: "GET",
        data: { reference: reference },
        success: function (response) {
            if (response.exists) {
                $confirmBtn.prop("disabled", false).text("Générer le PDF");
                Swal.fire({
                    icon: "error",
                    title: "Référence invalide",
                    text: "Cette référence existe déjà. Veuillez en choisir une autre.",
                    position: "center",
                    confirmButtonText: "OK",
                });
                return;
            }

            // Fermer la modale de choix
            $("#virementTypeModal").modal("hide");

            // Afficher la modale de chargement
            Swal.fire({
                title: "Génération du PDF en cours...",
                text: "Veuillez patienter...",
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const url = `/depenses/avances/pdf/selected?ids=${encodeURIComponent(selectedIds.join(","))}&type=${virementType}&ref=${encodeURIComponent(reference)}`;

            // Ouvrir le PDF dans un nouvel onglet
            window.open(url, "_blank");

            // Recharger la page actuelle après un court délai
            setTimeout(function () {
                Swal.close();
                window.location.reload();
            }, 1500);
        },
        error: function (xhr) {
            $confirmBtn.prop("disabled", false).text("Générer le PDF");
            console.error("Erreur vérification référence:", xhr.status, xhr.responseText);
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Impossible de vérifier la référence. Veuillez réessayer.",
                position: "center",
                confirmButtonText: "OK",
            });
        }
    });
});

$(document).ready(function () {

    // Initialisation quand la modale s'ouvre (meilleure pratique)
    $('#addAvancementDepenseModal').on('shown.bs.modal', function () {
        $('#avancement_salarie_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Sélectionner un salarié',
            allowClear: true,
            dropdownParent: $('#addAvancementDepenseModal'), // indispensable
            language: {
                noResults: function() {
                    return "Aucun salarié trouvé";
                },
                searching: function() {
                    return "Recherche...";
                }
            }
        });
    });

    // Nettoyage quand on ferme la modale (évite les bugs)
    $('#addAvancementDepenseModal').on('hidden.bs.modal', function () {
        if ($('#avancement_salarie_id').hasClass('select2-hidden-accessible')) {
            $('#avancement_salarie_id').select2('destroy');
        }
    });
});
});