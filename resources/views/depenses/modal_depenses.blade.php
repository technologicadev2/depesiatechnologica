<!-- Modal for Editing Depense -->
<div class="modal fade" id="editDepenseModal" tabindex="-1" aria-labelledby="editDepenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepenseModalLabel">Modifier une Dépense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDepenseForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nature_id" class="form-label">Nature de la Dépense</label>
                        <select class="form-select @error('nature_id') is-invalid @enderror" id="edit_nature_id" name="nature_id" required>
                            <option value="">Sélectionner</option>
                        </select>
                        @error('nature_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3" id="edit_salarie_field" style="display: none;">
                        <label for="edit_salarie_id" class="form-label">Salarié</label>
                        <select class="form-select @error('salarie_id') is-invalid @enderror" id="edit_salarie_id" name="salarie_id">
                            <option value="">Sélectionner un salarié</option>
                        </select>
                        @error('salarie_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3" id="edit_vehicle_field" style="display: none;">
                        <label for="edit_vehicle_id" class="form-label">Véhicule (Matricule)</label>
                        <select class="form-select @error('vehicle_id') is-invalid @enderror" id="edit_vehicle_id" name="vehicle_id">
                            <option value="">Sélectionner un véhicule</option>
                        </select>
                        @error('vehicle_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <label for="edit_vehicle_type" class="form-label mt-3">Type de Dépense</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="edit_vehicle_type" name="type">
                            <option value="">Sélectionner</option>
                            <option value="gasoil">Gasoil</option>
                            <option value="vidange">Vidange</option>
                            <option value="visite_technique">Visite Technique</option>
                            <option value="vignette">Vignette</option>
                            <option value="reparation">Réparation</option>
                            <option value="autre">Autre</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <label for="edit_vehicle_kilometrage" class="form-label mt-3">Kilométrage</label>
                        <input type="number" class="form-control @error('kilometrage') is-invalid @enderror" id="edit_vehicle_kilometrage" name="kilometrage">
                        @error('kilometrage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_date" class="form-label">Date</label>
                        <input type="date" class="form-control @error('date') is-invalid @enderror" id="edit_date" name="date" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_montant" class="form-label">Montant</label>
                        <input type="number" step="0.01" class="form-control @error('montant') is-invalid @enderror" id="edit_montant" name="montant" required>
                        @error('montant')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_reglement_id" class="form-label">Règlement</label>
                        <select class="form-select @error('reglement_id') is-invalid @enderror" id="edit_reglement_id" name="reglement_id" required>
                            <option value="">Sélectionner</option>
                        </select>
                        @error('reglement_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="edit_description" name="description" required></textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_epreuve" class="form-label">Épreuve (Image, PDF, Excel, Word)</label>
                        <input type="file" class="form-control @error('epreuve') is-invalid @enderror" id="edit_epreuve" name="epreuve" accept=".jpg,.jpeg,.png,.pdf,.xlsx,.xls,.doc,.docx">
                        <small class="form-text text-muted">Laissez vide pour conserver le fichier existant.</small>
                        @error('epreuve')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
<!-- Modal for Editing Vehicle Depense -->
<div class="modal fade" id="editVehicleDepenseModal" tabindex="-1" aria-labelledby="editVehicleDepenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editVehicleDepenseModalLabel">Modifier Dépense Véhicule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editVehicleDepenseForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nature_id" class="form-label">Nature</label>
                        <select id="edit_nature_id" name="nature_id" class="form-select" required>
                            <option value="">Sélectionner</option>
                        </select>
                    </div>
                    <div class="mb-3" id="edit_vehicle_field" style="display: block;">
                        <label for="edit_vehicle_id" class="form-label">Véhicule</label>
                        <select id="edit_vehicle_id" name="vehicle_id" class="form-select" required>
                            <option value="">Sélectionner un véhicule</option>
                        </select>
                        <label for="edit_vehicle_type" class="form-label mt-3">Type</label>
                        <select id="edit_vehicle_type" name="type" class="form-select" required>
                            <option value="">Sélectionner</option>
                            <option value="gasoil">Gasoil</option>
                            <option value="vidange">Vidange</option>
                            <option value="visite_technique">Visite Technique</option>
                            <option value="vignette">Vignette</option>
                            <option value="reparation">Réparation</option>
                            <option value="autre">Autre</option>
                        </select>
                        <label for="edit_vehicle_kilometrage" class="form-label mt-3">Kilométrage</label>
                        <input id="edit_vehicle_kilometrage" name="kilometrage" type="number" class="form-control">
                    </div>
                    <div class="mb-3" id="edit_salarie_field" style="display: none;">
                        <label for="edit_salarie_id" class="form-label">Salarié</label>
                        <select id="edit_salarie_id" name="salarie_id" class="form-select">
                            <option value="">Sélectionner un salarié</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_date" class="form-label">Date</label>
                        <input id="edit_date" name="date" type="date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_montant" class="form-label">Montant</label>
                        <input id="edit_montant" name="montant" type="number" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_reglement_id" class="form-label">Règlement</label>
                        <select id="edit_reglement_id" name="reglement_id" class="form-select" required>
                            <option value="">Sélectionner</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea id="edit_description" name="description" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_epreuve" class="form-label">Épreuve</label>
                        <input id="edit_epreuve" name="epreuve" type="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.xlsx,.xls,.doc,.docx">
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
 <!-- Modal for Adding Depense -->
<div class="modal fade" id="addDepenseModal" tabindex="-1" aria-labelledby="addDepenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepenseModalLabel">Ajouter une Dépense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addDepenseForm" action="{{ route('depenses.depenses') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nature_id" class="form-label">Nature de la Dépense</label>
                        <select class="form-select @error('nature_id') is-invalid @enderror" id="nature_id" name="nature_id" required>
                            <option value="">Sélectionner</option>
                            @foreach ($natureDepenses as $natureDepense)
                                @if ($natureDepense->designation !== 'Avancements de salaires' && $natureDepense->designation !== 'Dépenses de véhicule')
                                    <option value="{{ $natureDepense->id }}" {{ old('nature_id') == $natureDepense->id ? 'selected' : '' }}>
                                        {{ $natureDepense->designation }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @error('nature_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3" id="salarie_field" style="display: none;">
                        <label for="salarie_id" class="form-label">Salarié</label>
                        <select class="form-select @error('salarie_id') is-invalid @enderror" id="salarie_id" name="salarie_id">
                            <option value="">Sélectionner un salarié</option>
                            @foreach ($salaries as $salarie)
                                <option value="{{ $salarie->id }}" {{ old('salarie_id') == $salarie->id ? 'selected' : '' }}>
                                    {{ $salarie->n_matricule_entreprise }} - {{ $salarie->nom }} {{ $salarie->prenom }}
                                </option>
                            @endforeach
                        </select>
                        @error('salarie_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3" id="vehicle_field" style="display: none;">
                        <label for="vehicle_id" class="form-label">Véhicule (Matricule)</label>
                        <select class="form-select @error('vehicle_id') is-invalid @enderror" id="vehicle_id" name="vehicle_id">
                            <option value="">Sélectionner un véhicule</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->matricule }}
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <label for="vehicle_type" class="form-label mt-3">Type de Dépense</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="vehicle_type" name="type">
                            <option value="">Sélectionner</option>
                            <option value="gasoil">Gasoil</option>
                            <option value="vidange">Vidange</option>
                            <option value="visite_technique">Visite Technique</option>
                            <option value="vignette">Vignette</option>
                            <option value="reparation">Réparation</option>
                            <option value="autre">Autre</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <label for="vehicle_kilometrage" class="form-label mt-3">Kilométrage</label>
                        <input type="number" class="form-control @error('kilometrage') is-invalid @enderror" id="vehicle_kilometrage" name="kilometrage" value="{{ old('kilometrage') }}">
                        @error('kilometrage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date') }}" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant</label>
                        <input type="number" step="0.01" class="form-control @error('montant') is-invalid @enderror" id="montant" name="montant" value="{{ old('montant') }}" required>
                        @error('montant')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="reglement_id" class="form-label">Règlement</label>
                        <select class="form-select @error('reglement_id') is-invalid @enderror" id="reglement_id" name="reglement_id" required>
                            <option value="">Sélectionner</option>
                            @foreach ($typeReglements as $typeReglement)
                                <option value="{{ $typeReglement->id }}" {{ old('reglement_id') == $typeReglement->id ? 'selected' : '' }}>
                                    {{ $typeReglement->designation }}
                                </option>
                            @endforeach
                        </select>
                        @error('reglement_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="epreuve" class="form-label">Épreuve (Image, PDF, Excel, Word)</label>
                        <input type="file" class="form-control @error('epreuve') is-invalid @enderror" id="epreuve" name="epreuve" accept=".jpg,.jpeg,.png,.pdf,.xlsx,.xls,.doc,.docx">
                        @error('epreuve')
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
<!-- Modal for Adding Avancement Depense -->
<div class="modal fade" id="addAvancementDepenseModal" tabindex="-1" aria-labelledby="addAvancementDepenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAvancementDepenseModalLabel">Ajouter un Avancement de Salaire</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAvancementDepenseForm" action="{{ route('depenses.depenses') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="avancement_nature_id" name="nature_id" value="{{ $avancementNatureId }}">
                   <div class="mb-3">
    <label for="avancement_salarie_id" class="form-label">Salarié</label>
    <select class="form-select select2-salarie @error('salarie_id') is-invalid @enderror" 
            id="avancement_salarie_id" 
            name="salarie_id" 
            required>
        <option value="">Sélectionner un salarié</option>
        @foreach ($salaries as $salarie)
            <option value="{{ $salarie->id }}" {{ old('salarie_id') == $salarie->id ? 'selected' : '' }}>
                {{ $salarie->n_matricule_entreprise }} - {{ $salarie->nom }} {{ $salarie->prenom }}
            </option>
        @endforeach
    </select>
    @error('salarie_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
                    <div class="mb-3">
                        <label for="avancement_montant" class="form-label">Montant</label>
                        <input type="number" step="0.01" class="form-control @error('montant') is-invalid @enderror" id="avancement_montant" name="montant" value="{{ old('montant') }}" required>
                        @error('montant')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="avancement_date" class="form-label">Date</label>
                        <input type="date" class="form-control @error('date') is-invalid @enderror" id="avancement_date" name="date" value="{{ old('date') }}" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="avancement_reglement_id" class="form-label">Règlement</label>
                        <select class="form-select @error('reglement_id') is-invalid @enderror" id="avancement_reglement_id" name="reglement_id" required>
                            <option value="">Sélectionner</option>
                            @foreach ($typeReglements as $typeReglement)
                                <option value="{{ $typeReglement->id }}" {{ old('reglement_id') == $typeReglement->id ? 'selected' : '' }}>{{ $typeReglement->designation }}</option>
                            @endforeach
                        </select>
                        @error('reglement_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="avancement_description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="avancement_description" name="description" required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="avancement_epreuve" class="form-label">Épreuve (Image, PDF, Excel, Word)</label>
                        <input type="file" class="form-control @error('epreuve') is-invalid @enderror" id="avancement_epreuve" name="epreuve" accept=".jpg,.jpeg,.png,.pdf,.xlsx,.xls,.doc,.docx">
                        @error('epreuve')
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
