<!-- resources/views/projet/edit-public.blade.php -->
<form id="edit-public-projet-form" method="POST" action="{{ route('projets.update', $projet->id) }}">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12 col-sm-6">
            <label for="edit_intitule" class="form-label">Intitulé <span class="text-danger">*</span></label>
            <input type="text" name="intitule" class="form-control" id="edit_intitule" value="{{ old('intitule', $projet->intitule) }}" required />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_date_offre" class="form-label">Date Offre <span class="text-danger">*</span></label>
            <input type="date" name="date_offre" class="form-control" id="edit_date_offre" value="{{ old('date_offre', $projet->date_offre ? $projet->date_offre->format('Y-m-d') : '') }}" required />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_date_marche" class="form-label">Date Marché <span class="text-danger">*</span></label>
            <input type="date" name="date_marche" class="form-control" id="edit_date_marche" value="{{ old('date_marche', $projet->date_marche ? $projet->date_marche->format('Y-m-d') : '') }}" required />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_ville" class="form-label">Ville <span class="text-danger">*</span></label>
            <input type="text" name="ville" class="form-control" id="edit_ville" value="{{ old('ville', $projet->ville) }}" required />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_maitre_ouvrage" class="form-label">Maître d'Ouvrage <span class="text-danger">*</span></label>
            <input type="text" name="maitre_ouvrage" class="form-control" id="edit_maitre_ouvrage" value="{{ old('maitre_ouvrage', $projet->maitre_ouvrage) }}" required />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_budget" class="form-label">Montant Marché <span class="text-danger">*</span></label>
            <input type="number" name="budget" class="form-control" id="edit_budget" step="0.01" min="0" value="{{ old('budget', $projet->budget) }}" required />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_rg" class="form-label">Retenue de Garantie (7%) <span class="text-danger">*</span></label>
            <input type="number" name="rg" class="form-control" id="edit_rg" step="0.01" min="0" value="{{ old('rg', $projet->rg ?? ($projet->budget * 0.07)) }}" readonly />
            <div class="invalid-feedback">Ce champ est obligatoire et doit être égal à 7% du budget.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_caution_definitif" class="form-label">Caution Définitive (3%) <span class="text-danger">*</span></label>
            <input type="number" name="caution_definitif" class="form-control" id="edit_caution_definitif" step="0.01" min="0" value="{{ old('caution_definitif', $projet->caution_definitif ?? ($projet->budget * 0.03)) }}" readonly />
            <div class="invalid-feedback">Ce champ est obligatoire et doit être égal à 3% du budget.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_total_decompte" class="form-label">Total Décompte <span class="text-danger">*</span></label>
            <input type="number" name="total_decompte" class="form-control" id="edit_total_decompte" step="0.01" min="0" value="{{ old('total_decompte', $projet->total_decompte ?? ($projet->budget - ($projet->rg ?? $projet->budget * 0.07))) }}" readonly />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_delai_execution" class="form-label">Délai d'Exécution (jours) <span class="text-danger">*</span></label>
            <input type="number" name="delai_execution" class="form-control" id="edit_delai_execution" min="1" value="{{ old('delai_execution', $projet->delai_execution) }}" required />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_date_debut" class="form-label">Ordre de Service <span class="text-danger">*</span></label>
            <input type="date" name="date_debut" class="form-control" id="edit_date_debut" value="{{ old('date_debut', $projet->date_debut ? $projet->date_debut->format('Y-m-d') : '') }}" required />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="edit_date_fin" class="form-label">Date Fin</label>
            <input type="date" name="date_fin" class="form-control" id="edit_date_fin" value="{{ old('date_fin', $projet->date_fin ? $projet->date_fin->format('Y-m-d') : '') }}" />
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
        <input type="hidden" name="type_projet" value="Public">
        <input type="hidden" name="cloture" value="{{ $projet->cloture }}">
        <input type="hidden" name="commande_type" value="{{ $projet->commande_type }}">
        <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-success">
                <i class="bx bx-check me-1"></i> Mettre à jour
            </button>
        </div>
    </div>
</form>