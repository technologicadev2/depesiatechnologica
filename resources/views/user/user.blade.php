@extends('master_page.app')

@section('title')
    Liste utilisateur
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h4 class="fw-bold py-3 mb-4">Liste des utilisateurs</h4>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-basic table table-striped table-hover border-top" id="usersTable">
                <thead>
                    <tr>
                        <th>Nom utilisateur</th>
                        <th>Matricule Salarié</th>
                        <th>Statut</th>
                        <th class="cell-fit">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->username ?? '-' }}</td>
                            <td>{{ $user->salarie ? $user->salarie->n_matricule_entreprise : 'N/A' }}</td>
                            <td>
                                <span
                                    class="badge {{ $user->role ? ($user->role->name == 'Superadmin' ? 'bg-label-primary' : ($user->role->name == 'Admin' ? 'bg-label-success' : 'bg-label-info')) : 'bg-label-secondary' }}">
                                    {{ $user->role ? $user->role->name : 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:;" class="text-body edit-user" data-id="{{ $user->id }}"
                                        data-bs-toggle="tooltip" title="Modifier"><i class="bx bx-edit mx-1"></i></a>
                                    <a href="javascript:;" class="text-body delete-record" data-id="{{ $user->id }}"
                                        data-bs-toggle="tooltip" title="Supprimer"><i class="bx bx-trash mx-1"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Aucun utilisateur trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal pour modification -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            </div>
        </div>
    </div>

    <!-- Modal pour réinitialiser le mot de passe -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Réinitialiser le mot de passe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="resetPasswordForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" id="new_password_confirmation"
                                name="new_password_confirmation" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Valider</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Offcanvas pour ajouter un utilisateur -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="add-new-record" aria-labelledby="addNewRecordLabel"
        data-bs-backdrop="false">
        <div class="offcanvas-header">
            <h5 id="addNewRecordLabel" class="offcanvas-title">Ajouter un utilisateur</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="form-add-new-record">
                @csrf

                <div class="mb-3">
                    <label for="id_salarie" class="form-label">Salarié</label>
                    <select class="form-select" id="id_salarie" name="id_salarie" required>
                        <option value="">Sélectionner un salarié</option>
                        @foreach ($salaries as $salarie)
                            <option value="{{ $salarie->id }}" data-nom="{{ $salarie->nom }}"
                                data-prenom="{{ $salarie->prenom }}"
                                data-matricule="{{ $salarie->n_matricule_entreprise }}">
                                {{ $salarie->n_matricule_entreprise }} - {{ $salarie->nom }} {{ $salarie->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="generated-username" class="form-label">Nom d'utilisateur généré</label>
                    <input type="text" class="form-control" id="generated-username" name="username" readonly
                        placeholder="Sélectionnez un salarié pour générer le nom d'utilisateur">
                </div>
                <div class="mb-3">
                    <label for="generated-password" class="form-label">Mot de passe généré</label>
                    <input type="text" class="form-control" id="generated-password" name="password" readonly
                        placeholder="Sélectionnez un salarié pour générer le mot de passe">
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Rôle</label>
                    <select class="form-select" id="role" name="role_id" required>
                        <option value="">Sélectionner un rôle</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Permissions de menu supplémentaires</label>
                    <div class="row">
                        @foreach ($menus as $menu)
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="menu_permissions[]"
                                        value="{{ $menu->menu_name }}" id="menu_{{ $menu->menu_name }}">
                                    <label class="form-check-label" for="menu_{{ $menu->menu_name }}">{{ $menu->label }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mb-3 text-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/js/list-user.js') }}"></script>
@endsection