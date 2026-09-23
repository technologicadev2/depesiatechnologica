@extends('master_page.app')

@section('title')
    Liste des Salariés
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <h4 class="fw-bold py-3 mb-4">Liste des Salariés </h4>
    <link rel="stylesheet" href="{{ asset('assets/css/salaries.css') }}">
<!-- Add Select Dropdown for Status Filter -->
    <div class="row mb-3">
        <div class="col-md-4">
            <label for="statusFilter" class="form-label">Filtrer par statut</label>
            <select id="statusFilter" class="form-control">
                <option value="actif">Salarié Actif</option>
                <option value="inactif">Salarié Démissionné</option>
            </select>
        </div>
    </div>
<div class="card">
    <div class="card-datatable table-responsive">
        <table class="datatables-basic table table-striped table-hover border-top" id="salariesTable"
            data-salaries-list-url="{{ route('salaries.list') }}" data-salaries-base-url="{{ url('salaries') }}">
            <thead>
                <tr>
                    <th></th> <!-- Colonne vide -->
                    <th>Matricule Entreprise</th>
                    <th>Nom</th> <!-- Ajout explicite pour filtre -->
                    <th>Prénom</th>
                    <th>CIN</th>
                    <th>Téléphone</th>
                    <th>Adresse</th>
                    <th>Fonction</th>
                    <th class="cell-fit">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($salaries as $salarie)
                    <tr>
                        <td></td>
                        <td>{{ $salarie->n_matricule_entreprise ?? '-' }}</td>
                        <td>{{ $salarie->nom ?? '-' }}</td>
                        <td>{{ $salarie->prenom ?? '-' }}</td>
                        <td>{{ $salarie->cin ?? '-' }}</td>
                        <td>{{ $salarie->phone ?? '-' }}</td>
                        <td>{{ $salarie->adresse ?? '-' }}</td>
                        <td>{{ $salarie->fonction ? $salarie->fonction->designation : 'N/A' }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if ($salarie->statut === 'inactif')
                                    <a href="javascript:;" class="text-body reactivate-salarie" data-id="{{ $salarie->id }}"
                                        data-bs-toggle="tooltip" title="Réactiver"><i class="bx bx-undo mx-1"></i></a>
                                @else
                                    <a href="javascript:;" class="text-body edit-salarie" data-id="{{ $salarie->id }}"
                                        data-bs-toggle="tooltip" title="Modifier"><i class="bx bx-edit mx-1"></i></a>
                                    <a href="javascript:;" class="text-body delete-record" data-id="{{ $salarie->id }}"
                                        data-bs-toggle="tooltip" title="Supprimer"><i class="bx bx-trash mx-1"></i></a>
                                    <a href="javascript:;" class="text-body show-salarie"
                                        data-attr="{{ route('salaries.show', $salarie->id) }}" data-bs-toggle="tooltip"
                                        title="Voir"><i class="bx bx-show mx-1"></i></a>
                                    <a href="javascript:;" class="text-body print-salarie" data-id="{{ $salarie->id }}"
                                        data-bs-toggle="tooltip" title="Imprimer"><i class="bx bx-printer mx-1"></i></a>
                                    <a href="javascript:;" class="text-body demission-salarie" data-id="{{ $salarie->id }}"
                                        data-bs-toggle="tooltip" title="Démission"><i class="bx bx-exit mx-1"></i></a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">Aucun salarié trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
    <!-- Modal pour ajouter un salarié -->
    <div class="modal fade" id="addSalarieModal" tabindex="-1" aria-labelledby="addSalarieModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSalarieModalLabel">Ajouter un Salarié</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
              <div class="modal-body">
        <div class="bs-stepper wizard-vertical">
            <div class="bs-stepper-header" role="tablist">
                <div class="step active" data-target="#personal-info">
                    <button type="button" class="step-trigger" role="tab" aria-selected="true">
                        <span class="bs-stepper-circle"><i class="bx bx-user"></i></span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">Informations Personnelles</span>
                            <span class="bs-stepper-subtitle">Détails du salarié</span>
                        </span>
                    </button>
                </div>
                <div class="step" data-target="#employment-details">
                    <button type="button" class="step-trigger" role="tab" aria-selected="false">
                        <span class="bs-stepper-circle"><i class="bx bx-briefcase"></i></span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">Détails de l'Emploi</span>
                            <span class="bs-stepper-subtitle">Fonction et règlement</span>
                        </span>
                    </button>
                </div>
                <div class="step" data-target="#validation">
                    <button type="button" class="step-trigger" role="tab" aria-selected="false">
                        <span class="bs-stepper-circle"><i class='bx bxs-check-circle'></i></span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">Vérification</span>
                            <span class="bs-stepper-subtitle">Valider les informations</span>
                        </span>
                    </button>
                </div>
            </div>
            <div class="bs-stepper-content">
                <form id="create-salarie-form" name="create-salarie-form" method="POST" action="{{ route('salaries.store') }}">
                    @csrf
                    <!-- Step 1: Personal Info -->
                    <div id="personal-info" class="content active" role="tabpanel">
                        <div class="content-header mb-3">
                            <h6 class="mb-0">Informations Personnelles</h6>
                            <small>Entrez les détails personnels du salarié.</small>
                            <div id="form-errors" class="alert alert-danger d-none"></div>
                            <p><strong>Nombre de salariés existants :</strong> <span id="salarie-count">{{ \App\Models\Salarie::count() }}</span></p>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control" id="nom" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" name="prenom" class="form-control" id="prenom" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            
                            <div class="col-12 col-sm-6">
                                <label for="cin" class="form-label">CIN <span class="text-danger">*</span></label>
                                <input type="text" name="cin" class="form-control" id="cin" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                                <div class="text-danger mt-1" id="cin-error"></div>
                            </div>
                                            <div class="col-12 col-sm-6">
    <label for="cin_piece_jointe" class="form-label">Pièce jointe CIN</label>
    <input type="file" name="cin_piece_jointe" class="form-control" id="cin_piece_jointe" accept="image/jpeg,image/png,image/jpg,.pdf" />
 
    <div class="invalid-feedback">Fichier invalide.</div>
</div>

<div class="col-12 col-sm-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" id="email" />
                                <div class="invalid-feedback">Veuillez entrer un email valide.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="date_naissance" class="form-label">Date de Naissance <span class="text-danger">*</span></label>
                                <input type="date" name="date_naissance" class="form-control" id="date_naissance" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="photo" class="form-label">Photo</label>
                                <input type="file" name="photo" class="form-control" id="photo" accept="image/*" />
                                <div class="invalid-feedback">Veuillez sélectionner une image valide.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="situation_familiale" class="form-label">Situation Familiale<span class="text-danger">*</span></label>
                                <select name="situation_familiale" class="form-control" id="situation_familiale" required>
                                    <option value="" selected>-- Sélectionner --</option>
                                    <option value="Célibataire">Célibataire</option>
                                    <option value="Marié">Marié</option>
                                    <option value="Divorcé">Divorcé</option>
                                    <option value="Veuf">Veuf</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="nombre_enfant" class="form-label">Nombre d'Enfants</label>
                                <input type="number" name="nombre_enfant" class="form-control" id="nombre_enfant" value="0" min="0" />
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="text" name="phone" class="form-control" id="phone" pattern="[0-9]{10}" />
                                <div class="invalid-feedback">Veuillez entrer un numéro de téléphone valide (10 chiffres).</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="adresse" class="form-label">Adresse</label>
                                <textarea name="adresse" class="form-control" id="adresse" rows="4">{{ old('adresse') }}</textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <button class="btn btn-label-secondary btn-prev" disabled>
                                    <i class="bx bx-chevron-left bx-sm ms-sm-n2"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Précédent</span>
                                </button>
                                <button class="btn btn-primary btn-next">
                                    <span class="align-middle d-sm-inline-block d-none me-sm-1">Suivant</span>
                                    <i class="bx bx-chevron-right bx-sm me-sm-n2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Employment Details -->
                    <div id="employment-details" class="content" role="tabpanel">
                        <div class="content-header mb-3">
                            <h6 class="mb-0">Détails de l'Emploi</h6>
                            <small>Entrez les informations relatives à l'emploi.</small>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label for="n_matricule_cnss" class="form-label">Matricule CNSS <span class="text-danger">*</span></label>
                                <input type="text" name="n_matricule_cnss" class="form-control" id="n_matricule_cnss" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                                <div class="text-danger mt-1" id="n_matricule_cnss-error"></div>
                            </div>
                            <div class="col-12 col-sm-6">
    <label class="form-label">Matricule Entreprise <span class="text-danger">*</span></label>
    <div class="input-group">
        <input type="text" name="n_matricule_entreprise" id="n_matricule_entreprise" class="form-control" required />
        <button type="button" class="btn btn-primary" id="generateMatriculeBtn">Générer</button>
    </div>
    <div class="text-danger mt-1" id="n_matricule_entreprise-error"></div>
</div>
                            <div class="col-12 col-sm-6">
                                <label for="fonction_id" class="form-label">Fonction <span class="text-danger">*</span></label>
                                <select name="fonction_id" class="form-control" id="fonction_id" required>
                                    <option value="">-- Sélectionner --</option>
                                    @foreach ($fonctions as $fonction)
                                        <option value="{{ $fonction->id }}">{{ $fonction->designation }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="reglement_id" class="form-label">Type de Règlement</label>
                                <select name="reglement_id" class="form-control" id="reglement_id">
                                    <option value="">-- Sélectionner --</option>
                                    @foreach ($typeReglements as $typeReglement)
                                        <option value="{{ $typeReglement->id }}" data-designation="{{ $typeReglement->designation }}">{{ $typeReglement->designation }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6" id="rib-container" style="display: none;">
                                <label for="rib" class="form-label">RIB <span class="text-danger">*</span></label>
                                <input type="text" name="rib" class="form-control" id="rib" placeholder="Entrez le RIB" />
                                <div class="invalid-feedback">Le RIB est obligatoire pour le virement.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="type_travail" class="form-label">Type de Travail <span class="text-danger">*</span></label>
                                <select name="type_travail" class="form-control" id="type_travail" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="permanent">Permanent</option>
                                    <option value="occasionnel">Occasionnel</option>
                                </select>
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
                           
                            <div class="col-12 col-sm-6">
                                <label for="type_contrat" class="form-label">Type de Contrat <span class="text-danger">*</span></label>
                                <select name="type_contrat" class="form-control" id="type_contrat" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="CDI">CDI</option>
                                    <option value="CDD">CDD</option>
                                    <option value="Freelance">Freelance</option>
                                    <option value="Anapec">Anapec</option>
                                </select>
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            </div>
<div class="col-12 col-sm-6">
    <label for="salaire_net" class="form-label">Salaire net <span class="text-danger salaire-net-star">*</span></label>
    <input type="text" name="salaire_net" class="form-control" id="salaire_net" />
    <div class="invalid-feedback">Ce champ est obligatoire.</div>
    <div class="text-danger mt-1" id="salaire_net-error"></div>
    <small class="text-muted">Remplissez ceci OU (Salaire de base + Salaire journalier) ci-dessous.</small>
</div>

                                                <div class="col-12 col-sm-6">
                            <div class="form-check mt-5">
                                <input type="checkbox" name="auto_salary_calc" class="form-check-input" id="auto_salary_calc" checked />
                                <label for="auto_salary_calc" class="form-check-label">Calculer automatiquement les salaires</label>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
    <label for="salaire_base" class="form-label">Salaire de Base <span class="text-danger salaire-base-star">*</span></label>
    <input type="number" name="salaire_base" class="form-control" id="salaire_base" step="0.01" min="0" />
    <div class="invalid-feedback">Le salaire de base doit être un nombre positif.</div>
    <div class="text-danger mt-1" id="salaire_base-error"></div>
</div>
                       <div class="col-12 col-sm-6">
    <label for="salaire_journalier" class="form-label">Salaire journalier <span class="text-danger salaire-journalier-star">*</span></label>
    <input type="number" name="salaire_journalier" class="form-control" id="salaire_journalier" step="0.01" min="0" />
    <div class="invalid-feedback">Le salaire journalier doit être un nombre positif.</div>
    <div class="text-danger mt-1" id="salaire_journalier-error"></div>
</div>
                            <div class="col-12 col-sm-6">
                                <label for="contrat" class="form-label">Contrat</label>
                                <input type="file" name="contrat" class="form-control" id="contrat" accept=".pdf" />
                                <div class="invalid-feedback">Veuillez sélectionner un fichier PDF valide.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="date_embauche" class="form-label">Date d'embauche <span class="text-danger">*</span></label>
                                <input type="date" name="date_embauche" class="form-control" id="date_embauche" required />
                                <div class="invalid-feedback">Ce champ est obligatoire.</div>
                                <div class="text-danger mt-1" id="date_embauche-error"></div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-check">
                                    <input type="checkbox" name="calculer_anciennete" class="form-check-input" id="calculer_anciennete" />
                                    <label for="calculer_anciennete" class="form-check-label">Calculer l'ancienneté automatiquement</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <button class="btn btn-primary btn-prev">
                                    <i class="bx bx-chevron-left bx-sm ms-sm-n2"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Précédent</span>
                                </button>
                                <button class="btn btn-primary btn-next">
                                    <span class="align-middle d-sm-inline-block d-none me-sm-1">Suivant</span>
                                    <i class="bx bx-chevron-right bx-sm me-sm-n2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                   <!-- Step 3: Validation -->
                    <div id="validation" class="content" role="tabpanel">
                        <div class="content-header mb-3">
                            <h6 class="mb-0">Vérification</h6>
                            <small>Vérifiez les informations avant de soumettre.</small>
                            <div id="message"></div>
                        </div>
                        <div class="row g-3">
<div id="summary-section" class="mt-2 col-12">
    <h6 class="mb-3">Récapitulatif des Informations</h6>
    <div class="row">
        <div class="col-12 col-md-6">
            <p><strong>Photo:</strong> <span id="photo">-</span></p>
            <p><strong>Nom:</strong> <span id="nom">-</span></p>
            <p><strong>Prénom:</strong> <span id="prenom">-</span></p>
            <p><strong>Email:</strong> <span id="email">-</span></p>
            <p><strong>CIN:</strong> <span id="cin">-</span></p>
            <p><strong>Téléphone:</strong> <span id="phone">-</span></p>
            <p><strong>Date de Naissance:</strong> <span id="date_naissance">-</span></p>
        </div>
        <div class="col-12 col-md-6">
            <p><strong>Adresse:</strong> <span id="adresse">-</span></p>
            <p><strong>Situation Familiale:</strong> <span id="situation_familiale">-</span></p>
            <p><strong>Nombre d'Enfants:</strong> <span id="nombre_enfant">-</span></p>
            <p><strong>Matricule CNSS:</strong> <span id="n_matricule_cnss">-</span></p>
            <p><strong>Matricule Entreprise:</strong> <span id="n_matricule_entreprise">-</span></p>
            <p><strong>Type de Travail:</strong> <span id="type_travail">-</span></p>
            <p><strong>Type de Contrat:</strong> <span id="type_contrat">-</span></p>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-md-6">
            <p><strong>Fonction:</strong> <span id="fonction">-</span></p>
            <p><strong>Contrat:</strong> <span id="contrat">-</span></p>
            <p><strong>Salaire Net:</strong> <span id="salaire_net">-</span></p>
            <p><strong>Salaire de Base:</strong> <span id="salaire_base">-</span></p>
            <p><strong>Salaire Journalier:</strong> <span id="salaire_journalier">-</span></p>
        </div>
        <div class="col-12 col-md-6">
            <p><strong>Type de Règlement:</strong> <span id="reglement_id">-</span></p>
            <p><strong>RIB:</strong> <span id="rib">-</span></p>
            <p><strong>Date d'embauche:</strong> <span id="date_embauche">-</span></p>
        </div>
    </div>
</div></div>
                            <div class="col-12 d-flex justify-content-between">
                                <button class="btn btn-primary btn-prev">
                                    <i class="bx bx-chevron-left bx-sm ms-sm-n2"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Précédent</span>
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bx bx-check me-1"></i>
                                    <span class="align-middle">Ajouter</span>
                                </button>
                            </div>
                        </div>
                    </div>
                           
                </form>
            </div>
    </div>
</div>

            </div>
        </div>
    </div>
    <!-- Modal pour modification -->
    <div class="modal fade" id="editSalarieModal" tabindex="-1" aria-labelledby="editSalarieModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content"></div>
        </div>
    </div>
    <!-- Modal pour affichage -->
    <div class="modal fade" id="showSalarieModal" tabindex="-1" aria-labelledby="showSalarieModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showSalarieModalLabel">Détails du Salarié</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="salarie-details">
                    <!-- Les détails seront chargés ici -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
<!-- Modal pour réactiver un salarié -->
<div class="modal fade" id="reactivateSalarieModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Réactiver un salarié</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reactivate-salarie-form">
                    @csrf
                    <input type="hidden" id="reactivate_salarie_id" name="salarie_id">
                    <div class="invalid-feedback d-block" id="reactivate_salarie_id_error"></div>
                    <div class="mb-3">
                        <label for="reactivate_date_embauche" class="form-label">Date d'embauche (facultative)</label>
                        <input type="date" class="form-control" id="reactivate_date_embauche" name="date_embauche">
                        <div class="invalid-feedback">Veuillez entrer une date valide.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nouveau Matricule Entreprise <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="n_matricule_entreprise" id="reactivate_n_matricule_entreprise" class="form-control" required />
                            <button type="button" class="btn btn-primary" id="reactivate_generateMatriculeBtn">Générer</button>
                        </div>
                        <div class="invalid-feedback">Le matricule entreprise est requis.</div>
                        <div class="text-danger mt-1" id="reactivate_n_matricule_entreprise-error"></div>
                    </div>
                    <p><strong>Note :</strong> Un nouveau salarié sera créé avec les informations originales.</p>
                    <button type="submit" class="btn btn-primary">Créer nouveau salarié</button>
                </form>
            </div>
        </div>
    </div>
</div>
    <!-- Inclure le modal de démission -->
    @include('salaries.demission')
    @include('salaries.preavis_modal')
       <script>
        const companySettings = @json($companySettings ?? []);
        console.log('Company Settings:', companySettings); // Pour déboguer
    </script>
    <!-- Dépendances JS -->
    <script src="{{ asset('assets/js/salarie.js') }}"></script>

@endsection