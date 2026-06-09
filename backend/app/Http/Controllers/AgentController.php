<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $query = Agent::with(['user', 'affectations']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $agents = $query->get();

        return response()->json([
            'agents' => $agents,
            'total'  => $agents->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matricule' => 'required|string|max:50|unique:agents',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|string|email|max:255',
            'direction' => 'nullable|string|max:150',
            'service' => 'nullable|string|max:150',
            'poste' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $agent = Agent::create([
            'matricule' => $request->matricule,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'direction' => $request->direction,
            'service' => $request->service,
            'poste' => $request->poste,
            'statut' => 'actif',
        ]);

        return response()->json([
            'message' => 'Agent créé avec succès',
            'agent' => $agent->load(['user', 'affectations'])
        ], 201);
    }

    public function show(Agent $agent)
    {
        return response()->json([
            'agent' => $agent->load(['user', 'affectations'])
        ]);
    }

    public function update(Request $request, Agent $agent)
    {
        $validator = Validator::make($request->all(), [
            'matricule' => 'sometimes|required|string|max:50|unique:agents,matricule,' . $agent->id,
            'nom' => 'sometimes|required|string|max:100',
            'prenom' => 'sometimes|required|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|string|email|max:255',
            'direction' => 'nullable|string|max:150',
            'service' => 'nullable|string|max:150',
            'poste' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $agent->update($request->only([
            'matricule', 'nom', 'prenom', 'telephone', 'email', 'direction', 'service', 'poste'
        ]));

        return response()->json([
            'message' => 'Agent mis à jour',
            'agent' => $agent->load(['user', 'affectations'])
        ]);
    }

    public function desactiver(Agent $agent)
    {
        $agent->statut = 'inactif';
        $agent->save();

        return response()->json([
            'message' => 'Agent désactivé',
            'agent' => $agent
        ]);
    }

    public function reactiver(Agent $agent)
    {
        $agent->statut = 'actif';
        $agent->save();

        return response()->json([
            'message' => 'Agent réactivé',
            'agent' => $agent
        ]);
    }
}
