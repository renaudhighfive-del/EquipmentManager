<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use Illuminate\Http\Request;

class EquipementController extends Controller
{
    public function index()
    {
        $equipements = Equipement::with('categorie')->get();
        return response()->json([
            'status' => 'success',
            'data' => $equipements
        ]);
    }
}
