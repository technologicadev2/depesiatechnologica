<div class="modal fade" id="demissionModal" tabindex="-1" aria-labelledby="demissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="demissionModalLabel">Enregistrer une Démission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="demission-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="salarie_id" id="salarie_id">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="date_demission" class="form-label">Date de Démission <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="date_demission" class="form-control" id="date_demission"
                                required />
                            <div class="invalid-feedback">Ce champ est obligatoire.</div>
                        </div>
                        <div class="col-12">
                            <label for="motif" class="form-label">Motif de la Démission</label>
                            <textarea name="motif" class="form-control" id="motif" rows="4"></textarea>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="document" class="form-label">Lettre de Démission (PDF)</label>
                            <input type="file" name="document" class="form-control" id="document" accept=".pdf" />
                            <div class="invalid-feedback">Veuillez sélectionner un fichier PDF valide.</div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label">Préavis</label>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#preavisModal">
                                Ajouter un préavis
                            </button>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2"
                                data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-check me-1"></i> Enregistrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
