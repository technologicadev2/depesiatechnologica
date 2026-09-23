<div class="modal fade" id="uploadPrivateDocumentsModal" tabindex="-1" aria-labelledby="uploadPrivateDocumentsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadPrivateDocumentsModalLabel">Téléverser des Documents Privés</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="upload-private-documents-form" action="/projets/upload-private-documents" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="projet_id" id="private_document_projet_id">
                    <div class="mb-3">
                        <label for="private_documents" class="form-label">Sélectionner les documents (PDF uniquement)</label>
                        <input type="file" class="form-control" id="private_documents" name="private_documents[]" accept=".pdf" multiple>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div id="existing_private_documents" class="mb-3"></div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-upload me-1"></i> Téléverser
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>