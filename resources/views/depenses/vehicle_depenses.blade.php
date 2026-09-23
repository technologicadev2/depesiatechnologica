@extends('master_page.app')

@section('title')
    Dépenses de Véhicule
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Dépenses de Véhicule</h4>

    <style>
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
                         <th>Règlement</th>
                        <th>Type</th>
                       
                        <th>Montant</th>
                        <th>Kilométrage</th>
                        <th>Heures</th>
                        <th>État Vidange</th>
                        <th>État Plaquettes</th>
                        <th>État Pneus</th>
                        <th>État Courroie</th>
                        <th>État Amortisseur</th>
                        <th>Créé par</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vehicleDepenses as $depense)
                     
                        <tr data-description="{{ $depense->description }}" data-epreuve="{{ $depense->epreuve }}">
                            <td class="dtr-control"></td>
                            <td>{{ $depense->code }}</td>
                            <td>{{ $depense->date }}</td>
                            <td>{{ $depense->vehicle ? $depense->vehicle->matricule : 'N/A' }}</td>
                             <td>{{ $depense->typeReglement->designation ?? 'N/A' }}</td>
                            <td>{{ $depense->type ?? 'N/A' }}</td>
                            <td>{{ number_format($depense->montant, 2) }}</td>
                            <td>{{ $depense->kilometrage ? number_format($depense->kilometrage, 0) . ' km' : 'N/A' }}</td>
                            <td>{{ $depense->heures ? number_format($depense->heures, 0) . ' h' : 'N/A' }}</td>  
                            
                           
                                  <td>
                        @if ($depense->etat_vidange !== null)
                            @php
                                $isEngin = $depense->vehicle && $depense->vehicle->type === 'engins';
                                $seuilVidange = $isEngin ? 250 : 9550;   
                            @endphp
                            @if ($depense->etat_vidange >= $seuilVidange)
                                <span class="text-danger fw-bold">
                                    <i class="bx bx-droplet me-1"></i>
                                    {{ $depense->etat_vidange }}
                                </span>
                            @else
                                {{ $depense->etat_vidange }}
                            @endif 
                        @else
                            N/A
                        @endif
                    </td>
                                            <td>
                        @if ($depense->etat_plaquettes !== null)
                            @php
                                $isEngin = $depense->vehicle && $depense->vehicle->type === 'engins';
                                $seuilPlaquettes = $isEngin ? 2000 : 40000;
                            @endphp
                            @if ($depense->etat_plaquettes >= $seuilPlaquettes)
                                <span class="text-danger fw-bold">
                                    <i class="bx bx-stop-circle me-1"></i>
                                    {{ $depense->etat_plaquettes }}
                                </span>
                            @else
                                {{ $depense->etat_plaquettes }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>

                                        <td>
                        @if ($depense->etat_pneus !== null)
                            @php
                                $isEngin = $depense->vehicle && $depense->vehicle->type === 'engins';
                                $seuilPneus = $isEngin ? 4000 : 80000;
                            @endphp
                            @if ($depense->etat_pneus >= $seuilPneus)
                                <span class="text-danger fw-bold">
                                    <i class="bx bx-radio-circle me-1"></i>
                                    {{ $depense->etat_pneus }}
                                </span>
                            @else
                                {{ $depense->etat_pneus }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                                        <td>
                        @if ($depense->etat_courroie !== null)
                            @php
                                $isEngin = $depense->vehicle && $depense->vehicle->type === 'engins';
                                $seuilCourroie = $isEngin ? 5000 : 100000;
                            @endphp
                            @if ($depense->etat_courroie >= $seuilCourroie)
                                <span class="text-danger fw-bold">
                                    <i class="bx bx-link me-1"></i>
                                    {{ $depense->etat_courroie }}
                                </span>
                            @else
                                {{ $depense->etat_courroie }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                                    <td>
                        @if ($depense->etat_amortisseur !== null)
                            @php
                                $isEngin = $depense->vehicle && $depense->vehicle->type === 'engins';
                                $seuilAmortisseur = $isEngin ? 4000 : 80000;
                            @endphp
                            @if ($depense->etat_amortisseur >= $seuilAmortisseur)
                                <span class="text-danger fw-bold">
                                    <i class="bx bx-transfer-alt me-1"></i>
                                    {{ $depense->etat_amortisseur }}
                                </span>
                            @else
                                {{ $depense->etat_amortisseur }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>  
                    <td>{{ $depense->creator->username ?? 'N/A' }}</td>
                                
                            <td>
                                <div class="action-buttons d-flex align-items-center">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-icon edit-vehicle-btn" title="Modifier"
                                        data-bs-toggle="modal" data-bs-target="#editVehicleDepenseModal"
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

    <!-- Modale pour ajouter une dépense de véhicule -->
    <div class="modal fade" id="addVehicleDepenseModal" tabindex="-1" aria-labelledby="addVehicleDepenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVehicleDepenseModalLabel">Ajouter une Dépense de Véhicule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addVehicleDepenseForm" action="{{ route('depenses.store') }}" method="POST" enctype="multipart/form-data">
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
                                <option value="Pneus ">Pneus</option>
                                <option value="Courroie de distribution">Courroie de distribution</option>
                                <option value="Amortisseurs ">Amortisseurs </option>
                                <option value="Plaquettes de frein">Plaquettes de frein</option>
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
                                        {{-- Kilométrage OU Heures selon le type de véhicule --}}
                    <div class="mb-3" id="kilometrage_field">
                        <label for="vehicle_kilometrage" class="form-label">Kilométrage</label>
                        <input type="number" class="form-control @error('kilometrage') is-invalid @enderror"
                            id="vehicle_kilometrage" name="kilometrage" value="{{ old('kilometrage') }}">
                        @error('kilometrage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="heures_field" style="display:none;">
                        <label for="vehicle_heures" class="form-label">Heures de fonctionnement</label>
                        <input type="number" class="form-control @error('heures') is-invalid @enderror"
                            id="vehicle_heures" name="heures" value="{{ old('heures') }}">
                        @error('heures')
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
        window.depensesIndexUrl = "{{ route('depenses.vehicle') }}";
        window.avancementNatureId = "{{ $avancementNatureId ?? '' }}";
        window.vehicleNatureId = "{{ $vehicleNatureId ?? '' }}";
        const companySettings = @json($companySettings ?? []);
  // ← AJOUTER : map id => type pour détecter les engins
    window.vehiclesData = @json(
        $vehicles->map(fn($v) => ['id' => $v->id, 'type' => $v->type ?? ''])
    );          

    </script>

    
    <script src="{{ asset('assets/js/depenses.js') }}"></script>
@endsection