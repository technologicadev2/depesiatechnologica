@extends('master_page.app')

@section('title')
    Congés
@endsection

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

        #modalConge .modal-content {
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #f9f9f9;
        }

        #modalConge .modal-header {
            border-bottom: 2px solid #000;
            background-color: #fff;
            display: flex;
            align-items: center;
        }

        #modalConge .modal-header img {
            max-width: 80px;
            margin-right: 20px;
        }

        #modalConge .modal-body {
            padding: 20px;
            background-color: #fff;
        }

        #modalConge .form-label {
            font-weight: bold;
        }

        #modalConge .form-control {
            background-color: #f5f5f5;
            border: 1px solid #ccc;
        }

        #modalConge .form-control[readonly] {
            background-color: #e9ecef;
            opacity: 1;
        }

        .modal-body .row {
            margin-left: -10px;
            margin-right: -10px;
        }

        .modal-body .col-md-6 {
            padding-left: 10px;
            padding-right: 10px;
        }

        .form-check {
            margin-top: 10px;
        }

        .form-check-label {
            font-size: 14px;
        }

        .signature-pad {
            border: 1px solid #ccc;
            background-color: #fff;
            width: 100%;
            height: 150px;
            margin-top: 10px;
        }

        .signature-buttons {
            margin-top: 10px;
        }

        /* Status badge styles */
        .badge-pending {
            background-color: #fd7e14;
            /* Orange */
            color: #fff;
        }

        .badge-accepted {
            background-color: #28a745;
            /* Green */
            color: #fff;
        }

        .badge-rejected {
            background-color: #dc3545;
            /* Red */
            color: #fff;
        }

        .download-icon {
            font-size: 1.2rem;
            margin-right: 10px;
            transition: color 0.2s;
        }

        .download-icon.enabled {
            color: #28a745;
            cursor: pointer;
        }

        .download-icon.enabled:hover {
            color: #218838;
        }

        .download-icon.disabled {
            color: #6c757d;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Congés</h4>
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
        <div class="card-datatable table-responsive">
            <table class="datatables-basic table table-striped table-hover border-top" id="congesTable">
                <thead>
                    <tr>
                        <th>Date de début</th>
                        <th>Date de fin</th>
                        <th>Nombre de jours</th>
                        <th>Jours restants</th>
                        <th>Approbation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($conges as $conge)
                        <tr data-conge-id="{{ $conge->id }}">
                            <td>{{ \Carbon\Carbon::parse($conge->date_debut)->format('d/m/Y') }}</td>
                            <td>
                @if($conge->date_fin)
                    {{ \Carbon\Carbon::parse($conge->date_fin)->format('d/m/Y') }}
                @else
                    —
                @endif
            </td>
                            <td>{{ $conge->num_j }}</td>
                            <td>{{ $conge->n_jours_reste }}</td>
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
                                @if ($conge->approbation == 2 && $conge->pdf_path)
                                    <a href="{{ route('conge.download.pdf', $conge->id) }}" class="download-icon enabled"
                                        title="Télécharger PDF">
                                        <i class="bx bx-download"></i>
                                    </a>
                                @else
                                    <i class="bx bx-download download-icon disabled" title="PDF non disponible"></i>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Demander un Congé -->
    <div class="modal fade" id="modalConge" tabindex="-1" aria-labelledby="modalCongeLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <img src="{{ asset('assets/img/favicon/anassi2.jpg') }}" alt="Logo">
                    <h5 class="modal-title" id="modalCongeLabel">Demande de Congé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="congeForm" action="{{ route('conge.salarie.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @if ($salarie)
                            <input type="hidden" name="salarie_id" value="{{ $salarie->id }}">
                            <div class="mb-3">
                                <label class="h5 text-dark">Nom complet</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">Nom</label>
                                        <input type="text" class="form-control" id="nom" name="nom"
                                            value="{{ $salarie->nom }}" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">Prénom</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom"
                                            value="{{ $salarie->prenom }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="{{ $salarie->email ?? '' }}" readonly>
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
                            <div class="mb-3">
                                <label class="h5 text-dark">Raison</label>
                                <textarea class="form-control" id="raison" name="raison" rows="4" required></textarea>
                                <small class="form-text text-muted">Veuillez expliquer la raison de votre demande de
                                    congé</small>
                                <p class="mt-2 mb-2">
                                    La demande de congé sera analysée par la direction. Une réponse vous sera transmise par
                                    courriel quant à l'acceptation ou le refus de la demande de congé.
                                </p>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="acceptTerms" name="acceptTerms">
                                    <label class="form-check-label" for="acceptTerms">J'ai lu et j'accepte</label>
                                </div>
                                <div class="mt-3">
                                    <label class="h5 text-dark">Signature</label>
                                    <canvas id="signaturePad" class="signature-pad"></canvas>
                                    <input type="hidden" name="signature" id="signatureInput">
                                    <div class="signature-buttons">
                                        <button type="button" id="clearSignature"
                                            class="btn btn-secondary btn-sm">Effacer</button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-danger">Aucune information de salarié trouvée pour cet utilisateur.</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Soumettre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: rgba(0, 0, 0, 0.5); border: none; box-shadow: none;">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-white">Enregistrement en cours...</p>
                </div>
            </div>
        </div>
    </div>
    <script>
    const companySettings = @json($companySettings ?? []);
    console.log('Company Settings:', companySettings); // Pour déboguer
</script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
    <script src="{{ asset('assets/js/conges.js') }}"></script>
@endsection
