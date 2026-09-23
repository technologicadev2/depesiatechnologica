<div class="modal fade" id="modalConge" tabindex="-1" aria-labelledby="modalCongeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <img src="{{ asset('assets/img/favicon/anassi2.jpg') }}" alt="Logo">
                <h5 class="modal-title" id="modalCongeLabel">Demande de Congé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="congeForm" action="{{ route('conge.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="salarie_id" class="form-label">Sélectionner un salarié</label>
                        <select class="form-control" id="salarie_id" name="salarie_id" required>
                            <option value="">-- Choisir un salarié --</option>
                            @foreach ($salaries as $salarie)
                                <option value="{{ $salarie->id }}" data-nom="{{ $salarie->nom }}"
                                    data-prenom="{{ $salarie->prenom }}" data-email="{{ $salarie->email ?? '' }}">
                                    {{ $salarie->nom }} {{ $salarie->prenom }} ({{ $salarie->n_matricule_entreprise }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="h5 text-dark">Pour quelle date faites-vous cette demande de congé ?</label>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="date_debut" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nombre_jours" class="form-label">Nombre de jours</label>
                                <input type="number" class="form-control" id="nombre_jours" name="nombre_jours"
                                    min="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="h5 text-dark">Raison</label>
                        <textarea class="form-control" id="raison" name="raison" rows="4" required
                            placeholder="Veuillez expliquer la raison de votre demande de congé"></textarea>
                        <div class="mt-2">
                            <label class="h5 text-dark">Signature</label>
                            <canvas id="signaturePad" class="signature-pad"></canvas>
                            <input type="hidden" name="signature" id="signatureInput">
                            <div class="signature-buttons">
                                <button type="button" id="clearSignature"
                                    class="btn btn-secondary btn-sm">Effacer</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Soumettre</button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- modal de chargement  --}}
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
    <script>
    const companySettings = @json($companySettings ?? []);
    console.log('Company Settings:', companySettings); // Pour déboguer
</script>
</div>
