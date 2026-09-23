
@extends('master_page.app')

@section('title')
    Dépenses Variées
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Dépenses Variées</h4>

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
                        <div class="total-display" id="total-display-varie">Total: {{ number_format($totalVarie, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter">
                    <!-- Filtres de date si nécessaire -->
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
        window.depensesIndexUrl = "{{ route('depenses.varie') }}";
        window.avancementNatureId = "{{ $avancementNatureId ?? '' }}";
        window.vehicleNatureId = "{{ $vehicleNatureId ?? '' }}";
        const companySettings = @json($companySettings ?? []);
    </script>
    <script src="{{ asset('assets/js/depenses.js') }}"></script>
@endsection
