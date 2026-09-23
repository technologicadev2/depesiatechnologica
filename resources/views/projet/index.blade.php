@extends('master_page.app')

@section('title')
    Liste des Projets
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Liste des Projets</h4>
    <link rel="stylesheet" href="{{ asset('assets/css/projets.css') }}">

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-basic table table-striped table-hover border-top" id="projetsTable"
                data-projets-list-url="{{ route('projets.list') }}" data-projets-base-url="{{ url('projets') }}">
                <thead>
                    <tr>
                        <th></th>
                        <th>Référence Marché</th>
                        <th>Numéro Projet</th>
                        <th>Ordre de Service</th>
                        <th>Date Fin</th>
                        <th>Type Projet</th>
                        <th>Type Commande</th>
                        <th>Budget</th>
                        <th>Documents</th>
                        <th class="cell-fit">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projets as $projet)
                        <tr>
                            <td></td>
                            <td>{{ $projet->intitule ?? '-' }}</td>
                            <td>{{ $projet->num_p ?? '-' }}</td>
                            <td>{{ $projet->date_debut ?? '-' }}</td>
                            <td>{{ $projet->date_fin ?? '-' }}</td>
                            <td>{{ $projet->type_projet ?? '-' }}</td>
                            <td>{{ $projet->commande_type ?? '-' }}</td>
                            <td>{{ $projet->budget ?? '-' }}</td>
                            <td>
                                <a href="javascript:;" class="text-body upload-documents" data-id="{{ $projet->id }}"
                                    data-bs-toggle="tooltip" title="Gérer les documents">
                                    <i class="bx bx-folder-open mx-1"></i>
                                </a>
                                @if ($projet->type_projet === 'Privé' || $projet->dossier_complete)
                                    <a href="{{ $projet->type_projet === 'Privé' ? route('projets.download-private-dossier', $projet->id) : route('projets.download-dossier', $projet->id) }}"
                                        class="text-success ms-2 download-dossier" data-id="{{ $projet->id }}"
                                        data-commande-type="{{ $projet->type_projet === 'Privé' ? 'Private' : $projet->commande_type ?? 'Marche' }}"
                                        data-bs-toggle="tooltip"
                                        title="{{ $projet->type_projet === 'Privé' ? 'Télécharger les documents privés' : 'Télécharger le dossier complet' }}">
                                        <i class="bx bx-download"></i>
                                    </a>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if (!$projet->cloture)
                                        <a href="javascript:;" class="text-body cloturer-projet"
                                            data-id="{{ $projet->id }}" data-bs-toggle="tooltip" title="Clôturer">
                                            <i class="bx bx-lock-open mx-1"></i>
                                        </a>
                                    @else
                                        <i class="bx bx-lock mx-1 text-secondary" title="Projet clôturé"></i>
                                    @endif
                                    <a href="javascript:;" class="text-body gérer-ordres-service"
                                        data-id="{{ $projet->id }}" data-bs-toggle="tooltip"
                                        title="Gérer les ordres de service">
                                        <i class="bx bx-list-ul mx-1"></i>
                                    </a>
                                    <a href="javascript:;" class="text-body edit-projet" data-id="{{ $projet->id }}"
                                        data-type="{{ $projet->type_projet }}" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bx bx-edit mx-1"></i>
                                    </a>
                                    <a href="javascript:;" class="text-body delete-record" data-id="{{ $projet->id }}"
                                        data-bs-toggle="tooltip" title="Supprimer">
                                        <i class="bx bx-trash mx-1"></i>
                                    </a>
                                    <a href="javascript:;" class="text-body show-projet"
                                        data-attr="{{ route('projets.show', $projet->id) }}" data-bs-toggle="tooltip"
                                        title="Voir">
                                        <i class="bx bx-show mx-1"></i>
                                    </a>
                                    <a href="javascript:;" class="text-body print-projet" data-id="{{ $projet->id }}"
                                        data-bs-toggle="tooltip" title="Imprimer">
                                        <i class="bx bx-printer mx-1"></i>
                                    </a>
                                    <a href="javascript:;" class="text-body affecter-projet" data-id="{{ $projet->id }}"
                                        data-bs-toggle="tooltip" title="Affecter">
                                        <i
                                            class="bx bx-user-plus mx-1 {{ !$projet->has_affectation ? 'affectation-icon-red' : '' }}"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">Aucun projet trouvé.</td> <!-- Updated colspan -->
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal fade" id="setExitDateModal" tabindex="-1" aria-labelledby="setExitDateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="setExitDateModalLabel">Définir la Date de Sortie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="exit-date-form">
                        <input type="hidden" id="exit_projet_id" name="projet_id">
                        <input type="hidden" id="exit_salarie_id" name="salarie_id">
                        <div class="mb-3">
                            <label for="date_sortie" class="form-label">Date de Sortie</label>
                            <input type="date" class="form-control" id="date_sortie" name="date_sortie">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="submitExitDate">Confirmer</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal pour ajouter un projet privé -->
    <div class="modal fade" id="addProjetModal" tabindex="-1" aria-labelledby="addProjetModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProjetModalLabel">Ajouter un Projet Privé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="create-projet-form" name="create-projet-form" method="POST"
                        action="{{ route('projets.store') }}">
                        @csrf
                        <div class="row g-3">
                            <!-- Intitulé and Numéro Projet -->
                            <div class="col-12 col-sm-6">
                                <label for="intitule" class="form-label">Intitulé <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="intitule" class="form-control" id="intitule" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            {{-- <div class="col-12 col-sm-6">
                                <label for="num_p" class="form-label">Numéro Projet</label>
                                <div class="input-group">
                                    <input type="text" name="num_p" class="form-control" id="num_p" />
                                    <div class="input-group-text">
                                        <input type="checkbox" id="manual_num_p" name="manual_num_p"
                                            title="Saisir manuellement">
                                    </div>
                                </div>
                                <div class="text-danger mt-1" id="num_p-error"></div>
                            </div> --}}
                            <!-- Date Début and Date Fin -->
                            <div class="col-12 col-sm-6">
                                <label for="date_debut" class="form-label">Ordre de Service <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="date_debut" class="form-control" id="date_debut" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="date_fin" class="form-label">Date Fin
                                </label>
                                <input type="date" name="date_fin" class="form-control" id="date_fin" />

                            </div>
                            <!-- Budget and Type Projet -->
                            <div class="col-12 col-sm-6">
                                <label for="budget" class="form-label">Montant  <span
                                        class="text-danger">*</span> </label>
                                <input type="number" name="budget" class="form-control" id="budget" step="0.01"
                                    min="0" />
                                     <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            {{-- <div class="col-12 col-sm-6">
                                <label for="type_projet" class="form-label">Type Projet</label>
                                <select name="type_projet" class="form-control" id="type_projet">
                                    <option value="">Sélectionner...</option>
                                    <option value="Public">Public</option>
                                    <option value="Privé">Privé</option>
                                </select>
                            </div> --}}
                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" class="form-control" id="description" rows="4"></textarea>
                            </div>
                            <div class="form-check">
        <input class="form-check-input" type="checkbox" name="marche_cadre" id="marche_cadre" value="1">
        <label class="form-check-label" for="marche_cadre">
            Marché Cadre
        </label>
    </div>
                            <!-- Hidden Cloture -->
                            <input type="hidden" name="cloture" value="0" id="cloture">
                            <!-- Submit Button -->
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="bx bx-check me-1"></i>
                                    <span class="align-middle">Ajouter</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter un projet public -->
    <div class="modal fade" id="addPublicProjetModal" tabindex="-1" aria-labelledby="addPublicProjetModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPublicProjetModalLabel">Ajouter un Projet Public</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="create-public-projet-form" name="create-public-projet-form" method="POST"
                        action="{{ route('projets.store-public') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label for="public_intitule" class="form-label">Référence Marché <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="intitule" class="form-control" id="public_intitule"
                                    required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <!-- Champ select pour Marché ou Bon de Commande -->
                            <div class="col-12 col-sm-6">
                                <label for="commande_type" class="form-label">Type de Commande <span
                                        class="text-danger">*</span></label>
                                <select name="commande_type" class="form-control" id="commande_type" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="Marche">Marché</option>
                                    <option value="BC">Bon de Commande (BC)</option>
                                </select>
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="date_offre" class="form-label">Date Offre <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="date_offre" class="form-control" id="date_offre" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="date_marche" class="form-label">Date Marché <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="date_marche" class="form-control" id="date_marche"
                                    required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="ville" class="form-label">Ville <span class="text-danger">*</span></label>
                                <input type="text" name="ville" class="form-control" id="ville" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <!-- Maître d'Ouvrage non obligatoire -->
                            <div class="col-12 col-sm-6">
                                <label for="maitre_ouvrage" class="form-label">Maître d'Ouvrage</label>
                                <input type="text" name="maitre_ouvrage" class="form-control" id="maitre_ouvrage" />
                                <div class="invalid-feedback">Ce champ est invalide.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="public_budget" class="form-label">Montant Marché <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="budget" class="form-control" id="public_budget"
                                    step="0.01" min="0" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <!-- Retenue de Garantie -->
                            <div class="col-12 col-sm-6">
                                <label for="rg" class="form-label">Retenue de Garantie</label>
                                <input type="number" name="rg" class="form-control" id="rg" step="0.01"
                                    readonly />
                            </div>
                            <!-- Caution Définitive -->
                            <div class="col-12 col-sm-6">
                                <label for="caution_definitif" class="form-label">Caution Définitive</label>
                                <input type="number" name="caution_definitif" class="form-control"
                                    id="caution_definitif" step="0.01" readonly />
                            </div>
                            <!-- Délai d'Exécution non obligatoire -->
                            <div class="col-12 col-sm-6">
                                <label for="delai_execution" class="form-label">Délai d'Exécution (mois)</label>
                                <input type="number" name="delai_execution" class="form-control" id="delai_execution"
                                    min="1" />
                                <div class="invalid-feedback">Ce champ doit être un nombre positif.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="public_date_debut" class="form-label">Ordre de Service <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="date_debut" class="form-control" id="public_date_debut"
                                    required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="public_date_fin" class="form-label">Date Fin</label>
                                <input type="date" name="date_fin" class="form-control" id="public_date_fin" />
                            </div>


                                                <div class="col-12">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="marche_cadre" id="public_marche_cadre" value="1">
        <label class="form-check-label" for="public_marche_cadre">
            Marché Cadre
        </label>
    </div>
</div>
                            <input type="hidden" name="type_projet" value="Public">
                            <input type="hidden" name="cloture" value="0">
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="bx bx-check me-1"></i>
                                    <span class="align-middle">Ajouter</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<!-- Modal pour gérer les documents -->
<div class="modal fade" id="uploadDocumentsModal" tabindex="-1" aria-labelledby="uploadDocumentsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadDocumentsModalLabel">Gérer les Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="upload-documents-form" name="upload-documents-form" method="POST"
                    enctype="multipart/form-data" action="{{ route('projets.upload-documents') }}">
                    @csrf
                    <input type="hidden" name="projet_id" id="document_projet_id">
                    <div class="row g-3">
                        <!-- Document Marché -->
                        <div class="col-12 document-field" id="marche_document_container">
                            <label for="marche_document" class="form-label">Document Marché (PDF)</label>
                            <input type="file" name="marche_document" class="form-control" id="marche_document"
                                accept=".pdf" />
                            <div class="invalid-feedback">Veuillez sélectionner un fichier PDF.</div>
                        </div>

                        <!-- Ordre de Service -->
                        <div class="col-12 document-field" id="ordre_service_document_container">
                            <label for="ordre_service_document" class="form-label">Ordre de Service (PDF)</label>
                            <input type="file" name="ordre_service_document" class="form-control"
                                id="ordre_service_document" accept=".pdf" />
                            <div class="invalid-feedback">Veuillez sélectionner un fichier PDF valide.</div>
                        </div>

                        <!-- Document Assurance -->
                        <div class="col-12 col-sm-6 document-field" id="assurance_document_container">
                            <label for="assurance_document" class="form-label">Document Assurance (PDF)</label>
                            <input type="file" name="assurance_document" class="form-control"
                                id="assurance_document" accept=".pdf" />
                            <div class="invalid-feedback">Veuillez sélectionner un fichier PDF.</div>
                        </div>

                        <!-- Montant Assurance -->
                        <div class="col-12 col-sm-6 document-field" id="assurance_montant_container">
                            <label for="document_assurance_montant" class="form-label">Montant Assurance</label>
                            <input type="number" name="assurance_montant" class="form-control"
                                id="document_assurance_montant" step="0.01" min="0" />
                            <div class="invalid-feedback">Le montant est requis si un document d'assurance est
                                sélectionné.</div>
                        </div>

                        <!-- Demande de Cautionnement -->
                        <div class="col-12 document-field" id="demande_cautionnement_container">
                            <label for="demande_cautionnement" class="form-label">Demande de Cautionnement
                                (PDF)</label>
                            <input type="file" name="demande_cautionnement" class="form-control"
                                id="demande_cautionnement" accept=".pdf" />
                            <div class="invalid-feedback">Veuillez sélectionner un fichier PDF.</div>
                        </div>

                        <!-- Document Caution Provisoire -->
                        <div class="col-12 col-sm-6 document-field" id="caution_provision_document_container">
                            <label for="caution_provision_document" class="form-label">Document Caution Provisoire
                                (PDF)</label>
                            <input type="file" name="caution_provision_document" class="form-control"
                                id="caution_provision_document" accept=".pdf" />
                            <div class="invalid-feedback">Veuillez sélectionner un fichier PDF.</div>
                        </div>

                        <!-- Montant Caution Provisoire -->
                        <div class="col-12 col-sm-6 document-field" id="caution_provision_container">
                            <label for="document_caution_provision" class="form-label">Montant Caution Provisoire</label>
                            <input type="number" name="caution_provision" class="form-control"
                                id="document_caution_provision" step="0.01" min="0" />
                            <div class="invalid-feedback">Le montant est requis si un document de caution provisoire
                                est sélectionné.</div>
                        </div>

                        <!-- Document Caution Définitive -->
                        <div class="col-12 col-sm-6 document-field" id="caution_definitif_document_container">
                            <label for="caution_definitif_document" class="form-label">Document Caution Définitive
                                (PDF)</label>
                            <input type="file" name="caution_definitif_document" class="form-control"
                                id="caution_definitif_document" accept=".pdf" />
                            <div class="invalid-feedback">Veuillez sélectionner un fichier PDF.</div>
                        </div>

                        <!-- Montant Caution Définitive - ID MODIFIÉ -->
                        <div class="col-12 col-sm-6 document-field" id="caution_definitif_container">
                            <label for="document_caution_definitif" class="form-label">Montant Caution Définitive</label>
                            <input type="number" name="caution_definitif" class="form-control"
                                id="document_caution_definitif" step="0.01" min="0" />
                            <div class="invalid-feedback">Le montant est requis si un document de caution définitive
                                est sélectionné.</div>
                        </div>

                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">
                                <i class="bx bx-check me-1"></i>
                                <span class="align-middle">Enregistrer</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    <!-- Modal pour affichage -->
    <div class="modal fade" id="showProjetModal" tabindex="-1" aria-labelledby="showProjetModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showProjetModalLabel">Détails du Projet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="projet-details">
                    <!-- Les détails seront chargés ici -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour modification -->
    <div class="modal fade" id="editProjetModal" tabindex="-1" aria-labelledby="editProjetModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProjetModalLabel">Modifier un Projet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="edit-projet-form-container">
                    <!-- Form will be loaded dynamically via AJAX -->
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de confirmation d'affectation -->
    <div class="modal fade" id="confirmAffectationModal" tabindex="-1" aria-labelledby="confirmAffectationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmAffectationModalLabel">Affectation du Projet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Voulez-vous affecter ce projet à des salariés ?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Non</button>
                    <button type="button" class="btn btn-primary" id="confirmAffectationBtn">Oui</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'affectation -->
    <div class="modal fade" id="affectationModal" tabindex="-1" aria-labelledby="affectationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="affectationModalLabel">Affectation des Salariés</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="affectation-form">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" id="projet_id" name="projet_id">
                        <div class="row g-3">
                          <div class="col-12">
    <label for="responsable" class="form-label">Responsable <span class="text-danger">*</span></label>
    <div class="form-text mb-2">
        <i class="bx bx-info-circle"></i> Tous les salariés sont disponibles comme responsable.
        Les projets actuels de chaque salarié sont affichés entre crochets.
    </div>
    <select name="responsable" id="responsable" class="form-control select2" required>
        <option value="">Sélectionner un responsable</option>
    </select>
    <div class="invalid-feedback">Veuillez sélectionner un responsable.</div>
</div>

<div class="col-12 mt-3">
    <label for="ouvriers" class="form-label">Salariés</label>
    <div class="form-text mb-2">
        <i class="bx bx-info-circle"></i> Les salariés peuvent être affectés à plusieurs projets simultanément.
        Les projets actuels de chaque salarié sont affichés entre crochets.
    </div>
    <select name="ouvriers[]" id="ouvriers" class="form-control select2" multiple>
        <!-- Options générées dynamiquement -->
    </select>
</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="submitAffectation">Affecter</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour gérer les ordres de service -->
    <div class="modal fade" id="manageOrdresServiceModal" tabindex="-1" aria-labelledby="manageOrdresServiceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageOrdresServiceModalLabel">Gérer les Ordres de Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="ordres-service-form" method="POST" enctype="multipart/form-data"
                        action="{{ route('ordres-service.store') }}">
                        @csrf
                        <input type="hidden" name="projet_id" id="ordres_service_projet_id">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label for="type" class="form-label">Type d'Ordre <span
                                        class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-control" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="arret">Arrêt</option>
                                    <option value="reprise">Reprise</option>
                                </select>
                                <div class="invalid-feedback">Veuillez sélectionner un type.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="date_ordre" class="form-label">Date de l'Ordre <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="date_ordre" class="form-control" id="date_ordre" required />
                                <div class="invalid-feedback">Veuillez sélectionner une date.</div>
                            </div>
                            <div class="col-12">
                                <label for="document_path" class="form-label">Document (PDF)</label>
                                <input type="file" name="document_path" class="form-control" id="document_path"
                                    accept=".pdf" />
                                <div class="invalid-feedback">Veuillez sélectionner un fichier PDF.</div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="bx bx-check me-1"></i>
                                    <span class="align-middle">Ajouter</span>
                                </button>
                            </div>
                        </div>
                    </form>
                    <hr>
                    <div id="ordres-service-stats" class="mb-3">
                        <!-- Stats will be loaded here -->
                    </div>
                    <div id="ordres-service-list" class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 100px;">Type Arrêt</th>
                                            <th style="min-width: 120px;">Date Arrêt</th>
                                            <th style="min-width: 130px;">Document Arrêt</th>
                                            <th style="min-width: 100px;">Type Reprise</th>
                                            <th style="min-width: 120px;">Date Reprise</th>
                                            <th style="min-width: 130px;">Document Reprise</th>
                                            <th style="min-width: 120px;">Nombre de Jours</th>
                                            <th style="min-width: 80px; width: 80px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ordres-service-table-body">
                                        <!-- Liste des paires d'ordres sera chargée ici -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="downloadModalLabel">Téléchargement de documents</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Chargement...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de chargement -->
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
    @include('projet.upload_private_documents')
    <script>
        const companySettings = @json($companySettings ?? []);
        console.log('Company Settings:', companySettings); // Pour déboguer
    </script>
    <script>
        // Script pour gérer la Retenue de Garantie et la Caution Définitive en fonction de commande_type
        document.getElementById('commande_type').addEventListener('change', function() {
            const rgInput = document.getElementById('rg');
            const cautionDefinitifInput = document.getElementById('caution_definitif');
            const budgetInput = document.getElementById('public_budget');
            const rgField = rgInput.closest('.col-12');
            const cautionDefinitifField = cautionDefinitifInput.closest('.col-12');

            if (this.value === 'BC') {
                rgInput.value = 0;
                cautionDefinitifInput.value = 0;
                rgField.style.display = 'none'; // Masquer le champ RG
                cautionDefinitifField.style.display = 'none'; // Masquer le champ Caution Définitive
            } else {
                rgField.style.display = 'block'; // Afficher le champ RG
                cautionDefinitifField.style.display = 'block'; // Afficher le champ Caution Définitive
                if (budgetInput.value) {
                    rgInput.value = (parseFloat(budgetInput.value) * 0.07).toFixed(2);
                    cautionDefinitifInput.value = (parseFloat(budgetInput.value) * 0.03).toFixed(2);
                }
            }
        });

        // Mettre à jour RG et Caution Définitive si le budget change (pour Marché uniquement)
        document.getElementById('public_budget').addEventListener('input', function() {
            const commandeType = document.getElementById('commande_type').value;
            const rgInput = document.getElementById('rg');
            const cautionDefinitifInput = document.getElementById('caution_definitif');
            if (commandeType === 'Marche' && this.value) {
                rgInput.value = (parseFloat(this.value) * 0.07).toFixed(2);
                cautionDefinitifInput.value = (parseFloat(this.value) * 0.03).toFixed(2);
            }
        });
    </script>
    <!-- Dépendances JS -->
    <script src="{{ asset('assets/js/projet.js') }}"></script>
@endsection
