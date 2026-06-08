<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Liste tous les utilisateurs.
     */
    public function index()
    {
        return response()->json([
            'message' => 'Utilisateurs récupérés avec succès',
            'data'    => User::with('agent')->get(),
        ]);
    }

    /**
     * Crée un compte utilisateur.
     * Si rôle = agent et agent_id fourni, le compte est lié à l'agent existant.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'role'     => 'required|string|in:admin,gestionnaire,agent',
            'password' => 'required|string|min:8',
            'agent_id' => 'nullable|integer|exists:agents,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier qu'un agent n'est pas déjà lié à un compte
        if ($request->role === 'agent' && $request->agent_id) {
            $alreadyLinked = Agent::where('id', $request->agent_id)
                ->whereNotNull('user_id')
                ->exists();

            if ($alreadyLinked) {
                return response()->json([
                    'errors' => ['agent_id' => ['Cet agent possède déjà un compte utilisateur.']],
                ], 422);
            }
        }

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'role'      => $request->role,
            'password'  => Hash::make($request->password),
            'is_active' => true,
        ]);

        // Lier l'agent au nouveau compte
        if ($request->role === 'agent' && $request->agent_id) {
            Agent::where('id', $request->agent_id)->update(['user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'data'    => $user->load('agent'),
        ], 201);
    }

    /**
     * Met à jour un utilisateur.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'role'     => 'sometimes|required|string|in:admin,gestionnaire,agent',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Utilisateur modifié avec succès',
            'data'    => $user,
        ]);
    }

    /**
     * Active ou désactive un compte.
     */
    public function toggleStatus(User $user)
    {
        $user->is_active = ! $user->is_active;
        $user->save();

        // Synchroniser le statut de l'agent lié si existant
        if ($user->agent) {
            $user->agent->update([
                'statut' => $user->is_active ? 'actif' : 'inactif',
            ]);
        }

        return response()->json([
            'message' => $user->is_active ? 'Compte activé' : 'Compte désactivé',
            'data'    => $user,
        ]);
    }
}
