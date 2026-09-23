<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Models\Permission;

use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return view('roles.index', compact('roles'));
    }

    public function show($id)
    {
        $role = Role::findOrFail($id);
        return view('roles.show', compact('role'));
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));

    }

    public function store(StoreRoleRequest $request)
    {
        $role = Role::create([
            'name' => $request->name,
            'code' => $request->code,
            'created_by' => auth()->id(), 
        ]);

        return response()->json($role, 201);
    }

    public function edit()
    {
        $permissions = Permission::all();
        return view('roles.edit', compact('permissions'));

    }

    public function update(UpdateRoleRequest $request, $id)
    {
        $role = Role::findOrFail($id);

        $role->update([
            'name' => $request->name,
            'code' => $request->code,
            'updated_by' => auth()->id(), 
        ]);

        return response()->json($role);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        $role->deleted_by = auth()->id(); 
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    // Restaurer un rôle supprimé
    // public function restore($id)
    // {
    //     $role = Role::withTrashed()->findOrFail($id);
    //     $role->restore();

    //     return response()->json(['message' => 'Role restored successfully']);
    // }
}
