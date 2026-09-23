<!-- Navbar -->
<meta name="csrf-token" content="{{ csrf_token() }}">
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->
        <!--       <div class="navbar-nav align-items-center">
                <div class="nav-item navbar-search-wrapper mb-0">
                  <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
                    <i class="bx bx-search bx-sm"></i>
                    <span class="d-none d-md-inline-block text-muted">Search (Ctrl+/)</span>
                  </a>
                </div>
              </div> -->
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-auto">

            <!-- Style Switcher -->
            <li class="nav-item me-2 me-xl-0">
                <a class="nav-link style-switcher-toggle hide-arrow" href="javascript:void(0);">
                    <i class="bx bx-sm"></i>
                </a>
            </li>
            <!--/ Style Switcher -->
<!-- Réclamation Button -->
    <li class="nav-item me-2 me-xl-0">
        <a class="nav-link" href="{{ route('tickets.index') }}">
            <i class="bx bx-support bx-sm"></i>
            <span class="d-none d-md-inline-block"></span>
        </a>
    </li>
            <!-- Quick links  -->
            {{-- <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown me-2 me-xl-0">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
                    data-bs-auto-close="outside" aria-expanded="false">
                    <i class="bx bx-grid-alt bx-sm"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end py-0">
                    <div class="dropdown-menu-header border-bottom">
                        <div class="dropdown-header d-flex align-items-center py-3">
                            <h5 class="text-body mb-0 me-auto">Shortcuts</h5>
                            <a href="javascript:void(0)" class="dropdown-shortcuts-add text-body"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Add shortcuts"><i
                                    class="bx bx-sm bx-plus-circle"></i></a>
                        </div>
                    </div>
                    <div class="dropdown-shortcuts-list scrollable-container">
                        <div class="row row-bordered overflow-visible g-0">
                            <div class="dropdown-shortcuts-item col">
                                <span class="dropdown-shortcuts-icon bg-label-secondary rounded-circle mb-2">
                                    <i class="bx bx-calendar fs-4"></i>
                                </span>
                                <a href="app-calendar.html" class="stretched-link">Calendar</a>
                                <small class="text-muted mb-0">Appointments</small>
                            </div>
                            <div class="dropdown-shortcuts-item col">
                                <span class="dropdown-shortcuts-icon bg-label-secondary rounded-circle mb-2">
                                    <i class="bx bx-food-menu fs-4"></i>
                                </span>
                                <a href="app-invoice-list.html" class="stretched-link">Invoice App</a>
                                <small class="text-muted mb-0">Manage Accounts</small>
                            </div>
                        </div>
                        <div class="row row-bordered overflow-visible g-0">
                            <div class="dropdown-shortcuts-item col">
                                <span class="dropdown-shortcuts-icon bg-label-secondary rounded-circle mb-2">
                                    <i class="bx bx-user fs-4"></i>
                                </span>
                                <a href="app-user-list.html" class="stretched-link">User App</a>
                                <small class="text-muted mb-0">Manage Users</small>
                            </div>
                            <div class="dropdown-shortcuts-item col">
                                <span class="dropdown-shortcuts-icon bg-label-secondary rounded-circle mb-2">
                                    <i class="bx bx-check-shield fs-4"></i>
                                </span>
                                <a href="app-access-roles.html" class="stretched-link">Role Management</a>
                                <small class="text-muted mb-0">Permission</small>
                            </div>
                        </div>
                        <div class="row row-bordered overflow-visible g-0">
                            <div class="dropdown-shortcuts-item col">
                                <span class="dropdown-shortcuts-icon bg-label-secondary rounded-circle mb-2">
                                    <i class="bx bx-pie-chart-alt-2 fs-4"></i>
                                </span>
                                <a href="index.html" class="stretched-link">Dashboard</a>
                                <small class="text-muted mb-0">User Profile</small>
                            </div>
                            <div class="dropdown-shortcuts-item col">
                                <span class="dropdown-shortcuts-icon bg-label-secondary rounded-circle mb-2">
                                    <i class="bx bx-cog fs-4"></i>
                                </span>
                                <a href="pages-account-settings-account.html" class="stretched-link">Setting</a>
                                <small class="text-muted mb-0">Account Settings</small>
                            </div>
                        </div>
                        <div class="row row-bordered overflow-visible g-0">
                            <div class="dropdown-shortcuts-item col">
                                <span class="dropdown-shortcuts-icon bg-label-secondary rounded-circle mb-2">
                                    <i class="bx bx-help-circle fs-4"></i>
                                </span>
                                <a href="pages-help-center-landing.html" class="stretched-link">Help Center</a>
                                <small class="text-muted mb-0">FAQs & Articles</small>
                            </div>
                            <div class="dropdown-shortcuts-item col">
                                <span class="dropdown-shortcuts-icon bg-label-secondary rounded-circle mb-2">
                                    <i class="bx bx-window-open fs-4"></i>
                                </span>
                                <a href="modal-examples.html" class="stretched-link">Modals</a>
                                <small class="text-muted mb-0">Useful Popups</small>
                            </div>
                        </div>
                    </div>
                </div>
            </li> --}}
            <!-- Quick links -->
<!-- Notification -->
<li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
    <a class="nav-link dropdown-toggle hide-arrow position-relative" href="javascript:void(0);"
        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
        aria-label="Notifications">
        <i class="bx bx-bell bx-sm"></i>
        @if (auth()->user()->unreadNotifications->count() > 0)
            <span class="badge bg-danger rounded-pill badge-notifications">
                {{ auth()->user()->unreadNotifications->count() }}
            </span>
        @endif
    </a>

    <ul class="dropdown-menu dropdown-menu-end py-0" style="min-width: 360px;">
        <!-- Header -->
        <li class="dropdown-menu-header border-bottom">
            <div class="dropdown-header d-flex align-items-center py-3">
                <h5 class="text-body mb-0 me-auto">Notifications</h5>
                @if (auth()->user()->unreadNotifications->count() > 0)
                    <a href="javascript:void(0)" class="dropdown-notifications-all text-body"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="Marquer toutes comme lues"
                        onclick="markAllAsRead()">
                        <i class="bx fs-4 bx-envelope-open"></i>
                    </a>
                @endif
            </div>
        </li>

        <!-- Notification List -->
        <li class="dropdown-notifications-list scrollable-container">
            <ul class="list-group list-group-flush">
                @forelse (auth()->user()->unreadNotifications as $notification)
                
                    <!-- Notification de dépense -->
                    @if (array_key_exists('depense_id', $notification->data))
                        <li class="list-group-item list-group-item-action dropdown-notifications-item {{ $notification->read_at ? 'marked-as-read' : '' }}">
                            <div class="d-flex position-relative">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar">
                                        <span class="avatar-initial rounded-circle bg-label-{{ $notification->data['action'] === 'deleted' ? 'danger' : 'success' }}">
                                            <i class="bx bx-euro"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        Dépense {{ $notification->data['nature_depense'] ?? 'N/A' }}
                                        <span class="badge bg-{{ $notification->data['action'] === 'deleted' ? 'danger' : 'success' }}-soft rounded-pill fs-xs">
                                            {{ $notification->data['action'] === 'created' ? 'Nouvelle' : ucfirst($notification->data['action']) }}
                                        </span>
                                    </h6>
                                    <p class="mb-0">Code: {{ $notification->data['code'] ?? 'N/A' }}</p>
                                    <p class="mb-0">Montant: DH{{ number_format($notification->data['montant'] ?? 0, 2, ',', ' ') }}</p>
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    <a href="javascript:void(0)" class="dropdown-notifications-read">
                                        <span class="badge badge-dot"></span>
                                    </a>
                                </div>
                                <a href="{{ route('depenses.index', $notification->data['depense_id']) }}" class="stretched-link"></a>
                            </div>
                        </li>

                    <!-- Notification pour les documents expirés -->
                    @elseif (array_key_exists('document_label', $notification->data) || (array_key_exists('message', $notification->data) && str_contains($notification->data['message'], 'document')))
                        <li class="list-group-item list-group-item-action dropdown-notifications-item {{ $notification->read_at ? 'marked-as-read' : '' }}">
                            <div class="d-flex position-relative">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar">
                                        <span class="avatar-initial rounded-circle bg-label-danger">
                                            <i class="bx bx-file-blank"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        Document Expiré
                                        <span class="badge bg-danger rounded-pill fs-xs">Urgent</span>
                                    </h6>
                                    <p class="mb-0">
                                        {{ $notification->data['message'] ?? 'Document expiré: ' . ($notification->data['document_label'] ?? 'N/A') }}
                                    </p>
                                    @if(isset($notification->data['expires_at']))
                                        <p class="mb-0 text-muted">
                                            Expiré le: {{ \Carbon\Carbon::parse($notification->data['expires_at'])->format('d/m/Y') }}
                                        </p>
                                    @endif
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    <a href="javascript:void(0)" class="dropdown-notifications-read">
                                        <span class="badge badge-dot"></span>
                                    </a>
                                </div>
                                <a href="{{ $notification->data['url'] ?? route('parametres') }}" class="stretched-link"></a>
                            </div>
                        </li>
<!-- Notification de préavis -->
@elseif (array_key_exists('type', $notification->data) && $notification->data['type'] === 'preavis_expiration')
    <li class="list-group-item list-group-item-action dropdown-notifications-item {{ $notification->read_at ? 'marked-as-read' : '' }}">
        <div class="d-flex position-relative">
            <div class="flex-shrink-0 me-3">
                <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-warning">
                        <i class="bx bx-time-five"></i>
                    </span>
                </div>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-1">
                    Préavis se terminant bientôt
                    <span class="badge bg-warning rounded-pill fs-xs">
                        {{ $notification->data['days_remaining'] ?? 7 }} jours restants
                    </span>
                </h6>
                <p class="mb-0">
                    {{ $notification->data['message'] ?? 'Un préavis arrive à expiration' }}
                </p>
                <p class="mb-0 text-muted">
                    <strong>Salarié:</strong> {{ $notification->data['salarie_name'] ?? 'N/A' }}
                </p>
                <p class="mb-0 text-muted">
                    <strong>Date début:</strong> {{ isset($notification->data['date_debut']) ? \Carbon\Carbon::parse($notification->data['date_debut'])->format('d/m/Y') : 'N/A' }}
                </p>
                <p class="mb-0 text-muted">
                    <strong>Date fin:</strong> {{ isset($notification->data['date_fin']) ? \Carbon\Carbon::parse($notification->data['date_fin'])->format('d/m/Y') : 'N/A' }}
                </p>
                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
            </div>
            <div class="flex-shrink-0 dropdown-notifications-actions">
                <a href="javascript:void(0)" class="dropdown-notifications-read">
                    <span class="badge badge-dot"></span>
                </a>
            </div>
            <a href="{{ $notification->data['url'] ?? route('salaries.index') }}" class="stretched-link"></a>
        </div>
    </li>

@elseif (array_key_exists('project_name', $notification->data) && str_contains($notification->data['message'], 'réception définitive'))
    <li class="list-group-item list-group-item-action dropdown-notifications-item {{ $notification->read_at ? 'marked-as-read' : '' }}">
        <div class="d-flex position-relative">
            <div class="flex-shrink-0 me-3">
                <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-warning">
                        <i class="bx bx-calendar-check"></i>
                    </span>
                </div>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-1">
                    Réception Définitive Proche
                    <span class="badge bg-warning-soft rounded-pill fs-xs">Alerte</span>
                </h6>
                <p class="mb-0">{{ $notification->data['message'] }}</p>
                <p class="mb-0 text-muted">
                    Date: {{ $notification->data['reception_definitive'] }}
                </p>
                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
            </div>
            <div class="flex-shrink-0 dropdown-notifications-actions">
                <a href="javascript:void(0)" class="dropdown-notifications-read">
                    <span class="badge badge-dot"></span>
                </a>
            </div>
         <a href="{{ route('projets.index') }}" class="stretched-link"></a>

        </div>
            
    </li>               
      @elseif (array_key_exists('conge_id', $notification->data) && !array_key_exists('status', $notification->data))
                        <li class="list-group-item list-group-item-action dropdown-notifications-item {{ $notification->read_at ? 'marked-as-read' : '' }}">
                            <div class="d-flex position-relative">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            <i class="bx bx-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        Demande de congé de {{ $notification->data['salarie_nom'] ?? 'N/A' }}
                                        <span class="badge bg-primary-soft rounded-pill fs-xs">Nouvelle</span>
                                    </h6>
                                    <p class="mb-0">Date de début: {{ isset($notification->data['date_debut']) ? \Carbon\Carbon::parse($notification->data['date_debut'])->format('d/m/Y') : 'N/A' }}</p>
                                    <p class="mb-0">Nombre de jours: {{ $notification->data['nombre_jours'] ?? 'N/A' }}</p>
                                    <p class="mb-0">Raison: {{ $notification->data['raison'] ?? 'N/A' }}</p>
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    <a href="javascript:void(0)" class="dropdown-notifications-read">
                                        <span class="badge badge-dot"></span>
                                    </a>
                                </div>
                                <a href="{{ route('conges.administratif', $notification->data['conge_id']) }}" class="stretched-link"></a>
                            </div>
                        </li>

                    <!-- Notification de statut de congé (acceptée/refusée, pour salariés) -->
                    @elseif (array_key_exists('conge_id', $notification->data) && array_key_exists('status', $notification->data))
                        <li class="list-group-item list-group-item-action dropdown-notifications-item {{ $notification->read_at ? 'marked-as-read' : '' }}">
                            <div class="d-flex position-relative">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar">
                                        <span class="avatar-initial rounded-circle bg-label-{{ $notification->data['status'] == 2 ? 'success' : 'danger' }}">
                                            <i class="bx bx-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        Demande de congé {{ $notification->data['status'] == 2 ? 'acceptée' : 'refusée' }}
                                        <span class="badge bg-{{ $notification->data['status'] == 2 ? 'success' : 'danger' }}-soft rounded-pill fs-xs">
                                            {{ $notification->data['status'] == 2 ? 'Acceptée' : 'Refusée' }}
                                        </span>
                                    </h6>
                                    <p class="mb-0">Date de début: {{ isset($notification->data['date_debut']) ? \Carbon\Carbon::parse($notification->data['date_debut'])->format('d/m/Y') : 'N/A' }}</p>
                                    <p class="mb-0">Nombre de jours: {{ $notification->data['nombre_jours'] ?? 'N/A' }}</p>
                                    <p class="mb-0">Raison: {{ $notification->data['raison'] ?? 'N/A' }}</p>
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    <a href="javascript:void(0)" class="dropdown-notifications-read">
                                        <span class="badge badge-dot"></span>
                                    </a>
                                </div>
                                <a href="{{ route('conge.index', $notification->data['conge_id']) }}" class="stretched-link"></a>
                            </div>
                        </li>
                    @endif
                    
                @empty
                    <li class="list-group-item text-center py-4">
                        <i class="bx bx-bell-off bx-lg text-muted mb-3"></i>
                        <p class="text-muted mb-0">Aucune nouvelle notification</p>
                    </li>
                @endforelse
            </ul>
        </li>

        <!-- Footer -->
        @if (auth()->user()->unreadNotifications->count() > 0)
            <li class="dropdown-menu-footer border-top">
                <a href="javascript:void(0)" class="dropdown-item d-flex justify-content-center p-3" onclick="deleteAllNotifications()">
                    Supprimer toutes les notifications
                </a>
            </li>
        @endif
    </ul>
</li>

<!-- / Notification -->


<!-- User -->
<li class="nav-item navbar-dropdown dropdown-user dropdown">
    <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
            <img src="{{ asset('storage/uploads/' . Auth::user()->profil_image) }}?v={{ time() }}"
                alt="Profile Image" class="rounded" height="40" width="40">
        </div>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item" href="{{ route('profil.index') }}">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar avatar-online">
                            <img src="{{ asset('storage/uploads/' . Auth::user()->profil_image) }}?v={{ time() }}"
                                alt="Profile Image" class="rounded" height="40" width="40">
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <span class="fw-semibold d-block">{{ Auth::user()->prenom }} {{ Auth::user()->nom }}</span>
                        <small class="text-muted">{{ Auth::user()->role->name }}</small>
                    </div>
                </div>
            </a>
        </li>
        <li>
            <div class="dropdown-divider"></div>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('profil.index') }}">
                <i class="bx bx-user me-2"></i>
                <span class="align-middle">Mon Profil</span>
            </a>
        </li>
        @if (Auth::check() && Auth::user()->role->name === 'superadmin')
            <li>
                <a class="dropdown-item" href="{{ route('users.index') }}">
                    <i class="bx bx-user me-2"></i>
                    <span class="align-middle">Utilisateur</span>
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('parametres.index') }}">
                    <i class="bx bx-cog me-2"></i>
                    <span class="align-middle">Paramètres</span>
                </a>
            </li>
        @endif
        <li>
            <div class="dropdown-divider"></div>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="bx bx-power-off me-2"></i>
                <span class="align-middle">Déconnexion</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </li>
    </ul>
</li>
<!--/ User -->
        </ul>
    </div>

    <!-- Search Small Screens -->
    <div class="navbar-search-wrapper search-input-wrapper d-none">
        <input type="text" class="form-control search-input container-xxl border-0" placeholder="Search..."
            aria-label="Search..." />
        <i class="bx bx-x bx-sm search-toggler cursor-pointer"></i>
    </div>
</nav>
<!-- / Navbar -->
<!-- pour supprimer la notification-->
<script src="{{ asset('assets/js/notification.js') }}"></script>
