@extends('master_page.app')

@section('title')
    Gestion des Ordres de Mission
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <h4 class="fw-bold py-3 mb-4">Gestion des Ordres de Mission</h4>

 <!-- ==================== MODAL AJOUT ORDRE DE MISSION ==================== -->
<div class="modal fade" id="addOrdreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvel Ordre de Mission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="addOrdreForm">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">

                        <!-- Gérant -->
                        <div class="col-12">
                            <label class="form-label">Gérant / Responsable <span class="text-danger">*</span></label>
                            <select name="gerant" id="gerant_select" class="form-control select2" required>
                                <option value="">Sélectionner le gérant</option>
                                @foreach(\App\Models\Salarie::where('statut', 'actif')->get() as $s)
                                    <option value="{{ $s->id }}">{{ $s->matricule ?? '' }} - {{ $s->nom }} {{ $s->prenom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Salariés Participants -->
                        <div class="col-12">
                            <label class="form-label">Salariés participants <span class="text-danger">*</span></label>
                            <select name="salaries[]" id="salaries_select" class="form-control select2" multiple>
                                @foreach(\App\Models\Salarie::where('statut', 'actif')->get() as $s)
                                    <option value="{{ $s->id }}">{{ $s->matricule ?? '' }} - {{ $s->nom }} {{ $s->prenom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Moyen de Transport -->
                        <div class="col-12">
                            <label class="form-label">Moyen de Transport <span class="text-danger">*</span></label>
                            <select name="moyen_transport" id="moyen_transport" class="form-control" required>
                                <option value="">Sélectionner un moyen de transport</option>
                                <option value="transport_public">Transport Public</option>
                                <option value="voiture_mission">Voiture de Mission</option>
                                <option value="voiture_personnelle">Voiture Personnelle</option>
                            </select>
                        </div>

                        <!-- Champ Véhicule de Mission -->
<div class="col-12" id="mission_fields" style="display:none;">
    <label class="form-label">Véhicule de Mission <span class="text-danger">*</span></label>
    <select id="vehicule_mission_select" class="form-control select2">
        <option value="">Sélectionner un véhicule</option>
        @foreach(\App\Models\Vehicle::all() as $v)
          <option value="{{ $v->id }}"
        data-marque="{{ $v->marque }}"
        data-matricule="{{ $v->matricule }}">
    {{ $v->marque }} - {{ $v->matricule }}
</option>
        @endforeach
    </select>
    <input type="hidden" name="marque_mission" id="marque_mission">
    <input type="hidden" name="nplaque_mission" id="nplaque_mission">
</div>

                        <!-- Champs Voiture Personnelle -->
                        <div class="col-md-4" id="perso_marque" style="display:none;">
                            <label class="form-label">Marque Voiture Personnelle</label>
                            <input type="text" name="marque_personnelle" id="marque_personnelle" class="form-control">
                        </div>
                        <div class="col-md-4" id="perso_plaque" style="display:none;">
                            <label class="form-label">N° Plaque Personnelle</label>
                            <input type="text" name="nplaque_p" id="nplaque_p" class="form-control">
                        </div>
                        <div class="col-md-4" id="perso_puissance" style="display:none;">
                            <label class="form-label">Puissance Fiscale (CV)</label>
                            <input type="number" name="puissance_fiscale_p" id="puissance_fiscale_p" class="form-control">
                        </div>

                        <!-- Emplacement & Date -->
                        <div class="col-md-6">
                            <label class="form-label">Emplacement / Destination</label>
                            <input type="text" name="emplacement" class="form-control" value="oujda">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de départ <span class="text-danger">*</span></label>
                            <input type="date" name="date_depart" class="form-control" required>
                        </div>

                        <div class="col-md-6">
    <label class="form-label">Date de retour</label>
    <input type="date" name="date_retour" class="form-control">
</div>

                        <!-- Mission -->
                        <div class="col-12">
                            <label class="form-label">Objet de la mission</label>
                            <textarea name="mission" class="form-control" rows="4" placeholder="Décrivez l'objectif de la mission..."></textarea>
                        </div>

                      


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
    <div class="row mb-4">
        <div class="card">
            <!-- ... Votre card-header et table restent identiques ... -->
            <div class="card-header">
                <div class="row ms-2 me-3 dt-filter-container">
                    <div class="col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2 dt-controls">
                        <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3">
                            <!-- DataTables buttons will be injected here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Validation Rapide -->
<div class="modal fade" id="validateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Validation d'Ordre de Mission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="form-check form-switch d-flex justify-content-center">
                    <input class="form-check-input" type="checkbox" id="ordre_checkbox" style="width: 50px; height: 25px;">
                </div>
                <label for="ordre_checkbox" class="form-label mt-3">
                    Marquer cet ordre de mission comme <strong>validé</strong>
                </label>
                <input type="hidden" id="validate_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="saveValidation">Enregistrer</button>
            </div>
        </div>
    </div>
</div>


<!-- ==================== MODAL FICHE OFFICIELLE ==================== -->
<div class="modal fade" id="ficheModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fiche Ordre de Mission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fiche-content">
                <!-- Contenu chargé dynamiquement -->
            </div>
            <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
    <button type="button" class="btn btn-primary" id="printFicheBtn">Imprimer la fiche</button>
</div>
        </div>
    </div>
</div>


<!-- ==================== MODAL MODIFICATION ORDRE DE MISSION ==================== -->
<div class="modal fade" id="editOrdreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier l'Ordre de Mission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="editOrdreForm">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-body">
                    <div class="row g-3">

                        <!-- Gérant -->
                        <div class="col-12">
                            <label class="form-label">Gérant / Responsable <span class="text-danger">*</span></label>
                            <select name="gerant" id="edit_gerant_select" class="form-control select2" required>
                                <option value="">Sélectionner le gérant</option>
                                @foreach(\App\Models\Salarie::where('statut', 'actif')->get() as $s)
                                    <option value="{{ $s->id }}">{{ $s->matricule ?? '' }} - {{ $s->nom }} {{ $s->prenom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Salariés Participants -->
                        <div class="col-12">
                            <label class="form-label">Salariés participants <span class="text-danger">*</span></label>
                            <select name="salaries[]" id="edit_salaries_select" class="form-control select2" multiple>
                                @foreach(\App\Models\Salarie::where('statut', 'actif')->get() as $s)
                                    <option value="{{ $s->id }}">{{ $s->matricule ?? '' }} - {{ $s->nom }} {{ $s->prenom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Moyen de Transport -->
                        <div class="col-12">
                            <label class="form-label">Moyen de Transport <span class="text-danger">*</span></label>
                            <select name="moyen_transport" id="edit_moyen_transport" class="form-control" required>
                                <option value="">Sélectionner un moyen de transport</option>
                                <option value="transport_public">Transport Public</option>
                                <option value="voiture_mission">Voiture de Mission</option>
                                <option value="voiture_personnelle">Voiture Personnelle</option>
                            </select>
                        </div>

                        <!-- Champs Voiture de Mission -->
                       <div class="col-12" id="edit_mission_fields" style="display:none;">
    <label class="form-label">Véhicule de Mission <span class="text-danger">*</span></label>
    <select id="edit_vehicule_mission_select" class="form-control select2">
        <option value="">Sélectionner un véhicule</option>
        @foreach(\App\Models\Vehicle::all() as $v)
            <option value="{{ $v->id }}"
                    data-marque="{{ $v->marque }}"
                    data-matricule="{{ $v->matricule }}">
                {{ $v->marque }} - {{ $v->matricule }}
            </option>
        @endforeach
    </select>
    <input type="hidden" name="marque_mission" id="edit_marque_mission">
    <input type="hidden" name="nplaque_mission" id="edit_nplaque_mission">
</div>

                        <!-- Champs Voiture Personnelle -->
                        <div class="col-md-4" id="edit_perso_marque" style="display:none;">
                            <label class="form-label">Marque Voiture Personnelle</label>
                            <input type="text" name="marque_personnelle" id="edit_marque_personnelle" class="form-control">
                        </div>
                        <div class="col-md-4" id="edit_perso_plaque" style="display:none;">
                            <label class="form-label">N° Plaque Personnelle</label>
                            <input type="text" name="nplaque_p" id="edit_nplaque_p" class="form-control">
                        </div>
                        <div class="col-md-4" id="edit_perso_puissance" style="display:none;">
                            <label class="form-label">Puissance Fiscale (CV)</label>
                            <input type="number" name="puissance_fiscale_p" id="edit_puissance_fiscale_p" class="form-control">
                        </div>

                        <!-- Emplacement & Date -->
                        <div class="col-md-6">
                            <label class="form-label">Emplacement / Destination</label>
                            <input type="text" name="emplacement" id="edit_emplacement" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de départ <span class="text-danger">*</span></label>
                            <input type="date" name="date_depart" id="edit_date_depart" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                        <label class="form-label">Date de retour</label>
                        <input type="date" name="date_retour" id="edit_date_retour" class="form-control">
                    </div>

                        <!-- Mission -->
                        <div class="col-12">
                            <label class="form-label">Objet de la mission</label>
                            <textarea name="mission" id="edit_mission" class="form-control" rows="4"></textarea>
                        </div>


                          <!-- Heures & Frais -->
                        <div class="col-md-4">
                            <label class="form-label">Heure de départ</label>
                            <input type="time" name="heure_depart" id="edit_heure_depart" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Heure de retour</label>
                            <input type="time" name="heure_retour" id="edit_heure_retour" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Frais (MAD)</label>
                            <input type="number" step="0.01" name="frais" id="edit_frais" 
                                class="form-control" placeholder="0.00">
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

            <div class="card-datatable table-responsive">
                <table class="datatables-basic table table-striped table-hover border-top" id="ordermissionsTable">
                    <thead>
                        <tr>
                            <th>Code Mission</th>
                            <th>Salariés</th>
                            <th>Emplacement</th>
                            <th>Mission</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ordermissionsuper as $om)
                            <tr>
                                <td>{{ $om->code ?? '-' }}</td>
<td>
    <strong>{{ $om->gerant_name }}</strong>
    <br>
    <small class="text-muted">
        Participants : {{ $om->salaries_names }}
    </small>
</td>                               <td>{{ $om->emplacement ?? '-' }}</td>
                                <td>{{ $om->mission ?? '-' }}</td>

                                 <td>
        @if($om->ordre == 1)
            <span class="badge bg-success">Accepté</span>
        @else
            <span class="badge bg-warning text-dark">En cours</span>
        @endif
    </td>
            <td>
    <div class="action-buttons d-flex align-items-center gap-1">
        <!-- Icône Fiche Officielle -->
        <button class="btn btn-sm btn-icon show-fiche" 
                data-id="{{ $om->id }}" 
                title="Voir la fiche officielle">
            <i class="bx bx-file text-info"></i>
        </button>

        <!-- Validation -->
        <button class="btn btn-sm btn-icon validate-ordre" 
                data-id="{{ $om->id }}" 
                data-statut="{{ $om->ordre ?? 0 }}"
                title="Validation">
            <i class="bx bx-check-circle {{ $om->ordre == 1 ? 'text-success' : 'text-secondary' }}"></i>
        </button>

        <button class="btn btn-sm btn-icon edit-ordre" data-id="{{ $om->id }}" title="Modifier">
            <i class="bx bx-edit text-primary"></i>
        </button>
        <button class="btn btn-sm btn-icon delete-ordre" data-id="{{ $om->id }}" title="Supprimer">
            <i class="bx bx-trash text-danger"></i>
        </button>
    </div>
</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
 <script>
    const storeRoute = "{{ route('ordermissionsuper.store') }}";
</script>

<script>
    const companySettings = @json($companySettings ?? []);
    console.log('Company Settings:', companySettings); // Pour déboguer
</script>
<script>
    const ficheRouteBase = "{{ url('/ordermissionsuper') }}";
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script src="{{ asset('assets/js/ordermission.js') }}"></script>
@endsection