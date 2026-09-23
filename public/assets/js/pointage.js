const successSound = new Audio('/assets/audio/success.mp3');
const errorSound = new Audio('/assets/audio/error.mp3');

// Generic print function
function printPointageTable(table, title, columns) {
    const data = table.rows({ search: 'applied' }).nodes().toArray().map(row => {
        const checkbox = $(row).find('.presence-checkbox');
        return [
            checkbox.is(':checked') ? 'Oui' : 'Non', // Case
            $(row).find('td').eq(2).text(), // Nom Prénom
            $(row).find('td').eq(3).text() // Matricule
        ];
    });
  const logoUrl = companySettings && companySettings.logo
        ? `${window.location.origin}/storage/${companySettings.logo}`
        : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

    const rowsPerPage = 8;
    const pageData = [];
    for (let i = 0; i < data.length; i += rowsPerPage) {
        pageData.push(data.slice(i, i + rowsPerPage));
    }

    // Generate table content for each page
    const tableContentPages = pageData.map((pageRows) => {
        const tableContent = pageRows
            .map(row => `
                <tr>
                    <td>${row[0]}</td>
                    <td>${row[1] || '-'}</td>
                    <td>${row[2] || '-'}</td>
                </tr>`
            )
            .join("");

        return `
            <div class="table-section" style="margin-bottom: 20px; page-break-after: always;">
                <table>
                    <tr>
                        <th>Case</th>
                        <th>Nom Prénom</th>
                        <th>Matricule Entreprise</th>
                    </tr>
                    ${tableContent}
                </table>
            </div>
        `;
    }).join("");

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
                <p>Date: ${new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })}</p>
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
            icon: 'error',
            title: 'Erreur',
            text: 'Erreur lors de l\'impression : ' + e.message,
            timer: 5000
        });
        const errorSound = new Audio("/assets/audio/error.mp3");
        errorSound.play().catch((err) => console.log("Erreur audio:", err));
    }
}

function printContent(html, title) {
    console.log("Starting print content");
    try {
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
                icon: 'success',
                title: 'Succès',
                text: 'Impression lancée !',
                timer: 5000
            });
            const successSound = new Audio("/assets/audio/success.mp3");
            successSound.play().catch((err) => console.log("Erreur audio:", err));
        }, 500);
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Erreur lors de l\'impression : ' + e.message,
            timer: 5000
        });
        const errorSound = new Audio("/assets/audio/error.mp3");
        errorSound.play().catch((err) => console.log("Erreur audio:", err));
    }
}

$(document).ready(function () {
    $('.projet-salaries-table').each(function () {
        const table = $(this);
        const tableId = table.attr('id');
        const salariesData = JSON.parse(table.attr('data-salaries') || '[]');

        table.find('thead tr').clone(true).appendTo(table.find('thead'));
        table.find('thead tr:eq(1) th').each(function (i) {
            const title = $(this).text();
            if (i === 2 || i === 3) {
                $(this).html(
                    '<input type="text" class="form-control form-control-sm" placeholder="Rechercher ' + title + '" />'
                );
                $('input', this).on('keyup change', function () {
                    if (table.DataTable().column(i).search() !== this.value) {
                        table.DataTable().column(i).search(this.value).draw();
                    }
                });
            } else {
                $(this).html('');
            }
        });

        table.DataTable({
            data: salariesData,
            columns: [
                {
                    data: 3, // Case
                    render: function (data, type, row) {
                        return `<input type="checkbox" class="presence-checkbox" data-salarie-id="${row[4]}" ${data ? 'checked' : ''}>`;
                    }
                },
                {
                    data: 2, // Photo
                    render: function (data, type, row) {
                        const photoUrl = data || '/assets/img/avatars/1.png';
                        return `<img src="${photoUrl}" alt="Photo" class="employee-photo" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">`;
                    }
                },
                { data: 0 }, // Nom Prénom
                { data: 1 }  // Matricule Entreprise
            ],
            columnDefs: [
                { targets: [0, 1], orderable: false, searchable: false },
                { targets: [2, 3], render: (data) => data || '-' }
            ],
            order: [[2, 'asc']],
            language: {
                lengthMenu: 'Afficher _MENU_ ouvriers',
                search: 'Rechercher',
                searchPlaceholder: 'Rechercher un ouvrier',
                paginate: { next: 'Suivant', previous: 'Précédent' },
                info: 'Affichage de _START_ à _END_ sur _TOTAL_ éléments'
            },
            dom: '<"row ms-2 me-3"<"col-md-6"l<"dt-action-buttons"B>><"col-md-6"f>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-label-primary dropdown-toggle me-2',
                    text: '<i class="bx bx-export me-sm-1"></i> Exporter',
                    buttons: [
                        {
                            text: '<i class="bx bx-printer me-1"></i>Imprimer',
                            action: function (e, dt, node, config) {
                                printPointageTable(dt, 'Liste des Ouvriers du Projet', {
                                    cols: ['presence', 'nom_prenom', 'n_matricule_entreprise'],
                                    headers: ['Case', 'Nom Prénom', 'Matricule Entreprise']
                                });
                            }
                        },
                        { extend: 'excel', text: '<i class="bx bxs-file-export me-1"></i>Excel' },
                        { extend: 'pdf', text: '<i class="bx bxs-file-pdf me-1"></i>PDF' }
                    ]
                }
            ],
            responsive: true,
            orderCellsTop: true
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.save-presence-btn').forEach(button => {
        button.addEventListener('click', function () {
            const tableId = this.getAttribute('data-table-id');
            const projetId = this.getAttribute('data-projet-id');
            const table = document.getElementById(tableId);
            const allCheckboxes = table.querySelectorAll('.presence-checkbox');
            const presences = [];

            allCheckboxes.forEach(checkbox => {
                const salarieId = checkbox.getAttribute('data-salarie-id');
                if (salarieId && !isNaN(salarieId)) {
                    presences.push({
                        salarie_id: parseInt(salarieId),
                        statuts: checkbox.checked ? 1 : 0,
                        localisation: null,
                        id_projet: parseInt(projetId)
                    });
                }
            });

            if (presences.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Avertissement',
                    text: 'Aucune présence valide à enregistrer.',
                    timer: 5000
                });
                errorSound.play();
                return;
            }

            console.log('Données envoyées à /pointage/save:', presences);
            $("#loadingModal").modal("show");

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const localisation = `${position.coords.latitude}, ${position.coords.longitude}`;
                        presences.forEach(presence => {
                            if (presence.statuts === 1) {
                                presence.localisation = localisation;
                            }
                        });
                        sendPresences(presences, tableId);
                    },
                    (error) => {
                        console.warn('Erreur de géolocalisation:', error.message);
                        sendPresences(presences, tableId);
                    },
                    { timeout: 10000, maximumAge: 60000 }
                );
            } else {
                console.warn('Géolocalisation non supportée par ce navigateur.');
                sendPresences(presences, tableId);
            }
        });
    });

    function sendPresences(presences, tableId) {
        fetch('/pointage/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                presences: presences
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Erreur HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Réponse du serveur:', data);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: data.message,
                    timer: 5000
                });
                successSound.play();

                const table = $(`#${tableId}`).DataTable();
                presences.forEach(presence => {
                    const row = table.rows().nodes().toArray().find(row => {
                        return $(row).find('.presence-checkbox').data('salarie-id') == presence.salarie_id;
                    });
                    if (row) {
                        const checkbox = $(row).find('.presence-checkbox');
                        checkbox.prop('checked', presence.statuts == 1);
                    }
                });
                table.draw(false);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Erreur lors de l\'enregistrement : ' + data.message,
                    timer: 5000
                });
                errorSound.play();
                if (data.errors && data.errors.length > 0) {
                    console.error('Erreurs détaillées:', data.errors);
                    Swal.fire({
                        icon: 'error',
                        title: 'Détails des erreurs',
                        html: data.errors.map(err => `Ouvrier ID ${err.salarie_id}, Projet ID ${err.id_projet}: ${err.error}`).join('<br>'),
                        timer: 10000
                    });
                }
            }
        })
        .catch(error => {
            console.error('Erreur Fetch:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Une erreur s\'est produite : ' + error.message,
                timer: 5000
            });
            errorSound.play();
        })
        .finally(() => {
            $("#loadingModal").modal("hide");
        });
    }
});