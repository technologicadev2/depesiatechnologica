@extends('master_page.app')

@section('title')
    Salariés Démissionnés
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <h4 class="fw-bold py-3 mb-4">Salariés Démissionnés</h4>
            <div class="card">
                <div class="card-datatable table-responsive position-relative">
                    <!-- Loading Overlay -->
                    <div id="table-loading" class="loading-overlay d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                    <table id="resignedSalariesTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>CIN</th>
                                <th>Matricule</th>
                                <th>Date Embauche</th>
                                <th>Date Démission</th>
                                <th>Motif</th>
                                <th>Préavis</th>
                                <th>Démission</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="no-data-message">
                                <td colspan="10" class="text-center">Aucun salarié trouvé.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Modal pour modifier la démission -->
            <div class="modal fade" id="editDemissionModal" tabindex="-1" aria-labelledby="editDemissionModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editDemissionModalLabel">Modifier Démission</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editDemissionForm" enctype="multipart/form-data">
                                <input type="hidden" id="demission_id" name="demission_id">
                                <div class="mb-3">
                                    <label for="date_demission" class="form-label">Date de Démission <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date_demission" name="date_demission" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="motif" class="form-label">Motif <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="motif" name="motif" rows="4" required></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="preavis_file" class="form-label">Préavis (PDF)</label>
                                    <input type="file" class="form-control" id="preavis_file" name="preavis_file" accept=".pdf">
                                    <small id="preavis_file_info" class="form-text text-muted"></small>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="demission_file" class="form-label">Démission (PDF)</label>
                                    <input type="file" class="form-control" id="demission_file" name="demission_file" accept=".pdf">
                                    <small id="demission_file_info" class="form-text text-muted"></small>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary" id="saveDemissionBtn" data-loading-text="Enregistrement...">Enregistrer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
    </style>

    <script src="{{ asset('assets/js/salarie.js') }}"></script>
@endsection