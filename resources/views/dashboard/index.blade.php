@php
    use Carbon\Carbon;
@endphp
@extends('master_page.app')
@section('title')
    Dashboard
@endsection

@section('content')

    <link href="{{ asset('assets/css/dashboard.css') }}" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   
    <div class="card mb-4 py-2 px-2 py-lg-4 px-lg-3 border-none">
        <div class="container-xxl mx-auto flex-grow-1 container-p-y">
        <div class="scroll-icon-container">
    <span class="icon-circle scroll-icon" id="scrollIcon" aria-label="Scroll to top or bottom" data-scroll-action="toggle">
        <i class="bx bx-chevron-up bx-sm"></i>
    </span>
</div>
            <!-- Navigation Tabs -->
            <ul class="nav nav-pills mb-4" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#main-dashboard" aria-controls="main-dashboard" aria-selected="true">
                        Dashboard
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#vehicles-dashboard" aria-controls="vehicles-dashboard" aria-selected="false">
                        Véhicules Dashboard
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Main Dashboard Tab -->
                <div class="tab-pane fade show active" id="main-dashboard" role="tabpanel">
                    <div class="row g-4">
                        <!-- Existing Cards (Utilisateur, Dépenses, Salariés, Projets, Documents Expirant, Notifications) -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2">
                            <a href="{{ route('users.index') }}" class="clickable-card">
                                <div class="card card-user">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="content-left">
                                                <span>Utilisateur</span>
                                                <div class="d-flex align-items-end mt-2">
                                                    <h4 class="mb-0 me-2">{{ number_format($totalUsers, 0, ',', ',') }}</h4>
                                                </div>
                                                <small>Total Utilisateurs</small>
                                            </div>
                                            <span class="icon-circle bg-label-primary rounded-circle p-2">
                                                <i class="bx bx-user bx-md"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
  <!-- Carte Dépenses -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2">
                    <a href="{{ route('depenses.index') }}" class="clickable-card">
                        <div class="card card-depenses">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="content-left">
                                        <span>Total Dépenses</span>
                                        <div class="d-flex align-items-end mt-2">
                                            <h4 class="mb-0 me-2">{{ number_format($totalDepences, 0, ',', ',') }} DH</h4>
                                            <small class="{{ $depencesChange >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $depencesChange >= 0 ? '+' : '' }}{{ number_format($depencesChange, 0, ',', ',') }} DH
                                            </small>
                                        </div>
                                        <small>Analyse de la dernière période</small>
                                    </div>
                                    <span class="icon-circle bg-label-danger rounded-circle p-2">
                                        <i class="bx bx-wallet bx-md"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                        <!-- Salariés Card -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2">
                            <a href="{{ route('salaries.index') }}" class="clickable-card">
                                <div class="card card-salaries">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="content-left">
                                                <span>Salariés</span>
                                                <div class="d-flex align-items-end mt-2">
                                                    <h4 class="mb-0 me-2">{{ number_format($totalSalaries, 0, ',', ',') }}</h4>
                                                </div>
                                                <small>Total Salariés Actifs</small>
                                            </div>
                                            <span class="icon-circle bg-label-success rounded-circle p-2">
                                                <i class="bx bx-group bx-md"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!-- Projets Card -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2">
                            <a href="{{ route('projets.index') }}" class="clickable-card">
                                <div class="card card-projects">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="content-left">
                                                <span>Projets</span>
                                                <div class="mt-2">
                                                    <?php $totalProjects = $publicProjectsCount + $bcProjectsCount + $privateProjectsCount + $closedProjectsCount; ?>
                                                    <p class="mb-1"><small>Total: <span class="text-info">{{ $totalProjects }}</span></small></p>
                                                    <p class="mb-1"><small>Public: <span class="text-primary">{{ $publicProjectsCount }}</span></small>
                                                        <small>Bon De Commande: <span class="text-secondary">{{ $bcProjectsCount }}</span></small>
                                                    </p>
                                                    <p class="mb-1"><small>Privé: <span class="text-warning">{{ $privateProjectsCount }}</span></small></p>
                                                    <small>Clôturés: <span class="text-success">{{ $closedProjectsCount }}</span></small>
                                                </div>
                                            </div>
                                            <span class="icon-circle bg-label-secondary rounded-circle p-2">
                                                <i class="bx bx-briefcase bx-md"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                     <!-- Documents Expiring Card -->
<div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2">
    <div class="clickable-card position-relative">
        <a href="{{ route('parametres.index') }}" class="card card-expiring-documents text-decoration-none">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Documents & Véhicules Expirant</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2 {{ count(array_filter($expiringDocuments, fn($doc) => $doc['is_expired'])) > 0 ? 'text-light-red' : (count(array_filter($expiringDocuments, fn($doc) => $doc['is_urgent'])) > 0 ? 'text-light-yellow' : 'text-light-green') }}">
                                {{ count($expiringDocuments) }}
                            </h4>
                        </div>
                        <small>
                            @php
                                $expiredCount = count(array_filter($expiringDocuments, fn($doc) => $doc['is_expired']));
                                $urgentCount = count(array_filter($expiringDocuments, fn($doc) => $doc['is_urgent']));
                                $companyExpired = count(array_filter($expiringDocuments, fn($doc) => $doc['is_expired'] && $doc['category'] === 'company_document'));
                                $companyUrgent = count(array_filter($expiringDocuments, fn($doc) => $doc['is_urgent'] && $doc['category'] === 'company_document'));
                                $vehicleExpired = count(array_filter($expiringDocuments, fn($doc) => $doc['is_expired'] && in_array($doc['category'], ['vehicle_insurance', 'vehicle_registration', 'vehicle_inspection', 'vehicle_purchase'])));
                                $vehicleUrgent = count(array_filter($expiringDocuments, fn($doc) => $doc['is_urgent'] && in_array($doc['category'], ['vehicle_insurance', 'vehicle_registration', 'vehicle_inspection', 'vehicle_purchase'])));
                            @endphp
                            @if($expiredCount > 0)
                                {{ $companyExpired }} doc(s) expiré(s), {{ $vehicleExpired }} doc(s) véhicule(s) expiré(s)
                                @if($urgentCount > 0)
                                    , {{ $companyUrgent }} doc(s) urgent(s), {{ $vehicleUrgent }} doc(s) véhicule(s) urgent(s)
                                @endif
                            @elseif($urgentCount > 0)
                                {{ $companyUrgent }} doc(s) urgent(s), {{ $vehicleUrgent }} doc(s) véhicule(s) urgent(s)
                            @else
                                Expirant dans 24h
                            @endif
                        </small>
                    </div>
                    <span class="icon-circle {{ count(array_filter($expiringDocuments, fn($doc) => $doc['is_expired'])) > 0 ? 'bg-light-red' : (count(array_filter($expiringDocuments, fn($doc) => $doc['is_urgent'])) > 0 ? 'bg-light-yellow' : 'bg-light-green') }} rounded-circle p-2">
                        <i class="bx {{ count(array_filter($expiringDocuments, fn($doc) => $doc['is_expired'])) > 0 ? 'bx-calendar-x' : 'bx-calendar-exclamation' }} bx-md"></i>
                    </span>
                </div>
            </div>
        </a>
        <div class="employee-overlay" id="expiringDocumentsOverlay">
            <div class="employee-overlay-header">
                <h5>Documents & Véhicules Expirant</h5>
                <span class="badge rounded-pill {{ count(array_filter($expiringDocuments, fn($doc) => $doc['is_expired'])) > 0 ? 'bg-light-red' : (count(array_filter($expiringDocuments, fn($doc) => $doc['is_urgent'])) > 0 ? 'bg-light-yellow' : 'bg-light-green') }}">{{ count($expiringDocuments) }}</span>
            </div>
            <div id="expiringDocumentsOverlayContent">
                @if (count($expiringDocuments) > 0)
                    <ul class="list-group">
                        @foreach ($expiringDocuments as $doc)
                            <li class="list-group-item d-flex justify-content-between align-items-center 
                                {{ $doc['is_expired'] ? 'bg-light-red-opacity-10 border-light-red text-light-red' : ($doc['is_urgent'] ? 'bg-light-yellow-opacity-10 border-light-yellow text-light-yellow' : '') }}">
                                <div class="d-flex align-items-center">
                                    @if ($doc['is_expired'])
                                        <span class="badge bg-light-red me-2">EXPIRÉ</span>
                                    @elseif ($doc['is_urgent'])
                                        <span class="badge bg-light-yellow me-2">URGENT</span>
                                    @endif
                                    <div>
                                        <strong>{{ $doc['label'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ in_array($doc['category'], ['vehicle_insurance', 'vehicle_registration', 'vehicle_inspection', 'vehicle_purchase']) ? 'Véhicule' : $doc['project_name'] }}</small>
                                        @if ($doc['file_path'])
                                            <a href="{{ asset('storage/' . $doc['file_path']) }}" target="_blank" class="ms-2 text-primary">
                                                <i class="bx bx-file"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="mb-1">
                                        <span class="badge {{ $doc['is_expired'] ? 'bg-light-red' : ($doc['is_urgent'] ? 'bg-light-yellow' : 'bg-light-green') }} rounded-pill">
                                            {{ \Carbon\Carbon::parse($doc['expires_at'])->format('d/m/Y') }}
                                        </span>
                                    </div>
                                    <small class="{{ $doc['is_expired'] ? 'text-light-red' : ($doc['is_urgent'] ? 'text-light-yellow' : 'text-light-green') }}">
                                        @if ($doc['is_expired'])
                                            Expiré depuis {{ abs($doc['days_until_expiration']) }} jour{{ abs($doc['days_until_expiration']) != 1 ? 's' : '' }}
                                        @else
                                            Dans {{ $doc['days_until_expiration'] }} jour{{ $doc['days_until_expiration'] != 1 ? 's' : '' }}
                                        @endif
                                    </small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="alert alert-light-green">
                        <i class="bx bx-check-circle me-2"></i>
                        Aucun document ou document véhicule expiré ou expirant dans les 24 prochaines heures.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
                                  <!-- Notifications Card -->
<div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2">
    <div class="clickable-card position-relative">
        <a href="{{ route('conges.administratif') }}" class="card card-conge text-decoration-none">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Notifications</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2 {{ ($pendingLeaveNotifications ?? 0) + ($pendingDepenseNotifications ?? 0) > 0 ? 'text-light-yellow' : 'text-light-green' }}">
                                {{ number_format(($pendingLeaveNotifications ?? 0) + ($pendingDepenseNotifications ?? 0), 0, ',', ',') }}
                            </h4>
                            <div class="d-flex flex-column">
                                @if (($pendingLeaveNotifications ?? 0) > 0)
                                    <span class="badge bg-light-yellow rounded-pill mb-1">{{ $pendingLeaveNotifications }} Congés</span>
                                @endif
                                @if (($pendingDepenseNotifications ?? 0) > 0)
                                    <span class="badge bg-light-red rounded-pill">{{ $pendingDepenseNotifications }} Dépenses</span>
                                @endif
                            </div>
                        </div>
                        <small>Demandes en attente</small>
                    </div>
                    <span class="icon-circle {{ ($pendingLeaveNotifications ?? 0) + ($pendingDepenseNotifications ?? 0) > 0 ? 'bg-light-yellow' : 'bg-light-green' }} rounded-circle p-2">
                        <i class="bx bx-bell bx-md"></i>
                    </span>
                </div>
            </div>
        </a>
        <div class="employee-overlay" id="notificationOverlay">
            <div class="employee-overlay-header">
                <h5>Notifications</h5>
                <span class="badge rounded-pill {{ ($pendingLeaveNotifications ?? 0) + ($pendingDepenseNotifications ?? 0) > 0 ? 'bg-light-yellow' : 'bg-light-green' }}">
                    {{ ($pendingLeaveNotifications ?? 0) + ($pendingDepenseNotifications ?? 0) }}
                </span>
            </div>
            <div id="notificationOverlayContent">
                @if (($pendingLeaveNotifications ?? 0) + ($pendingDepenseNotifications ?? 0) > 0)
                    <ul class="list-group">
                        @foreach ($pendingLeaveNotificationDetails as $notification)
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-light-yellow-opacity-10 border-light-yellow text-light-yellow">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-light-yellow me-2">CONGÉ</span>
                                    <div>
                                        <strong>{{ $notification['message'] }}</strong>
                                        <br>
                                        <small class="text-muted">Demande de congé</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light-yellow rounded-pill">En attente</span>
                                </div>
                            </li>
                        @endforeach
                        @foreach ($pendingDepenseNotificationDetails as $notification)
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-light-red-opacity-10 border-light-red text-light-red">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-light-red me-2">DÉPENSE</span>
                                    <div>
                                        <strong>{{ $notification['message'] }}</strong>
                                        <br>
                                        <small class="text-muted">Action sur une dépense</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light-red rounded-pill">En attente</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="alert alert-light-green">
                        <i class="bx bx-check-circle me-2"></i>
                        Aucune notification en attente.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
                 
                        <!-- Projects Table -->
                        <div class="col-md-8 order-3 order-lg-4 mb-4 mb-lg-0">
                            <div class="card text-center">
                                <div class="card-header py-3">
                                    <select id="yearFilter" class="form-select w-auto" onchange="window.location.href='?year='+this.value">
                @foreach ($projectYears as $projectYear)
                    <option value="{{ $projectYear }}" {{ $year == $projectYear ? 'selected' : '' }}>{{ $projectYear }}</option>
                @endforeach
            </select>
                                    <ul class="nav nav-pills" role="tablist">
                                        <li class="nav-item">
                                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-projects" aria-controls="navs-pills-projects" aria-selected="true">
                                                Projets Publics
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-bc" aria-controls="navs-pills-bc" aria-selected="false">
                                                BC
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-browser" aria-controls="navs-pills-browser" aria-selected="false">
                                                Privé
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-closed" aria-controls="navs-pills-closed" aria-selected="false">
                                                Clôturés
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content pt-0">
                                    <div class="tab-pane fade show active" id="navs-pills-projects" role="tabpanel">
                                        <div class="table-responsive text-start">
                                            <table class="table table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Projet</th>
                                                        <th>Documents</th>
                                                        <th>Progression</th>
                                                        <th class="w-50">Data In Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($publicProjectsProgress as $index => $project)
                                                        <tr>
                                                            <td>{{ $publicProjectsProgress->firstItem() + $index }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <i class="bx bx-building-house bx-sm me-2"></i>
                                                                    <span>{{ $project['intitule'] }}</span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                @if (count($project['missing_fields']) == 0)
                                                                    <i class="bx bx-check-circle doc-status doc-complete" data-bs-toggle="tooltip" title="Tous les documents sont complets"></i>
                                                                @elseif(count($project['missing_fields']) <= 2)
                                                                    <i class="bx bx-error-circle doc-status doc-warning" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-html="true" data-bs-content="{{ implode('<br>', array_map('htmlspecialchars', $project['missing_fields'])) }}" title="Documents manquants ({{ count($project['missing_fields']) }}/{{ count($fields) }})"></i>
                                                                @else
                                                                    <i class="bx bx-x-circle doc-status doc-incomplete" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-html="true" data-bs-content="{{ implode('<br>', array_map('htmlspecialchars', $project['missing_fields'])) }}" title="Documents manquants ({{ count($project['missing_fields']) }}/{{ count($fields) }})"></i>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-{{ $project['cloture'] == 1 ? 'success' : ($project['progress'] >= 70 ? 'success' : ($project['progress'] >= 40 ? 'warning' : 'danger')) }} rounded-pill">
                                                                    {{ number_format($project['progress'], 2) }}%
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex justify-content-between align-items-center gap-3">
                                                                    <div class="progress w-100" style="height: 10px">
                                                                        <div class="progress-bar {{ $project['cloture'] == 1 ? 'bg-success' : ($project['progress'] >= 70 ? 'bg-success' : ($project['progress'] >= 40 ? 'bg-warning' : 'bg-danger')) }}" role="progressbar" style="width: {{ $project['cloture'] == 1 ? 100 : $project['progress'] }}%" aria-valuenow="{{ $project['cloture'] == 1 ? 100 : $project['progress'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                                    </div>
                                                                    <small class="fw-semibold">{{ number_format($project['cloture'] == 1 ? 100 : $project['progress'], 2) }}%</small>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center">Aucun projet public disponible.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                            @if ($publicProjectsProgress->hasPages())
                                                <div class="d-flex justify-content-center mt-3">
                                                    {{ $publicProjectsProgress->appends(['public_page' => $publicProjectsProgress->currentPage()])->links('vendor.pagination.bootstrap-4') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="navs-pills-bc" role="tabpanel">
                                        <div class="table-responsive text-start">
                                            <table class="table table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Projet</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($bcProjectsProgress as $index => $project)
                                                        <tr>
                                                            <td>{{ $bcProjectsProgress->firstItem() + $index }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <i class="bx bx-building-house bx-sm me-2"></i>
                                                                    <span>{{ $project['intitule'] }}</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center">Aucun projet BC disponible.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                            @if ($bcProjectsProgress->hasPages())
                                                <div class="d-flex justify-content-center mt-3">
                                                    {{ $bcProjectsProgress->appends(['bc_page' => $bcProjectsProgress->currentPage()])->links('vendor.pagination.bootstrap-4') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="navs-pills-browser" role="tabpanel">
                                        <div class="table-responsive text-start">
                                            <table class="table table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Projet</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($privateProjectsProgress as $index => $project)
                                                        <tr>
                                                            <td>{{ $privateProjectsProgress->firstItem() + $index }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <i class="bx bx-building-house bx-sm me-2"></i>
                                                                    <span>{{ $project['intitule'] }}</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center">Aucun projet privé disponible.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                            @if ($privateProjectsProgress->hasPages())
                                                <div class="d-flex justify-content-center mt-3">
                                                    {{ $privateProjectsProgress->appends(['private_page' => $privateProjectsProgress->currentPage()])->links('vendor.pagination.bootstrap-4') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="navs-pills-closed" role="tabpanel">
                                        <div class="table-responsive text-start">
                                            <table class="table table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Projet</th>
                                                        <th>Documents</th>
                                                        <th>Progression</th>
                                                        <th class="w-50">Data In Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($closedProjectsProgress as $index => $project)
                                                        <tr>
                                                            <td>{{ $closedProjectsProgress->firstItem() + $index }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <i class="bx bx-building-house bx-sm me-2"></i>
                                                                    <span>{{ $project['intitule'] }}</span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                @if (count($project['missing_fields']) == 0)
                                                                    <i class="bx bx-check-circle doc-status doc-complete" data-bs-toggle="tooltip" title="Tous les documents sont complets"></i>
                                                                @elseif(count($project['missing_fields']) <= 2)
                                                                    <i class="bx bx-error-circle doc-status doc-warning" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-html="true" data-bs-content="{{ implode('<br>', array_map('htmlspecialchars', $project['missing_fields'])) }}" title="Documents manquants ({{ count($project['missing_fields']) }}/{{ count($fields) }})"></i>
                                                                @else
                                                                    <i class="bx bx-x-circle doc-status doc-incomplete" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-html="true" data-bs-content="{{ implode('<br>', array_map('htmlspecialchars', $project['missing_fields'])) }}" title="Documents manquants ({{ count($project['missing_fields']) }}/{{ count($fields) }})"></i>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-success rounded-pill">{{ number_format($project['progress'], 2) }}%</span>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex justify-content-between align-items-center gap-3">
                                                                    <div class="progress w-100" style="height: 10px">
                                                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $project['progress'] }}%" aria-valuenow="{{ $project['progress'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                                    </div>
                                                                    <small class="fw-semibold">{{ number_format($project['progress'], 2) }}%</small>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center">Aucun projet clôturé disponible.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                            @if ($closedProjectsProgress->hasPages())
                                                <div class="d-flex justify-content-center mt-3">
                                                    {{ $closedProjectsProgress->appends(['closed_page' => $closedProjectsProgress->currentPage()])->links('vendor.pagination.bootstrap-4') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                <!-- Employee Statistics Chart -->
<div class="col-md-4 order-4 order-lg-5 mb-4 mb-lg-0">
    <div class="card">
        <div class="card-header py-3">
            <h5 class="mb-0">Statistiques des Employés</h5>
        </div>
        <div class="card-body">
            <div class="statistics-chart-container">
                <canvas id="statisticsChart"></canvas>
                <div class="statistics-chart-center">
                    <h3>{{ $totalSalaries }}</h3>
                    <small>Employés</small>
                </div>
            </div>
            <div class="employee-overlay" id="employeeOverlay">
                <div class="employee-overlay-header">
                    <h5 id="overlayTitle"></h5>
                    <span id="overlayCount" class="badge rounded-pill"></span>
                </div>
                <div id="overlayContent"></div>
            </div>
            <div class="statistics-legend mt-3">
                <div class="statistics-legend-item">
                    <div class="statistics-legend-color bg-success"></div>
                    <span>Présents ({{ $totalPresent }})</span>
                </div>
                <div class="statistics-legend-item">
                    <div class="statistics-legend-color bg-warning"></div>
                    <span>En Congé ({{ $totalOnLeave }})</span>
                </div>
                <div class="statistics-legend-item">
                    <div class="statistics-legend-color bg-danger"></div>
                    <span>Absents ({{ $totalAbsent }})</span>
                </div>
            </div>
        </div>
    </div>
</div>
                            
                        </div>
                        <!-- Salary Payments Chart -->
                                <div class="col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Paiements des Salaires par Mois</h5>
                                    <select id="periodFilter" class="form-select w-auto" onchange="window.location.href='?period='+this.value">
                                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Année {{ $currentYear }}</option>
                                        <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Dernier mois</option>
                                        <option value="last_7_days" {{ $period == 'last_7_days' ? 'selected' : '' }}>Derniers 7 jours</option>
                                        <option value="today" {{ $period == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                                    </select>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="salaryChart"></canvas>
                                    </div>
                                </div>
                            </div>
                    </div>
                </div>

                <!-- Vehicles Dashboard Tab -->
                <div class="tab-pane fade" id="vehicles-dashboard" role="tabpanel">
                    <div class="row g-4">
                   <!-- Vehicle Summary Card -->
<div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2">
    <div class="clickable-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Total Véhicules</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">{{ $totalVehicles }}</h4>
                        </div>
                        <small>Véhicules Actifs</small>
                    </div>
                    <span class="icon-circle bg-label-info rounded-circle p-2">
                        <i class="bx bx-car bx-md"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
  <!-- Total Vehicle Expenses Card -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2">
                            <a href="{{ route('depenses.index') }}" class="clickable-card">
                                <div class="card card-vehicle-expenses">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="content-left">
                                                <span>Dépenses Véhicules</span>
                                                <div class="d-flex align-items-end mt-2">
                                                    <h4 class="mb-0 me-2">{{ number_format($totalVehicleExpenses, 0, ',', ',') }} DH</h4>
                                                </div>
                                                <small>Total Dépenses Véhicules</small>
                                            </div>
                                            <span class="icon-circle bg-label-danger rounded-circle p-2">
                                                <i class="bx bx-wallet bx-md"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!-- Expiring Vehicle Documents Card -->
                  <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2">
    <div class="clickable-card position-relative">
        <a href="" class="card card-expiring-vehicles text-decoration-none">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Véhicules Expirant</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2 {{ $vehicleExpired > 0 ? 'text-light-red' : ($vehicleUrgent > 0 ? 'text-light-yellow' : 'text-light-green') }}">
                                {{ $vehicleExpired + $vehicleUrgent }}
                            </h4>
                        </div>
                        <small>
                            @if($vehicleExpired > 0)
                                {{ $vehicleExpired }} doc(s) expiré(s)
                                @if($vehicleUrgent > 0)
                                    , {{ $vehicleUrgent }} urgent(s)
                                @endif
                            @elseif($vehicleUrgent > 0)
                                {{ $vehicleUrgent }} doc(s) urgent(s)
                            @else
                                Aucun document urgent
                            @endif
                        </small>
                    </div>
                    <span class="icon-circle {{ $vehicleExpired > 0 ? 'bg-light-red' : ($vehicleUrgent > 0 ? 'bg-light-yellow' : 'bg-light-green') }} rounded-circle p-2">
                        <i class="bx {{ $vehicleExpired > 0 ? 'bx-calendar-x' : 'bx-calendar-exclamation' }} bx-md"></i>
                    </span>
                </div>
            </div>
        </a>
        <div class="employee-overlay" id="vehicleExpiringOverlay">
            <div class="employee-overlay-header">
                <h5>Documents Véhicules Expirant</h5>
                <span class="badge rounded-pill {{ $vehicleExpired > 0 ? 'bg-light-red' : ($vehicleUrgent > 0 ? 'bg-light-yellow' : 'bg-light-green') }}">{{ $vehicleExpired + $vehicleUrgent }}</span>
            </div>
            <div id="vehicleExpiringOverlayContent">
                @if ($vehicleExpired + $vehicleUrgent > 0)
                    <ul class="list-group">
                        @foreach ($expiringDocuments as $doc)
                            @if (in_array($doc['category'], ['vehicle_insurance', 'vehicle_registration', 'vehicle_inspection', 'vehicle_purchase']))
                                <li class="list-group-item d-flex justify-content-between align-items-center 
                                    {{ $doc['is_expired'] ? 'bg-light-red-opacity-10 border-light-red text-light-red' : ($doc['is_urgent'] ? 'bg-light-yellow-opacity-10 border-light-yellow text-light-yellow' : '') }}">
                                    <div class="d-flex align-items-center">
                                        @if ($doc['is_expired'])
                                            <span class="badge bg-light-red me-2">EXPIRÉ</span>
                                        @elseif ($doc['is_urgent'])
                                            <span class="badge bg-light-yellow me-2">URGENT</span>
                                        @endif
                                        <div>
                                            <strong>{{ $doc['label'] }}</strong>
                                            <br>
                                            <small class="text-muted">Véhicule</small>
                                            @if ($doc['file_path'])
                                                <a href="{{ asset('storage/' . $doc['file_path']) }}" target="_blank" class="ms-2 text-primary">
                                                    <i class="bx bx-file"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="mb-1">
                                            <span class="badge {{ $doc['is_expired'] ? 'bg-light-red' : ($doc['is_urgent'] ? 'bg-light-yellow' : 'bg-light-green') }} rounded-pill">
                                                {{ \Carbon\Carbon::parse($doc['expires_at'])->format('d/m/Y') }}
                                            </span>
                                        </div>
                                        <small class="{{ $doc['is_expired'] ? 'text-light-red' : ($doc['is_urgent'] ? 'text-light-yellow' : 'text-light-green') }}">
                                            @if ($doc['is_expired'])
                                                Expiré depuis {{ abs($doc['days_until_expiration']) }} jour{{ abs($doc['days_until_expiration']) != 1 ? 's' : '' }}
                                            @else
                                                Dans {{ $doc['days_until_expiration'] }} jour{{ $doc['days_until_expiration'] != 1 ? 's' : '' }}
                                            @endif
                                        </small>
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @else
                    <div class="alert alert-light-green">
                        <i class="bx bx-check-circle me-2"></i>
                        Aucun document de véhicule expiré ou urgent.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
                   <!-- Vehicles Table -->
<div class="col-12">
    <div class="card">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Liste des Véhicules</h5>
            <select id="vehicleFilter" class="form-select w-auto">
                <option value="all">Tous les véhicules</option>
                <option value="expired">Documents Expirés</option>
                <option value="urgent">Documents Urgents</option>
            </select>
        </div>
        <div class="card-body">
            <div class="table-responsive text-start">
                <table class="table table-borderless" id="vehiclesTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Matricule</th>
                            <th>Assurance</th>
                            <th>Carte Grise</th>
                            <th>Visite Technique</th>
                            <th>Contrat d'Achat</th>
                        </tr>
                    </thead>
                    <tbody id="vehicles-tbody">
                        @forelse ($vehiclesPaginated as $index => $vehicle)
                            <tr data-vehicle-id="{{ $vehicle->id }}">
                                <td>{{ $vehiclesPaginated->firstItem() + $index }}</td>
                                <td>{{ $vehicle->matricule }}</td>
                                <td>
                                    @if ($vehicle->assurance_expires_at)
                                        <span class="badge {{ Carbon::parse($vehicle->assurance_expires_at)->lt(now()) ? 'bg-light-red' : (Carbon::parse($vehicle->assurance_expires_at)->lte(now()->addDays(7)) ? 'bg-light-yellow' : 'bg-light-green') }} rounded-pill">
                                            {{ Carbon::parse($vehicle->assurance_expires_at)->format('d/m/Y') }}
                                        </span>
                                        @if ($vehicle->assurance_path)
                                            <a href="{{ asset('storage/' . $vehicle->assurance_path) }}" target="_blank" class="ms-2 text-primary">
                                                <i class="bx bx-file"></i>
                                            </a>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary rounded-pill">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($vehicle->carte_grise_expires_at)
                                        <span class="badge {{ Carbon::parse($vehicle->carte_grise_expires_at)->lt(now()) ? 'bg-light-red' : (Carbon::parse($vehicle->carte_grise_expires_at)->lte(now()->addDays(7)) ? 'bg-light-yellow' : 'bg-light-green') }} rounded-pill">
                                            {{ Carbon::parse($vehicle->carte_grise_expires_at)->format('d/m/Y') }}
                                        </span>
                                        @if ($vehicle->carte_grise_path)
                                            <a href="{{ asset('storage/' . $vehicle->carte_grise_path) }}" target="_blank" class="ms-2 text-primary">
                                                <i class="bx bx-file"></i>
                                            </a>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary rounded-pill">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($vehicle->visite_technique_expires_at)
                                        <span class="badge {{ Carbon::parse($vehicle->visite_technique_expires_at)->lt(now()) ? 'bg-light-red' : (Carbon::parse($vehicle->visite_technique_expires_at)->lte(now()->addDays(7)) ? 'bg-light-yellow' : 'bg-light-green') }} rounded-pill">
                                            {{ Carbon::parse($vehicle->visite_technique_expires_at)->format('d/m/Y') }}
                                        </span>
                                        @if ($vehicle->visite_technique_path)
                                            <a href="{{ asset('storage/' . $vehicle->visite_technique_path) }}" target="_blank" class="ms-2 text-primary">
                                                <i class="bx bx-file"></i>
                                            </a>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary rounded-pill">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($vehicle->contrat_achat_expires_at)
                                        <span class="badge {{ Carbon::parse($vehicle->contrat_achat_expires_at)->lt(now()) ? 'bg-light-red' : (Carbon::parse($vehicle->contrat_achat_expires_at)->lte(now()->addDays(7)) ? 'bg-light-yellow' : 'bg-light-green') }} rounded-pill">
                                            {{ Carbon::parse($vehicle->contrat_achat_expires_at)->format('d/m/Y') }}
                                        </span>
                                        @if ($vehicle->contrat_achat_path)
                                            <a href="{{ asset('storage/' . $vehicle->contrat_achat_path) }}" target="_blank" class="ms-2 text-primary">
                                                <i class="bx bx-file"></i>
                                            </a>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary rounded-pill">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Aucun véhicule disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3" id="vehicles-pagination-container">
                @if ($vehiclesPaginated->hasPages())
                    <nav>
                        <ul class="pagination">
                            {{-- Bouton Précédent --}}
                            @if ($vehiclesPaginated->currentPage() > 1)
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="{{ $vehiclesPaginated->currentPage() - 1 }}">Précédent</a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link">Précédent</span>
                                </li>
                            @endif

                            {{-- Numéros de page --}}
                            @php
                                $startPage = max(1, $vehiclesPaginated->currentPage() - 2);
                                $endPage = min($vehiclesPaginated->lastPage(), $vehiclesPaginated->currentPage() + 2);
                            @endphp

                            @if ($startPage > 1)
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="1">1</a>
                                </li>
                                @if ($startPage > 2)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                            @endif

                            @for ($i = $startPage; $i <= $endPage; $i++)
                                @if ($i == $vehiclesPaginated->currentPage())
                                    <li class="page-item active">
                                        <span class="page-link">{{ $i }}</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="#" data-page="{{ $i }}">{{ $i }}</a>
                                    </li>
                                @endif
                            @endfor

                            @if ($endPage < $vehiclesPaginated->lastPage())
                                @if ($endPage < $vehiclesPaginated->lastPage() - 1)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="{{ $vehiclesPaginated->lastPage() }}">{{ $vehiclesPaginated->lastPage() }}</a>
                                </li>
                            @endif

                            {{-- Bouton Suivant --}}
                            @if ($vehiclesPaginated->currentPage() < $vehiclesPaginated->lastPage())
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="{{ $vehiclesPaginated->currentPage() + 1 }}">Suivant</a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link">Suivant</span>
                                </li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- Tableau État Vidange -->
<div class="col-12 mt-4">
    <div class="card">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
        <i class="bx bx-droplet me-2 text-info"></i>
        État de Véhicule
    </h5>
    <button onclick="printVidangeTable()" class="btn btn-primary btn-sm">
        <i class="bx bx-printer me-1"></i> Imprimer
    </button>
</div>
        <div class="card-body">
            <div class="table-responsive text-start">
                <table class="table table-borderless" id="vidangeTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Matricule</th>
                            <th>État Vidange</th>
                            <th>Amortisseur</th>
                            <th>Courroie</th>
                            <th>Pneus</th>
                            <th>Plaquettes</th>
                        </tr>
                    </thead>
                    <tbody id="vidange-tbody">
                      @forelse ($vehicleVidangeStatus as $index => $vehicle)
  @php
    $etatNum = is_numeric($vehicle->etat_vidange) ? (float) $vehicle->etat_vidange : null;
    $isAlert = $etatNum !== null && $etatNum >= 10000;

    // Helper avec seuil numérique
    $getNumBadge = function($val, $threshold) {
        if (is_null($val) || $val === '') 
            return '<span class="badge bg-secondary rounded-pill">N/A</span>';
        
        $num = is_numeric($val) ? (float) $val : null;
        
        if ($num !== null) {
           $isBad = $num >= $threshold;
            $class = $isBad ? 'bg-danger' : 'bg-success';
            $icon  = $isBad ? 'bx-error-circle' : 'bx-check-circle';
            $alert = $isBad ? '<small class="text-danger ms-1"><i class="bx bx-wrench"></i> À vérifier !</small>' : '';
            return "<span class=\"badge {$class} rounded-pill\"><i class=\"bx {$icon} me-1\"></i>{$val} km</span>{$alert}";
        }
        
        // Valeur texte (bon/mauvais etc.)
        $badStates = ['mauvais', 'usé', 'usee', 'use', 'à changer', 'a changer', 'critique'];
        $isBad = in_array(strtolower(trim($val)), $badStates);
        $class = $isBad ? 'bg-danger' : 'bg-success';
        $icon  = $isBad ? 'bx-x-circle' : 'bx-check-circle';
        return "<span class=\"badge {$class} rounded-pill\"><i class=\"bx {$icon} me-1\"></i>{$val}</span>";
    };
        @endphp
        <tr class="{{ $isAlert ? 'table-danger' : '' }}">
            <td>{{ $index + 1 }}</td>
            <td>
                <i class="bx bx-car me-1 text-info"></i>
                <strong>{{ $vehicle->matricule }}</strong>
            </td>
            <td>
                @if ($isAlert)
                    <span class="badge bg-danger rounded-pill">
                        <i class="bx bx-error-circle me-1"></i>{{ $vehicle->etat_vidange }} km
                    </span>
                    <small class="text-danger ms-1"><i class="bx bx-wrench"></i> Vidange requise !</small>
                @elseif ($etatNum !== null)
                    <span class="badge bg-success rounded-pill">
                        <i class="bx bx-check-circle me-1"></i>{{ $vehicle->etat_vidange }} km
                    </span>
                @else
                    <span class="badge bg-secondary rounded-pill">{{ $vehicle->etat_vidange ?? 'N/A' }}</span>
                @endif
            </td>
            <td>{!! $getNumBadge($vehicle->etat_amortisseur, 80000) !!}</td>
            <td>{!! $getNumBadge($vehicle->etat_courroie, 100000) !!}</td>
            <td>{!! $getNumBadge($vehicle->etat_pneus, 80000) !!}</td>
            <td>{!! $getNumBadge($vehicle->etat_plaquettes, 40000) !!}</td>
        </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-3">
                    <i class="bx bx-info-circle me-1"></i>Aucun véhicule disponible.
                </td>
            </tr>
        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3" id="vidange-pagination-container"></div>
        </div>
    </div>
</div>
                        <div class="col-12 col-md-6 mb-4">
    <div class="card">
        <div class="card-header py-3">
            <h5 class="mb-0">Dépenses par Véhicule</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="vehicleExpensesChart"></canvas>
            </div>
        </div>
    </div>
</div>
                        <!-- Vehicles Dashboard Tab -->
<div class="tab-pane fade" id="vehicles-dashboard" role="tabpanel">
    <div class="row g-4">
        <!-- Total Vehicles Card -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2">
            <div class="clickable-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>Total Véhicules</span>
                                <div class="d-flex align-items-end mt-2">
                                    <h4 class="mb-0 me-2">{{ $totalVehicles }}</h4>
                                </div>
                                <small>Véhicules Actifs</small>
                            </div>
                            <span class="icon-circle bg-label-info rounded-circle p-2">
                                <i class="bx bx-car bx-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </div>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Existing JavaScript code for notifications, tooltips, popovers, and charts
            const leaveNotifications = @json($pendingLeaveNotificationDetails);
            const depenseNotifications = @json($pendingDepenseNotificationDetails);
            const notificationOverlay = document.getElementById('notificationOverlay');
            const notificationOverlayTitle = document.getElementById('notificationOverlayTitle');
            const notificationOverlayCount = document.getElementById('notificationOverlayCount');
            const notificationOverlayContent = document.getElementById('notificationOverlayContent');

            const notificationBadges = document.querySelectorAll('.notification-badge');
            notificationBadges.forEach(badge => {
                badge.addEventListener('mouseenter', function() {
                    const type = this.getAttribute('data-type');
                    let notifications, title, badgeClass;
                    if (type === 'leave') {
                        notifications = leaveNotifications;
                        title = 'Notifications de Congés';
                        badgeClass = 'bg-warning';
                    } else if (type === 'depense') {
                        notifications = depenseNotifications;
                        title = 'Notifications de Dépenses';
                        badgeClass = 'bg-danger';
                    }
                    notificationOverlayTitle.textContent = title;
                    notificationOverlayCount.textContent = notifications.length;
                    notificationOverlayCount.className = `badge rounded-pill ${badgeClass}`;
                    if (notifications.length > 0) {
                        notificationOverlayContent.innerHTML = `
                            <ul class="list-group">
                                ${notifications.map(notification => `
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        ${notification.message}
                                        <span class="badge ${badgeClass} rounded-pill">${type === 'leave' ? 'Congé' : 'Dépense'}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        `;
                    } else {
                        notificationOverlayContent.innerHTML = `
                            <div class="alert alert-info">Aucune notification ${type === 'leave' ? 'de congé' : 'de dépense'} en attente.</div>
                        `;
                    }
                    notificationOverlay.classList.add('show');
                });
                badge.addEventListener('mouseleave', function() {
                    notificationOverlay.classList.remove('show');
                });
            });
            document.querySelector('.clickable-card').addEventListener('mouseleave', function() {
                notificationOverlay.classList.remove('show');
            });

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl, { html: true });
            });
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.forEach(function(popoverTriggerEl) {
                new bootstrap.Popover(popoverTriggerEl, { html: true });
            });

            const statisticsCtx = document.getElementById('statisticsChart').getContext('2d');
            if (statisticsCtx) {
                const totalPresent = {{ $totalPresent }};
                const totalAbsent = {{ $totalAbsent }};
                const totalOnLeave = {{ $totalOnLeave }};
                const totalEmployees = {{ $totalSalaries }};
                const presentEmployees = @json($presentEmployees);
                const onLeaveEmployees = @json($onLeaveEmployees);
                const absentEmployees = @json($absentEmployees);
                const presentPercentage = totalEmployees > 0 ? (totalPresent / totalEmployees) * 100 : 0;
                const absentPercentage = totalEmployees > 0 ? (totalAbsent / totalEmployees) * 100 : 0;
                const leavePercentage = totalEmployees > 0 ? (totalOnLeave / totalEmployees) * 100 : 0;
                new Chart(statisticsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Présents', 'En Congé', 'Absents'],
                        datasets: [{
                            data: [presentPercentage, leavePercentage, absentPercentage],
                            backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                            borderWidth: 0,
                            cutout: '75%'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false }, tooltip: { enabled: false } },
                        onHover: function(event, elements) {
                            const overlay = document.getElementById('employeeOverlay');
                            const overlayTitle = document.getElementById('overlayTitle');
                            const overlayCount = document.getElementById('overlayCount');
                            const overlayContent = document.getElementById('overlayContent');
                            if (elements && elements.length > 0) {
                                const index = elements[0].index;
                                const labels = ['Présents', 'En Congé', 'Absents'];
                                const counts = [totalPresent, totalOnLeave, totalAbsent];
                                const employees = [presentEmployees, onLeaveEmployees, absentEmployees];
                                const badgeClasses = ['bg-success', 'bg-warning', 'bg-danger'];
                                overlayTitle.textContent = labels[index];
                                overlayCount.textContent = counts[index];
                                overlayCount.className = `badge rounded-pill ${badgeClasses[index]}`;
                                if (employees[index].length > 0) {
                                    overlayContent.innerHTML = `
                                        <ul class="list-group">
                                            ${employees[index].map(emp => `
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    ${emp}
                                                    <span class="badge ${badgeClasses[index]} rounded-pill">${labels[index]}</span>
                                                </li>
                                            `).join('')}
                                        </ul>
                                    `;
                                } else {
                                    overlayContent.innerHTML = `
                                        <div class="alert alert-info">Aucun employé ${labels[index].toLowerCase()} pour cette période.</div>
                                    `;
                                }
                                overlay.classList.add('show');
                            } else {
                                overlay.classList.remove('show');
                            }
                        },
                        onClick: function(event, elements) {
                            if (elements && elements.length > 0) {
                                const index = elements[0].index;
                                let url;
                                if (index === 0) url = '{{ route('presence.index') }}?status=present';
                                else if (index === 1) url = '{{ route('conge.index') }}?status=active';
                                else url = '{{ route('presence.index') }}?status=absent';
                                window.location.href = url;
                            }
                        }
                    }
                });
                document.getElementById('statisticsChart').addEventListener('mousemove', function(event) {
                    const activePoints = statisticsChart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, false);
                    this.style.cursor = activePoints.length > 0 ? 'pointer' : 'default';
                });
                document.getElementById('statisticsChart').addEventListener('mouseleave', function() {
                    document.getElementById('employeeOverlay').classList.remove('show');
                });
            }

            // Expiring Documents Overlay Trigger
            const expiringDocumentsCard = document.querySelector('.card-expiring-documents').closest('.clickable-card');
            const expiringDocumentsOverlay = document.getElementById('expiringDocumentsOverlay');
            if (expiringDocumentsCard && expiringDocumentsOverlay) {
                expiringDocumentsCard.addEventListener('mouseenter', function() {
                    expiringDocumentsOverlay.classList.add('show');
                });
                expiringDocumentsCard.addEventListener('mouseleave', function() {
                    expiringDocumentsOverlay.classList.remove('show');
                });
            }

            // Vehicle Expiring Overlay Trigger
            const vehicleExpiringCard = document.querySelector('.card-expiring-vehicles').closest('.clickable-card');
            const vehicleExpiringOverlay = document.getElementById('vehicleExpiringOverlay');
            if (vehicleExpiringCard && vehicleExpiringOverlay) {
                vehicleExpiringCard.addEventListener('mouseenter', function() {
                    vehicleExpiringOverlay.classList.add('show');
                });
                vehicleExpiringCard.addEventListener('mouseleave', function() {
                    vehicleExpiringOverlay.classList.remove('show');
                });
            }

            // Vehicle Filter Script
            const vehicleFilter = document.getElementById('vehicleFilter');
           if (vehicleFilter) {
        vehicleFilter.addEventListener('change', function() {
            applyVehicleFilter(this.value);  // Délègue à la fonction jQuery qui cible #vehiclesTable
        });
    }

            // Salary Chart Configuration
            const salaryCtx = document.getElementById('salaryChart');
            if (salaryCtx) {
                const salaryLabels = @json($salaryChartLabels);
                const salaryValues = @json($salaryChartValues);
                const currentYear = @json($currentYear);

                // Debug data
                console.log('Salary Chart Data:', {
                    labels: salaryLabels,
                    values: salaryValues,
                    year: currentYear
                });

                // Fallback data if empty
                const defaultLabels = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                const defaultValues = Array(12).fill(0);
                const chartLabels = Array.isArray(salaryLabels) && salaryLabels.length ? salaryLabels : defaultLabels;
                const chartValues = Array.isArray(salaryValues) && salaryValues.length ? salaryValues : defaultValues;

                try {
                    new Chart(salaryCtx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: chartLabels,
                            datasets: [{
                                label: 'Paiements des Salaires (' + currentYear + ')',
                                data: chartValues,
                                backgroundColor: 'rgba(124, 58, 237, 0.7)',
                                borderColor: 'rgba(124, 58, 237, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: { grid: { display: false }, title: { display: true, text: 'Mois' } },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(229, 231, 235, 0.5)' },
                                    title: { display: true, text: 'Montant (MAD)' },
                                    ticks: {
                                        callback: function(value) {
                                            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD', minimumFractionDigits: 0 }).format(value);
                                        }
                                    }
                                }
                            },
                            plugins: {
                                legend: { display: true, position: 'top' },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return 'Total: ' + new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(context.parsed.y);
                                        }
                                    }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error initializing salary chart:', error);
                }
            } else {
                console.error('Salary chart canvas element not found');
            }
        });
        // Scroll Icon Behavior
const scrollIcon = document.getElementById('scrollIcon');
if (scrollIcon) {
    const updateScrollIcon = () => {
        const scrollPosition = window.scrollY;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const nearBottom = scrollPosition + windowHeight >= documentHeight - 30;
        const nearTop = scrollPosition <= 30;

        if (nearBottom) {
            scrollIcon.innerHTML = '<i class="bx bx-chevron-up bx-sm"></i>';
            scrollIcon.setAttribute('data-scroll-direction', 'up');
        } else if (nearTop) {
            scrollIcon.innerHTML = '<i class="bx bx-chevron-down bx-sm"></i>';
            scrollIcon.setAttribute('data-scroll-direction', 'down');
        } else {
            scrollIcon.innerHTML = '<i class="bx bx-chevron-up bx-sm"></i>';
            scrollIcon.setAttribute('data-scroll-direction', 'up');
        }
    };

    window.addEventListener('scroll', updateScrollIcon);
    updateScrollIcon();

    scrollIcon.addEventListener('click', (e) => {
        const scrollPosition = window.scrollY;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const nearBottom = scrollPosition + windowHeight >= documentHeight - 30;

        if (nearBottom) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            window.scrollTo({ top: documentHeight, behavior: 'smooth' });
        }

        // Dispatch custom event for extensibility
        const scrollEvent = new CustomEvent('scrollIconClick', {
            detail: { direction: nearBottom ? 'up' : 'down' }
        });
        document.dispatchEvent(scrollEvent);
    });
}
// Vehicle Expenses Chart Configuration
document.querySelector('button[data-bs-target="#vehicles-dashboard"]').addEventListener('shown.bs.tab', function () {
    const vehicleExpensesCtx = document.getElementById('vehicleExpensesChart');
    if (vehicleExpensesCtx && !vehicleExpensesCtx.chart) { // Prevent re-initialization
        const vehicleExpensesData = @json($vehicleExpenses);
        console.log('Vehicle Expenses Data:', vehicleExpensesData);

        const defaultLabels = ['Aucun véhicule'];
        const defaultValues = [0];
        const chartLabels = vehicleExpensesData.length ? vehicleExpensesData.map(item => item.matricule) : defaultLabels;
        const chartValues = vehicleExpensesData.length ? vehicleExpensesData.map(item => parseFloat(item.total_expense)) : defaultValues;

        try {
            vehicleExpensesCtx.chart = new Chart(vehicleExpensesCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Dépenses par Véhicule (MAD)',
                        data: chartValues,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { grid: { display: false }, title: { display: true, text: 'Véhicule (Matricule)' } },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(229, 231, 235, 0.5)' },
                            title: { display: true, text: 'Montant (MAD)' },
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD', minimumFractionDigits: 0 }).format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Total: ' + new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' }).format(context.parsed.y);
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error initializing vehicle expenses chart:', error);
        }
    }
});
    </script>
 <script>
// Variables pour la pagination des véhicules
let currentVehiclePage = {{ $vehiclesPaginated->currentPage() }};
let totalVehiclePages = {{ $vehiclesPaginated->lastPage() }};

// Fonction pour charger les véhicules via AJAX
function loadVehicles(page = 1) {
    $.ajax({
        url: '{{ route("dashboard.vehicles.paginated") }}',
        type: 'GET',
        data: { page: page },
        beforeSend: function() {
            $('#vehicles-tbody').html('<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></td></tr>');
        },
        success: function(response) {
            currentVehiclePage = response.current_page;
            totalVehiclePages = response.last_page;
            
            // Construire le HTML du tbody
            let html = '';
            if (response.vehicles.length > 0) {
                response.vehicles.forEach((vehicle, index) => {
                    const rowNumber = response.from + index;
                    html += buildVehicleRow(vehicle, rowNumber);
                });
            } else {
                html = '<tr><td colspan="6" class="text-center">Aucun véhicule disponible.</td></tr>';
            }
            
            $('#vehicles-tbody').html(html);
            
            // Générer la pagination
            buildVehiclePagination(response);
            
            // Réappliquer le filtre si nécessaire
            const currentFilter = $('#vehicleFilter').val();
            if (currentFilter !== 'all') {
                applyVehicleFilter(currentFilter);
            }
        },
        error: function(xhr) {
            console.error('Erreur lors du chargement des véhicules:', xhr);
            $('#vehicles-tbody').html('<tr><td colspan="6" class="text-center text-danger">Erreur lors du chargement des données.</td></tr>');
        }
    });
}

// Fonction pour construire une ligne de véhicule
function buildVehicleRow(vehicle, rowNumber) {
    const now = new Date();
    const urgentThreshold = new Date();
    urgentThreshold.setDate(urgentThreshold.getDate() + 7);
    
    // Helper pour déterminer la classe badge
    function getBadgeClass(dateStr) {
        if (!dateStr) return 'bg-secondary';
        const date = new Date(dateStr);
        if (date < now) return 'bg-light-red';
        if (date <= urgentThreshold) return 'bg-light-yellow';
        return 'bg-light-green';
    }
    
    // Helper pour créer une cellule de document
    function buildDocCell(expiresAt, formattedDate, path) {
        if (!expiresAt) {
            return '<span class="badge bg-secondary rounded-pill">N/A</span>';
        }
        const badgeClass = getBadgeClass(expiresAt);
        let html = `<span class="badge ${badgeClass} rounded-pill">${formattedDate}</span>`;
        if (path) {
            html += `<a href="/storage/${path}" target="_blank" class="ms-2 text-primary"><i class="bx bx-file"></i></a>`;
        }
        return html;
    }
    
    return `
        <tr data-vehicle-id="${vehicle.id}">
            <td>${rowNumber}</td>
            <td>${vehicle.matricule || 'N/A'}</td>
            <td>${buildDocCell(vehicle.assurance_expires_at, vehicle.formatted_assurance_expires_at, vehicle.assurance_path)}</td>
            <td>${buildDocCell(vehicle.carte_grise_expires_at, vehicle.formatted_carte_grise_expires_at, vehicle.carte_grise_path)}</td>
            <td>${buildDocCell(vehicle.visite_technique_expires_at, vehicle.formatted_visite_technique_expires_at, vehicle.visite_technique_path)}</td>
            <td>${buildDocCell(vehicle.contrat_achat_expires_at, vehicle.formatted_contrat_achat_expires_at, vehicle.contrat_achat_path)}</td>
        </tr>
    `;
}

// Fonction pour construire la pagination
function buildVehiclePagination(response) {
    if (response.last_page <= 1) {
        $('#vehicles-pagination-container').html('');
        return;
    }
    
    let html = '<nav><ul class="pagination">';
    
    // Bouton Précédent
    if (response.current_page > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${response.current_page - 1}">Précédent</a></li>`;
    } else {
        html += '<li class="page-item disabled"><span class="page-link">Précédent</span></li>';
    }
    
    // Numéros de page
    const startPage = Math.max(1, response.current_page - 2);
    const endPage = Math.min(response.last_page, response.current_page + 2);
    
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
        if (startPage > 2) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === response.current_page) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
    }
    
    if (endPage < response.last_page) {
        if (endPage < response.last_page - 1) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${response.last_page}">${response.last_page}</a></li>`;
    }
    
    // Bouton Suivant
    if (response.current_page < response.last_page) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${response.current_page + 1}">Suivant</a></li>`;
    } else {
        html += '<li class="page-item disabled"><span class="page-link">Suivant</span></li>';
    }
    
    html += '</ul></nav>';
    
    $('#vehicles-pagination-container').html(html);
}

// Event listener pour les clics sur la pagination
$(document).on('click', '#vehicles-pagination-container .page-link', function(e) {
    e.preventDefault();
    const page = $(this).data('page');
    if (page) {
        loadVehicles(page);
    }
});

// Fonction pour appliquer le filtre
function applyVehicleFilter(filter) {
    const rows = document.querySelectorAll('#vehiclesTable tbody tr');
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) {
            return; // Ignorer les lignes vides
        }
        
        const assurance = row.querySelector('td:nth-child(3) .badge');
        const carteGrise = row.querySelector('td:nth-child(4) .badge');
        const visiteTechnique = row.querySelector('td:nth-child(5) .badge');
        const contratAchat = row.querySelector('td:nth-child(6) .badge');
        
        const isExpired = [assurance, carteGrise, visiteTechnique, contratAchat].some(
            badge => badge && badge.classList.contains('bg-light-red')
        );
        const isUrgent = [assurance, carteGrise, visiteTechnique, contratAchat].some(
            badge => badge && badge.classList.contains('bg-light-yellow')
        );
        
        if (filter === 'all') {
            row.style.display = '';
        } else if (filter === 'expired' && isExpired) {
            row.style.display = '';
        } else if (filter === 'urgent' && isUrgent) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Event listener pour le filtre
$('#vehicleFilter').on('change', function() {
    applyVehicleFilter($(this).val());
});
</script>
<script>// Pagination Vidange
const vidangeData    = @json($vehicleVidangeStatus);
const vidangePerPage = 5;
let currentVidangePage = 1;
function buildVidangeRow(vehicle, rowNumber) {
    const etatNum = parseFloat(vehicle.etat_vidange);
    const isAlert = !isNaN(etatNum) && etatNum >= 10000;

    const badStates = ['mauvais', 'usé', 'usee', 'use', 'à changer', 'a changer', 'critique'];

    // Helper avec seuil numérique
    function getNumBadge(val, threshold) {
        if (!val && val !== 0) return '<span class="badge bg-secondary rounded-pill">N/A</span>';
        
        const num = parseFloat(val);
        
        if (!isNaN(num)) {
            const isBad = num >= threshold;
            const cls   = isBad ? 'bg-danger' : 'bg-success';
            const icon  = isBad ? 'bx-error-circle' : 'bx-check-circle';
            const alert = isBad ? '<small class="text-danger ms-1"><i class="bx bx-wrench"></i> À vérifier !</small>' : '';
            return `<span class="badge ${cls} rounded-pill"><i class="bx ${icon} me-1"></i>${val} km</span>${alert}`;
        }
        
        // Valeur texte
        const isBad = badStates.includes(String(val).toLowerCase().trim());
        const cls   = isBad ? 'bg-danger' : 'bg-success';
        const icon  = isBad ? 'bx-x-circle' : 'bx-check-circle';
        return `<span class="badge ${cls} rounded-pill"><i class="bx ${icon} me-1"></i>${val}</span>`;
    }

    let vidangeBadge = '';
    if (isAlert) {
        vidangeBadge = `<span class="badge bg-danger rounded-pill"><i class="bx bx-error-circle me-1"></i>${vehicle.etat_vidange} km</span>
                        <small class="text-danger ms-1"><i class="bx bx-wrench"></i> Vidange requise !</small>`;
    } else if (!isNaN(etatNum)) {
        vidangeBadge = `<span class="badge bg-success rounded-pill"><i class="bx bx-check-circle me-1"></i>${vehicle.etat_vidange} km</span>`;
    } else {
        vidangeBadge = `<span class="badge bg-secondary rounded-pill">${vehicle.etat_vidange ?? 'N/A'}</span>`;
    }

    return `
        <tr class="${isAlert ? 'table-danger' : ''}">
            <td>${rowNumber}</td>
            <td><i class="bx bx-car me-1 text-info"></i><strong>${vehicle.matricule}</strong></td>
            <td>${vidangeBadge}</td>
            <td>${getNumBadge(vehicle.etat_amortisseur, 80000)}</td>
            <td>${getNumBadge(vehicle.etat_courroie, 100000)}</td>
            <td>${getNumBadge(vehicle.etat_pneus, 80000)}</td>
            <td>${getNumBadge(vehicle.etat_plaquettes, 40000)}</td>
        </tr>`;
}

function loadVidangePage(page) {
    currentVidangePage  = page;
    const totalPages    = Math.ceil(vidangeData.length / vidangePerPage);
    const start         = (page - 1) * vidangePerPage;
    const pageData      = vidangeData.slice(start, start + vidangePerPage);

    // Tbody
    let html = '';
    if (pageData.length > 0) {
        pageData.forEach((vehicle, index) => {
            html += buildVidangeRow(vehicle, start + index + 1);
        });
    } else {
        html = `<tr><td colspan="3" class="text-center text-muted py-3">
                    <i class="bx bx-info-circle me-1"></i>Aucun véhicule disponible.
                </td></tr>`;
    }
    $('#vidange-tbody').html(html);

    // Pagination
    if (totalPages <= 1) {
        $('#vidange-pagination-container').html('');
        return;
    }

    const startPage = Math.max(1, page - 2);
    const endPage   = Math.min(totalPages, page + 2);

    let paginationHtml = '<nav><ul class="pagination">';

    // Précédent
    paginationHtml += page > 1
        ? `<li class="page-item"><a class="page-link" href="#" data-vidange-page="${page - 1}">Précédent</a></li>`
        : `<li class="page-item disabled"><span class="page-link">Précédent</span></li>`;

    // Première page + ...
    if (startPage > 1) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-vidange-page="1">1</a></li>`;
        if (startPage > 2) paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }

    // Pages numérotées
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += i === page
            ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
            : `<li class="page-item"><a class="page-link" href="#" data-vidange-page="${i}">${i}</a></li>`;
    }

    // ... + Dernière page
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-vidange-page="${totalPages}">${totalPages}</a></li>`;
    }

    // Suivant
    paginationHtml += page < totalPages
        ? `<li class="page-item"><a class="page-link" href="#" data-vidange-page="${page + 1}">Suivant</a></li>`
        : `<li class="page-item disabled"><span class="page-link">Suivant</span></li>`;

    paginationHtml += '</ul></nav>';
    $('#vidange-pagination-container').html(paginationHtml);
}

// Initialisation
loadVidangePage(1);




// Clic pagination
$(document).on('click', '#vidange-pagination-container .page-link', function(e) {
    e.preventDefault();
    const page = $(this).data('vidange-page');
    if (page) loadVidangePage(page);
});

</script>
<script>
function printVidangeTable() {
    const rowsPerPage = 12;
    const pageData = [];
    for (let i = 0; i < vidangeData.length; i += rowsPerPage) {
        pageData.push(vidangeData.slice(i, i + rowsPerPage));
    }

    const badStates = ['mauvais', 'usé', 'usee', 'use', 'à changer', 'a changer', 'critique'];

    function getCellText(val, threshold) {
        if (!val && val !== 0) return '—';
        const num = parseFloat(val);
        if (!isNaN(num)) {
            return num >= threshold ? `⚠ ${val} km` : `${val} km`;
        }
        const isBad = badStates.includes(String(val).toLowerCase().trim());
        return isBad ? `✗ ${val}` : `${val}`;
    }

    const tableContentPages = pageData.map((pageRows) => {
        const tableContent = pageRows.map((vehicle, index) => {
            const etatNum = parseFloat(vehicle.etat_vidange);
            const isAlert = !isNaN(etatNum) && etatNum >= 10000;

            let vidangeText = '—';
            if (isAlert) {
                vidangeText = `⚠ ${vehicle.etat_vidange} km — Vidange requise !`;
            } else if (!isNaN(etatNum)) {
                vidangeText = `${vehicle.etat_vidange} km`;
            } else if (vehicle.etat_vidange) {
                vidangeText = vehicle.etat_vidange;
            }

            return `
                <tr class="table-tr" style="${isAlert ? 'background-color:#ffe0e0;' : ''}">
                    <td>${index + 1}</td>
                    <td>${vehicle.matricule || '—'}</td>
                    <td>${vidangeText}</td>
                    <td>${getCellText(vehicle.etat_amortisseur, 80000)}</td>
                    <td>${getCellText(vehicle.etat_courroie, 100000)}</td>
                    <td>${getCellText(vehicle.etat_pneus, 80000)}</td>
                    <td>${getCellText(vehicle.etat_plaquettes, 40000)}</td>
                </tr>`;
        }).join('');

        return `
            <div class="print-body-2" style="margin-bottom:8px; page-break-after: always;">
                <table class="table table-bordered">
                    <tr style="background-color:rgb(233, 233, 233);">
                        <td class="td-bold">No</td>
                        <td class="td-bold">Matricule</td>
                        <td class="td-bold">État Vidange</td>
                        <td class="td-bold">Amortisseur</td>
                        <td class="td-bold">Courroie</td>
                        <td class="td-bold">Pneus</td>
                        <td class="td-bold">Plaquettes</td>
                    </tr>
                    ${tableContent}
                </table>
            </div>`;
    }).join('');

    const page_html = `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>État des Véhicules</title>
            <style>
                html, body, * { padding: 0; margin: 0; box-sizing: border-box; font-family: sans-serif; }
                @page { size: landscape; margin: 10mm; }
                body * { -webkit-print-color-adjust: exact !important; }
                .print { width: 100%; height: auto; padding: 18px 18px 0 18px; }
                .print-header { width: 100%; height: auto; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
                .print-header-title { font-size: 15px; font-weight: bold; line-height: 1.45; margin-left: 10px; }
                .print-header-type { width: 100%; height: 23px; line-height: 21px; text-align: center; background-color: #efefef; border: 1px solid black; margin-bottom: 10px; font-size: 13px; font-weight: bold; letter-spacing: 0.5px; }
                .print-body-2 table { width: 100%; }
                .print-body-2 table, th, td { border: 1px solid black; border-collapse: collapse; padding: 6px; text-align: center; font-size: 12px; font-weight: 500; vertical-align: middle; }
                .table-tr td { padding: 6px; }
                .print-footer { width: 100%; display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; margin-top: 10px; }
                .print-footer h4 { display: inline-block; margin-right: 20px; font-size: 13px; }
                .td-bold { font-size: 13px; font-weight: bold; }
                .logo-title-container { display: flex; align-items: center; }
                .logo-title-container img { max-width: 50px; height: auto; }
                @media print {
                    body { margin: 2mm !important; }
                    .print { margin: 0; padding: 18px 18px 0 18px; }
                    .table-tr { page-break-inside: avoid !important; }
                }
            </style>
        </head>
        <body>
            <div class="print">
                <div class="print-header">
                    <div class="logo-title-container">
                        <h1 class="print-header-title">État des Véhicules</h1>
                    </div>
                    <div class="print-header-type">État des Véhicules</div>
                </div>
                ${tableContentPages || '<p style="text-align:center">Aucun véhicule disponible.</p>'}
                <div class="print-footer">
                    <div>
                        <h4>A: Oujda</h4>
                        <h4>Le: ${new Date().toLocaleDateString('fr-FR')}</h4>
                    </div>
                </div>
            </div>
        </body>
        </html>`;

    const iframe = $("<iframe/>", {
        id: "printVidangeIframe",
        style: "position: absolute; width: 0; height: 0; border: none;",
    }).appendTo("body");

    const iframeDoc = iframe[0].contentWindow.document;
    iframeDoc.open();
    iframeDoc.write(page_html);
    iframeDoc.close();

    setTimeout(function () {
        iframe[0].contentWindow.focus();
        iframe[0].contentWindow.print();
    }, 500);

    iframe[0].contentWindow.onafterprint = function () {
        iframe.remove();
    };
    setTimeout(function () {
        if (iframe[0]) iframe.remove();
    }, 5000);
}</script>


    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endsection