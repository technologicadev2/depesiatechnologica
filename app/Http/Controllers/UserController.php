<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Salarie;
use App\Models\UserMenuPermission;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function getMenuPermissions()
    {
        return response()->json([
            'menu_permissions' => Menu::all()->map(function ($menu) {
                return [
                    'menu_name' => $menu->menu_name,
                    'label' => $menu->label,
                ];
            })
        ]);
    }

    public function showUsersView()
    {
        $roles = Role::all();
        $users = User::with('role')->get();
        $salaries = Salarie::select('id', 'n_matricule_entreprise', 'nom', 'prenom')->where("statut", "actif")->get();
        $menus = Menu::all();
        return view('user.user', compact('roles', 'users', 'salaries', 'menus'));
    }

    public function index()
    {
        return User::with(['role', 'salarie'])->where('id', '!=', 2)->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'role_id' => $user->role_id,
                'role_name' => $user->role ? $user->role->name : 'N/A',
                'n_matricule_entreprise' => $user->salarie ? $user->salarie->n_matricule_entreprise : 'N/A',
                'id_salarie' => $user->id_salarie,
            ];
        });
    }

    public function store(Request $request)
    {
        try {
            Log::info('Submitted menu_permissions:', $request->input('menu_permissions', []));
            $validated = $request->validate([
                'role_id' => 'required|integer|exists:roles,id',
                'id_salarie' => 'required|integer|exists:salaries,id',
                'menu_permissions' => 'nullable|array',
                'menu_permissions.*' => 'string|exists:menu_permissions,menu_name',
            ]);

            $salarie = Salarie::find($validated['id_salarie']);
            if (!$salarie->nom || !$salarie->prenom) {
                throw ValidationException::withMessages([
                    'id_salarie' => 'Le salarié sélectionné doit avoir un nom et un prénom valides.',
                ]);
            }

            $prenom = iconv('UTF-8', 'ASCII//TRANSLIT', $salarie->prenom) ?: $salarie->prenom;
            $nom = iconv('UTF-8', 'ASCII//TRANSLIT', $salarie->nom) ?: $salarie->nom;
            $cleanPrenom = preg_replace('/[^a-z0-9]/', '', strtolower($prenom));
            $cleanNomInitial = preg_replace('/[^a-z0-9]/', '', strtolower(substr($nom, 0, 1)));
            $baseUsername = empty($cleanPrenom) || empty($cleanNomInitial) ? 'user_' . $salarie->n_matricule_entreprise : $cleanPrenom . '.' . $cleanNomInitial;

            $expectedUsername = $baseUsername;
            $suffix = 1;
            while (User::where('username', $expectedUsername)->exists()) {
                $expectedUsername = $baseUsername . '_' . $suffix++;
                if (strlen($expectedUsername) > 20) {
                    throw ValidationException::withMessages([
                        'id_salarie' => 'Le nom d\'utilisateur généré dépasse la longueur maximale autorisée (20 caractères).',
                    ]);
                }
            }

            $finalUsername = $expectedUsername;
            $password = $finalUsername;

            $user = User::create([
                'username' => $finalUsername,
                'password' => Hash::make($password),
                'role_id' => $validated['role_id'],
                'id_salarie' => $validated['id_salarie'],
                'created_by' => Auth::id(),
            ]);

            $this->assignMenuPermissions($user->id, $validated['menu_permissions'] ?? []);

            return response()->json([
                'id' => $user->id,
                'username' => $user->username,
                'role_id' => $user->role_id,
                'role_name' => $user->role ? $user->role->name : 'N/A',
            ], 201);
        } catch (ValidationException $e) {
            Log::error('Validation error:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'utilisateur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::all();
        $salaries = Salarie::select('id', 'n_matricule_entreprise', 'nom', 'prenom')->get();
        $menuPermissions = UserMenuPermission::where('user_id', $id)->pluck('menu_name')->toArray();
        $menus = Menu::all();
        return view('user.update', compact('user', 'roles', 'salaries', 'menuPermissions', 'menus'));
    }

    public function update(Request $request, $id)
    {
        Log::info('Updating user ID ' . $id . ', Submitted data:', [
            'input' => $request->all(),
            'user_id' => Auth::id(),
            'role' => Auth::user()->role->name ?? 'none'
        ]);

        try {
            $validated = $request->validate([
                'role_id' => 'required|integer|exists:roles,id',
                'id_salarie' => 'required|integer|exists:salaries,id',
                'username' => 'required|string|max:255|unique:users,username,' . $id,
                'menu_permissions' => 'nullable|array',
                'menu_permissions.*' => 'string|exists:menu_permissions,menu_name',
            ]);

            $user = User::findOrFail($id);
            Log::info('User found', ['user_id' => $user->id, 'current_username' => $user->username]);

            $user->update([
                'username' => $validated['username'],
                'role_id' => $validated['role_id'],
                'id_salarie' => $validated['id_salarie'],
            ]);

            Log::info('User updated', ['user_id' => $user->id, 'new_username' => $validated['username']]);

            UserMenuPermission::where('user_id', $id)->delete();
            Log::info('Existing menu permissions deleted for user ID ' . $id);

            $this->assignMenuPermissions($id, $validated['menu_permissions'] ?? []);
            Log::info('New menu permissions assigned for user ID ' . $id);

            // Return updated user data with role class
            $roleClass = [
                'superadmin' => 'bg-label-primary',
                'admin' => 'bg-label-success',
                'manager' => 'bg-label-info',
                'salarier' => 'bg-label-warning',
                'responsable' => 'bg-label-secondary',
            ][$user->role->name ?? 'N/A'] ?? 'bg-label-secondary';

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role->name ?? 'N/A',
                    'role_class' => $roleClass,
                ],
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during update:', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur de base de données lors de la mise à jour : ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de l\'utilisateur:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur : ' . $e->getMessage(),
            ], 500);
        }
    }

    private function assignMenuPermissions($userId, $selectedPermissions)
    {
        $menuActions = [
            'salaries.index' => ['salaries.store', 'salaries.edit', 'salaries.update', 'salaries.destroy', 'salaries.demission', 'salaries.preavis.store', 'salaries.reactivate'],
            'conge.index' => ['conge.salarie.store', 'conge.download.pdf'],
            'bulletinsa.index' => ['bulletin.downloadPaySlip', 'bulletin.downloadHiddenPaySlip'],
            'nature_depenses.index' => ['nature_depenses.store', 'nature_depenses.update', 'nature_depenses.destroy'],
            'depenses.varie' => ['depenses.store', 'depenses.edit', 'depenses.update', 'depenses.destroy'],
            'depenses.avancements' => [ 'depenses.store'],
            'depenses.vehicle' => ['depenses.store'],
            'pointage.index' => ['pointage.save'],
            'projets.index' => ['projets.store', 'projets.edit', 'projets.update', 'projets.destroy', 'projets.cloturer', 'projets.decloturer', 'projets.affecter'],
        ];

        $allPermissions = [];
        foreach ($selectedPermissions as $permission) {
            $allPermissions[] = $permission;
            if (isset($menuActions[$permission])) {
                $allPermissions = array_merge($allPermissions, $menuActions[$permission]);
            }
        }
        $allPermissions = array_unique($allPermissions);

        if (!empty($allPermissions)) {
            Log::info('Saving menu permissions for user ID ' . $userId . ': ' . json_encode($allPermissions));
            foreach ($allPermissions as $menu) {
                UserMenuPermission::create([
                    'user_id' => $userId,
                    'menu_name' => $menu,
                ]);
            }
        }
    }

    public function resetPassword(Request $request, $id)
    {
        try {
            $request->validate([
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::findOrFail($id);
            $user->password = Hash::make($request->new_password);
            $user->updated_by = Auth::id();
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation du mot de passe : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            UserMenuPermission::where('user_id', $id)->delete();
            $user->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'utilisateur : ' . $e->getMessage()
            ], 500);
        }
    }
}