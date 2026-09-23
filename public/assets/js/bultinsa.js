$(document).ready(function () {
    console.log("bultinsa.js loaded"); // Debug: Confirmer le chargement du script

    // Vérifier si jQuery et DataTables sont chargés
    if (!window.jQuery || !$.fn.DataTable) {
        console.error("jQuery or DataTables is not loaded.");
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "jQuery ou DataTables n'est pas chargé.",
            timer: 5000,
        });
        return;
    }

    const table = $("#bulletinsTable");

    // Vérifier si l'élément table existe
    if (!table.length) {
        console.error("L'élément #bulletinsTable n'existe pas dans le DOM");
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "La table des bulletins n'a pas été trouvée dans la page.",
            timer: 5000,
        });
        return;
    }

    // Détruire toute instance DataTable existante
    if ($.fn.DataTable.isDataTable(table)) {
        table.DataTable().destroy();
        console.log("Existing DataTable destroyed");
    }

    // Fonction pour ajouter la recherche par colonne
    function addColumnSearch(tableSelector, dataTable) {
        $(`${tableSelector} thead tr`).clone(true).appendTo(`${tableSelector} thead`);
        $(`${tableSelector} thead tr:eq(1) th`).each(function (i) {
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

    try {
        // Initialiser DataTable
        const dataTable = table.DataTable({
            columns: [
                { orderable: true, searchable: true }, // Mois
                { orderable: false, searchable: false }, // Bulletin Paie
                { orderable: false, searchable: false }, // Bulletin Paie Caché
            ],
            ordering: true,
            columnDefs: [
                { targets: [0, 1, 2], render: (data) => data || "-" },
            ],
            language: {
                lengthMenu: "Afficher _MENU_ bulletins",
                search: "",
                searchPlaceholder: "Rechercher un bulletin",
                paginate: { next: "Suivant", previous: "Précédent" },
                info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments", 
                emptyTable: "Aucun bulletin trouvé.",
            },
            // DOM modifié pour créer une structure personnalisée
            dom: '<"row"<"col-sm-12"<"dt-custom-header d-flex justify-content-start align-items-center mb-3"<"dt-year-filter-container"><"dt-search-container"f>>>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            responsive: true,
            initComplete: function () {
                console.log("DataTable initComplete triggered"); // Debug: Track initComplete calls
                
                // Ajouter la recherche par colonne
                addColumnSearch("#bulletinsTable", this.api());

                // Récupérer les données depuis le div original dans le HTML
                const originalDiv = $('.card-datatable .dt-year-filter');
                let rawYears = originalDiv.data('years');
                const selectedYear = originalDiv.data('selected-year') || new Date().getFullYear();
                const baseUrl = originalDiv.data('base-url');
                
                console.log("Raw data-years (as string):", JSON.stringify(rawYears));

                let years = [];
                try {
                    years = typeof rawYears === 'string' ? JSON.parse(rawYears.replace(/"/g, '"')) : (Array.isArray(rawYears) ? rawYears : []);
                } catch (e) {
                    console.error("Failed to parse data-years:", e, "Raw data:", rawYears);
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: "Erreur dans les données des années.",
                        timer: 5000,
                    });
                    years = []; // Fallback to empty array
                }

                // Créer le sélecteur d'année avec un label
                const yearContainer = $('.dt-year-filter-container');
                const yearWrapper = $('<div class="d-flex align-items-center me-3">');
                const yearLabel = $('<label class="me-2 mb-0 fw-bold">Année:</label>');
                const yearSelect = $('<select class="form-control form-control-sm" style="width: auto; min-width: 100px;">');
                
                if (years.length === 0) {
                    yearSelect.append(`<option value="${selectedYear}" selected>${selectedYear}</option>`);
                } else {
                    years.forEach(year => {
                        yearSelect.append(`<option value="${year}" ${year == selectedYear ? 'selected' : ''}>${year}</option>`);
                    });
                }
                
                yearSelect.on('change', function () {
                    window.location.href = baseUrl + '?year=' + $(this).val();
                });
                
                yearWrapper.append(yearLabel).append(yearSelect);
                yearContainer.append(yearWrapper);

                // Modifier le conteneur de recherche pour ajouter un label
                const searchContainer = $('.dt-search-container');
                const searchWrapper = searchContainer.find('.dataTables_filter');
                searchWrapper.addClass('d-flex align-items-center');
                
                // Ajouter un label à la recherche
                const searchLabel = $('<label class="me-2 mb-0 fw-bold">Rechercher:</label>');
                const searchInput = searchWrapper.find('input');
                searchInput.before(searchLabel);
                searchInput.addClass('form-control-sm').css({
                    'width': 'auto',
                    'min-width': '200px'
                });

                console.log("Year selector and search elements positioned successfully");
            },
            drawCallback: function () {
                console.log("DataTable redrawn");
            },
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