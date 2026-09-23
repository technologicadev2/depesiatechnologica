@extends('master_page.app')

@section('title')
    Bulletin de paie
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-4">Bulletin de paie</h4>

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <!-- Debug: Check bulletins array -->
    @if (empty($bulletins))
        <p>Debug: Aucun bulletin de paie trouvé.</p>
    @else
        <p>Debug: {{ count($bulletins) }} bulletin(s) trouvé(s).</p>
    @endif
<style>
/* Styles pour l'en-tête personnalisé du DataTable */
.dt-custom-header {
    display: flex !important;
    justify-content: flex-start !important;
    align-items: center !important;
    margin-bottom: 15px !important;
    margin-top: 20px !important;
    width: 100% !important;
    flex-wrap: nowrap !important;
    gap: 0 !important;
}

.dt-year-filter-container {
    flex-shrink: 0;
}

.dt-search-container {
    flex-shrink: 0;
    margin-left: 0 !important;
}

.dt-search-container .dataTables_filter {
    margin: 0 !important;
}

.dt-search-container .dataTables_filter label {
    margin-bottom: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.dt-year-filter-container label,
.dt-search-container label {
    font-weight: 600 !important;
    color: #566a7f !important;
    white-space: nowrap !important;
    margin-left: 10px !important;
}

/* Marges top et left pour les inputs */
.dt-year-filter-container select,
.dt-search-container input {
    margin-top: 5px !important;
    margin-left: 10px !important;
}

/* Style pour les contrôles sur mobile */
@media (max-width: 768px) {
    .dt-custom-header {
        flex-wrap: wrap !important;
        gap: 10px !important;
    }
}

/* Styles pour masquer l'ancien div de filtre d'année */
.card-datatable .dt-year-filter {
    display: none !important;
}

/* Styles existants conservés */
.dataTables_filter {
    text-align: right;
    margin: 0;
}

.dataTables_filter input.form-control {
    max-width: 250px;
}

.dt-year-filter {
    text-align: left;
    margin: 0;
}

.dt-year-filter select.form-control {
    max-width: 150px;
}

.dt-year-filter select {
    margin-top: 0 !important;
}
</style>


    <div class="card">
        <div class="card-datatable table-responsive">
            <!-- Ensure only one dt-year-filter div -->
            <div class="dt-year-filter" data-years="{{ json_encode($availableYears ?? []) }}" data-selected-year="{{ $selectedYear }}" data-base-url="{{ route('bulletinsa.index') }}"></div>
            <table class="datatables-basic table table-striped table-hover" id="bulletinsTable">
                <thead>
                    <tr>
                        <th>Mois</th>
                        <th>Bulletin Paie</th>
                        <th>Bulletin Paie Caché</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bulletins as $bulletin)
                        <tr>
                            <td>{{ $bulletin['mois'] }}</td>
                            <td>
                                @if ($bulletin['bulletin'])
                                    @if ($bulletin['is_paid'])
                                        <a href="{{ route('bulletin.downloadPaySlip', [
                                            'id_salarie' => Auth::user()->id_salarie,
                                            'year' => $selectedYear,
                                            'month' => strtolower($bulletin['mois'])
                                        ]) }}" class="btn btn-link p-0" title="Télécharger le bulletin de paie">
                                            <i class="bx bx-download" style="font-size: 1.5rem;"></i>
                                        </a>
                                    @else
                                        <span class="text-muted" title="Téléchargement bloqué : Paiement non effectué">
                                            <i class="bx bx-download" style="font-size: 1.5rem; opacity: 0.5; cursor: not-allowed;"></i>
                                        </span>
                                    @endif
                                @else
                                    Non disponible
                                @endif
                            </td>
                            <td>
                                @if ($bulletin['bulletin_cache'])
                                    @if ($bulletin['is_paid'])
                                        <a href="{{ route('bulletin.downloadHiddenPaySlip', [
                                            'id_salarie' => Auth::user()->id_salarie,
                                            'year' => $selectedYear,
                                            'month' => strtolower($bulletin['mois'])
                                        ]) }}" class="btn btn-link p-0" title="Télécharger le bulletin de paie caché">
                                            <i class="bx bx-download" style="font-size: 1.5rem;"></i>
                                        </a>
                                    @else
                                        <span class="text-muted" title="Téléchargement bloqué : Paiement non effectué">
                                            <i class="bx bx-download" style="font-size: 1.5rem; opacity: 0.5; cursor: not-allowed;"></i>
                                        </span>
                                    @endif
                                @else
                                    Non disponible
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Inclure le fichier JavaScript -->
    <script src="{{ asset('assets/js/bultinsa.js') }}"></script>
@endsection