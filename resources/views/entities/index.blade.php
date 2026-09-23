@extends('master_page.app')

@section('title')
    Gestion des Entités
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Gestion des Entités</h4>

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

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 5px;
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

        .actions-row .delete-btn i {
            color: #dc3545;
        }

        .actions-row .delete-btn:hover i {
            color: #bd2130;
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
        <div class="card">
            <div class="card-header">
                <div class="row ms-2 me-3 dt-filter-container">
                    <div class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                        <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEntityModal">
                                <i class="bx bx-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Ajouter Entité</span>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter">
                        <!-- Add any filters if needed -->
                    </div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
       <table class="datatables-basic table table-striped table-hover border-top" id="entitiesTable">
    <thead>
        <tr>
            <th>Raison Sociale</th>
            <th>ICE</th>
            <th>Numéro</th>
            <th>Email</th>
            <th>RIB</th>
            <th>RIB 1</th> 
        <th>RIB 2</th> 
            <th>Actions</th>
        </tr>
        <tr>
            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Raison Sociale"></th>
            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher ICE"></th>
            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Numéro"></th>
            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Email"></th>
            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher RIB"></th> 
             <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher RIB 1"></th> <!-- nouveau -->
        <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher RIB 2"></th> <!-- nouveau --><!-- Add RIB search -->
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($entities as $entity)
            <tr>
                <td>{{ $entity->raison_sociale }}</td>
                <td>{{ $entity->ice }}</td>
                <td>{{ $entity->numero }}</td>
                <td>{{ $entity->email }}</td>
                <td>{{ $entity->rib }}</td>
                   <td>{{ $entity->rib1 }}</td> <!-- nouveau -->
            <td>{{ $entity->rib2 }}</td> <!-- Display RIB -->
                <td>
                    <div class="action-buttons d-flex align-items-center actions-row">
                        <button class="btn btn-sm btn-icon edit-entity" data-id="{{ $entity->id }}" title="Modifier">
                            <i class="bx bx-edit text-primary"></i>
                        </button>
                        <form action="{{ route('manage-entities.destroy', $entity->id) }}" method="POST" class="delete-form" style="display:inline;">
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

    <!-- Add Entity Modal -->
<div class="modal fade" id="addEntityModal" tabindex="-1" aria-labelledby="addEntityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEntityModalLabel">Ajouter une Entité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addEntityForm" action="{{ route('manage-entities.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="raison_sociale" class="form-label">Raison Sociale</label>
                        <input type="text" class="form-control @error('raison_sociale') is-invalid @enderror" id="raison_sociale" name="raison_sociale" value="{{ old('raison_sociale') }}" required>
                        @error('raison_sociale')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="ice" class="form-label">ICE</label>
                        <input type="text" class="form-control @error('ice') is-invalid @enderror" id="ice" name="ice" value="{{ old('ice') }}" required>
                        @error('ice')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="numero" class="form-label">Numéro</label>
                        <input type="text" class="form-control @error('numero') is-invalid @enderror" id="numero" name="numero" value="{{ old('numero') }}">
                        @error('numero')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
               <div class="mb-3">
    <label for="rib" class="form-label">RIB</label>
    <input type="text" class="form-control @error('rib') is-invalid @enderror" id="rib" name="rib" value="{{ old('rib') }}">
    @error('rib')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="rib1" class="form-label">RIB 1</label>
    <input type="text" class="form-control @error('rib1') is-invalid @enderror" id="rib1" name="rib1" value="{{ old('rib1') }}">
    @error('rib1')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="rib2" class="form-label">RIB 2</label>
    <input type="text" class="form-control @error('rib2') is-invalid @enderror" id="rib2" name="rib2" value="{{ old('rib2') }}">
    @error('rib2')
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
    <!-- Edit Entity Modal -->
<div class="modal fade" id="editEntityModal" tabindex="-1" aria-labelledby="editEntityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEntityModalLabel">Modifier une Entité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editEntityForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="edit_entity_id" name="id">
                    <div class="mb-3">
                        <label for="edit_raison_sociale" class="form-label">Raison Sociale</label>
                        <input type="text" class="form-control" id="edit_raison_sociale" name="raison_sociale" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_ice" class="form-label">ICE</label>
                        <input type="text" class="form-control" id="edit_ice" name="ice" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_numero" class="form-label">Numéro</label>
                        <input type="text" class="form-control" id="edit_numero" name="numero">
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>
                   <div class="mb-3">
    <label for="edit_rib" class="form-label">RIB</label>
    <input type="text" class="form-control" id="edit_rib" name="rib">
</div>
<div class="mb-3">
    <label for="edit_rib1" class="form-label">RIB 1</label>
    <input type="text" class="form-control" id="edit_rib1" name="rib1">
</div>
<div class="mb-3">
    <label for="edit_rib2" class="form-label">RIB 2</label>
    <input type="text" class="form-control" id="edit_rib2" name="rib2">
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
        // Configurer le jeton CSRF pour les requêtes AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialiser DataTable
        const entitiesTable = $('#entitiesTable').DataTable({
            dom: '<"row ms-2 me-3"' +
                '<"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls"l<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3"B>>' +
                '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter"f<"dt-filter mb-3 mb-md-0">>' +
                ">t" +
                '<"row mx-2"' +
                '<"col-sm-12 col-md-6"i>' +
                '<"col-sm-12 col-md-6"p>' +
                ">",
            displayLength: 10,
            lengthMenu: [10, 25, 50, 75, 100],
            ordering: false,
            buttons: [],
            responsive: true,
            orderCellsTop: true,
            language: {
                search: "Rechercher:",
                lengthMenu: "Afficher _MENU_ ",
                info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
                infoEmpty: "Aucun élément à afficher",
                infoFiltered: "(filtré à partir de _MAX_ éléments au total)",
                paginate: {
                    first: "Premier",
                    last: "Dernier",
                    next: "Suivant",
                    previous: "Précédent"
                }
            },
           initComplete: function () {
    this.api().columns().every(function (index) {
        if (index === 7) return; // Skip actions column (nouvel index)
        var column = this;
        var input = $(`#entitiesTable thead tr:eq(1) th:eq(${index}) input`);
        input.on('keyup change', function () {
            if (column.search() !== this.value) {
                column.search(this.value).draw();
            }
        });
    });
}
        });
            // Ajouter une entité
            $('#addEntityForm').on('submit', function (e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...');

                $.ajax({
                    url: '{{ route("manage-entities.store") }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        submitBtn.prop('disabled', false).html('Enregistrer');
                        $('#addEntityModal').modal('hide');
                        Swal.fire({
                            iconrosa_sociale: 'success',
                            title: 'Succès',
                            text: response.message || 'Entité ajoutée avec succès !',
                            position: 'center',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function (xhr) {
                        submitBtn.prop('disabled', false).html('Enregistrer');
                        let errorMessage = 'Une erreur s\'est produite lors de l\'ajout.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            html: errorMessage,
                            position: 'center',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // Modifier une entité
    $(document).on('click', '.edit-entity', function () {
    const entityId = $(this).data('id');
    $.ajax({
        url: `/manage-entities/${entityId}/edit`,
        type: 'GET',
        success: function (response) {
            if (response.entity) {
                $('#edit_entity_id').val(response.entity.id);
                $('#edit_raison_sociale').val(response.entity.raison_sociale);
                $('#edit_ice').val(response.entity.ice);
                $('#edit_numero').val(response.entity.numero);
                $('#edit_email').val(response.entity.email);
                $('#edit_rib').val(response.entity.rib);
                $('#edit_rib1').val(response.entity.rib1); // nouveau
                $('#edit_rib2').val(response.entity.rib2); // nouveau
                $('#editEntityForm').attr('action', `/manage-entities/${entityId}`);
                $('#editEntityModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Impossible de récupérer les données de l\'entité.',
                    position: 'center',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function (xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: xhr.responseJSON?.message || 'Erreur lors de la récupération des données.',
                position: 'center',
                confirmButtonText: 'OK'
            });
        }
    });
});
            // Soumettre le formulaire de modification
            $('#editEntityForm').on('submit', function (e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mise à jour...');

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize() + '&_method=PUT',
                    success: function (response) {
                        submitBtn.prop('disabled', false).html('Mettre à jour');
                        $('#editEntityModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: response.message || 'Entité mise à jour avec succès !',
                            position: 'center',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function (xhr) {
                        submitBtn.prop('disabled', false).html('Mettre à jour');
                        let errorMessage = 'Une erreur s\'est produite lors de la mise à jour.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            html: errorMessage,
                            position: 'center',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // Supprimer une entité
            $(document).on('click', '.delete-btn', function (e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const url = form.attr('action');

                Swal.fire({
                    title: 'Êtes-vous sûr ?',
                    text: "Vous ne pourrez pas récupérer cette entité après suppression !",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Oui, supprimer !',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: form.serialize() + '&_method=DELETE',
                            success: function (response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Succès',
                                    text: response.message || 'Entité supprimée avec succès !',
                                    position: 'center',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function (xhr) {
                                let errorMessage = 'Ce Fournisseurs ne peut pas être supprimée car elle est référencée dans  une Facture ';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erreur',
                                    html: errorMessage,
                                    position: 'center',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection