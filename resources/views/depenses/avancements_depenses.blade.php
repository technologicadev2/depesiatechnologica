@extends('master_page.app')

@section('title')
    Avancements de Salaires
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Avancements de Salaires</h4>

    <style>
        .dt-controls,
        .dt-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dt-action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dt-action-buttons .btn {
            white-space: nowrap;
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }

        .dt-filter input {
            width: 200px;
        }

        @media (max-width: 767px) {
            .dt-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                justify-content: center;
                margin-bottom: 10px;
            }

            .dt-filter {
                flex-direction: column;
                align-items: stretch;
                justify-content: center;
                gap: 8px;
                width: 100%;
            }

            .dt-filter input {
                width: 100%;
                max-width: 100%;
                padding-left: 30px;
                font-size: 0.85rem;
                background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.7 1.7 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 10px center;
                background-size: 14px;
            }

            .dt-action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: 6px;
            }

            .dt-action-buttons .btn {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }

            .total-display {
                width: 100%;
                text-align: center;
                margin-top: 8px;
                font-size: 0.8rem;
                padding: 0.4rem;
            }

            .dataTables_filter label {
                font-size: 0;
                width: 100%;
            }

            .dataTables_filter label input {
                font-size: 0.85rem;
                width: 100%;
            }

            .datatables-basic th,
            .datatables-basic td {
                font-size: 0.75rem;
                padding: 4px;
            }

            .action-buttons .btn-icon {
                font-size: 0.8rem;
                padding: 1px;
            }
        }

        .datatables-basic thead input {
            width: 100%;
            padding: 3px;
            box-sizing: border-box;
            margin: 2px 0;
            font-size: 0.875rem;
        }

        .datatables-basic thead tr:nth-child(2) th {
            padding: 5px;
        }

        .total-display {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            background-color: #e9ecef;
            border-radius: 4px;
            white-space: nowrap;
        }

        .datatables-basic td .btn-icon,
        .datatables-basic td .delete-form {
            display: inline-block !important;
            vertical-align: middle !important;
            margin: 0 1px !important;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .action-buttons .btn-icon,
        .action-buttons .delete-form {
            margin: 0;
        }

        .details-control {
            display: none;
        }

        .actions-row {
            flex-wrap: nowrap !important;
            white-space: nowrap;
            align-items: center;
        }

        .actions-row a,
        .actions-row button {
            margin: 0 2px;
            padding: 0;
            font-size: 1.25rem;
            background: none !important;
            border: none;
            color: #6c757d;
        }

        .actions-row a:hover,
        .actions-row button:hover {
            color: #007bff;
        }

        .actions-row .delete-btn {
            background: none !important;
            border: none;
            padding: 0;
        }

        .actions-row .delete-btn i {
            color: #dc3545;
        }

        .actions-row .delete-form {
            display: inline;
            margin: 0;
        }

        .show-details-btn {
            background: none !important;
            border: none;
            padding: 0;
            cursor: pointer;
            color: inherit;
            font-size: 1.25rem;
        }

        .show-details-btn:hover {
            color: #007bff;
        }

        .show-details-btn i {
            vertical-align: middle;
        }
    </style>

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: "{{ session('success') }}",
                    position: 'center',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: () => {
                        const successSound = new Audio('{{ asset('assets/audio/success.mp3') }}');
                        successSound.play().catch(error => console.log('Erreur de lecture audio:', error));
                    }
                });
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: "{{ session('error') }}",
                    position: 'center',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: () => {
                        const errorSound = new Audio('{{ asset('assets/audio/error.mp3') }}');
                        errorSound.play().catch(error => console.log('Erreur de lecture audio:', error));
                    }
                });
            });
        </script>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="row ms-2 me-3 dt-filter-container">
                <div
                    class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                    <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3">
                        <div class="total-display" id="total-display-avancements">Total:
                            {{ number_format($totalAvancements, 2) }}</div>
                        @if (Auth::check() && Auth::user()->role->name === 'superadmin')
                            <!-- Bouton pour générer le PDF des avances sélectionnées -->
                            <button id="generate-selected-pdf" class="btn btn-primary" disabled>Générer PDF Avances
                                Sélectionnées</button>
                            <!-- Bouton existant pour générer le PDF de toutes les avances -->
                           <!--  <a href="{{ route('depenses.avances.pdf', ['date_debut' => $dateDebut ?? now()->startOfMonth()->format('Y-m-d'), 'date_fin' => $dateFin ?? now()->endOfMonth()->format('Y-m-d')]) }}"
                                class="btn btn-primary">Générer PDF Toutes Avances</a> -->
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-basic table table-striped table-hover border-top" id="avancementsTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-avances"></th>
                        <!-- Case à cocher pour tout sélectionner -->
                        <th>Code</th>
                        <th>Référence</th>
                        
                        <th>Date</th>
                        <th>Mois_creation</th>
                        <th>n_mt_salarié</th>
                        <th>Salarié</th>
                        <th>Montant</th>
                        <th>Règlement</th>
                       
                        <th>Créé par</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($avancementsDepenses as $depense)
                        <tr data-description="{{ $depense->description }}" data-epreuve="{{ $depense->epreuve }}">
                            <td class="dtr-control"></td> <!-- Placeholder pour DataTables -->
                            <td>{{ $depense->code }}</td>
                            <td>{{ $depense->reference ?? '-' }}</td>
                            <td>{{ $depense->date }}</td>
                            <td>{{ $depense->mois_depenses }}</td>
                            <td>{{ $depense->salarie ?? 'N/A' }}</td>
                            <td>{{ $depense->employee ? $depense->employee->nom . ' ' . $depense->employee->prenom : 'N/A' }}
                            </td>
                            <td>{{ number_format($depense->montant, 2, '.', '') }}</td>
                            <td>{{ $depense->reglement_depense }}</td>
                            
                            <td>{{ $depense->creator->username ?? 'N/A' }}</td>
                            <td>
                                <div class="action-buttons d-flex align-items-center">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-icon" title="Modifier"
                                        data-bs-toggle="modal" data-bs-target="#editDepenseModal"
                                        data-id="{{ $depense->id }}">
                                        <i class="bx bx-edit text-primary"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-icon show-details-btn" title="Détails"
                                        data-description="{{ $depense->description ?? '-' }}"
                                        data-epreuve="{{ $depense->epreuve }}">
                                        <i class="bx bx-info-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon print-depense" data-id="{{ $depense->id }}"
                                        title="Imprimer">
                                        <i class="bx bx-printer text-dark"></i>
                                    </button>
                                    @if (Auth::check() && Auth::user()->role->name === 'superadmin')
                                        <form action="{{ route('depenses.destroy', $depense->id) }}" method="POST"
                                            class="delete-form" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon" title="Supprimer">
                                                <i class="bx bx-trash text-danger"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modale pour afficher les détails -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header -->
            <div class="modal-header  text-black">
                <h5 class="modal-title d-flex align-items-center" id="detailsModalLabel">
                    <i class="bx bx-info-circle me-2 fs-4"></i>
                    Détails de l'Avancement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Informations principales -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body">
                                <h6 class="text-primary mb-3">
                                    <i class="bx bx-hash me-1"></i> Identification
                                </h6>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Code</small>
                                    <span class="fw-semibold fs-5" id="modal-code">-</span>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block">Référence</small>
                                    <span class="fw-semibold" id="modal-reference">-</span>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block">Date</small>
                                    <span class="fw-semibold" id="modal-date">-</span>
                                </div>

                                <div>
                                    <small class="text-muted d-block">Mois de création</small>
                                    <span class="fw-semibold" id="modal-mois">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Salarié & Montant -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body">
                                <h6 class="text-primary mb-3">
                                    <i class="bx bx-user me-1"></i> Salarié & Montant
                                </h6>

                                <div class="mb-3">
                                    <small class="text-muted d-block">N° Matricule</small>
                                    <span class="fw-semibold" id="modal-matricule">-</span>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block">Salarié</small>
                                    <span class="fw-semibold" id="modal-salarie">-</span>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block">Montant</small>
                                    <span class="fw-bold text-success fs-4" id="modal-montant">-</span>
                                </div>

                                <div>
                                    <small class="text-muted d-block">Règlement</small>
                                    <span class="badge bg-info text-dark fs-6" id="modal-reglement">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <div class="card border-0">
                            <div class="card-body">
                                <h6 class="text-primary mb-2">
                                    <i class="bx bx-text me-1"></i> Description
                                </h6>
                                <div class="p-3 bg-light rounded-3" id="modal-description" style="min-height: 60px;">
                                    -
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Épreuve -->
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                            <div>
                                <h6 class="mb-1 text-primary">
                                    <i class="bx bx-file me-1"></i> Épreuve
                                </h6>
                                <div id="modal-epreuve" class="text-muted">Non disponible</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Modale Choix Type de Virement -->
<!-- Modale Choix Type de Virement -->
<div class="modal fade" id="virementTypeModal" tabindex="-1" aria-labelledby="virementTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="virementTypeModalLabel">Type de Virement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <!-- NOUVEAU : Champ Référence -->
                <div class="mb-4">
                    <label for="virement_reference" class="form-label"><strong>Référence</strong></label>
                    <input type="text" class="form-control" id="virement_reference"  required>
                    <small class="text-muted">Cette référence sera affichée sur le document (REF : ...)</small>
                </div>

                <p class="mb-4">Veuillez choisir le type de virement pour les avances sélectionnées :</p>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="virement_type" id="virement_normal" value="normal" checked>
                    <label class="form-check-label" for="virement_normal">
                        <strong>Virement Normal</strong><br>
                        <small class="text-muted">Traitement standard (1-2 jours ouvrables)</small>
                    </label>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="virement_type" id="virement_instantane" value="instantane">
                    <label class="form-check-label" for="virement_instantane">
                        <strong>Virement Instantané</strong><br>
                        <small class="text-muted">Exécution immédiate (frais supplémentaires possibles)</small>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="confirm-virement-type" class="btn btn-primary">Générer le PDF</button>
            </div>
        </div>
    </div>
</div>

    <!-- Include Modals -->
    @include('depenses.modal_depenses')

    <script>
        window.depensesIndexUrl = "{{ route('depenses.avancements') }}";
        window.avancementNatureId = "{{ $avancementNatureId ?? '' }}";
        window.vehicleNatureId = "{{ $vehicleNatureId ?? '' }}";
        window.checkReferenceUrl = "{{ route('depenses.reference.check') }}"; 


        const companySettings = @json($companySettings ?? []);

        // Date filter functionality
        $("#date_debut_avancements, #date_fin_avancements").on("change", function() {
            const dateDebut = $("#date_debut_avancements").val();
            const dateFin = $("#date_fin_avancements").val();
            if (dateDebut && dateFin) {
                window.location.href = "{{ route('depenses.avancements') }}?date_debut=" + encodeURIComponent(
                    dateDebut) + "&date_fin=" + encodeURIComponent(dateFin);
            }
        });

        $("#reset-dates-avancements").on("click", function() {
            $("#date_debut_avancements").val("");
            $("#date_fin_avancements").val("");
            window.location.href = "{{ route('depenses.avancements') }}";
        });

        // Print button functionality
        $(document).on("click", ".print-depense", function() {
            const $button = $(this);
            let $row = $button.closest("tr");
            const tableId = $row.closest("table").attr("id");
            const isAvancement = tableId === "avancementsTable";
            const table = $(`#${tableId}`).DataTable();
            const depenseId = $button.data("id");

            $.ajax({
                url: `/depenses/${depenseId}`,
                method: "GET",
                success: function(response) {
                    const depense = response.depense;
                    if (isAvancement && depense.reglement_depense === 'Virement') {
                        const dateDebut = depense.date;
                        const dateFin = depense.date;
                        window.location.href = "{{ route('depenses.avances.pdf') }}?date_debut=" +
                            encodeURIComponent(dateDebut) + "&date_fin=" + encodeURIComponent(dateFin);
                    } else {
                        // Existing individual print logic (unchanged for now)
                        let rowData = table.row($row).data();
                        if (!rowData) {
                            let offset = 2; // +2 for avancements due to extra columns
                            rowData = {
                                code: $row.find("td:eq(1)").text() || "-",
                                date: $row.find("td:eq(2)").text() || "-",
                                mois_depenses: $row.find("td:eq(3)").text() || "-",
                                salarie: $row.find("td:eq(4)").text() || "-",
                                employee: $row.find("td:eq(5)").text() || "-",
                                montant: $row.find(`td:eq(${4 + offset})`).text().replace(
                                    /[^\d.,]/g, "") || "0",
                                reglement_depense: $row.find(`td:eq(${5 + offset})`).text() || "-",
                                creator: $row.find(`td:eq(${6 + offset})`).text() || "-",
                                description: $row.data("description") || "-",
                                epreuve: $row.data("epreuve") || ""
                            };
                        }

                        const data = {
                            code: rowData.code,
                            date: rowData.date,
                            mois_depenses: rowData.mois_depenses,
                            salarie: rowData.salarie,
                            nomPrenom: rowData.employee,
                            montant: rowData.montant,
                            reglement: rowData.reglement_depense,
                            creator: rowData.creator,
                            description: rowData.description,
                            hasEpreuve: rowData.epreuve && rowData.epreuve.includes("epreuve"),
                        };

                        const logoUrl = companySettings && companySettings.logo ?
                            `${window.location.origin}/storage/${companySettings.logo}` :
                            `${window.location.origin}/assets/img/favicon/anassi2.jpg`;

                        const iframe = document.createElement("iframe");
                        iframe.style.display = "none";
                        document.body.appendChild(iframe);
                        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

                        iframeDoc.open();
                        iframeDoc.write(`
                            <html>
                            <head>
                                <title>Impression - Détails de la Dépense</title>
                                <style>
                                    body { font-family: Arial, sans-serif; margin: 10mm; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                                    .print-container { max-width: 800px; margin: 0 auto; }
                                    .header { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
                                    .header img { max-width: 100px; margin-right: 20px; }
                                    .header h1 { font-size: 24px; margin: 0; }
                                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                                    th { background-color: #f2f2f2; font-weight: bold; }
                                    .print-footer { font-size: 12px; line-height: 1.5; text-align: center; position: fixed; bottom: 10mm; width: 100%; }
                                    .print-footer p { margin: 2px 0; }
                                    @page { size: A4; margin: 10mm; }
                                    @media print { .no-print { display: none; } .print-footer { position: fixed; bottom: 0; width: 100%; } }
                                </style>
                            </head>
                            <body>
                                <div class="print-container">
                                    <div class="header">
                                        <img src="${logoUrl}" alt="Logo" />
                                        <div>
                                            <h1>Détails de la Dépense</h1>
                                            <p>Date d'impression: ${new Date().toLocaleDateString("fr-FR")}</p>
                                        </div>
                                    </div>
                                    <table>
                                        <tr><th>Code</th><td>${data.code}</td></tr>
                                        <tr><th>Date</th><td>${data.date}</td></tr>
                                        <tr><th>Mois Création</th><td>${data.mois_depenses}</td></tr>
                                        <tr><th>N_mat_salarié</th><td>${data.salarie}</td></tr>
                                        <tr><th>Salarié</th><td>${data.nomPrenom}</td></tr>
                                        <tr><th>Description</th><td>${data.description}</td></tr>
                                        <tr><th>Montant</th><td>${data.montant}</td></tr>
                                        <tr><th>Règlement</th><td>${data.reglement}</td></tr>
                                        <tr><th>Créé par</th><td>${data.creator}</td></tr>
                                        <tr><th>Épreuve</th><td>${data.hasEpreuve ? "Disponible" : "Non disponible"}</td></tr>
                                    </table>
                                    <div class="print-footer">
                                        <p>Siège social: ${companySettings.address || "Non spécifié"} | Capital: ${companySettings.capital ? companySettings.capital.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD" : "Non spécifié"} | Tél: ${companySettings.phone_number || "Non spécifié"}</p>
                                        <p>R.C.: ${companySettings.commercial_register || "Non spécifié"} | CNSS: ${companySettings.cnss_number || "Non spécifié"} | IF: ${companySettings.tax_id || "Non spécifié"} | TP: ${companySettings.tax_id || "Non spécifié"} | ICE: ${companySettings.patent_number || "Non spécifié"}</p>
                                        <p>C.B.: ${companySettings.account_number || "Non spécifié"}, ${companySettings.bank_name || "Non spécifié"} | Email: ${companySettings.email || "Non spécifié"}</p>
                                    </div>
                                    <button class="no-print" onclick="window.print()">Imprimer</button>
                                </div>
                            </body>
                            </html>
                        `);
                        iframeDoc.close();

                        iframe.onload = function() {
                            try {
                                iframe.contentWindow.focus();
                                iframe.contentWindow.print();
                            } catch (e) {
                                console.error("Erreur lors de l'impression individuelle:", e);
                                Swal.fire({
                                    icon: "error",
                                    title: "Erreur",
                                    text: "Impossible de lancer l'impression.",
                                    position: "center",
                                    confirmButtonText: "OK",
                                });
                            }
                        };

                        iframe.contentWindow.onafterprint = function() {
                            document.body.removeChild(iframe);
                        };

                        setTimeout(() => {
                            if (iframe.parentNode) {
                                document.body.removeChild(iframe);
                            }
                        }, 5000);
                    }
                },
                // error: function () {
                //     Swal.fire({
                //         icon: "error",
                //         title: "Erreur",
                //         text: "Impossible de récupérer les données de la dépense.",
                //         position: "center",
                //         confirmButtonText: "OK",
                //     });
                // }
            });
        });

        // Apply styles to active tab
        function setActiveTabStyles() {
            document.querySelectorAll(".nav-tabs .nav-link").forEach((tab) => {
                tab.classList.remove("bg-success", "text-white");
            });
            const activeTab = document.querySelector(".nav-tabs .nav-link.active");
            if (activeTab) {
                activeTab.classList.add("bg-success", "text-white");
            }
        }
        setActiveTabStyles();
        document.querySelectorAll(".nav-tabs .nav-link").forEach((tab) => {
            tab.addEventListener("click", setActiveTabStyles);
        });
    </script>

    
    <script src="{{ asset('assets/js/depenses.js') }}"></script>
    <!-- Select2 CSS + thème Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection
