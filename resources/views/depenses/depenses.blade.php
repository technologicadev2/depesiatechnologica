@extends('master_page.app')

@section('title')
    Dépenses
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Dépenses</h4>

    <style>
        /* Styles de base inchangés */
        .dt-controls, .dt-filter {
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

        /* Ajustements pour mobile */
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

            .nav-tabs {
                flex-direction: column;
                gap: 4px;
            }

            .nav-tabs .nav-link {
                width: 100%;
                text-align: center;
                font-size: 0.85rem;
                padding: 8px;
                border-radius: 4px;
            }

            .card-datatable {
                overflow-x: auto;
            }
        }

        /* Styles existants inchangés */
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

        /* Supprimer les styles pour details-control */
        .details-control {
            display: none; /* Masquer la colonne details-control */
        }
        .actions-row {
    flex-wrap: nowrap !important; /* Empêcher le retour à la ligne */
    white-space: nowrap; /* Conserver tout sur une ligne */
    align-items: center; /* Aligner verticalement les éléments */
}

.actions-row a,
.actions-row button {
    margin: 0 2px; /* Réduire l'espace entre les icônes */
    padding: 0; /* Supprimer le padding par défaut */
    font-size: 1.25rem; /* Uniformiser la taille des icônes */
    background: none !important; /* Pas de fond */
    border: none; /* Pas de bordure */
    color: #6c757d; /* Couleur gris par défaut des icônes Bootstrap */
}

.actions-row a:hover,
.actions-row button:hover {
    color: #007bff; /* Couleur au survol */
}

.actions-row .delete-btn {
    background: none !important; /* S'assurer que le bouton delete n'a pas de fond */
    border: none;
    padding: 0;
}

.actions-row .delete-btn i {
    color: #dc3545; /* Rouge pour l'icône de suppression */
}

.actions-row .delete-form {
    display: inline; /* Garder le formulaire inline */
    margin: 0;
}

        /* Style pour le bouton Show */.show-details-btn {
    background: none !important; /* Supprimer le fond */
    border: none; /* Supprimer la bordure */
    padding: 0; /* Supprimer le padding par défaut */
    cursor: pointer; /* Indiquer que c'est clickable */
    color: inherit; /* Utiliser la couleur par défaut des icônes (généralement gris) */
    font-size: 1.25rem; /* Ajuster la taille pour correspondre à bx-edit (modifiable selon vos besoins) */
}

.show-details-btn:hover {
    color: #007bff; /* Couleur au survol pour indiquer l'interaction, similaire à un lien */
}

.show-details-btn i {
    vertical-align: middle; /* Aligner l'icône verticalement */
}
    </style>

    <!-- SweetAlert2 Notifications -->
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

    <div class="row mb-4">
        <ul class="nav nav-tabs border border-1 border-light rounded bg-white" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane"
                    type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Dépenses</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane"
                    type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Avancements des
                    salaires</button>
            </li>
            <li class="nav-item" role="presentation">
    <button class="nav-link" id="vehicle-tab" data-bs-toggle="tab" data-bs-target="#vehicle-tab-pane"
        type="button" role="tab" aria-controls="vehicle-tab-pane" aria-selected="false">Dépenses de véhicule</button>
</li>
        </ul>
        <div class="tab-content border border-1 border-light border-top-0 rounded-bottom p-3 bg-white" id="myTabContent">
            <!-- Varié Tab -->
            <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab"
                tabindex="0">
                <div class="card">
                    <div class="card-header">
                        <div class="row ms-2 me-3 dt-filter-container">
                            <div
                                class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                                <div
                                    class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3">
                                    <!-- Les boutons DataTables seront injectés ici -->
                                    <div class="total-display" id="total-display-varie">Total:
                                        {{ number_format($totalVarie, 2) }}</div>
                                </div>
                            </div>
                            <div
                                class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter">
                                <!-- Filtres de date supprimés comme dans votre code -->
                            </div>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive">
                        <table class="datatables-basic table table-striped table-hover border-top" id="varieTable">
                            <thead>
                                <tr>
                          
                                    <th></th> <!-- Colonne pour responsive -->
                                    <th>Code</th>
                                    <th>Date</th>
                                    <th>Mois_creation</th>
                                    <th>Montant</th>
                                    <th>Règlement</th>
                                    <th>Créé par</th>
                                    <th>Actions</th>
                                </tr>
                                {{-- <tr>
                             
                                    <th></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Code"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Date"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Mois Création"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Montant"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Règlement"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Créé par"></th>
                                    <th></th>
                                </tr> --}}
                            </thead>
                            <tbody>
                                @foreach ($varieDepenses as $depense)
                                    <tr data-description="{{ $depense->description }}" data-epreuve="{{ $depense->epreuve }}">
                                        <td class="dtr-control"></td>
                                        <td>{{ $depense->code }}</td>
                                        <td>{{ $depense->date }}</td>
                                        <td>{{ $depense->mois_depenses }}</td>
                                        <td>{{ number_format($depense->montant, 2) }}</td>
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
                                                    data-description="{{ $depense->description ?? '-' }}" data-epreuve="{{ $depense->epreuve }}">
                                                    <i class="bx bx-info-circle"></i>
                                                </button>
                                                <button class="btn btn-sm btn-icon print-depense"
                                                    data-id="{{ $depense->id }}" title="Imprimer">
                                                    <i class="bx bx-printer text-dark"></i>
                                                </button>
                                                @if (Auth::check() && Auth::user()->role->name === 'superadmin')
                                                    <form action="{{ route('depenses.destroy', $depense->id) }}"
                                                        method="POST" class="delete-form" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-icon"
                                                            title="Supprimer">
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
            </div>
            <!-- Avancements Tab -->
            <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                <div class="card">
                    <div class="card-header">
                        <div class="row ms-2 me-3 dt-filter-container">
                            <div
                                class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                                <div
                                    class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3">
                                    <div class="total-display" id="total-display-avancements">Total:
                                        {{ number_format($totalAvancements, 2) }}</div>
                                </div>
                            </div>
                            <div
                                class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter">
                                <!-- Filtres de date supprimés -->
                            </div>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive">
                        
                        <table class="datatables-basic table table-striped table-hover border-top" id="avancementsTable">
                            <thead>
                                <tr>
                                    <th></th> <!-- Colonne pour responsive -->
                                    <th>Code</th>
                                    <th>Date</th>
                                    <th>Mois_creation</th>
                                    <th>n_mt_salarié</th>
                                    <th>Salarié</th>
                                    <th>Montant</th>
                                    <th>Règlement</th>
                                    <th>Créé par</th>
                                    <th>Actions</th>
                                </tr>
                                {{-- <tr>
                                    <th></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Code"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Date"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Mois Création"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Matricule"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Salarié"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Montant"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Règlement"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Créé par"></th>
                                    <th></th>
                                </tr> --}}
                            </thead>
                          <tbody>
        @foreach ($varieDepenses as $depense)
            <tr>
                <td>{{ $depense->code }}</td>
                <td>{{ $depense->date }}</td>
                <td>{{ $depense->nature_depense }}</td>
                <td>{{ $depense->description }}</td>
                <td>{{ number_format($depense->montant, 2) }}</td>
                <td>{{ $depense->typeReglement->designation ?? 'N/A' }}</td>
                <td>{{ $depense->creator->username ?? 'N/A' }}</td>
                <td>
                    @if ($depense->epreuve)
                        <a href="{{ asset($depense->epreuve) }}" target="_blank">Voir</a>
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    <button class="btn btn-sm btn-primary edit-depense" data-id="{{ $depense->id }}">Modifier</button>
                    <form action="{{ route('depenses.destroy', $depense->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Voulez-vous vraiment supprimer cette dépense ?')">Supprimer</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="vehicle-tab-pane" role="tabpanel" aria-labelledby="vehicle-tab" tabindex="0">
    <div class="card">
        <div class="card-header">
            <div class="row ms-2 me-3 dt-filter-container">
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                    <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3">
                        <div class="total-display" id="total-display-vehicle">Total: {{ number_format($totalVehicle, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter">
                    <!-- Filtres de date si nécessaire -->
                </div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-basic table table-striped table-hover border-top" id="vehicleTable">
                <thead>
                    <tr>
                        <th></th> <!-- Colonne pour responsive -->
                        <th>Code</th>
                        <th>Date</th>
                        <th>Véhicule</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Règlement</th>
                        <th>Créé par</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Les données seront chargées via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>
        </div>
    </div>
<div class="modal fade" id="addVehicleDepenseModal" tabindex="-1" aria-labelledby="addVehicleDepenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVehicleDepenseModalLabel">Ajouter une Dépense de Véhicule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addVehicleDepenseForm" action="{{ route('depenses.depenses') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="vehicle_nature_id" name="nature_id" value="{{ $vehicleNatureId }}">
                    <div class="mb-3">
                        <label for="vehicle_id" class="form-label">Véhicule</label>
                        <select class="form-select @error('vehicle_id') is-invalid @enderror" id="vehicle_id" name="vehicle_id" required>
                            <option value="">Sélectionner un véhicule</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->matricule }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="vehicle_type" class="form-label">Type de Dépense</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="vehicle_type" name="type" required>
                            <option value="gasoil">Gasoil</option>
                            <option value="vidange">Vidange</option>
                            <option value="visite_technique">Visite Technique</option>
                            <option value="vignette">Vignette</option>
                            <option value="reparation">Réparation</option>
                            <option value="autre">Autre</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="vehicle_montant" class="form-label">Montant</label>
                        <input type="number" step="0.01" class="form-control @error('montant') is-invalid @enderror" id="vehicle_montant" name="montant" value="{{ old('montant') }}" required>
                        @error('montant')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="vehicle_date" class="form-label">Date</label>
                        <input type="date" class="form-control @error('date') is-invalid @enderror" id="vehicle_date" name="date" value="{{ old('date') }}" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="vehicle_kilometrage" class="form-label">Kilométrage</label>
                        <input type="number" class="form-control @error('kilometrage') is-invalid @enderror" id="vehicle_kilometrage" name="kilometrage" value="{{ old('kilometrage') }}">
                        @error('kilometrage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="vehicle_reglement_id" class="form-label">Règlement</label>
                        <select class="form-select @error('reglement_id') is-invalid @enderror" id="vehicle_reglement_id" name="reglement_id" required>
                            <option value="">Sélectionner</option>
                            @foreach ($typeReglements as $typeReglement)
                                <option value="{{ $typeReglement->id }}" {{ old('reglement_id') == $typeReglement->id ? 'selected' : '' }}>{{ $typeReglement->designation }}</option>
                            @endforeach
                        </select>
                        @error('reglement_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="vehicle_description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="vehicle_description" name="description" required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="vehicle_epreuve" class="form-label">Épreuve (Image, PDF, Excel, Word)</label>
                        <input type="file" class="form-control @error('epreuve') is-invalid @enderror" id="vehicle_epreuve" name="epreuve" accept=".jpg,.jpeg,.png,.pdf,.xlsx,.xls,.doc,.docx">
                        @error('epreuve')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <!-- Modale pour afficher les détails -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Détails de la dépense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Description :</strong> <span id="modal-description"></span></p>
                    <p><strong>Épreuve :</strong> <span id="modal-epreuve"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals -->
    @include('depenses.modal_depenses')

    <script>
        window.depensesIndexUrl = "{{ route('depenses.index') }}";
        window.avancementNatureId = "{{ $avancementNatureId ?? '' }}";
    </script>
    <script>
        const companySettings = @json($companySettings ?? []);
        console.log('Company Settings:', companySettings);
    </script>
<script>
    // Initialisation des variables globales
    window.avancementNatureId = {{ $avancementNatureId }};
    window.vehicleNatureId = {{ $vehicleNatureId }};
</script>
    <script src="{{ asset('assets/js/depenses.js') }}"></script>
@endsection