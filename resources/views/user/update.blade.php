<div class="modal-header">
    <h5 class="modal-title" id="editUserModalLabel">Modifier l'utilisateur</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <form id="updateUserForm" action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="id_salarie" class="form-label">Salarie</label>
            <select class="form-select" id="id_salarie" name="id_salarie" required>
                <option value="">Selectionner un salarie</option>
                @foreach ($salaries as $salarie)
                    <option value="{{ $salarie->id }}"
                        {{ $user->id_salarie == $salarie->id ? 'selected' : '' }}
                        data-nom="{{ $salarie->nom }}"
                        data-prenom="{{ $salarie->prenom }}"
                        data-matricule="{{ $salarie->n_matricule_entreprise }}">
                        {{ $salarie->n_matricule_entreprise }} - {{ $salarie->nom }} {{ $salarie->prenom }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="username" class="form-label">Nom d'utilisateur</label>
            <input type="text" class="form-control" id="username" name="username" value="{{ $user->username }}" required>
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role" name="role_id" required>
                <option value="">Selectionner un role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Permissions de menu supplementaires</label>
            <div class="row">
                @foreach ($menus as $menu)
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="menu_permissions[]"
                                value="{{ $menu->menu_name }}" id="menu_{{ $menu->menu_name }}"
                                {{ in_array($menu->menu_name, $menuPermissions) ? 'checked' : '' }}>
                            <label class="form-check-label" for="menu_{{ $menu->menu_name }}">{{ $menu->label }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="mb-3 text-end">
            <button type="button" class="btn btn-secondary reset-password-link" data-id="{{ $user->id }}">Reinitialiser le mot de passe</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary">Mettre a jour</button>
        </div>
    </form>
</div>