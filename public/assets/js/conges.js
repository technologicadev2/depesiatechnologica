$(document).ready(function () {
    console.log("conges.js loaded"); // Debug: Confirm script loading

    const table = $("#congesTable");

    // Vérifier si l'élément table existe
    if (!table.length) {
        console.error("L'élément #congesTable n'existe pas dans le DOM");
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "La table des congés n'a pas été trouvée dans la page.",
            timer: 5000,
        });
        return;
    }

    // Initialiser SignaturePad
    const canvas = document.getElementById("signaturePad");
    if (!canvas) {
        console.error("L'élément #signaturePad n'existe pas dans le DOM");
        return;
    }
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: "rgb(255, 255, 255)",
        penColor: "rgb(0, 0, 0)",
    });

    // Ajuster la taille du canvas lorsque le modal s'ouvre
    $("#modalConge").on("shown.bs.modal", function () {
        console.log("Modal shown"); // Debug
        const parentWidth = canvas.parentElement.offsetWidth;
        canvas.width = parentWidth;
        canvas.height = 150;
        signaturePad.clear();
    });

    // Bouton pour effacer la signature
    $("#clearSignature").on("click", function () {
        console.log("Clear signature clicked"); // Debug
        signaturePad.clear();
        $("#signatureInput").val("");
    });

    try {
        // Initialiser DataTable
   const dataTable = table.DataTable({
        columns: [
            { orderable: true, searchable: true }, // Date de début
            { orderable: true, searchable: true },
            { orderable: true, searchable: true }, // Nombre de jours
            { orderable: true, searchable: true }, // Jours restants
            { orderable: true, searchable: true }, // Statut
            { orderable: false, searchable: false }, // Actions
        ],
        ordering: false,
        columnDefs: [
            { targets: [0, 1, 2, 3, 4], render: (data) => data || "-" },
        ],
        language: {
            lengthMenu: "Afficher _MENU_ congés",
            search: "",
            searchPlaceholder: "Rechercher un congé",
            paginate: { next: "Suivant", previous: "Précédent" },
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            emptyTable: "Aucun congé trouvé dans la base de données.",
        },
        dom: '<"dt-header-controls d-flex justify-content-between align-items-center mb-3"Bf>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
            {
                text: '<i class="bx bx-plus"></i><span class="d-md-inline-block d-none ms-1">Demander un congé</span>',
                className: "btn btn-primary me-2",
                action: function () {
                    console.log("Demander un congé clicked");
                    $("#date_debut").val("");
                    $("#nombre_jours").val("");
                    $("#raison").val("");
                    $("#acceptTerms").prop("checked", false);
                    $("#signatureInput").val("");
                    signaturePad.clear();
                    $("#modalConge").modal("show");
                },
            },
            {
                extend: "collection",
                className: "btn btn-label-primary dropdown-toggle me-2",
                text: '<i class="bx bx-export"></i><span class="ms-1">Exporter</span>',
                buttons: [
                    {
                        text: '<i class="bx bx-printer"></i> Imprimer',
                        action: function (e, dt, node, config) {
                            console.log("Print button clicked");
                            printCongesTable(dt, "Liste sahi des Congés", {
                                cols: [
                                    "date_debut",
                                    "num_j",
                                    "n_jours_reste",
                                    "approbation",
                                ],
                                headers: [
                                    "Date de début",
                                    "Date de fin",
                                    "Nombre de jours",
                                    "Jours restants",
                                    "Statut",
                                ],
                            });
                        },
                    },
                    {
                        extend: "excel",
                        text: '<i class="bx bxs-file-export"></i> Excel',
                        exportOptions: { columns: [0, 1, 2, 3] },
                    },
                    {
                        extend: "pdf",
                        text: '<i class="bx bxs-file-pdf"></i> PDF',
                        exportOptions: { columns: [0, 1, 2, 3] },
                    },
                ],
            },
        ],
        responsive: true,
        deferLoading: 0,
        orderCellsTop: true,
        initComplete: function () {
            console.log("DataTable initialized successfully");
            // Customize the search input
            const searchInput = $(".dataTables_filter input");
            searchInput.attr("placeholder", "Rechercher un congé").addClass("form-control");
            // Remove the default "Search:" label
            $(".dataTables_filter label").contents().filter(function () {
                return this.nodeType === 3; // Remove text nodes
            }).remove();
        },
        drawCallback: function () {
            console.log("DataTable redrawn");
        },
    });

        // Fonction pour imprimer la table
        function printCongesTable(table, title, columns) {
            const logoUrl =
                companySettings && companySettings.logo
                    ? `${window.location.origin}/storage/${companySettings.logo}`
                    : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;
            console.log("Printing table"); // Debug
            const data = table
                .rows({ search: "applied" })
                .nodes()
                .toArray()
                .map((row) => {
                    return [
                        $(row).find("td").eq(0).text(), // Date de début
                        $(row).find("td").eq(1).text(), // Date fin
                        $(row).find("td").eq(1).text(), // Nombre de jours
                        $(row).find("td").eq(2).text(), // Jours restants
                        $(row).find("td").eq(3).text(), // Statut
                    ];
                })
                .filter((row) => row[3] === "Accepté"); // Filter for Approbation == "Accepté"

            // Pagination: Split data into pages with 8 rows each
            const rowsPerPage = 8;
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
                        <td>${row[0] || "-"}</td>
                        <td>${row[1] || "-"}</td>
                        <td>${row[2] || "-"}</td>
                        <td>${row[3] || "-"}</td>
                        <td>${row[4] || "-"}</td>
                    </tr>`
                        )
                        .join("");
                    return `
                <div class="table-section" style="margin-bottom: 20px; page-break-after: always;">
                    <table>
                        <tr>
                            <th>Date de début</th>
                            <th>Date de fin</th>
                            <th>Nombre de jours</th>
                            <th>Jours restants</th>
                            <th>Approbation</th>
                        </tr>
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
            <title>Impression - ${title}</title>
            <style>
                @page {
                    size: A4;
                    margin: 10mm;
                    @top-center {
                        content: "${title}";
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                        color: #2c3e50;
                    }
                    @bottom-center {
                        content: "Page " counter(page) " de " counter(pages);
                        font-family: Arial, sans-serif;
                        font-size: 10px;
                        color: #7f8c8d;
                    }
                }
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 10mm; 
                    color: #333; 
                    line-height: 1.5; 
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
                    max-width: 80px; 
                    margin-right: 20px; 
                    margin-bottom: 10px; 
                }
                .company-info { 
                    font-size: 12px; 
                    line-height: 1.2; 
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
                    margin-bottom: 15px; 
                    text-align: center; 
                    page-break-after: avoid; 
                }
                .title-section h1 { 
                    font-size: 20px; 
                    margin: 0; 
                    color: #2c3e50; 
                }
                .title-section p { 
                    font-size: 12px; 
                    margin: 2px 0; 
                    color: #7f8c8d; 
                }
                .table-section {
                    margin-bottom: 20px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 20px; 
                    page-break-inside: auto; 
                    font-size: 11px; 
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 8px; 
                    text-align: left; 
                }
                th { 
                    background-color: #f2f2f2; 
                    font-weight: bold; 
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
                        line-height: 1.2; 
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
                <p>Date: ${new Date().toLocaleDateString("fr-FR", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                })}</p>
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
            try {
                printContent(html, title);
            } catch (e) {
                console.error("Print error:", e); // Debug
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: "Erreur lors de l'impression : " + e.message,
                    timer: 5000,
                });
                const errorSound = new Audio("/assets/audio/error.mp3");
                errorSound
                    .play()
                    .catch((err) => console.log("Erreur audio:", err));
            }
        }
        // Fonction générique pour imprimer
        function printContent(html, title) {
            console.log("Starting print content"); // Debug
            const iframe = $("<iframe>", { style: "display: none;" }).appendTo(
                "body"
            )[0];
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            doc.open();
            doc.write(html);
            doc.close();
            setTimeout(() => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                iframe.contentWindow.onafterprint = () => $(iframe).remove();
                Swal.fire({
                    icon: "success",
                    title: "Succès",
                    text: "Impression lancée !",
                    timer: 5000,
                });
            }, 500);
        }

        // Gérer la soumission du formulaire avec AJAX
        $("#congeForm").on("submit", function (e) {
            e.preventDefault(); // Empêcher la soumission par défaut
            console.log("Form submission triggered"); // Debug

            // Vérifier acceptTerms
            if (!$("#acceptTerms").is(":checked")) {
                console.log("Conditions non acceptées"); // Debug
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: "Vous devez accepter les conditions en cochant \"J'ai lu et j'accepte\".",
                    timer: 5000,
                });
                return;
            }

            // Vérifier la signature
            if (signaturePad.isEmpty()) {
                console.log("Signature manquante"); // Debug
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: "Veuillez fournir une signature.",
                    timer: 5000,
                });
                return;
            }

            // Définir les données de la signature dans l'input caché
            const signatureData = signaturePad.toDataURL("image/png");
            $("#signatureInput").val(signatureData);
            console.log(
                "Signature capturée :",
                signatureData.substring(0, 50) + "..."
            ); // Debug

            // Désactiver le bouton Soumettre
            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop("disabled", true).text("Soumission...");

            // Afficher le modal de chargement
            $("#loadingModal").modal("show");

            // Collecter les données du formulaire
            const formData = new FormData(this);
            // Vérifier que salarie_id est présent
            if (!formData.get("salarie_id")) {
                console.error(
                    "salarie_id manquant dans les données du formulaire"
                );
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: "L'identifiant du salarié est manquant.",
                    timer: 5000,
                });
                $("#loadingModal").modal("hide");
                submitButton.prop("disabled", false).text("Soumettre");
                return;
            }
            console.log(
                "Données du formulaire :",
                Object.fromEntries(formData)
            ); // Debug

            // Envoyer la requête AJAX
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
                beforeSend: function () {
                    console.log("Envoi de la requête AJAX..."); // Debug
                },
                success: function (response) {
                    console.log("Réponse du serveur :", response); // Debug
                    $("#loadingModal").modal("hide"); // Masquer le modal de chargement
                    submitButton.prop("disabled", false).text("Soumettre"); // Réactiver le bouton
                    $("#modalConge").modal("hide"); // Fermer le modal de congé
                    Swal.fire({
                        icon: "success",
                        title: "Succès",
                        text:
                            response.success ||
                            "Votre demande de congé a été soumise avec succès.",
                        timer: 5000,
                    }).then(() => {
                        location.reload(); // Recharger la page
                    });
                },
                error: function (xhr) {
                    console.error("Erreur AJAX :", xhr.responseJSON || xhr); // Debug
                    $("#loadingModal").modal("hide"); // Masquer le modal de chargement
                    submitButton.prop("disabled", false).text("Soumettre"); // Réactiver le bouton
                    const errorMessage =
                        xhr.responseJSON?.message ||
                        "Une erreur est survenue lors de la soumission.";
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: errorMessage,
                        timer: 5000,
                    });
                },
            });
        });
    } catch (e) {
        console.error("DataTable initialization failed:", e);
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Erreur lors de l'initialisation du tableau : " + e.message,
            timer: 5000,
        });
    }
});
