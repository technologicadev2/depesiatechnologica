<?php

namespace App\Http\Controllers;

use App\Models\Entite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class EntityController extends Controller
{
    public function index()
    {
        $entities = Entite::all();
        return view('entities.index', compact('entities'));
    }

  public function store(Request $request)
{
    try {
        $rules = [
            'raison_sociale' => 'required|string|max:255',
            'ice' => 'required|string|max:255|unique:entites,ice',
            'numero' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'rib' => 'nullable|string|max:255', 
            'rib1' => 'nullable|string|max:255', // nouveau
            'rib2' => 'nullable|string|max:255', 
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $entity = Entite::create([
            'raison_sociale' => $request->raison_sociale,
            'ice' => $request->ice,
            'numero' => $request->numero,
            'email' => $request->email,
            'rib' => $request->rib, 
           'rib1' => $request->rib1, 
    'rib2' => $request->rib2,
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Entité ajoutée avec succès !', 'entity' => $entity]);
        }

        return redirect()->route('manage-entities.index')->with('success', 'Entité ajoutée avec succès !');
    } catch (\Exception $e) {
        if ($request->ajax()) {
            return response()->json(['error' => 'Erreur lors de l\'ajout : ' . $e->getMessage()], 500);
        }
        return redirect()->back()->with('error', 'Erreur lors de l\'ajout : ' . $e->getMessage())->withInput();
    }
}

    public function edit($id)
    {
        try {
            $entity = Entite::findOrFail($id);
            return response()->json(['entity' => $entity]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors du chargement des données'], 500);
        }
    }

 public function update(Request $request, $id)
{
    try {
        $entity = Entite::findOrFail($id);

        $rules = [
            'raison_sociale' => 'required|string|max:255',
            'ice' => 'required|string|max:255|unique:entites,ice,' . $id,
            'numero' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'rib' => 'nullable|string|max:255', 
            'rib1' => 'nullable|string|max:255', 
            'rib2' => 'nullable|string|max:255', 
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $entity->update([
            'raison_sociale' => $request->raison_sociale,
            'ice' => $request->ice,
            'numero' => $request->numero,
            'email' => $request->email,
            'rib' => $request->rib, 
            'rib1' => $request->rib1, // nouveau
    'rib2' => $request->rib2,
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Entité mise à jour avec succès !']);
        }

        return redirect()->route('manage-entities.index')->with('success', 'Entité mise à jour avec succès !');
    } catch (\Exception $e) {
        if ($request->ajax()) {
            return response()->json(['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()], 500);
        }
        return redirect()->back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage())->withInput();
    }
}

    public function destroy(Request $request, $id)
    {
        try {
            $entity = Entite::findOrFail($id);

            // Check if raison_sociale exists in factures_achat or factures_vente
            $existsInFacturesAchat = DB::table('factures_achat')
                ->where('raison_sociale', $entity->raison_sociale)
                ->exists();

            $existsInFacturesVente = DB::table('factures_vente')
                ->where('raison_sociale', $entity->raison_sociale)
                ->exists();

            if ($existsInFacturesAchat || $existsInFacturesVente) {
                $errorMessage = 'Cette entité ne peut pas être supprimée car elle est référencée dans ';
                if ($existsInFacturesAchat && $existsInFacturesVente) {
                    $errorMessage .= 'les factures d\'achat et de vente.';
                } elseif ($existsInFacturesAchat) {
                    $errorMessage .= 'les factures d\'achat.';
                } else {
                    $errorMessage .= 'les factures de vente.';
                }
                if ($request->ajax()) {
                    return response()->json(['error' => $errorMessage], 422);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            $entity->delete();

            if ($request->ajax()) {
                return response()->json(['message' => 'Entité supprimée avec succès !']);
            }
            return redirect()->route('manage-entities.index')->with('success', 'Entité supprimée avec succès !');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'entité ID ' . $id . ': ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }


}
