<?php

namespace App\Http\Controllers;

use App\Models\CompanySettings;
use App\Models\CorbeilleSalaries;
use App\Models\Demission;
use App\Models\Fonction;
use App\Models\Preavis;
use App\Models\Salarie;
use App\Models\Cotisations;
use App\Models\TypeReglement;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Faker\Core\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;          
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalarieController extends Controller
{
  public function index()
{
    $salaries = Salarie::with('fonction')->orderBy('created_at', 'desc')->get();
    $fonctions = Fonction::all();
    $typeReglements = TypeReglement::all();
    $companySettings = CompanySettings::first();

    // Ajoute cette ligne pour récupérer cnss_pp
    $cnss_ps = Cotisations::first()?->cnss_ps ?? 0;
 $amo_ps= Cotisations::first()?->amo_ps ?? 0;
    return view('salaries.index', compact(
        'salaries',
        'fonctions',
        'typeReglements',
        'companySettings',
        'cnss_ps'   ,
        'amo_ps'        
    ));
}
public function list(Request $request)
{
    $status = $request->input('status', 'actif');
    $salaries = Salarie::with(['fonction', 'reglement'])  
        ->where('statut', $status)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($salarie) {
            return [
                'id' => $salarie->id,
                'nom' => $salarie->nom,
                'prenom' => $salarie->prenom ?? '-',
                'cin' => $salarie->cin ?? '-',
                'n_matricule_entreprise' => $salarie->n_matricule_entreprise ?? '-',
                'n_matricule_cnss' => $salarie->n_matricule_cnss ?? '-',
                'phone' => $salarie->phone ?? '-',
                'adresse' => $salarie->adresse ?? '-',
                'email' => $salarie->email ?? '-',
                'statut' => $salarie->statut ?? 'actif',
                'anciennete' => $salarie->anciennete,
                'date_naissance' => $salarie->date_naissance ?? '-',      
                'situation_familiale' => $salarie->situation_familiale ?? '-', 
                'nombre_enfant' => $salarie->nombre_enfant ?? 0,          
                'type_travail' => $salarie->type_travail ?? '-',         
                'type_contrat' => $salarie->type_contrat ?? '-',         
                'salaire_base' => $salarie->salaire_base ?? null,         
                'salaire_journalier' => $salarie->salaire_journalier ?? null, 
                'salaire_net' => $salarie->salaire_net ?? null,
                'rib' => $salarie->rib ?? '-',                           
                'date_embauche' => $salarie->date_embauche ?? '-',       
                'fonction' => $salarie->fonction ? [
                    'designation' => $salarie->fonction->designation
                ] : null,
                'reglement' => $salarie->reglement ? [                   
                    'designation' => $salarie->reglement->designation
                ] : null,
            ];
        });

    return response()->json($salaries);
}
public function reactivate(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'salarie_id' => 'required|exists:salaries,id',
            'date_embauche' => 'nullable|date',
            'n_matricule_entreprise' => 'required|string|max:255|unique:salaries,n_matricule_entreprise',
        ]);

        $originalSalarie = Salarie::findOrFail($id);
        if ($originalSalarie->statut === 'actif') {
            return response()->json([
                'success' => false,
                'message' => 'Ce salarié est déjà actif.',
            ], 422);
        }

        // Sauvegarder les données originales
        $originalCin = $originalSalarie->cin;
        $originalCnss = $originalSalarie->n_matricule_cnss;

        // Déterminer le prochain suffixe unique
        $nextSuffix = 1;
        $modifiedCin = $originalCin . '-' . $nextSuffix;
        $modifiedCnss = $originalCnss . '-' . $nextSuffix;

        $maxAttempts = 1000;
        $attempt = 0;

        while (
            (Salarie::where('cin', $modifiedCin)->where('id', '!=', $id)->exists() ||
            Salarie::where('n_matricule_cnss', $modifiedCnss)->where('id', '!=', $id)->exists()) &&
            $attempt < $maxAttempts
        ) {
            $nextSuffix++;
            $modifiedCin = $originalCin . '-' . $nextSuffix;
            $modifiedCnss = $originalCnss . '-' . $nextSuffix;
            $attempt++;
        }

        if ($attempt >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Trop de réactivations pour ce salarié. La limite (1000) est atteinte.',
            ], 422);
        }

        // Vérifications d'unicité (CIN / CNSS)
        if (Salarie::where('cin', $modifiedCin)->where('id', '!=', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Le CIN modifié (' . $modifiedCin . ') est déjà utilisé par un autre salarié.',
            ], 422);
        }
        if (Salarie::where('n_matricule_cnss', $modifiedCnss)->where('id', '!=', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Le numéro CNSS modifié (' . $modifiedCnss . ') est déjà utilisé par un autre salarié.',
            ], 422);
        }
        if (Salarie::where('cin', $originalCin)->where('id', '!=', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Le CIN original (' . $originalCin . ') est déjà utilisé par un autre salarié.',
            ], 422);
        }
        if (Salarie::where('n_matricule_cnss', $originalCnss)->where('id', '!=', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Le numéro CNSS original (' . $originalCnss . ') est déjà utilisé par un autre salarié.',
            ], 422);
        }

        // Création des dossiers
        $folderName = Str::slug($originalSalarie->nom . '_' . $validated['n_matricule_entreprise'], '_');
        $basePath = 'assets/storage/salaries/' . $folderName;
        $subFolders = ['salaires', 'conges', 'absences', 'demission', 'infosPers'];

        try {
            $parentPaths = ['assets', 'assets/storage', 'assets/storage/salaries'];
            foreach ($parentPaths as $parentPath) {
                $fullParentPath = public_path($parentPath);
                if (!file_exists($fullParentPath)) {
                    mkdir($fullParentPath, 0755, true);
                    Log::info('Dossier parent créé', ['path' => $parentPath]);
                }
            }

            $fullBasePath = public_path($basePath);
            if (!file_exists($fullBasePath)) {
                mkdir($fullBasePath, 0755, true);
                Log::info('Dossier salarié créé', ['path' => $basePath]);
            }

            foreach ($subFolders as $subFolder) {
                $subFolderPath = $basePath . '/' . $subFolder;
                $fullSubFolderPath = public_path($subFolderPath);
                if (!file_exists($fullSubFolderPath)) {
                    mkdir($fullSubFolderPath, 0755, true);
                    Log::info('Sous-dossier créé', ['path' => $subFolderPath]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création des dossiers', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la création des dossiers : ' . $e->getMessage()], 500);
        }

        // Copie des fichiers (photo, contrat, CIN)
        $photoPath = $originalSalarie->photo;
        $contratPath = $originalSalarie->contrat;
        $cinPieceJointePath = $originalSalarie->cin_piece_jointe;

        if ($photoPath && file_exists(public_path($photoPath))) {
            $photoName = basename($photoPath);
            $newPhotoPath = $basePath . '/infosPers/' . $photoName;
            try {
                copy(public_path($photoPath), public_path($newPhotoPath));
                $photoPath = $newPhotoPath;
                Log::info('Photo copiée pour le nouveau salarié', ['new_path' => $newPhotoPath]);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la copie de la photo', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Erreur lors de la copie de la photo : ' . $e->getMessage()], 500);
            }
        }

        if ($contratPath && file_exists(public_path($contratPath))) {
            $contratName = basename($contratPath);
            $newContratPath = $basePath . '/infosPers/' . $contratName;
            try {
                copy(public_path($contratPath), public_path($newContratPath));
                $contratPath = $newContratPath;
                Log::info('Contrat copié pour le nouveau salarié', ['new_path' => $newContratPath]);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la copie du contrat', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Erreur lors de la copie du contrat : ' . $e->getMessage()], 500);
            }
        }

        if ($cinPieceJointePath && file_exists(public_path($cinPieceJointePath))) {
            $cinName = basename($cinPieceJointePath);
            $newCinPath = $basePath . '/infosPers/' . $cinName;
            try {
                copy(public_path($cinPieceJointePath), public_path($newCinPath));
                $cinPieceJointePath = $newCinPath;
                Log::info('Pièce jointe CIN copiée pour le nouveau salarié', ['new_path' => $newCinPath]);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la copie de la pièce jointe CIN', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Erreur lors de la copie de la pièce jointe CIN : ' . $e->getMessage()], 500);
            }
        }

        DB::beginTransaction();

        // Modifier l'ancien salarié
        $originalSalarie->update([
            'cin' => $modifiedCin,
            'n_matricule_cnss' => $modifiedCnss,
            'statut' => 'inactif',
        ]);

        // Créer le nouveau salarié
        $newSalarie = Salarie::create([
            'nom' => $originalSalarie->nom,
            'prenom' => $originalSalarie->prenom,
            'email' => $originalSalarie->email,
            'cin' => $originalCin,
            'cin_piece_jointe' => $cinPieceJointePath,
            'n_matricule_cnss' => $originalCnss,
            'n_matricule_entreprise' => $validated['n_matricule_entreprise'],
            'phone' => $originalSalarie->phone,
            'adresse' => $originalSalarie->adresse,
            'situation_familiale' => $originalSalarie->situation_familiale,
            'nombre_enfant' => $originalSalarie->nombre_enfant,
            'date_naissance' => $originalSalarie->date_naissance,
            'fonction_id' => $originalSalarie->fonction_id,
            'reglement_id' => $originalSalarie->reglement_id,
            'salaire_base' => $originalSalarie->salaire_base,
            'salaire_journalier' => $originalSalarie->salaire_journalier,
            'salaire_net' => $originalSalarie->salaire_net ?? null,
            'photo' => $photoPath,
            'contrat' => $contratPath,
            'type_travail' => $originalSalarie->type_travail,
            'type_contrat' => $originalSalarie->type_contrat,
            'rib' => $originalSalarie->rib,
            'anciennete' => 0,
            'date_embauche' => $validated['date_embauche'] ?? null,
            'statut' => 'actif',
            'auto_salary_calc' => $originalSalarie->auto_salary_calc,
        ]);

        // Stocker dans reactivated_salaries
        \App\Models\ReactivatedSalaries::create([
            'original_salarie_id' => $originalSalarie->id,
            'new_salarie_id' => $newSalarie->id,
            'original_cin' => $originalCin,
            'original_n_matricule_cnss' => $originalCnss,
            'modified_cin' => $modifiedCin,
            'modified_n_matricule_cnss' => $modifiedCnss,
            'new_n_matricule_entreprise' => $validated['n_matricule_entreprise'],
        ]);

        // Table salairs_XXXX
        $currentYear = date('Y');
        $tableName = 'salairs_' . $currentYear;
        if (Schema::hasTable($tableName)) {
            DB::table($tableName)
                ->where('id_salarie', $originalSalarie->id)
                ->update(['updated_at' => now()]);

            DB::table($tableName)->insert([
                'id_salarie' => $newSalarie->id,
                'nom' => $newSalarie->nom,
                'prenom' => $newSalarie->prenom,
                'n_matricule_entreprise' => $validated['n_matricule_entreprise'],
                'salaire' => $newSalarie->salaire_base,
                'salaire_journalier' => $newSalarie->salaire_journalier,
                'salaire_net'           => $newSalarie->salaire_net ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // =====================================================
        // AJOUT AUTOMATIQUE DES PRÉSENCES (POINTAGE)
        // =====================================================
        $dateEmbaucheStr = Carbon::parse($validated['date_embauche'] ?? now())->format('Y-m-d');

        $datesPointees = DB::table('presence')
            ->select('date')
            ->where('date', '>=', $dateEmbaucheStr)
            ->distinct()
            ->orderBy('date')
            ->pluck('date');

        if ($datesPointees->isNotEmpty()) {
            $presencesAInserer = [];

            foreach ($datesPointees as $datePointee) {
                $dateCourante = Carbon::parse($datePointee);

                // Ignorer les dimanches
                if ($dateCourante->isSunday()) {
                    continue;
                }

                // Ignorer les jours fériés
                $isHoliday = \App\Models\JourFerie::where('date_debut', '<=', $datePointee)
                    ->where(function ($q) use ($datePointee) {
                        $q->where('date_fin', '>=', $datePointee)
                          ->orWhereNull('date_fin')
                          ->orWhere('date_fin', '=', $datePointee);
                    })
                    ->exists();

                if ($isHoliday) {
                    continue;
                }

                $presencesAInserer[] = [
                    'salarie_id'   => $newSalarie->id,
                    'date'         => $datePointee,
                    'statuts'      => 1,
                    'mois'         => $dateCourante->month,
                    'jour'         => $dateCourante->day,
                    'heure'        => '08:00:00',
                    'localisation' => null,
                    'id_projet'    => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }

            if (!empty($presencesAInserer)) {
                DB::table('presence')->insert($presencesAInserer);

                Log::info('Présences automatiques créées lors de la réactivation', [
                    'salarie_id'    => $newSalarie->id,
                    'date_embauche' => $dateEmbaucheStr,
                    'nb_presences'  => count($presencesAInserer),
                ]);
            }
        } else {
            Log::info('Aucune date de pointage existante trouvée depuis la date d\'embauche (réactivation)', [
                'salarie_id'    => $newSalarie->id,
                'date_embauche' => $dateEmbaucheStr,
            ]);
        }
        // =====================================================

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Nouveau salarié créé avec succès et ancien salarié modifié.',
            'salarie' => $newSalarie->load('fonction', 'reglement'),
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        Log::error('Validation error in reactivate:', $e->errors());
        return response()->json(['success' => false, 'message' => 'Erreur de validation', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur dans reactivate : ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()], 500);
    }
}
    public function show(Salarie $salarie)
    {
        return response()->json($salarie->load('fonction', 'reglement', 'demission'));
    }
  public function store(Request $request)
{
    $validated = $request->validate([
        'nom' => 'required|string|max:255',
        'prenom' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'cin' => 'required|string|max:255|unique:salaries,cin',
        'cin_piece_jointe' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        'phone' => 'nullable|string|regex:/^[0-9]{10}$/',
        'date_naissance' => 'required|date',
        'adresse' => 'nullable|string|max:255',
        'situation_familiale' => 'required|string|in:Célibataire,Marié,Divorcé,Veuf',
        'nombre_enfant' => 'nullable|integer|min:0',
        'n_matricule_cnss' => [
            'required',
            'string',
            'max:255',
            'unique:salaries,n_matricule_cnss',
            'regex:/^\d{9}$/'
        ],
        'n_matricule_entreprise' => 'required|string|max:255|unique:salaries,n_matricule_entreprise',
        'fonction_id' => 'required|exists:fonctions,id',
        'reglement_id' => 'nullable|exists:type_reglement,id',
        'salaire_base' => 'numeric|min:0',
        'salaire_journalier' => 'numeric|min:0',
        'salaire_net' => 'numeric|min:0',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'contrat' => 'nullable|file|mimes:pdf|max:5120',
        'type_travail' => 'nullable|in:permanent,occasionnel',
        'type_contrat' => 'required|in:CDI,CDD,Freelance,Anapec',
        'date_embauche' => 'required|date',
        'rib' => [
            'nullable',
            'string',
            'max:255',
            function ($attribute, $value, $fail) use ($request) {
                $reglement = TypeReglement::find($request->reglement_id);
                if ($reglement && $reglement->designation === 'Virement' && empty($value)) {
                    $fail('Le RIB est obligatoire pour le type de règlement Virement.');
                }
            },
        ],
    ], [
        'cin.unique' => 'Ce CIN est déjà utilisé.',
        'n_matricule_cnss.unique' => 'Ce numéro de matricule CNSS est déjà utilisé.',
        'n_matricule_cnss.regex' => 'Le numéro CNSS doit contenir exactement 9 chiffres.',
        'n_matricule_entreprise.unique' => "Ce numéro de matricule d'entreprise est déjà utilisé.",
        'photo.image' => 'Le fichier doit être une image.',
        'photo.mimes' => 'L\'image doit être au format jpeg, png ou jpg.',
        'photo.max' => 'L\'image ne doit pas dépasser 2 Mo.',
        'contrat.mimes' => 'Le contrat doit être un fichier PDF.',
        'contrat.max' => 'Le contrat ne doit pas dépasser 5 Mo.',
        'type_contrat.in' => 'Le type de contrat doit être CDI, CDD, Freelance ou Anapec.',
        'salaire_base.required' => 'Le salaire de base est obligatoire.',
        'salaire_base.numeric' => 'Le salaire de base doit être un nombre.',
        'salaire_base.min' => 'Le salaire de base ne peut pas être négatif.',
        'salaire_journalier.required' => 'Le salaire journalier est obligatoire.',
        'salaire_journalier.numeric' => 'Le salaire journalier doit être un nombre.',
        'salaire_journalier.min' => 'Le salaire journalier ne peut pas être négatif.',
        'salaire_net.required' => 'Le salaire net est obligatoire.',
        'salaire_net.numeric' => 'Le salaire net doit être un nombre.',
        'salaire_net.min' => 'Le salaire net ne peut pas être négatif.',
    ]);

    // Calcul de l'ancienneté
    $anciennete = null;
    $dateEmbauche = Carbon::parse($validated['date_embauche']);
    $dateActuelle = Carbon::today();
    $hasDemission = Demission::where('salarie_id', $request->salarie_id)->exists();

    if (!$hasDemission && $request->input('calculer_anciennete', 0)) {
        $anciennete = $dateEmbauche->diffInYears($dateActuelle);
        Log::info('Ancienneté calculée pour le nouveau salarié', [
            'date_embauche' => $dateEmbauche->toDateString(),
            'date_actuelle' => $dateActuelle->toDateString(),
            'anciennete' => $anciennete
        ]);
    }

    // Génération du dossier
    $folderName = Str::slug($validated['nom'] . '_' . $validated['n_matricule_entreprise'], '_');
    $basePath = 'assets/storage/salaries/' . $folderName;
    $subFolders = ['salaires', 'conges', 'absences', 'demission', 'infosPers'];

    try {
        $parentPaths = ['assets', 'assets/storage', 'assets/storage/salaries'];
        foreach ($parentPaths as $parentPath) {
            $fullParentPath = public_path($parentPath);
            if (!file_exists($fullParentPath)) {
                mkdir($fullParentPath, 0755, true);
            }
        }

        $fullBasePath = public_path($basePath);
        if (!file_exists($fullBasePath)) {
            mkdir($fullBasePath, 0755, true);
        }

        foreach ($subFolders as $subFolder) {
            $subFolderPath = $basePath . '/' . $subFolder;
            $fullSubFolderPath = public_path($subFolderPath);
            if (!file_exists($fullSubFolderPath)) {
                mkdir($fullSubFolderPath, 0755, true);
            }
        }
    } catch (\Exception $e) {
        Log::error('Erreur lors de la création des dossiers', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Erreur lors de la création des dossiers : ' . $e->getMessage()], 500);
    }

    // =====================================================
    // Gestion des fichiers
    // =====================================================
    $photoPath = null;
    $contratPath = null;
    $cinPieceJointePath = null;

    // Photo
    if ($request->hasFile('photo')) {
        try {
            $photoName = time() . '_' . $request->file('photo')->getClientOriginalName();
            $photoPath = $basePath . '/infosPers/' . $photoName;
            $request->file('photo')->move(public_path($basePath . '/infosPers'), $photoName);
            Log::info('Photo enregistrée', ['path' => $photoPath]);
        } catch (\Exception $e) {
            Log::error('Erreur photo', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur photo : ' . $e->getMessage()], 500);
        }
    }

    // Contrat
    if ($request->hasFile('contrat')) {
        try {
            $contratName = time() . '_' . $request->file('contrat')->getClientOriginalName();
            $contratPath = $basePath . '/infosPers/' . $contratName;
            $request->file('contrat')->move(public_path($basePath . '/infosPers'), $contratName);
            Log::info('Contrat enregistré', ['path' => $contratPath]);
        } catch (\Exception $e) {
            Log::error('Erreur contrat', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur contrat : ' . $e->getMessage()], 500);
        }
    }

    // Pièce jointe CIN
    if ($request->hasFile('cin_piece_jointe')) {
        try {
            $cinName = time() . '_' . $request->file('cin_piece_jointe')->getClientOriginalName();
            $cinPieceJointePath = $basePath . '/infosPers/' . $cinName;
            $request->file('cin_piece_jointe')->move(public_path($basePath . '/infosPers'), $cinName);
            Log::info('Pièce jointe CIN enregistrée', ['path' => $cinPieceJointePath]);
        } catch (\Exception $e) {
            Log::error('Erreur CIN pièce jointe', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur CIN pièce jointe : ' . $e->getMessage()], 500);
        }
    }

    // =====================================================
    // Table dynamique + insertion
    // =====================================================
    $currentYear = date('Y');
    $tableName = 'salairs_' . $currentYear;   // ← ICI la variable est définie

    if (!Schema::hasTable($tableName)) {
        Log::error("La table $tableName n'existe pas.");
        return response()->json(['error' => "La table $tableName n'existe pas."], 500);
    }

    try {
        DB::beginTransaction();

        $salarie = Salarie::create([
            'nom'                  => $validated['nom'],
            'prenom'               => $validated['prenom'],
            'email'                => $validated['email'] ?? null,
            'cin'                  => $validated['cin'],
            'cin_piece_jointe'     => $cinPieceJointePath,
            'phone'                => $validated['phone'] ?? null,
            'date_naissance'       => $validated['date_naissance'],
            'adresse'              => $validated['adresse'] ?? null,
            'situation_familiale'  => $validated['situation_familiale'],
            'nombre_enfant'        => $validated['nombre_enfant'] ?? 0,
            'n_matricule_cnss'     => $validated['n_matricule_cnss'],
            'n_matricule_entreprise'=> $validated['n_matricule_entreprise'],
            'fonction_id'          => $validated['fonction_id'],
            'reglement_id'         => $validated['reglement_id'] ?? null,
             'salaire_base'         => $validated['salaire_base'] ?? null,
            'salaire_journalier'   => $validated['salaire_journalier'] ?? null,
            'photo'                => $photoPath,
            'contrat'              => $contratPath,
            'type_travail'         => $validated['type_travail'] ?? null,
            'type_contrat'         => $validated['type_contrat'],
            'rib'                  => $validated['rib'] ?? null,
            'statut'               => 'actif',
            'anciennete'           => $anciennete,
            'date_embauche'        => $validated['date_embauche'],
            'auto_salary_calc'     => $request->input('auto_salary_calc', 0),
            'salaire_net' => $validated['salaire_net'] ?? null,
        ]);

        DB::table($tableName)->insert([
            'id_salarie'           => $salarie->id,
            'nom'                  => $salarie->nom,
            'prenom'               => $salarie->prenom,
            'n_matricule_entreprise'=> $salarie->n_matricule_entreprise,
             'salaire'              => $validated['salaire_base'] ?? null,
              'salaire_journalier'              => $validated['salaire_journalier'] ?? null,
              'salaire_net'           => $validated['salaire_net'] ?? null,

            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::commit();

        // ===== AJOUT AUTOMATIQUE DES PRÉSENCES (POINTAGE) =====
$dateEmbaucheStr = Carbon::parse($validated['date_embauche'])->format('Y-m-d');

// Récupérer toutes les dates distinctes déjà pointées >= date_embauche
$datesPointees = DB::table('presence')
    ->select('date')
    ->where('date', '>=', $dateEmbaucheStr)
    ->distinct()
    ->orderBy('date')
    ->pluck('date');

if ($datesPointees->isNotEmpty()) {
    $presencesAInserer = [];

    foreach ($datesPointees as $datePointee) {
        $dateCourante = Carbon::parse($datePointee);

        // Ignorer les dimanches
        if ($dateCourante->isSunday()) {
            continue;
        }

        // Ignorer les jours fériés
        $isHoliday = \App\Models\JourFerie::where('date_debut', '<=', $datePointee)
            ->where(function ($q) use ($datePointee) {
                $q->where('date_fin', '>=', $datePointee)
                  ->orWhereNull('date_fin')
                  ->orWhere('date_fin', '=', $datePointee);
            })
            ->exists();

        if ($isHoliday) {
            continue;
        }

        $presencesAInserer[] = [
            'salarie_id'   => $salarie->id,
            'date'         => $datePointee,
            'statuts'      => 1,
            'mois'         => $dateCourante->month,
            'jour'         => $dateCourante->day,
            'heure'        => '08:00:00',
            'localisation' => null,
            'id_projet'    => null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
    }

    // Insertion en batch
    if (!empty($presencesAInserer)) {
        DB::table('presence')->insert($presencesAInserer);

        Log::info('Présences automatiques créées pour le nouveau salarié', [
            'salarie_id'    => $salarie->id,
            'date_embauche' => $dateEmbaucheStr,
            'nb_presences'  => count($presencesAInserer),
        ]);
    }
} else {
    Log::info('Aucune date de pointage existante trouvée depuis la date d\'embauche', [
        'salarie_id'    => $salarie->id,
        'date_embauche' => $dateEmbaucheStr,
    ]);
}

        return response()->json([
            'message' => "Salarié ajouté avec succès et enregistré dans $tableName.",
            'salarie' => $salarie->load('fonction', 'reglement')
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Erreur lors de l'ajout du salarié : " . $e->getMessage());
        return response()->json(['error' => 'Erreur lors de l\'ajout du salarié : ' . $e->getMessage()], 500);
    }
}

    public function create()
{
    $fonctions = Fonction::all();
    $typeReglements = TypeReglement::all();

    // Récupérer cnss_pp depuis la table cotisations
    $cnss_pp =Cotisations::first()?->cnss_pp ?? 0;

    return view('salaries.create', compact(
        'fonctions',
        'typeReglements',
        'cnss_pp'
        // ... autres variables si besoin
    ));
}



    public function edit(Salarie $salarie)
    {
        $fonctions = Fonction::all();
        $typeReglements = TypeReglement::all();
        return view('salaries.edit', compact('salarie', 'fonctions', 'typeReglements'));
    }
public function update(Request $request, Salarie $salarie)
{
    Log::info('Update request received', ['salarie_id' => $salarie->id, 'input' => $request->all()]);

    try {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'cin' => 'required|string|max:255|unique:salaries,cin,' . $salarie->id,
            'cin_piece_jointe' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120', // ✅ ajouté
            'phone' => 'nullable|string|regex:/^[0-9]{10}$/',
            'date_naissance' => 'required|date',
            'adresse' => 'nullable|string|max:255',
            'situation_familiale' => 'nullable|string|in:Célibataire,Marié,Divorcé,Veuf',
            'nombre_enfant' => 'nullable|integer|min:0',
            'n_matricule_cnss' => [
                'required',
                'string',
                'max:255',
                'unique:salaries,n_matricule_cnss,' . $salarie->id,
                'regex:/^\d{9}$/'
            ],
            'n_matricule_entreprise' => 'required|string|max:255|unique:salaries,n_matricule_entreprise,' . $salarie->id,
            'fonction_id' => 'required|exists:fonctions,id',
            'reglement_id' => 'nullable|exists:type_reglement,id',
           'salaire_base'       => 'nullable|numeric|min:0',
            'salaire_journalier' => 'nullable|numeric|min:0',
            'salaire_net'        => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'contrat' => 'nullable|file|mimes:pdf|max:5120',
            'type_travail' => 'nullable|in:permanent,occasionnel',
            'type_contrat' => 'required|in:CDI,CDD,Freelance,Anapec',
            'date_embauche' => 'required|date',
            'rib' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $reglement = TypeReglement::find($request->reglement_id);
                    if ($reglement && $reglement->designation === 'Virement' && empty($value)) {
                        $fail('Le RIB est obligatoire pour le type de règlement Virement.');
                    }
                },
            ],
            'auto_salary_calc' => 'nullable|boolean',
        ], [
            'cin.unique' => 'Ce CIN est déjà utilisé.',
            'cin_piece_jointe.mimes' => 'La pièce jointe CIN doit être une image (jpeg, png, jpg) ou un PDF.',
            'cin_piece_jointe.max' => 'La pièce jointe CIN ne doit pas dépasser 5 Mo.',
            'n_matricule_cnss.unique' => 'Ce numéro de matricule CNSS est déjà utilisé.',
            'n_matricule_cnss.regex' => 'Le numéro CNSS doit contenir exactement 9 chiffres.',
            'n_matricule_entreprise.unique' => "Ce numéro de matricule d'entreprise est déjà utilisé.",
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'L\'image doit être au format jpeg, png ou jpg.',
            'photo.max' => 'L\'image ne doit pas dépasser 2 Mo.',
            'contrat.mimes' => 'Le contrat doit être un fichier PDF.',
            'contrat.max' => 'Le contrat ne doit pas dépasser 5 Mo.',
            'type_contrat.required' => 'Le type de contrat est obligatoire.',
            'type_contrat.in' => 'Le type de contrat doit être CDI, CDD, Anapec ou Freelance.',
            
            'salaire_base.numeric' => 'Le salaire de base doit être un nombre.',
            'salaire_base.min' => 'Le salaire de base ne peut pas être négatif.',
            'salaire_journalier.required' => 'Le salaire journalier est obligatoire.',
            'salaire_journalier.numeric' => 'Le salaire journalier doit être un nombre.',
            'salaire_journalier.min' => 'Le salaire journalier ne peut pas être négatif.',
            'date_embauche.required' => 'La date d\'embauche est obligatoire.',
            
            'salaire_net.numeric' => 'Le salaire net doit être un nombre.',
            'salaire_net.min' => 'Le salaire net ne peut pas être négatif.',
        ]);

      // Safe auto-calc validation
        if ($request->input('auto_salary_calc', 0)) {
            $salaireBase = floatval($validated['salaire_base'] ?? 0);
            $salaireJournalier = floatval($validated['salaire_journalier'] ?? 0);
            $expectedBase = round($salaireJournalier * 26, 2);
            $expectedJournalier = round($salaireBase / 26, 2);

            if (abs($salaireBase - $expectedBase) > 0.01 && abs($salaireJournalier - $expectedJournalier) > 0.01) {
                return response()->json([
                    'errors' => [
                        'salaire_base' => 'Le salaire de base doit être égal au salaire journalier multiplié par 26 lorsque le calcul automatique est activé.',
                        'salaire_journalier' => 'Le salaire journalier doit être égal au salaire de base divisé par 26 lorsque le calcul automatique est activé.',
                    ]
                ], 422);
            }
        }

        Log::info('Validation passed', ['validated' => $validated]);

        // Gestion de l'ancienneté
        $anciennete = $salarie->anciennete;
        $hasDemission = Demission::where('salarie_id', $salarie->id)->exists();

        if (!$hasDemission && $request->input('calculer_anciennete', 0)) {
            $dateEmbauche = Carbon::parse($validated['date_embauche']);
            $dateActuelle = Carbon::today();
            $anciennete = $dateEmbauche->diffInYears($dateActuelle);
            Log::info('Ancienneté recalculée', [
                'salarie_id' => $salarie->id,
                'date_embauche' => $dateEmbauche->toDateString(),
                'date_actuelle' => $dateActuelle->toDateString(),
                'anciennete' => $anciennete
            ]);
        }

        // =====================================================
        // ✅ Chemin de stockage aligné sur celui du store()
        // On se base sur le nom/matricule AVANT modification,
        // c'est le dossier où les fichiers d'origine ont été créés.
        // =====================================================
        $folderName = Str::slug($salarie->nom . '_' . $salarie->n_matricule_entreprise, '_');
        $basePath = 'assets/storage/salaries/' . $folderName;
        $infosPersPath = $basePath . '/infosPers';

        try {
            $parentPaths = ['assets', 'assets/storage', 'assets/storage/salaries', $basePath, $infosPersPath];
            foreach ($parentPaths as $path) {
                $fullPath = public_path($path);
                if (!file_exists($fullPath)) {
                    mkdir($fullPath, 0755, true);
                    Log::info('Dossier créé', ['path' => $path]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création des dossiers', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la création des dossiers : ' . $e->getMessage()], 500);
        }

        // Gestion de la photo
        $photoPath = $salarie->photo;
        if ($request->hasFile('photo')) {
            try {
                if ($photoPath && file_exists(public_path($photoPath))) {
                    unlink(public_path($photoPath));
                    Log::info('Ancienne photo supprimée', ['path' => $photoPath]);
                }
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path($infosPersPath), $photoName);
                $photoPath = $infosPersPath . '/' . $photoName;
                Log::info('Nouvelle photo enregistrée', ['path' => $photoPath]);
            } catch (\Exception $e) {
                Log::error('Photo upload failed', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Erreur lors du téléchargement de la photo'], 500);
            }
        }

        // Gestion du contrat
        $contratPath = $salarie->contrat;
        if ($request->hasFile('contrat')) {
            try {
                if ($contratPath && file_exists(public_path($contratPath))) {
                    unlink(public_path($contratPath));
                    Log::info('Ancien contrat supprimé', ['path' => $contratPath]);
                }
                $contrat = $request->file('contrat');
                $contratName = time() . '_' . $contrat->getClientOriginalName();
                $contrat->move(public_path($infosPersPath), $contratName);
                $contratPath = $infosPersPath . '/' . $contratName;
                Log::info('Nouveau contrat enregistré', ['path' => $contratPath]);
            } catch (\Exception $e) {
                Log::error('Contract upload failed', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Erreur lors du téléchargement du contrat'], 500);
            }
        }

        // ✅ Gestion de la pièce jointe CIN (nouveau)
        $cinPieceJointePath = $salarie->cin_piece_jointe;
        if ($request->hasFile('cin_piece_jointe')) {
            try {
                if ($cinPieceJointePath && file_exists(public_path($cinPieceJointePath))) {
                    unlink(public_path($cinPieceJointePath));
                    Log::info('Ancienne pièce jointe CIN supprimée', ['path' => $cinPieceJointePath]);
                }
                $cinFile = $request->file('cin_piece_jointe');
                $cinName = time() . '_' . $cinFile->getClientOriginalName();
                $cinFile->move(public_path($infosPersPath), $cinName);
                $cinPieceJointePath = $infosPersPath . '/' . $cinName;
                Log::info('Nouvelle pièce jointe CIN enregistrée', ['path' => $cinPieceJointePath]);
            } catch (\Exception $e) {
                Log::error('CIN attachment upload failed', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Erreur lors du téléchargement de la pièce jointe CIN'], 500);
            }
        }

        // Mise à jour du salarié
       $salarie->update([
            'nom'                => $validated['nom'],
            'prenom'             => $validated['prenom'],
            'email'              => $validated['email'] ?? null,
            'cin'                => $validated['cin'],
            'cin_piece_jointe'   => $cinPieceJointePath,
            'phone'              => $validated['phone'] ?? null,
            'date_naissance'     => $validated['date_naissance'],
            'adresse'            => $validated['adresse'] ?? null,
            'situation_familiale'=> $validated['situation_familiale'] ?? null,
            'nombre_enfant'      => $validated['nombre_enfant'] ?? 0,
            'n_matricule_cnss'   => $validated['n_matricule_cnss'],
            'n_matricule_entreprise' => $validated['n_matricule_entreprise'],
            'fonction_id'        => $validated['fonction_id'],
            'reglement_id'       => $validated['reglement_id'] ?? null,
            'salaire_base'       => $validated['salaire_base'] ?? null,
            'salaire_journalier' => $validated['salaire_journalier'] ?? null,
            'salaire_net'        => $validated['salaire_net'] ?? null,
            'photo'              => $photoPath,
            'contrat'            => $contratPath,
            'type_travail'       => $validated['type_travail'] ?? null,
            'type_contrat'       => $validated['type_contrat'],
            'rib'                => $validated['rib'] ?? null,
            'anciennete'         => $anciennete,
            'date_embauche'      => $validated['date_embauche'],
            'auto_salary_calc'   => $request->input('auto_salary_calc', 0),
        ]);

        // Mettre à jour la table salairs_$currentYear
        $currentYear = date('Y');
        $tableName = 'salairs_' . $currentYear;

        if (Schema::hasTable($tableName)) {
            DB::table($tableName)
                ->where('id_salarie', $salarie->id)
                ->update([
                    'nom' => $validated['nom'],
                    'prenom' => $validated['prenom'],
                    'n_matricule_entreprise' => $validated['n_matricule_entreprise'],
                    'salaire'              => $validated['salaire_base'] ?? null,
                    'salaire_net'    => $validated['salaire_net'] ?? null,      
                    'salaire_journalier'    => $validated['salaire_journalier'] ?? null,        
                    'updated_at' => now(),
                ]);
        } else {
            Log::warning("Table $tableName n'existe pas.");
        }

        return response()->json([
            'message' => 'Salarié mis à jour avec succès.',
            'salarie' => $salarie->load('fonction', 'reglement'),
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        Log::error('Erreur serveur lors de la mise à jour', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
    }
}
    public function destroy(Salarie $salarie)
    {
        try {
            // Check if the salarie is associated with a user
            $hasUser = User::where('id_salarie', $salarie->id)->exists();

            if ($hasUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer ce salarié car il est associé à un utilisateur. Veuillez supprimer l\'utilisateur associé en premier.'
                ], 422);
            }

            // Check if the salarie has any payments
            $existsInPaiements = DB::table('paiement_salaires')
                ->where('id_salarie', $salarie->id)
                ->where('statutspj', 1)
                ->exists();

            if ($existsInPaiements) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer ce salarié, il a déjà un paiement.'
                ], 422);
            }

            $hasPayments = DB::table('paiement_salaires')
                ->where('id_salarie', $salarie->id)
                ->exists();

            if ($hasPayments) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer ce salarié, il a déjà un paiement.'
                ], 422);
            }

            // Save salarie data to corbeille (trash) before deletion
            $corbeilleData = $salarie->toArray();
            $corbeilleData['salarie_id'] = $salarie->id;
            CorbeilleSalaries::create($corbeilleData);

            // Delete associated files
            if ($salarie->photo && file_exists(public_path($salarie->photo))) {
                unlink(public_path($salarie->photo));
            }
            if ($salarie->contrat && file_exists(public_path($salarie->contrat))) {
                unlink(public_path($salarie->contrat));
            }

            // Delete the salarie
            $salarie->delete();

            return response()->json([
                'success' => true,
                'message' => 'Salarié supprimé avec succès.'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Erreur lors de la vérification des paiements : ' . $e->getMessage(), [
                'salarie_id' => $salarie->id,
                'table' => 'paiement_salaires',
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer ce salarié, il a déjà un paiement.'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du salarié : ' . $e->getMessage(), [
                'salarie_id' => $salarie->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression du salarié. Veuillez réessayer.'
            ], 500);
        }
    }
public function generateMatricule()
{
    $matricule = 1;
    while (Salarie::where('n_matricule_entreprise', $matricule)->exists()) {
        $matricule++;
    }

    // Si aucun matricule n'existe ou si le matricule 1 est libre, retourner le matricule
    // Sinon, retourner null pour indiquer qu'aucun matricule n'est nécessaire
    return response()->json(['matricule' => $matricule == 1 && !Salarie::exists() ? null : $matricule]);
}
 public function checkUnique(Request $request)
{
    $request->validate([
        'field' => 'required|string|in:cin,n_matricule_cnss,n_matricule_entreprise',
        'value' => [
            'required',
            'string',
            function ($attribute, $value, $fail) use ($request) {
                if ($request->field === 'n_matricule_cnss' && !preg_match('/^\d{9}(-\d+)?$/', $value)) {
                    $fail('Le numéro CNSS doit contenir exactement 9 chiffres ou 9 chiffres suivis d’un suffixe -nombre.');
                }
            },
        ],
        'currentId' => 'nullable|integer',
    ]);

    $field = $request->field;
    $value = $request->value;
    $currentId = $request->currentId;

    $query = Salarie::where($field, $value);
    if ($currentId) {
        $query->where('id', '!=', $currentId);
    }

    $exists = $query->exists();

    if ($exists) {
        return response()->json([
            'exists' => true,
            'message' => "Ce $field est déjà utilisé.",
        ], 422);
    }

    return response()->json(['exists' => false]);
}
protected function handleFileUpload($file, $salarie, $folderName, $existingPath = null)
{
    try {
        $basePath = public_path('assets/storage/salaries');
        $folderPath = Str::slug($salarie->nom . '_' . $salarie->n_matricule_entreprise, '_') . '/demission';
        $fullDirectoryPath = $basePath . '/' . $folderPath;

        if (!file_exists($fullDirectoryPath)) {
            Log::error('Dossier de destination introuvable :', ['path' => $fullDirectoryPath]);
            throw new \Exception('Le dossier de destination pour le salarié n\'existe pas. Veuillez vérifier la création du dossier salarié.');
        }

        $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
        $filePath = 'assets/storage/salaries/' . $folderPath . '/' . $filename;

        $file->move($fullDirectoryPath, $filename);
        Log::info('Fichier téléversé :', ['path' => $filePath]);

        return $filePath;
    } catch (\Exception $e) {
        Log::error('Erreur dans handleFileUpload :', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        throw $e;
    }
}


public function demission(Request $request)
{
    try {
        $validated = $request->validate([
            'salarie_id' => 'required|exists:salaries,id',
            'date_demission' => 'required|date',
            'motif' => 'required|string|max:1000',
            'document' => 'nullable|file|mimes:pdf|max:5120',
            'preavis_id' => 'nullable|exists:preavis,id',
            'preavis_document_path' => 'nullable|string', // Ajout pour recevoir le chemin du document de préavis
        ], [
            'date_demission.after_or_equal' => 'La date de démission doit être aujourd\'hui ou ultérieure.',
            'motif.required' => 'Le motif est obligatoire.',
            'document.mimes' => 'Le document doit être un fichier PDF.',
            'document.max' => 'Le document ne doit pas dépasser 5 Mo.',
        ]);

        Log::info('Données validées pour demission : ', $validated);

        $salarie = Salarie::findOrFail($validated['salarie_id']);

        if ($salarie->statut === 'inactif') {
            Log::warning('Tentative de démission pour un salarié déjà inactif : ' . $salarie->id);
            return response()->json([
                'success' => false,
                'message' => 'Ce salarié est déjà inactif.',
            ], 422);
        }

        // Récupérer la date_embauche depuis la table salaries
        $dateEmbauche = DB::selectOne('SELECT date_embauche FROM salaries WHERE id = ?', [$validated['salarie_id']]);

        if (!$dateEmbauche || !$dateEmbauche->date_embauche) {
            Log::warning('Aucune date d\'embauche trouvée pour le salarié : ' . $salarie->id);
            return response()->json([
                'success' => false,
                'message' => 'Le salarié doit avoir une date d\'embauche définie.',
            ], 422);
        }

        // Vérifier si le salarié a un utilisateur associé
        $user = User::where('id_salarie', $salarie->id)->first();
        $hasUser = $user !== null;
        $username = $hasUser ? $user->username : null;

        $message = 'Démission enregistrée avec succès !';
        if ($hasUser) {
            $message .= " L'utilisateur associé ({$username}) a également été supprimé.";
        }

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $this->handleFileUpload($request->file('document'), $salarie, 'demission');
            Log::info('Fichier de démission sauvegardé à : ' . $documentPath);
        }

        // Créer un nouvel enregistrement de démission
        $demission = Demission::create([
            'salarie_id' => $salarie->id,
            'date_demission' => $validated['date_demission'],
            'motif' => $validated['motif'],
            'document_path' => $documentPath,
            'preavis_id' => $validated['preavis_id'] ?? null,
            'docPreavis' => $validated['preavis_document_path'] ?? null, // Enregistrer le chemin du document de préavis
            'created_by' => Auth::id(),
            'date_embauche' => $dateEmbauche->date_embauche,
        ]);

        // Mettre à jour le statut du salarié à inactif
        $salarie->update(['statut' => 'inactif']);

        // Supprimer l'utilisateur associé s'il existe
        if ($hasUser) {
            $user->forceDelete();
            Log::info('Utilisateur associé supprimé : ' . $user->id . ' (username: ' . $user->username . ')');
        }

        Log::info('Démission créée avec ID : ' . $demission->id . ', salarie_id : ' . $salarie->id);

        return response()->json([
            'success' => true,
            'message' => $message,
            'demission' => $demission->load('preavis'),
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Erreur de validation dans demission : ' . json_encode($e->errors()));
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erreur dans demission : ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur : ' . $e->getMessage(),
        ], 500);
    }
}
public function storePreavis(Request $request)
{
    try {
        $validated = $request->validate([
            'salarie_id' => 'required|exists:salaries,id',
            'date_debut_preavis' => 'nullable|date',
            'date_fin_preavis' => 'nullable|date|after_or_equal:date_debut_preavis',
            'document_preavis' => 'nullable|file|mimes:pdf|max:5120',
        ], [
            'date_fin_preavis.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'document_preavis.mimes' => 'Le document doit être un fichier PDF.',
            'document_preavis.max' => 'Le document ne doit pas dépasser 5 Mo.',
        ]);

        Log::info('Données validées pour storePreavis : ', $validated);

        $salarie = Salarie::findOrFail($validated['salarie_id']);

        $documentPath = null;
        if ($request->hasFile('document_preavis')) {
            $documentPath = $this->handleFileUpload($request->file('document_preavis'), $salarie, 'demission');
            Log::info('Fichier préavis sauvegardé à : ' . $documentPath);
        }

        $preavis = Preavis::create([
            'salarie_id' => $validated['salarie_id'],
            'date_debut' => $validated['date_debut_preavis'] ?? null,
            'date_fin' => $validated['date_fin_preavis'] ?? null,
            'document_path' => $documentPath,
            'created_by' => Auth::id(),
        ]);

        Log::info('Préavis créé avec ID : ' . $preavis->id . ', salarie_id : ' . $preavis->salarie_id, [
            'document_path' => $documentPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Préavis enregistré avec succès !',
            'preavis_id' => $preavis->id,
            'document_path' => $documentPath, // Retourner le chemin du document pour utilisation dans demission
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Erreur de validation dans storePreavis : ' . json_encode($e->errors()));
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erreur dans storePreavis : ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur : ' . $e->getMessage(),
        ], 500);
    }
}
 
    
public function downloadPreavis($id)
{
    try {
        $demission = Demission::findOrFail($id);
        $filePath = $demission->docPreavis;

        if (empty($filePath)) {
            Log::error('Chemin de fichier docPreavis vide pour l\'ID: ' . $id);
            return response()->json(['error' => 'Aucun fichier de préavis attaché à cette démission'], 404);
        }

        $fullPath = public_path($filePath);

        if (!file_exists($fullPath)) {
            Log::error('Fichier docPreavis introuvable à: ' . $fullPath . ' pour l\'ID: ' . $id);
            return response()->json([
                'error' => 'Fichier de préavis introuvable',
                'details' => 'Chemin recherché: ' . $filePath
            ], 404);
        }

        Log::info('Téléchargement du fichier docPreavis à: ' . $fullPath);
        return response()->download($fullPath, basename($filePath), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur dans downloadPreavis: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        return response()->json(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
    }
}
    public function downloadDemission($id)
    {
        try {
            $demission = Demission::findOrFail($id);
            $filePath = $demission->document_path;

            if (empty($filePath)) {
                Log::error('Chemin de fichier démission vide pour l\'ID: ' . $id);
                return response()->json(['error' => 'Aucun fichier attaché à cette démission'], 404);
            }

            $fullPath = public_path($filePath);

            if (!file_exists($fullPath)) {
                Log::error('Fichier démission introuvable à: ' . $fullPath . ' pour l\'ID: ' . $id);
                return response()->json([
                    'error' => 'Fichier de démission introuvable',
                    'details' => 'Chemin recherché: ' . $filePath
                ], 404);
            }

            Log::info('Téléchargement du fichier démission à: ' . $fullPath);
            return response()->download($fullPath, basename($filePath));
        } catch (\Exception $e) {
            Log::error('Erreur dans downloadDemission: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

public function resigned(Request $request)
{
    if ($request->ajax()) {
        $demissions = Demission::with('salarie', 'preavis')
            ->get()
            ->map(function ($demission) {
                $salarie = $demission->salarie;
                $preavis = $demission->preavis;

                return [
                    'id' => $demission->id,
                    'salarie' => [
                        'id' => $salarie->id,
                        'nom' => $salarie->nom,
                        'prenom' => $salarie->prenom,
                        'cin' => $salarie->cin,
                        'n_matricule_entreprise' => $salarie->n_matricule_entreprise,
                    ],
                    'date_embauche' => $salarie->date_embauche,
                    'date_demission' => $demission->date_demission,
                    'motif' => $demission->motif,
                    'document_path' => $demission->document_path,
                    'download_link' => $demission->document_path ? url('/download/demission/' . $demission->id) : null,
                    'docPreavis' => $demission->docPreavis, // Ajout du chemin du docPreavis
                    'preavis_download_link' => $demission->docPreavis ? url('/download/preavis/' . $demission->id) : null, // Lien pour docPreavis
                    'preavis' => $preavis ? [
                        'document_path' => $preavis->document_path,
                        'download_link' => $preavis->document_path ? url('/download/preavis/' . $preavis->id) : null,
                    ] : null,
                ];
            });

        return response()->json($demissions);
    }

    return view('salaries.resigned');
}


 public function updateDemission(Request $request, $id)
{
    try {
        $demission = Demission::findOrFail($id);
        $salarie = Salarie::findOrFail($demission->salarie_id);

        $validated = $request->validate([
            'date_demission' => 'required|date',
            'motif' => 'required|string|max:1000',
            'preavis_file' => 'nullable|file|mimes:pdf|max:2048',
            'demission_file' => 'nullable|file|mimes:pdf|max:2048',
        ], [
            'date_demission.required' => 'La date de démission est obligatoire.',
            'date_demission.date' => 'La date de démission doit être une date valide.',
            'motif.required' => 'Le motif est obligatoire.',
            'motif.max' => 'Le motif ne doit pas dépasser 1000 caractères.',
            'preavis_file.mimes' => 'Le fichier de préavis doit être un PDF.',
            'preavis_file.max' => 'Le fichier de préavis ne doit pas dépasser 2 Mo.',
            'demission_file.mimes' => 'Le fichier de démission doit être un PDF.',
            'demission_file.max' => 'Le fichier de démission ne doit pas dépasser 2 Mo.',
        ]);

        Log::info('Validated data for updateDemission:', $validated);

        DB::beginTransaction();

        // Update demission details
        $demission->update([
            'date_demission' => $validated['date_demission'],
            'motif' => $validated['motif'],
        ]);

        // Handle Preavis file
        if ($request->hasFile('preavis_file')) {
            $preavis = Preavis::where('salarie_id', $salarie->id)->first();
            $existingPreavisPath = $preavis ? $preavis->document_path : null;
            $existingDocPreavisPath = $demission->docPreavis;

            // Delete old files if they exist
            if ($existingPreavisPath && file_exists(public_path($existingPreavisPath))) {
                unlink(public_path($existingPreavisPath));
                Log::info('Deleted old preavis file:', ['path' => $existingPreavisPath]);
            }
            if ($existingDocPreavisPath && file_exists(public_path($existingDocPreavisPath))) {
                unlink(public_path($existingDocPreavisPath));
                Log::info('Deleted old docPreavis file:', ['path' => $existingDocPreavisPath]);
            }

            // Upload new file
            $newPreavisPath = $this->handleFileUpload(
                $request->file('preavis_file'),
                $salarie,
                'demission'
            );

            // Update or create Preavis record
            if (!$preavis) {
                $preavis = Preavis::create([
                    'salarie_id' => $salarie->id,
                    'document_path' => $newPreavisPath,
                    'created_by' => Auth::id(),
                ]);
            } else {
                $preavis->update(['document_path' => $newPreavisPath]);
            }

            // Update docPreavis in Demission
            $demission->update([
                'docPreavis' => $newPreavisPath,
                'preavis_id' => $preavis->id,
            ]);

            Log::info('Preavis file updated:', [
                'preavis_path' => $newPreavisPath,
                'docPreavis_path' => $newPreavisPath,
                'preavis_id' => $preavis->id,
            ]);
        }

        // Handle Demission file
        if ($request->hasFile('demission_file')) {
            $existingDemissionPath = $demission->document_path;

            // Delete old file if it exists
            if ($existingDemissionPath && file_exists(public_path($existingDemissionPath))) {
                unlink(public_path($existingDemissionPath));
                Log::info('Deleted old demission file:', ['path' => $existingDemissionPath]);
            }

            // Upload new file
            $newDemissionPath = $this->handleFileUpload(
                $request->file('demission_file'),
                $salarie,
                'demission'
            );

            // Update demission record
            $demission->update(['document_path' => $newDemissionPath]);

            Log::info('Demission file updated:', [
                'path' => $newDemissionPath,
                'demission_id' => $demission->id,
            ]);
        }

        DB::commit();

        // Prepare response data to match resigned endpoint
        $responseData = [
            'id' => $demission->id,
            'salarie' => [
                'id' => $salarie->id,
                'nom' => $salarie->nom,
                'prenom' => $salarie->prenom,
                'cin' => $salarie->cin,
                'n_matricule_entreprise' => $salarie->n_matricule_entreprise,
            ],
            'date_embauche' => $salarie->date_embauche,
            'date_demission' => $demission->date_demission,
            'motif' => $demission->motif,
            'document_path' => $demission->document_path,
            'download_link' => $demission->document_path ? url('/download/demission/' . $demission->id) : null,
            'docPreavis' => $demission->docPreavis,
            'preavis_download_link' => $demission->docPreavis ? url('/download/preavis/' . $demission->id) : null,
            'preavis' => $demission->preavis ? [
                'document_path' => $demission->preavis->document_path,
                'download_link' => $demission->preavis->document_path ? url('/download/preavis/' . $demission->preavis->id) : null,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Démission mise à jour avec succès.',
            'data' => $responseData,
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation error in updateDemission:', $e->errors());
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error in updateDemission:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur: ' . $e->getMessage(),
        ], 500);
    }
}
public function destroyDemission($id)
{
    try {
        $demission = Demission::findOrFail($id);
        $salarie = Salarie::findOrFail($demission->salarie_id);

        // Delete demission file if exists
        if ($demission->document_path && file_exists(public_path($demission->document_path))) {
            unlink(public_path($demission->document_path));
            Log::info('Deleted demission file', ['path' => $demission->document_path]);
        }

        // Delete docPreavis file if exists
        if ($demission->docPreavis && file_exists(public_path($demission->docPreavis))) {
            unlink(public_path($demission->docPreavis));
            Log::info('Deleted docPreavis file', ['path' => $demission->docPreavis]);
        }

        // Delete associated preavis if exists
        if ($demission->preavis_id) {
            $preavis = Preavis::find($demission->preavis_id);
            if ($preavis) {
                if ($preavis->document_path && file_exists(public_path($preavis->document_path))) {
                    unlink(public_path($preavis->document_path));
                    Log::info('Deleted preavis file', ['path' => $preavis->document_path]);
                }
                $preavis->delete();
                Log::info('Deleted preavis record', ['id' => $preavis->id]);
            }
        }

        // Delete the demission record
        $demission->delete();
        Log::info('Deleted demission record', ['id' => $id]);

        // Set salarie status back to 'actif'
        $salarie->update(['statut' => 'actif']);
        Log::info('Updated salarie status to actif', ['salarie_id' => $salarie->id]);

        return response()->json([
            'success' => true,
            'message' => 'Démission supprimée avec succès. Le statut du salarié est maintenant actif.'
        ]);
    } catch (\Exception $e) {
        Log::error('Error deleting demission', [
            'id' => $id,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression de la démission : ' . $e->getMessage()
        ], 500);
    }
}
}