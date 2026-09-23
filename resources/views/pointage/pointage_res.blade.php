@extends('master_page.app')

@section('title')
    Pointage
@endsection

@section('styles')
    <style>
        .datatables-basic .employee-photo,
        .projet-salaries-table .employee-photo {
            width: 30px !important;
            height: 30px !important;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
        }
    </style>
@endsection
@section('content')
    <h4 class="fw-bold py-3 mb-4">Pointage des Projets (Ouvriers)</h4>

    <div class="bg-light m-4">
        <div class="accordion my-3" id="accordionExample">
            @forelse($projets as $index => $projet)
                @php
                    $nudeColors = [
                        'rgb(245, 230, 232)',
                        'rgb(248, 236, 228)',
                        'rgb(237, 228, 224)',
                        'rgb(232, 236, 239)',
                        'rgb(244, 237, 234)'
                    ];
                    $nudeColor = $nudeColors[$index % 5];
                    // Count the number of ouvriers for the project
                    $ouvrierCount = $projet->salaries->count();
                @endphp
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse{{ $projet->id }}" 
                                aria-expanded="false" 
                                aria-controls="collapse{{ $projet->id }}"
                                style="background-color: {{ $nudeColor }}; color: #333;">
                            {{ $projet->intitule }} ({{ $ouvrierCount }} ouvrier{{ $ouvrierCount > 1 ? 's' : '' }})
                        </button>
                    </h2>
                    <div id="collapse{{ $projet->id }}" 
                         class="accordion-collapse collapse" 
                         data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <h5 class="mt-5">Ouvriers affectés</h5>
                            <div class="card">
                                <div class="card-datatable table-responsive">
                                    <table class="datatables-basic table table-striped table-hover border-top projet-salaries-table"
                                           id="salariesTable{{ $projet->id }}"
                                           data-salaries='{{ json_encode($projet->salaries_data) }}'>
                                        <thead>
                                            <tr>
                                                <th>Case</th>
                                                <th>Photo</th>
                                                <th>Nom Prénom</th>
                                                <th>Matricule Entreprise</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($projet->salaries as $salarie)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" 
                                                               class="presence-checkbox" 
                                                               data-salarie-id="{{ $salarie->id }}"
                                                               value="{{ $salarie->id }}"
                                                               {{ isset($presences[$salarie->id]) && $presences[$salarie->id] == 1 ? 'checked' : '' }}>
                                                    </td>
                                                    <td>
                                                        <img src="{{ $salarie->photo_url }}" 
                                                             alt="Photo de {{ ($salarie->nom ?? '-') . ' ' . ($salarie->prenom ?? '-') }}" 
                                                             class="employee-photo">
                                                    </td>
                                                    <td>{{ ($salarie->nom ?? '-') . ' ' . ($salarie->prenom ?? '-') }}</td>
                                                    <td>{{ $salarie->n_matricule_entreprise ?? '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">Aucun ouvrier affecté à ce projet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <button class="btn btn-primary save-presence-btn" 
                                            data-table-id="salariesTable{{ $projet->id }}"
                                            data-projet-id="{{ $projet->id }}">
                                        Enregistrer la présence
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info" role="alert">
                    Aucun projet non clôturé n'est affecté à vous en tant que responsable.
                </div>
            @endforelse
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
</script>
    <script src="{{ asset('assets/js/pointage.js') }}"></script>
@endsection