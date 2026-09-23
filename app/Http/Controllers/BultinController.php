<?php

namespace App\Http\Controllers;

use App\Models\Cotisations;
use App\Models\Depences;
use App\Models\ImpotSurRevenu;
use App\Models\CompanySettings;
use App\Models\ConfigurationBp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\Salarie;
use App\Models\AncienneteTaux;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use DateTime;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class BultinController extends Controller
{

public function index()
{
    $currentYear = request('year', date('Y'));
    
    $monthMap = [
        1 => 'janvier', 2 => 'fevrier', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'aout',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'decembre'
    ];
    $currentSystemMonth = $monthMap[date('n')];
    $currentMonth = request('month', $currentSystemMonth);

    // Si aucun mois n'est sélectionné, utiliser le mois en cours
    if (is_null($currentMonth) || $currentMonth === '') {
        $currentMonth = $currentSystemMonth;
    }

    Session::put('selected_month', $currentMonth);

    Log::info('BultinController:index', [
        'currentMonth' => $currentMonth,
        'currentSystemMonth' => $currentSystemMonth,
        'currentYear' => $currentYear,
        'request_month' => request('month'),
        'selected_month' => Session::get('selected_month')
    ]);

    $years = [];
    foreach (range(2020, date('Y')) as $year) {
        if (Schema::hasTable("salairs_{$year}")) {
            $years[] = $year;
        }
    }

    // Calculer les 6 mois à afficher (mois en cours/sélectionné + 5 mois précédents)
    $months = [];
    $monthIndex = array_search($currentMonth, $monthMap) ?: date('n');
    for ($i = 5; $i >= 0; $i--) {
        $monthNum = ($monthIndex - $i + 12) % 12;
        $monthNum = $monthNum === 0 ? 12 : $monthNum;
        $monthKey = $monthMap[$monthNum];
        $months[$monthKey] = ucfirst($monthKey);
    }

    $salaries = [];
    $tableName = "salairs_{$currentYear}";
    if (in_array($currentYear, $years) && Schema::hasTable($tableName)) {
        $query = DB::table($tableName)
            ->leftJoin('salaries', 'salairs_' . $currentYear . '.id_salarie', '=', 'salaries.id')
            ->leftJoinSub(
                DB::table('demissions')
                    ->select('salarie_id', DB::raw('MAX(date_demission) as date_demission'))
                    ->groupBy('salarie_id'),
                'demissions',
                function ($join) {
                    $join->on('salaries.id', '=', 'demissions.salarie_id');
                }
            )
            ->select(
                'salairs_' . $currentYear . '.id_salarie',
                'salairs_' . $currentYear . '.n_matricule_entreprise as matricule',
                DB::raw('CONCAT(salairs_' . $currentYear . '.nom, " ", salairs_' . $currentYear . '.prenom) as nom_prenom'),
                'salairs_' . $currentYear . '.salaire',
                'salairs_' . $currentYear . '.salaire_net',
                'salairs_' . $currentYear . '.janvier',
                'salairs_' . $currentYear . '.fevrier',
                'salairs_' . $currentYear . '.mars',
                'salairs_' . $currentYear . '.avril',
                'salairs_' . $currentYear . '.mai',
                'salairs_' . $currentYear . '.juin',
                'salairs_' . $currentYear . '.juillet',
                'salairs_' . $currentYear . '.aout',
                'salairs_' . $currentYear . '.septembre',
                'salairs_' . $currentYear . '.octobre',
                'salairs_' . $currentYear . '.novembre',
                'salairs_' . $currentYear . '.decembre',
                'salaries.statut',
                'demissions.date_demission'
            );

        $statusFilter = request('status', 'actif');
        if ($statusFilter === 'actif') {
            $query->where('salaries.statut', 'actif');
        } elseif ($statusFilter === 'inactif') {
            $query->where('salaries.statut', 'inactif');
        }

        $query->orderBy('demissions.date_demission', 'desc');

        $salaries = $query->get()
            ->map(function ($salarie) use ($months, $currentYear, $monthMap) {
                $data = [
                    'id_salarie' => $salarie->id_salarie,
                    'nom_prenom' => $salarie->nom_prenom,
                    'matricule' => $salarie->matricule,
                    'statut' => $salarie->statut,
                    'date_demission' => $salarie->date_demission ? Carbon::parse($salarie->date_demission) : null,
                ];

                foreach ($months as $monthKey => $monthName) {
                    $paymentId = $salarie->$monthKey;
                    if ($paymentId && is_numeric($paymentId)) {
                        $payment = DB::table('paiement_salaires')
                            ->where('id', $paymentId)
                            ->where('annee', $currentYear)
                            ->where('mois', array_search($monthKey, $monthMap))
                            ->first();
                        $hasQuittanceCah = $payment ? DB::table('pieces_joint')
                            ->where('id_paiment', $paymentId)
                            ->where('id_salarier', $salarie->id_salarie)
                            ->where('mois', $monthKey)
                            ->where('annee', $currentYear)
                            ->whereNotNull('quittance_cah')
                            ->exists() : false;

    $hasPayslip = $payment ? DB::table('pieces_joint')
        ->where('id_paiment', $paymentId)
        ->where('id_salarier', $salarie->id_salarie)
        ->where('mois', $monthKey)
        ->where('annee', $currentYear)
        ->whereNotNull('bultin_paie_ca')
        ->exists() : false;

    $data[$monthKey] = [
        'value'           => $payment && $payment->salaire ? sprintf("%.2f", $payment->salaire) : '-',
        'statutspj'       => $payment ? $payment->statutspj : 0,
        'paymentId'       => $paymentId,
        'typer'           => $payment ? $payment->typer : null,
        'hasQuittanceCah' => $hasQuittanceCah,
        'hasPayslip'      => $hasPayslip,   
    ];
                    } else {
                        // Pas de paiement → on affiche le salaire_net de la table salairs_{année}
        // (et non plus le salaire de base)
        $salaireNet = null;
        if (isset($salarie->salaire_net) && is_numeric($salarie->salaire_net) && (float)$salarie->salaire_net > 0) {
            $salaireNet = $salarie->salaire_net;
        }

        $data[$monthKey] = [
            'value'          => $salaireNet ? sprintf("%.2f", $salaireNet) : '-',
            'statutspj'      => 0,
            'paymentId'      => null,
            'typer'          => null,
            'hasQuittanceCah'=> false,
           'hasPayslip'      => false,
        ];
                    }
                }
                return $data;
            });
    } else {
        session()->flash('error', "Aucune donnée pour l'année {$currentYear}.");
    }

    $companySettings = CompanySettings::first();
    $bulkPayrolls = $this->getBulkPayrolls($currentYear);
    $virementsPdfs = $this->getVirementsPdfs($currentYear);

    return view('bultin-paie.bultin', compact('years', 'months', 'salaries', 'currentYear', 'currentMonth', 'currentSystemMonth', 'bulkPayrolls', 'companySettings'));
}

protected function getVirementsPdfs($year)
{
    return DB::table('pieces_joint')
        ->where('annee', $year)
        ->whereNotNull('virement_pdf_filename')
        ->distinct()
        ->pluck('virement_pdf_filename')
        ->map(function ($path) {
            return basename($path); // pour n'afficher que le nom dans le dropdown
        })
        ->unique()
        ->values()
        ->toArray();
}



        public function getPrintData(Request $request)
{
    $year      = $request->input('year', date('Y'));
    $status    = $request->input('status', 'actif');
    $employees = $request->input('employees', []);
 
    $monthMap = [
        1=>'janvier',2=>'fevrier',3=>'mars',4=>'avril',
        5=>'mai',6=>'juin',7=>'juillet',8=>'aout',
        9=>'septembre',10=>'octobre',11=>'novembre',12=>'decembre'
    ];
 
    $tableName = "salairs_{$year}";
    if (!Schema::hasTable($tableName)) {
        return response()->json(['error' => "Table {$tableName} introuvable."], 404);
    }
 
    $query = DB::table($tableName)
        ->leftJoin('salaries', "{$tableName}.id_salarie", '=', 'salaries.id')
        ->leftJoinSub(
            DB::table('demissions')
                ->select('salarie_id', DB::raw('MAX(date_demission) as date_demission'))
                ->groupBy('salarie_id'),
            'demissions',
            fn($j) => $j->on('salaries.id', '=', 'demissions.salarie_id')
        )
        ->select(
            "{$tableName}.id_salarie",
            "{$tableName}.n_matricule_entreprise as matricule",
            DB::raw("CONCAT({$tableName}.nom, ' ', {$tableName}.prenom) as nom_prenom"),
            "{$tableName}.salaire",
            ...array_values($monthMap)   // janvier … decembre (colonne ID paiement)
        );
 
    if (!empty($employees)) {
        $query->whereIn("{$tableName}.id_salarie", $employees);
    }
    if ($status === 'actif') {
        $query->where('salaries.statut', 'actif');
    } elseif ($status === 'inactif') {
        $query->where('salaries.statut', 'inactif');
    }
 
    $salaries = $query->get()->map(function ($sal) use ($monthMap, $year) {
        $data = [
            'id_salarie' => $sal->id_salarie,
            'matricule'  => $sal->matricule,
            'nom_prenom' => $sal->nom_prenom,
        ];
 
        foreach ($monthMap as $monthNum => $monthKey) {
            $paymentId = $sal->$monthKey;
            if ($paymentId && is_numeric($paymentId)) {
                $payment = DB::table('paiement_salaires')
                    ->where('id', $paymentId)
                    ->where('annee', $year)
                    ->where('mois', $monthNum)
                    ->first();
 
                $hasQuittanceCah = $payment
                    ? DB::table('pieces_joint')
                        ->where('id_paiment', $paymentId)
                        ->where('id_salarier', $sal->id_salarie)
                        ->where('mois', $monthKey)
                        ->where('annee', $year)
                        ->whereNotNull('quittance_cah')
                        ->exists()
                    : false;
 
                $data[$monthKey] = [
                    'value'          => $payment && $payment->salaire ? sprintf("%.2f", $payment->salaire) : '-',
                    'typer'          => $payment ? $payment->typer : null,
                    'hasQuittanceCah'=> $hasQuittanceCah,
                ];
            } else {
                $data[$monthKey] = [
                    'value'          => $sal->salaire ? sprintf("%.2f", $sal->salaire) : '-',
                    'typer'          => null,
                    'hasQuittanceCah'=> false,
                ];
            }
        }
        return $data;
    });
 
    return response()->json(['salaries' => $salaries]);
}

     public function generateNotepad(Request $request)
    {
        // Récupérer les paramètres
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', Session::get('selected_month', 'mai'));
        $statusFilter = $request->input('status', 'actif');

        Log::info('generateNotepad: Paramètres reçus', [
            'year' => $year,
            'month' => $month,
            'status' => $statusFilter
        ]);

        // Mappage des mois
        $monthMap = [
            1 => 'janvier', 2 => 'fevrier', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'aout',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'decembre'
        ];
        $monthNumber = array_search(strtolower($month), $monthMap);

        if (!$monthNumber) {
            Log::error('generateNotepad: Mois invalide', ['month' => $month]);
            return response()->json(['error' => 'Mois invalide : ' . $month], 400);
        }

        // Récupérer les paramètres de l'entreprise
        $companySettings = CompanySettings::first();
        if (!$companySettings) {
            Log::error('generateNotepad: Paramètres de l\'entreprise introuvables');
            return response()->json(['error' => 'Paramètres de l\'entreprise introuvables.'], 500);
        }

        // Nettoyer et valider les valeurs de l'entreprise
        $code_cnss = trim($companySettings->code_cnss) ?: 'A014';
        $cnss_number = trim($companySettings->cnss_number) ?: '078839202506';
        $nom_entreprise = trim($companySettings->nom_etreprise) ?: 'STE FAGEOS SARL';
        $address = trim($companySettings->address) ?: 'BD MED ABDOU N18 BIS 3EME ETAGE BUREAU N 13 RESIDENCE HAMZA';
        $ice = trim($companySettings->ice) ?: '512025062620250710';
        $ville = 'OUJDA';
        $code_postal = '60000';

        // Supprimer tout caractère de nouvelle ligne ou espace indésirable
        $code_cnss = preg_replace('/[\r\n]+/', '', $code_cnss);
        $cnss_number = preg_replace('/[\r\n]+/', '', $cnss_number);
        $nom_entreprise = preg_replace('/[\r\n]+/', '', $nom_entreprise);
        $address = preg_replace('/[\r\n]+/', '', $address);
        $ice = preg_replace('/[\r\n]+/', '', $ice);

        // Construire la ligne d'en-tête sur une seule ligne
        $headerLine = str_pad($code_cnss, 4, ' ', STR_PAD_RIGHT) .
                      str_pad($cnss_number, 12, ' ', STR_PAD_RIGHT) .
                      str_pad($nom_entreprise, 60, ' ', STR_PAD_RIGHT) .
                      str_pad($address, 100, ' ', STR_PAD_RIGHT) .
                      str_pad($ville, 20, ' ', STR_PAD_RIGHT) .
                      str_pad($code_postal, 5, ' ', STR_PAD_RIGHT) .
                      str_pad($ice, 18, ' ', STR_PAD_RIGHT);

        Log::info('generateNotepad: Ligne d\'en-tête générée', ['headerLine' => $headerLine]);

        // Vérifier l'existence de la table
        $tableName = "salairs_{$year}";
        if (!Schema::hasTable($tableName)) {
            Log::error('generateNotepad: Table introuvable', ['tableName' => $tableName]);
            return response()->json(['error' => "La table {$tableName} n'existe pas."], 404);
        }

        // Construire la requête pour les salariés
        $query = DB::table($tableName)
            ->leftJoin('salaries', "salairs_{$year}.id_salarie", '=', 'salaries.id')
            ->leftJoin('paiement_salaires', function ($join) use ($year, $monthNumber) {
                $join->on("salairs_{$year}.id_salarie", '=', 'paiement_salaires.id_salarie')
                     ->where('paiement_salaires.annee', $year)
                     ->where('paiement_salaires.mois', $monthNumber)
                     ->where('paiement_salaires.typer', '!=', 'a');
            })
            ->select(
                "salairs_{$year}.n_matricule_entreprise as matricule",
                "salairs_{$year}.nom",
                "salairs_{$year}.prenom",
                'paiement_salaires.typer'
            );

        if ($statusFilter === 'actif') {
            $query->where('salaries.statut', 'actif');
        } elseif ($statusFilter === 'inactif') {
            $query->where('salaries.statut', 'inactif');
        }

        $query->whereNotNull('paiement_salaires.typer');

        $salaries = $query->get();

        Log::info('generateNotepad: Résultat de la requête', [
            'year' => $year,
            'month' => $month,
            'monthNumber' => $monthNumber,
            'salaries_count' => $salaries->count(),
            'salaries' => $salaries->toArray()
        ]);

        if ($salaries->isEmpty()) {
            Log::warning('generateNotepad: Aucun salarié trouvé', [
                'year' => $year,
                'month' => $month,
                'status' => $statusFilter
            ]);
            return response()->json(['error' => "Aucun salarié avec un paiement (typer différent de 'a') pour {$month} {$year}."], 404);
        }

        // Générer les lignes des salariés
        $content = $salaries->map(function ($salarie) use ($code_cnss, $cnss_number) {
            $nom = preg_replace('/[\r\n]+/', '', trim($salarie->nom ?? '')); // Nettoyer le nom
            $prenom = preg_replace('/[\r\n]+/', '', trim($salarie->prenom ?? '')); // Nettoyer le prénom
            $paddedNom = str_pad($nom, 30, ' ', STR_PAD_RIGHT);
            $paddedPrenom = str_pad($prenom, 30, ' ', STR_PAD_RIGHT);
            $matricule = $salarie->matricule ?? '00000000';
            $zeros = str_repeat('0', 20);
            return "{$code_cnss}{$cnss_number}{$matricule}{$paddedNom}{$paddedPrenom}{$zeros}";
        })->implode("\n");

        // Combiner l'en-tête et le contenu
        $finalContent = $headerLine . "\n" . $content;

        Log::info('generateNotepad: Contenu final généré', ['content_length' => strlen($finalContent)]);

        $filename = "AFFEBDS_{$cnss_number}_{$year}_{$month}.txt";

        return response($finalContent)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }
 public function fetchPayrollData(Request $request)
{
    $year = $request->input('year', date('Y')); // 2025 par défaut
    $month = $request->input('month', session('selected_month', 'juillet')); // Adapté à juillet 2025
    $status = $request->input('status', 'actif');
    $employees = $request->input('employees', []);

    Log::info('fetchPayrollData: Paramètres reçus', [
        'year' => $year,
        'month' => $month,
        'status' => $status,
        'employees_count' => count($employees)
    ]);

    if (empty($employees)) {
        Log::warning('Aucun salarié sélectionné', ['year' => $year, 'month' => $month, 'status' => $status]);
        return response()->json(['error' => "Aucun salarié sélectionné pour {$month} {$year}."], 400);
    }

    $tableName = "salairs_{$year}";
    if (!Schema::hasTable($tableName)) {
        Log::error('Table introuvable', ['tableName' => $tableName]);
        return response()->json(['error' => "La table {$tableName} n'existe pas."], 404);
    }

    $monthMap = [
        1 => 'janvier', 2 => 'fevrier', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'aout',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'decembre'
    ];

    $monthNumber = array_search(strtolower($month), $monthMap);
    if (!$monthNumber) {
        Log::error('Mois invalide', ['month' => $month]);
        return response()->json(['error' => "Mois invalide : {$month}"], 400);
    }

    $query = DB::table($tableName)
        ->leftJoin('salaries', "salairs_{$year}.id_salarie", '=', 'salaries.id')
        ->leftJoin('paiement_salaires', function ($join) use ($year, $monthNumber) {
            $join->on("salairs_{$year}.id_salarie", '=', 'paiement_salaires.id_salarie')
                 ->where('paiement_salaires.annee', $year)
                 ->where('paiement_salaires.mois', $monthNumber);
        })
        ->select(
            'salaries.id as id_salarie',
            'salaries.n_matricule_cnss',
            'salaries.nom',
            'salaries.prenom',
            DB::raw('COALESCE(CAST(paiement_salaires.cotisation_cnss AS DECIMAL(10,2)), 0) as cotisation_cnss'),
            'paiement_salaires.basePlafond',
            'paiement_salaires.num_jours'
        )
        ->whereIn("salairs_{$year}.id_salarie", $employees);

    if ($status === 'actif') {
        $query->where('salaries.statut', 'actif');
    } elseif ($status === 'inactif') {
        $query->where('salaries.statut', 'inactif');
    }

    // Tri explicite par cotisation_cnss en ordre décroissant
    $query->orderByRaw('COALESCE(CAST(paiement_salaires.cotisation_cnss AS DECIMAL(10,2)), 0) DESC');

    $data = $query->get();

    // Log des données brutes pour débogage
    Log::info('Données brutes triées', [
        'year' => $year,
        'month' => $month,
        'data_count' => $data->count(),
        'cotisation_cnss_values' => $data->pluck('cotisation_cnss')->toArray()
    ]);

    if ($data->isEmpty()) {
        Log::warning('Aucune donnée trouvée', ['year' => $year, 'month' => $month, 'status' => $status]);
        return response()->json(['error' => "Aucune donnée disponible pour {$month} {$year}."], 404);
    }

    return response()->json(['data' => $data]);
}

    //fonction de chckbox   
        public function checkBaseSalary(Request $request)
        {



        
            $idSalarie = $request->input('id_salarie');
            $year = $request->input('year');
            $month = $request->input('month');

            Log::info('checkBaseSalary called', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month
            ]);

            

            if (!$idSalarie || !$year || !$month) {
                Log::error('Missing required parameters in checkBaseSalary');
                return response()->json(['error' => 'ID salarié, année ou mois manquant.'], 400);
            }

            $validMonths = array_keys([
                'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
                'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
                'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
            ]);
            if (!in_array($month, $validMonths)) {
                Log::error('Invalid month', ['month' => $month]);
                return response()->json(['error' => "Mois invalide : {$month}."], 400);
            }

            $tableName = "salairs_{$year}";
            if (!Schema::hasTable($tableName)) {
                Log::error('Salary table does not exist', ['table' => $tableName]);
                return response()->json(['error' => "Table salairs_{$year} n'existe pas."], 404);
            }


                $salaryData = DB::table($tableName)
                    ->where('id_salarie', $idSalarie)
                    ->first();
                        try {
            
                

                if (!$salaryData) {
                    Log::error('Employee not found', ['id_salarie' => $idSalarie, 'table' => $tableName]);
                    return response()->json(['error' => "Salarié ID {$idSalarie} non trouvé."], 404);
                }
                $hasBase = isset($salaryData->salaire) && $salaryData->salaire !== null;
            $hasNet  = isset($salaryData->salaire_net) && is_numeric($salaryData->salaire_net) && (float) $salaryData->salaire_net > 0;

            if (!$hasBase && !$hasNet) {
                Log::error('Base salary not defined', ['id_salarie' => $idSalarie, 'table' => $tableName]);
                return response()->json(['error' => "Aucun salaire de base ni salaire net défini pour ID {$idSalarie}."], 400);
            }

                /* if (!isset($salaryData->salaire) || $salaryData->salaire === null) {
                    Log::error('Base salary not defined', ['id_salarie' => $idSalarie, 'table' => $tableName]);
                    return response()->json(['error' => "Aucun salaire de base défini pour ID {$idSalarie}."], 400);
                } */

                $monthMap = [
                    'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
                    'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
                    'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
                ];
                $monthNumber = $monthMap[$month];

                $existingPayment = DB::table('paiement_salaires')
                    ->where('id_salarie', $idSalarie)
                    ->where('annee', $year)
                    ->where('mois', $monthNumber)
                    ->first();

                if ($existingPayment) {
                    Log::warning('Payment already exists', [
                        'id_salarie' => $idSalarie,
                        'year' => $year,
                        'month' => $month,
                        'payment_id' => $existingPayment->id
                    ]);
                    return response()->json(['error' => "Le salaire est deja calculée de  {$month} {$year}."], 400);
                }

                Log::info('Base salary found', [
                    'id_salarie' => $idSalarie,
                    'base_salary' => $salaryData->salaire,
                     'salaire_net' => $salaryData->salaire_net ?? null,
                ]);




                return response()->json([
                    // Renvoyer le salaire comme chaîne avec point décimal
                    'base_salary' => sprintf("%.2f", $salaryData->salaire),
                    'salaire_net' => $hasNet ? sprintf("%.2f", $salaryData->salaire_net) : null,
                    'id_salarie' => $idSalarie,
                    'year' => $year,
                    'month' => $month,
                    'nom_prenom' => $salaryData->nom . ' ' . $salaryData->prenom,
                    'matricule' => $salaryData->n_matricule_entreprise
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error in checkBaseSalary', [
                        'error' => $e->getMessage(),
                        'id_salarie' => $idSalarie,
                        'year' => $year,
                        'month' => $month
                    ]);
                    return response()->json(['error' => "Erreur serveur : {$e->getMessage()}"], 500);
                }


                
        }

    //modification d'anciennete indeviduele
            public function updateAnciennete(Request $request)
                {
                    try {
                        // Étape 1 : Récupérer id_salarie depuis la requête
                        $idSalarie = $request->input('id_salarie');

                        if (!$idSalarie) {
                            Log::error('Missing id_salarie in request', ['request' => $request->all()]);
                            return response()->json(['error' => 'ID salarié manquant.'], 400);
                        }

                        // Étape 2 : Vérifier l'existence du salarié et récupérer date_embauche
                        $employee = DB::table('salaries')
                            ->where('id', $idSalarie)
                            ->select('date_embauche', 'anciennete')
                            ->first();

                        if (!$employee || !$employee->date_embauche) {
                            Log::error('Employee not found or no hire date', [
                                'id_salarie' => $idSalarie,
                            ]);
                            return response()->json(['error' => 'Salarié ou date d\'embauche introuvable.'], 404);
                        }

                        // Étape 3 : Vérifier si le champ anciennete est différent de NULL
                        if (is_null($employee->anciennete)) {
                            Log::info('Anciennete is NULL, skipping update', [
                                'id_salarie' => $idSalarie,
                            ]);
                            return response()->json(['message' => 'Mise à jour non effectuée : ancienneté est NULL.'], 200);
                        }

                        // Étape 4 : Calculer l'ancienneté
                        $hireDate = Carbon::parse($employee->date_embauche);
                        $currentDate = Carbon::now();
                        $anciennete = $hireDate->diffInYears($currentDate);

                        // Ensure anciennete is non-negative
                        if ($hireDate->isFuture()) {
                            $anciennete = 0;
                            Log::warning('Hire date is in the future, setting anciennete to 0', [
                                'id_salarie' => $idSalarie,
                                'hire_date' => $employee->date_embauche,
                                'current_date' => $currentDate->toDateString(),
                            ]);
                        }

                        // Étape 5 : Mettre à jour le champ anciennete
                        DB::table('salaries')
                            ->where('id', $idSalarie)
                            ->update([
                                'anciennete' => $anciennete,
                                'updated_at' => now()
                            ]);

                        // Étape 6 : Journaliser la mise à jour
                        Log::info('Anciennete updated successfully', [
                            'id_salarie' => $idSalarie,
                            'anciennete' => $anciennete,
                            'hire_date' => $employee->date_embauche,
                            'current_date' => $currentDate->toDateString()
                        ]);

                        return response()->json(['success' => 'Ancienneté mise à jour avec succès.', 'anciennete' => $anciennete], 200);
                    } catch (\Exception $e) {
                        // Étape 7 : Gérer les erreurs
                        Log::error('Error updating anciennete', [
                            'id_salarie' => $idSalarie,
                            'error' => $e->getMessage()
                        ]);
                        return response()->json(['error' => 'Erreur lors de la mise à jour de l\'ancienneté : ' . $e->getMessage()], 500);
                    }
                }

    // modification d'anciennete globale 
    public function updateAllAnciennetes()
        {
                try {
                    // Vérifier si la table salaries existe
                    if (!Schema::hasTable('salaries')) {
                        Log::error('Table salaries does not exist');
                        return response()->json([
                            'error' => 'La table salaries n\'existe pas.'
                        ], 500);
                    }

                    // Récupérer tous les salariés avec une date d'embauche non nulle
                    $employees = DB::table('salaries')
                        ->whereNotNull('date_embauche')
                        ->select('id', 'date_embauche')
                        ->get();

                    if ($employees->isEmpty()) {
                        Log::warning('No employees found with non-null date_embauche');
                        return response()->json([
                            'success' => 'Aucun salarié avec une date d\'embauche valide trouvé.'
                        ], 200);
                    }

                    $updatedCount = 0;

                    foreach ($employees as $employee) {
                        // Calculer l'ancienneté
                        $hireDate = Carbon::parse($employee->date_embauche);
                        $currentDate = Carbon::now();
                        $anciennete = $hireDate->isFuture() ? 0 : $hireDate->diffInYears($currentDate);

                        // Mettre à jour le champ anciennete
                        DB::table('salaries')
                            ->where('id', $employee->id)
                            ->update([
                                'anciennete' => $anciennete,
                                'updated_at' => now()
                            ]);

                        // Journaliser la mise à jour
                        Log::info('Anciennete updated successfully', [
                            'id_salarie' => $employee->id,
                            'anciennete' => $anciennete,
                            'hire_date' => $employee->date_embauche,
                            'current_date' => $currentDate->toDateString()
                        ]);

                        $updatedCount++;
                    }

                    // Journaliser le résultat global
                    Log::info('All anciennetes updated', [
                        'total_updated' => $updatedCount,
                        'total_employees' => $employees->count()
                    ]);

                    return response()->json([
                        'success' => "Ancienneté mise à jour pour {$updatedCount} salariés sur {$employees->count()}."
                    ], 200);
                } catch (\Exception $e) {
                    Log::error('Error updating all anciennetes', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json([
                        'error' => 'Erreur lors de la mise à jour des anciennetés : ' . $e->getMessage()
                    ], 500);
                }
        }


 // fonction de calcule de salaire de tout les salaries 
public function calculateAndGeneratePaySlips(Request $request)
{
    try {
        $updateResponse = $this->updateAllAnciennetes();
        $updateData = json_decode($updateResponse->getContent(), true);
        if (isset($updateData['error'])) {
            Log::warning('Failed to update anciennetes before payslip calculation', [
                'error' => $updateData['error']
            ]);
            return response()->json(['error' => $updateData['error']], 500);
        }

        $validator = Validator::make($request->all(), [
            'year'  => 'required|integer|min:2000|max:2100',
            'month' => 'required|in:janvier,fevrier,mars,avril,mai,juin,juillet,aout,septembre,octobre,novembre,decembre',
             'selected_ids'   => 'required|array|min:1',   // ✅ Obligatoire
            'selected_ids.*' => 'integer',
            'calc_mode'      => 'nullable|in:salaire_base,salaire_net',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $year  = $request->input('year');
        $month = $request->input('month');
        $selectedIds = $request->input('selected_ids');
        $calcMode    = $request->input('calc_mode', 'salaire_base');
        $monthMap = [
            'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
        ];
        $monthNumber = $monthMap[$month];

        $startOfMonth = sprintf('%d-%02d-01', $year, $monthNumber);
        $endOfMonth   = date('Y-m-t', strtotime($startOfMonth));

      $tableName = "salairs_{$year}";

      // ✅ Filtrer : cochés ET colonne du mois = NULL ET pas démissionnés avant le mois
            $employeesWithNullMonth = DB::table($tableName)
                ->whereIn('id_salarie', $selectedIds)
                ->whereNull($month)
                ->whereExists(function ($query) use ($startOfMonth, $tableName) {
                    $query->select(DB::raw(1))
                        ->from('salaries')
                        ->whereRaw("{$tableName}.id_salarie = salaries.id")
                        ->where(function ($q) use ($startOfMonth) {
                            // Garder les actifs
                            $q->where('statut', '!=', 'inactif')
                            // OU les inactifs démissionnés PENDANT ou APRÈS le mois sélectionné
                            ->orWhere(function ($sub) use ($startOfMonth) {
                                $sub->where('statut', 'inactif')
                                    ->whereExists(function ($d) use ($startOfMonth) {
                                        $d->select(DB::raw(1))
                                            ->from('demissions')
                                            ->whereRaw('demissions.salarie_id = salaries.id')
                                            ->where('date_demission', '>=', $startOfMonth);
                                    });
                            });
                        });
                })
                ->pluck('id_salarie')
                ->toArray();

                    $employees   = DB::table('salaries')
                    ->whereIn('id', $employeesWithNullMonth)
                    ->get(['id', 'anciennete', 'situation_familiale', 'nombre_enfant', 'type_contrat', 'statut', 'n_matricule_entreprise']);
                    $payslips    = [];
                    $totalSalary = 0;

                    Log::info('Starting payslip calculation', [
                        'year'           => $year,
                        'month'          => $month,
                        'employee_count' => $employees->count()
                    ]);

                    foreach ($employees as $employee) {
                        $idSalarie  = $employee->id;
                        $matricule  = $employee->n_matricule_entreprise ?? 'N/A';
                        $isAnapec   = strtolower($employee->type_contrat) === 'anapec';

                        // Chargement configuration
                        $config = \App\Models\ConfigurationBp::where('id_salarier', $idSalarie)->first();

                        $primPanierInput          = $config ? (float) ($config->pripanier ?? 0)              : 0;
                        $indTransUrbainInput      = $config ? (float) ($config->indtransport ?? 0)           : 0;
                        $primeRepresentationInput = $config ? (float) ($config->prirepresentation ?? 0)      : 0;
                        $primeDeplacementInput    = $config ? (float) ($config->prideplacement ?? 0)         : 0;
                        $autresPrimesImposables   = $config ? (float) ($config->autrespriimposables ?? 0)    : 0;

                        $primesDiversInput        = $config ? (float) ($config->pridivers ?? 0)              : 0;

                        $applyCimr            = $config ? (bool) ($config->cotisation_cimr ?? false)         : false;
                        $doubleSalaryHolidays = $config ? (bool) ($config->double_salary_holidays ?? false)  : false;
                        $applyMutuelle        = $config ? (bool) ($config->cotisation_mutuelle ?? false)     : false;
                        $calculateOvertime    = $config ? (bool) ($config->calculate_overtime ?? false)      : false;
                        $applyFraisPro        = $config ? (bool) ($config->apply_frais_pro ?? true)          : true;
                        $applyIpe             = $config ? (bool) ($config->indemnite_pe ?? false)            : false;
                        $applyPrimeRendement  = $config ? (bool) ($config->apply_prime_rendement ?? true)    : true;

                        Log::info('Config loaded from configurationbp', [
                            'id_salarie'           => $idSalarie,
                            'matricule'            => $matricule,
                            'config_found'         => $config ? true : false,
                            'primPanierInput'      => $primPanierInput,
                            'hhhhhhhhhhhhhhhhhhhhhhhhhhh '  => $autresPrimesImposables ,
                            'indTransUrbainInput'  => $indTransUrbainInput,
                            'applyCimr'            => $applyCimr,
                            'doubleSalaryHolidays' => $doubleSalaryHolidays,
                            'applyMutuelle'        => $applyMutuelle,
                            'calculateOvertime'    => $calculateOvertime,
                            'applyFraisPro'        => $applyFraisPro,
                            'applyIpe'             => $applyIpe,
                            'applyPrimeRendement'  => $applyPrimeRendement,
                        ]);

                        Log::info('Processing employee', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);

                        // Vérifier démission
                        $resignationData = DB::table('demissions')
                            ->where('salarie_id', $idSalarie)
                            ->where('date_demission', '>=', $startOfMonth)
                            ->where('date_demission', '<=', $endOfMonth)
                            ->select('date_demission')
                            ->first();

                        $isResigned = strtolower($employee->statut) === 'inactif' && $resignationData;

                        $endOfPeriod    = $endOfMonth;
                        $workingDays    = 26;

                        if ($isResigned) {
                            $resignationDate = new \DateTime($resignationData->date_demission);
                            $startDate       = new \DateTime($startOfMonth);
                            $interval        = $startDate->diff($resignationDate);
                            $workingDays     = min($interval->days + 1, 26);
                            $endOfPeriod     = $resignationData->date_demission;
                            Log::info('Employee resigned: adjusting period', [
                                'id_salarie'       => $idSalarie,
                                'matricule'        => $matricule,
                                'resignation_date' => $resignationData->date_demission,
                                'working_days'     => $workingDays,
                                'end_of_period'    => $endOfPeriod
                            ]);
                        } else {
                            Log::info('Non-resigned employee: using full month', [
                                'id_salarie'     => $idSalarie,
                                'matricule'      => $matricule,
                                'working_days'   => $workingDays,
                                'start_of_month' => $startOfMonth,
                                'end_of_period'  => $endOfPeriod
                            ]);
                        }

                        // Vérifier table salaires
                        $tableName = "salairs_{$year}";
                        if (!Schema::hasTable($tableName)) {
                            Log::error('Salary table does not exist', ['table' => $tableName, 'id_salarie' => $idSalarie]);
                            continue;
                        }

       $salaryData = DB::table($tableName)->where('id_salarie', $idSalarie)->first();
        if (!$salaryData) {
            Log::error('Salary data not found', ['id_salarie' => $idSalarie, 'table' => $tableName]);
            continue;
        }

        // ── Mode SALAIRE NET : on réutilise la méthode existante ─────────────────
        if ($calcMode === 'salaire_net') {
            if (!isset($salaryData->salaire_net) || !is_numeric($salaryData->salaire_net) || (float) $salaryData->salaire_net <= 0) {
                Log::warning('Pas de salaire net défini, salarié ignoré', [
                    'id_salarie' => $idSalarie,
                    'matricule'  => $matricule
                ]);
                continue;
            }

    // Construire une Request pour réutiliser incrementSalaryFromNet
    $fakeRequest = new Request([
        'id_salarie'               => $idSalarie,
        'year'                     => $year,
        'month'                    => $month,
        'prime_panier'             => $primPanierInput,
        'ind_trans_urbain'         => $indTransUrbainInput,
        'prime_representation'     => $primeRepresentationInput,
        'prime_deplacement'        => $primeDeplacementInput,
        'autres_primes_imposables' => $autresPrimesImposables,
        'primes_divers'            => $primesDiversInput,
        'apply_cimr'               => $applyCimr,
        'apply_mutuelle'           => $applyMutuelle,
        'calculate_overtime'       => $calculateOvertime,
        'double_salary_holidays'   => $doubleSalaryHolidays,
        'apply_frais_pro'          => $applyFraisPro,
        'apply_ipe'                => $applyIpe,
        'apply_prime_rendement'    => $applyPrimeRendement,
    ]);

    $result = $this->incrementSalaryFromNet($fakeRequest);
    $resultData = json_decode($result->getContent(), true);

    if (isset($resultData['error'])) {
        Log::warning('Calcul net échoué pour ce salarié', [
            'id_salarie' => $idSalarie,
            'matricule'  => $matricule,
            'error'      => $resultData['error']
        ]);
        continue;
    }

    // ✅ CORRECTIF : générer le PDF (et donc la pieces_joint + statutspj) avant de continuer
    if (isset($resultData['details'])) {
        $netPayer = $resultData['details']['net_payer'] ?? 0;

        $pdfPath = null;
        $pdfRequest = new Request([
            'id_salarie' => $idSalarie,
            'year'       => $year,
            'month'      => $month,
            'details'    => $resultData['details']
        ]);
        $pdfResponse = $this->generatePaySlipPDF($pdfRequest);

        if ($pdfResponse->getStatusCode() === 200) {
            $pdfData = json_decode($pdfResponse->getContent(), true);
            $pdfPath = $pdfData['path'] ?? null;
            Log::info('PDF généré avec succès (mode net)', [
                'id_salarie' => $idSalarie,
                'matricule'  => $matricule,
                'path'       => $pdfPath
            ]);
        } else {
            Log::warning('Échec de la génération du PDF (mode net), on continue sans bloquer', [
                'id_salarie' => $idSalarie,
                'matricule'  => $matricule,
                'response'   => $pdfResponse->getContent()
            ]);
        }

        $payslips[] = [
            'id_salarie' => $idSalarie,
            'matricule'  => $matricule,
            'path'       => $pdfPath,
            'net_payer'  => $netPayer,
            'details'    => $resultData['details']
        ];
        $totalSalary += $netPayer;
    }

    continue; // ← très important : on ne fait PAS le calcul « base » pour ce salarié
    }

                    // ── Mode SALAIRE DE BASE (code actuel) ───────────────────────────────────
                    if (!isset($salaryData->salaire) || $salaryData->salaire === null) {
                        Log::error('Base salary not found', ['id_salarie' => $idSalarie, 'table' => $tableName]);
                        continue;
                    }

                        // Congés approuvés
                        $leaveData = DB::table('conger')
                            ->where('salarie_id', $idSalarie)
                            ->where('approbation', 2)
                            ->where(function ($query) use ($startOfMonth, $endOfPeriod) {
                                $query->whereBetween('date_debut', [$startOfMonth, $endOfPeriod])
                                    ->orWhereBetween('date_fin', [$startOfMonth, $endOfPeriod])
                                    ->orWhere(function ($query) use ($startOfMonth, $endOfPeriod) {
                                        $query->where('date_debut', '<=', $startOfMonth)
                                            ->where('date_fin', '>=', $endOfPeriod);
                                    });
                            })
                            ->first();

                                if ($leaveData) {
                                    Log::warning('Employee on approved leave', [
                                        'id_salarie'  => $idSalarie,
                                        'matricule'   => $matricule,
                                        'leave_start' => $leaveData->date_debut,
                                        'leave_end'   => $leaveData->date_fin
                                    ]);
                                }

                        $congesPayeCount = 0;

                        $conges = DB::table('conger')
                            ->where('salarie_id', $idSalarie)
                            ->where('approbation', 2)
                            ->where(function ($query) use ($startOfMonth, $endOfPeriod) {
                                $query->whereBetween('date_debut', [$startOfMonth, $endOfPeriod])
                                    ->orWhereBetween('date_fin', [$startOfMonth, $endOfPeriod])
                                    ->orWhere(function ($query) use ($startOfMonth, $endOfPeriod) {
                                        $query->where('date_debut', '<=', $startOfMonth)
                                            ->where('date_fin', '>=', $endOfPeriod);
                                    });
                            })
                            ->get(['date_debut', 'date_fin', 'num_j']);

                        foreach ($conges as $conge) {
                            $debutConge = new \DateTime($conge->date_debut);
                            $finConge   = new \DateTime($conge->date_fin);
                            $debutMois  = new \DateTime($startOfMonth);
                            $finPeriode = new \DateTime($endOfPeriod);

                            $debut = $debutConge > $debutMois ? $debutConge : $debutMois;
                            $fin   = $finConge < $finPeriode  ? $finConge   : $finPeriode;

                            if ($debut <= $fin) {
                                $joursIntersection = (int) $debut->diff($fin)->days;
                                $congesPayeCount += min($joursIntersection, (int) $conge->num_j);
                            }
                        }

                        $congesPayeCount = (int) $congesPayeCount;

                        Log::info('Paid leave days calculated (borné à la période)', [
                            'id_salarie'        => $idSalarie,
                            'conges_paye_count' => $congesPayeCount,
                            'start_of_month'    => $startOfMonth,
                            'end_of_period'     => $endOfPeriod
                        ]);

                        // ── 1. Jours fériés (même logique incrementSalary) ───────────────────
                        $joursFeries = DB::table('jour_feries')
                            ->where(function ($query) use ($startOfMonth, $endOfPeriod) {
                                $query->whereBetween('date_debut', [$startOfMonth, $endOfPeriod])
                                    ->orWhereBetween('date_fin', [$startOfMonth, $endOfPeriod])
                                    ->orWhere(function ($query) use ($startOfMonth, $endOfPeriod) {
                                        $query->where('date_debut', '<=', $startOfMonth)
                                            ->where('date_fin', '>=', $endOfPeriod);
                                    });
                            })
                            ->get();

                        $joursFeriesCount = 0;
                        foreach ($joursFeries as $ferie) {
                            $dateDebut = new \DateTime($ferie->date_debut);
                            $dateFin   = new \DateTime($ferie->date_fin ?? $ferie->date_debut);
                            while ($dateDebut <= $dateFin) {
                                if ($dateDebut->format('Y-m') === substr($startOfMonth, 0, 7)) {
                                    if ($dateDebut->format('w') != 0) { // exclure dimanches
                                        $joursFeriesCount += 1;
                                    }
                                }
                                $dateDebut->modify('+1 day');
                            }
                        }
                        $joursFeriesCount = (int) $joursFeriesCount;

                        // ── 2. Jours fériés travaillés ────────────────────────────────────────
                        $joursFeriesTravaillesCount = DB::table('jour_feries')
                            ->join('presence', function ($join) use ($idSalarie, $startOfMonth, $endOfPeriod) {
                                $join->on(DB::raw('presence.date'), '>=', DB::raw('jour_feries.date_debut'))
                                    ->on(DB::raw('presence.date'), '<=', DB::raw('jour_feries.date_fin'))
                                    ->where('presence.salarie_id', $idSalarie)
                                    ->where('presence.statuts', 1)
                                    ->whereBetween('presence.date', [$startOfMonth, $endOfPeriod]);
                            })
                            ->where(function ($query) use ($startOfMonth, $endOfPeriod) {
                                $query->whereBetween('jour_feries.date_debut', [$startOfMonth, $endOfPeriod])
                                    ->orWhereBetween('jour_feries.date_fin', [$startOfMonth, $endOfPeriod])
                                    ->orWhere(function ($query) use ($startOfMonth, $endOfPeriod) {
                                        $query->where('date_debut', '<=', $startOfMonth)
                                            ->where('date_fin', '>=', $endOfPeriod);
                                    });
                            })
                            ->sum('jour_feries.nbr_jours');

                        $joursFeriesTravaillesCount = (int) min($joursFeriesTravaillesCount, $joursFeriesCount);

                        // Jours fériés NON travaillés uniquement
                        $joursFeriesCount = max(0, $joursFeriesCount - $joursFeriesTravaillesCount);

                        Log::info('Public holidays calculated', [
                            'id_salarie'                 => $idSalarie,
                            'matricule'                  => $matricule,
                            'joursFeriesCount'           => $joursFeriesCount,
                            'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount,
                            'start_of_month'             => $startOfMonth,
                            'end_of_period'              => $endOfPeriod
                        ]);

                    // ── 3. Jours travaillés hors fériés ────────────────────────────────────
                $joursTravails = DB::table('presence')
                    ->where('salarie_id', $idSalarie)
                    ->where('statuts', 1)
                    ->whereBetween('date', [$startOfMonth, $endOfPeriod])
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('jour_feries')
                            ->whereRaw('presence.date >= jour_feries.date_debut')
                            ->whereRaw('presence.date <= jour_feries.date_fin');
                    })
                    ->count();

                Log::info('Jours de présence hors fériés calculés', [
                    'id_salarie'            => $idSalarie,
                    'joursPresence_normaux' => $joursTravails,
                    'start_of_month'        => $startOfMonth,
                    'end_of_period'         => $endOfPeriod
                ]);
                        // Calcul des jours de présence NORMALE (hors jours fériés)
                                        $joursPresenceReel = $joursTravails - $joursFeriesTravaillesCount - $joursFeriesCount;

        // S'assurer que la somme totale ne dépasse pas 26
        $autresJours = $joursFeriesCount + $joursFeriesTravaillesCount + $congesPayeCount;
        $joursPresence = max(0, 26 - $autresJours);

            // joursCalcules fixé à 26 comme dans incrementSalary
            $joursCalcules = 26;

            Log::info('Jours calculés', [
                'id_salarie'                 => $idSalarie,
                'matricule'                  => $matricule,
                'joursCalcules'              => $joursCalcules,
                'joursPresence'              => $joursPresence,
                'joursFeriesCount'           => $joursFeriesCount,
                'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount
            ]);

            if ($joursPresence == 0 && $congesPayeCount == 0) {
                Log::error('No presence or paid leave recorded', [
                    'id_salarie' => $idSalarie,
                    'matricule'  => $matricule,
                    'year'       => $year,
                    'month'      => $month
                ]);
                continue;
            }
            $baseSalary = (float) $salaryData->salaire;
                Log::info('dailySalary', [
                    'jjjjjjjjjjjjjj'  =>$baseSalary,
                ]);

            $dailySalary = $baseSalary / 26;
            Log::info('dailySalary', [
                    'dailySalaryyyyyyuy'  =>$dailySalary,
                ]);

            // salaired comme dans incrementSalary
           $salaired = $joursPresence > 26 ? round($dailySalary * 26) : round($dailySalary * $joursPresence);

            // ── Heures supplémentaires (même logique incrementSalary) ─────────────
            $heurSuppPresence       = 0;
            $heurSuppPresencematin  = 0;
            $heurSuppPresenceNuit   = 0;
            $heurSuppPresencematinF = 0;
            $heurSuppPresenceNuitF  = 0;
            $heurSupp25             = 0;
            $heurSupp50             = 0;
            $heurSupp100            = 0;
            $tauxHeureSupp          = 0;

            if ($calculateOvertime) {
                $heurSuppPresence = DB::table('presence')
                    ->where('salarie_id', $idSalarie)
                    ->where('statuts', 1)
                    ->whereBetween('date', [$startOfMonth, $endOfPeriod])
                    ->sum('heures');

                $heurSuppPresencematin = DB::table('presence')
                    ->where('salarie_id', $idSalarie)
                    ->where('statuts', 1)
                    ->where('type_heure_supp', 'matin')
                    ->whereBetween('date', [$startOfMonth, $endOfPeriod])
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('jour_feries')
                            ->whereRaw('presence.date >= jour_feries.date_debut')
                            ->whereRaw('presence.date <= jour_feries.date_fin');
                    })
                    ->sum('heures');

                $heurSuppPresenceNuit = DB::table('presence')
                    ->where('salarie_id', $idSalarie)
                    ->where('statuts', 1)
                    ->where('type_heure_supp', 'nuit')
                    ->whereBetween('date', [$startOfMonth, $endOfPeriod])
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('jour_feries')
                            ->whereRaw('presence.date >= jour_feries.date_debut')
                            ->whereRaw('presence.date <= jour_feries.date_fin');
                    })
                    ->sum('heures');

                $heurSuppPresencematinF = DB::table('presence')
                    ->where('salarie_id', $idSalarie)
                    ->where('statuts', 1)
                    ->where('type_heure_supp', 'matin')
                    ->whereBetween('date', [$startOfMonth, $endOfPeriod])
                    ->whereExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('jour_feries')
                            ->whereRaw('presence.date >= jour_feries.date_debut')
                            ->whereRaw('presence.date <= jour_feries.date_fin');
                    })
                    ->sum('heures');

                $heurSuppPresenceNuitF = DB::table('presence')
                    ->where('salarie_id', $idSalarie)
                    ->where('statuts', 1)
                    ->where('type_heure_supp', 'nuit')
                    ->whereBetween('date', [$startOfMonth, $endOfPeriod])
                    ->whereExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('jour_feries')
                            ->whereRaw('presence.date >= jour_feries.date_debut')
                            ->whereRaw('presence.date <= jour_feries.date_fin');
                    })
                    ->sum('heures');

                // tauxHeureSupp seulement si heurSuppPresence != 0
                if ($heurSuppPresence != 0) {
                    $tauxHeureSupp = round($baseSalary / 191, 2);
                }

                if ($heurSuppPresencematin != 0) {
                    $heurSupp25 = round($heurSuppPresencematin * $tauxHeureSupp * 1.25, 2);
                }
                if (($heurSuppPresencematinF + $heurSuppPresenceNuit) != 0) {
                    $heurSupp50 = round(($heurSuppPresencematinF + $heurSuppPresenceNuit) * $tauxHeureSupp * 1.5, 2);
                }
                if ($heurSuppPresenceNuitF != 0) {
                    $heurSupp100 = round($heurSuppPresenceNuitF * $tauxHeureSupp * 2, 2);
                }

                Log::info('Overtime calculated', [
                    'id_salarie'             => $idSalarie,
                    'matricule'              => $matricule,
                    'heurSuppPresence'       => $heurSuppPresence,
                    'heurSuppPresencematin'  => $heurSuppPresencematin,
                    'heurSuppPresenceNuit'   => $heurSuppPresenceNuit,
                    'heurSuppPresencematinF' => $heurSuppPresencematinF,
                    'heurSuppPresenceNuitF'  => $heurSuppPresenceNuitF,
                    'tauxHeureSupp'          => $tauxHeureSupp,
                    'heurSupp25'             => $heurSupp25,
                    'heurSupp50'             => $heurSupp50,
                    'heurSupp100'            => $heurSupp100,
                ]);
            }

            // Jours fériés montants
            $jourFerier = round($joursFeriesCount * $dailySalary, 2);

            $joursFeriesTravailles = $doubleSalaryHolidays
                ? round($joursFeriesTravaillesCount * $dailySalary * 2, 2)
                : round($joursFeriesTravaillesCount * $dailySalary, 2);

            $congesPaye = round($congesPayeCount * $dailySalary, 2);

            // Primes non imposables (même logique incrementSalary)
            $primePanier        = round(($primPanierInput / 26) * $joursCalcules, 2);
            $indTransUrbain     = round(($indTransUrbainInput / 26) * $joursCalcules, 2);
            $primeRepresentation= round(($primeRepresentationInput / 26) * $joursCalcules, 2);
            $primeDeplacement   = round(($primeDeplacementInput / 26) * $joursCalcules, 2);
            $primesDivers       = round(($primesDiversInput / 26) * $joursCalcules, 2);

            Log::info('dailySalary', [
                    'ssssssssssssssssssssssss'  =>  $primesDivers,
                ]);

            $autresPrimesImposablesCalculated = round(($autresPrimesImposables / 26) * $joursCalcules, 2);

            $primNonimposables = round($primePanier + $indTransUrbain + $primeRepresentation + $primeDeplacement + $primesDivers, 2);

            Log::info('Primes non imposables calculated', [
                'id_salarie'        => $idSalarie,
                'matricule'         => $matricule,
                'primePanier'       => $primePanier,
                'indTransUrbain'    => $indTransUrbain,
                'primNonimposables' => $primNonimposables,
            ]);

            // ── salaire_base_imposable (même formule incrementSalary) ─────────────
           $salaire_base_imposable = round( round($dailySalary * $joursCalcules, 2) + $heurSupp25 + $heurSupp50 + $heurSupp100 , 2);
            Log::info('dailySalary', [
                    'bbbbbbbbbbbbbbbbbbbb'  => $salaire_base_imposable,
                ]);

            Log::info('Salaire base imposable calculated', [
                'id_salarie'             => $idSalarie,
                'matricule'              => $matricule,
                'dailySalary_x_jours'    => round($dailySalary * $joursCalcules, 2),
                'heurSupp25'             => $heurSupp25,
                'heurSupp50'             => $heurSupp50,
                'heurSupp100'            => $heurSupp100,
                'salaire_base_imposable' => $salaire_base_imposable
            ]);

            // Prime de rendement journalière
            $prime_journaliere      = 0;
            $base_prime_journaliere = $joursPresence + $joursFeriesCount + $congesPayeCount + $joursFeriesTravaillesCount;
            $taux_prime_journaliere = $base_prime_journaliere > 26 ? $base_prime_journaliere - 26 : 0;

            if ($applyPrimeRendement) {
                $prime_journaliere = round($taux_prime_journaliere * 1.25 * $base_prime_journaliere, 2);
            }

            Log::info('Prime de rendement journalière calculated', [
                'id_salarie'             => $idSalarie,
                'matricule'              => $matricule,
                'base_prime_journaliere' => $base_prime_journaliere,
                'taux_prime_journaliere' => $taux_prime_journaliere,
                'prime_journaliere'      => $prime_journaliere
            ]);

            // Prime d'ancienneté (basée sur salaire_base_imposable comme incrementSalary)
            $primeAnciennete = 0;
            $tauxAnciennete  = 0;
            if (!is_null($employee->anciennete)) {
                $tauxData = AncienneteTaux::where('an_min', '<=', $employee->anciennete)
                    ->where(function ($query) use ($employee) {
                        $query->where('an_max', '>=', $employee->anciennete)->orWhereNull('an_max');
                    })->first();
                if ($tauxData && isset($tauxData->taux)) {
                    $tauxAnciennete  = (float) $tauxData->taux;
                    $primeAnciennete = round($salaire_base_imposable * ($tauxAnciennete / 100), 2);
                    Log::info('Prime anciennete calculated', [
                        'id_salarie'       => $idSalarie,
                        'matricule'        => $matricule,
                        'anciennete'       => $employee->anciennete,
                        'taux'             => $tauxAnciennete,
                        'prime_anciennete' => $primeAnciennete
                    ]);
                } else {
                    Log::warning('No taux found for anciennete', [
                        'id_salarie' => $idSalarie,
                        'matricule'  => $matricule,
                        'anciennete' => $employee->anciennete
                    ]);
                }
            }

            // salaireBI (même formule incrementSalary)
            $salaireBI = round(
                $salaire_base_imposable +
                $primeAnciennete +
                $autresPrimesImposablesCalculated,
                2
            );

        $joursCongesAvecPresence      = 0;
        $congesPayeNonTravaillesCount = 0;
        $congesPayeTravailles         = 0;
        $congesPayeNonTravailles      = 0;

        if ($congesPayeCount > 0) {
            $joursCongesAvecPresence = DB::table('presence')
                ->where('salarie_id', $idSalarie)
                ->where('statuts', 1)
                ->whereBetween('date', [$startOfMonth, $endOfPeriod])
                ->whereExists(function ($query) use ($idSalarie, $endOfPeriod) {
                    $query->select(DB::raw(1))
                        ->from('conger')
                        ->where('conger.salarie_id', $idSalarie)
                        ->where('conger.approbation', 2)
                        ->whereRaw('presence.date >= conger.date_debut')
                        ->whereRaw("presence.date <= LEAST(conger.date_fin, ?)", [$endOfPeriod]);
                })
                ->count();

            $congesPayeNonTravaillesCount = max(0, $congesPayeCount - $joursCongesAvecPresence);
            $congesPayeTravailles         = round($joursCongesAvecPresence * $dailySalary, 2);
            $congesPayeNonTravailles      = round($congesPayeNonTravaillesCount * $dailySalary, 2);

            Log::info('Séparation congés travaillés / non travaillés', [
                'id_salarie'                   => $idSalarie,
                'congesPayeCount'              => $congesPayeCount,
                'joursCongesAvecPresence'      => $joursCongesAvecPresence,
                'congesPayeNonTravaillesCount' => $congesPayeNonTravaillesCount,
                'congesPayeTravailles'         => $congesPayeTravailles,
                'congesPayeNonTravailles'      => $congesPayeNonTravailles,
            ]);

            if ($joursCongesAvecPresence > 0) {
                $salaireBI = round($salaireBI + $congesPayeTravailles, 2);
                Log::info('Congés travaillés ajoutés au salaireBI', [
                    'id_salarie'           => $idSalarie,
                    'congesPayeTravailles' => $congesPayeTravailles,
                    'salaireBI_final'      => $salaireBI,
                ]);
            }
        }

            // salaireBG (même formule incrementSalary)
            $salaireBG = round($salaireBI + $primNonimposables, 2);

            Log::info('SalaireBG calculated', [
                'id_salarie'        => $idSalarie,
                'matricule'         => $matricule,
                'salaireBI'         => $salaireBI,
                'primNonimposables' => $primNonimposables,
                'salaireBG'         => $salaireBG
            ]);

            // Cotisations
            $cotisationCNSS     = 0;
            $cotisationAMO      = 0;
            $cotisationCIMR     = 0;
            $tauxCIMR           = 0;
            $cotisationMutuelle = 0;
            $tauxMutuelle       = 0;
            $indeminitePE       = 0;
            $fraisProfessionnels= 0;
            $tauxFrais          = 0;
            $cnssPs             = 0;
            $amoPs              = 0;
            $ipePs              = 0;
            $basePlafond        = 0;

            if (!$isAnapec) {
                $cotisations = Cotisations::select('cnss_ps', 'amo_ps', 'ipe_ps', 'plafond_ipe', 'charge_de_famille', 'plafond_cnss', 'taux_cimr', 'taux_mutuelle')->first();
                if (!$cotisations) {
                    Log::error('No cotisations found', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
                    continue;
                }

                if (!is_null($cotisations->cnss_ps)) {
                    $cnssPs         = (float) $cotisations->cnss_ps;
                    $cotisationCNSS = ($salaireBI > ($cotisations->plafond_cnss ?? 0))
                        ? round(($cotisations->plafond_cnss ?? 0) * ($cnssPs / 100), 2)
                        : round($salaireBI * ($cnssPs / 100), 2);
                    $basePlafond = $cnssPs * ($cotisations->plafond_ipe ?? 0);
                    Log::info('Cotisation CNSS calculated', [
                        'id_salarie'      => $idSalarie,
                        'matricule'       => $matricule,
                        'cotisation_cnss' => $cotisationCNSS
                    ]);
                }

                if (!is_null($cotisations->amo_ps)) {
                    $amoPs         = (float) $cotisations->amo_ps;
                    $cotisationAMO = round($salaireBI * ($amoPs / 100), 2);
                    Log::info('Cotisation AMO calculated', [
                        'id_salarie'     => $idSalarie,
                        'matricule'      => $matricule,
                        'cotisation_amo' => $cotisationAMO
                    ]);
                }

                if ($applyCimr && !is_null($cotisations->taux_cimr)) {
                    $tauxCIMR       = (float) $cotisations->taux_cimr;
                    $cotisationCIMR = round($salaireBI * ($tauxCIMR / 100), 2);
                    Log::info('Cotisation CIMR calculated', [
                        'id_salarie'      => $idSalarie,
                        'matricule'       => $matricule,
                        'cotisation_cimr' => $cotisationCIMR
                    ]);
                }

                if ($applyMutuelle && !is_null($cotisations->taux_mutuelle)) {
                    $tauxMutuelle       = (float) $cotisations->taux_mutuelle;
                    $cotisationMutuelle = round($salaireBI * ($tauxMutuelle / 100), 2);
                    Log::info('Cotisation Mutuelle calculated', [
                        'id_salarie'          => $idSalarie,
                        'matricule'           => $matricule,
                        'cotisation_mutuelle' => $cotisationMutuelle
                    ]);
                }

                if ($applyIpe && !is_null($cotisations->ipe_ps) && !is_null($cotisations->plafond_ipe)) {
                    $ipePs        = (float) $cotisations->ipe_ps;
                    $baseCalcul   = min($salaireBI, $cotisations->plafond_ipe);
                    $indeminitePE = round($baseCalcul * ($ipePs / 100), 2);
                    Log::info('Indemnité IPE calculated', [
                        'id_salarie'    => $idSalarie,
                        'matricule'     => $matricule,
                        'indeminite_pe' => $indeminitePE
                    ]);
                }

                if ($applyFraisPro) {
                    $fraisProData = DB::table('frais_professionnels')
                        ->where('sbi_min', '<=', $salaireBI)
                        ->where(function ($query) use ($salaireBI) {
                            $query->where('sbi_max', '>=', $salaireBI)->orWhereNull('sbi_max');
                        })->first();
                    if ($fraisProData && isset($fraisProData->taux, $fraisProData->plafond)) {
                        $tauxFrais           = (float) $fraisProData->taux;
                        $plafondFrais        = (float) $fraisProData->plafond;
                        $fraisProfessionnels = round(min($salaireBI * ($tauxFrais / 100), $plafondFrais), 2);
                        Log::info('Frais professionnels calculated', [
                            'id_salarie'           => $idSalarie,
                            'matricule'            => $matricule,
                            'frais_professionnels' => $fraisProfessionnels
                        ]);
                    } else {
                        Log::warning('No frais professionnels data found', [
                            'id_salarie' => $idSalarie,
                            'matricule'  => $matricule,
                            'salaireBI'  => $salaireBI
                        ]);
                    }
                }

            } else {
                Log::info('Anapec contract: Skipping cotisations', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            $salaireNI = round($salaireBI - $cotisationCNSS - $cotisationAMO - $indeminitePE - $cotisationCIMR - $cotisationMutuelle - $fraisProfessionnels, 2);

            // IR
            $irBrut          = 0;
            $irNet           = 0;
            $irTaux          = 0;
            $chargeFamiliale = 0;
            $sommeADeduire   = 0;

            if (!$isAnapec) {
                $irData = ImpotSurRevenu::where('revenu_min', '<=', $salaireNI)
                    ->where(function ($query) use ($salaireNI) {
                        $query->where('revenu_max', '>=', $salaireNI)->orWhereNull('revenu_max');
                    })->first();

                if ($irData && isset($irData->taux, $irData->somme_a_deduire)) {
                    $irTaux        = (float) $irData->taux;
                    $sommeADeduire = (float) $irData->somme_a_deduire;
                    $irBrut        = round(($salaireNI * ($irTaux / 100)) - $sommeADeduire, 2);
                    Log::info('IR brut calculated', [
                        'id_salarie'      => $idSalarie,
                        'matricule'       => $matricule,
                        'salaireNI'       => $salaireNI,
                        'taux'            => $irTaux,
                        'somme_a_deduire' => $sommeADeduire,
                        'ir_brut'         => $irBrut
                    ]);
                } else {
                    Log::warning('No IR tranche found for salaireNI', [
                        'id_salarie' => $idSalarie,
                        'matricule'  => $matricule,
                        'salaireNI'  => $salaireNI
                    ]);
                }

                if (strtolower($employee->situation_familiale) === 'marié') {
                    $cotisationsCharge = Cotisations::select('charge_de_famille')->first();
                    if ($cotisationsCharge && !is_null($cotisationsCharge->charge_de_famille)) {
                        $nombreEnfantLimite = min($employee->nombre_enfant ?? 0, 5);
                        $chargeFamiliale    = $cotisationsCharge->charge_de_famille + ($cotisationsCharge->charge_de_famille * $nombreEnfantLimite);
                        Log::info('Charge familiale calculated', [
                            'id_salarie'       => $idSalarie,
                            'matricule'        => $matricule,
                            'charge_familiale' => $chargeFamiliale
                        ]);
                    } else {
                        Log::warning('No charge_de_famille found', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
                    }
                }

                // IR net (même logique incrementSalary)
                if ($chargeFamiliale == 0) {
                    $irNet = $irBrut;
                } elseif ($irBrut > $chargeFamiliale) {
                    $irNet = round($irBrut - $chargeFamiliale, 2);
                } else {
                    $irNet = 0;
                }

                Log::info('IR net calculated', [
                    'id_salarie'       => $idSalarie,
                    'matricule'        => $matricule,
                    'ir_brut'          => $irBrut,
                    'charge_familiale' => $chargeFamiliale,
                    'ir_net'           => $irNet
                ]);
            } else {
                Log::info('Anapec contract: Skipping IR calculation', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            // Avancement
            $avancement = Depences::where('salarie_id', $idSalarie)
                ->whereHas('natureDepense', function ($query) {
                    $query->where('designation', 'Avancements de salaires');
                })
                ->where('mois_depenses', sprintf('%02d', $monthNumber))
                ->whereYear('date', $year)
                ->sum('montant');

            Log::info('Avancements de salaires retrieved', [
                'id_salarie' => $idSalarie,
                'matricule'  => $matricule,
                'avancement' => $avancement
            ]);

            // Net à payer
            $netPayer = round($salaireBG - ($cotisationCNSS + $cotisationAMO + $indeminitePE + $cotisationCIMR + $cotisationMutuelle + $irNet) - $avancement, 2);

            $netPayerSansExtras = round($salaireBI - ($cotisationCNSS + $cotisationAMO + $indeminitePE + $cotisationCIMR + $cotisationMutuelle + $irNet), 2);

            Log::info('Net payer calculated', [
                'id_salarie' => $idSalarie,
                'matricule'  => $matricule,
                'net_payer'  => $netPayer
            ]);

            // Données pour insertion
            $paymentData = [
                'id_salarie'                 => $idSalarie,
                'annee'                      => $year,
                'mois'                       => $monthNumber,
                'salaire'                    => $netPayer,
                'typer'                      => 'a',
                'statutspj'                  => 0,
                'created_at'                 => now(),
                'updated_at'                 => now(),
                'salaired'                   => $salaired,
                
                'prime_anciennete'           => $primeAnciennete,
                'professional_tax_deduction' => $fraisProfessionnels,
                'daily_salary'               => $dailySalary,
                'indemnite_pe'               => $indeminitePE,
                'ir_brut'                    => $irBrut,
                'net_payer_sans_extras'      => $netPayerSansExtras,
                'charge_de_famille'          => $chargeFamiliale,
                'cotisation_amo'             => $cotisationAMO,
                'cotisation_cnss'            => $cotisationCNSS,
                'cotisation_cimr'            => $cotisationCIMR,
                'cotisation_mutuelle'        => $cotisationMutuelle,
                'taux_cimr'                  => $tauxCIMR,
                'taux_mutuelle'              => $tauxMutuelle,
                'irNet'                      => $irNet,
                'salaireBI'                  => $salaireBI,
                'salaireNI'                  => $salaireNI,
                'salaireBG'                  => $salaireBG,
                'avancements_sal'            => $avancement,
                'basePlafond'                => $basePlafond,
                'tauxAnciennete'             => $tauxAnciennete,
                'cnss_ps'                    => $cnssPs,
                'amo_ps'                     => $amoPs,
                'ipe_ps'                     => $ipePs,
                'tauxFrais'                  => $tauxFrais,
                'irData'                     => $irTaux,
                'prime_panier'               => $primePanier,
                'ind_trans_urbain'           => $indTransUrbain,
                'jour_ferie'                 => $jourFerier,
                'jours_feries_travailles'    => $joursFeriesTravailles,
                'conges_paye'                => $congesPaye,
                'conges_paye_count'          => $congesPayeCount,
                'joursFeriesCount'           => $joursFeriesCount,
                'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount,
                'prime_journaliere'          => $prime_journaliere,
                'base_prime_journaliere'     => $base_prime_journaliere,
                'taux_prime_journaliere'     => $taux_prime_journaliere,
                'somme_a_deduire'            => $sommeADeduire,
                'num_jours'                  => $joursPresence,
                'primNonimposables'          => $primNonimposables,
                'heurSuppPresence'           => (float) $heurSuppPresence,
                'heurSuppPresencematin'      => (float) $heurSuppPresencematin,
                'heurSuppPresenceNuit'       => (float) $heurSuppPresenceNuit,
                'heurSuppPresencematinF'     => (float) $heurSuppPresencematinF,
                'heurSuppPresenceNuitF'      => (float) $heurSuppPresenceNuitF,
                'heurSupp25'                 => (float) $heurSupp25,
                'heurSupp50'                 => (float) $heurSupp50,
                'heurSupp100'                => (float) $heurSupp100,
                'tauxHeureSupp'              => (float) $tauxHeureSupp,
                'salaireBaseImposable'       => (float) $salaire_base_imposable,
                'joursCalcules'              => (int) $joursCalcules,
            ];

            // Colonnes obligatoires
            $requiredColumns = [
                'jour_ferie', 'jours_feries_travailles', 'conges_paye', 'conges_paye_count',
                'joursFeriesCount', 'joursFeriesTravaillesCount', 'prime_journaliere',
                'base_prime_journaliere', 'taux_prime_journaliere', 'salaireBG'
            ];
            foreach ($requiredColumns as $col) {
                if (!Schema::hasColumn('paiement_salaires', $col)) {
                    Log::error("Column {$col} not found in paiement_salaires", ['id_salarie' => $idSalarie]);
                    return response()->json(['error' => "La colonne {$col} n'existe pas dans la table paiement_salaires."], 500);
                }
            }

            // Colonnes optionnelles
            $optionalColumns = [
                'num_jours', 'tauxAnciennete', 'cnss_ps', 'amo_ps', 'ipe_ps',
                'tauxFrais', 'irData', 'avancements_sal', 'cotisation_cimr',
                'cotisation_mutuelle', 'taux_cimr', 'taux_mutuelle', 'somme_a_deduire',
                'primNonimposables', 'heurSuppPresence', 'heurSuppPresencematin',
                'heurSuppPresenceNuit', 'heurSuppPresencematinF', 'heurSuppPresenceNuitF',
                'heurSupp25', 'heurSupp50', 'heurSupp100', 'tauxHeureSupp',
                'salaireBaseImposable', 'joursCalcules'
            ];
            foreach ($optionalColumns as $col) {
                if (!Schema::hasColumn('paiement_salaires', $col)) {
                    Log::warning("Column {$col} not found in paiement_salaires", ['id_salarie' => $idSalarie]);
                    unset($paymentData[$col]);
                }
            }

            $paymentId = DB::table('paiement_salaires')->insertGetId($paymentData);
            Log::info('Payment inserted into paiement_salaires', [
                'payment_id' => $paymentId,
                'id_salarie' => $idSalarie,
                'matricule'  => $matricule,
                'salaireBG'  => $salaireBG
            ]);

            // Détails pour PDF
            $details = [
                'net_payer'                  => $netPayer,
                'base_salary'                => $baseSalary,
                'daily_salary'               => $dailySalary,
                'salaired'                   => $salaired,
                'salaireBI'                  => $salaireBI,
                'salaireNI'                  => $salaireNI,
                'salaireBG'                  => $salaireBG,
                'ir_brut'                    => $irBrut,
                'ir_net'                     => $irNet,
                'cotisation_cnss'            => $cotisationCNSS,
                'cotisation_amo'             => $cotisationAMO,
                'cotisation_cimr'            => $cotisationCIMR,
                'cotisation_mutuelle'        => $cotisationMutuelle,
                'indeminite_pe'              => $indeminitePE,
                'frais_professionnels'       => $fraisProfessionnels,
                'charge_familiale'           => $chargeFamiliale,
                'prime_anciennete'           => $primeAnciennete,
                'autresPrimesImposables'     => $autresPrimesImposablesCalculated,
                'prime_representation'       => $primeRepresentation,
                'PrimeDeplacement'           => $primeDeplacement,
                'working_days'               => $workingDays,
                'avancement'                 => $avancement,
                'basePlafond'                => $basePlafond,
                'tauxAnciennete'             => $tauxAnciennete,
                'cnss_ps'                    => $cnssPs,
                'amo_ps'                     => $amoPs,
                'ipe_ps'                     => $ipePs,
                'tauxFrais'                  => $tauxFrais,
                'irData'                     => $irTaux,
                'prime_panier'               => $primePanier,
                'ind_trans_urbain'           => $indTransUrbain,
                'primesDivers'               => $primesDivers,
                'jour_ferie'                 => $jourFerier,
                'jours_feries_travailles'    => $joursFeriesTravailles,
                'conges_paye'                => $congesPaye,
                'conges_paye_count'          => $congesPayeCount,
                'joursFeriesCount'           => $joursFeriesCount,
                'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount,
                'prime_journaliere'          => $prime_journaliere,
                'base_prime_journaliere'     => $base_prime_journaliere,
                'taux_prime_journaliere'     => $taux_prime_journaliere,
                'num_jours'                  => $joursPresence,
                'somme_a_deduire'            => $sommeADeduire,
                'taux_cimr'                  => $tauxCIMR,
                'taux_mutuelle'              => $tauxMutuelle,
                'primNonimposables'          => $primNonimposables,
                'net_payer_sans_extras'      => $netPayerSansExtras,
                'heurSuppPresence'           => (float) $heurSuppPresence,
                'heurSuppPresencematin'      => (float) $heurSuppPresencematin,
                'heurSuppPresenceNuit'       => (float) $heurSuppPresenceNuit,
                'heurSuppPresencematinF'     => (float) $heurSuppPresencematinF,
                'heurSuppPresenceNuitF'      => (float) $heurSuppPresenceNuitF,
                'heurSupp25'                 => (float) $heurSupp25,
                'heurSupp50'                 => (float) $heurSupp50,
                'heurSupp100'                => (float) $heurSupp100,
                'tauxHeureSupp'              => (float) $tauxHeureSupp,
                'salaireBaseImposable'       => (float) $salaire_base_imposable,
                'joursCalcules'              => (int) $joursCalcules,

                'conges_paye_non_travailles'       => $congesPayeNonTravailles,
                'conges_paye_non_travailles_count' => $congesPayeNonTravaillesCount,
                'joursCongesAvecPresence'          => $joursCongesAvecPresence,
                'congesPayeTravailles'             => $congesPayeTravailles,
            ];

            // Génération PDF
            $pdfRequest  = new Request([
                'id_salarie' => $idSalarie,
                'year'       => $year,
                'month'      => $month,
                'details'    => $details
            ]);
            $pdfResponse = $this->generatePaySlipPDF($pdfRequest);

            if ($pdfResponse->getStatusCode() === 200) {
                Log::info('PDF generated successfully', ['id_salarie' => $idSalarie, 'net_payer' => $netPayer]);
                $pdfData    = json_decode($pdfResponse->getContent(), true);
                $pdfPath    = $pdfData['path'];
                $payslips[] = [
                    'id_salarie' => $idSalarie,
                    'matricule'  => $matricule,
                    'path'       => $pdfPath,
                    'net_payer'  => $netPayer,
                    'details'    => $details
                ];
            } else {
                Log::warning('Failed to generate PDF, continuing', ['id_salarie' => $idSalarie]);
                $payslips[] = [
                    'id_salarie' => $idSalarie,
                    'matricule'  => $matricule,
                    'path'       => null,
                    'net_payer'  => $netPayer,
                    'details'    => $details
                ];
            }

            $totalSalary += $netPayer;

            DB::table($tableName)->where('id_salarie', $idSalarie)->update([
                $month       => $paymentId,
                'updated_at' => now()
            ]);

            Log::info('Salary table updated', [
                'table'      => $tableName,
                'id_salarie' => $idSalarie,
                'matricule'  => $matricule,
                'month'      => $month,
                'payment_id' => $paymentId
            ]);
        }

        if (empty($payslips)) {
            Log::warning('No payslips generated', ['year' => $year, 'month' => $month]);
        }

        Log::info('Payslips generation completed', ['count' => count($payslips), 'total_salary' => $totalSalary]);
        return response()->json(['total_salary' => $totalSalary, 'payslips' => $payslips], 200);

    } catch (\Exception $e) {
        Log::error('Error in calculateAndGeneratePaySlips', [
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
            'year'       => $year ?? 'N/A',
            'month'      => $month ?? 'N/A',
            'id_salarie' => $idSalarie ?? 'N/A',
            'matricule'  => $matricule ?? 'N/A',
        ]);
        return response()->json(['error' => "Erreur serveur : {$e->getMessage()}"], 500);
    }
}

// les calcule des variable qui sont en comenntaire car c'est un calcule journalier 



//fonction de calcule de salire 
   public function incrementSalary(Request $request)
    {

        // Calcul par salaire net → méthode séparée
        Log::info('incrementSalary calc_mode reçu', ['calc_mode' => $request->input('calc_mode')]);
        if ($request->input('calc_mode') === 'salaire_net') {
            return $this->incrementSalaryFromNet($request);
        }

        $idSalarie = $request->input('id_salarie');
        $year = $request->input('year');
        $month = $request->input('month');
        $primPanierInput = $request->input('prime_panier', 0);
        $indTransUrbainInput = $request->input('ind_trans_urbain', 0);
        $primeRepresentationInput = $request->input('prime_representation', 0);
        $primeDeplacementInput = $request->input('prime_deplacement', 0);
        $autresPrimesImposables = $request->input('autres_primes_imposables', 0);

        $primesDiversInput = $request->input('primes_divers', 0);
        $applyCimr = filter_var($request->input('apply_cimr', false), FILTER_VALIDATE_BOOLEAN);
        $doubleSalaryHolidays = filter_var($request->input('double_salary_holidays', false), FILTER_VALIDATE_BOOLEAN);
        $applyMutuelle = filter_var($request->input('apply_mutuelle', false), FILTER_VALIDATE_BOOLEAN);
        $calculateOvertime = filter_var($request->input('calculate_overtime', false), FILTER_VALIDATE_BOOLEAN);
        $applyFraisPro = filter_var($request->input('apply_frais_pro', true), FILTER_VALIDATE_BOOLEAN);
        $applyIpe = filter_var($request->input('apply_ipe', false), FILTER_VALIDATE_BOOLEAN);
        $applyPrimeRendement = filter_var($request->input('apply_prime_rendement', true), FILTER_VALIDATE_BOOLEAN);


        Log::info('incrementSalary called', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month,
            'prime_panier' => $primPanierInput,
            'ind_trans_urbain' => $indTransUrbainInput,
            'prime_representation' => $primeRepresentationInput,
            'PrimeDeplacement' => $primeDeplacementInput,
            'autres_primes_imposables' => $autresPrimesImposables,
            'primes_divers' => $primesDiversInput,
            'apply_cimr' => $applyCimr,
            'apply_mutuelle' => $applyMutuelle,
            'double_salary_holidays' => $doubleSalaryHolidays
        ]);

        if (!$idSalarie || !$year || !$month) {
            Log::error('Missing required parameters in incrementSalary');
            return response()->json(['error' => 'ID salarié, année ou mois manquant.'], 400);
        }

        if (!is_numeric($primPanierInput) || $primPanierInput < 0 || $primPanierInput > 800) {
            Log::error('Invalid prime_panier value', ['prime_panier' => $primPanierInput]);
            return response()->json(['error' => 'La prime de panier doit être un nombre entre 0 et 800 MAD.'], 400);
        }

        if (!is_numeric($indTransUrbainInput) || $indTransUrbainInput < 0 || $indTransUrbainInput > ($request->is_urban ? 700 : 500)) {
            Log::error('Invalid ind_trans_urbain value', ['ind_trans_urbain' => $indTransUrbainInput]);
            return response()->json(['error' => 'L\'indemnité de transport doit être un nombre entre 0 et ' . ($request->is_urban ? 700 : 500) . ' MAD.'], 400);
        }

        $monthMap = [
            'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
        ];
        if (!array_key_exists($month, $monthMap)) {
            Log::error('Invalid month', ['month' => $month]);
            return response()->json(['error' => "Mois invalide : {$month}."], 400);
        }
        $monthNumber = $monthMap[$month];

        $tableName = "salairs_{$year}";
        if (!Schema::hasTable($tableName)) {
            Log::error('Salary table does not exist', ['table' => $tableName]);
            return response()->json(['error' => "Table salairs_{$year} n'existe pas."], 404);
        }

        try {
            // Étape 1 : Récupérer les données du salarié
            $employeeData = DB::table('salaries')
                ->where('id', $idSalarie)
                ->select('anciennete', 'situation_familiale', 'nombre_enfant', 'type_contrat', 'statut', 'n_matricule_entreprise')
                ->first();

            if (!$employeeData) {
                Log::error('Employee not found in salaries table', ['id_salarie' => $idSalarie]);
                return response()->json(['error' => "Salarié ID {$idSalarie} non trouvé dans la table salaries."], 404);
            }

            $matricule = $employeeData->n_matricule_entreprise ?? 'N/A';
            $isAnapec = strtolower($employeeData->type_contrat) === 'anapec';
            Log::info('Contract type checked', [
                'id_salarie' => $idSalarie,
                'type_contrat' => $employeeData->type_contrat,
                'is_anapec' => $isAnapec,
                'matricule' => $matricule
            ]);

            $salaryData = DB::table($tableName)
                ->where('id_salarie', $idSalarie)
                ->first();

            if (!$salaryData) {
                Log::error('Employee not found', ['id_salarie' => $idSalarie, 'table' => $tableName]);
                return response()->json(['error' => "Salarié avec matricule {$matricule} non trouvé."], 404);
            }

            if (!isset($salaryData->salaire) || $salaryData->salaire === null) {
                Log::error('Base salary not defined', ['id_salarie' => $idSalarie, 'table' => $tableName]);
                return response()->json(['error' => "Aucun salaire de base défini pour le salarié avec matricule {$matricule}."], 400);
            }

            $existingPayment = DB::table('paiement_salaires')
                ->where('id_salarie', $idSalarie)
                ->where('annee', $year)
                ->where('mois', $monthNumber)
                ->first();

            if ($existingPayment) {
                Log::warning('Payment already exists', [
                    'id_salarie' => $idSalarie,
                    'year' => $year,
                    'month' => $month,
                    'payment_id' => $existingPayment->id
                ]);
                return response()->json(['error' => "Paiement déjà enregistré pour {$month} {$year}."], 400);
            }

            // Définir les dates du mois
            $startOfMonth = sprintf('%d-%02d-01', $year, $monthNumber);
            $endOfMonth = date('Y-m-t', strtotime($startOfMonth));


          // Calcul de $heurSuppPresence
                $heurSuppPresence = 0;
                if ($calculateOvertime) {
                    $heurSuppPresence = DB::table('presence')
                        ->where('salarie_id', $idSalarie)
                        ->where('statuts', 1)
                        ->whereBetween('date', [$startOfMonth, $endOfMonth])
                        ->sum('heures');
                }
                Log::info('Overtime hours calculated', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'year' => $year,
                    'month' => $month,
                    'heur_supp_presence' => $heurSuppPresence,
                    'calculate_overtime' => $calculateOvertime,
                    'start_of_month' => $startOfMonth,
                    'end_of_month' => $endOfMonth
                ]);


            // Calcul de $heurSuppPresencematin
            $heurSuppPresencematin = 0;
            if ($calculateOvertime) {
                $heurSuppPresencematin = DB::table('presence')
                    ->where('salarie_id', $idSalarie)
                    ->where('statuts', 1)
                    ->where('type_heure_supp', 'matin')
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('jour_feries')
                            ->whereRaw('presence.date >= jour_feries.date_debut')
                            ->whereRaw('presence.date <= jour_feries.date_fin');
                    })
                    ->sum('heures');
            }
            Log::info('Morning overtime hours calculated', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'year' => $year,
                'month' => $month,
                'heurSuppPresencematin' => $heurSuppPresencematin,
                'calculate_overtime' => $calculateOvertime,
                'start_of_month' => $startOfMonth,
                'end_of_month' => $endOfMonth
            ]);

                    // Calcul de $heurSuppPresenceNuit
            $heurSuppPresenceNuit = 0;
            if ($calculateOvertime) {
                $heurSuppPresenceNuit = DB::table('presence')
                    ->where('salarie_id', $idSalarie)
                    ->where('statuts', 1)
                    ->where('type_heure_supp', 'nuit')
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('jour_feries')
                            ->whereRaw('presence.date >= jour_feries.date_debut')
                            ->whereRaw('presence.date <= jour_feries.date_fin');
                    })
                    ->sum('heures');
            }
            Log::info('Night overtime hours calculated', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'year' => $year,
                'month' => $month,
                'heurSuppPresenceNuit' => $heurSuppPresenceNuit,
                'calculate_overtime' => $calculateOvertime,
                'start_of_month' => $startOfMonth,
                'end_of_month' => $endOfMonth
            ]);


            // Calcul de $heurSuppPresenceNuitF
            $heurSuppPresenceNuitF = 0;
            if ($calculateOvertime) {
                $heurSuppPresenceNuitF = DB::table('presence')
                    ->where('salarie_id', $idSalarie)
                    ->where('statuts', 1)
                    ->where('type_heure_supp', 'nuit')
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('jour_feries')
                            ->whereRaw('presence.date >= jour_feries.date_debut')
                            ->whereRaw('presence.date <= jour_feries.date_fin');
                    })
                    ->sum('heures');
            }
            Log::info('Night overtime hours on public holidays calculated', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'year' => $year,
                'month' => $month,
                'heurSuppPresenceNuitF' => $heurSuppPresenceNuitF,
                'calculate_overtime' => $calculateOvertime,
                'start_of_month' => $startOfMonth,
                'end_of_month' => $endOfMonth
            ]);


            // Calcul de $heurSuppPresencematinF
            $heurSuppPresencematinF = 0;
            if ($calculateOvertime) {
                $heurSuppPresencematinF = DB::table('presence')
                    ->where('salarie_id', $idSalarie)
                    ->where('statuts', 1)
                    ->where('type_heure_supp', 'matin')
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('jour_feries')
                            ->whereRaw('presence.date >= jour_feries.date_debut')
                            ->whereRaw('presence.date <= jour_feries.date_fin');
                    })
                    ->sum('heures');
            }
            Log::info('Morning overtime hours on public holidays calculated', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'year' => $year,
                'month' => $month,
                'heurSuppPresencematinF' => $heurSuppPresencematinF,
                'calculate_overtime' => $calculateOvertime,
                'start_of_month' => $startOfMonth,
                'end_of_month' => $endOfMonth
            ]);


                  
                        // Vérification des congés approuvés pour le mois
                        $leaveData = DB::table('conger')
                            ->where('salarie_id', $idSalarie)
                            ->where('approbation', 2)
                            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                                $query->whereBetween('date_debut', [$startOfMonth, $endOfMonth])
                                    ->orWhereBetween('date_fin', [$startOfMonth, $endOfMonth])
                                    ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
                                        $query->where('date_debut', '<=', $startOfMonth)
                                            ->where('date_fin', '>=', $endOfMonth);
                                    });
                            })
                            ->first();

            // Initialize variables to avoid undefined variable errors
            $primPanier = 0;
            $indTransUrbain = 0;
            $primeRepresentation = 0;
            $primeDeplacement = 0;
            $autresPrimesImposablesCalculated = 0;
            $primesDivers = 0;
            $joursFeriesTravailles = 0;
            $jourFerier = 0;

            

           

        // Vérification des jours fériés dans le mois
                $joursFeries = DB::table('jour_feries')
                        ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                            $query->whereBetween('date_debut', [$startOfMonth, $endOfMonth])
                                ->orWhereBetween('date_fin', [$startOfMonth, $endOfMonth])
                                ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
                                    $query->where('date_debut', '<=', $startOfMonth)
                                            ->where('date_fin', '>=', $endOfMonth);
                                });
                        })
                        ->get();  // On récupère les enregistrements au lieu de sommer directement

        $joursFeriesCount = 0;

        foreach ($joursFeries as $ferie) {
            $dateDebut = new \DateTime($ferie->date_debut);
            $dateFin   = new \DateTime($ferie->date_fin ?? $ferie->date_debut); // si pas de date_fin, on prend date_debut

            // On boucle sur chaque jour de la période fériée
            while ($dateDebut <= $dateFin) {
                // Vérifier si la date est dans le mois concerné
                if ($dateDebut->format('Y-m') === substr($startOfMonth, 0, 7)) {
                    // Exclure les dimanches (format 'w' : 0 = dimanche)
                    if ($dateDebut->format('w') != 0) {
                        $joursFeriesCount += 1;
                    }
                }
                $dateDebut->modify('+1 day');
            }
        }

        $joursFeriesCount = (int) $joursFeriesCount;

            // Vérification des jours fériés travaillés
            $joursFeriesTravaillesCount = DB::table('jour_feries')
                ->join('presence', function ($join) use ($idSalarie, $startOfMonth, $endOfMonth) {
                    $join->on(DB::raw('presence.date'), '>=', DB::raw('jour_feries.date_debut'))
                        ->on(DB::raw('presence.date'), '<=', DB::raw('jour_feries.date_fin'))
                        ->where('presence.salarie_id', $idSalarie)
                        ->where('presence.statuts', 1)
                        ->whereBetween('presence.date', [$startOfMonth, $endOfMonth]);
                })
                ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('jour_feries.date_debut', [$startOfMonth, $endOfMonth])
                        ->orWhereBetween('jour_feries.date_fin', [$startOfMonth, $endOfMonth])
                        ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
                            $query->where('date_debut', '<=', $startOfMonth)
                                ->where('date_fin', '>=', $endOfMonth);
                        });
                })
                ->sum('jour_feries.nbr_jours');

            $joursFeriesTravaillesCount = (int) min($joursFeriesTravaillesCount, $joursFeriesCount);

            // Redéfinir $joursFeriesCount pour représenter uniquement les jours fériés non travaillés
            $joursFeriesCount = max(0, $joursFeriesCount - $joursFeriesTravaillesCount);

            Log::info('Public holidays calculated', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'year' => $year,
                'month' => $month,
                'joursFeriesCount' => $joursFeriesCount,
                'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount,
                'start_of_month' => $startOfMonth,
                'end_of_month' => $endOfMonth
            ]);

                
                                


            // Vérification si le salarié a démissionné
            $resignationData = DB::table('demissions')
                ->where('salarie_id', $idSalarie)
                ->where('date_demission', '>=', $startOfMonth)
                ->where('date_demission', '<=', $endOfMonth)
                ->select('date_demission')
                ->first();

            $isResigned = strtolower($employeeData->statut) === 'inactif' && $resignationData;

            Log::info('Resignation status checked', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'statut' => $employeeData->statut,
                'has_resignation' => !empty($resignationData),
                'resignation_date' => $resignationData ? $resignationData->date_demission : null
            ]);

            if ($isResigned) {
                $resignationDate = new \DateTime($resignationData->date_demission);
                $startDate = new \DateTime($startOfMonth);
                $interval = $startDate->diff($resignationDate);
                $workingDays = min($interval->days + 1, 26);
                $endOfPeriod = $resignationData->date_demission;

                // Ajuster les jours fériés pour les salariés ayant démissionné
                $joursFeriesCountResigned = DB::table('jour_feries')
                    ->where(function ($query) use ($startOfMonth, $resignationData) {
                        $query->whereBetween('date_debut', [$startOfMonth, $resignationData->date_demission])
                            ->orWhereBetween('date_fin', [$startOfMonth, $resignationData->date_demission])
                            ->orWhere(function ($query) use ($startOfMonth, $resignationData) {
                                $query->where('date_debut', '<=', $startOfMonth)
                                    ->where('date_fin', '>=', $resignationData->date_demission);
                            });
                    })
                    ->sum('nbr_jours');

                // Ajuster les jours fériés travaillés pour les salariés ayant démissionné
                $joursFeriesTravaillesCountResigned = DB::table('jour_feries')
                    ->join('presence', function ($join) use ($idSalarie, $startOfMonth, $resignationData) {
                        $join->on(DB::raw('presence.date'), '>=', DB::raw('jour_feries.date_debut'))
                            ->on(DB::raw('presence.date'), '<=', DB::raw('jour_feries.date_fin'))
                            ->where('presence.salarie_id', $idSalarie)
                            ->where('presence.statuts', 1)
                            ->whereBetween('presence.date', [$startOfMonth, $resignationData->date_demission]);
                    })
                    ->where(function ($query) use ($startOfMonth, $resignationData) {
                        $query->whereBetween('jour_feries.date_debut', [$startOfMonth, $resignationData->date_demission])
                            ->orWhereBetween('jour_feries.date_fin', [$startOfMonth, $resignationData->date_demission])
                            ->orWhere(function ($query) use ($startOfMonth, $resignationData) {
                                $query->where('date_debut', '<=', $startOfMonth)
                                    ->where('date_fin', '>=', $resignationData->date_demission);
                            });
                    })
                    ->sum('jour_feries.nbr_jours');

                // Redéfinir $joursFeriesCount pour les salariés ayant démissionné
                $joursFeriesCount = (int) max(0, $joursFeriesCountResigned - $joursFeriesTravaillesCountResigned);
                $joursFeriesTravaillesCount = (int) min($joursFeriesTravaillesCountResigned, $joursFeriesCountResigned);

                // Ajuster les jours de congé payés pour les salariés ayant démissionné
               $congesPayeCount = 0;

                    $congesPayeCount = 0;

                    $conges = DB::table('conger')
                        ->where('salarie_id', $idSalarie)
                        ->where('approbation', 2)
                        ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                            $query->whereBetween('date_debut', [$startOfMonth, $endOfMonth])
                                ->orWhereBetween('date_fin', [$startOfMonth, $endOfMonth])
                                ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
                                    $query->where('date_debut', '<=', $startOfMonth)
                                        ->where('date_fin', '>=', $endOfMonth);
                                });
                        })
                        ->get(['date_debut', 'date_fin', 'num_j']);

                    foreach ($conges as $conge) {
                        $debutConge = new \DateTime($conge->date_debut);
                        $finConge   = new \DateTime($conge->date_fin);
                        $debutMois  = new \DateTime($startOfMonth);
                        $finMois    = new \DateTime($endOfMonth);

                        // Borner au mois sélectionné
                        $debut = $debutConge > $debutMois ? $debutConge : $debutMois;
                        $fin   = $finConge   < $finMois   ? $finConge   : $finMois;

                        if ($debut <= $fin) {
                            $joursIntersection = (int) $debut->diff($fin)->days ;
                            // Ne pas dépasser num_j
                            $congesPayeCount += min($joursIntersection, (int) $conge->num_j);
                        }
                    }

                    $congesPayeCount = (int) $congesPayeCount;

                    Log::info('Paid leave days calculated (borné au mois)', [
                        'id_salarie'       => $idSalarie,
                        'conges_paye_count'=> $congesPayeCount, // doit afficher 8 → 7 après correction
                        'start_of_month'   => $startOfMonth,
                        'end_of_month'     => $endOfMonth
                    ]);



                Log::info('Resigned employee: adjusted public holidays, worked public holidays, and paid leave', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'resignation_date' => $resignationData->date_demission,
                    'joursFeriesCount' => $joursFeriesCount,
                    'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount,
                    'conges_paye_count' => $congesPayeCount,
                    'start_of_month' => $startOfMonth,
                    'end_of_period' => $endOfPeriod
                ]);



            } else {
                $workingDays = 26;
                $endOfPeriod = $endOfMonth;

                Log::info('Non-resigned employee: using full month', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'working_days' => $workingDays,
                    'start_of_month' => $startOfMonth,
                    'end_of_period' => $endOfPeriod
                ]);
            }

            $joursTravails=DB::table('presence')
                                    ->where('salarie_id', $idSalarie)
                                    ->where('statuts', 1)
                                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                                    ->whereNotExists(function ($query) {
                                        $query->select(DB::raw(1))
                                            ->from('jour_feries')
                                            ->whereRaw('presence.date >= jour_feries.date_debut')
                                            ->whereRaw('presence.date <= jour_feries.date_fin');
                                    })
                                    ->count();

                                Log::info('Jours de présence hors fériés calculés', [
                                    'id_salarie' => $idSalarie,
                                    'joursPresence_normaux' => $joursTravails,
                                    'start_of_month' => $startOfMonth,
                                    'end_of_month' => $endOfMonth
                                ]);





                                            // Calculer les jours de congé payés approuvés dans le mois
                            $congesPayeCount = 0;
            $congesPayeCount = 0;

            $conges = DB::table('conger')
                ->where('salarie_id', $idSalarie)
                ->where('approbation', 2)
                ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('date_debut', [$startOfMonth, $endOfMonth])
                        ->orWhereBetween('date_fin', [$startOfMonth, $endOfMonth])
                        ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
                            $query->where('date_debut', '<=', $startOfMonth)
                                ->where('date_fin', '>=', $endOfMonth);
                        });
                })
                ->get(['date_debut', 'date_fin', 'num_j']);

            foreach ($conges as $conge) {
                $debutConge = new \DateTime($conge->date_debut);
                $finConge   = new \DateTime($conge->date_fin);
                $debutMois  = new \DateTime($startOfMonth);
                $finMois    = new \DateTime($endOfMonth);

                // Borner au mois sélectionné
                $debut = $debutConge > $debutMois ? $debutConge : $debutMois;
                $fin   = $finConge   < $finMois   ? $finConge   : $finMois;

                if ($debut <= $fin) {
            $joursIntersection = (int) $debut->diff($fin)->days + 1; // +1 pour inclure le jour de fin
            $congesPayeCount += min($joursIntersection, (int) $conge->num_j);
        }
                    }

                    $congesPayeCount = (int) $congesPayeCount;

                    Log::info('Paid leave days calculated (borné au mois)', [
                        'id_salarie'       => $idSalarie,
                        'conges_paye_count'=> $congesPayeCount, // doit afficher 8 → 7 après correction
                        'start_of_month'   => $startOfMonth,
                        'end_of_month'     => $endOfMonth
                    ]);
                            

                    
                                        // Calcul des jours de présence NORMALE (hors jours fériés)
                                    $joursPresenceReel = $joursTravails - $joursFeriesTravaillesCount - $joursFeriesCount;

                // S'assurer que la somme totale ne dépasse pas 26
                $autresJours = $joursFeriesCount + $joursFeriesTravaillesCount + $congesPayeCount;
                $joursPresence = max(0, 26 - $autresJours);


                    if ($joursPresence == 0 && $congesPayeCount == 0) {
                                        Log::error('No presence or paid leave recorded for employee', [
                                            'id_salarie' => $idSalarie,
                                            'matricule' => $matricule,
                                            'year' => $year,
                                            'month' => $month
                                        ]);
                                        return response()->json(['error' => "Aucune présence ni congé payé enregistré pour le salarié avec matricule {$matricule} pour {$month} {$year}."], 400);
                                    }

                            $joursCalcules = $joursPresence + $joursFeriesCount + $joursFeriesTravaillesCount;
                            
                        /* if ($joursCalcules > 26) {
                            $joursCalcules = 26;
                        } */

                                            // === Séparation des congés travaillés et non travaillés ===

                            $joursCalcules = 26;
                        Log::info('Jours calculés', [
                            'id_salarie' => $idSalarie,
                            'matricule' => $matricule,
                            'joursCalcules' => $joursCalcules,
                            'joursPresence' => $joursPresence,
                            'joursFeriesCount' => $joursFeriesCount,
                            'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount
                        ]);
                        


                
                                // Calculer prime_panier
                                $primPanier = round(($primPanierInput / 26) *  $joursCalcules, 2);
                                Log::info('Prime panier calculated', [
                                    'id_salarie' => $idSalarie,
                                    'matricule' => $matricule,
                                    'prime_panier' => $primPanier,
                                    'jours_presence' => $joursPresence
                                ]);

                                // Calculer ind_trans_urbain
                                $indTransUrbain = round(($indTransUrbainInput / 26) *  $joursCalcules, 2);
                                Log::info('Indemnité transport urbain calculated', [
                                    'id_salarie' => $idSalarie,
                                    'matricule' => $matricule,
                                    'ind_trans_urbain' => $indTransUrbain,
                                    'jours_presence' => $joursPresence
                                ]);

                                // Calculer prime_representation
                                $primeRepresentation = round(($primeRepresentationInput / 26) *  $joursCalcules, 2);
                                Log::info('Prime representation calculated', [
                                    'id_salarie' => $idSalarie,
                                    'matricule' => $matricule,
                                    'prime_representation' => $primeRepresentation,
                                    'jours_presence' => $joursPresence
                                ]);

                                // Calculer PrimeDeplacement
                                $primeDeplacement = round(($primeDeplacementInput / 26) *  $joursCalcules, 2);
                                Log::info('PrimeDeplacement calculated', [
                                    'id_salarie' => $idSalarie,
                                    'matricule' => $matricule,
                                    'PrimeDeplacement' => $primeDeplacement,
                                    'jours_presence' => $joursPresence
                                ]);

                                // Calculer autresPrimesImposables
                                $autresPrimesImposablesCalculated = round(($autresPrimesImposables / 26) *  $joursCalcules, 2);
                                Log::info('Autres primes imposables calculated', [
                                    'id_salarie' => $idSalarie,
                                    'matricule' => $matricule,
                                    'autres_primes_imposables_input' => $autresPrimesImposables,
                                    'jours_presence' => $joursPresence,
                                    'autres_primes_imposables_calculated' => $autresPrimesImposablesCalculated
                                ]);

                                // Calculer primes_divers
                                $primesDivers = round(($primesDiversInput / 26) *  $joursCalcules, 2);
                                Log::info('Primes divers calculated', [
                                    'id_salarie' => $idSalarie,
                                    'matricule' => $matricule,
                                    'primes_divers' => $primesDivers,
                                    'jours_presence' => $joursPresence
                                ]);
                            

                            // Calculer primNonimposables
                            $primNonimposables = round($primPanier + $indTransUrbain + $primeRepresentation + $primeDeplacement + $primesDivers, 2);
                            Log::info('Prime non-imposable calculated', [
                                'id_salarie' => $idSalarie,
                                'matricule' => $matricule,
                                'primNonimposables' => $primNonimposables,
                                'prime_panier' => $primPanier,
                                'ind_trans_urbain' => $indTransUrbain,
                                'prime_representation' => $primeRepresentation,
                                'PrimeDeplacement' => $primeDeplacement,
                                'primes_divers' => $primesDivers
                            ]);

                                        
                    // Insertion dans configurationbp 
                    // ────────────────────────────────────────────────

                                DB::table('configurationbp')
                ->updateOrInsert(
                    // Condition : sur quelle(s) clé(s) on vérifie l'existence
                    ['id_salarier' => $idSalarie],
                    
                    // Valeurs à insérer ou à mettre à jour
                    [
                        'pripanier'             => $primPanierInput,
                        'indtransport'          => $indTransUrbainInput,
                        'prirepresentation'     => $primeRepresentationInput,
                        'prideplacement'        => $primeDeplacementInput,
                        'pridivers'             => $primesDiversInput,
                        'autrespriimposables'   => $autresPrimesImposables,

                        'cotisation_cimr'       => $applyCimr ? 1 : 0,
                        'indemnite_pe'          => $applyIpe ? 1 : 0,
                        'calculate_overtime'    => $calculateOvertime ? 1 : 0,
                        'cotisation_mutuelle'   => $applyMutuelle ? 1 : 0,
                        'apply_frais_pro'       => $applyFraisPro ? 1 : 0,
                        'apply_prime_rendement' => $applyPrimeRendement ? 1 : 0,
                        'double_salary_holidays'=> $doubleSalaryHolidays ? 1 : 0,

                        'date_creation'         => now(),               // sera utilisé seulement à l'insert
                        'date_modification'     => now(),               // sera mis à jour à chaque fois
                    ]
                );

                        Log::info('Configurationbp mise à jour ou créée pour le salarié', [
                            'id_salarier'           => $idSalarie,
                            'action'                => DB::table('configurationbp')->where('id_salarier', $idSalarie)->exists() ? 'update' : 'insert',
                            'pripanier'             => $primPanierInput,
                            'indtransport'          => $indTransUrbainInput,
                            'cotisation_cimr'       => $applyCimr,
                            'indemnite_pe'          => $applyIpe,
                            'calculate_overtime'    => $calculateOvertime,
                            'cotisation_mutuelle'   => $applyMutuelle,
                            'apply_frais_pro'       => $applyFraisPro,
                            'apply_prime_rendement' => $applyPrimeRendement,
                            'double_salary_holidays'=> $doubleSalaryHolidays,
                        ]);
                   

                    $baseSalary = (float) $salaryData->salaire;
                     Log::info('dailySalary', [
                    'jjjjjjjjjjjjjj'  =>$baseSalary,
                ]);
                    $dailySalary = $baseSalary / 26;
                     Log::info('dailySalary', [
                    'dailySalaryyyyyyuy'  =>$dailySalary,
                ]);


    
                      $congesPaye = round($congesPayeCount * $dailySalary, 2);


                                
                  $jourscals=$joursPresence + $joursFeriesCount + $joursFeriesTravaillesCount;
                    $jourFerier = round($joursFeriesCount * $dailySalary, 2);
                            if($doubleSalaryHolidays){
                                $joursFeriesTravailles = round($joursFeriesTravaillesCount * $dailySalary * 2, 2);
                                
                                }
                                
                             
                                $salaired = $joursPresence > 26 ? round($dailySalary * 26) : round($dailySalary * $joursPresence);
                                 // $salaired = $jourscals > 26 ? round($dailySalary * 26) : round($dailySalary * $jourscals, 2);


                    

                    Log::info('Salary components calculated', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'joursFeriesCount' => $joursFeriesCount,
                        'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount,
                        'conges_paye_count' => $congesPayeCount,
                        'daily_salary' => $dailySalary,
                        'jour_ferie' => $jourFerier,
                        'jours_feries_travailles' => $joursFeriesTravailles,
                        'conges_paye' => $congesPaye
                    ]);

                        
                if ($calculateOvertime && $heurSuppPresence != 0) {
                    $tauxHeureSupp = round($baseSalary / 191, 2);
                } else {
                    $tauxHeureSupp = 0;
                }
                Log::info('Taux heure supp calculated', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'calculate_overtime' => $calculateOvertime,
                    'heurSuppPresence' => $heurSuppPresence,
                    'tauxHeureSupp' => $tauxHeureSupp,
                    'baseSalary' => $baseSalary
                ]);


                            // Calcul de $heurSupp25
                    $heurSupp25 = 0;
                    if ($calculateOvertime && $heurSuppPresencematin != 0) {
                        $heurSupp25 = round($heurSuppPresencematin * $tauxHeureSupp * 1.25, 2);
                    }
                    Log::info('heurSupp25 calculated', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'year' => $year,
                        'month' => $month,
                        'heurSuppPresencematin' => $heurSuppPresencematin,
                        'tauxHeureSupp' => $tauxHeureSupp,
                        'heurSupp25' => $heurSupp25,
                        'calculate_overtime' => $calculateOvertime
                    ]);

                            // Calcul de $heurSupp50
                $heurSupp50 = 0;
                if ($calculateOvertime && ($heurSuppPresencematinF + $heurSuppPresenceNuit) != 0) {
                    $heurSupp50 = round(($heurSuppPresencematinF + $heurSuppPresenceNuit) * ($tauxHeureSupp * 1.5), 2);
                }
                Log::info('heurSupp50 calculated', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'year' => $year,
                    'month' => $month,
                    'heurSuppPresencematinF' => $heurSuppPresencematinF,
                    'heurSuppPresenceNuit' => $heurSuppPresenceNuit,
                    'tauxHeureSupp' => $tauxHeureSupp,
                    'heurSupp50' => $heurSupp50,
                    'calculate_overtime' => $calculateOvertime
                ]);


            // Calcul de $heurSupp100
            $heurSupp100 = 0;
            if ($calculateOvertime && $heurSuppPresenceNuitF != 0) {
                $heurSupp100 = round($heurSuppPresenceNuitF * $tauxHeureSupp * 2, 2);
            }
            Log::info('heurSupp100 calculated', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'year' => $year,
                'month' => $month,
                'heurSuppPresenceNuitF' => $heurSuppPresenceNuitF,
                'tauxHeureSupp' => $tauxHeureSupp,
                'heurSupp100' => $heurSupp100,
                'calculate_overtime' => $calculateOvertime  
            ]);

              $salaire_base_imposable = round( round($dailySalary * $joursCalcules, 2) + $heurSupp25 + $heurSupp50 + $heurSupp100 , 2);
                  Log::info('dailySalary', [
                    'bbbbbbbbbbbbbbbbbbbb'  => $salaire_base_imposable,
                ]);
          //  $salaire_base_imposable = round($salaired + $heurSupp25 + $heurSupp50 + $heurSupp100 + $jourFerier + $joursFeriesTravailles, 2);
            Log::info('Salaire base imposable calculated', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'salaired' => $salaired,
                'heurSupp25' => $heurSupp25,
                'heurSupp50' => $heurSupp50,
                'heurSupp100' => $heurSupp100,
                'jour_ferie' => $jourFerier,
                'jours_feries_travailles' => $joursFeriesTravailles,
                'salaire_base_imposable' => $salaire_base_imposable
            ]);
             // Calcul de la prime de rendement journalière
            $prime_journaliere = 0;
                    
            $base_prime_journaliere = $joursPresence + $joursFeriesCount + $congesPayeCount + $joursFeriesTravaillesCount;
            $taux_prime_journaliere = $base_prime_journaliere > 26 ? $base_prime_journaliere - 26 : 0;

            if ($applyPrimeRendement)  {
                
                    $prime_journaliere = round($taux_prime_journaliere * 1.25 * $base_prime_journaliere, 2);

                    Log::info('Prime de rendement journalière calculated', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'base_prime_journaliere' => $base_prime_journaliere,
                        'taux_prime_journaliere' => $taux_prime_journaliere,
                        'prime_journaliere' => $prime_journaliere
                    ]);
                    }

          

                        // Calculer les primes d'ancienneté
                        $anciennete = $employeeData->anciennete;
                        $primeAnciennete = 0;
                        $tauxAnciennete = 0;

                    if (!is_null($anciennete)) {
                $tauxData = AncienneteTaux::where('an_min', '<=', $anciennete)
                    ->where(function ($query) use ($anciennete) {
                        $query->where('an_max', '>=', $anciennete)
                            ->orWhereNull('an_max');
                    })
                    ->select('taux')
                    ->first();

                if ($tauxData) {
                    $tauxAnciennete = (float) $tauxData->taux;
                    $primeAnciennete = round((float) $salaire_base_imposable * ($tauxData->taux / 100), 2);
                    Log::info('Prime anciennete calculated', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'anciennete' => $anciennete,
                        'taux' => $tauxData->taux,
                        'salaire_base_imposable' => $salaire_base_imposable,
                        'prime_anciennete' => $primeAnciennete
                    ]);
                } else {
                    Log::warning('No taux found for anciennete', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'anciennete' => $anciennete
                    ]);
                }
            } else {
                Log::warning('Anciennete is NULL, skipping prime calculation', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule
                ]);
            }

            

            // Inclure autresPrimesImposables dans le salaire imposable
          
        $salaireBI = round($salaire_base_imposable + $primeAnciennete + $autresPrimesImposablesCalculated, 2);

       $joursCongesAvecPresence = 0;
        $congesPayeNonTravaillesCount = 0;
        $congesPayeTravailles = 0;
        $congesPayeNonTravailles = 0;

       if ($congesPayeCount > 0) {

            $joursCongesAvecPresence = DB::table('presence')
                ->where('salarie_id', $idSalarie)
                ->where('statuts', 1)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->whereExists(function ($query) use ($idSalarie, $endOfMonth) {
                    $query->select(DB::raw(1))
                        ->from('conger')
                        ->where('conger.salarie_id', $idSalarie)
                        ->where('conger.approbation', 2)
                        ->whereRaw('presence.date >= conger.date_debut')
                        ->whereRaw("presence.date <= LEAST(conger.date_fin, ?)", [$endOfMonth]);
                })
                ->count();

            $congesPayeNonTravaillesCount = max(0, $congesPayeCount - $joursCongesAvecPresence);

            $congesPayeTravailles    = round($joursCongesAvecPresence * $dailySalary, 2);
            $congesPayeNonTravailles = round($congesPayeNonTravaillesCount * $dailySalary, 2);

            Log::info('Séparation congés travaillés / non travaillés', [
                'congesPayeCount'              => $congesPayeCount,         // total congés dans le mois
                'joursCongesAvecPresence'      => $joursCongesAvecPresence, // jours où il était présent ET en congé
                'congesPayeNonTravaillesCount' => $congesPayeNonTravaillesCount,
                'congesPayeTravailles'         => $congesPayeTravailles,
                'congesPayeNonTravailles'      => $congesPayeNonTravailles,
            ]);

                    // Ajouter au salaireBI UNIQUEMENT les jours travaillés pendant le congé
                    if ($joursCongesAvecPresence > 0) {
                        $salaireBI = round($salaireBI + $congesPayeTravailles, 2);
                        Log::info('congés travaillés ajoutés au salaireBI', [
                            'congesPayeTravailles' => $congesPayeTravailles,
                            'salaireBI_final'      => $salaireBI
                        ]);
                    }
                }
            // Initialiser et calculer salaireBG
            $salaireBG = round($salaire_base_imposable + $primeAnciennete + $autresPrimesImposablesCalculated + $primNonimposables, 2);

            Log::info('SalaireBG calculated', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'salaireBI' => $salaireBI,
                'primNonimposables' => $primNonimposables,
                'salaireBG' => $salaireBG
            ]);

            // Calculer les cotisations
            $cotisationCNSS = 0;
            $cotisationAMO = 0;
            $cotisationCIMR = 0;
            $tauxCIMR = 0;
            $indeminitePE = 0; 
            $fraisProfessionnels = 0;
            $tauxFrais = 0;
            $cnss_ps = 0;
            $amo_ps = 0;
            $ipe_ps = 0;
            $irDataTaux = 0;
            $cotisationMutuelle = 0;
            $tauxMutuelle = 0;
            $chargeFamiliale = 0;

           if (!$isAnapec) {
                $cotisations = Cotisations::select('cnss_ps', 'amo_ps', 'ipe_ps', 'plafond_ipe', 'charge_de_famille', 'plafond_cnss', 'taux_cimr', 'taux_mutuelle')->first();
                if (!$cotisations) {
                    Log::error('No cotisations found', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
                    return response()->json(['error' => 'Aucune donnée de cotisation trouvée.'], 400);
                }

                if (!is_null($cotisations->cnss_ps)) {
                    $cnss_ps = (float) $cotisations->cnss_ps;
                    $cotisationCNSS = ($salaireBI > ($cotisations->plafond_cnss ?? 0))
                        ? round(($cotisations->plafond_cnss ?? 0) * ($cotisations->cnss_ps / 100), 2)
                        : round($salaireBI * ($cotisations->cnss_ps / 100), 2);
                    Log::info('Cotisation CNSS calculated', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'salaireBI' => $salaireBI,
                        'cnss_taux' => $cotisations->cnss_ps,
                        'plafond_cnss' => $cotisations->plafond_cnss ?? 'N/A',
                        'cotisation_cnss' => $cotisationCNSS
                    ]);
                } else {
                    Log::warning('No CNSS taux found or cnss_ps is NULL', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
                }

                if (!is_null($cotisations->amo_ps)) {
                    $amo_ps = (float) $cotisations->amo_ps;
                    $cotisationAMO = round($salaireBI * ($cotisations->amo_ps / 100), 2);
                    Log::info('Cotisation AMO calculated', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'salaireBI' => $salaireBI,
                        'amo_taux' => $cotisations->amo_ps,
                        'cotisation_amo' => $cotisationAMO
                    ]);
                } else {
                    Log::warning('No AMO taux found or amo_ps is NULL', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
                }

                // CIMR
                if ($applyCimr && !is_null($cotisations->taux_cimr)) {
                    $tauxCIMR = (float) $cotisations->taux_cimr;
                    $cotisationCIMR = round($salaireBI * ($cotisations->taux_cimr / 100), 2);
                    Log::info('Cotisation CIMR calculated', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'salaireBI' => $salaireBI,
                        'taux_cimr' => $cotisations->taux_cimr,
                        'cotisation_cimr' => $cotisationCIMR,
                        'apply_cimr' => $applyCimr
                    ]);
                } else {
                    $tauxCIMR = 0;
                    $cotisationCIMR = 0;
                    Log::info('CIMR not applied', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'apply_cimr' => $applyCimr,
                        'taux_cimr' => $cotisations->taux_cimr ?? 'N/A'
                    ]);
                }

                // Mutuelle
                if ($applyMutuelle && !is_null($cotisations->taux_mutuelle)) {
                    $tauxMutuelle = (float) $cotisations->taux_mutuelle;
                    $cotisationMutuelle = round($salaireBI * ($cotisations->taux_mutuelle / 100), 2);
                    Log::info('Cotisation Mutuelle calculated', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'salaireBI' => $salaireBI,
                        'taux_mutuelle' => $cotisations->taux_mutuelle,
                        'cotisation_mutuelle' => $cotisationMutuelle,
                        'apply_mutuelle' => $applyMutuelle
                    ]);
                } else {
                    $tauxMutuelle = 0;
                    $cotisationMutuelle = 0;
                    Log::info('Mutuelle not applied', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'apply_mutuelle' => $applyMutuelle,
                        'taux_mutuelle' => $cotisations->taux_mutuelle ?? 'N/A'
                    ]);
                }
            if($applyIpe){
                 if (!is_null($cotisations->ipe_ps) && !is_null($cotisations->plafond_ipe)) {
                    $ipe_ps = (float) $cotisations->ipe_ps;
                    $baseCalcul = min($salaireBI, $cotisations->plafond_ipe);
                    $indeminitePE = round($baseCalcul * ($cotisations->ipe_ps / 100), 2);
                    Log::info('Indemnité IPE calculated', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'salaireBI' => $salaireBI,
                        'base_calcul' => $baseCalcul,
                        'ipe_taux' => $cotisations->ipe_ps,
                        'plafond_ipe' => $cotisations->plafond_ipe,
                        'indeminite_pe' => $indeminitePE
                    ]);
                } else {
                    Log::warning('No IPE taux or plafond found, or ipe_ps/plafond_ipe is NULL', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
                } 
                }
            if($applyFraisPro){
                $fraisProData = DB::table('frais_professionnels')
                    ->where('sbi_min', '<=', $salaireBI)
                    ->where(function ($query) use ($salaireBI) {
                        $query->where('sbi_max', '>=', $salaireBI)
                            ->orWhereNull('sbi_max');
                    })
                    ->select('taux', 'plafond', 'sbi_min', 'sbi_max')
                    ->first();

                if ($fraisProData) {
                    $tauxFrais = (float) $fraisProData->taux;
                    $plafondFrais = (float) $fraisProData->plafond;
                    $fraisProfessionnels = round(min($salaireBI * ($tauxFrais / 100), $plafondFrais), 2);
                    Log::info('Frais professionnels calculated', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'salaireBI' => $salaireBI,
                        'sbi_min' => $fraisProData->sbi_min,
                        'sbi_max' => $fraisProData->sbi_max ?? 'infinity',
                        'taux_frais' => $tauxFrais,
                        'plafond_frais' => $plafondFrais,
                        'frais_professionnels' => $fraisProfessionnels
                    ]);
                } else {
                    Log::warning('No frais professionnels data found for salaireBI', [
                        'id_salarie' => $idSalarie,
                        'matricule' => $matricule,
                        'salaireBI' => $salaireBI
                    ]);
                }
                }


            } else {
                Log::info('Anapec contract: Skipping cotisations and frais professionnels', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'cotisationCNSS' => $cotisationCNSS,
                    'cotisationAMO' => $cotisationAMO,
                    'cotisationCIMR' => $cotisationCIMR,
                    'indeminitePE' => $indeminitePE, 
                    'fraisProfessionnels' => $fraisProfessionnels
                ]);
            }

            $salaireNI = round($salaireBI - $cotisationCNSS - $cotisationAMO  - $indeminitePE  - $cotisationCIMR - $cotisationMutuelle -$fraisProfessionnels, 2);
        Log::warning('ccccccccc', [
                            'ggggg' => $salaireNI,
                                'salaireBI' => $salaireBI
                            ]);
                    // Calculer l'IR brut
                    $irBrut = 0;
                    $irNet = 0;

                    if (!$isAnapec) {
                        $irData = ImpotSurRevenu::where('revenu_min', '<=', $salaireNI)
                            ->where(function ($query) use ($salaireNI) {
                                $query->where('revenu_max', '>=', $salaireNI)
                                    ->orWhereNull('revenu_max');
                            })
                            ->select('taux', 'somme_a_deduire')
                            ->first();

                        if ($irData) {
                            $irDataTaux = (float) $irData->taux;
                            $irBrut = round(($salaireNI * ($irData->taux / 100)) - $irData->somme_a_deduire, 2);
                            Log::info('IR brut calculated', [
                                'id_salarie' => $idSalarie,
                                'matricule' => $matricule,
                                'salaireNI' => $salaireNI,
                                'taux' => $irData->taux,
                                'somme_a_deduire' => $irData->somme_a_deduire,
                                'ir_brut' => $irBrut
                            ]);
                        } else {
                            Log::warning('No IR tranche found for salaireNI', [
                                'id_salarie' => $idSalarie,
                                'matricule' => $matricule,
                                'salaireNI' => $salaireNI
                            ]);
                        }

                        // Calculer les charges familiales
                        
                        if (strtolower($employeeData->situation_familiale) === 'marié') {
                            $cotisations = Cotisations::select('charge_de_famille')->first();
                            if (!is_null($cotisations->charge_de_famille)) {
                                $nombreEnfantLimite = min($employeeData->nombre_enfant ?? 0, 5);
                                $chargeFamiliale = $cotisations->charge_de_famille + ($cotisations->charge_de_famille * $nombreEnfantLimite);
                                Log::info('Charge familiale calculated', [
                                    'id_salarie' => $idSalarie,
                                    'matricule' => $matricule,
                                    'situation_familiale' => $employeeData->situation_familiale,
                                    'nombre_enfant' => $employeeData->nombre_enfant,
                                    'nombre_enfant_limite' => $nombreEnfantLimite,
                                    'charge_de_famille' => $cotisations->charge_de_famille,
                                    'charge_familiale' => $chargeFamiliale
                                ]);
                            } else {
                                Log::warning('No charge_de_famille found in cotisations', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
                            }
                        } else {
                            Log::info('No charge familiale calculated, employee not married', [
                                'id_salarie' => $idSalarie,
                                'matricule' => $matricule,
                                'situation_familiale' => $employeeData->situation_familiale
                            ]);
                        }

                        // Calculer l'IR net
                        if ($chargeFamiliale == 0) {
                            $irNet = $irBrut;
                            Log::info('IR net equals IR brut (no charge familiale)', [
                                'id_salarie' => $idSalarie,
                                'matricule' => $matricule,
                                'ir_brut' => $irBrut,
                                'ir_net' => $irNet
                            ]);
                        } elseif ($irBrut > $chargeFamiliale) {
                            $irNet = $irBrut - $chargeFamiliale;
                            Log::info('IR net calculated (IR brut > charge familiale)', [
                                'id_salarie' => $idSalarie,
                                'matricule' => $matricule,
                                'ir_brut' => $irBrut,
                                'charge_familiale' => $chargeFamiliale,
                                'ir_net' => $irNet
                            ]);
                        } else {
                            $irNet = 0;
                            Log::info('IR net set to 0 (charge familiale >= IR brut)', [
                                'id_salarie' => $idSalarie,
                                'matricule' => $matricule,
                                'ir_brut' => $irBrut,
                                'charge_familiale' => $chargeFamiliale,
                                'ir_net' => $irNet
                            ]);
                        }
                    } else {
                        Log::info('Anapec contract: Skipping IR calculation', [
                            'id_salarie' => $idSalarie,
                            'matricule' => $matricule,
                            'irBrut' => $irBrut,
                            'irNet' => $irNet
                        ]);
                    }

            $avancement = Depences::where('salarie_id', $idSalarie)
                ->whereHas('natureDepense', function ($query) {
                    $query->where('designation', 'Avancements de salaires');
                })
                ->where('mois_depenses', sprintf('%02d', $monthNumber))
                ->whereYear('date', $year)
                ->sum('montant');

            Log::info('Avancements de salaires retrieved', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'year' => $year,
                'month' => $monthNumber,
                'avancement' => $avancement
            ]);

            $primPanier = (float) $primPanier;
            Log::info('Prime panier assigned', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'prime_panier' => $primPanier
            ]);

            $indTransUrbain = (float) $indTransUrbain;
            Log::info('Indemnité de transport urbain assigned', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'ind_trans_urbain' => $indTransUrbain
            ]);

            $primeRepresentation = (float) $primeRepresentation;
            Log::info('Prime representation assigned', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'prime_representation' => $primeRepresentation
            ]);

            $primeDeplacement = (float) $primeDeplacement;
            Log::info('PrimeDeplacement assigned', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'PrimeDeplacement' => $primeDeplacement
            ]);

            $autresPrimesImposablesCalculated = (float) $autresPrimesImposablesCalculated;
            Log::info('Autres primes imposables assigned', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'autres_primes_imposables' => $autresPrimesImposablesCalculated
            ]);

            $primesDivers = (float) $primesDivers;
            Log::info('Primes divers assigned', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'primes_divers' => $primesDivers
            ]);

            // Calculer le net à payer
            $netPayer = round(($salaireBI+ $primNonimposables)- ($cotisationCNSS + $cotisationAMO  + $indeminitePE  + $cotisationCIMR + $cotisationMutuelle + $irNet) - $avancement, 2);
            Log::info('Net payer calculated', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'salaireBI' => $salaireBI,
                'cotisationCNSS' => $cotisationCNSS,
                'cotisation_amo' => $cotisationAMO,
                'cotisation_cimr' => $cotisationCIMR,
                'indeminite_pe' => $indeminitePE, 
                'ir_net' => $irNet,
                'avancement' => $avancement,
                'primNonimposables' => $primNonimposables,
                'jour_ferie' => $jourFerier,
                'jours_feries_travailles' => $joursFeriesTravailles,
                'conges_paye' => $congesPaye,
                'prime_journaliere' => $prime_journaliere,
                'base_prime_journaliere' => $base_prime_journaliere,
                'taux_prime_journaliere' => $taux_prime_journaliere,
                'net_payer' => $netPayer
            ]);

            $netPayerSansExtras = round($salaireBI - ($cotisationCNSS + $cotisationAMO  + $indeminitePE  + $cotisationCIMR + $irNet), 2);

             

            // Préparer les données de paiement
            $paymentData = [
                'id_salarie' => $idSalarie,
                'annee' => $year,
                'mois' => $monthNumber,
                'salaire' => $netPayer,
                'typer' => 'a',
                'statutspj' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'prime_panier' => $primPanier,
                'ind_trans_urbain' => $indTransUrbain,
                'ind_transUrbainInput' => (float) $indTransUrbainInput,
                'prime_representation' => $primeRepresentation,
                'PrimeDeplacement' => $primeDeplacement,
                'autresPrimesImposables' => $autresPrimesImposablesCalculated,
                'primesDivers' => (float) $primesDivers,
                 'primesDiversInput' => (float) $primesDiversInput,
                'charge_de_famille' => $chargeFamiliale,
                'net_payer_sans_extras' => $netPayerSansExtras,
                'ir_brut' => $irBrut,
                'cotisation_amo' => $cotisationAMO,
                'cotisation_cimr' => $cotisationCIMR,
                'prime_anciennete' => $primeAnciennete,
                'professional_tax_deduction' => $fraisProfessionnels,
                'daily_salary' => $dailySalary,
                'indemnite_pe' => $indeminitePE, 
                'salaired' => $salaired,
                'jour_ferie' => $jourFerier,
                'jours_feries_travailles' => $joursFeriesTravailles,
                'conges_paye' => $congesPaye,
                'conges_paye_count' => $congesPayeCount,
                'joursFeriesCount' => $joursFeriesCount,
                'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount,
                'prime_journaliere' => $prime_journaliere,
                'base_prime_journaliere' => $base_prime_journaliere,
                'taux_prime_journaliere' => $taux_prime_journaliere,
                'salaireBG' => $salaireBG,
                'primPanierInput' => (float) $primPanierInput,
                'prime_representation_input' => (float) $primeRepresentationInput,
                'PrimeDeplacementInput' => (float) $primeDeplacementInput,
                'salaireNI' => $salaireNI,
                'salaireBI' => $salaireBI,
                'irNet' => $irNet,
                'cotisation_cnss' => $cotisationCNSS,
                'num_jours' => $joursPresence,
                'tauxAnciennete' => $tauxAnciennete,
                'cnss_ps' => $cnss_ps,
                'amo_ps' => $amo_ps,
                'ipe_ps' => $ipe_ps,
                'tauxFrais' => $tauxFrais,
                'taux_cimr' => $tauxCIMR,
                'irData' => $irDataTaux,
                'avancements_sal' => (float) $avancement,
                'primNonimposables' => $primNonimposables,
                'cotisation_mutuelle' => $cotisationMutuelle,
                'taux_mutuelle' => $tauxMutuelle,
                'somme_a_deduire' => (float) ($irData->somme_a_deduire ?? 0), 
                'heurSuppPresence' => (float) $heurSuppPresence,
                'heurSuppPresencematin' => (float) $heurSuppPresencematin,
                'heurSuppPresenceNuit' => (float) $heurSuppPresenceNuit,
                'tauxHeureSupp' => (float) $tauxHeureSupp,
                'heurSuppPresencematinF' => (float) $heurSuppPresencematinF,
                'heurSuppPresenceNuitF' => (float) $heurSuppPresenceNuitF,
                 'heurSupp25' => (float) $heurSupp25,
                 'heurSupp100' => (float) $heurSupp100,
                  'heurSupp50' => (float) $heurSupp50,
                  'heurSuppPresencematinF' => (float) $heurSuppPresencematinF, 
                'heurSuppPresenceNuit' => (float) $heurSuppPresenceNuit,    
                'heurSupp50' => (float) $heurSupp50,
                'heurSuppPresenceNuitF' => (float) $heurSuppPresenceNuitF, 
                'heurSupp100' => (float) $heurSupp100,
                'salaireBaseImposable' => (float) $salaire_base_imposable,
                'joursCalcules' => (int) $joursCalcules,
                'joursCongesAvecPresence' => $joursCongesAvecPresence,
                'congesPayeTravailles' => $congesPayeTravailles,
                'congesPayeNonTravaillesCount'=>$congesPayeNonTravaillesCount,
                'congesPayeNonTravailles'=>$congesPayeNonTravailles,
                
      
               
           

               


            ];

            // Vérifications de schéma pour les colonnes optionnelles
            if (Schema::hasColumn('paiement_salaires', 'num_jours')) {
                $paymentData['num_jours'] = $joursPresence;
                Log::info('Added num_jours to paymentData', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'num_jours' => $joursPresence
                ]);
            } else {
                Log::warning('Column num_jours not found in paiement_salaires', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            if (Schema::hasColumn('paiement_salaires', 'tauxAnciennete')) {
                $paymentData['tauxAnciennete'] = $tauxAnciennete;
                Log::info('Added tauxAnciennete to paymentData', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'tauxAnciennete' => $tauxAnciennete
                ]);
            } else {
                Log::warning('Column tauxAnciennete not found in paiement_salaires', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            if (Schema::hasColumn('paiement_salaires', 'cnss_ps')) {
                $paymentData['cnss_ps'] = $cnss_ps;
                Log::info('Added cnss_ps to paymentData', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'cnss_ps' => $cnss_ps
                ]);
            } else {
                Log::warning('Column cnss_ps not found in paiement_salaires', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            if (Schema::hasColumn('paiement_salaires', 'amo_ps')) {
                $paymentData['amo_ps'] = $amo_ps;
                Log::info('Added amo_ps to paymentData', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'amo_ps' => $amo_ps
                ]);
            } else {
                Log::warning('Column amo_ps not found in paiement_salaires', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            if (Schema::hasColumn('paiement_salaires', 'ipe_ps')) {
                $paymentData['ipe_ps'] = $ipe_ps;
                Log::info('Added ipe_ps to paymentData', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'ipe_ps' => $ipe_ps
                ]);
            } else {
                Log::warning('Column ipe_ps not found in paiement_salaires', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            if (Schema::hasColumn('paiement_salaires', 'tauxFrais')) {
                $paymentData['tauxFrais'] = $tauxFrais;
                Log::info('Added tauxFrais to paymentData', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'tauxFrais' => $tauxFrais
                ]);
            } else {
                Log::warning('Column tauxFrais not found in paiement_salaires', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            if (Schema::hasColumn('paiement_salaires', 'taux_cimr')) {
                $paymentData['taux_cimr'] = $tauxCIMR;
                Log::info('Added taux_cimr to paymentData', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'taux_cimr' => $tauxCIMR
                ]);
            } else {
                Log::warning('Column taux_cimr not found in paiement_salaires', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            if (Schema::hasColumn('paiement_salaires', 'irData')) {
                $paymentData['irData'] = $irDataTaux;
                Log::info('Added irData to paymentData', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'irData' => $irDataTaux
                ]);
            } else {
                Log::warning('Column irData not found in paiement_salaires', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            if (Schema::hasColumn('paiement_salaires', 'avancements_sal')) {
                $paymentData['avancements_sal'] = (float) $avancement;
                Log::info('Added avancements_sal to paymentData', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'avancements_sal' => $avancement
                ]);
            } else {
                Log::warning('Column avancements_sal not found in paiement_salaires', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            if (Schema::hasColumn('paiement_salaires', 'primNonimposables')) {
                $paymentData['primNonimposables'] = $primNonimposables;
                Log::info('Added primNonimposables to paymentData', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'primNonimposables' => $primNonimposables
                ]);
            } else {
                Log::warning('Column primNonimposables not found in paiement_salaires', ['id_salarie' => $idSalarie, 'matricule' => $matricule]);
            }

            // Insérer les données dans la table paiement_salaires
         // Insérer les données dans la table paiement_salaires et récupérer l'ID
        DB::beginTransaction();
        try {
            $paymentId = DB::table('paiement_salaires')->insertGetId($paymentData);
            Log::info('Salary payment inserted successfully', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'year' => $year,
                'month' => $monthNumber,
                'payment_id' => $paymentId,
                'payment_data' => $paymentData
            ]);

            // Vérifier si la colonne pour le mois existe dans la table salairs_{$year}
            if (!Schema::hasColumn($tableName, $month)) {
                Log::error('Month column does not exist in salary table', [
                    'table' => $tableName,
                    'month' => $month,
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule
                ]);
                DB::rollBack();
                return response()->json(['error' => "La colonne {$month} n'existe pas dans la table {$tableName}."], 400);
            }

        // Mettre à jour la table salairs_{$year} avec l'ID du paiement
        Log::info('Preparing to update salary table', [
            'table' => $tableName,
            'id_salarie' => $idSalarie,
            'matricule' => $matricule,
            'month' => $month,
            'payment_id' => $paymentId
        ]);

        DB::table($tableName)
            ->where('id_salarie', $idSalarie)
            ->update([
                $month => $paymentId,
                'updated_at' => now()
            ]);

        Log::info('Salary table updated', [
            'table' => $tableName,
            'id_salarie' => $idSalarie,
            'matricule' => $matricule,
            'month' => $month,
            'payment_id' => $paymentId
        ]);

        DB::commit();
        DB::table('salaries')
        ->where('id', $idSalarie)
        ->update([
            'salaire_net' => $netPayer,
            'updated_at'  => now(),
        ]);

        Log::info('salaire_net mis à jour dans la table salaries', [
            'id_salarie'  => $idSalarie,
            'matricule'   => $matricule,
            'salaire_net' => $netPayer,
        ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to insert salary payment or update salary table', [
                    'id_salarie' => $idSalarie,
                    'matricule' => $matricule,
                    'error' => $e->getMessage(),
                    'payment_data' => $paymentData
                ]);
                return response()->json(['error' => 'Erreur lors de l\'enregistrement du paiement ou de la mise à jour de la table des salaires : ' . $e->getMessage()], 500);
            }

            // Préparer les détails pour la réponse
            $details = [

                'base_salary' => $baseSalary,
                'id_salarie' => $idSalarie,
                'annee' => $year,
                'mois' => $monthNumber,
                'salaire' => $netPayer,
                'typer' => 'a',
                'statutspj' => 0,
                'prime_panier' => $primPanier,
                'ind_trans_urbain' => $indTransUrbain,
                'prime_representation' => $primeRepresentation,
                'PrimeDeplacement' => $primeDeplacement,
                'autresPrimesImposables' => $autresPrimesImposablesCalculated,
                'primesDivers' => $primesDivers,
                'charge_de_famille' => $chargeFamiliale,
                'net_payer_sans_extras' => $netPayerSansExtras,
                'ir_brut' => $irBrut,
                'cotisation_amo' => $cotisationAMO,
                'cotisation_cimr' => $cotisationCIMR,
                'prime_anciennete' => $primeAnciennete,
                'professional_tax_deduction' => $fraisProfessionnels,
                'daily_salary' => $dailySalary,
                'indemnite_pe' => $indeminitePE, 
                'salaired' => $salaired,
                'jour_ferie' => $jourFerier,
                'jours_feries_travailles' => $joursFeriesTravailles,
                'conges_paye' => $congesPaye,
                'conges_paye_count' => $congesPayeCount,
                'joursFeriesCount' => $joursFeriesCount,
                'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount,
                'prime_journaliere' => $prime_journaliere,
                'base_prime_journaliere' => $base_prime_journaliere,
                'taux_prime_journaliere' => $taux_prime_journaliere,
                'salaireBG' => $salaireBG,
                'salaireNI' => $salaireNI,
                'salaireBI' => $salaireBI,
                'irNet' => $irNet,
            
                'cotisation_cnss' => $cotisationCNSS,
                'num_jours' => $joursPresence,
                'tauxAnciennete' => $tauxAnciennete,
                'cnss_ps' => $cnss_ps,
                'amo_ps' => $amo_ps,
                'ipe_ps' => $ipe_ps,
                'tauxFrais' => $tauxFrais,
                'taux_cimr' => $tauxCIMR,
                'irData' => $irDataTaux,
                'avancements_sal' => (float) $avancement,
                'primNonimposables' => $primNonimposables,
                'net_payer' => $netPayer,
                'heurSuppPresence' => (float) $heurSuppPresence,
                'heurSuppPresencematin' => (float) $heurSuppPresencematin,
                'heurSuppPresenceNuit' => (float) $heurSuppPresenceNuit,
                'conges_paye_non_travailles'     => $congesPayeNonTravailles,        // congés non travaillés (ligne du haut)
                'conges_paye_non_travailles_count' => $congesPayeNonTravaillesCount, // nb jours non travaillés
                'joursCongesAvecPresence'        => $joursCongesAvecPresence,
                'joursCongesAvecPresence'=>$joursCongesAvecPresence,
                'congesPayeTravailles'=>$congesPayeTravailles

            ];

            // Message de congé si applicable
            $leaveMessage = null;
            if ($leaveData) {
                $leaveMessage = "Le salarié est en congé du {$leaveData->date_debut} au {$leaveData->date_fin}. Les primes et indemnités non imposables sont mises à zéro.";
            }

            Log::info('Salary increment completed successfully', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'year' => $year,
                'month' => $month,
                'net_payer' => $netPayer,
                'leave_message' => $leaveMessage
            ]);

            return response()->json([
                'success' => true,
                'details' => $details,
                'leave_message' => $leaveMessage
            ], 200);

        } catch (\Exception $e) {
            Log::error('Unexpected error in incrementSalary', [
                'id_salarie' => $idSalarie,
                'matricule' => $matricule,
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Erreur inattendue : ' . $e->getMessage()], 500);
        }
    } 



 

// generation de bulletin de paie 
  public function generatePaySlipPDF(Request $request)
{
    $idSalarie = $request->input('id_salarie');
    $year = $request->input('year');
    $month = $request->input('month');

    Log::info('generatePaySlipPDF called', [
        'id_salarie' => $idSalarie,
        'year' => $year,
        'month' => $month
    ]);

    if (!$idSalarie || !$year || !$month) {
        Log::error('Error: Missing required parameters in generatePaySlipPDF');
        return response()->json(['error' => 'ID salarié, année ou mois manquant.'], 400);
    }

    $monthMap = [
        'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
        'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
        'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
    ];
    $monthKey = strtolower($month);
    if (!array_key_exists($monthKey, $monthMap)) {
        Log::error('Error: Invalid month', ['month' => $month]);
        return response()->json(['error' => "Mois invalide : {$month}."], 400);
    }
    $monthNumber = $monthMap[$monthKey];

    try {
        $paymentData = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)
            ->where('annee', $year)
            ->where('mois', $monthNumber)
            ->select(
                'id',
                'salaire',
                'conges_paye',
                'conges_paye_count',
                'joursFeriesTravaillesCount',
                'joursFeriesCount',
                'jour_ferie',
                'prime_panier',
                'primPanierInput',
                'prime_representation',
                'prime_representation_input',
                'PrimeDeplacement',
                'PrimeDeplacementInput',
                'autresPrimesImposables',
                'num_jours',
                'avancements_sal',
                'tauxFrais',
                'professional_tax_deduction',
                'primNonimposables',
                'taux_cimr', 
                'cotisation_cimr',
                'indemnite_pe',
                'cotisation_mutuelle',
                'taux_mutuelle',
                'charge_de_famille',
                'irData',
                
                'heurSuppPresence',
                'heurSuppPresence',
                'heurSupp25',
                'tauxHeureSupp',
                'heurSuppPresencematin',
                'heurSuppPresencematinF', 
                'heurSuppPresenceNuit',   
                'heurSupp50',
                'heurSuppPresenceNuitF',
                'heurSupp100',
                'salaireBaseImposable',
                'ind_transUrbainInput',
                'primesDiversInput',
                'primesDivers',
                'joursCalcules'

                
            )
            ->first();

        $details = $request->input('details', []);
        Log::info('Details payload in generatePaySlipPDF', [
            'details' => $details,
            'avancements_sal' => $details['avancements_sal'] ?? 'Not provided',
            'tauxFrais' => $details['tauxFrais'] ?? 'Not provided',
            'professional_tax_deduction' => $details['professional_tax_deduction'] ?? 'Not provided',
            'primNonimposables' => $details['primNonimposables'] ?? 'Not provided',
            'taux_cimr' => $details['taux_cimr'] ?? 'Not provided', 
            'cotisation_cimr' => $details['cotisation_cimr'] ?? 'Not provided' ,
            'indeminite_pe' => $details['indeminite_pe'] ?? 'Not provided'
        ]);

        if (!$paymentData) {
            Log::warning('Payment not found, attempting to create', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month
            ]);
            if (empty($details['net_payer'])) {
                Log::error('Cannot create payment without net_payer', ['id_salarie' => $idSalarie]);
                return response()->json(['error' => 'Détails du salaire (net_payer) manquants pour créer le dossier.'], 400);
            }

            $paymentId = DB::table('paiement_salaires')->insertGetId([
                'id_salarie' => $idSalarie,
                'annee' => $year,
                'mois' => $monthNumber,
                'salaire' => $details['net_payer'],
                'conges_paye' => $details['conges_paye'] ?? 0,
                'conges_paye_count' => $details['conges_paye_count'] ?? 0,
                'joursFeriesTravaillesCount' => $details['joursFeriesTravaillesCount'] ?? 0,
                'joursFeriesCount' => $details['joursFeriesCount'] ?? 0,
                'jour_ferie' => $details['jour_ferie'] ?? 0,
                'prime_panier' => $details['prime_panier'] ?? 0,
                'primPanierInput' => $details['prime_panier_input'] ?? 0,
                'prime_representation' => $details['prime_representation'] ?? 0,
                'prime_representation_input' => $details['prime_representation_input'] ?? 0,
                'PrimeDeplacement' => $details['PrimeDeplacement'] ?? 0,
                'PrimeDeplacementInput' => $details['PrimeDeplacementInput'] ?? 0,
                'autresPrimesImposables' => $details['autresPrimesImposables'] ?? 0,
                'num_jours' => $details['working_days'] ?? 24,
                'avancements_sal' => $details['avancements_sal'] ?? 0,
                'tauxFrais' => $details['tauxFrais'] ?? 0,
                'professional_tax_deduction' => $details['professional_tax_deduction'] ?? 0,
                'primNonimposables' => $details['primNonimposables'] ?? 0,
                'taux_cimr' => $details['taux_cimr'] ?? 0, // Added
                'cotisation_cimr' => $details['cotisation_cimr'] ?? 0, 
                'indemnite_pe' => $details['indeminite_pe'] ?? 0,
                'cotisation_mutuelle' => $details['cotisation_mutuelle'] ?? 0,
                'taux_mutuelle' => $details['taux_mutuelle'] ?? 0,
                'cotisation_mutuelle' => $details['cotisation_mutuelle'] ?? 0,
                'taux_mutuelle' => $details['taux_mutuelle'] ?? 0,
                'heurSupp25' => $details['heurSupp25'] ?? 0,
                'tauxHeureSupp' => $details['tauxHeureSupp'] ?? 0,
                'heurSuppPresencematin' => $details['heurSuppPresencematin'] ?? 0,
                'heurSuppPresencematinF' => $details['heurSuppPresencematinF'] ?? 0, 
                 'heurSuppPresenceNuit' => $details['heurSuppPresenceNuit'] ?? 0,     
                  'heurSupp50' => $details['heurSupp50'] ?? 0,
                  'heurSuppPresenceNuitF' => $details['heurSuppPresenceNuitF'] ?? 0,
                'heurSupp100' => $details['heurSupp100'] ?? 0,

                'created_at' => now(),
                'updated_at' => now()
            ]);

            $paymentData = (object) [
                'id' => $paymentId,
                'salaire' => $details['net_payer'],
                'conges_paye' => $details['conges_paye'] ?? 0,
                'conges_paye_count' => $details['conges_paye_count'] ?? 0,
                'joursFeriesTravaillesCount' => $details['joursFeriesTravaillesCount'] ?? 0,
                'joursFeriesCount' => $details['joursFeriesCount'] ?? 0,
                'jour_ferie' => $details['jour_ferie'] ?? 0,
                'prime_panier' => $details['prime_panier'] ?? 0,
                'primPanierInput' => $details['prime_panier_input'] ?? 0,
                'prime_representation' => $details['prime_representation'] ?? 0,
                'prime_representation_input' => $details['prime_representation_input'] ?? 0,
                'PrimeDeplacement' => $details['PrimeDeplacement'] ?? 0,
                'PrimeDeplacementInput' => $details['PrimeDeplacementInput'] ?? 0,
                'autresPrimesImposables' => $details['autresPrimesImposables'] ?? 0,
                'num_jours' => $details['working_days'] ?? 24,
                'avancements_sal' => $details['avancements_sal'] ?? 0,
                'tauxFrais' => $details['tauxFrais'] ?? 0,
                'professional_tax_deduction' => $details['professional_tax_deduction'] ?? 0,
                'primNonimposables' => $details['primNonimposables'] ?? 0,
                'taux_cimr' => $details['taux_cimr'] ?? 0, 
                'cotisation_cimr' => $details['cotisation_cimr'] ?? 0 ,
                'indemnite_pe' => $details['indeminite_pe'] ?? 0,
                 'cotisation_mutuelle' => $details['cotisation_mutuelle'] ?? 0,
                'taux_mutuelle' => $details['taux_mutuelle'] ?? 0,
                'heurSupp25' => $details['heurSupp25'] ?? 0,
                'tauxHeureSupp' => $details['tauxHeureSupp'] ?? 0,
                'heurSuppPresencematin' => $details['heurSuppPresencematin'] ?? 0,
                'heurSuppPresencematinF' => $details['heurSuppPresencematinF'] ?? 0, 
                'heurSuppPresenceNuit' => $details['heurSuppPresenceNuit'] ?? 0,     
                'heurSupp50' => $details['heurSupp50'] ?? 0,
                'heurSuppPresenceNuitF' => $details['heurSuppPresenceNuitF'] ?? 0, 
                'heurSupp100' => $details['heurSupp100'] ?? 0,
                'net_ir'  => (float)($details['ir_net'] ?? 0),

            ];
            Log::info('Created new payment record', [
                'id_salarie' => $idSalarie,
                'payment_id' => $paymentId,
                'num_jours' => $paymentData->num_jours,
                'avancements_sal' => $paymentData->avancements_sal,
                'tauxFrais' => $paymentData->tauxFrais,
                'professional_tax_deduction' => $paymentData->professional_tax_deduction,
                'primNonimposables' => $paymentData->primNonimposables,
                'taux_cimr' => $paymentData->taux_cimr, 
                'cotisation_cimr' => $paymentData->cotisation_cimr ,
                'indemnite_pe' => $paymentData->indemnite_pe
            ]);
        }

        $employeeData = Salarie::where('id', $idSalarie)
            ->select(
                'nom',
                'prenom',
                'cin',
                'date_naissance',
                'date_embauche',
                'situation_familiale',
                'nombre_enfant',
                'adresse',
                'n_matricule_cnss',
                'n_matricule_entreprise',
                'anciennete',
                'reglement_id',
                'fonction_id'
            )
            ->first();

        if (!$employeeData) {
            Log::error('Employee not found', ['id_salarie' => $idSalarie]);
            return response()->json(['error' => "Salarié ID {$idSalarie} non trouvé."], 404);
        }

        $fonctionName = 'N/A';
        if ($employeeData->fonction_id) {
            $fonction = DB::table('fonctions')
                ->where('id', $employeeData->fonction_id)
                ->value('designation');
            $fonctionName = $fonction ?? 'N/A';
        }

        $paymentMethod = 'ESPÈCE';
        if ($employeeData->reglement_id) {
            $reglement = DB::table('type_reglement')
                ->where('id', $employeeData->reglement_id)
                ->value('designation');
            $paymentMethod = $reglement ?? 'ESPÈCE';
        }

        $cotisations = Cotisations::select('cnss_ps', 'ipe_ps', 'taux_cimr')->first(); 
        if (!$cotisations) {
            Log::error('No cotisations found', ['id_salarie' => $idSalarie]);
            return response()->json(['error' => 'Aucune donnée de cotisation trouvée.'], 500);
        }

        $companySettings = CompanySettings::first();
        if (!$companySettings) {
            Log::error('No company settings found', ['id_salarie' => $idSalarie]);
            return response()->json(['error' => 'Aucune configuration d\'entreprise trouvée.'], 500);
        }
        $companySettings = $companySettings->toArray();

        $defaultLogoPath = 'assets/img/favicon/anassi2.jpg';
        $customLogoPath = !empty($companySettings['logo']) ? 'storage/' . $companySettings['logo'] : null;

        if ($customLogoPath && file_exists(public_path($customLogoPath))) {
            $companySettings['logo_path'] = public_path($customLogoPath);
        } else {
            $companySettings['logo_path'] = file_exists(public_path($defaultLogoPath)) ? public_path($defaultLogoPath) : null;
        }

        $seniorityRate = 0;
        if (!is_null($employeeData->anciennete)) {
            $tauxData = DB::table('anciennete_taux')
                ->where('an_min', '<=', $employeeData->anciennete)
                ->where(function ($query) use ($employeeData) {
                    $query->where('an_max', '>=', $employeeData->anciennete)
                        ->orWhereNull('an_max');
                })
                ->select('taux')
                ->first();

            if ($tauxData) {
                $seniorityRate = (float) $tauxData->taux;
            }
        }

        $employee = [
            'name' => $employeeData->nom ?? 'Inconnu',
            'first_name' => $employeeData->prenom ?? 'Inconnu',
            'cin' => $employeeData->cin ?? 'N/A',
            'birth_date' => $employeeData->date_naissance ? \Carbon\Carbon::parse($employeeData->date_naissance)->format('d/m/Y') : 'N/A',
            'hire_date' => $employeeData->date_embauche ? \Carbon\Carbon::parse($employeeData->date_embauche)->format('d/m/Y') : 'N/A',
            'cnss_number' => $employeeData->n_matricule_cnss ?? 'N/A',
            'ese_number' => $employeeData->n_matricule_entreprise ?? 'N/A',
            'marital_status' => $employeeData->situation_familiale ?? 'N/A',
            'children_count' => $employeeData->nombre_enfant ?? 0,
            'address' => $employeeData->adresse ?? 'N/A',
            'payment_method' => $paymentMethod,
            'seniority' => !is_null($employeeData->anciennete) ? floor($employeeData->anciennete) . ' an(s)' : 'Entre 0 à 2 ans',
            'fonction' => $fonctionName
        ];

        $period = [
            'start' => sprintf('01/%02d/%d', $monthNumber, $year),
        ];

        $payment = (array) $paymentData;

     $payroll = [
    'num_jours' => (int)($details['num_jours'] ?? $paymentData->num_jours ?? 24),
    'days_worked' => (int)($details['num_jours'] ?? $paymentData->num_jours ?? 24),
    'base_salary' => (float)($details['base_salary'] ?? 0),
    'daily_salary' => (float)($details['daily_salary'] ?? 0),
    'salaired' => (float)($details['salaired'] ?? 0),
    'gross_salary' => (float)($details['salaireBI'] ?? 0),
    'taxable_salary' => (float)($details['salaireNI'] ?? 0),
    'net_to_pay' => (float)($details['net_payer'] ?? 0),
    'seniority_rate' => sprintf("%.2f%%", $seniorityRate),
    'seniority_rate_raw' => $seniorityRate,
    'prime_anciennete' => (float)($details['prime_anciennete'] ?? 0),
    'cnss_rate' => sprintf("%.2f%%", $cotisations->cnss_ps ?? 0),
    'cnss_deduction' => (float)($details['cotisation_cnss'] ?? 0),
    'amo_rate' => sprintf("%.2f%%", ($details['cotisation_amo'] ?? 0) / ($details['salaireBI'] ?? 1) * 100),
    'amo_deduction' => (float)($details['cotisation_amo'] ?? 0),
    'employment_loss_rate' => sprintf("%.2f%%", $cotisations->ipe_ps ?? 0.19),
    'employment_loss_deduction' => (float)($details['indeminite_pe'] ?? $paymentData->indemnite_pe ?? 0),  
    'professional_tax_rate' => sprintf("%.2f%%", $paymentData->tauxFrais ?? $details['tauxFrais'] ?? 0),
    'professional_tax_deduction' => (float)($paymentData->professional_tax_deduction ?? $details['professional_tax_deduction'] ?? 0),
    'gross_ir' => (float)($details['ir_brut'] ?? 0),
    'net_ir'   => (float) ($details['irNet']   ?? $payment['irNet'] ?? $payment['irNet'] ?? $payment['irNet'] ?? 0),
    'total_gains' => (float)($details['salaireBI'] ?? 0),
    'total_deductions' => (float)($details['cotisation_cnss'] ?? 0) +
                         (float)($details['cotisation_amo'] ?? 0) +
                         (float)($details['cotisation_cimr'] ?? 0) +
                         (float)($details['cotisation_mutuelle']  ?? 0) +
                         (float)($details['avancements_sal']  ?? 0) +
                         (float)($paymentData->professional_tax_deduction ?? $details['professional_tax_deduction']?? 0) +

                         (float)($details['ir_net'] ?? 0),

    'charge_familiale' => (float)($details['charge_de_famille'] ?? $paymentData->charge_de_famille ?? 0),
    'avancements_sal' => (float)($details['avancements_sal'] ?? $paymentData->avancements_sal ?? 0),
    'prime_panier' => (float)($details['prime_panier'] ?? $paymentData->prime_panier ?? 0),
    'primPanierInput' => (float)($details['prime_panier_input'] ?? $paymentData->primPanierInput ?? 0),
    'ind_trans_urbain' => (float)($details['ind_trans_urbain'] ?? $paymentData->ind_trans_urbain ?? 0),
    'ind_transUrbainInput' => (float)($details['ind_transUrbainInput'] ?? $paymentData->ind_transUrbainInput ?? 0),
    'net_payer_sans_extras' => (float)($details['net_payer_sans_extras'] ?? $paymentData->net_payer_sans_extras ?? 0),
    'conges_paye' => (float)($details['conges_paye'] ?? $paymentData->conges_paye ?? 0),
    'conges_paye_count' => (int)($details['conges_paye_count'] ?? $paymentData->conges_paye_count ?? 0),
    'jour_ferie' => (float)($details['jour_ferie'] ?? $paymentData->jour_ferie ?? 0),
    'jours_feries_travailles' => (float)($details['jours_feries_travailles'] ?? $paymentData->jours_feries_travailles ?? 0),
    'joursFeriesTravaillesCount' => (int)($details['joursFeriesTravaillesCount'] ?? $paymentData->joursFeriesTravaillesCount ?? 0),
    'joursFeriesCount' => (int)($details['joursFeriesCount'] ?? $paymentData->joursFeriesCount ?? 0),
    'base_prime_journaliere' => (float)($details['base_prime_journaliere'] ?? 0) < 26 ? 0 : (float)($details['base_prime_journaliere'] ?? 0),
    'taux_prime_journaliere' => (float)($details['taux_prime_journaliere'] ?? 0),
    'prime_journaliere' => (float)($details['prime_journaliere'] ?? 0),
    'autresPrimesImposables' => (float)($details['autresPrimesImposablesInput'] ?? $request->input('autres_primes_imposables', 0)),
    'autresPrimesImposablesCalculated' => (float)($details['autresPrimesImposables'] ?? $paymentData->autresPrimesImposables ?? 0),
    'representation_bonus' => (float)($details['prime_representation'] ?? $paymentData->prime_representation ?? 0),
    'representation_bonus_input' => (float)($details['prime_representation_input'] ?? $paymentData->prime_representation_input ?? 0),
    'PrimeDeplacement' => (float)($details['PrimeDeplacement'] ?? $paymentData->PrimeDeplacement ?? 0),
    'PrimeDeplacementInput' => (float)($details['PrimeDeplacementInput'] ?? $paymentData->PrimeDeplacementInput ?? 0),
    'salaireBG' => (float)($details['salaireBG'] ?? 0),
    'primesDivers' => (float)($details['primes_divers'] ?? $paymentData->primesDivers ?? 0),
    'primesDiversInput' => (float)($details['primes_divers_input'] ?? $paymentData->primesDiversInput ?? 0),
    'primNonimposables' => (float)($details['primNonimposables'] ?? $paymentData->primNonimposables ?? 0),
    'taux_cimr' => (float)($details['taux_cimr'] ?? $paymentData->taux_cimr ?? 0),
    'cotisation_cimr' => (float)($details['cotisation_cimr'] ?? $paymentData->cotisation_cimr ?? 0),
    'cotisation_mutuelle' => (float)($details['cotisation_mutuelle'] ?? $paymentData->cotisation_mutuelle ?? 0),
    'taux_mutuelle' => (float)($details['taux_mutuelle'] ?? $paymentData->taux_mutuelle ?? 0),
    'irData' => (float)($details['irData'] ?? $paymentData->irData ?? 0),
    'heurSuppPresence' => (float)($details['heurSuppPresence'] ?? $paymentData->heurSuppPresence ?? 0),
    'heurSuppPresence' => (float)($details['heurSuppPresence'] ?? $paymentData->heurSuppPresence ?? 0),
    'heurSupp25' => (float)($details['heurSupp25'] ?? $paymentData->heurSupp25 ?? 0),
    'tauxHeureSupp' => (float)($details['tauxHeureSupp'] ?? $paymentData->tauxHeureSupp ?? 0),
    'heurSuppPresencematin' => (float)($details['heurSuppPresencematin'] ?? $paymentData->heurSuppPresencematin ?? 0),
    'heurSuppPresencematinF' => (float)($details['heurSuppPresencematinF'] ?? $paymentData->heurSuppPresencematinF ?? 0), 
    'heurSuppPresenceNuit' => (float)($details['heurSuppPresenceNuit'] ?? $paymentData->heurSuppPresenceNuit ?? 0),    
    'heurSupp50' => (float)($details['heurSupp50'] ?? $paymentData->heurSupp50 ?? 0),
    'heurSuppPresenceNuitF' => (float)($details['heurSuppPresenceNuitF'] ?? $paymentData->heurSuppPresenceNuitF ?? 0), 
    'heurSupp100' => (float)($details['heurSupp100'] ?? $paymentData->heurSupp100 ?? 0),
    'salaireBaseImposable' => (float)($paymentData->salaireBaseImposable ?? $details['salaireBaseImposable'] ?? 0),
    'professional_tax_rate' => sprintf("%.2f%%", $paymentData->tauxFrais ?? $details['tauxFrais'] ?? 0),
    'jours_calcules' => (int)($details['joursCalcules'] ?? $paymentData->joursCalcules ?? 0),
    'conges_paye_non_travailles'       => (float)($details['conges_paye_non_travailles'] ?? 0),
    'conges_paye_non_travailles_count' => (int)($details['conges_paye_non_travailles_count'] ?? 0),
    'joursCongesAvecPresence'          => (int)($details['joursCongesAvecPresence'] ?? 0),
    'congesPayeTravailles'          => (int)($details['congesPayeTravailles'] ?? 0),




    ];

            Log::info('Payroll data passed to template', ['payroll' => $payroll]);

            $pdf = Pdf::loadView('bultin-paie.bultin_paie_pdf', compact('employee', 'period', 'payroll', 'companySettings'));

            $folderName = Str::slug($employeeData->nom . '_' . $employeeData->n_matricule_entreprise, '_');
            $directory = public_path("assets/storage/salaries/{$folderName}/salaires");

            if (!file_exists($directory)) {
                Log::info('Creating directory', ['directory' => $directory]);
                if (!mkdir($directory, 0755, true)) {
                    Log::error('Failed to create directory', ['directory' => $directory]);
                    throw new \Exception("Échec de la création du dossier : {$directory}");
                }
            }

            if (!is_writable($directory)) {
                Log::error('Directory not writable', ['directory' => $directory]);
                throw new \Exception("Le dossier n'est pas accessible en écriture : {$directory}");
            }

            $filename = "bulletin_paie_{$employeeData->n_matricule_entreprise}_" . strtolower($month) . "_{$year}.pdf";
            $path = "assets/storage/salaries/{$folderName}/salaires/{$filename}";
            $fullPath = public_path($path);

            $pdf->save($fullPath);

            if (!file_exists($fullPath)) {
                Log::error('PDF file not saved', ['full_path' => $fullPath]);
                throw new \Exception("Échec de l'enregistrement du PDF à {$fullPath}");
            }

            Log::info('PDF generated and saved successfully', [
                'path' => $path,
                'full_path' => $fullPath,
                'size' => filesize($fullPath)
            ]);

            if (Schema::hasColumn('pieces_joint', 'bultin_paie') && Schema::hasColumn('pieces_joint', 'id_paiment') && Schema::hasColumn('pieces_joint', 'id_salarier')) {
                DB::table('pieces_joint')->insert([
                    'id_paiment' => $paymentData->id,
                    'id_salarier' => $idSalarie,
                    'bultin_paie' => $path,
                    'type' => 'bp',
                    'mois' => $month,
                    'annee' => $year,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                Log::error('Required columns not found in pieces_joint', [
                    'id_salarie' => $idSalarie,
                    'year' => $year,
                    'month' => $month,
                    'columns' => ['id_paiment', 'id_salarier', 'bultin_paie']
                ]);
                throw new \Exception('Les champs id_paiment, id_salarier ou bultin_paie n\'existent pas dans la table pieces_joint.');
            }

            if (Schema::hasColumn('paiement_salaires', 'statutspj')) {
                DB::table('paiement_salaires')
                    ->where('id', $paymentData->id)
                    ->update(['statutspj' => 1]);
            } else {
                Log::error('Column statutspj not found in paiement_salaires', [
                    'id_salarie' => $idSalarie,
                    'year' => $year,
                    'month' => $month
                ]);
                throw new \Exception('Le champ statutspj n\'existe pas dans la table paiement_salaires.');
            }

            return response()->json([
                'success' => 'Bulletin de paie généré et enregistré avec succès.',
                'path' => $path
            ]);
        } catch (\Exception $e) {
            Log::error('Error in generatePaySlipPDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month
            ]);
            return response()->json(['error' => "Erreur : {$e->getMessage()}"], 500);
        }
}
   /**
 * Récupère les valeurs par défaut de configurationbp pour un salarié (utilisé par le modal JS)
 */
public function getConfigurationBp(Request $request)
{
    $id_salarie = $request->input('id_salarie');

    if (!$id_salarie) {
        return response()->json(['success' => false, 'message' => 'ID salarié manquant'], 400);
    }

    $config = DB::table('configurationbp')
        ->where('id_salarier', $id_salarie)
        ->first();

    if ($config) {
        return response()->json([
            'success' => true,
            'data' => [
                // Primes (déjà là)
                'pripanier'             => (float) $config->pripanier,
                'indtransport'          => (float) $config->indtransport,
                'prirepresentation'     => (float) $config->prirepresentation,
                'prideplacement'        => (float) $config->prideplacement,
                'pridivers'             => (float) $config->pridivers,
                'autrespriimposables'   => (float) $config->autrespriimposables,

                // Nouveaux champs booléens (1/0)
                'cotisation_cimr'         => (bool) $config->cotisation_cimr,
                'cotisation_mutuelle'     => (bool) $config->cotisation_mutuelle,
                'calculate_overtime'      => (bool) $config->calculate_overtime,
                'double_salary_holidays'  => (bool) $config->double_salary_holidays,
                'apply_frais_pro'         => (bool) $config->apply_frais_pro,
                'indemnite_pe'            => (bool) $config->indemnite_pe,           // IPE
                'apply_prime_rendement'   => (bool) $config->apply_prime_rendement,
            ]
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Aucune configuration trouvée'
    ]);
}


    public function generatePayslipsZip(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');
        $status = $request->input('status', 'actif');
        $employees = $request->input('employees', []);

        Log::info('generatePayslipsZip called', [
            'year' => $year,
            'month' => $month,
            'status' => $status,
            'employees' => $employees
        ]);

        if (!$year || !$month || empty($employees)) {
            Log::error('Missing required parameters in generatePayslipsZip', [
                'year' => $year,
                'month' => $month,
                'employees' => $employees
            ]);
            return response()->json(['error' => 'Année, mois ou liste des salariés manquante.'], 400);
        }

        // Validate employee IDs
        $employees = array_filter($employees, function($id) {
            return is_numeric($id) && (int)$id > 0;
        });
        if (empty($employees)) {
            Log::error('No valid employee IDs provided', ['employees' => $employees]);
            return response()->json(['error' => 'Aucun ID de salarié valide fourni.'], 400);
        }

        $monthMap = [
            'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
        ];
        $monthKey = strtolower($month);
        if (!array_key_exists($monthKey, $monthMap)) {
            Log::error('Invalid month', ['month' => $month]);
            return response()->json(['error' => "Mois invalide : {$month}."], 400);
        }
        $monthNumber = $monthMap[$monthKey];

        try {
            // Retrieve company settings
            $companySettings = CompanySettings::first();
            if (!$companySettings) {
                Log::error('No company settings found');
                return response()->json(['error' => 'Aucune configuration d\'entreprise trouvée.'], 500);
            }
            $companySettings = $companySettings->toArray();

            // Initialize ZIP archive
            $zip = new ZipArchive();
            $zipFileName = "Payslips_{$year}_{$monthKey}.zip";
            $zipFilePath = storage_path("app/temp/{$zipFileName}");
            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                Log::error('Failed to create ZIP archive', ['path' => $zipFilePath]);
                return response()->json(['error' => 'Échec de la création du fichier ZIP.'], 500);
            }

            $payslipPaths = [];
            foreach ($employees as $idSalarie) {
                // Check for payment with statutspj = 1
                $paymentData = DB::table('paiement_salaires')
                    ->where('id_salarie', $idSalarie)
                    ->where('annee', $year)
                    ->where('mois', $monthNumber)
                    ->where('statutspj', 1)
                    ->select('id', 'salaire', 'typer')
                    ->first();

                if (!$paymentData) {
                    Log::warning('No payment with statutspj = 1 found for employee, skipping', [
                        'id_salarie' => $idSalarie,
                        'year' => $year,
                        'month' => $month,
                        'query' => 'SELECT id, salaire, typer FROM paiement_salaires WHERE id_salarie = ' . $idSalarie . ' AND annee = ' . $year . ' AND mois = ' . $monthNumber . ' AND statutspj = 1'
                    ]);
                    continue;
                }

                // Check for existing payslip in pieces_joint
                $payslip = DB::table('pieces_joint')
                    ->where('id_paiment', $paymentData->id)
                    ->where('id_salarier', $idSalarie)
                    ->where('type', 'bp')
                    ->where('mois', $month)
                    ->where('annee', $year)
                    ->value('bultin_paie');

                if (!$payslip) {
                    Log::warning('No payslip found in pieces_joint for employee, skipping', [
                        'id_salarie' => $idSalarie,
                        'year' => $year,
                        'month' => $month,
                        'payment_id' => $paymentData->id,
                        'query' => 'SELECT bultin_paie FROM pieces_joint WHERE id_paiment = ' . $paymentData->id . ' AND id_salarier = ' . $idSalarie . ' AND type = "bp" AND mois = "' . $month . '" AND annee = "' . $year . '"'
                    ]);
                    continue;
                }

                $fullPath = public_path($payslip);
                if (file_exists($fullPath)) {
                    $filename = basename($payslip);
                    $zip->addFile($fullPath, $filename);
                    $payslipPaths[] = $payslip;
                    Log::info('Payslip added to ZIP', [
                        'id_salarie' => $idSalarie,
                        'payslip_path' => $payslip,
                        'full_path' => $fullPath
                    ]);
                } else {
                    Log::warning('Payslip file not found on server, skipping', [
                        'id_salarie' => $idSalarie,
                        'payslip_path' => $payslip,
                        'full_path' => $fullPath
                    ]);
                }
            }

            $zip->close();

            if (count($payslipPaths) === 0) {
                Log::error('No payslips were added to the ZIP', [
                    'year' => $year,
                    'month' => $month,
                    'employees' => $employees
                ]);
                return response()->json(['error' => 'Aucun bulletin de paie disponible pour les salariés avec statutspj = 1 pour le mois et l\'année sélectionnés.'], 400);
            }

            Log::info('ZIP file created', [
                'zip_path' => $zipFilePath,
                'payslips_included' => $payslipPaths
            ]);

            return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error in generatePayslipsZip', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'year' => $year,
                'month' => $month,
                'employees' => $employees
            ]);
            return response()->json(['error' => "Erreur : {$e->getMessage()}"], 500);
        }
    }

public function generateAnnualPaySlip(Request $request)
{
    $idSalarie = $request->input('id_salarie');
    $year      = $request->input('year');
 
    Log::info('generateAnnualPaySlip called', [
        'id_salarie' => $idSalarie,
        'year'       => $year
    ]);
 
    if (!$idSalarie || !$year) {
        Log::error('Error: Missing required parameters in generateAnnualPaySlip');
        return response()->json(['error' => 'ID salarié ou année manquant.'], 400);
    }
 
    try {
        // ── Colonnes à récupérer dans paiement_salaires ────────────────────
        $columns = [
            'num_jours',
            'daily_salary',
            'salaired',
            'salaireBI',
            'prime_anciennete',
            'cotisation_cnss',
            'cotisation_amo',
            'indemnite_pe',              // ← Indemnité perte d'emploi
            'professional_tax_deduction',
            'salaireNI',
            'ir_brut',
            'charge_de_famille',
            'irNet',
            'net_payer_sans_extras',
            'avancements_sal',
            'prime_panier',
            'ind_trans_urbain',
            'salaire',
            'joursFeriesCount',
            'joursFeriesTravaillesCount',
            'jour_ferie',
            'jours_feries_travailles',
            'conges_paye_count',
            'conges_paye',
            // ── Nouveaux champs congés ────────────────────────────────────
            'conges_paye_non_travailles',
            'conges_paye_non_travailles_count',
            'joursCongesAvecPresence',
            'congesPayeTravailles',
            // ─────────────────────────────────────────────────────────────
            'heurSuppPresencematin',
            'heurSupp25',
            'heurSuppPresencematinF',
            'heurSuppPresenceNuit',
            'heurSupp50',
            'heurSuppPresenceNuitF',
            'heurSupp100',
            'tauxHeureSupp',
            'base_prime_journaliere',
            'taux_prime_journaliere',
            'prime_journaliere',
            'autresPrimesImposables',
            'primPanierInput',
            'representation_bonus_input',
            'representation_bonus',
            'PrimeDeplacementInput',
            'PrimeDeplacement',
            'ind_transUrbainInput',
            'primesDiversInput',
            'primesDivers',
            'salaireBG',
            'primNonimposables',
            'cnss_rate',
            'amo_rate',
            'taux_cimr',
            'cotisation_cimr',
            'taux_mutuelle',
            'cotisation_mutuelle',
            'professional_tax_rate',
            'irData',
            'jours_calcules',            // ← total jours calculés
        ];
 
        // ── Colonnes existantes dans la table ─────────────────────────────
        $existingColumns = Schema::getColumnListing('paiement_salaires');
        $selectColumns   = array_intersect($columns, $existingColumns);
 
        if (empty($selectColumns)) {
            Log::error('No valid columns found in paiement_salaires table');
            return response()->json(['error' => 'Aucune colonne valide trouvée dans la table paiement_salaires.'], 500);
        }
 
        // ── Récupérer les paiements de l'année ────────────────────────────
        $paymentsData = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)
            ->where('annee', $year)
            ->select($selectColumns)
            ->get();
 
        if ($paymentsData->isEmpty()) {
            Log::warning('No payments found for the year', [
                'id_salarie' => $idSalarie,
                'year'       => $year
            ]);
            return response()->json([
                'error' => "Aucun paiement trouvé pour l'année {$year} pour le salarié ID {$idSalarie}."
            ], 404);
        }
 
        // ── Colonnes agrégées par TAUX (moyenne) ──────────────────────────
        $rateColumns = [
            'cnss_rate', 'amo_rate', 'taux_cimr', 'taux_mutuelle',
            'professional_tax_rate', 'irData', 'tauxHeureSupp',
            'taux_prime_journaliere', 'daily_salary',
        ];
 
        // ── Agrégation annuelle ───────────────────────────────────────────
        $annualData = [];
        foreach ($columns as $column) {
            if (in_array($column, $selectColumns)) {
                if (in_array($column, $rateColumns)) {
                    $annualData['total_' . $column] = $paymentsData->avg($column);
                } else {
                    $annualData['total_' . $column] = $paymentsData->sum($column);
                }
            } else {
                $annualData['total_' . $column] = 0;
            }
        }
 
        // autresPrimesImposablesCalculated = autresPrimesImposables
        $annualData['total_autresPrimesImposablesCalculated'] = $annualData['total_autresPrimesImposables'];
 
        // ── Données du salarié ────────────────────────────────────────────
        $employeeData = Salarie::where('id', $idSalarie)
            ->select(
                'nom', 'prenom', 'cin', 'date_naissance', 'date_embauche',
                'situation_familiale', 'nombre_enfant', 'adresse',
                'n_matricule_cnss', 'n_matricule_entreprise',
                'anciennete', 'reglement_id', 'fonction_id'
            )
            ->first();
 
        if (!$employeeData) {
            Log::error('Employee not found', ['id_salarie' => $idSalarie]);
            return response()->json(['error' => "Salarié ID {$idSalarie} non trouvé."], 404);
        }
 
        // Fonction
        $fonctionName = 'N/A';
        if ($employeeData->fonction_id) {
            $fonction     = DB::table('fonctions')->where('id', $employeeData->fonction_id)->value('designation');
            $fonctionName = $fonction ?? 'N/A';
        }
 
        // Mode de règlement
        $paymentMethod = 'ESPÈCE';
        if ($employeeData->reglement_id) {
            $reglement     = DB::table('type_reglement')->where('id', $employeeData->reglement_id)->value('designation');
            $paymentMethod = $reglement ?? 'ESPÈCE';
        }
 
        // Paramètres entreprise
        $companySettings = CompanySettings::first();
        if (!$companySettings) {
            Log::error('No company settings found', ['id_salarie' => $idSalarie]);
            return response()->json(['error' => "Aucune configuration d'entreprise trouvée."], 500);
        }
        $companySettings = $companySettings->toArray();
 
        // Logo
        $defaultLogoPath = 'assets/img/favicon/anassi2.jpg';
        $customLogoPath  = !empty($companySettings['logo']) ? 'storage/' . $companySettings['logo'] : null;
 
        if ($customLogoPath && file_exists(public_path($customLogoPath))) {
            $companySettings['logo_path'] = public_path($customLogoPath);
        } elseif (file_exists(public_path($defaultLogoPath))) {
            $companySettings['logo_path'] = public_path($defaultLogoPath);
        } else {
            $companySettings['logo_path'] = null;
        }
 
        // ── Données employé pour la vue ───────────────────────────────────
        $employee = [
            'name'           => $employeeData->nom    ?? 'Inconnu',
            'first_name'     => $employeeData->prenom ?? 'Inconnu',
            'cin'            => $employeeData->cin    ?? 'N/A',
            'birth_date'     => $employeeData->date_naissance
                                    ? Carbon::parse($employeeData->date_naissance)->format('d/m/Y')
                                    : 'N/A',
            'hire_date'      => $employeeData->date_embauche
                                    ? Carbon::parse($employeeData->date_embauche)->format('d/m/Y')
                                    : 'N/A',
            'cnss_number'    => $employeeData->n_matricule_cnss       ?? 'N/A',
            'ese_number'     => $employeeData->n_matricule_entreprise ?? 'N/A',
            'marital_status' => $employeeData->situation_familiale    ?? 'N/A',
            'children_count' => $employeeData->nombre_enfant ?? 0,
            'address'        => $employeeData->adresse       ?? 'N/A',
            'payment_method' => $paymentMethod,
            'seniority'      => !is_null($employeeData->anciennete)
                                    ? floor($employeeData->anciennete) . ' an(s)'
                                    : 'Entre 0 à 2 ans',
            'fonction'       => $fonctionName,
        ];
 
        $period = ['year' => $year];
 
        // ── Données paie agrégées pour la vue ─────────────────────────────
        $payroll = [
            'days_worked'                       => $annualData['total_num_jours']                    ?? 0,
            'daily_salary'                      => $annualData['total_daily_salary']                 ?? 0,
            'salaired'                          => $annualData['total_salaired']                     ?? 0,
            'prime_anciennete'                  => $annualData['total_prime_anciennete']             ?? 0,
            'gross_salary'                      => $annualData['total_salaireBI']                    ?? 0,
            'cnss_deduction'                    => $annualData['total_cotisation_cnss']              ?? 0,
            'amo_deduction'                     => $annualData['total_cotisation_amo']               ?? 0,
            'employment_loss_deduction'         => $annualData['total_indemnite_pe']                 ?? 0,
            'employment_loss_rate'              => '0,58 %',   // taux fixe IPE
            'professional_tax_deduction'        => $annualData['total_professional_tax_deduction']  ?? 0,
            'taxable_salary'                    => $annualData['total_salaireNI']                    ?? 0,
            'gross_ir'                          => $annualData['total_ir_brut']                      ?? 0,
            'charge_familiale'                  => $annualData['total_charge_de_famille']            ?? 0,
            'net_ir'                            => $annualData['total_irNet']                        ?? 0,
            'net_payer_sans_extras'             => $annualData['total_net_payer_sans_extras']        ?? 0,
            'avancements_sal'                   => $annualData['total_avancements_sal']              ?? 0,
            'prime_panier'                      => $annualData['total_prime_panier']                 ?? 0,
            'ind_trans_urbain'                  => $annualData['total_ind_trans_urbain']             ?? 0,
            'net_to_pay'                        => $annualData['total_salaire']                      ?? 0,
            // Jours fériés
            'joursFeriesCount'                  => $annualData['total_joursFeriesCount']             ?? 0,
            'joursFeriesTravaillesCount'         => $annualData['total_joursFeriesTravaillesCount']  ?? 0,
            'jour_ferie'                        => $annualData['total_jour_ferie']                   ?? 0,
            'jours_feries_travailles'           => $annualData['total_jours_feries_travailles']      ?? 0,
            // Congés — ancienne logique (somme)
            'conges_paye_count'                 => $annualData['total_conges_paye_count']            ?? 0,
            'conges_paye'                       => $annualData['total_conges_paye']                  ?? 0,
            // Congés — nouvelle logique (séparation travaillés / non travaillés)
            'conges_paye_non_travailles'        => $annualData['total_conges_paye_non_travailles']        ?? 0,
            'conges_paye_non_travailles_count'  => $annualData['total_conges_paye_non_travailles_count']  ?? 0,
            'joursCongesAvecPresence'           => $annualData['total_joursCongesAvecPresence']           ?? 0,
            'congesPayeTravailles'              => $annualData['total_congesPayeTravailles']               ?? 0,
            // Jours calculés
            'jours_calcules'                    => $annualData['total_jours_calcules']               ?? 0,
            // Heures sup
            'heurSuppPresencematin'             => $annualData['total_heurSuppPresencematin']        ?? 0,
            'heurSupp25'                        => $annualData['total_heurSupp25']                   ?? 0,
            'heurSuppPresencematinF'            => $annualData['total_heurSuppPresencematinF']       ?? 0,
            'heurSuppPresenceNuit'              => $annualData['total_heurSuppPresenceNuit']         ?? 0,
            'heurSupp50'                        => $annualData['total_heurSupp50']                   ?? 0,
            'heurSuppPresenceNuitF'             => $annualData['total_heurSuppPresenceNuitF']        ?? 0,
            'heurSupp100'                       => $annualData['total_heurSupp100']                  ?? 0,
            'tauxHeureSupp'                     => $annualData['total_tauxHeureSupp']                ?? 0,
            // Primes
            'base_prime_journaliere'            => $annualData['total_base_prime_journaliere']       ?? 0,
            'taux_prime_journaliere'            => $annualData['total_taux_prime_journaliere']       ?? 0,
            'prime_journaliere'                 => $annualData['total_prime_journaliere']            ?? 0,
            'autresPrimesImposables'            => $annualData['total_autresPrimesImposables']       ?? 0,
            'autresPrimesImposablesCalculated'  => $annualData['total_autresPrimesImposablesCalculated'] ?? 0,
            'primPanierInput'                   => $annualData['total_primPanierInput']              ?? 0,
            'representation_bonus_input'        => $annualData['total_representation_bonus_input']  ?? 0,
            'representation_bonus'              => $annualData['total_representation_bonus']         ?? 0,
            'PrimeDeplacementInput'             => $annualData['total_PrimeDeplacementInput']        ?? 0,
            'PrimeDeplacement'                  => $annualData['total_PrimeDeplacement']             ?? 0,
            'ind_transUrbainInput'              => $annualData['total_ind_transUrbainInput']         ?? 0,
            'primesDiversInput'                 => $annualData['total_primesDiversInput']            ?? 0,
            'primesDivers'                      => $annualData['total_primesDivers']                 ?? 0,
            'salaireBG'                         => $annualData['total_salaireBG']                    ?? 0,
            'primNonimposables'                 => $annualData['total_primNonimposables']            ?? 0,
            // Taux
            'cnss_rate'                         => $annualData['total_cnss_rate']                    ?? 0,
            'amo_rate'                          => $annualData['total_amo_rate']                     ?? 0,
            'taux_cimr'                         => $annualData['total_taux_cimr']                    ?? 0,
            'cotisation_cimr'                   => $annualData['total_cotisation_cimr']              ?? 0,
            'taux_mutuelle'                     => $annualData['total_taux_mutuelle']                ?? 0,
            'cotisation_mutuelle'               => $annualData['total_cotisation_mutuelle']          ?? 0,
            'professional_tax_rate'             => $annualData['total_professional_tax_rate']        ?? 0,
            'irData'                            => $annualData['total_irData']                       ?? 0,
            'base_salary'                       => $annualData['total_salaired']                     ?? 0,
        ];
 
        // ── Générer le PDF ────────────────────────────────────────────────
        $pdf = Pdf::loadView(
            'bultin-paie.bultin_paie_annual_pdf',
            compact('employee', 'period', 'payroll', 'companySettings')
        );
 
        // Dossier de sauvegarde
        $folderName = Str::slug($employeeData->nom . '_' . $employeeData->n_matricule_entreprise, '_');
        $directory  = public_path("assets/storage/salaries/{$folderName}/salaires_annuels");
 
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \Exception("Échec de la création du dossier : {$directory}");
            }
        }
        if (!is_writable($directory)) {
            throw new \Exception("Le dossier n'est pas accessible en écriture : {$directory}");
        }
 
        $filename = "bulletin_paie_annuel_{$employeeData->n_matricule_entreprise}_{$year}.pdf";
        $path     = "assets/storage/salaries/{$folderName}/salaires_annuels/{$filename}";
        $fullPath = public_path($path);
 
        $pdf->save($fullPath);
 
        if (!file_exists($fullPath)) {
            throw new \Exception("Échec de l'enregistrement du PDF à {$fullPath}");
        }
 
        Log::info('Annual PDF generated', [
            'path'       => $path,
            'id_salarie' => $idSalarie,
            'year'       => $year,
            'size'       => filesize($fullPath),
        ]);
 
        return response()->json([
            'success' => 'Bulletin de paie annuel généré et enregistré avec succès.',
            'path'    => $path,
        ]);
 
    } catch (\Exception $e) {
        Log::error('Error in generateAnnualPaySlip', [
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
            'id_salarie' => $idSalarie,
            'year'       => $year,
        ]);
        return response()->json(['error' => "Erreur : {$e->getMessage()}"], 500);
    }
}


        //télechargement de bulletin de paie 
public function downloadPaySlip(Request $request)
{
    try {
        $idSalarie = $request->input('id_salarie');
        $year = $request->input('year');
        $month = trim(strtolower($request->input('month')));

        Log::info('downloadPaySlip called', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month
        ]);

        // Validation des entrées
        if (!$idSalarie || !$year || !$month) {
            Log::error('Missing required parameters', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month
            ]);
            return response()->json(['error' => 'ID salarié, année ou mois manquant.'], 400);
        }

        // Valider le mois
        $monthMap = [
            'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
        ];

        if (!isset($monthMap[$month])) {
            Log::error('Invalid month', ['month' => $month]);
            return response()->json(['error' => 'Mois invalide.'], 400);
        }
        $monthNumber = $monthMap[$month];

        // Vérifier si le paiement existe
        $payment = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)
            ->where('annee', $year)
            ->where('mois', $monthNumber)
            ->first();

        if (!$payment) {
            Log::error('No payment found', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month,
                'month_number' => $monthNumber
            ]);
            return response()->json(['error' => 'Aucun paiement trouvé pour ce mois.'], 404);
        }

        // Vérifier si le bulletin de paie existe dans pieces_joint
        $pieceJointe = DB::table('pieces_joint')
            ->where('id_paiment', $payment->id)
            ->where('id_salarier', $idSalarie)
            ->where('mois', $month)
            ->where('annee', $year)
            ->first();

        if (!$pieceJointe || empty($pieceJointe->bultin_paie)) {
            Log::error('No pay slip found in pieces_joint', [
                'id_salarie' => $idSalarie,
                'id_paiment' => $payment->id,
                'month' => $month,
                'year' => $year,
                'piece_jointe' => $pieceJointe
            ]);
            return response()->json(['error' => 'Aucun bulletin de paie trouvé.'], 404);
        }

        $filePath = public_path($pieceJointe->bultin_paie);

        if (!file_exists($filePath)) {
            Log::error('Pay slip file does not exist', [
                'id_salarie' => $idSalarie,
                'file_path' => $filePath,
                'bultin_paie' => $pieceJointe->bultin_paie
            ]);
            return response()->json(['error' => 'Le fichier du bulletin de paie n\'existe pas.'], 404);
        }

        $fileName = "bulletin_paie_{$idSalarie}_{$month}_{$year}.pdf";
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\""
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur lors du téléchargement du bulletin de paie', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Erreur lors du téléchargement du bulletin de paie : ' . $e->getMessage()], 500);
    }
}


public function uploadPaySlip(Request $request)
{
    $request->validate([
        'file'       => 'required|file|mimes:pdf|max:10240',
        'id_salarie' => 'required|integer',
        'year'       => 'required|integer',
        'month'      => 'required|string',
    ]);

    $idSalarie = $request->input('id_salarie');
    $year      = $request->input('year');
    $month     = strtolower($request->input('month'));

    $monthMap = [
        'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
        'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
        'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
    ];
    $monthNumber = $monthMap[$month] ?? null;

    if (!$monthNumber) {
        return response()->json(['error' => 'Mois invalide.'], 400);
    }

    // Récupérer l'id du paiement
    $payment = DB::table('paiement_salaires')
        ->where('id_salarie', $idSalarie)
        ->where('annee', $year)
        ->where('mois', $monthNumber)
        ->first();

    if (!$payment) {
        return response()->json(['error' => 'Aucun paiement trouvé pour ce mois.'], 404);
    }

    // Récupérer les données de l'employé pour construire le dossier (comme uploadQuittance)
    $employeeData = Salarie::where('id', $idSalarie)
        ->select('nom', 'n_matricule_entreprise')
        ->first();

    if (!$employeeData) {
        Log::error('Employee not found in uploadPaySlip', ['id_salarie' => $idSalarie]);
        return response()->json(['error' => "Salarié ID {$idSalarie} non trouvé."], 404);
    }

    // Définir le chemin de stockage (même logique que uploadQuittance / generatePaySlipPDF)
    $folderName = Str::slug($employeeData->nom . '_' . $employeeData->n_matricule_entreprise, '_');
    $directory = public_path("assets/storage/salaries/{$folderName}/salaires");

    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
        Log::info('Directory created', ['directory' => $directory]);
    }

    if (!is_writable($directory)) {
        Log::error('Directory not writable', ['directory' => $directory]);
        return response()->json(['error' => "Le dossier n'est pas accessible en écriture : {$directory}"], 500);
    }

    // Générer le nom du fichier (convention bulletin_paie_cah_{id}_{month}_{year}.pdf)
    $file = $request->file('file');
    $filename = "bulletin_paie_cah_{$idSalarie}_{$month}_{$year}.pdf";
    $filePath = "assets/storage/salaries/{$folderName}/salaires/{$filename}";

    // Déplacer le fichier physiquement (et non storeAs)
    $file->move($directory, $filename);

    if (!file_exists(public_path($filePath))) {
        Log::error('File not found after move', ['file_path' => public_path($filePath)]);
        return response()->json(['error' => 'Le fichier n\'a pas été enregistré sur le disque.'], 500);
    }

    Log::info('Pay slip cachet uploaded successfully', [
        'id_salarie' => $idSalarie,
        'year' => $year,
        'month' => $month,
        'file_path' => $filePath
    ]);

    // Enregistrer / mettre à jour dans pieces_joint avec le champ bultin_paie_ca
    DB::table('pieces_joint')->updateOrInsert(
        [
            'id_paiment'  => $payment->id,
            'id_salarier' => $idSalarie,
            'mois'        => $month,
            'annee'       => $year,
        ],
        [
            'bultin_paie_ca' => $filePath,
            'updated_at'     => now(),
            'created_at'     => now(),
        ]
    );

    // Optionnel : mettre à jour statutspj si vous l'utilisez
    DB::table('paiement_salaires')
        ->where('id', $payment->id)
        ->update(['statutspj' => 1, 'updated_at' => now()]);

    return response()->json([
        'success' => true,
        'message' => 'Bulletin de paie téléversé avec succès.',
        'path'    => $filePath
    ]);
}


//fonction paiement salaire 

public function addSalaryPayment(Request $request)
{
    try {
        $idSalaries = $request->input('id_salaries', []);
        $year = $request->input('year');
        $month = trim(strtolower($request->input('month')));
        $paymentMethod = trim(strtolower($request->input('payment_method', 'espece')));
        $numCheque = $request->input('num_cheque');

        // Log raw input for debugging
        Log::info('Payment method input', [
            'raw_payment_method' => $request->input('payment_method'),
            'normalized_payment_method' => $paymentMethod,
            'id_salaries' => $idSalaries,
            'year' => $year,
            'month' => $month,
            'num_cheque' => $numCheque
        ]);

        // Vérifier que le mois correspond au mois attendu
        $currentMonth = session('current_month', '');
        if ($currentMonth && $month !== $currentMonth) {
            Log::error('Month mismatch', ['requested_month' => $month, 'current_month' => $currentMonth]);
            return response()->json(['error' => 'Le mois fourni ne correspond pas au mois sélectionné.'], 400);
        }

        // Validation des entrées
        if (empty($idSalaries)) {
            Log::error('No employee IDs provided');
            return response()->json(['error' => 'Aucun ID de salarié fourni.'], 400);
        }

        if (count($idSalaries) > 1) {
            Log::error('Multiple employee IDs provided', ['id_salaries' => $idSalaries]);
            return response()->json(['error' => 'Seul un salarié peut être traité à la fois.'], 400);
        }

        if (empty($year) || !is_numeric($year)) {
            Log::error('Invalid or missing year', ['year' => $year]);
            return response()->json(['error' => 'Année non valide ou non fournie.'], 400);
        }

        // Valider le mois contre la liste des mois valides
        $monthMap = [
            'janvier' => '01', 'fevrier' => '02', 'mars' => '03', 'avril' => '04',
            'mai' => '05', 'juin' => '06', 'juillet' => '07', 'aout' => '08',
            'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'decembre' => '12'
        ];
        if (!array_key_exists($month, $monthMap)) {
            Log::error('Invalid month provided', ['month' => $month]);
            return response()->json(['error' => 'Mois non valide. Veuillez sélectionner un mois valide.'], 400);
        }
        $monthNumber = $monthMap[$month];

        // Valider le mode de paiement
        $validMethods = ['espece', 'cheque', 'virement'];
        if (!in_array($paymentMethod, $validMethods)) {
            Log::error('Invalid payment method', ['payment_method' => $paymentMethod]);
            return response()->json(['error' => 'Mode de paiement non valide pour le paiement individuel.'], 400);
        }

        // Valider num_cheque pour cheque
        if ($paymentMethod === 'cheque' && empty($numCheque)) {
            Log::error('Missing cheque number', ['payment_method' => $paymentMethod]);
            return response()->json(['error' => 'Numéro de chèque requis.'], 400);
        }

        // Map paymentMethod to typer
        $typerValue = match ($paymentMethod) {
            'espece' => 'e',
            'virement' => 'v',
            'cheque' => 'c',
            default => throw new \Exception('Mode de paiement non pris en charge : ' . $paymentMethod)
        };

        // Define payment method text for PDF
        $paymentMethodText = $paymentMethod === 'cheque' ? "Chèque N° $numCheque" : ucfirst($paymentMethod);

        $idSalarie = reset($idSalaries);
        Log::info('Processing employee', ['id_salarie' => $idSalarie]);

        // Vérifier que le salaire est calculé (statutspj = 1)
        $paymentExists = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)
            ->where('mois', $monthNumber)
            ->where('annee', $year)
            ->where('statutspj', 1)
            ->exists();

        if (!$paymentExists) {
            Log::error('Employee salary not calculated', [
                'id_salarie' => $idSalarie,
                'month' => $month,
                'year' => $year
            ]);
            return response()->json([
                'error' => 'Vous devez calculer le paiement avant de le payer.'
            ], 400);
        }

        // Fetch employee data
        $salaryData = DB::table('salaries')->where('id', $idSalarie)->first();
        if (!$salaryData) {
            Log::error('Employee not found', ['id_salarie' => $idSalarie]);
            return response()->json(['error' => "Salarié ID {$idSalarie} non trouvé."], 404);
        }

        // Fetch additional fields from Salarie model
        $employeeData = Salarie::where('id', $idSalarie)->select('n_matricule_entreprise', 'nom', 'prenom')->first();
        $salaryData->n_matricule_entreprise = $employeeData->n_matricule_entreprise ?? 'unknown';
        $salaryData->nom = $employeeData->nom ?? $salaryData->nom ?? 'Inconnu';
        $salaryData->prenom = $employeeData->prenom ?? $salaryData->prenom ?? 'Inconnu';

        // Fetch existing payment data
        $paymentData = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)
            ->where('mois', $monthNumber)
            ->where('annee', $year)
            ->first();

        // Générer receipt_number
        $lastPayment = DB::table('paiement_salaires')
            ->where('annee', $year)
            ->whereNotNull('receipt_number')
            ->orderBy('id', 'desc')
            ->first();
        $counter = $lastPayment && $lastPayment->receipt_number ? (int) explode('/', $lastPayment->receipt_number)[0] + 1 : 1;
        $receiptNumber = sprintf('%03d/%d', $counter, $year);

        if ($paymentData) {
            Log::info('Existing payment found, updating', [
                'id_salarie' => $idSalarie,
                'payment_id' => $paymentData->id,
                'typer' => $typerValue,
                'receipt_number' => $receiptNumber
            ]);
            DB::table('paiement_salaires')
                ->where('id', $paymentData->id)
                ->update([
                    'typer' => $typerValue,
                    'num_cheque' => $paymentMethod === 'cheque' ? $numCheque : null,
                    'receipt_number' => $receiptNumber,
                    'updated_at' => now()
                ]);
        } else {
            Log::error('Unexpected: No payment record found after validation', [
                'id_salarie' => $idSalarie,
                'month' => $monthNumber,
                'year' => $year
            ]);
            return response()->json(['error' => "Erreur inattendue : Aucun enregistrement de paiement trouvé pour $month $year."], 500);
        }

        // Generate and save PDF
        $pdfPath = $this->generateQuittancePdf($salaryData, $paymentData->salaire, $month, $year, $paymentMethodText, $receiptNumber);
        Log::info('PDF generated', ['pdf_path' => $pdfPath]);

        // Verify PDF exists
        $fullPath = public_path($pdfPath);
        if (!file_exists($fullPath)) {
            Log::error('PDF file not found', ['pdf_path' => $pdfPath]);
            return response()->json(['error' => "Le fichier PDF n'a pas été généré correctement."], 500);
        }

        // Update pieces_joint
        $existingPiece = DB::table('pieces_joint')
            ->where('id_paiment', $paymentData->id)
            ->where('id_salarier', $idSalarie)
            ->where('mois', $month)
            ->where('annee', $year)
            ->first();

        if ($existingPiece) {
            DB::table('pieces_joint')
                ->where('id', $existingPiece->id)
                ->update([
                    'pj' => $pdfPath,
                    'updated_at' => now()
                ]);
            Log::info('pieces_joint updated', [
                'piece_joint_id' => $existingPiece->id,
                'id_salarie' => $idSalarie,
                'id_paiment' => $paymentData->id,
                'mois' => $month,
                'annee' => $year,
                'pj_path' => $pdfPath
            ]);
        } else {
            DB::table('pieces_joint')->insert([
                'id_paiment' => $paymentData->id,
                'id_salarier' => $idSalarie,
                'mois' => $month,
                'annee' => $year,
                'pj' => $pdfPath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('pieces_joint created', [
                'id_salarie' => $idSalarie,
                'id_paiment' => $paymentData->id,
                'mois' => $month,
                'annee' => $year,
                'pj_path' => $pdfPath
            ]);
        }


     // ✅ Si paiement par virement individuel → ajouter au BulkPayrollPdf
        if ($typerValue === 'v') {
            Log::info('Virement individuel détecté, ajout au bulk payroll PDF', [
                'id_salarie' => $idSalarie,
                'month'      => $month,
                'year'       => $year,
            ]);

            // Construire une fausse requête avec les données nécessaires
            $bulkRequest = new Request([
                'id_salaries'    => [$idSalarie],
                'year'           => $year,
                'month'          => $month,
                'payment_method' => 'virement-g',
            ]);

            // Appeler addBulkSalaryPayment pour inclure cet employé dans le PDF groupé
            $bulkResponse = $this->addBulkSalaryPayment($bulkRequest);
            $bulkData = json_decode($bulkResponse->getContent(), true);

            if (isset($bulkData['error'])) {
                Log::warning('Échec de l\'ajout au bulk payroll PDF', [
                    'id_salarie' => $idSalarie,
                    'error'      => $bulkData['error'],
                ]);
            } else {
                Log::info('Employé ajouté au bulk payroll PDF avec succès', [
                    'id_salarie'   => $idSalarie,
                    'bulk_pdf_path' => $bulkData['bulk_pdf_path'] ?? 'N/A',
                ]);
            }
        }
            // Trigger PDF download
            $filename = basename($pdfPath);
            return response()->download($fullPath, $filename);

        } catch (\Exception $e) {
            Log::error('Error in addSalaryPayment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => "Erreur : {$e->getMessage()}"], 500);
        }
}


// fonction de generation des quittance 

public function generateQuittancePdf($salaryData, $salaire, $month, $year, $paymentMethodText, $receiptNumber)
{
    try {
        // Récupérer les paramètres de l'entreprise
        $companySettings = CompanySettings::first();
        Log::debug('Company Settings Raw', ['companySettings' => $companySettings ? $companySettings->toArray() : null]);

        if (!$companySettings) {
            Log::error('No company settings found');
            throw new \Exception('Aucune configuration d\'entreprise trouvée.');
        }

        $companySettings = $companySettings->toArray();

        // Vérifier que nom_etreprise existe et n'est pas vide
        if (!isset($companySettings['nom_etreprise']) || trim($companySettings['nom_etreprise']) === '') {
            Log::warning('nom_etreprise is missing or empty', ['companySettings' => $companySettings]);
            $companySettings['nom_etreprise'] = 'Non défini';
        } else {
            Log::info('nom_etreprise found', ['nom_etreprise' => $companySettings['nom_etreprise']]);
        }

        // Vérifier le champ logo
        $defaultLogoPath = 'assets/logo/logo2.png';
        $customLogoPath = !empty($companySettings['logo']) ? 'storage/' . $companySettings['logo'] : null;

        if ($customLogoPath && file_exists(public_path($customLogoPath))) {
            Log::info('Custom logo found and exists', ['logo_path' => $customLogoPath, 'full_path' => public_path($customLogoPath)]);
            $companySettings['logo_path'] = public_path($customLogoPath);
        } else {
            Log::warning('Custom logo is empty or file does not exist', [
                'logo' => $companySettings['logo'] ?? 'Not set',
                'custom_logo_path' => $customLogoPath,
                'file_exists' => $customLogoPath ? file_exists(public_path($customLogoPath)) : false
            ]);
            if (file_exists(public_path($defaultLogoPath))) {
                Log::info('Default logo exists', ['logo_path' => $defaultLogoPath, 'full_path' => public_path($defaultLogoPath)]);
                $companySettings['logo_path'] = public_path($defaultLogoPath);
            } else {
                Log::error('Default logo does not exist', ['logo_path' => $defaultLogoPath, 'full_path' => public_path($defaultLogoPath)]);
                $companySettings['logo_path'] = null;
            }
        }

        // Define folderName
        $folderName = Str::slug(($salaryData->nom ?? 'unknown') . '_' . ($salaryData->n_matricule_entreprise ?? 'unknown'), '_');

        // Fetch the designation from the fonctions table
        $functionDesignation = DB::table('salaries')
            ->leftJoin('fonctions', 'salaries.fonction_id', '=', 'fonctions.id')
            ->where('salaries.id', $salaryData->id)
            ->value('fonctions.designation') ?? 'Non spécifié';

        Log::debug('Function Designation Data', [
            'id_salarie' => $salaryData->id,
            'fonction_id' => $salaryData->fonction_id ?? 'N/A',
            'designation' => $functionDesignation
        ]);

        // Fetch the username of the currently logged-in user
        $username = auth()->check() ? auth()->user()->username : 'Non spécifié';

        Log::debug('Authenticated User Data', [
            'is_authenticated' => auth()->check(),
            'username' => $username
        ]);

        // Prepare data for the PDF
        $receipts = [
            [
                'net_amount' => $salaire ?? 0,
                'employee_name' => ($salaryData->nom ?? 'Inconnu') . ' ' . ($salaryData->prenom ?? 'Inconnu'),
                'function' => $functionDesignation,
                'cin' => $salaryData->cin ?? 'Non spécifié',
                'cnss' => $salaryData->n_matricule_cnss ?? 'Non spécifié',
                'work_days' => $salaryData->jours_travail ?? 26,
                'payment_date' => now()->format('d/m/Y'),
                'month' => $month,
                'year' => $year,
                'payment_method' => $paymentMethodText,
                'receipt_number' => $receiptNumber,
                'username' => $username
            ]
        ];

        // Load the PDF view
        $pdf = Pdf::loadView('bultin-paie.quittance_pdf', [
            'receipts' => $receipts,
            'numberToFrenchWords' => fn($amount) => $this->numberToFrenchWords($amount),
            'companySettings' => $companySettings
        ]);

        // Create the directory
        $directory = public_path("assets/storage/salaries/{$folderName}/salaires");

        if (!file_exists($directory)) {
            Log::info('Creating directory', ['directory' => $directory]);
            if (!mkdir($directory, 0755, true)) {
                Log::error('Failed to create directory', ['directory' => $directory]);
                throw new \Exception("Échec de la création du dossier : {$directory}");
            }
        }

        if (!is_writable($directory)) {
            Log::error('Directory not writable', ['directory' => $directory]);
            throw new \Exception("Le dossier n'est pas accessible en écriture : {$directory}");
        }

        // Generate filename
        $filename = "quittance_" . ($salaryData->n_matricule_entreprise ?? 'unknown') . "_" . strtolower($month) . "_{$year}.pdf";
        $path = "assets/storage/salaries/{$folderName}/salaires/{$filename}";
        $fullPath = public_path($path);

        Log::info('Attempting to save PDF', [
            'path' => $path,
            'full_path' => $fullPath,
            'directory_exists' => file_exists($directory),
            'directory_writable' => is_writable($directory)
        ]);

        // Save the PDF
        $pdf->save($fullPath);

        if (!file_exists($fullPath)) {
            Log::error('PDF file not saved', ['full_path' => $fullPath]);
            throw new \Exception("Échec de l'enregistrement du PDF à {$fullPath}");
        }

        Log::info('PDF generated and saved', [
            'path' => $path,
            'full_path' => $fullPath,
            'size' => filesize($fullPath)
        ]);

        return $path;
    } catch (\Exception $e) {
        Log::error('Erreur lors de la génération du PDF', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'folderName' => $folderName ?? 'undefined'
        ]);
        throw $e;
    }
}

// fonction  de transformation  des mois en nombre au mois en letre 

public function numberToFrenchWords($number)
    {
        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $teens = ['dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];
        $thousands = ['', 'mille', 'million', 'milliard'];

        if ($number == 0) {
            return 'zéro';
        }

        $integerPart = floor($number);
        $decimalPart = round(($number - $integerPart) * 100);

        $convert = function ($num, $level = 0) use ($units, $teens, $tens, $thousands, &$convert) {
            if ($num == 0) {
                return '';
            }

            $result = '';
            if ($num >= 1000000) {
                $millions = floor($num / 1000000);
                $result .= $convert($millions, 2) . ' million' . ($millions > 1 ? 's' : '');
                $num %= 1000000;
                if ($num > 0) $result .= ' ';
            }

            if ($num >= 1000) {
                $thousandsPart = floor($num / 1000);
                $result .= ($thousandsPart == 1 ? '' : $convert($thousandsPart)) . ' mille';
                $num %= 1000;
                if ($num > 0) $result .= ' ';
            }

            if ($num >= 100) {
                $hundreds = floor($num / 100);
                $result .= ($hundreds == 1 ? 'cent' : $units[$hundreds] . ' cent');
                $num %= 100;
                if ($num > 0) $result .= ' ';
            }

            if ($num >= 20) {
                $tensPart = floor($num / 10);
                $result .= $tens[$tensPart];
                $num %= 10;
                if ($num > 0) $result .= ($tensPart == 7 || $tensPart == 9 ? '-' : ' ') . $units[$num];
            } elseif ($num >= 10) {
                $result .= $teens[$num - 10];
            } elseif ($num > 0) {
                $result .= $units[$num];
            }

            return $result;
        };

        $result = $convert($integerPart);
        if ($decimalPart > 0) {
            $result .= ' dirham' . ($integerPart > 1 ? 's' : '') . ' et ' . $convert($decimalPart) . ' centime' . ($decimalPart > 1 ? 's' : '');
        } else {
            $result .= ' dirham' . ($integerPart > 1 ? 's' : '');
        }

        return ucfirst(trim($result));
    }



// telechargement des quittances 

public function downloadQuittance(Request $request)
{
    try {
        $idSalarie = $request->input('id_salarie');
        $year = $request->input('year');
        $month = trim(strtolower($request->input('month')));

        // Vérifier que le mois correspond au mois sélectionné (par exemple, via la session)
        $currentMonth = session('current_month', ''); // Supposons que le mois sélectionné est stocké dans la session
        if ($currentMonth && $month !== $currentMonth) {
            Log::error('Month mismatch', ['requested_month' => $month, 'current_month' => $currentMonth]);
            return response()->json(['error' => 'Le mois fourni ne correspond pas au mois sélectionné.'], 400);
        }

        Log::info('downloadQuittance called', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month
        ]);

        // Validation des entrées
        if (!$idSalarie || !$year || !$month) {
            Log::error('Missing required parameters', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month
            ]);
            return response()->json(['error' => 'ID salarié, année ou mois manquant.'], 400);
        }

        // Valider le mois
        $monthMap = [
            'janvier' => '01', 'fevrier' => '02', 'mars' => '03', 'avril' => '04',
            'mai' => '05', 'juin' => '06', 'juillet' => '07', 'aout' => '08',
            'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'decembre' => '12'
        ];
        
        if (!array_key_exists($month, $monthMap)) {
            Log::error('Invalid month', ['month' => $month]);
            return response()->json(['error' => "Mois invalide : {$month}."], 400);
        }
        $monthNumber = $monthMap[$month];

        // Vérifier l'enregistrement de paiement
        $payment = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)
            ->where('annee', $year)
            ->where('mois', $monthNumber)
            ->first();

        if (!$payment) {
            Log::error('No payment found', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month,
                'month_number' => $monthNumber
            ]);
            return response()->json(['error' => 'Aucun paiement trouvé pour ce mois.'], 404);
        }

        // Vérifier l'enregistrement dans pieces_joint avec le mois en français
        $pieceJointe = DB::table('pieces_joint')
            ->where('id_paiment', $payment->id)
            ->where('id_salarier', $idSalarie)
            ->where('mois', $month)
            ->where('annee', $year)
            ->first();

        if (!$pieceJointe || !$pieceJointe->pj) {
            Log::error('No quittance found in pieces_joint', [
                'id_salarie' => $idSalarie,
                'id_paiment' => $payment->id,
                'month' => $month,
                'year' => $year,
                'piece_jointe' => $pieceJointe
            ]);
            return response()->json(['error' => 'Aucune quittance trouvée.'], 404);
        }

        $filePath = public_path($pieceJointe->pj);

        if (!file_exists($filePath)) {
            Log::error('Quittance file does not exist', [
                'id_salarie' => $idSalarie,
                'file_path' => $filePath,
                'pj' => $pieceJointe->pj
            ]);
            return response()->json(['error' => 'Le fichier de quittance n\'existe pas.'], 404);
        }

        // Télécharger directement le fichier
        $fileName = "quittance_{$idSalarie}_{$month}_{$year}.pdf";
        return response()->download($filePath, $fileName);

    } catch (\Exception $e) {
        Log::error('Erreur lors du téléchargement de la quittance', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Erreur lors du téléchargement de la quittance : ' . $e->getMessage()], 500);
    }
}


// prcourire quittance 


public function uploadQuittance(Request $request)
{
    $idSalarie = $request->input('id_salarie');
    $year = $request->input('year');
    $month = trim(strtolower($request->input('month')));
    $file = $request->file('quittance_file');

    Log::info('uploadQuittance called', [
        'id_salarie' => $idSalarie,
        'year' => $year,
        'month' => $month,
        'file_name' => $file ? $file->getClientOriginalName() : null
    ]);


    // Vérifier que le mois correspond au mois sélectionné (par exemple, via la session)
    $currentMonth = session('current_month', ''); // Supposons que le mois sélectionné est stocké dans la session
    if ($currentMonth && $month !== $currentMonth) {
        Log::error('Month mismatch', ['requested_month' => $month, 'current_month' => $currentMonth]);
        return response()->json(['error' => 'Le mois fourni ne correspond pas au mois sélectionné.'], 400);
    }


    // Validation initiale des paramètres
    if (!$idSalarie || !$year || !$month || !$file || !$file->isValid()) {
        Log::error('Missing or invalid parameters', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month,
            'file_valid' => $file ? $file->isValid() : false
        ]);
        return response()->json(['error' => 'ID salarié, année, mois ou fichier manquant/invalide.'], 400);
    }

    try {
        // Validation des entrées
        $validated = $request->validate([
            'id_salarie' => 'required|exists:salaries,id',
            'year' => 'required|integer|min:2020|max:' . date('Y'),
            'month' => 'required|in:janvier,fevrier,mars,avril,mai,juin,juillet,aout,septembre,octobre,novembre,decembre',
            'quittance_file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:5120'
        ], [
            'quittance_file.mimes' => 'Le fichier doit être un PDF, PNG, JPG ou JPEG.',
            'quittance_file.max' => 'Le fichier ne doit pas dépasser 5 Mo.',
            'id_salarie.exists' => 'Le salarié spécifié n\'existe pas.',
            'month.in' => 'Mois invalide.'
        ]);

        Log::info('Données validées pour uploadQuittance', $validated);

        // Mapper le mois en numéro pour la table paiement_salaires
        $monthMap = [
            'janvier' => '01', 'fevrier' => '02', 'mars' => '03', 'avril' => '04',
            'mai' => '05', 'juin' => '06', 'juillet' => '07', 'aout' => '08',
            'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'decembre' => '12'
        ];
        $monthNumber = $monthMap[$month];

        // Vérifier si le paiement existe
        $payment = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)
            ->where('annee', $year)
            ->where('mois', $monthNumber)
            ->first();

        if (!$payment) {
            Log::error('No payment found', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month,
                'month_number' => $monthNumber
            ]);
            return response()->json(['error' => 'Aucun paiement trouvé pour ce mois.'], 404);
        }

        // Récupérer les données de l'employé
        $employeeData = Salarie::where('id', $idSalarie)
            ->select('nom', 'n_matricule_entreprise')
            ->first();

        if (!$employeeData) {
            Log::error('Employee not found', ['id_salarie' => $idSalarie]);
            return response()->json(['error' => "Salarié ID {$idSalarie} non trouvé."], 404);
        }

        // Définir le chemin de stockage
        $folderName = Str::slug($employeeData->nom . '_' . $employeeData->n_matricule_entreprise, '_');
        $directory = public_path("assets/storage/salaries/{$folderName}/salaires");

        if (!file_exists($directory)) {
            mkdir($directory, 0775, true);
            Log::info('Directory created', ['directory' => $directory]);
        }

        if (!is_writable($directory)) {
            Log::error('Directory not writable', ['directory' => $directory]);
            return response()->json(['error' => "Le dossier n'est pas accessible en écriture : {$directory}"], 500);
        }

        // Générer le nom du fichier
        $extension = $file->getClientOriginalExtension();
        $fileName = "quittance_cah_{$idSalarie}_{$month}_{$year}.{$extension}";
        $filePath = "assets/storage/salaries/{$folderName}/salaires/{$fileName}";

        // Déplacer le fichier
        Log::info('Attempting to move file', ['source' => $file->getPathname(), 'destination' => $directory . '/' . $fileName]);
        $file->move($directory, $fileName);
        Log::info('File moved successfully', ['file_path' => $filePath]);

        if (!file_exists(public_path($filePath))) {
            Log::error('File not found after move', ['file_path' => public_path($filePath)]);
            return response()->json(['error' => 'Le fichier n\'a pas été enregistré sur le disque.'], 500);
        }

        // Vérifier si une entrée existe dans pieces_joint
        $pieceJointe = DB::table('pieces_joint')
            ->where('id_paiment', $payment->id)
            ->where('id_salarier', $idSalarie)
            ->where('mois', $month)
            ->where('annee', $year)
            ->first();

        if ($pieceJointe) {
            // Mettre à jour l'entrée existante
            DB::table('pieces_joint')
                ->where('id', $pieceJointe->id)
                ->update([
                    'quittance_cah' => $filePath,
                    'updated_at' => now()
                ]);
            Log::info('Existing pieces_joint updated with quittance_cah', [
                'piece_joint_id' => $pieceJointe->id,
                'id_salarie' => $idSalarie,
                'id_paiment' => $payment->id,
                'quittance_cah_path' => $filePath
            ]);
        } else {
            // Créer une nouvelle entrée
            DB::table('pieces_joint')->insert([
                'id_paiment' => $payment->id,
                'id_salarier' => $idSalarie,
                'mois' => $month,
                'annee' => $year,
                'quittance_cah' => $filePath,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            Log::info('New pieces_joint created for quittance_cah', [
                'id_salarie' => $idSalarie,
                'id_paiment' => $payment->id,
                'quittance_cah_path' => $filePath
            ]);
        }

        Log::info('Quittance uploaded successfully', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month,
            'file_path' => $filePath
        ]);

        return response()->json([
            'success' => true,
            'message' => "Quittance téléversée avec succès pour {$month} {$year}.",
            'file_name' => $file->getClientOriginalName()
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Erreur de validation dans uploadQuittance', ['errors' => $e->errors()]);
        return response()->json(['error' => 'Erreur de validation : ' . implode(', ', array_merge(...array_values($e->errors())))], 422);
    } catch (\Exception $e) {
        Log::error('Error in uploadQuittance', [
            'error' => $e->getMessage(),
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month,
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Erreur serveur : ' . $e->getMessage()], 500);
    }
}



// paiement par virement en groupe 

public function addBulkSalaryPayment(Request $request)
{
    try {
        $idSalaries = $request->input('id_salaries', []);
        $year = $request->input('year');
        $month = trim(strtolower($request->input('month')));
        $paymentMethod = trim(strtolower($request->input('payment_method', 'virement-g')));

        // Log raw input for debugging
        Log::info('Bulk payment input', [
            'id_salaries' => $idSalaries,
            'year' => $year,
            'month' => $month,
            'payment_method' => $paymentMethod
        ]);

        // Validation des entrées
        if (empty($idSalaries)) {
            Log::error('No employee IDs provided');
            return response()->json(['error' => 'Aucun ID de salarié fourni.'], 400);
        }

        if (empty($year) || !is_numeric($year)) {
            Log::error('Invalid or missing year', ['year' => $year]);
            return response()->json(['error' => 'Année non valide ou non fournie.'], 400);
        }

        // Valider le mois contre la liste des mois valides
        $monthMap = [
            'janvier' => '01', 'fevrier' => '02', 'mars' => '03', 'avril' => '04',
            'mai' => '05', 'juin' => '06', 'juillet' => '07', 'aout' => '08',
            'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'decembre' => '12'
        ];
        if (!array_key_exists($month, $monthMap)) {
            Log::error('Invalid month provided', ['month' => $month]);
            return response()->json(['error' => 'Mois non valide. Veuillez sélectionner un mois valide.'], 400);
        }
        $monthNumber = $monthMap[$month];

        // Valider le mode de paiement
        if ($paymentMethod !== 'virement-g') {
            Log::error('Invalid payment method for bulk', ['payment_method' => $paymentMethod]);
            return response()->json(['error' => 'Seul le mode de paiement "virement-g" est pris en charge pour le paiement groupé.'], 400);
        }

        // Vérifier que chaque employé a un salaire calculé (statutspj = 1)
        foreach ($idSalaries as $idSalarie) {
            $paymentExists = DB::table('paiement_salaires')
                ->where('id_salarie', $idSalarie)
                ->where('mois', $monthNumber)
                ->where('annee', $year)
                ->where('statutspj', 1)
                ->exists();

            if (!$paymentExists) {
                Log::error('Employee salary not calculated', [
                    'id_salarie' => $idSalarie,
                    'month' => $month,
                    'year' => $year
                ]);
                return response()->json([
                    'error' => 'Vous devez calculer le paiement avant de le payer.'
                ], 400);
            }
        }

        $paymentData = [];
        $employeeDataList = [];

        // Récupérer les employés déjà inclus dans le PDF existant
        $existingEmployeeIds = DB::table('pieces_joint')
            ->where('mois', $month)
            ->where('annee', $year)
            ->where('pj', 'like', "assets/storage/salaries/bulk_payrolls/bulk_payroll_{$month}_{$year}.pdf")
            ->pluck('id_salarier')
            ->toArray();

        Log::info('Existing employees in PDF', [
            'month' => $month,
            'year' => $year,
            'existing_employee_ids' => $existingEmployeeIds
        ]);

        // Fusionner les IDs des employés existants et nouveaux, en évitant les doublons
        $allEmployeeIds = array_unique(array_merge($existingEmployeeIds, $idSalaries));

        foreach ($allEmployeeIds as $idSalarie) {
            Log::info('Processing employee for bulk payment', ['id_salarie' => $idSalarie]);

            // Récupérer les données de l'employé depuis la table salaries
            $salaryData = DB::table('salaries')->where('id', $idSalarie)->first();
            if (!$salaryData) {
                Log::error('Employee not found', ['id_salarie' => $idSalarie]);
                continue;
            }

            // Récupérer les champs supplémentaires
            $employeeData = Salarie::where('id', $idSalarie)->select('n_matricule_entreprise', 'nom', 'prenom', 'nombre_enfant')->first();
            $salaryData->n_matricule_entreprise = $employeeData->n_matricule_entreprise ?? 'unknown';
            $salaryData->nom = $employeeData->nom ?? $salaryData->nom ?? 'Inconnu';
            $salaryData->prenom = $employeeData->prenom ?? $salaryData->prenom ?? 'Inconnu';
            $salaryData->nombre_enfant = $employeeData->nombre_enfant ?? '0';

            // Récupérer les données depuis paiement_salaires
            $paymentRecord = DB::table('paiement_salaires')
                ->where('id_salarie', $idSalarie)
                ->where('mois', $monthNumber)
                ->where('annee', $year)
                ->first();

            if (!$paymentRecord) {
                Log::error('Payment record not found', [
                    'id_salarie' => $idSalarie,
                    'month' => $monthNumber,
                    'year' => $year
                ]);
                continue;
            }

            // Ensure numeric values are floats or strings, with defaults for empty or invalid values
            $baseSalary = is_numeric($salaryData->salaire_base) ? (float)$salaryData->salaire_base : 0.0;
            $joursTravail = is_numeric($paymentRecord->num_jours) ? (int)$paymentRecord->num_jours : 26;
            $salaireBrut = is_numeric($paymentRecord->salaireBI) ? (float)$paymentRecord->salaireBI : 0.0;
            $salaireNet = is_numeric($paymentRecord->salaireNI) ? (float)$paymentRecord->salaireNI : 0.0;
            $irNet = is_numeric($paymentRecord->irNet) ? (float)$paymentRecord->irNet : 0.0;
            $cotisationCnss = is_numeric($paymentRecord->cotisation_cnss) ? (float)$paymentRecord->cotisation_cnss : 0.0;
            $joursFeriesCount = property_exists($paymentRecord, 'joursFeriesCount') && is_numeric($paymentRecord->joursFeriesCount) ? (int)$paymentRecord->joursFeriesCount : 0;
            $congesPayeCount = property_exists($paymentRecord, 'conges_paye_count') && is_numeric($paymentRecord->conges_paye_count) ? (int)$paymentRecord->conges_paye_count : 0;
            $joursFeriesTravaillesCount = property_exists($paymentRecord, 'joursFeriesTravaillesCount') && is_numeric($paymentRecord->joursFeriesTravaillesCount) ? (int)$paymentRecord->joursFeriesTravaillesCount : 0;
            $primeDeplacement = property_exists($paymentRecord, 'PrimeDeplacement') && is_numeric($paymentRecord->PrimeDeplacement) ? (float)$paymentRecord->PrimeDeplacement : 0.0;
            $primeRepresentation = property_exists($paymentRecord, 'prime_representation') && is_numeric($paymentRecord->prime_representation) ? (float)$paymentRecord->prime_representation : 0.0;
            $primePanier = property_exists($paymentRecord, 'prime_panier') && is_numeric($paymentRecord->prime_panier) ? (float)$paymentRecord->prime_panier : 0.0;
            $primeTransport = property_exists($paymentRecord, 'ind_trans_urbain') && is_numeric($paymentRecord->ind_trans_urbain) ? (float)$paymentRecord->ind_trans_urbain : 0.0;
            $primeDivers = property_exists($paymentRecord, 'primesDivers') && is_numeric($paymentRecord->primesDivers) ? (float)$paymentRecord->primesDivers : 0.0;
            $tauxCimr = property_exists($paymentRecord, 'taux_CIMR') ? $paymentRecord->taux_CIMR : '0%';
            $tauxMutuelle = property_exists($paymentRecord, 'taux_mutuelle') ? $paymentRecord->taux_mutuelle : '0%';
            $solidarite = property_exists($paymentRecord, 'solidarite') && is_numeric($paymentRecord->solidarite) ? (float)$paymentRecord->solidarite : 0.0;
            $avance = property_exists($paymentRecord, 'avancements_sal') && is_numeric($paymentRecord->avancements_sal) ? (float)$paymentRecord->avancements_sal : 0.0;
            $heuresService = property_exists($paymentRecord, 'heures_service') && is_numeric($paymentRecord->heures_service) ? (float)$paymentRecord->heures_service : 0.0;
            $prixHeure = property_exists($paymentRecord, 'prix_heure') && is_numeric($paymentRecord->prix_heure) ? (float)$paymentRecord->prix_heure : 0.0; 
            $salaired = property_exists($paymentRecord, 'salaired') ? $paymentRecord->salaired : '0%';
            $primeJournaliere = property_exists($paymentRecord, 'prime_journaliere') && is_numeric($paymentRecord->prime_journaliere) ? (float)$paymentRecord->prime_journaliere : 0.0;
            $salaireBG = property_exists($paymentRecord, 'salaireBG') && is_numeric($paymentRecord->salaireBG) ? (float)$paymentRecord->salaireBG : 0.0;
            $primNonimposables = property_exists($paymentRecord, 'primNonimposables') && is_numeric($paymentRecord->primNonimposables) ? (float)$paymentRecord->primNonimposables : 0.0;
            $cotisationCnss = is_numeric($paymentRecord->cotisation_cnss) ? (float)$paymentRecord->cotisation_cnss : 0.0;
            $cotisationAmo = property_exists($paymentRecord, 'cotisation_amo') && is_numeric($paymentRecord->cotisation_amo) ? (float)$paymentRecord->cotisation_amo : 0.0;
            $cotisationCIMR = property_exists($paymentRecord, 'cotisation_CIMR') && is_numeric($paymentRecord->cotisation_CIMR) ? (float)$paymentRecord->cotisation_CIMR : 0.0;
            $cotisationMutuelle = property_exists($paymentRecord, 'cotisation_mutuelle') && is_numeric($paymentRecord->cotisation_mutuelle) ? (float)$paymentRecord->cotisation_mutuelle : 0.0;
            $tauxFrais = property_exists($paymentRecord, 'tauxFrais') && is_numeric($paymentRecord->tauxFrais) ? (float)$paymentRecord->tauxFrais : 0.0;
            $professionalTaxDeduction = property_exists($paymentRecord, 'professional_tax_deduction') && is_numeric($paymentRecord->professional_tax_deduction) ? (float)$paymentRecord->professional_tax_deduction : 0.0;
            $salaireNet = is_numeric($paymentRecord->salaireNI) ? (float)$paymentRecord->salaireNI : 0.0;
            $irData = property_exists($paymentRecord, 'irData') && is_numeric($paymentRecord->irData) ? (float)$paymentRecord->irData : 0.0;
            $sommeADeduire = property_exists($paymentRecord, 'somme_a_deduire') && is_numeric($paymentRecord->somme_a_deduire) ? (float)$paymentRecord->somme_a_deduire : 0.0;
            $irBrut = property_exists($paymentRecord, 'ir_brut') && is_numeric($paymentRecord->ir_brut) ? (float)$paymentRecord->ir_brut : 0.0;
            $chargeDeFamille = property_exists($paymentRecord, 'charge_de_famille') && is_numeric($paymentRecord->charge_de_famille) ? (float)$paymentRecord->charge_de_famille : 0.0;
           
            $irNet = is_numeric($paymentRecord->irNet) ? (float)$paymentRecord->irNet : 0.0;
             $salaire = property_exists($paymentRecord, 'salaire') && is_numeric($paymentRecord->salaire) ? (float)$paymentRecord->salaire : 0.0;
             $salaireBrut = is_numeric($paymentRecord->salaireBI) ? (float)$paymentRecord->salaireBI : 0.0;

            // Log the fetched values for debugging
            Log::info('Employee payment data', [
                'id_salarie' => $idSalarie,
                'salaire_base' => $baseSalary,
                'jours_travail' => $joursTravail,
                'salaire_brut' => $salaireBrut,
                'salaire_net' => $salaireNet,
                'ir_net' => $irNet,
                'solidarite' => $solidarite,
                'cotisation_cnss' => $cotisationCnss,
                'avance' => $avance,
                'heures_service' => $heuresService,
                'prix_heure' => $prixHeure,
                'nombre_enfant' => $salaryData->nombre_enfant,
                'jours_feries' => $joursFeriesCount,
                'jours_conges' => $congesPayeCount,
                'jours_supp' => $joursFeriesTravaillesCount,
                'prime_deplacement' => $primeDeplacement,
                'prime_representation' => $primeRepresentation,
                'prime_panier' => $primePanier,
                'prime_transport' => $primeTransport,
                'prime_divers' => $primeDivers,
                'taux_cimr' => $tauxCimr,
                'taux_mutuelle' => $tauxMutuelle,
                'salaired' => $salaired
            ]);

            // Récupérer la désignation de la fonction
            $functionDesignation = DB::table('salaries')
                ->leftJoin('fonctions', 'salaries.fonction_id', '=', 'fonctions.id')
                ->where('salaries.id', $idSalarie)
                ->value('fonctions.designation') ?? 'Non spécifié';

            // Valeurs calculées ou fixes
            $dateNaissance = $salaryData->date_naissance ?? 'Non spécifié';
            $dateEmbauche = $salaryData->date_embauche ?? 'Non spécifié';
            $cin = $salaryData->cin ?? 'Non spécifié';
            $cnss = $salaryData->n_matricule_cnss ?? 'Non spécifié';

            $employeeDataList[] = [
                'id' => $idSalarie,
                'n_matricule_entreprise' => $salaryData->n_matricule_entreprise,
                'nom_prenom' => ($salaryData->nom ?? 'Inconnu') . ' ' . ($salaryData->prenom ?? 'Inconnu'),
                'function' => $functionDesignation,
                'date_naissance' => $dateNaissance,
                'date_embauche' => $dateEmbauche,
                'cin' => $cin,
                'cnss' => $cnss,
                'salaire_base' => $baseSalary,
                'jours_travail' => $joursTravail,
                'jours_feries' => $joursFeriesCount,
                'jours_conges' => $congesPayeCount,
                'jours_supp' => $joursFeriesTravaillesCount,
                'prime_deplacement' => $primeDeplacement,
                'prime_representation' => $primeRepresentation,
                'prime_panier' => $primePanier,
                'prime_transport' => $primeTransport,
                'prime_divers' => $primeDivers,
                'taux_cimr' => $tauxCimr,
                'taux_mutuelle' => $tauxMutuelle,
                'heures_service' => $heuresService,
                'prix_heure' => $prixHeure,
                'salaire_brut' => $salaireBrut,
                'salaire_net' => $salaireNet,
                'ir_net' => $irNet,
                'solidarite' => $solidarite,
                'cotisation_cnss' => $cotisationCnss,
                'avance' => $avance,
                'situation_familiale' => $salaryData->situation_familiale ?? 'Célibataire',
                'nbr_enfants' => $salaryData->nombre_enfant ?? '0',
                'salaired' => property_exists($paymentRecord, 'salaired') ? $paymentRecord->salaired : '0%',
              'anciennete' => is_numeric($salaryData->anciennete) ? (float)$salaryData->anciennete : 0,
              'tauxAnciennete' => property_exists($paymentRecord, 'tauxAnciennete') && is_numeric($paymentRecord->tauxAnciennete) ? (float)$paymentRecord->tauxAnciennete : 0,
              'prime_anciennete' => property_exists($paymentRecord, 'prime_anciennete') && is_numeric($paymentRecord->prime_anciennete) ? (float)$paymentRecord->prime_anciennete : 0,
              'prime_journaliere' => $primeJournaliere,
              'salaireBG' => $salaireBG,
              'primNonimposables' => $primNonimposables,
              'cotisation_amo' => $cotisationAmo,
              'cotisation_CIMR' => $cotisationCIMR,
              'cotisation_mutuelle' => $cotisationMutuelle,
              'tauxFrais' => $tauxFrais, 
            'professional_tax_deduction' => $professionalTaxDeduction,
            'salaireNI' => $salaireNet,
            'irData' => $irData,
                'somme_a_deduire' => $sommeADeduire,
                'ir_brut' => $irBrut,
                'charge_de_famille' => $chargeDeFamille,
                'ir_net' => $irNet,
                'salaire' => $salaire,
                'salaireBI' => $salaireBrut,
                'hs_25' => property_exists($paymentRecord, 'heurSupp25') && is_numeric($paymentRecord->heurSupp25) ? (float)$paymentRecord->heurSupp25 : 0,
            'hs_50' => property_exists($paymentRecord, 'heurSupp50') && is_numeric($paymentRecord->heurSupp50) ? (float)$paymentRecord->heurSupp50 : 0,
            'hs_100' => property_exists($paymentRecord, 'heurSupp100') && is_numeric($paymentRecord->heurSupp100) ? (float)$paymentRecord->heurSupp100 : 0,
            'daily_salary' => property_exists($paymentRecord, 'daily_salary') && is_numeric($paymentRecord->daily_salary) ? (float)$paymentRecord->daily_salary : 0,
            'salaireBaseImposable' => property_exists($paymentRecord, 'salaireBaseImposable') && is_numeric($paymentRecord->salaireBaseImposable) ? (float)$paymentRecord->salaireBaseImposable : 0,
            'joursCalcules' => property_exists($paymentRecord, 'joursCalcules') && is_numeric($paymentRecord->joursCalcules) ? (int)$paymentRecord->joursCalcules : 26,
                        


             
        ];
                    

                    // Mettre à jour uniquement le typer dans paiement_salaires pour les nouveaux employés
                    if (in_array($idSalarie, $idSalaries)) {
                        DB::table('paiement_salaires')
                            ->where('id', $paymentRecord->id)
                            ->update([
                                'typer' => 'vg',
                                'updated_at' => now()
                            ]);
                        Log::info('Updated paiement_salaires', [
                            'id_salarie' => $idSalarie,
                            'payment_id' => $paymentRecord->id,
                            'typer' => 'vg'
                        ]);

                        $paymentData[$idSalarie] = (object) ['id' => $paymentRecord->id, 'salaire' => $baseSalary];
                    }
                }

                // Générer un PDF unique pour tous les employés
                $pdfPath = $this->generateBulkPayrollPdf($employeeDataList, $month, $year);
                Log::info('Single bulk payroll PDF generated', ['pdf_path' => $pdfPath]);

                // Mettre à jour pieces_joint pour tous les employés (nouveaux et existants)
                foreach ($allEmployeeIds as $idSalarie) {
                    $paymentRecord = DB::table('paiement_salaires')
                        ->where('id_salarie', $idSalarie)
                        ->where('mois', $monthNumber)
                        ->where('annee', $year)
                        ->first();

                    if (!$paymentRecord) {
                        continue;
                    }

                    $paymentId = in_array($idSalarie, $idSalaries) ? $paymentData[$idSalarie]->id : $paymentRecord->id;
                    $existingPiece = DB::table('pieces_joint')
                        ->where('id_paiment', $paymentId)
                        ->where('id_salarier', $idSalarie)
                        ->where('mois', $month)
                        ->where('annee', $year)
                        ->first();

                    if ($existingPiece) {
                        DB::table('pieces_joint')
                            ->where('id', $existingPiece->id)
                            ->update([
                                'pj' => $pdfPath,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('pieces_joint')->insert([
                            'id_paiment' => $paymentId,
                            'id_salarier' => $idSalarie,
                            'mois' => $month,
                            'annee' => $year,
                            'pj' => $pdfPath,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    Log::info('pieces_joint updated', [
                        'id_salarie' => $idSalarie,
                        'id_paiment' => $paymentId,
                        'mois' => $month,
                        'annee' => $year,
                        'pj_path' => $pdfPath
                    ]);
                }

                // Préparer la réponse
                $response = [
                    'success' => 'Paiement groupé effectué avec succès pour ' . $month . ' ' . $year . '.',
                    'bulk_pdf_path' => $pdfPath,
                    'bulk_pdf_name' => "bulk_quittance_{$month}_{$year}.pdf",
                    'individual_pdfs' => []
                ];

                return response()->json($response);

            } catch (\Exception $e) {
                Log::error('Error in addBulkSalaryPayment', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => "Erreur : {$e->getMessage()}"], 500);
            }
}

// Fonction downloadVirementsPdf (similaire à downloadGroupPayment)
public function downloadVirementsPdf($year, $month)
{
    $pdfPath = "assets/storage/salaries/virements_pdfs/virements_{$month}_{$year}.pdf";
    $fullPath = public_path($pdfPath);

    if (!File::exists($fullPath)) {
        Log::warning('Fichier PDF virements non trouvé', ['path' => $fullPath]);
        return response()->json(['error' => 'Fichier non trouvé.'], 404);
    }

    return response()->file($fullPath, ['Content-Type' => 'application/pdf']);
}

// Fonction uploadVirementsPdf (similaire à uploadBulkPayroll)
public function uploadVirementsPdf(Request $request)
{
    $request->validate([
        'virements_file' => 'required|file|mimes:pdf|max:2048',
        'filename' => 'required|string'
    ]);

    $file = $request->file('virements_file');
    $originalFilename = $request->input('filename');
    $directory = public_path('assets/storage/salaries/virements_pdfs');
    if (!File::exists($directory)) {
        File::makeDirectory($directory, 0755, true);
    }

    $newPath = $directory . '/' . $originalFilename;
    $file->move($directory, $originalFilename);

    DB::table('virements_pdfs')->updateOrInsert(
        ['month' => explode('_', $originalFilename)[1], 'year' => explode('_', $originalFilename)[2]],
        ['uploaded_path' => "assets/storage/salaries/virements_pdfs/{$originalFilename}", 'updated_at' => now()]
    );

    Log::info('Upload virements PDF réussi', ['path' => $newPath]);

    return response()->json(['success' => true, 'message' => 'Fichier uploadé avec succès.']);
}

public function generateVirementsPdf(Request $request)
{
    try {
        $year = $request->input('year', date('Y'));
        $month = strtolower($request->input('month', Session::get('selected_month', 'janvier')));

        $monthMap = [
            'janvier' => '01', 'fevrier' => '02', 'mars' => '03', 'avril' => '04',
            'mai' => '05', 'juin' => '06', 'juillet' => '07', 'aout' => '08',
            'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'decembre' => '12'
        ];
        $monthNumber = $monthMap[$month] ?? '01';

        // Requête corrigée avec le champ salaire
        $employees = DB::table('paiement_salaires')
            ->join('salaries', 'paiement_salaires.id_salarie', '=', 'salaries.id')
            ->where('paiement_salaires.annee', $year)
            ->where('paiement_salaires.mois', $monthNumber)
            ->whereIn('paiement_salaires.typer', ['vg', 'v'])
            ->select(
                'salaries.nom',
                'salaries.prenom',
                'paiement_salaires.salaire as salaire',
                'salaries.rib',
                'paiement_salaires.id_salarie'
            )
            ->get();

        if ($employees->isEmpty()) {
            Log::warning('Aucun virement trouvé pour génération PDF', [
                'year' => $year,
                'month' => $month,
                'monthNumber' => $monthNumber
            ]);
            return response()->json(['error' => 'Aucun salarié avec virement (v ou vg) pour ce mois.'], 404);
        }

        $total = $employees->sum('salaire');

        $companySettings = CompanySettings::first() ?? (object) [
            'nom_etreprise' => 'STE TEST SARL',
            'address' => 'Oujda',
            'cnss_number' => 'XXXXXXXXXXXX',
            'ice' => 'XXXXXXXXXXXXXX',
        ];

        // Logique pour le logo (comme dans generatePaySlipPDF)
        $defaultLogoPath = 'assets/img/favicon/anassi2.jpg';
        $customLogoPath = !empty($companySettings->logo) ? 'storage/' . $companySettings->logo : null;

        if ($customLogoPath && file_exists(public_path($customLogoPath))) {
            $companySettings->logo_path = public_path($customLogoPath);
        } else {
            $companySettings->logo_path = file_exists(public_path($defaultLogoPath)) ? public_path($defaultLogoPath) : null;
        }

        // Après la logique du logo, ajoute ceci :
    $stampPath = !empty($companySettings->stamp) ? 'storage/' . $companySettings->stamp : null;

    if ($stampPath && file_exists(public_path($stampPath))) {
        $companySettings->stamp_path = public_path($stampPath);
    } else {
        $companySettings->stamp_path = null;
    }

        $pdf = Pdf::loadView('bultin-paie.virements_list', compact(
            'employees',
            'month',
            'year',
            'total',
            'companySettings'
        ))->setPaper('a4', 'landscape');

        $directory = public_path('assets/storage/salaries/virements_pdfs');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = "virements_{$month}_{$year}.pdf";
        $relativePath = "assets/storage/salaries/virements_pdfs/{$filename}";

        $fullPath = public_path($relativePath);

        $pdf->save($fullPath);

        // Enregistrement du chemin dans pieces_joint (tous les salariés concernés)
        $updated = DB::table('pieces_joint')
            ->where('annee', $year)
            ->where('mois', $month)
            ->whereIn('id_salarier', $employees->pluck('id_salarie'))
            ->update([
                'virement_pdf_filename' => $relativePath,
                'updated_at' => now()
            ]);

        Log::info('PDF virements généré et lié dans pieces_joint', [
            'fichier' => $filename,
            'chemin' => $relativePath,
            'lignes_mises_a_jour' => $updated,
            'mois' => $month,
            'annee' => $year
        ]);

        return response()->download($fullPath, $filename);

    } catch (\Exception $e) {
        Log::critical('Erreur génération virements PDF : ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'error' => 'Erreur lors de la génération du PDF des virements.'
        ], 500);
    }
}

// generation de livre de paie de paiement par groupe 

public function generateBulkPayrollPdf($employeeDataList, $month, $year)
{
    try {
        // Récupérer les paramètres de l'entreprise
        $companySettings = CompanySettings::first();
        if (!$companySettings) {
            Log::error('No company settings found');
            throw new \Exception('Aucune configuration d\'entreprise trouvée.');
        }
        $companySettings = $companySettings->toArray();

        $directory = public_path('assets/storage/salaries/bulk_payrolls');

        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true)) {
                Log::error('Failed to create directory', ['directory' => $directory]);
                throw new \Exception("Échec de la création du dossier : {$directory}");
            }
        }

        if (!is_writable($directory)) {
            Log::error('Directory not writable', ['directory' => $directory]);
            throw new \Exception("Le dossier n'est pas accessible en écriture : {$directory}");
        }

        $data = [
            'employees' => $employeeDataList,
            'month' => $month,
            'year' => $year
        ];

        $pdf = Pdf::loadView('bultin-paie.bulk_quittance_pdf', [
            'data' => $data,
            'companySettings' => $companySettings
        ]);

        $filename = "bulk_payroll_{$month}_{$year}.pdf";
        $path = "assets/storage/salaries/bulk_payrolls/{$filename}";
        $fullPath = public_path($path);

        $pdf->save($fullPath);

        if (!file_exists($fullPath)) {
            Log::error('PDF file not saved', ['full_path' => $fullPath]);
            throw new \Exception("Échec de l'enregistrement du PDF à {$fullPath}");
        }

        Log::info('Bulk payroll PDF generated', ['path' => $path, 'full_path' => $fullPath, 'employee_count' => count($employeeDataList)]);
        return $path;
    } catch (\Exception $e) {
        Log::error('Error in generateBulkPayrollPdf', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}



protected function getBulkPayrolls($year)
{
    $directory = public_path('assets/storage/salaries/bulk_payrolls');
    $bulkPayrolls = [];

    if (File::exists($directory)) {
        $files = File::files($directory);
        $monthMap = [
            1 => 'janvier', 2 => 'fevrier', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'aout',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'decembre'
        ];
        $monthOrder = array_values($monthMap);

        foreach ($files as $file) {
            $filename = $file->getFilename();
            if (preg_match("/bulk_payroll_(.+)_{$year}\.pdf/", $filename, $matches)) {
                $month = $matches[1];
                if (in_array($month, $monthOrder)) {
                    $bulkPayrolls[] = $filename;
                }
            }
        }

        usort($bulkPayrolls, function ($a, $b) use ($monthOrder) {
            preg_match("/bulk_payroll_(.+)_(\d+)\.pdf/", $a, $matchA);
            preg_match("/bulk_payroll_(.+)_(\d+)\.pdf/", $b, $matchB);
            $monthA = array_search($matchA[1], $monthOrder);
            $monthB = array_search($matchB[1], $monthOrder);
            return $monthA <=> $monthB;
        });
    }

    return $bulkPayrolls;
}


//  fonction de telechargement de livre de paie 

public function downloadGroupPayment($year, $month)
{
    try {
        // Valider les entrées
        if (!is_numeric($year) || $year < 1900 || $year > 9999) {
            Log::error('Invalid year', ['year' => $year]);
            return response()->json(['error' => 'Année non valide.'], 400);
        }

        $validMonths = ['janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];
        $month = strtolower($month);
        if (!in_array($month, $validMonths)) {
            Log::error('Invalid month', ['month' => $month]);
            return response()->json(['error' => 'Mois non valide.'], 400);
        }

        // Construire le chemin du fichier
        $filename = "bulk_payroll_{$month}_{$year}.pdf";
        $filePath = public_path("assets/storage/salaries/bulk_payrolls/{$filename}");

        // Vérifier si le fichier existe
        if (!File::exists($filePath)) {
            Log::error('Payroll file not found', [
                'month' => $month,
                'year' => $year,
                'file_path' => $filePath,
                'directory_contents' => File::files(public_path('assets/storage/salaries/bulk_payrolls'))
            ]);
            return response()->json(['error' => "Aucun fichier de paie trouvé pour {$month} {$year}."], 404);
        }

        // Vérifier les permissions du fichier
        if (!is_readable($filePath)) {
            Log::error('Payroll file not readable', ['file_path' => $filePath]);
            return response()->json(['error' => 'Le fichier n\'est pas accessible en lecture.'], 500);
        }

        Log::info('Downloading payroll file', [
            'month' => $month,
            'year' => $year,
            'file_path' => $filePath,
            'file_size' => File::size($filePath)
        ]);

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => File::size($filePath),
        ]);
    } catch (\Exception $e) {
        Log::error('Error in downloadGroupPayment', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'month' => $month,
            'year' => $year,
            'file_path' => $filePath ?? 'undefined'
        ]);
        return response()->json(['error' => "Erreur lors du téléchargement : {$e->getMessage()}"], 500);
    }
}

// prcourire les livres de paies 

public function uploadBulkPayroll(Request $request)
{
    // Valider la requête
    $request->validate([
        'bulk_payroll_file' => 'required|file|mimes:pdf|max:10240', // Fichier PDF, max 10 Mo
        'filename' => ['required', 'string', 'regex:/^bulk_payroll_[a-zA-Z]+_\d{4}\.pdf$/'], // Pas de _partX
    ]);

    try {
        $file = $request->file('bulk_payroll_file');
        $originalFilename = $request->input('filename'); // Ex: bulk_payroll_janvier_2025.pdf
        $year = preg_match("/_(\d{4})\.pdf/", $originalFilename, $matches) ? $matches[1] : date('Y');
        $month = Str::beforeLast(Str::after($originalFilename, 'bulk_payroll_'), '_' . $year . '.pdf');
        $month = strtolower($month);

        // Vérifier que le mois est valide
        $validMonths = ['janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];
        if (!in_array($month, $validMonths)) {
            Log::error('Invalid month extracted from filename', ['month' => $month, 'filename' => $originalFilename]);
            throw new \Exception('Mois invalide extrait du nom de fichier : ' . $month);
        }

        // Générer le nom du fichier avec "_cach"
        $newFilename = str_replace('.pdf', '_cach.pdf', $originalFilename); // Ex: bulk_payroll_janvier_2025_cach.pdf
        $destinationPath = public_path('assets/storage/salaries/bulk_payrolls');

        // Vérifier si le dossier existe, sinon le créer
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        // Vérifier les permissions d'écriture
        if (!is_writable($destinationPath)) {
            Log::error('Directory not writable', ['directory' => $destinationPath]);
            throw new \Exception('Le dossier de destination n\'est pas accessible en écriture.');
        }

        // Enregistrer le fichier dans le dossier bulk_payrolls
        $file->move($destinationPath, $newFilename);
        $newFilePath = $destinationPath . '/' . $newFilename;

        // Vérifier que le fichier a été enregistré
        if (!File::exists($newFilePath)) {
            Log::error('Failed to save uploaded file', ['file_path' => $newFilePath]);
            throw new \Exception('Échec de l\'enregistrement du fichier uploadé.');
        }

        // Rechercher les enregistrements dans pieces_joint pour le fichier bulk_payroll
        $pjPath = "assets/storage/salaries/bulk_payrolls/{$originalFilename}";
        $piecesJointes = DB::table('pieces_joint')
            ->where('pj', $pjPath)
            ->orWhereRaw('LOWER(pj) = ?', [strtolower($pjPath)])
            ->get();

        Log::info('Recherche enregistrements dans pieces_joint', [
            'pj' => $pjPath,
            'found' => $piecesJointes->isNotEmpty(),
            'count' => $piecesJointes->count(),
            'records' => $piecesJointes->toArray()
        ]);

        if ($piecesJointes->isEmpty()) {
            // Supprimer le fichier uploadé si aucun enregistrement n'est trouvé
            File::delete($newFilePath);
            Log::error('No matching records found in pieces_joint', ['pj' => $pjPath]);
            throw new \Exception('Aucun enregistrement trouvé pour le fichier : ' . $originalFilename);
        }

        // Mettre à jour le champ quittance_cah pour tous les enregistrements trouvés
        DB::table('pieces_joint')
            ->where('pj', $pjPath)
            ->orWhereRaw('LOWER(pj) = ?', [strtolower($pjPath)])
            ->update([
                'quittance_cah' => "assets/storage/salaries/bulk_payrolls/{$newFilename}",
                'updated_at' => now(),
            ]);

        Log::info('Champ quittance_cah mis à jour dans pieces_joint', [
            'quittance_cah' => "assets/storage/salaries/bulk_payrolls/{$newFilename}",
            'pj' => $pjPath,
            'updated_records' => $piecesJointes->count()
        ]);

        // Copier le fichier dans les dossiers individuels des salariés
        foreach ($piecesJointes as $piece) {
            try {
                $idSalarie = $piece->id_salarier;

                // Récupérer les données du salarié
                $employeeData = DB::table('salaries')
                    ->where('id', $idSalarie)
                    ->select('nom', 'n_matricule_entreprise')
                    ->first();

                if (!$employeeData) {
                    Log::warning('Employee not found for quittance copy', [
                        'id_salarie' => $idSalarie,
                        'piece_id' => $piece->id
                    ]);
                    continue; // Passer au suivant si le salarié n'existe pas
                }

                // Définir le chemin de stockage individuel
                $folderName = Str::slug($employeeData->nom . '_' . $employeeData->n_matricule_entreprise, '_');
                $employeeDirectory = public_path("assets/storage/salaries/{$folderName}/salaires");

                // Créer le dossier s'il n'existe pas
                if (!File::exists($employeeDirectory)) {
                    File::makeDirectory($employeeDirectory, 0755, true);
                    Log::info('Employee directory created', ['directory' => $employeeDirectory]);
                }

                // Vérifier les permissions d'écriture
                if (!is_writable($employeeDirectory)) {
                    Log::warning('Employee directory not writable', [
                        'directory' => $employeeDirectory,
                        'id_salarie' => $idSalarie
                    ]);
                    continue; // Passer au suivant si le dossier n'est pas accessible
                }

                // Générer le nom du fichier individuel
                $employeeFileName = "quittance_cah_{$idSalarie}_{$month}_{$year}.pdf";
                $employeeFilePath = $employeeDirectory . '/' . $employeeFileName;

                // Copier le fichier
                if (!File::copy($newFilePath, $employeeFilePath)) {
                    Log::warning('Failed to copy file to employee directory', [
                        'source' => $newFilePath,
                        'destination' => $employeeFilePath,
                        'id_salarie' => $idSalarie
                    ]);
                    continue; // Passer au suivant si la copie échoue
                }

                Log::info('File copied to employee directory', [
                    'id_salarie' => $idSalarie,
                    'source' => $newFilePath,
                    'destination' => $employeeFilePath
                ]);

                // Mettre à jour le champ quittance_cah avec le chemin individuel si nécessaire
                // (Optionnel : si vous voulez que quittance_cah pointe vers le fichier individuel)
                DB::table('pieces_joint')
                    ->where('id', $piece->id)
                    ->update([
                        'quittance_cah' => "assets/storage/salaries/{$folderName}/salaires/{$employeeFileName}",
                        'updated_at' => now(),
                    ]);

                Log::info('Champ quittance_cah mis à jour avec chemin individuel', [
                    'piece_id' => $piece->id,
                    'id_salarie' => $idSalarie,
                    'quittance_cah' => "assets/storage/salaries/{$folderName}/salaires/{$employeeFileName}"
                ]);

            } catch (\Exception $e) {
                Log::warning('Error processing employee quittance copy', [
                    'id_salarie' => $idSalarie,
                    'piece_id' => $piece->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue; // Continuer avec le suivant en cas d'erreur
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Fichier uploadé sous ' . $newFilename . ' et copié pour ' . $piecesJointes->count() . ' salariés avec succès.',
        ]);

    } catch (\Exception $e) {
        // Supprimer le fichier en cas d'erreur
        $filePath = public_path('assets/storage/salaries/bulk_payrolls/' . ($newFilename ?? 'unknown'));
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        Log::error('Erreur dans uploadBulkPayroll: ' . $e->getMessage(), [
            'filename' => $request->input('filename'),
            'month' => $month ?? 'undefined',
            'year' => $year ?? 'undefined',
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'upload : ' . $e->getMessage(),
        ], 500);
    }
}

 // fonction de suppression de calcule d'un salaire 

   public function deleteSalaryPayment(Request $request)
{
    DB::beginTransaction();
    try {
        $idSalarie = $request->input('id_salarie');
        $year = $request->input('year');
        $month = $request->input('month');

        // Validation des entrées
        if (!$idSalarie || !$year || !$month) {
            Log::warning('Paramètres manquants pour suppression', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Paramètres manquants : id_salarie, year ou month.'
            ], 400);
        }

        // Validation de l'année
        if (!preg_match('/^\d{4}$/', $year) || $year < 1900 || $year > date('Y')) {
            Log::warning('Année invalide', ['year' => $year]);
            return response()->json([
                'success' => false,
                'message' => 'Année invalide.'
            ], 400);
        }

        // Normaliser et valider le mois
        $month = strtolower(preg_replace('/[\p{M}]/u', '', $month)); // Remove accents
        $validMonths = ['janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];
        if (!in_array($month, $validMonths)) {
            Log::warning('Mois invalide', ['month' => $month]);
            return response()->json([
                'success' => false,
                'message' => 'Mois invalide.'
            ], 400);
        }

        // Mapper le mois en numéro pour paiement_salaires
        $monthMap = [
            'janvier' => '01', 'fevrier' => '02', 'mars' => '03', 'avril' => '04',
            'mai' => '05', 'juin' => '06', 'juillet' => '07', 'aout' => '08',
            'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'decembre' => '12'
        ];
        $monthNumber = $monthMap[$month];

        // Vérifier l'existence de la table salairs_{$year}
        $tableName = "salairs_{$year}";
        if (!Schema::hasTable($tableName)) {
            Log::warning('Table des salaires inexistante', ['table' => $tableName]);
            return response()->json([
                'success' => false,
                'message' => "La table {$tableName} n'existe pas."
            ], 404);
        }

        // Vérifier l'existence de la colonne du mois dans salairs_{$year}
        if (!Schema::hasColumn($tableName, $month)) {
            Log::warning('Colonne du mois inexistante', ['table' => $tableName, 'month' => $month]);
            return response()->json([
                'success' => false,
                'message' => "La colonne {$month} n'existe pas dans la table {$tableName}."
            ], 400);
        }

        // Récupérer le matricule et le nom de l'employé
        $employee = DB::table('salaries')
            ->where('id', $idSalarie)
            ->select('n_matricule_entreprise', 'nom')
            ->first();

        if (!$employee) {
            Log::warning('Salarié non trouvé', ['id_salarie' => $idSalarie]);
            return response()->json([
                'success' => false,
                'message' => 'Salarié non trouvé.'
            ], 404);
        }

        $matricule = $employee->n_matricule_entreprise ?? 'unknown';
        $folderName = Str::slug($employee->nom . '_' . $matricule, '_');

        // Vérifier les enregistrements dans pieces_joint
        $piecesRecords = DB::table('pieces_joint')
            ->where('id_salarier', $idSalarie)
            ->where('annee', $year)
            ->where('mois', $month)
            ->select('id_paiment', 'bultin_paie', 'bultin_paie_ca', 'pj', 'quittance_cah')
            ->get();

        $piecesCount = $piecesRecords->count();
        $idPaiment = $piecesRecords->pluck('id_paiment')->first();
        Log::info('Vérification pieces_joint', [
            'id_salarier' => $idSalarie,
            'year' => $year,
            'month' => $month,
            'matricule' => $matricule,
            'folder_name' => $folderName,
            'count' => $piecesCount,
            'id_paiment' => $idPaiment,
            'records' => $piecesRecords->toArray()
        ]);

        // Vérifier les enregistrements dans paiement_salaires
        $paiementRecords = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)
            ->where('annee', $year)
            ->where('mois', $monthNumber)
            ->get();

        $paiementCount = $paiementRecords->count();
        Log::info('Vérification paiement_salaires', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $monthNumber,
            'matricule' => $matricule,
            'count' => $paiementCount,
            'records' => $paiementRecords->toArray()
        ]);

        // Si aucun enregistrement dans les deux tables
        if ($paiementCount === 0 && $piecesCount === 0) {
            Log::warning('Aucun enregistrement trouvé pour suppression', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month,
                'month_number' => $monthNumber,
                'matricule' => $matricule
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Aucun enregistrement trouvé pour ce salarié, mois et année.'
            ], 404);
        }

        // Vérifier si le salarié fait partie d'un PDF groupé
        $bulkPdfPath = "assets/storage/salaries/bulk_payrolls/bulk_payroll_{$month}_{$year}.pdf";
        $isBulkPayment = $piecesRecords->contains('pj', $bulkPdfPath);

        // Supprimer les fichiers individuels (sauf le PDF groupé pour l'instant)
        $filesDeleted = 0;
        $columnsToCheck = ['bultin_paie', 'bultin_paie_ca', 'quittance_cah']; // Exclure 'pj' pour gérer le PDF groupé séparément

        foreach ($piecesRecords as $record) {
            foreach ($columnsToCheck as $column) {
                if (!empty($record->$column)) {
                    $filePath = public_path($record->$column);
                    Log::info('Tentative de suppression du fichier', [
                        'id_salarier' => $idSalarie,
                        'matricule' => $matricule,
                        'column' => $column,
                        'file_path' => $record->$column,
                        'full_path' => $filePath,
                        'file_exists' => File::exists($filePath),
                        'file_writable' => File::exists($filePath) ? is_writable($filePath) : false
                    ]);
                    if (File::exists($filePath)) {
                        if (is_writable($filePath)) {
                            File::delete($filePath);
                            Log::info('Fichier supprimé avec succès', [
                                'id_salarier' => $idSalarie,
                                'matricule' => $matricule,
                                'column' => $column,
                                'file_path' => $record->$column,
                                'full_path' => $filePath
                            ]);
                            $filesDeleted++;
                        } else {
                            Log::warning('Fichier non accessible en écriture', [
                                'id_salarier' => $idSalarie,
                                'matricule' => $matricule,
                                'column' => $column,
                                'file_path' => $record->$column,
                                'full_path' => $filePath
                            ]);
                        }
                    }
                }
            }
        }

        // Gérer le PDF groupé
        if ($isBulkPayment) {
            // Récupérer les autres salariés associés au même PDF groupé
            $otherEmployees = DB::table('pieces_joint')
                ->where('mois', $month)
                ->where('annee', $year)
                ->where('pj', $bulkPdfPath)
                ->where('id_salarier', '!=', $idSalarie)
                ->pluck('id_salarier')
                ->toArray();

            Log::info('Autres salariés dans le PDF groupé', [
                'month' => $month,
                'year' => $year,
                'other_employee_ids' => $otherEmployees
            ]);

            if (!empty($otherEmployees)) {
                // Régénérer le PDF avec les autres salariés
                $employeeDataList = [];
                foreach ($otherEmployees as $otherIdSalarie) {
                    $salaryData = DB::table('salaries')->where('id', $otherIdSalarie)->first();
                    if (!$salaryData) {
                        Log::warning('Salarié non trouvé pour régénération PDF', ['id_salarie' => $otherIdSalarie]);
                        continue;
                    }

                    $employeeData = Salarie::where('id', $otherIdSalarie)
                        ->select('n_matricule_entreprise', 'nom', 'prenom', 'nombre_enfant')
                        ->first();
                    $salaryData->n_matricule_entreprise = $employeeData->n_matricule_entreprise ?? 'unknown';
                    $salaryData->nom = $employeeData->nom ?? $salaryData->nom ?? 'Inconnu';
                    $salaryData->prenom = $employeeData->prenom ?? $salaryData->prenom ?? 'Inconnu';
                    $salaryData->nombre_enfant = $employeeData->nombre_enfant ?? '0';

                    $paymentRecord = DB::table('paiement_salaires')
                        ->where('id_salarie', $otherIdSalarie)
                        ->where('mois', $monthNumber)
                        ->where('annee', $year)
                        ->first();

                    if (!$paymentRecord) {
                        Log::warning('Enregistrement de paiement non trouvé pour régénération PDF', [
                            'id_salarie' => $otherIdSalarie,
                            'month' => $monthNumber,
                            'year' => $year
                        ]);
                        continue;
                    }

                    $functionDesignation = DB::table('salaries')
                        ->leftJoin('fonctions', 'salaries.fonction_id', '=', 'fonctions.id')
                        ->where('salaries.id', $otherIdSalarie)
                        ->value('fonctions.designation') ?? 'Non spécifié';

                    $employeeDataList[] = [
                        'id' => $otherIdSalarie,
                        'n_matricule_entreprise' => $salaryData->n_matricule_entreprise,
                        'nom_prenom' => ($salaryData->nom ?? 'Inconnu') . ' ' . ($salaryData->prenom ?? 'Inconnu'),
                        'function' => $functionDesignation,
                        'date_naissance' => $salaryData->date_naissance ?? 'Non spécifié',
                        'date_embauche' => $salaryData->date_embauche ?? 'Non spécifié',
                        'cin' => $salaryData->cin ?? 'Non spécifié',
                        'cnss' => $salaryData->n_matricule_cnss ?? 'Non spécifié',
                        'salaire_base' => is_numeric($salaryData->salaire_base) ? (float)$salaryData->salaire_base : 0.0,
                        'jours_travail' => is_numeric($paymentRecord->num_jours) ? (int)$paymentRecord->num_jours : 26,
                        'jours_feries' => property_exists($paymentRecord, 'joursFeriesCount') && is_numeric($paymentRecord->joursFeriesCount) ? (int)$paymentRecord->joursFeriesCount : 0,
                        'jours_conges' => property_exists($paymentRecord, 'conges_paye_count') && is_numeric($paymentRecord->conges_paye_count) ? (int)$paymentRecord->conges_paye_count : 0,
                        'jours_supp' => property_exists($paymentRecord, 'joursFeriesTravaillesCount') && is_numeric($paymentRecord->joursFeriesTravaillesCount) ? (int)$paymentRecord->joursFeriesTravaillesCount : 0,
                        'prime_deplacement' => property_exists($paymentRecord, 'PrimeDeplacement') && is_numeric($paymentRecord->PrimeDeplacement) ? (float)$paymentRecord->PrimeDeplacement : 0.0,
                        'prime_representation' => property_exists($paymentRecord, 'prime_representation') && is_numeric($paymentRecord->prime_representation) ? (float)$paymentRecord->prime_representation : 0.0,
                        'prime_panier' => property_exists($paymentRecord, 'prime_panier') && is_numeric($paymentRecord->prime_panier) ? (float)$paymentRecord->prime_panier : 0.0,
                        'prime_transport' => property_exists($paymentRecord, 'ind_trans_urbain') && is_numeric($paymentRecord->ind_trans_urbain) ? (float)$paymentRecord->ind_trans_urbain : 0.0,
                        'prime_divers' => property_exists($paymentRecord, 'primesDivers') && is_numeric($paymentRecord->primesDivers) ? (float)$paymentRecord->primesDivers : 0.0,
                        'taux_cimr' => property_exists($paymentRecord, 'taux_CIMR') ? $paymentRecord->taux_CIMR : '0%',
                        'taux_mutuelle' => property_exists($paymentRecord, 'taux_mutuelle') ? $paymentRecord->taux_mutuelle : '0%',
                        'salaire_brut' => is_numeric($paymentRecord->salaireBI) ? (float)$paymentRecord->salaireBI : 0.0,
                        'salaire_net' => is_numeric($paymentRecord->salaireNI) ? (float)$paymentRecord->salaireNI : 0.0,
                        'ir_net' => is_numeric($paymentRecord->irNet) ? (float)$paymentRecord->irNet : 0.0,
                        'solidarite' => property_exists($paymentRecord, 'solidarite') && is_numeric($paymentRecord->solidarite) ? (float)$paymentRecord->solidarite : 0.0,
                        'cotisation_cnss' => is_numeric($paymentRecord->cotisation_cnss) ? (float)$paymentRecord->cotisation_cnss : 0.0,
                        'avance' => property_exists($paymentRecord, 'avancements_sal') && is_numeric($paymentRecord->avancements_sal) ? (float)$paymentRecord->avancements_sal : 0.0,
                        'heures_service' => property_exists($paymentRecord, 'heures_service') && is_numeric($paymentRecord->heures_service) ? (float)$paymentRecord->heures_service : 0.0,
                        'prix_heure' => property_exists($paymentRecord, 'prix_heure') && is_numeric($paymentRecord->prix_heure) ? (float)$paymentRecord->prix_heure : 0.0,
                        'situation_familiale' => $salaryData->situation_familiale ?? 'Célibataire',
                        'nbr_enfants' => $salaryData->nombre_enfant ?? '0',
                        'salaired' => property_exists($paymentRecord, 'salaired') ? $paymentRecord->salaired : '0%',
                        'anciennete' => is_numeric($salaryData->anciennete) ? (float)$salaryData->anciennete : 0,
                        'tauxAnciennete' => property_exists($paymentRecord, 'tauxAnciennete') && is_numeric($paymentRecord->tauxAnciennete) ? (float)$paymentRecord->tauxAnciennete : 0,
                        'prime_anciennete' => property_exists($paymentRecord, 'prime_anciennete') && is_numeric($paymentRecord->prime_anciennete) ? (float)$paymentRecord->prime_anciennete : 0,
                        'prime_journaliere' => property_exists($paymentRecord, 'prime_journaliere') && is_numeric($paymentRecord->prime_journaliere) ? (float)$paymentRecord->prime_journaliere : 0.0,
                        'salaireBG' => property_exists($paymentRecord, 'salaireBG') && is_numeric($paymentRecord->salaireBG) ? (float)$paymentRecord->salaireBG : 0.0,
                        'primNonimposables' => property_exists($paymentRecord, 'primNonimposables') && is_numeric($paymentRecord->primNonimposables) ? (float)$paymentRecord->primNonimposables : 0.0,
                        'cotisation_amo' => property_exists($paymentRecord, 'cotisation_amo') && is_numeric($paymentRecord->cotisation_amo) ? (float)$paymentRecord->cotisation_amo : 0.0,
                        'cotisation_CIMR' => property_exists($paymentRecord, 'cotisation_CIMR') && is_numeric($paymentRecord->cotisation_CIMR) ? (float)$paymentRecord->cotisation_CIMR : 0.0,
                        'cotisation_mutuelle' => property_exists($paymentRecord, 'cotisation_mutuelle') && is_numeric($paymentRecord->cotisation_mutuelle) ? (float)$paymentRecord->cotisation_mutuelle : 0.0,
                        'tauxFrais' => property_exists($paymentRecord, 'tauxFrais') && is_numeric($paymentRecord->tauxFrais) ? (float)$paymentRecord->tauxFrais : 0.0,
                        'professional_tax_deduction' => property_exists($paymentRecord, 'professional_tax_deduction') && is_numeric($paymentRecord->professional_tax_deduction) ? (float)$paymentRecord->professional_tax_deduction : 0.0,
                        'irData' => property_exists($paymentRecord, 'irData') && is_numeric($paymentRecord->irData) ? (float)$paymentRecord->irData : 0.0,
                        'somme_a_deduire' => property_exists($paymentRecord, 'somme_a_deduire') && is_numeric($paymentRecord->somme_a_deduire) ? (float)$paymentRecord->somme_a_deduire : 0.0,
                        'ir_brut' => property_exists($paymentRecord, 'ir_brut') && is_numeric($paymentRecord->ir_brut) ? (float)$paymentRecord->ir_brut : 0.0,
                        'charge_de_famille' => property_exists($paymentRecord, 'charge_de_famille') && is_numeric($paymentRecord->charge_de_famille) ? (float)$paymentRecord->charge_de_famille : 0.0,
                        'salaire' => is_numeric($paymentRecord->salaire) ? (float)$paymentRecord->salaire : 0.0,
                        'hs_25' => property_exists($paymentRecord, 'heurSupp25') && is_numeric($paymentRecord->heurSupp25) ? (float)$paymentRecord->heurSupp25 : 0,
                        'hs_50' => property_exists($paymentRecord, 'heurSupp50') && is_numeric($paymentRecord->heurSupp50) ? (float)$paymentRecord->heurSupp50 : 0,
                        'hs_100' => property_exists($paymentRecord, 'heurSupp100') && is_numeric($paymentRecord->heurSupp100) ? (float)$paymentRecord->heurSupp100 : 0,
                        'daily_salary' => property_exists($paymentRecord, 'daily_salary') && is_numeric($paymentRecord->daily_salary) ? (float)$paymentRecord->daily_salary : 0,
                        'salaireBaseImposable' => property_exists($paymentRecord, 'salaireBaseImposable') && is_numeric($paymentRecord->salaireBaseImposable) ? (float)$paymentRecord->salaireBaseImposable : 0,
                        'joursCalcules' => property_exists($paymentRecord, 'joursCalcules') && is_numeric($paymentRecord->joursCalcules) ? (int)$paymentRecord->joursCalcules : 26,
                    ];
                }

                // Régénérer le PDF groupé
                $newPdfPath = $this->generateBulkPayrollPdf($employeeDataList, $month, $year);

                // Mettre à jour les enregistrements dans pieces_joint pour les autres salariés
                DB::table('pieces_joint')
                    ->where('mois', $month)
                    ->where('annee', $year)
                    ->whereIn('id_salarier', $otherEmployees)
                    ->update(['pj' => $newPdfPath, 'updated_at' => now()]);

                Log::info('PDF groupé régénéré', [
                    'month' => $month,
                    'year' => $year,
                    'new_pdf_path' => $newPdfPath,
                    'employee_count' => count($employeeDataList)
                ]);
            } else {
                // Aucun autre salarié dans le PDF groupé, supprimer le fichier
                $filePath = public_path($bulkPdfPath);
                if (File::exists($filePath) && is_writable($filePath)) {
                    File::delete($filePath);
                    Log::info('PDF groupé supprimé', [
                        'id_salarier' => $idSalarie,
                        'file_path' => $bulkPdfPath,
                        'full_path' => $filePath
                    ]);
                    $filesDeleted++;
                }
            }
        }

        // Supprimer l'enregistrement du salarié dans pieces_joint
        $piecesDeleted = DB::table('pieces_joint')
            ->where('id_salarier', $idSalarie)
            ->where('annee', $year)
            ->where('mois', $month)
            ->delete();

        Log::info('Suppression pieces_joint', [
            'id_salarier' => $idSalarie,
            'year' => $year,
            'month' => $month,
            'matricule' => $matricule,
            'deleted_count' => $piecesDeleted
        ]);

        // Supprimer dans paiement_salaires
        $paiementDeleted = 0;
        if ($paiementCount > 0 || $idPaiment) {
            $paiementDeleted = DB::table('paiement_salaires')
                ->where(function ($query) use ($idSalarie, $year, $monthNumber, $idPaiment) {
                    $query->where('id_salarie', $idSalarie)
                          ->where('annee', $year)
                          ->where('mois', $monthNumber);
                    if ($idPaiment) {
                        $query->orWhere('id', $idPaiment);
                    }
                })
                ->delete();
            Log::info('Suppression paiement_salaires', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $monthNumber,
                'matricule' => $matricule,
                'id_paiment' => $idPaiment,
                'deleted_count' => $paiementDeleted
            ]);
        }

        // Mettre à jour la table salairs_{$year}
        $salaryUpdated = DB::table($tableName)
            ->where('id_salarie', $idSalarie)
            ->update([$month => null, 'updated_at' => now()]);

        Log::info('Mise à jour salairs_{$year}', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month,
            'matricule' => $matricule,
            'table' => $tableName,
            'updated_count' => $salaryUpdated
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Suppression réussie, $filesDeleted fichier(s) supprimé(s)."
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur lors de la suppression du salaire : ' . $e->getMessage(), [
            'id_salarie' => $idSalarie ?? null,
            'year' => $year ?? null,
            'month' => $month ?? null,
            'matricule' => $matricule ?? null,
            'id_paiment' => $idPaiment ?? null,
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur lors de la suppression.'
        ], 500);
    }
}



private function solveBaseSalaryFromNet(float $targetNet, array $ctx): array
{
    $f = fn(float $b) => $this->computeNetFromBase($b, $ctx)['net'];

    // Cas où même un salaire de base = 0 donne déjà un net > cible
    // (souvent à cause des primes non imposables)
    if ($f(0.0) > $targetNet + 0.01) {
        return [
            'error' => "Le salaire net ({$targetNet} MAD) est inférieur aux primes non imposables du mois : calcul impossible."
        ];
    }

    // Recherche de la borne haute
    $lo = 0.0;
    $hi = max($targetNet, 1000.0);
    $guard = 0;
    while ($f($hi) < $targetNet && $guard++ < 40) {
        $lo = $hi;
        $hi *= 2;
    }

    if ($f($hi) < $targetNet) {
        // Même avec un salaire de base très élevé on n'atteint pas le net
        // → on prend quand même la meilleure valeur trouvée
        Log::warning('solveBaseSalaryFromNet: net non atteint même avec base élevée', [
            'target_net' => $targetNet,
            'hi'         => $hi,
            'net_at_hi'  => $f($hi),
        ]);
    }

    // Dichotomie classique
    for ($i = 0; $i < 80 && ($hi - $lo) > 0.0001; $i++) {
        $mid = ($lo + $hi) / 2;
        if ($f($mid) < $targetNet) {
            $lo = $mid;
        } else {
            $hi = $mid;
        }
    }

    // Affinage au centime près
    $base = round(($lo + $hi) / 2, 2);
    $best = null;

    foreach ([-0.05, -0.02, -0.01, 0, 0.01, 0.02, 0.05] as $delta) {
        $cand  = max(0, round($base + $delta, 2));
        $ecart = round($f($cand) - $targetNet, 2);

        if ($best === null || abs($ecart) < abs($best['ecart'])) {
            $best = [
                'base_salary' => $cand,
                'ecart'       => $ecart,
            ];
        }
    }

    // ── PLUS D'ERREUR SI L'ÉCART EST GRAND ───────────────────────────────
    // On log juste un warning et on continue avec le meilleur résultat
    if (abs($best['ecart']) > 0.10) {
        Log::warning('solveBaseSalaryFromNet: net non atteint exactement, on continue avec le meilleur écart', [
            'target_net'  => $targetNet,
            'base_trouve' => $best['base_salary'],
            'ecart'       => $best['ecart'],
        ]);
    }

    return $best;
}


public function incrementSalaryFromNet(Request $request)
{
    $idSalarie = $request->input('id_salarie');
    $year      = $request->input('year');
    $month     = $request->input('month');

    $primPanierInput          = $request->input('prime_panier', 0);
    $indTransUrbainInput      = $request->input('ind_trans_urbain', 0);
    $primeRepresentationInput = $request->input('prime_representation', 0);
    $primeDeplacementInput    = $request->input('prime_deplacement', 0);
    $autresPrimesImposables   = $request->input('autres_primes_imposables', 0);
    $primesDiversInput        = $request->input('primes_divers', 0);

    $applyCimr            = filter_var($request->input('apply_cimr', false), FILTER_VALIDATE_BOOLEAN);
    $doubleSalaryHolidays = filter_var($request->input('double_salary_holidays', false), FILTER_VALIDATE_BOOLEAN);
    $applyMutuelle        = filter_var($request->input('apply_mutuelle', false), FILTER_VALIDATE_BOOLEAN);
    $calculateOvertime    = filter_var($request->input('calculate_overtime', false), FILTER_VALIDATE_BOOLEAN);
    $applyFraisPro        = filter_var($request->input('apply_frais_pro', true), FILTER_VALIDATE_BOOLEAN);
    $applyIpe             = filter_var($request->input('apply_ipe', false), FILTER_VALIDATE_BOOLEAN);
    $applyPrimeRendement  = filter_var($request->input('apply_prime_rendement', true), FILTER_VALIDATE_BOOLEAN);

    Log::info('incrementSalaryFromNet called', ['id_salarie' => $idSalarie, 'year' => $year, 'month' => $month]);

    if (!$idSalarie || !$year || !$month) {
        return response()->json(['error' => 'ID salarié, année ou mois manquant.'], 400);
    }
    if (!is_numeric($primPanierInput) || $primPanierInput < 0 || $primPanierInput > 800) {
        return response()->json(['error' => 'La prime de panier doit être un nombre entre 0 et 800 MAD.'], 400);
    }
    if (!is_numeric($indTransUrbainInput) || $indTransUrbainInput < 0 || $indTransUrbainInput > ($request->is_urban ? 700 : 500)) {
        return response()->json(['error' => 'L\'indemnité de transport doit être un nombre entre 0 et ' . ($request->is_urban ? 700 : 500) . ' MAD.'], 400);
    }

    $primPanierInput          = (float) $primPanierInput;
    $indTransUrbainInput      = (float) $indTransUrbainInput;
    $primeRepresentationInput = (float) $primeRepresentationInput;
    $primeDeplacementInput    = (float) $primeDeplacementInput;
    $autresPrimesImposables   = (float) $autresPrimesImposables;
    $primesDiversInput        = (float) $primesDiversInput;

    $monthMap = [
        'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
        'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
        'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
    ];
    if (!array_key_exists($month, $monthMap)) {
        return response()->json(['error' => "Mois invalide : {$month}."], 400);
    }
    $monthNumber = $monthMap[$month];

    $tableName = "salairs_{$year}";
    if (!Schema::hasTable($tableName)) {
        return response()->json(['error' => "Table salairs_{$year} n'existe pas."], 404);
    }

    try {
        // ── Salarié ───────────────────────────────────────────────────────────
        $employeeData = DB::table('salaries')
            ->where('id', $idSalarie)
            ->select('anciennete', 'situation_familiale', 'nombre_enfant', 'type_contrat', 'statut', 'n_matricule_entreprise')
            ->first();
        if (!$employeeData) {
            return response()->json(['error' => "Salarié ID {$idSalarie} non trouvé dans la table salaries."], 404);
        }
        $matricule = $employeeData->n_matricule_entreprise ?? 'N/A';
        $isAnapec  = strtolower($employeeData->type_contrat) === 'anapec';

        $salaryData = DB::table($tableName)->where('id_salarie', $idSalarie)->first();
        if (!$salaryData) {
            return response()->json(['error' => "Salarié avec matricule {$matricule} non trouvé."], 404);
        }
        if (!isset($salaryData->salaire_net) || !is_numeric($salaryData->salaire_net) || (float) $salaryData->salaire_net <= 0) {
            return response()->json(['error' => "Aucun salaire net défini pour le salarié avec matricule {$matricule}."], 400);
        }
        $salaireNetCible = (float) $salaryData->salaire_net; // net FINAL à payer

        $existingPayment = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)->where('annee', $year)->where('mois', $monthNumber)->first();
        if ($existingPayment) {
            return response()->json(['error' => "Paiement déjà enregistré pour {$month} {$year}."], 400);
        }

        $startOfMonth = sprintf('%d-%02d-01', $year, $monthNumber);
        $endOfMonth   = date('Y-m-t', strtotime($startOfMonth));

        // Helper : chevauchement d'une période avec le mois
        $overlapMonth = function ($q, string $debut = 'date_debut', string $fin = 'date_fin') use ($startOfMonth, $endOfMonth) {
            $q->whereBetween($debut, [$startOfMonth, $endOfMonth])
              ->orWhereBetween($fin, [$startOfMonth, $endOfMonth])
              ->orWhere(function ($q2) use ($debut, $fin, $startOfMonth, $endOfMonth) {
                  $q2->where($debut, '<=', $startOfMonth)->where($fin, '>=', $endOfMonth);
              });
        };

        // ── Heures supplémentaires ───────────────────────────────────────────
        $sumHeures = function (?string $type, ?bool $ferie) use ($idSalarie, $startOfMonth, $endOfMonth) {
            $q = DB::table('presence')
                ->where('salarie_id', $idSalarie)->where('statuts', 1)
                ->whereBetween('date', [$startOfMonth, $endOfMonth]);
            if ($type) {
                $q->where('type_heure_supp', $type);
            }
            if ($ferie !== null) {
                $sub = function ($s) {
                    $s->select(DB::raw(1))->from('jour_feries')
                      ->whereRaw('presence.date >= jour_feries.date_debut')
                      ->whereRaw('presence.date <= jour_feries.date_fin');
                };
                $ferie ? $q->whereExists($sub) : $q->whereNotExists($sub);
            }
            return (float) $q->sum('heures');
        };

        $heurSuppPresence = $heurSuppPresencematin = $heurSuppPresenceNuit = 0.0;
        $heurSuppPresencematinF = $heurSuppPresenceNuitF = 0.0;
        if ($calculateOvertime) {
            $heurSuppPresence       = $sumHeures(null, null);
            $heurSuppPresencematin  = $sumHeures('matin', false);
            $heurSuppPresenceNuit   = $sumHeures('nuit', false);
            $heurSuppPresenceNuitF  = $sumHeures('nuit', true);
            $heurSuppPresencematinF = $sumHeures('matin', true);
        }

        // ── Congé approuvé (message d'information) ───────────────────────────
        $leaveData = DB::table('conger')
            ->where('salarie_id', $idSalarie)->where('approbation', 2)
            ->where(function ($q) use ($overlapMonth) { $overlapMonth($q); })
            ->first();

        // ── Jours fériés ─────────────────────────────────────────────────────
        $joursFeries = DB::table('jour_feries')
            ->where(function ($q) use ($overlapMonth) { $overlapMonth($q); })
            ->get();

        $joursFeriesCount = 0;
        foreach ($joursFeries as $ferie) {
            $d = new \DateTime($ferie->date_debut);
            $f = new \DateTime($ferie->date_fin ?? $ferie->date_debut);
            while ($d <= $f) {
                if ($d->format('Y-m') === substr($startOfMonth, 0, 7) && $d->format('w') != 0) {
                    $joursFeriesCount++;
                }
                $d->modify('+1 day');
            }
        }

        $joursFeriesTravaillesCount = DB::table('jour_feries')
            ->join('presence', function ($join) use ($idSalarie, $startOfMonth, $endOfMonth) {
                $join->on(DB::raw('presence.date'), '>=', DB::raw('jour_feries.date_debut'))
                    ->on(DB::raw('presence.date'), '<=', DB::raw('jour_feries.date_fin'))
                    ->where('presence.salarie_id', $idSalarie)
                    ->where('presence.statuts', 1)
                    ->whereBetween('presence.date', [$startOfMonth, $endOfMonth]);
            })
            ->where(function ($q) use ($overlapMonth) { $overlapMonth($q, 'jour_feries.date_debut', 'jour_feries.date_fin'); })
            ->sum('jour_feries.nbr_jours');

        $joursFeriesTravaillesCount = (int) min($joursFeriesTravaillesCount, $joursFeriesCount);
        $joursFeriesCount           = (int) max(0, $joursFeriesCount - $joursFeriesTravaillesCount);

        // ── Démission dans le mois ───────────────────────────────────────────
        $resignationData = DB::table('demissions')
            ->where('salarie_id', $idSalarie)
            ->where('date_demission', '>=', $startOfMonth)
            ->where('date_demission', '<=', $endOfMonth)
            ->select('date_demission')->first();
        $isResigned = strtolower($employeeData->statut) === 'inactif' && $resignationData;

        if ($isResigned) {
            $dem = $resignationData->date_demission;

            $ferieRes = DB::table('jour_feries')
                ->where(function ($q) use ($startOfMonth, $dem) {
                    $q->whereBetween('date_debut', [$startOfMonth, $dem])
                      ->orWhereBetween('date_fin', [$startOfMonth, $dem])
                      ->orWhere(function ($q2) use ($startOfMonth, $dem) {
                          $q2->where('date_debut', '<=', $startOfMonth)->where('date_fin', '>=', $dem);
                      });
                })->sum('nbr_jours');

            $ferieTravRes = DB::table('jour_feries')
                ->join('presence', function ($join) use ($idSalarie, $startOfMonth, $dem) {
                    $join->on(DB::raw('presence.date'), '>=', DB::raw('jour_feries.date_debut'))
                        ->on(DB::raw('presence.date'), '<=', DB::raw('jour_feries.date_fin'))
                        ->where('presence.salarie_id', $idSalarie)
                        ->where('presence.statuts', 1)
                        ->whereBetween('presence.date', [$startOfMonth, $dem]);
                })
                ->where(function ($q) use ($startOfMonth, $dem) {
                    $q->whereBetween('jour_feries.date_debut', [$startOfMonth, $dem])
                      ->orWhereBetween('jour_feries.date_fin', [$startOfMonth, $dem])
                      ->orWhere(function ($q2) use ($startOfMonth, $dem) {
                          $q2->where('date_debut', '<=', $startOfMonth)->where('date_fin', '>=', $dem);
                      });
                })->sum('jour_feries.nbr_jours');

            $joursFeriesCount           = (int) max(0, $ferieRes - $ferieTravRes);
            $joursFeriesTravaillesCount = (int) min($ferieTravRes, $ferieRes);
        }

        // ── Congés payés du mois ─────────────────────────────────────────────
        $congesPayeCount = 0;
        $conges = DB::table('conger')
            ->where('salarie_id', $idSalarie)->where('approbation', 2)
            ->where(function ($q) use ($overlapMonth) { $overlapMonth($q); })
            ->get(['date_debut', 'date_fin', 'num_j']);

        foreach ($conges as $conge) {
            $debut = max(new \DateTime($conge->date_debut), new \DateTime($startOfMonth));
            $fin   = min(new \DateTime($conge->date_fin), new \DateTime($endOfMonth));
            if ($debut <= $fin) {
                $congesPayeCount += min((int) $debut->diff($fin)->days + 1, (int) $conge->num_j);
            }
        }
        $congesPayeCount = (int) $congesPayeCount;

        // ── Jours de présence ────────────────────────────────────────────────
        $joursPresence = max(0, 26 - ($joursFeriesCount + $joursFeriesTravaillesCount + $congesPayeCount));
        if ($joursPresence == 0 && $congesPayeCount == 0) {
            return response()->json(['error' => "Aucune présence ni congé payé enregistré pour le salarié avec matricule {$matricule} pour {$month} {$year}."], 400);
        }
        $joursCalcules = 26;

        // ── Primes (mêmes formules que incrementSalary) ──────────────────────
        $primPanier                       = round(($primPanierInput / 26) * $joursCalcules, 2);
        $indTransUrbain                   = round(($indTransUrbainInput / 26) * $joursCalcules, 2);
        $primeRepresentation              = round(($primeRepresentationInput / 26) * $joursCalcules, 2);
        $primeDeplacement                 = round(($primeDeplacementInput / 26) * $joursCalcules, 2);
        $autresPrimesImposablesCalculated = round(($autresPrimesImposables / 26) * $joursCalcules, 2);
        $primesDivers                     = round(($primesDiversInput / 26) * $joursCalcules, 2);
        $primNonimposables                = round($primPanier + $indTransUrbain + $primeRepresentation + $primeDeplacement + $primesDivers, 2);

        // ── Sauvegarde de la configuration ───────────────────────────────────
        DB::table('configurationbp')->updateOrInsert(
            ['id_salarier' => $idSalarie],
            [
                'pripanier'              => $primPanierInput,
                'indtransport'           => $indTransUrbainInput,
                'prirepresentation'      => $primeRepresentationInput,
                'prideplacement'         => $primeDeplacementInput,
                'pridivers'              => $primesDiversInput,
                'autrespriimposables'    => $autresPrimesImposables,
                'cotisation_cimr'        => $applyCimr ? 1 : 0,
                'indemnite_pe'           => $applyIpe ? 1 : 0,
                'calculate_overtime'     => $calculateOvertime ? 1 : 0,
                'cotisation_mutuelle'    => $applyMutuelle ? 1 : 0,
                'apply_frais_pro'        => $applyFraisPro ? 1 : 0,
                'apply_prime_rendement'  => $applyPrimeRendement ? 1 : 0,
                'double_salary_holidays' => $doubleSalaryHolidays ? 1 : 0,
                'date_creation'          => now(),
                'date_modification'      => now(),
            ]
        );

        // ── Données nécessaires au calcul inverse ────────────────────────────
        $tauxAnciennete = 0;
        if (!is_null($employeeData->anciennete)) {
            $tauxRow = AncienneteTaux::where('an_min', '<=', $employeeData->anciennete)
                ->where(function ($q) use ($employeeData) {
                    $q->where('an_max', '>=', $employeeData->anciennete)->orWhereNull('an_max');
                })->first();
            $tauxAnciennete = $tauxRow ? (float) $tauxRow->taux : 0;
        }

        $joursCongesAvecPresence      = 0;
        $congesPayeNonTravaillesCount = 0;
        if ($congesPayeCount > 0) {
            $joursCongesAvecPresence = DB::table('presence')
                ->where('salarie_id', $idSalarie)->where('statuts', 1)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->whereExists(function ($query) use ($idSalarie, $endOfMonth) {
                    $query->select(DB::raw(1))->from('conger')
                        ->where('conger.salarie_id', $idSalarie)
                        ->where('conger.approbation', 2)
                        ->whereRaw('presence.date >= conger.date_debut')
                        ->whereRaw('presence.date <= LEAST(conger.date_fin, ?)', [$endOfMonth]);
                })->count();
            $congesPayeNonTravaillesCount = max(0, $congesPayeCount - $joursCongesAvecPresence);
        }

        $cotisations  = null;
        $chargeFamRev = 0;
        if (!$isAnapec) {
            $cotisations = Cotisations::first();
            if (!$cotisations) {
                return response()->json(['error' => 'Aucune donnée de cotisation trouvée.'], 400);
            }
            if (strtolower($employeeData->situation_familiale) === 'marié' && !is_null($cotisations->charge_de_famille)) {
                $chargeFamRev = $cotisations->charge_de_famille * (1 + min($employeeData->nombre_enfant ?? 0, 5));
            }
        }

        $ctx = [
            'isAnapec'                => $isAnapec,
            'cotisations'             => $cotisations,
            'fraisTranches'           => DB::table('frais_professionnels')->get(),
            'irTranches'              => ImpotSurRevenu::all(),
            'applyCimr'               => $applyCimr,
            'applyMutuelle'           => $applyMutuelle,
            'applyIpe'                => $applyIpe,
            'applyFraisPro'           => $applyFraisPro,
            'calculateOvertime'       => $calculateOvertime,
            'heurSuppPresence'        => $heurSuppPresence,
            'heurSuppMatin'           => $heurSuppPresencematin,
            'heurSuppNuit'            => $heurSuppPresenceNuit,
            'heurSuppMatinF'          => $heurSuppPresencematinF,
            'heurSuppNuitF'           => $heurSuppPresenceNuitF,
            'tauxAnciennete'          => $tauxAnciennete,
            'autresPrimesImposables'  => $autresPrimesImposablesCalculated,
            'primNonimposables'       => $primNonimposables,
            'joursCongesAvecPresence' => $joursCongesAvecPresence,
            'chargeFamiliale'         => $chargeFamRev,
        ];

        // ── Avancements de salaire (déduits du net final) ────────────────────
        $avancement = Depences::where('salarie_id', $idSalarie)
            ->whereHas('natureDepense', function ($query) {
                $query->where('designation', 'Avancements de salaires');
            })
            ->where('mois_depenses', sprintf('%02d', $monthNumber))
            ->whereYear('date', $year)
            ->sum('montant');

        // salaire_net = net FINAL (après avancement)
        // donc net avant avancement = salaire_net + avancement
        $netAvantAvancement = round($salaireNetCible + (float) $avancement, 2);

        // ── Calcul inverse : net → salaire de base ───────────────────────────
        $reverse = $this->solveBaseSalaryFromNet($netAvantAvancement, $ctx);
        if (isset($reverse['error'])) {
            Log::warning('Reverse calculation failed', ['id_salarie' => $idSalarie, 'error' => $reverse['error']]);
            return response()->json(['error' => $reverse['error']], 400);
        }
        $baseSalary = $reverse['base_salary'];
        $ecartNet   = $reverse['ecart'];

        // ── Calcul direct avec le salaire de base retrouvé ───────────────────
        $r = $this->computeNetFromBase($baseSalary, $ctx);

        $dailySalary = $baseSalary / 26;
        $congesPaye  = round($congesPayeCount * $dailySalary, 2);
        $jourFerier  = round($joursFeriesCount * $dailySalary, 2);
        $joursFeriesTravailles = $doubleSalaryHolidays
            ? round($joursFeriesTravaillesCount * $dailySalary * 2, 2) : 0;
        $salaired = $joursPresence > 26 ? round($dailySalary * 26) : round($dailySalary * $joursPresence);

        $congesPayeTravailles    = round($joursCongesAvecPresence * $dailySalary, 2);
        $congesPayeNonTravailles = round($congesPayeNonTravaillesCount * $dailySalary, 2);

        $prime_journaliere      = 0;
        $base_prime_journaliere = $joursPresence + $joursFeriesCount + $congesPayeCount + $joursFeriesTravaillesCount;
        $taux_prime_journaliere = $base_prime_journaliere > 26 ? $base_prime_journaliere - 26 : 0;
        if ($applyPrimeRendement) {
            $prime_journaliere = round($taux_prime_journaliere * 1.25 * $base_prime_journaliere, 2);
        }

        $salaireBI = $r['bi'];
        $salaireNI = $r['ni'];
        $salaireBG = round($r['sbi'] + $r['primeAnc'] + $autresPrimesImposablesCalculated + $primNonimposables, 2);
        $irBrut    = $r['irBrut'];
        $irNet     = $r['irNet'];

        // Le net à payer du bulletin = exactement le salaire_net de la table
        // ($r['net'] - $avancement ne diffère de salaire_net que de quelques centimes d'arrondi)
        $netPayer           = round($salaireNetCible, 2);
        $netPayerSansExtras = round($salaireBI - ($r['cnss'] + $r['amo'] + $r['ipe'] + $r['cimr'] + $irNet), 2);

        Log::info('Reverse calc result', [
            'id_salarie'          => $idSalarie,
            'net_cible_final'     => $salaireNetCible,
            'avancement'          => $avancement,
            'net_avant_avancement'=> $netAvantAvancement,
            'base_trouve'         => $baseSalary,
            'ecart'               => $ecartNet,
            'net_payer'           => $netPayer,
        ]);

        // ── Insertion ────────────────────────────────────────────────────────
        $paymentData = [
            'id_salarie' => $idSalarie, 'annee' => $year, 'mois' => $monthNumber,
            'salaire' => $netPayer, 'typer' => 'a', 'statutspj' => 0,
            'created_at' => now(), 'updated_at' => now(),
            'prime_panier' => $primPanier,
            'ind_trans_urbain' => $indTransUrbain,
            'ind_transUrbainInput' => $indTransUrbainInput,
            'prime_representation' => $primeRepresentation,
            'PrimeDeplacement' => $primeDeplacement,
            'autresPrimesImposables' => $autresPrimesImposablesCalculated,
            'primesDivers' => $primesDivers,
            'primesDiversInput' => $primesDiversInput,
            'primPanierInput' => $primPanierInput,
            'prime_representation_input' => $primeRepresentationInput,
            'PrimeDeplacementInput' => $primeDeplacementInput,
            'charge_de_famille' => $chargeFamRev,
            'net_payer_sans_extras' => $netPayerSansExtras,
            'ir_brut' => $irBrut,
            'cotisation_amo' => $r['amo'],
            'cotisation_cnss' => $r['cnss'],
            'cotisation_cimr' => $r['cimr'],
            'cotisation_mutuelle' => $r['mut'],
            'prime_anciennete' => $r['primeAnc'],
            'professional_tax_deduction' => $r['frais'],
            'daily_salary' => $dailySalary,
            'indemnite_pe' => $r['ipe'],
            'salaired' => $salaired,
            'jour_ferie' => $jourFerier,
            'jours_feries_travailles' => $joursFeriesTravailles,
            'conges_paye' => $congesPaye,
            'conges_paye_count' => $congesPayeCount,
            'joursFeriesCount' => $joursFeriesCount,
            'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount,
            'prime_journaliere' => $prime_journaliere,
            'base_prime_journaliere' => $base_prime_journaliere,
            'taux_prime_journaliere' => $taux_prime_journaliere,
            'salaireBG' => $salaireBG,
            'salaireNI' => $salaireNI,
            'salaireBI' => $salaireBI,
            'irNet' => $irNet,
            'num_jours' => $joursPresence,
            'tauxAnciennete' => $tauxAnciennete,
            'cnss_ps' => $r['cnssPs'],
            'amo_ps' => $r['amoPs'],
            'ipe_ps' => $r['ipePs'],
            'tauxFrais' => $r['tauxFrais'],
            'taux_cimr' => $r['tauxCimr'],
            'taux_mutuelle' => $r['tauxMut'],
            'irData' => $r['irTaux'],
            'avancements_sal' => (float) $avancement,
            'primNonimposables' => $primNonimposables,
            'somme_a_deduire' => $r['sommeADeduire'],
            'heurSuppPresence' => $heurSuppPresence,
            'heurSuppPresencematin' => $heurSuppPresencematin,
            'heurSuppPresenceNuit' => $heurSuppPresenceNuit,
            'heurSuppPresencematinF' => $heurSuppPresencematinF,
            'heurSuppPresenceNuitF' => $heurSuppPresenceNuitF,
            'tauxHeureSupp' => $r['tauxHS'],
            'heurSupp25' => $r['hs25'],
            'heurSupp50' => $r['hs50'],
            'heurSupp100' => $r['hs100'],
            'salaireBaseImposable' => $r['sbi'],
            'joursCalcules' => $joursCalcules,
            'joursCongesAvecPresence' => $joursCongesAvecPresence,
            'congesPayeTravailles' => $congesPayeTravailles,
            'congesPayeNonTravaillesCount' => $congesPayeNonTravaillesCount,
            'congesPayeNonTravailles' => $congesPayeNonTravailles,
        ];

        DB::beginTransaction();
        try {
            $paymentId = DB::table('paiement_salaires')->insertGetId($paymentData);

            if (!Schema::hasColumn($tableName, $month)) {
                DB::rollBack();
                return response()->json(['error' => "La colonne {$month} n'existe pas dans la table {$tableName}."], 400);
            }

            // 1. Mise à jour table annuelle
            DB::table($tableName)->where('id_salarie', $idSalarie)
                ->update([
                    $month       => $paymentId,
                    'salaire'    => $baseSalary,
                    'updated_at' => now(),
                ]);

            // 2. Mise à jour table principale salaries
            DB::table('salaries')
                ->where('id', $idSalarie)
                ->update([
                    'salaire_base'       => $baseSalary,
                    'salaire_journalier' => $dailySalary,
                    'updated_at'         => now(),
                ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to insert salary payment (net mode)', [
                'id_salarie' => $idSalarie,
                'error'      => $e->getMessage()
            ]);
            return response()->json(['error' => 'Erreur lors de l\'enregistrement du paiement : ' . $e->getMessage()], 500);
        }

        // ── Détails (même structure que incrementSalary, lus par generatePaySlipPDF) ──
        $details = [
            'base_salary' => $baseSalary,
            'id_salarie' => $idSalarie, 'annee' => $year, 'mois' => $monthNumber,
            'salaire' => $netPayer, 'typer' => 'a', 'statutspj' => 0,
            'prime_panier' => $primPanier,
            'ind_trans_urbain' => $indTransUrbain,
            'prime_representation' => $primeRepresentation,
            'PrimeDeplacement' => $primeDeplacement,
            'autresPrimesImposables' => $autresPrimesImposablesCalculated,
            'primesDivers' => $primesDivers,
            'charge_de_famille' => $chargeFamRev,
            'net_payer_sans_extras' => $netPayerSansExtras,
            'ir_brut' => $irBrut,
            'cotisation_amo' => $r['amo'],
            'cotisation_cimr' => $r['cimr'],
            'prime_anciennete' => $r['primeAnc'],
            'professional_tax_deduction' => $r['frais'],
            'daily_salary' => $dailySalary,
            'indemnite_pe' => $r['ipe'],
            'salaired' => $salaired,
            'jour_ferie' => $jourFerier,
            'jours_feries_travailles' => $joursFeriesTravailles,
            'conges_paye' => $congesPaye,
            'conges_paye_count' => $congesPayeCount,
            'joursFeriesCount' => $joursFeriesCount,
            'joursFeriesTravaillesCount' => $joursFeriesTravaillesCount,
            'prime_journaliere' => $prime_journaliere,
            'base_prime_journaliere' => $base_prime_journaliere,
            'taux_prime_journaliere' => $taux_prime_journaliere,
            'salaireBG' => $salaireBG,
            'salaireNI' => $salaireNI,
            'salaireBI' => $salaireBI,
            'irNet' => $irNet,
            'cotisation_cnss' => $r['cnss'],
            'num_jours' => $joursPresence,
            'tauxAnciennete' => $tauxAnciennete,
            'cnss_ps' => $r['cnssPs'],
            'amo_ps' => $r['amoPs'],
            'ipe_ps' => $r['ipePs'],
            'tauxFrais' => $r['tauxFrais'],
            'taux_cimr' => $r['tauxCimr'],
            'irData' => $r['irTaux'],
            'avancements_sal' => (float) $avancement,
            'primNonimposables' => $primNonimposables,
            'net_payer' => $netPayer,
            'heurSuppPresence' => $heurSuppPresence,
            'heurSuppPresencematin' => $heurSuppPresencematin,
            'heurSuppPresenceNuit' => $heurSuppPresenceNuit,
            'conges_paye_non_travailles' => $congesPayeNonTravailles,
            'conges_paye_non_travailles_count' => $congesPayeNonTravaillesCount,
            'joursCongesAvecPresence' => $joursCongesAvecPresence,
            'congesPayeTravailles' => $congesPayeTravailles,
        ];

        $leaveMessage = $leaveData
            ? "Le salarié est en congé du {$leaveData->date_debut} au {$leaveData->date_fin}. Les primes et indemnités non imposables sont mises à zéro."
                    : null;

                return response()->json([
                    'success'              => true,
                    'details'              => $details,
                    'leave_message'        => $leaveMessage,
                    'calc_mode'            => 'salaire_net',
                    'salaire_net_cible'    => $salaireNetCible,
                    'net_avant_avancement' => $netAvantAvancement,
                    'ecart_net'            => $ecartNet,
                ], 200);

            } catch (\Exception $e) {
                Log::error('Unexpected error in incrementSalaryFromNet', [
                    'id_salarie' => $idSalarie, 'error' => $e->getMessage(), 'stack' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => 'Erreur inattendue : ' . $e->getMessage()], 500);
            }
}


private function computeNetFromBase(float $base, array $c): array
{
    $daily = $base / 26;

    // Heures supplémentaires
    $tauxHS = ($c['calculateOvertime'] && $c['heurSuppPresence'] != 0) ? round($base / 191, 2) : 0;
    $hs25   = ($c['calculateOvertime'] && $c['heurSuppMatin'] != 0)
        ? round($c['heurSuppMatin'] * $tauxHS * 1.25, 2) : 0;
    $hs50   = ($c['calculateOvertime'] && ($c['heurSuppMatinF'] + $c['heurSuppNuit']) != 0)
        ? round(($c['heurSuppMatinF'] + $c['heurSuppNuit']) * ($tauxHS * 1.5), 2) : 0;
    $hs100  = ($c['calculateOvertime'] && $c['heurSuppNuitF'] != 0)
        ? round($c['heurSuppNuitF'] * $tauxHS * 2, 2) : 0;

    // Salaire base imposable -> salaire brut imposable
    $sbi      = round(round($daily * 26, 2) + $hs25 + $hs50 + $hs100, 2);
    $primeAnc = round($sbi * ($c['tauxAnciennete'] / 100), 2);
    $bi       = round($sbi + $primeAnc + $c['autresPrimesImposables'], 2);
    if ($c['joursCongesAvecPresence'] > 0) {
        $bi = round($bi + round($c['joursCongesAvecPresence'] * $daily, 2), 2);
    }

    $cnss = $amo = $cimr = $mut = $ipe = $frais = 0.0;
    $cnssPs = $amoPs = $ipePs = $tauxCimr = $tauxMut = $tauxFrais = 0.0;
    $irBrut = $irNet = $irTaux = $sommeADeduire = 0.0;
    $ni = $bi;

    if (!$c['isAnapec']) {
        $cot = $c['cotisations'];

        if (!is_null($cot->cnss_ps)) {
            $cnssPs  = (float) $cot->cnss_ps;
            $plafond = (float) ($cot->plafond_cnss ?? 0);
            $cnss = $bi > $plafond
                ? round($plafond * ($cnssPs / 100), 2)
                : round($bi * ($cnssPs / 100), 2);
        }
        if (!is_null($cot->amo_ps)) {
            $amoPs = (float) $cot->amo_ps;
            $amo   = round($bi * ($amoPs / 100), 2);
        }
        if ($c['applyCimr'] && !is_null($cot->taux_cimr)) {
            $tauxCimr = (float) $cot->taux_cimr;
            $cimr     = round($bi * ($tauxCimr / 100), 2);
        }
        if ($c['applyMutuelle'] && !is_null($cot->taux_mutuelle)) {
            $tauxMut = (float) $cot->taux_mutuelle;
            $mut     = round($bi * ($tauxMut / 100), 2);
        }
        if ($c['applyIpe'] && !is_null($cot->ipe_ps) && !is_null($cot->plafond_ipe)) {
            $ipePs = (float) $cot->ipe_ps;
            $ipe   = round(min($bi, $cot->plafond_ipe) * ($ipePs / 100), 2);
        }
        if ($c['applyFraisPro']) {
            foreach ($c['fraisTranches'] as $t) {
                if ($t->sbi_min <= $bi && (is_null($t->sbi_max) || $t->sbi_max >= $bi)) {
                    $tauxFrais = (float) $t->taux;
                    $frais     = round(min($bi * ($tauxFrais / 100), (float) $t->plafond), 2);
                    break;
                }
            }
        }

        $ni = round($bi - $cnss - $amo - $ipe - $cimr - $mut - $frais, 2);

        foreach ($c['irTranches'] as $t) {
            if ($t->revenu_min <= ($ni) && (is_null($t->revenu_max) || $t->revenu_max >= $ni)) {
                $irTaux        = (float) $t->taux;
                $sommeADeduire = (float) $t->somme_a_deduire;
                $irBrut        = round(($ni * ($irTaux / 100)) - $sommeADeduire, 2);
                break;
            }
        }

        $charge = $c['chargeFamiliale'];
        if ($charge == 0)          $irNet = $irBrut;
        elseif ($irBrut > $charge) $irNet = round($irBrut - $charge, 2);
        else                       $irNet = 0.0;
    }

    $net = round($bi + $c['primNonimposables'] - ($cnss + $amo + $ipe + $cimr + $mut + $irNet), 2);

    return [
        'net' => $net, 'daily' => $daily,
        'tauxHS' => $tauxHS, 'hs25' => $hs25, 'hs50' => $hs50, 'hs100' => $hs100,
        'sbi' => $sbi, 'primeAnc' => $primeAnc, 'bi' => $bi, 'ni' => $ni,
        'cnss' => $cnss, 'amo' => $amo, 'cimr' => $cimr, 'mut' => $mut, 'ipe' => $ipe, 'frais' => $frais,
        'cnssPs' => $cnssPs, 'amoPs' => $amoPs, 'ipePs' => $ipePs,
        'tauxCimr' => $tauxCimr, 'tauxMut' => $tauxMut, 'tauxFrais' => $tauxFrais,
        'irBrut' => $irBrut, 'irNet' => $irNet, 'irTaux' => $irTaux, 'sommeADeduire' => $sommeADeduire,
    ];
}

   
public function downloadPaySlipCachet(Request $request)
{
    try {
        $idSalarie = $request->input('id_salarie');
        $year = $request->input('year');
        $month = trim(strtolower($request->input('month')));

        Log::info('downloadPaySlipCachet called', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month
        ]);

        if (!$idSalarie || !$year || !$month) {
            return response()->json(['error' => 'ID salarié, année ou mois manquant.'], 400);
        }

        $monthMap = [
            'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
        ];

        if (!isset($monthMap[$month])) {
            Log::error('Invalid month', ['month' => $month]);
            return response()->json(['error' => 'Mois invalide.'], 400);
        }
        $monthNumber = $monthMap[$month];

        // Vérifier si le paiement existe
        $payment = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)
            ->where('annee', $year)
            ->where('mois', $monthNumber)
            ->first();

        if (!$payment) {
            Log::error('No payment found', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month
            ]);
            return response()->json(['error' => 'Aucun paiement trouvé pour ce mois.'], 404);
        }

        // Vérifier si le bulletin cacheté existe dans pieces_joint
        $pieceJointe = DB::table('pieces_joint')
            ->where('id_paiment', $payment->id)
            ->where('id_salarier', $idSalarie)
            ->where('mois', $month)
            ->where('annee', $year)
            ->first();

        if (!$pieceJointe || empty($pieceJointe->bultin_paie_ca)) {
            Log::error('No stamped pay slip found in pieces_joint', [
                'id_salarie' => $idSalarie,
                'id_paiment' => $payment->id,
                'month' => $month,
                'year' => $year,
                'piece_jointe' => $pieceJointe
            ]);
            return response()->json(['error' => 'Aucun bulletin de paie cacheté trouvé.'], 404);
        }

        $filePath = public_path($pieceJointe->bultin_paie_ca);

        if (!file_exists($filePath)) {
            Log::error('Stamped pay slip file does not exist', [
                'id_salarie' => $idSalarie,
                'file_path' => $filePath,
                'bultin_paie_ca' => $pieceJointe->bultin_paie_ca
            ]);
            return response()->json(['error' => 'Le fichier du bulletin de paie cacheté n\'existe pas.'], 404);
        }

        $fileName = "bulletin_paie_cachete_{$idSalarie}_{$month}_{$year}.pdf";
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\""
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur lors du téléchargement du bulletin de paie cacheté', [
            'id_salarie' => $idSalarie ?? null,
            'year' => $year ?? null,
            'month' => $month ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Erreur lors du téléchargement du bulletin cacheté : ' . $e->getMessage()], 500);
    }
}

public function downloadQuittanceCah(Request $request)
{
    try {
        $idSalarie = $request->input('id_salarie');
        $year = $request->input('year');
        $month = trim(strtolower($request->input('month')));

        Log::info('downloadQuittanceCah called', [
            'id_salarie' => $idSalarie,
            'year' => $year,
            'month' => $month
        ]);

        if (!$idSalarie || !$year || !$month) {
            return response()->json(['error' => 'ID salarié, année ou mois manquant.'], 400);
        }

        $monthMap = [
            'janvier' => '01', 'fevrier' => '02', 'mars' => '03', 'avril' => '04',
            'mai' => '05', 'juin' => '06', 'juillet' => '07', 'aout' => '08',
            'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'decembre' => '12'
        ];

        if (!array_key_exists($month, $monthMap)) {
            Log::error('Invalid month', ['month' => $month]);
            return response()->json(['error' => "Mois invalide : {$month}."], 400);
        }
        $monthNumber = $monthMap[$month];

        // Vérifier l'enregistrement de paiement
        $payment = DB::table('paiement_salaires')
            ->where('id_salarie', $idSalarie)
            ->where('annee', $year)
            ->where('mois', $monthNumber)
            ->first();

        if (!$payment) {
            Log::error('No payment found', [
                'id_salarie' => $idSalarie,
                'year' => $year,
                'month' => $month
            ]);
            return response()->json(['error' => 'Aucun paiement trouvé pour ce mois.'], 404);
        }

        // Vérifier l'enregistrement dans pieces_joint
        $pieceJointe = DB::table('pieces_joint')
            ->where('id_paiment', $payment->id)
            ->where('id_salarier', $idSalarie)
            ->where('mois', $month)
            ->where('annee', $year)
            ->first();

        if (!$pieceJointe || empty($pieceJointe->quittance_cah)) {
            Log::error('No quittance_cah found in pieces_joint', [
                'id_salarie' => $idSalarie,
                'id_paiment' => $payment->id,
                'month' => $month,
                'year' => $year,
                'piece_jointe' => $pieceJointe
            ]);
            return response()->json(['error' => 'Aucune quittance cachetée trouvée.'], 404);
        }

        $filePath = public_path($pieceJointe->quittance_cah);

        if (!file_exists($filePath)) {
            Log::error('Quittance_cah file does not exist', [
                'id_salarie' => $idSalarie,
                'file_path' => $filePath,
                'quittance_cah' => $pieceJointe->quittance_cah
            ]);
            return response()->json(['error' => 'Le fichier de la quittance cachetée n\'existe pas.'], 404);
        }

        // Déterminer l'extension réelle pour garder le bon Content-Type et le bon nom
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) ?: 'pdf';
        $mimeTypes = [
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];
        $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';

        $fileName = "quittance_cachetee_{$idSalarie}_{$month}_{$year}.{$extension}";

        return response()->download($filePath, $fileName, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=\"{$fileName}\""
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur lors du téléchargement de la quittance cachetée', [
            'id_salarie' => $idSalarie ?? null,
            'year' => $year ?? null,
            'month' => $month ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Erreur lors du téléchargement de la quittance cachetée : ' . $e->getMessage()], 500);
    }
}




   
}