<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::all();
        return response()->json([
            'status' => 'success',
            'data' => $agents
        ]);
    }
}
