<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')->latest()->paginate(10);
        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    public function create()
    {
        $roles = Role::all();
        return Inertia::render('Users/Create', [
            'roles' => $roles
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);
        
        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('message', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $user->load('roles');
        
        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $roles
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = [
            'name' => $request->name,
            'username' => $request->username,
        ];
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('message', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('message', 'User deleted successfully.');
    }
}
