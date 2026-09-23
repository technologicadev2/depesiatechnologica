@extends('master_page.app')

@section('title')
    Liste des Fonctions
@endsection

@section('content')
    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Liste des Fonctions</h4>
    <!-- Container -->
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered invoice-list-table" style="width:100%">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Désignation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Adding/Editing Function -->
    <div class="modal fade" id="modalFonction" tabindex="-1" aria-labelledby="modalFonctionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFonctionLabel">Ajouter/Modifier une Fonction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="fonctionForm">
                    <div class="modal-body">
                        <input type="hidden" id="fonctionId" name="id">
                        <div class="mb-3">
                            <label for="designation" class="form-label">Désignation</label>
                            <input type="text" class="form-control" id="designation" name="designation" required>
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

    <!-- Scripts -->
    <script src="{{ asset('assets/js/salarie-fonction.js') }}"></script>
@endsection