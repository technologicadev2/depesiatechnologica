$(document).ready(function () {
    // Initialize DataTable
    var table = $("#natureDepenseTable").DataTable({
        dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        displayLength: 10,
        lengthMenu: [10, 25, 50, 75, 100],
        buttons: [
            {
                text: '<i class="bx bx-plus me-sm-1"></i><span class="d-sm-inline-block">Ajouter</span>',
                className: "create-new btn btn-primary",
                action: function () {
                    // Reset form and clear validation errors
                    $("#addNatureDepenseForm")[0].reset();
                    $("#addNatureDepenseForm").find(".is-invalid").removeClass("is-invalid");
                    $("#addNatureDepenseForm").find(".invalid-feedback").remove();
                    $("#addNatureDepenseModal").modal("show");
                },
            },
        ],
        responsive: true,
        language: {
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ éléments par page",
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
    });

    // Loading box functions
    function showLoadingBox() {
        $("#loading-box").show();
    }

    function hideLoadingBox() {
        $("#loading-box").hide();
    }

    // Client-side validation for add form
    $("#addNatureDepenseForm").on("submit", function (e) {
        e.preventDefault();
        var form = $(this);
        var designation = $("#designation").val().trim();

        if (!designation) {
            $("#designation").addClass("is-invalid");
            if (!$("#designation").next(".invalid-feedback").length) {
                $("#designation").after('<div class="invalid-feedback">La désignation est requise.</div>');
            }
            return;
        }

        // Hide modal and show confirmation
        $("#addNatureDepenseModal").modal("hide");

        Swal.fire({
            title: "Confirmer l'ajout",
            text: "Voulez-vous ajouter cette nature de dépense ?",
            icon: "question",
            position: "center",
            showCancelButton: true,
            confirmButtonText: "Oui, ajouter",
            cancelButtonText: "Annuler",
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
        }).then((result) => {
            if (result.isConfirmed) {
                showLoadingBox();
                $.ajax({
                    url: form.attr("action"),
                    method: "POST",
                    data: form.serialize(),
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    success: function (response) {
                        hideLoadingBox();
                        if (response.success) {
                            table.row.add([
                                response.natureDepense.designation,
                                `<div class="d-flex">
                                    <a href="javascript:;" class="btn btn-sm btn-icon item-edit me-2" data-id="${response.natureDepense.id}" title="Modifier">
                                        <i class="bx bxs-edit"></i>
                                    </a>
                                    <form action="/nature-depenses/${response.natureDepense.id}" method="POST" class="delete-form">
                                        <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-sm btn-icon" title="Supprimer">
                                            <i class="bx bxs-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>`,
                            ]).draw(false);

                            Swal.fire({
                                icon: "success",
                                title: "Succès",
                                text: response.message,
                                position: "center",
                                confirmButtonText: "OK",
                                timer: 5000,
                                timerProgressBar: true,
                                didOpen: () => {
                                    const successSound = new Audio('/assets/audio/success.mp3');
                                    successSound.play().catch(error => console.log('Erreur audio:', error));
                                },
                            });

                            form[0].reset();
                        }
                    },
                    error: function (xhr) {
                        hideLoadingBox();
                        let errorMessage = "Une erreur est survenue.";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join("<br>");
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            html: errorMessage,
                            position: "center",
                            confirmButtonText: "OK",
                            timer: 5000,
                            timerProgressBar: true,
                            didOpen: () => {
                                const errorSound = new Audio('/assets/audio/error.mp3');
                                errorSound.play().catch(error => console.log('Erreur audio:', error));
                            },
                        });
                        $("#addNatureDepenseModal").modal("show");
                    },
                });
            } else {
                $("#addNatureDepenseModal").modal("show");
            }
        });
    });

    // Handle edit button click
    $("#natureDepenseTable").on("click", ".item-edit", function () {
        var id = $(this).data("id");
        var designation = $(this).closest("tr").find("td:first").text().trim();
        $("#updateNatureDepenseForm").attr("action", "/nature-depenses/" + id);
        $("#edit-designation").val(designation);
        $("#edit-designation").removeClass("is-invalid");
        $("#edit-designation").next(".invalid-feedback").remove();
        $("#editNatureDepenseModal").modal("show");
    });

    // Client-side validation for edit form
    $("#updateNatureDepenseForm").on("submit", function (e) {
        e.preventDefault();
        var form = $(this);
        var designation = $("#edit-designation").val().trim();

        if (!designation) {
            $("#edit-designation").addClass("is-invalid");
            if (!$("#edit-designation").next(".invalid-feedback").length) {
                $("#edit-designation").after('<div class="invalid-feedback">La désignation est requise.</div>');
            }
            return;
        }

        $("#editNatureDepenseModal").modal("hide");

        Swal.fire({
            title: "Confirmer la modification",
            text: "Voulez-vous enregistrer ces modifications ?",
            icon: "question",
            position: "center",
            showCancelButton: true,
            confirmButtonText: "Oui, enregistrer",
            cancelButtonText: "Annuler",
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
        }).then((result) => {
            if (result.isConfirmed) {
                showLoadingBox();
                $.ajax({
                    url: form.attr("action"),
                    method: "POST",
                    data: form.serialize(),
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    success: function (response) {
                        hideLoadingBox();
                        if (response.success) {
                            var row = table.row($(`[data-id="${response.natureDepense.id}"]`).closest("tr"));
                            row.data([
                                response.natureDepense.designation,
                                row.data()[1],
                            ]).draw(false);

                            Swal.fire({
                                icon: "success",
                                title: "Succès",
                                text: response.message,
                                position: "center",
                                confirmButtonText: "OK",
                                timer: 5000,
                                timerProgressBar: true,
                                didOpen: () => {
                                    const successSound = new Audio('/assets/audio/success.mp3');
                                    successSound.play().catch(error => console.log('Erreur audio:', error));
                                },
                            });
                        }
                    },
                    error: function (xhr) {
                        hideLoadingBox();
                        let errorMessage = "Une erreur est survenue.";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join("<br>");
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            html: errorMessage,
                            position: "center",
                            confirmButtonText: "OK",
                            timer: 5000,
                            timerProgressBar: true,
                            didOpen: () => {
                                const errorSound = new Audio('/assets/audio/error.mp3');
                                errorSound.play().catch(error => console.log('Erreur audio:', error));
                            },
                        });
                        $("#editNatureDepenseModal").modal("show");
                    },
                });
            } else {
                $("#editNatureDepenseModal").modal("show");
            }
        });
    });

    // Handle delete form submission
    $("#natureDepenseTable").on("submit", ".delete-form", function (e) {
        e.preventDefault();
        var form = $(this);

        Swal.fire({
            title: "Êtes-vous sûr ?",
            text: "Cette nature de dépense sera supprimée définitivement !",
            icon: "warning",
            position: "center",
            showCancelButton: true,
            confirmButtonText: "Oui, supprimer",
            cancelButtonText: "Annuler",
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
        }).then((result) => {
            if (result.isConfirmed) {
                showLoadingBox();
                $.ajax({
                    url: form.attr("action"),
                    method: "POST",
                    data: form.serialize(),
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    success: function (response) {
                        hideLoadingBox();
                        if (response.success) {
                            table.row(form.closest("tr")).remove().draw(false);
                            Swal.fire({
                                icon: "success",
                                title: "Succès",
                                text: response.message,
                                position: "center",
                                confirmButtonText: "OK",
                                timer: 5000,
                                timerProgressBar: true,
                                didOpen: () => {
                                    const successSound = new Audio('/assets/audio/success.mp3');
                                    successSound.play().catch(error => console.log('Erreur audio:', error));
                                },
                            });
                        }
                    },
                    error: function (xhr) {
                        hideLoadingBox();
                        let errorMessage = "Une erreur est survenue.";
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            text: errorMessage,
                            position: "center",
                            confirmButtonText: "OK",
                            timer: 5000,
                            timerProgressBar: true,
                            didOpen: () => {
                                const errorSound = new Audio('/assets/audio/error.mp3');
                                errorSound.play().catch(error => console.log('Erreur audio:', error));
                            },
                        });
                    },
                });
            }
        });
    });
});