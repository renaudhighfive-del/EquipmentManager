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
     * Retourne le détail complet d'un utilisateur :
     * - informations du compte
     * - agent lié (avec ses affectations en cours + équipements)
     * - dernière connexion (via tokens Sanctum)
     */
    public function show(User $user)
    {
        $user->load([
            'agent.affectations' => fn ($q) => $q->with('equipement')->latest(),
        ]);

        // Dernière connexion = token le plus récent
        $lastToken = $user->tokens()->latest('last_used_at')->first();

        return response()->json([
            'message' => 'Utilisateur récupéré avec succès',
            'data'    => array_merge($user->toArray(), [
                'last_login_at'   => $lastToken?->last_used_at,
                'tokens_count'    => $user->tokens()->count(),
            ]),
        ]);
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
     * Un utilisateur ne peut pas se désactiver lui-même.
     */
    public function toggleStatus(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas désactiver votre propre compte.',
            ], 403);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

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

    /**
     * Mise à jour du profil de l'utilisateur connecté (nom, email, avatar).
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'   => 'sometimes|required|string|max:255',
            'email'  => "sometimes|required|email|max:255|unique:users,email,{$user->id}",
            'avatar' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email']);

        if ($request->hasFile('avatar')) {
            // Supprimer l'ancien avatar
            if ($user->avatar) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('users/avatars', 'public');
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'data'    => $user,
        ]);
    }

    /**
     * Changement de mot de passe avec vérification de l'ancien.
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'errors' => ['current_password' => ['Le mot de passe actuel est incorrect.']],
            ], 422);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        return response()->json(['message' => 'Mot de passe mis à jour avec succès']);
    }
}
