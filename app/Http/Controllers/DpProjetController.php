<?php

namespace App\Http\Controllers;

use App\Models\CompanySettings;
use App\Models\Projet;
use App\Models\Decompte;
use App\Models\DossiersPdf;
use App\Models\OrdreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Dotenv\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DpProjetController extends Controller
{
    public function index()
    {
        $projets = Projet::where('type_projet', 'Public')->orderBy('created_at', 'desc')->get();
        $companySettings = CompanySettings::first();
        return view('depProjet.index', compact('projets', 'companySettings'));
    }
    public function show(Projet $projet)
    {
        return response()->json([
            'success' => true,
            'data' => $projet->load('decomptes', 'ordresService')
        ]);
    }

    public function listPublicProjects(Request $request)
    {
        try {
            $query = Projet::where('type_projet', 'Public')
                ->with(['decomptes', 'ordresService', 'dossiersPdf'])
                ->orderBy('created_at', 'desc');

            // Filter by commande_type if provided
            if ($request->has('commande_type')) {
                $query->where('commande_type', $request->commande_type);
            }

            $projets = $query->get()->map(function ($projet) {
                $totalRgDp = $projet->decomptes->sum('rg_dp');
                $rgRestant = ($projet->rg ?? 0) - $totalRgDp;
                $totalMontantDp = $projet->decomptes->sum('montant_dp');
                $total_decompte = $projet->total_decompte ?? ($projet->budget - ($projet->rg ?? 0));
                $montantDecompteRestant = $total_decompte - $totalMontantDp;

                $pourcentage = 0;
                if ($montantDecompteRestant == 0) {
                    $pourcentage = 100;
                } elseif ($total_decompte > 0) {
                    $pourcentage = (($total_decompte - $montantDecompteRestant) / $total_decompte) * 100;
                }
                $pourcentage = number_format($pourcentage, 2);

                $ordresService = $projet->ordresService->map(function ($ordre) {
                    return [
                        'type' => $ordre->type,
                        'date_ordre' => $ordre->date_ordre ? $ordre->date_ordre->format('Y-m-d') : '-',
                        'nbrjour' => $ordre->nbrjour ?? 0,
                    ];
                })->all();

                return [
                    'id' => $projet->id,
                    'num_p' => $projet->num_p ?? '-',
                    'intitule' => $projet->intitule ?? '-',
                    'date_offre' => $projet->date_offre ? $projet->date_offre->format('Y-m-d') : '-',
                    'date_marche' => $projet->date_marche ? $projet->date_marche->format('Y-m-d') : '-',        
                    'ville' => $projet->ville ?? '-',
                    'maitre_ouvrage' => $projet->maitre_ouvrage ?? '-',
                    'date_debut' => $projet->date_debut ? $projet->date_debut->format('Y-m-d') : '-',
                    'budget' => $projet->budget ? number_format($projet->budget, 2) : '-',
                    'rg' => number_format($projet->rg ?? 0, 2),
                    'delai_execution' => $projet->delai_execution ?? '-',
                    'reception_definitive' => $projet->reception_definitive ? $projet->reception_definitive->format('Y-m-d') : '-',
                    'assurance_montant' => $projet->dossiersPdf()->first() ? number_format($projet->dossiersPdf()->first()->assurance_montant, 2) : '-',
                    'total_revision' => $projet->total_revision ? number_format($projet->total_revision, 2) : '-',
                    'description' => $projet->description ?? '-',
                    'travaux_executier' => number_format($projet->travaux_executier ?? 0, 2),
                    'total_decompte' => number_format($total_decompte, 2),
                    'rg_restant' => number_format($rgRestant, 2),
                    'montant_decompte_restant' => number_format($montantDecompteRestant, 2),
                    'decomptes_count' => $projet->decomptes->count(),
                    'ordres_service' => $ordresService,
                    'pourcentage' => $pourcentage,
                    'commande_type' => $projet->commande_type ?? '-',
                    'cloture' => $projet->cloture ?? false,
                    'cloture_date' => $projet->cloture_date ? $projet->cloture_date->format('Y-m-d') : null,
                    'marche_cadre' => (bool) ($projet->marche_cadre ?? false),
        ];
                        
                    });

                    return response()->json($projets);
                } catch (\Exception $e) {
                    Log::error('Error in listPublicProjects', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    return response()->json(['error' => 'Erreur serveur lors du chargement des projets publics'], 500);
                }
            }

            public function editDecompte($id)
{
    try {
        $decompte = Decompte::findOrFail($id);
        $projet = Projet::with('decomptes')->findOrFail($decompte->projet_id);

        $totalRgDp = $projet->decomptes->sum('rg_dp');
        $rgRestant = ($projet->rg ?? 0) - $totalRgDp + ($decompte->rg_dp ?? 0);
        $totalMontantDp = $projet->decomptes->sum('montant_dp');
        $total_decompte = $projet->total_decompte ?? ($projet->budget - ($projet->rg ?? 0));
        $montantDecompteRestant = $total_decompte - $totalMontantDp + ($decompte->montant_dp ?? 0);

        Log::info('editDecompte Calculations', [
            'decompte_id' => $id,
            'totalMontantDp' => $totalMontantDp,
            'total_decompte' => $total_decompte,
            'decompte_montant_dp' => $decompte->montant_dp,
            'montantDecompteRestant' => $montantDecompteRestant,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'decompte' => [
                    'id' => $decompte->id,
                    'projet_id' => $decompte->projet_id,
                    'type_decompte' => $decompte->type_decompte,
                    'montant_dp' => number_format($decompte->montant_dp, 2),
                    'rg_dp' => $decompte->rg_dp ? number_format($decompte->rg_dp, 2) : null,
                    'revision_prix' => $decompte->revision_prix ? number_format($decompte->revision_prix, 2) : null,
                    'date_dp' => $decompte->date_dp->format('Y-m-d'),
                    'document_path' => $decompte->document_path ? Storage::url($decompte->document_path) : null,
                ],
                'projet' => [
                    'rg_restant' => number_format($rgRestant, 2),
                    'montant_decompte_restant' => number_format($montantDecompteRestant, 2),
                ],
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('Error in editDecompte', [
            'decompte_id' => $id,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement du décompte',
        ], 500);
    }
}
public function updateDecompte(Request $request, $id)
{
    Log::info('Données reçues dans updateDecompte', ['request_data' => $request->all(), 'decompte_id' => $id]);

    $toFloat = function ($value) {
        if (!isset($value) || $value === '' || !is_scalar($value)) {
            return 0.0;
        }
        $value = trim((string)$value);
        $value = preg_replace('/[^0-9.-]/', '', $value);
        return is_numeric($value) ? floatval($value) : 0.0;
    };

    $validated = $request->validate([
        'projet_id' => 'required|exists:projet,id',
        'montant_dp' => [
            'required',
            'numeric',
            'min:0.01',
            function ($attribute, $value, $fail) use ($request, $toFloat, $id) {
                $projet = Projet::findOrFail($request->projet_id);
                $decompte = Decompte::findOrFail($id);
                $totalMontantDp = $toFloat($projet->decomptes()->where('id', '!=', $id)->sum('montant_dp'));
                $montantRestant = $toFloat($projet->total_decompte ?? $projet->budget ?? 0) - $totalMontantDp;
                $submittedValue = $toFloat($value);
                Log::info('Montant validation in updateDecompte', [
                    'submitted_value' => $submittedValue,
                    'montantRestant' => $montantRestant,
                    'totalMontantDp' => $totalMontantDp,
                ]);
                if ($submittedValue > $montantRestant) {
                    $fail('Le montant du décompte dépasse le montant restant autorisé (' . number_format($montantRestant, 2) . ').');
                }
            },
        ],
        'date_dp' => 'required|date',
        'type_decompte' => 'required|in:Provisoire,Définitif',
        'rg_dp' => [
            'nullable',
            'numeric',
            'min:0',
            function ($attribute, $value, $fail) use ($request, $toFloat, $id) {
                $projet = Projet::findOrFail($request->projet_id);
                $decompte = Decompte::findOrFail($id);
                $totalRgDp = $toFloat($projet->decomptes()->where('id', '!=', $id)->sum('rg_dp'));
                $rgRestant = max(0, $toFloat($projet->rg ?? 0) - $totalRgDp);
                $submittedRg = $toFloat($value ?? 0);
                if ($rgRestant == 0 && $submittedRg > 0) {
                    $fail('La retenue de garantie ne peut pas être supérieure à 0 lorsque la retenue restante est épuisée.');
                }
                if ($request->type_decompte === 'Définitif' && $submittedRg == 0 && $rgRestant > 0) {
                    $fail('La retenue de garantie est obligatoire pour un décompte définitif.');
                }
            },
        ],
        'revision_prix' => 'nullable|numeric|min:0',
        'document' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
    ]);

    try {
        $decompte = Decompte::findOrFail($id);
        $projet = Projet::findOrFail($validated['projet_id']);

        $projectNum = preg_replace('/[^A-Za-z0-9_-]/', '_', $projet->num_p ?: 'PROJ-' . $projet->id);
        $folderPath = "documents/{$projectNum}/decompte";

        if (!Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->makeDirectory($folderPath, 0755, true);
        }

        $documentPath = $decompte->document_path;
        if ($request->hasFile('document') && $request->file('document')->isValid()) {
            if ($documentPath && Storage::disk('public')->exists($documentPath)) {
                Storage::disk('public')->delete($documentPath);
            }
            $file = $request->file('document');
            $fileExtension = $file->getClientOriginalExtension();
            $fileName = "decompte_" . time() . "_{$projectNum}.{$fileExtension}";
            $documentPath = $file->storeAs($folderPath, $fileName, 'public');
            Log::info('Document updated', ['path' => $documentPath]);
        }

        $totalMontantDp = $toFloat($projet->decomptes()->where('id', '!=', $id)->sum('montant_dp')) + $toFloat($validated['montant_dp']);
        $total_decompte = $toFloat($projet->total_decompte ?? ($projet->budget - ($projet->rg ?? 0)));
        $montantDecompteRestant = $total_decompte - $totalMontantDp;

        $pourcentage = 0;
        if ($montantDecompteRestant == 0) {
            $pourcentage = 100;
        } elseif ($total_decompte > 0) {
            $pourcentage = (($total_decompte - $montantDecompteRestant) / $total_decompte) * 100;
        }
        $pourcentage = number_format($pourcentage, 2);

        Log::info('updateDecompte Final Calculations', [
            'decompte_id' => $id,
            'totalMontantDp' => $totalMontantDp,
            'total_decompte' => $total_decompte,
            'montantDecompteRestant' => $montantDecompteRestant,
            'pourcentage' => $pourcentage,
        ]);

        $decompte->update([
            'montant_dp' => $toFloat($validated['montant_dp']),
            'date_dp' => $validated['date_dp'],
            'type_decompte' => $validated['type_decompte'],
            'rg_dp' => $toFloat($validated['rg_dp'] ?? 0),
            'revision_prix' => $toFloat($validated['revision_prix'] ?? 0),
            'document_path' => $documentPath,
            'pourcentage' => $pourcentage,
            'updated_by' => Auth::id(),
        ]);

        $total_revision = $toFloat($projet->decomptes()->sum('revision_prix'));
        $total_montant_dp = $toFloat($projet->decomptes()->sum('montant_dp'));
        $travaux_executier = $total_montant_dp + $total_revision + $toFloat($projet->rg ?? 0);

        $updateData = [
            'total_revision' => $total_revision,
            'travaux_executier' => $travaux_executier,
        ];

        if ($validated['type_decompte'] === 'Définitif') {
            $receptionDate = Carbon::parse($validated['date_dp'])->addYear();
            $updateData['reception_definitive'] = $receptionDate->format('Y-m-d');
        } else {
            $updateData['reception_definitive'] = $projet->decomptes()->where('type_decompte', 'Définitif')->exists()
                ? $projet->reception_definitive
                : null;
        }

        $projet->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Décompte mis à jour avec succès',
            'data' => [
                'decompte' => $decompte,
                'montant_decompte_restant' => number_format($montantDecompteRestant, 2),
            ],
        ], 200);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la mise à jour du décompte', [
            'decompte_id' => $id,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du décompte : ' . $e->getMessage(),
        ], 500);
    }
}
    public function storeDecompte(Request $request)
    {
        Log::info('Données reçues dans storeDecompte', ['request_data' => $request->all()]);
          $projetCheck = Projet::find($request->projet_id);
        $isCadre = $projetCheck && $projetCheck->marche_cadre;
        // Helper function to convert to float safely
        $toFloat = function ($value) {
            if (!isset($value) || $value === '' || !is_scalar($value)) {
                return 0.0;
            }
            $value = trim((string)$value);
            $value = preg_replace('/[^0-9.-]/', '', $value);
            return is_numeric($value) ? floatval($value) : 0.0;
        };
        

        // Validate request with custom type conversion
        $validated = $request->validate([
            'projet_id' => 'required|exists:projet,id',
            'montant_dp' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($request, $toFloat) {
                    $projet = Projet::findOrFail($request->projet_id);
                    $totalMontantDp = $toFloat($projet->decomptes()->sum('montant_dp'));
                    $montantRestant = $toFloat($projet->total_decompte ?? $projet->budget ?? 0) - $totalMontantDp;
                    $submittedValue = $toFloat($value);
                    Log::info('Montant validation', [
                        'submitted_value' => $submittedValue,
                        'montantRestant' => $montantRestant,
                    ]);
                    if ($submittedValue > $montantRestant) {
                        $fail('Le montant du décompte dépasse le montant restant autorisé (' . number_format($montantRestant, 2) . ').');
                    }
                },
            ],
            'tranche' => $isCadre ? 'required|integer|between:1,3' : 'nullable|integer|between:1,3',
            'date_dp' => 'required|date',
            'type_decompte' => 'required|in:Provisoire,Définitif',
            'rg_dp' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $toFloat) {
                    $projet = Projet::findOrFail($request->projet_id);
                    $totalRgDp = $toFloat($projet->decomptes()->sum('rg_dp'));
                    $rgRestant = max(0, $toFloat($projet->rg ?? 0) - $totalRgDp);
                    $submittedRg = $toFloat($value ?? 0);
                    Log::info('RG validation', [
                        'submitted_rg' => $submittedRg,
                        'rgRestant' => $rgRestant,
                    ]);
                    if ($rgRestant == 0 && $submittedRg > 0) {
                        $fail('La retenue de garantie ne peut pas être supérieure à 0 lorsque la retenue restante est épuisée.');
                    }
                    if ($request->type_decompte === 'Définitif' && $submittedRg == 0 && $rgRestant > 0) {
                        $fail('La retenue de garantie est obligatoire pour un décompte définitif.');
                    }
                },
            ],
            'revision_prix' => 'nullable|numeric|min:0',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        try {
            $projet = Projet::findOrFail($validated['projet_id']);
            Log::info('Projet trouvé', ['projet_id' => $projet->id]);

            $projectNum = preg_replace('/[^A-Za-z0-9_-]/', '_', $projet->num_p ?: 'PROJ-' . $projet->id);
            $folderPath = "documents/{$projectNum}/decompte";

            if (!Storage::disk('public')->exists($folderPath)) {
                Storage::disk('public')->makeDirectory($folderPath, 0755, true);
            }

            $documentPath = null;
            if ($request->hasFile('document') && $request->file('document')->isValid()) {
                $file = $request->file('document');
                $fileExtension = $file->getClientOriginalExtension();
                $fileName = "decompte_" . time() . "_{$projectNum}.{$fileExtension}";
                $documentPath = $file->storeAs($folderPath, $fileName, 'public');
                Log::info('Document uploaded', ['path' => $documentPath]);
            }

            // Calculer le pourcentage en utilisant la logique de listPublicProjects
            $totalMontantDp = $toFloat($projet->decomptes()->sum('montant_dp')) + $toFloat($validated['montant_dp']);
            $total_decompte = $toFloat($projet->total_decompte ?? ($projet->budget - ($projet->rg ?? 0)));
            $montantDecompteRestant = $total_decompte - $totalMontantDp;

            $pourcentage = 0;
            if ($montantDecompteRestant == 0) {
                $pourcentage = 100;
            } elseif ($total_decompte > 0) {
                $pourcentage = (($total_decompte - $montantDecompteRestant) / $total_decompte) * 100;
            }
            $pourcentage = number_format($pourcentage, 2);

            $decompte = Decompte::create([
                'projet_id' => (int)$validated['projet_id'],
                'montant_dp' => $toFloat($validated['montant_dp']),
                'date_dp' => $validated['date_dp'],
                'type_decompte' => $validated['type_decompte'],
                'rg_dp' => $toFloat($validated['rg_dp'] ?? 0),
                'revision_prix' => $toFloat($validated['revision_prix'] ?? 0),
                'document_path' => $documentPath,
                'pourcentage' => $pourcentage, 
                 'tranche' => $isCadre ? (int)$validated['tranche'] : 1,
                // Utilisation du pourcentage calculé
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $total_revision = $toFloat($projet->decomptes()->sum('revision_prix'));
            $total_montant_dp = $toFloat($projet->decomptes()->sum('montant_dp'));
            $travaux_executier = $total_montant_dp + $total_revision + $toFloat($projet->rg ?? 0);

            $updateData = [
                'total_revision' => $total_revision,
                'travaux_executier' => $travaux_executier,
            ];

            if ($validated['type_decompte'] === 'Définitif') {
                $receptionDate = Carbon::parse($validated['date_dp'])->addYear();
                $updateData['reception_definitive'] = $receptionDate->format('Y-m-d');
            }

            $projet->update($updateData);

            Log::info('Décompte créé avec succès', ['decompte_id' => $decompte->id]);

            return response()->json([
                'success' => true,
                'message' => 'Décompte ajouté avec succès',
                'data' => $decompte,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du décompte', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du décompte : ' . $e->getMessage(),
            ], 500);
        }
    }
    public function listDecomptes(Request $request)
    {
        $projet_id = $request->query('projet_id');
        Log::info('listDecomptes called', ['projet_id' => $projet_id]);

        if (!$projet_id) {
            return response()->json([
                'success' => false,
                'message' => 'ID du projet non fourni',
            ], 400);
        }

        try {
            $projet = Projet::with('ordresService', 'dossiersPdf')->findOrFail($projet_id);
            $decomptes = Decompte::where('projet_id', $projet_id)->get()->map(function ($decompte) {
                return [
                    'id' => $decompte->id,
                    'type_decompte' => $decompte->type_decompte,
                    'montant_dp' => number_format($decompte->montant_dp, 2),
                    'rg_dp' => $decompte->rg_dp ? number_format($decompte->rg_dp, 2) : '-',
                    'revision_prix' => $decompte->revision_prix ? number_format($decompte->revision_prix, 2) : '-',
                    'date_dp' => $decompte->date_dp->format('Y-m-d'),
                    'document_path' => $decompte->document_path ? Storage::url($decompte->document_path) : null,
                    'tranche' => $decompte->tranche ?? 1,
                ];
            });
            $projetData = [
                'num_p' => $projet->num_p ?? '-',
                'intitule' => $projet->intitule ?? '-',
                'date_offre' => $projet->date_offre ? $projet->date_offre->format('Y-m-d') : '-',
                'date_marche' => $projet->date_marche ? $projet->date_marche->format('Y-m-d') : '-',
                'ville' => $projet->ville ?? '-',
                'maitre_ouvrage' => $projet->maitre_ouvrage ?? '-',
                'date_debut' => $projet->date_debut ? $projet->date_debut->format('Y-m-d') : '-',
                'budget' => $projet->budget ? number_format($projet->budget, 2) : '-',
                'rg' => $projet->rg ? number_format($projet->rg, 2) : '-',
                'delai_execution' => $projet->delai_execution ?? '-',
                'reception_definitive' => $projet->reception_definitive ? $projet->reception_definitive->format('Y-m-d') : '-',
                'assurance_montant' => $projet->dossiersPdf->first() ? number_format($projet->dossiersPdf->first()->assurance_montant, 2) : '-',
                'total_revision' => $projet->total_revision ? number_format($projet->total_revision, 2) : '-',
                'description' => $projet->description ?? '-',
                'caution_definitif' => $projet->caution_definitif ? number_format($projet->caution_definitif, 2) : '-',
                'cloture' => $projet->cloture ? 'Oui' : 'Non',
                'travaux_executier' => $projet->travaux_executier ? number_format($projet->travaux_executier, 2) : '-',
                'total_decompte' => $projet->total_decompte ? number_format($projet->total_decompte, 2) : '-',
                 'marche_cadre' => (bool) ($projet->marche_cadre ?? false),
            ];

            // Formater les ordres de service
            $ordresService = $projet->ordresService->map(function ($ordre) {
                return [
                    'type' => $ordre->type,
                    'date_ordre' => $ordre->date_ordre ? $ordre->date_ordre->format('Y-m-d') : '-',
                    'nbrjour' => $ordre->nbrjour ?? 0,
                ];
            })->all();

            $jour_d_arret = $projet->ordresService->where('type', 'arret')->sum('nbrjour');

            return response()->json([
                'success' => true,
                'data' => [
                    'decomptes' => $decomptes,
                    'projet' => $projetData,
                    'ordres_service' => $ordresService,
                    'jour_d_arret' => $jour_d_arret ?? 0,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Project not found', ['projet_id' => $projet_id]);
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé pour l\'ID: ' . $projet_id,
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in listDecomptes', [
                'projet_id' => $projet_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

public function destroyDecompte($id)
{
    try {
        $decompte = Decompte::findOrFail($id);
        $projet = Projet::findOrFail($decompte->projet_id);

        if ($decompte->document_path && Storage::disk('public')->exists($decompte->document_path)) {
            Storage::disk('public')->delete($decompte->document_path);
        }

        $decompte->delete();

        $total_revision = $projet->decomptes()->sum('revision_prix');
        $total_montant_dp = $projet->decomptes()->sum('montant_dp');
        $travaux_executier = $total_montant_dp + $total_revision + ($projet->rg ?? 0);
        $total_decompte = $projet->total_decompte ?? ($projet->budget - ($projet->rg ?? 0));
        $montantDecompteRestant = $total_decompte - $total_montant_dp;

        $projet->update([
            'total_revision' => $total_revision,
            'travaux_executier' => $travaux_executier,
            'reception_definitive' => $projet->decomptes()->where('type_decompte', 'Définitif')->exists()
                ? $projet->reception_definitive
                : null,
        ]);

        Log::info('Décompte supprimé avec succès', [
            'decompte_id' => $id,
            'projet_id' => $projet->id,
            'montant_decompte_restant' => $montantDecompteRestant,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Décompte supprimé avec succès',
            'data' => [
                'montant_decompte_restant' => number_format($montantDecompteRestant, 2),
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la suppression du décompte', [
            'decompte_id' => $id,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression du décompte : ' . $e->getMessage(),
        ], 500);
    }
}
}