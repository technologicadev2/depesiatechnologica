<?php

namespace App\Http\Controllers;

use App\Models\OrdreMission;
use Illuminate\Http\Request;

class OrdermissionsuperController extends Controller
{
    
public function index()
{
    $ordermissionsuper = OrdreMission::with('gerantRelation')
                        ->latest('created_at')
                        ->get();

    return view('ordermission.ordermissionsuper', compact('ordermissionsuper'));
}


public function store(Request $request)
{
    $request->validate([
        'salaries'      => 'required|array|min:1',
        'gerant'        => 'required|exists:salaries,id',
        'date_depart'   => 'required|date',
        'date_retour' => 'nullable|date',
        'moyen_transport' => 'required|in:transport_public,voiture_mission,voiture_personnelle',
        'emplacement'   => 'nullable|string',
        'mission'       => 'nullable|string',
        // Champs conditionnels
        'marque_mission'     => 'required_if:moyen_transport,voiture_mission|string|nullable',
        'nplaque_mission'    => 'required_if:moyen_transport,voiture_mission|string|nullable',
        'marque_personnelle' => 'required_if:moyen_transport,voiture_personnelle|string|nullable',
        'nplaque_p'          => 'required_if:moyen_transport,voiture_personnelle|string|nullable',
        'puissance_fiscale_p'=> 'required_if:moyen_transport,voiture_personnelle|integer|nullable',
    ]);

    $data = [
        'code'          => 'OM-' . date('Ymd') . '-' . str_pad(OrdreMission::count() + 1, 3, '0', STR_PAD_LEFT),
        'salaries'      => json_encode($request->salaries),
        'gerant'        => $request->gerant,
        'emplacement'   => $request->emplacement,
        'mission'       => $request->mission,
        'date_depart'   => $request->date_depart,
        'date_retour' => $request->date_retour,
    ];

    // Gestion selon le moyen de transport
    if ($request->moyen_transport === 'transport_public') {
        $data['transport_public'] = 1;
    } 
    elseif ($request->moyen_transport === 'voiture_mission') {
        $data['voiture_mission'] = 1;
        $data['marque_mission'] = $request->marque_mission;
        $data['nplaque_mission'] = $request->nplaque_mission;
    } 
    elseif ($request->moyen_transport === 'voiture_personnelle') {
        $data['voiture_personnelle'] = 1;
        $data['marque_personnelle'] = $request->marque_personnelle;
        $data['nplaque_p'] = $request->nplaque_p;
        $data['puissance_fiscale_p'] = $request->puissance_fiscale_p;
    }

    OrdreMission::create($data);

    return response()->json(['success' => true, 'message' => 'Ordre de mission créé avec succès !']);
}
public function destroy($id)
{
    try {
        $ordre = OrdreMission::findOrFail($id);
        $ordre->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ordre de mission supprimé avec succès !'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression.'
        ], 500);
    }
}



// Ajouter cette méthode
public function update(Request $request, $id)
{
    $request->validate([
        'salaries'      => 'required|array|min:1',
        'gerant'        => 'required|exists:salaries,id',
        'date_depart'   => 'required|date',
        'date_retour' => 'nullable|date',
        'moyen_transport' => 'required|in:transport_public,voiture_mission,voiture_personnelle',
        'emplacement'   => 'nullable|string',
        'mission'       => 'nullable|string',
        'marque_mission'     => 'required_if:moyen_transport,voiture_mission|string|nullable',
        'nplaque_mission'    => 'required_if:moyen_transport,voiture_mission|string|nullable',
        'marque_personnelle' => 'required_if:moyen_transport,voiture_personnelle|string|nullable',
        'nplaque_p'          => 'required_if:moyen_transport,voiture_personnelle|string|nullable',
        'puissance_fiscale_p'=> 'required_if:moyen_transport,voiture_personnelle|integer|nullable',

        'heure_depart' => 'nullable|date_format:H:i',
            'heure_retour' => 'nullable|date_format:H:i',
            'frais'        => 'nullable|numeric|min:0',
        
    ]);

    $ordre = OrdreMission::findOrFail($id);

    $data = [
        'gerant'        => $request->gerant,
        'salaries'      => json_encode($request->salaries),
        'emplacement'   => $request->emplacement,
        'mission'       => $request->mission,
        'date_depart'   => $request->date_depart,

        'heure_depart' => $request->heure_depart,
        'date_retour' => $request->date_retour,
        'heure_retour' => $request->heure_retour,
        'frais'        => $request->frais,
    ];

    // Réinitialiser les champs de transport
    $ordre->update([
        'transport_public' => 0,
        'voiture_mission'  => 0,
        'voiture_personnelle' => 0,
    ]);

    if ($request->moyen_transport === 'transport_public') {
        $data['transport_public'] = 1;
    } elseif ($request->moyen_transport === 'voiture_mission') {
        $data['voiture_mission'] = 1;
        $data['marque_mission'] = $request->marque_mission;
        $data['nplaque_mission'] = $request->nplaque_mission;
    } elseif ($request->moyen_transport === 'voiture_personnelle') {
        $data['voiture_personnelle'] = 1;
        $data['marque_personnelle'] = $request->marque_personnelle;
        $data['nplaque_p'] = $request->nplaque_p;
        $data['puissance_fiscale_p'] = $request->puissance_fiscale_p;
    }

    $ordre->update($data);

    return response()->json(['success' => true, 'message' => 'Ordre de mission modifié avec succès !']);
}



public function validateOrdre($id, Request $request)
{
    $request->validate([
        'ordre' => 'required|boolean'
    ]);

    $ordreMission = OrdreMission::findOrFail($id);
    $ordreMission->update([
        'ordre' => $request->ordre
    ]);

    return response()->json([
        'success' => true,
        'message' => $request->ordre == 1 ? 'Ordre validé avec succès' : 'Validation retirée'
    ]);
}

public function showFiche($id)
{
    $ordre = OrdreMission::with('gerantRelation')->findOrFail($id);
    $companySettings = \App\Models\CompanySettings::first();
    
    $html = view('ordermission.fiche', compact('ordre', 'companySettings'))->render();
    
    return response()->json([
        'success' => true,
        'html' => $html
    ]);
}

public function edit($id)
{
    try {
        $ordre = OrdreMission::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $ordre
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Ordre de mission non trouvé'
        ], 404);
    }
}



}