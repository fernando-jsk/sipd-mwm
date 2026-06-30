<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::withCount('users')->latest()->paginate(10);
        return Inertia::render('Roles/Index', [
            'roles' => $roles
        ]);
    }

    public function create()
    {
        $permissions = Permission::all();
        return Inertia::render('Roles/Create', [
            'permissions' => $permissions
        ]);
    }

    public function store(StoreRoleRequest $request)
    {
        $role = Role::create([
            'name' => $request->name,
        ]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index')->with('message', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $role->load('permissions');
        
        return Inertia::render('Roles/Edit', [
            'role' => $role,
            'permissions' => $permissions
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role->update([
            'name' => $request->name,
        ]);
        
        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $newPermissions = $request->input('permissions', []);
        
        $role->syncPermissions($newPermissions);

        // Sort arrays to compare
        $oldSorted = $oldPermissions;
        $newSorted = $newPermissions;
        sort($oldSorted);
        sort($newSorted);

        if ($oldSorted !== $newSorted) {
            activity()
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties([
                    'attributes' => ['permissions' => $newPermissions],
                    'old' => ['permissions' => $oldPermissions]
                ])
                ->log('updated permissions');
        }

        return redirect()->route('roles.index')->with('message', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'super-admin') {
            return back()->with('message', 'Super admin role cannot be deleted.');
        }

        $role->delete();
        return redirect()->route('roles.index')->with('message', 'Role deleted successfully.');
    }
}
