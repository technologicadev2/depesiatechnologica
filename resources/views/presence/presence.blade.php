@extends('master_page.app')

@section('title')
    Gestion de Présence
@endsection

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-calendar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/presence.css') }}" />
    <style>
        .select2-results__option i,
        .select2-selection__rendered i {
            font-size: 1.2rem;
            vertical-align: middle;
        }
        .fc-event-holiday {
            background-color: #90ee90 !important; /* Vert clair pour les jours fériés */
            border-color: #28a745 !important;
            color: #000 !important;
            font-weight: bold;
        }
        .holiday-event {
            padding: 5px;
            text-align: center;
        }
    </style>
@endsection

@section('content')    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Présence</h4>

        <!-- Sélecteur de mois et bouton d'impression -->
        <div class="mb-3 d-flex align-items-center gap-3">
            <select id="monthFilter" class="form-select select2" style="width: 200px;">
                <option value="">Sélectionner un mois</option>
            </select>
            <button id="printPointage" class="btn btn-primary">
                <i class="bx bx-printer me-1"></i> Imprimer le pointage
            </button>
        </div>

        <div class="card app-calendar-wrapper">
            <div class="row g-0">
                <!-- Calendar Sidebar -->
                <div class="col app-calendar-sidebar" id="app-calendar-sidebar">
                    <div class="p-4">
                        <div class="ms-n2">
                            <div class="inline-calendar"></div>
                        </div>
                        <hr class="container-m-nx my-4" />
                        <div class="mb-4">
                            <small class="text-small text-muted text-uppercase align-middle">Filtrer par Projet</small>
                        </div>
                        <div class="app-calendar-events-filter">
                            <select class="select2 select-project-filter form-select" multiple id="projectFilter"
                                autocomplete="off">
                                <option value="sans-projet" data-label="secondary">Sans Projet</option>
                                @foreach ($projets as $index => $project)
                                    <option value="projet-{{ $project->id }}"
                                        data-label="{{ ['primary', 'success', 'danger', 'warning', 'info', 'secondary'][$loop->index % 6] }}">
                                        {{ $project->intitule }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Calendar & Modal -->
                <div class="col app-calendar-content">
                    <div class="card shadow-none border-0">
                        <div class="card-body pb-0">
                            <div id="calendar"></div>
                        </div>
                    </div>
                    <div class="app-overlay"></div>
                    <div class="offcanvas offcanvas-end event-sidebar" tabindex="-1" id="addEventSidebar"
                        aria-labelledby="addEventSidebarLabel">
                        <div class="offcanvas-header border-bottom">
                            <h5 class="offcanvas-title mb-2" id="addEventSidebarLabel">Détails des Salariés</h5>
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <div class="employee-list-container" id="employeeListContainer">
                                <h6 id="employeeListTitle"></h6>
                                <ul class="employee-list" id="employeeList"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Iframe pour l'impression -->
        <iframe id="printFrame" style="display: none;"></iframe>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/fr.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/locale/fr.js"></script>
    <script>
    window.togglePresenceRoute = '{{ route('presence.toggle') }}';
    window.csrfToken = '{{ csrf_token() }}';
</script>

    <script>
        window.calendarsColor = {
            'sans-projet': 'secondary',
            @foreach ($projets as $index => $project)
                'projet-{{ $project->id }}': '{{ ['primary', 'success', 'danger', 'warning', 'info', 'secondary'][$loop->index % 6] }}',
            @endforeach
        };
        window.currentEvents = @json($events);
        window.presenceEmployeesRoute = '{{ route('presence.employees') }}';
        window.pointageByMonthRoute = '{{ route('presence.pointage-by-month') }}';
        window.companySettings = @json($companySettings ?? null);
        console.log('=== DEBUG INITIAL ===');
        console.log('Couleurs des calendriers:', window.calendarsColor);
        console.log('Nombre d\'événements:', window.currentEvents.length);
        console.log('Premier événement:', window.currentEvents[0]);
        console.log('Route employés:', window.presenceEmployeesRoute);
        console.log('Route pointage:', window.pointageByMonthRoute);
        console.log('Company Settings:', window.companySettings);
    </script>
    <script src="{{ asset('assets/js/presence.js') }}"></script>
    <script>
    window.togglePresenceRoute = '{{ route('presence.toggle') }}';
    window.csrfToken = '{{ csrf_token() }}';
</script>
@endsection
