<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    /**
     * Ajoute photo_url à un agent.
     */
    private function withPhotoUrl(Agent $agent): array
    {
        $arr = $agent->toArray();
        $arr['photo_url'] = $agent->photo
            ? Storage::url($agent->photo)
            : null;
        return $arr;
    }

    /**
     * Liste les agents avec pagination et recherche optionnelle.
     */
    public function index(Request $request)
    {
        $query = Agent::with(['user', 'affectations.equipement']);

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $agents = $query->paginate(max(1, min($perPage, 100)));

        return response()->json([
            'agents' => $agents->getCollection()->map(fn ($agent) => $this->withPhotoUrl($agent)),
            'total' => $agents->total(),
            'current_page' => $agents->currentPage(),
            'last_page' => $agents->lastPage(),
            'per_page' => $agents->perPage(),
            'has_more_pages' => $agents->hasMorePages(),
        ]);
    }

    /**
     * Création d'un agent.
     * Le matricule est généré automatiquement côté backend.
     * L'image est uploadée si fournie.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom'       => 'required|string|max:100',
            'prenom'    => 'required|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'email'     => 'nullable|string|email|max:255',
            'direction' => 'nullable|string|max:150',
            'service'   => 'nullable|string|max:150',
            'poste'     => 'nullable|string|max:150',
            'photo'     => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Génération automatique du matricule : MAT-YYYY-XXXXX
        $year      = now()->format('Y');
        $lastAgent = Agent::whereYear('created_at', $year)->latest()->first();
        $sequence  = $lastAgent
            ? (int) substr($lastAgent->matricule, -5) + 1
            : 1;
        $matricule = 'MAT-' . $year . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        // Uploader les photos
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('agents/photos', 'public');
        }

        $agent = Agent::create([
            'matricule'  => $matricule,
            'nom'        => $request->input('nom'),
            'prenom'     => $request->input('prenom'),
            'telephone'  => $request->input('telephone'),
            'email'      => $request->input('email'),
            'direction'  => $request->input('direction'),
            'service'    => $request->input('service'),
            'poste'      => $request->input('poste'),
            'statut'     => 'actif',
            'photo'      => $photoPath,
        ]);

        return response()->json([
            'message' => 'Agent créé avec succès',
            'agent'   => $this->withPhotoUrl($agent->load(['user', 'affectations'])),
        ], 201);
    }

    public function show(Agent $agent)
    {
        return response()->json([
            'agent' => $this->withPhotoUrl($agent->load(['user', 'affectations.equipement'])),
        ]);
    }

    /**
     * Mise à jour d'un agent (admin uniquement).
     * Supporte le remplacement de photo.
     * Si l'agent est lié à un utilisateur, synchronise users.name, users.email et users.avatar.
     */
    public function update(Request $request, Agent $agent)
    {
        $validator = Validator::make($request->all(), [
            'nom'       => 'sometimes|required|string|max:100',
            'prenom'    => 'sometimes|required|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'email'     => 'nullable|string|email|max:255',
            'direction' => 'nullable|string|max:150',
            'service'   => 'nullable|string|max:150',
            'poste'     => 'nullable|string|max:150',
            'statut'    => 'in:actif,inactif',
            'photo'     => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['nom', 'prenom', 'telephone', 'email', 'direction', 'service', 'poste', 'statut']);

        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo
            if ($agent->photo) {
                Storage::disk('public')->delete($agent->photo);
            }
            $data['photo'] = $request->file('photo')->store('agents/photos', 'public');
        }

        $agent->update($data);

        // ── Synchroniser le profil utilisateur lié si existant ───────────────
        if ($agent->user) {
            $userData = [];

            // Concaténer prénom + nom pour le name utilisateur
            if ($request->filled('prenom') || $request->filled('nom')) {
                $prenom = $request->filled('prenom') ? $request->input('prenom') : $agent->prenom;
                $nom    = $request->filled('nom') ? $request->input('nom') : $agent->nom;
                $userData['name'] = trim("{$prenom} {$nom}");
            }

            if ($request->filled('email')) {
                $userData['email'] = $request->input('email');
            }

            // Synchroniser l'avatar utilisateur avec la photo agent
            if (isset($data['photo'])) {
                // Supprimer l'ancien avatar utilisateur si différent
                if ($agent->user->avatar && $agent->user->avatar !== $data['photo']) {
                    Storage::disk('public')->delete($agent->user->avatar);
                }
                $userData['avatar'] = $data['photo'];
            }

            if (!empty($userData)) {
                $agent->user->update($userData);
            }
        }

        return response()->json([
            'message' => 'Agent mis à jour',
            'agent'   => $this->withPhotoUrl($agent->load(['user', 'affectations'])),
        ]);
    }

    public function desactiver(Agent $agent)
    {
        $agent->update(['statut' => 'inactif']);

        // Désactiver le compte user lié si existant
        if ($agent->user) {
            $agent->user->update(['is_active' => false]);
        }

        return response()->json(['message' => 'Agent désactivé', 'agent' => $this->withPhotoUrl($agent)]);
    }

    public function reactiver(Agent $agent)
    {
        $agent->update(['statut' => 'actif']);

        if ($agent->user) {
            $agent->user->update(['is_active' => true]);
        }

        return response()->json(['message' => 'Agent réactivé', 'agent' => $this->withPhotoUrl($agent)]);
    }
}
