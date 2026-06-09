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
     * Liste tous les agents.
     */
    public function index()
    {
        $agents = Agent::with(['user', 'affectations.equipement'])->get();

        return response()->json([
            'agents' => $agents->map(fn ($a) => $this->withPhotoUrl($a)),
            'total'  => $agents->count(),
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

        // Upload photo
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

        return response()->json(['message' => 'Agent désactivé', 'agent' => $agent]);
    }

    public function reactiver(Agent $agent)
    {
        $agent->update(['statut' => 'actif']);

        if ($agent->user) {
            $agent->user->update(['is_active' => true]);
        }

        return response()->json(['message' => 'Agent réactivé', 'agent' => $agent]);
    }
}
