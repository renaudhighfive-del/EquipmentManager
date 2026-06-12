<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Utilisateurs récupérés avec succès',
            'data'    => User::with('agent')->get()->map(fn ($u) => $this->withAvatarUrl($u)),
        ]);
    }

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

        if ($request->input('role') === 'agent' && $request->input('agent_id')) {
            $alreadyLinked = Agent::where('id', $request->input('agent_id'))
                ->whereNotNull('user_id')
                ->exists();

            if ($alreadyLinked) {
                return response()->json([
                    'errors' => ['agent_id' => ['Cet agent possède déjà un compte utilisateur.']],
                ], 422);
            }
        }

        $user = User::create([
            'name'      => $request->input('name'),
            'email'     => $request->input('email'),
            'role'      => $request->input('role'),
            'password'  => Hash::make($request->input('password')),
            'is_active' => true,
        ]);

        if ($request->input('role') === 'agent' && $request->input('agent_id')) {
            Agent::where('id', $request->input('agent_id'))->update(['user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'data'    => $this->withAvatarUrl($user->load('agent')),
        ], 201);
    }

    public function show(User $user)
    {
        $user->load([
            'agent.affectations' => fn ($q) => $q->with('equipement')->latest(),
        ]);

        $lastToken = $user->tokens()->latest('last_used_at')->first();

        return response()->json([
            'message' => 'Utilisateur récupéré avec succès',
            'data'    => [
                ...$this->withAvatarUrl($user),
                'last_login_at' => $lastToken?->last_used_at,
                'tokens_count'  => $user->tokens()->count(),
            ],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'email'    => "sometimes|required|string|email|max:255|unique:users,email,{$user->id}",
            'role'     => 'sometimes|required|string|in:admin,gestionnaire,agent',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        return response()->json([
            'message' => 'Utilisateur modifié avec succès',
            'data'    => $this->withAvatarUrl($user),
        ]);
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas désactiver votre propre compte.',
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        if ($user->agent) {
            $user->agent->update(['statut' => $user->is_active ? 'actif' : 'inactif']);
        }

        return response()->json([
            'message' => $user->is_active ? 'Compte activé' : 'Compte désactivé',
            'data'    => $this->withAvatarUrl($user),
        ]);
    }

    /**
     * Mise à jour du profil connecté.
     * - Met à jour users.name, users.email, users.avatar
     * - Si l'user est un agent → synchronise agents.nom, agents.prenom, agents.email, agents.photo
     *   pour que les vues admin/gestionnaire voient le changement en temps réel
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
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('users/avatars', 'public');
        }

        $user->update($data);

        // ── Synchroniser le profil agent si l'utilisateur est un agent ──────
        if ($user->agent) {
            $agentData = [];

            // Décomposer le name en prenom + nom (premier mot = prénom, reste = nom)
            if ($request->filled('name')) {
                $parts = explode(' ', trim($request->input('name')), 2);
                $agentData['prenom'] = $parts[0];
                $agentData['nom']    = $parts[1] ?? $parts[0];
            }

            if ($request->filled('email')) {
                $agentData['email'] = $request->input('email');
            }

            // Synchroniser la photo de l'agent avec l'avatar utilisateur
            if (isset($data['avatar'])) {
                // Supprimer l'ancienne photo agent si différente
                if ($user->agent->photo && $user->agent->photo !== $data['avatar']) {
                    Storage::disk('public')->delete($user->agent->photo);
                }
                $agentData['photo'] = $data['avatar'];
            }

            if (!empty($agentData)) {
                $user->agent->update($agentData);
            }
        }

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'data'    => $this->withAvatarUrl($user->fresh('agent')),
        ]);
    }

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

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'errors' => ['current_password' => ['Le mot de passe actuel est incorrect.']],
            ], 422);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        return response()->json(['message' => 'Mot de passe mis à jour avec succès']);
    }

    /**
     * Ajoute avatar_url (URL publique absolue) à un utilisateur.
     * Stocke le chemin relatif en base, expose l'URL complète à l'API.
     */
    private function withAvatarUrl(User $user): array
    {
        $arr = $user->toArray();
        $arr['avatar_url'] = $user->avatar_url;
        return $arr;
    }
}
