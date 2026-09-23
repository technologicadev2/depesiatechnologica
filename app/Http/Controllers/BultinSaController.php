<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BultinSaController extends Controller
{
public function index(Request $request)
{
    Log::info('Index request received', ['url' => url()->full(), 'year' => $request->input('year')]);
    $id_salarie = Auth::user()->id_salarie;
    Log::info('Authenticated user', ['id_salarie' => $id_salarie]);
    
    
    $currentYear = $request->input('year', date('Y')); // Par défaut : 2025
    Log::info('Selected year', ['year' => $currentYear]); 

    $availableYears = [];
    $startYear = 2020;
    $endYear = (int)date('Y') + 1; // Inclure l'année prochaine
    for ($year = $startYear; $year <= $endYear; $year++) {
        if (Schema::hasTable("salairs_$year")) {
            $availableYears[] = $year;
        }
    }
    Log::info('Available years', ['years' => $availableYears]);

    $tableName = 'salairs_' . $currentYear;
    Log::info('Checking table', ['table' => $tableName]);

    if (!Schema::hasTable($tableName)) {
        Log::error('Table does not exist', ['table' => $tableName]);
        return view('bultin-paie.bultin_salarier', [
            'bulletins' => [],
            'availableYears' => $availableYears,
            'selectedYear' => $currentYear
        ])->with('error', "Aucun bulletin disponible pour l'année $currentYear.");
    }
        // Étape 6 : Récupérer les données pour ce salarié dans la table salairs_XXXX
        $salairs = DB::table($tableName)
                     ->where('id_salarie', $id_salarie)
                     ->first();
        Log::info('Salairs data', ['salairs' => $salairs]);

        // Étape 7 : Liste des mois
        $months = [
            'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
        ];

        // Étape 8 : Préparer les données pour l'affichage
        $bulletins = [];
        if ($salairs) {
            foreach ($months as $month => $monthNumber) {
                if (!empty($salairs->$month)) {
                    // Récupérer les pièces jointes associées dans pieces_joint
                    $piece = DB::table('pieces_joint')
                               ->where('id_paiment', $salairs->$month)
                               ->where('id_salarier', $id_salarie)
                               ->where('mois', $month)
                               ->where('annee', $currentYear)
                               ->first();

                    // Vérifier le statut de paiement dans paiement_salaires
                    $payment = DB::table('paiement_salaires')
                                ->where('id_salarie', $id_salarie)
                                ->where('annee', $currentYear)
                                ->where('mois', $monthNumber)
                                ->first();

                    $isPaid = $payment && $payment->typer !== 'a';

                    Log::info('Piece jointe and payment status', [
                        'month' => $month,
                        'id_paiment' => $salairs->$month,
                        'piece' => $piece,
                        'is_paid' => $isPaid
                    ]);

                    $bulletins[] = [
                        'mois' => ucfirst($month),
                        'bulletin' => $piece && property_exists($piece, 'bultin_paie') ? $piece->bultin_paie : null,
                        'bulletin_cache' => $piece && property_exists($piece, 'bultin_paie_ca') ? $piece->bultin_paie_ca : null,
                        'is_paid' => $isPaid
                    ];
                }
            }
        }
        Log::info('Bulletins prepared', ['bulletins' => $bulletins]);

        // Étape 9 : Retourner la vue avec les données
        return view('bultin-paie.bultin_salarier', [
            'bulletins' => $bulletins,
            'availableYears' => $availableYears,
            'selectedYear' => $currentYear
        ]);
    }

    public function download($path)
    {
        // Étape 1 : Vérifier que le salarié est connecté
        $id_salarie = Auth::user()->id_salarie;
        $piece = DB::table('pieces_joint')
                   ->where('bultin_paie', $path)
                   ->orWhere('bultin_paie_ca', $path)
                   ->first();

        // Étape 2 : Vérifier si le paiement appartient au salarié
        if ($piece) {
            $paiement = DB::table('paiement_salaires')
                          ->where('id', $piece->id_paiment)
                          ->first();
            if ($paiement && $paiement->id_salarie == $id_salarie) {
                // Vérifier le statut de paiement
                if ($paiement->typer === 'a') {
                    Log::error('Download blocked: Payment not confirmed', [
                        'id_salarie' => $id_salarie,
                        'path' => $path
                    ]);
                    return redirect()->back()->with('error', 'Téléchargement bloqué : Paiement non effectué.');
                }

                // Étape 3 : Vérifier si le fichier existe
                $filePath = public_path($path);
                $alternativePath = storage_path('app/public/' . $path);
                if (file_exists($filePath)) {
                    return response()->download($filePath);
                } elseif (file_exists($alternativePath)) {
                    return response()->download($alternativePath);
                }
            }
        }

        // Étape 4 : Rediriger avec une erreur si le fichier est introuvable ou non autorisé
        return redirect()->back()->with('error', 'Fichier introuvable ou accès non autorisé.');
    }

    public function downloadPaySlip(Request $request, $id_salarie, $year, $month)
    {
        try {
            // Étape 1 : Vérifier que l'utilisateur connecté est le salarié concerné
            if (Auth::user()->id_salarie != $id_salarie) {
                Log::error('Unauthorized access attempt', [
                    'id_salarie' => $id_salarie,
                    'auth_id_salarie' => Auth::user()->id_salarie
                ]);
                return redirect()->back()->with('error', 'Accès non autorisé.');
            }

            // Étape 2 : Valider le mois
            $monthMap = [
                'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
                'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
                'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
            ];

            if (!isset($monthMap[$month])) {
                Log::error('Invalid month', ['month' => $month]);
                return redirect()->back()->with('error', 'Mois invalide.');
            }
            $monthNumber = $monthMap[$month];

            // Étape 3 : Vérifier si le paiement existe
            $payment = DB::table('paiement_salaires')
                ->where('id_salarie', $id_salarie)
                ->where('annee', $year)
                ->where('mois', $monthNumber)
                ->first();

            if (!$payment) {
                Log::error('No payment found', [
                    'id_salarie' => $id_salarie,
                    'year' => $year,
                    'month' => $month,
                    'month_number' => $monthNumber
                ]);
                return redirect()->back()->with('error', 'Aucun paiement trouvé pour ce mois.');
            }

            // Étape 4 : Vérifier le statut de paiement
            if ($payment->typer === 'a') {
                Log::error('Download blocked: Payment not confirmed', [
                    'id_salarie' => $id_salarie,
                    'year' => $year,
                    'month' => $month
                ]);
                return redirect()->back()->with('error', 'Téléchargement bloqué : Paiement non effectué pour ce mois.');
            }

            // Étape 5 : Récupérer le bulletin de paie depuis pieces_joint
            $pieceJointe = DB::table('pieces_joint')
                ->where('id_paiment', $payment->id)
                ->where('id_salarier', $id_salarie)
                ->where('mois', $month)
                ->where('annee', $year)
                ->first();

            if (!$pieceJointe || empty($pieceJointe->bultin_paie)) {
                Log::error('No pay slip found in pieces_joint', [
                    'id_salarie' => $id_salarie,
                    'id_paiment' => $payment->id,
                    'month' => $month,
                    'year' => $year
                ]);
                return redirect()->back()->with('error', 'Aucun bulletin de paie trouvé.');
            }

            // Étape 6 : Construire le chemin du fichier
            $filePath = public_path($pieceJointe->bultin_paie);
            $alternativePath = storage_path('app/public/' . $pieceJointe->bultin_paie);

            // Étape 7 : Vérifier l'existence du fichier
            if (file_exists($filePath)) {
                Log::info('File found at public path', [
                    'id_salarie' => $id_salarie,
                    'file_path' => $filePath,
                    'bultin_paie' => $pieceJointe->bultin_paie
                ]);
            } elseif (file_exists($alternativePath)) {
                Log::info('File found at alternative storage path', [
                    'id_salarie' => $id_salarie,
                    'file_path' => $alternativePath,
                    'bultin_paie' => $pieceJointe->bultin_paie
                ]);
                $filePath = $alternativePath;
            } else {
                Log::error('Pay slip file does not exist', [
                    'id_salarie' => $id_salarie,
                    'public_path' => $filePath,
                    'alternative_path' => $alternativePath,
                    'bultin_paie' => $pieceJointe->bultin_paie
                ]);
                return redirect()->back()->with('error', 'Le fichier du bulletin de paie n\'existe pas.');
            }

            // Étape 8 : Vérifier les permissions
            if (!is_readable($filePath)) {
                Log::error('File is not readable', [
                    'id_salarie' => $id_salarie,
                    'file_path' => $filePath,
                    'bultin_paie' => $pieceJointe->bultin_paie
                ]);
                return redirect()->back()->with('error', 'Le fichier du bulletin de paie n\'est pas accessible (permissions).');
            }

            // Étape 9 : Télécharger le fichier
            $fileName = "bulletin_paie_{$id_salarie}_{$month}_{$year}.pdf";
            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\""
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du bulletin de paie', [
                'id_salarie' => $id_salarie,
                'year' => $year,
                'month' => $month,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Erreur lors du téléchargement du bulletin de paie : ' . $e->getMessage());
        }
    }

    public function downloadHiddenPaySlip(Request $request, $id_salarie, $year, $month)
    {
        try {
            // Étape 1 : Vérifier que l'utilisateur connecté est le salarié concerné
            if (Auth::user()->id_salarie != $id_salarie) {
                Log::error('Unauthorized access', [
                    'id_salarie' => $id_salarie,
                    'auth_id_salarie' => Auth::user()->id_salarie
                ]);
                return redirect()->back()->with('error', 'Accès non autorisé.');
            }

            // Étape 2 : Valider le mois
            $monthMap = [
                'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
                'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
                'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12
            ];

            if (!isset($monthMap[$month])) {
                Log::error('Invalid month', ['month' => $month]);
                return redirect()->back()->with('error', 'Mois invalide.');
            }
            $monthNumber = $monthMap[$month];

            // Étape 3 : Vérifier si le paiement existe
            $payment = DB::table('paiement_salaires')
                ->where('id_salarie', $id_salarie)
                ->where('annee', $year)
                ->where('mois', $monthNumber)
                ->first();

            if (!$payment) {
                Log::error('No payment found', [
                    'id_salarie' => $id_salarie,
                    'year' => $year,
                    'month' => $month,
                    'month_number' => $monthNumber
                ]);
                return redirect()->back()->with('error', 'Aucun paiement trouvé pour ce mois.');
            }

            // Étape 4 : Vérifier le statut de paiement
            if ($payment->typer === 'a') {
                Log::error('Download blocked: Payment not confirmed', [
                    'id_salarie' => $id_salarie,
                    'year' => $year,
                    'month' => $month
                ]);
                return redirect()->back()->with('error', 'Téléchargement bloqué : Paiement non effectué pour ce mois.');
            }

            // Étape 5 : Récupérer le bulletin de paie caché depuis pieces_joint
            $pieceJointe = DB::table('pieces_joint')
                ->where('id_paiment', $payment->id)
                ->where('id_salarier', $id_salarie)
                ->where('mois', $month)
                ->where('annee', $year)
                ->first();

            if (!$pieceJointe || empty($pieceJointe->bultin_paie_ca)) {
                Log::error('No hidden pay slip found in pieces_joint', [
                    'id_salarie' => $id_salarie,
                    'id_paiment' => $payment->id,
                    'month' => $month,
                    'year' => $year
                ]);
                return redirect()->back()->with('error', 'Aucun bulletin de paie caché trouvé.');
            }

            // Étape 6 : Construire le fichier
            $filePath = public_path($pieceJointe->bultin_paie_ca);
            $alternativePath = storage_path('app/public/' . $pieceJointe->bultin_paie_ca);

            // Étape 7 : Vérifier l'existence du fichier
            if (file_exists($filePath)) {
                Log::info('Hidden pay slip file found at public path', [
                    'id_salarie' => $id_salarie,
                    'file_path' => $filePath,
                    'bultin_paie_ca' => $pieceJointe->bultin_paie_ca
                ]);
            } elseif (file_exists($alternativePath)) {
                Log::info('Hidden pay slip file found at alternative storage path', [
                    'id_salarie' => $id_salarie,
                    'file_path' => $alternativePath,
                    'bultin_paie_ca' => $pieceJointe->bultin_paie_ca
                ]);
                $filePath = $alternativePath;
            } else {
                Log::error('Hidden pay slip file does not exist', [
                    'id_salarie' => $id_salarie,
                    'public_path' => $filePath,
                    'alternative_path' => $alternativePath,
                    'bultin_paie_ca' => $pieceJointe->bultin_paie_ca
                ]);
                return redirect()->back()->with('error', 'Le fichier du bulletin de paie caché n\'existe pas.');
            }

            // Étape 8 : Vérifier les permissions
            if (!is_readable($filePath)) {
                Log::error('Hidden pay slip file is not readable', [
                    'id_salarie' => $id_salarie,
                    'file_path' => $filePath,
                    'bultin_paie_ca' => $pieceJointe->bultin_paie_ca
                ]);
                return redirect()->back()->with('error', 'Le fichier du bulletin de paie caché n\'est pas accessible (permissions).');
            }

            // Étape 9 : Télécharger le fichier
            $fileName = "bulletin_paie_cah_{$id_salarie}_{$month}_{$year}.pdf";
            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\""
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du bulletin de paie caché', [
                'id_salarie' => $id_salarie,
                'year' => $year,
                'month' => $month,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Erreur lors du téléchargement du bulletin de paie caché : ' . $e->getMessage());
        }
    }
}