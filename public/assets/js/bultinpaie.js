document.addEventListener("DOMContentLoaded", function () {
    if (!window.jQuery || !$.fn.DataTable) {
        console.error("jQuery or DataTables is not loaded.");
        return;
    }

    // fonction utilitaire pour formater les nombres sans virgules
    function formatNumberWithoutCommas(value) {
        if (value === null || value === undefined || value === "-") return value;
        return parseFloat(value.toString().replace(/[,]/g, '')).toFixed(2);
    }

    // gestionnaire pour selectAll
    document.getElementById('selectAll').addEventListener('change', function () {
    const table = $('#payrollTable').DataTable();
    const isChecked = this.checked;

    // Coche / décoche TOUTES les lignes (toutes les pages) qui matchent le filtre actuel
    table.rows({ search: 'applied' }).every(function () {
        const checkbox = this.node().querySelector('.employee-checkbox');
        if (checkbox) {
            checkbox.checked = isChecked;
        }
    });
});

    function addColumnSearch(tableSelector, dataTable) {
        $(`${tableSelector} thead tr`).clone(true).appendTo(`${tableSelector} thead`);
        $(`${tableSelector} thead tr:eq(1) th`).each(function (i) {
            if (i === $(`${tableSelector} thead tr:eq(1) th`).length - 1) {
                $(this).html('');
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

    function generatePrintContent(salaries, title) {
    const monthOrder = [
        'janvier','fevrier','mars','avril','mai','juin',
        'juillet','aout','septembre','octobre','novembre','decembre'
    ];
    const monthLabels = {
        'janvier':'Janvier','fevrier':'Février','mars':'Mars','avril':'Avril',
        'mai':'Mai','juin':'Juin','juillet':'Juillet','aout':'Août',
        'septembre':'Septembre','octobre':'Octobre','novembre':'Novembre','decembre':'Décembre'
    };
 
    const logoUrl = companySettings?.logo
        ? `${window.location.origin}/storage/${companySettings.logo}`
        : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;
 
    // ── Mapper statut paiement → classe CSS badge ───────────────────────────
    function badgeClass(monthData) {
        if (!monthData || monthData.value === '-') return 'badge-initial';
        if (monthData.hasQuittanceCah)             return 'badge-quittance-cah';
        if (monthData.typer === 'a')               return 'badge-unpaid';
        if (monthData.typer && monthData.typer !== 'a') return 'badge-paid';
        return 'badge-initial';
    }
 
    // ── Icône vg / individuel ───────────────────────────────────────────────
    function iconHtml(monthData) {
        if (!monthData) return '';
        if (monthData.typer === 'vg') return '<i class="bi bi-people"  style="margin-left:3px;font-size:.8em;"></i>';
        if (['e','c','v'].includes(monthData.typer)) return '<i class="bi bi-person" style="margin-left:3px;font-size:.8em;"></i>';
        return '';
    }
 
    const recordsPerPage = 12;
    const pages = [];
 
    for (let i = 0; i < salaries.length; i += recordsPerPage) {
        const chunk = salaries.slice(i, i + recordsPerPage);
        const isLast = (i + recordsPerPage) >= salaries.length;
 
        const tableRows = chunk.map(sal => {
            let row = '<tr class="table-tr">';
            row += `<td><span class="badge badge-initial">${sal.matricule ?? '-'}</span></td>`;
            row += `<td><span class="badge badge-initial">${sal.nom_prenom ?? '-'}</span></td>`;
 
            monthOrder.forEach(m => {
                const md  = sal[m] ?? null;
                const val = md?.value ?? '-';
                const cls = badgeClass(md);
                const ico = iconHtml(md);
                row += `<td><span class="badge ${cls}">${val}${ico}</span></td>`;
            });
 
            row += '</tr>';
            return row;
        }).join('');
 
        pages.push(`
            <div class="print-page" style="${!isLast ? 'page-break-after:always;' : ''}">
                <div class="print">
                    <div class="print-header">
                        <div class="logo-title-container">
                            <img src="${logoUrl}" alt="Logo" style="max-width:90px;" />
                            <h1 class="print-header-title">Bulletins de paie</h1>
                        </div>
                        <div class="print-header-type">${title}</div>
                    </div>
                    <div class="print-body-2">
                        <table class="table table-bordered">
                            <tr style="background-color:rgb(233,233,233);">
                                <td class="td-bold">Matricule</td>
                                <td class="td-bold">Nom et Prénom</td>
                                ${monthOrder.map(m => `<td class="td-bold">${monthLabels[m]}</td>`).join('')}
                            </tr>
                            ${tableRows}
                        </table>
                    </div>
                    <div class="print-footer">
                        <h4>A: Oujda</h4>
                        <h4>Le: ${new Date().toLocaleDateString('fr-FR')}</h4>
                    </div>
                </div>
                <div class="company-info">
                    <p>Siège social: ${companySettings?.address || 'Non spécifié'} • Capital: ${
                        companySettings?.capital
                            ? companySettings.capital.toLocaleString('fr-FR') + ' MAD'
                            : 'Non spécifié'
                    }</p>
                    <p>R.C.: ${companySettings?.commercial_register || ''} | CNSS: ${companySettings?.cnss_number || ''} | ICE: ${companySettings?.patent_number || ''}</p>
                </div>
            </div>
        `);
    }
 
    return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>${title}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @page { size: landscape; margin: 8mm; }
        body  { font-family: Arial, sans-serif; margin:0; padding:0; }
        .print { padding:15px; }
        .print-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .logo-title-container { display:flex; align-items:center; gap:10px; }
        .print-header-title { font-size:17px; font-weight:bold; margin:0; }
        .print-header-type  { background:#efefef; padding:8px; text-align:center; font-weight:bold; border:1px solid #000; }
        .print-footer { display:flex; justify-content:space-between; margin-top:10px; }
        .company-info { text-align:center; font-size:10px; color:#555; border-top:1px solid #ccc; padding-top:4px; margin-top:4px; }
        table { width:100%; border-collapse:collapse; }
        th, td { border:1px solid #000; padding:3px 2px; text-align:center; font-size:9px; }
        .td-bold { font-weight:bold; background:#f0f0f0; }
        .badge {
            display:inline-block; padding:2px 4px; border-radius:4px;
            font-size:.68rem; font-weight:600;
            -webkit-print-color-adjust:exact !important;
            print-color-adjust:exact !important;
        }
        .badge-initial       { background-color:#f8f9fa !important; color:#6c757d !important; border:1px solid #ddd; }
        .badge-unpaid        { background-color:#da3030 !important; color:white !important; }
        .badge-paid          { background-color:#71dd37 !important; color:white !important; }
        .badge-quittance-cah { background-color:#ffab00 !important; color:white !important; }
    </style>
</head>
<body>
    ${pages.join('')}
</body>
</html>`;
}

     function printTable(tableId, title) {
    const year   = document.getElementById('yearSelect')?.value  || new Date().getFullYear();
    const status = document.getElementById('statusSelect')?.value || 'actif';
 
    // ── 1. Récupérer les IDs des salariés visibles dans DataTables ──────────
    const dtInstance = $(`#${tableId}`).DataTable();
    const visibleIds = Array.from(dtInstance.rows({ search: 'applied' }).nodes())
        .map(row => {
            const cb = row.querySelector('.employee-checkbox');
            return cb ? cb.getAttribute('data-id-salarie') : null;
        })
        .filter(Boolean);
 
    if (visibleIds.length === 0) {
        Swal.fire('Attention', 'Aucun salarié à imprimer.', 'warning');
        return;
    }
 
    // ── 2. Afficher un loader ────────────────────────────────────────────────
    Swal.fire({
        title: 'Préparation de l\'impression…',
        text: 'Chargement des données annuelles…',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });
 
    // ── 3. Appel AJAX : récupérer les données complètes (12 mois) ───────────
    $.ajax({
        url: '/bultin/print-data',          // ← route à ajouter (voir ci-dessous)
        method: 'POST',
        data: {
            year:      year,
            status:    status,
            employees: visibleIds,
            _token:    $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            Swal.close();
            if (!response.salaries || response.salaries.length === 0) {
                Swal.fire('Attention', 'Aucune donnée disponible.', 'warning');
                return;
            }
            const pageHtml = generatePrintContent(response.salaries, title);
            _doPrint(pageHtml);
        },
        error: function (xhr) {
            Swal.close();
            const msg = xhr.responseJSON?.error || 'Erreur lors du chargement des données.';
            Swal.fire('Erreur', msg, 'error');
        }
    });
}

function _doPrint(pageHtml) {
    const iframe = $('<iframe/>', {
        id:    'printIframe',
        style: 'position:absolute;width:0;height:0;border:none;'
    }).appendTo('body');
 
    const doc = iframe[0].contentWindow.document;
    doc.open();
    doc.write(pageHtml);
    doc.close();
 
    setTimeout(function () {
        iframe[0].contentWindow.focus();
        iframe[0].contentWindow.print();
    }, 600);
 
    iframe[0].contentWindow.onafterprint = function () { iframe.remove(); };
    setTimeout(function () { if (iframe[0]) iframe.remove(); }, 6000);
}

    function generateNotepadContent(table, year, month) {
        const data = table.rows({ search: "applied" }).data().toArray();
        const monthMap = {
            'janvier': 1, 'fevrier': 2, 'mars': 3, 'avril': 4, 'mai': 5, 'juin': 6,
            'juillet': 7, 'aout': 8, 'septembre': 9, 'octobre': 10, 'novembre': 11, 'decembre': 12
        };

        const selectedMonth = month || document.getElementById('monthSelect')?.value || Object.keys(monthMap)[new Date().getMonth() - 1];
        const monthKey = selectedMonth.toLowerCase();
        const monthNumber = monthMap[monthKey];

        if (!monthNumber) {
            alert("Erreur : Mois sélectionné invalide.");
            return;
        }

        console.log("Données brutes DataTable :", data);
        console.log("Mois sélectionné :", selectedMonth, "MonthKey :", monthKey, "Année :", year);

        const filteredData = data.filter(row => {
            const typer = row[`typer_${monthKey}`] || null;
            console.log(`Vérification salarié ${row[2]} pour ${monthKey} : typer = ${typer}`);
            return typer && typer !== 'a';
        });

        if (filteredData.length === 0) {
            alert(`Aucun salarié avec un paiement (typer différent de "a") pour ${selectedMonth} ${year}.`);
            return;
        }

        const content = filteredData.map(row => {
            const code_cnss = companySettings.code_cnss || 'A000';
            const cnss_number = companySettings.cnss_number || '0000000000000';
            const n_matricule_cnss = row[1] || '00000000';
            const [nom, prenom = ''] = row[2].split(' ');
            const paddedNom = nom.padEnd(30, ' ');
            const paddedPrenom = prenom.padEnd(30, ' ');
            const zeros = '0'.repeat(20);
            return `${code_cnss}${cnss_number}${n_matricule_cnss}${paddedNom}${paddedPrenom}${zeros}`;
        }).join('\n');

        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `AFFEBDS_${companySettings.cnss_number}_${year}_${monthKey}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    function getMonthNameFromIndex(index) {
        const months = [
            '', '', '',
            'janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'
        ];
        return months[index] || '';
    }

    const dataTable = $("#payrollTable").DataTable({
        dom: '<"row ms-2 me-3"' +
            '<"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls"l<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3"B>>' +
            '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter"f<"dt-filter mb-3 mb-md-0">>' +
            ">t" +
            '<"row mx-2"' +
            '<"col-sm-12 col-md-6"i>' +
            '<"col-sm-12 col-md-6"p>' +
            ">",
        displayLength: 9,
        lengthMenu: [10, 25, 50, 75, 100],
        ordering: false,
buttons: [
    {
        extend: "collection",
        className: "btn btn-label-primary dropdown-toggle me-2",
        text: '<i class="bx bx-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Exporter</span>',
        buttons: [
            {
                text: '<i class="bx bx-printer me-1"></i>Imprimer',
                className: "dropdown-item",
                action: function () {
                    printTable("payrollTable", "Bulletins de paie", {
                        cols: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
                        headers: [
                            "Nom et Prénom", "Matricule", "Janvier", "Février", "Mars",
                            "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre",
                            "Octobre", "Novembre", "Décembre"
                        ]
                    });
                }
            },
           {
    text: '<i class="bx bx-file-blank me-1"></i>Générer Notepad CNSS',
    className: "dropdown-item",
    action: function () {
        const year = document.getElementById('yearSelect')?.value || new Date().getFullYear();
        const month = document.getElementById('monthSelect')?.value || 'mai';
        const status = document.getElementById('statusSelect')?.value || 'actif';

        console.log('Génération du fichier Notepad CNSS avec :', { year, month, status });

        // Afficher un modal de chargement
        Swal.fire({
            title: 'Génération en cours...',
            text: 'Veuillez patienter pendant la génération du fichier CNSS.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '/generate-notepad',
            method: 'POST',
            data: {
                year: year,
                month: month,
                status: status,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function (data, status, xhr) {
                Swal.close();
                
                // Vérifier le type de contenu
                const contentType = xhr.getResponseHeader('Content-Type');
                
                // Si c'est du JSON (erreur), le traiter comme tel
                if (contentType && contentType.includes('application/json')) {
                    const reader = new FileReader();
                    reader.onload = function() {
                        try {
                            const response = JSON.parse(reader.result);
                            Swal.fire({
                                icon: 'warning',
                                title: 'Aucun salarié',
                                text: response.error || 'Aucun salarié trouvé pour ce mois.'
                            });
                        } catch (e) {
                            console.error('Erreur lors du parsing de la réponse :', e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: 'Une erreur est survenue lors de la génération du fichier CNSS.'
                            });
                        }
                    };
                    reader.readAsText(data);
                    return;
                }
                
                // Sinon, traiter comme un fichier texte
                console.log('Réponse AJAX réussie pour Notepad :', status);
                
                const disposition = xhr.getResponseHeader('Content-Disposition');
                const filename = disposition ? disposition.match(/filename="(.+)"/)?.[1] : `AFFEBDS_${year}_${month}.txt`;
                
                const url = window.URL.createObjectURL(data);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);

                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: `Fichier CNSS généré avec succès pour ${month} ${year}.`,
                    timer: 3000,
                    timerProgressBar: true
                });
            },
            error: function (xhr) {
                Swal.close();
                
                console.error('Erreur AJAX pour Notepad :', xhr.status, xhr.responseText);
                let errorMessage = 'Erreur lors de la génération du fichier CNSS.';
                
                try {
                    // Vérifier si la réponse est un Blob
                    if (xhr.response instanceof Blob) {
                        const reader = new FileReader();
                        reader.onload = function() {
                            try {
                                const response = JSON.parse(reader.result);
                                errorMessage = response.error || errorMessage;
                            } catch (e) {
                                console.error('Erreur lors du parsing de la réponse :', e);
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: errorMessage
                            });
                        };
                        reader.readAsText(xhr.response);
                    } else {
                        // Si ce n'est pas un Blob, essayer de parser directement
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.error || errorMessage;
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: errorMessage
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: errorMessage
                    });
                }
            }
        });
    }
},
              {
                text: '<i class="bx bx-file me-1"></i>Exporter Excel',
                className: "dropdown-item",
                action: function (e, dt, node, config) {
                    const year = document.getElementById('yearSelect')?.value || new Date().getFullYear();
                    const month = document.getElementById('monthSelect')?.value || 'mai';
                    const status = document.getElementById('statusSelect')?.value || 'actif';
                    const table = $("#payrollTable").DataTable();
                    const data = table.rows({ search: "applied" }).data().toArray();
                    const selectedEmployees = data
                        .map(row => {
                            const checkbox = row[0];
                            const match = checkbox.match(/data-id-salarie="(\d+)"/);
                            return match ? match[1] : null;
                        })
                        .filter(id => id !== null);

                    if (selectedEmployees.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Aucun salarié sélectionné',
                            text: `Veuillez sélectionner au moins un salarié pour ${month} ${year}.`
                        });
                        return;
                    }

                    console.log('Envoi de la requête AJAX pour Excel avec :', { year, month, status, employees: selectedEmployees });

                    $.ajax({
                        url: '/fetch-payroll-data',
                        method: 'POST',
                        data: {
                            year: year,
                            month: month,
                            status: status,
                            employees: selectedEmployees,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            console.log('Réponse AJAX réussie pour Excel :', response);
                            // Log des valeurs de cotisation_cnss pour débogage
                            console.log('Valeurs de cotisation_cnss reçues :', response.data.map(row => row.cotisation_cnss));

                            if (!response.data || response.data.length === 0) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Aucune donnée',
                                    text: `Aucune donnée disponible pour ${month} ${year}.`
                                });
                                return;
                            }

                            // Les données sont triées par le serveur, pas besoin de tri client-side
                            const exportData = response.data.map(row => [
                                row.n_matricule_cnss || '-',
                                row.nom || '-',
                                row.prenom || '-',
                                row.cotisation_cnss ? parseFloat(row.cotisation_cnss).toFixed(2) : '0.00',
                                row.basePlafond ? parseFloat(row.basePlafond).toFixed(2) : '0.00',
                                row.num_jours || '0'
                            ]);

                            // Créer une table DataTable temporaire pour l'exportation
                            const tempTable = $('<table>').appendTo('body').hide();
                            const tempDataTable = tempTable.DataTable({
                                dom: 'B',
                                data: exportData,
                                columns: [
                                    { title: 'N CNSS' },
                                    { title: 'NOM' },
                                    { title: 'PRENOM' },
                                    { title: 'BRUT CNSS' },
                                    { title: 'BASE PLAFOND CNSS' },
                                    { title: 'JRS PRESENT' }
                                ],
                                ordering: false, // Désactiver le tri automatique
                                buttons: [
                                    {
                                        extend: 'excelHtml5',
                                        text: 'Exporter Excel',
                                        filename: `Payroll_${year}_${month}`,
                                        exportOptions: {
                                            columns: [0, 1, 2, 3, 4, 5]
                                        },
                                        customize: function (xlsx) {
                                            const sheet = xlsx.xl.worksheets['sheet1.xml'];
                                            // Appliquer un format numérique à la colonne BRUT CNSS (colonne D, index 4)
                                            $('row c[r^="D"]', sheet).each(function () {
                                                $(this).attr('s', '2'); // Format numérique avec 2 décimales
                                            });
                                            // Appliquer un format numérique à la colonne BASE PLAFOND CNSS (colonne E, index 5)
                                            $('row c[r^="E"]', sheet).each(function () {
                                                $(this).attr('s', '2'); // Format numérique avec 2 décimales
                                            });
                                        }
                                    }
                                ]
                            });

                            // Déclencher l'exportation Excel
                            tempDataTable.button(0).trigger();

                            // Nettoyer la table temporaire
                            tempDataTable.destroy();
                            tempTable.remove();
                        },
                        error: function (xhr) {
                            console.error('Erreur AJAX pour Excel :', xhr.status, xhr.responseText);
                            let errorMessage = 'Erreur inconnue lors de la génération du fichier Excel.';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.error || errorMessage;
                            } catch (e) {
                                console.error('Erreur lors du parsing de la réponse :', e);
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: errorMessage
                            });
                        }
                    });
                }
            }, 
            {
                text: '<i class="bx bx-archive me-1"></i>Dossier Bulletin de paie ',
                className: "dropdown-item",
                action: function () {
                    const year = document.getElementById('yearSelect')?.value || new Date().getFullYear();
                    const month = document.getElementById('monthSelect')?.value || 'mai';
                    const status = document.getElementById('statusSelect')?.value || 'actif';
                    const table = $("#payrollTable").DataTable();
                    const data = table.rows({ search: "applied" }).data().toArray();
                    const selectedEmployees = data
                        .map(row => {
                            const checkbox = row[0];
                            const match = checkbox.match(/data-id-salarie="(\d+)"/);
                            return match ? match[1] : null;
                        })
                        .filter(id => id !== null);

                    if (selectedEmployees.length === 0) {
                        alert(`Aucun salarié sélectionné pour ${month} ${year}.`);
                        return;
                    }

                    console.log('Envoi de la requête AJAX pour ZIP avec :', { year, month, status, employees: selectedEmployees });

                    $.ajax({
                        url: '/generate-payslips-zip',
                        method: 'POST',
                        data: {
                            year: year,
                            month: month,
                            status: status,
                            employees: selectedEmployees,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        xhrFields: {
                            responseType: 'blob'
                        },
                        success: function (data, status, xhr) {
                            console.log('Réponse AJAX réussie pour ZIP :', status, xhr.getResponseHeader('Content-Disposition'));
                            const disposition = xhr.getResponseHeader('Content-Disposition');
                            const filename = disposition ? disposition.match(/filename="(.+)"/)?.[1] : `Payslips_${year}_${month}.zip`;
                            const url = window.URL.createObjectURL(data);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = filename;
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            window.URL.revokeObjectURL(url);
                        },
                        error: function (xhr) {
                            console.error('Erreur AJAX pour ZIP :', xhr.status, xhr.responseText);
                            let errorMessage = 'Erreur inconnue lors de la génération du fichier ZIP.';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.error || errorMessage;
                            } catch (e) {
                                console.error('Erreur lors du parsing de la réponse :', e);
                            }
                            alert(errorMessage);
                        }
                    });
                }
            }
        ]
    }
],
        responsive: true,
        orderCellsTop: true,
        language: {
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ ",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            infoEmpty: "Aucun élément à afficher",
            infoFiltered: "(filtré à partir de _MAX_ éléments au total)",
            paginate: {
                first: "Premier",
                last: "Dernier",
                next: "Suivant",
                previous: "Précédent"
            }
        },
       initComplete: function () {
            console.log("DataTable payroll initialized");
            addColumnSearch("#payrollTable", this.api());

            const exportButton = document.querySelector('.dt-action-buttons .btn.btn-label-primary.dropdown-toggle');
            if (exportButton) {
                const keywordsContainer = document.createElement('span');
                keywordsContainer.style.marginLeft = '10px';
                keywordsContainer.style.display = 'inline-flex';
                keywordsContainer.style.alignItems = 'center';
                keywordsContainer.style.gap = '9px';
                keywordsContainer.style.fontSize = '17px';
                
                keywordsContainer.innerHTML = `
                    <span style="
                        color: #8b7355; 
                        background-color: #f8f6f0; 
                        border: 2px solid #f8f6f0; 
                        padding: 4px 8px; 
                        border-radius: 4px; 
                        font-size: 0.7rem; 
                        font-weight: 500; 
                        letter-spacing: 0.2px;
                    ">salaire n'est pas calculé</span>
                    
                    <span style="
                        background-color: #da3030; 
                        color: white; 
                        border: 2px solid #da3030; 
                        padding: 4px 8px; 
                        border-radius: 4px; 
                        font-size: 0.7rem; 
                        font-weight: 500; 
                        letter-spacing: 0.2px;
                    ">salaire calculé</span>
                    
                    <span style="
                        background-color: #71dd37; 
                        color: white; 
                        border: 2px solid #71dd37; 
                        padding: 4px 8px; 
                        border-radius: 4px; 
                        font-size: 0.7rem; 
                        font-weight: 500; 
                        letter-spacing: 0.2px;
                    ">paiement effectué</span>
                    
                    <span style="
                        background-color: #ffab00; 
                        color: white; 
                        border: 2px solid #ffab00; 
                        padding: 4px 8px; 
                        border-radius: 4px; 
                        font-size: 0.7rem; 
                        font-weight: 500; 
                        letter-spacing: 0.2px;
                    ">quittance parcourir</span>
                  
                `;
                
                exportButton.insertAdjacentElement('afterend', keywordsContainer);
            } else {
                console.warn("Export button not found.");
            }
        }
    });



  // gestionnaire pour .generate-annual-payslip
    document.addEventListener('click', function (e) {
                const element = e.target.closest('.generate-annual-payslip');
                if (element) {
                    e.preventDefault();
                    const idSalarie = element.getAttribute('data-id-salarie');
                    const year = element.getAttribute('data-year');

                    Swal.fire({
                        title: 'Générer le bulletin annuel?',
                        text: `Voulez-vous générer le bulletin de paie annuel pour l'année ${year}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Oui',
                        cancelButtonText: 'Non'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#loadingModal').modal('show');
                            $.ajax({
                                url: $('meta[name="generate-annual-payslip-url"]').attr('content'),
                                method: 'POST',
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content'),
                                    id_salarie: idSalarie,
                                    year: year
                                },
                                success: function(response) {
                                    $('#loadingModal').modal('hide');
                                    if (response.success) {
                                        Swal.fire({
                                            title: 'Succès',
                                            text: response.success,
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            const link = document.createElement('a');
                                            link.href = response.path;
                                            link.download = '';
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Erreur',
                                            text: response.error || 'Une erreur est survenue.',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                },
                                error: function(xhr) {
                                    $('#loadingModal').modal('hide');
                                    Swal.fire({
                                        title: 'Erreur',
                                        text: xhr.responseJSON?.error || 'Une erreur est survenue lors de la génération.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            });
                        }
                    });
                }
            });

    // gestionnaire pour #total-salary-btn
// gestionnaire pour #total-salary-btn
document.addEventListener('click', function (e) {
    if (e.target.closest('#total-salary-btn')) {
        e.preventDefault();
        const year  = document.getElementById('yearSelect').value;
        const month = document.getElementById('monthSelect').value;

        const monthNames = {
            janvier: 'Janvier', fevrier: 'Février', mars: 'Mars', avril: 'Avril',
            mai: 'Mai', juin: 'Juin', juillet: 'Juillet', aout: 'Août',
            septembre: 'Septembre', octobre: 'Octobre', novembre: 'Novembre', decembre: 'Décembre',
            '': 'Aucun mois sélectionné'
        };

        if (!year || !month) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Veuillez sélectionner une année et un mois.'
            });
            return;
        }

        // Récupérer TOUS les IDs cochés (toutes les pages)
        const dataTable = $('#payrollTable').DataTable();
        const selectedIds = [];
        dataTable.rows({ search: 'applied' }).every(function () {
            const checkbox = this.node().querySelector('.employee-checkbox');
            if (checkbox && checkbox.checked) {
                selectedIds.push(checkbox.getAttribute('data-id-salarie'));
            }
        });

        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Aucune sélection',
                text: 'Veuillez cocher au moins un employé.'
            });
            return;
        }

        // ── CHOIX DU MODE DE CALCUL (comme le calcul individuel) ──────────────
        Swal.fire({
            title: 'Type de calcul',
            icon: 'question',
            html: `
                <p style="margin-bottom:12px;">Choisissez le mode de calcul pour
                   <strong>${monthNames[month] || month} ${year}</strong>
                   (${selectedIds.length} salarié(s)) :</p>
            `,
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Par salaire de base',
            denyButtonText: 'Par salaire net',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#3498db',
            denyButtonColor: '#71dd37'
        }).then((choice) => {
            if (!choice.isConfirmed && !choice.isDenied) return;

            const calcMode = choice.isDenied ? 'salaire_net' : 'salaire_base';

            $('#loadingModal').modal('show');

            $.ajax({
                url: '/calculate-and-generate-payslips',
                method: 'POST',
                data: {
                    year: year,
                    month: month,
                    selected_ids: selectedIds,
                    calc_mode: calcMode,          // ← nouveau paramètre
                    _token: document.querySelector('meta[name="csrf-token"]').content
                },
                success: function (response) {
                    $('#loadingModal').modal('hide');

                    if (response.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: response.error
                        });
                        return;
                    }

                    const moisCapitalise = month.charAt(0).toUpperCase() + month.slice(1);
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        html: `Les salaires sont calculés avec succès<br>
                               <small>${moisCapitalise} ${year} — mode : ${calcMode === 'salaire_net' ? 'Salaire net' : 'Salaire de base'}</small>`,
                        timer: 5000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });

                    setTimeout(() => location.reload(), 4200);
                },
                error: function (xhr) {
                    $('#loadingModal').modal('hide');
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: xhr.responseJSON?.error || 'Erreur lors du calcul et de la génération des bulletins.'
                    });
                }
            });
        });
    }
});

// gestionnaire pour .increment-salary
document.addEventListener('click', function (e) {
    if (e.target.closest('.increment-salary')) {
        e.preventDefault();
        const element = e.target.closest('.increment-salary');
        const idSalarie = element.getAttribute('data-id-salarie');
        const year = element.getAttribute('data-year');
        const month = element.getAttribute('data-month');
        const selectedMonth = document.getElementById('monthSelect').value;

        console.log('Increment salary clicked', { idSalarie, year, month, selectedMonth });

        // Mappage des mois pour afficher un nom lisible
        const monthNames = { 
            janvier: 'Janvier', fevrier: 'Février', mars: 'Mars', avril: 'Avril',
            mai: 'Mai', juin: 'Juin', juillet: 'Juillet', aout: 'Août',
            septembre: 'Septembre', octobre: 'Octobre', novembre: 'Novembre', decembre: 'Décembre',
            '': 'Aucun mois sélectionné'
        };

        // Vérifier si le mois cliqué correspond au mois sélectionné
        if (month !== selectedMonth) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: `Vous ne pouvez calculer le salaire que pour le mois sélectionné (${monthNames[selectedMonth] || selectedMonth}).`
            });
            return;
        }

        if (!idSalarie || !year || !month) {
            console.error('Missing required attributes', { idSalarie, year, month });
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'ID salarié, année ou mois manquant.'
            });
            return;
        }

        // Stocker l'état actuel du DataTable
        const dataTable = $('#payrollTable').DataTable();
        const currentPage = dataTable.page();
        const searchTerms = dataTable.columns().search().toArray();

        $.ajax({
            url: document.querySelector('meta[name="check-base-salary-url"]').content,
            method: 'POST',
            data: {
                id_salarie: idSalarie,
                year: year,
                month: month,
                apply_cimr: false,
                apply_mutuelle: false,
                _token: document.querySelector('meta[name="csrf-token"]').content
            },
            success: function (response) {
                console.log('checkBaseSalary response:', response);
                if (!response.base_salary) {
                    console.error('No base salary in response:', response);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Salaire de base non trouvé dans la réponse.'
                    });
                    return;
                }


                 // ⬇️ NOUVEAU : modale de choix
    const baseDispo = parseFloat(response.base_salary) > 0;
    const netDispo  = response.salaire_net !== null && response.salaire_net !== undefined && parseFloat(response.salaire_net) > 0;

    Swal.fire({
        title: 'Type de calcul',
        icon: 'question',
        html: `
            <p style="margin-bottom:12px;">Choisissez le mode de calcul pour
               <strong>${monthNames[month] || month} ${year}</strong> :</p>
            <div style="text-align:left;display:inline-block;font-size:14px;line-height:1.8;">
                <div><strong>Salaire de base :</strong> ${baseDispo ? response.base_salary + ' MAD' : 'non défini'}</div>
                <div><strong>Salaire net :</strong> ${netDispo ? response.salaire_net + ' MAD' : 'non défini'}</div>
            </div>`,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'Par salaire de base',
        denyButtonText: 'Par salaire net',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#3498db',
        denyButtonColor: '#71dd37'
    }).then((choice) => {
        if (!choice.isConfirmed && !choice.isDenied) return;

        const calcMode = choice.isDenied ? 'salaire_net' : 'salaire_base';

        if (calcMode === 'salaire_net' && !netDispo) {
            Swal.fire({ icon: 'error', title: 'Erreur', text: 'Aucun salaire net défini pour ce salarié.' });
            return;
        }
        if (calcMode === 'salaire_base' && !baseDispo) {
            Swal.fire({ icon: 'error', title: 'Erreur', text: 'Aucun salaire de base défini pour ce salarié.' });
            return;
        }

                // Déterminer la limite de l'indemnité de transport
                const isUrban = response.is_urban ?? true;
                const transUrbainMax = isUrban ? 700 : 500;
                const transUrbainMessage = "L'indemnité de transport ne doit pas dépasser 700 MAD pour les déplacements urbains et 500 MAD pour les déplacements ruraux.";

                // Injecter des styles CSS pour un design amélioré
                const style = document.createElement('style');
                style.innerHTML = `
                    .swal2-popup {
                        border-radius: 12px !important;
                        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
                    }
                    .swal2-popup .swal2-content {
                        width: 100% !important;
                        max-width: 1200px !important;
                        padding: 20px !important;
                    }
                    .swal2-popup .horizontal-container {
                        display: flex !important;
                        flex-wrap: wrap !important;
                        gap: 16px !important;
                        justify-content: center !important;
                        align-items: flex-start !important;
                    }
                    .swal2-popup .input-group {
                        flex: 1 !important;
                        min-width: 180px !important;
                        max-width: 220px !important;
                        margin: 0 !important;
                    }
                    .swal2-popup .input-group label {
                        font-size: 13px !important;
                        color: #34495e !important;
                        font-weight: 600 !important;
                        margin-bottom: 6px !important;
                        text-transform: uppercase !important;
                        letter-spacing: 0.5px !important;
                    }
                    .swal2-popup .input-group input[type="number"] {
                        width: 100% !important;
                        padding: 10px !important;
                        font-size: 15px !important;
                        border: 1px solid #dcdcdc !important;
                        border-radius: 8px !important;
                        background: #f9fafb !important;
                        text-align: center !important;
                        transition: all 0.2s ease !important;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
                    }
                    .swal2-popup .input-group input[type="number"]:hover {
                        border-color: #3498db !important;
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
                    }
                    .swal2-popup .input-group input[type="number"]:focus {
                        border-color: #2980b9 !important;
                        outline: none !important;
                        box-shadow: 0 0 8px rgba(41, 128, 185, 0.3) !important;
                    }
                    .swal2-popup .input-group input[type="checkbox"] {
                        width: 20px !important;
                        height: 20px !important;
                        margin: 8px auto !important;
                        display: block !important;
                        accent-color: #3498db !important;
                        cursor: pointer !important;
                    }
                    .swal2-popup .input-group .info-text {
                        font-size: 11px !important;
                        color: #7f8c8d !important;
                        margin-top: 6px !important;
                        font-style: italic !important;
                        line-height: 1.4 !important;
                    }
                    .swal2-popup .section-title {
                        font-size: 14px !important;
                        color: #2c3e50 !important;
                        font-weight: 700 !important;
                        margin: 20px 0 10px !important;
                        text-align: left !important;
                        width: 100% !important;
                        border-bottom: 1px solid #ecf0f1 !important;
                        padding-bottom: 5px !important;
                    }
                    .swal2-popup .ramadan-alert {
                        display: block !important;
                        max-width: 400px !important;
                        margin: 20px auto !important;
                        padding: 12px 16px !important;
                        font-size: 13px !important;
                        color: #d35400 !important;
                        background-color: #fef5e7 !important;
                        border: 1px solid #f1c40f !important;
                        border-radius: 8px !important;
                        border-left: 4px solid #e67e22 !important;
                        text-align: center !important;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
                    }
                    .swal2-popup .confirm-text {
                        text-align: center !important;
                        font-size: 16px !important;
                        color: #2c3e50 !important;
                        font-weight: 600 !important;
                        margin-top: 20px !important;
                    }
                    .swal2-confirm {
                        background-color: #3498db !important;
                        border-radius: 8px !important;
                        padding: 10px 24px !important;
                        font-size: 14px !important;
                        transition: background-color 0.2s ease !important;
                    }
                    .swal2-confirm:hover {
                        background-color: #2980b9 !important;
                    }
                    .swal2-cancel {
                        background-color: #ecf0f1 !important;
                        color: #7f8c8d !important;
                        border-radius: 8px !important;
                        padding: 10px 24px !important;
                        font-size: 14px !important;
                        transition: background-color 0.2s ease !important;
                    }
                    .swal2-cancel:hover {
                        background-color: #dfe6e9 !important;
                    }
                `;
                document.head.appendChild(style);

               Swal.fire({
                    width: '1200px',
                    title: calcMode === 'salaire_net' ? 'Calcul par salaire net' : 'Calcul par salaire de base',
                    html: `
                        <div style="padding: 20px; font-family: Arial, sans-serif;">
                            
                            <!-- Nouvelle checkbox pour charger les valeurs par défaut -->
                            <div style="margin-bottom: 20px; text-align: center;">
                                <label style="font-size: 16px; font-weight: 600; cursor: pointer;">
                                    <input type="checkbox" id="loadFromConfig" style="width: 18px; height: 18px; margin-right: 10px; accent-color: #3498db;" />
                                    Charger les valeurs par défaut de la configuration du salarié
                                </label>
                                <div style="font-size: 12px; color: #7f8c8d; margin-top: 6px;">
                                    (récupère les dernières valeurs enregistrées dans configurationbp)
                                </div>
                            </div>

                            <div class="section-title">Primes et Indemnités</div>
                            <div class="horizontal-container">
                                <div class="input-group">
                                    <label>Prime de panier</label>
                                    <input 
                                        type="number" 
                                        id="primePanier" 
                                        min="0" 
                                        max="800" 
                                        step="0.01" 
                                        value="0" 
                                    />
                                    <div class="info-text">
                                        Maximum 800 MAD
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Indemnité de Transport</label>
                                    <input 
                                        type="number" 
                                        id="indTransUrbain" 
                                        min="0" 
                                        max="${transUrbainMax}" 
                                        step="0.01" 
                                        value="0" 
                                    />
                                    <div class="info-text">
                                        ${transUrbainMessage}
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Prime de Représentation</label>
                                    <input 
                                        type="number" 
                                        id="primeRepresentation" 
                                        min="0" 
                                        step="0.01" 
                                        value="0" 
                                    />
                                    <div class="info-text">
                                        Divisée par 26 et multipliée par les jours de présence
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Prime de Déplacement</label>
                                    <input 
                                        type="number" 
                                        id="primeDeplacement" 
                                        min="0" 
                                        max="1000" 
                                        step="0.01" 
                                        value="0" 
                                    />
                                    <div class="info-text">
                                        Divisée par 26 et multipliée par les jours de présence
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Primes Divers</label>
                                    <input 
                                        type="number" 
                                        id="primesDivers" 
                                        min="0" 
                                        max="1000" 
                                        step="0.01" 
                                        value="0" 
                                    />
                                    <div class="info-text">
                                        Divisée par 26 et multipliée par les jours de présence
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Autres Primes Imposables</label>
                                    <input 
                                        type="number" 
                                        id="autresPrimesImposables" 
                                        min="0" 
                                        max="1000" 
                                        step="0.01" 
                                        value="0" 
                                    />
                                    <div class="info-text">
                                        Divisée par 26 et multipliée par les jours de présence
                                    </div>
                                </div>
                            </div>
                            <div class="section-title">Options de Calcul</div>
                            <div class="horizontal-container">
                                <div class="input-group">
                                    <label>Cotisation CIMR</label>
                                    <input 
                                        type="checkbox" 
                                        id="applyCimr" 
                                    />
                                    <div class="info-text">
                                        Inclure la cotisation CIMR dans le calcul
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Cotisation Mutuelle</label>
                                    <input 
                                        type="checkbox" 
                                        id="applyMutuelle" 
                                    />
                                    <div class="info-text">
                                        Inclure la cotisation Mutuelle dans le calcul
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Heures supplémentaires</label>
                                    <input 
                                        type="checkbox" 
                                        id="calculateOvertime" 
                                    />
                                    <div class="info-text">
                                        Inclure les heures supplémentaires dans le calcul
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label> salaire jours fériés</label>
                                    <input 
                                        type="checkbox" 
                                        id="doubleSalaryHolidays" 
                                    />
                                    <div class="info-text">
                                        le salaire des jours fériés
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>Appliquer Frais Professionnels</label>
                                    <input 
                                        type="checkbox" 
                                        id="applyFraisPro" 
                                        
                                    />
                                    <div class="info-text">
                                        Inclure la déduction des frais professionnels (si décoché, 0 MAD)
                                    </div>
                                </div>
                                <div class="horizontal-container">
                                    <div class="input-group">
                                        <label>Appliquer IPE</label>
                                        <input type="checkbox" id="applyIpe"  />
                                        <div class="info-text">
                                            Inclure la cotisation IPE (Indemnité de Perte d'Emploi)
                                        </div>
                                    </div>
                                    <div class="input-group">
                                        <label>Appliquer Prime de Rendement</label>
                                        <input type="checkbox" id="applyPrimeRendement"/>
                                        <div class="info-text">
                                            Inclure la prime de rendement journalière dans le calcul
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ramadan-alert">
                                Cette indemnité et primes ne sont pas attribuées pendant le mois de Ramadan.
                            </div>
                            <p class="confirm-text">
                                Confirmer pour ${month} ${year} ?
                            </p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Confirmer',
                    cancelButtonText: 'Annuler',
                    didOpen: () => {
                        // Nettoyer le style après la fermeture du modal
                        Swal.getPopup().addEventListener('afterClose', () => {
                            style.remove();
                        });

                        // Logique pour la checkbox : charger les valeurs via AJAX quand cochée
                        const checkbox = document.getElementById('loadFromConfig');
                        
                        if (checkbox) {
                            checkbox.addEventListener('change', function () {
                                if (this.checked) {
                                    this.disabled = true;  // Désactiver pendant la requête
                                    
                                    $.ajax({
                                        url: '/bultin/configuration-bp/get',  // La route qu'on a ajoutée
                                        method: 'POST',
                                        data: {
                                            id_salarie: idSalarie,
                                            _token: document.querySelector('meta[name="csrf-token"]').content
                                        },
                                        success: function (response) {
                                            if (response.success && response.data) {
                                                // Remplir les champs
                                                document.getElementById('primePanier').value             = response.data.pripanier || 0;
                                                document.getElementById('indTransUrbain').value          = response.data.indtransport || 0;
                                                document.getElementById('primeRepresentation').value     = response.data.prirepresentation || 0;
                                                document.getElementById('primeDeplacement').value        = response.data.prideplacement || 0;
                                                document.getElementById('primesDivers').value            = response.data.pridivers || 0;
                                                document.getElementById('autresPrimesImposables').value  = response.data.autrespriimposables || 0;

                                                                            // ─── Remplissage des CHECKBOXES selon la config ───
                                                document.getElementById('applyCimr').checked              = response.data.cotisation_cimr         === true;
                                                document.getElementById('applyMutuelle').checked         = response.data.cotisation_mutuelle     === true;
                                                document.getElementById('calculateOvertime').checked      = response.data.calculate_overtime      === true;
                                                document.getElementById('doubleSalaryHolidays').checked  = response.data.double_salary_holidays  === true;
                                                document.getElementById('applyFraisPro').checked         = response.data.apply_frais_pro         === true;
                                                document.getElementById('applyIpe').checked               = response.data.indemnite_pe            === true;
                                                document.getElementById('applyPrimeRendement').checked    = response.data.apply_prime_rendement   === true;
                                                
                                                Swal.showValidationMessage('Valeurs chargées avec succès !');
                                            } else {
                                                Swal.showValidationMessage(response.message || 'Aucune configuration trouvée.');
                                                checkbox.checked = false;
                                            }
                                        },
                                        error: function (xhr) {
                                            Swal.showValidationMessage('Erreur : ' + (xhr.responseJSON?.message || 'Erreur serveur'));
                                            checkbox.checked = false;
                                        },
                                        complete: function () {
                                            checkbox.disabled = false;
                                        }
                                    });
                                }
                            });
                        }
                    },
                    preConfirm: () => {
                        const primePanier = parseFloat(document.getElementById('primePanier').value) || 0;
                        const indTransUrbain = parseFloat(document.getElementById('indTransUrbain').value) || 0;
                        const primeRepresentation = parseFloat(document.getElementById('primeRepresentation').value) || 0;
                        const primeDeplacement = parseFloat(document.getElementById('primeDeplacement').value) || 0;
                        const primesDivers = parseFloat(document.getElementById('primesDivers').value) || 0;
                        const autresPrimesImposables = parseFloat(document.getElementById('autresPrimesImposables').value) || 0;
                        const applyCimr = document.getElementById('applyCimr').checked;
                        const applyMutuelle = document.getElementById('applyMutuelle').checked;
                        const calculateOvertime = document.getElementById('calculateOvertime').checked;
                        const doubleSalaryHolidays = document.getElementById('doubleSalaryHolidays').checked;
                        const applyFraisPro = document.getElementById('applyFraisPro').checked; 
                        const applyIpe = document.getElementById('applyIpe').checked;   
                        const applyPrimeRendement = document.getElementById('applyPrimeRendement').checked;

                        if (primePanier < 0) {
                            Swal.showValidationMessage('La prime de panier ne peut pas être négative.');
                            return false;
                        }
                        if (primePanier > 800) {
                            Swal.showValidationMessage('La prime de panier ne peut pas dépasser 800 MAD.');
                            return false;
                        }
                        if (indTransUrbain < 0) {
                            Swal.showValidationMessage('L\'indemnité de transport urbain ne peut pas être négative.');
                            return false;
                        }
                        if (indTransUrbain > transUrbainMax) {
                            Swal.showValidationMessage(`L'indemnité de transport ne doit pas dépasser ${transUrbainMax} MAD pour les déplacements ${isUrban ? 'urbains' : 'ruraux'}.`);
                            return false;
                        }
                        if (primeRepresentation < 0) {
                            Swal.showValidationMessage('La prime de représentation ne peut pas être négative.');
                            return false;
                        }
                        if (primeDeplacement < 0) {
                            Swal.showValidationMessage('La prime de déplacement ne peut pas être négative.');
                            return false;
                        }
                        if (primeDeplacement > 1000) {
                            Swal.showValidationMessage('La prime de déplacement ne peut pas dépasser 1000 MAD.');
                            return false;
                        }
                        if (primesDivers < 0) {
                            Swal.showValidationMessage('Les primes divers ne peuvent pas être négatives.');
                            return false;
                        }
                        if (primesDivers > 1000) {
                            Swal.showValidationMessage('Les primes divers ne peuvent pas dépasser 1000 MAD.');
                            return false;
                        }
                        if (autresPrimesImposables < 0) {
                            Swal.showValidationMessage('Les autres primes imposables ne peuvent pas être négatives.');
                            return false;
                        }
                        if (autresPrimesImposables > 1000) {
                            Swal.showValidationMessage('Les autres primes imposables ne peuvent pas dépasser 1000 MAD.');
                            return false;
                        }
                        return { 
                            primePanier: primePanier,
                            indTransUrbain: indTransUrbain,
                            primeRepresentation: primeRepresentation,
                            primeDeplacement: primeDeplacement,
                            primesDivers: primesDivers,
                            autresPrimesImposables: autresPrimesImposables,
                            applyCimr: applyCimr,
                            applyMutuelle: applyMutuelle,
                            calculateOvertime: calculateOvertime,
                            doubleSalaryHolidays: doubleSalaryHolidays,
                            applyFraisPro: applyFraisPro,
                            applyIpe: applyIpe,
                            applyPrimeRendement: applyPrimeRendement
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('Confirming salary operations', { 
                            idSalarie, 
                            year, 
                            month, 
                            primePanier: result.value.primePanier,
                            indTransUrbain: result.value.indTransUrbain,
                            primeRepresentation: result.value.primeRepresentation,
                            primeDeplacement: result.value.primeDeplacement,
                            primesDivers: result.value.primesDivers,
                            autresPrimesImposables: result.value.autresPrimesImposables,
                            applyCimr: result.value.applyCimr,
                            applyMutuelle: result.value.applyMutuelle,
                            calculateOvertime: result.value.calculateOvertime
                        });

                        $('#loadingModal').modal('show');

                        $.ajax({
                            url: document.querySelector('meta[name="update-anciennete-url"]').content,
                            method: 'POST',
                            data: {
                                id_salarie: idSalarie,
                                _token: document.querySelector('meta[name="csrf-token"]').content
                            },
                            success: function (ancienneteResponse) {
                                console.log('Anciennete update response:', ancienneteResponse);

                                $.ajax({
                                    url: document.querySelector('meta[name="increment-salary-url"]').content,
                                    method: 'POST',
                                    data: {
                                        id_salarie: idSalarie,
                                        year: year,
                                        month: month,
                                        calc_mode: calcMode, 
                                        prime_panier: result.value.primePanier,
                                        ind_trans_urbain: result.value.indTransUrbain,
                                        prime_representation: result.value.primeRepresentation,
                                        prime_deplacement: result.value.primeDeplacement,
                                        primes_divers: result.value.primesDivers,
                                        autres_primes_imposables: result.value.autresPrimesImposables,
                                        apply_cimr: result.value.applyCimr,
                                        apply_mutuelle: result.value.applyMutuelle,
                                        calculate_overtime: result.value.calculateOvertime,
                                        double_salary_holidays: result.value.doubleSalaryHolidays,
                                        apply_frais_pro: result.value.applyFraisPro,
                                        apply_ipe: result.value.applyIpe,
                                        apply_prime_rendement: result.value.applyPrimeRendement,
                                        _token: document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    success: function (incrementResponse) {
                                        console.log('incrementSalary response:', incrementResponse);
                                        if (!incrementResponse.details || !incrementResponse.details.net_payer) {
                                            console.error('No details or net_payer in incrementResponse:', incrementResponse);
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Erreur',
                                                text: 'Détails du calcul non disponibles.'
                                            });
                                            $('#loadingModal').modal('hide');
                                            return;
                                        }

                                        if (incrementResponse.leave_message) {
                                            Swal.fire({
                                                icon: 'info',
                                                title: 'Information',
                                                text: incrementResponse.leave_message,
                                                confirmButtonText: 'OK'
                                            });
                                        }

                                        const cellSelector = `td[data-id-salarie="${idSalarie}"][data-month="${month}"]`;
                                        const cell = document.querySelector(cellSelector);
                                        
                                        if (cell) {
                                            const badge = cell.querySelector('.badge');
                                            if (badge) {
                                                const typer = incrementResponse.details.typer || 'a';
                                                const hasQuittanceCah = incrementResponse.details.hasQuittanceCah || false;
                                                const badgeClass = hasQuittanceCah ? 'badge-quittance-cah' : (
                                                    typer === 'a' ? 'badge-unpaid' : (
                                                        typer ? 'badge-paid' : 'badge-initial'
                                                    )
                                                );

                                                badge.classList.remove('badge-initial', 'badge-quittance-cah', 'badge-paid', 'badge-unpaid');
                                                badge.classList.add(badgeClass);

                                                const salaryValueElement = badge.querySelector('.salary-value');
                                                if (salaryValueElement) {
                                                    salaryValueElement.textContent = incrementResponse.details.net_payer;
                                                }

                                                let iconElement = badge.querySelector('.salary-icon i.bi-people, .salary-icon i.bi-person');
                                                const iconContainer = badge.querySelector('.salary-icon');
                                                
                                                if (typer === 'vg') {
                                                    if (iconElement) {
                                                        iconElement.className = 'bi bi-people';
                                                    } else if (iconContainer) {
                                                        iconContainer.innerHTML = '<i class="bi bi-people" style="font-size: 0.9em;"></i>';
                                                    }
                                                } else if (['e', 'c', 'v'].includes(typer)) {
                                                    if (iconElement) {
                                                        iconElement.className = 'bi bi-person';
                                                    } else if (iconContainer) {
                                                        iconContainer.innerHTML = '<i class="bi bi-person" style="font-size: 0.9em;"></i>';
                                                    }
                                                } else {
                                                    if (iconElement) {
                                                        iconElement.remove();
                                                    }
                                                }

                                                $(badge).find('.dropdown-toggle').dropdown();
                                            }
                                        } else {
                                            console.error('Cell not found:', cellSelector);
                                        }

                                        const dataTable = $('#payrollTable').DataTable();
                                        if (dataTable) {
                                            dataTable.draw(false);
                                        }

                                        $.ajax({
                                            url: '/generate-payslip-pdf',
                                            method: 'POST',
                                            data: {
                                                id_salarie: idSalarie,
                                                year: year,
                                                month: month,
                                                details: incrementResponse.details,
                                                _token: document.querySelector('meta[name="csrf-token"]').content
                                            },
                                            success: function (pdfResponse) {
                                                console.log('generatePaySlipPDF response:', pdfResponse);
                                                $('#loadingModal').modal('hide');

                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Succès',
                                                    text: calcMode === 'salaire_net'
                                                    ? `Salaire de base déduit : ${incrementResponse.details.base_salary} MAD. Net à payer : ${incrementResponse.details.net_payer} MAD (${month} ${year}).`
                                                    : `Salaire de ${incrementResponse.details.net_payer} MAD calculé pour ${month} ${year}.`,
                                                    timer: 3000,
                                                    timerProgressBar: true,
                                                    didOpen: () => {
                                                        if (typeof successSound !== 'undefined' && successSound) {
                                                            successSound.play().catch(error => console.error('Erreur de lecture du son de succès:', error));
                                                        }
                                                    }
                                                });
                                            },
                                            error: function (xhr) {
                                                console.error('generatePaySlipPDF error:', xhr.responseJSON);
                                                $('#loadingModal').modal('hide');
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Erreur',
                                                    text: xhr.responseJSON?.error || 'Erreur lors de la génération du PDF.'
                                                });
                                            }
                                        });
                                    },
                                    error: function (xhr) {
                                        console.error('incrementSalary error:', xhr.responseJSON);
                                        $('#loadingModal').modal('hide');
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Erreur',
                                            text: xhr.responseJSON?.error || 'Erreur lors de l\'incrémentation.'
                                        });
                                    }
                                });
                            },
                            error: function (xhr) {
                                console.error('updateAnciennete error:', xhr.responseJSON);
                                $('#loadingModal').modal('hide');
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erreur',
                                    text: xhr.responseJSON?.error || 'Erreur lors de la mise à jour de l\'ancienneté.'
                                });
                            }
                        });
                    }
                });
                   }); 
            },
            error: function (xhr) {
                console.error('checkBaseSalary error:', xhr.responseJSON);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: xhr.responseJSON?.error || 'Erreur lors de la vérification du salaire.'
                });
            }
        });
    }
});

// Gestionnaire pour .add-salary-payment
document.addEventListener('click', function (e) {
    if (e.target.closest('.add-salary-payment')) {
        e.preventDefault();
        const element = e.target.closest('.add-salary-payment');
        
        console.log('Add salary payment clicked - START');
        
        const idSalarie = element.getAttribute('data-id-salarie');
        const year = element.getAttribute('data-year');
        const month = element.getAttribute('data-month');
        const paymentMethodSelect = document.getElementById('paymentMethodSelect');
        const paymentMethod = paymentMethodSelect ? paymentMethodSelect.value.toLowerCase() : '';
        const selectedMonth = document.getElementById('monthSelect').value.toLowerCase();

        console.log('Payment data:', { idSalarie, year, month, paymentMethod, selectedMonth });

        // Vérifier si le mois sélectionné correspond au mois du salaire
        if (selectedMonth !== month) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: `Vous ne pouvez payer que pour le mois sélectionné (${selectedMonth}).`
            });
            return;
        }

        if (!month || !year) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Veuillez sélectionner un mois et une année valides.'
            });
            return;
        }

        const validMethods = ['espece', 'cheque', 'virement'];
        if (!paymentMethod || !validMethods.includes(paymentMethod)) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Veuillez sélectionner un mode de paiement valide (Espèce, Chèque, Virement).'
            });
            return;
        }

        const selectedEmployees = [idSalarie];

        console.log('Proceeding with payment for:', selectedEmployees);

        if (paymentMethod === 'cheque') {
            Swal.fire({
                title: 'Définir le mode de paiement',
                html: `
                    <p>Employé ID: <strong>${idSalarie}</strong></p>
                    <p>Mode de paiement: <strong>${paymentMethodSelect.options[paymentMethodSelect.selectedIndex].text}</strong></p>
                    <p>Numéro de chèque: <input type="text" id="chequeNumber" class="swal2-input" placeholder="Entrez le numéro de chèque"></p>
                    <p>Confirmer pour ${month} ${year} ?</p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Confirmer',
                cancelButtonText: 'Annuler',
                preConfirm: () => {
                    const chequeNumber = document.getElementById('chequeNumber').value;
                    if (!chequeNumber) {
                        Swal.showValidationMessage('Veuillez entrer un numéro de chèque.');
                        return false;
                    }
                    return { chequeNumber };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Cheque payment confirmed');
                    processPayment(selectedEmployees, year, month, paymentMethod, result.value.chequeNumber);
                }
            });
        } else {
            Swal.fire({
                title: 'Définir le mode de paiement',
                html: `
                    <p>Employé ID: <strong>${idSalarie}</strong></p>
                    <p>Mode de paiement: <strong>${paymentMethodSelect.options[paymentMethodSelect.selectedIndex].text}</strong></p>
                    <p>Confirmer pour ${month} ${year} ?</p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Confirmer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Payment confirmed');
                    processPayment(selectedEmployees, year, month, paymentMethod, null);
                }
            });
        }
    }
});

document.getElementById('generate-virements-pdf-btn')?.addEventListener('click', function() {
    const year  = document.getElementById('yearSelect')?.value;
    const month = document.getElementById('monthSelect')?.value;

    if (!year || !month) {
        Swal.fire('Erreur', 'Veuillez sélectionner une année et un mois', 'warning');
        return;
    }

    const url = document.querySelector('meta[name="generate-virements-pdf-url"]')?.content;
    if (!url) return;

    Swal.fire({
        title: 'Génération en cours',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({ year, month })
    })
    .then(r => {
        if (!r.ok) throw new Error('Erreur serveur');
        return r.blob();
    })
    .then(blob => {
        Swal.close();
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `virements_${month}_${year}.pdf`;
        a.click();
    })
    .catch(err => {
        Swal.fire('Erreur', err.message || 'Impossible de générer le PDF', 'error');
    });
});

// Nouvelle fonction pour traiter le paiement
function processPayment(selectedEmployees, year, month, paymentMethod, chequeNumber) {
    console.log('processPayment called with:', { selectedEmployees, year, month, paymentMethod, chequeNumber });
    
    $('#loadingModal').modal('show');

    $.ajax({
        url: document.querySelector('meta[name="add-salary-payment-url"]').content,
        method: 'POST',
        data: {
            id_salaries: selectedEmployees,
            year: year,
            month: month,
            payment_method: paymentMethod,
            num_cheque: chequeNumber,
            _token: document.querySelector('meta[name="csrf-token"]').content
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function (response, status, xhr) {
            $('#loadingModal').modal('hide');

            // Mise à jour du badge dans le tableau
            const idSalarie = selectedEmployees[0];
            const cellSelector = `td[data-id-salarie="${idSalarie}"][data-month="${month}"] .badge`;
            const cell = document.querySelector(cellSelector);
            if (cell) {
                cell.classList.remove('badge-initial', 'badge-quittance-cah', 'badge-unpaid');
                cell.classList.add('badge-paid');
                const icon = document.createElement('i');
                icon.className = 'bi bi-person';
                icon.style.marginLeft = '5px';
                icon.style.fontSize = '0.9em';
                cell.appendChild(icon);
                console.log('Badge mis à jour');
            }

            // Création d'un iframe INVISIBLE juste pour imprimer
            const printIframe = document.createElement('iframe');
            printIframe.style.position = 'absolute';
            printIframe.style.width = '0';
            printIframe.style.height = '0';
            printIframe.style.border = 'none';
            printIframe.style.visibility = 'hidden';
            document.body.appendChild(printIframe);

            const blob = new Blob([response], { type: 'application/pdf' });
            const blobUrl = window.URL.createObjectURL(blob);
            printIframe.src = blobUrl;

            printIframe.onload = function() {
                try {
                    printIframe.contentWindow.focus();
                    printIframe.contentWindow.print();
                    console.log('Fenêtre d\'impression ouverte');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: 'Paiement traité et bulletin prêt à imprimer.',
                        timer: 2800,
                        timerProgressBar: true
                    });
                } catch (err) {
                    console.error('Erreur lors du déclenchement de l\'impression :', err);
                    Swal.fire({
                        icon: 'warning',
                        title: 'Attention',
                        text: 'Le PDF est prêt mais l\'impression automatique a échoué.\nVeuillez imprimer manuellement.'
                    });
                }

                // Nettoyage après un délai (on laisse un peu de temps pour l'impression)
                setTimeout(() => {
                    document.body.removeChild(printIframe);
                    window.URL.revokeObjectURL(blobUrl);
                }, 8000);
            };

            printIframe.onerror = function() {
                document.body.removeChild(printIframe);
                window.URL.revokeObjectURL(blobUrl);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Impossible de charger le bulletin pour impression.'
                });
            };
        },
        error: function (xhr, status, error) {
            $('#loadingModal').modal('hide');
            
            let errorMessage = 'Erreur lors du traitement du paiement.';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.error || errorMessage;
            } catch (e) {
                console.error('Erreur parsing réponse :', e);
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: errorMessage
            });
        }
    });
}


$(document).on('click', '#generate-virements-btn', function () {
    const year = $('#yearSelect').val();
    const month = $('#monthSelect').val().toLowerCase() || '{{ strtolower($currentMonth) }}';
    const url = $('meta[name="generate-virements-pdf-url"]').attr('content');
    const csrf = $('meta[name="csrf-token"]').attr('content');

    if (!month) {
        Swal.fire('Attention', 'Veuillez sélectionner un mois', 'warning');
        return;
    }

    $('#loadingModal').modal('show');

    $.ajax({
        url: url,
        type: 'POST',
        data: { year: year, month: month, _token: csrf },
        xhrFields: { responseType: 'blob' },
        success: function (blob) {
            $('#loadingModal').modal('hide');
            const urlBlob = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = urlBlob;
            a.download = `virements_${month}_${year}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(urlBlob);

            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: 'PDF des virements généré et téléchargé !',
                timer: 2500
            }).then(() => location.reload());
        },
        error: function (xhr) {
            $('#loadingModal').modal('hide');
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: xhr.responseJSON?.error || 'Impossible de générer le PDF.'
            });
        }
    });
});
const virementsItems = document.querySelectorAll('#virements-dropdown + .dropdown-menu .dropdown-item');
virementsItems.forEach(item => {
    item.addEventListener('click', function (e) {
        if (e.target.closest('.action-icon')) return;
        const text = this.querySelector('span').textContent;
        document.getElementById('virements-selected').textContent = text;
    });

    const downloadIcon = item.querySelector('.download-icon');
    if (downloadIcon) {
        downloadIcon.addEventListener('click', function (e) {
            e.stopPropagation();
            const filename = item.getAttribute('data-filename');
            if (!filename) {
                Swal.fire({ icon: 'error', title: 'Erreur', text: 'Nom de fichier non défini.' });
                return;
            }

            const match = filename.match(/virements_(.+?)_(\d{4})\.pdf/);
            if (!match) {
                Swal.fire({ icon: 'error', title: 'Erreur', text: 'Format de nom de fichier invalide.' });
                return;
            }

            const month = match[1];
            const year = match[2];
            const downloadUrl = `/bultin/downloadVirementsPdf/${year}/${month}`; // Ajoutez cette route si nécessaire

            $('#loadingModal').modal('show');

            fetch(downloadUrl)
                .then(response => {
                    if (!response.ok) throw new Error('Erreur HTTP');
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    link.click();
                    window.URL.revokeObjectURL(url);
                    $('#loadingModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Succès', text: 'Fichier téléchargé.' });
                })
                .catch(error => {
                    $('#loadingModal').modal('hide');
                    Swal.fire({ icon: 'error', title: 'Erreur', text: error.message });
                });
        });
    }

    const browseIcon = item.querySelector('.browse-icon');
    if (browseIcon) {
        browseIcon.addEventListener('click', function (e) {
            e.stopPropagation();
            const filename = item.getAttribute('data-filename');
            if (!filename) {
                Swal.fire({ icon: 'error', title: 'Erreur', text: 'Nom de fichier non défini.' });
                return;
            }

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = '.pdf';
            fileInput.onchange = function (evt) {
                const file = evt.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('virements_file', file);
                formData.append('filename', filename);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                $('#loadingModal').modal('show');

                $.ajax({
                    url: '/bultin/uploadVirementsPdf', // Ajoutez cette route
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $('#loadingModal').modal('hide');
                        Swal.fire({ icon: 'success', title: 'Succès', text: response.message });
                        location.reload();
                    },
                    error: function (xhr) {
                        $('#loadingModal').modal('hide');
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.error || 'Erreur d\'upload.' });
                    }
                });
            };
            fileInput.click();
        });
    }
});

  // gestionnaire pour .download-pay-slip
document.addEventListener('click', function (e) {
    const element = e.target.closest('.download-pay-slip');
    if (!element) return;

    e.preventDefault();

    if (element.classList.contains('disabled')) {
        Swal.fire({
            icon: 'error',
            title: 'Action bloquée',
            text: 'Vous ne pouvez télécharger le bulletin de paie que pour le mois sélectionné.'
        });
        return;
    }

    const idSalarie = element.getAttribute('data-id-salarie');
    const year = element.getAttribute('data-year');
    const month = element.getAttribute('data-month').toLowerCase();
    const selectedMonth = document.getElementById('monthSelect').value.toLowerCase();

    if (selectedMonth !== month) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: `Vous ne pouvez télécharger le bulletin de paie que pour le mois sélectionné (${selectedMonth}).`
        });
        return;
    }

    console.log('Download pay slip clicked', { idSalarie, year, month });

    $.ajax({
        url: '/bultin/download-pay-slip',
        method: 'POST',
        data: {
            id_salarie: idSalarie,
            year: year,
            month: month,
            _token: document.querySelector('meta[name="csrf-token"]').content
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function (data, status, xhr) {
            const contentType = xhr.getResponseHeader('Content-Type');
            console.log('Response Content-Type:', contentType, 'Data size:', data.size);

            if (contentType.includes('application/pdf')) {
                let fileName = `bulletin_paie_${idSalarie}_${month}_${year}.pdf`;
                const disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.includes('attachment')) {
                    const matches = /filename="([^"]*)"/.exec(disposition);
                    if (matches && matches[1]) {
                        fileName = matches[1];
                    }
                }

                const blob = new Blob([data], { type: 'application/pdf' });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                Swal.fire({
                    icon: 'success',
                    text: 'Bulletin de paie téléchargé avec succès.',
                    timer: 2000,
                    timerProgressBar: true
                });
            } else {
                const reader = new FileReader();
                reader.onload = function () {
                    try {
                        const jsonResponse = JSON.parse(reader.result);
                        console.error('JSON error response:', jsonResponse);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: jsonResponse.error || 'Erreur lors du téléchargement.'
                        });
                    } catch (err) {
                        console.error('Failed to parse JSON:', err, 'Response text:', reader.result);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Réponse serveur invalide.'
                        });
                    }
                };
                reader.readAsText(data);
            }
        },
        error: function (xhr) {
            console.error('downloadPaySlip error:', {
                status: xhr.status,
                contentType: xhr.getResponseHeader('Content-Type'),
                responseText: xhr.responseText
            });
            try {
                const jsonResponse = JSON.parse(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: jsonResponse.error || 'Erreur lors du téléchargement.'
                });
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Erreur serveur lors du téléchargement.'
                });
            }
        }
    });
});

    // gestionnaire pour .upload-pay-slip

document.addEventListener('click', function (e) {
    const element = e.target.closest('.upload-pay-slip');
    if (element) {
        e.preventDefault();

        // Check if the link is disabled
        if (element.classList.contains('disabled')) {
            Swal.fire({
                icon: 'error',
                title: 'Action bloquée',
                text: 'Vous ne pouvez téléverser le bulletin de salaire que pour le mois sélectionné.'
            });
            return;
        }

        const idSalarie = element.getAttribute('data-id-salarie');
        const year = element.getAttribute('data-year');
        const month = element.getAttribute('data-month').toLowerCase();
        const selectedMonth = document.getElementById('monthSelect').value.toLowerCase();

        // Fallback validation for month
        if (selectedMonth !== month) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: `Vous ne pouvez téléverser le bulletin de salaire que pour le mois sélectionné (${selectedMonth}).`
            });
            return;
        }

        console.log('Upload pay slip clicked', { idSalarie, year, month });

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'application/pdf';

        input.onchange = function (event) {
            const file = event.target.files[0];
            if (!file) {
                console.log('File selection canceled, no badge update');
                return; // Ne rien faire si l'utilisateur annule
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('id_salarie', idSalarie);
            formData.append('year', year);
            formData.append('month', month);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            // Afficher le modal de chargement (auto loading)
            $('#loadingModal').modal('show');

            $.ajax({
                url: '/bultin/upload-pay-slip',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    console.log('uploadPaySlip success', response);

                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Bulletin de salaire téléversé avec succès!',
                        timer: 2500,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });

                    // Recharger la page automatiquement pour actualiser l'icône du badge
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                },
                error: function (xhr) {
                    // Masquer le modal de chargement uniquement en cas d'erreur
                    $('#loadingModal').modal('hide');

                    console.error('uploadPaySlip error', xhr.responseJSON);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: xhr.responseJSON?.error || 'Erreur lors du téléversement du bulletin de salaire.'
                    });
                }
            });
        };

        input.click();
    }
});


      // gestionnaire pour .download-quittance
document.addEventListener('click', function (e) {
    // Vérifier si l'élément cliqué ou l'un de ses parents a la classe .download-quittance
    const element = e.target.closest('.download-quittance');
    if (!element) return; // Si ce n'est pas un élément .download-quittance, sortir

    e.preventDefault();
    const idSalarie = element.getAttribute('data-id-salarie');
    const year = element.getAttribute('data-year');
    const month = element.getAttribute('data-month');
    const selectedMonth = document.getElementById('monthSelect').value.toLowerCase();

    // Vérifier si le mois sélectionné correspond au mois de la quittance
    if (selectedMonth !== month) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: `Vous ne pouvez télécharger la quittance que pour le mois sélectionné (${selectedMonth}).`
        });
        return;
    }

    console.log('Download quittance clicked', { idSalarie, year, month });

    $.ajax({
        url: document.querySelector('meta[name="download-quittance-url"]').content,
        method: 'POST',
        data: {
            id_salarie: idSalarie,
            year: year,
            month: month,
            _token: document.querySelector('meta[name="csrf-token"]').content
        },
        xhrFields: {
            responseType: 'blob' // Expect a PDF response
        },
        success: function (response, status, xhr) {
            const contentType = xhr.getResponseHeader('Content-Type');
            if (contentType && contentType.includes('application/pdf')) {
                const disposition = xhr.getResponseHeader('Content-Disposition');
                let fileName = `quittance_${idSalarie}_${month}_${year}.pdf`;
                if (disposition && disposition.includes('attachment')) {
                    const matches = /filename="([^"]*)"/.exec(disposition);
                    if (matches && matches[1]) {
                        fileName = matches[1];
                    }
                }

                const blob = new Blob([response], { type: 'application/pdf' });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: 'Quittance téléchargée avec succès.',
                    timer: 2000,
                    timerProgressBar: true
                });
            } else {
                response.text().then(text => {
                    try {
                        const jsonResponse = JSON.parse(text);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: jsonResponse.error || 'Une erreur est survenue lors du téléchargement.'
                        });
                    } catch (e) {
                        console.error('Failed to parse response as JSON', { text, error: e });
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Réponse inattendue du serveur.'
                        });
                    }
                });
            }
        },
        error: function (xhr) {
            console.error('downloadQuittance error', {
                status: xhr.status,
                responseText: xhr.responseText,
                contentType: xhr.getResponseHeader('Content-Type')
            });

            if (xhr.response instanceof Blob) {
                xhr.response.text().then(text => {
                    try {
                        const jsonResponse = JSON.parse(text);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: jsonResponse.error || 'Une erreur est survenue lors du téléchargement.'
                        });
                    } catch (e) {
                        console.error('Failed to parse Blob response as JSON', { text, error: e });
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Erreur lors du téléchargement de la quittance.'
                        });
                    }
                }).catch(err => {
                    console.error('Failed to read Blob as text', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Erreur lors du téléchargement de la quittance.'
                    });
                });
            } else {
                let errorMessage = 'Erreur lors du téléchargement de la quittance.';
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMessage = response.error;
                        }
                    } catch (e) {
                        console.error('Failed to parse JSON response', e);
                        errorMessage = xhr.responseText || errorMessage;
                    }
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: errorMessage
                });
            }
        }
    });
});



 // gestionnaire pour .upload-quittance
document.addEventListener('click', function (e) {
    const element = e.target.closest('.upload-quittance');
    if (!element) return;

    e.preventDefault();

    if (element.classList.contains('disabled')) {
        Swal.fire({
            icon: 'error',
            title: 'Action bloquée',
            text: 'Vous ne pouvez téléverser la quittance que pour le mois sélectionné.'
        });
        return;
    }

    const idSalarie = element.getAttribute('data-id-salarie');
    const year = element.getAttribute('data-year');
    const month = element.getAttribute('data-month').toLowerCase();
    const selectedMonth = document.getElementById('monthSelect').value.toLowerCase();

    if (selectedMonth !== month) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: `Vous ne pouvez téléverser la quittance que pour le mois sélectionné (${selectedMonth}).`
        });
        return;
    }

    console.log('Upload quittance clicked', { idSalarie, year, month });

    Swal.fire({
        title: 'Parcourir Quittance',
        html: `
            <p>Sélectionnez un fichier (PDF, PNG, JPG, JPEG).</p>
            <input type="file" id="quittanceFile" class="swal2-file" accept="image/*,.pdf" style="display: block; margin: 10px auto;">
        `,
        showCancelButton: true,
        confirmButtonText: 'Envoyer',
        cancelButtonText: 'Annuler',
        preConfirm: () => {
            const fileInput = document.getElementById('quittanceFile');
            const file = fileInput.files[0];
            if (!file) {
                Swal.showValidationMessage('Veuillez sélectionner un fichier.');
                return false;
            }
            return { file, idSalarie, year, month };
        }
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('quittance_file', result.value.file);
            formData.append('id_salarie', result.value.idSalarie);
            formData.append('year', result.value.year);
            formData.append('month', result.value.month);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            console.log('Submitting quittance', {
                idSalarie: result.value.idSalarie,
                year: result.value.year,
                month: result.value.month,
                fileName: result.value.file.name
            });

            $('#loadingModal').modal('show');

            $.ajax({
                url: document.querySelector('meta[name="upload-quittance-url"]').content,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#loadingModal').modal('hide');

                    console.log('Success', response);
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message,
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        const cell = document.querySelector(`td[data-id-salarie="${idSalarie}"][data-month="${month}"] .badge`);
                        if (cell) {
                            cell.classList.remove('badge-initial', 'badge-paid', 'badge-unpaid');
                            cell.classList.add('badge-quittance-cah');
                        }
                    });
                },
                error: function (xhr) {
                    $('#loadingModal').modal('hide');

                    console.error('uploadQuittance error', {
                        status: xhr.status,
                        response: xhr.responseJSON
                    });
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: xhr.responseJSON?.error || 'Erreur lors de l\'upload.'
                    });
                }
            });
        }
    });
});





 // gestionnaire pour .bulk-payment-btn
document.getElementById('bulk-payment-btn').addEventListener('click', function (e) {
    e.preventDefault();

    const paymentMethodSelect = document.getElementById('paymentMethodSelect');
    const paymentMethod = paymentMethodSelect.value;
    const year = document.getElementById('yearSelect').value;
    const month = document.getElementById('monthSelect').value;

    if (!month) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Vous devez sélectionner un mois pour effectuer le paiement.'
        });
        return;
    }

    if (!year) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Vous devez sélectionner une année pour effectuer le paiement.'
        });
        return;
    }

    // Récupérer toutes les lignes du DataTable (toutes les pages, cochées)
    const dataTable = $('#payrollTable').DataTable();
    const selectedEmployees = [];

    dataTable.rows().every(function () {
        const row = this.node();
        const checkbox = row.querySelector('.employee-checkbox');
        if (checkbox && checkbox.checked) {
            const idSalarie = checkbox.getAttribute('data-id-salarie');
            if (idSalarie) {
                selectedEmployees.push(idSalarie);
            }
        }
    });

    if (selectedEmployees.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Veuillez sélectionner au moins un employé.'
        });
        return;
    }

    console.log('Bulk payment clicked', { selectedEmployees, year, month, paymentMethod });

    const isBulkPayment = paymentMethod === 'virement-g' || paymentMethod === 'vg';
    const addSalaryPaymentUrl = document.querySelector('meta[name="add-salary-payment-url"]')?.content;
    const bulkSalaryPaymentUrl = document.querySelector('meta[name="bulk-salary-payment-url"]')?.content;

    if (!addSalaryPaymentUrl || !bulkSalaryPaymentUrl) {
        console.error('Missing URL meta tags', { addSalaryPaymentUrl, bulkSalaryPaymentUrl });
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'URL de l\'API non trouvée.'
        });
        return;
    }

    const url = isBulkPayment ? bulkSalaryPaymentUrl : addSalaryPaymentUrl;

    Swal.fire({
        title: 'Exécuter Paiement Groupé',
        html: `
            <p>Mode de paiement: <strong>${paymentMethodSelect.options[paymentMethodSelect.selectedIndex].text}</strong></p>
            <p>Salariés sélectionnés: <strong>${selectedEmployees.length}</strong> (IDs: ${selectedEmployees.join(', ')})</p>
            <p>Confirmer pour ${month} ${year} ?</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Confirmer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            console.log('Confirming payment', { selectedEmployees, year, month, paymentMethod, url });

            // Auto loading pendant le traitement
            Swal.fire({
                title: 'Traitement en cours...',
                text: 'Veuillez patienter pendant l\'exécution du paiement groupé.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    id_salaries: selectedEmployees,
                    year: year,
                    month: month,
                    payment_method: paymentMethod,
                    _token: document.querySelector('meta[name="csrf-token"]').content
                },
                success: function (response) {
                    Swal.close();

                    // Colorer immédiatement tous les badges concernés en mauve
                    selectedEmployees.forEach(function (idSalarie) {
        const cell = document.querySelector(
            `td[data-id-salarie="${idSalarie}"][data-month="${month}"] .badge`
        );
        if (cell) {
            cell.classList.remove(
                'badge-initial',
                'badge-unpaid',
                'badge-payslip',
                'badge-quittance-cah'
            );
            cell.classList.add('badge-paid');
        }
    });

    console.log('Payment success', response);

    Swal.fire({
        icon: 'success',
        title: 'Succès',
        text: response.success || 'Paiement effectué avec succès',
        timer: 2200,
        timerProgressBar: true,
        showConfirmButton: false
    }).then(() => {

        if (response.bulk_pdf_path) {
            const pdfUrl = window.location.origin + '/' + response.bulk_pdf_path;
            console.log('Début impression PDF:', pdfUrl);

            const printIframe = document.createElement('iframe');
            printIframe.style.position = 'absolute';
            printIframe.style.left = '-9999px';
            printIframe.style.top = '-9999px';
            printIframe.style.width = '1px';
            printIframe.style.height = '1px';
            printIframe.style.opacity = '0';
            printIframe.style.border = 'none';

            printIframe.src = pdfUrl;
            document.body.appendChild(printIframe);

            printIframe.onload = function () {
                console.log('PDF chargé - Lancement de l\'impression');
                setTimeout(() => {
                    try {
                        printIframe.contentWindow.focus();
                        printIframe.contentWindow.print();
                        console.log('Commande print() envoyée');
                    } catch (err) {
                        console.error('Erreur print via iframe', err);
                    }
                }, 1300);
            };

            // === AUTO-RELOAD APRÈS L'IMPRESSION ===
            setTimeout(() => {
                console.log('Rechargement de la page après impression');
                if (printIframe && printIframe.parentNode) {
                    printIframe.parentNode.removeChild(printIframe);
                }
                location.reload();
            }, 5000);

        } else {
            // Pas de PDF groupé → rechargement rapide
            setTimeout(() => {
                location.reload();
            }, 1500);
        }
    });
},
                error: function (xhr) {
                    Swal.close();
                    console.error('Payment error', xhr.responseJSON);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: xhr.responseJSON?.error || 'Erreur lors de l\'exécution du paiement.'
                    });
                }
            });
        }
    });
});


// gestionnaire pour .delete-payment
document.addEventListener('click', function (e) {
    const element = e.target.closest('.delete-payment');
    if (!element) return;

    e.preventDefault();

    const idSalarie = element.getAttribute('data-id-salarie');
    const year = element.getAttribute('data-year');
    const month = element.getAttribute('data-month').toLowerCase();
    const selectedMonth = document.getElementById('monthSelect').value.toLowerCase();

    // Validate month
    const monthNames = {
        janvier: 'Janvier', fevrier: 'Février', mars: 'Mars', avril: 'Avril',
        mai: 'Mai', juin: 'Juin', juillet: 'Juillet', aout: 'Août',
        septembre: 'Septembre', octobre: 'Octobre', novembre: 'Novembre', decembre: 'Décembre'
    };
    if (selectedMonth !== month) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: `Vous ne pouvez supprimer le paiement que pour le mois sélectionné (${monthNames[selectedMonth] || selectedMonth}).`
        });
        return;
    }

    // Confirm deletion
    Swal.fire({
        title: 'Supprimer le paiement',
        html: `
            <p>Voulez-vous vraiment supprimer le paiement pour l'employé ID <strong>${idSalarie}</strong> pour ${monthNames[month]} ${year} ?</p>
            <p>Cela supprimera également les fichiers associés (bulletin et quittance).</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            // Store current DataTable state
            const dataTable = $('#payrollTable').DataTable();
            const currentPage = dataTable.page();
            const searchTerms = dataTable.columns().search().toArray();

            // Show loading modal
            $('#loadingModal').modal('show');

            $.ajax({
                url: document.querySelector('meta[name="delete-payment-url"]').content,
                method: 'POST',
                data: {
                    id_salarie: idSalarie,
                    year: year,
                    month: month,
                    _token: document.querySelector('meta[name="csrf-token"]').content
                },
                success: function (response) {
                    $('#loadingModal').modal('hide');

                    // Update DataTable cell
                    const cellSelector = `td[data-id-salarie="${idSalarie}"][data-month="${month}"] .badge`;
                    const cell = document.querySelector(cellSelector);
                    if (cell) {
                        cell.classList.remove('badge-paid', 'badge-unpaid', 'badge-quittance-cah');
                        cell.classList.add('badge-initial');
                        cell.textContent = '-';
                        // Remove any icons (e.g., person or trash)
                        const icons = cell.querySelectorAll('i');
                        icons.forEach(icon => icon.remove());
                    } else {
                        console.error('Cell not found:', cellSelector);
                    }

                    // Update DataTable data
                    const row = dataTable.row(`tr td[data-id-salarie="${idSalarie}"]`);
                    if (row.length) {
                        const rowData = row.data();
                        const monthIndex = Object.keys(monthNames).indexOf(month) + 3; // Adjust based on column index
                        rowData[monthIndex] = '<span class="badge badge-initial">-</span>';
                        row.data(rowData).invalidate();
                    } else {
                        console.error('Row not found for id_salarie:', idSalarie);
                    }

                    // Redraw DataTable while preserving state
                    dataTable.draw(false);
                    dataTable.page(currentPage).draw(false);
                    searchTerms.forEach((term, index) => {
                        if (term) {
                            dataTable.column(index).search(term).draw(false);
                        }
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Paiement supprimé avec succès.',
                        timer: 2000,
                        timerProgressBar: true
                    });
                },
                error: function (xhr) {
                    $('#loadingModal').modal('hide');
                    console.error('deletePayment error:', xhr.responseJSON);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: xhr.responseJSON?.error || 'Erreur lors de la suppression du paiement.'
                    });
                }
            });
        }
    });
});



    
// gestionnaire pour .download-icon et .browse-icon
const dropdownItems = document.querySelectorAll('#bulk-payroll-dropdown + .dropdown-menu .dropdown-item');
dropdownItems.forEach(item => {
    item.addEventListener('click', function (e) {
        if (e.target.closest('.action-icon')) return;
        const text = this.querySelector('span').textContent;
        document.getElementById('bulk-payroll-selected').textContent = text;
    });

    const downloadIcon = item.querySelector('.download-icon');
    if (downloadIcon) {
        downloadIcon.addEventListener('click', function (e) {
            e.stopPropagation();
            const filename = item.getAttribute('data-filename');
            if (!filename) {
                console.error('No filename attribute found', { item });
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Nom de fichier non défini.'
                });
                return;
            }

            // Extraire le mois et l'année
            const match = filename.match(/bulk_payroll_(.+?)_(\d{4})\.pdf/);
            if (!match) {
                console.error('Invalid filename format', { filename });
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Format de nom de fichier invalide.'
                });
                return;
            }

            const month = match[1];
            const year = match[2];
            const downloadUrl = `/bultin/downloadGroupPayment/${year}/${month}`;
            console.log('Initiating download', { filename, month, year, downloadUrl });

            // Afficher le modal de chargement
            $('#loadingModal').modal('show');

            fetch(downloadUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf, application/json'
                }
            })
                .then(response => {
                    console.log('Download response', {
                        status: response.status,
                        headers: Object.fromEntries(response.headers.entries())
                    });
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || `Erreur HTTP ${response.status}`);
                        });
                    }
                    return response.blob();
                })
                .then(blob => {
                    if (blob.type !== 'application/pdf') {
                        throw new Error('Réponse inattendue : pas un PDF');
                    }
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `bulk_payroll_${month}_${year}.pdf`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                    $('#loadingModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: 'Fichier téléchargé avec succès.',
                        timer: 2000
                    });
                })
                .catch(error => {
                    $('#loadingModal').modal('hide');
                    console.error('Download error', { error: error.message, stack: error.stack });
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: error.message || 'Erreur lors du téléchargement.'
                    });
                });
        });
    }

    const browseIcon = item.querySelector('.browse-icon');
    if (browseIcon) {
        browseIcon.addEventListener('click', function (e) {
            e.stopPropagation();
            const filename = item.getAttribute('data-filename');
            if (!filename) {
                console.error('No filename attribute found', { item });
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Nom de fichier non défini.'
                });
                return;
            }

            console.log('Initiating upload', { filename });

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = '.pdf';
            fileInput.style.display = 'none';

            fileInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (!file) {
                    console.warn('No file selected');
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Aucun fichier sélectionné.'
                    });
                    return;
                }

                const formData = new FormData();
                formData.append('bulk_payroll_file', file);
                formData.append('filename', filename);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                console.log('Uploading bulk payroll', { filename, fileName: file.name });

                $('#loadingModal').modal('show');

                $.ajax({
                    url: document.querySelector('meta[name="upload-bulk-payroll-url"]').content,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $('#loadingModal').modal('hide');
                        console.log('uploadBulkPayroll success', response);
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: response.message || 'Fichier uploadé avec succès.',
                            timer: 3000,
                            timerProgressBar: true
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        $('#loadingModal').modal('hide');
                        console.error('uploadBulkPayroll error', {
                            status: xhr.status,
                            response: xhr.responseJSON
                        });
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: xhr.responseJSON?.error || xhr.responseJSON?.message || 'Erreur lors de l\'upload.'
                        });
                    }
                });
            });

            document.body.appendChild(fileInput);
            fileInput.click();
            document.body.removeChild(fileInput);
        });
    }
});


$(document).on('click', '.delete-salary-payment', function (e) {
    e.preventDefault();
    if ($(this).hasClass('disabled')) return;

    const idSalarie = $(this).data('id-salarie');
    const year = $(this).data('year');
    const month = $(this).data('month').toLowerCase();
    const url = $('meta[name="delete-salary-payment-url"]').attr('content');
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    if (!csrfToken) {
        Swal.fire('Erreur', 'Token CSRF manquant.', 'error');
        return;
    }

    console.log('Données envoyées : ', { id_salarie: idSalarie, year: year, month: month, _token: csrfToken });

    Swal.fire({
        title: 'Confirmation',
        text: `Voulez-vous vraiment supprimer le calcul et le paiement pour ${month} ${year} ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            $('#loadingModal').modal('show');
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    id_salarie: idSalarie,
                    year: year,
                    month: month,
                    _token: csrfToken
                },
                success: function (response) {
                    $('#loadingModal').modal('hide');
                    if (response.success) {
                        Swal.fire('Succès', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Erreur', response.message, 'error');
                    }
                },
                error: function (xhr) {
                    $('#loadingModal').modal('hide');
                    let errorMessage = 'Une erreur est survenue lors de la suppression.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 403) {
                        errorMessage = 'Erreur CSRF : Token invalide.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Aucun enregistrement trouvé.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Erreur serveur : vérifiez les logs pour plus de détails.';
                    }
                    Swal.fire('Erreur', errorMessage, 'error');
                    console.error('Erreur AJAX : ', xhr);
                }
            });
        }
    });
});

document.addEventListener('click', function (e) {
    const element = e.target.closest('.download-pay-slip-cachet');
    if (!element) return;

    e.preventDefault();

    if (element.classList.contains('disabled')) {
        Swal.fire({
            icon: 'error',
            title: 'Action bloquée',
            text: 'Vous ne pouvez télécharger le bulletin cacheté que pour le mois sélectionné.'
        });
        return;
    }

    const idSalarie = element.getAttribute('data-id-salarie');
    const year = element.getAttribute('data-year');
    const month = element.getAttribute('data-month').toLowerCase();
    const selectedMonth = document.getElementById('monthSelect').value.toLowerCase();

    if (selectedMonth !== month) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: `Vous ne pouvez télécharger le bulletin cacheté que pour le mois sélectionné (${selectedMonth}).`
        });
        return;
    }

    console.log('Download stamped pay slip clicked', { idSalarie, year, month });

    $.ajax({
        url: document.querySelector('meta[name="download-pay-slip-cachet-url"]').content,
        method: 'POST',
        data: {
            id_salarie: idSalarie,
            year: year,
            month: month,
            _token: document.querySelector('meta[name="csrf-token"]').content
        },
        xhrFields: { responseType: 'blob' },
        success: function (data, status, xhr) {
            const contentType = xhr.getResponseHeader('Content-Type');

            if (contentType && contentType.includes('application/pdf')) {
                let fileName = `bulletin_paie_cachete_${idSalarie}_${month}_${year}.pdf`;
                const disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.includes('attachment')) {
                    const matches = /filename="([^"]*)"/.exec(disposition);
                    if (matches && matches[1]) fileName = matches[1];
                }

                const blob = new Blob([data], { type: 'application/pdf' });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                Swal.fire({
                    icon: 'success',
                    text: 'Bulletin de paie cacheté téléchargé avec succès.',
                    timer: 2000,
                    timerProgressBar: true
                });
            } else {
                const reader = new FileReader();
                reader.onload = function () {
                    try {
                        const jsonResponse = JSON.parse(reader.result);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: jsonResponse.error || 'Erreur lors du téléchargement.'
                        });
                    } catch (err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Réponse serveur invalide.'
                        });
                    }
                };
                reader.readAsText(data);
            }
        },
        error: function (xhr) {
            console.error('downloadPaySlipCachet error:', xhr.status, xhr.responseText);
            let errorMessage = 'Erreur serveur lors du téléchargement.';
            try {
                const jsonResponse = JSON.parse(xhr.responseText);
                errorMessage = jsonResponse.error || errorMessage;
            } catch (e) {}
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: errorMessage
            });
        }
    });
});
document.addEventListener('click', function (e) {
    const element = e.target.closest('.download-quittance-cah');
    if (!element) return;

    e.preventDefault();

    if (element.classList.contains('disabled')) {
        Swal.fire({
            icon: 'error',
            title: 'Action bloquée',
            text: 'Vous ne pouvez télécharger la quittance cachetée que pour le mois sélectionné.'
        });
        return;
    }

    const idSalarie = element.getAttribute('data-id-salarie');
    const year = element.getAttribute('data-year');
    const month = element.getAttribute('data-month').toLowerCase();
    const selectedMonth = document.getElementById('monthSelect').value.toLowerCase();

    if (selectedMonth !== month) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: `Vous ne pouvez télécharger la quittance cachetée que pour le mois sélectionné (${selectedMonth}).`
        });
        return;
    }

    console.log('Download quittance cah clicked', { idSalarie, year, month });

    $.ajax({
        url: document.querySelector('meta[name="download-quittance-cah-url"]').content,
        method: 'POST',
        data: {
            id_salarie: idSalarie,
            year: year,
            month: month,
            _token: document.querySelector('meta[name="csrf-token"]').content
        },
        xhrFields: { responseType: 'blob' },
        success: function (response, status, xhr) {
            const contentType = xhr.getResponseHeader('Content-Type');

            if (contentType && !contentType.includes('application/json')) {
                const disposition = xhr.getResponseHeader('Content-Disposition');
                let fileName = `quittance_cachetee_${idSalarie}_${month}_${year}`;
                if (disposition && disposition.includes('attachment')) {
                    const matches = /filename="([^"]*)"/.exec(disposition);
                    if (matches && matches[1]) fileName = matches[1];
                }

                const blob = new Blob([response], { type: contentType });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: 'Quittance cachetée téléchargée avec succès.',
                    timer: 2000,
                    timerProgressBar: true
                });
            } else {
                response.text().then(text => {
                    try {
                        const jsonResponse = JSON.parse(text);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: jsonResponse.error || 'Une erreur est survenue lors du téléchargement.'
                        });
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Réponse inattendue du serveur.'
                        });
                    }
                });
            }
        },
        error: function (xhr) {
            console.error('downloadQuittanceCah error', {
                status: xhr.status,
                responseText: xhr.responseText
            });

            if (xhr.response instanceof Blob) {
                xhr.response.text().then(text => {
                    try {
                        const jsonResponse = JSON.parse(text);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: jsonResponse.error || 'Erreur lors du téléchargement.'
                        });
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Erreur lors du téléchargement de la quittance cachetée.'
                        });
                    }
                });
            } else {
                let errorMessage = 'Erreur lors du téléchargement de la quittance cachetée.';
                try {
                    const jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse.error) errorMessage = jsonResponse.error;
                } catch (e) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: errorMessage
                });
            }
        }
    });
});




});