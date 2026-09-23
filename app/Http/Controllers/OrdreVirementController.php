<?php

namespace App\Http\Controllers;

use App\Models\CompanySettings;
use App\Models\OrdreVirement;
use App\Models\Entite;
use App\Models\FactureAchat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class OrdreVirementController extends Controller
{
 public function index()
{
    $ordres = OrdreVirement::orderBy('reference', 'desc')->get();
    $entites = Entite::all();
    return view('ordres-virement.index', compact('ordres', 'entites'));
}

  public function getEntiteRibs($id)
{
    try {
        $entite = Entite::findOrFail($id);
        $ribs = [];

        if (!empty($entite->rib)) {
            $ribs[] = ['value' => $entite->rib, 'label' => 'RIB Principal - ' . $entite->rib];
        }
        if (!empty($entite->rib1)) {
            $ribs[] = ['value' => $entite->rib1, 'label' => 'RIB 1 - ' . $entite->rib1];
        }
        if (!empty($entite->rib2)) {
            $ribs[] = ['value' => $entite->rib2, 'label' => 'RIB 2 - ' . $entite->rib2];
        }

        return response()->json(['ribs' => $ribs]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erreur lors de la récupération des RIBs'], 500);
    }
}
    public function store(Request $request)
    {

        try {
            $rules = [
                'type_destinataire' => 'required|in:societe',
                'destinataire_id' => 'required|integer|exists:entites,id',
                'reference' => 'nullable|string|max:255',
                'montant' => 'required|numeric|min:0.01',
                'date_virement' => 'required|date',
                'motif' => 'nullable|string',
                'rib_virement' => 'nullable|string',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {

                if ($request->ajax()) {
                    return response()->json(['errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $year = date('y', strtotime($request->date_virement));
            $baseNumero = OrdreVirement::whereYear('date_virement', date('Y', strtotime($request->date_virement)))->count() + 1;
            $numero = $baseNumero;
            $ref = 'F-' . str_pad($numero, 2, '0', STR_PAD_LEFT) . '/' . $year;
            while (OrdreVirement::where('ref', $ref)->exists()) {
                $numero++;
                $ref = 'F-' . str_pad($numero, 2, '0', STR_PAD_LEFT) . '/' . $year;
            }

            // CORRECTION: Utiliser la référence utilisateur si fournie, sinon utiliser ref généré
            $reference = !empty($request->reference) ? $request->reference : $ref;

            $ordre = OrdreVirement::create([
                'ref' => $ref, // Toujours généré automatiquement
                'type_destinataire' => $request->type_destinataire,
                'destinataire_id' => $request->destinataire_id,
                'reference' => $reference, // Référence utilisateur ou ref si vide
                'montant' => $request->montant,
                'date_virement' => $request->date_virement,
                'motif' => $request->motif,
                    'rib_virement' => $request->rib_virement, 
            ]);

       /*      // Fetch entite details
            $entite = Entite::findOrFail($request->destinataire_id);
            $montantTtc = $request->montant;
            $tauxTva = 20.0;
            $montantHt = $montantTtc / (1 + ($tauxTva / 100));
            $montantTva = $montantTtc - $montantHt;

            // CORRECTION: Utiliser la référence choisie (utilisateur ou générée)
            FactureAchat::create([
                'numero_facture' => $reference, // Utiliser reference au lieu de ref
                'date_facture' => $request->date_virement,
                'raison_sociale' => $entite->raison_sociale,
                'ice' => $entite->ice ?? null,
                'montant_ht' => number_format($montantHt, 2, '.', ''),
                'taux_tva' => $tauxTva,
                'montant_tva' => number_format($montantTva, 2, '.', ''),
                'montant_ttc' => number_format($montantTtc, 2, '.', ''),
                'file_path' => null,
            ]);
 */

            if ($request->ajax()) {
                return response()->json(['message' => 'Ordre de virement ajouté avec succès !', 'ordre' => $ordre]);
            }

            return redirect()->route('manage-ordres-virement.index')->with('success', 'Ordre de virement ajouté avec succès !');
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
            $ordre = OrdreVirement::findOrFail($id);
            return response()->json(['ordre' => $ordre]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors du chargement des données'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $ordre = OrdreVirement::findOrFail($id);

            $rules = [
                'type_destinataire' => 'required|in:societe',
                'destinataire_id' => 'required|integer|exists:entites,id',
                'reference' => 'nullable|string|max:255',
                'montant' => 'required|numeric|min:0',
                'date_virement' => 'required|date',
                'motif' => 'nullable|string',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json(['errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $year = date('y', strtotime($request->date_virement));
            if ($ordre->date_virement != $request->date_virement) {
                $last = OrdreVirement::whereYear('date_virement', date('Y', strtotime($request->date_virement)))->count();
                $numero = $last + 1;
                $ref = 'F-' . str_pad($numero, 2, '0', STR_PAD_LEFT) . '/' . $year;
                $ordre->ref = $ref;
            }

            // CORRECTION: Utiliser la référence utilisateur si fournie, sinon garder l'ancienne ou utiliser ref
            $reference = !empty($request->reference) ? $request->reference : $ordre->ref;

            $ordre->update([
                'type_destinataire' => $request->type_destinataire,
                'destinataire_id' => $request->destinataire_id,
                'reference' => $reference,
                'montant' => $request->montant,
                'date_virement' => $request->date_virement,
                'motif' => $request->motif,
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => 'Ordre de virement mis à jour avec succès !']);
            }

            return redirect()->route('manage-ordres-virement.index')->with('success', 'Ordre de virement mis à jour avec succès !');
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
            $ordre = OrdreVirement::findOrFail($id);
            $ordre->delete();

            if ($request->ajax()) {
                return response()->json(['message' => 'Ordre de virement supprimé avec succès !']);
            }
            return redirect()->route('manage-ordres-virement.index')->with('success', 'Ordre de virement supprimé avec succès !');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }


  

public function download(Request $request, $id)
{
    $ordre = OrdreVirement::findOrFail($id);
    $company = CompanySettings::first();
    if ($company && $company->logo) {
        $company->logo = basename($company->logo);
    }

    $typeVirement = $request->query('type_virement', 'normal');
    $isInstantane = $typeVirement === 'instantane';

    // Utiliser le RIB sélectionné lors de la création/modification de l'ordre,
    // avec un fallback sur le RIB principal de l'entité si aucun n'a été choisi
    $ribSource = $ordre->rib_virement ?: ($ordre->destinataire->rib ?? '');

    // Nettoyer le RIB (garder uniquement les chiffres)
    $ribClean = preg_replace('/\D/', '', $ribSource);

    // Découper le RIB en groupes (format RIB marocain: Banque(3) + Ville(3) + Compte(16) + Clé(2) = 24 chiffres)
    $ribDestFormatted = [
        'bank'    => str_split(substr($ribClean, 0, 3)),
        'ville'   => str_split(substr($ribClean, 3, 3)),
        'compte'  => str_split(substr($ribClean, 6, 16)),
        'cle'     => str_split(substr($ribClean, 22, 2)),
    ];

    $nomFichier = $ordre->reference ?? $ordre->ref ?? 'virement';
    $nomFichier = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $nomFichier);
    $nomFichier = trim($nomFichier, '-');

    $pdf = PDF::loadView('ordres-virement.pdf', compact('ordre', 'company', 'isInstantane', 'ribDestFormatted'));
    return $pdf->download('Virement - ' . $nomFichier . '.pdf');
}


    public function generateReference(Request $request)
    {
        try {
            $request->validate([
                'date_virement' => 'required|date',
            ]);

            $year = date('y', strtotime($request->date_virement));
            $last = OrdreVirement::whereYear('date_virement', date('Y', strtotime($request->date_virement)))->count();
            $numero = $last + 1;
            $reference = 'F-' . str_pad($numero, 2, '0', STR_PAD_LEFT) . '/' . $year; // Match ref format

            return response()->json(['reference' => $reference]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la génération de la référence : ' . $e->getMessage()], 500);
        }
    }
}
