<?php



namespace App\Http\Controllers;

use App\Models\CompanySettings;
use App\Models\Cotisations;
use App\Models\FraisProfessionnel;
use App\Models\AncienneteTaux;
use App\Models\CompanyDocuments;
use App\Models\ImpotSurRevenu;
use App\Models\JourFerie;
use App\Models\Salarie;
use App\Models\Vehicle;
use App\Models\HeuresSupp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class ParametreController extends Controller
{
    public function index()
    {
        $cotisations = Cotisations::first();
        $fraisPros = FraisProfessionnel::all();
        $impotRevenus = ImpotSurRevenu::all();
        $companySettings = CompanySettings::first();
        $ancienneteTaux = AncienneteTaux::all();
        $companyDocuments = CompanyDocuments::first();
        $jourFeries = JourFerie::all();
        $heuresSupp = HeuresSupp::all(); // Récupère les données de la table heuressupp

        if (!$companySettings || empty($companySettings->email)) {
            session(['show_company_settings_modal' => true]);
        }

        return view('parametres.index', compact('cotisations', 'fraisPros', 'impotRevenus', 'companySettings', 'ancienneteTaux', 'companyDocuments', 'jourFeries', 'heuresSupp'));
    }

     public function storeAncienneteTaux(Request $request)
    {
        $request->validate([
            'an_min' => 'required|integer|min:0',
            'an_max' => 'nullable|integer|min:0|gt:an_min',
            'taux' => 'required|numeric|min:0',
        ]);

        AncienneteTaux::create([
            'an_min' => $request->an_min,
            'an_max' => $request->an_max,
            'taux' => $request->taux,
        ]);

        return redirect()->back()->with('success', 'Barème d\'ancienneté ajouté avec succès.');
    }
public function destroyAncienneteTaux($id)
{
    try {
        $anciennete = AncienneteTaux::findOrFail($id);
        $anciennete->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barème d\'ancienneté supprimé avec succès.'
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la suppression du barème ancienneté : ' . $e->getMessage(), [
            'id' => $id,
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression du barème.'
        ], 500);
    }
}

    public function updateAncienneteTaux(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'an_min' => 'required|array',
            'an_min.*' => 'required|integer|min:0',
            'an_max' => 'required|array',
            'an_max.*' => 'nullable|integer|min:0',
            'taux' => 'required|array',
            'taux.*' => 'required|numeric|min:0',
        ]);

        foreach ($request->ids as $index => $id) {
            $anciennete = AncienneteTaux::find($id);
            if ($anciennete) {
                $an_max = $request->an_max[$index] !== '' ? $request->an_max[$index] : null;
                if ($an_max !== null && $an_max <= $request->an_min[$index]) {
                    return redirect()->back()->withErrors(['an_max' => "Années Max doit être supérieur à Années Min pour la ligne " . ($index + 1)]);
                }
                $anciennete->update([
                    'an_min' => $request->an_min[$index],
                    'an_max' => $an_max,
                    'taux' => $request->taux[$index],
                ]);
            }
        }

        return redirect()->back()->with('success', 'Barème de l\'ancienneté mis à jour avec succès.');
    }

public function storeHeurs(Request $request)
    {
        $request->validate([
            'secteur' => 'required|string',
            'jour' => 'required|string|max:50',
            'horaire_min' => 'required|numeric|min:0',
            'horaire_max' => 'required|numeric|min:0',
            'jr_ouvrable' => 'required|string',
            'jr_feries' => 'required|string',
            
        ]);

        HeuresSupp::create([
            'secteur' => $request->secteur,
            'jour' => $request->jour,
            'horaire_min' => $request->horaire_min,
            'horaire_max' => $request->horaire_max,
            'jr_ouvrable' => $request->jr_ouvrable,
            'jr_feries' => $request->jr_feries,
            
        ]);

        return redirect()->back()->with('success', 'Données d\'heures supplémentaires ajoutées avec succès.');
    }

    public function destroyHeurs($id)
{
    try {
        $heure = HeuresSupp::findOrFail($id);
        $heure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Donnée d\'heures supplémentaires supprimée avec succès.'
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la suppression des heures supplémentaires : ' . $e->getMessage(), [
            'id' => $id,
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression.'
        ], 500);
    }
}

     public function updateHeurs(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'secteur' => 'required|array',
            'secteur.*' => 'required|string|max:100',
            'jour' => 'required|array',
            'jour.*' => 'required|string|max:50',
            'horaire_min' => 'required|array',
            'horaire_min.*' => 'required|numeric|min:0',
            'horaire_max' => 'required|array',
            'horaire_max.*' => 'required|numeric|min:0',
            'jr_ouvrable' => 'required|array',
            'jr_ouvrable.*' => 'required|string|max:10',
            'jr_feries' => 'required|array',
            'jr_feries.*' => 'required|string|max:10',
         
        ]);

        foreach ($request->ids as $index => $id) {
            $heure = HeuresSupp::find($id);
            if ($heure) {
                if ($request->horaire_max[$index] <= $request->horaire_min[$index]) {
                    return redirect()->back()->withErrors(['horaire_max' => "Horaire Max doit être supérieur à Horaire Min pour la ligne " . ($index + 1)]);
                }
                $heure->update([
                    'secteur' => $request->secteur[$index],
                    'jour' => $request->jour[$index],
                    'horaire_min' => $request->horaire_min[$index],
                    'horaire_max' => $request->horaire_max[$index],
                    'jr_ouvrable' => $request->jr_ouvrable[$index],
                    'jr_feries' => $request->jr_feries[$index],
                     
                ]);
            }
        }

        return redirect()->back()->with('success', 'Données d\'heures supplémentaires mises à jour avec succès.');
    }

        public function updateCotisations(Request $request)
    {
        $request->validate([
            'cnss_pp' => 'nullable|numeric',
            'plafond_cnss' => 'nullable|numeric',
            'amo_pp' => 'nullable|numeric',
            'cnss_ps' => 'nullable|numeric',
            'amo_ps' => 'nullable|numeric',
            /* 'fp_ps' => 'nullable|numeric', */
            'ipe_ps' => 'nullable|numeric',
            'plafond_ipe' => 'nullable|numeric',
            'charge_de_famille' => 'nullable|numeric',
            'taux_CIMR' => 'nullable|numeric',
            'taux_mutuelle' => 'nullable|numeric'
        ]);

        $cotisation = Cotisations::first();
        if ($cotisation) {
            $cotisation->update($request->only([
                'cnss_pp',
                'amo_pp',
                'cnss_ps',
                'amo_ps',
                /* 'fp_ps', */
                'ipe_ps',
                'plafond_ipe',
                'charge_de_famille',
                'plafond_cnss',
                'taux_CIMR',
                'taux_mutuelle'
            ]));
        }

        return redirect()->back()->with('success', 'Cotisations mises à jour avec succès.');
    }

 public function updateImpotRevenus(Request $request)
{
    $request->validate([
        'ids' => 'required|array',
        'revenu_min' => 'required|array',
        'revenu_max' => 'required|array',
        'taux' => 'required|array',
        'somme_a_deduire' => 'required|array',
        'revenu_min.*' => 'nullable|numeric|min:0',
        'revenu_max.*' => 'nullable|numeric|min:0',
        'taux.*' => 'nullable|numeric|min:0|max:100',
        'somme_a_deduire.*' => 'nullable|numeric|min:0',
    ]);

    foreach ($request->ids as $index => $id) {
        $impot = ImpotSurRevenu::find($id);
        if ($impot) {
            $impot->update([
                'revenu_min' => $request->revenu_min[$index],
                'revenu_max' => $request->revenu_max[$index] ?: null, // Convertir chaîne vide en null
                'taux' => $request->taux[$index],
                'somme_a_deduire' => $request->somme_a_deduire[$index]
            ]);
        }
    }

    return redirect()->back()->with('success', 'Impôt sur le revenu mis à jour avec succès.');
}
   public function storeFraisPros(Request $request)
    {
        $request->validate([
            'sbi_min' => 'required|numeric|min:0',
            'sbi_max' => 'nullable|numeric|min:0|gt:sbi_min',
            'taux' => 'required|numeric|min:0',
            'plafond' => 'required|numeric|min:0',
        ]);

        FraisProfessionnel::create([
            'sbi_min' => $request->sbi_min,
            'sbi_max' => $request->sbi_max,
            'taux' => $request->taux,
            'plafond' => $request->plafond,
        ]);

        return redirect()->back()->with('success', 'Barème des frais professionnels ajouté avec succès.');
    }

    public function updateFraisPros(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'sbi_min' => 'required|array',
            'sbi_min.*' => 'required|numeric|min:0',
            'sbi_max' => 'required|array',
            'sbi_max.*' => 'nullable|numeric|min:0',
            'taux' => 'required|array',
            'taux.*' => 'required|numeric|min:0',
            'plafond' => 'required|array',
            'plafond.*' => 'required|numeric|min:0',
        ]);

        foreach ($request->ids as $index => $id) {
            $frais = FraisProfessionnel::find($id);
            if ($frais) {
                $sbi_max = $request->sbi_max[$index] !== '' ? $request->sbi_max[$index] : null;
                if ($sbi_max !== null && $sbi_max <= $request->sbi_min[$index]) {
                    return redirect()->back()->withErrors(['sbi_max' => "SBI Max doit être supérieur à SBI Min pour la ligne " . ($index + 1)]);
                }
                $frais->update([
                    'sbi_min' => $request->sbi_min[$index],
                    'sbi_max' => $sbi_max,
                    'taux' => $request->taux[$index],
                    'plafond' => $request->plafond[$index],
                ]);
            }
        }

        return redirect()->back()->with('success', 'Barème des frais professionnels mis à jour avec succès.');
    }

    public function destroyFraisPros($id)
{
    try {
        $frais = FraisProfessionnel::findOrFail($id);
        $frais->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barème des frais professionnels supprimé avec succès.'
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la suppression du barème frais pro : ' . $e->getMessage(), [
            'id' => $id,
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression du barème.'
        ], 500);
    }
}


public function createSalaryTable(Request $request)
{
    $year = date('Y'); // Récupérer l'année courante
    $tableName = "salairs_{$year}";

    // Vérifier si la table existe déjà
    if (Schema::hasTable($tableName)) {
        return response()->json(['error' => "La table {$tableName} existe déjà."], 400);
    }

    try {
        // Créer la table
        Schema::create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id'); // Clé primaire
            $table->unsignedBigInteger('id_salarie'); // Clé étrangère
            $table->text('janvier')->nullable();
            $table->text('fevrier')->nullable();
            $table->text('mars')->nullable();
            $table->text('avril')->nullable();
            $table->text('mai')->nullable();
            $table->text('juin')->nullable();
            $table->text('juillet')->nullable();
            $table->text('aout')->nullable();
            $table->text('septembre')->nullable();
            $table->text('octobre')->nullable();
            $table->text('novembre')->nullable();
            $table->text('decembre')->nullable();
            $table->timestamps(); // created_at et updated_at
            $table->string('nom', 255)->nullable();
            $table->string('prenom', 255)->nullable();
            $table->string('n_matricule_entreprise', 50)->nullable();
            $table->double('salaire')->default(null);
            $table->double('salaire_journalier')->default(null);
            $table->double('salaire_net')->default(null);

            // Définir la clé étrangère
            $table->foreign('id_salarie')->references('id')->on('salaries')->onDelete('cascade');

            // Ajouter un index sur id_salarie
            $table->index('id_salarie');
        });

        // Récupérer uniquement les salariés actifs (exclure les inactifs/démissionnés)
        $activeSalaries = Salarie::where('statut', 'actif')
            ->where('statut', '!=', 'inactif')
            ->get([
                'id',
                'nom',
                'prenom',
                'n_matricule_entreprise',
                'salaire_base' ,
                'salaire_net',
                'salaire_journalier'
            ]);

        foreach ($activeSalaries as $salarie) {
            DB::table($tableName)->insert([
                'id_salarie' => $salarie->id,
                'nom' => $salarie->nom,
                'prenom' => $salarie->prenom,
                'n_matricule_entreprise' => $salarie->n_matricule_entreprise,
                'salaire' => $salarie->salaire_base ?? null, 
                'salaire_net' => $salarie->salaire_net ?? null,
                'salaire_journalier' => $salarie->salaire_journalier ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => "La table {$tableName} a été créée et peuplée avec succès avec " . $activeSalaries->count() . " salariés actifs."
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la création ou du peuplement de la table : ' . $e->getMessage(), [
            'table' => $tableName,
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'error' => "Erreur lors de la création ou du peuplement de la table : {$e->getMessage()}"
        ], 500);
    }
}



/**
 * Ajouter le contrat de résiliation d'un véhicule
 */
public function addResiliationContract(Request $request, $id)
{
    $vehicle = Vehicle::findOrFail($id);

    $request->validate([
        'resiliation_file'       => 'required|file|mimes:pdf|max:2048',
        'resiliation_expires_at' => 'nullable|date',
    ]);

    try {
        DB::beginTransaction();

        $folderPath = 'documents/vehicles/resiliations';

        // Créer le dossier s'il n'existe pas
        if (!Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->makeDirectory($folderPath, 0755, true);
        }

        // Supprimer l'ancien contrat s'il existe
        if ($vehicle->resiliation_path && Storage::disk('public')->exists($vehicle->resiliation_path)) {
            Storage::disk('public')->delete($vehicle->resiliation_path);
        }

        $file = $request->file('resiliation_file');
        $fileName = "resiliation_{$vehicle->matricule}_" . uniqid() . ".pdf";
        $path = $file->storeAs($folderPath, $fileName, 'public');

        $vehicle->update([
            'resiliation_path'       => $path,
            'resiliation_expires_at' => $request->resiliation_expires_at,
            'resilie'                => 1,   // Marquer comme résilié
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Contrat de résiliation enregistré avec succès.'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur résiliation véhicule : ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement du contrat de résiliation.'
        ], 500);
    }
}


public function generateResiliationPdf($id)
{
    $vehicle         = Vehicle::findOrFail($id);
    $companySettings = CompanySettings::first();

    $insuranceCompany = $companySettings->assuranceCompagnie ?? 'Nom de la compagnie d’assurance';
    $insuranceAddress = $companySettings->adresseAssurance ?? 'Adresse de l\'assureur';

    // === LOGO ===
    $logoPath = null;
    if ($companySettings->logo && Storage::disk('public')->exists($companySettings->logo)) {
        $logoPath = public_path('storage/' . $companySettings->logo);
    }

    // === CACHET (Stamp) ===
    $stampPath = null;
    if ($companySettings->stamp && Storage::disk('public')->exists($companySettings->stamp)) {
        $stampPath = public_path('storage/' . $companySettings->stamp);
    }

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('parametres.resiliation', compact(
        'vehicle', 
        'companySettings', 
        'insuranceCompany', 
        'insuranceAddress',
        'logoPath',      // ← Nouveau
        'stampPath'
    ));

    $pdf->setOption('isRemoteEnabled', true);
    $pdf->setOption('isHtml5ParserEnabled', true);

    return $pdf->download('resiliation-assurance-' . $vehicle->matricule . '.pdf');
}

public function updateCompanySettings(Request $request)
{
    $request->validate([
        'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'stamp' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'email' => 'nullable|email',
        'address' => 'nullable|string',
        'account_number' => 'nullable|string',
        'bank_name' => 'nullable|string',
        'ice' => 'nullable|string',
        'cnss_number' => 'nullable|string',
        'tax_id' => 'nullable|string',
        'commercial_register' => 'nullable|string',
        'patent_number' => 'nullable|string',
        'capital' => 'nullable|numeric',
        'phone_number' => 'nullable|string',
        'nom_etreprise' => 'nullable|string',
        'tva_declaration' => 'nullable|in:mensuelle,trimestrielle',
        'code_cnss' => 'nullable|string', 
        'activite_societe' => 'nullable|string', 
        'gerant' => 'nullable|string',
        'ville' => 'nullable|string',
        'tva' => 'nullable|in:mensuelle,trimestrielle,annuelle',
        'assuranceCompagnie' => 'nullable',
        'adresseAssurance' => 'nullable',
    ]);

    $companySettings = CompanySettings::firstOrCreate([]);

    $data = $request->only([
        'email',
        'address',
        'account_number',
        'bank_name',
        'ice',
        'cnss_number',
        'tax_id',
        'commercial_register',
        'patent_number',
        'capital',
        'phone_number',
        'nom_entreprise',
        'nom_etreprise',
        'code_cnss',
        'activite_societe',
        'gerant',
        'ville',
        'tva',
        'assuranceCompagnie',
        'adresseAssurance'
    ]);

    // Gestion du logo
    if ($request->hasFile('logo')) {
        if ($companySettings->logo) {
            Storage::delete('public/' . $companySettings->logo);
        }
        $path = $request->file('logo')->store('logos', 'public');
        $data['logo'] = $path;
    }

    // Gestion du cachet
    if ($request->hasFile('stamp')) {
        if ($companySettings->stamp) {
            Storage::delete('public/' . $companySettings->stamp);
        }
        $path = $request->file('stamp')->store('stamps', 'public');
        $data['stamp'] = $path;
    }

    $companySettings->update($data);

    // Clear the session flag
    session()->forget('show_company_settings_modal');

    return redirect()->route('welcome')->with('success', 'Paramètres de la société enregistrés avec succès.');
}

public function storeCompanyDocuments(Request $request)
{
    $request->validate([
        'attestation_regularite_fiscale' => 'nullable|file|mimes:pdf|max:2048',
        'attestation_regularite_fiscale_expires_at' => 'nullable|date|after:today',
        'attestation_cnss' => 'nullable|file|mimes:pdf|max:2048',
        'attestation_cnss_expires_at' => 'nullable|date|after:today',
        'attestation_soumission_marche' => 'nullable|file|mimes:pdf|max:2048',
        'attestation_soumission_marche_expires_at' => 'nullable|date|after:today',
        'assurance_accident_travail' => 'nullable|file|mimes:pdf|max:2048',
        'assurance_accident_travail_expires_at' => 'nullable|date|after:today',
        'assurance_responsabilite_civile' => 'nullable|file|mimes:pdf|max:2048',
        'assurance_responsabilite_civile_expires_at' => 'nullable|date|after:today',
        'modele_rc_7' => 'nullable|file|mimes:pdf|max:2048',
        'modele_rc_7_expires_at' => 'nullable|date|after:today',
        'modele_rc_9' => 'nullable|file|mimes:pdf|max:2048',
        'modele_rc_9_expires_at' => 'nullable|date|after:today',
        'signature_electronic'          => 'nullable|string|max:255',
        'date_exp_signature'            => 'nullable|date|after:today',
    ], [
        'mimes' => 'Le fichier :attribute doit être un PDF.',
        'max' => 'Le fichier :attribute ne doit pas dépasser 2 Mo.',
        'date' => 'La date d\'expiration :attribute doit être valide et postérieure à aujourd\'hui.',
        'string' => 'Le champ :attribute doit être du texte.',
        'max'    => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ]);

    try {
        DB::beginTransaction();
        $folderPath = 'documents/company';
        $data = [
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        if (!Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->makeDirectory($folderPath, 0755, true);
        }

        $singleFileFields = [
            'attestation_regularite_fiscale',
            'attestation_cnss',
            'attestation_soumission_marche',
            'assurance_accident_travail',
            'assurance_responsabilite_civile',
            'modele_rc_7',
            'modele_rc_9',
            'signature_electronic',
        ];

        foreach ($singleFileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                if ($file->isValid()) {
                    $fileName = "{$field}_" . uniqid() . ".pdf";
                    $data[$field] = $file->storeAs($folderPath, $fileName, 'public');
                    $data["{$field}_expires_at"] = $request->input("{$field}_expires_at");
                    Log::info("Uploaded file for {$field}: {$data[$field]}");
                } else {
                    Log::warning("Invalid file upload for {$field}");
                }
            }
        }

        CompanyDocuments::create($data);

        DB::commit();
        return response()->json(['message' => 'Documents de la société enregistrés avec succès.']);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error storing company documents: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Erreur lors de l\'enregistrement des documents.', 'error' => $e->getMessage()], 500);
    }
}


public function updateCompanyDocuments(Request $request)
{
    Log::info('Request data for updateCompanyDocuments:', $request->all());

    $request->validate([
        'attestation_regularite_fiscale' => 'nullable|file|mimes:pdf|max:2048',
        'attestation_regularite_fiscale_expires_at' => 'nullable|date|after:today',
        'attestation_cnss' => 'nullable|file|mimes:pdf|max:2048',
        'attestation_cnss_expires_at' => 'nullable|date|after:today',
        'attestation_soumission_marche' => 'nullable|file|mimes:pdf|max:2048',
        'attestation_soumission_marche_expires_at' => 'nullable|date|after:today',
        'assurance_accident_travail' => 'nullable|file|mimes:pdf|max:2048',
        'assurance_accident_travail_expires_at' => 'nullable|date|after:today',
        'assurance_responsabilite_civile' => 'nullable|file|mimes:pdf|max:2048',
        'assurance_responsabilite_civile_expires_at' => 'nullable|date|after:today',
        'modele_rc_7' => 'nullable|file|mimes:pdf|max:2048',
        'modele_rc_7_expires_at' => 'nullable|date|after:today',
        'modele_rc_9' => 'nullable|file|mimes:pdf|max:2048',
        'modele_rc_9_expires_at' => 'nullable|date|after:today',
        'signature_electronic'   => 'nullable|string|max:255',
        'date_exp_signature'     => 'nullable|date|after:today',
    ], [
        'mimes' => 'Le fichier :attribute doit être un PDF.',
        'max' => 'Le fichier :attribute ne doit pas dépasser 2 Mo.',
        'date' => 'La date d\'expiration :attribute doit être valide et postérieure à aujourd\'hui.',
        'string' => 'Le champ :attribute doit être du texte.',
        'max'    => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ]);

    try {
        DB::beginTransaction();
        $folderPath = 'documents/company';
        $data = ['updated_by' => Auth::id()];
        $companyDocuments = CompanyDocuments::first();

        if (!$companyDocuments) {
            return response()->json(['message' => 'Aucun enregistrement de documents à mettre à jour.'], 404);
        }

        if (!Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->makeDirectory($folderPath, 0755, true);
        }

        $singleFileFields = [
            'attestation_regularite_fiscale',
            'attestation_cnss',
            'attestation_soumission_marche',
            'assurance_accident_travail',
            'assurance_responsabilite_civile',
            'modele_rc_7',
            'modele_rc_9',
        ];

        foreach ($singleFileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                if ($file->isValid()) {
                    if ($companyDocuments->$field && Storage::disk('public')->exists($companyDocuments->$field)) {
                        Storage::disk('public')->delete($companyDocuments->$field);
                        Log::info("Deleted old file for {$field}: {$companyDocuments->$field}");
                    }
                    $fileName = "{$field}_" . uniqid() . ".pdf";
                    $path = $file->storeAs($folderPath, $fileName, 'public');
                    $data[$field] = $path;
                    $data["{$field}_expires_at"] = $request->input("{$field}_expires_at");
                    Log::info("Updated {$field}: {$path}, expires_at: {$data["{$field}_expires_at"]}");
                } else {
                    Log::error("Invalid file uploaded for {$field}");
                    throw new \Exception("Le fichier pour {$field} n'est pas valide.");
                }
            } else {
                $data[$field] = $companyDocuments->$field;
                $expiresAt = $request->input("{$field}_expires_at");
                $data["{$field}_expires_at"] = $expiresAt ?? $companyDocuments->{"{$field}_expires_at"};
                Log::info("Retained {$field}, updated expires_at to: {$data["{$field}_expires_at"]}");
            }
        }

        $companyDocuments->update($data);
        Log::info('Updated company documents:', $data);

        DB::commit();
        return response()->json(['message' => 'Documents de la société mis à jour avec succès.']);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur lors de la mise à jour des documents: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Erreur lors de la mise à jour des documents.', 'error' => $e->getMessage()], 500);
    }
}
public function getCompanyDocuments()
{
    try {
        $companyDocuments = CompanyDocuments::first();
        $documents = [];

        if ($companyDocuments) {
            $singleFileFields = [
                'attestation_regularite_fiscale' => 'Attestation de Régularité Fiscale',
                'attestation_cnss' => 'Attestation CNSS',
                'attestation_soumission_marche' => 'Attestation de Soumission Marché',
                'assurance_accident_travail' => 'Assurance Accident de Travail',
                'assurance_responsabilite_civile' => 'Assurance Responsabilité Civile',
                'modele_rc_7' => 'Modèle RC 7',
                'modele_rc_9' => 'Modèle RC 9',
            ];

            foreach ($singleFileFields as $field => $label) {
                if ($companyDocuments->$field && Storage::disk('public')->exists($companyDocuments->$field)) {
                    $documents[] = [
                        'label' => $label,
                        'path' => Storage::url($companyDocuments->$field),
                        'file_name' => basename($companyDocuments->$field),
                        'expires_at' => $companyDocuments->{"{$field}_expires_at"} ? $companyDocuments->{"{$field}_expires_at"}->format('Y-m-d') : null,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => ['documents' => $documents],
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la récupération des documents: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['success' => false, 'message' => 'Erreur lors de la récupération des documents.'], 500);
    }
}
public function addAdditionalDocument(Request $request)
{
            $request->validate([
                'vehicle_index' => 'required|integer|min:0',
                'file' => 'required|file|mimes:pdf|max:2048',
                'expires_at' => 'required|date|after:today',
                'document_type' => 'required|in:technical_inspection,registration_certificate,purchase_contract,custom_document',
                'custom_document_label' => 'nullable|string|max:255|required_if:document_type,custom_document',
            ], [
                'file.required' => 'Le fichier est requis.',
                'mimes' => 'Le fichier doit être un PDF.',
                'max' => 'Le fichier ne doit pas dépasser 2 Mo.',
                'expires_at.required' => 'La date d\'expiration est requise.',
                'date' => 'La date d\'expiration doit être valide et postérieure à aujourd\'hui.',
                'document_type.required' => 'Le type de document est requis.',
                'custom_document_label.required_if' => 'Le label est requis pour un document personnalisé.',
            ]);

            try {
                DB::beginTransaction();
                $folderPath = 'documents/company';
                $companyDocuments = CompanyDocuments::firstOrCreate([]);

                if (!Storage::disk('public')->exists($folderPath)) {
                    Storage::disk('public')->makeDirectory($folderPath, 0755, true);
                }

                $file = $request->file('file');
                if ($file->isValid()) {
                    $fileName = "{$request->document_type}_vehicle_{$request->vehicle_index}_" . uniqid() . ".pdf";
                    $path = $file->storeAs($folderPath, $fileName, 'public');
                    $expiresAt = $request->input('expires_at');

                    // Retrieve existing vehicle insurances
                    $vehicleInsurances = $companyDocuments->assurance_vehicule ? json_decode($companyDocuments->assurance_vehicule, true) : [];
                    $vehicleIndex = $request->vehicle_index;

                    // Ensure the vehicle index exists
                    if (!isset($vehicleInsurances[$vehicleIndex])) {
                        throw new \Exception('L\'index du véhicule est invalide.');
                    }

                    // Add additional document to the vehicle
                $vehicleInsurances[$vehicleIndex]['additional_documents'][] = [
            'type' => $request->document_type,
            'path' => $path, // e.g., documents/company/technical_inspection_vehicle_0_abc123.pdf
            'file_name' => $fileName,
            'expires_at' => $expiresAt,
                    'custom_document_label' => $request->document_type === 'custom_document' ? $request->custom_document_label : null,
                ];

                    $companyDocuments->update(['assurance_vehicule' => json_encode($vehicleInsurances), 'updated_by' => Auth::id()]);
                } else {
                    throw new \Exception('Le fichier téléchargé n\'est pas valide.');
                }

                DB::commit();
                return response()->json(['message' => 'Document supplémentaire ajouté avec succès.', 'success' => true]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erreur lors de l\'ajout du document supplémentaire: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return response()->json(['message' => 'Erreur lors de l\'ajout du document supplémentaire.', 'error' => $e->getMessage()], 500);
            }
}


public function storeVehicle(Request $request)
{
    $request->validate([
        'matricule' => 'required|string|max:50|unique:vehicules,matricule',
        'assurance_file' => 'nullable|file|mimes:pdf|max:5048',
        'assurance_expires_at' => 'nullable|date|after:today',
        'visite_technique_file' => 'nullable|file|mimes:pdf|max:5048',
        'visite_technique_expires_at' => 'nullable|date|after:today',
        'carte_grise_file' => 'nullable|file|mimes:pdf|max:5048',
        'carte_grise_expires_at' => 'nullable|date|after:today',
        'contrat_achat_file' => 'nullable|file|mimes:pdf|max:5048',
        'contrat_achat_expires_at' => 'nullable|date|after:today',
        'marque'    => 'string|max:100',
        'type'    => 'required|string|max:100',  

        'vignette_path'       => 'nullable|file|mimes:pdf|max:2048',
            'vignette_expires_at' => 'nullable|date|after:today',
    ], [
        'matricule.required' => 'Le matricule est requis.',
        
        'matricule.unique' => 'Ce matricule est déjà utilisé.',
        'assurance_file.mimes' => 'Le fichier d\'assurance doit être un PDF.',
        'assurance_file.max' => 'Le fichier d\'assurance ne doit pas dépasser 5 Mo.',
        'assurance_expires_at.after' => 'La date d\'expiration de l\'assurance doit être postérieure à aujourd\'hui.',
        // Ajoutez d'autres messages d'erreur si nécessaire
    ]);

    try {
        DB::beginTransaction();
        $folderPath = 'documents/vehicles';
        $data = [
            'matricule' => $request->matricule,
            'marque'    => $request->marque,
             'type'    => $request->type, 
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        if (!Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->makeDirectory($folderPath, 0755, true);
        }

        $fileFields = [
            'assurance' => 'assurance_file',
            'visite_technique' => 'visite_technique_file',
            'carte_grise' => 'carte_grise_file',
            'contrat_achat' => 'contrat_achat_file',
            'vignette' => 'vignette_path',
        ];

        foreach ($fileFields as $field => $inputName) {
            if ($request->hasFile($inputName)) {
                $file = $request->file($inputName);
                if ($file->isValid()) {
                    $fileName = "{$field}_{$request->matricule}_" . uniqid() . ".pdf";
                    $data["{$field}_path"] = $file->storeAs($folderPath, $fileName, 'public');
                    $data["{$field}_expires_at"] = $request->input("{$field}_expires_at");
                    Log::info("Uploaded file for {$field}: {$data["{$field}_path"]}");
                } else {
                    throw new \Exception("Le fichier {$field} n'est pas valide.");
                }
            } elseif ($request->input("{$field}_expires_at")) {
                // Enregistrer la date d'expiration même sans fichier
                $data["{$field}_expires_at"] = $request->input("{$field}_expires_at");
            }
        }

        Vehicle::create($data);

        DB::commit();
        return response()->json(['message' => 'Véhicule ajouté avec succès.']);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error storing vehicle: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'message' => 'Erreur lors de l\'ajout du véhicule.',
            'error' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ], 500);
    }
}

public function updateVehicle(Request $request, $id)
{
    $vehicle = Vehicle::findOrFail($id);

    $request->validate([
        'matricule' => 'required|string|max:50|unique:vehicules,matricule,' . $vehicle->id,
        'marque'    => 'required|string|max:100',                    // ← ajouté / rendu obligatoire
        'type'    => 'string', 
        'assurance_file' => 'nullable|file|mimes:pdf',
        'assurance_expires_at' => 'nullable|date|after:today',
        'visite_technique_file' => 'nullable|file|mimes:pdf|max:2048',
        'visite_technique_expires_at' => 'nullable|date|after:today',
        'carte_grise_file' => 'nullable|file|mimes:pdf|max:2048',
        'carte_grise_expires_at' => 'nullable|date|after:today',
        'contrat_achat_file' => 'nullable|file|mimes:pdf|max:2048',
        'contrat_achat_expires_at' => 'nullable|date|after:today',
        'autres_documents.*' => 'nullable|file|mimes:pdf|max:2048',
        'autres_documents_expires_at.*' => 'nullable|date|after:today',
        'custom_document_label.*' => 'nullable|string|max:255',
    ]);

    try {
        DB::beginTransaction();

        $data = [
            'matricule' => $request->matricule,
            'marque'    => $request->marque,
            'type'    => $request->type,           
            'updated_by' => Auth::id(),
        ];

        if (!Storage::disk('public')->exists('documents/vehicles')) {
            Storage::disk('public')->makeDirectory('documents/vehicles', 0755, true);
        }

        $fileFields = [
            'assurance' => 'assurance_file',
            'visite_technique' => 'visite_technique_file',
            'carte_grise' => 'carte_grise_file',
            'contrat_achat' => 'contrat_achat_file',
        ];

        foreach ($fileFields as $field => $inputName) {
            if ($request->hasFile($inputName)) {
                $file = $request->file($inputName);
                if ($file->isValid()) {
                    if ($vehicle->{"{$field}_path"} && Storage::disk('public')->exists($vehicle->{"{$field}_path"})) {
                        Storage::disk('public')->delete($vehicle->{"{$field}_path"});
                    }
                    $fileName = "{$field}_{$vehicle->matricule}_" . uniqid() . ".pdf";
                    $data["{$field}_path"] = $file->storeAs('documents/vehicles', $fileName, 'public');
                    $data["{$field}_expires_at"] = $request->input("{$field}_expires_at");
                    Log::info("Updated file for {$field}: {$data["{$field}_path"]}");
                } else {
                    throw new \Exception("Le fichier {$field} n'est pas valide.");
                }
            } elseif ($request->input("{$field}_expires_at") !== null) {
                $data["{$field}_expires_at"] = $request->input("{$field}_expires_at");
            }
        }

        // Gestion des documents supplémentaires (inchangé)
        $autresDocuments = $vehicle->autres_documents ?? [];
        if ($request->hasFile('autres_documents')) {
            $expiresAtArray = $request->input('autres_documents_expires_at', []);
            $labels = $request->input('custom_document_label', []);

            foreach ($request->file('autres_documents') as $index => $file) {
                if ($file->isValid()) {
                    $fileName = "autres_{$vehicle->matricule}_" . uniqid() . ".pdf";
                    $path = $file->storeAs('documents/vehicles', $fileName, 'public');
                    $autresDocuments[] = [
                        'type' => 'custom_document',
                        'path' => $path,
                        'file_name' => $fileName,
                        'expires_at' => $expiresAtArray[$index] ?? null,
                        'custom_document_label' => $labels[$index] ?? 'Document supplémentaire',
                    ];
                }
            }
        }
        $data['autres_documents'] = $autresDocuments;

        Log::info('Updating vehicle with data', [
            'vehicle_id' => $vehicle->id,
            'data' => $data,
        ]);

        $vehicle->update($data);
        Log::info('Vehicle updated:', $vehicle->toArray());

        DB::commit();
        return response()->json(['message' => 'Véhicule mis à jour avec succès.']);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating vehicle: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'message' => 'Erreur lors de la mise à jour du véhicule.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function destroyVehicle($id)
{
    try {
        $vehicle = Vehicle::findOrFail($id);

        // Optionnel : supprimer les fichiers physiques
        $fields = ['assurance_path', 'visite_technique_path', 'carte_grise_path', 'contrat_achat_path'];
        
        foreach ($fields as $field) {
            if ($vehicle->$field && Storage::disk('public')->exists($vehicle->$field)) {
                Storage::disk('public')->delete($vehicle->$field);
            }
        }

        // Supprimer les documents supplémentaires si stockés dans json
        if ($vehicle->autres_documents && is_array($vehicle->autres_documents)) {
            foreach ($vehicle->autres_documents as $doc) {
                if (isset($doc['path']) && Storage::disk('public')->exists($doc['path'])) {
                    Storage::disk('public')->delete($doc['path']);
                }
            }
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Véhicule supprimé avec succès.'
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur suppression véhicule : ' . $e->getMessage(), [
            'id'    => $id,
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Impossible de supprimer le véhicule.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

public function getVehicles(Request $request)
{
    try {
        // Nombre d'éléments par page (par défaut 10, modifiable via ?per_page=XX)
        $perPage = $request->query('per_page', 9);
        $search  = $request->query('search', '');
        $query = Vehicle::query()->orderBy('matricule', 'asc');
         if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('marque',    'like', "%{$search}%")
                  ->orWhere('type',      'like', "%{$search}%");
            });
        }

        // Requête paginée (trié par matricule, tu peux changer par marque si tu veux)
       $vehicles = $query->paginate($perPage);
          /*   ->orderBy('matricule', 'asc')  // ou 'marque' si tu préfères
            ->paginate($perPage); */

        // Transformation des données (exactement comme avant)
        $data = $vehicles->map(function ($vehicle) {
            $autresDocuments = is_array($vehicle->autres_documents) 
                ? array_map(function ($file) {
                    $path = is_string($file) ? $file : ($file['path'] ?? null);
                    if (!$path || !Storage::disk('public')->exists($path)) {
                        return null;
                    }
                    return [
                        'type'                  => $file['type'] ?? 'custom_document',
                        'path'                  => Storage::url($path),
                        'file_name'             => basename($path),
                        'expires_at'            => isset($file['expires_at']) 
                            ? \Carbon\Carbon::parse($file['expires_at'])->format('Y-m-d') 
                            : null,
                        'custom_document_label' => $file['custom_document_label'] ?? basename($path),
                    ];
                }, $vehicle->autres_documents) 
                : [];

            $autresDocuments = array_filter($autresDocuments);

            return [
                'id'              => $vehicle->id,
                'matricule'       => $vehicle->matricule,
                'marque'          => $vehicle->marque ?? '—', // ← ajouté pour l'affichage dans le tableau
                'type'          => $vehicle->type ?? '—', 
                'resilie'               => (bool) $vehicle->resilie,
                'assurance'       => $vehicle->assurance_path && Storage::disk('public')->exists($vehicle->assurance_path) 
                    ? [
                        'path'      => Storage::url($vehicle->assurance_path),
                        'file_name' => basename($vehicle->assurance_path),
                        'expires_at'=> $vehicle->assurance_expires_at 
                            ? $vehicle->assurance_expires_at->format('Y-m-d') 
                            : null,
                      ] 
                    : ($vehicle->assurance_expires_at 
                        ? ['expires_at' => $vehicle->assurance_expires_at->format('Y-m-d')] 
                        : null),

                'visite_technique'=> $vehicle->visite_technique_path && Storage::disk('public')->exists($vehicle->visite_technique_path) 
                    ? [
                        'path'      => Storage::url($vehicle->visite_technique_path),
                        'file_name' => basename($vehicle->visite_technique_path),
                        'expires_at'=> $vehicle->visite_technique_expires_at 
                            ? $vehicle->visite_technique_expires_at->format('Y-m-d') 
                            : null,
                      ] 
                    : ($vehicle->visite_technique_expires_at 
                        ? ['expires_at' => $vehicle->visite_technique_expires_at->format('Y-m-d')] 
                        : null),

                'carte_grise'     => $vehicle->carte_grise_path && Storage::disk('public')->exists($vehicle->carte_grise_path) 
                    ? [
                        'path'      => Storage::url($vehicle->carte_grise_path),
                        'file_name' => basename($vehicle->carte_grise_path),
                        'expires_at'=> $vehicle->carte_grise_expires_at 
                            ? $vehicle->carte_grise_expires_at->format('Y-m-d') 
                            : null,
                      ] 
                    : ($vehicle->carte_grise_expires_at 
                        ? ['expires_at' => $vehicle->carte_grise_expires_at->format('Y-m-d')] 
                        : null),

                    'contrat_achat'   => $vehicle->contrat_achat_path && Storage::disk('public')->exists($vehicle->contrat_achat_path) 
                        ? [
                            'path'      => Storage::url($vehicle->contrat_achat_path),
                            'file_name' => basename($vehicle->contrat_achat_path),
                            'expires_at'=> $vehicle->contrat_achat_expires_at 
                                ? $vehicle->contrat_achat_expires_at->format('Y-m-d') 
                                : null,
                        ] 
                        : ($vehicle->contrat_achat_expires_at 
                            ? ['expires_at' => $vehicle->contrat_achat_expires_at->format('Y-m-d')] 
                            : null),

                    'autres_documents' => array_values($autresDocuments),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicles'   => $data,
                    'pagination' => [
                        'current_page' => $vehicles->currentPage(),
                        'last_page'    => $vehicles->lastPage(),
                        'per_page'     => $vehicles->perPage(),
                        'total'        => $vehicles->total(),
                        'from'         => $vehicles->firstItem(),
                        'to'           => $vehicles->lastItem(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving vehicles: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des véhicules.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
}

// Méthode helper pour factoriser le code
private function formatDocument($vehicle, $field)
{
    $pathKey  = "{$field}_path";
    $dateKey  = "{$field}_expires_at";

    if ($vehicle->$pathKey && Storage::disk('public')->exists($vehicle->$pathKey)) {
        return [
            'path'      => Storage::url($vehicle->$pathKey),
            'file_name' => basename($vehicle->$pathKey),
            'expires_at'=> $vehicle->$dateKey ? $vehicle->$dateKey->format('Y-m-d') : null,
        ];
    }

    return $vehicle->$dateKey ? ['expires_at' => $vehicle->$dateKey->format('Y-m-d')] : null;
}

public function addAdditionalVehicleDocument(Request $request, $id)
{
    $vehicle = Vehicle::findOrFail($id);

    $request->validate([
        'file' => 'nullable|file|mimes:pdf|max:2048',
        'expires_at' => 'nullable|date|after:today',
        'document_type' => 'required|in:technical_inspection,registration_certificate,purchase_contract,custom_document',
        'custom_document_label' => 'nullable|string|max:255|required_if:document_type,custom_document',
    ], [
        'file.mimes' => 'Le fichier doit être un PDF.',
        'file.max' => 'Le fichier ne doit pas dépasser 2 Mo.',
        'expires_at.after' => 'La date d\'expiration doit être postérieure à aujourd\'hui.',
        'document_type.required' => 'Le type de document est requis.',
        'custom_document_label.required_if' => 'Le label est requis pour un document personnalisé.',
    ]);

    try {
        DB::beginTransaction();
        $folderPath = 'documents/vehicles';

        if (!Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->makeDirectory($folderPath, 0755, true);
        }

        $autresDocuments = $vehicle->autres_documents ?? [];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if ($file->isValid()) {
                $fileName = "{$request->document_type}_{$vehicle->matricule}_" . uniqid() . ".pdf";
                $path = $file->storeAs($folderPath, $fileName, 'public');
                $autresDocuments[] = [
                    'type' => $request->document_type,
                    'path' => $path,
                    'file_name' => $fileName,
                    'expires_at' => $request->input('expires_at'),
                    'custom_document_label' => $request->document_type === 'custom_document' ? $request->custom_document_label : null,
                ];
            } else {
                throw new \Exception('Le fichier téléchargé n\'est pas valide.');
            }
        } elseif ($request->input('expires_at')) {
            $autresDocuments[] = [
                'type' => $request->document_type,
                'path' => null,
                'file_name' => null,
                'expires_at' => $request->input('expires_at'),
                'custom_document_label' => $request->document_type === 'custom_document' ? $request->custom_document_label : null,
            ];
        }

        // Log the data before saving
        Log::info('Updating autres_documents for vehicle', [
            'vehicle_id' => $vehicle->id,
            'autres_documents' => $autresDocuments,
        ]);

        $vehicle->update(['autres_documents' => $autresDocuments]);

        DB::commit();
        return response()->json(['message' => 'Document supplémentaire ajouté avec succès.', 'success' => true]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur lors de l\'ajout du document supplémentaire: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Erreur lors de l\'ajout du document supplémentaire.', 'error' => $e->getMessage()], 500);
    }
}
public function update(Request $request, $id)
{
    try {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->matricule = $request->input('matricule');

        // Handle file uploads and expiration dates
        $documentFields = ['assurance', 'visite_technique', 'carte_grise', 'contrat_achat'];
        foreach ($documentFields as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store('documents/vehicles', 'public');
                $vehicle->{"{$field}_path"} = $path;
                $vehicle->{"{$field}_expires_at"} = $request->input("{$field}_expires_at");
            } elseif ($request->input("{$field}_expires_at")) {
                $vehicle->{"{$field}_expires_at"} = $request->input("{$field}_expires_at");
            }
        }

        // Handle autres_documents (multiple files)
        if ($request->hasFile('autres_documents')) {
            $autresDocs = [];
            foreach ($request->file('autres_documents') as $file) {
                $autresDocs[] = $file->store('documents/vehicles', 'public');
            }
            $vehicle->autres_documents = $autresDocs;
        }

        $vehicle->save();

        Log::info('Vehicle updated:', ['id' => $id, 'data' => $vehicle->toArray()]);
        return response()->json(['success' => true, 'message' => 'Véhicule mis à jour avec succès.']);
    } catch (\Exception $e) {
        Log::error('Error updating vehicle: ' . $e->getMessage(), ['id' => $id, 'trace' => $e->getTraceAsString()]);
        return response()->json(['success' => false, 'message' => 'Erreur lors de la mise à jour.'], 500);
    }
}
public function updateJrsFerie(Request $request)
{
    $request->validate([
        'nom' => 'required|array',
        'date_debut' => 'required|array',
        'date_fin' => 'required|array',
        'nbr_jours' => 'required|array',
        'ids' => 'required|array',
    ]);

    foreach ($request->ids as $index => $id) {
        $jourFerie = JourFerie::find($id);
        if ($jourFerie) {
            $jourFerie->update([
                'nom' => $request->nom[$index],
                'date_debut' => $request->date_debut[$index], 
                'date_fin' => $request->date_fin[$index],
                'nbr_jours' => $request->nbr_jours[$index]
            ]);
        }
    }

     return redirect()->back()->with('success', 'JourFerie mises à jour avec succès.');

}
public function destroyJourFerie($id)
{
    try {
        $jourFerie = JourFerie::findOrFail($id);
        $jourFerie->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jour férié supprimé avec succès.'
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la suppression du jour férié : ' . $e->getMessage(), [
            'id' => $id,
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression.'
        ], 500);
    }
}


}
