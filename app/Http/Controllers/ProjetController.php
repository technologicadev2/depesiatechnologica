<?php

namespace App\Http\Controllers;

use App\Models\CompanySettings;
use App\Models\DossiersPdf;
use App\Models\Projet;
use App\Models\Salarie;
use Carbon\Carbon;
use Dotenv\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Models\OrdreService;

class ProjetController extends Controller
{
    public function index()
    {
        $projets = Projet::with(['salaries', 'ordresService', 'dossiersPdf'])
            ->orderBy('created_at', 'desc')
            ->get();
        $salaries = Salarie::all();
        $companySettings = CompanySettings::first();
        return view('projet.index', compact('projets', 'salaries', 'companySettings'));
    }
    public function setExitDate(Request $request)
    {
        $validated = $request->validate([
            'projet_id' => 'required|exists:projet,id',
            'salarie_id' => 'required|exists:salaries,id',
            'date_sortie' => 'required|date',
        ]);

        $projet = Projet::findOrFail($validated['projet_id']);
        $salarie = Salarie::findOrFail($validated['salarie_id']);

        $pivot = $projet->salaries()->where('salarie_id', $salarie->id)->first();
        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Ce salarié n\'est pas affecté à ce projet.'
            ], 400);
        }
        if ($pivot->pivot->date_integration && $validated['date_sortie'] < $pivot->pivot->date_integration) {
            return response()->json([
                'success' => false,
                'message' => 'La date de sortie doit être postérieure ou égale à la date d\'intégration.'
            ], 422);
        }

        $projet->salaries()->updateExistingPivot($salarie->id, [
            'date_sortie' => $validated['date_sortie'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Date de sortie définie avec succès !'
        ]);
    }
    public function list(Request $request)
    {
        $query = Projet::query();
        $query->orderBy('cloture', 'asc')->orderBy('created_at', 'desc');
 
        $projets = $query->get()->map(function ($projet) {
            $has_affectation = $projet->salaries()->count() > 0;
            $dossier_complete = $this->checkDossierComplete($projet);

            // DEBUG: Afficher les relations disponibles
            Log::debug('Relations du projet', [
                'projet_id' => $projet->id,
                'relations_loaded' => $projet->getRelations(),
                'relation_exists_dossierPdf' => $projet->relationLoaded('dossierPdf'),
                'relation_exists_dossiersPdf' => $projet->relationLoaded('dossiersPdf'),
            ]);

            // Calculate private_documents_count - VERSION CORRIGÉE
            $private_documents_count = 0;

            // Essayer d'abord avec dossiersPdf (pluriel)
            $dossier = null;
            if ($projet->relationLoaded('dossiersPdf') && $projet->dossiersPdf) {
                $dossier = $projet->dossiersPdf;
                Log::debug('Utilisation de dossiersPdf (pluriel)', ['projet_id' => $projet->id]);
            }
            // Puis essayer avec dossierPdf (singulier)
            elseif ($projet->relationLoaded('dossierPdf') && $projet->dossierPdf) {
                $dossier = $projet->dossierPdf;
                Log::debug('Utilisation de dossierPdf (singulier)', ['projet_id' => $projet->id]);
            }
            // Sinon, requête directe
            else {
                $dossier = DossiersPdf::where('projet_id', $projet->id)->first();
                Log::debug('Requête directe DossiersPdf', ['projet_id' => $projet->id, 'found' => $dossier ? 'oui' : 'non']);
            }

            if ($dossier && !empty($dossier->private_documents)) {
                Log::debug('Documents privés trouvés', [
                    'projet_id' => $projet->id,
                    'count_total' => count($dossier->private_documents),
                    'documents' => $dossier->private_documents
                ]);

                $private_documents_count = count(array_filter($dossier->private_documents, function ($doc) use ($projet) {
                    // Vérifier que le document a un path
                    if (empty($doc['path'])) {
                        Log::warning('Chemin de document privé vide', ['projet_id' => $projet->id, 'doc' => $doc]);
                        return false;
                    }

                    // Convertir le chemin storage/ en public/ pour vérifier l'existence
                    $filePath = str_replace('storage/', 'public/', $doc['path']);
                    $exists = Storage::disk('public')->exists($filePath);

                    Log::debug('Vérification fichier privé', [
                        'projet_id' => $projet->id,
                        'original_path' => $doc['path'],
                        'converted_path' => $filePath,
                        'exists' => $exists ? 'oui' : 'non'
                    ]);

                    if (!$exists) {
                        Log::warning('Fichier privé introuvable', [
                            'projet_id' => $projet->id,
                            'original_path' => $doc['path'],
                            'converted_path' => $filePath,
                        ]);
                    }

                    return $exists;
                }));
            } else {
                Log::debug('Aucun document privé', [
                    'projet_id' => $projet->id,
                    'dossier_exists' => $dossier ? 'oui' : 'non',
                    'private_documents_empty' => $dossier ? (empty($dossier->private_documents) ? 'oui' : 'non') : 'N/A'
                ]);
            }

            Log::info('Résultat final pour projet', [
                'projet_id' => $projet->id,
                'type_projet' => $projet->type_projet,
                'private_documents_count' => $private_documents_count
            ]);

            return [
                'id' => $projet->id,
                'cloture' => $projet->cloture,
                'intitule' => $projet->intitule ?? '-',
                'adress' => $projet->adress ?? '-',
                'num_p' => $projet->num_p ?? '-',
                'date_debut' => $projet->date_debut ? $projet->date_debut->format('Y-m-d') : '-',
                'date_fin' => $projet->date_fin ? $projet->date_fin->format('Y-m-d') : '-',
                'type_projet' => $projet->type_projet ?? '-',
                'budget' => $projet->budget ?? '-',
                'has_affectation' => $has_affectation,
                'commande_type' => $projet->commande_type ?? '-',
                'dossier_complete' => $dossier_complete,
                'private_documents_count' => $private_documents_count,
                'cloture_date' => $projet->cloture_date ? $projet->cloture_date->format('Y-m-d') : null,
            ];
        });

        return response()->json($projets);
    }
    public function getSalaries(Projet $projet)
    {
        $salaries = $projet->salaries()->with('fonction')->get()->map(function ($salarie) use ($projet) {
            $date_demission = $salarie->date_demission;

            if (!$date_demission) {
                $demission = \App\Models\Demission::where('salarie_id', $salarie->id)->first();
                $date_demission = $demission ? $demission->date_demission : null;
            }

            $date_sortie = $salarie->pivot->date_sortie;
            if ($date_demission && !$date_sortie) {
                $date_sortie = $date_demission;
                $projet->salaries()->updateExistingPivot($salarie->id, [
                    'date_sortie' => $date_sortie,
                ]);
            }

            return [
                'id' => $salarie->id,
                'nom' => $salarie->nom ?? '-',
                'prenom' => $salarie->prenom ?? '-',
                'fonction' => $salarie->fonction ? ['designation' => $salarie->fonction->designation] : null,
                'statut' => $salarie->statut ?? 'actif',
                'pivot' => [
                    'role' => $salarie->pivot->role ?? '-',
                    'date_integration' => $salarie->pivot->date_integration
                        ? date('Y-m-d', strtotime($salarie->pivot->date_integration))
                        : '-',
                    'date_sortie' => $date_sortie
                        ? date('Y-m-d', strtotime($date_sortie))
                        : '-',
                ],
            ];
        });

        return response()->json($salaries);
    }
    public function cloturer(Request $request, $id)
    {
        try {
            $projet = Projet::findOrFail($id);
            if ($projet->cloture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le projet est déjà clôturé.'
                ], 400);
            }

            // If date_fin is null, set it to now()
            if (!$projet->date_fin) {
                $projet->date_fin = now();
            }

            // Always set cloture_date to now() for the cloture action
            $projet->cloture_date = now();
            $projet->cloture = true;
            $projet->save();

            return response()->json([
                'success' => true,
                'message' => 'Projet clôturé avec succès.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in cloturer for project ID: {$id}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Échec de la clôture : ' . $e->getMessage()
            ], 500);
        }
    }
    public function decloturer(Request $request, $id)
    {
        try {
            $projet = Projet::findOrFail($id);
            if (!$projet->cloture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le projet n\'est pas clôturé.'
                ], 400);
            }

            $projet->cloture = false;
            $projet->cloture_date = null;
            $projet->save();

            return response()->json([
                'success' => true,
                'message' => 'Projet rouvert avec succès.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in decloturer for project ID: {$id}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Échec de la réouverture : ' . $e->getMessage()
            ], 500);
        }
    }
    public function show(Projet $projet)
    {
        try {
            $projet->load([
                'salaries.fonction',
                'ordresService' => function ($query) {
                    $query->orderBy('date_ordre', 'asc');
                },
            ]);

            $dossier = DossiersPdf::where('projet_id', $projet->id)->first();
            $companySettings = CompanySettings::first();
            $projectData = [
                'id' => $projet->id,
                'intitule' => $projet->intitule ?? '-',
                'description' => $projet->description ?? '-',
                'num_p' => $projet->num_p ?? '-',
                'date_debut' => $projet->date_debut ? $projet->date_debut->format('Y-m-d') : '-',
                'date_fin' => $projet->date_fin ? $projet->date_fin->format('Y-m-d') : '-',
                'type_projet' => $projet->type_projet ?? '-',
                'budget' => $projet->budget ?? 0,
                'rg' => $projet->rg ?? 0,
                'ville' => $projet->ville ?? '-',
                'maitre_ouvrage' => $projet->maitre_ouvrage ?? '-',
                'date_offre' => $projet->date_offre ? $projet->date_offre->format('Y-m-d') : '-',
                'date_marche' => $projet->date_marche ? $projet->date_marche->format('Y-m-d') : '-',
                'delai_execution' => $projet->delai_execution ?? '-',
                'cloture' => $projet->cloture ?? false,
                'adress' => $projet->adress ?? '-',
                'reception_definitive' => $projet->reception_definitive ? $projet->reception_definitive->format('Y-m-d') : '-',
                'total_revision' => $projet->total_revision ?? 0,
                'created_at' => $projet->created_at ? $projet->created_at->format('Y-m-d H:i:s') : '-',
                'updated_at' => $projet->updated_at ? $projet->updated_at->format('Y-m-d H:i:s') : '-',
                'assurance' => $dossier ? ($dossier->assurance_montant ?? 0) : 0,
                'assurance_document' => $dossier && $dossier->assurance_document ? Storage::url($dossier->assurance_document) : null,
                'travaux_executier' => $projet->travaux_executier ?? 0,
                'caution_definitif' => $projet->caution_definitif ?? 0,


            ];

            $salaries = $projet->salaries->map(function ($salarie) {
                return [
                    'id' => $salarie->id,
                    'nom' => $salarie->nom ?? '-',
                    'prenom' => $salarie->prenom ?? '-',
                    'role' => $salarie->pivot->role ?? '-',
                    'fonction' => $salarie->fonction ? ($salarie->fonction->designation ?? '-') : '-',
                    'date_integration' => $salarie->pivot->date_integration ? date('Y-m-d', strtotime($salarie->pivot->date_integration)) : '-',
                    'date_sortie' => $salarie->pivot->date_sortie ? date('Y-m-d', strtotime($salarie->pivot->date_sortie)) : '-',
                    'statut' => $salarie->statut ?? 'actif',
                ];
            });

            $ordresService = $projet->ordresService->map(function ($ordre) {
                return [
                    'id' => $ordre->id,
                    'type' => $ordre->type ?? '-',
                    'date_ordre' => $ordre->date_ordre ? $ordre->date_ordre->format('Y-m-d') : '-',
                    'document_path' => $ordre->document_path ? Storage::url($ordre->document_path) : null,
                    'nbrjour' => $ordre->nbrjour ?? null,
                    'created_at' => $ordre->created_at ? $ordre->created_at->format('Y-m-d H:i:s') : '-',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => array_merge($projectData, [
                    'salaries' => $salaries,
                    'ordres_service' => $ordresService,
                    'companySettings' => $companySettings,
                ]),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur dans show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails du projet : ' . $e->getMessage(),
            ], 500);
        }
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'intitule' => 'required|string|max:255',
            'adress' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'num_p' => 'nullable|string|max:255|unique:projet,num_p',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'type_projet' => 'nullable|string|max:255',
            'budget' => 'required|numeric|min:0',
            'cloture' => 'required|boolean',
            'marche_cadre' => 'nullable|boolean',
        ],[
                     'budget.required' => 'le montant est obligatoire.',

        ]);

        if (empty($validated['num_p'])) {
            $validated['num_p'] = $this->generateProjectNumber();
        }

        // Force type_projet to Public for private projects
        $validated['type_projet'] = 'Privé';

        $projet = Projet::create(array_merge($validated, [
            'cloture' => $validated['cloture'] ?? 0,
            'created_by' => Auth::id(),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Projet ajouté avec succès !',
            'projet_id' => $projet->id
        ]);
    }
    private function generateProjectNumber()
    {
        $year = date('Y');
        $prefix = "PROJ-{$year}-";
        $latestProject = Projet::where('num_p', 'like', $prefix . '%')
            ->orderBy('num_p', 'desc')
            ->first();

        $nextNumber = 1;
        if ($latestProject) {
            $lastNumber = (int) substr($latestProject->num_p, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
   public function affecter(Request $request)
{
    $validated = $request->validate([
        'projet_id' => 'required|exists:projet,id',
        'responsable' => 'required|exists:salaries,id,statut,actif',
        'ouvriers' => 'nullable|array',
        'ouvriers.*' => 'exists:salaries,id,statut,actif',
    ]);

    $projet = Projet::findOrFail($validated['projet_id']);

    // Gestion du responsable
    $existingResponsable = $projet->salaries()
        ->where('salarie_id', $validated['responsable'])
        ->wherePivot('role', 'responsable')
        ->whereNull('sal_projet.date_sortie')
        ->first();

    if (!$existingResponsable) {
        // Vérifier si le salarié existe déjà avec un autre rôle
        $existingAsOuvrier = $projet->salaries()
            ->where('salarie_id', $validated['responsable'])
            ->wherePivot('role', 'ouvrier')
            ->whereNull('sal_projet.date_sortie')
            ->first();

        if ($existingAsOuvrier) {
            // Mettre à jour son rôle d'ouvrier à responsable
            $projet->salaries()->updateExistingPivot($validated['responsable'], [
                'role' => 'responsable',
                'date_integration' => $existingAsOuvrier->pivot->date_integration, // Garder la date originale
            ]);
        } else {
            // Ajouter comme nouveau responsable
            $projet->salaries()->attach($validated['responsable'], [
                'date_integration' => now(),
                'role' => 'responsable',
                'date_sortie' => null,
            ]);
        }
    }

    // Récupérer les IDs des ouvriers actuels pour ce projet
    $currentOuvrierIds = $projet->salaries()
        ->wherePivot('role', 'ouvrier')
        ->whereNull('sal_projet.date_sortie')
        ->pluck('salarie_id')
        ->toArray();

    $newOuvrierIds = [];

    // Gestion des ouvriers
    if (!empty($validated['ouvriers'])) {
        foreach ($validated['ouvriers'] as $ouvrierId) {
            // Un responsable ne peut pas être ouvrier en même temps sur le même projet
            if ($ouvrierId == $validated['responsable']) {
                continue;
            }

            $existingOuvrier = $projet->salaries()
                ->where('salarie_id', $ouvrierId)
                ->wherePivot('role', 'ouvrier')
                ->whereNull('sal_projet.date_sortie')
                ->first();

            if (!$existingOuvrier) {
                // Ajouter comme nouvel ouvrier (peut être déjà sur d'autres projets)
                $projet->salaries()->attach($ouvrierId, [
                    'date_integration' => now(),
                    'role' => 'ouvrier',
                    'date_sortie' => null,
                ]);
            }

            $newOuvrierIds[] = $ouvrierId;
        }
    }

    // Retirer uniquement les ouvriers qui ne sont plus sélectionnés POUR CE PROJET
    $ouvriersToRemove = array_diff($currentOuvrierIds, $newOuvrierIds);
    
    foreach ($ouvriersToRemove as $ouvrierId) {
        $projet->salaries()
            ->wherePivot('salarie_id', $ouvrierId)
            ->wherePivot('role', 'ouvrier')
            ->whereNull('sal_projet.date_sortie')
            ->update(['sal_projet.date_sortie' => now()]);
    }

    // Retirer l'ancien responsable si un nouveau est assigné
    $projet->salaries()
        ->where('salarie_id', '!=', $validated['responsable'])
        ->wherePivot('role', 'responsable')
        ->whereNull('sal_projet.date_sortie')
        ->update(['sal_projet.date_sortie' => now()]);

    return response()->json([
        'success' => true,
        'message' => 'Affectation effectuée avec succès !',
    ]);
}
    public function checkPresence($salarie_id, Request $request)
    {
        try {
            $projet_id = $request->query('projet_id');
            $count = \App\Models\Presence::where('salarie_id', $salarie_id)
                ->when($projet_id, function ($query) use ($projet_id) {})
                ->count();

            return response()->json([
                'success' => true,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification des présences : ' . $e->getMessage(),
            ], 500);
        }
    }
    public function detachSalarie(Projet $projet, Salarie $salarie)
    {
        try {
            $pivot = $projet->salaries()->where('salarie_id', $salarie->id)->first();

            if (!$pivot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce salarié n\'est pas affecté à ce projet.',
                ], 400);
            }

            $projet->salaries()->detach($salarie->id);

            return response()->json([
                'success' => true,
                'message' => 'Affectation supprimée avec succès !',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage(),
            ], 500);
        }
    }
   public function getAffectationData(Projet $projet)
{
    try {
        $allSalaries = Salarie::where('statut', 'actif')
            ->with([
                'fonction',
                'projets' => function ($query) {
                    // Charger TOUS les projets actifs du salarié (pas seulement le projet actuel)
                    $query->whereNull('sal_projet.date_sortie')
                          ->where('cloture', 0);
                }
            ])->get();

        // RESPONSABLES : Tous les salariés actifs peuvent être responsables
        $responsables = $allSalaries->map(function ($salarie) use ($projet) {
            $projectAssignment = $salarie->projets->firstWhere('id', $projet->id);
            
            // Récupérer les autres projets actifs (hors projet actuel)
            $autresProjets = $salarie->projets->filter(function ($p) use ($projet) {
                return $p->id !== $projet->id;
            })->map(function ($p) {
                return [
                    'id' => $p->id,
                    'designation' => $p->designation,
                    'role' => $p->pivot->role ?? 'N/A'
                ];
            })->values();

            return [
                'id' => $salarie->id,
                'nom' => $salarie->nom ?? 'N/A',
                'prenom' => $salarie->prenom ?? 'N/A',
                'fonction' => $salarie->fonction ? ['designation' => $salarie->fonction->designation] : null,
                'is_assigned' => $projectAssignment && !$projectAssignment->pivot->date_sortie ? true : false,
                'projets_actuels' => $autresProjets, // Liste des autres projets actifs
                'pivot' => $projectAssignment ? [
                    'role' => $projectAssignment->pivot->role,
                    'date_integration' => $projectAssignment->pivot->date_integration
                        ? (is_string($projectAssignment->pivot->date_integration)
                            ? Carbon::parse($projectAssignment->pivot->date_integration)->toIso8601String()
                            : $projectAssignment->pivot->date_integration->toIso8601String())
                        : null,
                    'date_sortie' => $projectAssignment->pivot->date_sortie
                        ? (is_string($projectAssignment->pivot->date_sortie)
                            ? Carbon::parse($projectAssignment->pivot->date_sortie)->toIso8601String()
                            : $projectAssignment->pivot->date_sortie->toIso8601String())
                        : null,
                ] : null,
            ];
        });

        // OUVRIERS : Maintenant TOUS les salariés actifs peuvent être ouvriers
        // (même ceux déjà affectés à d'autres projets)
        $ouvriers = $allSalaries->map(function ($salarie) use ($projet) {
            $projectAssignment = $salarie->projets->firstWhere('id', $projet->id);
            
            // Récupérer les autres projets actifs (hors projet actuel)
            $autresProjets = $salarie->projets->filter(function ($p) use ($projet) {
                return $p->id !== $projet->id;
            })->map(function ($p) {
                return [
                    'id' => $p->id,
                    'designation' => $p->designation,
                    'role' => $p->pivot->role ?? 'N/A'
                ];
            })->values();

            return [
                'id' => $salarie->id,
                'nom' => $salarie->nom ?? 'N/A',
                'prenom' => $salarie->prenom ?? 'N/A',
                'fonction' => $salarie->fonction ? ['designation' => $salarie->fonction->designation] : null,
                'is_assigned' => $projectAssignment && !$projectAssignment->pivot->date_sortie ? true : false,
                'projets_actuels' => $autresProjets, // Liste des autres projets actifs
                'pivot' => $projectAssignment ? [
                    'role' => $projectAssignment->pivot->role,
                    'date_integration' => $projectAssignment->pivot->date_integration
                        ? (is_string($projectAssignment->pivot->date_integration)
                            ? Carbon::parse($projectAssignment->pivot->date_integration)->toIso8601String()
                            : $projectAssignment->pivot->date_integration->toIso8601String())
                        : null,
                    'date_sortie' => $projectAssignment->pivot->date_sortie
                        ? (is_string($projectAssignment->pivot->date_sortie)
                            ? Carbon::parse($projectAssignment->pivot->date_sortie)->toIso8601String()
                            : $projectAssignment->pivot->date_sortie->toIso8601String())
                        : null,
                ] : null,
            ];
        });

        return response()->json([
            'responsables' => $responsables->values(),
            'ouvriers' => $ouvriers->values(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des données d\'affectation: ' . $e->getMessage(),
        ], 500);
    }
}
    public function edit(Projet $projet)
    {
        $projet->date_debut = $projet->date_debut ? $projet->date_debut->format('Y-m-d') : null;
        $projet->date_fin = $projet->date_fin ? $projet->date_fin->format('Y-m-d') : null;
        $projet->date_offre = $projet->date_offre ? $projet->date_offre->format('Y-m-d') : null;
        $projet->date_marche = $projet->date_marche ? $projet->date_marche->format('Y-m-d') : null;

        Log::info("Projet data for edit: ", $projet->toArray()); // Debug log
        $view = $projet->type_projet === 'Public' ? 'projet.edit-public' : 'projet.edit';
        return view($view, compact('projet'))->render();
    }
public function update(Request $request, Projet $projet)
{
    $rules = [
        'intitule' => 'required|string|max:255',
        'date_debut' => 'required|date',
        'date_fin' => 'nullable|date|after_or_equal:date_debut',
        'type_projet' => 'required|string|in:Public,Privé',
        'budget' => 'nullable|numeric|min:0',
        'description' => 'nullable|string',
         'marche_cadre' => 'nullable|boolean',
    ];

    if ($request->type_projet === 'Public') {
        $rules += [
            'date_offre' => 'required|date',
            'date_marche' => 'required|date',
            'ville' => 'required|string|max:100',
            'maitre_ouvrage' => 'required|string|max:255',
            'delai_execution' => 'required|integer|min:1',
            'commande_type' => 'required|string|in:Marche,BC',
        ];
    }

    $validated = $request->validate($rules);

    // Calcul automatique pour les projets publics
    if ($request->type_projet === 'Public' && array_key_exists('budget', $validated)) {
        $validated['rg'] = $validated['commande_type'] === 'Marche' ? $validated['budget'] * 0.07 : 0;
        $validated['caution_definitif'] = $validated['commande_type'] === 'Marche' ? $validated['budget'] * 0.03 : 0;
        $validated['total_decompte'] = $validated['budget'] - $validated['rg'];
    }
    $validated['marche_cadre'] = $request->has('marche_cadre') ? 1 : 0;

    $validated['updated_by'] = Auth::id();

    $projet->update($validated);

    return response()->json([
        'success' => true,
        'message' => 'Projet mis à jour avec succès !'
    ]);
}
    public function destroy(Projet $projet)
    {
        $projet->delete();
        return response()->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès !',
        ]);
    }
    public function storePublic(Request $request)
    {
        $validated = $request->validate([
            'intitule' => 'required|string|max:255',
            'num_p' => 'nullable|string|max:255|unique:projets,num_p',
            'date_offre' => 'required|date',
            'date_marche' => 'required|date',
            'ville' => 'required|string|max:100',
            'maitre_ouvrage' => 'nullable|string|max:255', // Non obligatoire
            'budget' => 'required|numeric|min:0',
            'delai_execution' => 'nullable|integer|min:1', // Non obligatoire
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'type_projet' => 'required|string|in:Public',
            'cloture' => 'required|boolean',
            'commande_type' => 'required|string|in:Marche,BC',
            'rg' => 'nullable|numeric|min:0',
            'marche_cadre' => 'nullable|boolean',
        ]);

        if (empty($validated['num_p'])) {
            $validated['num_p'] = $this->generateProjectNumber();
        }

        // Calculer RG, total_decompte et caution_definitif selon commande_type
        $rg = $validated['commande_type'] === 'Marche' ? $validated['budget'] * 0.07 : 0;
        $total_decompte = $validated['budget'] - $rg;
        $caution_definitif = $validated['commande_type'] === 'Marche' ? $validated['budget'] * 0.03 : 0; // 0 pour BC, 3% pour Marche
         $marcheCadre = $request->has('marche_cadre') ? 1 : 0;
        Log::info('Creating public project', [
            'intitule' => $validated['intitule'],
            'budget' => $validated['budget'],
            'rg' => $rg,
            'total_decompte' => $total_decompte,
            'caution_definitif' => $caution_definitif,
            'commande_type' => $validated['commande_type'],
        ]);

        try {
            $projet = Projet::create([
                'intitule' => $validated['intitule'],
                'num_p' => $validated['num_p'],
                'date_offre' => $validated['date_offre'],
                'date_marche' => $validated['date_marche'],
                'ville' => $validated['ville'],
                'maitre_ouvrage' => $validated['maitre_ouvrage'],
                'budget' => $validated['budget'],
                'rg' => $rg,
                'caution_definitif' => $caution_definitif,
                'total_decompte' => $total_decompte,
                'delai_execution' => $validated['delai_execution'],
                'date_debut' => $validated['date_debut'],
                'date_fin' => $validated['date_fin'],
                'type_projet' => 'Public',
                'cloture' => $validated['cloture'],
                'commande_type' => $validated['commande_type'],
                'marche_cadre' => $marcheCadre,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            Log::info('Public project created successfully', ['projet_id' => $projet->id]);

            return response()->json([
                'success' => true,
                'message' => 'Projet public créé avec succès',
                'data' => $projet,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating public project', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du projet public',
            ], 500);
        }
    }
    public function uploadDocuments(Request $request)
    {
        $validated = $request->validate([
            'projet_id' => 'required|exists:projet,id',
            'marche_document' => 'nullable|file|mimes:pdf|max:5048',
            'ordre_service_document' => 'nullable|file|mimes:pdf|max:5048',
            'assurance_document' => 'nullable|file|mimes:pdf|max:5048',
            'assurance_montant' => 'nullable|required_with:assurance_document|numeric|min:0',
            'demande_cautionnement' => 'nullable|file|mimes:pdf|max:5048',
            'caution_provision_document' => 'nullable|file|mimes:pdf|max:5048',
            'caution_provision' => 'nullable|required_with:caution_provision_document|numeric|min:0',
            'caution_definitif_document' => 'nullable|file|mimes:pdf|max:5048',
            'caution_definitif' => 'nullable|required_with:caution_definitif_document|numeric|min:0',
        ]);

        $projet = Projet::findOrFail($validated['projet_id']);
        $projectNum = $projet->num_p ?: 'PROJ-' . $projet->id;
        $folderPath = "documents/{$projectNum}";

        $data = $request->only([
            'projet_id',
            'assurance_montant',
            'demande_cautionnement',
            'caution_provision',
            'caution_definitif',
        ]);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        if (!Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->makeDirectory($folderPath, 0755, true);
        }

        // Fetch the existing dossier record to get current file paths
        $dossier = DossiersPdf::where('projet_id', $validated['projet_id'])->first();

        $fileFields = [
            'marche_document',
            'assurance_document',
            'demande_cautionnement',
            'caution_provision_document',
            'caution_definitif_document',
            'ordre_service_document',
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);

                // Generate a consistent file name using the field name and project number
                $fileExtension = $file->getClientOriginalExtension();
                $fileName = "{$field}_{$projectNum}.{$fileExtension}";

                if ($dossier && $dossier->$field) {
                    if (Storage::disk('public')->exists($dossier->$field)) {
                        Storage::disk('public')->delete($dossier->$field);
                        Log::info("Deleted old file for {$field}: {$dossier->$field}");
                    }
                }
                $data[$field] = $file->storeAs($folderPath, $fileName, 'public');
                Log::info("Uploaded new file for {$field}: {$data[$field]}");
            }
        }

        if ($dossier) {
            $dossier->update($data);
        } else {
            $dossier = DossiersPdf::create($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Documents enregistrés avec succès',
            'data' => $dossier,
            'download_url' => route('projets.download-dossier', ['projet' => $projet->id])
        ], 201);
    }
    public function uploadPrivateDocuments(Request $request)
    {
        try {
            $validated = $request->validate([
                'projet_id' => 'required|exists:projet,id',
                'private_documents' => 'required|array|min:1',
                'private_documents.*' => 'required|file|mimes:pdf|max:10240',
            ]);

            $projet = Projet::findOrFail($validated['projet_id']);
            if ($projet->type_projet !== 'Privé') {
                Log::warning('Tentative d’upload pour un projet non privé', ['projet_id' => $projet->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ce projet n\'est pas privé.',
                ], 403);
            }

            $projectNum = $projet->num_p ?: 'PROJ-' . $projet->id;
            $folderPath = "documents/{$projectNum}/private";

            if (!Storage::disk('public')->exists($folderPath)) {
                Storage::disk('public')->makeDirectory($folderPath, 0755, true);
                Log::info('Dossier créé', ['path' => $folderPath]);
            }

            $dossier = DossiersPdf::where('projet_id', $validated['projet_id'])->first();
            $privateDocs = $dossier ? ($dossier->private_documents ?? []) : [];

            if ($request->hasFile('private_documents')) {
                foreach ($request->file('private_documents') as $file) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $fileName = "{$originalName}_{$projectNum}_" . time() . ".{$extension}";

                    $path = $file->storeAs($folderPath, $fileName, 'public');
                    $storagePath = str_replace('public/', 'storage/', $path);

                    $privateDocs[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $storagePath,
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ];
                    Log::info('Document privé téléversé', ['file' => $fileName, 'path' => $storagePath]);
                }

                $data = [
                    'projet_id' => $validated['projet_id'],
                    'private_documents' => $privateDocs,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ];

                if ($dossier) {
                    $dossier->update($data);
                } else {
                    $dossier = DossiersPdf::create($data);
                }

                $projet->update(['dossier_complete' => true]);
                Log::info('Projet marqué comme complet', ['projet_id' => $projet->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Documents téléversés avec succès.',
                'data' => [
                    'projet_id' => $projet->id,
                    'project_num' => $projectNum,
                    'documents' => $privateDocs,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléversement des documents privés', [
                'projet_id' => $request->projet_id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function downloadPrivateDossier($projetId)
    {
        try {
            // Récupérer le projet
            $projet = Projet::findOrFail($projetId);

            // Vérifier que le projet est privé
            if ($projet->type_projet !== 'Privé') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce projet n\'est pas privé.',
                ], 403);
            }

            // Générer le numéro du projet
            $projectNum = $projet->num_p ?: 'PROJ-' . $projet->id;

            // Récupérer l'entrée DossiersPdf
            $dossier = DossiersPdf::where('projet_id', $projetId)->first();
            if (!$dossier || empty($dossier->private_documents)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun document privé trouvé pour ce projet.',
                ], 404);
            }

            // Créer un fichier ZIP temporaire
            $zipFileName = "dossier_prive_projet_{$projectNum}.zip";
            $zipPath = storage_path("app/temp/{$zipFileName}");

            // Créer le dossier temporaire si nécessaire
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                Log::error("Impossible de créer le fichier ZIP pour le projet {$projetId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création du fichier ZIP.',
                ], 500);
            }

            // Ajouter les documents privés au ZIP
            $filesAdded = false;
            $missingFiles = [];
            foreach ($dossier->private_documents as $doc) {
                $filePath = str_replace('storage/', 'public/', $doc['path']);
                Log::debug('Vérification du fichier', ['doc_path' => $doc['path'], 'filePath' => $filePath]);
                if (Storage::disk('public')->exists($filePath)) {
                    $localPath = Storage::disk('public')->path($filePath);
                    $zipPathInArchive = "{$projectNum}/private/" . $doc['name'];
                    $zip->addFile($localPath, $zipPathInArchive);
                    $filesAdded = true;
                    Log::info("Ajout du fichier {$doc['name']} dans le ZIP pour le projet {$projectNum}");
                } else {
                    $missingFiles[] = $filePath;
                    Log::warning("Fichier introuvable : {$filePath} pour le projet {$projetId}");
                }
            }

            $zip->close();
            Log::info('ZIP créé', ['zip_path' => $zipPath, 'num_files' => $zip->numFiles]);

            // Vérifier si des fichiers ont été ajoutés
            if (!$filesAdded) {
                if (file_exists($zipPath)) {
                    unlink($zipPath);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun fichier valide disponible pour le téléchargement.',
                    'missing_files' => $missingFiles,
                ], 404);
            }

            // Vérifier que le ZIP existe
            if (!file_exists($zipPath)) {
                Log::error("Le fichier ZIP n'a pas été généré pour le projet {$projetId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la génération du fichier ZIP.',
                ], 500);
            }

            // Retourner le fichier ZIP
            $response = response()->download($zipPath, $zipFileName, [
                'Content-Type' => 'application/zip',
            ]);

            // Nettoyer le fichier temporaire après l'envoi
            register_shutdown_function(function () use ($zipPath) {
                if (file_exists($zipPath)) {
                    unlink($zipPath);
                    Log::info("Fichier ZIP temporaire supprimé : {$zipPath}");
                }
            });

            return $response;
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du dossier privé', [
                'projet_id' => $projetId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement : ' . $e->getMessage(),
            ], 500);
        }
    }
    public function getDocuments($projet_id)
    {
        try {
            $dossier = DossiersPdf::where('projet_id', $projet_id)->first();

            if (!$dossier) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'Aucun document trouvé pour ce projet.',
                ]);
            }

            // Debug: Log les valeurs avant de les retourner
            \Log::info("=== DEBUG DOSSIER DATA ===");
            \Log::info("Projet ID: " . $projet_id);
            \Log::info("Dossier ID: " . $dossier->id);
            \Log::info("Assurance montant: " . $dossier->assurance_montant);
            \Log::info("Caution provision: " . $dossier->caution_provision);
            \Log::info("Caution definitif: " . $dossier->caution_definitif);
            \Log::info("Caution definitif type: " . gettype($dossier->caution_definitif));
            \Log::info("Raw dossier data: " . json_encode($dossier->toArray()));
            \Log::info("===========================");

            $responseData = [
                'marche_document' => $dossier->marche_document ? Storage::url($dossier->marche_document) : null,
                'ordre_service_document' => $dossier->ordre_service_document ? Storage::url($dossier->ordre_service_document) : null,
                'assurance_document' => $dossier->assurance_document ? Storage::url($dossier->assurance_document) : null,
                'assurance_montant' => $dossier->assurance_montant,
                'demande_cautionnement' => $dossier->demande_cautionnement ? Storage::url($dossier->demande_cautionnement) : null,
                'caution_provision_document' => $dossier->caution_provision_document ? Storage::url($dossier->caution_provision_document) : null,
                'caution_provision' => $dossier->caution_provision,
                'caution_definitif_document' => $dossier->caution_definitif_document ? Storage::url($dossier->caution_definitif_document) : null,
                'caution_definitif' => $dossier->caution_definitif,
            ];

            // Debug: Log la réponse finale
            \Log::info("Response data: " . json_encode($responseData));

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ]);
        } catch (\Exception $e) {
            \Log::error("Erreur getDocuments: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des documents : ' . $e->getMessage(),
            ], 500);
        }
    }
    public function checkDossierComplete(Projet $projet)
    {
        $dossier = DossiersPdf::where('projet_id', $projet->id)->first();

        if (!$dossier) {
            Log::error("No dossier found for project ID: {$projet->id}");
            return false;
        }

        // If commande_type is 'BC', allow download regardless of completeness
        if ($projet->commande_type === 'BC') {
            Log::info("Project ID: {$projet->id} has commande_type 'BC', allowing dossier download.");
            return true;
        }

        // For other commande_type, all fields must be complete
        $requiredFields = [
            'marche_document',
            'ordre_service_document',
            'assurance_document',
            'assurance_montant',
            'demande_cautionnement',
            'caution_provision_document',
            'caution_provision',
            'caution_definitif_document',
            'caution_definitif'
        ];

        foreach ($requiredFields as $field) {
            if (empty($dossier->$field)) {
                Log::warning("Field {$field} is missing for project ID: {$projet->id}");
                return false;
            }
        }

        Log::info("All required fields are complete for project ID: {$projet->id}");
        return true;
    }
    public function checkDossierFiles(Projet $projet)
    {
        $dossier = DossiersPdf::where('projet_id', $projet->id)->first();

        if (!$dossier) {
            return response()->json([
                'success' => false,
                'files_exist' => false,
                'message' => 'Aucun dossier trouvé pour ce projet',
                'details' => 'Aucune entrée dans la base de données'
            ], 404);
        }

        $files = [
            'marche_document',
            'ordre_service_document',
            'assurance_document',
            'demande_cautionnement',
            'caution_provision_document',
            'caution_definitif_document'
        ];

        $fileDetails = [];
        $fileExists = false;

        foreach ($files as $file) {
            if ($dossier->$file) {
                $relativePath = str_replace('public/storage/', '', $dossier->$file);
                $publicPath = public_path('storage/' . $relativePath);
                $storagePath = storage_path('app/public/' . $relativePath);

                $publicExists = file_exists($publicPath);
                $storageExists = file_exists($storagePath);

                $fileDetails[$file] = [
                    'db_path' => $dossier->$file,
                    'public_path' => $publicPath,
                    'storage_path' => $storagePath,
                    'public_exists' => $publicExists,
                    'storage_exists' => $storageExists
                ];

                if ($publicExists || $storageExists) {
                    $fileExists = true;
                }
            }
        }

        if ($fileExists) {
            return response()->json([
                'success' => true,
                'files_exist' => true,
                'message' => 'Fichiers trouvés pour ce projet',
                'details' => $fileDetails
            ]);
        } else {
            return response()->json([
                'success' => false,
                'files_exist' => false,
                'message' => 'Les fichiers référencés n\'existent pas sur le serveur',
                'details' => $fileDetails
            ], 404);
        }
    }
    public function downloadDossier($id)
    {
        try {
            $dossier = DossiersPdf::where('projet_id', $id)->first();
            if (!$dossier) {
                Log::error("No dossier found for project ID: {$id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun dossier trouvé pour cet ID de projet'
                ], 404);
            }
            Log::info("Starting downloadDossier for project ID: {$id}");

            $documentFields = [
                'marche_document',
                'ordre_service_document',
                'assurance_document',
                'demande_cautionnement',
                'caution_provision_document',
                'caution_definitif_document'
            ];

            $filePaths = [];

            foreach ($documentFields as $field) {
                $relativePath = $dossier->$field;
                Log::info("Processing field {$field}", ['relativePath' => $relativePath]);

                if (is_string($relativePath) && !empty($relativePath)) {
                    $filePath = public_path('storage/' . $relativePath);
                    Log::info("Computed file path for {$field}", [
                        'relativePath' => $relativePath,
                        'filePath' => $filePath,
                        'exists' => file_exists($filePath),
                        'readable' => is_readable($filePath)
                    ]);

                    if (file_exists($filePath) && is_readable($filePath)) {
                        $filePaths[] = $filePath;
                        Log::info("File found and added to ZIP list: {$filePath}");
                    } else {
                        Log::warning("File not found or not readable: {$filePath}");
                    }
                } else {
                    Log::warning("Invalid or null path for field {$field}", ['relativePath' => $relativePath]);
                }
            }

            Log::info("Collected file paths for ZIP", ['filePaths' => $filePaths]);

            if (empty($filePaths)) {
                Log::error("No valid files found to compress for project ID: {$id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun fichier valide trouvé pour la création du ZIP'
                ], 404);
            }

            $zipFileName = storage_path('app/public/dossier_projet_' . $id . '_' . time() . '.zip');
            $zip = new ZipArchive();

            if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                Log::error("Failed to create ZIP file: {$zipFileName}");
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création du fichier ZIP'
                ], 500);
            }

            foreach ($filePaths as $filePath) {
                $fileName = basename($filePath);
                if ($zip->addFile($filePath, $fileName)) {
                    Log::info("Added to ZIP: {$fileName}");
                } else {
                    Log::warning("Failed to add file to ZIP: {$fileName}");
                }
            }

            $zip->close();

            if (!file_exists($zipFileName)) {
                Log::error("ZIP file was not created: {$zipFileName}");
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la création du fichier ZIP'
                ], 500);
            }

            return response()->download($zipFileName, "dossier_projet_{$id}.zip", [
                'Content-Type' => 'application/zip',
                'Content-Length' => filesize($zipFileName)
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error("Error in downloadDossier for project ID: {$id}", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], 500);
        }
    }
    public function destroyOrdreService($projet_id, $pair_index)
    {
        Log::info('Attempting to delete ordre service pair', [
            'projet_id' => $projet_id,
            'pair_index' => $pair_index,
        ]);

        try {
            $projet = Projet::findOrFail($projet_id);
            $ordres = OrdreService::where('projet_id', $projet_id)
                ->orderBy('date_ordre', 'asc')
                ->get();

            $stops = $ordres->where('type', 'arret')->values();
            $resumes = $ordres->where('type', 'reprise')->values();

            Log::info('Stops and Resumes count', [
                'stops_count' => count($stops),
                'resumes_count' => count($resumes),
            ]);

            if ($pair_index >= count($stops)) {
                Log::warning('Invalid pair index', [
                    'projet_id' => $projet_id,
                    'pair_index' => $pair_index,
                    'stop_count' => count($stops),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Paire d\'ordres non trouvée',
                ], 404);
            }

            // Delete the stop order
            $stop = $stops[$pair_index];
            if ($stop->document_path && Storage::disk('public')->exists($stop->document_path)) {
                Storage::disk('public')->delete($stop->document_path);
                Log::info('Deleted stop document', ['path' => $stop->document_path]);
            } else {
                Log::info('No stop document to delete or file does not exist', ['path' => $stop->document_path]);
            }
            $stop->delete();
            Log::info('Deleted stop order', ['id' => $stop->id]);

            // Delete the resume order if it exists
            if (isset($resumes[$pair_index])) {
                $resume = $resumes[$pair_index];
                if ($resume->document_path && Storage::disk('public')->exists($resume->document_path)) {
                    Storage::disk('public')->delete($resume->document_path);
                    Log::info('Deleted resume document', ['path' => $resume->document_path]);
                } else {
                    Log::info('No resume document to delete or file does not exist', ['path' => $resume->document_path]);
                }
                $resume->delete();
                Log::info('Deleted resume order', ['id' => $resume->id]);
            } else {
                Log::info('No resume order to delete for this pair', ['pair_index' => $pair_index]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paire d\'ordres supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting ordre service pair', [
                'projet_id' => $projet_id,
                'pair_index' => $pair_index,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la paire d\'ordres : ' . $e->getMessage(),
            ], 500);
        }
    }
    public function storeOrdreService(Request $request)
    {
        $validated = $request->validate([
            'projet_id' => 'required|exists:projet,id',
            'type' => 'required|in:arret,reprise',
            'date_ordre' => 'required|date_format:Y-m-d',
            'document_path' => 'nullable|file|mimes:pdf|max:5048',
        ]);

        Log::info('Storing ordre service', ['validated' => $validated]);

        $projet = Projet::findOrFail($validated['projet_id']);
        $projectNum = $projet->num_p ?: 'PROJ-' . $projet->id;
        $folderPath = "documents/{$projectNum}/ordre";

        // Vérifier si un ordre d'arrêt ouvert existe pour ce projet
        if ($validated['type'] === 'arret') {
            $existingStop = OrdreService::where('projet_id', $validated['projet_id'])
                ->where('type', 'arret')
                ->whereNull('nbrjour')
                ->first();

            if ($existingStop) {
                Log::warning('Tentative d\'ajout d\'un ordre d\'arrêt alors qu\'un arrêt est encore ouvert', [
                    'projet_id' => $validated['projet_id'],
                    'existing_stop_id' => $existingStop->id,
                    'existing_stop_date' => $existingStop->date_ordre,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Un ordre d\'arrêt est déjà en cours pour ce projet. Veuillez ajouter un ordre de reprise avant de créer un nouvel arrêt.',
                ], 422);
            }
        }

        // Créer le nouvel ordre
        $data = [
            'projet_id' => $validated['projet_id'],
            'type' => $validated['type'],
            'date_ordre' => $validated['date_ordre'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        if ($request->hasFile('document_path')) {
            $file = $request->file('document_path');
            $fileName = "ordre_service_{$projectNum}_" . time() . ".pdf";
            $data['document_path'] = $file->storeAs($folderPath, $fileName, 'public');
            Log::info('File uploaded', ['document_path' => $data['document_path']]);
        }

        $ordreService = OrdreService::create($data);
        Log::info('Ordre service created', ['id' => $ordreService->id, 'type' => $ordreService->type]);

        // Si c'est une reprise, associer à l'arrêt le plus récent sans reprise
        if ($ordreService->type === 'reprise') {
            $lastStop = OrdreService::where('projet_id', $validated['projet_id'])
                ->where('type', 'arret')
                ->whereNull('nbrjour')
                ->orderBy('date_ordre', 'desc')
                ->first();

            Log::info('Searching for last stop order', [
                'projet_id' => $validated['projet_id'],
                'last_stop_found' => $lastStop ? $lastStop->toArray() : null,
            ]);

            if ($lastStop) {
                // Vérifier les dates
                $stopDate = \Carbon\Carbon::parse($lastStop->date_ordre);
                $resumeDate = \Carbon\Carbon::parse($ordreService->date_ordre);

                if ($resumeDate->gt($stopDate)) {
                    $days = $stopDate->diffInDays($resumeDate);
                    $lastStop->nbrjour = $days;
                    $lastStop->save();

                    Log::info('Updated nbrjour for stop order', [
                        'stop_id' => $lastStop->id,
                        'reprise_id' => $ordreService->id,
                        'nbrjour' => $days,
                        'stop_date' => $stopDate->toDateString(),
                        'resume_date' => $resumeDate->toDateString(),
                    ]);
                } else {
                    Log::warning('Resume date is not after stop date', [
                        'stop_id' => $lastStop->id,
                        'reprise_id' => $ordreService->id,
                        'stop_date' => $stopDate->toDateString(),
                        'resume_date' => $resumeDate->toDateString(),
                    ]);
                }
            } else {
                Log::warning('No matching stop order found', [
                    'reprise_id' => $ordreService->id,
                    'projet_id' => $validated['projet_id'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Ordre de service ajouté avec succès !',
            'data' => $ordreService,
        ]);
    }
    public function getOrdresService($projet_id)
    {
        Log::info('Fetching ordres service', ['projet_id' => $projet_id]);
        try {
            $projet = Projet::find($projet_id);
            if (!$projet) {
                Log::warning('Projet not found', ['projet_id' => $projet_id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Projet non trouvé',
                ], 404);
            }

            $ordres = OrdreService::where('projet_id', $projet_id)
                ->orderBy('date_ordre', 'asc')
                ->get();

            $stopCount = $ordres->where('type', 'arret')->count();
            $resumeCount = $ordres->where('type', 'reprise')->count();
            $netStopOrders = $stopCount - $resumeCount;
            $totalStopDays = $ordres->where('type', 'arret')->sum('nbrjour') ?: 0;

            $pairedOrders = [];
            $stops = $ordres->where('type', 'arret')->values();
            $resumes = $ordres->where('type', 'reprise')->values();

            foreach ($stops as $index => $stop) {
                $resume = $resumes[$index] ?? null;
                $pair = [
                    'type_arret' => $stop->type,
                    'date_arret' => $stop->date_ordre ? $stop->date_ordre->format('Y-m-d') : null,
                    'document_arret' => $stop->document_path ? Storage::url($stop->document_path) : null,
                    'type_reprise' => $resume ? $resume->type : '-',
                    'date_reprise' => $resume && $resume->date_ordre ? $resume->date_ordre->format('Y-m-d') : '-',
                    'document_reprise' => $resume && $resume->document_path ? Storage::url($resume->document_path) : null,
                    'nbrjour' => $stop->nbrjour ?? '-',
                ];
                $pairedOrders[] = $pair;
            }

            Log::info('Ordres service retrieved', ['count' => $ordres->count()]);
            return response()->json([
                'success' => true,
                'data' => $pairedOrders,
                'stop_count' => $stopCount,
                'resume_count' => $resumeCount,
                'net_stop_orders' => $netStopOrders,
                'total_stop_days' => $totalStopDays,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getOrdresService', [
                'projet_id' => $projet_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des ordres de service',
            ], 500);
        }
    }
    public function checkDossier($id)
    {
        try {
            // Récupérer le type de commande depuis la requête
            $commandeType = request()->input('commande_type', null);

            $projet = Projet::findOrFail($id);
            $dossier = DossiersPdf::where('projet_id', $id)->first();

            if (!$dossier) {
                Log::error("No dossier found for project ID: {$id}");
                return response()->json([
                    'files_exist' => false,
                    'message' => 'Aucun dossier trouvé pour cet ID de projet'
                ], 404);
            }

            Log::info("Checking dossier for project ID: {$id} with commande_type: {$commandeType}");

            // Définir les champs requis en fonction du type de commande
            $requiredFields = [];

            if ($commandeType === 'BC') {
                // Pour Bon de Commande, seuls les deux premiers documents sont requis
                $requiredFields = [
                    'marche_document' => 'Document Marché',
                    'ordre_service_document' => 'Ordre de Service'
                ];
            } else {
                // Pour Marché, tous les documents sont requis
                $requiredFields = [
                    'marche_document' => 'Document Marché',
                    'ordre_service_document' => 'Ordre de Service',
                    'assurance_document' => 'Document Assurance',
                    'demande_cautionnement' => 'Demande de Cautionnement',
                    'caution_provision_document' => 'Document Caution Provisoire',
                    'caution_definitif_document' => 'Document Caution Définitive'
                ];
            }

            $missingFiles = [];
            $validFiles = [];
            $filesExist = true; // On présume que tous les fichiers existent jusqu'à preuve du contraire
            $availableDocuments = [];

            foreach ($requiredFields as $field => $label) {
                $relativePath = $dossier->$field;
                Log::info("Checking field {$field}", ['relativePath' => $relativePath]);

                if (is_string($relativePath) && !empty($relativePath)) {
                    $filePath = public_path('storage/' . $relativePath);

                    Log::info("Computed file path for {$field}", [
                        'relativePath' => $relativePath,
                        'filePath' => $filePath,
                        'exists' => file_exists($filePath),
                        'readable' => is_readable($filePath)
                    ]);

                    if (file_exists($filePath) && is_readable($filePath)) {
                        $validFiles[$label] = ['db_path' => $relativePath, 'exists' => true];

                        // Ajouter à la liste des documents disponibles
                        $availableDocuments[] = [
                            'id' => $field,
                            'name' => $label,
                            'path' => $relativePath
                        ];

                        Log::info("File exists and is readable: {$filePath}");
                    } else {
                        $missingFiles[$label] = ['db_path' => $relativePath, 'exists' => false];
                        Log::warning("File not found or not readable: {$filePath}");
                        $filesExist = false; // Un fichier requis est manquant
                    }
                } else {
                    $missingFiles[$label] = ['db_path' => $relativePath, 'exists' => false];
                    Log::warning("Invalid or null path for field {$field}", ['relativePath' => $relativePath]);
                    $filesExist = false; // Un fichier requis est manquant
                }
            }

            // Vérifier aussi les fichiers non requis mais disponibles
            $optionalFields = [];
            if ($commandeType === 'BC') {
                // Les champs qui ne sont pas requis pour BC mais qui pourraient être présents
                $optionalFields = [
                    'assurance_document' => 'Document Assurance',
                    'demande_cautionnement' => 'Demande de Cautionnement',
                    'caution_provision_document' => 'Document Caution Provisoire',
                    'caution_definitif_document' => 'Document Caution Définitive'
                ];
            }

            foreach ($optionalFields as $field => $label) {
                $relativePath = $dossier->$field;

                if (is_string($relativePath) && !empty($relativePath)) {
                    $filePath = public_path('storage/' . $relativePath);

                    if (file_exists($filePath) && is_readable($filePath)) {
                        // Ajouter aux documents disponibles même s'ils ne sont pas requis
                        $availableDocuments[] = [
                            'id' => $field,
                            'name' => $label,
                            'path' => $relativePath
                        ];
                    }
                }
            }

            if ($filesExist) {
                return response()->json([
                    'files_exist' => true,
                    'valid_files' => $validFiles,
                    'missing_files' => $missingFiles,
                    'available_documents' => $availableDocuments,
                    'commande_type' => $commandeType
                ]);
            } else {
                return response()->json([
                    'files_exist' => false,
                    'message' => 'Certains documents requis sont manquants pour le type ' . ($commandeType === 'BC' ? 'Bon de Commande' : 'Marché'),
                    'valid_files' => $validFiles,
                    'missing_files' => $missingFiles,
                    'commande_type' => $commandeType
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error("Error in checkDossier for project ID: {$id}", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'files_exist' => false,
                'message' => 'Erreur lors de la vérification des fichiers : ' . $e->getMessage()
            ], 500);
        }
    }
}
