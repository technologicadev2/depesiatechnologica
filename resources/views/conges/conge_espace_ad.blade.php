@extends('master_page.app')

@section('title')
    Congés Administratif
@endsection

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-calendar.css') }}" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/conge_admin.css') }}" />
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Congés Administratif</h4>

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
        <ul class="nav nav-pills border border-1 border-light rounded bg-white" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane"
                    type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">
                    Les demandes des salariés
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane"
                    type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">
                    Analytique
                </button>
            </li>
        </ul>
        <div class="tab-content border border-1 border-light border-top-0 rounded-bottom p-3 bg-white" id="myTabContent">
            <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab"
                tabindex="0">
                <div class="card">
                    <div class="card-datatable table-responsive">
                        <table class="datatables-basic table table-striped table-hover border-top" id="congesTable">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Matricule</th>
                                    <th>Date de début</th>
                                    <th>Date de fin</th>
                                    <th>Nombre de jours</th>
                                    <th>Raison</th>
                                    <th>Jours Restants</th>
                                    <th>Approbation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($conges as $conge)
                                    <tr data-salarie-id="{{ $conge->salarie_id }}">
                                        <td>{{ $conge->salarie->nom ?? 'N/A' }}</td>
                                        <td>{{ $conge->salarie->prenom ?? 'N/A' }}</td> 
                                        <td>{{ $conge->salarie->n_matricule_entreprise ?? 'N/A' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($conge->date_debut)->format('d/m/Y') }}</td>
                                        <td>
                @if ($conge->date_fin)
                    {{ \Carbon\Carbon::parse($conge->date_fin)->format('d/m/Y') }}
                @else
                    —
                @endif
            </td>
                                        <td>{{ $conge->num_j ?? 'N/A' }}</td>
                                        <td>{{ $conge->raison ?? 'Aucune raison' }}</td>
                                         <td>
    @if ($conge->approbation == 0)
        {{ max(0, $conge->n_jours_reste - $conge->num_j) }}
    @else 
        {{ $conge->n_jours_reste ?? 'N/A' }}
    @endif
</td>
                                        <td>
                                            @if ($conge->approbation == 0)
                                                <span class="badge badge-pending">En cours</span>
                                            @elseif ($conge->approbation == 1)
                                                <span class="badge badge-rejected">Refusé</span>
                                            @elseif ($conge->approbation == 2)
                                                <span class="badge badge-accepted">Accepté</span>
                                            @else
                                                <span class="badge badge-secondary">N/A (Valeur:
                                                    {{ $conge->approbation ?? 'NULL' }})</span>
                                            @endif
                                        </td>
                          <td>
    <!-- Icône d'approbation/refus -->
    <i class="bx bx-check-circle action-icon" 
       data-id="{{ $conge->id }}"
       title="Approuver/Refuser"
       style="cursor: pointer; color: #28a745; font-size: 1.5rem; margin-right: 10px;"></i>
    
    <!-- Icône de téléchargement PDF -->
    @if ($conge->approbation == 2 && $conge->pdf_path)
        <a href="{{ route('conge.download.administration.pdf', $conge->id) }}"
           class="download-icon enabled" 
           title="Télécharger PDF"
           style="margin-right: 10px;">
            <i class="bx bx-download" style="font-size: 1.5rem; color: #007bff;"></i>
        </a>
    @else
        <i class="bx bx-download download-icon disabled"
           title="PDF non disponible"
           style="font-size: 1.5rem; color: #ccc; margin-right: 10px;"></i>
    @endif
    
    <!-- Icône de modification (visible si approbation != 2) -->
    @if ($conge->approbation != 2)
        <i class="bx bx-edit edit-icon" 
           data-id="{{ $conge->id }}"
           title="Modifier"
           style="cursor: pointer; color: #ffc107; font-size: 1.5rem; margin-right: 10px;"></i>
    @else
        <i class="bx bx-edit edit-icon disabled" 
           title="Modification non disponible pour les congés approuvés"
           style="font-size: 1.5rem; color: #ccc; margin-right: 10px;"></i>
    @endif
    
    <!-- Icône de suppression -->
    <i class="bx bx-trash delete-icon" 
       data-id="{{ $conge->id }}"
       title="Supprimer"
       style="cursor: pointer; color: #dc3545; font-size: 1.5rem;"></i>
</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="card app-calendar-wrapper">
                        <div class="row g-0">
                            <!-- Calendar Sidebar -->
                            <div class="col app-calendar-sidebar" id="app-calendar-sidebar">
                                <div class="p-4">
                                    <div class="ms-n2">
                                        <div class="inline-calendar"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- Calendar & Modal -->
                            <div class="col app-calendar-content">
                                <div class="card shadow-none border-0">
                                    <div class="card-body pb-0">
                                        <div id="calendar"></div>
                                    </div>
                                </div>
                                <div class="app-overlay"></div>
                                <div class="offcanvas offcanvas-end event-sidebar" tabindex="-1" id="addEventSidebar"
                                    aria-labelledby="addEventSidebarLabel">
                                    <div class="offcanvas-header border-bottom">
                                        <h5 class="offcanvas-title mb-2" id="addEventSidebarLabel">Détails du Congé</h5>
                                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="offcanvas-body">
                                        <div class="leave-details" id="leaveDetails">
                                            <p><strong>Employé:</strong> <span id="leaveEmployee"></span></p>
                                            <p><strong>Raison:</strong> <span id="leaveReason"></span></p>
                                            <p><strong>Période:</strong> <span id="leavePeriod"></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalConge" tabindex="-1" aria-labelledby="modalCongeLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <img src="{{ asset('assets/img/favicon/anassi2.jpg') }}" alt="Logo">
                    <h5 class="modal-title" id="modalCongeLabel">Demande de Congé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="congeForm" action="{{ route('conge.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="salarie_id" class="form-label">Sélectionner un salarié</label>
                            <select class="form-control" id="salarie_id" name="salarie_id" required>
                                <option value="">-- Choisir un salarié --</option>
                                @foreach ($salaries as $salarie)
                                    <option value="{{ $salarie->id }}" data-nom="{{ $salarie->nom }}"
                                        data-prenom="{{ $salarie->prenom }}" data-email="{{ $salarie->email ?? '' }}">
                                        {{ $salarie->nom }} {{ $salarie->prenom }}
                                        ({{ $salarie->n_matricule_entreprise }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="nom" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="prenom" class="form-label">Prénom</label>
                                    <input type="text" class="form-control" id="prenom" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="h5 text-dark">Pour quelle date faites-vous cette demande de congé ?</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="date_debut" class="form-label">Date de début</label>
                                    <input type="date" class="form-control" id="date_debut" name="date_debut"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="nombre_jours" class="form-label">Nombre de jours</label>
                                    <input type="number" class="form-control" id="nombre_jours" name="nombre_jours"
                                        min="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="h5 text-dark">Raison</label>
                            <textarea class="form-control" id="raison" name="raison" rows="4" required
                                placeholder="Veuillez expliquer la raison de votre demande de congé"></textarea>
                            <div class="mt-2">
                                <label class="h5 text-dark">Signature</label>
                                <canvas id="signaturePad" class="signature-pad"></canvas>
                                <input type="hidden" name="signature" id="signatureInput">
                                <div class="signature-buttons">
                                    <button type="button" id="clearSignature"
                                        class="btn btn-secondary btn-sm">Effacer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Soumettre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

   <div class="modal fade" id="modalEditConge" tabindex="-1" aria-labelledby="modalEditCongeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <img src="{{ asset('assets/img/favicon/anassi2.jpg') }}" alt="Logo">
                <h5 class="modal-title" id="modalEditCongeLabel">Modifier la Demande de Congé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCongeForm" action="" method="POST">
    @csrf
    <input type="hidden" name="_method" id="editFormMethod" value="PUT">
    <div class="modal-body">
        <div class="mb-3">
            <label for="edit_salarie_id" class="form-label">Sélectionner un salarié</label>
            <select class="form-control" id="edit_salarie_id" name="salarie_id" required>
                <option value="">-- Choisir un salarié --</option>
                @foreach ($salaries as $salarie)
                    <option value="{{ $salarie->id }}" data-nom="{{ $salarie->nom }}"
                        data-prenom="{{ $salarie->prenom }}" data-email="{{ $salarie->email ?? '' }}">
                        {{ $salarie->nom }} {{ $salarie->prenom }}
                        ({{ $salarie->n_matricule_entreprise }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <div class="row">
                <div class="col-md-6">
                    <label for="edit_nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="edit_nom" readonly>
                </div>
                <div class="col-md-6">
                    <label for="edit_prenom" class="form-label">Prénom</label>
                    <input type="text" class="form-control" id="edit_prenom" readonly>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label for="edit_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="edit_email" readonly>
        </div>
        <div class="mb-3">
            <label class="h5 text-dark">Pour quelle date faites-vous cette demande de congé ?</label>
            <div class="row">
                <div class="col-md-6">
                    <label for="edit_date_debut" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="edit_date_debut" name="date_debut" required>
                </div>
                <div class="col-md-6">
                    <label for="edit_nombre_jours" class="form-label">Nombre de jours</label>
                    <input type="number" class="form-control" id="edit_nombre_jours" name="nombre_jours" min="1" required>
                </div>
            </div>
        </div>
        <div class="mb-2">
            <label class="h5 text-dark">Raison</label>
            <textarea class="form-control" id="edit_raison" name="raison" rows="4" required
                placeholder="Veuillez expliquer la raison de votre demande de congé"></textarea>
            <div class="mt-2">
                <label class="h5 text-dark">Signature</label>
                <canvas id="editSignaturePad" class="signature-pad"></canvas>
                <input type="hidden" name="signature" id="editSignatureInput">
                <div class="signature-buttons">
                    <button type="button" id="editClearSignature" class="btn btn-secondary btn-sm">Effacer</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </div>
</form>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
    const companySettings = @json($companySettings ?? []);
    console.log('Company Settings:', companySettings); // Pour déboguer
</script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
    <script src="{{ asset('assets/js/conge_espace_ad.js') }}"></script>
    <script src="{{ asset('assets/js/calendar_conge_admin.js') }}"></script>
@endsection
