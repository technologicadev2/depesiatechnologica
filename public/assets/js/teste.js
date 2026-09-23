const successSound = new Audio("/assets/audio/success.mp3");
const errorSound = new Audio("/assets/audio/error.mp3");

// Fonction générique pour l'impression
function printContent(html, title) {
    const iframe = $("<iframe>", { style: "display: none;" }).appendTo("body")[0];
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
        successSound.play();
    }, 500);
}

// Fonction d'impression du tableau de pointage
function printPointageTable(table, title, columns) {
    const logoUrl = companySettings && companySettings.logo
        ? `${window.location.origin}/storage/${companySettings.logo}`
        : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;
    const data = table
        .rows({ search: "applied" })
        .nodes()
        .toArray()
        .map((row) => {
            const checkbox = $(row).find(".presence-checkbox");
            const projetSelect = $(row).find(".projet-select");
            const projetName = projetSelect.find("option:selected").text() || "Sans projet";
            return [
                checkbox.is(":checked") ? "Oui" : "Non",
                $(row).find("td").eq(2).text(),
                $(row).find("td").eq(3).text(),
                $(row).find("td").eq(4).text(),
                projetName,
            ];
        });

    const rowsPerPage = 15;
    const pageData = [];
    for (let i = 0; i < data.length; i += rowsPerPage) {
        pageData.push(data.slice(i, i + rowsPerPage));
    }

    const tableContentPages = pageData
        .map((pageRows) => {
            const tableContent = pageRows
                .map(
                    (row) => `
                <tr>
                    <td>${row[0]}</td>
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
                            <th>Case</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Matricule Entreprise</th>
                            <th>Projet</th>
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
                    background-color: #e3f2fd; 
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
                <p>Date: ${new Date().toLocaleDateString("fr-FR", { day: "2-digit", month: "2-digit", year: "numeric" })}</p>
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
    try {
        printContent(html, title);
    } catch (e) {
        Swal.fire({
            icon: "error",
            title: "Erreur",
            text: "Erreur lors de l'impression : " + e.message,
            timer: 5000,
        });
        errorSound.play().catch((err) => console.log("Erreur audio:", err));
    }
}

$(document).ready(function () {
    const table = $("#salariesTable");

    // Ajouter des champs de recherche dans l'en-tête
    table.find("thead tr").clone(true).appendTo(table.find("thead"));
    table.find("thead tr:eq(1) th").each(function (i) {
        const title = $(this).text();
        if (i === 2 || i === 3 || i === 4) {
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
        } else {
            $(this).html("");
        }
    });

    // Initialiser le DataTable
    const dataTable = table.DataTable({
        columns: [
            { orderable: false, searchable: false, responsivePriority: 1 }, // Case
            { orderable: false, searchable: false, responsivePriority: 3 }, // Photo
            { orderable: true, responsivePriority: 2 }, // Nom
            { orderable: true, responsivePriority: 4 }, // Prénom
            { orderable: true, responsivePriority: 5 }, // Matricule Entreprise
            { orderable: false, searchable: false, responsivePriority: 1 }, // Projet
        ],
        columnDefs: [
            { targets: [0, 1, 5], orderable: false, searchable: false },
            { targets: [2, 3, 4], render: (data) => data || "-" },
        ],
        order: [[2, "asc"]],
        language: {
            lengthMenu: "Afficher _MENU_ employés",
            search: "Rechercher",
            searchPlaceholder: "Rechercher un employé",
            paginate: { next: "Suivant", previous: "Précédent" },
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
        },
        dom: '<"row ms-2 me-3"<"col-md-6"l<"dt-action-buttons"B>><"col-md-6"f>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
            {
                extend: "collection",
                className: "btn btn-label-primary btn-sm dropdown-toggle me-2",
                text: '<i class="bx bx-export me-sm-1"></i> Exporter',
                buttons: [
                    {
                        text: '<i class="bx bx-printer me-1"></i>Imprimer',
                        action: function (e, dt, node, config) {
                            printPointageTable(
                                dt,
                                "Liste des Employés Administratifs",
                                {
                                    cols: [
                                        "presence",
                                        "nom",
                                        "prenom",
                                        "n_matricule_entreprise",
                                        "project_name",
                                    ],
                                    headers: [
                                        "Case",
                                        "Nom",
                                        "Prénom",
                                        "Matricule Entreprise",
                                        "Projet",
                                    ],
                                }
                            );
                        },
                    },
                ],
            },
            {
    text: '<div class="date-picker-container"><input type="text" id="pointageDate" class="form-control form-control-sm" placeholder="Choisir une date" readonly></div>',
    className: "btn btn-outline-secondary btn-sm date-picker-btn",
    init: function (dt, node, config) {
        node.on('click', function(e) {
            if (e.target.id === 'pointageDate') {
                e.stopPropagation();
                if ('showPicker' in e.target) {
                    e.target.showPicker();
                }
            }
        });
    },
    action: function () {}
},
            {
                text: '<i class="bx bx-save me-sm-1"></i> Enregistrer la présence',
                className: "btn btn-primary btn-sm save-presence-btn",
                attr: { "data-table-id": "salariesTable" },
                action: function (e, dt, node, config) {
                    const tableId = node.attr("data-table-id");
                    const table = document.getElementById(tableId);
                    const allCheckboxes = table.querySelectorAll(".presence-checkbox");
                    const selectedDate = document.getElementById("pointageDate").value;

                    if (!selectedDate) {
                        Swal.fire({
                            icon: "warning",
                            title: "Avertissement",
                            text: "Veuillez sélectionner une date pour le pointage.",
                            timer: 5000,
                        });
                        errorSound.play();
                        return;
                    }

                    const dateObj = new Date(selectedDate);
                    if (isNaN(dateObj.getTime())) {
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            text: "La date sélectionnée est invalide.",
                            timer: 5000,
                        });
                        errorSound.play();
                        return;
                    }

                    const presences = [];
                    const absences = [];

                    allCheckboxes.forEach((checkbox) => {
                        const salarieId = checkbox.getAttribute("data-salarie-id");
                        const projetSelect = checkbox.closest("tr").querySelector(".projet-select");
                        const selectedProjet = checkbox.checked && projetSelect ? projetSelect.value : "";
                        if (salarieId && !isNaN(salarieId)) {
                            const presenceData = {
                                salarie_id: parseInt(salarieId),
                                statuts: checkbox.checked ? 1 : 0,
                                date: selectedDate,
                                mois: dateObj.getMonth() + 1,
                                heure: new Date().toTimeString().split(" ")[0],
                                jour: dateObj.getDate(),
                                localisation: null,
                                id_projet: selectedProjet ? parseInt(selectedProjet) : null,
                            };
                            presences.push(presenceData);

                            if (!checkbox.checked) {
                                absences.push({
                                    salarie_id: parseInt(salarieId),
                                    date_debut: selectedDate,
                                });
                            }
                        }
                    });

                    if (presences.length === 0) {
                        Swal.fire({
                            icon: "warning",
                            title: "Avertissement",
                            text: "Aucune présence ou absence valide à enregistrer.",
                            timer: 5000,
                        });
                        errorSound.play();
                        return;
                    }

                    // Sauvegarder l'état des cases à cocher
                    const checkboxStates = {};
                    allCheckboxes.forEach((checkbox) => {
                        checkboxStates[checkbox.getAttribute("data-salarie-id")] = checkbox.checked;
                    });
                    localStorage.setItem("checkboxStates", JSON.stringify(checkboxStates));

                    // Sauvegarder l'état des sélections de projet
                    const projetStates = {};
                    document.querySelectorAll(".projet-select").forEach((select) => {
                        projetStates[select.getAttribute("data-salarie-id")] = select.value;
                    });
                    localStorage.setItem("projetStates", JSON.stringify(projetStates));

                    console.log("Données envoyées à /pointage/admin/save:", { presences, absences });

                    const hasCheckedEmployees = presences.some(p => p.statuts === 1);
                    if (navigator.geolocation && hasCheckedEmployees) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const localisation = `${position.coords.latitude}, ${position.coords.longitude}`;
                                presences.forEach((presence) => {
                                    if (presence.statuts === 1) {
                                        presence.localisation = localisation;
                                    }
                                });
                                sendPresencesAndAbsences(presences, absences, tableId);
                            },
                            (error) => {
                                console.warn("Erreur de géolocalisation:", error.message);
                                sendPresencesAndAbsences(presences, absences, tableId);
                            },
                            { timeout: 10000, maximumAge: 60000 }
                        );
                    } else {
                        console.warn("Géolocalisation non nécessaire ou non supportée.");
                        sendPresencesAndAbsences(presences, absences, tableId);
                    }
                },
            },
        ],
        responsive: true,
        orderCellsTop: true,
    });

    // Vérifier si la date sélectionnée est un jour férié
function updateFerieIndicator() {
    const dateValue = $('#pointageDate').val();
    const indicator = $('#ferieIndicator');

    // === LIGNES DE TEST : à supprimer plus tard ===
    console.log("Date sélectionnée :", dateValue);
    console.log("Est dans la liste ?", joursFeries.includes(dateValue));
    // =============================================

    if (dateValue && joursFeries.includes(dateValue)) {
        indicator.removeClass('d-none');
    } else {
        indicator.addClass('d-none');
    }
}

// Au chargement
updateFerieIndicator();

// À chaque changement de date
$('#pointageDate').on('change', updateFerieIndicator);

// Au chargement
updateFerieIndicator();

// À chaque changement de date
$('#pointageDate').on('change', updateFerieIndicator);

    // Restaurer l'état des cases à cocher et des sélections de projet
    const savedCheckboxStates = JSON.parse(localStorage.getItem("checkboxStates")) || {};
    const savedProjetStates = JSON.parse(localStorage.getItem("projetStates")) || {};
    $(".presence-checkbox").each(function () {
        const salarieId = $(this).attr("data-salarie-id");
        if (savedCheckboxStates[salarieId] !== undefined) {
            $(this).prop("checked", savedCheckboxStates[salarieId]);
        }
    });
    $(".projet-select").each(function () {
        const salarieId = $(this).attr("data-salarie-id");
        if (savedProjetStates[salarieId] !== undefined) {
            $(this).val(savedProjetStates[salarieId]);
        }
    });

    // Fonctionnalité de la case "Tout sélectionner"
    $('#selectAll').on('change', function () {
        const isChecked = this.checked;
        $('.presence-checkbox').prop('checked', isChecked);
        dataTable.draw(false);
    });

    function sendPresencesAndAbsences(presences, absences, tableId) {
        $("#loadingModal").modal("show");

        fetch("/pointage/admin/save", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ presences, absences }),
        })
            .then((response) => {
                if (!response.ok) {
                    return response.text().then((text) => {
                        throw new Error(`Erreur HTTP ${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            .then((data) => {
                console.log("Réponse du serveur:", data);
                if (data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Succès",
                        text: data.message,
                        timer: 5000,
                    });
                    successSound.play();
                    // Rafraîchir la page pour mettre à jour la colonne Projet
                    window.location.reload();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Erreur",
                        text: "Erreur lors de l'enregistrement : " + data.message,
                        timer: 5000,
                    });
                    errorSound.play();
                    if (data.errors && data.errors.length > 0) {
                        console.error("Erreurs détaillées:", data.errors);
                        Swal.fire({
                            icon: "error",
                            title: "Détails des erreurs",
                            html: data.errors
                                .map(
                                    (err) =>
                                        `Employé ID ${
                                            err.salarie_id || "Inconnu"
                                        }: ${err.error}`
                                )
                                .join("<br>"),
                            timer: 10000,
                        });
                    }
                }
            })
            .catch((error) => {
                console.error("Erreur Fetch:", error);
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: "Une erreur s'est produite : " + error.message,
                    timer: 5000,
                });
                errorSound.play();
            })
            .finally(() => {
                $("#loadingModal").modal("hide");
            });
    }
   flatpickr("#pointageDate", {
    locale: {
        firstDayOfWeek: 1, // Lundi comme premier jour de la semaine
        weekdays: {
            shorthand: ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"],
            longhand: ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"]
        },
        months: {
            shorthand: ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Aoû", "Sep", "Oct", "Nov", "Déc"],
            longhand: ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"]
        },
        ordinal: () => "er", // Pour le "1er" au lieu de "1st"
        rangeSeparator: " au ",
        weekAbbreviation: "Sem",
        scrollTitle: "Défiler pour augmenter",
        toggleTitle: "Cliquer pour basculer"
    },
    dateFormat: "Y-m-d",
   defaultDate: new URLSearchParams(window.location.search).get('date') 
                 || "{{ $selectedDateStr ?? now()->format('Y-m-d') }}",
                 // Recharge la page quand on change la date
    onChange: function(selectedDates, dateStr, instance) {
        if (dateStr) {
            const url = new URL(window.location);
            url.searchParams.set('date', dateStr);
            url.searchParams.delete('page'); // optionnel : reset pagination si besoin
            window.location.href = url.toString();
        }
    },
    altInput: true,
    altFormat: "j F Y", // Affiche "2 janvier 2026" dans le champ
    onDayCreate: function(dObj, dStr, fp, dayElem) {
        const dateStr = flatpickr.formatDate(dayElem.dateObj, "Y-m-d");
        if (joursFeries.includes(dateStr)) {
            dayElem.style.backgroundColor = "#e74c3c";
            dayElem.style.color = "white";
            dayElem.style.borderRadius = "50%";
        }
    }
});
});