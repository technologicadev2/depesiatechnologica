@extends('master_page.app')

@section('title')
    Liste des Projets Publics - Décomptes et Paiements
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Liste des Projets Publics - Gestion des Décomptes et Paiements</h4>
    <link rel="stylesheet" href="{{ asset('assets/css/projets.css') }}">

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs" id="projectTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="paiements-tab" data-bs-toggle="tab" href="#paiements" role="tab"
                aria-controls="paiements" aria-selected="true">Décomptes (Marché)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="decomptes-tab" data-bs-toggle="tab" href="#decomptes" role="tab"
                aria-controls="decomptes" aria-selected="false">Paiements (BC)</a>
        </li>
    </ul>

    <div class="tab-content" id="projectTabsContent">
        <!-- Décomptes Tab -->
        <div class="tab-pane fade show active" id="paiements" role="tabpanel" aria-labelledby="paiements-tab">
            <div class="card">
                <div class="card-datatable table-responsive">
                    <table class="datatables-basic table table-striped table-hover border-top" id="paiementsTable"
                        data-projects-list-url="{{ route('decomptes.public.list') }}?commande_type=Marche">
                        <thead>
                            <tr>
                                <th class="details-control"></th>
                                <th>Référence Marché</th>
                                <th>Total Décompte</th>
                                <th>Retenue de Garantie (RG)</th>
                                <th>Travaux Exécutés</th>
                                <th class="bg-green">RG Restant</th>
                                <th class="bg-green">Montant Décompte Restant</th>
                                <th>Pourcentage</th>
                                <th class="cell-fit">Actions</th>
                                <th class="cell-fit">Impression</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Paiements Tab -->
        <div class="tab-pane fade" id="decomptes" role="tabpanel" aria-labelledby="decomptes-tab">
            <div class="card">
                <div class="card-datatable table-responsive">
                <table class="datatables-basic table table-striped table-hover border-top" id="decomptesTable"
    data-projects-list-url="{{ route('decomptes.public.list') }}?commande_type=BC">
    <thead>
        <tr>
            <th class="details-control"></th>
            <th>Référence BC</th>
            <th>Total Paiement</th>
            <th>Travaux Exécutés</th>
            <th class="bg-green">Montant Paiement Restant</th>
            <th class="cell-fit">Actions</th>
            <th class="cell-fit">Impression</th>
        </tr>
    </thead>
    <tbody>
        <!-- Data loaded via AJAX -->
    </tbody>
</table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter un décompte/paiement -->


<div class="modal fade" id="addDecompteModal" tabindex="-1" aria-labelledby="addDecompteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDecompteModalLabel"><span id="modal-action">Ajouter</span> un <span id="modal-title-term">Décompte</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               <!--  <div class="alert alert-info" role="alert">
                    <p><strong>Retenue de Garantie Restante :</strong> <span id="rg_restant_display">-</span></p>
                    <p><strong>Montant <span id="montant-term">Décompte</span> Restant :</strong> <span id="montant_decompte_restant_display">-</span></p>
                </div> -->
                <form id="create-decompte-form" name="create-decompte-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" id="form_method" value="POST"> <!-- Toggle POST/PUT -->
                    <input type="hidden" name="projet_id" id="decompte_projet_id">
                    <input type="hidden" name="commande_type" id="commande_type" value="">
                    <input type="hidden" name="decompte_id" id="decompte_id"> <!-- Store décompte ID for editing -->
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="type_decompte" class="form-label">Type de <span id="type-term">Décompte</span> <span class="text-danger">*</span></label>
                            <select name="type_decompte" class="form-select" id="type_decompte" required>
                                <option value="" disabled selected>Sélectionnez un type</option>
                                <option value="Provisoire">Provisoire</option>
                                <option value="Définitif">Définitif</option>
                            </select>
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="montant_dp" class="form-label">Montant <span id="montant-dp-term">Décompte</span> <span class="text-danger">*</span></label>
                            <input type="number" name="montant_dp" class="form-control" id="montant_dp" step="0.01" min="0" required />
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                        </div>
                        <div class="col-12 col-sm-6" id="rg_dp_container">
                            <label for="rg_dp" class="form-label">Retenue de Garantie <span id="rg-term">Décompte</span></label>
                            <input type="number" name="rg_dp" class="form-control" id="rg_dp" step="0.01" min="0" />
                            <div class="invalid-feedback">Ce champ doit être un nombre positif.</div>
                        </div>
                                         <div class="col-12 col-sm-6">
    <label for="revision_prix" class="form-label">Révision des Prix</label>
    <input 
        type="number" 
        name="revision_prix" 
        class="form-control" 
        id="revision_prix" 
        step="0.01" 
    
    />
    <div class="invalid-feedback">Veuillez entrer un nombre valide.</div>
</div>
                        <div class="col-12 col-sm-6">
                            <label for="date_dp" class="form-label">Date <span id="date-term">Décompte</span> <span class="text-danger">*</span></label>
                            <input type="date" name="date_dp" class="form-control" id="date_dp" required />
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="document" class="form-label">Document (PDF, DOC, DOCX)</label>
                            <input type="file" name="document" class="form-control" id="document" accept=".pdf,.doc,.docx" />
                            <div class="invalid-feedback">Veuillez sélectionner un fichier valide (PDF, DOC, DOCX, max 10MB).</div>
                        </div>
<div class="col-12 col-sm-6" id="tranche_container" style="display: none;">
    <label for="tranche" class="form-label">Tranche <span class="text-danger">*</span></label>
    <select name="tranche" class="form-select" id="tranche">
        <option value="" disabled selected>Sélectionnez une tranche</option>
        <option value="1">Tranche 1</option>
        <option value="2">Tranche 2</option>
        <option value="3">Tranche 3</option>
    </select>
    <div class="invalid-feedback">La tranche est obligatoire pour un marché cadre.</div>
</div>

                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-success" id="submit-decompte-btn">
                                <i class="bx bx-check me-1"></i>
                                <span class="align-middle" id="submit-btn-text">Ajouter</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- Modal pour voir les décomptes/paiements -->
    <div class="modal fade" id="viewDecomptesModal" tabindex="-1" aria-labelledby="viewDecomptesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDecomptesModalLabel">Détails du Projet et <span
                            id="view-modal-title-term">Décomptes</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="project-details">
                        <h6>Informations du Projet</h6>
                        <table class="table table-bordered">
                            <tbody id="project-details-body">
                                <!-- Project details will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div class="jours-arret mt-3">
                        <h6>Jours d'Arrêt</h6>
                        <p id="jours-arret-text">Nombre de jours d'arrêt : <span id="jour_d_arret">0</span></p>
                    </div>
                    <div class="decomptes mt-3">
                        <h6>Liste des <span id="decomptes-table-term">Décomptes</span></h6>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Montant <span id="montant-table-term">Décompte</span></th>
                                        <th>Retenue de Garantie</th>
                                        <th>Révision des Prix</th>
                                        <th>Date <span id="date-table-term">Décompte</span></th>
                                        <th>Document</th>
                                    </tr>
                                </thead>
                                <tbody id="decomptes-table-body">
                                    <!-- Décomptes will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour l'impression -->
    <div class="modal fade" id="printDecompteModal" tabindex="-1" aria-labelledby="printDecompteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="printDecompteModalLabel">Aperçu de l'Impression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="printIframe" style="width: 100%; height: 500px; border: none;" src=""></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="printIframeBtn">
                        <i class="bx bx-printer me-1"></i> Imprimer
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Passage URL pour JavaScript -->
    <script>
        var decompteListUrl = "{{ route('decomptes.list') }}";
        const companySettings = @json($companySettings ?? []);
        console.log('Company Settings:', companySettings);
    </script>

    <!-- Dépendances JS -->
    <script src="{{ asset('assets/js/decompte.js') }}"></script>
@endsection
