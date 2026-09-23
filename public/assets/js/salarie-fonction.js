"use strict";

const successSound = new Audio("/assets/audio/success.mp3");
const errorSound = new Audio("/assets/audio/error.mp3");

$(function () {
    // Set up CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    const dt_fonction_table = $(".invoice-list-table");

    if (dt_fonction_table.length) {
        const dt_fonction = dt_fonction_table.DataTable({
            ajax: {
                url: "/salaries/fonction/data",
                dataSrc: "",
                error: function (xhr, error, thrown) {
                    console.error("AJAX Error:", xhr, error, thrown);
                    Swal.fire({
                        title: "Erreur !",
                        text: "Impossible de charger les données. Vérifiez votre connexion ou contactez l'administrateur.",
                        icon: "error",
                    });
                },
            },
            columns: [{ data: "" }, { data: "designation" }, { data: null }],
            columnDefs: [
                {
                    className: "control",
                    responsivePriority: 2,
                    searchable: false,
                    orderable: false,
                    targets: 0,
                    render: function () {
                        return "";
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, full) {
                        return full.designation || "-";
                    },
                },
                {
                    targets: 2,
                    searchable: false,
                    orderable: false,
                    render: function (data, type, full) {
                        return `
                            <div class="d-flex align-items-center">
                                <a href="javascript:;" class="text-body edit-record" data-id="${full.id}" data-bs-toggle="tooltip" title="Modifier"><i class="bx bx-edit mx-1"></i></a>
                                <a href="javascript:;" class="text-body delete-record" data-id="${full.id}" data-bs-toggle="tooltip" title="Supprimer"><i class="bx bx-trash mx-1"></i></a>
                            </div>
                        `;
                    },
                },
            ],
            order: [[1, "asc"]],
            dom:
                '<"row ms-2 me-3"' +
                '<"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2"l<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3"B>>' +
                '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2"f>' +
                ">t" +
                '<"row mx-2"' +
                '<"col-sm-12 col-md-6"i>' +
                '<"col-sm-12 col-md-6"p>' +
                ">",
            language: {
                sLengthMenu: "_MENU_",
                search: "Rechercher",
                searchPlaceholder: "Recherche une fonction",
                paginate: {
                    first: "Premier",
                    last: "Dernier",
                    next: "Suivant",
                    previous: "Précédent",
                },
                info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            },
            buttons: [
                {
                    text: '<i class="bx bx-plus me-md-1"></i><span class="d-md-inline-block d-none">Ajouter une fonction</span>',
                    className: "btn btn-primary",
                    action: function () {
                        $("#fonctionForm")[0].reset();
                        $("#fonctionId").val("");
                        $("#modalFonction").modal("show");
                    },
                },
            ],
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function (row) {
                            const data = row.data();
                            return (
                                "Détails de " + (data.designation || "Fonction")
                            );
                        },
                    }),
                    type: "column",
                    renderer: function (api, rowIdx, columns) {
                        const data = $.map(columns, function (col) {
                            return col.title !== ""
                                ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                                       <td>${col.title}:</td>
                                       <td>${col.data}</td>
                                   </tr>`
                                : "";
                        }).join("");
                        return data
                            ? $('<table class="table"/><tbody />').append(data)
                            : false;
                    },
                },
            },
        });

        // Initialize tooltips on table draw
        dt_fonction_table.on("draw.dt", function () {
            const tooltipTriggerList = document.querySelectorAll(
                '[data-bs-toggle="tooltip"]'
            );
            tooltipTriggerList.forEach((tooltipTriggerEl) => {
                new bootstrap.Tooltip(tooltipTriggerEl, {
                    boundary: document.body,
                });
            });
        });

        // Handle delete action
        dt_fonction_table.on("click", ".delete-record", function () {
            const id = $(this).data("id");
            Swal.fire({
                title: "Êtes-vous sûr ?",
                text: "Vous ne pourrez pas revenir en arrière !",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Oui, supprimer !",
                cancelButtonText: "Annuler",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/salaries/fonction/${id}`,
                        type: "DELETE",
                        success: function () {
                            dt_fonction.ajax.reload(null, false);
                            successSound
                                .play()
                                .catch((err) =>
                                    console.error("Success Sound Error:", err)
                                );
                            Swal.fire(
                                "Supprimé !",
                                "La fonction a été supprimée.",
                                "success"
                            );
                        },
                        error: function (xhr) {
                            const errorMessage = xhr.responseJSON?.error || "Une erreur s'est produite lors de la suppression.";
                            errorSound
                                .play()
                                .catch((err) =>
                                    console.error("Error Sound Error:", err)
                                );
                            Swal.fire(
                                "Erreur !",
                                errorMessage,
                                "error"
                            );
                        },
                    });
                }
            });
        });

        // Handle edit action
        dt_fonction_table.on("click", ".edit-record", function () {
            const id = $(this).data("id");
            $.ajax({
                url: `/salaries/fonction/${id}`,
                type: "GET",
                success: function (data) {
                    $("#fonctionId").val(data.id);
                    $("#designation").val(data.designation);
                    $("#modalFonction").modal("show");
                },
                error: function (xhr) {
                    console.error("Edit Error:", xhr);
                    errorSound.play();
                    Swal.fire(
                        "Erreur !",
                        "Impossible de charger les données de la fonction.",
                        "error"
                    );
                },
            });
        });
    }

    // Handle form submission
    $("#fonctionForm").on("submit", function (e) {
        e.preventDefault();
        const id = $("#fonctionId").val();
        const url = id ? `/salaries/fonction/${id}` : "/salaries/fonction";
        const method = id ? "PUT" : "POST";

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function () {
                $("#modalFonction").modal("hide");
                dt_fonction_table.DataTable().ajax.reload(null, false);
                successSound
                    .play()
                    .catch((err) => console.error("Success Sound Error:", err));
                Swal.fire(
                    "Succès !",
                    "La fonction a été enregistrée.",
                    "success"
                );
            },
            error: function (xhr) {
                console.error("Form Submission Error:", xhr);
                const errorMessage = xhr.responseJSON?.error || "Une erreur s'est produite lors de l'enregistrement.";
                errorSound
                    .play()
                    .catch((err) => console.error("Error Sound Error:", err));
                Swal.fire(
                    "Erreur !",
                    errorMessage,
                    "error"
                );
            },
        });
    });

    // Adjust DataTables styling
    setTimeout(() => {
        $(".dataTables_filter .form-control").removeClass("form-control-sm");
        $(".dataTables_length .form-select").removeClass("form-select-sm");
    }, 300);
});