$(document).ready(function () {
    const successSound = new Audio("/assets/audio/success.mp3");
    const errorSound = new Audio("/assets/audio/error.mp3");


    // Fonction pour initialiser les calculs automatiques des salaires

function initializeSalaryCalculations(modalId = '#addSalarieModal') {
    const modal = document.querySelector(modalId);
    if (!modal) return;

    const salaireBrutInput       = modal.querySelector('#salaire_brut_mensuel');
    const salaireBaseInput       = modal.querySelector('#salaire_base');
    const salaireJournalierInput = modal.querySelector('#salaire_journalier');
    const autoSalaryCalcCheckbox = modal.querySelector('#auto_salary_calc');
    const cnssPpInput            = modal.querySelector('#cnss_pp');

    if (!salaireBaseInput || !salaireJournalierInput || !autoSalaryCalcCheckbox) {
        console.warn("Champs obligatoires manquants dans le modal", modalId);
        return;
    }

    const cnssPpPercent = cnssPpInput ? parseFloat(cnssPpInput.value) || 0 : 0;
    const JOURS_PAR_MOIS = 26;
    let isUpdating = false;

    // =============================================
    // CAS 1 : Formulaire AJOUT (avec salaire_brut)
    // =============================================
    function updateFromBrut() {
        if (!autoSalaryCalcCheckbox.checked || isUpdating) return;
        isUpdating = true;

        const brutMensuel = parseFloat(salaireBrutInput.value) || 0;
        if (brutMensuel > 0 && cnssPpPercent > 0) {
            const partCnss = brutMensuel * (cnssPpPercent / 100);
            const salaireBaseCalcule = (partCnss / JOURS_PAR_MOIS) + brutMensuel;
            salaireBaseInput.value = salaireBaseCalcule.toFixed(2);
            salaireJournalierInput.value = (salaireBaseCalcule / JOURS_PAR_MOIS).toFixed(2);
        } else {
            salaireBaseInput.value = '';
            salaireJournalierInput.value = '';
        }
        isUpdating = false;
    }

    // =============================================
    // CAS 2 : Formulaire ÉDITION (base ↔ journalier)
    // =============================================
    function updateJournalierFromBase() {
        if (!autoSalaryCalcCheckbox.checked || isUpdating) return;
        isUpdating = true;

        const base = parseFloat(salaireBaseInput.value) || 0;
        if (base > 0) {
            salaireJournalierInput.value = (base / JOURS_PAR_MOIS).toFixed(2);
        } else {
            salaireJournalierInput.value = '';
        }
        isUpdating = false;
    }

    function updateBaseFromJournalier() {
        if (!autoSalaryCalcCheckbox.checked || isUpdating) return;
        isUpdating = true;

        const journalier = parseFloat(salaireJournalierInput.value) || 0;
        if (journalier > 0) {
            salaireBaseInput.value = (journalier * JOURS_PAR_MOIS).toFixed(2);
        } else {
            salaireBaseInput.value = '';
        }
        isUpdating = false;
    }

    // Attacher les bons listeners selon le contexte
    if (salaireBrutInput) {
        // Formulaire AJOUT
        salaireBrutInput.addEventListener('input', updateFromBrut);
        salaireBrutInput.addEventListener('change', updateFromBrut);

        autoSalaryCalcCheckbox.addEventListener('change', () => {
            if (autoSalaryCalcCheckbox.checked) updateFromBrut();
        });

        if (autoSalaryCalcCheckbox.checked && salaireBrutInput.value) {
            updateFromBrut();
        }

    } else {
        // Formulaire ÉDITION : calcul croisé base ↔ journalier
        salaireBaseInput.addEventListener('input', updateJournalierFromBase);
        salaireBaseInput.addEventListener('change', updateJournalierFromBase);

        salaireJournalierInput.addEventListener('input', updateBaseFromJournalier);
        salaireJournalierInput.addEventListener('change', updateBaseFromJournalier);

        autoSalaryCalcCheckbox.addEventListener('change', () => {
            if (autoSalaryCalcCheckbox.checked && salaireBaseInput.value) {
                updateJournalierFromBase(); // recalculer journalier depuis base au cochage
            }
        });

        // Calcul initial si case déjà cochée (données existantes)
        if (autoSalaryCalcCheckbox.checked && salaireBaseInput.value) {
            updateJournalierFromBase();
        }
    }
}


function printSalarieTable(table, title, columns) {
    const logoUrl =
        companySettings && companySettings.logo
            ? `${window.location.origin}/storage/${companySettings.logo}`
            : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;
    try {
        const data = table.rows({ search: "applied" }).data().toArray();

        // Split data into chunks of 13 rows
        const rowsPerPage = 20;
        const pageData = [];
        for (let i = 0; i < data.length; i += rowsPerPage) {
            pageData.push(data.slice(i, i + rowsPerPage));
        }

        // Generate table content for each page
        const tableContentPages = pageData.map((pageRows) => {
            const tableContent = pageRows
                .map(
                    (row) => `
                    <tr>
                        ${columns.cols
                            .map(
                                (col) =>
                                    `<td>${
                                        col === "fonction"
                                            ? row.fonction?.designation || "N/A"
                                            : col === "salaire_base"
                                            ? (row[col] ? row[col].toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD' : "-")
                                            : row[col] || "-"
                                    }</td>`
                            )
                            .join("")}
                    </tr>`
                )
                .join("");

            return `
                <div class="table-section" style="margin-bottom: 20px; page-break-after: always;">
                    <table>
                        <tr>${columns.headers
                            .map((header) => `<th>${header}</th>`)
                            .join("")}</tr>
                        ${tableContent}
                    </table>
                </div>
            `;
        }).join("");

        const html = `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Impression - ${title}</title>
            <style>
                @page {
                    size: A4;
                    margin: 15mm;
                    @top-center {
                        content: "${title}";
                        font-family: 'Helvetica Neue', Arial, sans-serif;
                        font-size: 12px;
                        color: #2c3e50;
                    }
                    @bottom-center {
                        content: "Page " counter(page) " de " counter(pages);
                        font-family: 'Helvetica Neue', Arial, sans-serif;
                        font-size: 10px;
                        color: #7f8c8d;
                    }
                }
                body {
                    font-family: 'Helvetica Neue', Arial, sans-serif;
                    margin: 15mm;
                    color: #333;
                    line-height: 1.5;
                    font-size: 12px;
                }
                .header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    border-bottom: 2px solid #2c3e50;
                    padding-bottom: 10px;
                    margin-bottom: 15px;
                    page-break-after: avoid;
                }
                .header img {
                    max-width: 100px;
                    height: auto;
                }
                .header .company-title {
                    font-size: 20px;
                    font-weight: bold;
                    color: #2c3e50;
                }
                .title-section {
                    margin-bottom: 15px;
                    text-align: center;
                    page-break-after: avoid;
                }
                .title-section h1 {
                    font-size: 24px;
                    color: #2c3e50;
                    margin: 0;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .title-section p {
                    font-size: 12px;
                    color: #7f8c8d;
                    margin: 5px 0;
                }
                .table-section {
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    page-break-inside: auto;
                }
                th, td {
                    border: 1px solid #e0e0e0;
                    padding: 8px 10px;
                    text-align: left;
                    font-size: 11px;
                }
                th {
                    background-color: #34495e;
                    color: #fff;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                td {
                    background-color: #f9f9f9;
                }
                tr:nth-child(even) td {
                    background-color: #fff;
                }
                tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }
                .company-info { 
                    font-size: 12px; 
                    line-height: 1.5; 
                    text-align: center; 
                    position: fixed; 
                    bottom: 10mm; 
                    width: 100%; 
                    page-break-outside: avoid;
                }
                .company-info p { 
                    margin: 2px 0; 
                }
                @media print {
                    body {
                        margin: 0;
                    }
                    .header {
                        margin-bottom: 10px;
                    }
                    .title-section {
                        margin-bottom: 10px;
                    }
                    table {
                        box-shadow: none;
                        page-break-outside: auto;
                        break-outside: auto;
                    }
                    .table-section {
                        margin-bottom: 20px;
                        page-break-after: always;
                    }
                    tr {
                        page-break-inside: avoid;
                        break-inside: avoid;
                        page-break-after: auto;
                        break-after: auto;
                    }
                    .company-info { 
                        position: fixed;
                        bottom: 10mm;
                        width: 100%;
                        font-size: 12px;
                        line-height: 1.5;
                        text-align: center;
                        page-break-outside: avoid;
                        break-outside: avoid;
                    }
                    td:nth-child(10) { /* Colonne "Adresse", ajustez l'index selon vos colonnes */
                        max-width: 150px;
                        word-wrap: break-word;
                        white-space: normal;
                    }
                    td:not(:nth-child(10)) {
                        max-width: 100px;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="${logoUrl}" alt="Logo" />
                <div class="company-title">${companySettings.nom_etreprise || 'Entreprise'}</div>
            </div>
            <div class="title-section">
                <h1>${title}</h1>
                <p>Date: ${new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })}</p>
            </div>
            ${tableContentPages}
            <div class="company-info">
                <p>Siège social: ${companySettings.address || "Non spécifié"} | Capital: ${
                    companySettings.capital
                        ? companySettings.capital.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD"
                        : "Non spécifié"
                } | Tél: ${companySettings.phone_number || "Non spécifié"}</p>
                <p>R.C.: ${companySettings.commercial_register || "Non spécifié"} | CNSS: ${
                    companySettings.cnss_number || "Non spécifié"
                } | IF: ${companySettings.tax_id || "Non spécifié"} | TP: ${
                    companySettings.tax_id || "Non spécifié"
                } | ICE: ${companySettings.patent_number || "Non spécifié"}</p>
                <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${
                    companySettings.bank_name || "Non spécifié"
                } | Email: ${companySettings.email || "Non spécifié"}</p>
            </div>
        </body>
        </html>`;
        printContent(html, title);
    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Erreur lors de l'impression de la table",
            timer: 5000,
        });
        const errorSound = new Audio("/assets/audio/error.mp3");
        errorSound.play().catch((err) => console.log("Erreur audio:", err));
    }
}

function printContent(html, title) {
    console.log("Starting print content");
    try {
        const iframe = $("<iframe>", { style: "display: none;" }).appendTo("body")[0];
        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
        setTimeout(() => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            iframe.contentWindow.onafterprint = () => $(iframe).remove();
        }, 500);
    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Erreur lors de l'impression",
            timer: 5000,
        });
        const errorSound = new Audio("/assets/audio/error.mp3");
        errorSound.play().catch((err) => console.log("Erreur audio:", err));
    }
}
    function printSingleSalarie(data) {
    try {
        const contractPath = data.contrat
            ? `${window.location.origin}/${data.contrat}`
            : "Non disponible (vérifiez dans public/assets/docs)";
        
        // ✅ Ajout : récupération du chemin photo
        const photoUrl = data.photo
            ? `${window.location.origin}/${data.photo}`
            : null;

        const logoUrl =
            companySettings && companySettings.logo
                ? `${window.location.origin}/storage/${companySettings.logo}`
                : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

        const html = `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Impression - Détails du Salarié</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin: 10mm; color: #333;
            line-height: 1.4; font-size: 12px;
        }
        .header {
            display: flex; align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #2c3e50;
            padding-bottom: 8px; margin-bottom: 12px;
        }
        .header img { max-width: 80px; height: auto; }
        .header .company-title { font-size: 18px; font-weight: bold; color: #2c3e50; }
        .title-section { margin-bottom: 10px; text-align: center; }
        .title-section h1 {
            font-size: 20px; color: #2c3e50; margin: 0;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .title-section p { font-size: 10px; color: #7f8c8d; margin: 3px 0; }

        /* ✅ Style photo salarié */
        .photo-section {
            text-align: center;
            margin-bottom: 15px;
        }
        .photo-section img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #34495e;
        }
        .photo-section .no-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #ecf0f1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #7f8c8d;
            border: 2px solid #bdc3c7;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #e0e0e0; padding: 8px 10px; text-align: left; font-size: 11px; }
        th { background-color: #34495e; color: #fff; font-weight: 600; text-transform: uppercase; }
        td { background-color: #f9f9f9; }
        tr:nth-child(even) td { background-color: #fff; }
        .company-info {
            font-size: 12px; line-height: 1.5; text-align: center;
            position: fixed; bottom: 10mm; width: 100%;
        }
        .company-info p { margin: 2px 0; }
        @media print {
            body { margin: 0; }
            .company-info {
                font-size: 12px; line-height: 1.5; text-align: center;
                position: fixed; bottom: 10mm; width: 100%;
            }
            .company-info p { margin: 2px 0; }
            table { box-shadow: none; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="${logoUrl}" alt="Logo" />
        <div class="company-title">${companySettings.nom_etreprise || 'Entreprise'}</div>
    </div>
    <div class="title-section">
        <h1>Détails du Salarié</h1>
        <p>Date: ${new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })}</p>
    </div>

    <!-- ✅ Affichage photo salarié -->
    <div class="photo-section">
        ${photoUrl
            ? `<img src="${photoUrl}" alt="Photo du salarié" />`
            : `<div class="no-photo">Pas de photo</div>`
        }
    </div>

    <table>
        <tr><th>Nom</th><td>${data.nom || "-"}</td></tr>
        <tr><th>Prénom</th><td>${data.prenom || "-"}</td></tr>
        <tr><th>Email</th><td>${data.email || "-"}</td></tr>
        <tr><th>CIN</th><td>${data.cin || "-"}</td></tr>
        <tr><th>Date de Naissance</th><td>${data.date_naissance || "-"}</td></tr>
        <tr><th>Matricule CNSS</th><td>${data.n_matricule_cnss || "-"}</td></tr>
        <tr><th>Situation Familiale</th><td>${data.situation_familiale || "-"}</td></tr>
        <tr><th>Nombre d'Enfants</th><td>${data.nombre_enfant || "0"}</td></tr>
        <tr><th>Téléphone</th><td>${data.phone || "-"}</td></tr>
        <tr><th>Adresse</th><td>${data.adresse || "-"}</td></tr>
        <tr><th>Fonction</th><td>${data.fonction ? data.fonction.designation : "N/A"}</td></tr>
        <tr><th>Matricule Entreprise</th><td>${data.n_matricule_entreprise || "-"}</td></tr>
        <tr><th>Type de Règlement</th><td>${data.reglement ? data.reglement.designation : "N/A"}</td></tr>
        <tr><th>RIB</th><td>${data.rib || "-"}</td></tr>
        <tr><th>Type de Travail</th><td>${data.type_travail || "-"}</td></tr>
         <tr><th>Salaire net </th><td>${data.salaire_net || "-"}</td></tr>
        <tr><th>Salaire de Base</th><td>${data.salaire_base ? data.salaire_base.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD' : "-"}</td></tr>
        <tr><th>Contrat</th><td>${data.type_contrat}</td></tr>
    </table>

    <div class="company-info">
        <p>Siège social: ${companySettings.address || "Non spécifié"} | Capital: ${
            companySettings.capital
                ? companySettings.capital.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD"
                : "Non spécifié"
        } | Tél: ${companySettings.phone_number || "Non spécifié"}</p>
        <p>R.C.: ${companySettings.commercial_register || "Non spécifié"} | CNSS: ${
            companySettings.cnss_number || "Non spécifié"
        } | IF: ${companySettings.tax_id || "Non spécifié"} | TP: ${
            companySettings.tax_id || "Non spécifié"
        } | ICE: ${companySettings.patent_number || "Non spécifié"}</p>
        <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${
            companySettings.bank_name || "Non spécifié"
        } | Email: ${companySettings.email || "Non spécifié"}</p>
    </div>
</body>
</html>`;
        printContent(html, "Détails du Salarié");
    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Erreur lors de l'impression du salarié",
            timer: 5000,
        });
        errorSound.play();
    }
}
  let preavisData = {
        date_debut_preavis: null,
        date_fin_preavis: null,
        document_preavis: null,
        preavis_id: null,
    };

    const dt_salarie_table = $("#salariesTable");
    let dt_salarie;

    if (dt_salarie_table.length) {
        // Cloner une seule fois la ligne d'en-tête
        dt_salarie_table.find("thead tr").clone(true).appendTo(dt_salarie_table.find("thead"));

        // Appliquer les filtres à la deuxième ligne
        dt_salarie_table.find("thead tr:eq(1) th").each(function (i) {
            const title = $(this).text().trim();
            if ([0, 8].indexOf(i) === -1) {
                $(this).html(
                    `<input type="text" class="form-control form-control-sm" placeholder="Rechercher ${title}" />`
                );
                $("input", this).on("keyup change", function () {
                    if (dt_salarie.column(i).search() !== this.value) {
                        dt_salarie.column(i).search(this.value).draw();
                    }
                });
            } else {
                $(this).html("");
            }
        });

        dt_salarie = dt_salarie_table.DataTable({
            ajax: {
                url: dt_salarie_table.data("salaries-list-url"),
                data: function (d) {
                    d.status = $('#statusFilter').val();
                },
                dataSrc: "",
               
            },
            columns: [
                { data: null, defaultContent: "" }, // Colonne vide
                { data: "n_matricule_entreprise" },
                { data: "nom" },
                { data: "prenom" },
                { data: "cin" },
                { data: "phone" },
                { data: "adresse" },
                {
                    data: "fonction",
                    render: (data, type, full) => full.fonction?.designation || "N/A",
                },
                {
                    data: null,
                    render: (data, type, full) => {
                        if (full.statut === 'inactif') {
                            return `
                                <div class="d-flex align-items-center">
                                    <a href="javascript:;" class="text-body reactivate-salarie" data-id="${full.id}"
                                        data-bs-toggle="tooltip" title="Réactiver"><i class="bx bx-undo mx-1"></i></a>
                                </div>`;
                        } else {
                            return `
                                <div class="d-flex align-items-center">
                                    <a href="javascript:;" class="text-body edit-salarie" data-id="${full.id}"
                                        data-bs-toggle="tooltip" title="Modifier"><i class="bx bx-edit mx-1"></i></a>
                                    <a href="javascript:;" class="text-body delete-record" data-id="${full.id}"
                                        data-bs-toggle="tooltip" title="Supprimer"><i class="bx bx-trash mx-1"></i></a>
                                    <a href="javascript:;" class="text-body show-salarie" data-attr="${dt_salarie_table.data("salaries-base-url")}/${full.id}"
                                        data-bs-toggle="tooltip" title="Voir"><i class="bx bx-show mx-1"></i></a>
                                    <a href="javascript:;" class="text-body print-salarie" data-id="${full.id}"
                                        data-bs-toggle="tooltip" title="Imprimer"><i class="bx bx-printer mx-1"></i></a>
                                    <a href="javascript:;" class="text-body demission-salarie" data-id="${full.id}"
                                        data-bs-toggle="tooltip" title="Démission"><i class="bx bx-exit mx-1"></i></a>
                                </div>`;
                        }
                    },
                },
            ],
            columnDefs: [
                { targets: [0, 8], searchable: false, orderable: false },
                { targets: 0, render: () => '<i class="" style="cursor: pointer;"></i>' },
                { targets: [1, 2, 3, 4, 5, 6, 7], render: (data) => data || "-" },
            ],
            language: {
                lengthMenu: "Afficher _MENU_ salariés",
                search: "Rechercher",
                searchPlaceholder: "Rechercher un salarié",
                paginate: { next: "Suivant", previous: "Précédent" },
                info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            },
            dom: '<"row ms-2 me-3"<"col-md-6"l<"dt-action-buttons"B>><"col-md-6"f>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                {
                    extend: "collection",
                    className: "btn btn-label-primary dropdown-toggle me-2",
                    text: '<i class="bx bx-export me-sm-1"></i> Exporter',
                    buttons: [
                     /*    {
                            text: '<i class="bx bx-printer me-1"></i>Imprimer',
                            action: () => printSalarieTable(dt_salarie, "Liste des Salariés", {
                                cols: ["n_matricule_entreprise", "nom", "prenom", "cin", "phone", "adresse", "fonction"],
                                headers: ["Matricule Entreprise", "Nom", "Prénom", "CIN", "Téléphone", "Adresse", "Fonction"],
                            }),
                        }, */
                        //{ extend: "csv", text: '<i class="bx bx-file me-1"></i>CSV', exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7] } },
                 {
    text: '<i class="bx bxs-file-export me-1"></i>Excel',
    action: function () {
        const data = dt_salarie.rows({ search: "applied" }).data().toArray();

        // Créer les lignes Excel
        let rows = [
            // En-tête
            [
                "Matricule", "Nom", "Prénom", "CIN", "Email", "Téléphone",
                "Date Naissance", "Adresse", "Situation Familiale", "Enfants",
                "CNSS", "Fonction", "Type Travail", "Type Contrat",
                "Salaire Base (MAD)", "Salaire Journalier (MAD)", "Règlement", "RIB", "Date Embauche"
            ]
        ];

        // Données
        data.forEach(row => {
            rows.push([
                row.n_matricule_entreprise || "-",
                row.nom || "-",
                row.prenom || "-",
                row.cin || "-",
                row.email || "-",
                row.phone || "-",
                row.date_naissance || "-",
                row.adresse || "-",
                row.situation_familiale || "-",
                row.nombre_enfant ?? "0",
                row.n_matricule_cnss || "-",
                row.fonction?.designation || "N/A",
                row.type_travail || "-",
                row.type_contrat || "-",
                row.salaire_base ? parseFloat(row.salaire_base).toFixed(2) : "-",
                row.salaire_journalier ? parseFloat(row.salaire_journalier).toFixed(2) : "-",
                row.reglement?.designation || "N/A",
                row.rib || "-",
                row.date_embauche || "-"
            ]);
        });

        // Convertir en format CSV/Excel
        let csvContent = rows.map(row =>
            row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(";")
        ).join("\n");

        // Ajouter BOM pour UTF-8 (caractères spéciaux français)
        const BOM = "\uFEFF";
        const blob = new Blob([BOM + csvContent], { type: "text/csv;charset=utf-8;" });

        // Télécharger
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `Liste_Salaries_${new Date().toLocaleDateString('fr-FR').replace(/\//g, '-')}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }
},
     {
    text: '<i class="bx bxs-file-pdf me-1"></i>IMPRIMER',
    action: function () {
        const logoUrl = companySettings && companySettings.logo
            ? `${window.location.origin}/storage/${companySettings.logo}`
            : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

        const data = dt_salarie.rows({ search: "applied" }).data().toArray();

        const rowsPerPage = 15;
        const pages = [];
        for (let i = 0; i < data.length; i += rowsPerPage) {
            pages.push(data.slice(i, i + rowsPerPage));
        }

      const tablePages = pages.map((pageRows) => {
    const rows1 = pageRows.map((row) => `
        <tr>
            <td>${row.n_matricule_entreprise || "-"}</td>
            <td>${row.nom || "-"}</td>
            <td>${row.prenom || "-"}</td>
            <td>${row.cin || "-"}</td>
            <td>${row.email || "-"}</td>
            <td>${row.phone || "-"}</td>
            <td>${row.date_naissance || "-"}</td>
            <td>${row.adresse || "-"}</td>
            <td>${row.situation_familiale || "-"}</td>
            <td>${row.nombre_enfant ?? "0"}</td>
            <td>${row.n_matricule_cnss || "-"}</td>
            <td>${row.fonction?.designation || "N/A"}</td>
            <td>${row.type_travail || "-"}</td>
            <td>${row.type_contrat || "-"}</td>
            <td>${row.salaire_base 
                ? row.salaire_base.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD' 
                : "-"}</td>
            <td>${row.salaire_journalier
                ? row.salaire_journalier.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD'
                : "-"}</td>
            <td>${row.reglement?.designation || "N/A"}</td>
            <td>${row.rib || "-"}</td>
            <td>${row.date_embauche || "-"}</td>
        </tr>`
    ).join("");

    return `
        <div style="page-break-after: always;">
            <table>
                <thead>
                    <tr>
                        <th style="width:4%">Mat.</th>
                        <th style="width:6%">Nom</th>
                        <th style="width:6%">Prénom</th>
                        <th style="width:5%">CIN</th>
                        <th style="width:11%">Email</th>
                        <th style="width:6%">Tél.</th>
                        <th style="width:5%">Naiss.</th>
                        <th style="width:9%">Adresse</th>
                        <th style="width:4%">S.F.</th>
                        <th style="width:3%">Enf</th>
                        <th style="width:6%">CNSS</th>
                        <th style="width:7%">Fonction</th>
                        <th style="width:4%">T.Tr.</th>
                        <th style="width:4%">T.Co.</th>
                        <th style="width:6%">S.Base</th>
                        <th style="width:6%">S.Jour.</th>
                        <th style="width:4%">Règl.</th>
                        <th style="width:9%">RIB</th>
                        <th style="width:5%">Emb.</th>
                    </tr>
                    <!-- TOTAL = 4+6+6+5+11+6+5+9+4+3+6+7+4+4+6+6+4+9+5 = 100% ✅ -->
                </thead>
                <tbody>${rows1}</tbody>
            </table>
        </div>`;
}).join("");

        const html = `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Salariés</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin: 8mm;
            color: #333;
            font-size: 8px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #2c3e50;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header img { max-width: 70px; height: auto; }
        .header .company-title { font-size: 16px; font-weight: bold; color: #2c3e50; }
        .title-section { text-align: center; margin-bottom: 8px; }
        .title-section h1 {
            font-size: 15px; color: #2c3e50;
            text-transform: uppercase; margin: 0;
        }
        .title-section p { font-size: 9px; color: #7f8c8d; margin: 2px 0; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 4px 4px;
            text-align: left;
            font-size: 7.5px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }
        th {
            background-color: #34495e;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
        }
        td { background-color: #f9f9f9; }
        tr:nth-child(even) td { background-color: #fff; }
        tr { page-break-inside: avoid; }
        .company-info {
            font-size: 7.5px; line-height: 1.5; text-align: center;
            position: fixed; bottom: 4mm; width: 100%;
        }
        .company-info p { margin: 1px 0; }
        @media print {
            body { margin: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="${logoUrl}" alt="Logo" />
        <div class="company-title">${companySettings.nom_etreprise || 'Entreprise'}</div>
    </div>
    <div class="title-section">
        <h1>Liste des Salariés</h1>
        <p>Date: ${new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })}</p>
    </div>

    ${tablePages}

    <div class="company-info">
        <p>Siège social: ${companySettings.address || "Non spécifié"} | Capital: ${
            companySettings.capital
                ? companySettings.capital.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD"
                : "Non spécifié"
        } | Tél: ${companySettings.phone_number || "Non spécifié"}</p>
        <p>R.C.: ${companySettings.commercial_register || "Non spécifié"} | CNSS: ${
            companySettings.cnss_number || "Non spécifié"
        } | IF: ${companySettings.tax_id || "Non spécifié"} | ICE: ${
            companySettings.patent_number || "Non spécifié"
        }</p>
        <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${
            companySettings.bank_name || "Non spécifié"
        } | Email: ${companySettings.email || "Non spécifié"}</p>
    </div>
</body>
</html>`;

        printContent(html, "Liste des Salariés");
    },
},
                        // { extend: "copy", text: '<i class="bx bx-copy me-1"></i>Copier', exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7] } },
                    ],
                },
                {
                    text: '<i class="bx bx-plus me-1"></i>Ajouter un salarié',
                    className: "btn btn-primary add-salarie-btn",
                    action: () => $("#addSalarieModal").modal("show"),
                },
            ],
            responsive: true,
            orderCellsTop: true,
            drawCallback: () => {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
                    new bootstrap.Tooltip(el);
                });
            },
        });



        dt_salarie_table.on("click", ".edit-salarie", function (e) {
            e.preventDefault();
            const id = $(this).data("id");
            console.log("Edit clicked for ID:", id);
            $.ajax({
                url: `${dt_salarie_table.data("salaries-base-url")}/${id}/edit`,
                type: "GET",
                headers: { Accept: "text/html" },
                success: (response) => {
                    console.log("Edit response received");
                    $("#editSalarieModal .modal-content").html(response);
                    $("#editSalarieModal").modal("show");
                    initializeSalaryCalculations("#editSalarieModal");
                },
                error: (xhr) => {
                    console.error("Edit AJAX Error:", xhr);
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: "Erreur lors du chargement du formulaire",
                        timer: 5000,
                    });
                   
                },
            });
        });

        dt_salarie_table.on("click", ".delete-record", function (e) {
            e.preventDefault();
            const id = $(this).data("id");
            console.log("Delete clicked for ID:", id);
            Swal.fire({
                title: "Êtes-vous sûr ?",
                text: "Voulez-vous supprimer ce salarié ?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Oui, supprimer",
                cancelButtonText: "Annuler",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `${dt_salarie_table.data("salaries-base-url")}/${id}`,
                        type: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                        },
                        success: (response) => {
                            console.log("Delete success:", response);
                            dt_salarie.ajax.reload();
                            Swal.fire({
                                icon: "success",
                                title: "Succès",
                                text: response.message || "Salarié supprimé !",
                                timer: 5000,
                            });
                            successSound.play();
                        },
                        error: (xhr) => {
                            console.error("Delete AJAX Error:", xhr);
                            Swal.fire({
                                icon: "error",
                                title: "Erreur",
                                text: xhr.responseJSON?.message || "Erreur lors de la suppression",
                                timer: 5000,
                            });
                            errorSound.play();
                        },
                    });
                }
            });
        });

       dt_salarie_table.on("click", ".show-salarie", function (e) {
    e.preventDefault();
    const url = $(this).data("attr");
    $.ajax({
        url: url,
        type: "GET",
        success: (data) => {
            const details = `
                <div class="row">
                    <!-- ✅ Photo du salarié -->
                    <div class="col-12 text-center mb-3">
                        ${data.photo
                            ? `<img src="${window.location.origin}/${data.photo}" 
                                alt="Photo de ${data.nom} ${data.prenom}" 
                                style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:2px solid #34495e;" />`
                            : `<div style="width:100px; height:100px; border-radius:50%; background:#ecf0f1; 
                                display:inline-flex; align-items:center; justify-content:center; 
                                font-size:11px; color:#7f8c8d; border:2px solid #bdc3c7;">
                                Pas de photo
                               </div>`
                        }
                        <p class="mt-2 mb-0"><strong>${data.nom || ''} ${data.prenom || ''}</strong></p>
                    </div>

                    <div class="col-md-6">
                         <p><strong>CIN:</strong> ${data.cin || "-"}</p>
                             <p><strong>Pièce jointe CIN:</strong> ${data.cin_piece_jointe
                    ? `<a href="${window.location.origin}/${data.cin_piece_jointe}" target="_blank">Voir / Télécharger</a>`
                    : "-"}</p>
                        <p><strong>Email:</strong> ${data.email || "-"}</p>
                       
                        <p><strong>Téléphone:</strong> ${data.phone || "-"}</p>
                        <p><strong>Date de Naissance:</strong> ${data.date_naissance || "-"}</p>
                        <p><strong>Adresse:</strong> ${data.adresse || "-"}</p>
                         <p><strong>Situation Familiale:</strong> ${data.situation_familiale || "-"}</p>
                        <p><strong>Nombre d'Enfants:</strong> ${data.nombre_enfant || "0"}</p>
                        
                    </div>
                    <div class="col-md-6">
                       
                        <p><strong>Matricule CNSS:</strong> ${data.n_matricule_cnss || "-"}</p>
                        <p><strong>Matricule Entreprise:</strong> ${data.n_matricule_entreprise || "-"}</p>
                        <p><strong>Fonction:</strong> ${data.fonction ? data.fonction.designation : "N/A"}</p>
                        <p><strong>Type de Règlement:</strong> ${data.reglement ? data.reglement.designation : "N/A"}</p>
                        <p><strong>RIB:</strong> ${data.rib || "-"}</p>
                        <p><strong>Type de Travail:</strong> ${data.type_travail || "-"}</p>
                        <p><strong>Salaire de Base:</strong> ${data.salaire_base 
                            ? data.salaire_base.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD' 
                            : "-"}</p>
                            <p><strong>Salaire net:</strong> ${data.salaire_net || "-"}</p>
                             <p><strong>Salaire journalier:</strong> ${data.salaire_journalier || "-"}</p>
                            <p><strong>Contrat:</strong> ${data.type_contrat || "-"}</p>
                        <p><strong>Contrat:</strong> ${data.contrat
                            ? `<a href="${window.location.origin}/${data.contrat}" target="_blank">Voir le contrat</a>`
                            : "-"}</p>
                    </div>
                </div>`;
            $("#salarie-details").html(details);
            $("#showSalarieModal").modal("show");
        },
        error: (xhr) => {
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Erreur lors du chargement des détails",
                timer: 5000,
            });
            errorSound.play();
        },
    });
});

        dt_salarie_table.on("click", ".print-salarie", function (e) {
            e.preventDefault();
            const id = $(this).data("id");
            const url = `${dt_salarie_table.data("salaries-base-url")}/${id}`;
            console.log("Print clicked for ID:", id);
            $.ajax({
                url: url,
                type: "GET",
                success: (data) => {
                    console.log("Print data received:", data);
                    printSingleSalarie(data);
                },
                error: (xhr) => {
                    console.error("Print AJAX Error:", xhr);
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: "Erreur lors du chargement",
                        timer: 5000,
                    });
                    errorSound.play();
                },
            });
        });

     dt_salarie_table.on("click", ".demission-salarie", function (e) {
    e.preventDefault();
    const id = $(this).data("id");
    console.log("Démission clicked for ID:", id);
    $.ajax({
        url: `/salaries/${id}`,
        type: "GET",
        success: function (data) {
            console.log("Données reçues:", data); // Ajout pour débogage
            if (data.statut === "inactif") {
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: "Ce salarié est déjà inactif.",
                    timer: 5000,
                });
                return;
            }
            $("#demissionModal #salarie_id").val(id);
            $("#demission-form")[0].reset();
            $("#demission-form").find(".is-invalid").removeClass("is-invalid");
            $("#demission-form").find(".invalid-feedback").text("");
            preavisData = {
                date_debut_preavis: null,
                date_fin_preavis: null,
                document_preavis: null,
                preavis_id: null,
            };
            console.log("Modal de démission prêt à être affiché");
            $("#demissionModal").modal("show");
        },
        error: function (xhr) {
            console.error("Erreur AJAX:", xhr.responseJSON || xhr);
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: xhr.responseJSON?.message || "Erreur lors de la vérification du salarié.",
                timer: 5000,
            });
            errorSound.play();
        },
    });
});
        // Handle status filter change
        $('#statusFilter').on('change', function () {
            dt_salarie.ajax.reload();
        });

        dt_salarie_table.on("click", ".reactivate-salarie", function (e) {
    e.preventDefault();
    const id = $(this).data("id");
    console.log("Réactivation clicked for ID:", id);
    $('#reactivate_salarie_id').val(id);
    $('#reactivateSalarieModal').modal("show");
    console.log("Modal opened, salarie_id set to:", $('#reactivate_salarie_id').val());
});

        // Handle reactivate form submission

$('#reactivate-salarie-form').on('submit', function (e) {
    e.preventDefault();
    const form = $(this);
    const salarieId = $('#reactivate_salarie_id').val();
    const dateEmbauche = $('#reactivate_date_embauche').val();
    const nMatriculeEntreprise = $('#reactivate_n_matricule_entreprise').val();

    console.log("Form submitted, salarieId:", salarieId, "dateEmbauche:", dateEmbauche, "nMatriculeEntreprise:", nMatriculeEntreprise);

    // Réinitialiser les erreurs
    $('#reactivate_date_embauche').removeClass('is-invalid');
    $('#reactivate_salarie_id_error').text('');
    $('#reactivate_n_matricule_entreprise').addClass('is-invalid');
    $('#reactivate_n_matricule_entreprise-error').text('');

    // Validation
    let isValid = true;
    if (!salarieId) {
        $('#reactivate_salarie_id_error').text('ID du salarié manquant.');
        isValid = false;
    }
    if (!nMatriculeEntreprise) {
        $('#reactivate_n_matricule_entreprise').addClass('is-invalid');
        $('#reactivate_n_matricule_entreprise-error').text('Le matricule entreprise est requis.');
        isValid = false;
    }

    if (!isValid) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Veuillez remplir tous les champs obligatoires.',
            timer: 3000,
        });
        return;
    }

    Swal.fire({
        title: 'Confirmer la réactivation',
        text: 'Un nouveau salarié sera créé avec les informations originales, et l’ancien salarié sera modifié. Voulez-vous continuer ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, créer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/salaries/${salarieId}/reactivate`,
                type: 'POST',
                data: {
                    salarie_id: salarieId,
                    date_embauche: dateEmbauche,
                    n_matricule_entreprise: nMatriculeEntreprise,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    console.log("Succès de la réactivation:", response);
                    $('#reactivateSalarieModal').modal('hide');
                    form[0].reset();
                    dt_salarie.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message,
                        timer: 5000,
                    });
                    successSound.play();
                },
                error: function (xhr) {
                    console.error("Erreur AJAX:", xhr.responseJSON || xhr);
                    let errorMessage = xhr.responseJSON?.message || 'Erreur lors de la réactivation';
                    if (xhr.responseJSON?.errors) {
                        errorMessage += '\n' + Object.values(xhr.responseJSON.errors).flat().join('\n');
                        if (xhr.responseJSON.errors.n_matricule_entreprise) {
                            $('#reactivate_n_matricule_entreprise').addClass('is-invalid');
                            $('#reactivate_n_matricule_entreprise-error').text(xhr.responseJSON.errors.n_matricule_entreprise[0]);
                        }
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: errorMessage,
                        timer: 5000,
                    });
                    errorSound.play();
                },
            });
        }
    });
});




     // Handle demission form submission
$("#demission-form").on("submit", function (e) {
    e.preventDefault();
    const form = $(this);
    form.find('button[type="submit"]').prop("disabled", true);
    console.log("Soumission du formulaire de démission");

    const formData = new FormData();
    formData.append("salarie_id", $("#salarie_id").val());
    formData.append("date_demission", $("#date_demission").val());
    formData.append("motif", $("#motif").val());
    if ($("#document")[0].files[0]) formData.append("document", $("#document")[0].files[0]);
    if (preavisData.preavis_id) formData.append("preavis_id", preavisData.preavis_id);
    if (preavisData.document_path) formData.append("preavis_document_path", preavisData.document_path); // Ajout du chemin du document de préavis
    formData.append("_token", $('meta[name="csrf-token"]').attr("content"));

    for (let pair of formData.entries()) {
        console.log("FormData:", pair[0], pair[1] instanceof File ? pair[1].name : pair[1]);
    }

    $.ajax({
        url: "/salaries/demission",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            console.log("Succès de la démission:", response);
            preavisData = {
                date_debut_preavis: null,
                date_fin_preavis: null,
                document_preavis: null,
                preavis_id: null,
                document_path: null, // Réinitialiser le chemin du document
            };
            $("#demissionModal").modal("hide");
            form[0].reset();
            dt_salarie.ajax.reload();
            Swal.fire({
                icon: "success",
                title: "Succès",
                text: response.message,
                timer: 5000,
            });
            successSound.play();
        },
        error: function (xhr) {
            console.error("Erreur AJAX:", xhr.responseJSON || xhr);
            let errorMessage = "Erreur :\n";
            if (xhr.responseJSON?.errors) {
                errorMessage += Object.values(xhr.responseJSON.errors).flat().join("\n");
                Object.keys(xhr.responseJSON.errors).forEach((field) => {
                    $(`#${field}`).addClass("is-invalid").siblings(".invalid-feedback").text(xhr.responseJSON.errors[field][0]);
                });
            } else {
                errorMessage += xhr.responseJSON?.message || "Erreur inconnue";
            }
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: errorMessage,
                timer: 5000,
            });
            errorSound.play();
        },
        complete: function () {
            form.find('button[type="submit"]').prop("disabled", false);
        },
    });
});

// Handle preavis save
$("#save-preavis").on("click", function () {
    const dateDebut = $("#date_debut_preavis").val();
    const dateFin = $("#date_fin_preavis").val();
    const document = $("#document_preavis")[0].files[0];
    const salarieId = $("#demissionModal #salarie_id").val();

    let isValid = true;
    $("#date_debut_preavis, #date_fin_preavis, #document_preavis")
        .removeClass("is-invalid")
        .siblings(".invalid-feedback")
        .text("");

    if (dateDebut && dateFin && new Date(dateFin) < new Date(dateDebut)) {
        isValid = false;
        $("#date_fin_preavis")
            .addClass("is-invalid")
            .siblings(".invalid-feedback")
            .text("La date de fin doit être postérieure ou égale à la date de début.");
    }

    if (document) {
        const maxSize = 5 * 1024 * 1024;
        if (document.size > maxSize) {
            isValid = false;
            $("#document_preavis")
                .addClass("is-invalid")
                .siblings(".invalid-feedback")
                .text("Le fichier ne doit pas dépasser 5 Mo.");
        }
        if (!document.type.includes("pdf")) {
            isValid = false;
            $("#document_preavis")
                .addClass("is-invalid")
                .siblings(".invalid-feedback")
                .text("Le fichier doit être un PDF.");
        }
    }

    if (isValid) {
        const formData = new FormData();
        formData.append("salarie_id", salarieId);
        if (dateDebut) formData.append("date_debut_preavis", dateDebut);
        if (dateFin) formData.append("date_fin_preavis", dateFin);
        if (document) formData.append("document_preavis", document);
        formData.append("_token", $('meta[name="csrf-token"]').attr("content"));

        for (let pair of formData.entries()) {
            console.log("FormData préavis envoyé :", pair[0], pair[1]);
        }

        $.ajax({
            url: "/salaries/preavis",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                console.log("Réponse préavis:", response);
                preavisData.preavis_id = response.preavis_id;
                preavisData.document_path = response.document_path; // Stocker le chemin du document
                if (dateFin) $("#date_demission").val(dateFin).change();
                $("#preavisModal").modal("hide");
                setTimeout(() => $("#demissionModal").modal("show"), 300);
                Swal.fire({
                    icon: "success",
                    title: "Succès",
                    text: response.message,
                    timer: 5000,
                });
                successSound.play();
            },
            error: function (xhr) {
                console.error("Erreur AJAX préavis:", xhr);
                let errorMessage = "Erreur :\n";
                if (xhr.responseJSON?.errors) {
                    errorMessage += Object.values(xhr.responseJSON.errors)
                        .flat()
                        .join("\n");
                    Object.keys(xhr.responseJSON.errors).forEach((field) => {
                        $(`#${field}`)
                            .addClass("is-invalid")
                            .siblings(".invalid-feedback")
                            .text(xhr.responseJSON.errors[field][0]);
                    });
                } else {
                    errorMessage += xhr.responseJSON?.message || "Erreur inconnue";
                }
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: errorMessage,
                    timer: 5000,
                });
                errorSound.play();
            },
        });
    } else {
        console.log("Validation préavis échouée");
    }
});

        $("#addSalarieModal").on("shown.bs.modal", () => {
            console.log("Add modal shown, initializing stepper");
            initializeStepper("#addSalarieModal", "#create-salarie-form");
            initializeSalaryCalculations("#addSalarieModal");
        });

        $("#editSalarieModal").on("shown.bs.modal", () => {
            console.log("Edit modal shown, initializing stepper");
            initializeStepper("#editSalarieModal", "#update-salarie-form", true);
            initializeSalaryCalculations("#editSalarieModal");
        });
    }

    // Initialisation de la table des salariés démissionnés (si nécessaire)
  


    // Open modal and populate fields for edit-demission
$(document).on('click', '.edit-demission', function (e) {
    e.preventDefault();
    console.log('Edit demission clicked');
    const $this = $(this);
    const demissionId = $this.data('demission-id');
    const dateDemission = $this.data('date-demission');
    const motif = $this.data('motif');
    const preavisPath = $this.data('preavis-path');
    const preavisLink = $this.data('preavis-link');
    const demissionPath = $this.data('demission-path');
    const demissionLink = $this.data('demission-link');

    // Populate modal
    $('#demission_id').val(demissionId);
    $('#date_demission').val(dateDemission);
    $('#motif').val(motif);
    $('#preavis_file').val('');
    $('#demission_file').val('');

    // Show existing file links
    $('#preavis_file_info').html(
        preavisPath
            ? `Fichier existant: <a href="${preavisLink}" target="_blank">Voir le fichier</a>`
            : 'Aucun fichier préavis.'
    );
    $('#demission_file_info').html(
        demissionPath
            ? `Fichier existant: <a href="${demissionLink}" target="_blank">Voir le fichier</a>`
            : 'Aucun fichier démission.'
    );

    // Show modal
    $('#editDemissionModal').modal('show');
});
    // Submit modal form for edit-demission
  $('#saveDemissionBtn').on('click', function () {
    console.log('Save demission clicked');
    const demissionId = $('#demission_id').val();
    const dateDemission = $('#date_demission').val();
    const motif = $('#motif').val();
    const preavisFile = $('#preavis_file')[0].files[0];
    const demissionFile = $('#demission_file')[0].files[0];

    console.log('Preavis file:', preavisFile ? preavisFile.name : 'No file selected');
    console.log('Demission file:', demissionFile ? demissionFile.name : 'No file selected');

    if (!demissionId || !dateDemission || !motif) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Veuillez remplir tous les champs obligatoires.',
            timer: 5000,
        });
        errorSound.play();
        return;
    }

    const formData = new FormData();
    formData.append('date_demission', dateDemission);
    formData.append('motif', motif);
    if (preavisFile) formData.append('preavis_file', preavisFile);
    if (demissionFile) formData.append('demission_file', demissionFile);
    formData.append('_method', 'PUT');
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

    for (let pair of formData.entries()) {
        console.log('FormData:', pair[0], pair[1] instanceof File ? pair[1].name : pair[1]);
    }

    $.ajax({
        url: '/demissions/' + demissionId,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        success: function (response) {
            console.log('Update success:', response);
            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: response.message || 'Démission mise à jour avec succès',
                timer: 3000,
            });
            $('#editDemissionModal').modal('hide');
            // Recharge la table DataTables pour refléter les changements
            table.ajax.reload(function () {
                // Réinitialiser les tooltips après rechargement
                $('[data-bs-toggle="tooltip"]').tooltip();
            }, false);
            successSound.play();
        },
        error: function (xhr) {
            console.error('Update AJAX Error:', xhr);
            let errorMessage = xhr.responseJSON?.message || 'Erreur lors de la mise à jour';
            if (xhr.responseJSON?.errors) {
                errorMessage += '\n' + Object.values(xhr.responseJSON.errors).flat().join('\n');
                Object.keys(xhr.responseJSON.errors).forEach((field) => {
                    $(`#${field}`).addClass('is-invalid').siblings('.invalid-feedback').text(xhr.responseJSON.errors[field][0]);
                });
            }
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: errorMessage,
                timer: 10000,
            });
            errorSound.play();
        },
    });
});
$('#reactivate_generateMatriculeBtn').on('click', function () {
    console.log('Génération de matricule pour réactivation');
    $.ajax({
        url: '/salaries/generate-matricule',
        type: 'GET',
        success: function (response) {
            console.log('Matricule généré:', response.matricule);
            // Si aucun matricule n'est retourné, laisser le champ vide
            $('#reactivate_n_matricule_entreprise').val(response.matricule || '');
            $('#reactivate_n_matricule_entreprise-error').text('');
        },
        error: function (xhr) {
            console.error('Erreur AJAX pour génération de matricule:', xhr);
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Erreur lors de la génération du matricule.',
                timer: 5000,
            });
            errorSound.play();
        },
    });
});
    $('#reactivate_n_matricule_entreprise').on('blur', function () {
        const value = $(this).val();
        const currentId = $('#reactivate_salarie_id').val();
        if (value && currentId) {
            $.ajax({
                url: '/salaries/check-unique',
                type: 'POST',
                data: {
                    field: 'n_matricule_entreprise',
                    value: value,
                    currentId: currentId,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    if (response.exists) {
                        $('#reactivate_n_matricule_entreprise').addClass('is-invalid');
                        $('#reactivate_n_matricule_entreprise-error').text(response.message);
                    } else {
                        $('#reactivate_n_matricule_entreprise').removeClass('is-invalid');
                        $('#reactivate_n_matricule_entreprise-error').text('');
                    }
                },
                error: function (xhr) {
                    console.error('Erreur AJAX pour vérification unicité:', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Erreur lors de la vérification du matricule.',
                        timer: 5000,
                    });
                    errorSound.play();
                },
            });
        }
    });
function setupSalaireExclusivity(modal, form) {
    const netInput = form.querySelector('[name="salaire_net"]');
    const baseInput = form.querySelector('[name="salaire_base"]');
    const journalierInput = form.querySelector('[name="salaire_journalier"]');
    if (!netInput || !baseInput || !journalierInput) return;

    function toggleFields() {
        const netFilled = netInput.value.trim() !== '';
        const baseOrJournalierFilled =
            baseInput.value.trim() !== '' || journalierInput.value.trim() !== '';

        if (netFilled && !baseOrJournalierFilled) {
            baseInput.disabled = true;        // ← C'EST ÇA qui grise vos champs
            journalierInput.disabled = true;  // ← C'EST ÇA qui grise vos champs
            netInput.disabled = false;
        } else if (baseOrJournalierFilled && !netFilled) {
            netInput.disabled = true;
            baseInput.disabled = false;
            journalierInput.disabled = false;
        } else {
            netInput.disabled = false;
            baseInput.disabled = false;
            journalierInput.disabled = false;
        }

        [netInput, baseInput, journalierInput].forEach((input) => {
            if (input.disabled) {
                input.classList.remove('is-invalid');
                const errorEl = modal.querySelector(`#${input.name}-error`);
                if (errorEl) errorEl.textContent = '';
            }
        });
    }

    [netInput, baseInput, journalierInput].forEach((input) => {
        input.addEventListener('input', toggleFields);
    });

    toggleFields();
}

function initializeStepper(modalId, formId, isEdit = false) {
    const modal = document.querySelector(modalId);
    const form = modal.querySelector(formId);

    setupSalaireExclusivity(modal, form);

    const stepperEl = modal.querySelector(".bs-stepper");
    if (!stepperEl || !form || !modal) {
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Configuration du wizard incorrecte",
            timer: 5000,
        });
        errorSound.play();
        return;
    }

    const stepper = new window.Stepper(stepperEl, {
        linear: true,
        animation: true,
    });
    const steps = modal.querySelectorAll(".bs-stepper-content .content");
    const stepHeaders = modal.querySelectorAll(".bs-stepper-header .step");
    const nextButtons = modal.querySelectorAll(".btn-next");
    const prevButtons = modal.querySelectorAll(".btn-prev");
    let currentStep = 0;
    let isSubmitting = false; // Variable pour bloquer les soumissions multiples
    let isNextButtonSubmitting = false; // Nouvelle variable pour bloquer les clics multiples sur "Suivant"

    // Mettre à jour l'en-tête du stepper
    const updateHeader = (index) => {
        stepHeaders.forEach((header, i) => {
            header.classList.toggle("active", i === index);
            const trigger = header.querySelector(".step-trigger");
            if (trigger) trigger.setAttribute("aria-selected", i === index);
        });
    };

    // Afficher l'étape actuelle
    const showStep = (index) => {
        steps.forEach((step, i) => {
            step.classList.toggle("active", i === index);
            step.style.display = i === index ? "block" : "none";
        });
        prevButtons.forEach((btn) => (btn.disabled = index === 0));
        nextButtons.forEach((btn) => {
            btn.disabled = index === steps.length - 1 && !isSubmitting;
            btn.textContent = index === steps.length - 1 ? "Confirmer" : "Suivant";
        });
        updateHeader(index);
    };

    // Valider l'étape actuelle
    const validateStep = async (stepIndex) => {
        console.log("Validating step:", stepIndex);
        const requiredFields = {
            0: ["nom", "prenom", "cin", "date_naissance", "situation_familiale"],
            1: [
                "n_matricule_cnss",
                "fonction_id",
                "n_matricule_entreprise",
                "type_travail",
                "type_contrat",
               
                "date_embauche",
            ],
        };
        let isValid = true;

        // Validation des champs requis
        requiredFields[stepIndex]?.forEach((field) => {
            const input = form.querySelector(`[name="${field}"]`);
            const errorElement = modal.querySelector(`#${field}-error`);
            if (!input?.value.trim()) {
                isValid = false;
                input.classList.add("is-invalid");
                if (errorElement)
                    errorElement.textContent = "Ce champ est obligatoire.";
            } else {
                input.classList.remove("is-invalid");
                if (errorElement) errorElement.textContent = "";
            }
        });

            if (stepIndex === 0) {
                const email = form.querySelector("[name='email']").value.trim();
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    isValid = false;
                    const emailInput = form.querySelector("[name='email']");
                    emailInput.classList.add("is-invalid");
                    modal.querySelector("#email-error").textContent =
                        "Veuillez entrer un email valide.";
                }

                const cin = form.querySelector("[name='cin']").value.trim();
                if (cin) {
                    try {
                        const response = await $.ajax({
                            url: "/salaries/check-unique",
                            method: "POST",
                            data: {
                                field: "cin",
                                value: cin,
                                currentId: isEdit ? form.dataset.id : null,
                                _token: $('meta[name="csrf-token"]').attr(
                                    "content"
                                ),
                            },
                            dataType: "json",
                        });
                        if (response.exists) {
                            isValid = false;
                            form.querySelector("[name='cin']").classList.add(
                                "is-invalid"
                            );
                            modal.querySelector("#cin-error").textContent =
                                response.message;
                        }
                    } catch (error) {
                        isValid = false;
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            text: "Erreur lors de la vérification du CIN",
                            timer: 5000,
                        });
                        errorSound.play();
                        console.error("CIN Check Error:", error); // Débogage
                    }
                }
            }

        // Validation spécifique pour l'étape 1
        if (stepIndex === 1) {
            const cnss = form.querySelector("[name='n_matricule_cnss']")?.value.trim();
            if (cnss) {
                if (!/^\d{9}$/.test(cnss)) {
                    isValid = false;
                    form.querySelector("[name='n_matricule_cnss']").classList.add("is-invalid");
                    modal.querySelector("#n_matricule_cnss-error").textContent =
                        "Le numéro CNSS doit contenir exactement 9 chiffres.";
                } else {
                    try {
                        const response = await $.ajax({
                            url: "/salaries/check-unique",
                            method: "POST",
                            data: {
                                field: "n_matricule_cnss",
                                value: cnss,
                                currentId: isEdit ? form.dataset.id : null,
                                _token: $('meta[name="csrf-token"]').attr("content"),
                            },
                            dataType: "json",
                        });
                        if (response.exists) {
                            isValid = false;
                            form.querySelector("[name='n_matricule_cnss']").classList.add(
                                "is-invalid"
                            );
                            modal.querySelector("#n_matricule_cnss-error").textContent =
                                response.message;
                        }
                    } catch (error) {
                        isValid = false;
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            text: "Erreur lors de la vérification du matricule CNSS",
                            timer: 5000,
                        });
                        errorSound.play();
                        console.error("CNSS Check Error:", error);
                    }
                }
            }

            const matricule = form
                .querySelector("[name='n_matricule_entreprise']")
                ?.value.trim();
            if (matricule) {
                try {
                    const response = await $.ajax({
                        url: "/salaries/check-unique",
                        method: "POST",
                        data: {
                            field: "n_matricule_entreprise",
                            value: matricule,
                            currentId: isEdit ? form.dataset.id : null,
                            _token: $('meta[name="csrf-token"]').attr("content"),
                        },
                        dataType: "json",
                    });
                    if (response.exists) {
                        isValid = false;
                        form.querySelector("[name='n_matricule_entreprise']").classList.add(
                            "is-invalid"
                        );
                        modal.querySelector("#n_matricule_entreprise-error").textContent =
                            response.message;
                    }
                } catch (error) {
                    isValid = false;
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: "Erreur lors de la vérification du matricule entreprise",
                        timer: 5000,
                    });
                    errorSound.play();
                    console.error("Matricule Entreprise Check Error:", error);
                }
            }
            const netVal = form.querySelector("[name='salaire_net']")?.value.trim() || "";
            const baseVal = form.querySelector("[name='salaire_base']")?.value.trim() || "";
            const journalierVal = form.querySelector("[name='salaire_journalier']")?.value.trim() || "";

            const netInput = form.querySelector("[name='salaire_net']");
            const baseInput = form.querySelector("[name='salaire_base']");
            const journalierInput = form.querySelector("[name='salaire_journalier']");

            const hasNet = netVal !== "";
            const hasBaseAndJournalier = baseVal !== "" && journalierVal !== "";

            if (!hasNet && !hasBaseAndJournalier) {
                isValid = false;
                netInput.classList.add("is-invalid");
                baseInput.classList.add("is-invalid");
                journalierInput.classList.add("is-invalid");
                modal.querySelector("#salaire_net-error").textContent =
                    "Renseignez le salaire net, ou le salaire de base ET journalier.";
                modal.querySelector("#salaire_base-error").textContent =
                    "Renseignez le salaire de base ET journalier, ou le salaire net.";
                modal.querySelector("#salaire_journalier-error").textContent =
                    "Renseignez le salaire journalier ET de base, ou le salaire net.";
            } else {
                netInput.classList.remove("is-invalid");
                baseInput.classList.remove("is-invalid");
                journalierInput.classList.remove("is-invalid");
                modal.querySelector("#salaire_net-error").textContent = "";
                modal.querySelector("#salaire_base-error").textContent = "";
                modal.querySelector("#salaire_journalier-error").textContent = "";
            }

            if (hasNet && (isNaN(netVal) || parseFloat(netVal) < 0)) {
                isValid = false;
                netInput.classList.add("is-invalid");
                modal.querySelector("#salaire_net-error").textContent =
                    "Le salaire net doit être un nombre positif.";
            }
            if (baseVal !== "" && (isNaN(baseVal) || parseFloat(baseVal) < 0)) {
                isValid = false;
                baseInput.classList.add("is-invalid");
                modal.querySelector("#salaire_base-error").textContent =
                    "Le salaire de base doit être un nombre positif.";
            }
            if (journalierVal !== "" && (isNaN(journalierVal) || parseFloat(journalierVal) < 0)) {
                isValid = false;
                journalierInput.classList.add("is-invalid");
                modal.querySelector("#salaire_journalier-error").textContent =
                    "Le salaire journalier doit être un nombre positif.";
            }

            const reglementSelect = form.querySelector("[name='reglement_id']");
            const ribInput = form.querySelector("[name='rib']");
            if (reglementSelect && ribInput) {
                const selectedOption = reglementSelect.options[reglementSelect.selectedIndex];
                if (
                    selectedOption &&
                    selectedOption.dataset.designation === "Virement" &&
                    !ribInput.value.trim()
                ) {
                    isValid = false;
                    ribInput.classList.add("is-invalid");
                    ribInput.nextElementSibling.textContent =
                        "Le RIB est obligatoire pour le virement.";
                } else {
                    ribInput.classList.remove("is-invalid");
                    ribInput.nextElementSibling.textContent = "";
                }
            }
        }

        console.log("Step validation result:", isValid);
        return isValid;
    };

    // Mettre à jour le récapitulatif (inchangé de l'ancienne version)
    const updateSummary = () => {
        const fields = [
            "nom",
            "prenom",
            "email",
            "cin",
            "phone",
            "date_naissance",
            "adresse",
            "situation_familiale",
            "nombre_enfant",
            "n_matricule_cnss",
            "n_matricule_entreprise",
            "type_travail",
            "type_contrat",
            "rib",
            "date_embauche",
        ];
        fields.forEach((field) => {
            const element = form.querySelector(`[name="${field}"]`);
            const summaryElement = modal.querySelector(`#summary-section #${field}`);
            if (element && summaryElement) {
                summaryElement.textContent = element.value || "-";
            } else {
                console.warn(`Champ ou élément de récapitulatif manquant pour : ${field}`);
            }
        });

        const salaireNetInput = form.querySelector('[name="salaire_net"]');
const salaireNetSummary = modal.querySelector("#summary-section #salaire_net");
if (salaireNetInput && salaireNetSummary) {
    const value = parseFloat(salaireNetInput.value);
    salaireNetSummary.textContent =
        !isNaN(value) && value >= 0
            ? value.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD"
            : "-";
}

        const salaireBaseInput = form.querySelector('[name="salaire_base"]');
        const salaireBaseSummary = modal.querySelector("#summary-section #salaire_base");
        if (salaireBaseInput && salaireBaseSummary) {
            const value = parseFloat(salaireBaseInput.value);
            salaireBaseSummary.textContent =
                !isNaN(value) && value >= 0
                    ? value.toLocaleString("fr-FR", {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                      }) + " MAD"
                    : "-";
        }

        const salaireJournalierInput = form.querySelector('[name="salaire_journalier"]');
        const salaireJournalierSummary = modal.querySelector(
            "#summary-section #salaire_journalier"
        );
        if (salaireJournalierInput && salaireJournalierSummary) {
            const value = parseFloat(salaireJournalierInput.value);
            salaireJournalierSummary.textContent =
                !isNaN(value) && value >= 0
                    ? value.toLocaleString("fr-FR", {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                      }) + " MAD"
                    : "-";
        }

        const fonctionSelect = form.querySelector('[name="fonction_id"]');
        const fonctionSummary = modal.querySelector("#summary-section #fonction");
        if (fonctionSelect && fonctionSummary) {
            fonctionSummary.textContent =
                fonctionSelect.options[fonctionSelect.selectedIndex]?.text || "-";
        }

        const reglementSelect = form.querySelector('[name="reglement_id"]');
        const reglementSummary = modal.querySelector("#summary-section #reglement_id");
        if (reglementSelect && reglementSummary) {
            reglementSummary.textContent =
                reglementSelect.options[reglementSelect.selectedIndex]?.text || "-";
        }

        const contratInput = form.querySelector('[name="contrat"]');
        const contratSummary = modal.querySelector("#summary-section #contrat");
        if (contratInput && contratSummary) {
            contratSummary.textContent =
                contratInput.files[0]?.name || (isEdit && form.dataset.contrat ? "Contrat existant" : "-");
        }

        const photoInput = form.querySelector('[name="photo"]');
        const photoSummary = modal.querySelector("#summary-section #photo");
        if (photoInput && photoSummary) {
            if (photoInput.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    photoSummary.innerHTML = `<img src="${e.target.result}" alt="Photo Preview" style="max-width: 100px;" />`;
                };
                reader.readAsDataURL(photoInput.files[0]);
            } else {
                photoSummary.innerHTML = isEdit && form.dataset.photo
                    ? `<img src="${window.location.origin}/${form.dataset.photo}" alt="Photo Actuelle" style="max-width: 100px;" />`
                    : "-";
            }
        }

        console.log("Récapitulatif mis à jour");
    };

    // Initialiser la première étape
    showStep(0);

    // Gestionnaire du bouton "Suivant"
    nextButtons.forEach((btn) => {
        btn.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log("Next button clicked, currentStep:", currentStep, "isNextButtonSubmitting:", isNextButtonSubmitting);
            if (isNextButtonSubmitting) {
                console.log("Clic Suivant bloqué car isNextButtonSubmitting est vrai");
                return;
            }

            btn.disabled = true; // Désactiver immédiatement le bouton pour éviter les doubles clics
            const isValid = await validateStep(currentStep);
            if (isValid) {
                if (currentStep < steps.length - 1) {
                    currentStep++;
                    console.log("Advancing to step:", currentStep);
                    stepper.to(currentStep + 1);
                    showStep(currentStep);
                    if (currentStep === 2) {
                        console.log("Updating summary for validation step");
                        updateSummary();
                    }
                    btn.disabled = false; // Réactiver le bouton pour la prochaine étape
                } else {
                    // Dernière étape : déclencher la soumission
                    console.log("Dernière étape, déclenchement de la soumission");
                    isNextButtonSubmitting = true;
                    form.dispatchEvent(new Event("submit"));
                }
            } else {
                console.log("Validation failed for step:", currentStep);
                btn.disabled = false; // Réactiver si la validation échoue
            }
        });
    });

    // Gestionnaire du bouton "Précédent"
    prevButtons.forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (currentStep > 0) {
                currentStep--;
                console.log("Going back to step:", currentStep);
                stepper.to(currentStep + 1);
                showStep(currentStep);
            }
        });
    });

    // Générer le matricule
    const generateMatriculeBtn = modal.querySelector("#generateMatriculeBtn");
    if (generateMatriculeBtn) {
        generateMatriculeBtn.addEventListener("click", () => {
            fetch("/salaries/generate-matricule")
                .then((response) => response.json())
                .then((data) => {
                    form.querySelector("[name='n_matricule_entreprise']").value = data.matricule;
                })
                .catch(() => {
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: "Erreur lors de la génération du matricule",
                        timer: 5000,
                    });
                    errorSound.play();
                });
        });
    }

    // Formatage du numéro de téléphone
    const phoneInput = form.querySelector("[name='phone']");
    if (phoneInput) {
        phoneInput.addEventListener("input", () => {
            phoneInput.value = phoneInput.value.replace(/\D/g, "").slice(0, 10);
        });
    }

    // Gestion de l'affichage du champ RIB
    const reglementSelect = form.querySelector("[name='reglement_id']");
    const ribContainer = form.querySelector("#rib-container");
    const ribInput = form.querySelector("[name='rib']");

    if (reglementSelect && ribContainer && ribInput) {
        const toggleRibField = () => {
            const selectedOption = reglementSelect.options[reglementSelect.selectedIndex];
            const isVirement = selectedOption && selectedOption.dataset.designation === "Virement";

            if (isVirement) {
                ribContainer.style.display = "block";
                ribInput.setAttribute("required", "required");
            } else {
                ribContainer.style.display = "none";
                ribInput.removeAttribute("required");
                ribInput.value = "";
                ribInput.classList.remove("is-invalid");
            }
        };

        toggleRibField();
        reglementSelect.addEventListener("change", toggleRibField);
    }

    // Gestion de la soumission du formulaire
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        console.log("Form submit event triggered, isSubmitting:", isSubmitting);
        if (isSubmitting) {
            console.log("Soumission bloquée car isSubmitting est vrai");
            return;
        }

        isSubmitting = true;
        const submitButton = form.querySelector('button[type="submit"]');
        const spinner = form.querySelector("#submit-spinner");
        if (submitButton) submitButton.disabled = true;
        if (spinner) spinner.classList.remove("d-none");

        form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
        form.querySelectorAll(".invalid-feedback").forEach((el) => (el.textContent = ""));

        const formData = new FormData(form);
        if (isEdit) formData.append("_method", "PUT");

        $.ajax({
            url: form.action,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: (response) => {
                console.log("Requête AJAX réussie:", response);
                $(modal).modal("hide");
                form.reset();
                dt_salarie.ajax.reload(null, false);
                Swal.fire({
                    icon: "success",
                    title: "Succès",
                    text: response.message || `Salarié ${isEdit ? "mis à jour" : "ajouté"} !`,
                    timer: 5000,
                });
                successSound.play();
            },
         
            complete: () => {
                console.log("Requête AJAX terminée, réinitialisation de isSubmitting");
                isSubmitting = false;
                isNextButtonSubmitting = false;
                if (submitButton) submitButton.disabled = false;
                if (spinner) spinner.classList.add("d-none");
            },
        });
    });

    // Réinitialiser lors de la fermeture du modal
    $(modal).on("hidden.bs.modal", () => {
        form.reset();
        currentStep = 0;
        stepper.to(1);
        showStep(0);
        isSubmitting = false;
        isNextButtonSubmitting = false;
        form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
        form.querySelectorAll(".invalid-feedback").forEach((el) => (el.textContent = ""));
    });
}




$("#addSalarieModal").on("shown.bs.modal", () => {
    console.log("Add modal shown, initializing stepper");
    initializeStepper("#addSalarieModal", "#create-salarie-form");
    initializeSalaryCalculations("#addSalarieModal");
});

$("#editSalarieModal").on("shown.bs.modal", () => {
    console.log("Edit modal shown, initializing stepper");
    initializeStepper("#editSalarieModal", "#update-salarie-form", true);
    initializeSalaryCalculations("#editSalarieModal");
});

$(document).ready(function () {
    const successSound = new Audio('/assets/audio/success.mp3');
    const errorSound = new Audio('/assets/audio/error.mp3');

    // Initialize DataTable
    const table = $('#resignedSalariesTable').DataTable({
        ajax: {
            url: '/salaries/resigned',
            dataSrc: '',
            beforeSend: function () {
                $('#table-loading').removeClass('d-none'); // Show loading overlay
            },
            complete: function () {
                $('#table-loading').addClass('d-none'); // Hide loading overlay
            },
            error: function (xhr) {
                console.error('DataTable AJAX Error:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Erreur de chargement des données.',
                    timer: 5000,
                });
                errorSound.play();
            },
        },
     columns: [
    { data: 'salarie.nom' },
    { data: 'salarie.prenom' },
    { data: 'salarie.cin' },
    { data: 'salarie.n_matricule_entreprise' },
    { data: 'date_embauche' },
    { data: 'date_demission' },
    { data: 'motif' },
    {
        data: null,
        render: function (data, type, full) {
            return full.docPreavis
                ? `<a href="${full.preavis_download_link}" class="btn btn-sm btn-primary" target="_blank"><i class="bx bx-download"></i></a>`
                : `<button class="btn btn-sm btn-secondary" disabled><i class="bx bx-download"></i></button>`;
        },
    },
    {
        data: null,
        render: function (data, type, full) {
            return full.document_path
                ? `<a href="${full.download_link}" class="btn btn-sm btn-primary" target="_blank"><i class="bx bx-download"></i></a>`
                : `<button class="btn btn-sm btn-secondary" disabled><i class="bx bx-download"></i></button>`;
        },
    },
    {
        data: null,
        render: function (data, type, full) {
            return `
                <a href="#" class="edit-demission" 
                   data-demission-id="${full.id}" 
                   data-date-demission="${full.date_demission}" 
                   data-motif="${full.motif}" 
                   data-preavis-path="${full.docPreavis || ''}" 
                   data-preavis-link="${full.preavis_download_link || ''}" 
                   data-demission-path="${full.document_path || ''}" 
                   data-demission-link="${full.download_link || ''}">
                   <i class="bx bx-edit"></i>
                </a>
                <a href="#" class="delete-demission mx-2" data-demission-id="${full.id}">
                    <i class="bx bx-trash"></i>
                </a>`;
        },
    },
],
        language: {
            emptyTable: 'Aucun salarié trouvé.',
            search: 'Rechercher',
            paginate: { next: 'Suivant', previous: 'Précédent' },
            info: 'Affichage de _START_ à _END_ sur _TOTAL_ éléments',
        },
        drawCallback: function () {
            $('#no-data-message').toggle(this.api().data().length === 0);
        },
    });

    // Handle Edit Demission Click
    $('#resignedSalariesTable').on('click', '.edit-demission', function (e) {
        e.preventDefault();
        const data = $(this).data();

        // Populate modal fields
        $('#demission_id').val(data.demissionId);
        $('#date_demission').val(data.dateDemission);
        $('#motif').val(data.motif);
        $('#preavis_file_info').text(data.preavisPath ? 'Fichier actuel: ' + data.preavisPath.split('/').pop() : 'Aucun fichier');
        $('#demission_file_info').text(data.demissionPath ? 'Fichier actuel: ' + data.demissionPath.split('/').pop() : 'Aucun fichier');

        // Show modal
        $('#editDemissionModal').modal('show');
    });

    // Handle Save Demission Button
    $('#saveDemissionBtn').on('click', function () {
        const $btn = $(this);
        const form = $('#editDemissionForm')[0];
        const formData = new FormData(form);

        // Validate form
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            errorSound.play();
            return;
        }

        // Show loading state on button and table
        $btn.prop('disabled', true).html($btn.data('loading-text'));
        $('#table-loading').removeClass('d-none');

        $.ajax({
            url: '/salaries/demission/update/' + $('#demission_id').val(),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            success: function (response) {
                if (response.success) {
                    $('#editDemissionModal').modal('hide');
                    form.reset();
                    form.classList.remove('was-validated');
                    $('#table-loading').addClass('d-none');
                    $btn.prop('disabled', false).html('Enregistrer');

                    // Reload DataTable
                    table.ajax.reload(null, false); // Preserve pagination

                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message,
                        timer: 5000,
                    });
                    successSound.play();
                } else {
                    $('#table-loading').addClass('d-none');
                    $btn.prop('disabled', false).html('Enregistrer');
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: response.message || 'Erreur lors de la modification de la démission.',
                        timer: 5000,
                    });
                    errorSound.play();
                }
            },
            error: function (xhr) {
                $('#table-loading').addClass('d-none');
                $btn.prop('disabled', false).html('Enregistrer');
                let errorMsg = 'Erreur lors de la modification de la démission.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg += ' Détails: ' + Object.values(xhr.responseJSON.errors).join(', ');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: errorMsg,
                    timer: 5000,
                });
                errorSound.play();
            },
        });
    });
$('#resignedSalariesTable').on('click', '.delete-demission', function (e) {
    e.preventDefault();
    const demissionId = $(this).data('demission-id');

    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: 'Voulez-vous supprimer cette démission ? Le statut du salarié reviendra à actif.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/demissions/${demissionId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    if (response.success) {
                        table.ajax.reload(null, false); // Reload table without resetting pagination
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: response.message,
                            timer: 5000,
                        });
                        successSound.play();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: response.message || 'Erreur lors de la suppression de la démission.',
                            timer: 5000,
                        });
                        errorSound.play();
                    }
                },
                error: function (xhr) {
                    let errorMsg = 'Erreur lors de la suppression de la démission.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: errorMsg,
                        timer: 5000,
                    });
                    errorSound.play();
                },
            });
        }
    });
});

});


});

