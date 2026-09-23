@extends('master_page.app')

@section('title')
    Mes Ordres de Mission
@endsection

@section('content')
@section('styles')
@section('styles')
<style>
    .dt-header-controls .btn {
        font-size: 10px;
        padding: 6px 12px;
    }
    .dt-header-controls .dataTables_filter input {
        height: 36px;
        font-size: 14px;
    }
    .datatables-basic .dataTable {
        width: 100% !important;
    }
    .dt-buttons {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    .dt-buttons .btn {
        margin-right: 10px;
    }
    .dataTables_wrapper .dataTables_filter {
        display: flex;
        align-items: center;
    }
    .dataTables_wrapper .dataTables_filter input {
        margin-left: 10px;
        max-width: 200px;
    }
    .dt-header-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .dataTables_scrollBody {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
}
</style>
@endsection
@endsection
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Avant -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<!-- Après -->
<script src="{{ asset('assets/js/vendor/html2pdf.bundle.min.js') }}"></script>

    <h4 class="fw-bold py-3 mb-4">Mes Ordres de Mission</h4>

    <div class="row mb-4">
        <div class="card">
            <div class="card-header">
                <div class="row mx-2">
                    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="dt-action-buttons text-start text-md-end">
                            <!-- Boutons DataTable injectés ici par le JS -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-datatable table-responsive">
                <table class="datatables-basic table table-striped table-hover border-top" 
                       id="ordermissionsalTable" 
                       style="width:100%">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Code Mission</th>
                            <th>Gérant</th>
                            <th>Participants</th>
                            <th>Emplacement</th>
                            <th>Date Départ</th>
                            <th>Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ordermissionsal as $om)
                            <tr>

                            <td></td> 
                                <td><strong>{{ $om->code ?? '-' }}</strong></td>
                                <td>{{ $om->gerant_name ?? '-' }}</td>
                                <td>
                                    <small class="text-muted">{{ $om->salaries_names ?? 'Aucun' }}</small>
                                </td>
                                <td>{{ $om->emplacement ?? '-' }}</td>
                                <td>{{ $om->date_depart ? \Carbon\Carbon::parse($om->date_depart)->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @if($om->ordre == 1)
                                        <span class="badge bg-success">Validé</span>
                                    @else
                                        <span class="badge bg-secondary">En attente</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="action-buttons d-flex align-items-center justify-content-center gap-1">
                                        <!-- Voir la fiche -->
                                        <button class="btn btn-sm btn-icon show-fiche" 
                                                data-id="{{ $om->id }}" 
                                                title="Voir la fiche">
                                            <i class="bx bx-file text-info"></i>
                                        </button>
                                        
                                        <!-- Modifier -->
                                        <button class="btn btn-sm btn-icon edit-mission" 
                                                data-id="{{ $om->id }}" 
                                                title="Modifier horaires & frais">
                                            <i class="bx bx-edit-alt text-warning"></i>
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

    <!-- Modal Fiche Officielle -->
    <div class="modal fade" id="ficheModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fiche Ordre de Mission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="fiche-content"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
<button type="button" class="btn btn-primary" id="printFicheBtn">Imprimer la fiche</button>                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modification -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'Ordre de Mission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="edit_id">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Heure de Départ</label>
                                <input type="time" class="form-control" id="heure_depart" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Heure de Retour</label>
                                <input type="time" class="form-control" id="heure_retour">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Frais de Mission (DH)</label>
                                <input type="number" step="0.01" class="form-control" id="frais" placeholder="0.00">
                            </div>

                            <div class="col-12 col-md-6">
                            <label class="form-label">Date de Retour</label>
                            <input type="date" class="form-control" id="date_retour">
                        </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="btn-save-edit">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = "{{ url('ordermissionsal') }}";
    </script>
@if(isset($companySettings) && $companySettings->stamp)
    <img src="{{ asset('storage/' . $companySettings->stamp) }}" ... >
@endif
    <script src="{{ asset('assets/js/ordermissionsal.js') }}"></script>

@endsection