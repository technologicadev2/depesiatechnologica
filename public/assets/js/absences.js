document.addEventListener("DOMContentLoaded", function () {
    // Ensure jQuery and DataTables are loaded
    if (!window.jQuery || !$.fn.DataTable) {
        console.error("jQuery or DataTables is not loaded.");
        return;
    }

    // Fonction pour ajouter les filtres par colonne
    function addColumnSearch(
        tableSelector,
        dataTable,
        nonSearchableColumns = []
    ) {
        $(`${tableSelector} thead tr`)
            .clone(true)
            .appendTo(`${tableSelector} thead`);
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

    // Fonction pour générer le contenu HTML de l'impression
function generatePrintContent(table, title, columns) {
    const data = table.rows({ search: "applied" }).data().toArray();
    const logoUrl = companySettings && companySettings.logo
        ? `${window.location.origin}/storage/${companySettings.logo}`
        : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

    // Split data into chunks of 13 rows
    const rowsPerPage = 12;
    const pageData = [];
    for (let i = 0; i < data.length; i += rowsPerPage) {
        pageData.push(data.slice(i, i + rowsPerPage));
    }

    // Generate table content for each page
    const tableContentPages = pageData.map((pageRows) => {
        const tableContent = pageRows
            .map(
                (row) => `
                <tr class="table-tr">
                    ${columns.cols
                        .map((col) => {
                            // Handle justification column for printing
                            if (col === 6) {
                                const justification = row[col];
                                if (
                                    justification === 0 ||
                                    justification === "0" ||
                                    justification == null
                                ) {
                                    return `<td><span class="badge badge-rejected">Non justifié</span></td>`;
                                } else {
                                    return `<td><span class="badge badge-accepted">Justifié</span></td>`;
                                }
                            }
                            return `<td>${row[col] || "-"}</td>`;
                        })
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
                            .map(
                                (header) =>
                                    `<td class="td-bold">${header}</td>`
                            )
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
                html, body, * {
                    padding: 0;
                    margin: 0;
                    box-sizing: border-box;
                    font-family: sans-serif;
                }
                @page {
                    size: landscape;
                    margin: 0mm;
                }
                body * {
                    -webkit-print-color-adjust: exact !important;
                }
                .print {
                    width: 100%;
                    height: auto;
                    padding: 18px 18px 0 18px;
                }
                .print-header {
                    width: 100%;
                    height: auto;
                    margin-bottom: 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                }
                .print-header-box {
                    position: relative;
                }
                .print-header-title {
                    font-size: 15px;
                    font-weight: bold;
                    line-height: 1.45;
                    margin-left: 10px;
                }
                .print-header-type {
                    width: 100%;
                    height: 23px;
                    line-height: 21px;
                    text-align: center;
                    background-color: #efefef;
                    border: 1px solid black;
                    margin-bottom: 10px;
                    font-size: 13px;
                    font-weight: bold;
                    letter-spacing: 0.5px;
                }
                .print-body-2 table {
                    width: 100%;
                }
                .print-body-2 table, th, td {
                    border: 1px solid black;
                    border-collapse: collapse;
                    padding: 6px;
                    text-align: center;
                    font-size: 12px;
                    font-weight: 500;
                    vertical-align: middle;
                }
                .table-tr td {
                    padding: 6px;
                }
                .print-footer {
                    width: 100%;
                    display: flex;
                    justify-content: space-between;
                    align-items: start;
                    flex-wrap: wrap;
                    margin-top: 10px;
                }
                .print-footer h4 {
                    display: inline-block;
                    margin-right: 20px;
                    font-size: 13px;
                }
                .td-bold {
                    font-size: 13px;
                    font-weight: bold;
                }
                .logo-title-container {
                    display: flex;
                    align-items: center;
                }
                .logo-title-container img {
                    max-width: 50px;
                    height: auto;
                }
                .company-info {
                    position: absolute;
                    bottom: 10px;
                    width: 100%;
                    text-align: center;
                    font-size: 12px;
                    padding: 10px 0;
                    border-top: 1px solid #ddd;
                }
                .badge {
                    display: inline-block;
                    padding: 0.25em 0.4em;
                    font-size: 11px;
                    font-weight: 700;
                    line-height: 1;
                    text-align: center;
                    white-space: nowrap;
                    vertical-align: baseline;
                    border-radius: 0.25rem;
                }
                .badge-accepted {
                    background-color: #28a745;
                    color: #fff;
                }
                .badge-rejected {
                    background-color: #dc3545;
                    color: #fff;
                }
                @media print {
                    body {
                        margin: 0 !important;
                    }
                    .print {
                        margin: 0;
                        padding: 18px 18px 0 18px;
                    }
                    .print-body-2 {
                        margin-bottom: 8px !important;
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
                    .company-info {
                        position: fixed !important;
                        bottom: 0 !important;
                        width: 100% !important;
                        padding: 10px 0 !important;
                        border-top: 1px solid #ddd !important;
                        font-size: 12px !important;
                        z-index: 1000 !important;
                        page-break-outside: avoid !important;
                        break-outside: avoid !important;
                    }
                    @page {
                        margin: 0mm !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print">
                <div class="print-header">
                    <div class="logo-title-container">
                        <img src="${logoUrl}" alt="Logo" />
                        <h1 class="print-header-title">Fiche des Absences</h1>
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
    // Fonction pour gérer l'impression via iframe
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

    // Initialize DataTable
    const dataTable = $("#absencesTable").DataTable({
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
        ordering: false,
        buttons: [
            {
                extend: "collection",
                className:
                    "btn btn-label-primary dropdown-toggle me-2 export-btn",
                text: '<i class="bx bx-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Exporter</span>',
                buttons: [
                    {
                        text: '<i class="bx bx-printer me-1"></i>Imprimer',
                        className: "dropdown-item",
                        action: function (e, dt, node, config) {
                            printTable(
                                "absencesTable",
                                "Absences des Employés",
                                {
                                    cols: [0, 1, 2, 3, 4, 5, 6],
                                    headers: [
                                        "Nom",
                                        "Prénom",
                                        "Matricule",
                                        "Date Début",
                                        "Date Fin",
                                        "Nombre de Jours",
                                        "État de Justification",
                                    ],
                                }
                            );
                        },
                    },
                    {
                        extend: "excel",
                        text: '<i class="bx bxs-file-export me-1"></i>Excel',
                        className: "dropdown-item",
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] },
                    },
                    {
                        extend: "pdf",
                        text: '<i class="bx bxs-file-pdf me-1"></i>PDF',
                        className: "dropdown-item",
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] },
                    },
                ],
            },
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
                previous: "Précédent",
            },  
        },
        columnDefs: [
    {
        targets: 6,
        render: function (data, type, row) {
            if (type === "display") {
                if (data === 'c' || data === "c") {
                    return '<span class="badge bg-warning text-dark">Congé</span>';
                } else if (data === 0 || data === "0" || data == null) {
                    return '<span class="badge badge-rejected">Non justifié</span>';
                } else if (data === 1 || data === "1") {
                    return '<span class="badge badge-accepted">Justifié</span>';
                }
            }
            return data;
        },
    },
],
        initComplete: function () {
            // console.log("DataTable absences initialized");
            // Log all row data to inspect justification values
            this.api()
                .rows()
                .every(function () {
                    const rowData = this.data();
                    // console.log(`Row data: ${JSON.stringify(rowData)}`);
                });
            addColumnSearch("#absencesTable", this.api(), [7]); // Exclure seulement la colonne Actions
            if ($(".dt-action-buttons").find(".btn").length === 0) {
                console.warn("No buttons found in .dt-action-buttons");
            }
        },
        drawCallback: function () {
            // console.log("DataTable redrawn, checking justification values");
            this.api()
                .rows()
                .every(function () {
                    const rowData = this.data();
                    // console.log(`Redraw row data: ${JSON.stringify(rowData)}`);
                });
        },
    });

    // Gérer l'impression d'une absence individuelle
    $(document).on("click", ".print-absence", function () {
        const $button = $(this);
        const $row = $button.closest("tr");
        const absenceId = $button.data("id");
        const rowData = dataTable.row($row).data();

        if (!rowData) {
            console.error("No row data found for Absence ID:", absenceId);
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Impossible de récupérer les données de la ligne.",
                position: "center",
                confirmButtonText: "OK",
            });
            return;
        }

        const data = {
            nom: rowData[0],
            prenom: rowData[1],
            matricule: rowData[2],
            date_debut: rowData[3],
            date_fin: rowData[4],
            nbre_jours: rowData[5],
            justification: rowData[6],
        };

        console.log(
            `Print justification: value=${
                data.justification
            }, type=${typeof data.justification}, row=${JSON.stringify(
                rowData
            )}`
        );

        // Map justification status to badge classes
        let badgeClass = "badge";
        let justificationText = data.justification || "-";
        if (
            data.justification === 0 ||
            data.justification === "0" ||
            data.justification == null
        ) {
            badgeClass += " badge-rejected";
            justificationText = "Non justifié";
        } else if (data.justification === 1 || data.justification === "1") {
            badgeClass += " badge-accepted";
            justificationText = "Justifié";
        }
      const logoUrl = companySettings && companySettings.logo
    ? `${window.location.origin}/storage/${companySettings.logo}`
    : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;
        const iframe = document.createElement("iframe");
        iframe.style.display = "none";
        document.body.appendChild(iframe);
        const iframeDoc =
            iframe.contentDocument || iframe.contentWindow.document;

        iframeDoc.open();
        iframeDoc.write(`
            <html>
            <head>
                <title>Impression - Détails de l'Absence</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 20mm; 
                        padding-bottom: 40px; /* Space for footer */
                        box-sizing: border-box;
                    }
                    @page {
                        size: A4 portrait;
                        margin: 20mm;
                    }
                    .print-container { 
                        max-width: 100%; 
                        margin: 0 auto; 
                        page-break-after: avoid;
                    }
                    .header { 
                        display: flex; 
                        align-items: center; 
                        border-bottom: 2px solid #000; 
                        padding-bottom: 8px; 
                        margin-bottom: 15px; 
                    }
                    .header img { 
                        max-width: 80px; 
                        margin-right: 15px; 
                    }
                    .header h1 { 
                        font-size: 20px; 
                        margin: 0; 
                    }
                    .header p { 
                        font-size: 12px; 
                        margin: 5px 0 0; 
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-top: 15px; 
                        font-size: 12px;
                    }
                    th, td { 
                        border: 1px solid #ddd; 
                        padding: 6px; 
                        text-align: left; 
                    }
                    th { 
                        background-color: #f2f2f2; 
                        font-weight: bold; 
                        width: 30%; 
                    }
                    td { 
                        width: 70%; 
                        word-break: break-all; /* Prevent long text from overflowing */
                    }
                    .badge {
                        display: inline-block;
                        padding: 0.25em 0.4em;
                        font-size: 11px;
                        font-weight: 700;
                        line-height: 1;
                        text-align: center;
                        white-space: nowrap;
                        vertical-align: baseline;
                        border-radius: 0.25rem;
                    }
                    .badge-accepted {
                        background-color: #28a745;
                        color: #fff;
                    }
                    .badge-rejected {
                        background-color: #dc3545;
                        color: #fff;
                    }
                    .company-info {
                        position: fixed;
                        bottom: 10mm;
                        left: 20mm;
                        right: 20mm;
                        text-align: center;
                        font-size: 10px;
                        padding-top: 5px;
                        border-top: 1px solid #ddd;
                    }
                    @media print {
                        .print-container {
                            page-break-after: avoid;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="print-container">
                    <div class="header">
                           <img src="${logoUrl}" alt="Logo" />
                        <div>
                            <h1>Détails de l'Absence</h1>
                            <p>Date d'impression: ${new Date().toLocaleDateString(
                                "fr-FR"
                            )}</p>
                        </div>
                    </div>
                    <table>
                        <tr><th>Nom</th><td>${data.nom || "-"}</td></tr>
                        <tr><th>Prénom</th><td>${data.prenom || "-"}</td></tr>
                        <tr><th>Matricule</th><td>${
                            data.matricule || "-"
                        }</td></tr>
                        <tr><th>Date Début</th><td>${
                            data.date_debut || "-"
                        }</td></tr>
                        <tr><th>Date Fin</th><td>${
                            data.date_fin || "-"
                        }</td></tr>
                        <tr><th>Nombre de Jours</th><td>${
                            data.nbre_jours || "-"
                        }</td></tr>
                        <tr><th>État de Justification</th><td><span class="${badgeClass}">${justificationText}</span></td></tr>
                    </table>
                </div>
               <div class="company-info">
                    <p>Siège social: ${
                        companySettings.address || "Non spécifié"
                    }  Capital: ${companySettings.capital ? companySettings.capital.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD" : "Non spécifié"} | Tél: ${companySettings.phone_number || "Non spécifié"} </p>
                    <p>R.C.: ${
                        companySettings.commercial_register || "Non spécifié"
                    } | CNSS: ${companySettings.cnss_number || "Non spécifié"} | IF: ${companySettings.tax_id || "Non spécifié"} TP: ${companySettings.tax_id || "Non spécifique"} ICE: ${companySettings.patent_number || "Non spécifique"}</p>
                    <p>C.B.: ${
                        companySettings.account_number || "Non spécifié"
                    }, ${companySettings.bank_name || "Non spécifié"} Email: ${companySettings.email || "Non spécifié"} </p>
                </div>
            </body>
            </html>
        `);
        iframeDoc.close();

        iframe.onload = function () {
            iframe.contentWindow.print();
            iframe.contentWindow.onafterprint = function () {
                document.body.removeChild(iframe);
            };
            setTimeout(function () {
                document.body.removeChild(iframe);
            }, 1000);
        };
    });

    // Gérer l'ouverture du modal de justification
    $(document).on("click", ".justify-absence", function () {
        const absenceId = $(this).data("id");
        $("#absence_id").val(absenceId);

        $.ajax({
            url: `/absences/${absenceId}`,
            type: "GET",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                console.log(
                    `Justification modal data: ${JSON.stringify(response)}`
                );
                $("#description").val(response.description || "");
                $("#piece_jointe").val("");
                $("#justificationModal").modal("show");
            },
            error: function (xhr) {
                console.error(
                    `Error loading absence data: ${xhr.responseText}`
                );
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: "Impossible de charger les données de l'absence.",
                    position: "center",
                    confirmButtonText: "OK",
                });
            },
        });
    });

    // Gérer la soumission du formulaire de justification
    $("#justificationForm").on("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const absenceId = $("#absence_id").val();

        const description = formData.get("description");
        if (description === "absence") {
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Le champ description ne peut pas contenir la valeur 'absence'.",
                position: "center",
                confirmButtonText: "OK",
            });
            return;
        }

        $.ajax({
            url: `/absences/${absenceId}/justification`,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                console.log(
                    `Justification submission response: ${JSON.stringify(
                        response
                    )}`
                );
                $("#justificationModal").modal("hide");
                Swal.fire({
                    icon: "success",
                    title: "Succès",
                    text:
                        response.message ||
                        "Justification enregistrée avec succès",
                    position: "center",
                    confirmButtonText: "OK",
                });

                // Update the DataTable row only after successful save
                const table = $("#absencesTable").DataTable();
                const row = table.row(`tr:has(button[data-id="${absenceId}"])`); // Find the row by absence ID
                const rowData = row.data(); // Get the current row data

                if (rowData) {
                    // Update the justification status (index 6 corresponds to the État de Justification column)
                    rowData[6] = 1; // Set to 1 (Justifié)
                    console.log(
                        "Row found:",
                        row.index(),
                        "Updated data:",
                        rowData
                    );

                    // Update the row in the DataTable
                    row.data(rowData).invalidate().draw(false); // Invalidate and redraw without resetting pagination
                } else {
                    console.error("Row not found for absence ID:", absenceId);
                    Swal.fire({
                        icon: "warning",
                        title: "Attention",
                        text: "La ligne n'a pas été trouvée. Veuillez rafraîchir la page.",
                        position: "center",
                        confirmButtonText: "OK",
                    });
                    // Fallback: Redraw the table to ensure consistency
                    table.draw(false);
                }
            },
            error: function (xhr) {
                console.error(
                    `Justification submission error: ${xhr.responseText}`
                );
                let errorMessage =
                    "Une erreur s'est produite lors de l'enregistrement.";
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
});
