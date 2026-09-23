@extends('master_page.app')

@section('title')
    Gestion des Clients
@endsection

@section('content')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <h4 class="fw-bold py-3 mb-4">Gestion des Clients</h4>

    <!-- SweetAlert success / error (comme avant) -->
    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: "{{ session('success') }}",
                    timer: 5000,
                    timerProgressBar: true,
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
                    timer: 5000,
                    timerProgressBar: true,
                });
            });
        </script>
    @endif

    <div class="row mb-4">
        <div class="card">
            <div class="card-header">
                <div class="row ms-2 me-3 dt-filter-container">
                    <div class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                        <div class="dt-action-buttons">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                                <i class="bx bx-plus me-sm-1"></i>
                                <span class="d-none d-sm-inline-block">Ajouter Client</span>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter">
                        <!-- filtre supplémentaire si besoin -->
                    </div>
                </div>
            </div>

            <div class="card-datatable table-responsive">
                <table class="datatables-basic table table-striped table-hover border-top" id="clientsTable">
                    <thead>
                        <tr>
                            <th>Nom Complet</th>
                            <th>ICE</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Adresse</th>
                            <th>Actions</th>
                        </tr>
                        <tr>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher nom"></th>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher ICE"></th>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Téléphone"></th>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Email"></th>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Adresse"></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                            <tr>
                                <td>{{ $client->nom_complet }}</td>
                                <td>{{ $client->ice ?? '-' }}</td>
                                <td>{{ $client->telephone ?? '-' }}</td>
                                <td>{{ $client->email ?? '-' }}</td>
                                <td>{{ $client->adresse ?? '-' }}</td>
                                <td>
                                    <div class="action-buttons d-flex align-items-center actions-row">
                                        <button class="btn btn-sm btn-icon edit-client" data-id="{{ $client->id }}" title="Modifier">
                                            <i class="bx bx-edit text-primary"></i>
                                        </button>
                                        <form action="{{ route('clients.destroy', $client->id) }}" method="POST" class="delete-form" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon delete-btn" title="Supprimer">
                                                <i class="bx bx-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal AJOUT -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClientModalLabel">Ajouter un Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addClientForm" action="{{ route('clients.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nom_complet" class="form-label">Nom Complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom_complet" name="nom_complet" required>
                        </div>
                        <div class="mb-3">
                            <label for="ice" class="form-label">ICE</label>
                            <input type="text" class="form-control" id="ice" name="ice">
                        </div>
                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="telephone" name="telephone">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="2"></textarea>
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

    <!-- Modal MODIFICATION -->
    <div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClientModalLabel">Modifier le Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editClientForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" id="edit_client_id" name="id">
                        <div class="mb-3">
                            <label for="edit_nom_complet" class="form-label">Nom Complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nom_complet" name="nom_complet" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_ice" class="form-label">ICE</label>
                            <input type="text" class="form-control" id="edit_ice" name="ice">
                        </div>
                        <div class="mb-3">
                            <label for="edit_telephone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="edit_telephone" name="telephone">
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="edit_adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="edit_adresse" name="adresse" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {

            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            // DataTable
            const table = $('#clientsTable').DataTable({
                dom: '<"row ms-2 me-3"' +
                     '<"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls"l<"dt-action-buttons">>' +
                     '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter"f<"dt-filter mb-3 mb-md-0">>' +
                     ">t" +
                     '<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                displayLength: 10,
                lengthMenu: [10, 25, 50, 100],
                ordering: false,
                responsive: true,
                orderCellsTop: true,
                language: {
                    search: "Rechercher :",
                    lengthMenu: "Afficher _MENU_ ",
                    info: "Affichage _START_ à _END_ sur _TOTAL_",
                    paginate: { first: "Premier", last: "Dernier", next: "Suivant", previous: "Précédent" }
                },
                initComplete: function () {
                    this.api().columns().every(function (index) {
                        if (index === 5) return; // skip actions
                        let column = this;
                        let input = $(`#clientsTable thead tr:eq(1) th:eq(${index}) input`);
                        input.on('keyup change', function () {
                            if (column.search() !== this.value) {
                                column.search(this.value).draw();
                            }
                        });
                    });
                }
            });

            // ── AJOUT ───────────────────────────────────────
            $('#addClientForm').on('submit', function (e) {
                e.preventDefault();
                let btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Enregistrement...');

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (res) {
                        btn.prop('disabled', false).html('Enregistrer');
                        $('#addClientModal').modal('hide');
                        Swal.fire('Succès', res.message || 'Client ajouté !', 'success')
                            .then(() => location.reload());
                    },
                    error: function (xhr) {
                        btn.prop('disabled', false).html('Enregistrer');
                        let msg = xhr.responseJSON?.errors
                            ? Object.values(xhr.responseJSON.errors).flat().join('<br>')
                            : 'Erreur lors de l\'ajout';
                        Swal.fire('Erreur', msg, 'error');
                    }
                });
            });

            // ── EDIT ────────────────────────────────────────
            $(document).on('click', '.edit-client', function () {
                let id = $(this).data('id');
                $.ajax({
                    url: `/clients/${id}/edit`,
                    type: 'GET',
                    success: function (res) {
                        if (res.client) {
                            $('#edit_client_id').val(res.client.id);
                            $('#edit_nom_complet').val(res.client.nom_complet);
                            $('#edit_ice').val(res.client.ice);
                            $('#edit_telephone').val(res.client.telephone);
                            $('#edit_email').val(res.client.email);
                            $('#edit_adresse').val(res.client.adresse);
                            $('#editClientForm').attr('action', `/clients/${id}`);
                            $('#editClientModal').modal('show');
                        }
                    },
                    error: function () {
                        Swal.fire('Erreur', 'Impossible de charger les données', 'error');
                    }
                });
            });

            $('#editClientForm').on('submit', function (e) {
                e.preventDefault();
                let btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Mise à jour...');

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize() + '&_method=PUT',
                    success: function (res) {
                        btn.prop('disabled', false).html('Mettre à jour');
                        $('#editClientModal').modal('hide');
                        Swal.fire('Succès', res.message || 'Client modifié !', 'success')
                            .then(() => location.reload());
                    },
                    error: function (xhr) {
                        btn.prop('disabled', false).html('Mettre à jour');
                        let msg = xhr.responseJSON?.errors
                            ? Object.values(xhr.responseJSON.errors).flat().join('<br>')
                            : 'Erreur lors de la mise à jour';
                        Swal.fire('Erreur', msg, 'error');
                    }
                });
            });

            // ── SUPPRESSION ─────────────────────────────────
            $(document).on('click', '.delete-btn', function (e) {
                e.preventDefault();
                let form = $(this).closest('form');

                Swal.fire({
                    title: 'Confirmer la suppression ?',
                    text: "Cette action est irréversible !",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: form.attr('action'),
                            type: 'POST',
                            data: form.serialize() + '&_method=DELETE',
                            success: function (res) {
                                Swal.fire('Supprimé !', res.message || 'Client supprimé.', 'success')
                                    .then(() => location.reload());
                            },
                            error: function (xhr) {
                                let msg = xhr.responseJSON?.error || 'Erreur lors de la suppression';
                                Swal.fire('Erreur', msg, 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>

@endsection