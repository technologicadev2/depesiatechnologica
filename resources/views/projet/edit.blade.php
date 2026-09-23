<form id="edit-private-projet-form" method="POST" action="{{ route('projets.update', $projet->id) }}">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12 col-sm-6">
            <label for="edit_intitule" class="form-label">Intitulé <span class="text-danger">*</span></label>
            <input type="text" name="intitule" class="form-control" id="edit_intitule" value="{{ $projet->intitule }}"
                required />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>

        <div class="col-12 col-sm-6">
            <label for="edit_date_debut" class="form-label">Date Début <span class="text-danger">*</span></label>
            <input type="date" name="date_debut" class="form-control" id="edit_date_debut"
                value="{{ old('date_debut', $projet->date_debut ? $projet->date_debut->format('Y-m-d') : '') }}"
                required />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_date_fin" class="form-label">Date Fin </label>
            <input type="date" name="date_fin" class="form-control" id="edit_date_fin"
                value="{{ old('date_fin', $projet->date_fin ? $projet->date_fin->format('Y-m-d') : '') }}" />

        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_budget" class="form-label">Budget</label>
            <input type="number" name="budget" class="form-control" id="edit_budget" step="0.01" min="0"
                value="{{ $projet->budget }}" />
        </div>
        <div class="col-12">
            <label for="edit_description" class="form-label">Description</label>
            <textarea name="description" class="form-control" id="edit_description" rows="4">{{ $projet->description }}</textarea>
        </div>
        <div class="col-12">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="marche_cadre" id="edit_marche_cadre" value="1"
            {{ $projet->marche_cadre ? 'checked' : '' }}>
        <label class="form-check-label" for="edit_marche_cadre">
            Marché Cadre
        </label>
    </div>
</div>
        <input type="hidden" name="type_projet" value="Privé">
        <input type="hidden" name="cloture" value="{{ $projet->cloture }}">
        <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-success">
                <i class="bx bx-check me-1"></i> Mettre à jour
            </button>
        </div>
    </div>
</form>
