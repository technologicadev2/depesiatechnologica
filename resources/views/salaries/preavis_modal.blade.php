<div class="modal fade" id="preavisModal" tabindex="-1" aria-labelledby="preavisModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="preavisModalLabel">Ajouter un Préavis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="preavis-form">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="date_debut_preavis" class="form-label">Date de Début du Préavis</label>
                            <input type="date" name="date_debut_preavis" class="form-control" id="date_debut_preavis" />
                            <div class="invalid-feedback">Ce champ est requis si vous ajoutez un préavis.</div>
                        </div>
                        <div class="col-12">
                            <label for="date_fin_preavis" class="form-label">Date de Fin du Préavis</label>
                            <input type="date" name="date_fin_preavis" class="form-control" id="date_fin_preavis" />
                            <div class="invalid-feedback">Ce champ est requis si vous ajoutez un préavis et doit être postérieur ou égal à la date de début.</div>
                        </div>
                        <div class="col-12">
                            <label for="document_preavis" class="form-label">Document du Préavis (PDF)</label>
                            <input type="file" name="document_preavis" class="form-control" id="document_preavis" accept=".pdf" />
                            <div class="invalid-feedback">Veuillez sélectionner un fichier PDF valide.</div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary" id="save-preavis">
                                <i class="bx bx-check me-1"></i> Enregistrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>