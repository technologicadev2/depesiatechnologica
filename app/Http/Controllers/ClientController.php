<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::all();
        return view('client.index', compact('clients'));
    }

    public function store(Request $request)
    {
        try {
            $rules = [
                'nom_complet'  => 'required|string|max:255',
                'ice'          => 'nullable|string|max:255|unique:clients,ice',
                'telephone'    => 'nullable|string|max:50',
                'email'        => 'nullable|email|max:255',
                'adresse'      => 'nullable|string|max:500',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json(['errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $client = Client::create([
                'nom_complet'  => $request->nom_complet,
                'ice'          => $request->ice,
                'telephone'    => $request->telephone,
                'email'        => $request->email,
                'adresse'      => $request->adresse,
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => 'Client ajouté avec succès !', 'client' => $client]);
            }

            return redirect()->route('clients.index')->with('success', 'Client ajouté avec succès !');
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
            $client = Client::findOrFail($id);
            return response()->json(['client' => $client]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors du chargement des données'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $client = Client::findOrFail($id);

            $rules = [
                'nom_complet'  => 'required|string|max:255',
                'ice'          => 'nullable|string|max:255|unique:clients,ice,' . $id,
                'telephone'    => 'nullable|string|max:50',
                'email'        => 'nullable|email|max:255',
                'adresse'      => 'nullable|string|max:500',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json(['errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $client->update([
                'nom_complet'  => $request->nom_complet,
                'ice'          => $request->ice,
                'telephone'    => $request->telephone,
                'email'        => $request->email,
                'adresse'      => $request->adresse,
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => 'Client mis à jour avec succès !']);
            }

            return redirect()->route('clients.index')->with('success', 'Client mis à jour avec succès !');
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
        \Log::info("Tentative de suppression du client ID: " . $id);

        $client = Client::findOrFail($id);
        \Log::info("Client trouvé : " . $client->nom_complet);

     $existsInFacturesVente = DB::table('factures_vente')
                ->where('client_id', $client->id)
                ->orWhere('nom_client', $client->nom_complet) // ← au cas où tu stockes encore le nom
                ->exists();

            if ($existsInFacturesVente) {
                $msg = 'Ce client ne peut pas être supprimé car il est référencé dans une ou plusieurs factures de vente.';
                if ($request->ajax()) {
                    return response()->json(['error' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

        $client->delete();
        \Log::info("Client supprimé avec succès ID: " . $id);

        if ($request->ajax()) {
            return response()->json(['message' => 'Client supprimé (test)']);
        }
        return redirect()->route('clients.index')->with('success', 'Client supprimé (test)');
    } catch (\Exception $e) {
        \Log::error("ERREUR SUPPRESSION CLIENT {$id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        if ($request->ajax()) {
            return response()->json(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
        return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
    }
}
}