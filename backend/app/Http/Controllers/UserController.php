<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(User::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string|in:admin,gestionnaire,agent',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        return response()->json($user, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'sometimes|required|string|in:admin,gestionnaire,agent',
            'password' => 'sometimes|nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email', 'role']);
        if ($request->has('password') && $request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    /**
     * Toggle the active status of the user.
     */
    public function toggleStatus(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => $user->is_active ? 'Compte activé' : 'Compte désactivé',
            'user' => $user
        ]);
    }
}
