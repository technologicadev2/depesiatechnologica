<div class="modal-header">
    <h5 class="modal-title" id="editSalarieModalLabel">Modifier un Salarié</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="bs-stepper wizard-vertical" id="edit-stepper">
        <div class="bs-stepper-header" role="tablist">
            <div class="step active" data-target="#personal-info-edit">
                <button type="button" class="step-trigger" role="tab" aria-controls="personal-info-edit"
                    aria-selected="true">
                    <span class="bs-stepper-circle"><i class="bx bx-user"></i></span>
                    <span class="bs-stepper-label">
                        <span class="bs-stepper-title">Informations Personnelles</span>
                        <span class="bs-stepper-subtitle">Détails du salarié</span>
                    </span>
                </button>
            </div>
            <div class="step" data-target="#employment-details-edit">
                <button type="button" class="step-trigger" role="tab" aria-controls="employment-details-edit"
                    aria-selected="false">
                    <span class="bs-stepper-circle"><i class="bx bx-briefcase"></i></span>
                    <span class="bs-stepper-label">
                        <span class="bs-stepper-title">Détails de l'Emploi</span>
                        <span class="bs-stepper-subtitle">Fonction et règlement</span>
                    </span>
                </button>
            </div>
            <div class="step" data-target="#validation-edit">
                <button type="button" class="step-trigger" role="tab" aria-controls="validation-edit"
                    aria-selected="false">
                    <span class="bs-stepper-circle"><i class='bx bxs-check-circle'></i></span>
                    <span class="bs-stepper-label">
                        <span class="bs-stepper-title">Vérification</span>
                        <span class="bs-stepper-subtitle">Valider les informations</span>
                    </span>
                </button>
            </div>
        </div>
        <div class="bs-stepper-content">
            <form id="update-salarie-form" method="POST" action="{{ route('salaries.update', $salarie->id) }}"
                data-id="{{ $salarie->id }}" data-photo="{{ $salarie->photo }}" data-contrat="{{ $salarie->contrat }}">
                @csrf
                @method('PUT')
                <!-- Step 1: Personal Info -->
                <div id="personal-info-edit" class="content active" role="tabpanel">
                    <div class="content-header mb-3">
                        <h6 class="mb-0">Informations Personnelles</h6>
                        <small>Modifiez les détails personnels du salarié.</small>
                        <div id="form-errors" class="alert alert-danger d-none"></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" id="nom"
                                value="{{ $salarie->nom }}" required />
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            <div class="text-danger mt-1" id="nom-error"></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control" id="prenom"
                                value="{{ $salarie->prenom }}" required />
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            <div class="text-danger mt-1" id="prenom-error"></div>
                        </div>
                 
                        <div class="col-12 col-sm-6">
                            <label for="cin" class="form-label">CIN <span class="text-danger">*</span></label>
                            <input type="text" name="cin" class="form-control" id="cin"
                                value="{{ $salarie->cin }}" required />
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            <div class="text-danger mt-1" id="cin-error"></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="cin_piece_jointe" class="form-label">Pièce jointe CIN</label>
                            <input type="file" name="cin_piece_jointe" class="form-control" id="cin_piece_jointe"
                                accept="image/jpeg,image/png,image/jpg,.pdf" />
                            <div class="invalid-feedback">Fichier invalide.</div>

                            

                            @if ($salarie->cin_piece_jointe)
                                <small class="d-block mt-1">
                                    Fichier actuel :
                                    <a href="{{ url($salarie->cin_piece_jointe) }}" target="_blank">Voir / Télécharger</a>
                                </small>
                            @endif
                        </div>
                        <div class="col-12 col-sm-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" id="email" />
                                <div class="invalid-feedback">Veuillez entrer un email valide.</div>
                            </div>
                        <div class="col-12 col-sm-6">
                            <label for="date_naissance" class="form-label">Date de Naissance <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="date_naissance" class="form-control" id="date_naissance"
                                value="{{ $salarie->date_naissance }}" required />
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            <div class="text-danger mt-1" id="date_naissance-error"></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="photo" class="form-label">Photo</label>
                            <input type="file" name="photo" class="form-control" id="photo"
                                accept="image/*" />
                            <div class="invalid-feedback">Veuillez sélectionner une image valide.</div>
                            @if ($salarie->photo)
                                <p>Photo actuelle : <a href="{{ asset($salarie->photo) }}" target="_blank">Voir</a>
                                </p>
                            @endif
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="situation_familiale" class="form-label">Situation Familiale</label>
                            <select name="situation_familiale" class="form-control" id="situation_familiale">
                                <option value="" {{ !$salarie->situation_familiale ? 'selected' : '' }}>--
                                    Sélectionner --</option>
                                <option value="Célibataire"
                                    {{ $salarie->situation_familiale == 'Célibataire' ? 'selected' : '' }}>Célibataire
                                </option>
                                <option value="Marié"
                                    {{ $salarie->situation_familiale == 'Marié' ? 'selected' : '' }}>Marié</option>
                                <option value="Divorcé"
                                    {{ $salarie->situation_familiale == 'Divorcé' ? 'selected' : '' }}>Divorcé</option>
                                <option value="Veuf"
                                    {{ $salarie->situation_familiale == 'Veuf' ? 'selected' : '' }}>Veuf</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="nombre_enfant" class="form-label">Nombre d'Enfants</label>
                            <input type="number" name="nombre_enfant" class="form-control" id="nombre_enfant"
                                value="{{ $salarie->nombre_enfant ?? 0 }}" min="0" />
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="text" name="phone" class="form-control" id="phone"
                                value="{{ $salarie->phone }}" pattern="[0-9]{10}" />
                            <div class="invalid-feedback">Veuillez entrer un numéro de téléphone valide (10 chiffres).
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea name="adresse" class="form-control" id="adresse" rows="4">{{ $salarie->adresse }}</textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-between">
                            <button type="button" class="btn btn-label-secondary btn-prev" disabled>
                                <i class="bx bx-chevron-left bx-sm ms-sm-n2"></i>
                                <span class="align-middle d-sm-inline-block d-none">Précédent</span>
                            </button>
                            <button type="button" class="btn btn-primary btn-next">
                                <span class="align-middle d-sm-inline-block d-none me-sm-1">Suivant</span>
                                <i class="bx bx-chevron-right bx-sm me-sm-n2"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Step 2: Employment Details -->
                <div id="employment-details-edit" class="content" role="tabpanel">
                    <div class="content-header mb-3">
                        <h6 class="mb-0">Détails de l'Emploi</h6>
                        <small>Modifiez les informations relatives à l'emploi.</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="n_matricule_cnss" class="form-label">Matricule CNSS <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="n_matricule_cnss" class="form-control" id="n_matricule_cnss"
                                value="{{ $salarie->n_matricule_cnss }}" required />
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            <div class="text-danger mt-1" id="n_matricule_cnss-error"></div>
                        </div>
                   <div class="col-12 col-sm-6">
                            <label class="form-label">Matricule Entreprise <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="n_matricule_entreprise" id="n_matricule_entreprise"
                                    class="form-control" value="{{ $salarie->n_matricule_entreprise }}" readonly
                                    required />
                                <button type="button" class="btn btn-primary"
                                    id="generateMatriculeBtn">Générer</button>
                            </div>
                            <div class="text-danger mt-1" id="n_matricule_entreprise-error"></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="fonction_id" class="form-label">Fonction <span
                                    class="text-danger">*</span></label>
                            <select name="fonction_id" class="form-control" id="fonction_id" required>
                                <option value="">-- Sélectionner --</option>
                                @foreach ($fonctions as $fonction)
                                    <option value="{{ $fonction->id }}"
                                        {{ $salarie->fonction_id == $fonction->id ? 'selected' : '' }}>
                                        {{ $fonction->designation }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            <div class="text-danger mt-1" id="fonction_id-error"></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="reglement_id" class="form-label">Type de Règlement</label>
                            <select name="reglement_id" class="form-control" id="reglement_id">
                                <option value="">-- Sélectionner --</option>
                                @foreach ($typeReglements as $typeReglement)
                                    <option value="{{ $typeReglement->id }}"
                                        data-designation="{{ $typeReglement->designation }}"
                                        {{ $salarie->reglement_id == $typeReglement->id ? 'selected' : '' }}>
                                        {{ $typeReglement->designation }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6" id="rib-container"
                            style="display: {{ $salarie->reglement && $salarie->reglement->designation === 'Virement' ? 'block' : 'none' }};">
                            <label for="rib" class="form-label">RIB <span class="text-danger">*</span></label>
                            <input type="text" name="rib" class="form-control" id="rib"
                                value="{{ $salarie->rib ?? '' }}" placeholder="Entrez le RIB" />
                            <div class="invalid-feedback">Le RIB est obligatoire pour le virement.</div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="type_travail" class="form-label">Type de Travail <span
                                    class="text-danger">*</span></label>
                            <select name="type_travail" class="form-control" id="type_travail" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="permanent"
                                    {{ $salarie->type_travail == 'permanent' ? 'selected' : '' }}>Permanent</option>
                                <option value="occasionnel"
                                    {{ $salarie->type_travail == 'occasionnel' ? 'selected' : '' }}>Occasionnel
                                </option>
                            </select>
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            <div class="text-danger mt-1" id="type_travail-error"></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="type_contrat" class="form-label">Type de Contrat <span
                                    class="text-danger">*</span></label>
                            <select name="type_contrat" class="form-control" id="type_contrat" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="CDI" {{ $salarie->type_contrat == 'CDI' ? 'selected' : '' }}>CDI
                                </option>
                                <option value="CDD" {{ $salarie->type_contrat == 'CDD' ? 'selected' : '' }}>CDD
                                </option>
                                <option value="Freelance"
                                    {{ $salarie->type_contrat == 'Freelance' ? 'selected' : '' }}>Freelance</option>
                                <option value="Anapec" {{ $salarie->type_contrat == 'Anapec' ? 'selected' : '' }}>
                                    Anapec</option>
                            </select>
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                        </div>
<div class="col-12 col-sm-6">
    <label for="salaire_net" class="form-label">Salaire net <span class="text-danger">*</span></label>
    <input type="text" name="salaire_net" class="form-control" id="salaire_net"
        value="{{ old('salaire_net', $salarie->salaire_net) }}" required />
    <div class="invalid-feedback">Ce champ est obligatoire.</div>
    <div class="text-danger mt-1" id="salaire_net-error"></div>
</div>

                                <!-- Ajouter avant le champ salaire_base -->
<div class="col-12 col-sm-6">
    <div class="form-check">
        <input type="checkbox" name="auto_salary_calc" class="form-check-input" id="auto_salary_calc" {{ old('auto_salary_calc', $salarie->auto_salary_calc) ? 'checked' : '' }} value="1">
        <input type="hidden" name="auto_salary_calc" value="0">
        <label for="auto_salary_calc" class="form-check-label">Calculer automatiquement les salaires</label>
    </div>
</div>
<div class="col-12 col-sm-6">
    <label for="salaire_base" class="form-label">Salaire de Base <span class="text-danger">*</span></label>
    <input type="number" name="salaire_base" class="form-control" id="salaire_base" step="0.01" min="0" value="{{ old('salaire_base', $salarie->salaire_base) }}" required />
    <div class="invalid-feedback">Le salaire de base est obligatoire et doit être un nombre positif.</div>
    <div class="text-danger mt-1" id="salaire_base-error"></div>
</div>
<div class="col-12 col-sm-6">
    <label for="salaire_journalier" class="form-label">Salaire journalier <span class="text-danger">*</span></label>
    <input type="number" name="salaire_journalier" class="form-control" id="salaire_journalier" step="0.01" min="0" value="{{ old('salaire_journalier', $salarie->salaire_journalier) }}" required />
    <div class="invalid-feedback">Le salaire journalier est obligatoire et doit être un nombre positif.</div>
    <div class="text-danger mt-1" id="salaire_journalier-error"></div>
</div>
                        <div class="col-12 col-sm-6">
                            <label for="contrat" class="form-label">Contrat</label>
                            <input type="file" name="contrat" class="form-control" id="contrat"
                                accept=".pdf" />
                            <div class="invalid-feedback">Veuillez sélectionner un fichier PDF valide.</div>
                            @if ($salarie->contrat)
                                <p>Contrat actuel : <a href="{{ asset($salarie->contrat) }}" target="_blank">Voir</a>
                                </p>
                            @endif
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="date_embauche" class="form-label">Date d'embauche <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="date_embauche" class="form-control" id="date_embauche"
                                value="{{ $salarie->date_embauche }}" required />
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                            <div class="text-danger mt-1" id="date_embauche-error"></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-check">
                                <input type="checkbox" name="calculer_anciennete" class="form-check-input"
                                    id="calculer_anciennete" {{ $salarie->anciennete !== null ? 'checked' : '' }} />
                                <label for="calculer_anciennete" class="form-check-label">Calculer l'ancienneté
                                    automatiquement</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-between">
                            <button type="button" class="btn btn-primary btn-prev">
                                <i class="bx bx-chevron-left bx-sm ms-sm-n2"></i>
                                <span class="align-middle d-sm-inline-block d-none">Précédent</span>
                            </button>
                            <button type="button" class="btn btn-primary btn-next">
                                <span class="align-middle d-sm-inline-block d-none me-sm-1">Suivant</span>
                                <i class="bx bx-chevron-right bx-sm me-sm-n2"></i>
                            </button>
                        </div>
                    </div>
                </div>
             <!-- Step 3: Validation -->
                <div id="validation-edit" class="content" role="tabpanel">
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
            <p><strong>Photo:</strong> <span id="photo">{{ $salarie->photo ? basename($salarie->photo) : '-' }}</span></p>
            <p><strong>Nom:</strong> <span id="nom">{{ $salarie->nom }}</span></p>
            <p><strong>Prénom:</strong> <span id="prenom">{{ $salarie->prenom }}</span></p>
            <p><strong>Email:</strong> <span id="email">{{ $salarie->email ?? '-' }}</span></p>
            <p><strong>CIN:</strong> <span id="cin">{{ $salarie->cin }}</span></p>
            <p><strong>Téléphone:</strong> <span id="phone">{{ $salarie->phone ?? '-' }}</span></p>
            <p><strong>Date de Naissance:</strong> <span id="date_naissance">{{ $salarie->date_naissance }}</span></p>
        </div>
        <div class="col-12 col-md-6">
            <p><strong>Adresse:</strong> <span id="adresse">{{ $salarie->adresse ?? '-' }}</span></p>
            <p><strong>Situation Familiale:</strong> <span id="situation_familiale">{{ $salarie->situation_familiale ?? '-' }}</span></p>
            <p><strong>Nombre d'Enfants:</strong> <span id="nombre_enfant">{{ $salarie->nombre_enfant ?? 0 }}</span></p>
            <p><strong>Matricule CNSS:</strong> <span id="n_matricule_cnss">{{ $salarie->n_matricule_cnss }}</span></p>
            <p><strong>Matricule Entreprise:</strong> <span id="n_matricule_entreprise">{{ $salarie->n_matricule_entreprise }}</span></p>
            <p><strong>Type de Travail:</strong> <span id="type_travail">{{ $salarie->type_travail ?? '-' }}</span></p>
            <p><strong>Type de Contrat:</strong> <span id="type_contrat">{{ $salarie->type_contrat ?? '-' }}</span></p>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-md-6">
            <p><strong>Fonction:</strong> <span id="fonction">{{ $salarie->fonction ? $salarie->fonction->designation : 'N/A' }}</span></p>
            <p><strong>Contrat:</strong> <span id="contrat">{{ $salarie->contrat ? basename($salarie->contrat) : '-' }}</span></p>
            <p><strong>Salaire Net:</strong> <span id="salaire_net">{{ $salarie->salaire_net ? number_format($salarie->salaire_net, 2, ',', ' ') . ' MAD' : '-' }}</span></p>
            <p><strong>Salaire de Base:</strong> <span id="salaire_base">{{ $salarie->salaire_base ? number_format($salarie->salaire_base, 2, ',', ' ') . ' MAD' : '-' }}</span></p>
            <p><strong>Salaire Journalier:</strong> <span id="salaire_journalier">{{ $salarie->salaire_journalier ? number_format($salarie->salaire_journalier, 2, ',', ' ') . ' MAD' : '-' }}</span></p>
        </div>
        <div class="col-12 col-md-6">
            <p><strong>Type de Règlement:</strong> <span id="reglement_id">{{ $salarie->reglement ? $salarie->reglement->designation : 'N/A' }}</span></p>
            <p><strong>RIB:</strong> <span id="rib">{{ $salarie->rib ?? '-' }}</span></p>
            <p><strong>Date d'embauche:</strong> <span id="date_embauche">{{ $salarie->date_embauche ?? '-' }}</span></p>
        </div>
    </div>
</div>
                        <div class="col-12 d-flex justify-content-between">
                            <button type="button" class="btn btn-primary btn-prev">
                                <i class="bx bx-chevron-left bx-sm ms-sm-n2"></i>
                                <span class="align-middle d-sm-inline-block d-none">Précédent</span>
                            </button>
                            <button type="submit" class="btn btn-success" id="update-salarie-btn">
                                <i class="bx bx-check me-1"></i>
                                <span class="align-middle">Mettre à jour</span>
                                <span id="submit-spinner" class="spinner-border spinner-border-sm d-none"
                                    role="status"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
