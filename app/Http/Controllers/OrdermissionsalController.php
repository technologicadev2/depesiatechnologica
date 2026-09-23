<?php

namespace App\Http\Controllers;

use App\Models\OrdreMission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrdermissionsalController extends Controller
{
   public function index()
{
    $user = Auth::user();
    $id_salarie = $user->id_salarie ?? $user->id;

    $ordermissionsal = OrdreMission::with('gerantRelation')
        ->where('ordre', 1)                    // ← Ajout ici
        ->where(function ($query) use ($id_salarie) {
            $query->where('gerant', $id_salarie)
                  ->orWhereRaw("JSON_CONTAINS(salaries, JSON_QUOTE(?))", [$id_salarie]);
        })
        ->latest('created_at')
        ->get();

    return view('ordermission.ordermissionsal', compact('ordermissionsal'));
}
    public function showFiche($id)
{
    $user = Auth::user();
    $id_salarie = $user->id_salarie ?? $user->id;

    $ordre = OrdreMission::with('gerantRelation')
        ->where(function ($query) use ($id_salarie) {
            $query->where('gerant', $id_salarie)
                  ->orWhereRaw("JSON_CONTAINS(salaries, JSON_QUOTE(?))", [$id_salarie]);
        })
        ->findOrFail($id);

    $companySettings = \App\Models\CompanySettings::first(); // ← ajout

    $html = view('ordermission.fiche', compact('ordre', 'companySettings'))->render(); // ← ajout

    return response()->json([
        'success' => true,
        'html' => $html
    ]);
}


    public function updateMission(Request $request, $id)
{
    $user = Auth::user();
    $id_salarie = $user->id_salarie ?? $user->id;

    $ordre = OrdreMission::where('ordre', 1)
        ->where(function ($query) use ($id_salarie) {
            $query->where('gerant', $id_salarie)
                  ->orWhereRaw("JSON_CONTAINS(salaries, JSON_QUOTE(?))", [$id_salarie]);
        })
        ->findOrFail($id);

    $ordre->update([
        'heure_depart' => $request->heure_depart,
        'heure_retour'  => $request->heure_retour,
        'frais'         => $request->frais,
        'date_retour'  => $request->date_retour,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Modifications enregistrées avec succès'
    ]);
}

public function getForEdit($id)
{
    $user = Auth::user();
    $id_salarie = $user->id_salarie ?? $user->id;

    $ordre = OrdreMission::where('ordre', 1)
        ->where(function ($query) use ($id_salarie) {
            $query->where('gerant', $id_salarie)
                  ->orWhereRaw("JSON_CONTAINS(salaries, JSON_QUOTE(?))", [$id_salarie]);
        })
        ->findOrFail($id);

    return response()->json([
        'success' => true,
        'data' => $ordre
    ]);
}
}