const successSound = new Audio("/assets/audio/success.mp3");
const errorSound = new Audio("/assets/audio/error.mp3");

$(document).ready(function () {
    // Configure CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    // Function to format child row for decomptes/paiements
function formatChildRow(decomptes, term = "Payment", marcheCadre = false) {
    function buildTable(items, title) {
        let html = title
            ? `<h6 class="mt-3 mb-1" style="margin-left: 50px;">${title}</h6>`
            : "";
        html += `<table class="table table-bordered child-table" style="margin-left: 50px; width: calc(100% - 50px);">`;
        html += `
        <thead>
            <tr>
                <th>Type</th>
                <th>Montant ${term}</th>
                ${term === "Paiement" ? "" : "<th>Retenue de Garantie</th>"}
                <th>Révision des Prix</th>
                <th>Date ${term}</th>
                <th>Document</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
    `;

        if (items.length > 0) {
            items.forEach(function (decompte) {
                const documentLink = decompte.document_path
                    ? `<a href="${decompte.document_path}" target="_blank">Voir</a>`
                    : "-";
                const montant_dp = isNaN(
                    parseFloat(decompte.montant_dp?.replace(/,/g, ""))
                )
                    ? "-"
                    : parseFloat(decompte.montant_dp.replace(/,/g, ""))
                          .toString()
                          .replace(/\B(?=(\d{3})+(?!\d))/g, "");
                const rg_dp = decompte.rg_dp
                    ? isNaN(parseFloat(decompte.rg_dp.replace(/,/g, "")))
                        ? "-"
                        : parseFloat(decompte.rg_dp.replace(/,/g, ""))
                              .toString()
                              .replace(/\B(?=(\d{3})+(?!\d))/g, "")
                    : "-";
                const revision_prix = decompte.revision_prix
                    ? isNaN(
                          parseFloat(decompte.revision_prix.replace(/,/g, ""))
                      )
                        ? "-"
                        : parseFloat(decompte.revision_prix.replace(/,/g, ""))
                              .toString()
                              .replace(/\B(?=(\d{3})+(?!\d))/g, "")
                    : "-";
                html += `
    <tr>
        <td>${decompte.type_decompte}</td>
        <td>${montant_dp}</td>
        ${term === "Paiement" ? "" : `<td>${rg_dp}</td>`}
        <td>${revision_prix}</td>
        <td>${decompte.date_dp}</td>
        <td>${documentLink}</td>
        <td>
            <a href="javascript:;" class="text-primary edit-decompte" data-id="${decompte.id}" data-bs-toggle="tooltip" title="Modifier le ${term.toLowerCase()}">
                <i class="bx bx-edit mx-1"></i>
            </a>
            <a href="javascript:;" class="text-danger delete-decompte" data-id="${decompte.id}" data-bs-toggle="tooltip" title="Supprimer le ${term.toLowerCase()}">
                <i class="bx bx-trash mx-1"></i>
            </a>
        </td>
    </tr>
`;
            });
        } else {
            html += `
            <tr>
                <td colspan="${
                    term === "Paiement" ? 6 : 7
                }" class="text-center">Aucun ${term.toLowerCase()} ${
                title ? "pour cette tranche" : "pour ce projet"
            }.</td>
            </tr>
        `;
        }

        html += "</tbody></table>";
        return html;
    }

    if (marcheCadre) {
        let out = "";
        for (let t = 1; t <= 3; t++) {
            const items = decomptes.filter((d) => (d.tranche || 1) == t);
            out += buildTable(items, `Tranche ${t}`);
        }
        return out;
    }

    return buildTable(decomptes, null);
}



    // Initialize DataTable for a given table and commande_type
function initializeDataTable(tableId, commandeType) {
    const term = commandeType === "BC" ? "Paiement" : "Décomptes";
    const isBC = commandeType === "BC";

    const columns = [
        {
            className: "details-control",
            orderable: false,
            data: null,
            defaultContent: '<i class="bx bx-chevron-right"></i>',
        },
        { data: "intitule" },
        {
            data: "total_decompte",
            render: function (data) {
                const value = parseFloat(data?.replace(/,/g, ""));
                return isNaN(value)
                    ? "-"
                    : value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "");
            },
        },
        {
            data: "rg",
            render: function (data) {
                const value = parseFloat(data?.replace(/,/g, ""));
                return isNaN(value)
                    ? "-"
                    : value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "");
            },
        },
        {
            data: "travaux_executier",
            render: function (data) {
                const value = parseFloat(data?.replace(/,/g, ""));
                return isNaN(value)
                    ? "-"
                    : value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "");
            },
        },
        {
            data: "rg_restant",
            render: function (data) {
                const value = parseFloat(data?.replace(/,/g, ""));
                const displayValue = isNaN(value)
                    ? "-"
                    : value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "");
                return value === 0 && !isNaN(value)
                    ? `<span class="text-danger">${displayValue}</span>`
                    : displayValue;
            },
        },
        {
            data: "montant_decompte_restant",
            render: function (data) {
                const value = parseFloat(data?.replace(/,/g, ""));
                const displayValue = isNaN(value)
                    ? "-"
                    : value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "");
                return value === 0 && !isNaN(value)
                    ? `<span class="text-danger">${displayValue}</span>`
                    : displayValue;
            },
        },
        {
            data: "pourcentage",
            render: function (data) {
                return data !== null && data !== undefined ? `${data}%` : "-";
            },
            orderable: true,
        },
        {
            data: null,
            render: function (data) {
                return `
                    <div class="d-flex align-items-center">
                        <a href="javascript:;" class="text-body add-decompte" data-id="${data.id}" data-commande-type="${data.commande_type}" data-bs-toggle="tooltip" title="Ajouter un ${term.toLowerCase()}">
                            <i class="bx bx-plus-circle mx-1"></i>
                        </a>
                        <span class="badge bg-primary ms-2">${data.decomptes_count || 0}</span>
                        <a href="javascript:;" class="text-body view-decomptes" data-id="${data.id}" data-commande-type="${data.commande_type}" data-bs-toggle="tooltip" title="Voir les ${term.toLowerCase()}s">
                            <i class="bx bx-list-ul mx-1"></i>
                        </a>
                    </div>`;
            },
            orderable: false,
        },
        {
            data: "id",
            render: function (data) {
                return `
                    <a href="javascript:;" class="text-body print-decompte" data-id="${data}" data-bs-toggle="tooltip" title="Imprimer le ${term.toLowerCase()}">
                        <i class="bx bx-printer mx-1"></i>
                    </a>`;
            },
            orderable: false,
        },
    ];

    if (isBC) {
        // For BC, remove rg, rg_restant, and pourcentage columns
        columns.splice(3, 1); // Remove rg
        columns.splice(4, 1); // Remove rg_restant
        columns.splice(5, 1); // Remove pourcentage
    }

    return $(`#${tableId}`).DataTable({
        processing: true,
        serverSide: false,
        searching: true,
        ajax: {
            url: $(`#${tableId}`).data("projects-list-url"),
            dataSrc: function (json) {
                return json || [];
            },
            error: function (xhr, error, thrown) {
                console.error("Error loading data:", error, thrown);
                Swal.fire(
                    "Erreur!",
                    `Erreur lors du chargement des ${term.toLowerCase()}s`,
                    "error"
                );
            },
        },
        columns: columns,
        columnDefs: [
            {
                targets: [5, 6], // RG Restant and Montant Décompte Restant for Marche
                className: "bg-green",
            },
        ],
        dom: '<"d-flex justify-content-between align-items-center header-actions mx-2 row mt-75"<"col-sm-12 col-lg-4 d-flex align-items-center"lB><"col-sm-12 col-lg-8"f>>t<"d-flex justify-content-between mx-2 row mb-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
            {
                extend: "collection",
                className: "btn btn-label-primary dropdown-toggle me-2",
                text: '<i class="bx bx-export me-sm-1"></i> Exporter',
                buttons: [
                    {
                        text: '<i class="bx bx-printer me-1"></i>Imprimer',
                        action: function () {
                            printAllProjects(tableId, commandeType);
                        },
                    },
                ],
            },
        ],
        language: {
            lengthMenu: `Afficher _MENU_ ${term.toLowerCase()}s`,
            search: "Rechercher",
            searchPlaceholder: `Rechercher un ${term.toLowerCase()}`,
            paginate: { next: "Suivant", previous: "Précédent" },
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
        },
    });
}

    // Initialize DataTables for both tabs
   // Juste après ces deux lignes :
 const decomptesTable = initializeDataTable("decomptesTable", "BC");
 const paiementsTable = initializeDataTable("paiementsTable", "Marche");

function buildDecompteYearFilter(tableId, dtTable) {
    const currentYear = new Date().getFullYear();

    function getAvailableYears(data) {
    const years = new Set();
    years.add(currentYear);
    data.forEach(function (row) {
        if (row.cloture_date) {
            const y = new Date(row.cloture_date).getFullYear();
            if (!isNaN(y)) years.add(y);
        }
    });
    return Array.from(years).sort((a, b) => b - a);
}

    function renderSelect(years) {
        let opts = `<option value="all">Toutes les années</option>`;
        opts += `<option value="${currentYear}" selected>${currentYear} (en cours)</option>`;
        years.forEach(function (y) {
            if (y !== currentYear) {
                opts += `<option value="${y}">${y}</option>`;
            }
        });
        return `<select id="year-filter-${tableId}"
                    class="form-select form-select-sm"
                    style="width:170px; height:38px; display:inline-block; vertical-align:middle;">
                    ${opts}
                </select>`;
    }

    function injectSelect(years) {
        $(`#year-filter-${tableId}`).remove();
        const $select = $(renderSelect(years));

        // Insérer après le bouton Exporter dans la toolbar de cette table
        const $toolbar = $(`#${tableId}`).closest('.card').find('.dt-buttons');
        const $firstBtn = $toolbar.find('.btn:first-child, .buttons-collection:first-child').first();
        $firstBtn.after($select);

        $select.on('change', function () {
            const val = $(this).val();
            applyYearFilter(val === 'all' ? null : parseInt(val));
        });
    }

    // Filtre côté client
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
        (fn) => fn[`_yearFilter_${tableId}`] !== true
    );

   function applyYearFilter(selectedYear) {
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
        (fn) => fn[`_yearFilter_${tableId}`] !== true
    );

    if (selectedYear === null) {
        dtTable.draw();
        return;
    }

    const filterFn = function (settings, data, dataIndex) {
        if (settings.nTable.id !== tableId) return true;
        const rowData = dtTable.row(dataIndex).data();
        if (!rowData) return true;

        const isClosed = rowData.cloture && rowData.cloture_date;

        if (!isClosed) {
            // Projet non clôturé → visible uniquement dans l'année courante
            return selectedYear === currentYear;
        } else {
            // Projet clôturé → visible dans l'année de sa cloture_date
            const closeYear = new Date(rowData.cloture_date).getFullYear();
            return closeYear === selectedYear;
        }
    };

    filterFn[`_yearFilter_${tableId}`] = true;
    $.fn.dataTable.ext.search.push(filterFn);
    dtTable.draw();
}

    // Chargement initial
    $.ajax({
        url: $(`#${tableId}`).data('projects-list-url'),
        type: 'GET',
        success: function (data) {
            const years = getAvailableYears(Array.isArray(data) ? data : []);
            injectSelect(years);
            applyYearFilter(currentYear);
        },
        error: function () {
            injectSelect([currentYear]);
            applyYearFilter(currentYear);
        },
    });

    // Rafraîchir les années après rechargement AJAX
    dtTable.on('xhr', function () {
        const json = dtTable.ajax.json();
        if (!json) return;
        const data = Array.isArray(json) ? json : [];
        const years = getAvailableYears(data);
        const $sel = $(`#year-filter-${tableId}`);
        const currentVal = $sel.val();

        let opts = `<option value="all">Toutes les années</option>`;
        opts += `<option value="${currentYear}">${currentYear} (en cours)</option>`;
        years.forEach(function (y) {
            if (y !== currentYear) opts += `<option value="${y}">${y}</option>`;
        });
        $sel.html(opts).val(currentVal);
    });
}

// Appeler pour les deux tables
buildDecompteYearFilter('paiementsTable', paiementsTable);
buildDecompteYearFilter('decomptesTable', decomptesTable);



    // Handle child row toggle
    // Handle child row toggle
    function handleChildRowToggle(tableId, commandeType) {
        const term = commandeType === "BC" ? "Paiement" : "Décomptes";
        $(`#${tableId} tbody`).on("click", "td.details-control", function () {
            const tr = $(this).closest("tr");
            const row = $(`#${tableId}`).DataTable().row(tr);
            const icon = $(this).find("i");

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass("shown");
                icon.removeClass("bx-chevron-down").addClass(
                    "bx-chevron-right"
                );
            } else {
                const projet_id = row.data().id;
                $.ajax({
                    url: decompteListUrl,
                    type: "GET",
                    data: { projet_id: projet_id },
                success: function (response) {
    if (response.success) {
        const marcheCadre = !!(
            response.data.projet && response.data.projet.marche_cadre
        );
        row.child(
            formatChildRow(response.data.decomptes, term, marcheCadre)
        ).show();
        tr.addClass("shown");
        icon.removeClass("bx-chevron-right").addClass(
            "bx-chevron-down"
        );
    } else {
        row.child(
            '<div class="text-center">Erreur lors du chargement des décomptes</div>'
        ).show();
        tr.addClass("shown");
    }
},
                    error: function (xhr) {
                        console.error(
                            "Erreur AJAX:",
                            xhr.status,
                            xhr.responseText
                        );
                        row.child(
                            '<div class="text-center">Erreur lors du chargement des décomptes</div>'
                        ).show();
                        tr.addClass("shown");
                    },
                });
            }
        });
    }

    handleChildRowToggle("decomptesTable", "BC");
    handleChildRowToggle("paiementsTable", "Marche");

    // Handle delete decompte/paiement
$("[id$='Table'] tbody").on("click", ".delete-decompte", function () {
    const decompteId = $(this).data("id");
    const tr = $(this).closest("tr");
    const childRow = tr.closest(".child");
    const parentTr = childRow.prev("tr");
    const tableId = parentTr.closest("table").attr("id");
    const table = $(`#${tableId}`).DataTable();
    const term = tableId === "decomptesTable" ? "Paiement" : "Décomptes";

    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: `Voulez-vous vraiment supprimer ce ${term.toLowerCase()} ? Cette action est irréversible.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Oui, supprimer",
        cancelButtonText: "Annuler",
        customClass: {
            confirmButton: "btn btn-danger",
            cancelButton: "btn btn-secondary",
        },
        buttonsStyling: false,
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: "Suppression en cours...",
                text: "Veuillez patienter.",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            $.ajax({
                url: `/decomptes/${decompteId}`,
                type: "DELETE",
                success: function (response) {
                    if (response.success) {
                        successSound.play().catch((error) => console.warn("Erreur lors de la lecture du son:", error));
                        Swal.fire({
                            title: "Succès !",
                            text: `Le ${term.toLowerCase()} a été supprimé avec succès. Montant restant: ${response.data.montant_decompte_restant} DH`,
                            icon: "success",
                            confirmButtonText: "OK",
                            customClass: {
                                confirmButton: "btn btn-success",
                            },
                            buttonsStyling: false,
                        }).then(() => {
                            decomptesTable.ajax.reload(null, false);
                            paiementsTable.ajax.reload(null, false);
                        });
                    } else {
                        Swal.fire("Erreur !", response.message, "error");
                        errorSound.play().catch((error) => console.warn("Erreur lors de la lecture du son:", error));
                    }
                },
                error: function (xhr) {
                    console.error("Erreur AJAX:", xhr.status, xhr.responseText);
                    Swal.fire("Erreur !", `Erreur lors de la suppression du ${term.toLowerCase()}.`, "error");
                    errorSound.play().catch((error) => console.warn("Erreur lors de la lecture du son:", error));
                },
            });
        }
    });
});

    // Handle montant_dp input for RG calculation
    $("#montant_dp").on("input", function () {
        const montant_dp = parseFloat($(this).val()) || 0;
        const rg_dp = montant_dp * 0.1;

        if (
            !$("#rg_dp").prop("disabled") &&
            !$("#rg_dp").hasClass("user-modified")
        ) {
            $("#rg_dp").val(rg_dp.toFixed(2));
        } else if ($("#rg_dp").prop("disabled")) {
            $("#rg_dp").val("0.00");
        }
    });

    $("#rg_dp").on("input", function () {
        if (!$(this).prop("disabled")) {
            $(this).addClass("user-modified");
        }
    });

    // Handle add decompte/paiement
    $(document).on("click", ".add-decompte", function () {
        $("#form_method").val("POST"); // Reset to POST for adding
    $("#modal-action").text("Ajouter"); // Reset modal title
    $("#decompte_id").val(""); // Clear decompte_id
        const projet_id = $(this).data("id");
        const commande_type = $(this).data("commande-type");
        const term = commande_type === "BC" ? "Paiement" : "Décomptes";

        // Reset form and validation
        $("#create-decompte-form")[0].reset();
        $("#create-decompte-form .is-invalid").removeClass("is-invalid");
        $("#create-decompte-form .invalid-feedback").hide();
        $("#decompte_projet_id").val(projet_id);
        $("#commande_type").val(commande_type);
        $("#rg_dp").removeClass("user-modified").prop("disabled", false);
        $("#type_decompte").prop("disabled", false);
        $("#rg_dp_container").show();

        // Update modal labels
        $(
            "#modal-title-term, #type-term, #montant-term, #montant-dp-term, #rg-term, #date-term"
        ).text(term);
        $("#rg_restant_display, #montant_decompte_restant_display")
            .text("-")
            .removeClass("text-danger");

        const loadingModal = Swal.fire({
            title: "Chargement...",
            text: "Récupération des données du projet",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: $(
                `#${
                    commande_type === "BC" ? "decomptesTable" : "paiementsTable"
                }`
            ).data("projects-list-url"),
            type: "GET",
            success: function (response) {
                loadingModal.close();
                const projet = response.find((p) => p.id == projet_id);
                if (!projet) {
                    Swal.fire("Erreur!", "Projet non trouvé.", "error");
                    return;
                }
                // Gestion du champ tranche pour les marchés cadre
    if (projet.marche_cadre) {
        $("#tranche_container").show();
        $("#tranche").prop("required", true).val("");
    } else {
        $("#tranche_container").hide();
        $("#tranche").prop("required", false).val("");
    }

                const rgRestant = parseFloat(
                    projet.rg_restant?.replace(/,/g, "") || 0
                );
                const montantDecompteRestant = parseFloat(
                    projet.montant_decompte_restant?.replace(/,/g, "") || 0
                );
                const rgRestantDisplay = isNaN(rgRestant)
                    ? "-"
                    : rgRestant.toLocaleString("fr-FR", {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                      });
                const montantDecompteRestantDisplay = isNaN(
                    montantDecompteRestant
                )
                    ? "-"
                    : montantDecompteRestant.toLocaleString("fr-FR", {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                      });

                $("#rg_restant_display").text(rgRestantDisplay + " DH");
                $("#montant_decompte_restant_display").text(
                    montantDecompteRestantDisplay + " DH"
                );

                if (commande_type === "BC") {
                    $("#rg_dp_container").hide();
                    $("#rg_dp").val("0").prop("disabled", true);
                    $("#type_decompte").val("Définitif").prop("disabled", true);
                    $(
                        "#create-decompte-form input[name='type_decompte'][type='hidden']"
                    ).remove();
                    $("#create-decompte-form").append(
                        $("<input>").attr({
                            type: "hidden",
                            name: "type_decompte",
                            value: "Définitif",
                        })
                    );
                } else {
                    $(
                        "#create-decompte-form input[name='type_decompte'][type='hidden']"
                    ).remove();
                    if (rgRestant <= 0) {
                        $("#rg_restant_display").addClass("text-danger");
                        $("#rg_dp").prop("disabled", true).val("0.00");
                        $("#rg_dp")
                            .siblings(".invalid-feedback")
                            .text("La retenue de garantie est épuisée.")
                            .show();
                    } else {
                        $("#rg_dp").prop("disabled", false);
                        $("#rg_dp").siblings(".invalid-feedback").hide();
                    }
                }

                if (montantDecompteRestant <= 0) {
                    $("#montant_decompte_restant_display").addClass(
                        "text-danger"
                    );
                    Swal.fire({
                        title: "Attention!",
                        text: `Le montant restant pour les ${term.toLowerCase()}s est insuffisant.`,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Continuer quand même",
                        cancelButtonText: "Annuler",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $("#addDecompteModal").modal("show");
                        }
                    });
                } else {
                    $("#addDecompteModal").modal("show");
                }
            },
            error: function (xhr) {
                loadingModal.close();
                console.error("Erreur AJAX:", xhr.status, xhr.responseText);
                Swal.fire(
                    "Erreur!",
                    "Impossible de charger les données du projet.",
                    "error"
                );
            },
        });
    });

    // Handle type_decompte change
    $("#type_decompte").on("change", function () {
        const typeDecompte = $(this).val();
        const commande_type = $("#commande_type").val();
        if (
            typeDecompte === "Définitif" &&
            !$("#rg_dp").prop("disabled") &&
            commande_type !== "BC"
        ) {
            $("#rg_dp").prop("required", true);
            $('label[for="rg_dp"]').html(
                `Retenue de Garantie ${
                    commande_type === "BC" ? "Paiement" : "Décompte"
                } <span class="text-danger">*</span>`
            );
        } else {
            $("#rg_dp").prop("required", false);
            $('label[for="rg_dp"]').html(
                `Retenue de Garantie ${
                    commande_type === "BC" ? "Paiement" : "Décompte"
                }`
            );
        }
    });
$(document).on("click", ".edit-decompte", function () {
    const decompteId = $(this).data("id");
    const tableId = $(this).closest("table").closest("tr").prev("tr").closest("table").attr("id");
    const commande_type = tableId === "decomptesTable" ? "BC" : "Marche";
    const term = commande_type === "BC" ? "Paiement" : "Décomptes";

    // Reset form and validation
    $("#create-decompte-form")[0].reset();
    $("#create-decompte-form .is-invalid").removeClass("is-invalid");
    $("#create-decompte-form .invalid-feedback").hide();
    $("#form_method").val("PUT");
    $("#modal-action").text("Modifier");
    $("#rg_dp").removeClass("user-modified").prop("disabled", false);
    $("#type_decompte").prop("disabled", false);
    $("#rg_dp_container").show();

    // Update modal labels
    $("#modal-title-term, #type-term, #montant-term, #montant-dp-term, #rg-term, #date-term").text(term);
    $("#rg_restant_display, #montant_decompte_restant_display").text("-").removeClass("text-danger");

    const loadingModal = Swal.fire({
        title: "Chargement...",
        text: "Récupération des données du décompte",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    $.ajax({
        url: `/decomptes/${decompteId}/edit`,
        type: "GET",
        success: function (response) {
            if (response.success) {
                const decompte = response.data.decompte;
                const projet = response.data.projet;

                // Populate form
                $("#decompte_id").val(decompte.id);
                $("#decompte_projet_id").val(decompte.projet_id);
                $("#commande_type").val(commande_type);
                $("#type_decompte").val(decompte.type_decompte);
                $("#montant_dp").val(parseFloat(decompte.montant_dp.replace(/,/g, "")));
                $("#rg_dp").val(decompte.rg_dp ? parseFloat(decompte.rg_dp.replace(/,/g, "")) : "");
                $("#revision_prix").val(decompte.revision_prix ? parseFloat(decompte.revision_prix.replace(/,/g, "")) : "");
                $("#date_dp").val(decompte.date_dp);

                // Update RG and montant restant
                const rgRestant = parseFloat(projet.rg_restant.replace(/,/g, "") || 0);
                const montantDecompteRestant = parseFloat(projet.montant_decompte_restant.replace(/,/g, "") || 0);
                $("#rg_restant_display").text(rgRestant.toLocaleString("fr-FR", { minimumFractionDigits: 2 }) + " DH");
                $("#montant_decompte_restant_display").text(montantDecompteRestant.toLocaleString("fr-FR", { minimumFractionDigits: 2 }) + " DH");

                if (commande_type === "BC") {
                    $("#rg_dp_container").hide();
                    $("#rg_dp").val("0").prop("disabled", true);
                    $("#type_decompte").val("Définitif").prop("disabled", true);
                    $("#create-decompte-form input[name='type_decompte'][type='hidden']").remove();
                    $("#create-decompte-form").append($("<input>").attr({ type: "hidden", name: "type_decompte", value: "Définitif" }));
                } else {
                    $("#create-decompte-form input[name='type_decompte'][type='hidden']").remove();
                    if (rgRestant <= 0 && parseFloat(decompte.rg_dp || 0) === 0) {
                        $("#rg_restant_display").addClass("text-danger");
                        $("#rg_dp").prop("disabled", true).val("0.00");
                        $("#rg_dp").siblings(".invalid-feedback").text("La retenue de garantie est épuisée.").show();
                    }
                }

                if (montantDecompteRestant <= 0) {
                    $("#montant_decompte_restant_display").addClass("text-danger");
                }

                loadingModal.close();
                $("#addDecompteModal").modal("show");
            } else {
                loadingModal.close();
                Swal.fire("Erreur!", response.message, "error");
            }
        },
        error: function (xhr) {
            loadingModal.close();
            Swal.fire("Erreur!", "Erreur lors du chargement des données.", "error");
        },
    });
});
    // Handle form submission
$("#create-decompte-form").on("submit", function (e) {
    e.preventDefault();
    const submitButton = $(this).find('button[type="submit"]');
    submitButton.prop("disabled", true);
    const commande_type = $("#commande_type").val();
    const term = commande_type === "BC" ? "Paiement" : "Décomptes";
    const method = $("#form_method").val();
    const decompteId = $("#decompte_id").val();
    const url = method === "PUT" ? `/decomptes/${decompteId}` : "/decomptes";

    const formData = new FormData(this);
    const cleanNumber = (value) => {
        if (!value || value === "") return null;
        return parseFloat(value.replace(/[^0-9.-]/g, "")) || 0;
    };

    formData.set("montant_dp", cleanNumber(formData.get("montant_dp")));
    formData.set("rg_dp", cleanNumber(formData.get("rg_dp")) || "0");
    formData.set("revision_prix", cleanNumber(formData.get("revision_prix")) || "");
    formData.set("_method", method);

    if (commande_type === "BC") {
        formData.set("rg_dp", "0");
        formData.set("type_decompte", "Définitif");
    }

    let isValid = true;
    $(this).find("[required]").each(function () {
        if (!$(this).val()) {
            $(this).addClass("is-invalid");
            $(this).siblings(".invalid-feedback").show();
            isValid = false;
        } else {
            $(this).removeClass("is-invalid");
            $(this).siblings(".invalid-feedback").hide();
        }
    });

    if (!isValid) {
        submitButton.prop("disabled", false);
        return;
    }

    const loadingToast = Swal.fire({
        title: "Envoi en cours...",
        text: `Veuillez patienter pendant le traitement du ${term.toLowerCase()}`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    $.ajax({
        url: url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            loadingToast.close();
            submitButton.prop("disabled", false);
            if (response.success) {
                successSound.play().catch((error) => console.warn("Erreur son:", error));
                $("#create-decompte-form")[0].reset();
                $("#addDecompteModal").modal("hide");
                Swal.fire({
                    title: "Succès!",
                    text: `${term} ${method === "PUT" ? "modifié" : "ajouté"} avec succès! Montant restant: ${response.data.montant_decompte_restant} DH`,
                    icon: "success",
                }).then(() => {
                    decomptesTable.ajax.reload(null, false);
                    paiementsTable.ajax.reload(null, false);
                });
            } else {
                Swal.fire("Erreur!", response.message || `Erreur lors de l'opération.`, "error");
                errorSound.play().catch((error) => console.warn("Erreur son:", error));
            }
        },
        error: function (xhr) {
            loadingToast.close();
            submitButton.prop("disabled", false);
            let errorMsg = "Erreur serveur. Veuillez réessayer.";
            if (xhr.responseJSON) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join("<br>");
                    for (const field in xhr.responseJSON.errors) {
                        const inputField = $(`#${field}`);
                        if (inputField.length) {
                            inputField.addClass("is-invalid");
                            inputField.siblings(".invalid-feedback").text(xhr.responseJSON.errors[field][0]).show();
                        }
                    }
                } else if (xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
            }
            Swal.fire({ title: "Erreur!", html: errorMsg, icon: "error" });
            errorSound.play().catch((error) => console.warn("Erreur son:", error));
        },
    });
});

    // Input validation for montant_dp
    $("#montant_dp").on("input", function () {
        const value = parseFloat($(this).val());
        const maxValue = parseFloat(
            $("#montant_decompte_restant_display")
                .text()
                .replace(/[^\d.-]/g, "")
        );
        if (value > maxValue && !isNaN(maxValue)) {
            $(this).addClass("is-invalid");
            $(this)
                .siblings(".invalid-feedback")
                .text(
                    `Le montant ne peut pas dépasser le montant restant de ${maxValue.toFixed(
                        2
                    )} DH`
                )
                .show();
        } else {
            $(this).removeClass("is-invalid");
            $(this).siblings(".invalid-feedback").hide();
        }
    });

    // Input validation for rg_dp
    $("#rg_dp").on("input", function () {
        $(this).addClass("user-modified");
        const value = parseFloat($(this).val());
        const rgRestant = parseFloat(
            $("#rg_restant_display")
                .text()
                .replace(/[^\d.-]/g, "")
        );
        if (isNaN(value) || value < 0) {
            $(this).addClass("is-invalid");
            $(this)
                .siblings(".invalid-feedback")
                .text("La retenue de garantie doit être positive")
                .show();
        } else if (value > rgRestant && !isNaN(rgRestant)) {
            $(this).addClass("is-invalid");
            $(this)
                .siblings(".invalid-feedback")
                .text(
                    `La retenue de garantie ne peut pas dépasser ${rgRestant.toFixed(
                        2
                    )} DH`
                )
                .show();
        } else {
            $(this).removeClass("is-invalid");
            $(this).siblings(".invalid-feedback").hide();
        }
    });

    // Handle view decomptes/paiements
    $(document).on("click", ".view-decomptes", function () {
        const projet_id = $(this).data("id");
        const commande_type = $(this).data("commande-type");
        const term = commande_type === "BC" ? "Paiement" : "Décomptes";

        $(
            "#view-modal-title-term, #decomptes-table-term, #montant-table-term, #date-table-term"
        ).text(term);

        const loadingModal = Swal.fire({
            title: "Chargement...",
            text: "Récupération des détails du projet",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: decompteListUrl,
            type: "GET",
            data: { projet_id: projet_id },
            success: function (response) {
                if (response.success) {
                    const data = response.data;
                    const projet = data.projet;

                    // Populate project details
                    $("#project-details-body").html(`
                        <tr><td><strong>Référence:</strong></td><td>${
                            projet.num_p || "-"
                        }</td></tr>
                        <tr><td><strong>Intitulé:</strong></td><td>${
                            projet.intitule || "-"
                        }</td></tr>
                        <tr><td><strong>Date Début:</strong></td><td>${
                            projet.date_debut || "-"
                        }</td></tr>
                        <tr><td><strong>Budget:</strong></td><td>${
                            projet.budget
                                ? parseFloat(
                                      projet.budget.replace(/,/g, "")
                                  ).toLocaleString("fr-FR", {
                                      minimumFractionDigits: 2,
                                  }) + " DH"
                                : "-"
                        }</td></tr>
                        <tr><td><strong>RG:</strong></td><td>${
                            projet.rg
                                ? parseFloat(
                                      projet.rg.replace(/,/g, "")
                                  ).toLocaleString("fr-FR", {
                                      minimumFractionDigits: 2,
                                  }) + " DH"
                                : "-"
                        }</td></tr>
                        <tr><td><strong>Délai Exécution:</strong></td><td>${
                            projet.delai_execution || "-"
                        }</td></tr>
                        <tr><td><strong>Réception Définitive:</strong></td><td>${
                            projet.reception_definitive || "-"
                        }</td></tr>
                        <tr><td><strong>Travaux Exécutés:</strong></td><td>${
                            projet.travaux_executier
                                ? parseFloat(
                                      projet.travaux_executier.replace(/,/g, "")
                                  ).toLocaleString("fr-FR", {
                                      minimumFractionDigits: 2,
                                  }) + " DH"
                                : "-"
                        }</td></tr>
                    `);

                    // Populate jours d'arrêt
                    $("#jour_d_arret").text(data.jour_d_arret || "0");

                    // Populate decomptes table
                    let decomptesHtml = "";
                    if (data.decomptes && data.decomptes.length > 0) {
                        data.decomptes.forEach((decompte) => {
                            const documentLink = decompte.document_path
                                ? `<a href="${decompte.document_path}" target="_blank">Voir</a>`
                                : "-";
                            decomptesHtml += `
                                <tr>
                                    <td>${decompte.type_decompte}</td>
                                    <td>${
                                        decompte.montant_dp
                                            ? parseFloat(
                                                  decompte.montant_dp.replace(
                                                      /,/g,
                                                      ""
                                                  )
                                              ).toLocaleString("fr-FR", {
                                                  minimumFractionDigits: 2,
                                              })
                                            : "-"
                                    }</td>
                                    <td>${
                                        decompte.rg_dp
                                            ? parseFloat(
                                                  decompte.rg_dp.replace(
                                                      /,/g,
                                                      ""
                                                  )
                                              ).toLocaleString("fr-FR", {
                                                  minimumFractionDigits: 2,
                                              })
                                            : "-"
                                    }</td>
                                    <td>${
                                        decompte.revision_prix
                                            ? parseFloat(
                                                  decompte.revision_prix.replace(
                                                      /,/g,
                                                      ""
                                                  )
                                              ).toLocaleString("fr-FR", {
                                                  minimumFractionDigits: 2,
                                              })
                                            : "-"
                                    }</td>
                                    <td>${decompte.date_dp || "-"}</td>
                                    <td>${documentLink}</td>
                                </tr>
                            `;
                        });
                    } else {
                        decomptesHtml = `<tr><td colspan="6" class="text-center">Aucun ${term.toLowerCase()} pour ce projet.</td></tr>`;
                    }
                    $("#decomptes-table-body").html(decomptesHtml);

                    loadingModal.close();
                    $("#viewDecomptesModal").modal("show");
                } else {
                    loadingModal.close();
                    Swal.fire(
                        "Erreur!",
                        response.message ||
                            "Erreur lors du chargement des détails.",
                        "error"
                    );
                }
            },
            error: function (xhr) {
                loadingModal.close();
                console.error("Erreur AJAX:", xhr.status, xhr.responseText);
                Swal.fire(
                    "Erreur!",
                    "Erreur serveur lors du chargement des détails.",
                    "error"
                );
            },
        });
    });

let isPrinting = false;
    async function printAllProjects(tableId, commandeType) {
        const term =
            commandeType === "BC"
                ? "Situation de Bon de Commande"
                : "Situation des Marchés";
        Swal.fire({
            title: "Génération de l'impression...",
            text: "Veuillez patienter, cela peut prendre un moment.",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        try {
            const table = $(`#${tableId}`).DataTable();
            const projects = table.rows().data().toArray();

            if (!projects || projects.length === 0) {
                throw new Error(`Aucun ${term.toLowerCase()} à imprimer.`);
            }

            const printData = [];
            for (const projet of projects) {
                const response = await $.ajax({
                    url: decompteListUrl,
                    type: "GET",
                    data: { projet_id: projet.id },
                });

                if (!response.success || !response.data) {
                    console.warn(
                        `Impossible de charger les ${term.toLowerCase()}s pour le projet ${
                            projet.id
                        }`
                    );
                    continue;
                }

                const formatNumber = (value) => {
                    if (value === undefined || value === null || value === "")
                        return "-";
                    const cleanedValue = String(value).replace(/,/g, "");
                    const parsed = parseFloat(cleanedValue);
                    return isNaN(parsed)
                        ? "-"
                        : parsed
                              .toString()
                              .replace(/\B(?=(\d{3})+(?!\d))/g, "");
                };

                const formattedProjet = {
                    num_p: projet.num_p || "-",
                    intitule: projet.intitule || "-",
                    date_debut: projet.date_debut || "-",
                    budget: formatNumber(projet.budget),
                    rg: formatNumber(projet.rg),
                    caution_definitif: formatNumber(projet.caution_definitif),
                    delai_execution: projet.delai_execution || "-",
                    reception_definitive: projet.reception_definitive || "-",
                    travaux_executier: formatNumber(projet.travaux_executier),
                    total_decompte: formatNumber(projet.total_decompte),
                    cloture: projet.cloture || "-",
                    date_offre: projet.date_offre || "-",
                    date_marche: projet.date_marche || "-",
                    ville: projet.ville || "-",
                    maitre_ouvrage: projet.maitre_ouvrage || "-",
                    assurance_montant:
                        projet.assurance_montant !== null &&
                        projet.assurance_montant !== undefined
                            ? formatNumber(projet.assurance_montant)
                            : "-",
                    total_revision: formatNumber(projet.total_revision || "-"),
                    description: projet.description || "-",
                    ordres_service: response.data.ordres_service || [],
                    commande_type: projet.commande_type || "Marché",
                };

                const formattedDecomptes = response.data.decomptes
                    .filter((d) =>
                        commandeType === "BC"
                            ? d.type_decompte === "Définitif"
                            : true
                    )
                    .map((decompte) => ({
                        ...decompte,
                        montant_dp: formatNumber(decompte.montant_dp),
                        rg_dp: decompte.rg_dp
                            ? formatNumber(decompte.rg_dp)
                            : "-",
                        revision_prix: decompte.revision_prix
                            ? formatNumber(decompte.revision_prix)
                            : "-",
                    }));

                printData.push({
                    projet: formattedProjet,
                    decomptes: formattedDecomptes,
                    jour_d_arret: response.data.jour_d_arret || "0",
                    total_decompte: formattedProjet.total_decompte,
                    total_rg: formattedProjet.rg,
                    travaux_executier: formattedProjet.travaux_executier,
                });
            }

            const logoUrl =
                companySettings && companySettings.logo
                    ? `${window.location.origin}/storage/${companySettings.logo}`
                    : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

            let htmlContent = `
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <meta charset="UTF-8">
                    <title>Situation des ${term} - Tous les Projets</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 10mm; color: #333; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                        .header { display: flex; align-items: center; border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; }
                        .header img { max-width: 100px; margin-right: 20px; }
                        .company-info { font-size: 12px; line-height: 1.5; text-align: center; position: fixed; bottom: 10mm; width: 100%; }
                        .company-info p { margin: 2px 0; }
                        .title-section { margin-bottom: 20px; text-align: center; }
                        .title-section h1 { font-size: 24px; margin: 0; color: #0056b3; font-weight: bold; }
                        .title-section p { font-size: 14px; margin: 5px 0; }
                        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; page-break-inside: auto; font-size: 10px; }
                        th { background-color: #99ccff; color: #000; font-weight: bold; padding: 6px; text-align: center; border: 1px solid #ddd; }
                        td { padding: 6px; border: 1px solid #ddd; text-align: center; }
                        tr:nth-child(even) { background-color: #f9f9f9; }
                        h2 { color: #0056b3; margin: 25px 0 10px; }
                        h3 { font-size: 16px; color: #0056b3; margin-top: 20px; margin-bottom: 10px; text-align: center; font-weight: bold; }
                        p { font-size: 12px; }
                        .project-info { margin-bottom: 20px; }
                        .project-info p { margin: 5px 0; }
                        .project-section { margin-bottom: 40px; page-break-before: always; }
                        .first-page { page-break-before: avoid; page-break-inside: avoid; }
                        @page { size: landscape; margin: 10mm; }
                        @media print {
                            table { page-break-inside: auto; }
                            tr { page-break-inside: avoid; page-break-after: auto; }
                            th, td { font-size: 9px; padding: 5px; }
                            .project-section { page-break-before: always; }
                            .first-page { page-break-before: avoid; page-break-inside: avoid; }
                            .company-info { position: fixed; bottom: 0; width: 100%; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <img src="${logoUrl}" alt="Logo" />
                    </div>
                    <div class="title-section">
                        <h1>Situation des ${term} ${new Date().getFullYear()}</h1>
                        <p>Date: ${new Date().toLocaleDateString("fr-FR")}</p>
                    </div>
            `;

            printData.forEach((data, index) => {
                const projet = data.projet;
                const decomptes = data.decomptes;
                const isBC = projet.commande_type === "BC";
                const title = isBC
                    ? "Situation de Bon de Commande"
                    : "Situation des Marchés";
                const term = isBC ? "Paiement" : "Décomptes";

                const calculateCautionDefinitive = (budget) => {
                    const budgetValue = parseFloat(
                        budget?.replace(/,/g, "") || 0
                    );
                    return isNaN(budgetValue)
                        ? "-"
                        : (budgetValue * 0.03)
                              .toString()
                              .replace(/\B(?=(\d{3})+(?!\d))/g, "");
                };

                const calculateWorkProgress = (travaux_executier, budget) => {
                    if (!travaux_executier || !budget) return "-";
                    const travaux = parseFloat(
                        String(travaux_executier).replace(/,/g, "")
                    );
                    const budgetValue = parseFloat(
                        String(budget).replace(/,/g, "")
                    );
                    if (
                        isNaN(travaux) ||
                        isNaN(budgetValue) ||
                        budgetValue === 0
                    )
                        return "-";
                    const percentage = (travaux / budgetValue) * 100;
                    return percentage.toFixed(2) + "%";
                };

                const getTotalJoursArret = (ordres_service) => {
                    const arrets = ordres_service.filter(
                        (ordre) => ordre.type === "arret"
                    );
                    return arrets
                        .reduce(
                            (total, arret) =>
                                total + (parseInt(arret.nbrjour) || 0),
                            0
                        )
                        .toString();
                };

                const ordresArret =
                    projet.ordres_service.filter(
                        (ordre) => ordre.type === "arret"
                    ) || [];
                const ordresReprise =
                    projet.ordres_service.filter(
                        (ordre) => ordre.type === "reprise"
                    ) || [];

                const mainTable = `
                    <div class="project-info">
                        <p><strong>Intitulé du projet:</strong> ${
                            projet.intitule
                        }</p>
                        <p><strong>Description:</strong> ${
                            projet.description
                        }</p>
                    </div>
                    <table>
                        <thead>
                            <tr style="background-color: #99ccff;">
                                <th>N°</th>
                                <th>DATE D'OFFRE</th>
                                <th>DATE MARCHE</th>
                                <th>VILLE</th>
                                <th>Maître d'Ouvrage</th>
                                <th>REFERENCE ${isBC ? "BC" : "MARCHE"}</th>
                                <th>MONTANT ${isBC ? "BC" : "MARCHE"}</th>
                                ${!isBC ? "<th>RG<br>7%</th>" : ""}
                                <th>ORDRE DE<br>SERVICE</th>
                                <th>ORDRE<br>D'ARRET</th>
                                <th>ORDRE DE<br>REPRISE</th>
                                <th>JOURS<br>D'ARRÊT</th>
                                <th>DELAI D'EXECUTION EN MOIS</th>
                                <th>RECEPTION DEFINITIVE</th>
                                <th>ASSURANCE</th>
                                <th>TOTAL REVISION DES PRIX</th>
                                <th>CAUTION DEFINITIVE<br>3%</th>
                                <th>POURCENTAGE TRAVAUX EXÉCUTÉS</th>
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
                                                        }">${
                                                            projet.num_p || "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.date_offre ||
                                                            "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.date_marche ||
                                                            "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.ville || "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.maitre_ouvrage ||
                                                            "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.intitule ||
                                                            "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.budget || "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  !isFirstRow
                                                      ? ""
                                                      : !isBC
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.rg || "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.date_debut ||
                                                            "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              <td>${
                                                  arret.date_ordre || "-"
                                              }</td>
                                              <td>${
                                                  reprise
                                                      ? reprise.date_ordre ||
                                                        "-"
                                                      : "-"
                                              }</td>
                                              <td>${arret.nbrjour || "-"}</td>
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.delai_execution ||
                                                            "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.reception_definitive ||
                                                            "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.assurance_montant ||
                                                            "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${
                                                            projet.total_revision ||
                                                            "-"
                                                        }</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${calculateCautionDefinitive(
                                                            projet.budget
                                                        )}</td>`
                                                      : ""
                                              }
                                              ${
                                                  isFirstRow
                                                      ? `<td rowspan="${
                                                            ordresArret.length
                                                        }">${calculateWorkProgress(
                                                            projet.travaux_executier,
                                                            projet.budget
                                                        )}</td>`
                                                      : ""
                                              }
                                          </tr>`;
                                          })
                                          .join("")
                                    : `<tr>
                                          <td>${projet.num_p || "-"}</td>
                                          <td>${projet.date_offre || "-"}</td>
                                          <td>${projet.date_marche || "-"}</td>
                                          <td>${projet.ville || "-"}</td>
                                          <td>${
                                              projet.maitre_ouvrage || "-"
                                          }</td>
                                          <td>${projet.intitule || "-"}</td>
                                          <td>${projet.budget || "-"}</td>
                                          ${
                                              !isBC
                                                  ? `<td>${
                                                        projet.rg || "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          <td>${projet.date_debut || "-"}</td>
                                          <td>-</td>
                                          <td>-</td>
                                          <td>-</td>
                                          <td>${
                                              projet.delai_execution || "-"
                                          }</td>
                                          <td>${
                                              projet.reception_definitive || "-"
                                          }</td>
                                          <td>${
                                              projet.assurance_montant || "-"
                                          }</td>
                                          <td>${
                                              projet.total_revision || "-"
                                          }</td>
                                          <td>${calculateCautionDefinitive(
                                              projet.budget
                                          )}</td>
                                          <td>${calculateWorkProgress(
                                              projet.travaux_executier,
                                              projet.budget
                                          )}</td>
                                      </tr>`
                            }
                            <tr style="background-color: #99ccff;">
                                <td colspan="${
                                    isBC ? 9 : 10
                                }" style="text-align: right; font-weight: bold;">TOTAL</td>
                                <td></td>
                                <td></td>
                                <td>${getTotalJoursArret(
                                    projet.ordres_service
                                )}</td>
                                <td colspan="3"></td>
                                <td>${projet.total_revision || "-"}</td>
                                <td>${calculateCautionDefinitive(
                                    projet.budget
                                )}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                `;

                let decompteTables = "";
                const dpGroupSize = 4;

                let headerRow1First = `
                    <th rowspan="2" style="background-color: #ccffcc !important;">TOTAL ${term.toUpperCase()}S</th>
                    ${
                        !isBC
                            ? '<th rowspan="2" style="background-color: #ccffcc !important;">TOTAL RG</th>'
                            : ""
                    }
                    <th rowspan="2" style="background-color: #ffff99 !important;">Travaux Exécutés</th>`;
                let headerRow2First = "";
                let dataRowFirst = `
                    <td style="background-color: #ccffcc !important;">${
                        data.total_decompte
                    }</td>
                    ${
                        !isBC
                            ? `<td style="background-color: #ccffcc !important;">${data.total_rg}</td>`
                            : ""
                    }
                    <td style="background-color: #ffff99 !important;">${
                        data.travaux_executier
                    }</td>`;

                const firstGroup = decomptes.slice(0, 3);
                firstGroup.forEach((d, i) => {
                    headerRow1First += `<th colspan="${
                        isBC ? 3 : 4
                    }" style="background-color: #99ccff !important;">${term} N°${
                        i + 1
                    }</th>`;
                    headerRow2First += `
                        <th style="background-color: #99ccff !important;">Montant ${term}</th>
                        <th style="background-color: #99ccff !important;">Révision</th>
                        <th style="background-color: #99ccff !important;">Date</th>
                        ${
                            !isBC
                                ? `<th style="background-color: #99ccff !important;">RG</th>`
                                : ""
                        }`;
                    dataRowFirst += `
                        <td>${d.montant_dp}</td>
                        <td>${d.revision_prix}</td>
                        <td>${
                            d.date_dp
                                ? new Date(d.date_dp).toLocaleDateString(
                                      "fr-FR"
                                  )
                                : "-"
                        }</td>
                        ${!isBC ? `<td>${d.rg_dp}</td>` : ""}`;
                });

                decompteTables += `
                    <h3>${title}</h3>
                    <table class="decompte-table">
                        <thead><tr>${headerRow1First}</tr><tr>${headerRow2First}</tr></thead>
                        <tbody><tr>${dataRowFirst}</tr></tbody>
                    </table>`;

                for (
                    let groupIndex = 3;
                    groupIndex < decomptes.length;
                    groupIndex += dpGroupSize
                ) {
                    const groupStart = groupIndex + 1;
                    const groupEnd = Math.min(
                        groupIndex + dpGroupSize,
                        decomptes.length
                    );
                    const currentGroup = decomptes.slice(
                        groupIndex,
                        groupIndex + dpGroupSize
                    );

                    let headerRow1 = "";
                    let headerRow2 = "";
                    let dataRow = "";

                    currentGroup.forEach((d, i) => {
                        const dpNumber = groupIndex + i + 1;
                        headerRow1 += `<th colspan="${
                            isBC ? 3 : 4
                        }" style="background-color: #99ccff !important;">${term} N°${dpNumber}</th>`;
                        headerRow2 += `
                            <th style="background-color: #99ccff !important;">Montant ${term}</th>
                            <th style="background-color: #99ccff !important;">Révision</th>
                            <th style="background-color: #99ccff !important;">Date</th>
                            ${
                                !isBC
                                    ? `<th style="background-color: #99ccff !important;">RG</th>`
                                    : ""
                            }`;
                        dataRow += `
                            <td>${d.montant_dp}</td>
                            <td>${d.revision_prix}</td>
                            <td>${
                                d.date_dp
                                    ? new Date(d.date_dp).toLocaleDateString(
                                          "fr-FR"
                                      )
                                    : "-"
                            }</td>
                            ${!isBC ? `<td>${d.rg_dp}</td>` : ""}`;
                    });

                    decompteTables += `
                        <h3>${title} (${term} ${groupStart} à ${term} ${groupEnd})</h3>
                        <table class="decompte-table">
                            <thead><tr>${headerRow1}</tr><tr>${headerRow2}</tr></thead>
                            <tbody><tr>${dataRow}</tr></tbody>
                        </table>`;
                }

                const sectionClass =
                    index === 0
                        ? "project-section first-page"
                        : "project-section";
                htmlContent += `<div class="${sectionClass}">${mainTable}${decompteTables}</div>`;
            });

            htmlContent += `
                <div class="company-info">
                    <p>Siège social: ${
                        companySettings.address || "Non spécifié"
                    } Capital: ${
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
            } | IF: ${companySettings.tax_id || "Non spécifié"} TP: ${
                companySettings.tax_id || "Non spécifique"
            } ICE: ${companySettings.patent_number || "Non spécifique"}</p>
                    <p>C.B.: ${
                        companySettings.account_number || "Non spécifié"
                    }, ${companySettings.bank_name || "Non spécifié"} Email: ${
                companySettings.email || "Non spécifié"
            }</p>
                </div>
                </body>
                </html>`;

            const iframe = document.createElement("iframe");
            iframe.name = "printFrame";
            iframe.style.cssText =
                "position:absolute;width:0;height:0;border:none;";
            document.body.appendChild(iframe);

            const printDocument = iframe.contentWindow.document;
            printDocument.open();
            printDocument.write(htmlContent);
            printDocument.close();

            Swal.close();
            setTimeout(() => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                document.body.removeChild(iframe);
            }, 500);
        } catch (error) {
            console.error(
                `Erreur lors de l'impression des ${term.toLowerCase()}s:`,
                error
            );
            Swal.fire(
                "Erreur!",
                `Impossible de générer le document d'impression: ${error.message}`,
                "error"
            );
        }
    }

    // Print single decompte/paiement
    $(document).on("click", ".print-decompte", function () {
        const projetId = $(this).data("id");
        printDecompteById(projetId);
    });

    async function printDecompteById(projetId) {
 
        try {
            if (!projetId) throw new Error("ID du projet non fourni.");

            console.log("Fetching decompte data for projet_id:", projetId);
            const response = await $.ajax({
                url: decompteListUrl,
                type: "GET",
                data: { projet_id: projetId },
            });

            if (!response.success || !response.data) {
                throw new Error(
                    response.message ||
                        "Échec du chargement des données des décomptes."
                );
            }

            // Try fetching from both tables if necessary
            let projetData = null;
            try {
                const bcResponse = await $.ajax({
                    url: $("#decomptesTable").data("projects-list-url"),
                    type: "GET",
                });
                projetData = bcResponse.find((projet) => projet.id == projetId);
            } catch (e) {
                console.warn(
                    "Project not found in BC table, trying Marche table"
                );
            }

            if (!projetData) {
                const marcheResponse = await $.ajax({
                    url: $("#paiementsTable").data("projects-list-url"),
                    type: "GET",
                });
                projetData = marcheResponse.find(
                    (projet) => projet.id == projetId
                );
            }

            if (!projetData) {
                throw new Error(
                    "Projet non trouvé dans les données récupérées."
                );
            }

            const printData = {
                decomptes: response.data.decomptes,
                total_decompte: projetData.total_decompte,
                total_rg: projetData.rg,
                travaux_executier: projetData.travaux_executier,
                projet: projetData,
                jour_d_arret: response.data.jour_d_arret || "0",
            };

            printSingleDecompte(printData, projetData.commande_type);
        } catch (error) {
            console.error("Error in printDecompteById:", error);
            Swal.fire(
                "Erreur!",
                `Impossible de charger les données: ${error.message}`,
                "error"
            );
        }
    }

    function printSingleDecompte(data, commande_type) {
        const logoUrl =
            companySettings && companySettings.logo
                ? `${window.location.origin}/storage/${companySettings.logo}`
                : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

        const isBC = commande_type === "BC";
        const title = isBC
            ? "Situation de Bon de Commande"
            : "Situation des Marchés";
        const term = isBC ? "Paiement" : "Décomptes";

        if (!data || (!data.decomptes && !data.total_decompte)) {
            const emptyHtml = `
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <meta charset="UTF-8">
                    <title>${title}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20mm; text-align: center; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                        .header { display: flex; align-items: center; border-bottom: 2px solid #000; margin-bottom: 20px; }
                        .header img { max-width: 100px; margin-right: 20px; }
                        .company-info { font-size: 12px; line-height: 1.5; text-align: center; position: fixed; bottom: 10mm; width: 100%; }
                        .company-info p { margin: 2px 0; }
                        .empty-state { margin-top: 50px; }
                        .title-section { margin-bottom: 20px; text-align: center; }
                        .title-section h1 { font-size: 24px; margin: 0; color: #0056b3; font-weight: bold; }
                        @page { size: A4 landscape; margin: 10mm; }
                        @media print { body { margin: 10mm; } .company-info { position: fixed; bottom: 0; width: 100%; } }
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
                    <div class="empty-state">
                        <h2>Aucune donnée disponible</h2>
                        <p>Veuillez sélectionner un élément valide</p>
                    </div>
                    <div class="company-info">
                        <p>Siège social: ${
                            companySettings.address || "Non spécifié"
                        } Capital: ${
                companySettings.capital
                    ? companySettings.capital.toLocaleString("fr-FR", {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                      }) + " MAD"
                    : "Non spécifié"
            } | Tél: ${companySettings.phone_number || "Non spécifié"}</p>
                        <p>R.C.: ${
                            companySettings.commercial_register ||
                            "Non spécifié"
                        } | CNSS: ${
                companySettings.cnss_number || "Non spécifié"
            } | IF: ${companySettings.tax_id || "Non spécifié"} TP: ${
                companySettings.tax_id || "Non spécifique"
            } ICE: ${companySettings.patent_number || "Non spécifique"}</p>
                        <p>C.B.: ${
                            companySettings.account_number || "Non spécifié"
                        }, ${
                companySettings.bank_name || "Non spécifié"
            } Email: ${companySettings.email || "Non spécifié"}</p>
                    </div>
                </body>
                </html>`;
            loadIframeContent(emptyHtml);
            return;
        }

        const projet = data.projet;
        const decomptes = isBC
            ? (data.decomptes || []).filter(
                  (d) => d.type_decompte === "Définitif"
              )
            : data.decomptes || [];

        const calculateCautionDefinitive = (budget) => {
            const budgetValue = parseFloat(budget?.replace(/,/g, "") || 0);
            return isNaN(budgetValue)
                ? "-"
                : (budgetValue * 0.03)
                      .toString()
                      .replace(/\B(?=(\d{3})+(?!\d))/g, "");
        };

        const calculateWorkProgress = (travaux_executier, budget) => {
            if (!travaux_executier || !budget) return "-";
            const travaux = parseFloat(
                String(travaux_executier).replace(/,/g, "")
            );
            const budgetValue = parseFloat(String(budget).replace(/,/g, ""));
            if (isNaN(travaux) || isNaN(budgetValue) || budgetValue === 0)
                return "-";
            const percentage = (travaux / budgetValue) * 100;
            return percentage.toFixed(2) + "%";
        };

        const getTotalJoursArret = (ordres_service) => {
            const arrets = ordres_service.filter(
                (ordre) => ordre.type === "arret"
            );
            return arrets
                .reduce(
                    (total, arret) => total + (parseInt(arret.nbrjour) || 0),
                    0
                )
                .toString();
        };

        const ordresArret =
            projet.ordres_service.filter((ordre) => ordre.type === "arret") ||
            [];
        const ordresReprise =
            projet.ordres_service.filter((ordre) => ordre.type === "reprise") ||
            [];

        let htmlContent = `
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <title>${title}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 10mm; color: #333; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                    .header { display: flex; align-items: center; border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; }
                    .header img { max-width: 100px; margin-right: 20px; }
                    .company-info { font-size: 12px; line-height: 1.5; text-align: center; position: fixed; bottom: 10mm; width: 100%; }
                    .company-info p { margin: 2px 0; }
                    .title-section { margin-bottom: 20px; text-align: center; }
                    .title-section h1 { font-size: 24px; margin: 0; color: #0056b3; font-weight: bold; }
                    .title-section p { font-size: 14px; margin: 5px 0; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; page-break-inside: auto; font-size: 10px; }
                    th { background-color: #99ccff; color: #000; font-weight: bold; padding: 6px; text-align: center; border: 1px solid #ddd; }
                    td { padding: 6px; border: 1px solid #ddd; text-align: center; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    h3 { font-size: 16px; color: #0056b3; margin-top: 20px; margin-bottom: 10px; text-align: center; font-weight: bold; }
                    .project-info { margin-bottom: 20px; }
                    .project-info p { margin: 5px 0; font-size: 12px; }
                    @page { size: landscape; margin: 10mm; }
                    @media print {
                        table { page-break-inside: auto; }
                        tr { page-break-inside: avoid; page-break-after: auto; }
                        th, td { font-size: 9px; padding: 5px; }
                        .company-info { position: fixed; bottom: 0; width: 100%; }
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
                        projet.intitule || "-"
                    }</p>
                    <p><strong>Description:</strong> ${
                        projet.description || "-"
                    }</p>
                </div>
                <table>
                    <thead>
                        <tr style="background-color: #99ccff;">
                            <th>N°</th>
                            <th>DATE D'OFFRE</th>
                            <th>DATE MARCHE</th>
                            <th>VILLE</th>
                            <th>Maître d'Ouvrage</th>
                            <th>REFERENCE ${isBC ? "BC" : "MARCHE"}</th>
                            <th>MONTANT ${isBC ? "BC" : "MARCHE"}</th>
                            ${!isBC ? "<th>RG<br>7%</th>" : ""}
                            <th>ORDRE DE<br>SERVICE</th>
                            <th>ORDRE<br>D'ARRET</th>
                            <th>ORDRE DE<br>REPRISE</th>
                            <th>JOURS<br>D'ARRÊT</th>
                            <th>DELAI D'EXECUTION EN MOIS</th>
                            <th>RECEPTION DEFINITIVE</th>
                            <th>ASSURANCE</th>
                            <th>TOTAL REVISION DES PRIX</th>
                            <th>CAUTION DEFINITIVE<br>3%</th>
                            <th>POURCENTAGE TRAVAUX EXÉCUTÉS</th>
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
                                                    }">${
                                                        projet.num_p || "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.date_offre || "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.date_marche ||
                                                        "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.ville || "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.maitre_ouvrage ||
                                                        "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.intitule || "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.budget || "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              !isFirstRow
                                                  ? ""
                                                  : !isBC
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${projet.rg || "-"}</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.date_debut || "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          <td>${arret.date_ordre || "-"}</td>
                                          <td>${
                                              reprise
                                                  ? reprise.date_ordre || "-"
                                                  : "-"
                                          }</td>
                                          <td>${arret.nbrjour || "-"}</td>
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.delai_execution ||
                                                        "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.reception_definitive ||
                                                        "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.assurance_montant ||
                                                        "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${
                                                        projet.total_revision ||
                                                        "-"
                                                    }</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${calculateCautionDefinitive(
                                                        projet.budget
                                                    )}</td>`
                                                  : ""
                                          }
                                          ${
                                              isFirstRow
                                                  ? `<td rowspan="${
                                                        ordresArret.length
                                                    }">${calculateWorkProgress(
                                                        projet.travaux_executier,
                                                        projet.budget
                                                    )}</td>`
                                                  : ""
                                          }
                                      </tr>`;
                                      })
                                      .join("")
                                : `<tr>
                                      <td>${projet.num_p || "-"}</td>
                                      <td>${projet.date_offre || "-"}</td>
                                      <td>${projet.date_marche || "-"}</td>
                                      <td>${projet.ville || "-"}</td>
                                      <td>${projet.maitre_ouvrage || "-"}</td>
                                      <td>${projet.intitule || "-"}</td>
                                      <td>${projet.budget || "-"}</td>
                                      ${
                                          !isBC
                                              ? `<td>${projet.rg || "-"}</td>`
                                              : ""
                                      }
                                      <td>${projet.date_debut || "-"}</td>
                                      <td>-</td>
                                      <td>-</td>
                                      <td>-</td>
                                      <td>${projet.delai_execution || "-"}</td>
                                      <td>${
                                          projet.reception_definitive || "-"
                                      }</td>
                                      <td>${
                                          projet.assurance_montant || "-"
                                      }</td>
                                      <td>${projet.total_revision || "-"}</td>
                                      <td>${calculateCautionDefinitive(
                                          projet.budget
                                      )}</td>
                                      <td>${calculateWorkProgress(
                                          projet.travaux_executier,
                                          projet.budget
                                      )}</td>
                                  </tr>`
                        }
                        <tr style="background-color: #99ccff;">
                            <td colspan="${
                                isBC ? 9 : 10
                            }" style="text-align: right; font-weight: bold;">TOTAL</td>
                            <td></td>
                            <td></td>
                            <td>${getTotalJoursArret(
                                projet.ordres_service
                            )}</td>
                            <td colspan="3"></td>
                            <td>${projet.total_revision || "-"}</td>
                            <td>${calculateCautionDefinitive(
                                projet.budget
                            )}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
        `;

        let decompteTables = "";
        const dpGroupSize = 4;

        let headerRow1First = `
            <th rowspan="2" style="background-color: #ccffcc !important;">TOTAL ${term.toUpperCase()}S</th>
            ${
                !isBC
                    ? '<th rowspan="2" style="background-color: #ccffcc !important;">TOTAL RG</th>'
                    : ""
            }
            <th rowspan="2" style="background-color: #ffff99 !important;">Travaux Exécutés</th>`;
        let headerRow2First = "";
        let dataRowFirst = `
            <td style="background-color: #ccffcc !important;">${
                isNaN(parseFloat(data.total_decompte?.replace(/,/g, "")))
                    ? "-"
                    : parseFloat(data.total_decompte.replace(/,/g, ""))
                          .toString()
                          .replace(/\B(?=(\d{3})+(?!\d))/g, "") || "-"
            }</td>
            ${
                !isBC
                    ? `<td style="background-color: #ccffcc !important;">${
                          isNaN(parseFloat(data.total_rg?.replace(/,/g, "")))
                              ? "-"
                              : parseFloat(data.total_rg.replace(/,/g, ""))
                                    .toString()
                                    .replace(/\B(?=(\d{3})+(?!\d))/g, "") || "-"
                      }</td>`
                    : ""
            }
            <td style="background-color: #ffff99 !important;">${
                isNaN(parseFloat(data.travaux_executier?.replace(/,/g, "")))
                    ? "-"
                    : parseFloat(data.travaux_executier.replace(/,/g, ""))
                          .toString()
                          .replace(/\B(?=(\d{3})+(?!\d))/g, "") || "-"
            }</td>`;

        const firstGroup = decomptes.slice(0, 3);
        firstGroup.forEach((d, i) => {
            headerRow1First += `<th colspan="${
                isBC ? 3 : 4
            }" style="background-color: #99ccff !important;">${term} N°${
                i + 1
            }</th>`;
            headerRow2First += `
                <th style="background-color: #99ccff !important;">Montant ${term}</th>
                <th style="background-color: #99ccff !important;">Révision</th>
                <th style="background-color: #99ccff !important;">Date</th>
                ${
                    !isBC
                        ? `<th style="background-color: #99ccff !important;">RG</th>`
                        : ""
                }`;
            dataRowFirst += `
                <td>${
                    isNaN(parseFloat(d.montant_dp?.replace(/,/g, "")))
                        ? "-"
                        : parseFloat(d.montant_dp.replace(/,/g, ""))
                              .toString()
                              .replace(/\B(?=(\d{3})+(?!\d))/g, "") || "-"
                }</td>
                <td>${
                    isNaN(parseFloat(d.revision_prix?.replace(/,/g, "")))
                        ? "-"
                        : parseFloat(d.revision_prix.replace(/,/g, ""))
                              .toString()
                              .replace(/\B(?=(\d{3})+(?!\d))/g, "") || "-"
                }</td>
                <td>${
                    d.date_dp
                        ? new Date(d.date_dp).toLocaleDateString("fr-FR")
                        : "-"
                }</td>
                ${
                    !isBC
                        ? `<td>${
                              isNaN(parseFloat(d.rg_dp?.replace(/,/g, "")))
                                  ? "-"
                                  : parseFloat(d.rg_dp.replace(/,/g, ""))
                                        .toString()
                                        .replace(/\B(?=(\d{3})+(?!\d))/g, "") ||
                                    "-"
                          }</td>`
                        : ""
                }`;
        });

        decompteTables += `
        
        <table>
            <thead><tr>${headerRow1First}</tr><tr>${headerRow2First}</tr></thead>
            <tbody><tr>${dataRowFirst}</tr></tbody>
        </table>`;

        // Additional groups (if more than 3 décomptes/paiements)
        for (
            let groupIndex = 3;
            groupIndex < decomptes.length;
            groupIndex += dpGroupSize
        ) {
            const groupStart = groupIndex + 1;
            const groupEnd = Math.min(
                groupIndex + dpGroupSize,
                decomptes.length
            );
            const currentGroup = decomptes.slice(
                groupIndex,
                groupIndex + dpGroupSize
            );

            let headerRow1 = "";
            let headerRow2 = "";
            let dataRow = "";

            currentGroup.forEach((d, i) => {
                const dpNumber = groupIndex + i + 1;
                headerRow1 += `<th colspan="${
                    isBC ? 3 : 4
                }" style="background-color: #99ccff !important;">${term} N°${dpNumber}</th>`;
                headerRow2 += `
                <th style="background-color: #99ccff !important;">Montant ${term}</th>
                <th style="background-color: #99ccff !important;">Révision</th>
                <th style="background-color: #99ccff !important;">Date</th>
                ${
                    !isBC
                        ? `<th style="background-color: #99ccff !important;">RG</th>`
                        : ""
                }`;
                dataRow += `
                <td>${
                    isNaN(parseFloat(d.montant_dp?.replace(/,/g, "")))
                        ? "-"
                        : parseFloat(d.montant_dp.replace(/,/g, ""))
                              .toString()
                              .replace(/\B(?=(\d{3})+(?!\d))/g, "") || "-"
                }</td>
                <td>${
                    isNaN(parseFloat(d.revision_prix?.replace(/,/g, "")))
                        ? "-"
                        : parseFloat(d.revision_prix.replace(/,/g, ""))
                              .toString()
                              .replace(/\B(?=(\d{3})+(?!\d))/g, "") || "-"
                }</td>
                <td>${
                    d.date_dp
                        ? new Date(d.date_dp).toLocaleDateString("fr-FR")
                        : "-"
                }</td>
                ${
                    !isBC
                        ? `<td>${
                              isNaN(parseFloat(d.rg_dp?.replace(/,/g, "")))
                                  ? "-"
                                  : parseFloat(d.rg_dp.replace(/,/g, ""))
                                        .toString()
                                        .replace(/\B(?=(\d{3})+(?!\d))/g, "") ||
                                    "-"
                          }</td>`
                        : ""
                }`;
            });

            decompteTables += `
            <h3>${title} (${term} ${groupStart} à ${term} ${groupEnd})</h3>
            <table>
                <thead><tr>${headerRow1}</tr><tr>${headerRow2}</tr></thead>
                <tbody><tr>${dataRow}</tr></tbody>
            </table>`;
        }

        const html = `
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>${title}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20mm; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .header { display: flex; align-items: center; border-bottom: 2px solid #000; margin-bottom: 20px; }
            .header img { max-width: 100px; margin-right: 20px; }
            .company-info { font-size: 12px; line-height: 1.5; text-align: center; position: fixed; bottom: 10mm; width: 100%; }
            .company-info p { margin: 2px 0; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; page-break-inside: avoid; font-size: 10px; }
            th, td { padding: 8px; border: 1px solid #ddd; text-align: center; }
            th { background-color: #99ccff; color: #000; font-weight: bold; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            h3 { color: #0056b3; margin: 25px 0 10px; text-align: center; font-weight: bold; }
            h1 { text-align: center; color: #0056b3; font-size: 24px; margin: 0 0 10px; font-weight: bold; }
            .title-section { margin-bottom: 20px; text-align: center; }
            .title-section p { font-size: 14px; margin: 5px 0; }
            @page { size: A4 landscape; margin: 10mm; }
            @media print { body { margin: 10mm; } table { font-size: 9px; page-break-inside: auto; } tr { page-break-inside: avoid; page-break-after: auto; } .company-info { position: fixed; bottom: 0; width: 100%; } }
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
        ${decompteTables}
        <div class="company-info">
            <p>Siège social: ${
                companySettings.address || "Non spécifié"
            } Capital: ${
            companySettings.capital
                ? companySettings.capital.toLocaleString("fr-FR", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                  }) + " MAD"
                : "Non spécifié"
        } | Tél: ${companySettings.phone_number || "Non spécifié"}</p>
            <p>R.C.: ${
                companySettings.commercial_register || "Non spécifié"
            } | CNSS: ${companySettings.cnss_number || "Non spécifié"} | IF: ${
            companySettings.tax_id || "Non spécifié"
        } TP: ${companySettings.tax_id || "Non spécifique"} ICE: ${
            companySettings.patent_number || "Non spécifique"
        }</p>
            <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${
            companySettings.bank_name || "Non spécifié"
        } Email: ${companySettings.email || "Non spécifié"}</p>
        </div>
    </body>
    </html>`;

        const iframe = document.createElement("iframe");
        iframe.name = "printFrame";
        iframe.style.cssText =
            "position:absolute;width:0;height:0;border:none;";
        document.body.appendChild(iframe);

        const printDocument = iframe.contentWindow.document;
        printDocument.open();
        printDocument.write(html);
        printDocument.close();

        setTimeout(() => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            document.body.removeChild(iframe);
        }, 500);
    }

    function loadIframeContent(htmlContent) {
        const iframe = document.getElementById("printIframe");
        const iframeDoc =
            iframe.contentDocument || iframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(htmlContent);
        iframeDoc.close();

        iframe.style.height = iframeDoc.body.scrollHeight + "px";

        $("#printDecompteModal").modal("show");
    }


    $("#printIframeBtn").on("click", function () {
        var iframe = document.getElementById("printIframe");
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    });

    $(document).on("click", ".view-decomptes", function () {
        const projet_id = $(this).data("id");
        const commande_type = $(this).data("commande-type");
        const term = commande_type === "BC" ? "Paiement" : "Décomptes";

        $(
            "#view-modal-title-term, #decomptes-table-term, #montant-table-term, #date-table-term"
        ).text(term);

        const loadingModal = Swal.fire({
            title: "Chargement...",
            text: "Récupération des détails du projet",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: decompteListUrl,
            type: "GET",
            data: { projet_id: projet_id },
            success: function (response) {
                console.log(
                    "AJAX Success - Response:",
                    response,
                    "projet_id:",
                    projet_id
                );
                if (response.success) {
                    const data = response.data;
                    const projet = data.projet;
                    // Populate project details and table as before
                    // ...
                } else {
                    Swal.fire(
                        "Erreur!",
                        response.message ||
                            "Erreur lors du chargement des détails.",
                        "error"
                    );
                }
                loadingModal.close();
                $("#viewDecomptesModal").modal("show");
            },
            error: function (xhr) {
                console.error(
                    "AJAX Error:",
                    xhr.status,
                    xhr.responseText,
                    "projet_id:",
                    projet_id
                );
                Swal.fire(
                    "Erreur!",
                    "Erreur serveur lors du chargement des détails.",
                    "error"
                );
                loadingModal.close();
            },
        });
    });
});
