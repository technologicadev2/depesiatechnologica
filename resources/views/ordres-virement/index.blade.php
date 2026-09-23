@extends('master_page.app')

@section('title')
    Gestion des Ordres de Virement
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Gestion des Ordres de Virement</h4>

    <!-- SweetAlert2 Notifications -->
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: '{{ session('success') }}',
                position: 'center',
                confirmButtonText: 'OK'
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: '{{ session('error') }}',
                position: 'center',
                confirmButtonText: 'OK'
            });
        </script>
    @endif

    <div class="row mb-4">
        <div class="card">
            <div class="card-header">
                <div class="row ms-2 me-3 dt-filter-container">
                    <div
                        class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                        <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOrdreModal">
                                <i class="bx bx-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Ajouter
                                    Ordre</span>
                            </button>
                        </div>
                    </div>
                    <div
                        class="col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2 dt-filter">
                        <!-- Filters if needed -->
                    </div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                 <table class="datatables-basic table table-striped table-hover border-top" id="ordresTable">
                    <thead>
                        <tr>
                            <th>Réf</th>
                            <th>Destinataire</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                        <tr>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Réf"></th>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Destinataire"></th>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Montant"></th>
                            <th><input type="text" class="form-control form-control-sm" placeholder="Rechercher Date"></th>
                            <th></th>
                        </tr>
                    </thead>
                <tbody>
    @foreach ($ordres as $ordre)
        <tr>
            <td>{{ $ordre->reference }}</td> 
            <td>{{ $ordre->name }}</td>
            <td>{{ number_format($ordre->montant, 2, '.', ' ') }} DHS</td>
            <td>{{ date('d/m/Y', strtotime($ordre->date_virement)) }}</td>
            <td>
                <div class="action-buttons d-flex align-items-center actions-row">
                    <button class="btn btn-sm btn-icon edit-ordre" data-id="{{ $ordre->id }}" title="Modifier">
                        <i class="bx bx-edit text-primary"></i>
                    </button>
                    <button class="btn btn-sm btn-icon download-ordre" data-id="{{ $ordre->id }}" title="Télécharger PDF">
    <i class="bx bx-download text-success"></i>
</button>
                    <form action="{{ route('manage-ordres-virement.destroy', $ordre->id) }}" method="POST" class="delete-form" style="display:inline;">
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

    <!-- Add Ordre Modal -->
    <div class="modal fade" id="addOrdreModal" tabindex="-1" aria-labelledby="addOrdreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addOrdreModalLabel">Ajouter un Ordre de Virement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addOrdreForm" action="{{ route('manage-ordres-virement.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="type_destinataire" value="societe">
                        <div class="mb-3">
                            <label for="destinataire_id" class="form-label">Société</label>
                            <select class="form-control" id="destinataire_id" name="destinataire_id" required>
                                <option value="">Sélectionner</option>
                                @foreach ($entites as $entite)
                                    <option value="{{ $entite->id }}">{{ $entite->raison_sociale }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3" id="rib_virement_container" style="display:none;">
                            <label for="rib_virement" class="form-label">RIB à utiliser</label>
                            <select class="form-control" id="rib_virement" name="rib_virement">
                                <option value="">Sélectionner un RIB</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reference" class="form-label">Référence Facture</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="reference" name="reference">
                                <button type="button" class="btn btn-outline-primary"
                                    id="generateReference">Générer</button>
                                <button type="button" class="btn btn-outline-danger" id="clearReference">Effacer</button>
                            </div>
                        </div>
                       <div class="mb-3">
                        <label for="montant" class="form-label">Montant (DHS)</label>
                        <input type="text" inputmode="decimal" class="form-control montant-formate" id="montant" name="montant_affiche" required autocomplete="off">
                        <input type="hidden" id="montant_reel" name="montant">
                    </div>
                        <div class="mb-3">
                            <label for="date_virement" class="form-label">Date Virement</label>
                            <input type="date" class="form-control" id="date_virement" name="date_virement"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="motif" class="form-label">Motif</label>
                            <input type="text" class="form-control" id="motif" name="motif">
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

    <!-- Edit Ordre Modal -->
    <div class="modal fade" id="editOrdreModal" tabindex="-1" aria-labelledby="editOrdreModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOrdreModalLabel">Modifier un Ordre de Virement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editOrdreForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" id="edit_ordre_id" name="id">
                        <input type="hidden" name="type_destinataire" value="societe">
                        <div class="mb-3">
                            <label for="edit_destinataire_id" class="form-label">Société</label>
                            <select class="form-control" id="edit_destinataire_id" name="destinataire_id" required>
                                @foreach ($entites as $entite)
                                    <option value="{{ $entite->id }}">{{ $entite->raison_sociale }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_reference" class="form-label">Référence Facture</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="edit_reference" name="reference">
                                <button type="button" class="btn btn-outline-primary"
                                    id="edit_generateReference">Générer</button>
                                <button type="button" class="btn btn-outline-danger"
                                    id="edit_clearReference">Effacer</button>
                            </div>
                        </div>
                        <div class="mb-3">
    <label for="edit_montant" class="form-label">Montant (DHS)</label>
    <input type="text" inputmode="decimal" class="form-control" id="edit_montant" name="montant_affiche" required autocomplete="off">
    <input type="hidden" id="edit_montant_reel" name="montant">
</div>
                        <div class="mb-3">
                            <label for="edit_date_virement" class="form-label">Date Virement</label>
                            <input type="date" class="form-control" id="edit_date_virement" name="date_virement"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_motif" class="form-label">Motif</label>
                            <input type="text" class="form-control" id="edit_motif" name="motif">
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


    <!-- Download Modal -->
<div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="downloadModalLabel">
                    <i class="bx bx-download me-2"></i>Type de Virement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="mb-4 fs-6">Veuillez choisir le type de virement pour ce document :</p>
                <div class="d-flex justify-content-center gap-3">
                    <button id="btn-instantane" class="btn btn-success btn-lg px-4">
                        <i class="bx bx-bolt-circle me-2"></i>Virement Instantané
                    </button>
                    <button id="btn-normal" class="btn btn-primary btn-lg px-4">
                        <i class="bx bx-transfer me-2"></i>Virement Normal
                    </button>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            </div>
        </div>
    </div>
</div>

    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const ordresTable = $('#ordresTable').DataTable({
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
                initComplete: function() {
                    this.api().columns().every(function(index) {
                        if (index === 4) return; // Skip actions
                        var column = this;
                        var input = $(`#ordresTable thead tr:eq(1) th:eq(${index}) input`);
                        input.on('keyup change', function() {
                            if (column.search() !== this.value) {
                                column.search(this.value).draw();
                            }
                        });
                    });
                }
            });


            // Formate un nombre avec des espaces comme séparateur de milliers
function formatMontant(value) {
    // On garde uniquement chiffres et un seul point décimal
    value = value.replace(/[^\d.]/g, '');
    const parts = value.split('.');
    let integerPart = parts[0] || '';
    let decimalPart = parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '';

    // Ajout des espaces tous les 3 chiffres
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

    return integerPart + decimalPart;
}

function attachMontantFormatter(inputId, hiddenId) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);

    input.addEventListener('input', function () {
        const cursorPos = input.selectionStart;
        const oldLength = input.value.length;

        const formatted = formatMontant(input.value);
        input.value = formatted;

        // Valeur réelle sans espaces, pour l'envoi au serveur
        hidden.value = formatted.replace(/\s/g, '');

        // Ajuster la position du curseur après reformattage
        const newLength = formatted.length;
        const diff = newLength - oldLength;
        input.setSelectionRange(cursorPos + diff, cursorPos + diff);
    });
}

// Initialisation pour le modal d'ajout
attachMontantFormatter('montant', 'montant_reel');

// Initialisation pour le modal de modification
attachMontantFormatter('edit_montant', 'edit_montant_reel');
            // Generate reference for Add Modal
            $('#generateReference').on('click', function() {
                const dateVirement = $('#date_virement').val();
                if (!dateVirement) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Avertissement',
                        text: 'Veuillez sélectionner une date de virement pour générer la référence.',
                        position: 'center',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                $.ajax({
                    url: '{{ route('manage-ordres-virement.generate-reference') }}',
                    type: 'POST',
                    data: {
                        date_virement: dateVirement
                    },
                    success: function(response) {
                        $('#reference').val(response.reference);
                        console.log('Generated Reference:', response.reference);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: xhr.responseJSON?.message ||
                                'Erreur lors de la génération de la référence.',
                            position: 'center',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // Clear reference for Add Modal
            $('#clearReference').on('click', function() {
                $('#reference').val('');
            });

            // Generate reference for Edit Modal
            $('#edit_generateReference').on('click', function() {
                const dateVirement = $('#edit_date_virement').val();
                if (!dateVirement) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Avertissement',
                        text: 'Veuillez sélectionner une date de virement pour générer la référence.',
                        position: 'center',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                $.ajax({
                    url: '{{ route('manage-ordres-virement.generate-reference') }}',
                    type: 'POST',
                    data: {
                        date_virement: dateVirement
                    },
                    success: function(response) {
                        $('#edit_reference').val(response.reference);
                        console.log('Generated Reference (Edit):', response.reference);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: xhr.responseJSON?.message ||
                                'Erreur lors de la génération de la référence.',
                            position: 'center',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // Clear reference for Edit Modal
            $('#edit_clearReference').on('click', function() {
                $('#edit_reference').val('');
            });

            // Add form submit
            $('#addOrdreForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const destinataireId = $('#destinataire_id').val();
                const montant = $('#montant').val();
                const dateVirement = $('#date_virement').val();

                if (!destinataireId || destinataireId === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Avertissement',
                        text: 'Veuillez sélectionner une société.',
                        position: 'center',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                if (!montant || montant <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Avertissement',
                        text: 'Veuillez entrer un montant valide.',
                        position: 'center',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                if (!dateVirement) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Avertissement',
                        text: 'Veuillez sélectionner une date de virement.',
                        position: 'center',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...'
                    );

                const formData = $(this).serialize();
                console.log('Add Form Data:', formData);

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        submitBtn.prop('disabled', false).html('Enregistrer');
                        $('#addOrdreModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: response.message ||
                                'Ordre de virement ajouté avec succès !',
                            position: 'center',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).html('Enregistrer');
                        let errorMessage = 'Une erreur s\'est produite lors de l\'ajout.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join(
                                '<br>');
                        }
                        console.error('Add Error:', xhr.status, xhr.responseText);
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

            // Edit
        $(document).on('click', '.edit-ordre', function() {
    const ordreId = $(this).data('id');
    $.ajax({
        url: `/manage-ordres-virement/${ordreId}/edit`,
        type: 'GET',
        success: function(response) {
            if (response.ordre) {
                $('#edit_ordre_id').val(response.ordre.id);
                $('#edit_destinataire_id').val(response.ordre.destinataire_id);
                // CORRECTION: Afficher la vraie référence (reference) pas ref
                $('#edit_reference').val(response.ordre.reference);
                const montantValue = response.ordre.montant;
$('#edit_montant').val(formatMontant(String(montantValue)));
$('#edit_montant_reel').val(montantValue);
                $('#edit_date_virement').val(response.ordre.date_virement);
                $('#edit_motif').val(response.ordre.motif);
                $('#editOrdreForm').attr('action', `/manage-ordres-virement/${ordreId}`);
                $('#editOrdreModal').modal('show');
                console.log('Edit Data Loaded:', response.ordre);
            }
        },
        error: function(xhr) {
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
            // Update form submit
            $('#editOrdreForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mise à jour...'
                    );

                const formData = $(this).serialize() + '&_method=PUT';
                console.log('Update Form Data:', formData);

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        submitBtn.prop('disabled', false).html('Mettre à jour');
                        $('#editOrdreModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: response.message ||
                                'Ordre de virement mis à jour avec succès !',
                            position: 'center',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).html('Mettre à jour');
                        let errorMessage = 'Une erreur s\'est produite lors de la mise à jour.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join(
                                '<br>');
                        }
                        console.error('Update Error:', xhr.status, xhr.responseText);
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

            // Delete
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const url = form.attr('action');

                Swal.fire({
                    title: 'Êtes-vous sûr ?',
                    text: "Vous ne pourrez pas récupérer cet ordre après suppression !",
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
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Succès',
                                    text: response.message ||
                                        'Ordre de virement supprimé avec succès !',
                                    position: 'center',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                let errorMessage =
                                    'Une erreur s\'est produite lors de la suppression.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
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


        // Download avec choix type virement
let currentOrdreId = null;

$(document).on('click', '.download-ordre', function() {
    currentOrdreId = $(this).data('id');
    $('#downloadModal').modal('show');
});

$('#btn-instantane').on('click', function() {
    if (currentOrdreId) {
        window.location.href = `/manage-ordres-virement/${currentOrdreId}/download?type_virement=instantane`;
        $('#downloadModal').modal('hide');
    }
});

$('#btn-normal').on('click', function() {
    if (currentOrdreId) {
        window.location.href = `/manage-ordres-virement/${currentOrdreId}/download?type_virement=normal`;
        $('#downloadModal').modal('hide');
    }
});


function loadEntiteRibs(entiteId, selectId, containerId, selectedValue = null) {
    const select = $(`#${selectId}`);
    const container = $(`#${containerId}`);
    select.html('<option value="">Sélectionner un RIB</option>');

    if (!entiteId) {
        container.hide();
        return;
    }

    $.ajax({
        url: `/manage-ordres-virement/entite/${entiteId}/ribs`,
        type: 'GET',
        success: function(response) {
            if (response.ribs && response.ribs.length > 0) {
                response.ribs.forEach(function(rib) {
                    const isSelected = selectedValue && rib.value === selectedValue ? 'selected' : '';
                    select.append(`<option value="${rib.value}" ${isSelected}>${rib.label}</option>`);
                });
                container.show();
            } else {
                container.hide();
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Erreur lors du chargement des RIBs.',
                position: 'center',
                confirmButtonText: 'OK'
            });
        }
    });
}

// Modal Ajouter : quand on choisit une société
$('#destinataire_id').on('change', function() {
    loadEntiteRibs($(this).val(), 'rib_virement', 'rib_virement_container');
});

// Modal Modifier : quand on choisit une société
$('#edit_destinataire_id').on('change', function() {
    loadEntiteRibs($(this).val(), 'edit_rib_virement', 'edit_rib_virement_container');
});
    </script>
@endsection