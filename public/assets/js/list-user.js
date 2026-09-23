const successSound = new Audio("/assets/audio/success.mp3");
const errorSound = new Audio("/assets/audio/error.mp3");

$(document).ready(function () {
    console.log("Document ready, initializing DataTable");

    var dt_invoice_table = $("#usersTable");

    if (!dt_invoice_table.length) {
        console.error("Table #usersTable not found in the DOM");
        return;
    }

    // Toggle custom backdrop
    $("#add-new-record").on("show.bs.offcanvas", function () {
        $(".custom-backdrop").addClass("active");
    });

    $("#add-new-record").on("hide.bs.offcanvas", function () {
        $(".custom-backdrop").removeClass("active");
    });

    // Fetch dynamic menu permissions from the server
 $.ajax({
        url: '/menu-permissions',
        type: 'GET',
        success: function (response) {
        const rolePermissions = {
    'superadmin': response.menuPermissions.map(menu => menu.menu_name),
    'admin': [
        'nature_depenses.index', 'nature_depenses.store', 'nature_depenses.update', 'nature_depenses.destroy',
        'depenses.varie', 'depenses.depenses', 'depenses.edit', 'depenses.update', 'depenses.destroy','depenses.avancements', 'depenses.store','depenses.vehicle',
        'factures.index', 'factures.totals', 'factures.store', 'factures.show', 'factures.update', 'factures.destroy', 'factures.download', 'factures.vat-report', 'entities.store',   'bultin.index'
    ],
    'manager': [
        'nature_depenses.index', 'nature_depenses.store', 'nature_depenses.update', 'nature_depenses.destroy',
        'depenses.index', 'depenses.depenses', 'depenses.edit', 'depenses.update', 'depenses.destroy',
        'factures.index', 'factures.totals', 'factures.store', 'factures.show', 'factures.update', 'factures.destroy', 'factures.download', 'factures.vat-report', 'entities.store',   'bultin.index'
    ],
    'salarier': [
        'conge.index', 'conge.salarie.store', 'conge.download.pdf',
        'bulletinsa.index', 'bulletin.downloadPaySlip', 'bulletin.downloadHiddenPaySlip',   'bultin.index'

    ],
    'responsable': [
        'conge.index', 'conge.salarie.store', 'conge.download.pdf',
        'bulletinsa.index', 'bulletin.downloadPaySlip', 'bulletin.downloadHiddenPaySlip',
        'pointage.index', 'pointage.save'
    ]
};

            $("#role").on("change", function () {
                const selectedRole = $(this).find("option:selected").text().toLowerCase();
                console.log("Selected role:", selectedRole);
                $('input[name="menu_permissions[]"]').prop('checked', false);
                if (rolePermissions[selectedRole]) {
                    rolePermissions[selectedRole].forEach(function (permission) {
                        $(`input[name="menu_permissions[]"][value="${permission}"]`).prop('checked', true);
                    });
                }
            });
        },
        error: function (xhr) {
            console.error("Error fetching menu permissions:", xhr);
        }
    });

    if (dt_invoice_table.length) {
        console.log("Initializing DataTable for #usersTable");
        var dt_invoice = dt_invoice_table.DataTable({
            ajax: {
                url: "/users/list",
                dataSrc: "",
                error: function (xhr, error, thrown) {
                    console.error(
                        "AJAX error:",
                        xhr.status,
                        xhr.responseText,
                        error,
                        thrown
                    );
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text:
                            "Erreur lors du chargement des données : " +
                            xhr.status +
                            " " +
                            (xhr.responseJSON?.message || xhr.statusText),
                        confirmButtonText: "OK",
                        timer: 5000,
                    });
                },
            },
            columns: [
                { data: "username" },
                { data: "n_matricule_entreprise" },
                { data: "role_name" },
                { data: null, defaultContent: "" },
            ],
            columnDefs: [
                {
                    targets: 0,
                    responsivePriority: 2,
                    render: function (data, type, full) {
                        return full.username || "-";
                    },
                },
                {
                    targets: 1,
                    responsivePriority: 3,
                    render: function (data, type, full) {
                        return full.n_matricule_entreprise || "N/A";
                    },
                },
                {
                    targets: 2,
                    responsivePriority: 4,
                    render: function (data, type, full) {
                        var role = full.role_name
                            ? full.role_name.toLowerCase()
                            : "";
                        var badgeClass =
                            {
                                superadmin: "bg-label-primary",
                                admin: "bg-label-success",
                                manager: "bg-label-info",
                            }[role] || "bg-label-secondary";
                        return `<span class="badge ${badgeClass}">${
                            full.role_name || "N/A"
                        }</span>`;
                    },
                },
                {
                    targets: 3,
                    searchable: false,
                    orderable: false,
                    responsivePriority: 1,
                    render: function (data, type, full) {
                        return `
                            <div class="d-flex align-items-center">
                                <a href="javascript:;" class="text-body edit-user" data-id="${full.id}" data-bs-toggle="tooltip" title="Modifier"><i class="bx bx-edit mx-1"></i></a>
                                <a href="javascript:;" class="text-body delete-record" data-id="${full.id}" data-bs-toggle="tooltip" title="Supprimer"><i class="bx bx-trash mx-1"></i></a>
                            </div>
                        `;
                    },
                },
            ],
            order: [[0, "asc"]],
            language: {
                sLengthMenu: "_MENU_",
                search: "Rechercher",
                searchPlaceholder: "Rechercher un utilisateur",
                paginate: {
                    first: "Premier",
                    last: "Dernier",
                    next: "Suivant",
                    previous: "Précédent",
                },
                info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            },
            dom:
                '<"row ms-2 me-3"' +
                '<"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2"l<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3"B>>' +
                '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2"f<"invoice_status mb-3 mb-md-0">>' +
                ">t" +
                '<"row mx-2"' +
                '<"col-sm-12 col-md-6"i>' +
                '<"col-sm-12 col-md-6"p>' +
                ">",
            buttons: [
                {
                    text: '<i class="bx bx-plus me-1"></i>Ajouter un utilisateur',
                    className: "btn btn-primary",
                    attr: {
                        "data-bs-toggle": "offcanvas",
                        "data-bs-target": "#add-new-record",
                        "aria-controls": "add-new-record",
                    },
                },
            ],
            responsive: true,
            initComplete: function () {
                console.log("DataTable initialized successfully");
            },
        });

        dt_invoice_table.on("draw.dt", function () {
            console.log("DataTable drawn");
            var tooltipTriggerList = [].slice.call(
                document.querySelectorAll('[data-bs-toggle="tooltip"]')
            );
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    boundary: document.body,
                });
            });
        });
    }

    // Fonction pour normaliser les chaînes (gérer les accents et caractères spéciaux)
    function normalizeString(str) {
        return str
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .replace(/[^a-z0-9]/g, "");
    }

    // Gérer le changement dans le select du salarié pour générer le username et le mot de passe
    $("#id_salarie").on("change", function () {
        var selectedOption = $(this).find("option:selected");
        var nom = selectedOption.data("nom") || "";
        var prenom = selectedOption.data("prenom") || "";
        console.log("Nom:", nom, "Prénom:", prenom);

        var baseUsername = "";
        if (nom && prenom) {
            var cleanPrenom = normalizeString(prenom);
            var cleanNomFirstLetter = normalizeString(nom.charAt(0));
            baseUsername = cleanPrenom + "." + cleanNomFirstLetter;
            console.log("Generated baseUsername:", baseUsername);
        }

        if (baseUsername) {
            $("#generated-username").val(baseUsername);
            $("#generated-password").val(baseUsername); // Remplir le champ mot de passe
        } else {
            console.log("baseUsername is empty, resetting inputs");
            $("#generated-username").val("");
            $("#generated-password").val("");
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Le salarié sélectionné n'a pas de nom ou prénom valide.",
                confirmButtonText: "OK",
                timer: 5000,
            });
        }
    });

    // Gestion des boutons "Supprimer"
    $("#usersTable").on("click", ".delete-record", function () {
        let userId = $(this).data("id");

        Swal.fire({
            title: "Êtes-vous sûr ?",
            text: "Voulez-vous supprimer cet utilisateur ?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Oui, supprimer",
            cancelButtonText: "Annuler",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/users/" + userId,
                    type: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                            "content"
                        ),
                    },
                    success: function (response) {
                        if (response.success) {
                            dt_invoice_table.DataTable().ajax.reload();
                            Swal.fire({
                                icon: "success",
                                title: "Succès",
                                text: "Utilisateur supprimé avec succès !",
                                confirmButtonText: "OK",
                                didOpen: () => {
                                    successSound
                                        .play()
                                        .catch((error) =>
                                            console.log(
                                                "Erreur de lecture audio:",
                                                error
                                            )
                                        );
                                },
                                timer: 5000,
                            });
                        }
                    },
                    error: function (xhr) {
                        console.error(
                            "AJAX error:",
                            xhr.status,
                            xhr.responseJSON
                        );
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            text:
                                xhr.responseJSON?.message || "Erreur inconnue",
                            confirmButtonText: "OK",
                            timer: 5000,
                        });
                    },
                });
            }
        });
    });

    // Gestion des boutons "Modifier"
    $("#usersTable").on("click", ".edit-user", function () {
        let userId = $(this).data("id");

        $.ajax({
            url: "/users/" + userId + "/edit",
            type: "GET",
            success: function (response) {
                $("#editUserModal .modal-content").html(response);
                $("#editUserModal").modal("show");
            },
            error: function (xhr) {
                console.error("AJAX error:", xhr.status, xhr.responseJSON);
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: xhr.responseJSON?.message || "Erreur inconnue",
                    confirmButtonText: "OK",
                    timer: 5000,
                });
            },
        });
    });

 $(document).on("submit", "#updateUserForm", function (e) {
    e.preventDefault();

    let form = $(this);
    let actionUrl = form.attr("action");

    $.ajax({
        url: actionUrl,
        type: "POST", // Note: Using POST with @method('PUT') for Laravel compatibility
        data: form.serialize(),
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (response) {
            if (response.success) {
                let user = response.user;
                let row = dt_invoice_table.find("tr").filter(function () {
                    return $(this).find(".edit-user").data("id") == user.id;
                });

                row.find("td:eq(0)").text(user.username || "-");
                row.find("td:eq(2)").html(
                    `<span class="badge ${user.role_class}">${user.role || "N/A"}</span>`
                );

                $("#editUserModal").modal("hide");
                Swal.fire({
                    icon: "success",
                    title: "Succès",
                    text: response.message,
                    confirmButtonText: "OK",
                    didOpen: () => {
                        successSound
                            .play()
                            .catch((error) =>
                                console.log("Erreur de lecture audio:", error)
                            );
                    },
                    timer: 5000,
                });
                dt_invoice_table.DataTable().ajax.reload(null, false);
            }
        },
        error: function (xhr) {
            console.error("AJAX error:", xhr.status, xhr.responseJSON);
            let errors = xhr.responseJSON?.errors;
            let errorMessage = "Erreur lors de la mise à jour :\n";
            if (errors) {
                for (let field in errors) {
                    errorMessage += errors[field].join("\n") + "\n";
                }
            } else {
                errorMessage += xhr.responseJSON?.message || "Erreur inconnue";
            }
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: errorMessage,
                confirmButtonText: "OK",
                didOpen: () => {
                    errorSound
                        .play()
                        .catch((error) =>
                            console.log("Erreur de lecture audio:", error)
                        );
                },
                timer: 5000,
            });
        },
    });
});

    $(document).on("click", ".reset-password-link", function () {
        let userId = $(this).data("id");
        $("#resetPasswordForm").attr(
            "action",
            "/users/" + userId + "/reset-password"
        );
        $("#editUserModal").modal("hide");
        $("#resetPasswordModal").modal("show");
    });

    $(document).on("submit", "#resetPasswordForm", function (e) {
        e.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: form.serialize(),
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success) {
                    $("#resetPasswordModal").modal("hide");
                    Swal.fire({
                        icon: "success",
                        title: "Succès",
                        text: response.message,
                        confirmButtonText: "OK",
                        timer: 5000,
                    });
                }
            },
            error: function (xhr) {
                console.error("AJAX error:", xhr.status, xhr.responseJSON);
                let errors = xhr.responseJSON?.errors;
                let errorMessage = "Erreur lors de la réinitialisation :\n";
                if (errors) {
                    for (let field in errors) {
                        errorMessage += errors[field].join("\n") + "\n";
                    }
                } else {
                    errorMessage +=
                        xhr.responseJSON?.message || "Erreur inconnue";
                }
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: errorMessage,
                    confirmButtonText: "OK",
                    timer: 5000,
                });
            },
        });
    });

    $("#form-add-new-record").on("submit", function (e) {
        e.preventDefault();

        // Récupérer les données du formulaire
        var formData = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            role_id: $("#role").val(),
            id_salarie: $("#id_salarie").val(),
            menu_permissions: $('input[name="menu_permissions[]"]:checked').map(function() {
                return $(this).val();
            }).get(),
        };

        // Vérifier que tous les champs requis sont remplis
        if (!formData.role_id || !formData.id_salarie) {
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Veuillez remplir tous les champs requis (Rôle, Salarié).",
                confirmButtonText: "OK",
                timer: 5000,
            });
            return;
        }

        $.ajax({
            url: "/users",
            type: "POST",
            data: formData,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                $("#add-new-record").offcanvas("hide");
                $("#form-add-new-record")[0].reset();
                $("#generated-username").val("");
                $("#generated-password").val("");
                dt_invoice_table.DataTable().ajax.reload();
                Swal.fire({
                    icon: "success",
                    title: "Succès",
                    text: `Utilisateur ajouté avec succès avec le nom d'utilisateur : ${response.username}`,
                    confirmButtonText: "OK",
                    timer: 5000,
                });
                successSound
                    .play()
                    .catch((error) =>
                        console.log("Erreur de lecture audio:", error)
                    );
            },
            error: function (xhr) {
                console.error("AJAX error:", xhr.status, xhr.responseJSON);
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: xhr.responseJSON?.message || "Erreur inconnue",
                    confirmButtonText: "OK",
                    timer: 5000,
                });
                errorSound
                    .play()
                    .catch((error) =>
                        console.log("Erreur de lecture audio:", error)
                    );
            },
        });
    });
});