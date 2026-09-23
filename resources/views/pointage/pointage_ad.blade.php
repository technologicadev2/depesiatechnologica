@extends('master_page.app')

@section('title')
    Pointage administratif
@endsection

@section('styles')
    <style>
        .datatables-basic .employee-photo {
            width: 30px !important;
            height: 30px !important;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
        }
        .date-picker-container {
            display: inline-block;
            margin-right: 10px;
            vertical-align: middle;
        }
        #pointageDate {
            padding: 6px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .type-column select.form-select {
            padding: 4px 8px;
            font-size: 14px;
            border-radius: 4px;
            border-color: #ced4da;
            background-color: #f8f9fa;
        }
.flatpickr-day.ferie {
    background-color: #e74c3c !important;
    color: white !important;
    border-radius: 50% !important;
    font-weight: bold;
}

.flatpickr-day.ferie:hover {
    background-color: #c0392b !important;
    color: white !important;
}
    </style>
@endsection

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Pointage administratif</h4>

    <!-- SweetAlert2 Notifications -->
    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: "{{ session('success') }}",
                    position: 'center',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: () => {
                        const successSound = new Audio('{{ asset('assets/audio/success.mp3') }}');
                        successSound.play().catch(error => console.log('Erreur de lecture audio:', error));
                    }
                });
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: "{{ session('error') }}",
                    position: 'center',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: () => {
                        const errorSound = new Audio('{{ asset('assets/audio/error.mp3') }}');
                        errorSound.play().catch(error => console.log('Erreur de lecture audio:', error));
                    }
                });
            });
        </script>
    @endif

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-basic table table-striped table-hover border-top" id="salariesTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th> <!-- Select All Checkbox -->
                        <th>Matricule Entreprise</th>
                        <th>Photo</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Heures travail</th>
                        <th>temps de travail</th>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($salaries as $salarie)
                        <tr>
                            <td>
                                <input type="checkbox" 
                                       class="presence-checkbox" 
                                       data-salarie-id="{{ $salarie->id }}"
                                       value="{{ $salarie->id }}"
                                       {{ isset($presences[$salarie->id]) && $presences[$salarie->id] == 1 ? 'checked' : '' }}>
                            </td>
                            <td>{{ $salarie->n_matricule_entreprise ?? '-' }}</td>
                            <td>
                                <img src="{{ $salarie->photo_url }}" 
                                     alt="Photo de {{ ($salarie->nom ?? '-') . ' ' . ($salarie->prenom ?? '-') }}" 
                                     class="employee-photo">
                            </td>
                            <td>{{ $salarie->nom ?? '-' }}</td>
                            <td>{{ $salarie->prenom ?? '-' }}</td>
                            <td>
                            <input type="number" 
                            name="heures[{{ $salarie->id }}]" 
                            value="{{ isset($presence[$salarie->id]) ? $presence[$salarie->id] : '0' }}" 
                            id="heure"
                            class="form-control heures" style="width : 100px">
                         </td >
                            <td id="type-{{ $salarie->id }}">
                                <p id="par-{{ $salarie->id }}" style="display:inline">
                                    {{ isset($p[$salarie->id]) ? $p[$salarie->id] : "null" }}
                                </p>
                                <select id="update-{{ $salarie->id }}" style="display: none" class="form-select type">
                                    <option value="matin" {{ isset($p[$salarie->id]) && $p[$salarie->id] == 'matin' ? 'selected' : '' }}>Matin</option>
                                    <option value="nuit"  {{ isset($p[$salarie->id]) && $p[$salarie->id] == 'nuit' ? 'selected' : '' }}>Nuit</option>
                                </select>
                            </td>

                        </tr> 
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: rgba(0, 0, 0, 0.5); border: none; box-shadow: none;">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="text-white mt-2">Chargement...</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        const companySettings = @json($companySettings ?? []);
        console.log('Company Settings:', companySettings); // Pour déboguer
        
       document.addEventListener("DOMContentLoaded", function () {
        // Pour chaque cellule "type"
        document.querySelectorAll("td[id^='type-']").forEach(cell => {
            cell.addEventListener("click", () => {
                const id = cell.id.split("-")[1]; // récupère l'ID du salarié
                const p = document.getElementById(`par-${id}`);
                const select = document.getElementById(`update-${id}`);

                // Toggle l'affichage
                if (p && select) {
                    p.style.display = 'none';
                    select.style.display = 'inline';
                }
            });
        });
    });
    
</script>

<script>
    // Liste des jours fériés passée depuis le contrôleur
    const joursFeries = @json($joursFeries ?? []);
    console.log('Jours fériés chargés :', joursFeries);
</script>

    <script src="{{ asset('assets/js/pointage_ad.js') }}"></script>
@endsection