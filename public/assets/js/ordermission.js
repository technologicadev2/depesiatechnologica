document.addEventListener("DOMContentLoaded", function () {
    if (!window.jQuery || !$.fn.DataTable) {
        console.error("jQuery or DataTables is not loaded.");
        return;
    }

    // Filtres par colonne
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

    // Génération du contenu d'impression
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
            const tableContent = pageRows.map((row) => `
                <tr class="table-tr">
                    ${columns.cols.map((col) => `<td>${row[col] || "-"}</td>`).join("")}
                </tr>
            `).join("");

            return `
                <div class="print-body-2" style="margin-bottom:8px; page-break-after: always;">
                    <table class="table table-bordered">
                        <tr style="background-color:rgb(233, 233, 233);">
                            ${columns.headers.map((header) => `<td class="td-bold">${header}</td>`).join("")}
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
                <title>Impression - ${title}</title>
                <style>
                    html, body, * { padding: 0; margin: 0; box-sizing: border-box; font-family: sans-serif; }
                    @page { size: landscape; margin: 0mm; }
                    body * { -webkit-print-color-adjust: exact !important; }
                    .print { width: 100%; padding: 18px 18px 0 18px; }
                    .print-header { width: 100%; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 10px; }
                    .print-header-type { width: 100%; height: 23px; line-height: 21px; text-align: center; background-color: #efefef; border: 1px solid black; margin-bottom: 10px; font-size: 13px; font-weight: bold; }
                    .logo-title-container { display: flex; align-items: center; }
                    .logo-title-container img { max-width: 50px; height: auto; }
                    .print-header-title { font-size: 15px; font-weight: bold; margin-left: 10px; }
                    .print-body-2 table { width: 100%; }
                    .print-body-2 table, th, td { border: 1px solid black; border-collapse: collapse; padding: 6px; text-align: center; font-size: 12px; font-weight: 500; vertical-align: middle; }
                    .td-bold { font-size: 13px; font-weight: bold; }
                    .print-footer { width: 100%; display: flex; justify-content: space-between; margin-top: 10px; }
                    .print-footer h4 { display: inline-block; margin-right: 20px; font-size: 13px; }
                    .company-info { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 12px; padding: 10px 0; border-top: 1px solid #ddd; }
                    @media print { body { margin: 0 !important; } @page { margin: 0mm !important; } }
                </style>
            </head>
            <body>
                <div class="print">
                    <div class="print-header">
                        <div class="logo-title-container">
                            <img src="${logoUrl}" alt="Logo" />
                            <h1 class="print-header-title">Ordres de Mission</h1>
                        </div>
                        <div>
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
                    <p>R.C.: ${companySettings.commercial_register || "Non spécifié"} | CNSS: ${companySettings.cnss_number || "Non spécifié"} | IF: ${companySettings.tax_id || "Non spécifié"} | ICE: ${companySettings.patent_number || "Non spécifié"}</p>
                    <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${companySettings.bank_name || "Non spécifié"} | Email: ${companySettings.email || "Non spécifié"}</p>
                </div>
            </body>
            </html>
        `;
    }

    function printTable(tableId, title, columns) {
        const table = $(`#${tableId}`).DataTable();
        const iframe = $("<iframe/>", {
            style: "position: absolute; width: 0; height: 0; border: none;",
        }).appendTo("body");

        const iframeDoc = iframe[0].contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(generatePrintContent(table, title, columns));
        iframeDoc.close();

        setTimeout(function () {
            iframe[0].contentWindow.focus();
            iframe[0].contentWindow.print();
        }, 500);

        iframe[0].contentWindow.onafterprint = function () { iframe.remove(); };
        setTimeout(function () { if (iframe[0]) iframe.remove(); }, 5000);
    }

    // Initialisation DataTable
    const dataTable = $("#ordermissionsTable").DataTable({
        dom:
            '<"row ms-2 me-3"' +
            '<"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls"l<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3"B>>' +
            '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter"f>' +
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
                className: "btn btn-label-primary dropdown-toggle me-2 export-btn",
                text: '<i class="bx bx-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Exporter</span>',
                buttons: [
                    {
                        text: '<i class="bx bx-printer me-1"></i>Imprimer',
                        className: "dropdown-item",
                        action: function () {
                            printTable("ordermissionsTable", "Ordres de Mission", {
                                cols: [0, 1, 2, 3, 4],
                                headers: ["Code Mission", "Matricule", "Salarié", "Emplacement", "Mission"],
                            });
                        },
                    },
                    {
                        extend: "excel",
                        text: '<i class="bx bxs-file-export me-1"></i>Excel',
                        className: "dropdown-item",
                        exportOptions: { columns: [0, 1, 2, 3, 4] },
                    },
                    {
                        extend: "pdf",
                        text: '<i class="bx bxs-file-pdf me-1"></i>PDF',
                        className: "dropdown-item",
                        exportOptions: { columns: [0, 1, 2, 3, 4] },
                    },
                ],
            },
            {
                text: '<i class="bx bx-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Ajouter</span>',
                className: "btn btn-primary",
                action: function () {
                    $("#addOrdreForm")[0].reset();
                    $("#addOrdreModal").modal("show");
                }
            },
        ],
        responsive: true,
        orderCellsTop: true,
        language: {
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_",
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
        initComplete: function () {
            addColumnSearch("#ordermissionsTable", this.api(), [5]);
        },
    });

    // ===================== SELECT2 MODAL AJOUT =====================
function initOrdreMissionSelects() {
    $('#gerant_select').select2({
        dropdownParent: $('#addOrdreModal'),
        placeholder: "Sélectionner le gérant",
        allowClear: true,
        width: '100%'
    });

    $('#salaries_select').select2({
        dropdownParent: $('#addOrdreModal'),
        placeholder: "Sélectionner les salariés participants",
        allowClear: true,
        width: '100%',
        multiple: true
    });

    $('#vehicule_mission_select').select2({
        dropdownParent: $('#addOrdreModal'),
        placeholder: "Sélectionner un véhicule",
        allowClear: true,
        width: '100%'
    });
}


    $(document).on('change', '#vehicule_mission_select', function () {
    const selected = $(this).find(':selected');
    $('#marque_mission').val(selected.data('marque') || '');
    $('#nplaque_mission').val(selected.data('matricule') || '');
});

$(document).on('change', '#edit_vehicule_mission_select', function () {
    const selected = $(this).find(':selected');
    $('#edit_marque_mission').val(selected.data('marque') || '');
    $('#edit_nplaque_mission').val(selected.data('matricule') || '');
});

    $('#addOrdreModal').on('shown.bs.modal', function () {
        initOrdreMissionSelects();
    });

    $('#addOrdreModal').on('hidden.bs.modal', function () {
        $('#gerant_select').val(null).trigger('change');
        $('#salaries_select').val(null).trigger('change');
    });

    // ===================== TRANSPORT AJOUT =====================
  $('#moyen_transport').on('change', function () {
    const value = $(this).val();
    $('#mission_fields, #perso_marque, #perso_plaque, #perso_puissance').hide();
    if (value === 'voiture_mission') {
        $('#mission_fields').show();
    } else if (value === 'voiture_personnelle') {
        $('#perso_marque, #perso_plaque, #perso_puissance').show();
    }
});

    // ===================== SOUMISSION AJOUT =====================
    $('#addOrdreForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: storeRoute,
            type: "POST",
            data: $(this).serialize(),
            success: function (response) {
                if (response.success) {
                    Swal.fire('Succès', response.message, 'success');
                    $("#addOrdreModal").modal("hide");
                    location.reload();
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: xhr.responseJSON?.message || 'Une erreur est survenue'
                });
            }
        });
    });

    // ===================== SUPPRESSION =====================
    $(document).on("click", ".delete-ordre", function () {
        const id = $(this).data("id");
        const row = $(this).closest("tr");

        Swal.fire({
            title: "Êtes-vous sûr ?",
            text: "Cette action est irréversible !",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Oui, supprimer",
            cancelButtonText: "Annuler"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${storeRoute.replace('/store', '')}/${id}`,
                    type: "DELETE",
                    data: { _token: $('meta[name="csrf-token"]').attr("content") },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire("Supprimé !", response.message, "success");
                            row.fadeOut(400, function () { $(this).remove(); });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            text: xhr.responseJSON?.message || "Impossible de supprimer cet ordre de mission."
                        });
                    }
                });
            }
        });
    });

    // ===================== VALIDATION ORDRE =====================
    $(document).on("click", ".validate-ordre", function () {
        const id = $(this).data("id");
        const currentStatut = $(this).data("statut");
        $("#validate_id").val(id);
        $("#ordre_checkbox").prop("checked", currentStatut == 1);
        $("#validateModal").modal("show");
    });

    $("#saveValidation").on("click", function () {
        const id = $("#validate_id").val();
        const value = $("#ordre_checkbox").is(":checked") ? 1 : 0;

        $.ajax({
            url: `${storeRoute.replace('/store', '')}/${id}/validate`,
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                ordre: value
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: value === 1 ? 'Validé !' : 'Modifié',
                        text: response.message,
                        timer: 1500
                    });
                    $("#validateModal").modal("hide");
                    location.reload();
                }
            },
            error: function () {
                Swal.fire('Erreur', 'Impossible de mettre à jour le statut.', 'error');
            }
        });
    });

    // ===================== FICHE OFFICIELLE =====================
    $(document).on("click", ".show-fiche", function () {
        const id = $(this).data("id");
        $.ajax({
            url: `${storeRoute.replace('/store', '')}/${id}/fiche`,
            type: "GET",
            success: function (response) {
                if (response.success) {
                    $("#fiche-content").html(response.html);
                    $("#ficheModal").modal("show");
                }
            },
            error: function () {
                Swal.fire("Erreur", "Impossible de charger la fiche", "error");
            }
        });
    });

    // ===================== MODIFICATION — stockage temporaire des données =====================
    // On stocke les données ici pour les utiliser après l'ouverture du modal
    let ordreDataTemp = null;

    $(document).on("click", ".edit-ordre", function () {
        const id = $(this).data("id");

        $.ajax({
            url: `${storeRoute.replace('/store', '')}/${id}/edit`,
            type: "GET",
            success: function (response) {
                if (response.success) {
                    ordreDataTemp = response.data;
                    // On ouvre le modal AVANT de remplir les champs
                    $("#editOrdreModal").modal("show");
                }
            },
            error: function () {
                Swal.fire("Erreur", "Impossible de charger les données", "error");
            }
        });
    });

    // On remplit les champs APRÈS que le modal est complètement ouvert (Select2 est prêt)
    $('#editOrdreModal').on('shown.bs.modal', function () {

        // Initialiser Select2 en premier
        $('#edit_gerant_select').select2({
            dropdownParent: $('#editOrdreModal'),
            placeholder: "Sélectionner le gérant",
            allowClear: true,
            width: '100%'
        });
        $('#edit_salaries_select').select2({
            dropdownParent: $('#editOrdreModal'),
            placeholder: "Sélectionner les salariés",
            allowClear: true,
            width: '100%',
            multiple: true
        });

        // Puis remplir les champs si on a des données
        if (ordreDataTemp) {
            const ordre = ordreDataTemp;

            $("#edit_id").val(ordre.id);
            $("#edit_emplacement").val(ordre.emplacement);
            $("#edit_mission").val(ordre.mission);

            // ✅ Date — extraire uniquement YYYY-MM-DD
            const dateDepart = ordre.date_depart ? ordre.date_depart.split('T')[0].split(' ')[0] : '';
            $("#edit_date_depart").val(dateDepart);
            const dateRetour = ordre.date_retour ? ordre.date_retour.split('T')[0].split(' ')[0] : '';
                $("#edit_date_retour").val(dateRetour);


            // ✅ Heures & Frais
            $("#edit_heure_depart").val(ordre.heure_depart ? ordre.heure_depart.substring(0, 5) : '');
            $("#edit_heure_retour").val(ordre.heure_retour ? ordre.heure_retour.substring(0, 5) : '');
            $("#edit_frais").val(ordre.frais);

            // ✅ Gérant (Select2 déjà initialisé)
            $("#edit_gerant_select").val(ordre.gerant).trigger('change');

            // ✅ Salariés (Select2 déjà initialisé)
            const salaries = Array.isArray(ordre.salaries)
                ? ordre.salaries
                : JSON.parse(ordre.salaries || '[]');
            $("#edit_salaries_select").val(salaries).trigger('change');

            // ✅ Moyen de transport
            let moyen = '';
            if (ordre.transport_public == 1) moyen = 'transport_public';
            else if (ordre.voiture_mission == 1) moyen = 'voiture_mission';
            else if (ordre.voiture_personnelle == 1) moyen = 'voiture_personnelle';

            $("#edit_moyen_transport").val(moyen).trigger('change');

            if (moyen === 'voiture_mission') {
                $("#edit_marque_mission").val(ordre.marque_mission);
                $("#edit_nplaque_mission").val(ordre.nplaque_mission);
            } else if (moyen === 'voiture_personnelle') {
                $("#edit_marque_personnelle").val(ordre.marque_personnelle);
                $("#edit_nplaque_p").val(ordre.nplaque_p);
                $("#edit_puissance_fiscale_p").val(ordre.puissance_fiscale_p);
            }

            ordreDataTemp = null; // reset
        }
    });

    // Reset quand on ferme le modal edit
    $('#editOrdreModal').on('hidden.bs.modal', function () {
        ordreDataTemp = null;
        $('#edit_gerant_select').val(null).trigger('change');
        $('#edit_salaries_select').val(null).trigger('change');
        $('#edit_moyen_transport').val('').trigger('change');
        $('#edit_date_depart,#edit_date_retour, #edit_heure_depart, #edit_heure_retour, #edit_frais, #edit_emplacement, #edit_mission').val('');
    });

    // ===================== TRANSPORT MODIFICATION =====================
  $('#edit_moyen_transport').on('change', function () {
    const value = $(this).val();
    $('#edit_mission_fields, #edit_perso_marque, #edit_perso_plaque, #edit_perso_puissance').hide();
    if (value === 'voiture_mission') {
        $('#edit_mission_fields').show();
    } else if (value === 'voiture_personnelle') {
        $('#edit_perso_marque, #edit_perso_plaque, #edit_perso_puissance').show();
    }
});

    // ===================== SOUMISSION MODIFICATION =====================
    $('#editOrdreForm').on('submit', function (e) {
        e.preventDefault();
        const id = $("#edit_id").val();
        const baseUrl = storeRoute.replace('/store', '');

        $.ajax({
            url: `${baseUrl}/${id}`,
            type: "PUT",
            data: $(this).serialize() + '&_token=' + $('meta[name="csrf-token"]').attr("content"),
            success: function (response) {
                if (response.success) {
                    Swal.fire('Succès', response.message, 'success');
                    $("#editOrdreModal").modal("hide");
                    location.reload();
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: xhr.responseJSON?.message || 'Une erreur est survenue'
                });
            }
        });
    });


    $(document).on("click", "#printFicheBtn", function () {
    const element = document.getElementById("fiche-printable");

    const opt = {
        margin: 0.5,
        filename: 'ordre_de_mission.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
});

$(document).ready(function () {

    // ==================== INITIALISATION SELECT2 - Véhicule Mission (Ajout) ====================
    $('#vehicule_mission_select').select2({
        placeholder: "Rechercher par marque ou matricule...",
        allowClear: true,
        width: '100%',
        dropdownParent: $('#addOrdreModal'), // important pour que le dropdown s'affiche bien dans le modal
        matcher: function (params, data) {
            // Si pas de recherche, retourner tous les résultats
            if ($.trim(params.term) === '') {
                return data;
            }

            if (typeof data.text === 'undefined') {
                return null;
            }

            const term = params.term.toLowerCase();
            const marque = $(data.element).data('marque') ? $(data.element).data('marque').toString().toLowerCase() : '';
            const matricule = $(data.element).data('matricule') ? $(data.element).data('matricule').toString().toLowerCase() : '';

            if (marque.indexOf(term) > -1 || matricule.indexOf(term) > -1) {
                return data;
            }

            return null;
        }
    });

    // Quand on sélectionne un véhicule -> remplir les champs cachés
    $('#vehicule_mission_select').on('change', function () {
        const selected = $(this).find('option:selected');
        $('#marque_mission').val(selected.data('marque') || '');
        $('#nplaque_mission').val(selected.data('matricule') || '');
    });


    // ==================== INITIALISATION SELECT2 - Véhicule Mission (Modification) ====================
    $('#edit_vehicule_mission_select').select2({
        placeholder: "Rechercher par marque ou matricule...",
        allowClear: true,
        width: '100%',
        dropdownParent: $('#editOrdreModal'),
        matcher: function (params, data) {
            if ($.trim(params.term) === '') {
                return data;
            }

            if (typeof data.text === 'undefined') {
                return null;
            }

            const term = params.term.toLowerCase();
            const marque = $(data.element).data('marque') ? $(data.element).data('marque').toString().toLowerCase() : '';
            const matricule = $(data.element).data('matricule') ? $(data.element).data('matricule').toString().toLowerCase() : '';

            if (marque.indexOf(term) > -1 || matricule.indexOf(term) > -1) {
                return data;
            }

            return null;
        }
    });

    $('#edit_vehicule_mission_select').on('change', function () {
        const selected = $(this).find('option:selected');
        $('#edit_marque_mission').val(selected.data('marque') || '');
        $('#edit_nplaque_mission').val(selected.data('matricule') || '');
    });

});
});