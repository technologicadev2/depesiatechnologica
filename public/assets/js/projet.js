const successSound = new Audio("/assets/audio/success.mp3");
const errorSound = new Audio("/assets/audio/error.mp3");

function printProjetTable(table, title, columns) {
    const data = table.rows({ search: "applied" }).data().toArray();

    const logoUrl =
        companySettings && companySettings.logo
            ? `${window.location.origin}/storage/${companySettings.logo}`
            : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

    // Split data into chunks of 13 rows
    const rowsPerPage = 20;
    const pageData = [];
    for (let i = 0; i < data.length; i += rowsPerPage) {
        pageData.push(data.slice(i, i + rowsPerPage));
    }

    // Generate table content for each page
    const tableContentPages = pageData
        .map((pageRows) => {
            const tableContent = pageRows
                .map(
                    (row) => `
                <tr>
                    ${columns.cols
                        .map((col) => `<td>${row[col] || "-"}</td>`)
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
        })
        .join("");

    const html = `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>${title}</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 10mm; 
                    color: #333; 
                    -webkit-print-color-adjust: exact !important; 
                    print-color-adjust: exact !important; 
                }
                .header { 
                    display: flex; 
                    align-items: center; 
                    border-bottom: 2px solid #000; 
                    margin-bottom: 20px; 
                    padding-bottom: 10px; 
                    page-break-after: avoid;
                }
                .header img { 
                    max-width: 100px; 
                    margin-right: 20px; 
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
                .title-section { 
                    margin-bottom: 20px; 
                    text-align: center; 
                    page-break-after: avoid;
                }
                .title-section h1 { 
                    font-size: 24px; 
                    margin: 0; 
                    color: #0056b3; 
                    font-weight: bold; 
                }
                .title-section p { 
                    font-size: 14px; 
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
                    font-size: 10px; 
                }
                th { 
                    background-color: #99ccff; 
                    color: #000; 
                    font-weight: bold; 
                    padding: 6px; 
                    text-align: center; 
                    border: 1px solid #ddd; 
                }
                td { 
                    padding: 6px; 
                    border: 1px solid #ddd; 
                    text-align: center; 
                }
                tr:nth-child(even) { 
                    background-color: #f9f9f9; 
                }
                tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }
                @page { 
                    size: landscape; 
                    margin: 10mm; 
                }
                @media print {
                    body { 
                        margin: 0; 
                    }
                    table { 
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
                    th, td { 
                        font-size: 9px; 
                        padding: 5px; 
                    }
                    .company-info {
                        position: fixed;
                        bottom: 0;
                        width: 100%;
                        font-size: 12px;
                        line-height: 1.5;
                        text-align: center;
                        page-break-outside: avoid;
                        break-outside: avoid;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="${logoUrl}" alt="Logo" />
            </div>
            <div class="title-section">
                <h1>${title}</h1>
                <p>Date: ${new Date().toLocaleDateString()}</p>
            </div>
            ${tableContentPages}
            <div class="company-info">
                <p>Siège social: ${
                    companySettings.address || "Non spécifié"
                } | Capital: ${
        companySettings.capital
            ? companySettings.capital.toLocaleString("fr-FR", {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
              }) + " MAD"
            : "Non spécifié"
    } | Tél: ${companySettings.phone_number || "Non spécifié"}</p>
                <p>R.C.: ${
                    companySettings.commercial_register || "Non spécifié"
                } | CNSS: ${
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
}

let isPrinting = false;

function printContent(html, title) {
    if (isPrinting) {
        console.warn("Print already in progress, ignoring request.");
        return;
    }
    isPrinting = true;

    console.log("Starting print content for:", title);

    // Remove any existing iframe
    const existingIframe = $("iframe.print-iframe");
    if (existingIframe.length) {
        existingIframe.remove();
        console.log("Removed existing iframe.");
    }

    // Create new iframe
    const iframe = $("<iframe>", {
        class: "print-iframe",
        style: "display: none;"
    }).appendTo("body")[0];

    const doc = iframe.contentDocument || iframe.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();

    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        // Clean up iframe after printing or canceling
        iframe.contentWindow.onafterprint = () => {
            console.log("Print dialog closed, removing iframe.");
            $(iframe).remove();
            isPrinting = false;
        };
        // Fallback cleanup in case onafterprint doesn't fire
        setTimeout(() => {
            if ($(iframe).length) {
                console.log("Fallback: Removing iframe after timeout.");
                $(iframe).remove();
                isPrinting = false;
            }
        }, 3000); // 3-second fallback
    }, 500);
}
async function printProjetById(projetId) {
    try {
        if (!projetId) {
            throw new Error("ID du projet non fourni.");
        }
        const response = await $.ajax({
            url: `${$("#projetsTable").data("projets-base-url")}/${projetId}`,
            type: "GET",
        });
        console.log("Réponse AJAX pour projet ID", projetId, ":", response);
        if (!response.success || !response.data) {
            throw new Error(
                response.message || "Échec du chargement des données du projet."
            );
        }
        printSingleProjet(response.data);
    } catch (error) {
        console.error("Erreur lors de la récupération des données:", error);
        errorSound.play();
        Swal.fire(
            "Erreur!",
            "Impossible de charger les données du projet: " + error.message,
            "error"
        );
    }
}
function printSingleProjet(data) {
    // Log pour déboguer les données reçues
    console.log("Données reçues dans printSingleProjet:", data);
    console.log("Valeur de commande_type:", data.commande_type);

    const logoUrl =
        companySettings && companySettings.logo
            ? `${window.location.origin}/storage/${companySettings.logo}`
            : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

    // Determine project type
    const projectType = data.type_projet || "Public";

    // Determine title based on project type and commande_type
    let title;
    if (projectType === "Privé") {
        title = "Situation des Projets";
    } else {
        title =
            data.commande_type === "BC"
                ? "Situation de Bon de Commande"
                : "Situation des Marchés";
    }
    console.log("Titre généré:", title);

    // Vérifier si les données sont vides ou non définies
    if (!data || Object.keys(data).length === 0) {
        alert("Aucune donnée fournie ou non disponible.");
        const emptyHtml = `
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 20mm; 
                        text-align: center; 
                        -webkit-print-color-adjust: exact !important; 
                        print-color-adjust: exact !important; 
                    }
                    .header { 
                        display: flex; 
                        align-items: center; 
                        border-bottom: 2px solid #000; 
                        margin-bottom: 20px; 
                    }
                    .header img { 
                        max-width: 100px; 
                        margin-right: 20px; 
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
                    .title-section { 
                        margin-bottom: 20px; 
                        text-align: center; 
                    }
                    .title-section h1 { 
                        font-size: 24px; 
                        margin: 0; 
                        color: #0056b3; 
                        font-weight: bold; 
                    }
                    .empty-state { 
                        margin-top: 50px; 
                    }
                    @media print {
                        .company-info {
                            position: fixed;
                            bottom: 0;
                            width: 100%;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <img src="${logoUrl}" alt="Logo" />
                </div>
                <div class="title-section">
                    <h1>${title}</h1>
                    <p>Date: ${new Date().toLocaleDateString("fr-FR")}</p>
                </div>
                <div class="empty-state">
                    <h2>Aucune donnée disponible</h2>
                    <p>Veuillez sélectionner un projet valide</p>
                </div>
                <div class="company-info">
                    <p>Siège social: ${
                        companySettings.address || "Non spécifié"
                    }  Capital: ${
            companySettings.capital
                ? companySettings.capital.toLocaleString("fr-FR", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                  }) + " MAD"
                : "Non spécifié"
        } | Tél: ${companySettings.phone_number || "Non spécifié"} </p>
                    <p>R.C.: ${
                        companySettings.commercial_register || "Non spécifié"
                    } | CNSS: ${
            companySettings.cnss_number || "Non spécifié"
        } | IF: ${companySettings.tax_id || "Non spécifié"} TP: ${
            companySettings.tax_id || "Non spécifique"
        } ICE: ${companySettings.patent_number || "Non spécifique"}</p>
                    <p>C.B.: ${
                        companySettings.account_number || "Non spécifié"
                    }, ${companySettings.bank_name || "Non spécifié"} Email: ${
            companySettings.email || "Non spécifié"
        } </p>
                </div>
            </body>
            </html>`;
        printContent(emptyHtml, title);
        return;
    }

    const logoPath =
        projectType === "Privé"
            ? `${window.location.origin}/assets/private/img/anassi_private.jpg`
            : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

    if (projectType === "Privé") {
        // Enhanced horizontal table for private projects
        const mainContent = `
            <div class="project-info">
                <p><strong>Intitulé du projet:</strong> ${
                    data.intitule || "-"
                }</p>
                <p><strong>Description:</strong> ${data.description || "-"}</p>
            </div>
            <h3>Détails du Projet</h3>
            <table>
                <thead>
                    <tr style="background-color: #99ccff;">
                        <th>Intitulé</th>
                        <th>Ordre de Service</th>
                        <th>Date Fin</th>
                        <th>Description</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>${data.intitule || "-"}</td>
                        <td>${data.date_debut || "-"}</td>
                        <td>${data.date_fin || "-"}</td>
                        <td>${data.description || "-"}</td>
                        <td>${
                            data.budget
                                ? data.budget.toLocaleString("fr-FR")
                                : "-"
                        }</td>
                    </tr>
                    <tr style="background-color: #99ccff;">
                        <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL MONTANT</td>
                        <td style="font-weight: bold;">${
                            data.budget
                                ? data.budget.toLocaleString("fr-FR")
                                : "-"
                        }</td>
                    </tr>
                </tbody>
            </table>`;

        const html = `
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <title>${title}</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 10mm; 
                        color: #333; 
                        -webkit-print-color-adjust: exact !important; 
                        print-color-adjust: exact !important; 
                    }
                    .header { 
                        display: flex; 
                        align-items: center; 
                        border-bottom: 2px solid #000; 
                        margin-bottom: 20px; 
                        padding-bottom: 10px; 
                    }
                    .header img { 
                        max-width: 100px; 
                        margin-right: 20px; 
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
                    .title-section { 
                        margin-bottom: 20px; 
                        text-align: center; 
                    }
                    .title-section h1 { 
                        font-size: 24px; 
                        margin: 0; 
                        color: #0056b3; 
                        font-weight: bold; 
                    }
                    .title-section p { 
                        font-size: 14px; 
                        margin: 5px 0; 
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-bottom: 20px; 
                        page-break-inside: auto; 
                        font-size: 12px; 
                    }
                    th { 
                        background-color: #99ccff; 
                        color: #000; 
                        font-weight: bold; 
                        padding: 8px; 
                        text-align: center; 
                        border: 1px solid #ddd; 
                    }
                    td { 
                        padding: 8px; 
                        border: 1px solid #ddd; 
                        text-align: center; 
                    }
                    tr:nth-child(even) { 
                        background-color: #f9f9f9; 
                    }
                    h3 { 
                        font-size: 16px; 
                        color: #0056b3; 
                        margin-top: 20px; 
                        margin-bottom: 10px; 
                        text-align: center; 
                        font-weight: bold; 
                    }
                    p { 
                        font-size: 12px; 
                    }
                    .project-info { 
                        margin-bottom: 20px; 
                    }
                    .project-info p { 
                        margin: 5px 0; 
                        font-size: 14px; 
                    }
                    @page { 
                        size: landscape; 
                        margin: 10mm; 
                    }
                    @media print {
                        table { 
                            page-break-inside: auto; 
                        }
                        tr { 
                            page-break-inside: avoid; 
                            page-break-after: auto; 
                        }
                        th, td { 
                            font-size: 11px; 
                            padding: 6px; 
                        }
                        .company-info {
                            position: fixed;
                            bottom: 0;
                            width: 100%;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <img src="${logoUrl}" alt="Logo" />
                </div>
                <div class="title-section">
                    <h1>${title} ${new Date().getFullYear()}</h1>
                    <p>Date: ${new Date().toLocaleDateString("fr-FR")}</p>
                </div>
                ${mainContent}
               <div class="company-info">
                    <p>Siège social: ${
                        companySettings.address || "Non spécifié"
                    }  Capital: ${
            companySettings.capital
                ? companySettings.capital.toLocaleString("fr-FR", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                  }) + " MAD"
                : "Non spécifié"
        } | Tél: ${companySettings.phone_number || "Non spécifié"} </p>
                    <p>R.C.: ${
                        companySettings.commercial_register || "Non spécifié"
                    } | CNSS: ${
            companySettings.cnss_number || "Non spécifié"
        } | IF: ${companySettings.tax_id || "Non spécifié"} TP: ${
            companySettings.tax_id || "Non spécifique"
        } ICE: ${companySettings.patent_number || "Non spécifique"}</p>
                    <p>C.B.: ${
                        companySettings.account_number || "Non spécifié"
                    }, ${companySettings.bank_name || "Non spécifié"} Email: ${
            companySettings.email || "Non spécifié"
        } </p>
                </div>
            </body>
            </html>`;

        console.log("HTML généré pour l'impression (Privé):", html);
        printContent(html, title);
        return;
    }

    // Fonction pour calculer le pourcentage des travaux exécutés
    const calculateWorkProgress = (travaux_executier, budget) => {
        if (!travaux_executier || !budget) return "-";
        const travaux = parseFloat(String(travaux_executier).replace(/,/g, ""));
        const budgetValue = parseFloat(String(budget).replace(/,/g, ""));
        if (isNaN(travaux) || isNaN(budgetValue) || budgetValue === 0)
            return "-";
        return ((travaux / budgetValue) * 100).toFixed(2) + "%";
    };

    // Fonction pour calculer la caution définitive
    const calculateCautionDefinitive = (budget, commandeType) => {
        if (!budget || commandeType !== "Marche") return "0.00";
        const budgetValue = parseFloat(String(budget).replace(/,/g, ""));
        if (isNaN(budgetValue)) return "0.00";
        return (budgetValue * 0.03).toFixed(2);
    };

    // Fonction pour calculer le total des jours d'arrêt
    const getTotalJoursArret = (ordres) => {
        if (!ordres || ordres.length === 0) return "-";
        const total = ordres
            .filter((ordre) => ordre.type === "arret" && ordre.nbrjour)
            .reduce((sum, ordre) => sum + parseInt(ordre.nbrjour), 0);
        return total || "-";
    };

    // Récupérer les ordres de service
    const ordreInitial =
        data.ordres_service && data.ordres_service.length > 0
            ? data.ordres_service.find((ordre) => ordre.type === "initial")
            : null;
    const ordreInitialDate = ordreInitial ? ordreInitial.date_ordre : "-";

    // Extraire les paires arret/reprise
    const ordresArret = data.ordres_service
        ? data.ordres_service.filter((ordre) => ordre.type === "arret")
        : [];
    const ordresReprise = data.ordres_service
        ? data.ordres_service.filter((ordre) => ordre.type === "reprise")
        : [];

    // Créer le tableau principal
    const mainTable = `
    <table>
        <thead>
            <tr style="background-color: #99ccff;">
                <th>N°</th>
                <th>DATE D'OFFRE</th>
                <th>DATE MARCHE</th>
                <th>VILLE</th>
                <th>Maître d'Ouvrage</th>
                <th>REFERENCE MARCHE</th>
                <th>MONTANT DE MARCHE</th>
                <th>RG<br>7%</th> 
                <th>ORDRE DE<br>SERVICE</th>
                <th>ORDRE<br>D'ARRET</th>
                <th>ORDRE DE<br>REPRISE</th>
                <th>JOURS<br>D'ARRÊT</th>
                <th>DELAI D'EXECUTION EN MOIS</th>
                <th>RECEPTION DEFINITIVE</th>
                <th>ASSURANCE</th>
                <th>TOTAL REVISION DES PRIX</th>
                <th>CAUTION DEFINITIVE<br>3%</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            ${
                ordresArret.length > 0
                    ? ordresArret
                          .map((arret, index) => {
                              const reprise =
                                  index < ordresReprise.length
                                      ? ordresReprise[index]
                                      : null;
                              const isFirstRow = index === 0;

                              return `<tr>
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${data.num_p || "-"}</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${data.date_offre || "-"}</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${data.date_marche || "-"}</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${data.ville || "-"}</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${
                                                data.maitre_ouvrage || "-"
                                            }</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${data.intitule || "-"}</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${data.budget || "-"}</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${data.rg || "-"}</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${data.date_debut || "-"}</td>`
                                          : ""
                                  }
                                  <td>${arret.date_ordre || "-"}</td>
                                  <td>${
                                      reprise ? reprise.date_ordre || "-" : "-"
                                  }</td>
                                  <td>${arret.nbrjour || "-"}</td>
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${
                                                data.delai_execution || "-"
                                            }</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${
                                                data.reception_definitive || "-"
                                            }</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${
                                                data.assurance !== null &&
                                                data.assurance !== undefined
                                                    ? data.assurance
                                                    : "-"
                                            }</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${
                                                data.total_revision || "-"
                                            }</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${
                                                data.caution_definitif ||
                                                calculateCautionDefinitive(
                                                    data.budget
                                                )
                                            }</td>`
                                          : ""
                                  }
                                  ${
                                      isFirstRow
                                          ? `<td rowspan="${
                                                ordresArret.length
                                            }">${calculateWorkProgress(
                                                data.travaux_executier,
                                                data.budget
                                            )}</td>`
                                          : ""
                                  }
                              </tr>`;
                          })
                          .join("")
                    : `<tr>
                          <td>${data.num_p || "-"}</td>
                          <td>${data.date_offre || "-"}</td>
                          <td>${data.date_marche || "-"}</td>
                          <td>${data.ville || "-"}</td>
                          <td>${data.maitre_ouvrage || "-"}</td>
                          <td>${data.intitule || "-"}</td>
                          <td>${data.budget || "-"}</td>
                          <td>${data.rg || "-"}</td>
                          <td>${data.date_debut || "-"}</td>
                          <td>-</td>
                          <td>-</td>
                          <td>-</td>
                          <td>${data.delai_execution || "-"}</td>
                          <td>${data.reception_definitive || "-"}</td>
                          <td>${
                              data.assurance !== null &&
                              data.assurance !== undefined
                                  ? data.assurance
                                  : "-"
                          }</td>
                          <td>${data.total_revision || "-"}</td>
                          <td>${
                              data.caution_definitif ||
                              calculateCautionDefinitive(data.budget)
                          }</td>
                          <td>${calculateWorkProgress(
                              data.travaux_executier,
                              data.budget
                          )}</td>
                      </tr>`
            }
            <tr style="background-color: #99ccff;">
                <td colspan="9" style="text-align: right; font-weight: bold;">TOTAL</td>
                <td></td>
                <td></td>
                <td>${getTotalJoursArret(data.ordres_service)}</td>
                <td colspan="3"></td>
                <td>${data.total_revision || "-"}</td>
                <td>${
                    data.caution_definitif ||
                    calculateCautionDefinitive(data.budget)
                }</td>
                <td></td>
            </tr>
        </tbody>
    </table>`;

    // Générer le HTML complet pour public projects
    const html = `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>${title}</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 10mm; 
                    color: #333; 
                    -webkit-print-color-adjust: exact !important; 
                    print-color-adjust: exact !important; 
                }
                .header { 
                    display: flex; 
                    align-items: center; 
                    border-bottom: 2px solid #000; 
                    margin-bottom: 20px; 
                    padding-bottom: 10px; 
                }
                .header img { 
                    max-width: 100px; 
                    margin-right: 20px; 
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
                .title-section { 
                    margin-bottom: 20px; 
                    text-align: center; 
                }
                .title-section h1 { 
                    font-size: 24px; 
                    margin: 0; 
                    color: #0056b3; 
                    font-weight: bold; 
                }
                .title-section p { 
                    font-size: 14px; 
                    margin: 5px 0; 
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 20px; 
                    page-break-inside: auto; 
                    font-size: 10px; 
                }
                th { 
                    background-color: #99ccff; 
                    color: #000; 
                    font-weight: bold; 
                    padding: 6px; 
                    text-align: center; 
                    border: 1px solid #ddd; 
                }
                td { 
                    padding: 6px; 
                    border: 1px solid #ddd; 
                    text-align: center; 
                }
                tr:nth-child(even) { 
                    background-color: #f9f9f9; 
                }
                h3 { 
                    font-size: 16px; 
                    color: #0056b3; 
                    margin-top: 20px; 
                    margin-bottom: 10px; 
                    text-align: center; 
                    font-weight: bold; 
                }
                p { 
                    font-size: 12px; 
                }
                .project-info { 
                    margin-bottom: 20px; 
                }
                .project-info p { 
                    margin: 5px 0; 
                }
                @page { 
                    size: landscape; 
                    margin: 10mm; 
                }
                @media print {
                    table { 
                        page-break-inside: auto; 
                   那么.completion = true; 
                    }
                    tr { 
                        page-break-inside: avoid; 
                        page-break-after: auto; 
                    }
                    th, td { 
                        font-size: 9px; 
                        padding: 5px; 
                    }
                    .company-info {
                        position: fixed;
                        bottom: 0;
                        width: 100%;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="${logoUrl}" alt="Logo" />
            </div>
            <div class="title-section">
                <h1>${title} ${new Date().getFullYear()}</h1>
                <p>Date: ${new Date().toLocaleDateString("fr-FR")}</p>
            </div>
            <div class="project-info">
                <p><strong>Intitulé du projet:</strong> ${
                    data.intitule || "-"
                }</p>
                <p><strong>Description:</strong> ${data.description || "-"}</p>
            </div>
            ${mainTable}
            <div class="company-info">
                <p>Siège social: ${
                    companySettings.address || "Non spécifié"
                }  Capital: ${
        companySettings.capital
            ? companySettings.capital.toLocaleString("fr-FR", {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
              }) + " MAD"
            : "Non spécifié"
    } | Tél: ${companySettings.phone_number || "Non spécifié"} </p>
                <p>R.C.: ${
                    companySettings.commercial_register || "Non spécifié"
                } | CNSS: ${
        companySettings.cnss_number || "Non spécifié"
    } | IF: ${companySettings.tax_id || "Non spécifié"} TP: ${
        companySettings.tax_id || "Non spécifique"
    } ICE: ${companySettings.patent_number || "Non spécifique"}</p>
                <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${
        companySettings.bank_name || "Non spécifié"
    } Email: ${companySettings.email || "Non spécifié"} </p>
            </div>
        </body>
        </html>`;

    console.log("HTML généré pour l'impression (Public):", html);
    printContent(html, title);
}
function getArretDates(ordresService) {
    if (!ordresService || !Array.isArray(ordresService)) return ["-"];
    return ordresService
        .filter((ordre) => ordre.type === "arret")
        .map((ordre) => {
            const date = ordre.date_ordre || "-";
            const jours = ordre.nbrjour ? `${ordre.nbrjour} jours` : "";
            return jours ? `${date} (${jours})` : date;
        });
}

function getRepriseDates(ordresService) {
    if (!ordresService || !Array.isArray(ordresService)) return ["-"];
    return ordresService
        .filter((ordre) => ordre.type === "reprise")
        .map((ordre) => ordre.date_ordre || "-");
}
// Fonction auxiliaire pour calculer le total des jours d'arrêt
function getTotalJoursArret(ordresService) {
    if (!ordresService || !Array.isArray(ordresService)) return "-";

    const totalJours = ordresService
        .filter((ordre) => ordre.type === "arret" && ordre.nbrjour)
        .reduce((total, ordre) => total + (parseInt(ordre.nbrjour) || 0), 0);

    return totalJours > 0 ? totalJours.toString() : "-";
}
// Fonction pour calculer la caution définitive (3% du budget)
function calculateCautionDefinitive(budget) {
    if (!budget) return "-";

    try {
        const budgetValue = parseFloat(
            budget.toString().replace(/[^\d.-]/g, "")
        );
        if (isNaN(budgetValue)) return "-";

        const caution = budgetValue * 0.03;
        return caution.toFixed(2);
    } catch (e) {
        console.error("Erreur lors du calcul de la caution définitive:", e);
        return "-";
    }
}
function formatChildRow(data) {
    return new Promise((resolve) => {
        $.ajax({
            url: `${$("#projetsTable").data("projets-base-url")}/${
                data.id
            }/salaries`,
            type: "GET",
            success: function (salaries) {
                let html = `<table class="table table-bordered" style="margin-left: 50px; width: calc(100% - 50px);">
                    <thead><tr>
                        <th>Nom</th>
                        <th>Rôle</th>
                        <th>Fonction</th>
                        <th>Date intégration</th>
                        <th>Date sortie</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr></thead><tbody>`;

                if (salaries.length > 0) {
                    const presenceChecks = salaries.map((salarie) =>
                        $.ajax({
                            url: `/projets/check-presence/${salarie.id}`,
                            type: "GET",
                            data: { projet_id: data.id },
                        })
                    );

                    Promise.all(presenceChecks)
                        .then((results) => {
                            salaries.forEach((salarie, index) => {
                                const presenceCount = results[index].count || 0;
                                const showDeleteButton = presenceCount === 0;

                                html += `<tr>
                                    <td>${salarie.nom || "-"}</td>
                                    <td>${salarie.pivot.role || "-"}</td>
                                    <td>${
                                        salarie.fonction?.designation || "-"
                                    }</td>
                                    <td>${
                                        salarie.pivot.date_integration || "-"
                                    }</td>
                                    <td>${salarie.pivot.date_sortie || "-"}</td>
                                    <td>${salarie.statut || "-"}</td>
                                    <td>
                                        <a href="javascript:;" class="text-body set-exit-date" 
                                            data-projet-id="${data.id}" 
                                            data-salarie-id="${salarie.id}" 
                                            data-bs-toggle="tooltip" 
                                            title="Définir/Modifier Date Sortie">
                                            <i class="bx bx-calendar mx-1"></i>
                                        </a>
                                        ${
                                            showDeleteButton
                                                ? `<a href="javascript:;" class="text-danger delete-salarie" 
                                                    data-projet-id="${data.id}" 
                                                    data-salarie-id="${salarie.id}" 
                                                    data-bs-toggle="tooltip" 
                                                    title="Supprimer l'affectation">
                                                    <i class="bx bx-trash mx-1"></i>
                                                </a>`
                                                : ""
                                        }
                                    </td>
                                </tr>`;
                            });

                            html += `</tbody></table>`;
                            resolve(html);
                        })
                        .catch(() => {
                            html += `<tr><td colspan="7" class="text-center">Erreur lors de la vérification des présences</td></tr>`;
                            html += `</tbody></table>`;
                            resolve(html);
                        });
                } else {
                    html += `<tr><td colspan="7" class="text-center">Aucun salarié affecté</td></tr>`;
                    html += `</tbody></table>`;
                    resolve(html);
                }
            },
            error: () =>
                resolve(
                    `<div class="alert alert-danger">Erreur de chargement des données</div>`
                ),
        });
    });
}

$(document).on("click", ".delete-salarie", function () {
    const $row = $(this).closest("tr");
    const projetId = $(this).data("projet-id");
    const salarieId = $(this).data("salarie-id");

    Swal.fire({
        title: "Confirmer la suppression?",
        text: "Cette action supprimera l'affectation du salarié pour ce projet.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Supprimer",
        cancelButtonText: "Annuler",
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/projets/${projetId}/salarie/${salarieId}/detach`,
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content"
                    ),
                },
                success: (response) => {
                    if (response.success) {
                        $row.fadeOut(400, () => {
                            $row.remove();
                            dt_projet.ajax.reload(null, false);
                        });
                        successSound.play();
                        Swal.fire("Succès!", response.message, "success");
                    }
                },
                error: (xhr) => {
                    console.error("Erreur AJAX:", xhr);
                    errorSound.play();
                    Swal.fire(
                        "Erreur!",
                        xhr.responseJSON?.message || "Échec de la suppression",
                        "error"
                    );
                },
            });
        }
    });
});

$(document).ready(function () {
    $("#public_budget").on("input", function () {
        const budget = parseFloat($(this).val()) || 0;
        const rg = (budget * 0.07).toFixed(2);
        $("#rg").val(rg);
    });

    $("#public_manual_num_p").on("change", function () {
        $("#public_num_p").prop("disabled", !this.checked);
        if (!this.checked) {
            $("#public_num_p").val("");
        }
    });
    $("#public_num_p").prop(
        "disabled",
        !$("#public_manual_num_p").is(":checked")
    );

    $("#create-public-projet-form").on("submit", function (e) {
        e.preventDefault();
        const form = $(this);
        $("#loadingModal").modal("show"); // Afficher le modal de chargement

        $.ajax({
            url: form.attr("action"),
            method: "POST",
            data: form.serialize(),
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success) {
                    $("#addPublicProjetModal").modal("hide");
                    form[0].reset();
                    dt_projet.ajax.reload(); // Recharger la table si DataTable est utilisée
                    Swal.fire("Succès!", response.message, "success");
                    successSound.play();
                }
            },
            error: function (xhr) {
                console.log("Create public projet AJAX error:", xhr);
                Swal.fire(
                    "Erreur!",
                    xhr.responseJSON?.message || "Erreur serveur",
                    "error"
                );
                errorSound.play();
            },
            complete: function () {
                $("#loadingModal").modal("hide"); // Cacher le modal une fois terminé
            },
        });
    });

    $(document).on("click", ".set-exit-date", function () {
        const projetId = $(this).data("projet-id");
        const salarieId = $(this).data("salarie-id");
        $("#exit_projet_id").val(projetId);
        $("#exit_salarie_id").val(salarieId);
        $("#date_sortie").val("");
        $("#setExitDateModal").modal("show");
    });

    $("#submitExitDate").on("click", function () {
        const form = $("#exit-date-form");
        $.ajax({
            url: "/projets/set-exit-date",
            method: "POST",
            data: form.serialize(),
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success) {
                    $("#setExitDateModal").modal("hide");
                    dt_projet.ajax.reload();
                    successSound.play();
                    Swal.fire("Succès!", response.message, "success");
                }
            },
            error: function (xhr) {
                console.log("Set Exit Date AJAX error:", xhr);
                errorSound.play();
                Swal.fire(
                    "Erreur!",
                    xhr.responseJSON?.message ||
                        "Erreur lors de la définition de la date de sortie",
                    "error"
                );
            },
        });
    });

    $("<style>")
        .prop("type", "text/css")
        .html(
            `
            .projet-cloture { background-color: rgba(40, 167, 69, 0.2) !important; }
            .affectation-icon-red { color: #dc3545 !important; }
            .affectation-icon-normal { color: inherit; }
            .delete-record, .print-projet, .edit-projet, .show-projet, .affecter-projet, .cloturer-projet, .upload-documents { 
                display: inline-block !important; 
                visibility: visible !important; 
                margin-right: 5px !important;
            }
        `
        )
        .appendTo("head");

    $("#manual_num_p").on("change", function () {
        $("#num_p").prop("disabled", !this.checked);
        if (!this.checked) {
            $("#num_p").val("");
        }
    });

    $("#edit_manual_num_p").on("change", function () {
        $("#edit_num_p").prop("disabled", !this.checked);
        if (!this.checked) {
            $("#edit_num_p").val("");
        }
    });

    $("#num_p").prop("disabled", !$("#manual_num_p").is(":checked"));
    $("#edit_num_p").prop("disabled", !$("#edit_manual_num_p").is(":checked"));

    const dt_projet_table = $("#projetsTable");
    let dt_projet;

    if (dt_projet_table.length) {
        dt_projet_table
            .find("thead tr")
            .clone(true)
            .appendTo(dt_projet_table.find("thead"));
        dt_projet_table.find("thead tr:eq(1) th").each(function (i) {
            if ([0, 7, 8].includes(i)) return;
            $(this).html(
                `<input type="text" class="form-control form-control-sm" placeholder="Rechercher ${$(
                    this
                ).text()}" />`
            );
            $("input", this).on("keyup change", function () {
                dt_projet.column(i).search(this.value).draw();
            });
        });

        dt_projet = dt_projet_table.DataTable({
            ajax: {
                url: dt_projet_table.data("projets-list-url"),
                dataSrc: "",
                error: function (xhr, error, thrown) {
                    console.log(
                        "DataTable AJAX error:",
                        xhr.responseText,
                        error,
                        thrown
                    );
                    Swal.fire(
                        "Erreur!",
                        "Échec du chargement des données de la table.",
                        "error"
                    );
                },
            },
            columns: [
                {
                    className: "dt-control",
                    orderable: false,
                    data: null,
                    defaultContent: "",
                },
                { data: "intitule" },
                { data: "num_p" },
                { data: "date_debut" },
                { data: "date_fin" },
                { data: "type_projet" },
                { data: "commande_type" },
                { data: "budget" },
           {
    data: null,
    render: function (data, type, row) {
        let html = `
            <a href="javascript:;" class="text-body upload-documents" 
               data-id="${row.id}" 
               data-bs-toggle="tooltip" 
               title="Gérer les documents">
                <i class="bx bx-folder-open mx-1"></i>
            </a>`;

        // Vérification pour projets publics
        if (row.type_projet === "Public" && row.dossier_complete) {
            html += `
                <a href="javascript:;" class="text-success ms-2 download-dossier" 
                   data-id="${row.id}" 
                   data-commande-type="${row.commande_type || "Marche"}" 
                   data-bs-toggle="tooltip" 
                   title="Télécharger le dossier complet">
                    <i class="bx bx-download"></i>
                </a>`;
        }
        // Vérification pour projets privés
        else if (row.type_projet === "Privé" && row.private_documents_count > 0) {
            html += `
                <a href="javascript:;" class="text-success ms-2 download-dossier" 
                   data-id="${row.id}" 
                   data-commande-type="Private" 
                   data-bs-toggle="tooltip" 
                   title="Télécharger les documents privés (${row.private_documents_count} fichier${row.private_documents_count > 1 ? "s" : ""})">
                    <i class="bx bx-download"></i>
                </a>`;
        }

        return html;
    },
},

                {
                    data: null,
                    render: function (data) {
                        const projectId = data.id || "unknown";
                        const isClotured = data.cloture || false;
                        const hasAffectation = data.has_affectation || false;
                        const baseUrl =
                            dt_projet_table.data("projets-base-url") || "";
                        return `
                <div class="d-flex align-items-center">
                    <a href="javascript:;" class="text-body cloturer-projet" 
                       data-id="${projectId}" 
                       data-clotured="${isClotured}" 
                       data-bs-toggle="tooltip" 
                       title="${isClotured ? "Déclôturer" : "Clôturer"}">
                        <i class="bx bx-${
                            isClotured ? "lock" : "lock-open"
                        } mx-1"></i>
                    </a>
                    <a href="javascript:;" class="text-body gérer-ordres-service" 
                       data-id="${projectId}" 
                       data-bs-toggle="tooltip" 
                       title="Gérer les ordres de service">
                        <i class="bx bx-list-ul mx-1"></i>
                    </a>
                    <a href="javascript:;" class="text-body edit-projet" 
                       data-id="${projectId}" 
                       data-type="${data.type_projet}" 
                       data-bs-toggle="tooltip" 
                       title="Modifier">
                        <i class="bx bx-edit mx-1"></i>
                    </a>
                    <a href="javascript:;" class="text-body delete-record" 
                       data-id="${projectId}" 
                       data-bs-toggle="tooltip" 
                       title="Supprimer">
                        <i class="bx bx-trash mx-1"></i>
                    </a>
                    <a href="javascript:;" class="text-body show-projet" 
                       data-id="${projectId}" 
                       data-attr="${baseUrl}/${projectId}" 
                       data-bs-toggle="tooltip" 
                       title="Voir">
                        <i class="bx bx-show mx-1"></i>
                    </a>
                    <a href="javascript:;" class="text-body print-projet" 
                       data-id="${projectId}" 
                       data-bs-toggle="tooltip" 
                       title="Imprimer">
                        <i class="bx bx-printer mx-1"></i>
                    </a>
                    <a href="javascript:;" class="text-body affecter-projet" 
                       data-id="${projectId}" 
                       data-bs-toggle="tooltip" 
                       title="Affecter">
                        <i class="bx bx-user-plus mx-1 ${
                            !hasAffectation ? "affectation-icon-red" : ""
                        }"></i>
                    </a>
                </div>`;
                    },
                },
            ],
            columnDefs: [
                { targets: [0, 7, 8], searchable: false, orderable: false },
                { targets: [1, 2, 3, 4, 5, 6], render: (data) => data || "-" },
            ],
            language: {
                lengthMenu: "Afficher _MENU_ projets",
                search: "Rechercher",
                searchPlaceholder: "Rechercher un projet",
                paginate: { next: "Suivant", previous: "Précédent" },
                info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            },
dom: '<"row ms-2 me-3"<"col-md-6"<"d-flex align-items-center flex-wrap gap-2"lB>><"col-md-6"f>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
           buttons: [
    {
        extend: "collection",
        className: "btn btn-label-primary dropdown-toggle me-0",
        text: '<i class="bx bx-export me-sm-1"></i> Exporter',
        buttons: [
            {
                text: '<i class="bx bx-printer me-1"></i>Imprimer',
                action: () =>
                    printProjetTable(
                        dt_projet,
                        "Liste des Projets",
                        {
                            cols: [
                                "intitule",
                                "num_p",
                                "date_debut",
                                "date_fin",
                                "type_projet",
                                "commande_type",
                                "budget",
                            ],
                            headers: [
                                "intitule",
                                "Numéro Projet",
                                "Date Début",
                                "Date Fin",
                                "Type Projet",
                                "commande_type",
                                "mont budget",
                            ],
                        }
                    ),
            },
        ],
    },
    {
        text: '<i class="bx bx-plus me-1"></i>Ajouter un projet privé',
        className: "btn btn-primary me-0",
        attr: {
            "data-bs-toggle": "modal",
            "data-bs-target": "#addProjetModal",
        },
    },
    {
        text: '<i class="bx bx-plus me-1"></i>Ajouter un projet public',
        className: "btn btn-primary me-0",
        attr: {
            "data-bs-toggle": "modal",
            "data-bs-target": "#addPublicProjetModal",
        },
    },
],
            responsive: {
                details: {
                    type: "column",
                    target: 0,
                },
            },
            rowCallback: function (row, data) {
                if (data.cloture) $(row).addClass("projet-cloture");
            },
        });

// ── Filtre par année ──────────────────────────────────────────────────
// Génère les années disponibles et injecte le select à côté du bouton Exporter
function buildYearFilter() {
    const currentYear = new Date().getFullYear();

    // Collecte les années depuis les données chargées
    function getAvailableYears(data) {
        const years = new Set();
        years.add(currentYear); // Toujours inclure l'année courante
        data.forEach(function (row) {
            if (row.cloture_date) {
                const y = new Date(row.cloture_date).getFullYear();
                if (!isNaN(y)) years.add(y);
            }
        });
        return Array.from(years).sort((a, b) => b - a); // Décroissant
    }

    // Construit le select HTML
   function renderSelect(years) {
    let opts = `<option value="${currentYear}" selected>${currentYear} (en cours)</option>`;
    years.forEach(function (y) {
        if (y !== currentYear) {
            opts += `<option value="${y}">${y}</option>`;
        }
    });
    return `<select id="year-filter-select" 
                class="form-select form-select-sm" 
                style="width:150px; height:38px; display:inline-block; vertical-align:middle;">
                ${opts}
            </select>`;
}

function injectSelect(years) {
    $("#year-filter-select").remove();
    const $select = $(renderSelect(years));
    
    // Insérer après le premier bouton (Exporter)
    const $firstBtn = $(".dt-buttons .btn:first-child, .dt-buttons .buttons-collection:first-child").first();
    $firstBtn.after($select);

    $select.on("change", function () {
        applyYearFilter(parseInt($(this).val()));
    });
}
    // Applique le filtre côté client via $.fn.dataTable.ext.search
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
        (fn) => fn._yearFilter !== true
    );

    function applyYearFilter(selectedYear) {
        // Retire un éventuel filtre précédent
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
            (fn) => fn._yearFilter !== true
        );

        const filterFn = function (settings, data, dataIndex) {
            if (settings.nTable.id !== "projetsTable") return true;
            const rowData = dt_projet.row(dataIndex).data();
            if (!rowData) return true;

            const isClosed = rowData.cloture && rowData.cloture_date;

            if (!isClosed) {
                // Projet non clôturé : visible uniquement dans l'année courante
                return selectedYear === currentYear;
            } else {
                // Projet clôturé : visible dans l'année de sa clôture
                const closeYear = new Date(rowData.cloture_date).getFullYear();
                return closeYear === selectedYear;
            }
        };

        filterFn._yearFilter = true;
        $.fn.dataTable.ext.search.push(filterFn);
        dt_projet.draw();
    }

    // Chargement initial des années depuis l'API
    $.ajax({
        url: dt_projet_table.data("projets-list-url"),
        type: "GET",
        success: function (data) {
            const years = getAvailableYears(Array.isArray(data) ? data : []);
            injectSelect(years);
            applyYearFilter(currentYear); // Filtre par défaut : année en cours
        },
        error: function () {
            // En cas d'erreur : injecte juste l'année courante
            injectSelect([currentYear]);
            applyYearFilter(currentYear);
        },
    });
}

buildYearFilter();

// Rafraîchit les années disponibles après chaque rechargement AJAX
dt_projet.on("xhr", function () {
    const json = dt_projet.ajax.json();
    if (!json) return;
    const data = Array.isArray(json) ? json : [];
    const currentYear = new Date().getFullYear();
    const years = new Set([currentYear]);
    data.forEach(function (row) {
        if (row.cloture_date) {
            const y = new Date(row.cloture_date).getFullYear();
            if (!isNaN(y)) years.add(y);
        }
    });
    const sortedYears = Array.from(years).sort((a, b) => b - a);
    const $sel = $("#year-filter-select");
    const currentVal = parseInt($sel.val()) || currentYear;

    let opts = `<option value="${currentYear}">${currentYear} (en cours)</option>`;
    sortedYears.forEach(function (y) {
        if (y !== currentYear) {
            opts += `<option value="${y}">${y}</option>`;
        }
    });
    $sel.html(opts).val(
        sortedYears.includes(currentVal) ? currentVal : currentYear
    );
});
// ─────────────────────────────────────────────────────────────────────
        

        // Activer les tooltips après chaque dessin de la table
        dt_projet.on("draw", () => {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
        dt_projet_table.on("click", "td.dt-control", function () {
            const tr = $(this).closest("tr");
            const row = dt_projet.row(tr);

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass("shown");
            } else {
                formatChildRow(row.data()).then((html) => {
                    row.child(html).show();
                    tr.addClass("shown");
                });
            }
        });

        dt_projet.on("draw", () => {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    }

    $(document).on("click", ".download-dossier", function () {
        const projetId = $(this).data("id");
        const commandeType = $(this).data("commande-type");
        const baseUrl = dt_projet_table.data("projets-base-url");
        const downloadPrivateDossierUrl = `${baseUrl}/${projetId}/download-private-dossier`;

        console.log("Tentative de téléchargement", {
            projetId,
            commandeType,
            downloadPrivateDossierUrl,
        });

        $.ajax({
            url: `/projets/${projetId}`,
            type: "GET",
            dataType: "json",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
            success: function (response) {
                console.log("Réponse de /projets/:", response);
                if (!response.success || !response.data) {
                    Swal.fire("Erreur!", "Projet non trouvé.", "error");
                    errorSound.play();
                    return;
                }

                const project = response.data;

                if (
                    project.type_projet === "Privé" &&
                    commandeType === "Private"
                ) {
                    // Supprimer la vérification de private_documents_count
                    Swal.fire({
                        title: "Téléchargement en cours",
                        html: "Préparation du dossier privé...",
                        timerProgressBar: true,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });

                    $.ajax({
                        url: downloadPrivateDossierUrl,
                        type: "GET",
                        xhrFields: { responseType: "blob" },
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                        success: function (data, status, xhr) {
                            const contentType =
                                xhr.getResponseHeader("Content-Type");
                            console.log("Réponse de téléchargement:", {
                                contentType,
                                status,
                            });
                            if (contentType.includes("application/zip")) {
                                const blob = new Blob([data], {
                                    type: "application/zip",
                                });
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement("a");
                                a.href = url;
                                a.download = `dossier_prive_projet_${projetId}.zip`;
                                document.body.appendChild(a);
                                a.click();
                                window.URL.revokeObjectURL(url);
                                a.remove();

                                Swal.fire({
                                    title: "Succès!",
                                    text: "Dossier privé téléchargé avec succès.",
                                    icon: "success",
                                    timer: 2000,
                                    showConfirmButton: false,
                                });
                            } else {
                                data.text().then((text) => {
                                    console.error("Réponse non-ZIP:", text);
                                    try {
                                        const json = JSON.parse(text);
                                        Swal.fire({
                                            title: "Erreur!",
                                            text:
                                                json.message ||
                                                "Erreur lors du téléchargement",
                                            icon: "error",
                                        });
                                    } catch (e) {
                                        Swal.fire({
                                            title: "Erreur!",
                                            text: "Réponse invalide du serveur",
                                            icon: "error",
                                        });
                                    }
                                });
                            }
                        },
                        error: function (xhr) {
                            console.error(
                                "Erreur AJAX de téléchargement:",
                                xhr
                            );
                            Swal.fire({
                                title: "Erreur!",
                                text:
                                    xhr.responseJSON?.message ||
                                    "Erreur lors du téléchargement",
                                icon: "error",
                            });
                            errorSound.play();
                        },
                    });
                } else if (project.type_projet === "Public") {
                    const checkUrl = `${baseUrl}/${projetId}/check-dossier`;
                    const downloadUrl = `${baseUrl}/${projetId}/download-dossier`;
                    const downloadDecompteUrl = `${baseUrl}/${projetId}/download-decompte-dossier`;
                    const downloadOrdreServiceUrl = `${baseUrl}/${projetId}/download-ordre-service-dossier`;

                    $("#downloadModal").modal("show");
                    $("#downloadModal .modal-body").html(
                        "<p>Vérification des fichiers en cours...</p>"
                    );

                    $.ajax({
                        url: checkUrl,
                        type: "GET",
                        data: { commande_type: commandeType },
                        dataType: "json",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            Accept: "application/json",
                        },
                        success: function (checkResponse) {
                            console.log(
                                "Réponse de check-dossier:",
                                checkResponse
                            );
                            if (checkResponse.files_exist) {
                                let modalContent = `
                                <h5>Options de téléchargement</h5>
                                <div class="download-options" data-projet-id="${projetId}">
                                    <button class="btn btn-primary mb-2 download-complete-dossier" data-url="${downloadUrl}">
                                        <i class="bx bx-download"></i> Télécharger le dossier complet
                                    </button>
                                    <button class="btn btn-secondary mb-2 download-decompte-dossier" data-url="${downloadDecompteUrl}">
                                        <i class="bx bx-download"></i> Télécharger le dossier des décomptes
                                    </button>
                                    <button class="btn btn-info mb-2 download-ordre-service-dossier" data-url="${downloadOrdreServiceUrl}">
                                        <i class="bx bx-download"></i> Télécharger le dossier des ordres de service
                                    </button>
                            `;
                                if (
                                    checkResponse.available_documents?.length >
                                    0
                                ) {
                                    modalContent += `<h6 class="mt-3">Documents individuels :</h6><div class="list-group">`;
                                    checkResponse.available_documents.forEach(
                                        (doc) => {
                                            modalContent += `
                                        <a href="${baseUrl}/${projetId}/download-document/${doc.id}" 
                                           class="list-group-item list-group-item-action download-single-document"
                                           data-url="${baseUrl}/${projetId}/download-document/${doc.id}">
                                            ${doc.name} <i class="bx bx-download float-end"></i>
                                        </a>
                                    `;
                                        }
                                    );
                                    modalContent += `</div>`;
                                }
                                modalContent += `</div>`;
                                $("#downloadModal .modal-body").html(
                                    modalContent
                                );
                            } else {
                                let errorMessage =
                                    checkResponse.message ||
                                    "Certains fichiers sont introuvables.";
                                let detailsHtml =
                                    '<div class="alert alert-warning"><ul class="text-left">';
                                if (commandeType === "BC") {
                                    detailsHtml += `<li>Pour un Bon de Commande (BC), vous devez au moins télécharger :</li>
                                              <li>- Document Marché</li><li>- Ordre de Service</li>`;
                                } else {
                                    detailsHtml += `<li>Pour un Marché, tous les documents suivants sont requis :</li>
                                              <li>- Document Marché</li><li>- Ordre de Service</li><li>- Document Assurance</li>
                                              <li>- Demande de Cautionnement</li><li>- Document Caution Provisoire</li>
                                              <li>- Document Caution Définitive</li>`;
                                }
                                if (
                                    checkResponse.missing_files &&
                                    Object.keys(checkResponse.missing_files)
                                        .length > 0
                                ) {
                                    detailsHtml += `<li class="mt-2">Fichiers manquants :</li>`;
                                    for (const [file, info] of Object.entries(
                                        checkResponse.missing_files
                                    )) {
                                        detailsHtml += `<li class="text-danger">- ${file}: ${
                                            info.db_path || "Non défini"
                                        } - Introuvable</li>`;
                                    }
                                }
                                detailsHtml += "</ul></div>";
                                $("#downloadModal .modal-body").html(`


                                    
                                <h6>Documents incomplets</h6>
                                <p>${errorMessage}</p>
                                ${detailsHtml}
                                <div class="mt-3">
                                    <button class="btn btn-primary upload-documents" data-id="${projetId}">
                                        <i class="bx bx-upload"></i> Téléverser les documents manquants
                                    </button>
                                </div>
                            `);
                            }
                        },
                        error: function (xhr) {
                            console.error("Erreur AJAX de check-dossier:", xhr);
                            $("#downloadModal .modal-body").html(`
                            <div class="alert alert-danger">
                                <h6>Erreur !</h6>
                                <p>${
                                    xhr.responseJSON?.message ||
                                    "Erreur inconnue lors de la vérification des fichiers."
                                }</p>
                                <p>Veuillez vérifier les logs ou contacter l'administrateur.</p>
                            </div>
                        `);
                            errorSound.play();
                        },
                    });
                } else {
                    Swal.fire(
                        "Erreur!",
                        "Type de projet invalide ou non autorisé.",
                        "error"
                    );
                    errorSound.play();
                }
            },
            error: function (xhr) {
                console.error("Erreur AJAX de /projets:", xhr);
                Swal.fire({
                    title: "Erreur!",
                    text:
                        xhr.responseJSON?.message ||
                        "Erreur lors de la récupération des détails du projet.",
                    icon: "error",
                });
                errorSound.play();
            },
        });
    });

    $("#upload-private-documents-form").on("submit", function (e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(this);
        const submitButton = form.find("button[type='submit']");

        // Validate files
        const files = $("#private_documents")[0].files;
        let isValid = true;

        if (files.length === 0) {
            $("#private_documents").addClass("is-invalid");
            $("#private_documents")
                .siblings(".invalid-feedback")
                .text("Veuillez sélectionner au moins un fichier.");
            isValid = false;
        } else {
            for (let file of files) {
                if (!file.type.includes("pdf")) {
                    $("#private_documents").addClass("is-invalid");
                    $("#private_documents")
                        .siblings(".invalid-feedback")
                        .text("Seuls les fichiers PDF sont acceptés.");
                    isValid = false;
                    break;
                }
            }
        }

        if (!isValid) return;

        $("#loadingModal").modal("show");
        submitButton
            .prop("disabled", true)
            .html('<i class="bx bx-loader bx-spin me-1"></i> Chargement...');

        $.ajax({
            url: form.attr("action"),
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success) {
                    $("#uploadPrivateDocumentsModal").modal("hide");
                    form[0].reset();
                    $("#existing_private_documents").empty();
                    dt_projet.ajax.reload(null, false);
                    successSound.play();
                    Swal.fire("Succès!", response.message, "success");
                }
            },
            error: function (xhr) {
                console.error("Upload private documents AJAX error:", xhr);
                errorSound.play();
                Swal.fire(
                    "Erreur!",
                    xhr.responseJSON?.message ||
                        "Erreur lors de l'upload des documents",
                    "error"
                );
            },
            complete: function () {
                $("#loadingModal").modal("hide");
                submitButton
                    .prop("disabled", false)
                    .html('<i class="bx bx-upload me-1"></i> Téléverser');
            },
        });
    });

    dt_projet_table
        .DataTable()
        .column(8)
        .nodes()
        .to$()
        .each(function (index) {
            const data = dt_projet.row($(this).closest("tr")).data();
            if (data.type_projet === "Privé") {
                $(this).html(`
            <a href="javascript:;" class="text-body upload-documents" 
               data-id="${data.id}" 
               data-bs-toggle="tooltip" 
               title="Gérer les documents">
                <i class="bx bx-folder-open mx-1"></i>
            </a>
            ${
                data.private_documents_count && data.private_documents_count > 0
                    ? `
                <a href="javascript:;" class="text-success ms-2 download-dossier" 
                   data-id="${data.id}" 
                   data-commande-type="Private"
                   data-bs-toggle="tooltip" 
                   title="Télécharger les documents privés (${
                       data.private_documents_count
                   } fichier${data.private_documents_count > 1 ? "s" : ""})">
                    <i class="bx bx-download"></i>
                </a>`
                    : ""
            }
        `);
            }
        });
    // Handle complete dossier download
    $(document).on("click", ".download-complete-dossier", function () {
        const downloadUrl = $(this).data("url");
        Swal.fire({
            title: "Téléchargement en cours",
            html: "Préparation du dossier complet...",
            timerProgressBar: true,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: downloadUrl,
            type: "GET",
            xhrFields: {
                responseType: "blob",
            },
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
            success: function (data, status, xhr) {
                const contentType = xhr.getResponseHeader("Content-Type");
                if (contentType.includes("application/zip")) {
                    const blob = new Blob([data], { type: "application/zip" });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = `dossier_projet_${$(this)
                        .closest(".download-options")
                        .data("projet-id")}.zip`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();

                    Swal.fire({
                        title: "Succès!",
                        text: "Dossier complet téléchargé avec succès.",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    $("#downloadModal").modal("hide");
                } else {
                    data.text().then((text) => {
                        const json = JSON.parse(text);
                        Swal.fire({
                            title: "Erreur!",
                            text:
                                json.message || "Erreur lors du téléchargement",
                            icon: "error",
                        });
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    title: "Erreur!",
                    text:
                        xhr.responseJSON?.message ||
                        "Erreur lors du téléchargement",
                    icon: "error",
                });
            },
        });
    });

    // Handle decompte dossier download
    $(document).on("click", ".download-decompte-dossier", function () {
        const downloadUrl = $(this).data("url");
        Swal.fire({
            title: "Téléchargement en cours",
            html: "Préparation du dossier des décomptes...",
            timerProgressBar: true,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: downloadUrl,
            type: "GET",
            xhrFields: {
                responseType: "blob",
            },
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
            success: function (data, status, xhr) {
                const contentType = xhr.getResponseHeader("Content-Type");
                if (contentType.includes("application/zip")) {
                    const blob = new Blob([data], { type: "application/zip" });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = `decompte_projet_${$(this)
                        .closest(".download-options")
                        .data("projet-id")}.zip`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();

                    Swal.fire({
                        title: "Succès!",
                        text: "Dossier des décomptes téléchargé. Vérifiez le contenu du ZIP, car certains fichiers peuvent être manquants.",
                        icon: "success",
                        timer: 3000,
                        showConfirmButton: false,
                    });
                    $("#downloadModal").modal("hide");
                } else {
                    data.text().then((text) => {
                        const json = JSON.parse(text);
                        Swal.fire({
                            title: "Erreur!",
                            text:
                                json.message || "Erreur lors du téléchargement",
                            icon: "error",
                        });
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    title: "Erreur!",
                    text:
                        xhr.responseJSON?.message ||
                        "Erreur lors du téléchargement",
                    icon: "error",
                });
            },
        });
    });

    // Handle ordre de service dossier download
    $(document).on("click", ".download-ordre-service-dossier", function () {
        const downloadUrl = $(this).data("url");
        Swal.fire({
            title: "Téléchargement en cours",
            html: "Préparation du dossier des ordres de service...",
            timerProgressBar: true,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: downloadUrl,
            type: "GET",
            xhrFields: {
                responseType: "blob",
            },
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
            success: function (data, status, xhr) {
                const contentType = xhr.getResponseHeader("Content-Type");
                if (contentType.includes("application/zip")) {
                    const blob = new Blob([data], { type: "application/zip" });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = `ordre_service_projet_${$(this)
                        .closest(".download-options")
                        .data("projet-id")}.zip`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();

                    Swal.fire({
                        title: "Succès!",
                        text: "Dossier des ordres de service téléchargé. Vérifiez le contenu du ZIP, car certains fichiers peuvent être manquants.",
                        icon: "success",
                        timer: 3000,
                        showConfirmButton: false,
                    });
                    $("#downloadModal").modal("hide");
                } else {
                    data.text().then((text) => {
                        const json = JSON.parse(text);
                        Swal.fire({
                            title: "Erreur!",
                            text:
                                json.message || "Erreur lors du téléchargement",
                            icon: "error",
                        });
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    title: "Erreur!",
                    text:
                        xhr.responseJSON?.message ||
                        "Erreur lors du téléchargement",
                    icon: "error",
                });
            },
        });
    });

    // Handle single document download
    $(document).on("click", ".download-single-document", function (e) {
        e.preventDefault();
        const downloadUrl = $(this).data("url");
        Swal.fire({
            title: "Téléchargement en cours",
            html: "Préparation du document...",
            timerProgressBar: true,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: downloadUrl,
            type: "GET",
            xhrFields: {
                responseType: "blob",
            },
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
            success: function (data, status, xhr) {
                const contentType = xhr.getResponseHeader("Content-Type");
                const disposition = xhr.getResponseHeader(
                    "Content-Disposition"
                );
                let filename = "document.pdf";
                if (disposition && disposition.indexOf("attachment") !== -1) {
                    const matches = /filename="([^"]*)"/.exec(disposition);
                    if (matches != null && matches[1]) filename = matches[1];
                }

                const blob = new Blob([data], { type: contentType });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();

                Swal.fire({
                    title: "Succès!",
                    text: "Document téléchargé avec succès.",
                    icon: "success",
                    timer: 2000,
                    showConfirmButton: false,
                });
                $("#downloadModal").modal("hide");
            },
            error: function (xhr) {
                Swal.fire({
                    title: "Erreur!",
                    text:
                        xhr.responseJSON?.message ||
                        "Erreur lors du téléchargement",
                    icon: "error",
                });
            },
        });
    });

    $(document)
        .on("click", ".cloturer-projet", function () {
            const id = $(this).data("id");
            const isClotured = $(this).data("clotured") || false; // Get current cloture status from data attribute
            const action = isClotured ? "déclôturer" : "clôturer";
            const actionTitle = isClotured ? "Déclôturer" : "Clôturer";
            const actionText = isClotured
                ? "Voulez-vous rouvrir ce projet ?"
                : "Voulez-vous clôturer ce projet ? Cette action est réversible.";
            const url = isClotured
                ? `${dt_projet_table.data("projets-base-url")}/${id}/decloturer`
                : `${dt_projet_table.data("projets-base-url")}/${id}/cloturer`;

            Swal.fire({
                title: `Confirmer la ${actionTitle} ?`,
                text: actionText,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: actionTitle,
                cancelButtonText: "Annuler",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                                "content"
                            ),
                        },
                        success: (response) => {
                            if (response.success) {
                                dt_projet.ajax.reload(null, false); // Refresh DataTable without resetting pagination
                                successSound.play();
                                Swal.fire(
                                    "Succès!",
                                    response.message,
                                    "success"
                                );
                            }
                        },
                        error: (xhr) => {
                            console.log(`${actionTitle} AJAX error:`, xhr);
                            errorSound.play();
                            Swal.fire(
                                "Erreur!",
                                xhr.responseJSON?.message ||
                                    `Échec de la ${action}`,
                                "error"
                            );
                        },
                    });
                }
            });
        })
        .on("click", ".affecter-projet", function () {
    const id = $(this).data("id");
    $("#projet_id").val(id);
    $("#responsable")
        .empty()
        .append('<option value="">Sélectionner un responsable</option>');
    $("#ouvriers").empty();
    $("#responsable").append('<option value="">Chargement...</option>');
    $("#ouvriers").append('<option value="">Chargement...</option>');

    $.ajax({
        url: `${dt_projet_table.data("projets-base-url")}/${id}/affectation-data`,
        type: "GET",
        success: function (response) {
            $("#responsable")
                .empty()
                .append('<option value="">Sélectionner un responsable</option>');
            $("#ouvriers").empty();

            // Remplir la liste des responsables
            response.responsables.forEach(function (salarie) {
                let text = `${salarie.nom} ${salarie.prenom}`;
                if (salarie.fonction && salarie.fonction.designation) {
                    text += ` (${salarie.fonction.designation})`;
                }
                
                // Afficher les projets actuels du salarié
                if (salarie.projets_actuels && salarie.projets_actuels.length > 0) {
                    const projetsNames = salarie.projets_actuels.map(p => p.designation).join(', ');
                    text += ` [Projets: ${projetsNames}]`;
                }
                
                if (salarie.pivot && salarie.pivot.date_sortie) {
                    text += ` [Sorti: ${new Date(salarie.pivot.date_sortie).toLocaleDateString()}]`;
                }
                
                const option = new Option(
                    text,
                    salarie.id,
                    false,
                    salarie.is_assigned && salarie.pivot && salarie.pivot.role === "responsable"
                );
                $("#responsable").append(option);
            });

            // Remplir la liste des ouvriers
            response.ouvriers.forEach(function (salarie) {
                let text = `${salarie.nom} ${salarie.prenom}`;
                if (salarie.fonction && salarie.fonction.designation) {
                    text += ` (${salarie.fonction.designation})`;
                }
                
                // Afficher les projets actuels du salarié
                if (salarie.projets_actuels && salarie.projets_actuels.length > 0) {
                    const projetsNames = salarie.projets_actuels.map(p => p.designation).join(', ');
                    text += ` [Projets: ${projetsNames}]`;
                }
                
                if (salarie.pivot && salarie.pivot.date_sortie) {
                    text += ` [Sorti: ${new Date(salarie.pivot.date_sortie).toLocaleDateString()}]`;
                }
                
                const option = new Option(
                    text,
                    salarie.id,
                    false,
                    salarie.is_assigned && salarie.pivot && salarie.pivot.role === "ouvrier"
                );
                $("#ouvriers").append(option);
            });

            $("#responsable").select2({
                dropdownParent: $("#affectationModal"),
                placeholder: "Sélectionner un responsable",
                allowClear: true,
            });

            $("#ouvriers").select2({
                dropdownParent: $("#affectationModal"),
                placeholder: "Sélectionner des ouvriers",
                allowClear: true,
                multiple: true,
            });

            $("#affectationModal").modal("show");
        },
        error: function (xhr) {
            console.error("AJAX Error:", xhr);
            let errorMessage = "Échec du chargement des données d'affectation.";
            if (xhr.status === 500) {
                errorMessage += " Erreur serveur.";
            } else if (xhr.status === 404) {
                errorMessage += " Route non trouvée.";
            }
            Swal.fire("Erreur!", errorMessage, "error");
            errorSound.play();
        },
    });
})
        .on("click", "#submitAffectation", function () {
            const form = $("#affectation-form");
            $.ajax({
                url: "/projets/affecter",
                method: "POST",
                data: form.serialize(),
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content"
                    ),
                },
                success: (response) => {
                    if (response.success) {
                        $("#affectationModal").modal("hide");
                        dt_projet.ajax.reload();
                        successSound.play();
                        Swal.fire("Succès!", response.message, "success");
                    }
                },
                error: (xhr) => {
                    console.error("Affectation AJAX error:", xhr);
                    errorSound.play();
                    Swal.fire(
                        "Erreur!",
                        xhr.responseJSON?.message ||
                            "Erreur lors de l'affectation",
                        "error"
                    );
                },
            });
        })
        .on("click", ".print-projet", function () {
            const id = $(this).data("id");
            const projetId = $(this).data("id");
            printProjetById(projetId);
            $.ajax({
                url: `${dt_projet_table.data("projets-base-url")}/${id}`,
                success: function (data) {
                    printSingleProjet(data);
                },
                error: function (xhr) {
                    Swal.fire(
                        "Erreur!",
                        "Échec du chargement des données pour l'impression",
                        "error"
                    );
                },
            });
        })
        .on("submit", "#create-projet-form", function (e) {
            e.preventDefault();
            const form = $(this);
            $("#loadingModal").modal("show");
            const submitButton = form.find("button[type='submit']");
            submitButton
                .prop("disabled", true)
                .html(
                    '<i class="bx bx-loader bx-spin me-1"></i> Chargement...'
                );

            $.ajax({
                url: $(this).attr("action"),
                method: "POST",
                data: $(this).serialize(),
                success: function (response) {
                    if (response.success) {
                        $("#addProjetModal").modal("hide");
                        $("#create-projet-form")[0].reset();
                        $("#projet_id").val(response.projet_id);
                        dt_projet.ajax.reload();
                        $("#confirmAffectationModal").modal("show");
                        successSound.play();
                    }
                },
                error: (xhr) => {
                    console.log("Create form AJAX error:", xhr);
                    errorSound.play();
                    Swal.fire(
                        "Erreur!",
                        xhr.responseJSON?.message || "Erreur serveur",
                        "error"
                    );
                },
                complete: function () {
                    $("#loadingModal").modal("hide");
                    submitButton
                        .prop("disabled", false)
                        .html(
                            '<i class="bx bx-check me-1"></i><span class="align-middle">Ajouter</span>'
                        );
                },
            });
        })
        .on("click", ".delete-record", function () {
            const id = $(this).data("id");
            Swal.fire({
                title: "Confirmer la suppression?",
                text: "Cette action est irréversible!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Supprimer",
                cancelButtonText: "Annuler",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `${dt_projet_table.data(
                            "projets-base-url"
                        )}/${id}`,
                        method: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                                "content"
                            ),
                        },
                        success: () => {
                            dt_projet.ajax.reload();
                            successSound.play();
                            Swal.fire("Succès!", "Projet supprimé!", "success");
                        },
                        error: (xhr) => {
                            console.log("Delete AJAX error:", xhr);
                            errorSound.play();
                            Swal.fire(
                                "Erreur!",
                                "Échec de la suppression",
                                "error"
                            );
                        },
                    });
                }
            });
        })
        .on("click", ".show-projet", function () {
            const url = $(this).data("attr");
            $.ajax({
                url: url,
                success: (data) => {
                    if (data.success) {
                        const project = data.data;
                        let tableRows = [
                            { key: "intitule", label: "Intitulé" },
                            { key: "description", label: "Description" },
                            { key: "num_p", label: "Numéro Projet" },
                            { key: "date_debut", label: "Date Début" },
                            { key: "date_fin", label: "Date Fin" },
                            { key: "type_projet", label: "Type Projet" },
                            { key: "budget", label: "Budget" },
                            {
                                key: "rg",
                                label: "RG (7%)",
                                showIf: () => project.type_projet === "Public",
                            },
                            {
                                key: "ville",
                                label: "Ville",
                                showIf: () => project.type_projet === "Public",
                            },
                            {
                                key: "maitre_ouvrage",
                                label: "Maître d'Ouvrage",
                                showIf: () => project.type_projet === "Public",
                            },
                            {
                                key: "date_offre",
                                label: "Date Offre",
                                showIf: () => project.type_projet === "Public",
                            },
                            {
                                key: "date_marche",
                                label: "Date Marché",
                                showIf: () => project.type_projet === "Public",
                            },
                            {
                                key: "delai_execution",
                                label: "Délai d'Exécution (mois)",
                                showIf: () => project.type_projet === "Public",
                            },
                            {
                                key: "cloture",
                                label: "Clôturé",
                                format: (val) => (val ? "Oui" : "Non"),
                            },
                            {
                                key: "cloture_date",
                                label: "Date de Clôture",
                                format: (val) => val || "-",
                            },
                            { key: "created_at", label: "Créé le" },
                            { key: "updated_at", label: "Mis à jour le" },
                        ]
                            .filter((field) => !field.showIf || field.showIf())
                            .map(
                                (field) => `
                <tr>
                    <th>${field.label}</th>
                    <td>${
                        field.format
                            ? field.format(project[field.key])
                            : project[field.key] || "-"
                    }</td>
                </tr>
            `
                            )
                            .join("");
                        // Use tableRows instead of tabsContent
                        $("#projet-details").html(
                            `<table class="table">${tableRows}</table>`
                        );
                        $("#showProjetModal").modal("show");
                        $("#showProjetModal .modal-footer").html(`
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            <button type="button" class="btn btn-primary btn-print-projet-details" data-projet-id="${project.id}">
                <i class="bx bx-printer me-1"></i> Imprimer
            </button>
        `);
                        $(".btn-print-projet-details").on("click", function () {
                            const projetId = $(this).data("projet-id");
                            printShowProjet(project);
                        });
                    } else {
                        Swal.fire(
                            "Erreur!",
                            data.message || "Échec du chargement des détails",
                            "error"
                        );
                    }
                },
                error: (xhr) => {
                    console.log(
                        "Show AJAX error:",
                        xhr.status,
                        xhr.responseText
                    );
                    Swal.fire(
                        "Erreur!",
                        "Échec du chargement des détails: " +
                            (xhr.responseJSON?.message ||
                                "Vérifiez la console"),
                        "error"
                    );
                },
            });
        })

        .on("click", ".edit-projet", function () {
           const projetId = $(this).data("id");
    const typeProjet = $(this).data("type") || "Privé";
    const normalizedType =
        typeProjet.charAt(0).toUpperCase() +
        typeProjet.slice(1).toLowerCase();
    $("#editProjetModalLabel").text(
        `Modifier un Projet ${normalizedType}`
    );
    $("#edit-projet-form-container").html(
        "<p>Chargement du formulaire...</p>"
    );
    $("#editProjetModal").modal("show");

    $.ajax({
        url: `/projets/${projetId}/edit`,
        type: "GET",
        data: { type: normalizedType.toLowerCase() },
        success: function (response) {
            $("#edit-projet-form-container").html(
                response.form || response
            );

            if (normalizedType === "Public") {
                $("#edit_budget").on("input", function () {
                    const budget = parseFloat($(this).val()) || 0;
                    const commandeType = $("#edit-public-projet-form input[name='commande_type']").val();
                    const rg = commandeType === 'Marche' ? (budget * 0.07).toFixed(2) : 0;
                    const cautionDefinitif = commandeType === 'Marche' ? (budget * 0.03).toFixed(2) : 0;
                    const totalDecompte = (budget - parseFloat(rg)).toFixed(2);

                    $("#edit_rg").val(rg);
                    $("#edit_caution_definitif").val(cautionDefinitif);
                    $("#edit_total_decompte").val(totalDecompte);
                });

                $("#edit_manual_num_p").on("change", function () {
                    $("#edit_num_p").prop("disabled", !this.checked);
                    if (!this.checked) {
                        $("#edit_num_p").val("");
                    }
                });
                $("#edit_num_p").prop(
                    "disabled",
                    !$("#edit_manual_num_p").is(":checked")
                );
            } else {
                $("#edit_manual_num_p").on("change", function () {
                    $("#edit_num_p").prop("disabled", !this.checked);
                    if (!this.checked) {
                        $("#edit_num_p").val("");
                    }
                });
                $("#edit_num_p").prop(
                    "disabled",
                    !$("#edit_manual_num_p").is(":checked")
                );
            }

            if ($.fn.select2) {
                $("#edit_projet_form select").select2({
                    dropdownParent: $("#editProjetModal"),
                    placeholder: "Sélectionner...",
                    allowClear: true,
                });
            }
        },
        error: function (xhr) {
            console.error(
                "Erreur lors du chargement du formulaire:",
                xhr
            );
            errorSound.play();
            $("#edit-projet-form-container").html(
                '<div class="alert alert-danger">Erreur lors du chargement du formulaire.</div>'
            );
            Swal.fire(
                "Erreur!",
                "Échec du chargement du formulaire de modification.",
                "error"
            );
        },
    });
        });

    $(document).on(
        "submit",
        "#edit-private-projet-form, #edit-public-projet-form",
        function (e) {
            e.preventDefault();
            const form = $(this);
            const url = form.attr("action");
            const isPublic = form.attr("id") === "edit-public-projet-form";
            form.find(".is-invalid").removeClass("is-invalid");
            form.find(".invalid-feedback").text("");
            form.find(".text-danger").text("");

            // if (isPublic) {
            //     const budget = parseFloat(form.find("#edit_budget").val()) || 0;
            //     const rg = parseFloat(form.find("#edit_rg").val()) || 0;
            //     if (rg !== (budget * 0.07).toFixed(2)) {
            //         form.find("#edit_rg").addClass("is-invalid");
            //         form.find("#edit_rg").siblings(".invalid-feedback").text("Le RG doit être égal à 7% du budget.");
            //         return;
            //     }
            // }

            $.ajax({
                url: url,
                type: "POST",
                data: form.serialize(),
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content"
                    ),
                },
                success: function (response) {
                    if (response.success) {
                        form.closest(".modal").modal("hide");
                        dt_projet.ajax.reload(null, false);
                        successSound.play();
                        Swal.fire("Succès!", response.message, "success");
                    }
                },
                error: function (xhr) {
                    console.error("Form submission error:", xhr);
                    errorSound.play();
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        $.each(errors, function (key, value) {
                            const field = form.find(`[name="${key}"]`);
                            field.addClass("is-invalid");
                            field.siblings(".invalid-feedback").text(value[0]);
                            form.find(`#edit_${key}-error`).text(value[0]);
                        });
                    } else {
                        Swal.fire(
                            "Erreur!",
                            xhr.responseJSON?.message ||
                                "Erreur lors de la mise à jour",
                            "error"
                        );
                    }
                },
            });
        }
    );
    $(document).on("submit", "#edit-projet-form", function (e) {
        e.preventDefault();
        const form = $(this);
        const url = form.attr("action");
        $(".is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").text("");

        $.ajax({
            url: url,
            type: "POST",
            data: form.serialize(),
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success) {
                    $("#editProjetModal").modal("hide");
                    dt_projet.ajax.reload(null, false);
                    successSound.play();
                    Swal.fire("Succès!", response.message, "success");
                }
            },
            error: function (xhr) {
                console.log(
                    "Edit form submission error:",
                    xhr.status,
                    xhr.responseText
                );
                errorSound.play();
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function (key) {
                        const field = $("#edit_" + key);
                        field.addClass("is-invalid");
                        field
                            .siblings(".invalid-feedback")
                            .text(errors[key][0]);
                    });
                } else {
                    Swal.fire(
                        "Erreur!",
                        xhr.responseJSON?.message || "Une erreur est survenue",
                        "error"
                    );
                }
            },
        });
    });
$(document).on("click", ".upload-documents", function () {
    const projetId = $(this).data("id");
    $("#document_projet_id").val(projetId);
    $("#upload-documents-form")[0].reset();
    $(".is-invalid").removeClass("is-invalid");
    $(".invalid-feedback").text("");

    $(".existing-file").remove();

    console.log("Clicked upload-documents for projetId:", projetId);

    // Fetch project details to determine type
    $.ajax({
        url: `/projets/${projetId}`,
        type: "GET",
        success: function (response) {
            console.log("Project details response:", response);
            if (response.success && response.data) {
                const project = response.data;
                console.log(
                    "Project type:",
                    project.type_projet,
                    "Commande type:",
                    project.commande_type
                );

                if (project.type_projet === "Public") {
                    console.log("Setting up public project modal");
                    $("#document_projet_id").val(projetId);
                    $("#upload-documents-form")[0].reset();
                    $(".is-invalid").removeClass("is-invalid");
                    $(".invalid-feedback").text("");
                    $(".existing-file").remove();

                    // Tous les champs sont maintenant visibles
                    console.log("Showing all fields - BC validation removed");
                    $("#assurance_montant_container").show();
                    $("#assurance_document_container").show();
                    $("#demande_cautionnement_container").show();
                    $("#caution_provision_document_container").show();
                    $("#caution_provision_container").show();
                    $("#caution_definitif_document_container").show();
                    $("#caution_definitif_container").show();

                    // Fetch existing documents
                    $.ajax({
                        url: `/projets/${projetId}/documents`,
                        type: "GET",
                        success: function (docResponse) {
                            console.log("Public documents response:", docResponse);
                            if (docResponse.success && docResponse.data) {
                                const data = docResponse.data;
                                
                                // Debug: Log les valeurs reçues
                                console.log("=== DEBUG FRONTEND RECEIVED DATA ===");
                                console.log("assurance_montant:", data.assurance_montant);
                                console.log("caution_provision:", data.caution_provision);
                                console.log("caution_definitif:", data.caution_definitif);
                                console.log("caution_definitif type:", typeof data.caution_definitif);
                                console.log("===================================");

                                // Populate fields with NEW IDs (prefixed with 'document_')
                                $("#document_assurance_montant").val(data.assurance_montant !== null && data.assurance_montant !== undefined ? data.assurance_montant : "");
                                $("#document_caution_provision").val(data.caution_provision !== null && data.caution_provision !== undefined ? data.caution_provision : "");
                                $("#document_caution_definitif").val(data.caution_definitif !== null && data.caution_definitif !== undefined ? data.caution_definitif : "");

                                // Debug: Verify values are set in the form
                                console.log("=== DEBUG FORM VALUES SET ===");
                                console.log("Form assurance_montant:", $("#document_assurance_montant").val());
                                console.log("Form caution_provision:", $("#document_caution_provision").val());
                                console.log("Form caution_definitif:", $("#document_caution_definitif").val());
                                console.log("=============================");

                                const fileFields = [
                                    {
                                        id: "marche_document",
                                        path: data.marche_document,
                                    },
                                    {
                                        id: "ordre_service_document",
                                        path: data.ordre_service_document,
                                    },
                                    {
                                        id: "assurance_document",
                                        path: data.assurance_document,
                                    },
                                    {
                                        id: "demande_cautionnement",
                                        path: data.demande_cautionnement,
                                    },
                                    {
                                        id: "caution_provision_document",
                                        path: data.caution_provision_document,
                                    },
                                    {
                                        id: "caution_definitif_document",
                                        path: data.caution_definitif_document,
                                    },
                                ];

                                fileFields.forEach((field) => {
                                    if (field.path) {
                                        const fileName = field.path
                                            .split("/")
                                            .pop();
                                        $(`#${field.id}`).after(`
                                        <div class="existing-file mt-2">
                                            <a href="${field.path}" target="_blank" class="text-primary">
                                                ${fileName}
                                            </a>
                                            <small class="text-muted"> (Fichier existant, sélectionnez un nouveau fichier pour remplacer)</small>
                                        </div>
                                    `);
                                    }
                                });
                            }
                            console.log("Showing uploadDocumentsModal");
                            $("#uploadDocumentsModal").modal("show");
                        },
                        error: function (xhr) {
                            console.error("Error fetching public documents:", xhr);
                            Swal.fire(
                                "Erreur!",
                                "Échec de la récupération des documents.",
                                "error"
                            );
                            $("#uploadDocumentsModal").modal("show");
                        },
                    });
                } else if (project.type_projet === "Privé") {
                    // Logique pour les projets privés (inchangée)
                    console.log("Setting up private project modal");
                    $("#private_document_projet_id").val(projetId);
                    $("#upload-private-documents-form")[0].reset();
                    $(".is-invalid").removeClass("is-invalid");
                    $(".invalid-feedback").text("");
                    $("#existing_private_documents").empty();

                    $.ajax({
                        url: `/projets/${projetId}/documents`,
                        type: "GET",
                        success: function (docResponse) {
                            console.log("Private documents response:", docResponse);
                            if (
                                docResponse.success &&
                                docResponse.data &&
                                docResponse.data.private_documents
                            ) {
                                let docsHtml =
                                    "<strong>Documents existants:</strong><ul>";
                                docResponse.data.private_documents.forEach(
                                    (doc) => {
                                        docsHtml += `<li><a href="${doc.path}" target="_blank">${doc.name}</a></li>`;
                                    }
                                );
                                docsHtml += "</ul>";
                                $("#existing_private_documents").html(
                                    docsHtml
                                );
                            }
                            console.log("Showing uploadPrivateDocumentsModal");
                            $("#uploadPrivateDocumentsModal").modal("show");
                        },
                        error: function (xhr) {
                            console.error("Error fetching private documents:", xhr);
                            Swal.fire(
                                "Erreur!",
                                "Impossible de charger les documents existants.",
                                "error"
                            );
                            $("#uploadPrivateDocumentsModal").modal("show");
                        },
                    });
                } else {
                    console.error("Unknown project type:", project.type_projet);
                    Swal.fire("Erreur!", "Type de projet inconnu.", "error");
                }
            } else {
                console.error("Project not found:", response);
                Swal.fire("Erreur!", "Projet non trouvé.", "error");
            }
        },
        error: function (xhr) {
            console.error("Error fetching project details:", xhr);
            Swal.fire(
                "Erreur!",
                "Erreur lors de la récupération du projet.",
                "error"
            );
        },
    });
});
    $(document).on("click", ".delete-ordre-pair", function () {
        const projetId = $(this).data("projet-id");
        const pairIndex = $(this).data("pair-index");
        console.log("Deleting pair:", { projetId, pairIndex }); // Log pour vérifier les valeurs

        Swal.fire({
            title: "Confirmer la suppression?",
            text: "Cette action supprimera l'arrêt et la reprise associés. Cette action est irréversible!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Supprimer",
            cancelButtonText: "Annuler",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/projets/${projetId}/ordres-service/${pairIndex}`,
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                            "content"
                        ),
                    },
                    success: function (response) {
                        console.log("Delete response:", response); // Log pour vérifier la réponse
                        if (response.success) {
                            // Refresh the ordres list
                            $.ajax({
                                url: `/projets/${projetId}/ordres-service`,
                                type: "GET",
                                success: function (response) {
                                    console.log("Refresh response:", response); // Log pour vérifier la réponse
                                    if (response.success && response.data) {
                                        let statsHtml = `
                                        <div class="alert alert-info">
                                            <h6>Statistiques des Ordres de Service</h6>
                                            <p><strong>Nombre d'ordres d'arrêt :</strong> ${response.stop_count}</p>
                                            <p><strong>Nombre d'ordres de reprise :</strong> ${response.resume_count}</p>
                                            <p><strong>Ordres d'arrêt nets (Arrêt - Reprise) :</strong> ${response.net_stop_orders}</p>
                                            <p><strong>Total des jours d'arrêt :</strong> ${response.total_stop_days} jours</p>
                                        </div>`;
                                        $("#ordres-service-stats").html(
                                            statsHtml
                                        );

                                        let html = `
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Type Arrêt</th>
                                                    <th>Date Arrêt</th>
                                                    <th>Document Arrêt</th>
                                                    <th>Type Reprise</th>
                                                    <th>Date Reprise</th>
                                                    <th>Document Reprise</th>
                                                    <th>Nombre de Jours</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>`;
                                        response.data.forEach(function (
                                            pair,
                                            index
                                        ) {
                                            html += `
                                            <tr>
                                                <td>${
                                                    pair.type_arret === "arret"
                                                        ? "Arrêt"
                                                        : "-"
                                                }</td>
                                                <td>${
                                                    pair.date_arret || "-"
                                                }</td>
                                                <td>${
                                                    pair.document_arret
                                                        ? `<a href="${pair.document_arret}" target="_blank">Télécharger</a>`
                                                        : "Aucun"
                                                }</td>
                                                <td>${
                                                    pair.type_reprise ===
                                                    "reprise"
                                                        ? "Reprise"
                                                        : "-"
                                                }</td>
                                                <td>${
                                                    pair.date_reprise || "-"
                                                }</td>
                                                <td>${
                                                    pair.document_reprise
                                                        ? `<a href="${pair.document_reprise}" target="_blank">Télécharger</a>`
                                                        : "Aucun"
                                                }</td>
                                                <td>${pair.nbrjour || "-"}</td>
                                                <td>
                                                    <a href="javascript:;" class="text-danger delete-ordre-pair" 
                                                       data-projet-id="${projetId}" 
                                                       data-pair-index="${index}" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Supprimer">
                                                        <i class="bx bx-trash mx-1"></i>
                                                    </a>
                                                </td>
                                            </tr>`;
                                        });
                                        html += `</tbody></table>`;
                                        $("#ordres-service-list").html(html);
                                        $(
                                            '[data-bs-toggle="tooltip"]'
                                        ).tooltip();
                                    } else {
                                        $("#ordres-service-stats").html(
                                            "<p>Aucune statistique disponible.</p>"
                                        );
                                        $("#ordres-service-list").html(
                                            '<p class="text-center">Aucune paire d\'ordres trouvée.</p>'
                                        );
                                    }
                                },
                                error: function (xhr) {
                                    console.error(
                                        "Refresh AJAX Error:",
                                        xhr.responseText
                                    );
                                    Swal.fire(
                                        "Erreur!",
                                        xhr.responseJSON?.message ||
                                            "Échec de la récupération des ordres de service",
                                        "error"
                                    );
                                    errorSound.play();
                                },
                            });
                            successSound.play();
                            Swal.fire(
                                "Succès!",
                                "Nous avons supprimé l'arrêt et la reprise",
                                "success"
                            );
                        }
                    },
                    error: function (xhr) {
                        console.error(
                            "Delete ordre pair AJAX error:",
                            xhr.responseText
                        ); // Log pour vérifier l'erreur
                        errorSound.play();
                        Swal.fire(
                            "Erreur!",
                            xhr.responseJSON?.message ||
                                "Échec de la suppression des ordres",
                            "error"
                        );
                    },
                });
            }
        });
    });
$("#upload-documents-form").on("submit", function (e) {
    e.preventDefault();
    $("#loadingModal").modal("show");
    var formData = new FormData(this);
    var projetId = $("#document_projet_id").val();

    $.ajax({
        url: $(this).attr("action"),
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (response) {
            $("#loadingModal").modal("hide");
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "Succès",
                    text: response.message,
                });
                $("#uploadDocumentsModal").modal("hide");
                $("#upload-documents-form")[0].reset();

                // Vérifier l'état des documents pour mettre à jour dossier_complete
                $.ajax({
                    url: `/projets/${projetId}/check-dossier`,
                    type: "GET",
                    data: { commande_type: response.commande_type || "Marche" },
                    success: function (checkResponse) {
                        if (checkResponse.files_exist) {
                            // Mettre à jour la ligne dans DataTable
                            let row = dt_projet.row(
                                (index, data) => data.id === projetId
                            );
                            let rowData = row.data();
                            rowData.dossier_complete = true; // Mettre à jour la propriété
                            row.data(rowData).draw(false); 
                        }
                    },
                    error: function (xhr) {
                        console.error("Erreur lors de la vérification des documents:", xhr);
                    },
                });

                dt_projet.ajax.reload(null, false); 
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: response.message,
                });
            }
        },
        error: function (xhr) {
            $("#loadingModal").modal("hide");
            var errors = xhr.responseJSON?.errors || {};
            var errorMessage = "Une erreur est survenue.";
            if (Object.keys(errors).length) {
                errorMessage = Object.values(errors)[0][0];
            }
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: errorMessage,
            });
        },
    });
});

    $("#upload-private-documents-form").on("submit", function (e) {
        e.preventDefault();
        $("#loadingModal").modal("show");
        var formData = new FormData(this);
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                $("#loadingModal").modal("hide");
                if (response.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Succès",
                        text: response.message,
                    });
                    $("#uploadPrivateDocumentsModal").modal("hide");
                    loadExistingPrivateDocuments(
                        $("#private_document_projet_id").val()
                    );
                    projetsTable.ajax.reload();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: response.message,
                    });
                }
            },
            error: function (xhr) {
                $("#loadingModal").modal("hide");
                var errors = xhr.responseJSON?.errors || {};
                var errorMessage = "Une erreur est survenue.";
                if (Object.keys(errors).length) {
                    errorMessage = Object.values(errors)[0];
                }
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: errorMessage,
                });
            },
        });
    });

    function loadExistingPrivateDocuments(projetId) {
        $.ajax({
            url: `/projets/${projetId}/documents`,
            type: "GET",
            success: function (response) {
                var html = "";
                if (
                    response.success &&
                    response.data?.private_documents?.length
                ) {
                    response.data.private_documents.forEach(function (doc) {
                        html += `<p><a href="${doc.path}" target="_blank">${doc.name}</a></p>`;
                    });
                } else {
                    html = "<p>Aucun document privé existant.</p>";
                }
                $("#existing_private-docs").html(html);
            },
            error: function () {
                $("#existing_private-docs").html(
                    "<p>Erreur lors du chargement des documents.</p>"
                );
            },
        });
    }
    $(document).on("click", ".gérer-ordres-service", function () {
        const projetId = $(this).data("id");
        console.log("Fetching ordres for projetId:", projetId);
        $("#ordres_service_projet_id").val(projetId);
        $("#ordres-service-form")[0].reset();
        $("#ordres-service-list").html(
            "<p>Chargement des ordres de service...</p>"
        );
        $("#ordres-service-stats").html(
            "<p>Chargement des statistiques...</p>"
        );

        $.ajax({
            url: `/projets/${projetId}/ordres-service`,
            type: "GET",
            success: function (response) {
                console.log("Response:", response);
                if (response.success && response.data) {
                    // Afficher les statistiques
                    let statsHtml = `
                    <div class="alert alert-info">
                        <h6>Statistiques des Ordres de Service</h6>
                        <p><strong>Nombre d'ordres d'arrêt :</strong> ${response.stop_count}</p>
                        <p><strong>Nombre d'ordres de reprise :</strong> ${response.resume_count}</p>
                        <p><strong>Ordres d'arrêt nets (Arrêt - Reprise) :</strong> ${response.net_stop_orders}</p>
                        <p><strong>Total des jours d'arrêt :</strong> ${response.total_stop_days} jours</p>
                    </div>`;
                    $("#ordres-service-stats").html(statsHtml);

                    // Afficher les paires avec bouton Supprimer conditionnel
                    if (response.data.length > 0) {
                        let html = `
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Type Arrêt</th>
                                    <th>Date Arrêt</th>
                                    <th>Document Arrêt</th>
                                    <th>Type Reprise</th>
                                    <th>Date Reprise</th>
                                    <th>Document Reprise</th>
                                    <th>Nombre de Jours</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>`;
                        response.data.forEach(function (pair, index) {
                            // Vérifier si date_reprise existe et n'est pas vide
                            const hasDateReprise =
                                pair.date_reprise && pair.date_reprise !== "-";
                            html += `
                            <tr>
                                <td>${
                                    pair.type_arret === "arret" ? "Arrêt" : "-"
                                }</td>
                                <td>${pair.date_arret || "-"}</td>
                                <td>${
                                    pair.document_arret
                                        ? `<a href="${pair.document_arret}" target="_blank">Télécharger</a>`
                                        : "Aucun"
                                }</td>
                                <td>${
                                    pair.type_reprise === "reprise"
                                        ? "Reprise"
                                        : "-"
                                }</td>
                                <td>${pair.date_reprise || "-"}</td>
                                <td>${
                                    pair.document_reprise
                                        ? `<a href="${pair.document_reprise}" target="_blank">Télécharger</a>`
                                        : "Aucun"
                                }</td>
                                <td>${pair.nbrjour || "-"}</td>
                                <td>
                                    ${
                                        hasDateReprise
                                            ? `
                                            <a href="javascript:;" class="text-danger delete-ordre-pair" 
                                               data-projet-id="${projetId}" 
                                               data-pair-index="${index}" 
                                               data-bs-toggle="tooltip" 
                                               title="Supprimer">
                                                <i class="bx bx-trash mx-1"></i>
                                            </a>`
                                            : "-"
                                    }
                                </td>
                            </tr>`;
                        });
                        html += `</tbody></table>`;
                        $("#ordres-service-list").html(html);
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    } else {
                        $("#ordres-service-list").html(
                            '<p class="text-center">Aucune paire d\'ordres trouvée.</p>'
                        );
                    }
                } else {
                    $("#ordres-service-stats").html(
                        "<p>Aucune statistique disponible.</p>"
                    );
                    $("#ordres-service-list").html(
                        '<p class="text-center">Aucune paire d\'ordres trouvée.</p>'
                    );
                }
                $("#manageOrdresServiceModal").modal("show");
            },
            error: function (xhr) {
                console.error("AJAX Error:", xhr.responseText);
                Swal.fire(
                    "Erreur!",
                    xhr.responseJSON?.message ||
                        "Échec de la récupération des ordres de service",
                    "error"
                );
                errorSound.play();
                $("#ordres-service-stats").html(
                    "<p>Erreur lors du chargement des statistiques.</p>"
                );
                $("#ordres-service-list").html(
                    "<p>Erreur lors du chargement des ordres de service.</p>"
                );
                $("#manageOrdresServiceModal").modal("show");
            },
        });
    });

    $("#ordres-service-form").on("submit", function (e) {
        e.preventDefault();
        let isValid = true;

        $(".is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").text("");

        const typeInput = $("#type");
        const dateInput = $("#date_ordre");
        const fileInput = $("#document_path");

        if (!typeInput.val()) {
            typeInput.addClass("is-invalid");
            typeInput
                .siblings(".invalid-feedback")
                .text("Veuillez sélectionner un type.");
            isValid = false;
        }

        if (!dateInput.val()) {
            dateInput.addClass("is-invalid");
            dateInput
                .siblings(".invalid-feedback")
                .text("Veuillez sélectionner une date.");
            isValid = false;
        }

        if (
            fileInput[0].files.length > 0 &&
            !fileInput[0].files[0].type.includes("pdf")
        ) {
            fileInput.addClass("is-invalid");
            fileInput
                .siblings(".invalid-feedback")
                .text("Veuillez sélectionner un fichier PDF.");
            isValid = false;
        }

        if (isValid) {
            const formData = new FormData(this);
            const submitButton = $(this).find("button[type='submit']");
            $("#loadingModal").modal("show"); // Afficher le modal de chargement
            submitButton
                .prop("disabled", true)
                .html(
                    '<i class="bx bx-loader bx-spin me-1"></i> Chargement...'
                );

            $.ajax({
                url: $(this).attr("action"),
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content"
                    ),
                },
                success: function (response) {
                    if (response.success) {
                        $("#ordres-service-form")[0].reset();
                        // Recharger la liste des ordres de service
                        $.ajax({
                            url: `/projets/${$(
                                "#ordres_service_projet_id"
                            ).val()}/ordres-service`,
                            type: "GET",
                            success: function (response) {
                                if (response.success && response.data) {
                                    let statsHtml = `
                                    <div class="alert alert-info">
                                        <h6>Statistiques des Ordres de Service</h6>
                                        <p><strong>Nombre d'ordres d'arrêt :</strong> ${response.stop_count}</p>
                                        <p><strong>Nombre d'ordres de reprise :</strong> ${response.resume_count}</p>
                                        <p><strong>Ordres d'arrêt nets (Arrêt - Reprise) :</strong> ${response.net_stop_orders}</p>
                                        <p><strong>Total des jours d'arrêt :</strong> ${response.total_stop_days} jours</p>
                                    </div>`;
                                    $("#ordres-service-stats").html(statsHtml);

                                    let html = `
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Type Arrêt</th>
                                                <th>Date Arrêt</th>
                                                <th>Document Arrêt</th>
                                                <th>Type Reprise</th>
                                                <th>Date Reprise</th>
                                                <th>Document Reprise</th>
                                                <th>Nombre de Jours</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                                    response.data.forEach(function (
                                        pair,
                                        index
                                    ) {
                                        const hasDateReprise =
                                            pair.date_reprise &&
                                            pair.date_reprise !== "-";
                                        html += `
                                        <tr>
                                            <td>${
                                                pair.type_arret === "arret"
                                                    ? "Arrêt"
                                                    : "-"
                                            }</td>
                                            <td>${pair.date_arret || "-"}</td>
                                            <td>${
                                                pair.document_arret
                                                    ? `<a href="${pair.document_arret}" target="_blank">Télécharger</a>`
                                                    : "Aucun"
                                            }</td>
                                            <td>${
                                                pair.type_reprise === "reprise"
                                                    ? "Reprise"
                                                    : "-"
                                            }</td>
                                            <td>${pair.date_reprise || "-"}</td>
                                            <td>${
                                                pair.document_reprise
                                                    ? `<a href="${pair.document_reprise}" target="_blank">Télécharger</a>`
                                                    : "Aucun"
                                            }</td>
                                            <td>${pair.nbrjour || "-"}</td>
                                            <td>${
                                                hasDateReprise
                                                    ? `<a href="javascript:;" class="text-danger delete-ordre-pair" data-projet-id="${$(
                                                          "#ordres_service_projet_id"
                                                      ).val()}" data-pair-index="${index}" data-bs-toggle="tooltip" title="Supprimer"><i class="bx bx-trash mx-1"></i></a>`
                                                    : "-"
                                            }</td>
                                        </tr>`;
                                    });
                                    html += `</tbody></table>`;
                                    $("#ordres-service-list").html(html);
                                    $('[data-bs-toggle="tooltip"]').tooltip();
                                } else {
                                    $("#ordres-service-stats").html(
                                        "<p>Aucune statistique disponible.</p>"
                                    );
                                    $("#ordres-service-list").html(
                                        '<p class="text-center">Aucune paire d\'ordres trouvée.</p>'
                                    );
                                }
                            },
                            error: function (xhr) {
                                console.error("AJAX Error:", xhr.responseText);
                                Swal.fire(
                                    "Erreur!",
                                    xhr.responseJSON?.message ||
                                        "Échec de la récupération des ordres de service",
                                    "error"
                                );
                                errorSound.play();
                            },
                        });
                        successSound.play();
                        Swal.fire("Succès!", response.message, "success");
                    }
                },
                error: function (xhr) {
                    console.error(
                        "Erreur lors de l'ajout de l'ordre de service:",
                        xhr
                    );
                    errorSound.play();
                    Swal.fire(
                        "Erreur!",
                        xhr.responseJSON?.message ||
                            "Erreur lors de l'ajout de l'ordre de service",
                        "error"
                    );
                },
                complete: function () {
                    submitButton
                        .prop("disabled", false)
                        .html(
                            '<i class="bx bx-check me-1"></i><span class="align-middle">Ajouter</span>'
                        );
                    $("#loadingModal").modal("hide"); // Cacher le modal une fois la requête terminée
                },
            });
        }
    });
    function printShowProjet(data) {
        // Ensure companySettings is defined
      const companySettings = data.companySettings || window.companySettings || {};

        // Define logoUrl
        const logoUrl =
            companySettings && companySettings.logo
                ? `${window.location.origin}/storage/${companySettings.logo}`
                : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;
        if (!data || Object.keys(data).length === 0) {
            const emptyHtml = `
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <title>Détails du Projet</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20mm; }
 Scouts - margin-bottom: 20px; }
                    .header { display: flex; align-items: center; border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; }
                    .header img { max-width: 100px; margin-right: 20px; }
                    .company-info { font-size: 12px; line-height: 1.5; position: fixed; bottom: 10mm; width: 100%; text-align: center; }
                    .company-info p { margin: 2px 0; }
                    .title-section { margin-bottom: 20px; text-align: center; }
                    .title-section h1 { font-size: 24px; margin: 0; color: #0056b3; }
                    .title-section p { font-size: 14px; margin: 5px 0; }
                    p { font-size: 12px; }
                    @media print {
                        .company-info { position: fixed; bottom: 0; width: 100%; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <img src="${logoUrl}" alt="Logo" />
                </div>
                <div class="title-section">
                    <h1>Détails du Projet</h1>
                    <p>Date: ${new Date().toLocaleDateString()}</p>
                </div>
                <p>Aucune donnée disponible pour ce projet.</p>
                <div class="company-info">
                    <p>Siège social: ${
                        companySettings.address || "Non spécifié"
                    }  Capital: ${
                companySettings.capital
                    ? companySettings.capital.toLocaleString("fr-FR", {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                      }) + " MAD"
                    : "Non spécifié"
            } | Tél: ${companySettings.phone_number || "Non spécifié"} </p>
                    <p>R.C.: ${
                        companySettings.commercial_register || "Non spécifié"
                    } | CNSS: ${
                companySettings.cnss_number || "Non spécifié"
            } | IF: ${companySettings.tax_id || "Non spécifié"} TP: ${
                companySettings.tax_id || "Non spécifique"
            } ICE: ${companySettings.patent_number || "Non spécifique"}</p>
                    <p>C.B.: ${
                        companySettings.account_number || "Non spécifié"
                    }, ${companySettings.bank_name || "Non spécifié"} Email: ${
                companySettings.email || "Non spécifié"
            } </p>
                </div>
            </body>
            </html>`;
            printContent(emptyHtml, "Détails du Projet");
            return;
        }

        // Définir les colonnes du tableau principal
        const columns = {
            cols: [
                "intitule",
                "num_p",
                "date_debut",
                "date_fin",
                "type_projet",
                "budget",
            ],
            headers: [
                "Intitulé",
                "Numéro Projet",
                "Ordre de Service",
                "Date Fin",
                "Type Projet",
                "Budget",
            ],
        };

        // Créer la ligne du tableau principal
        const tableContent = `
        <tr>
            ${columns.cols
                .map((col) => `<td>${data[col] || "-"}</td>`)
                .join("")}
        </tr>`;

        // Préparer les ordres de service
        let ordresTable = "";
        if (data.ordres_service && data.ordres_service.length > 0) {
            const sortedOrdres = data.ordres_service.sort(
                (a, b) => new Date(a.date_ordre) - new Date(b.date_ordre)
            );
            const stops = sortedOrdres.filter(
                (ordre) => ordre.type === "arret"
            );
            const resumes = sortedOrdres.filter(
                (ordre) => ordre.type === "reprise"
            );

            ordresTable = `
            <h3>Ordres de Service</h3>
            <table>
                <thead>
                    <tr>
                        <th>Type Arrêt</th>
                        <th>Date Arrêt</th>
                        <th>Document Arrêt</th>
                        <th>Type Reprise</th>
                        <th>Date Reprise</th>
                        <th>Document Reprise</th>
                        <th>Nombre de Jours</th>
                    </tr>
                </thead>
                <tbody>
                    ${stops
                        .map((stop, index) => {
                            const resume = resumes[index] || null;
                            return `
                                <tr>
                                    <td>${
                                        stop.type === "arret" ? "Arrêt" : "-"
                                    }</td>
                                    <td>${stop.date_ordre || "-"}</td>
                                    <td>${
                                        stop.document_path ? "Oui" : "Non"
                                    }</td>
                                    <td>${resume ? "Reprise" : "-"}</td>
                                    <td>${
                                        resume && resume.date_ordre
                                            ? resume.date_ordre
                                            : "-"
                                    }</td>
                                    <td>${
                                        resume && resume.document_path
                                            ? "Oui"
                                            : "Non"
                                    }</td>
                                    <td>${stop.nbrjour || "-"}</td>
                                </tr>`;
                        })
                        .join("")}
                </tbody>
            </table>
        `;
        } else {
            ordresTable = "<p>Aucun ordre de service associé.</p>";
        }

        // Préparer les salariés
        let salariesTable = "";
        if (data.salaries && data.salaries.length > 0) {
            salariesTable = `
            <h3>Salariés</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Rôle</th>
                        <th>Fonction</th>
                        <th>Date Intégration</th>
                        <th>Date Sortie</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.salaries
                        .map(
                            (salarie) => `
                                <tr>
                                    <td>${salarie.nom || "-"}</td>
                                    <td>${salarie.prenom || "-"}</td>
                                    <td>${salarie.role || "-"}</td>
                                    <td>${salarie.fonction || "-"}</td>
                                    <td>${salarie.date_integration || "-"}</td>
                                    <td>${salarie.date_sortie || "-"}</td>
                                </tr>
                            `
                        )
                        .join("")}
                </tbody>
            </table>
        `;
        } else {
            salariesTable = "<p>Aucun salarié affecté.</p>";
        }

        // HTML pour l'impression
        const html = `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Détails du Projet</title>
            <style>
                body { font-family: Arial; margin: 10mm; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .header { display: flex; align-items: center; border-bottom: 2px solid #000; margin-bottom: 20px; }
                .header img { max-width: 80px; margin-right: 20px; margin-bottom: 10px; }
                .company-info { font-size: 12px; line-height: 1.2; position: fixed; bottom: 10mm; width: 100%; text-align: center; }
                .company-info p { margin: 2px 0; }
                .title-section { margin-bottom: 15px; }
                .title-section h1 { font-size: 20px; margin: 0; }
                .title-section p { font-size: 12px; margin: 2px 0; }
                h3 { font-size: 16px; margin: 20px 0 10px; }
                @media print {
                    .company-info { position: fixed; bottom: 0; width: 100%; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="${logoUrl}" alt="Logo" />
            </div>
            <div class="title-section">
                <h1>Détails du Projet</h1>
                <p>Date: ${new Date().toLocaleDateString()}</p>
            </div>
            <h3>Projet</h3>
            <table>
                <tr>${columns.headers
                    .map((header) => `<th>${header}</th>`)
                    .join("")}</tr>
                ${tableContent}
            </table>
            ${ordresTable}
            ${salariesTable}
            <div class="company-info">
                <p>Siège social: ${
                    companySettings.address || "Non spécifié"
                }  Capital: ${
            companySettings.capital
                ? companySettings.capital.toLocaleString("fr-FR", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                  }) + " MAD"
                : "Non spécifié"
        } | Tél: ${companySettings.phone_number || "Non spécifié"} </p>
                <p>R.C.: ${
                    companySettings.commercial_register || "Non spécifié"
                } | CNSS: ${
            companySettings.cnss_number || "Non spécifié"
        } | IF: ${companySettings.tax_id || "Non spécifié"} TP: ${
            companySettings.tax_id || "Non spécifique"
        } ICE: ${companySettings.patent_number || "Non spécifique"}</p>
                <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${
            companySettings.bank_name || "Non spécifié"
        } Email: ${companySettings.email || "Non spécifié"} </p>
            </div>
        </body>
        </html>`;
        printContent(html, `Détails du Projet - ${data.intitule}`);
    }

    // Function to handle delete AJAX call

    document
        .getElementById("confirmAffectationBtn")
        .addEventListener("click", function () {
            const confirmModal = bootstrap.Modal.getInstance(
                document.getElementById("confirmAffectationModal")
            );
            const projetId = $("#projet_id").val();

            if (!projetId) {
                Swal.fire("Erreur!", "ID du projet manquant.", "error");
                errorSound.play();
                return;
            }

            $("#projet_id").val(projetId);
            $("#responsable")
                .empty()
                .append(
                    '<option value="">Sélectionner un responsable</option>'
                );
            $("#ouvriers").empty();
            $("#responsable").append('<option value="">Chargement...</option>');
            $("#ouvriers").append('<option value="">Chargement...</option>');

            $.ajax({
                url: `${$("#projetsTable").data(
                    "projets-base-url"
                )}/${projetId}/affectation-data`,
                type: "GET",
                success: function (response) {
                    $("#responsable")
                        .empty()
                        .append(
                            '<option value="">Sélectionner un responsable</option>'
                        );
                    $("#ouvriers").empty();

                    response.responsables.forEach(function (salarie) {
                        let text = `${salarie.nom} ${salarie.prenom}`;
                        if (salarie.fonction && salarie.fonction.designation) {
                            text += ` (${salarie.fonction.designation})`;
                        }
                        if (salarie.pivot && salarie.pivot.date_sortie) {
                            text += ` [Sorti: ${new Date(
                                salarie.pivot.date_sortie
                            ).toLocaleDateString()}]`;
                        }
                        const option = new Option(
                            text,
                            salarie.id,
                            false,
                            salarie.is_assigned &&
                                salarie.pivot &&
                                salarie.pivot.role === "responsable"
                        );
                        $("#responsable").append(option);
                    });

                    response.ouvriers.forEach(function (salarie) {
                        let text = `${salarie.nom} ${salarie.prenom}`;
                        if (salarie.fonction && salarie.fonction.designation) {
                            text += ` (${salarie.fonction.designation})`;
                        }
                        if (salarie.pivot && salarie.pivot.date_sortie) {
                            text += ` [Sorti: ${new Date(
                                salarie.pivot.date_sortie
                            ).toLocaleDateString()}]`;
                        }
                        const option = new Option(
                            text,
                            salarie.id,
                            false,
                            salarie.is_assigned &&
                                salarie.pivot &&
                                salarie.pivot.role === "ouvrier"
                        );
                        $("#ouvriers").append(option);
                    });

                    $("#responsable").select2({
                        dropdownParent: $("#affectationModal"),
                        placeholder: "Sélectionner un responsable",
                        allowClear: true,
                    });

                    $("#ouvriers").select2({
                        dropdownParent: $("#affectationModal"),
                        placeholder: "Sélectionner des ouvriers",
                        allowClear: true,
                        multiple: true,
                    });

                    confirmModal.hide();
                    const affectationModal = new bootstrap.Modal(
                        "#affectationModal"
                    );
                    affectationModal.show();
                },
                error: function (xhr) {
                    console.error("AJAX Error:", xhr);
                    let errorMessage =
                        "Échec du chargement des données d'affectation.";
                    Swal.fire("Erreur!", errorMessage, "error");
                    errorSound.play();
                },
            });
        });

    if ($.fn.select2) {
        $("#responsable, #ouvriers").select2({
            dropdownParent: $("#affectationModal"),
            placeholder: "Sélectionner...",
            allowClear: true,
        });
    }
});
