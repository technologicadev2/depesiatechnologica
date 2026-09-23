<?php

namespace App\Http\Controllers;

use App\Models\CompanySettings;
use App\Models\Depences;
use App\Models\NatureDepense;
use App\Models\Role;
use App\Models\TypeReglement;
use App\Models\Salarie;
use App\Notifications\DepenseActionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class DepenseController extends Controller
{
    private function getCommonData(Request $request)
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $companySettings = CompanySettings::first();

        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->role && $currentUser->role->name === 'superadmin';

        // Dépenses variées
        $queryVarie = Depences::whereNotIn('nature_depense', ['Avancements de salaires', 'Dépenses de véhicule'])
            ->select(['id', 'date', 'created_by', 'nature_depense', 'description', 'montant', 'reglement_id', 'epreuve', 'code', 'mois_depenses',])
            ->with(['creator:id,username', 'typeReglement:id,designation'])
            ->orderBy('created_at', 'desc');

       $queryAvancements = Depences::where('nature_depense', 'Avancements de salaires')
    ->select(['id', 'date', 'created_by', 'nature_depense', 'salarie', 'salarie_id', 'description', 'montant', 'reglement_id', 'epreuve', 'code', 'mois_depenses','reference'])
    ->with(['employee:id,nom,prenom,n_matricule_entreprise', 'creator:id,username', 'typeReglement:id,designation'])
    // Tri numérique sur la référence (ex: "07/26" -> 726) au lieu d'un tri alphabétique
    ->orderByRaw("CAST(REPLACE(REPLACE(reference, '/', ''), ' ', '') AS UNSIGNED) DESC")
    ->orderBy('id', 'desc');

        // Dépenses véhicule
        $queryVehicle = Depences::where('nature_depense', 'Dépenses de véhicule')
            ->select(['id', 'date', 'created_by', 'nature_depense', 'vehicle_id', 'description', 'montant','heures', 'reglement_id', 'epreuve', 'code', 'mois_depenses', 'type','etat_vidange','etat_plaquettes','etat_pneus','etat_courroie','etat_amortisseur','kilometrage'])
            ->with(['vehicle:id,matricule', 'creator:id,username', 'typeReglement:id,designation','vehicle:id,matricule,typeReglement:id',])
            ->orderBy('date', 'desc');

        // ✅ Filtrer par utilisateur si ce n’est pas un superadmin
        if (!$isSuperAdmin) {
            $queryVarie->where('created_by', $currentUser->id);
            $queryAvancements->where('created_by', $currentUser->id);
            $queryVehicle->where('created_by', $currentUser->id);
        }

        // ✅ Filtrer par date
        if ($dateDebut && $dateFin) {
            $queryVarie->whereBetween('date', [$dateDebut, $dateFin]);            
            $queryAvancements->whereBetween('date', [$dateDebut, $dateFin]);
            $queryVehicle->whereBetween('date', [$dateDebut, $dateFin]);
        }

        // Récupérer les données
        $varieDepenses = $queryVarie->get();
        $avancementsDepenses = $queryAvancements->get();
        $vehicleDepenses = $queryVehicle->get();

        // Calculer les totaux
        $totalVarie = $varieDepenses->sum('montant');
        $totalAvancements = $avancementsDepenses->sum('montant');
        $totalVehicle = $vehicleDepenses->sum('montant');

        // Données communes
        $typeReglements = TypeReglement::all();
        $natureDepenses = NatureDepense::all();
        $salaries = Salarie::select('id', 'n_matricule_entreprise', 'nom', 'prenom')->get();
        $vehicles = \App\Models\Vehicle::select('id', 'matricule','type')->get();
        $avancementNatureId = NatureDepense::where('designation', 'Avancements de salaires')->first()->id ?? null;
        $vehicleNatureId = NatureDepense::where('designation', 'Dépenses de véhicule')->first()->id ?? null;

        return compact(
            'varieDepenses',
            'avancementsDepenses',
            'vehicleDepenses',
            'typeReglements',
            'natureDepenses',
            'salaries',
            'vehicles',  
            'avancementNatureId',
            'vehicleNatureId',
            'dateDebut',
            'dateFin',
            'totalVarie',
            'totalAvancements',
            'totalVehicle',
            'companySettings',
          
        );
    }


    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->getCommonData($request);
            return response()->json([
                'varieDepenses' => $data['varieDepenses']->map(function ($depense) {
                    return [
                        'code' => $depense->code,
                        'date' => $depense->date,
                        'mois_depenses' => $depense->mois_depenses,
                        'nature_depense' => $depense->nature_depense,
                        'description' => $depense->description,
                        'montant' => number_format($depense->montant, 2),
                        'reglement_depense' => $depense->typeReglement->designation ?? 'N/A',
                        'creator' => $depense->creator->username ?? 'N/A',
                        'epreuve' => $depense->epreuve ? asset($depense->epreuve) : null,
                        'id' => $depense->id,
                    ];
                }),
              'avancementsDepenses' => $data['avancementsDepenses']->map(function ($depense) {
    return [
        'code' => $depense->code,
        'date' => $depense->date,
        'mois_depenses' => $depense->mois_depenses,
        'nature_depense' => $depense->nature_depense,
        'salarie' => $depense->salarie ?? 'N/A',
        'employee' => $depense->employee ? $depense->employee->nom . ' ' . $depense->employee->prenom : 'N/A',
        'description' => $depense->description,
        'montant' => number_format($depense->montant, 2),
        'reglement_depense' => $depense->typeReglement->designation ?? 'N/A',
        'creator' => $depense->creator->username ?? 'N/A',
        'epreuve' => $depense->epreuve ? asset($depense->epreuve) : null,
        'id' => $depense->id,
        'reference' => $depense->reference,
    ];
}),
                'vehicleDepenses' => $data['vehicleDepenses']->map(function ($depense) {
                    
                    return [
                        'code' => $depense->code,
                        'date' => $depense->date,
                        'mois_depenses' => $depense->mois_depenses,
                        'vehicle' => $depense->vehicle ? $depense->vehicle->matricule : 'N/A',
                        'vehicle_id'       => $depense->vehicle_id,  
                        'type' => $depense->type ?? 'N/A',
                        'heures'           => $depense->heures,
                        'description' => $depense->description,
                        'montant' => number_format($depense->montant, 2),
                        'kilometrage'       => $depense->kilometrage,
                        'reglement_depense' => $depense->typeReglement->designation ?? 'N/A',
                        'etat_vidange'    => $depense->etat_vidange,
                        'etat_plaquettes' => $depense->etat_plaquettes,
                        'creator' => $depense->creator->username ?? 'N/A',
                        'epreuve' => $depense->epreuve ? asset($depense->epreuve) : null,
                        'etat_pneus'      => $depense->etat_pneus,
                        'etat_courroie'   => $depense->etat_courroie,
                        'etat_amortisseur' => $depense->etat_amortisseur,
                        'id' => $depense->id,
                        
                    ];
                }),
                'totalVarie' => number_format($data['totalVarie'], 2),
                'totalAvancements' => number_format($data['totalAvancements'], 2),
                'totalVehicle' => number_format($data['totalVehicle'], 2),
            ]);
        }

        return redirect()->route('depenses.varie');
    }

    public function varie(Request $request)
    {
        $data = $this->getCommonData($request);
        return view('depenses.varie_depenses', $data);
    }

    public function avancements(Request $request)
    {
        $data = $this->getCommonData($request);
        return view('depenses.avancements_depenses', $data);
    }

private function calculerEtatPlaquettes(int $vehicleId, ?int $valeurActuelle, string $dateActuelle, ?int $depenseIdExclure = null, string $mode = 'kilometrage'): ?int
{
    $champ = $mode === 'heures' ? 'heures' : 'kilometrage';

    if (!$valeurActuelle || $valeurActuelle <= 0) {
        return null;
    }

    // Chercher les dernières plaquettes selon le même champ
    $query = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'Plaquettes de frein')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '<=', $dateActuelle);

    if ($depenseIdExclure) {
        $query->where('id', '!=', $depenseIdExclure);
    }

    $dernieresPlaques = $query
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['id', 'date', $champ]);

    if (!$dernieresPlaques) {
        return null;
    }      

    // Chercher le dernier gasoil (même champ) entre les plaquettes et maintenant
    $dernierGasoil = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'gasoil')
        ->whereNotNull('etat_plaquettes')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '>', $dernieresPlaques->date)
        ->where('date', '<', $dateActuelle);

    if ($depenseIdExclure) {
        $dernierGasoil->where('id', '!=', $depenseIdExclure);
    }

    $dernierGasoil = $dernierGasoil
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['etat_plaquettes', $champ]);

    if ($dernierGasoil && $dernierGasoil->etat_plaquettes !== null) {
        // Accumuler depuis le dernier gasoil connu
        return $dernierGasoil->etat_plaquettes + ($valeurActuelle - $dernierGasoil->$champ);
    }

    // Premier gasoil après plaquettes : valeur_gasoil - valeur_plaquettes
    return $valeurActuelle - $dernieresPlaques->$champ;
}
private function calculerEtatPneus(int $vehicleId, ?int $valeurActuelle, string $dateActuelle, ?int $depenseIdExclure = null, string $mode = 'kilometrage'): ?int
{
    $champ = $mode === 'heures' ? 'heures' : 'kilometrage';

    if (!$valeurActuelle || $valeurActuelle <= 0) {
        return null;
    }

    // Chercher les derniers pneus selon le même champ
    $query = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'Pneus')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '<=', $dateActuelle);

    if ($depenseIdExclure) {
        $query->where('id', '!=', $depenseIdExclure);
    }

    $derniersPneus = $query
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['id', 'date', $champ]);

    if (!$derniersPneus) {
        return null;
    }

    // Chercher le dernier gasoil (même champ) entre les pneus et maintenant
    $dernierGasoil = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'gasoil')
        ->whereNotNull('etat_pneus')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '>', $derniersPneus->date)
        ->where('date', '<', $dateActuelle);

    if ($depenseIdExclure) {
        $dernierGasoil->where('id', '!=', $depenseIdExclure);
    }

    $dernierGasoil = $dernierGasoil
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['etat_pneus', $champ]);

    if ($dernierGasoil && $dernierGasoil->etat_pneus !== null) {
        // Accumuler depuis le dernier gasoil connu
        return $dernierGasoil->etat_pneus + ($valeurActuelle - $dernierGasoil->$champ);
    }

    // Premier gasoil après pneus : valeur_gasoil - valeur_pneus
    return $valeurActuelle - $derniersPneus->$champ;
}

private function recalculerGasoilsApresChangementPneus(int $vehicleId, string $dateReference, ?int $depenseIdExclure = null): void
{
    // Détecter le mode selon les pneus eux-mêmes
    $pneus = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'Pneus')
        ->where('date', $dateReference)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['heures', 'kilometrage']);

    $mode = ($pneus && $pneus->heures && $pneus->heures > 0) ? 'heures' : 'kilometrage';
    $champ = $mode === 'heures' ? 'heures' : 'kilometrage';

    $gasoils = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'gasoil')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '>=', $dateReference)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date')
        ->orderBy('id')
        ->get(['id', 'date', 'heures', 'kilometrage']);

    foreach ($gasoils as $gasoil) {
        $valeur = $mode === 'heures' ? $gasoil->heures : $gasoil->kilometrage;
        $nouveauEtat = $this->calculerEtatPneus(
            $vehicleId,
            $valeur,
            $gasoil->date,
            $depenseIdExclure,
            $mode
        );
        $gasoil->update(['etat_pneus' => $nouveauEtat]);
    }
}
    public function vehicle(Request $request)
    {
        $data = $this->getCommonData($request);
        return view('depenses.vehicle_depenses', $data);
    }

private function calculerEtatVidange(int $vehicleId, ?int $valeurActuelle, string $dateActuelle, ?int $depenseIdExclure = null, string $mode = 'kilometrage'): ?int
{
    $champ = $mode === 'heures' ? 'heures' : 'kilometrage';

    if (!$valeurActuelle || $valeurActuelle <= 0) {
        return null;
    }

    // Chercher la dernière vidange selon le même champ
    $derniereVidange = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'vidange')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '<=', $dateActuelle)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first([$champ, 'date']);

    if (!$derniereVidange) {
        return null;
    }

    // Chercher le dernier gasoil précédent (qui a déjà un etat_vidange calculé)
    // MÊME logique que le km : date >= vidange ET date < actuelle
    $dernierGasoil = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'gasoil')
        ->whereNotNull('etat_vidange')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '>=', $derniereVidange->date)
        ->where('date', '<', $dateActuelle)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['etat_vidange', $champ]);

    // ✅ CUMUL : parcouru depuis la vidange + état précédent
    $parcouruDepuisVidange = $valeurActuelle - $derniereVidange->$champ;
    $etatPrecedent = $dernierGasoil ? $dernierGasoil->etat_vidange : 0;

    return $etatPrecedent + $parcouruDepuisVidange;
}


private function calculerEtatCourroie(int $vehicleId, ?int $valeurActuelle, string $dateActuelle, ?int $depenseIdExclure = null, string $mode = 'kilometrage'): ?int
{
    $champ = $mode === 'heures' ? 'heures' : 'kilometrage';

    if (!$valeurActuelle || $valeurActuelle <= 0) {
        return null;
    }

    // Chercher la dernière courroie selon le même champ
    $query = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'Courroie de distribution')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '<=', $dateActuelle);

    if ($depenseIdExclure) {
        $query->where('id', '!=', $depenseIdExclure);
    }

    $derniereCourroie = $query
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['id', 'date', $champ]);

    if (!$derniereCourroie) {
        return null;
    }

    // Chercher le dernier gasoil (même champ) entre la courroie et maintenant
    $dernierGasoil = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'gasoil')
        ->whereNotNull('etat_courroie')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '>', $derniereCourroie->date)
        ->where('date', '<', $dateActuelle);

    if ($depenseIdExclure) {
        $dernierGasoil->where('id', '!=', $depenseIdExclure);
    }

    $dernierGasoil = $dernierGasoil
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['etat_courroie', $champ]);

    if ($dernierGasoil && $dernierGasoil->etat_courroie !== null) {
        // Accumuler depuis le dernier gasoil connu
        return $dernierGasoil->etat_courroie + ($valeurActuelle - $dernierGasoil->$champ);
    }

    // Premier gasoil après courroie : valeur_gasoil - valeur_courroie
    return $valeurActuelle - $derniereCourroie->$champ;
}

private function recalculerGasoilsApresCourroie(int $vehicleId, string $dateReference, ?int $depenseIdExclure = null): void
{
    // Détecter le mode selon la courroie elle-même
    $courroie = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'Courroie de distribution')
        ->where('date', $dateReference)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['heures', 'kilometrage']);

    $mode = ($courroie && $courroie->heures && $courroie->heures > 0) ? 'heures' : 'kilometrage';
    $champ = $mode === 'heures' ? 'heures' : 'kilometrage';

    $gasoils = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'gasoil')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '>=', $dateReference)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date')
        ->orderBy('id')
        ->get(['id', 'date', 'heures', 'kilometrage']);

    foreach ($gasoils as $gasoil) {
        $valeur = $mode === 'heures' ? $gasoil->heures : $gasoil->kilometrage;
        $nouveauEtat = $this->calculerEtatCourroie(
            $vehicleId,
            $valeur,
            $gasoil->date,
            $depenseIdExclure,
            $mode
        );
        $gasoil->update(['etat_courroie' => $nouveauEtat]);
    }
}


private function calculerEtatAmortisseur(int $vehicleId, ?int $valeurActuelle, string $dateActuelle, ?int $depenseIdExclure = null, string $mode = 'kilometrage'): ?int
{
    $champ = $mode === 'heures' ? 'heures' : 'kilometrage';

    if (!$valeurActuelle || $valeurActuelle <= 0) {
        return null;
    }

    // Chercher le dernier amortisseur selon le même champ
    $query = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'Amortisseurs')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '<=', $dateActuelle);

    if ($depenseIdExclure) {
        $query->where('id', '!=', $depenseIdExclure);
    }

    $dernierAmortisseur = $query
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['id', 'date', $champ]);

    if (!$dernierAmortisseur) {
        return null;
    }

    // Chercher le dernier gasoil (même champ) entre l'amortisseur et maintenant
    $dernierGasoil = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'gasoil')
        ->whereNotNull('etat_amortisseur')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '>', $dernierAmortisseur->date)
        ->where('date', '<', $dateActuelle);

    if ($depenseIdExclure) {
        $dernierGasoil->where('id', '!=', $depenseIdExclure);
    }

    $dernierGasoil = $dernierGasoil
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['etat_amortisseur', $champ]);

    if ($dernierGasoil && $dernierGasoil->etat_amortisseur !== null) {
        // Accumuler depuis le dernier gasoil connu
        return $dernierGasoil->etat_amortisseur + ($valeurActuelle - $dernierGasoil->$champ);
    }

    // Premier gasoil après amortisseur : valeur_gasoil - valeur_amortisseur
    return $valeurActuelle - $dernierAmortisseur->$champ;
}
private function recalculerGasoilsApresAmortisseur(int $vehicleId, string $dateReference, ?int $depenseIdExclure = null): void
{
    // Détecter le mode selon l'amortisseur lui-même
    $amortisseur = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'Amortisseurs')
        ->where('date', $dateReference)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['heures', 'kilometrage']);

    $mode = ($amortisseur && $amortisseur->heures && $amortisseur->heures > 0) ? 'heures' : 'kilometrage';
    $champ = $mode === 'heures' ? 'heures' : 'kilometrage';

    $gasoils = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'gasoil')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '>=', $dateReference)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date')
        ->orderBy('id')
        ->get(['id', 'date', 'heures', 'kilometrage']);

    foreach ($gasoils as $gasoil) {
        $valeur = $mode === 'heures' ? $gasoil->heures : $gasoil->kilometrage;
        $nouveauEtat = $this->calculerEtatAmortisseur(
            $vehicleId,
            $valeur,
            $gasoil->date,
            $depenseIdExclure,
            $mode
        );
        $gasoil->update(['etat_amortisseur' => $nouveauEtat]);
    }
}
public function store(Request $request)
{
    try {
        $avancementNature = NatureDepense::where('designation', 'Avancements de salaires')->first();
        $vehicleNature    = NatureDepense::where('designation', 'Dépenses de véhicule')->first();

        $rules = [
            'montant'      => 'required|numeric|min:0',
            'description'  => 'string|nullable',
            'date'         => 'required|date',
            'epreuve'      => 'nullable|file|mimes:jpg,jpeg,png,pdf,xlsx,xls,doc,docx',
            'reglement_id' => 'required|exists:type_reglement,id',
            'nature_id'    => 'required|exists:nature_depences,id',
            'salarie_id'   => 'nullable|exists:salaries,id|required_if:nature_id,' . ($avancementNature ? $avancementNature->id : 0),
            'vehicle_id'   => 'nullable|exists:vehicules,id|required_if:nature_id,' . ($vehicleNature ? $vehicleNature->id : 0),
            'type' => 'nullable|string|required_if:nature_id,' . ($vehicleNature ? $vehicleNature->id : 0),
            'kilometrage'  => 'nullable|integer|min:0',
            'heures' => 'nullable|integer|min:0',
        ];

        Log::info('TYPE RECU: [' . $request->type . '] | KM: ' . $request->kilometrage);

        $validator = Validator::make($request->all(), $rules);


        

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // ✅ Vérification de doublon pour les dépenses de véhicule
        if ($vehicleNature && $request->nature_id == $vehicleNature->id && $request->vehicle_id) {
            $mode = ($request->heures && $request->heures > 0) ? 'heures' : 'kilometrage';

            $query = Depences::where('vehicle_id', $request->vehicle_id)
                ->where('montant', $request->montant)
                ->where('date', $request->date);

            if ($mode === 'heures') {
                $query->where('heures', $request->heures);
            } else {
                $query->where('kilometrage', $request->kilometrage);
            }

            $existingDepense = $query->first();

            if ($existingDepense) {
                $message = "Une dépense de véhicule identique existe déjà (Code : {$existingDepense->code}) "
                        . "pour le même véhicule, la même date, le même montant "
                        . "et le même " . ($mode === 'heures' ? "nombre d'heures" : "kilométrage") . ".";

                if ($request->ajax()) {
                    return response()->json(['error' => $message, 'duplicate' => true], 409);
                }
                return redirect()->back()->with('error', $message)->withInput();
            }
        }

                // Gestion du fichier épreuve
                $epreuvePath = null;
                if ($request->hasFile('epreuve')) {
                    $file = $request->file('epreuve');
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $destinationPath = public_path('assets/epreuve');
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
                    $file->move($destinationPath, $filename);
                    $epreuvePath = 'assets/epreuve/' . $filename;
                }

                $code    = null;
                $depense = null;

                DB::transaction(function () use ($request, &$code, &$depense, $epreuvePath, $vehicleNature) {

                    // Génération du code unique
                    $lastDepense = Depences::orderBy('id', 'desc')->lockForUpdate()->first();
                    $lastNumber  = $lastDepense && preg_match('/db_(\d+)/', $lastDepense->code, $matches)
                        ? (int) $matches[1]
                        : 0;
                    $newNumber = $lastNumber + 1;
                    do {
                        $code = 'db_' . $newNumber;
                        $newNumber++;
                    } while (Depences::where('code', $code)->exists());

                    // Nature de la dépense
                    $natureDepense = NatureDepense::findOrFail($request->nature_id);

                    // Matricule salarié si avancement
                    $salarieData = null;
                    if ($request->salarie_id && $natureDepense->designation === 'Avancements de salaires') {
                        $salarie     = Salarie::findOrFail($request->salarie_id);
                        $salarieData = $salarie->n_matricule_entreprise;
                    }

                    $moisDepenses = Carbon::parse($request->date)->format('m');

                    // Calcul etat_vidange et etat_plaquettes selon le type de dépense véhicule
                    $etatVidange    = null;
                    $etatPlaquettes = null;
                    $etatPneus      = null;
                    $etatCourroie   = null;
                    $etatAmortisseur = null;
                    

                if ($natureDepense->id === $vehicleNature->id && $request->vehicle_id) {

                    $mode = ($request->heures && $request->heures > 0) ? 'heures' : 'kilometrage';
                    $valeurMetrique = $mode === 'heures' ? (int)$request->heures : (int)$request->kilometrage;

                    if ($request->type === 'gasoil' && $valeurMetrique > 0) {
                        $etatVidange = $this->calculerEtatVidange(
                            $request->vehicle_id, $valeurMetrique, $request->date, null, $mode
                        );
                        $etatPlaquettes = $this->calculerEtatPlaquettes(
                            $request->vehicle_id, $valeurMetrique, $request->date, null, $mode
                        );
                        $etatPneus = $this->calculerEtatPneus(
                            $request->vehicle_id, $valeurMetrique, $request->date, null, $mode
                        );
                        $etatCourroie = $this->calculerEtatCourroie(
                            $request->vehicle_id, $valeurMetrique, $request->date, null, $mode
                        );
                        $etatAmortisseur = $this->calculerEtatAmortisseur(
                            $request->vehicle_id, $valeurMetrique, $request->date, null, $mode
                        );
                    } elseif ($request->type === 'vidange') {
                        $etatVidange = 0;
                    } elseif ($request->type === 'Plaquettes de frein') {
                        $etatPlaquettes = 0;
                    } elseif ($request->type === 'Pneus') {
                        $etatPneus = 0;
                    } elseif ($request->type === 'Courroie de distribution') {
                        $etatCourroie = 0;
                    } elseif ($request->type === 'Amortisseurs') {
                        // Stocker la valeur de référence selon le mode
                        $etatAmortisseur = 0;
                    }
                }
                    // Création de la dépense
                    $depense = Depences::create([
                        'code'           => $code,
                        'montant'        => $request->montant,
                        'description'    => $request->description,
                        'date'           => $request->date,
                        'mois_depenses'  => $moisDepenses,
                        'epreuve'        => $epreuvePath,
                        'reglement_id'   => $request->reglement_id,
                        'nature_depense' => $natureDepense->designation,
                        'nature_id'      => $request->nature_id,
                        'salarie'        => $salarieData,
                        'salarie_id'     => $request->salarie_id,
                        'vehicle_id'     => $request->vehicle_id,
                        'type'           => $request->type,
                        'kilometrage'    => $request->kilometrage,
                        'etat_vidange'   => $etatVidange,
                        'etat_plaquettes'=> $etatPlaquettes,
                        'etat_courroie'   => $etatCourroie,
                        'etat_pneus'      => $etatPneus,
                        'etat_amortisseur' => $etatAmortisseur,
                        'heures' => $request->heures,
                        'created_by'     => Auth::id(),
                    ]);

                    // Si on vient de créer une vidange → recalculer les gasoils postérieurs
                    if (
                        $natureDepense->id === $vehicleNature->id &&
                        $request->type === 'vidange' &&
                        $request->vehicle_id &&
                        ($request->kilometrage || $request->heures)
                    ) {
                        $this->recalculerGasoilsApresVidange(
                            $request->vehicle_id,
                            $request->date,
                            $depense->id
                        );
                    }

                    // Si on vient de créer des plaquettes → recalculer les gasoils postérieurs
                    if (
                        $natureDepense->id === $vehicleNature->id &&
                        $request->type === 'Plaquettes de frein' &&
                        $request->vehicle_id &&
                        $request->kilometrage
                    ) {
                        $this->recalculerGasoilsApresPlaquettes(
                            $request->vehicle_id,
                            $request->date,
                            $depense->id
                        );
                    }

                                // Recalcul après pneus
                    if (
                        $natureDepense->id === $vehicleNature->id &&
                        $request->type === 'Pneus' &&
                        $request->vehicle_id && $request->kilometrage
                    ) {
                        $this->recalculerGasoilsApresChangementPneus(
                            $request->vehicle_id, $request->date, $depense->id
                        );
                    }

                                    // Recalcul après courroie
                        if (
                            $natureDepense->id === $vehicleNature->id &&
                            $request->type === 'Courroie de distribution' &&
                            $request->vehicle_id && $request->kilometrage
                        ) {
                            $this->recalculerGasoilsApresCourroie(
                                $request->vehicle_id, $request->date, $depense->id
                            );
                            }

                                            // Recalcul après amortisseur
                        if (
                            $natureDepense->id === $vehicleNature->id &&
                            $request->type === 'Amortisseurs' &&
                            $request->vehicle_id && $request->kilometrage
                        ) {
                            $this->recalculerGasoilsApresAmortisseur(
                                $request->vehicle_id, $request->date, $depense->id
                            );
                        }
                });

                

                // Notifications aux superadmins
                $superAdmins = \App\Models\User::whereHas('role', function ($query) {
                    $query->where('name', 'superadmin');
                })->get();

                $currentUser  = Auth::user();
                $isSuperAdmin = $currentUser->role && $currentUser->role->name === 'superadmin';

                foreach ($superAdmins as $admin) {
                    if ($isSuperAdmin && $admin->id === $currentUser->id) {
                        continue;
                    }
                    $admin->notify(new DepenseActionNotification($depense, 'created'));
                }

                if ($request->ajax()) {
                    return response()->json(['message' => 'Dépense ajoutée avec succès !']);
                }

                return redirect()->route('depenses.vehicle')
                    ->with('success', 'Dépense ajoutée avec succès !');

            } catch (\Exception $e) {
                Log::error('Erreur lors de la création de la dépense : ' . $e->getMessage());

                if ($request->ajax()) {
                    return response()->json(['error' => 'Erreur lors de l\'ajout : ' . $e->getMessage()], 500);
                }

                return redirect()->back()
                    ->with('error', 'Erreur lors de l\'ajout : ' . $e->getMessage())
                    ->withInput();
            }
}
private function recalculerGasoilsApresPlaquettes(int $vehicleId, string $dateReference, ?int $depenseIdExclure = null): void
{
    // Détecter le mode selon les plaquettes elles-mêmes
    $plaquettes = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'Plaquettes de frein')
        ->where('date', $dateReference)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['heures', 'kilometrage']);

    $mode = ($plaquettes && $plaquettes->heures && $plaquettes->heures > 0) ? 'heures' : 'kilometrage';
    $champ = $mode === 'heures' ? 'heures' : 'kilometrage';

    $gasoils = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'gasoil')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '>=', $dateReference)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date')
        ->orderBy('id')
        ->get(['id', 'date', 'heures', 'kilometrage']);

    foreach ($gasoils as $gasoil) {
        $valeur = $mode === 'heures' ? $gasoil->heures : $gasoil->kilometrage;
        $nouveauEtat = $this->calculerEtatPlaquettes(
            $vehicleId,
            $valeur,
            $gasoil->date,
            $depenseIdExclure,
            $mode
        );
        $gasoil->update(['etat_plaquettes' => $nouveauEtat]);
    }
}

private function recalculerGasoilsApresVidange(int $vehicleId, string $dateReference, ?int $depenseIdExclure = null): void
{
    // Détecter le mode selon la vidange elle-même
    $vidange = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'vidange')
        ->where('date', $dateReference)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date', 'desc')
        ->orderBy('id', 'desc')
        ->first(['heures', 'kilometrage']);

    $mode = ($vidange && $vidange->heures && $vidange->heures > 0) ? 'heures' : 'kilometrage';
    $champ = $mode === 'heures' ? 'heures' : 'kilometrage';

    // Récupérer les gasoils postérieurs dans l'ordre chronologique
    $gasoils = Depences::where('vehicle_id', $vehicleId)
        ->where('type', 'gasoil')
        ->whereNotNull($champ)
        ->where($champ, '>', 0)
        ->where('date', '>=', $dateReference)
        ->when($depenseIdExclure, fn($q) => $q->where('id', '!=', $depenseIdExclure))
        ->orderBy('date')   // ← ordre croissant pour le cumul
        ->orderBy('id')
        ->get(['id', 'date', 'heures', 'kilometrage']);

    foreach ($gasoils as $gasoil) {
        $valeur = $mode === 'heures' ? $gasoil->heures : $gasoil->kilometrage;
        $nouveauEtat = $this->calculerEtatVidange(
            $vehicleId,
            $valeur,
            $gasoil->date,
            $depenseIdExclure,
            $mode
        );
        $gasoil->update(['etat_vidange' => $nouveauEtat]);
    }
}
 
    public function edit($id)
    {
        try {
            $depense = Depences::findOrFail($id);
            $typeReglements = TypeReglement::all();
            $natureDepenses = NatureDepense::all();
            $salaries = Salarie::select('id', 'n_matricule_entreprise', 'nom', 'prenom')->get();
            $vehicles = \App\Models\Vehicle::select('id', 'matricule')->get();
            $avancementNatureId = NatureDepense::where('designation', 'Avancements de salaires')->first()->id ?? null;
            $vehicleNatureId = NatureDepense::where('designation', 'Dépenses de véhicule')->first()->id ?? null;

            \Log::info('Edit Depense Response:', [
                'depense_id' => $id,
                'depense' => $depense->toArray(),
                'typeReglements_count' => $typeReglements->count(),
                'natureDepenses_count' => $natureDepenses->count(),
                'salaries_count' => $salaries->count(),
                'vehicles_count' => $vehicles->count(),
                'avancementNatureId' => $avancementNatureId,
                'vehicleNatureId' => $vehicleNatureId
            ]);

            return response()->json(compact(
                'depense',
                'typeReglements',
                'natureDepenses',
                'salaries',
                'vehicles',
                'avancementNatureId',
                'vehicleNatureId'
            ));
        } catch (\Exception $e) {
            \Log::error('Error in edit Depense: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors du chargement des données'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $depense = Depences::findOrFail($id);
            $avancementNature = NatureDepense::where('designation', 'Avancements de salaires')->first();
            $vehicleNature = NatureDepense::where('designation', 'Dépenses de véhicule')->first();

            $rules = [
                'montant' => 'required|numeric|min:0',
                'description' => 'string',
                'date' => 'required|date',
                'epreuve' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xlsx,xls,doc,docx|max:2048',
                'reglement_id' => 'required|exists:type_reglement,id',
                'nature_id' => 'required|exists:nature_depences,id',
                'salarie_id' => 'nullable|exists:salaries,id|required_if:nature_id,' . ($avancementNature ? $avancementNature->id : 0),
                'vehicle_id' => 'nullable|exists:vehicules,id|required_if:nature_id,' . ($vehicleNature ? $vehicleNature->id : 0),
                'type' => 'nullable|in:gasoil,vidange,visite_technique,vignette,reparation,autre|required_if:nature_id,' . ($vehicleNature ? $vehicleNature->id : 0),
                'kilometrage' => 'nullable|integer|min:0',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json(['errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $epreuvePath = $depense->epreuve;
            if ($request->hasFile('epreuve')) {
                if ($epreuvePath && file_exists(public_path($epreuvePath))) {
                    unlink(public_path($epreuvePath));
                }

                $file = $request->file('epreuve');
                $filename = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('assets/epreuve');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $epreuvePath = 'assets/epreuve/' . $filename;
            }

            $natureDepense = NatureDepense::findOrFail($request->nature_id);

            $salarieData = null;
            if ($request->salarie_id && $natureDepense->designation === 'Avancements de salaires') {
                $salarie = Salarie::findOrFail($request->salarie_id);
                $salarieData = $salarie->n_matricule_entreprise;
            }

            $moisDepenses = Carbon::parse($request->date)->format('m');

            $depense->update([
                'montant' => $request->montant,
                'description' => $request->description,
                'date' => $request->date,
                'mois_depenses' => $moisDepenses,
                'epreuve' => $epreuvePath,
                'reglement_id' => $request->reglement_id,
                'nature_depense' => $natureDepense->designation,
                'nature_id' => $request->nature_id,
                'salarie' => $salarieData,
                'salarie_id' => $request->salarie_id,
                'vehicle_id' => $request->vehicle_id,
                'type' => $request->type,
                'kilometrage' => $request->kilometrage,
                'updated_by' => Auth::id(),
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => 'Dépense mise à jour avec succès !']);
            }

            return redirect()->route('depenses.varie')->with('success', 'Dépense mise à jour avec succès !');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $depense = Depences::findOrFail($id);

            if ($depense->epreuve && file_exists(public_path($depense->epreuve))) {
                unlink(public_path($depense->epreuve));
            }

            $depense->delete();

            return redirect()->route('depenses.varie')->with('success', 'Dépense supprimée avec succès !');
        } catch (\Exception $e) {
            return redirect()->route('depenses.varie')->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
    public function show($id)
    {
        $depense = Depences::with(['nature', 'reglement', 'salarie', 'vehicle', 'creator'])->findOrFail($id);
        return response()->json(['depense' => $depense]);
    }
    public function downloadAvancesPdf(Request $request)
    {
        $dateDebut = $request->date_debut ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $dateFin = $request->date_fin ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $avances = Depences::where('nature_depense', 'Avancements de salaires')
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->with(['employee' => function ($query) {
                $query->select('id', 'nom', 'prenom', 'rib');
            }])
            ->get();

        if ($avances->isEmpty()) {
            return redirect()->back()->with('error', 'Aucune avance trouvée pour la période.');
        }

        $missingRibs = $avances->filter(function ($avance) {
            return !$avance->employee || !$avance->employee->rib;
        });
        if ($missingRibs->isNotEmpty()) {
            $names = $missingRibs->map(function ($avance) {
                return $avance->employee->nom . ' ' . $avance->employee->prenom;
            })->implode(', ');
            return redirect()->back()->with('error', "RIB manquant pour les employés : $names. Veuillez les ajouter dans la table Salarié.");
        }

        $company = CompanySettings::first();
        // Clean the logo path if necessary
        if ($company && $company->logo) {
            $company->logo = basename($company->logo); // Ensure only filename is used
        }
        $month = Carbon::parse($dateDebut)->month;
        $year = Carbon::parse($dateDebut)->format('y');
        $ref = sprintf('%02d/%s', $month, $year);
        $date_virement = Carbon::now()->format('d/m/Y');
        $signatory_name = Auth::user()->name ?? '';

        $pdf = PDF::loadView('depenses.avances-pdf', compact('avances', 'company', 'ref', 'date_virement', 'signatory_name'));
        return $pdf->download('Ordre_Virement_Avances_' . $dateDebut . '_to_' . $dateFin . '.pdf');
    }
    
public function downloadSelectedAvancesPdf(Request $request)
{
    $ids = explode(',', $request->input('ids', ''));

    if (empty($ids)) {
        return redirect()->back()->with('error', 'Aucune avance sélectionnée.');
    }

    $type = $request->input('type', 'normal');

    $avances = Depences::whereIn('id', $ids)
        ->where('nature_depense', 'Avancements de salaires')
        ->where('reglement_id', TypeReglement::where('designation', 'Virement')->first()?->id)
        ->with(['employee' => function ($query) {
            $query->select('id', 'nom', 'prenom', 'rib');
        }])
        ->get();

    if ($avances->isEmpty()) {
        return redirect()->back()->with('error', 'Aucune avance valide trouvée.');
    }

    $missingRibs = $avances->filter(function ($avance) {
        return !$avance->employee || empty(trim($avance->employee->rib ?? ''));
    });

    if ($missingRibs->isNotEmpty()) {
        $names = $missingRibs->map(fn($a) => $a->employee?->nom . ' ' . $a->employee?->prenom)->implode(', ');
        return redirect()->back()->with('error', "RIB manquant pour : $names");
    }

    $company = CompanySettings::first();
    if ($company && $company->logo) {
        $company->logo = basename($company->logo);
    }

    // Référence envoyée par l'utilisateur, sinon fallback auto-généré
    $ref = $request->input('ref');
    if (empty($ref)) {
        $month = Carbon::parse($avances->first()->date)->month;
        $year  = Carbon::parse($avances->first()->date)->format('y');
        $ref   = sprintf('%02d/%s', $month, $year);
    }

    // ← NOUVEAU : enregistrer la référence dans la table depences
    // pour toutes les avances sélectionnées
    Depences::whereIn('id', $ids)->update(['reference' => $ref]);

    $date_virement = Carbon::now()->format('d/m/Y');

    $pdf = PDF::loadView('depenses.avances-pdf', 
        compact('avances', 'company', 'ref', 'date_virement', 'type')
    );

    return $pdf->download('Ordre_Virement_Avances_Selection_' . now()->format('Ymd_His') . '.pdf');
}

public function generateSelectedAvancesPdf(Request $request)
{
    $ids = explode(',', $request->query('ids'));
    $type = $request->query('type', 'normal');
    $ref = trim($request->query('ref'));

    if (empty($ref)) {
        return redirect()->back()->with('error', 'Une référence est requise.');
    }

    // Vérification anti-doublon côté serveur
    $referenceExists = Depences::where('reference', $ref)->exists();
    if ($referenceExists) {
        return redirect()->back()->with('error', 'Cette référence existe déjà. Veuillez en choisir une autre.');
    }

    $avances = Depences::with('employee')
        ->whereIn('id', $ids)
        ->where('reglement_depense', 'Virement')
        ->get();

    Depences::whereIn('id', $ids)->update(['reference' => $ref]);

    $date_virement = now()->format('d/m/Y');

    return view('depenses.pdf.avances_selected', compact('avances', 'ref', 'date_virement', 'type'));
}
/**
 * Récupère la dernière référence utilisée pour un avancement de salaire
 */
public function getLastAvanceReference()
{
    $lastReference = Depences::where('nature_depense', 'Avancements de salaires')
        ->whereNotNull('reference')
        ->where('reference', '!=', '')
        ->orderByDesc('updated_at')
        ->value('reference');

    return response()->json(['reference' => $lastReference]);
}

/**
 * Vérifie si une référence existe déjà dans la table depences
 */
public function checkReferenceExists(Request $request)
{
    $reference = trim($request->query('reference', ''));

    if (empty($reference)) {
        return response()->json(['exists' => false]);
    }

    $exists = Depences::where('reference', $reference)->exists();

    return response()->json(['exists' => $exists]);
}



}
