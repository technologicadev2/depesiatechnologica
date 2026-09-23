@extends('master_page.app')

@section('title')
    Gestion des Absences
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Absences des Employés</h4>

    <style>
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
        .datatables-basic td .btn-icon {
            display: inline-block !important;
            vertical-align: middle !important;
            margin: 0 1px !important;
        }
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .action-buttons .btn-icon {
            margin: 0;
        }
        .datatables-basic thead th {
            background-image: none !important;
            cursor: default !important;
        }
        /* Styles pour les badges */
        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .badge-accepted {
            background-color: #28a745;
            color: #fff;
        }
        .badge-rejected {
            background-color: #dc3545;
            color: #fff;
        }

        .btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
    </style>

    <div class="row mb-4">
        <div class="card">
            <div class="card-header">
                <div class="row ms-2 me-3 dt-filter-container">
                    <div class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                        <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3">
                            <!-- DataTables buttons will be injected here -->
                        </div>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter">
                        <!-- No date filters -->
                    </div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-basic table table-striped table-hover border-top" id="absencesTable">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Matricule</th>
                            <th>Date Début</th>
                            <th>Date Fin</th>
                            <th>Nombre de Jours</th>
                            <th>État de Justification</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($absences as $absence)
                            <tr>
                                <td>{{ $absence->salarie->nom ?? 'N/A' }}</td>
                                <td>{{ $absence->salarie->prenom ?? 'N/A' }}</td>
                                <td>{{ $absence->salarie->n_matricule_entreprise ?? 'N/A' }}</td>
                                <td>{{ $absence->date_debut }}</td>
                                <td>
                                    @if (is_null($absence->date_fin))
                                      Non repris(e)
                                    @else
                                        {{ $absence->date_fin }}
                                    @endif
                                </td>
                                <td>
                                    @if (is_null($absence->nbre_jours))
                                        Non repris(e)                         
                                       @else
                                        {{ $absence->nbre_jours }}
                                    @endif
                                </td>
                               <td>
    @if ($absence->justification == 'c')
        <span class="badge bg-warning text-dark">Congé</span>
    @elseif ($absence->justification == 1)
        <span class="badge badge-accepted">Justifié</span>
    @else
        <span class="badge badge-rejected">Non justifié</span>
    @endif
</td>
                                <td>
                                    <div class="action-buttons d-flex align-items-center">
                                      
    <button class="btn btn-sm btn-icon justify-absence" 
        data-id="{{ $absence->id }}" 
        title="Justifier"
        @if($absence->date_fin === null || $absence->nbre_jours === null) disabled @endif>
    <i class="bx bx-file text-dark"></i>
</button>

  <button class="btn btn-sm btn-icon print-absence" data-id="{{ $absence->id }}" title="Imprimer">
                                            <i class="bx bx-printer text-dark"></i>
                                            </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal pour justification -->
   <div class="modal fade" id="justificationModal" tabindex="-1" aria-labelledby="justificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="justificationModalLabel">Justifier l'Absence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="justificationForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="absence_id" name="absence_id">
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="piece_jointe" class="form-label">Pièce Jointe (PDF, JPG, PNG)</label>
                        <input type="file" class="form-control" id="piece_jointe" name="piece_jointe" accept=".pdf,.jpg,.png">
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
<script>
    const companySettings = @json($companySettings ?? []);
    console.log('Company Settings:', companySettings); // Pour déboguer
</script>
    <!-- Include JavaScript dependencies -->
    <script src="{{ asset('assets/js/absences.js') }}"></script>
@endsection