<?php

namespace App\Http\Controllers;

use App\Models\NatureDepense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NatureDepenseController extends Controller
{
    public function index()
    {
        $natureDepenses = NatureDepense::select(['id', 'designation'])
            ->whereNotIn('designation', ['Avancements de salaires', 'Dépenses de véhicule'])
            ->orderBy('created_at', 'desc')
            ->get();
        return view('nature_depenses.nature_depense', compact('natureDepenses'));
    }

    public function store(Request $request)
    {
        try {
            $designation = trim($request->input('designation'));

            // Prevent adding protected designations
            $protected = ['Avancements de salaires', 'Dépenses de véhicule'];
            if (in_array(strtolower($designation), array_map('strtolower', $protected))) {
                return $request->ajax()
                    ? response()->json(['error' => 'La désignation "' . $designation . '" est réservée.'], 422)
                    : redirect()->back()->with('error', 'La désignation "' . $designation . '" est réservée.')->withInput();
            }

            $validator = Validator::make(['designation' => $designation], [
                'designation' => 'required|string|max:255|unique:nature_depences,designation,NULL,id,deleted_at,NULL',
            ]);

            if ($validator->fails()) {
                return $request->ajax()
                    ? response()->json(['errors' => $validator->errors()], 422)
                    : redirect()->back()->withErrors($validator)->withInput();
            }

            $natureDepense = NatureDepense::create([
                'designation' => $designation,
            ]);

            return $request->ajax()
                ? response()->json([
                    'success' => true,
                    'message' => 'Nature de dépense ajoutée avec succès !',
                    'natureDepense' => $natureDepense,
                ])
                : redirect()->route('nature_depenses.index')->with('success', 'Nature de dépense ajoutée avec succès !');
        } catch (\Illuminate\Database\QueryException $e) {
            $errorMessage = $e->getCode() == 23000
                ? 'Une nature de dépense avec cette désignation existe déjà.'
                : 'Erreur de base de données : ' . $e->getMessage();
            return $request->ajax()
                ? response()->json(['error' => $errorMessage], 500)
                : redirect()->back()->with('error', $errorMessage)->withInput();
        } catch (\Exception $e) {
            return $request->ajax()
                ? response()->json(['error' => 'Erreur inattendue : ' . $e->getMessage()], 500)
                : redirect()->back()->with('error', 'Erreur inattendue : ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $natureDepense = NatureDepense::findOrFail($id);

            // Prevent updating protected designations
            if (in_array($natureDepense->designation, ['Avancements de salaires', 'Dépenses de véhicule'])) {
                return $request->ajax()
                    ? response()->json(['error' => 'La nature de dépense "' . $natureDepense->designation . '" ne peut pas être modifiée.'], 422)
                    : redirect()->back()->with('error', 'La nature de dépense "' . $natureDepense->designation . '" ne peut pas être modifiée.');
            }

            $designation = trim($request->input('designation'));

            $validator = Validator::make(['designation' => $designation], [
                'designation' => 'required|string|max:255|unique:nature_depences,designation,' . $id . ',id,deleted_at,NULL',
            ]);

            if ($validator->fails()) {
                return $request->ajax()
                    ? response()->json(['errors' => $validator->errors()], 422)
                    : redirect()->back()->withErrors($validator)->withInput();
            }

            $natureDepense->update([
                'designation' => $designation,
            ]);

            return $request->ajax()
                ? response()->json([
                    'success' => true,
                    'message' => 'Nature de dépense modifiée avec succès !',
                    'natureDepense' => $natureDepense,
                ])
                : redirect()->route('nature_depenses.index')->with('success', 'Nature de dépense modifiée avec succès !');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $request->ajax()
                ? response()->json(['error' => 'Nature de dépense introuvable.'], 404)
                : redirect()->back()->with('error', 'Nature de dépense introuvable.');
        } catch (\Illuminate\Database\QueryException $e) {
            $errorMessage = $e->getCode() == 23000
                ? 'Une nature de dépense avec cette désignation existe déjà.'
                : 'Erreur de base de données : ' . $e->getMessage();
            return $request->ajax()
                ? response()->json(['error' => $errorMessage], 500)
                : redirect()->back()->with('error', $errorMessage)->withInput();
        } catch (\Exception $e) {
            return $request->ajax()
                ? response()->json(['error' => 'Erreur inattendue : ' . $e->getMessage()], 500)
                : redirect()->back()->with('error', 'Erreur inattendue : ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $natureDepense = NatureDepense::findOrFail($id);

            // Prevent deleting protected designations
            if (in_array($natureDepense->designation, ['Avancements de salaires', 'Dépenses de véhicule'])) {
                return request()->ajax()
                    ? response()->json(['error' => 'La nature de dépense "' . $natureDepense->designation . '" ne peut pas être supprimée.'], 422)
                    : redirect()->route('nature_depenses.index')->with('error', 'La nature de dépense "' . $natureDepense->designation . '" ne peut pas être supprimée.');
            }

            $natureDepense->delete();

            return request()->ajax()
                ? response()->json([
                    'success' => true,
                    'message' => 'Nature de dépense supprimée avec succès !',
                ])
                : redirect()->route('nature_depenses.index')->with('success', 'Nature de dépense supprimée avec succès !');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return request()->ajax()
                ? response()->json(['error' => 'Nature de dépense introuvable.'], 404)
                : redirect()->route('nature_depenses.index')->with('error', 'Nature de dépense introuvable.');
        } catch (\Illuminate\Database\QueryException $e) {
            return request()->ajax()
                ? response()->json(['error' => 'Impossible de supprimer : cette nature de dépense est utilisée ailleurs.'], 422)
                : redirect()->route('nature_depenses.index')->with('error', 'Impossible de supprimer : cette nature de dépense est utilisée ailleurs.');
        } catch (\Exception $e) {
            return request()->ajax()
                ? response()->json(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()], 500)
                : redirect()->route('nature_depenses.index')->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
}