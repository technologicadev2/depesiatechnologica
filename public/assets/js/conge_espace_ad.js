'use strict';

$(document).ready(function () {
    console.log('conge_espace_ad.js loaded');

    const table = $("#congesTable");

    const canvas = document.getElementById("signaturePad");
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: 'rgb(0, 0, 0)'
    });

    $("#modalConge").on("shown.bs.modal", function () {
        console.log('Modal shown');
        const canvas = document.getElementById("signaturePad");
        const parentWidth = canvas.parentElement.offsetWidth;
        canvas.width = parentWidth;
        canvas.height = 100;
        signaturePad.clear();
    });

    $("#clearSignature").on("click", function () {
        console.log('Clear signature clicked');
        signaturePad.clear();
        $("#signatureInput").val("");
    });

    try {
        table.find("thead tr").clone(true).appendTo(table.find("thead"));
        table.find("thead tr:eq(1) th").each(function (i) {
            const title = $(this).text();
            $(this).html(
                '<input type="text" class="form-control form-control-sm" placeholder="Rechercher ' +
                    title +
                    '" />'
            );
            $("input", this).on("keyup change", function () {
                if (table.DataTable().column(i).search() !== this.value) {
                    table.DataTable().column(i).search(this.value).draw();
                }
            });
        });

        const dataTable = table.DataTable({
            columns: [
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: false },
            ],
            columnDefs: [
                { targets: [0, 1, 2, 3, 4, 5, 6, 7, 8,9], render: (data) => data || "-" }
            ],
         
            language: {
                lengthMenu: "Afficher _MENU_ congés",
                search: "Rechercher",
                searchPlaceholder: "Rechercher un congé",
                paginate: { next: "Suivant", previous: "Précédent" },
                info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
                emptyTable: "Aucun congé trouvé dans la base de données."
            },
            dom: '<"dt-header-controls"Bf>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                {
                    text: '<i class="bx bx-plus me-md-1"></i><span class="d-md-inline-block d-none">Demander un congé</span>',
                    className: "btn btn-success",
                    action: function () {
                        console.log('Demander un congé clicked');
                        $("#salarie_id").val("");
                        $("#nom").val("");
                        $("#prenom").val("");
                        $("#email").val("");
                        $("#date_debut").val("");
                        $("#nombre_jours").val("");
                        $("#raison").val("");
                        $("#signatureInput").val("");
                        signaturePad.clear();
                        $("#modalConge").modal("show");
                    },
                },
                {
                    extend: "collection",
                    className: "btn btn-label-primary dropdown-toggle me-2",
                    text: '<i class="bx bx-export me-sm-1"></i> Exporter',
                    buttons: [
                     /*    {
                            text: '<i class="bx bx-printer me-1"></i>Imprimer',
                            action: function (e, dt, node, config) {
                                console.log('Print button clicked');
                                printCongesTable(dt, "Liste des Congés Administratifs", {
                                    cols: ["nom", "prenom", "matricule", "date_debut", "Date de fin", "num_j", "raison", "n_jours_reste", "approbation"],
                                    headers: ["Nom", "Prénom", "Matricule", "Date de début", "Date de fin","Nombre de jours", "raison", "Jours Restants", "Approbation"],
                                });
                            },
                        }, */
                        {
                            extend: "excel",
                            text: '<i class="bx bxs-file-export me-1"></i>Excel',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] },
                        },
                        {
                            extend: "pdf",
                            text: '<i class="bx bxs-file-pdf me-1"></i>PDF',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7,8] },
                
                            
                        },

                        
                    ],
                },
            ],
            responsive: true,
            orderCellsTop: true,
            initComplete: function () {
                console.log('DataTable initialized successfully');
                attachActionIconEvents();
            },
            drawCallback: function () {
                console.log('DataTable redrawn');
                attachActionIconEvents();
            }
        });
        
function attachActionIconEvents() {
    // Use event delegation to handle clicks on dynamically added .action-icon elements
    $('#congesTable').off('click', '.action-icon').on('click', '.action-icon', function () {
        const congeId = $(this).data('id');
        const row = $(this).closest('tr');
        const salarieId = row.data('salarie-id');
        const currentStatus = row.find('td:eq(7)').text().trim();
        console.log(`Action icon clicked, congeId: ${congeId}, salarieId: ${salarieId}, currentStatus: ${currentStatus}`);

        Swal.fire({
            title: 'Modifier le statut de la demande',
            text: 'Voulez-vous accepter ou refuser cette demande de congé ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Accepter',
            cancelButtonText: 'Refuser',
            showDenyButton: true,
            denyButtonText: 'Annuler',
        }).then((result) => {
            // --- Lines Before ---
            if (result.isConfirmed) {
                console.log(`Accept selected for congeId: ${congeId}, sending AJAX with status 2`);
                $.ajax({
                    url: `/conges-administratif/approve/${congeId}`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: { status: 2 },
                    success: function (response) {
                        console.log('AJAX success:', response);
                        if (response.success) {
                            // Update the Approval column
                            row.find('td:eq(7)').html('<span class="badge badge-accepted">Accepté</span>');

                            // Update the Actions column: enable download icon and ensure delete icon is present
                            const downloadLink = response.pdf_path 
                                ? `<a href="/conges-administratif/download-pdf/${congeId}" class="download-icon enabled" title="Télécharger PDF"><i class="bx bx-download" style="font-size: 1.5rem; color: #007bff;"></i></a>`
                                : `<i class="bx bx-download download-icon disabled" title="PDF non disponible" style="font-size: 1.5rem; color: #ccc;"></i>`;
                            row.find('td:eq(8)').html(
                                `<i class="bx bx-check-circle action-icon" data-id="${congeId}" title="Approuver/Refuser" style="cursor: pointer; color: #28a745; font-size: 1.5rem; margin-right: 10px;"></i>` +
                                downloadLink +
                                `<i class="bx bx-trash delete-icon" data-id="${congeId}" title="Supprimer" style="cursor: pointer; color: #dc3545; font-size: 1.5rem;"></i>`
                            );

                            // Update the Remaining Days column for affected rows using updated_leaves
                            if (response.updated_leaves) {
                                Object.keys(response.updated_leaves).forEach(function (congeId) {
                                    const targetRow = $(`#congesTable .action-icon[data-id="${congeId}"]`).closest('tr');
                                    if (targetRow.length) {
                                        const numJ = parseInt(targetRow.find('td:eq(4)').text()) || 0;
                                        const approvalStatus = targetRow.find('td:eq(7)').text().trim();
                                        const displayValue = approvalStatus === 'En cours' 
                                            ? Math.max(0, response.updated_leaves[congeId] - numJ) 
                                            : response.updated_leaves[congeId];
                                        targetRow.find('td:eq(6)').text(displayValue || '-');
                                        console.log(`Updated n_jours_reste for congeId: ${congeId} to ${displayValue}`);
                                    }
                                });
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Succès',
                                text: 'Demande de congé acceptée avec succès.',
                                timer: 1000,
                            showConfirmButton: false
                            }).then(() => {
                                location.reload();  // ← RAFRAÎCHISSEMENT COMPLET DE LA PAGE
                            });
                            const successSound = new Audio('/assets/audio/success.mp3');
                            successSound.play().catch(error => console.log('Erreur audio:', error));
                        } else {
                            console.error('Server responded with failure:', response.message);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: response.message || 'Une erreur est survenue.',
                                timer: 5000,
                            });
                            const errorSound = new Audio('/assets/audio/error.mp3');
                            errorSound.play().catch(error => console.log('Erreur audio:', error));
                        }
                    },
                    error: function (xhr) {
                        console.error('AJAX error:', xhr.status, xhr.responseText);
                        let errorMessage = 'Une erreur est survenue lors de l\'acceptation.';
                        if (xhr.status === 419) {
                            errorMessage = 'Erreur de validation CSRF. Veuillez rafraîchir la page.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Erreur serveur. Veuillez vérifier les logs.';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: errorMessage,
                            timer: 5000,
                        });
                        const errorSound = new Audio('/assets/audio/error.mp3');
                        errorSound.play().catch(error => console.log('Erreur audio:', error));
                    }
                });
            } else if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
                // --- Lines After ---
                console.log(`Reject selected for congeId: ${congeId}, sending AJAX with status 1`);
                $.ajax({
                    url: `/conges-administratif/approve/${congeId}`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: { status: 1 },
                    success: function (response) {
                        console.log('AJAX success:', response);
                        if (response.success) {
                            // Update the Approval column
                            row.find('td:eq(7)').html('<span class="badge badge-rejected">Refusé</span>');

                            // Update the Actions column: disable download icon and ensure delete icon is present
                            row.find('td:eq(8)').html(
                                `<i class="bx bx-check-circle action-icon" data-id="${congeId}" title="Approuver/Refuser" style="cursor: pointer; color: #28a745; font-size: 1.5rem; margin-right: 10px;"></i>` +
                                `<i class="bx bx-download download-icon disabled" title="PDF non disponible" style="font-size: 1.5rem; color: #ccc;"></i>` +
                                `<i class="bx bx-trash delete-icon" data-id="${congeId}" title="Supprimer" style="cursor: pointer; color: #dc3545; font-size: 1.5rem;"></i>`
                            );

                            // Update the Remaining Days column for affected rows using updated_leaves
                            if (response.updated_leaves) {
                                Object.keys(response.updated_leaves).forEach(function (congeId) {
                                    const targetRow = $(`#congesTable .action-icon[data-id="${congeId}"]`).closest('tr');
                                    if (targetRow.length) {
                                        const numJ = parseInt(targetRow.find('td:eq(4)').text()) || 0;
                                        const approvalStatus = targetRow.find('td:eq(7)').text().trim();
                                        const displayValue = approvalStatus === 'En cours' 
                                            ? Math.max(0, response.updated_leaves[congeId] - numJ) 
                                            : response.updated_leaves[congeId];
                                        targetRow.find('td:eq(6)').text(displayValue || '-');
                                        console.log(`Updated n_jours_reste for congeId: ${congeId} to ${displayValue}`);
                                    }
                                });
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Succès',
                                text: 'Demande de congé refusée avec succès.',
                                timer: 1000,
                            showConfirmButton: false
                            }).then(() => {
                                location.reload();  // ← RAFRAÎCHISSEMENT COMPLET DE LA PAGE
                            });
                            const successSound = new Audio('/assets/audio/success.mp3');
                            successSound.play().catch(error => console.log('Erreur audio:', error));
                        } else {
                            console.error('Server responded with failure:', response.message);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: response.message || 'Une erreur est survenue.',
                                timer: 5000,
                            });
                            const errorSound = new Audio('/assets/audio/error.mp3');
                            errorSound.play().catch(error => console.log('Erreur audio:', error));
                        }
                    },
                    error: function (xhr) {
                        console.error('AJAX error:', xhr.status, xhr.responseText);
                        let errorMessage = 'Une erreur est survenue lors du refus.';
                        if (xhr.status === 419) {
                            errorMessage = 'Erreur de validation CSRF. Veuillez rafraîchir la page.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Erreur serveur. Veuillez vérifier les logs.';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: errorMessage,
                            timer: 5000,
                        });
                        const errorSound = new Audio('/assets/audio/error.mp3');
                        errorSound.play().catch(error => console.log('Erreur audio:', error));
                    }
                });
            }
        });
    });
}

        $("#salarie_id").on("change", function () {
            console.log('Salarie selection changed');
            const selectedOption = $(this).find("option:selected");
            const nom = selectedOption.data("nom") || "";
            const prenom = selectedOption.data("prenom") || "";
            const email = selectedOption.data("email") || "";
            $("#nom").val(nom);
            $("#prenom").val(prenom);
            $("#email").val(email);
        });

$("#congeForm").on("submit", function (e) {
    e.preventDefault(); // Empêcher la soumission par défaut
    console.log('Form submission triggered'); // Debug

    // Vérifier la signature
    if (signaturePad.isEmpty()) {
        console.log('Signature manquante'); // Debug
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Veuillez fournir une signature.",
            timer: 5000,
        });
        const errorSound = new Audio('/assets/audio/error.mp3');
        errorSound.play().catch(error => console.log('Erreur audio:', error));
        return;
    }

    // Définir les données de la signature dans l'input caché
    const signatureData = signaturePad.toDataURL("image/png");
    $("#signatureInput").val(signatureData);
    console.log('Signature capturée :', signatureData.substring(0, 50) + '...'); // Debug

    // Désactiver le bouton Soumettre
    const submitButton = $(this).find('button[type="submit"]');
    submitButton.prop("disabled", true).text("Soumission...");

    // Afficher le modal de chargement
    $("#loadingModal").modal("show");

    // Collecter les données du formulaire
    const formData = new FormData(this);
    // Vérifier que salarie_id est présent (facultatif, selon votre logique)
    if (!formData.get('salarie_id')) {
        console.error('salarie_id manquant dans les données du formulaire');
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Veuillez sélectionner un salarié.",
            timer: 5000,
        });
        const errorSound = new Audio('/assets/audio/error.mp3');
        errorSound.play().catch(error => console.log('Erreur audio:', error));
        $("#loadingModal").modal("hide");
        submitButton.prop("disabled", false).text("Soumettre");
        return;
    }
    console.log('Données du formulaire :', Object.fromEntries(formData)); // Debug

    // Envoyer la requête AJAX
    $.ajax({
        url: $(this).attr("action"),
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            console.log('Envoi de la requête AJAX...'); // Debug
        },
        success: function (response) {
            console.log('Réponse du serveur :', response); // Debug
            $("#loadingModal").modal("hide"); // Masquer le modal de chargement
            submitButton.prop("disabled", false).text("Soumettre"); // Réactiver le bouton
            $("#modalConge").modal("hide"); // Fermer le modal de congé
            Swal.fire({
                icon: "success",
                title: "Succès",
                text: response.success || "Votre demande de congé a été soumise avec succès.",
                timer: 5000,
            }).then(() => {
                location.reload(); // Recharger la page
            });
            const successSound = new Audio('/assets/audio/success.mp3');
            successSound.play().catch(error => console.log('Erreur audio:', error));
        },
        error: function (xhr) {
            console.error('Erreur AJAX :', xhr.responseJSON || xhr); // Debug
            $("#loadingModal").modal("hide"); // Masquer le modal de chargement
            submitButton.prop("disabled", false).text("Soumettre"); // Réactiver le bouton
            const errorMessage = xhr.responseJSON?.message || "Une erreur est survenue lors de la soumission.";
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: errorMessage,
                timer: 5000,
            });
            const errorSound = new Audio('/assets/audio/error.mp3');
            errorSound.play().catch(error => console.log('Erreur audio:', error));
        }
    });
});


function printCongesTable(table, title, columns) {
    const logoUrl = companySettings && companySettings.logo
        ? `${window.location.origin}/storage/${companySettings.logo}`
        : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;
    console.log('Printing table');

    // Extract data from the table
    const data = table
        .rows({ search: "applied" })
        .nodes()
        .toArray()
        .map((row) => {
            return [
                $(row).find("td").eq(0).text(), // Nom
                $(row).find("td").eq(1).text(), // Prénom
                $(row).find("td").eq(2).text(), // Matricule
                $(row).find("td").eq(3).text(), // Date de début
                 $(row).find("td").eq(3).text(),
                $(row).find("td").eq(4).text(), // Nombre de jours
                $(row).find("td").eq(5).text(), // Raison
                $(row).find("td").eq(6).text(), // Jours Restants
                $(row).find("td").eq(7).text(), // Approbation
            ];
        });

    // Pagination: Split data into pages with 8 rows each
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
                        <td>${row[0] || "-"}</td>
                        <td>${row[1] || "-"}</td>
                        <td>${row[2] || "-"}</td>
                        <td>${row[3] || "-"}</td>
                        <td>${row[4] || "-"}</td>
                        <td>${row[5] || "-"}</td>
                        <td>${row[6] || "-"}</td>
                        <td>${row[7] || "-"}</td>
                        <td>${row[8] || "-"}</td>
                    </tr>`
                )
                .join("");
            return `
                <div class="table-section" style="margin-bottom: 20px; page-break-after: always;">
                    <table>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Matricule</th>
                            <th>Date de début</th>
                            <th>Date de fin</th>
                            <th>Nombre de jours</th>
                            <th>Raison</th>
                            <th>Jours Restants</th>
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
                    font-family: Arial; 
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
                <p>Siège social: ${companySettings.address || 'Non spécifié'} | Capital: ${
                    companySettings.capital
                        ? companySettings.capital.toLocaleString('fr-FR', {
                              minimumFractionDigits: 2,
                              maximumFractionDigits: 2,
                          }) + ' MAD'
                        : 'Non spécifié'
                } | Tél: ${companySettings.phone_number || 'Non spécifié'}</p>
                <p>R.C.: ${companySettings.commercial_register || 'Non spécifié'} | CNSS: ${
                    companySettings.cnss_number || 'Non spécifié'
                } | IF: ${companySettings.tax_id || 'Non spécifié'} | TP: ${
                    companySettings.tax_id || 'Non spécifié'
                } | ICE: ${companySettings.patent_number || 'Non spécifié'}</p>
                <p>C.B.: ${companySettings.account_number || 'Non spécifié'}, ${
                    companySettings.bank_name || 'Non spécifié'
                } | Email: ${companySettings.email || 'Non spécifié'}</p>
            </div>
        </body>
        </html>`;

    try {
        printContent(html, title);
    } catch (e) {
        console.error('Print error:', e);
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Erreur lors de l'impression : " + e.message,
            timer: 5000,
        });
        const errorSound = new Audio('/assets/audio/error.mp3');
        errorSound.play().catch((error) => console.log('Erreur audio:', error));
    }
}

function printContent(html, title) {
    console.log('Starting print content');
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
}

    } catch (e) {
        console.error("DataTable initialization failed:", e);
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Erreur lors de l'initialisation du tableau : " + e.message,
            timer: 5000,
        });
    }


$('#congesTable').off('click', '.delete-icon').on('click', '.delete-icon', function () {
    const congeId = $(this).data('id');
    console.log(`Delete icon clicked, congeId: ${congeId}`);

    Swal.fire({
        title: 'Confirmer la suppression',
        text: 'Êtes-vous sûr de vouloir supprimer cette demande de congé ? Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            console.log(`Delete confirmed for congeId: ${congeId}, sending AJAX request`);
            $.ajax({
                url: `/conges-administratif/destroy/${congeId}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function (response) {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        // Remove the row from the table
                        const row = $(`#congesTable .delete-icon[data-id="${congeId}"]`).closest('tr');
                        row.remove();

                        // Update leave balances for other rows of the same employee
                        if (response.updated_leaves) {
                            Object.keys(response.updated_leaves).forEach(function (congeId) {
                                const targetRow = $(`#congesTable .action-icon[data-id="${congeId}"]`).closest('tr');
                                if (targetRow.length) {
                                    const numJ = parseInt(targetRow.find('td:eq(4)').text()) || 0;
                                    const approvalStatus = targetRow.find('td:eq(7)').text().trim();
                                    const displayValue = approvalStatus === 'En cours' ?
                                        Math.max(0, response.updated_leaves[congeId] - numJ) :
                                        response.updated_leaves[congeId];
                                    targetRow.find('td:eq(6)').text(displayValue || '-');
                                    console.log(`Updated n_jours_reste for congeId: ${congeId} to ${displayValue}`);
                                }
                            });
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: 'Demande de congé supprimée avec succès.',
                            timer: 5000,
                        });
                        const successSound = new Audio('/assets/audio/success.mp3');
                        successSound.play().catch(error => console.log('Erreur audio:', error));
                    } else {
                        console.error('Server responded with failure:', response.message);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: response.message || 'Une erreur est survenue lors de la suppression.',
                            timer: 5000,
                        });
                        const errorSound = new Audio('/assets/audio/error.mp3');
                        errorSound.play().catch(error => console.log('Erreur audio:', error));
                    }
                },
                error: function (xhr) {
                    console.error('AJAX error:', xhr.status, xhr.responseText);
                    let errorMessage = 'Une erreur est survenue lors de la suppression.';
                    if (xhr.status === 419) {
                        errorMessage = 'Erreur de validation CSRF. Veuillez rafraîchir la page.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Demande de congé introuvable.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Erreur serveur. Veuillez vérifier les logs.';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: errorMessage,
                        timer: 5000,
                    });
                    const errorSound = new Audio('/assets/audio/error.mp3');
                    errorSound.play().catch(error => console.log('Erreur audio:', error));
                }
            });
        }
    });
});


// Add to the top of $(document).ready
const editCanvas = document.getElementById("editSignaturePad");
const editSignaturePad = new SignaturePad(editCanvas, {
    backgroundColor: 'rgb(255, 255, 255)',
    penColor: 'rgb(0, 0, 0)'
});

$("#modalEditConge").on("shown.bs.modal", function () {
    console.log('Edit modal shown');
    const canvas = document.getElementById("editSignaturePad");
    const parentWidth = canvas.parentElement.offsetWidth;
    canvas.width = parentWidth;
    canvas.height = 100;
    editSignaturePad.clear();
});

$("#editClearSignature").on("click", function () {
    console.log('Clear signature clicked (edit modal)');
    editSignaturePad.clear();
    $("#editSignatureInput").val("");
});

$("#modalEditConge").on("hidden.bs.modal", function () {
    console.log('Edit modal hidden, resetting form');
    $("#editCongeForm").attr('action', "");
    $("#edit_salarie_id").val("");
    $("#edit_nom").val("");
    $("#edit_prenom").val("");
    $("#edit_email").val("");
    $("#edit_date_debut").val("");
    $("#edit_nombre_jours").val("");
    $("#edit_raison").val("");
    $("#editSignatureInput").val("");
    editSignaturePad.clear();
});

   $('#congesTable').off('click', '.edit-icon').on('click', '.edit-icon:not(.disabled)', function () {
        const congeId = $(this).data('id');
        console.log(`Edit icon clicked, congeId: ${congeId}`);

        // Verify textarea exists
        console.log('edit_raison textarea exists:', $('#edit_raison').length > 0);

        $.ajax({
            url: `/conges-administratif/edit/${congeId}`,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function (response) {
                console.log('Edit data fetched:', response);
                console.log('raison value:', response.conge?.raison ?? 'undefined'); // Debug raison
                if (response.success) {
                    const conge = response.conge;
                    const salarie = response.salarie;

                    // Populate the edit form
                    $("#edit_salarie_id").val(salarie.id);
                    $("#edit_nom").val(salarie.nom || '');
                    $("#edit_prenom").val(salarie.prenom || '');
                    $("#edit_email").val(salarie.email || '');
                    $("#edit_date_debut").val(conge.date_debut);
                    $("#edit_nombre_jours").val(conge.num_j);
                    $("#edit_raison").val(conge.raison || ''); // Populate raison
                    console.log('Setting edit_raison value to:', conge.raison || '');
                    console.log('edit_raison current value:', $("#edit_raison").val()); // Verify after setting
                    $("#editSignatureInput").val(conge.signature || '');
                    if (conge.signature) {
                        editSignaturePad.fromDataURL(`{{ asset('') }}${conge.signature}`);
                    } else {
                        editSignaturePad.clear();
                    }

                    // Set form action
                    $("#editCongeForm").attr('action', `/conges-administratif/update/${congeId}`);
                    $("#modalEditConge").modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: response.message || 'Impossible de charger les données du congé.',
                        timer: 5000,
                    });
                    const errorSound = new Audio('/assets/audio/error.mp3');
                    errorSound.play().catch(error => console.log('Erreur audio:', error));
                }
            },
            error: function (xhr) {
                console.error('AJAX error:', xhr.status, xhr.responseText);
                let errorMessage = 'Une erreur est survenue lors du chargement des données.';
                if (xhr.status === 404) {
                    errorMessage = 'Demande de congé introuvable.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Modification non autorisée pour les congés approuvés.';
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: errorMessage,
                    timer: 5000,
                });
                const errorSound = new Audio('/assets/audio/error.mp3');
                errorSound.play().catch(error => console.log('Erreur audio:', error));
            }
        });
    });


// Modify the existing form submission handler to support both forms
$("#congeForm, #editCongeForm").on("submit", function (e) {
        e.preventDefault();
        console.log('Form submission triggered', this.id);

        const isEditForm = this.id === 'editCongeForm';
        const signaturePad = isEditForm ? editSignaturePad : signaturePad;
        const signatureInput = isEditForm ? $("#editSignatureInput") : $("#signatureInput");

        // Verify signature
        if (signaturePad.isEmpty()) {
            console.log('Signature manquante');
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Veuillez fournir une signature.",
                timer: 5000,
            });
            const errorSound = new Audio('/assets/audio/error.mp3');
            errorSound.play().catch(error => console.log('Erreur audio:', error));
            return;
        }

        // Capture signature
        const signatureData = signaturePad.toDataURL("image/png");
        signatureInput.val(signatureData);
        console.log('Signature capturée :', signatureData.substring(0, 50) + '...');

        // Debug raison value before submission
        if (isEditForm) {
            console.log('raison value before submission:', $("#edit_raison").val());
        }

        // Disable submit button
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop("disabled", true).text(isEditForm ? "Mise à jour..." : "Soumission...");

        // Show loading modal
        $("#loadingModal").modal("show");

        // Create FormData and add _method for PUT if edit form
        const formData = new FormData(this);
        if (isEditForm) {
            formData.append('_method', 'PUT');
        }

        // Debug form data
        const formDataObject = Object.fromEntries(formData);
        console.log('Données du formulaire :', formDataObject);

        // Verify salarie_id
        if (!formData.get('salarie_id')) {
            console.error('salarie_id manquant dans les données du formulaire');
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Veuillez sélectionner un salarié.",
                timer: 5000,
            });
            const errorSound = new Audio('/assets/audio/error.mp3');
            errorSound.play().catch(error => console.log('Erreur audio:', error));
            $("#loadingModal").modal("hide");
            submitButton.prop("disabled", false).text(isEditForm ? "Mettre à jour" : "Soumettre");
            return;
        }

        // Send AJAX request
        $.ajax({
            url: $(this).attr("action"),
            method: 'POST', // Laravel uses POST with _method=PUT for updates
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            beforeSend: function () {
                console.log('Envoi de la requête AJAX...');
            },
            success: function (response) {
                console.log('Réponse du serveur :', response);
                $("#loadingModal").modal("hide");
                submitButton.prop("disabled", false).text(isEditForm ? "Mettre à jour" : "Soumettre");
                $(isEditForm ? "#modalEditConge" : "#modalConge").modal("hide");
                Swal.fire({
                    icon: "success",
                    title: "Succès",
                    text: response.message || (isEditForm ? "Demande de congé mise à jour avec succès." : "Votre demande de congé a été soumise avec succès."),
                    timer: 5000,
                }).then(() => {
                    location.reload();
                });
                const successSound = new Audio('/assets/audio/success.mp3');
                successSound.play().catch(error => console.log('Erreur audio:', error));
            },
            error: function (xhr) {
                console.error('Erreur AJAX :', xhr.responseJSON || xhr);
                $("#loadingModal").modal("hide");
                submitButton.prop("disabled", false).text(isEditForm ? "Mettre à jour" : "Soumettre");
                const errors = xhr.responseJSON?.errors;
                let errorMessage = xhr.responseJSON?.message || (isEditForm ? "Une erreur est survenue lors de la mise à jour." : "Une erreur est survenue lors de la soumission.");
                if (errors) {
                    errorMessage = Object.values(errors).flat().join(' ');
                }
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: errorMessage,
                    timer: 5000,
                });
                const errorSound = new Audio('/assets/audio/error.mp3');
                errorSound.play().catch(error => console.log('Erreur audio:', error));
            }
        });
    });

// Add to the salarie_id change handler to support edit modal
$("#edit_salarie_id").on("change", function () {
    console.log('Salarie selection changed (edit modal)');
    const selectedOption = $(this).find("option:selected");
    const nom = selectedOption.data("nom") || "";
    const prenom = selectedOption.data("prenom") || "";
    const email = selectedOption.data("email") || "";
    $("#edit_nom").val(nom);
    $("#edit_prenom").val(prenom);
    $("#edit_email").val(email);
});


// Update the attachActionIconEvents function to handle edit icon visibility
function attachActionIconEvents() {
    // Use event delegation to handle clicks on dynamically added .action-icon elements
    $('#congesTable').off('click', '.action-icon').on('click', '.action-icon', function () {
        const congeId = $(this).data('id');
        const row = $(this).closest('tr');
        const salarieId = row.data('salarie-id');
        const currentStatus = row.find('td:eq(7)').text().trim();
        console.log(`Action icon clicked, congeId: ${congeId}, salarieId: ${salarieId}, currentStatus: ${currentStatus}`);

        Swal.fire({
            title: 'Modifier le statut de la demande',
            text: 'Voulez-vous accepter ou refuser cette demande de congé ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Accepter',
            cancelButtonText: 'Refuser',
            showDenyButton: true,
            denyButtonText: 'Annuler',
        }).then((result) => {
            // --- Lines Before ---
            if (result.isConfirmed) {
                console.log(`Accept selected for congeId: ${congeId}, sending AJAX with status 2`);
                $.ajax({
                    url: `/conges-administratif/approve/${congeId}`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: { status: 2 },
                    success: function (response) {
                        console.log('AJAX success:', response);
                        if (response.success) {
                            // Update the Approval column
                            row.find('td:eq(7)').html('<span class="badge badge-accepted">Accepté</span>');

                            // Update the Actions column: enable download icon and ensure delete icon is present
                            const downloadLink = response.pdf_path 
                                ? `<a href="/conges-administratif/download-pdf/${congeId}" class="download-icon enabled" title="Télécharger PDF"><i class="bx bx-download" style="font-size: 1.5rem; color: #007bff;"></i></a>`
                                : `<i class="bx bx-download download-icon disabled" title="PDF non disponible" style="font-size: 1.5rem; color: #ccc;"></i>`;
                            row.find('td:eq(8)').html(
                                `<i class="bx bx-check-circle action-icon" data-id="${congeId}" title="Approuver/Refuser" style="cursor: pointer; color: #28a745; font-size: 1.5rem; margin-right: 10px;"></i>` +
                                downloadLink +
                                `<i class="bx bx-trash delete-icon" data-id="${congeId}" title="Supprimer" style="cursor: pointer; color: #dc3545; font-size: 1.5rem;"></i>`
                            );

                            // Update the Remaining Days column for affected rows using updated_leaves
                            if (response.updated_leaves) {
                                Object.keys(response.updated_leaves).forEach(function (congeId) {
                                    const targetRow = $(`#congesTable .action-icon[data-id="${congeId}"]`).closest('tr');
                                    if (targetRow.length) {
                                        const numJ = parseInt(targetRow.find('td:eq(4)').text()) || 0;
                                        const approvalStatus = targetRow.find('td:eq(7)').text().trim();
                                        const displayValue = approvalStatus === 'En cours' 
                                            ? Math.max(0, response.updated_leaves[congeId] - numJ) 
                                            : response.updated_leaves[congeId];
                                        targetRow.find('td:eq(6)').text(displayValue || '-');
                                        console.log(`Updated n_jours_reste for congeId: ${congeId} to ${displayValue}`);
                                    }
                                });
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Succès',
                                text: 'Demande de congé acceptée avec succès.',
                                timer: 5000,
                            });
                            const successSound = new Audio('/assets/audio/success.mp3');
                            successSound.play().catch(error => console.log('Erreur audio:', error));
                        } else {
                            console.error('Server responded with failure:', response.message);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: response.message || 'Une erreur est survenue.',
                                timer: 5000,
                            });
                            const errorSound = new Audio('/assets/audio/error.mp3');
                            errorSound.play().catch(error => console.log('Erreur audio:', error));
                        }
                    },
                    error: function (xhr) {
                        console.error('AJAX error:', xhr.status, xhr.responseText);
                        let errorMessage = 'Une erreur est survenue lors de l\'acceptation.';
                        if (xhr.status === 419) {
                            errorMessage = 'Erreur de validation CSRF. Veuillez rafraîchir la page.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Erreur serveur. Veuillez vérifier les logs.';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: errorMessage,
                            timer: 5000,
                        });
                        const errorSound = new Audio('/assets/audio/error.mp3');
                        errorSound.play().catch(error => console.log('Erreur audio:', error));
                    }
                });
            } else if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
                // --- Lines After ---
                console.log(`Reject selected for congeId: ${congeId}, sending AJAX with status 1`);
                $.ajax({
                    url: `/conges-administratif/approve/${congeId}`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: { status: 1 },
                    success: function (response) {
                        console.log('AJAX success:', response);
                        if (response.success) {
                            // Update the Approval column
                            row.find('td:eq(7)').html('<span class="badge badge-rejected">Refusé</span>');

                            // Update the Actions column: disable download icon and ensure delete icon is present
                            row.find('td:eq(8)').html(
                                `<i class="bx bx-check-circle action-icon" data-id="${congeId}" title="Approuver/Refuser" style="cursor: pointer; color: #28a745; font-size: 1.5rem; margin-right: 10px;"></i>` +
                                `<i class="bx bx-download download-icon disabled" title="PDF non disponible" style="font-size: 1.5rem; color: #ccc;"></i>` +
                                `<i class="bx bx-trash delete-icon" data-id="${congeId}" title="Supprimer" style="cursor: pointer; color: #dc3545; font-size: 1.5rem;"></i>`
                            );

                            // Update the Remaining Days column for affected rows using updated_leaves
                            if (response.updated_leaves) {
                                Object.keys(response.updated_leaves).forEach(function (congeId) {
                                    const targetRow = $(`#congesTable .action-icon[data-id="${congeId}"]`).closest('tr');
                                    if (targetRow.length) {
                                        const numJ = parseInt(targetRow.find('td:eq(4)').text()) || 0;
                                        const approvalStatus = targetRow.find('td:eq(7)').text().trim();
                                        const displayValue = approvalStatus === 'En cours' 
                                            ? Math.max(0, response.updated_leaves[congeId] - numJ) 
                                            : response.updated_leaves[congeId];
                                        targetRow.find('td:eq(6)').text(displayValue || '-');
                                        console.log(`Updated n_jours_reste for congeId: ${congeId} to ${displayValue}`);
                                    }
                                });
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Succès',
                                text: 'Demande de congé refusée avec succès.',
                                timer: 5000,
                            });
                            const successSound = new Audio('/assets/audio/success.mp3');
                            successSound.play().catch(error => console.log('Erreur audio:', error));
                        } else {
                            console.error('Server responded with failure:', response.message);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: response.message || 'Une erreur est survenue.',
                                timer: 5000,
                            });
                            const errorSound = new Audio('/assets/audio/error.mp3');
                            errorSound.play().catch(error => console.log('Erreur audio:', error));
                        }
                    },
                    error: function (xhr) {
                        console.error('AJAX error:', xhr.status, xhr.responseText);
                        let errorMessage = 'Une erreur est survenue lors du refus.';
                        if (xhr.status === 419) {
                            errorMessage = 'Erreur de validation CSRF. Veuillez rafraîchir la page.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Erreur serveur. Veuillez vérifier les logs.';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: errorMessage,
                            timer: 5000,
                        });
                        const errorSound = new Audio('/assets/audio/error.mp3');
                        errorSound.play().catch(error => console.log('Erreur audio:', error));
                    }
                });
            }
        });
    });
}
 

});