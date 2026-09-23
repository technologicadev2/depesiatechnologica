@extends('master_page.app')

@section('title')
    Pointage administratif
@endsection

@section('styles')
    <style>
        /* Conteneur principal */
        
        .datatables-basic {
            width: 100% !important;
            overflow-x: auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 15px;
        }
        .datatables-basic table {
            min-width: 600px; /* Réduit pour une meilleure adaptation mobile */
            border-collapse: separate;
            border-spacing: 0;
        }
        .datatables-basic th, .datatables-basic td {
            padding: 12px;
            font-size: 14px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
            color: #333;
        }
        .datatables-basic th {
            /* background-color: #e3f2fd; */
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            color: #1565c0;
        }
        .datatables-basic tbody tr {
            transition: background-color 0.2s ease;
        }
        .datatables-basic tbody tr:hover {
            background-color: #f5f7fa;
        }

        /* Photo des employés */
        .datatables-basic .employee-photo {
            width: 30px !important;
            height: 30px !important;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid #e0e0e0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-right: 8px;
        }

        /* Case à cocher */
        .datatables-basic .presence-checkbox {
            transform: scale(1.3);
            cursor: pointer;
            accent-color: #1976d2;
        }

        /* Menu déroulant des projets */
        .projet-select {
            padding: 6px;
            font-size: 13px;
            border-radius: 5px;
            border: 1px solid #ced4da;
            width: 140px;
            background-color: #fff;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .projet-select:focus {
            border-color: #1976d2;
            box-shadow: 0 0 5px rgba(25, 118, 210, 0.3);
            outline: none;
        }

        /* Barre d'outils */
        .dt-action-buttons {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
        }
        .dt-action-buttons .btn {
            border-radius: 5px;
            padding: 8px 14px;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        .dt-action-buttons .btn.btn-label-primary {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }
        .dt-action-buttons .btn.btn-primary {
            background-color: #1976d2;
            border-color: #1976d2;
        }
        .dt-action-buttons .btn.btn-outline-secondary {
            border-color: #ced4da;
            color: #333;
        }
        .dt-action-buttons .date-picker-container {
            margin-left: 10px;
        }
        #pointageDate {
            padding: 6px;
            font-size: 13px;
            border-radius: 5px;
            border: 1px solid #ced4da;
            width: 140px;
            background-color: #fff;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        #pointageDate:focus {
            border-color: #1976d2;
            box-shadow: 0 0 5px rgba(25, 118, 210, 0.3);
            outline: none;
        }

        /* Media Queries pour responsivité */
        @media (max-width: 768px) {
            .datatables-basic .employee-photo {
                width: 26px !important;
                height: 26px !important;
            }
            .projet-select, #pointageDate {
                width: 120px;
                font-size: 12px;
                padding: 5px;
            }
            .dt-action-buttons {
                flex-direction: column;
                align-items: flex-start;
            }
            .dt-action-buttons .date-picker-container {
                margin-left: 0;
                margin-top: 10px;
            }
            .dt-action-buttons .btn {
                width: 100%;
                font-size: 12px;
                padding: 7px 12px;
            }
            .datatables-basic th, .datatables-basic td {
                font-size: 12px;
                padding: 8px;
            }
        }
        @media (max-width: 576px) {
            .projet-select, #pointageDate {
                width: 100%;
                font-size: 11px;
            }
            .datatables-basic .employee-photo {
                width: 24px !important;
                height: 24px !important;
            }
            .datatables-basic th, .datatables-basic td {
                font-size: 11px;
                padding: 6px;
            }
            .dt-action-buttons .btn {
                font-size: 11px;
                padding: 6px 10px;
            }
        }
        #ferieIndicator {
    font-size: 13px;
    margin-left: 8px;
    min-height: 20px; /* pour qu'il soit visible même si caché */
}
    </style>
@endsection

@section('content')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script> <!-- Pour le français -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Pointage administratif</h4>

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

    <div class="card">
        <div class="card-header">
            <!-- Suppression du bouton "Enregistrer ma présence" et du champ de date -->
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-basic table table-striped table-hover border-top" id="salariesTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Photo</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Matricule Entreprise</th>
                        <th>Projet</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($salaries as $salarie)
                        <tr>
                            <td>
                                <input type="checkbox" 
                                       class="presence-checkbox" 
                                       data-salarie-id="{{ $salarie->id }}"
                                       value="{{ $salarie->id }}"
                                       {{ isset($presences[$salarie->id]) && $presences[$salarie->id] == 1 ? 'checked' : '' }}>
                            </td>
                            <td>
                                <img src="{{ $salarie->photo_url }}" 
                                     alt="Photo de {{ ($salarie->nom ?? '-') . ' ' . ($salarie->prenom ?? '-') }}" 
                                     class="employee-photo">
                            </td>
                            <td>{{ $salarie->nom ?? '-' }}</td>
                            <td>{{ $salarie->prenom ?? '-' }}</td>
                            <td>{{ $salarie->n_matricule_entreprise ?? '-' }}</td>
                            <td>
                                <select class="projet-select" data-salarie-id="{{ $salarie->id }}">
                                    <option value="">Sans projet</option>
                                    @foreach ($projets as $projet)
                                        <option value="{{ $projet->id }}" 
                                                {{ $salarie->project_name == $projet->intitule ? 'selected' : '' }}>
                                            {{ $projet->intitule }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: rgba(0, 0, 0, 0.6); border: none; box-shadow: none;">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="text-white mt-2">Chargement...</p>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Liste des jours fériés passée depuis le contrôleur
    const joursFeries = @json($joursFeries ?? []);
    console.log('Jours fériés chargés :', joursFeries);
</script>

    <script>
        const companySettings = @json($companySettings ?? []);
        console.log('Company Settings:', companySettings);
    </script>
    <script src="{{ asset('assets/js/pointage_ad.js') }}"></script>
@endsection