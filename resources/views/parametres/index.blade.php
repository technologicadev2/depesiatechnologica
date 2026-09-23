@extends('master_page.app')

@section('title')
    Paramètres
@endsection

@section('content')
<style>
/* Style existant */
    .delete-vehicle-btn {
        transition: all 0.2s;
    }
    .delete-vehicle-btn:hover {
        transform: scale(1.08);
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
    }

    /* NOUVEAU : Ligne véhicule résilié */
    tr.resilie {
        background-color: #f8d7da !important;   /* Rouge clair */
        color: #721c24;
        opacity: 0.85;
    }

    tr.resilie:hover {
        background-color: #f5c6cb !important;
    }

    /* Optionnel : icône visuelle dans la ligne */
    tr.resilie td:first-child::before {
        content: "⚠️ ";
        font-size: 1.1em;
    }

</style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Liste des Paramètres</h4>

    <link rel="stylesheet" href="{{ asset('assets/css/projets.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>              

    <!-- Modal de chargement -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
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

    <div class="card">
        <div class="card-datatable table-responsive">
            <ul class="tabs-nav nav nav-pills mb-4">
                <li class="nav-item"><a class="nav-link active" href="#" data-tab="bpaie">Bulletin de Paie</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-tab="companySettings">Paramètres de Société</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-tab="companyDocuments">Documents Société</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-tab="jrsFerie">Jours Fériés</a></li>
            </ul>

            <div id="bpaie" class="tab-content active">
                <ul class="nav nav-tabs mb-4" id="bpaieTabs">
                    <li class="nav-item"><a class="nav-link active" href="#" data-subtab="pp">PP</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-subtab="ps">PS</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-subtab="chargeFamille">CHARGE DE FAMILLE</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-subtab="impotRevenu">IMPOT SUR LE REVENU</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-subtab="fraisPro">Frais Professionnels</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-subtab="anciennete">Ancienneté</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-subtab="heuresSupp"> Heures Supplémentaires</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-subtab="creationTable">Création de Table</a></li>
                </ul>

                <!-- Contenus des onglets -->
                <div id="pp" class="sub-tab-content">
                    <h5>Part Patronale</h5>
                    <form method="POST" action="{{ route('cotisations.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="cnss_pp" class="form-label">CNSS PP</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="cnss_pp" id="cnss_pp" value="{{ $cotisations->cnss_pp ?? '' }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="amo_pp" class="form-label">AMO PP</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="amo_pp" id="amo_pp" value="{{ $cotisations->amo_pp ?? '' }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Enregistrer</button>
                    </form>
                </div>

    <div id="ps" class="sub-tab-content" style="display: none;">
    <h5>Part Salariale</h5>
    <form method="POST" action="{{ route('cotisations.update') }}">
        @csrf
        @method('PUT')
      <div class="row">
            <div class="col-md-3 mb-3">
                <label for="cnss_ps" class="form-label">CNSS PS</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="cnss_ps" id="cnss_ps" value="{{ $cotisations->cnss_ps }}">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <label for="plafond_cnss" class="form-label">Plafond CNSS</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="plafond_cnss" id="plafond_cnss" value="{{ $cotisations->plafond_cnss ?? '' }}">
                </div>
            </div>
        </div>
     
        <div class="row">
            <div class="col-md-3 mb-3">
                <label for="ipe_ps" class="form-label">IPE</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="ipe_ps" id="ipe_ps" value="{{ $cotisations->ipe_ps }}">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <label for="plafond_ipe" class="form-label">Plafond IPE</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="plafond_ipe" id="plafond_ipe" value="{{ $cotisations->plafond_ipe }}">
                </div>
            </div>
        </div>
           <div class="col-md-3 mb-3">
            <label for="amo_ps" class="form-label">AMO PS</label>
            <div class="input-group">
                <input type="text" class="form-control" name="amo_ps" id="amo_ps" value="{{ $cotisations->amo_ps }}">
                <span class="input-group-text">%</span>
            </div>
        </div>
         <div class="col-md-3 mb-3">
            <label for="amo_ps" class="form-label">Taux CIMR</label>
            <div class="input-group">
                <input type="text" class="form-control" name="taux_CIMR" id="taux_CIMR" value="{{ $cotisations->taux_CIMR }}">
                <span class="input-group-text">%</span>
            </div>
        </div>
          <div class="col-md-3 mb-3">
            <label for="amo_ps" class="form-label">Taux Mutuelle</label>
            <div class="input-group">
                <input type="text" class="form-control" name="taux_mutuelle" id="taux_mutuelle" value="{{ $cotisations->taux_mutuelle }}">
                <span class="input-group-text">%</span>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Enregistrer</button>
    </form>
</div>


                <div id="chargeFamille" class="sub-tab-content" style="display: none;">
                    <h5>Charge de Famille</h5>
                    <form method="POST" action="{{ route('cotisations.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="charge_de_famille" class="form-label">Charge de Famille</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="charge_de_famille" id="charge_de_famille" value="{{ $cotisations->charge_de_famille ?? '' }}">
                                    <span class="input-group-text">DHS</span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Enregistrer</button>
                    </form>
                </div>

                <div id="impotRevenu" class="sub-tab-content" style="display: none;">
                    <h5>L'IMPOT SUR LE REVENU</h5>
                    <form method="POST" action="{{ route('impotRevenus.update') }}">
                        @csrf
                        @method('PUT')
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>TRANCHES DE REVENUS (EN DH)</th>
                                    <th>TAUX</th>
                                    <th>SOMME A DEDUIRE</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($impotRevenus as $impot)
                                    <tr>
                                        <td>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="revenu_min[]" value="{{ $impot->revenu_min }}" placeholder="Revenu min">
                                                <span class="input-group-text">-</span>
                                                <input type="text" class="form-control" name="revenu_max[]" value="{{ $impot->revenu_max ?? '' }}" placeholder="Revenu max">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="hidden" name="ids[]" value="{{ $impot->id }}">
                                                <input type="text" class="form-control" name="taux[]" value="{{ rtrim(rtrim(number_format($impot->taux, 2, '.', ''), '0'), '.') }}">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="somme_a_deduire[]" value="{{ $impot->somme_a_deduire }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-success">Enregistrer</button>
                    </form>
                </div>

               <div id="fraisPro" class="sub-tab-content" style="display: none;">
                <h5>Frais Professionnels</h5>
                <form method="POST" action="{{ route('fraisPros.store') }}">
                    @csrf
                    <table class="table table-bordered">
                    <thead>
                <tr>
                    <th>SBI Min</th>
                    <th>SBI Max</th>
                    <th>Taux (%)</th>
                    <th>Plafond</th>
                  
                </tr>
                   </thead>
                   <tbody>
                <tr>
                    <td>
                        <input type="number" class="form-control" name="sbi_min" placeholder="0" required>
                    </td>
                    <td>
                        <input type="number" class="form-control" name="sbi_max" placeholder="∞">
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="text" class="form-control" name="taux" placeholder="2.00" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="plafond" placeholder="0.00" required>
                    </td>
                     
                </tr>
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Ajouter</button>
    </form>

    @if ($fraisPros->isNotEmpty())
        <h5 class="mt-4">Barèmes Existants</h5>
        <form method="POST" action="{{ route('fraisPros.update') }}">
            @csrf
            @method('PUT')
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>SBI Min</th>
                        <th>SBI Max</th>
                        <th>Taux (%)</th>
                        <th>Plafond</th>
                    <th>Actions</th>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($fraisPros as $frais)
    <tr id="frais-row-{{ $frais->id }}">
        <td>
            <input type="hidden" name="ids[]" value="{{ $frais->id }}">
            <input type="number" class="form-control" name="sbi_min[]" value="{{ $frais->sbi_min }}" required>
        </td>
        <td>
            <input type="number" class="form-control" name="sbi_max[]" value="{{ $frais->sbi_max ?? '' }}" placeholder="∞">
        </td>
        <td>
            <div class="input-group">
                <input type="text" class="form-control" name="taux[]" value="{{ rtrim(rtrim(number_format($frais->taux, 2, '.', ''), '0'), '.') }}">
                <span class="input-group-text">%</span>
            </div>
        </td>
        <td>
            <input type="text" class="form-control" name="plafond[]" value="{{ $frais->plafond }}">
        </td>
        <td>
            <button type="button"
                    class="btn btn-sm btn-danger delete-frais-pro"
                    data-id="{{ $frais->id }}"
                    title="Supprimer">
                <i class="bx bx-trash"></i>
            </button>
        </td>
    </tr>
@endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </form>
    @endif
                </div>

                <div id="anciennete" class="sub-tab-content" style="display: none;">
                    <h5>Barème de l'Ancienneté</h5>
                    <form method="POST" action="{{ route('anciennete.taux.store') }}">
                        @csrf
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Années Min</th>
                                    <th>Années Max</th>
                                    <th>Taux (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="number" class="form-control" name="an_min" placeholder="0" required>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" name="an_max" placeholder="2 (laisser vide pour ∞)">
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="taux" placeholder="2.00" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </form>

                        @if ($ancienneteTaux->isNotEmpty())
        <h5 class="mt-4">Barèmes Existants</h5>
        <form method="POST" action="{{ route('anciennete.taux.update') }}">
            @csrf
            @method('PUT')
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Années Min</th>
                        <th>Années Max</th>
                        <th>Taux (%)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ancienneteTaux as $anciennete)
                        <tr id="anciennete-row-{{ $anciennete->id }}">
                            <td>
                                <input type="hidden" name="ids[]" value="{{ $anciennete->id }}">
                                <input type="number" class="form-control" name="an_min[]" value="{{ $anciennete->an_min }}" required>
                            </td>
                            <td>
                                <input type="number" class="form-control" name="an_max[]" value="{{ $anciennete->an_max ?? '' }}" placeholder="∞">
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="taux[]" value="{{ rtrim(rtrim(number_format($anciennete->taux, 2, '.', ''), '0'), '.') }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-danger delete-anciennete"
                                        data-id="{{ $anciennete->id }}"
                                        title="Supprimer">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </form>
    @endif
                </div>

<div id="heuresSupp" class="sub-tab-content" style="display: none;">
    <h5>Les Heures Supplémentaires</h5>
    <form method="POST" action="{{ route('heuresSupp.storeHeurs') }}">
        @csrf
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Secteur</th>
                    <th>Jour</th>
                    <th>Horaire Min</th>
                    <th>Horaire Max</th>
                    <th>Jours Ouvrables</th>
                    <th>Jours Fériés</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <input type="text" class="form-control" name="secteur" placeholder="Secteur" required>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="jour" placeholder="Ex: Lundi" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control" name="horaire_min" placeholder="0.00" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control" name="horaire_max" placeholder="0.00" required>
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="text" class="form-control" name="jr_ouvrable" placeholder="0.00" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="text" class="form-control" name="jr_feries" placeholder="0.00" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Ajouter</button>
    </form>

        @if ($heuresSupp->isNotEmpty())
        <h5 class="mt-4">Données Existantes</h5>
        <form method="POST" action="{{ route('heuresSupp.updateHeurs') }}">
            @csrf
            @method('PUT')
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Secteur</th>
                        <th>Jour</th>
                        <th>Horaire Min</th>
                        <th>Horaire Max</th>
                        <th>Jours Ouvrables</th>
                        <th>Jours Fériés</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($heuresSupp as $heure)
                        <tr id="heure-row-{{ $heure->id }}">
                            <td>
                                <input type="hidden" name="ids[]" value="{{ $heure->id }}">
                                <input type="text" class="form-control" name="secteur[]" value="{{ $heure->secteur }}" required>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="jour[]" value="{{ $heure->jour }}" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control" name="horaire_min[]" value="{{ rtrim(rtrim(number_format($heure->horaire_min, 2, '.', ''), '0'), '.') }}" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control" name="horaire_max[]" value="{{ rtrim(rtrim(number_format($heure->horaire_max, 2, '.', ''), '0'), '.') }}" required>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="jr_ouvrable[]" value="{{ $heure->jr_ouvrable }}" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="jr_feries[]" value="{{ $heure->jr_feries }}" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-danger delete-heure-supp"
                                        data-id="{{ $heure->id }}"
                                        title="Supprimer">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </form>
    @endif
</div>
                <div id="creationTable" class="sub-tab-content" style="display: none;">
                    <h5>Création de la table des salaires</h5>
                    <button type="button" class="btn btn-primary" id="createSalaryTable">Créer la table salaires_{{ date('Y') }}</button>
                    <div id="responseMessage" class="mt-3"></div>
                </div>
            </div>

              <div id="companySettings" class="tab-content" style="display: none;">
                <h5>Paramètres de Société</h5>
                <form method="POST" action="{{ route('company.settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="logo" class="form-label">Logo</label>
                            <input type="file" class="form-control" name="logo" id="logo" accept="image/*">
                            @if (isset($companySettings) && $companySettings && $companySettings->logo)
                                <img src="{{ asset('storage/' . $companySettings->logo) }}" alt="Logo" width="100" class="mt-2">
                            @endif
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" value="{{ $companySettings->email ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="nom_etreprise" class="form-label">Nom de l'entreprise</label>
                            <input type="text" class="form-control" name="nom_etreprise" id="nom_etreprise" value="{{ isset($companySettings) ? $companySettings->nom_etreprise : '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <textarea class="form-control" name="address" id="address">{{ $companySettings->address ?? '' }}</textarea>
                        </div>
                         <div class="col-md-4 mb-3">
                            <label for="ville" class="form-label">Ville</label>
                            <input type="text" class="form-control" name="ville" id="ville" value="{{ $companySettings->ville ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="account_number" class="form-label">Numéro de compte bancaire</label>
                            <input type="text" class="form-control" name="account_number" id="account_number" value="{{ $companySettings->account_number ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="bank_name" class="form-label">Nom de la banque</label>
                            <input type="text" class="form-control" name="bank_name" id="bank_name" value="{{ $companySettings->bank_name ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="ice" class="form-label">ICE</label>
                            <input type="text" class="form-control" name="ice" id="ice" value="{{ $companySettings->ice ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="cnss_number" class="form-label">Numéro CNSS</label>
                            <input type="text" class="form-control" name="cnss_number" id="cnss_number" value="{{ $companySettings->cnss_number ?? '' }}">
                        </div>
                       <!--  <div class="col-md-4 mb-3">
                            <label for="code_cnss" class="form-label">Code CNSS</label>
                            <input type="text" class="form-control" name="code_cnss" id="code_cnss" value="{{ $companySettings->code_cnss ?? '' }}">
                         </div> -->
                        <div class="col-md-4 mb-3">
                            <label for="tax_id" class="form-label">Identifiant fiscal (IF)</label>
                            <input type="text" class="form-control" name="tax_id" id="tax_id" value="{{ $companySettings->tax_id ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="commercial_register" class="form-label">Registre de commerce (RC)</label>
                            <input type="text" class="form-control" name="commercial_register" id="commercial_register" value="{{ $companySettings->commercial_register ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="patent_number" class="form-label">Patente (TP)</label>
                            <input type="text" class="form-control" name="patent_number" id="patent_number" value="{{ $companySettings->patent_number ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                    <label for="tva_declaration" class="form-label">Déclaration TVA</label>
                    <select class="form-control" name="tva_declaration" id="tva_declaration">
                        <option value="mensuelle" {{ isset($companySettings) && $companySettings->tva_declaration == 'mensuelle' ? 'selected' : '' }}>Mensuelle</option>
                        <option value="trimestrielle" {{ isset($companySettings) && $companySettings->tva_declaration == 'trimestrielle' ? 'selected' : '' }}>Trimestrielle</option>
                    </select>
                       </div>
                            <div class="col-md-4 mb-3">
                            <label for="capital" class="form-label">Capital</label>
                            <input type="number" step="0.01" class="form-control" name="capital" id="capital" value="{{ $companySettings->capital ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="phone_number" class="form-label">Numéro de téléphone</label>
                            <input type="text" class="form-control" name="phone_number" id="phone_number" value="{{ $companySettings->phone_number ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="stamp" class="form-label">Cachet</label>
                            <input type="file" class="form-control" name="" id="stamp" accept="image/*">
                            @if (isset($companySettings) && $companySettings && $companySettings->stamp)
                                <img src="{{ asset('storage/' . $companySettings->stamp) }}" alt="Cachet" width="100" class="mt-2">
                            @endif
                        </div>
                      <div class="col-md-4 mb-3">
                            <label for="code_cnss" class="form-label">activite_societe</label>
                            <input type="text" class="form-control" name="activite_societe" id="activite_societe" value="{{ $companySettings->activite_societe?? '' }}">
                         </div>
                          <div class="col-md-4 mb-3">
                            <label for="gerant" class="form-label">Gerant</label>
                            <input type="text" class="form-control" name="gerant" id="gerant" value="{{ $companySettings->gerant ?? '' }}">
                        </div>
 <!--                         <div class="col-md-4 mb-3">
    <label for="tva" class="form-label">TVA</label>
    <select class="form-select" name="tva" id="tva">
        <option value="">-- Sélectionner le type TVA --</option>
        <option value="mensuelle"      {{ ($companySettings->tva ?? '') == 'mensuelle'      ? 'selected' : '' }}>Mensuelle</option>
        <option value="trimestrielle"  {{ ($companySettings->tva ?? '') == 'trimestrielle'  ? 'selected' : '' }}>Trimestrielle</option>
        <option value="annuelle"       {{ ($companySettings->tva ?? '') == 'annuelle'       ? 'selected' : '' }}>Annuelle</option>
    </select>
</div>
 -->
     <div class="col-md-4 mb-3">
                            <label for="assuranceCompagnie" class="form-label"> Compagnie Assurance des Véhicules</label>
                            <input type="text" step="0.01" class="form-control" name="assuranceCompagnie" id="assuranceCompagnie" value="{{ $companySettings->assuranceCompagnie ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="adresseAssurance" class="form-label"> AdresseAssurance</label>
                            <input type="text" step="0.01" class="form-control" name="adresseAssurance" id="adresseAssurance" value="{{ $companySettings->adresseAssurance ?? '' }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Enregistrer</button>
                </form>
            </div>

            <div id="companyDocuments" class="tab-content" style="display: none;">
                <h5 class="mb-4">
                    <i class="fas fa-file-alt me-2"></i>Documents Société
                </h5>

                <ul class="nav nav-tabs mb-4" id="companyDocumentsTabs">
                    <li class="nav-item"><a class="nav-link active" href="#" data-subtab="documentSociete">document societe</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-subtab="vehicule">vehicule</a></li>
                </ul>

                <div id="documentSociete" class="sub-tab-content">
                    <form id="company-documents-form" method="POST" action="{{ $companyDocuments ? route('company.documents.update') : route('company.documents.store') }}" enctype="multipart/form-data">
                        @csrf
                        @if($companyDocuments)
                            @method('PUT')
                        @endif

                        <!-- Section: Documents Registre de Commerce -->
                        <div class="document-section mb-5">
                            <div class="section-header d-flex align-items-center mb-3">
                                <i class="fas fa-building text-primary me-2"></i>
                                <h6 class="section-title mb-0">Documents Registre de Commerce</h6>
                            </div>
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                <label for="modele_rc_7" class="form-label fw-semibold">
                                    <i class="fas fa-file-pdf text-danger me-1"></i>Modèle RC 7 (PDF)
                                </label>
                                <input type="file" class="form-control" name="modele_rc_7" id="modele_rc_7" accept=".pdf">
                                @if ($companyDocuments && $companyDocuments->modele_rc_7)
                                    <div class="mt-2 p-2 bg-light rounded">
                                    <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>Fichier actuel : 
                                    <a href="{{ Storage::url($companyDocuments->modele_rc_7) }}" target="_blank" class="text-decoration-none">
                                        {{ basename($companyDocuments->modele_rc_7) }}
                                    </a>
                                    </small>
                                    </div>
                                @endif
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="modele_rc_9" class="form-label fw-semibold">
                                        <i class="fas fa-file-pdf text-danger me-1"></i>Modèle RC 9 (PDF)
                                    </label>
                                    <input type="file" class="form-control" name="modele_rc_9" id="modele_rc_9" accept=".pdf">
                                @if ($companyDocuments && $companyDocuments->modele_rc_9)
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>Fichier actuel : 
                                        <a href="{{ Storage::url($companyDocuments->modele_rc_9) }}" target="_blank" class="text-decoration-none">
                                            {{ basename($companyDocuments->modele_rc_9) }}
                                        </a>
                                    </small>
                                </div>
                                @endif
                                </div>
                                 </div>
                                    <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="modele_rc_7_expires_at" class="form-label fw-semibold">
                                        <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'expiration RC 7
                                    </label>
                                    <div class="input-group">
                                    <input type="date" class="form-control" name="modele_rc_7_expires_at" id="modele_rc_7_expires_at" value="{{ $companyDocuments && $companyDocuments->modele_rc_7_expires_at ? $companyDocuments->modele_rc_7_expires_at->format('Y-m-d') : '' }}">
                                @if($companyDocuments && $companyDocuments->modele_rc_7_expires_at)
                                                @php
                                    $currentDate = \Carbon\Carbon::now('Europe/Paris');
                                    $expirationDate = \Carbon\Carbon::parse($companyDocuments->modele_rc_7_expires_at)->setTimezone('Europe/Paris');
                                    $daysUntilExpiration = $currentDate->diffInDays($expirationDate, false);
                                @endphp
                                <span class="input-group-text">
                                @if($daysUntilExpiration < 0)
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle me-1"></i>Expiré
                                    </span>
                                @elseif($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                    <span class="badge bg-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>À renouveler
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Valide
                                    </span>
                                @endif
                                    </span>
                                @endif
                                    </div>
                                @if($companyDocuments && $companyDocuments->modele_rc_7_expires_at)
                                                <div class="form-text">
                                @if($daysUntilExpiration < 0)
                                    <small class="text-danger">
                                        <i class="fas fa-times-circle me-1"></i>Expiré depuis {{ abs($daysUntilExpiration) }} jour(s)
                                    </small>
                                @elseif($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                    <small class="text-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans {{ $daysUntilExpiration }} jour(s)
                                    </small>
                                @else
                                    <small class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>Valide (expire dans {{ $daysUntilExpiration }} jour(s))
                                    </small>
                                @endif
                                                </div>
                                @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="modele_rc_9_expires_at" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'expiration RC 9
                                            </label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" name="modele_rc_9_expires_at" id="modele_rc_9_expires_at" value="{{ $companyDocuments && $companyDocuments->modele_rc_9_expires_at ? $companyDocuments->modele_rc_9_expires_at->format('Y-m-d') : '' }}">
                                                @if($companyDocuments && $companyDocuments->modele_rc_9_expires_at)
                                                    @php
                                                        $currentDate = \Carbon\Carbon::now('Europe/Paris');
                                                        $expirationDate = \Carbon\Carbon::parse($companyDocuments->modele_rc_9_expires_at)->setTimezone('Europe/Paris');
                                                        $daysUntilExpiration = $currentDate->diffInDays($expirationDate, false);
                                                    @endphp
                                                    <span class="input-group-text">
                                                        @if($daysUntilExpiration < 0)
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times-circle me-1"></i>Expiré
                                                            </span>
                                                        @elseif($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>À renouveler
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle me-1"></i>Valide
                                                            </span>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                            @if($companyDocuments && $companyDocuments->modele_rc_9_expires_at)
                                                <div class="form-text">
                                                    @if($daysUntilExpiration < 0)
                                                        <small class="text-danger">
                                                            <i class="fas fa-times-circle me-1"></i>Expiré depuis {{ abs($daysUntilExpiration) }} jour(s)
                                                        </small>
                                                    @elseif($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                        <small class="text-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans {{ $daysUntilExpiration }} jour(s)
                                                        </small>
                                                    @else
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>Valide (expire dans {{ $daysUntilExpiration }} jour(s))
                                                        </small>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>





                        <!-- Section: Attestations Administratives -->
                        <div class="document-section mb-5">
                            <div class="section-header d-flex align-items-center mb-3">
                                <i class="fas fa-certificate text-success me-2"></i>
                                <h6 class="section-title mb-0">Attestations Administratives</h6>
                            </div>
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="attestation_regularite_fiscale" class="form-label fw-semibold">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>Attestation de Régularité Fiscale (PDF)
                                            </label>
                                            <input type="file" class="form-control" name="attestation_regularite_fiscale" id="attestation_regularite_fiscale" accept=".pdf">
                                            @if (isset($companyDocuments) && !empty($companyDocuments->attestation_regularite_fiscale))
                                                <div class="mt-2 p-2 bg-light rounded">
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>Fichier actuel : 
                                                        <a href="{{ Storage::url($companyDocuments->attestation_regularite_fiscale) }}" target="_blank" class="text-decoration-none">
                                                            {{ basename($companyDocuments->attestation_regularite_fiscale) }}
                                                        </a>
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="attestation_cnss" class="form-label fw-semibold">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>Attestation CNSS (PDF)
                                            </label>
                                            <input type="file" class="form-control" name="attestation_cnss" id="attestation_cnss" accept=".pdf">
                                            @if (isset($companyDocuments) && !empty($companyDocuments->attestation_cnss))
                                                <div class="mt-2 p-2 bg-light rounded">
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>Fichier actuel : 
                                                        <a href="{{ Storage::url($companyDocuments->attestation_cnss) }}" target="_blank" class="text-decoration-none">
                                                            {{ basename($companyDocuments->attestation_cnss) }}
                                                        </a>
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="attestation_soumission_marche" class="form-label fw-semibold">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>Attestation Soumission Marché (PDF)
                                            </label>
                                            <input type="file" class="form-control" name="attestation_soumission_marche" id="attestation_soumission_marche" accept=".pdf">
                                            @if (isset($companyDocuments) && !empty($companyDocuments->attestation_soumission_marche))
                                                <div class="mt-2 p-2 bg-light rounded">
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>Fichier actuel : 
                                                        <a href="{{ Storage::url($companyDocuments->attestation_soumission_marche) }}" target="_blank" class="text-decoration-none">
                                                            {{ basename($companyDocuments->attestation_soumission_marche) }}
                                                        </a>
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="attestation_regularite_fiscale_expires_at" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'expiration Fiscale
                                            </label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" name="attestation_regularite_fiscale_expires_at" id="attestation_regularite_fiscale_expires_at" value="{{ isset($companyDocuments) && $companyDocuments->attestation_regularite_fiscale_expires_at ? $companyDocuments->attestation_regularite_fiscale_expires_at->format('Y-m-d') : '' }}">
                                                @if (isset($companyDocuments) && !empty($companyDocuments->attestation_regularite_fiscale_expires_at))
                                                    @php
                                                        $currentDate = \Carbon\Carbon::now('Europe/Paris');
                                                        $expirationDate = \Carbon\Carbon::parse($companyDocuments->attestation_regularite_fiscale_expires_at)->setTimezone('Europe/Paris');
                                                        $daysUntilExpiration = $currentDate->diffInDays($expirationDate, false);
                                                    @endphp
                                                    <span class="input-group-text">
                                                        @if ($daysUntilExpiration < 0)
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times-circle me-1"></i>Expiré
                                                            </span>
                                                        @elseif ($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>À renouveler
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle me-1"></i>Valide
                                                            </span>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                            @if (isset($companyDocuments) && !empty($companyDocuments->attestation_regularite_fiscale_expires_at))
                                                <div class="form-text">
                                                    @if ($daysUntilExpiration < 0)
                                                        <small class="text-danger">
                                                            <i class="fas fa-times-circle me-1"></i>Expiré depuis {{ abs($daysUntilExpiration) }} jour(s)
                                                        </small>
                                                    @elseif ($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                        <small class="text-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans {{ $daysUntilExpiration }} jour(s)
                                                        </small>
                                                    @else
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>Valide (expire dans {{ $daysUntilExpiration }} jour(s))
                                                        </small>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="attestation_cnss_expires_at" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'expiration CNSS
                                            </label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" name="attestation_cnss_expires_at" id="attestation_cnss_expires_at" value="{{ isset($companyDocuments) && $companyDocuments->attestation_cnss_expires_at ? $companyDocuments->attestation_cnss_expires_at->format('Y-m-d') : '' }}">
                                                @if (isset($companyDocuments) && !empty($companyDocuments->attestation_cnss_expires_at))
                                                    @php
                                                        $currentDate = \Carbon\Carbon::now('Europe/Paris');
                                                        $expirationDate = \Carbon\Carbon::parse($companyDocuments->attestation_cnss_expires_at)->setTimezone('Europe/Paris');
                                                        $daysUntilExpiration = $currentDate->diffInDays($expirationDate, false);
                                                    @endphp
                                                    <span class="input-group-text">
                                                        @if ($daysUntilExpiration < 0)
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times-circle me-1"></i>Expiré
                                                            </span>
                                                        @elseif ($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>À renouveler
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle me-1"></i>Valide
                                                            </span>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                            @if (isset($companyDocuments) && !empty($companyDocuments->attestation_cnss_expires_at))
                                                <div class="form-text">
                                                    @if ($daysUntilExpiration < 0)
                                                        <small class="text-danger">
                                                            <i class="fas fa-times-circle me-1"></i>Expiré depuis {{ abs($daysUntilExpiration) }} jour(s)
                                                        </small>
                                                    @elseif ($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                        <small class="text-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans {{ $daysUntilExpiration }} jour(s)
                                                        </small>
                                                    @else
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>Valide (expire dans {{ $daysUntilExpiration }} jour(s))
                                                        </small>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="attestation_soumission_marche_expires_at" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'expiration Marché
                                            </label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" name="attestation_soumission_marche_expires_at" id="attestation_soumission_marche_expires_at" value="{{ isset($companyDocuments) && $companyDocuments->attestation_soumission_marche_expires_at ? $companyDocuments->attestation_soumission_marche_expires_at->format('Y-m-d') : '' }}">
                                                @if (isset($companyDocuments) && !empty($companyDocuments->attestation_soumission_marche_expires_at))
                                                    @php
                                                        $currentDate = \Carbon\Carbon::now('Europe/Paris');
                                                        $expirationDate = \Carbon\Carbon::parse($companyDocuments->attestation_soumission_marche_expires_at)->setTimezone('Europe/Paris');
                                                        $daysUntilExpiration = $currentDate->diffInDays($expirationDate, false);
                                                    @endphp
                                                    <span class="input-group-text">
                                                        @if ($daysUntilExpiration < 0)
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times-circle me-1"></i>Expiré
                                                            </span>
                                                        @elseif ($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>À renouveler
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle me-1"></i>Valide
                                                            </span>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                            @if (isset($companyDocuments) && !empty($companyDocuments->attestation_soumission_marche_expires_at))
                                                <div class="form-text">
                                                    @if ($daysUntilExpiration < 0)
                                                        <small class="text-danger">
                                                            <i class="fas fa-times-circle me-1"></i>Expiré depuis {{ abs($daysUntilExpiration) }} jour(s)
                                                        </small>
                                                    @elseif ($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                        <small class="text-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans {{ $daysUntilExpiration }} jour(s)
                                                        </small>
                                                    @else
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>Valide (expire dans {{ $daysUntilExpiration }} jour(s))
                                                        </small>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Documents d'Assurance & Protection -->
                        <div class="document-section mb-5">
                            <div class="section-header d-flex align-items-center mb-3">
                                <i class="fas fa-shield-alt text-info me-2"></i>
                                <h6 class="section-title mb-0">Documents d'Assurance & Protection</h6>
                            </div>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-shield-virus text-primary me-2"></i>Assurances Générales
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="assurance_responsabilite_civile" class="form-label fw-semibold">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>Assurance Responsabilité Civile (PDF)
                                            </label>
                                            <input type="file" class="form-control" name="assurance_responsabilite_civile" id="assurance_responsabilite_civile" accept=".pdf">
                                            @if (isset($companyDocuments) && !empty($companyDocuments->assurance_responsabilite_civile))
                                                <div class="mt-2 p-2 bg-light rounded">
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>Fichier actuel : 
                                                        <a href="{{ Storage::url($companyDocuments->assurance_responsabilite_civile) }}" target="_blank" class="text-decoration-none">
                                                            {{ basename($companyDocuments->assurance_responsabilite_civile) }}
                                                        </a>
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="assurance_accident_travail" class="form-label fw-semibold">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>Assurance Accident Travail (PDF)
                                            </label>
                                            <input type="file" class="form-control" name="assurance_accident_travail" id="assurance_accident_travail" accept=".pdf">
                                            @if (isset($companyDocuments) && !empty($companyDocuments->assurance_accident_travail))
                                                <div class="mt-2 p-2 bg-light rounded">
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>Fichier actuel : 
                                                        <a href="{{ Storage::url($companyDocuments->assurance_accident_travail) }}" target="_blank" class="text-decoration-none">
                                                            {{ basename($companyDocuments->assurance_accident_travail) }}
                                                        </a>
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="assurance_responsabilite_civile_expires_at" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'expiration Resp. Civile
                                            </label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" name="assurance_responsabilite_civile_expires_at" id="assurance_responsabilite_civile_expires_at" value="{{ isset($companyDocuments) && $companyDocuments->assurance_responsabilite_civile_expires_at ? $companyDocuments->assurance_responsabilite_civile_expires_at->format('Y-m-d') : '' }}">
                                                @if (isset($companyDocuments) && !empty($companyDocuments->assurance_responsabilite_civile_expires_at))
                                                    @php
                                                        $currentDate = \Carbon\Carbon::now('Europe/Paris');
                                                        $expirationDate = \Carbon\Carbon::parse($companyDocuments->assurance_responsabilite_civile_expires_at)->setTimezone('Europe/Paris');
                                                        $daysUntilExpiration = $currentDate->diffInDays($expirationDate, false);
                                                    @endphp
                                                    <span class="input-group-text">
                                                        @if ($daysUntilExpiration < 0)
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times-circle me-1"></i>Expiré
                                                            </span>
                                                        @elseif ($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>À renouveler
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle me-1"></i>Valide
                                                            </span>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                            @if (isset($companyDocuments) && !empty($companyDocuments->assurance_responsabilite_civile_expires_at))
                                                <div class="form-text">
                                                    @if ($daysUntilExpiration < 0)
                                                        <small class="text-danger">
                                                            <i class="fas fa-times-circle me-1"></i>Expiré depuis {{ abs($daysUntilExpiration) }} jour(s)
                                                        </small>
                                                    @elseif ($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                        <small class="text-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans {{ $daysUntilExpiration }} jour(s)
                                                        </small>
                                                    @else
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>Valide (expire dans {{ $daysUntilExpiration }} jour(s))
                                                        </small>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="assurance_accident_travail_expires_at" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'expiration Accident Travail
                                            </label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" name="assurance_accident_travail_expires_at" id="assurance_accident_travail_expires_at" value="{{ isset($companyDocuments) && $companyDocuments->assurance_accident_travail_expires_at ? $companyDocuments->assurance_accident_travail_expires_at->format('Y-m-d') : '' }}">
                                                @if (isset($companyDocuments) && !empty($companyDocuments->assurance_accident_travail_expires_at))
                                                    @php
                                                        $currentDate = \Carbon\Carbon::now('Europe/Paris');
                                                        $expirationDate = \Carbon\Carbon::parse($companyDocuments->assurance_accident_travail_expires_at)->setTimezone('Europe/Paris');
                                                        $daysUntilExpiration = $currentDate->diffInDays($expirationDate, false);
                                                    @endphp
                                                    <span class="input-group-text">
                                                        @if ($daysUntilExpiration < 0)
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times-circle me-1"></i>Expiré
                                                            </span>
                                                        @elseif ($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>À renouveler
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle me-1"></i>Valide
                                                            </span>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                            @if (isset($companyDocuments) && !empty($companyDocuments->assurance_accident_travail_expires_at))
                                                <div class="form-text">
                                                    @if ($daysUntilExpiration < 0)
                                                        <small class="text-danger">
                                                            <i class="fas fa-times-circle me-1"></i>Expiré depuis {{ abs($daysUntilExpiration) }} jour(s)
                                                        </small>
                                                    @elseif ($daysUntilExpiration >= 0 && $daysUntilExpiration <= 30)
                                                        <small class="text-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans {{ $daysUntilExpiration }} jour(s)
                                                        </small>
                                                    @else
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>Valide (expire dans {{ $daysUntilExpiration }} jour(s))
                                                        </small>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                  <!-- Section: Signature Electronique -->
<div class="document-section mb-5">
    <div class="section-header d-flex align-items-center mb-3">
        <i class="fas fa-signature text-info me-2"></i>
        <h6 class="section-title mb-0">Signature Électronique</h6>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="signature_electronic" class="form-label fw-semibold">
                        <i class="fas fa-key text-primary me-1"></i>Identifiant / Référence Signature Électronique
                    </label>
                    <input type="text" 
                           class="form-control" 
                           name="signature_electronic" 
                           id="signature_electronic" 
                           placeholder="Ex: CERT-123456789 ou ID signature" 
                           value="{{ old('signature_electronic', $companyDocuments->signature_electronic ?? '') }}">
                    
                    <div class="form-text text-muted">
                        Entrez l'identifiant, le numéro de certificat ou la référence de la signature électronique.
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="date_exp_signature" class="form-label fw-semibold">
                        <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'expiration
                    </label>
                    <div class="input-group">
                        <input type="date" 
                               class="form-control" 
                               name="date_exp_signature" 
                               id="date_exp_signature" 
                               value="{{ old('date_exp_signature', $companyDocuments && $companyDocuments->date_exp_signature ? $companyDocuments->date_exp_signature->format('Y-m-d') : '') }}">
                        
                        @if($companyDocuments && $companyDocuments->date_exp_signature)
                            @php
                                $currentDate = \Carbon\Carbon::now('Europe/Paris');
                                $expirationDate = \Carbon\Carbon::parse($companyDocuments->date_exp_signature)->setTimezone('Europe/Paris');
                                $daysUntilExpiration = $currentDate->diffInDays($expirationDate, false);
                            @endphp
                            <span class="input-group-text">
                                @if($daysUntilExpiration < 0)
                                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Expiré</span>
                                @elseif($daysUntilExpiration <= 30)
                                    <span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>À renouveler</span>
                                @else
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Valide</span>
                                @endif
                            </span>
                        @endif
                    </div>

                    @if($companyDocuments && $companyDocuments->date_exp_signature)
                        <div class="form-text">
                            @if($daysUntilExpiration < 0)
                                <small class="text-danger"><i class="fas fa-times-circle me-1"></i>Expiré depuis {{ abs($daysUntilExpiration) }} jour(s)</small>
                            @elseif($daysUntilExpiration <= 30)
                                <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans {{ $daysUntilExpiration }} jour(s)</small>
                            @else
                                <small class="text-success"><i class="fas fa-check-circle me-1"></i>Valide (expire dans {{ $daysUntilExpiration }} jour(s))</small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
                        <!-- Enregistrer Button -->
                        <div class="text-center mb-5">
                            <button type="submit" class="btn btn-success btn-lg px-5" form="company-documents-form">
                                <i class="fas fa-save me-2"></i>Enregistrer les Documents
                            </button>
                        </div>
                    </form>
                </div>

                <div id="vehicule" class="sub-tab-content" style="display: none;">
                    <h5 class="mb-4">
                        <i class="fas fa-car me-2"></i>Gestion des Véhicules
                    </h5>

                    <!-- Form for Adding a New Vehicle -->
                    <div class="document-section mb-5">
                        <div class="card border-0 shadow-sm border-top border-primary border-3">
                            <div class="card-header bg-secondary ">
                                <h6 class="mb-0 text-white">
                                    <i class="fas fa-plus-circle me-2"></i>Ajouter un Nouveau Véhicule
                                </h6>
                            </div>
                            <div class="card-body">
                                <form id="vehicle-form" method="POST" action="{{ route('vehicles.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                            <label for="marque" class="form-label fw-semibold">
                                <i class="fas fa-car-side me-1"></i>Marque <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="marque" id="marque" required placeholder="Ex: Renault, Peugeot, Toyota">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>Marque du véhicule (obligatoire)
                            </div>
                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="matricule" class="form-label fw-semibold">
                                                <i class="fas fa-id-card me-1"></i>Matricule <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" name="matricule" id="matricule" required>
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>Numéro unique d'identification du véhicule
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="assurance_vehicule_file" class="form-label fw-semibold">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>Assurance (PDF)
                                            </label>
                                            <input type="file" class="form-control" name="assurance_file" id="assurance_vehicule_file" accept=".pdf">
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>Facultatif, sélectionnez un fichier PDF
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="assurance_vehicule_expires_at" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'Expiration Assurance
                                            </label>
                                            <input type="date" class="form-control" name="assurance_expires_at" id="assurance_vehicule_expires_at">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="visite_technique_file" class="form-label fw-semibold">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>Visite Technique (PDF)
                                            </label>
                                            <input type="file" class="form-control" name="visite_technique_file" id="visite_technique_file" accept=".pdf">
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>Facultatif, sélectionnez un fichier PDF
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="visite_technique_expires_at" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'Expiration Visite Technique
                                            </label>
                                            <input type="date" class="form-control" name="visite_technique_expires_at" id="visite_technique_expires_at">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="carte_grise_file" class="form-label fw-semibold">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>Carte Grise (PDF)
                                            </label>
                                            <input type="file" class="form-control" name="carte_grise_file" id="carte_grise_file" accept=".pdf">
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>Facultatif, sélectionnez un fichier PDF
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="carte_grise_expires_at" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'Expiration Carte Grise
                                            </label>
                                            <input type="date" class="form-control" name="carte_grise_expires_at" id="carte_grise_expires_at">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="contrat_achat_file" class="form-label fw-semibold">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>Contrat d'Achat (PDF)
                                            </label>
                                            <input type="file" class="form-control" name="contrat_achat_file" id="contrat_achat_file" accept=".pdf">
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>Facultatif, sélectionnez un fichier PDF
                                            </div>
                                        </div>

                                                <div class="col-md-4 mb-3">
                            <label for="vignette_path" class="form-label fw-semibold">
                                <i class="fas fa-file-pdf text-danger me-1"></i>Vignette (PDF)
                            </label>
                            <input type="file" class="form-control" name="vignette_path" id="vignette_path" accept=".pdf">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>Facultatif, sélectionnez un fichier PDF
                            </div>
                        </div>
                            <div class="col-md-4 mb-3">
                            <label for="vignette_expires_at" class="form-label fw-semibold">
                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'Expiration Vignette
                            </label>
                            <input type="date" class="form-control" name="vignette_expires_at" id="vignette_expires_at">
                        </div>
                                        <!-- <div class="col-md-4 mb-3">
                                            <label for="contrat_achat_expires_at" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'Expiration Contrat d'Achat
                                            </label>
                                            <input type="date" class="form-control" name="contrat_achat_expires_at" id="contrat_achat_expires_at">
                                        </div> -->
                                        <div class="col-md-4 mb-3">
                                            <label for="type" class="form-label fw-semibold">
                                                <i class="fas fa-calendar-alt text-warning me-1"></i>Type de véhicules
                                            </label>
                                        <select class="form-select" name="type" id="type" required>
                                            <option value="normale">Normale</option>
                                                        <option value="engins">Engins</option>
                                                        
                                                        
                                                    </select>                                        </div>
                                        <div class="col-md-12 mb-3 text-center">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Ajouter le Véhicule
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
    <!-- Liste des véhicules existants -->
        <div class="document-section mb-5 mt-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-car text-primary me-2"></i>Véhicules Existants
                    </h6>
                    <span id="vehicle-count" class="badge bg-primary">0 véhicule(s)</span>
                </div>
                <div class="card-body">
                    <form id="vehicle-search-form" class="mb-3 d-flex gap-2">
                        <input type="text" id="vehicle-search-input" name="search" class="form-control"
                            placeholder="Rechercher par matricule, marque ou type..."
                            style="max-width: 400px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Rechercher
                        </button>
                        <button type="button" id="vehicle-search-reset" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Réinitialiser
                        </button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover" id="vehicles-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Marque</th>
                                    <th>Matricule</th>
                                    <th>Type</th>
                                    <th>Assurance</th>
                                    <th>Visite Technique</th>
                                    <th>Carte Grise</th>
                                    
                                    <th class="fw-semibold">Vignette</th>
                                    <th>Documents Associés</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ajout Document Véhicule -->
        <div class="modal fade" id="addVehicleDocumentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2"></i>
                            <span class="modal-title-text">Ajouter un Document</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="add-vehicle-document-form" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="vehicle_id" id="vehicle_id">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Type de Document <span class="text-danger">*</span></label>
                                    <select class="form-select" name="document_type" id="vehicle_document_type" required>
                                        <option value="technical_inspection">Visite Technique</option>
                                        <option value="registration_certificate">Carte Grise</option>
                                        <option value="purchase_contract">Contrat d'Achat</option>
                                        <option value="custom_document">Document Personnalisé</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Document (PDF) <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="file" id="vehicle_document_file" accept=".pdf" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Date d'Expiration <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="expires_at" id="vehicle_document_expires_at" required>
                                </div>
                                <div class="col-md-6 mb-3 d-none" id="vehicle_custom_label_field">
                                    <label class="form-label fw-semibold">Label (Document Personnalisé)</label>
                                    <input type="text" class="form-control" name="custom_document_label" id="vehicle_custom_document_label" placeholder="Ex: Certificat de Conformité">
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i>Ajouter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Contrat de Résiliation -->
        <div class="modal fade" id="resiliationContractModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="resiliationContractModalLabel">
                            <i class="fas fa-file-contract me-2"></i>Contrat de Résiliation
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="resiliation-contract-form" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="vehicle_id" id="resiliation_vehicle_id">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-file-pdf text-danger me-1"></i>Contrat de Résiliation (PDF)
                                </label>
                                <input type="file" class="form-control" name="resiliation_file" id="resiliation_file" accept=".pdf" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar-alt text-warning me-1"></i>Date d'expiration
                                </label>
                                <input type="date" class="form-control" name="resiliation_expires_at" id="resiliation_expires_at">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-success" id="save-resiliation-contract">
                            <i class="fas fa-save me-2"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
            <div id="jrsFerie" class="tab-content" style="display: none;">
                <h5>Les Jours Fériés officiels</h5>
                <ul class="nav nav-tabs mb-4" id="jrsFerieTabs">
                    <li class="nav-item"><a class="nav-link active" href="#" data-subtab="fetesNationales">Fêtes Nationales </a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-subtab="fetesReligieuses">Fêtes Religieuses </a></li>
                </ul>
<div id="fetesNationales" class="sub-tab-content ">
    <h5>Fêtes Nationales (fixes)</h5>
    <form method="POST" action="{{ route('jrsFerie.update') }}">
        @csrf
        @method('PUT')
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Occasion</th>
                    <th>Date Debut</th>
                    <th>Date Fin</th>
                    <th>Nbr Jours</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jourFeries as $jrf)
                    @if ($jrf->religieux == 0)
                        <tr id="jourferie-row-{{ $jrf->id }}">
                            <td>
                                <div class="input-group">
                                    <input type="hidden" name="ids[]" value="{{ $jrf->id }}">
                                    <input type="text" class="form-control" name="nom[]"
                                        value="{{ $jrf->nom }}" placeholder="Occasion">
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="date_debut[]"
                                        value="{{ $jrf->date_debut->format('Y-m-d') }}" >
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="date_fin[]"
                                        value="{{ $jrf->date_fin->format('Y-m-d') }}">
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="nbr_jours[]"
                                    value="{{ $jrf->nbr_jours }}" readonly >
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-danger delete-jour-ferie"
                                        data-id="{{ $jrf->id }}"
                                        title="Supprimer">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary mt-3">Enregistrer</button>
    </form>
</div>
<div id="fetesReligieuses" class="sub-tab-content" style="display: none;">
    <h5>Dates lunaires confirmées estimées pour 2025</h5>
    <form method="POST" action="{{ route('jrsFerie.update') }}">
        @csrf
        @method('PUT')
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Occasion</th>
                    <th>Date Debut</th>
                    <th>Date Fin</th>
                    <th>Nbr Jours</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jourFeries as $jrf)
                    @if ($jrf->religieux == 1)
                        <tr id="jourferie-row-{{ $jrf->id }}">
                            <td>
                                <div class="input-group">
                                    <input type="hidden" name="ids[]" value="{{ $jrf->id }}">
                                    <input type="text" class="form-control" name="nom[]"
                                        value="{{ $jrf->nom }}" placeholder="Occasion">
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="date_debut[]"
                                        value="{{ $jrf->date_debut->format('Y-m-d') }}" >
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="date_fin[]"
                                        value="{{ $jrf->date_fin->format('Y-m-d') }}">
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="nbr_jours[]"
                                    value="{{ $jrf->nbr_jours }}" readonly >
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-danger delete-jour-ferie"
                                        data-id="{{ $jrf->id }}"
                                        title="Supprimer">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary mt-3">Enregistrer</button>
    </form>
</div>

            </div>
        </div>
<!-- Modal d'erreur -->
                <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger" id="errorModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Erreur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body fs-6" id="errorModalBody">
                        <!-- Le message sera injecté ici -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                    </div>
                </div>
                </div>

<!-- Modal Ajout Contrat de Résiliation -->
<div class="modal fade" id="resiliationContractModal" tabindex="-1" aria-labelledby="resiliationContractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="resiliationContractModalLabel">
                    <i class="fas fa-file-contract me-2"></i> Contrat de Résiliation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="resiliation-contract-form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="vehicle_id" id="resiliation_vehicle_id">

                    <div class="mb-3">
                        <label for="resiliation_file" class="form-label fw-semibold">
                            <i class="fas fa-file-pdf text-danger me-1"></i> Contrat de Résiliation (PDF)
                        </label>
                        <input type="file" class="form-control" name="resiliation_file" id="resiliation_file" 
                               accept=".pdf" required>
                        <div class="form-text">
                            Joignez le contrat de résiliation signé de l'assurance.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="resiliation_expires_at" class="form-label fw-semibold">
                            <i class="fas fa-calendar-alt text-warning me-1"></i> Date d'expiration du contrat
                        </label>
                        <input type="date" class="form-control" name="resiliation_expires_at" id="resiliation_expires_at">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="save-resiliation-contract">
                    <i class="fas fa-save me-2"></i> Enregistrer le contrat
                </button>
            </div>
        </div>
    </div>
</div>


<script>
                $(document).ready(function () {
                    // Gestion des onglets principaux
                    $('.tabs-nav .nav-link').on('click', function (e) {
                        e.preventDefault();
                        $('.tabs-nav .nav-link').removeClass('active');
                        $(this).addClass('active');
                        $('.tab-content').hide().removeClass('active');
                        const tabId = $(this).data('tab');
                        $('#' + tabId).show().addClass('active');

                        // Automatically load and show default sub-tab content
                        if (tabId === 'companyDocuments') {
                            $('#companyDocumentsTabs .nav-link:first').trigger('click'); // Trigger first sub-tab (document societé)
                        } else if (tabId === 'vehicule') {
                            loadVehicles();
                        } else if (tabId === 'jrsFerie') {
                            $('#jrsFerieTabs .nav-link:first').trigger('click'); // Trigger first sub-tab for Jours Fériés
                        } else if (tabId === 'bpaie') {
                            $('#bpaieTabs .nav-link:first').trigger('click'); // Activate first sub-tab
                        }
                    });

                    // Gestion des sous-onglets dans Documents Société
                    $('#companyDocumentsTabs .nav-link').on('click', function (e) {
                        e.preventDefault();
                        $('#companyDocumentsTabs .nav-link').removeClass('active');
                        $(this).addClass('active');
                        $('.sub-tab-content').hide().removeClass('active');
                        const subTabId = $(this).data('subtab');
                        $('#' + subTabId).show().addClass('active');

                        if (subTabId === 'documentSociete') {
                            loadDocuments(); // Load documents for document societé
                        } else if (subTabId === 'vehicule') {
                            loadVehicles();
                        }
                    });

                    // Gestion des sous-onglets dans Jours Fériés
                    $('#jrsFerieTabs .nav-link').on('click', function (e) {
                        e.preventDefault();
                        $('#jrsFerieTabs .nav-link').removeClass('active');
                        $(this).addClass('active');
                        $('.sub-tab-content').hide();
                        const subtabId = $(this).data('subtab');
                        $('#' + subtabId).show();
                    });

                    // Gestion des sous-onglets dans Bulletin de Paie
                    $('#bpaieTabs .nav-link').on('click', function (e) {
                        e.preventDefault();
                        $('#bpaieTabs .nav-link').removeClass('active');
                        $(this).addClass('active');
                        $('.sub-tab-content').hide().removeClass('active');
                        const subTabId = $(this).data('subtab');
                        $('#' + subTabId).show().addClass('active');
                    });

                    // Validation des fichiers uploadés
                    $('input[type="file"]').on('change', function () {
                        const file = this.files[0];
                        if (file) {
                            const maxSize = 2 * 1024 * 1024; // 2MB
                            const allowedTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];

                            if (file.size > maxSize) {
                                Swal.fire('Erreur', 'Le fichier ne doit pas dépasser 2 Mo.', 'error');
                                this.value = '';
                            } else if (!allowedTypes.includes(file.type)) {
                                Swal.fire('Erreur', 'Seuls les formats PDF, PNG et JPG sont acceptés.', 'error');
                                this.value = '';
                            }
                        }
                    });

                    // Load Vehicles
               function loadVehicles(page = 1) {
                const search = $('#vehicle-search-input').val() || '';
    $.ajax({
        url: '{{ route("vehicles.get") }}',
        type: 'GET',
        data: { page: page ,search: search },
        success: function (response) {
            const tbody = $('#vehicles-table tbody');
            tbody.empty();

            $('#vehicle-count').text(
                response.data.pagination?.total 
                    ? `${response.data.pagination.total} véhicule(s)` 
                    : '0 véhicule(s)'
            );

            if (!response.success || !response.data?.vehicles?.length) {
                tbody.html('<tr><td colspan="7" class="text-center py-4">Aucun véhicule trouvé</td></tr>');
                $('#simple-pagination').remove();
                return;
            }

            // ====================== TON CODE ORIGINAL ICI ======================
            response.data.vehicles.forEach(vehicle => {
                console.log('Processing vehicle:', vehicle);

                // Helper function to format document cell (copie-colle ta fonction)
                const formatDocumentCell = (doc, docType) => {
                    let statusHtml = '';
                    if (doc && doc.expires_at) {
                        const currentDate = new Date();
                        const expirationDate = new Date(doc.expires_at);
                        const daysUntilExpiration = Math.round((expirationDate - currentDate) / (1000 * 60 * 60 * 24));

                        if (expirationDate < currentDate) {
                            statusHtml = '<span class="badge bg-danger mt-1"><i class="fas fa-times-circle me-1"></i>Expiré</span>';
                        } else if (daysUntilExpiration <= 30) {
                            statusHtml = '<span class="badge bg-warning mt-1"><i class="fas fa-exclamation-triangle me-1"></i>À renouveler</span>';
                        } else {
                            statusHtml = '<span class="badge bg-success mt-1"><i class="fas fa-check-circle me-1"></i>Valide</span>';
                        }
                    }

                    return `
                        <td>
                            <input type="file" class="form-control form-control-sm mb-1" name="${docType}_file[${vehicle.id}]" accept=".pdf">
                            ${doc && doc.path ? `
                                <small class="text-success">Fichier actuel: <a href="${doc.path}" target="_blank">${doc.file_name}</a></small><br>
                            ` : `
                                <small class="text-muted">Aucun fichier</small><br>
                            `}
                            <input type="date" class="form-control form-control-sm mt-1" name="${docType}_expires_at[${vehicle.id}]" value="${doc?.expires_at || ''}">
                            ${statusHtml}
                        </td>
                    `;
                };

                const assurance       = formatDocumentCell(vehicle.assurance,       'assurance');
                const visiteTechnique = formatDocumentCell(vehicle.visite_technique, 'visite_technique');
                const carteGrise      = formatDocumentCell(vehicle.carte_grise,      'carte_grise');
                const contratAchat    = formatDocumentCell(vehicle.contrat_achat,    'contrat_achat');

                const autresDocuments = vehicle.autres_documents && vehicle.autres_documents.length > 0 ? `
                    <td>
                        <input type="file" class="form-control form-control-sm mb-1" name="autres_documents[${vehicle.id}][]" multiple>
                        <ul class="list-group list-group-flush mt-1">
                            ${vehicle.autres_documents.map(doc => {
                                let docStatus = '';
                                if (doc.expires_at) {
                                    const currentDate = new Date();
                                    const expirationDate = new Date(doc.expires_at);
                                    const daysUntilExpiration = Math.round((expirationDate - currentDate) / (1000 * 60 * 60 * 24));
                                    if (expirationDate < currentDate) {
                                        docStatus = '<span class="badge bg-danger ms-2"><i class="fas fa-times-circle me-1"></i>Expiré</span>';
                                    } else if (daysUntilExpiration <= 30) {
                                        docStatus = '<span class="badge bg-warning ms-2"><i class="fas fa-exclamation-triangle me-1"></i>À renouveler</span>';
                                    } else {
                                        docStatus = '<span class="badge bg-success ms-2"><i class="fas fa-check-circle me-1"></i>Valide</span>';
                                    }
                                }
                                return `
                                    <li class="list-group-item py-1">
                                        <small class="text-success">
                                            <a href="${doc.path}" target="_blank">${doc.file_name}</a>
                                            ${docStatus}
                                        </small>
                                    </li>
                                `;
                            }).join('')}
                        </ul>
                    </td>
                ` : `
                    <td>
                        <input type="file" class="form-control form-control-sm mb-1" name="autres_documents[${vehicle.id}][]" multiple>
                        <small class="text-muted">Aucun fichier</small>
                    </td>
                `;

               tbody.append(`
   <tr class="${vehicle.resilie == 1 || vehicle.resilie === true ? 'resilie' : ''}">
        <td>
            <input type="text" class="form-control form-control-sm" name="marque[${vehicle.id}]" value="${vehicle.marque || ''}" placeholder="Ex: Renault">
        </td>
        <td>
            {{-- ✅ Matricule en 2ème position --}}
            <input type="text" class="form-control form-control-sm" name="matricule[${vehicle.id}]" value="${vehicle.matricule || ''}">
        </td>
      <td>
    <select class="form-select form-select-sm" name="type[${vehicle.id}]" style="min-width: 120px; width: 120px;">
        <option value="normale" ${vehicle.type === 'normale' ? 'selected' : ''}>Normale</option>
        <option value="engins"  ${vehicle.type === 'engins'  ? 'selected' : ''}>Engins</option>
    </select>
</td>

        ${assurance}
        ${visiteTechnique}
        ${carteGrise}
        ${contratAchat}
        ${autresDocuments}

        <td class="text-center align-middle">
            <div class="btn-group btn-group-sm" role="group">
                <!-- Sauvegarder -->
                <button type="button" 
                        class="btn btn-primary save-vehicle-btn" 
                        data-vehicle-id="${vehicle.id}"
                        title="Sauvegarder les modifications">
                    <i class="fas fa-save"></i>
                </button>

                <!-- Générer lettre de résiliation -->
                <button type="button" 
                        class="btn btn-warning generate-resiliation-btn" 
                        data-vehicle-id="${vehicle.id}"
                        title="Générer lettre de résiliation d'assurance">
                    <i class="fas fa-file-pdf"></i>
                </button>

                <!-- Ajouter contrat de résiliation -->
                <button type="button" 
                        class="btn btn-info add-resiliation-contract-btn" 
                        data-vehicle-id="${vehicle.id}"
                        data-matricule="${vehicle.matricule || ''}"
                        title="Ajouter contrat de résiliation">
                    <i class="fas fa-file-contract"></i>
                </button>

                <!-- Supprimer -->
                <button type="button" 
                        class="btn btn-danger delete-vehicle-btn"
                        data-vehicle-id="${vehicle.id}"
                        data-matricule="${vehicle.matricule?.replace(/"/g, '&quot;') || 'inconnu'}"
                        title="Supprimer ce véhicule">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </td>
    </tr>
`);
            });

            // Suppression d'un véhicule
$(document).on('click', '.delete-vehicle-btn', function () {
    const vehicleId   = $(this).data('vehicle-id');
    const matricule   = $(this).data('matricule');
    const $row        = $(this).closest('tr');

    Swal.fire({
        title: 'Supprimer ce véhicule ?',
        html: `Vous allez supprimer le véhicule <strong>${matricule}</strong>.<br>
               Cette action est <b>irréversible</b>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash-alt me-2"></i> Oui, supprimer',
        cancelButtonText: '<i class="fas fa-times me-2"></i> Annuler'
    }).then((result) => {
        if (!result.isConfirmed) return;

        $('#loadingModal').modal('show');

        $.ajax({
            url: `/vehicles/${vehicleId}`,           // ← adaptez selon votre route
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $('#loadingModal').modal('hide');
                
                Swal.fire({
                    title: 'Supprimé !',
                    text: response.message || 'Véhicule supprimé avec succès.',
                    icon: 'success',
                    timer: 1800
                });

                $row.fadeOut(400, function() {
                    $(this).remove();
                    // Optionnel : mettre à jour le compteur
                    const count = $('#vehicles-table tbody tr').length;
                    $('#vehicle-count').text(`${count} véhicule(s)`);
                });
            },
            error: function (xhr) {
                $('#loadingModal').modal('hide');
                
                let errorMsg = 'Erreur lors de la suppression.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    title: 'Erreur',
                    html: errorMsg,
                    icon: 'error'
                });
                console.error('Delete error:', xhr.responseJSON);
            }
        });
    });
});

// Recherche véhicules
$('#vehicle-search-form').on('submit', function(e) {
    e.preventDefault();
    loadVehicles(1);
});

$('#vehicle-search-reset').on('click', function() {
    $('#vehicle-search-input').val('');
    loadVehicles(1);
});

// ====================== AJOUT CONTRAT DE RÉSILIATION ======================

// Ouvrir le modal quand on clique sur l'icône contrat
$(document).on('click', '.add-resiliation-contract-btn', function () {
    const vehicleId = $(this).data('vehicle-id');
    const matricule = $(this).data('matricule');

    $('#resiliation_vehicle_id').val(vehicleId);
    $('#resiliationContractModalLabel').html(`
        <i class="fas fa-file-contract me-2"></i> Contrat de Résiliation - ${matricule}
    `);

    $('#resiliation-contract-form')[0].reset();
    $('#resiliationContractModal').modal('show');
});

// Sauvegarder le contrat de résiliation
$('#save-resiliation-contract').on('click', function () {
    const form = $('#resiliation-contract-form')[0];
    const formData = new FormData(form);
    const vehicleId = $('#resiliation_vehicle_id').val();

    if (!formData.get('resiliation_file')) {
        Swal.fire('Erreur', 'Veuillez sélectionner un fichier PDF.', 'error');
        return;
    }

    $('#loadingModal').modal('show');

    $.ajax({
        url: `/vehicles/${vehicleId}/add-resiliation-contract`,   // ← tu devras créer cette route/méthode
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            $('#loadingModal').modal('hide');
            $('#resiliationContractModal').modal('hide');
            Swal.fire('Succès', response.message || 'Contrat de résiliation enregistré.', 'success');
            loadVehicles(); // recharger la table
        },
        error: function (xhr) {
            $('#loadingModal').modal('hide');
            Swal.fire('Erreur', xhr.responseJSON?.message || 'Impossible d\'enregistrer le contrat.', 'error');
        }
    });
});




// ====================== GÉNÉRATION PDF RÉSILIATION ======================
$(document).on('click', '.generate-resiliation-btn', function () {
    const vehicleId = $(this).data('vehicle-id');
    $('#loadingModal').modal('show');

    fetch(`/vehicles/${vehicleId}/resiliation-pdf`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur lors de la génération du PDF');
        return response.blob();
    })
    .then(blob => {
        $('#loadingModal').modal('hide');

        // Créer un lien temporaire pour télécharger le PDF
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `resiliation-assurance-vehicule-${vehicleId}.pdf`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);

        // Message de succès
        Swal.fire({
            title: 'Succès !',
            text: 'La lettre de résiliation a été générée et téléchargée avec succès.',
            icon: 'success',
            confirmButtonText: 'OK'
        });
    })
    .catch(error => {
        $('#loadingModal').modal('hide');
        Swal.fire({
            title: 'Erreur',
            text: error.message || 'Impossible de générer la lettre de résiliation.',
            icon: 'error'
        });
        console.error('Erreur résiliation PDF:', error);
    });
});
            // ====================== FIN DE TON CODE ORIGINAL ======================

            // Ajout de la pagination très simple
            const pag = response.data.pagination || { current_page: 1, last_page: 1 };
            const paginationHtml = `
                <div id="simple-pagination" class="d-flex justify-content-between align-items-center mt-3">
                    <button class="btn btn-sm btn-outline-secondary prev-page" 
                            ${pag.current_page <= 1 ? 'disabled' : ''} 
                            data-page="${pag.current_page - 1}">
                        ← Précédent
                    </button>
                    <span class="align-self-center text-muted">
                        Page ${pag.current_page} sur ${pag.last_page}
                    </span>
                    <button class="btn btn-sm btn-outline-secondary next-page" 
                            ${pag.current_page >= pag.last_page ? 'disabled' : ''} 
                            data-page="${pag.current_page + 1}">
                        Suivant →
                    </button>
                </div>
            `;

            $('#simple-pagination').remove();
            $('#vehicles-table').after(paginationHtml);
        },
        error: function (xhr) {
            console.error('AJAX Error:', xhr);
            Swal.fire('Erreur', 'Impossible de charger les véhicules', 'error');
        }
    });
}

// Gestion des clics Précédent / Suivant
$(document).on('click', '#simple-pagination button', function() {
    const page = parseInt($(this).data('page'));
    if (page > 0 && !$(this).prop('disabled')) {
        loadVehicles(page);
    }
});

// Chargement initial de la page 1 quand l'onglet "vehicule" est activé
$('#companyDocumentsTabs a[data-subtab="vehicule"]').on('shown.bs.tab', function () {
    loadVehicles(1);
});
                    // Helper function to check if a date is near expiration (within 15 days)
                    function isNearExpiration(expirationDate) {
                        const today = new Date();
                        const expDate = new Date(expirationDate);
                        const diffTime = expDate - today;
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        return diffDays > 0 && diffDays <= 15;
                    }

                    // Submit New Vehicle Form
                    $('#vehicle-form').on('submit', function (e) {
                        e.preventDefault();
                        const form = $(this);
                        $('#loadingModal').modal('show');

                        $.ajax({
                            url: form.attr('action'),
                            method: 'POST',
                            data: new FormData(this),
                            processData: false,
                            contentType: false,
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            success: function (response) {
                                $('#loadingModal').modal('hide');
                                form[0].reset();
                                Swal.fire('Succès', response.message, 'success');
                                loadVehicles();
                            },
                            error: function (xhr) {
                                $('#loadingModal').modal('hide');
                                let errorMessage = xhr.responseJSON?.message || 'Erreur lors de l\'ajout du véhicule.';
                                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                                } else if (xhr.status === 419) {
                                    errorMessage = 'Session expirée. Veuillez recharger la page.';
                                }
                                Swal.fire({
                                    title: 'Erreur',
                                    html: errorMessage,
                                    icon: 'error'
                                });
                                console.error('AJAX Error:', xhr.responseJSON);
                            }
                        });
                    });

                    // Submit Add Vehicle Document Form
                    $('#add-vehicle-document-form').on('submit', function (e) {
                        e.preventDefault();
                        const vehicleId = $('#vehicle_id').val();
                        console.log('Vehicle ID:', vehicleId);
                        if (!vehicleId) {
                            $('#loadingModal').modal('hide');
                            Swal.fire('Erreur', 'ID du véhicule manquant.', 'error');
                            return;
                        }
                        $('#loadingModal').modal('show');

                        const url = '{{ route("vehicles.add.additional", ":id") }}'.replace(':id', vehicleId);
                        console.log('Generated URL:', url);

                        $.ajax({
                            url: url,
                            method: 'POST',
                            data: new FormData(this),
                            processData: false,
                            contentType: false,
                            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            success: function (response) {
                                $('#loadingModal').modal('hide');
                                $('#addVehicleDocumentModal').modal('hide');
                                Swal.fire('Succès', response.message, 'success');
                                loadVehicles();
                            },
                            error: function (xhr) {
                                $('#loadingModal').modal('hide');
                                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de l\'ajout.', 'error');
                                console.error('AJAX Error:', xhr.responseJSON);
                            }
                        });
                    });

                    // Handle Add Document Button
                    $(document).on('click', '.add-document-btn', function() {
                        const vehicleId = $(this).data('vehicle-id');
                        $('#vehicle_id').val(vehicleId);
                        $('#addVehicleDocumentModal .modal-title-text').text(`Ajouter un Document pour le Véhicule ${vehicleId}`);
                    });

                    // Handle Document Type Change
                    $('#vehicle_document_type').on('change', function() {
                        $('#vehicle_custom_label_field').toggleClass('d-none', this.value !== 'custom_document');
                    });

                    // Save Vehicle Updates
                $(document).on('click', '.save-vehicle-btn', function () {
                    const vehicleId = $(this).data('vehicle-id');
                    console.log('Saving vehicle ID:', vehicleId);

                    const matriculeInput = $(`input[name="matricule[${vehicleId}]"]`);
                    const matriculeValue = matriculeInput.val().trim();
                    if (!matriculeValue) {
                        Swal.fire({
                            title: 'Erreur',
                            html: 'Le matricule ne peut pas être vide.',
                            icon: 'error'
                        });
                        return;
                    }

                    const formData = new FormData();
                    formData.append('id', vehicleId);
                    formData.append('matricule', matriculeValue);

                                const marqueValue = $(`input[name="marque[${vehicleId}]"]`).val().trim();
                formData.append('marque', marqueValue);

                // ✅ Récupérer la valeur du SELECT type (et non plus d'un input)
            const typeValue = $(`select[name="type[${vehicleId}]"]`).val() || 'normale';
            formData.append('type', typeValue);


                    // Append file inputs and expiration dates
                    ['assurance', 'visite_technique', 'carte_grise', 'contrat_achat'].forEach(docType => {
                        const fileInput = $(`input[name="${docType}_file[${vehicleId}]"]`)[0].files[0];
                        const expiresAt = $(`input[name="${docType}_expires_at[${vehicleId}]"]`).val();
                        if (fileInput) {
                            formData.append(`${docType}_file`, fileInput);
                            formData.append(`${docType}_expires_at`, expiresAt || '');
                        } else if (expiresAt) {
                            formData.append(`${docType}_expires_at`, expiresAt);
                        }
                    });

                    // Append additional documents
                    const files = $(`input[name="autres_documents[${vehicleId}][]"]`)[0].files;
                    const expiresAtArray = $(`input[name="autres_documents_expires_at[${vehicleId}][]"]`).map(function() { return $(this).val(); }).get();
                    const labels = $(`input[name="custom_document_label[${vehicleId}][]"]`).map(function() { return $(this).val(); }).get();

                    for (let i = 0; i < files.length; i++) {
                        formData.append(`autres_documents[${i}]`, files[i]);
                        formData.append(`autres_documents_expires_at[${i}]`, expiresAtArray[i] || '');
                        formData.append(`custom_document_label[${i}]`, labels[i] || '');
                    }

                    // Log FormData content
                    for (let pair of formData.entries()) {
                        console.log(`${pair[0]}: ${pair[1]}`);
                    }

                    $('#loadingModal').modal('show');
                    $.ajax({
                        url: '{{ route("vehicles.update", ["id" => ":id"]) }}'.replace(':id', vehicleId),
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'X-HTTP-Method-Override': 'PUT'
                        },
                        success: function (response) {
                            $('#loadingModal').modal('hide');
                            Swal.fire({
                                title: 'Succès',
                                html: response.message,
                                icon: 'success'
                            });
                            loadVehicles();
                        },
                        error: function (xhr) {
                            $('#loadingModal').modal('hide');
                            Swal.fire({
                                title: 'Erreur',
                                html: xhr.responseJSON?.message || 'Erreur lors de la mise à jour.',
                                icon: 'error'
                            });
                            console.error('Save Error:', xhr.responseJSON);
                        }
                    });
                });
                $('#company-documents-form').on('submit', function (e) {
                        e.preventDefault();
                        let hasErrors = false;

                        // Validation des champs d'assurance véhicule
                        $('#company-documents-form input[name^="assurance_vehicule["][type="file"]').each(function () {
                            const fileInput = $(this);
                            const index = fileInput.attr('name').match(/\[(\d+)\]/)[1];
                            const dateInput = $(`input[name="assurance_vehicule[${index}][expires_at]"]`);
                            const hasExistingFile = fileInput.closest('td').find('div.bg-light').length > 0;

                            if (!hasExistingFile && fileInput[0].files.length === 0) {
                                Swal.fire('Erreur', `Veuillez uploader un fichier pour Assurance Véhicule ${parseInt(index) + 1}.`, 'error');
                                hasErrors = true;
                                return false;
                            }
                            if (dateInput.val() && new Date(dateInput.val()) <= new Date()) {
                                Swal.fire('Erreur', `La date d'expiration pour Assurance Véhicule ${parseInt(index) + 1} doit être postérieure à aujourd'hui.`, 'error');
                                hasErrors = true;
                                return false;
                            }
                        });

                        if (hasErrors) return;

                        $('#loadingModal').modal('show');
                        const form = $(this);
                        $.ajax({
                            url: form.attr('action'),
                            method: 'POST',
                            data: new FormData(this),
                            processData: false,
                            contentType: false,
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            xhr: function () {
                                const xhr = new window.XMLHttpRequest();
                                xhr.upload.addEventListener('progress', function (e) {
                                    if (e.lengthComputable) {
                                        const percentComplete = (e.loaded / e.total) * 100;
                                        $('#loadingModal .modal-body p').text(`Téléchargement: ${Math.round(percentComplete)}%`);
                                    }
                                }, false);
                                return xhr;
                            },
                            success: function (response) {
                                $('#loadingModal').modal('hide');
                                Swal.fire('Succès !', response.message || 'Documents enregistrés.', 'success');
                                loadDocuments();
                            },
                            error: function (xhr) {
                                $('#loadingModal').modal('hide');
                                const errors = xhr.responseJSON?.errors || { message: 'Erreur lors de l\'enregistrement.' };
                                let errorMessage = 'Erreur lors de l\'enregistrement.';
                                if (xhr.responseJSON?.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (errors) {
                                    errorMessage = Object.values(errors).flat().map(err => `<p>${err}</p>`).join('');
                                }
                                Swal.fire({
                                    title: 'Erreur !',
                                    html: errorMessage + (xhr.responseJSON?.error ? '<br>Détails : ' + xhr.responseJSON.error : ''),
                                    icon: 'error'
                                });
                                console.error('Erreur AJAX (Enregistrer):', xhr.responseJSON);
                            }
                        });
                    });

                    // Load Documents
                    function loadDocuments() {
                        $.ajax({
                            url: '{{ route("company.documents.get") }}',
                            type: 'GET',
                            success: function (response) {
                                console.log('AJAX Response:', response);
                                const tableBody = $('#company-documents-form table tbody');
                                tableBody.empty();

                                if (response.success && response.data && response.data.documents && response.data.documents.length > 0) {
                                    const vehicleInsurances = response.data.documents.filter(doc => doc.label.includes('Assurance Véhicule'));
                                    if (vehicleInsurances.length > 0) {
                                        vehicleInsurances.forEach((insurance, index) => {
                                            const expiresAt = insurance.expires_at || '';
                                            const daysUntilExpiration = insurance.expires_at ? Math.round((new Date(insurance.expires_at) - new Date()) / (1000 * 60 * 60 * 24)) : null;
                                            let statusBadge = '';
                                            let expirationText = '';
                                            if (daysUntilExpiration !== null) {
                                                if (daysUntilExpiration < 0) {
                                                    statusBadge = `<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Expiré</span>`;
                                                    expirationText = `<small class="text-danger"><i class="fas fa-times-circle me-1"></i>Expiré depuis ${Math.abs(daysUntilExpiration)} jour(s)</small>`;
                                                } else if (daysUntilExpiration >= 0 && daysUntilExpiration <= 30) {
                                                    statusBadge = `<span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>À renouveler</span>`;
                                                    expirationText = `<small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans ${daysUntilExpiration} jour(s)</small>`;
                                                } else {
                                                    statusBadge = `<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Valide</span>`;
                                                    expirationText = `<small class="text-success"><i class="fas fa-check-circle me-1"></i>Valide (expire dans ${daysUntilExpiration} jour(s))</small>`;
                                                }
                                            } else {
                                                statusBadge = `<span class="badge bg-secondary"><i class="fas fa-question-circle me-1"></i>Non définie</span>`;
                                            }

                                            let additionalDocumentsHtml = '';
                                            if (insurance.additional_documents && insurance.additional_documents.length > 0) {
                                                additionalDocumentsHtml = `
                                                    <div>
                                                        <button type="button" class="btn btn-primary btn-sm view-documents-btn" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#viewDocumentsModal" 
                                                                data-vehicle-index="${index}">
                                                            <i class="fas fa-eye me-1"></i>Voir
                                                        </button>
                                                    </div>`;
                                            } else {
                                                additionalDocumentsHtml = `<small class="text-muted">Aucun document associé</small>`;
                                            }

                                            tableBody.append(`
                                                <tr>
                                                    <td class="align-middle">
                                                        <span class="badge bg-secondary">${index + 1}</span>
                                                    </td>
                                                    <td>
                                                        <input type="hidden" name="vehicle_ids[]" value="${index}">
                                                        <div class="mb-2">
                                                            <input type="file" class="form-control form-control-sm" name="assurance_vehicule[${index}][file]" accept=".pdf">
                                                        </div>
                                                        ${insurance.path && insurance.file_name ? `
                                                            <div class="p-2 bg-light rounded">
                                                                <small class="text-success d-flex align-items-center">
                                                                    <i class="fas fa-check-circle me-1"></i>
                                                                    <span class="me-2">Fichier actuel :</span>
                                                                    <a href="${insurance.path}" target="_blank" class="text-decoration-none fw-semibold view-document">
                                                                        <i class="fas fa-external-link-alt me-1"></i>${insurance.file_name}
                                                                    </a>
                                                                </small>
                                                            </div>
                                                        ` : `
                                                            <div class="p-2 bg-warning bg-opacity-10 rounded">
                                                                <small class="text-warning">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i>Aucun fichier disponible
                                                                </small>
                                                            </div>
                                                        `}
                                                    </td>
                                                    <td class="align-middle">
                                                        <input type="date" class="form-control form-control-sm" name="assurance_vehicule[${index}][expires_at]" value="${expiresAt}">
                                                        ${expiresAt && expirationText ? `<div class="mt-1">${expirationText}</div>` : ''}
                                                    </td>
                                                    <td class="align-middle">
                                                        ${additionalDocumentsHtml}
                                                    </td>
                                                    <td class="text-center align-middle">${statusBadge}</td>
                                                    <td class="text-center align-middle">
                                                        <button type="button" class="btn btn-success btn-sm ms-2 add-document-btn" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#addDocumentModal" 
                                                                data-vehicle-index="${index}">
                                                            <i class="fas fa-plus me-1"></i>Ajouter un Document
                                                        </button>
                                                    </td>
                                                </tr>
                                            `);
                                        });

                                        // Gestionnaire pour le bouton "Voir" pour remplir la modale
                                        $(document).on('click', '.view-documents-btn', function() {
                                            const vehicleIndex = $(this).data('vehicle-index');
                                            console.log('Vehicle Index:', vehicleIndex);
                                            const insurance = vehicleInsurances[vehicleIndex];
                                            const documents = insurance ? (insurance.additional_documents || []) : [];
                                            console.log('Documents:', documents);
                                            const documentsList = $('#documents-list');
                                            documentsList.empty();

                                            if (documents.length > 0) {
                                                documents.forEach(doc => {
                                                    const docExpiresAt = doc.expires_at || '';
                                                    let docStatusText = '';
                                                    if (docExpiresAt) {
                                                        const docDaysUntilExpiration = Math.round((new Date(doc.expires_at) - new Date()) / (1000 * 60 * 60 * 24));
                                                        if (docDaysUntilExpiration < 0) {
                                                            docStatusText = `<small class="text-danger"><i class="fas fa-times-circle me-1"></i>Expiré depuis ${Math.abs(docDaysUntilExpiration)} jour(s)</small>`;
                                                        } else if (docDaysUntilExpiration <= 30) {
                                                            docStatusText = `<small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans ${docDaysUntilExpiration} jour(s)</small>`;
                                                        } else {
                                                            docStatusText = `<small class="text-success"><i class="fas fa-check-circle me-1"></i>Valide (expire dans ${docDaysUntilExpiration} jour(s))</small>`;
                                                        }
                                                    }
                                                    const docPath = doc.path.startsWith('/storage/') ? doc.path : `/storage/${doc.path}`;
                                                    console.log('Document Path:', docPath);
                                                    documentsList.append(`
                                                        <li class="list-group-item py-2">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span>
                                                                    <i class="fas fa-file-pdf text-danger me-1"></i>
                                                                    ${doc.type === 'custom_document' ? doc.custom_document_label : doc.type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}
                                                                </span>
                                                                <a href="${docPath}" target="_blank" class="text-decoration-none">
                                                                    <i class="fas fa-eye me-1"></i>Voir le document
                                                                </a>
                                                            </div>
                                                            ${docStatusText ? `<div class="mt-1">${docStatusText}</div>` : ''}
                                                        </li>
                                                    `);
                                                });
                                            } else {
                                                documentsList.append('<li class="list-group-item text-muted">Aucun document associé</li>');
                                            }

                                            $('#viewDocumentsModalLabel').text(`Documents Associés - Véhicule ${vehicleIndex + 1}`);
                                        });
                                    } else {
                                        tableBody.append('<tr><td colspan="6" class="text-muted text-center">Aucune assurance véhicule enregistrée.</td></tr>');
                                    }
                                } else {
                                    tableBody.append('<tr><td colspan="6" class="text-muted text-center">Aucune assurance véhicule enregistrée.</td></tr>');
                                }
                            },
                            error: function (xhr) {
                                $('#company-documents-form table tbody').html('<tr><td colspan="6" class="text-danger text-center">Erreur lors du chargement des données.</td></tr>');
                                console.error('Erreur AJAX (loadDocuments):', xhr.responseJSON);
                            }
                        });
                    }

                    // Création de la table des salaires
                    $('#createSalaryTable').on('click', function () {
                        const responseMessage = $('#responseMessage');
                        responseMessage.html('<div class="alert alert-info">Création en cours...</div>');
                        $.ajax({
                            url: '{{ route("create.salary.table") }}',
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            success: function (data) {
                                responseMessage.html(`<div class="alert alert-success">${data.success}</div>`);
                            },
                            error: function (xhr) {
                                responseMessage.html(`<div class="alert alert-danger">${xhr.responseJSON?.error || 'Erreur.'}</div>`);
                                console.error('Erreur AJAX (createSalaryTable):', xhr.responseJSON);
                            }
                        });
                    });

                    // Gestion des modals pour ajouter un document
                    document.addEventListener('DOMContentLoaded', function () {
                        const addDocumentModal = document.getElementById('addDocumentModal');
                        const form = document.getElementById('add-document-form');
                        const modalTitle = document.querySelector('#addDocumentModalLabel .modal-title-text');
                        const documentVehicleIndex = document.getElementById('document_vehicle_index');
                        const documentTypeSelect = document.getElementById('document_type');
                        const customLabelField = document.getElementById('custom_label_field');

                        function resetForm() {
                            form.reset();
                            customLabelField.classList.add('d-none');
                        }

                        document.querySelectorAll('.add-document-btn').forEach(button => {
                            button.addEventListener('click', function () {
                                const vehicleIndex = this.getAttribute('data-vehicle-index');
                                modalTitle.textContent = `Ajouter un Document pour le Véhicule #${parseInt(vehicleIndex) + 1}`;
                                documentVehicleIndex.value = vehicleIndex;
                                resetForm();
                            });
                        });

                        documentTypeSelect.addEventListener('change', function () {
                            if (this.value === 'custom_document') {
                                customLabelField.classList.remove('d-none');
                            } else {
                                customLabelField.classList.add('d-none');
                                document.getElementById('custom_document_label').value = '';
                            }
                        });

                        addDocumentModal.addEventListener('hidden.bs.modal', resetForm);
                    });

                    // Soumission du formulaire d'ajout de document
                    $('#add-document-form').submit(function(e) {
                        e.preventDefault();
                        $('#loadingModal').modal('show');
                        
                        $.ajax({
                            url: $(this).attr('action'),
                            method: 'POST',
                            data: new FormData(this),
                            processData: false,
                            contentType: false,
                            success: function() {
                                $('#loadingModal').modal('hide');
                                $('#addDocumentModal').modal('hide');
                                Swal.fire('Succès!', 'Document ajouté avec succès', 'success');
                                loadDocuments();
                            },
                            error: function(xhr) {
                                $('#loadingModal').modal('hide');
                                Swal.fire('Erreur!', xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
                            }
                        });
                    });

                    // Charger les données au démarrage si l'onglet est actif
                    if ($('#companyDocuments').hasClass('active')) {
                        $('#companyDocumentsTabs .nav-link:first').trigger('click'); // Load document societé by default
                    }
                    if ($('#vehicule').hasClass('active')) {
                        loadVehicles();
                    }
                    if ($('#jrsFerie').hasClass('active')) {
                        $('#jrsFerieTabs .nav-link:first').trigger('click'); // Load first Jours Fériés sub-tab by default
                    }
                    function showErrorModal(message) {
                        $('#errorModalBody').html(message);
                        const modal = new bootstrap.Modal(document.getElementById('errorModal'));
                        modal.show();
                    }
                function calculerJours($row) {
                    const dateDebut = $row.find('input[name="date_debut[]"]').val();
                    const dateFin = $row.find('input[name="date_fin[]"]').val();

                    if (dateDebut && dateFin) {
                        // Créer des objets Date et normaliser à minuit
                        let debut = new Date(dateDebut);
                        let fin = new Date(dateFin);
                        debut.setHours(0, 0, 0, 0); // Réinitialiser à minuit
                        fin.setHours(0, 0, 0, 0);   // Réinitialiser à minuit

                        // Calculer la différence en millisecondes
                        const diffTime = fin - debut;

                        // Convertir en jours (inclusif) et forcer un entier
                        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;

                        if (!isNaN(diffDays) && diffDays >= 0) {
                            $row.find('input[name="nbr_jours[]"]').val(diffDays); // Assigner directement l'entier
                        } /* else {
                            $row.find('input[name="nbr_jours[]"]').val('');
                            showErrorModal("📅 La <strong>date de fin</strong> doit être supérieure ou égale à la <strong>date de début</strong>.");
                        } */
                    } else {
                        $row.find('input[name="nbr_jours[]"]').val('');
                        showErrorModal("📅 Veuillez remplir <strong>la date de début</strong> et <strong>la date de fin</strong>.");
                    }
                }
                    // Lorsqu'on modifie une date_debut ou date_fin, on recalcule les jours
                    $('input[name="date_debut[]"], input[name="date_fin[]"]').on('change', function () {
                        const $row = $(this).closest('tr');
                        calculerJours($row);
                    });
                });

                document.addEventListener('DOMContentLoaded', function () {
                    // Sélectionner tous les champs de date d'expiration
                    const dateInputs = document.querySelectorAll('input[type="date"][id$="_expires_at"]');

                    // Fonction pour calculer les jours et mettre à jour l'affichage
                    function updateExpirationStatus(input) {
                        const currentDate = new Date();
                        currentDate.setHours(0, 0, 0, 0); // Normaliser à minuit (Europe/Paris approx.)
                        const expirationDate = input.value ? new Date(input.value) : null;
                        
                        if (!expirationDate) {
                            input.closest('.input-group').querySelector('.input-group-text').innerHTML = '';
                            input.closest('.col-md-6, .col-md-4').querySelector('.form-text').innerHTML = '';
                            return;
                        }

                        expirationDate.setHours(0, 0, 0, 0); // Normaliser à minuit

                        const diffTime = expirationDate - currentDate;
                        const daysUntilExpiration = Math.floor(diffTime / (1000 * 60 * 60 * 24));

                        const inputGroupText = input.closest('.input-group').querySelector('.input-group-text');
                        const formText = input.closest('.col-md-6, .col-md-4').querySelector('.form-text');

                        // Mettre à jour le badge
                        if (daysUntilExpiration < 0) {
                            inputGroupText.innerHTML = '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Expiré</span>';
                            formText.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle me-1"></i>Expiré depuis ' + Math.abs(daysUntilExpiration) + ' jour(s)</small>';
                        } else if (daysUntilExpiration >= 0 && daysUntilExpiration <= 30) {
                            inputGroupText.innerHTML = '<span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>À renouveler</span>';
                            formText.innerHTML = '<small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>À renouveler dans ' + daysUntilExpiration + ' jour(s)</small>';
                        } else {
                            inputGroupText.innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Valide</span>';
                            formText.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Valide (expire dans ' + daysUntilExpiration + ' jour(s))</small>';
                        }
                    }

                    // Ajouter un écouteur d'événements à chaque champ de date
                    dateInputs.forEach(input => {
                        input.addEventListener('change', function () {
                            updateExpirationStatus(this);
                        });

                        // Initialiser l'affichage au chargement de la page
                        updateExpirationStatus(input);
                    });

                    // Fonction utilitaire pour abs (valeur absolue)
                    function MathAbs(value) {
                        return value < 0 ? -value : value;
                    }
                });


                // Suppression d'un barème de frais professionnel
$(document).on('click', '.delete-frais-pro', function () {
    const id = $(this).data('id');
    const $row = $('#frais-row-' + id);

    Swal.fire({
        title: 'Supprimer ce barème ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash-alt me-2"></i> Oui, supprimer',
        cancelButtonText: '<i class="fas fa-times me-2"></i> Annuler'
    }).then((result) => {
        if (!result.isConfirmed) return;

        $('#loadingModal').modal('show');

        $.ajax({
            url: `/frais-pros/${id}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $('#loadingModal').modal('hide');
                Swal.fire({
                    title: 'Supprimé !',
                    text: response.message || 'Barème supprimé avec succès.',
                    icon: 'success',
                    timer: 1800
                });
                $row.fadeOut(400, function () {
                    $(this).remove();
                });
            },
            error: function (xhr) {
                $('#loadingModal').modal('hide');
                Swal.fire({
                    title: 'Erreur',
                    text: xhr.responseJSON?.message || 'Erreur lors de la suppression.',
                    icon: 'error'
                });
                console.error('Delete frais pro error:', xhr.responseJSON);
            }
        });
    });
});


 $(document).on('click', '.delete-anciennete', function () {
        const id = $(this).data('id');
        const $row = $('#anciennete-row-' + id);

        Swal.fire({
            title: 'Supprimer ce barème ?',
            text: 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash-alt me-2"></i> Oui, supprimer',
            cancelButtonText: '<i class="fas fa-times me-2"></i> Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $('#loadingModal').modal('show');

            $.ajax({
                url: `/anciennete-taux/${id}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    $('#loadingModal').modal('hide');
                    Swal.fire({
                        title: 'Supprimé !',
                        text: response.message || 'Barème supprimé avec succès.',
                        icon: 'success',
                        timer: 1800
                    });
                    $row.fadeOut(400, function () {
                        $(this).remove();
                    });
                },
                error: function (xhr) {
                    $('#loadingModal').modal('hide');
                    Swal.fire({
                        title: 'Erreur',
                        text: xhr.responseJSON?.message || 'Erreur lors de la suppression.',
                        icon: 'error'
                    });
                    console.error('Delete anciennete error:', xhr.responseJSON);
                }
            });
        });
    });

     // ✅ Suppression d'une donnée d'heures supplémentaires
    $(document).on('click', '.delete-heure-supp', function () {
        const id = $(this).data('id');
        const $row = $('#heure-row-' + id);

        Swal.fire({
            title: 'Supprimer cette ligne ?',
            text: 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash-alt me-2"></i> Oui, supprimer',
            cancelButtonText: '<i class="fas fa-times me-2"></i> Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $('#loadingModal').modal('show');

            $.ajax({
                url: `/heures-supp/${id}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    $('#loadingModal').modal('hide');
                    Swal.fire({
                        title: 'Supprimé !',
                        text: response.message || 'Donnée supprimée avec succès.',
                        icon: 'success',
                        timer: 1800
                    });
                    $row.fadeOut(400, function () {
                        $(this).remove();
                    });
                },
                error: function (xhr) {
                    $('#loadingModal').modal('hide');
                    Swal.fire({
                        title: 'Erreur',
                        text: xhr.responseJSON?.message || 'Erreur lors de la suppression.',
                        icon: 'error'
                    });
                    console.error('Delete heure supp error:', xhr.responseJSON);
                }
            });
        });
    });

    // ✅ Suppression d'un jour férié (national ou religieux)
    $(document).on('click', '.delete-jour-ferie', function () {
        const id = $(this).data('id');
        const $row = $('#jourferie-row-' + id);

        Swal.fire({
            title: 'Supprimer ce jour férié ?',
            text: 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash-alt me-2"></i> Oui, supprimer',
            cancelButtonText: '<i class="fas fa-times me-2"></i> Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $('#loadingModal').modal('show');

            $.ajax({
                url: `/jours-feries/${id}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    $('#loadingModal').modal('hide');
                    Swal.fire({
                        title: 'Supprimé !',
                        text: response.message || 'Jour férié supprimé avec succès.',
                        icon: 'success',
                        timer: 1800
                    });
                    $row.fadeOut(400, function () {
                        $(this).remove();
                    });
                },
                error: function (xhr) {
                    $('#loadingModal').modal('hide');
                    Swal.fire({
                        title: 'Erreur',
                        text: xhr.responseJSON?.message || 'Erreur lors de la suppression.',
                        icon: 'error'
                    });
                    console.error('Delete jour ferie error:', xhr.responseJSON);
                }
            });
        });
    });

</script>
@endsection              