<?php

namespace App\Http\Controllers;

use App\Models\Fonction;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SalarieFonctionController extends Controller
{
    public function index()
    {
        return view('salaries.fonction');
    }

    public function getFonctions()
    {
        try {
            $fonctions = Fonction::select('id', 'designation')->get();
            return response()->json($fonctions);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur serveur lors du chargement des fonctions.'], 500);
        }
    }

    public function show($id)
    {
        try {
            $fonction = Fonction::findOrFail($id);
            return response()->json($fonction);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Fonction non trouvée.'], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'designation' => 'required|string|max:255|unique:fonctions,designation',
            ], [
                'designation.unique' => 'Une fonction avec cette désignation existe déjà.',
            ]);

            $fonction = Fonction::create($request->all());
            return response()->json(['success' => true, 'data' => $fonction]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()['designation'][0] ?? 'Erreur de validation.'], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur serveur lors de la création de la fonction.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'designation' => 'required|string|max:255|unique:fonctions,designation,' . $id,
            ], [
                'designation.unique' => 'Une fonction avec cette désignation existe déjà.',
            ]);

            $fonction = Fonction::findOrFail($id);
            $fonction->update($request->all());
            return response()->json(['success' => true, 'data' => $fonction]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()['designation'][0] ?? 'Erreur de validation.'], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur serveur lors de la mise à jour de la fonction.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $fonction = Fonction::findOrFail($id);
            if ($fonction->salaries()->exists()) {
                return response()->json(['error' => 'Vous ne pouvez pas supprimer cette fonction car elle est associée à un ou plusieurs salariés.'], 400);
            }
            $fonction->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur serveur lors de la suppression de la fonction.'], 500);
        }
    }
}