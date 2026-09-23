<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
<div class="app-brand demo" style="padding: 50px 15px;">
        @php
            // Determine the link destination based on user role
            $userRole = Auth::check() ? Auth::user()->role->name : null;
            $linkHref = route('dashboard'); 
            if ($userRole === 'responsable') {
                $linkHref = route('pointage.index');
            } elseif ($userRole === 'admin' || $userRole === 'manager') {
                $linkHref = route('nature_depenses.index');
            } elseif ($userRole === 'salarier') {
                $linkHref = route('conge.index');
            }
            
        @endphp
        <a href="{{ $linkHref }}" class="app-brand-link">
            <span class="app-brand-logo demo d-flex justify-content-center">
                <img src="{{ asset('assets/logo/logo2.png') }}" alt="Logo" class="img-fluid mx-auto"
                    style="max-width: 150px; height: auto;">
            </span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto" aria-label="Toggle menu">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow mt-3"></div>
    <ul class="menu-inner py-1 mt-3">
        @php
            // Fetch all menus from the database
            $menus = \App\Models\Menu::all();
            // Define default menus for roles
            $defaultMenus = [
                'salarier' => ['bulletinsa.index', 'conge.index','ordermissionsal.index',],
                'responsable' => [
                    'conge.index',
                    'bulletinsa.index',
                    'pointage.index',
                    'manage-ordres-virement.index',
                    'ordermissionsal.index'
                    
                ],
            ];
            // Get user role and permissions
            $userRole = Auth::check() ? Auth::user()->role->name : null;
            $userPermissions = Auth::check() ? Auth::user()->menuPermissions()->pluck('menu_name')->toArray() : [];
            // Combine default menus with user permissions, ensuring uniqueness
            $accessibleMenus = [];
            if ($userRole && isset($defaultMenus[$userRole])) {
                $accessibleMenus = array_unique(array_merge($defaultMenus[$userRole], $userPermissions));
            } elseif ($userRole === 'superadmin') {
                $accessibleMenus = $menus->pluck('menu_name')->toArray();
            } elseif ($userRole === 'admin' || $userRole === 'manager') {
                $accessibleMenus = array_unique(
                    array_merge(
                        [
                            'nature_depenses.index',
                            'depenses.varie',
                            'depenses.avancements',
                            'depenses.vehicle',
                            'factures.index',
                            'manage-entities.index',
                            'manage-ordres-virement.index', 
                        ],
                        $userPermissions,
                    ),
                );
            }
            // Define menu groups for organization
            $menuGroups = [
                'Gestion des salariés' => ['fonction.index', 'salaries.index', 'salaries.resigned'],
                'Gestion financière' => [
                    'nature_depenses.index',
                    'depenses.varie',
                    'depenses.avancements',
                    'depenses.vehicle',
                    'factures.index',
                    'manage-entities.index',
                    'manage-ordres-virement.index',
                    'clients.index',
                ],
                'Gestion des projets' => ['projets.index', 'decomptes.index'],
                'Administration' => [
                    'conges.administratif',
                    'pointage.administratif',
                    'presence.index',
                    'absences.employees',
                    'bultin.index',
                    'ordermissionsuper.index',
                   

                ],
                'Gestion personnelle' => ['conge.index', 'bulletinsa.index', 'bultin.index','ordermissionsal.index',],
                'Gestion d\'équipe' => ['pointage.index'],
                
            ];
            // Define submenu for Dépenses dropdown
            $depensesSubmenu = ['nature_depenses.index', 'depenses.varie', 'manage-ordres-virement.index',  'depenses.avancements', 'depenses.vehicle'];
            // Define menu icons
            $menuIcons = [
                'dashboard' => 'bx-home-circle',
                'conges.administratif' => 'bx-briefcase',
                'pointage.administratif' => 'bx-time',
                'pointage.index' => 'bx-calendar-check',
                'presence.index' => 'bx-calendar',
                'absences.employees' => 'bx-user-minus',
                'bultin.index' => 'bx-file',
                'nature_depenses.index' => 'bx-money',
                'depenses.varie' => 'bx-wallet',
                'depenses.avancements' => 'bx-trending-up',
                'depenses.vehicle' => 'bx-car',
                'factures.index' => 'bx-receipt',
                'clients.index' => 'bx-receipt',
                'projets.index' => 'bx-list-check',
                'decomptes.index' => 'bx-calculator',
                'conge.index' => 'bx-briefcase',
                'bulletinsa.index' => 'bx-file',
              
                'fonction.index' => 'bx-user',
                'salaries.index' => 'bx-user',
                'salaries.resigned' => 'bx-user',
                'manage-entities.index' => 'bx-building',
                'manage-ordres-virement.index' => 'bx-transfer',
                'ordermissionsuper.index'=> 'bx-transfer',
                'ordermissionsal.index' => 'bx-transfer',

            ];
            // Collect ungrouped menus (those not in any predefined group, excluding dashboard)
            $groupedMenus = array_merge(...array_values($menuGroups));
            $ungroupedMenus = array_diff($accessibleMenus, $groupedMenus, ['dashboard']);
            // Track rendered menus to avoid duplicates
            $renderedMenus = [];
        @endphp

        <!-- Render Menus -->
        @if (Auth::check())
            <!-- Gestion générale (Dashboard) -->
            @if (in_array('dashboard', $accessibleMenus) && !in_array('dashboard', $renderedMenus))
                <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" role="menuitem">
                    <a href="{{ route('dashboard') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-home-circle"></i>
                        <div data-i18n="Console de gestion">Console de gestion</div>
                    </a>
                </li>
                @php $renderedMenus[] = 'dashboard'; @endphp
            @endif

            <!-- Gestion des salariés (Dropdown for all roles with access) -->
            @if (array_intersect($menuGroups['Gestion des salariés'], $accessibleMenus))
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Gestion des salariés</span>
                </li>
                <li
                    class="menu-item {{ request()->routeIs(['salaries.index', 'salaries.resigned', 'fonction.index']) ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-user"></i>
                        <div data-i18n="Salariés">Salariés</div>
                    </a>
                    <ul class="menu-sub">
                        @foreach ($menus as $menu)
                            @if (in_array($menu->menu_name, $menuGroups['Gestion des salariés']) &&
                                    in_array($menu->menu_name, $accessibleMenus) &&
                                    !in_array($menu->menu_name, $renderedMenus))
                                <li class="menu-item {{ request()->routeIs($menu->menu_name) ? 'active' : '' }}">
                                    <a href="{{ route($menu->menu_name) }}" class="menu-link">
                                        <div data-i18n="{{ $menu->label }}">{{ $menu->label }}</div>
                                    </a>
                                </li>
                                @php $renderedMenus[] = $menu->menu_name; @endphp
                            @endif
                        @endforeach
                    </ul>
                </li>
            @endif

            <!-- Gestion des projets -->
            @if (array_intersect($menuGroups['Gestion des projets'], $accessibleMenus))
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Gestion des projets</span>
                </li>
                @if ($userRole === 'superadmin')
                    <!-- Dropdown for superadmin -->
                    <li
                        class="menu-item {{ request()->routeIs(['projets.index', 'decomptes.index']) ? 'active open' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-list-check"></i>
                            <div data-i18n="Projets">Projets</div>
                        </a>
                        <ul class="menu-sub">
                            @foreach ($menus as $menu)
                                @if (in_array($menu->menu_name, $menuGroups['Gestion des projets']) &&
                                        in_array($menu->menu_name, $accessibleMenus) &&
                                        !in_array($menu->menu_name, $renderedMenus))
                                    <li class="menu-item {{ request()->routeIs($menu->menu_name) ? 'active' : '' }}">
                                        <a href="{{ route($menu->menu_name) }}" class="menu-link">
                                            <div data-i18n="{{ $menu->label }}">{{ $menu->label }}</div>
                                        </a>
                                    </li>
                                    @php $renderedMenus[] = $menu->menu_name; @endphp
                                @endif
                            @endforeach
                        </ul>
                    </li>
                @else
                    <!-- Individual menu items for non-superadmin roles -->
                    @foreach ($menus as $menu)
                        @if (in_array($menu->menu_name, $menuGroups['Gestion des projets']) &&
                                in_array($menu->menu_name, $accessibleMenus) &&
                                !in_array($menu->menu_name, $renderedMenus))
                            <li class="menu-item {{ request()->routeIs($menu->menu_name) ? 'active' : '' }}"
                                role="menuitem">
                                <a href="{{ route($menu->menu_name) }}" class="menu-link">
                                    <i
                                        class="menu-icon tf-icons bx {{ $menuIcons[$menu->menu_name] ?? 'bx-menu' }}"></i>
                                    <div data-i18n="{{ $menu->label }}">{{ $menu->label }}</div>
                                </a>
                            </li>
                            @php $renderedMenus[] = $menu->menu_name; @endphp
                        @endif
                    @endforeach
                @endif
            @endif

            <!-- Administration Menus -->
            @if ($userRole === 'superadmin' && array_intersect($menuGroups['Administration'], $accessibleMenus))
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Administration</span>
                </li>
                @foreach ($menus as $menu)
                    @if (in_array($menu->menu_name, $menuGroups['Administration']) &&
                            in_array($menu->menu_name, $accessibleMenus) &&
                            !in_array($menu->menu_name, $renderedMenus))
                        <li class="menu-item {{ request()->routeIs($menu->menu_name) ? 'active' : '' }}"
                            role="menuitem">
                            <a href="{{ route($menu->menu_name) }}" class="menu-link">
                                <i class="menu-icon tf-icons bx {{ $menuIcons[$menu->menu_name] ?? 'bx-menu' }}"></i>
                                <div data-i18n="{{ $menu->label }}">{{ $menu->label }}</div>
                            </a>
                        </li>
                        @php $renderedMenus[] = $menu->menu_name; @endphp
                    @endif
                @endforeach
            @endif

            <!-- Gestion financière -->
            @if (array_intersect($menuGroups['Gestion financière'], $accessibleMenus))
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Gestion financière</span>
                </li>
                @if ($userRole === 'superadmin')
                    <!-- Dropdown for Dépenses -->
                    <li
                        class="menu-item {{ request()->routeIs(['nature_depenses.index', 'depenses.varie', 'depenses.avancements', 'manage-ordres-virement.index', 'depenses.vehicle']) ? 'active open' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-wallet"></i>
                            <div data-i18n="Dépenses">Dépenses</div>
                        </a>
                        <ul class="menu-sub">
                            @foreach ($menus as $menu)
                                @if (in_array($menu->menu_name, $depensesSubmenu) &&
                                        in_array($menu->menu_name, $accessibleMenus) &&
                                        !in_array($menu->menu_name, $renderedMenus))
                                    <li class="menu-item {{ request()->routeIs($menu->menu_name) ? 'active' : '' }}">
                                        <a href="{{ route($menu->menu_name) }}" class="menu-link">
                                            <i
                                                class="menu-icon tf-icons bx {{ $menuIcons[$menu->menu_name] ?? 'bx-menu' }}"></i>
                                            <div data-i18n="{{ $menu->label }}">{{ $menu->label }}</div>
                                        </a>
                                    </li>
                                    @php $renderedMenus[] = $menu->menu_name; @endphp
                                @endif
                            @endforeach
                        </ul>
                    </li>
                @else
                    <!-- Individual menu items for non-superadmin roles -->
                    @foreach ($menus as $menu)
                        @if (in_array($menu->menu_name, $menuGroups['Gestion financière']) &&
                                in_array($menu->menu_name, $accessibleMenus) &&
                                !in_array($menu->menu_name, $renderedMenus))
                            <li class="menu-item {{ request()->routeIs($menu->menu_name) ? 'active' : '' }}"
                                role="menuitem">
                                <a href="{{ route($menu->menu_name) }}" class="menu-link">
                                    <i
                                        class="menu-icon tf-icons bx {{ $menuIcons[$menu->menu_name] ?? 'bx-menu' }}"></i>
                                    <div data-i18n="{{ $menu->label }}">{{ $menu->label }}</div>
                                </a>
                            </li>
                            @php $renderedMenus[] = $menu->menu_name; @endphp
                        @endif
                    @endforeach
                @endif
                <!-- Render remaining Gestion financière menus -->
                @foreach ($menus as $menu)
                    @if (in_array($menu->menu_name, ['factures.index', 'manage-entities.index','clients.index','manage-ordres-virement.index']) &&
                            in_array($menu->menu_name, $accessibleMenus) &&
                            !in_array($menu->menu_name, $renderedMenus))
                        <li class="menu-item {{ request()->routeIs($menu->menu_name) ? 'active' : '' }}"
                            role="menuitem">
                            <a href="{{ route($menu->menu_name) }}" class="menu-link">
                                <i class="menu-icon tf-icons bx {{ $menuIcons[$menu->menu_name] ?? 'bx-menu' }}"></i>
                                <div data-i18n="{{ $menu->label }}">{{ $menu->label }}</div>
                            </a>
                        </li>
                        @php $renderedMenus[] = $menu->menu_name; @endphp
                    @endif
                @endforeach
            @endif

             <!-- Gestion d'équipe -->
            @if ($userRole === 'responsable' && array_intersect($menuGroups['Gestion d\'équipe'], $accessibleMenus))
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Gestion d'équipe</span>
                </li>
                @foreach ($menus as $menu)
                    @if (in_array($menu->menu_name, $menuGroups['Gestion d\'équipe']) &&
                            in_array($menu->menu_name, $accessibleMenus) &&
                            !in_array($menu->menu_name, $renderedMenus))
                        <li class="menu-item {{ request()->routeIs($menu->menu_name) ? 'active' : '' }}"
                            role="menuitem">
                            <a href="{{ route($menu->menu_name) }}" class="menu-link">
                                <i class="menu-icon tf-icons bx {{ $menuIcons[$menu->menu_name] ?? 'bx-menu' }}"></i>
                                <div data-i18n="{{ $menu->label }}">{{ $menu->label }}</div>
                            </a>
                        </li>
                        @php $renderedMenus[] = $menu->menu_name; @endphp
                    @endif
                @endforeach
            @endif
            <!-- Gestion personnelle -->
            @if (in_array($userRole, ['salarier', 'responsable', 'admin', 'manager']) &&
                    array_intersect($menuGroups['Gestion personnelle'], $accessibleMenus))
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Gestion personnelle</span>
                </li>
                @foreach ($menus as $menu)
                    @if (in_array($menu->menu_name, $menuGroups['Gestion personnelle']) &&
                            in_array($menu->menu_name, $accessibleMenus) &&
                            !in_array($menu->menu_name, $renderedMenus))
                        <li class="menu-item {{ request()->routeIs($menu->menu_name) ? 'active' : '' }}"
                            role="menuitem">
                            <a href="{{ route($menu->menu_name) }}" class="menu-link">
                                <i class="menu-icon tf-icons bx {{ $menuIcons[$menu->menu_name] ?? 'bx-menu' }}"></i>
                                <div data-i18n="{{ $menu->label }}">{{ $menu->label }}</div>
                            </a>
                        </li>
                        @php $renderedMenus[] = $menu->menu_name; @endphp
                    @endif
                @endforeach
            @endif

           

            <!-- Additional Menus (for other permissions) -->
            @if (!empty($ungroupedMenus))
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Autres menus</span>
                </li>
                @foreach ($menus as $menu)
                    @if (in_array($menu->menu_name, $ungroupedMenus) && !in_array($menu->menu_name, $renderedMenus))
                        <li class="menu-item {{ request()->routeIs($menu->menu_name) ? 'active' : '' }}"
                            role="menuitem">
                            <a href="{{ route($menu->menu_name) }}" class="menu-link">
                                <i class="menu-icon tf-icons bx {{ $menuIcons[$menu->menu_name] ?? 'bx-menu' }}"></i>
                                <div data-i18n="{{ $menu->label }}">{{ $menu->label }}</div>
                            </a>
                        </li>
                        @php $renderedMenus[] = $menu->menu_name; @endphp
                    @endif
                @endforeach
            @endif
        @endif
    </ul>
</aside>

<style>
    .app-brand-logo img {
        transition: width 0.3s ease;
    }

    .layout-menu-collapsed .app-brand-logo img {
        width: 200px;
    }

    .layout-menu-collapsed .app-brand {
        margin-left: 2px !important;
        padding-left: 2px !important;
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .layout-menu-collapsed .app-brand-logo {
        min-width: 40px;
        overflow: visible;
        display: flex;
        justify-content: center;
    }

    .layout-menu-collapsed .app-brand-link {
        display: flex;
        justify-content: center;
        width: 100%;
    }

    @media (max-width: 768px) {
        .app-brand-logo img {
            max-width: 100px;
        }

        .layout-menu-collapsed .app-brand-logo img {
            width: 30px;
        }
    }
</style>

<script>
    document.querySelector('.layout-menu-toggle').addEventListener('click', function() {
        document.querySelector('.layout-menu').classList.toggle('layout-menu-collapsed');
    });
</script>
