<?php

namespace App\Http\Controllers;

use App\Models\CompanySettings;
use App\Models\Conge;
use App\Models\Salarie;
use App\Models\User;
use App\Notifications\CongeRequestNotification;
use App\Notifications\CongeStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class CongeAdministratifController extends Controller
{
    public function index()
    {
        $conges = Conge::with('salarie')->orderBy('created_at', 'desc')->get();
        $salaries = Salarie::where('statut', 'actif')->get();
        $companySettings = CompanySettings::first();

        return view('conges.conge_espace_ad', compact('conges', 'salaries', 'companySettings'));
    }



        /**
     * Calcule la date de fin du congé en excluant :
     * - Les dimanches
     * - Tous les jours fériés de la table `jour_feries`
     */
    private function calculateEndDate(Carbon $startDate, int $nombreJours): string
    {
        $current = $startDate->copy();
        $counted = 0;

        // Récupérer tous les jours fériés (date_debut à date_fin inclus)
        $feries = \App\Models\JourFerie::all()->flatMap(function ($jourFerie) {
            $dates = [];
            $debut = Carbon::parse($jourFerie->date_debut);
            $fin   = Carbon::parse($jourFerie->date_fin ?? $jourFerie->date_debut);

            while ($debut->lte($fin)) {
                $dates[] = $debut->format('Y-m-d');
                $debut->addDay();
            }
            return $dates;
        })->unique()->values()->all();

        while ($counted < $nombreJours) {
            $dateStr = $current->format('Y-m-d');
            $weekday = $current->weekday(); // 0 = dimanche, 1=lundi ... 6=samedi

            // On compte uniquement les jours qui ne sont ni dimanche ni férié
            if ($weekday !== 0 && !in_array($dateStr, $feries)) {
                $counted++;
            }

            // On avance toujours d'un jour (même les jours non comptés)
            if ($counted < $nombreJours) {
                $current->addDay();
            }
        }

        return $current->format('Y-m-d');
    }
               public function store(Request $request)
        {
            try {
                $validated = $request->validate([
                    'salarie_id'     => 'required|exists:salaries,id',
                    'date_debut'     => 'required|date',
                    'nombre_jours'   => 'required|integer|min:1',
                    'raison'         => 'required|string',
                    'signature'      => 'required|string|starts_with:data:image/png;base64,',
                ]);

                // ─── Sauvegarde signature ─────────────────────────────────────────────
                $directory = public_path('assets/img/signatures');
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                $signatureData    = str_replace('data:image/png;base64,', '', $validated['signature']);
                $signatureData    = str_replace(' ', '+', $signatureData);
                $signatureContent = base64_decode($signatureData);

                if ($signatureContent === false) {
                    throw new \Exception('Données de signature invalides');
                }

                $filename = 'signature_' . time() . '_' . uniqid() . '.png';
                $path     = 'assets/img/signatures/' . $filename;
                file_put_contents(public_path($path), $signatureContent);

                // ─── Calcul date_fin avec exclusion des dimanches + jours fériés ─────
                $start = Carbon::parse($validated['date_debut']);
                $jours = (int) $validated['nombre_jours'];
                $date_fin = $this->calculateEndDate($start, $jours);

                // ─── Calcul n_jours_reste ───────────────────────────────────────────
                $latestConge = Conge::where('salarie_id', $validated['salarie_id'])
                                    ->orderBy('created_at', 'desc')
                                    ->first();

                $n_jours_reste = $latestConge 
                    ? $latestConge->n_jours_reste 
                    : 18;

                // ─── Création du congé ──────────────────────────────────────────────
                $conge = Conge::create([
                    'salarie_id'     => $validated['salarie_id'],
                    'date_debut'     => $validated['date_debut'],
                    'date_fin'       => $date_fin,
                    'num_j'          => $jours,
                    'raison'         => $validated['raison'],
                    'accepter'       => 1,
                    'signature'      => $path,
                    'approbation'    => 0,
                    'n_jours_reste'  => $n_jours_reste,
                ]);

                // ─── Notifications ──────────────────────────────────────────────────
                $salarie = Salarie::find($validated['salarie_id']);
                $superAdmins = User::whereHas('role', fn($q) => $q->where('name', 'superadmin'))->get();

                $currentUser  = Auth::user();
                $isSuperAdmin = $currentUser?->role?->name === 'superadmin';

                foreach ($superAdmins as $admin) {
                    if ($isSuperAdmin && $admin->id === $currentUser?->id) {
                        Log::info('Skip notif créateur superadmin', ['conge_id' => $conge->id]);
                        continue;
                    }
                    $admin->notify(new CongeRequestNotification($conge, $salarie));
                }

                if ($superAdmins->isEmpty()) {
                    Log::warning('Aucun superadmin trouvé');
                }

                return redirect()->back()->with('success', 'Demande de congé soumise avec succès.');

            } catch (\Exception $e) {
                Log::error('Erreur création congé : ' . $e->getMessage());
                return redirect()->back()->with('error', 'Erreur : ' . $e->getMessage());
            }
        }
        public function approve($id, Request $request)
        {
            try {
                Log::debug('Approbation demandée pour congé ID: ' . $id . ', Données: ' . json_encode($request->all()));

                $conge = Conge::findOrFail($id);

                $status = $request->input('status');
                if (!in_array($status, [1, 2])) {
                    Log::warning('Statut invalide reçu pour congé ID ' . $id . ': ' . $status);
                    return response()->json(['success' => false, 'message' => 'Statut invalide.'], 400);
                }

                // ────────────────────────────────────────────────────────────────
                // On utilise directement num_j (jours saisis par le salarié)
                // ────────────────────────────────────────────────────────────────
                $daysToUse = (int) $conge->num_j;

                Log::info("Nombre de jours impactant le solde pour congé ID {$id} : {$daysToUse}");

                // Solde actuel (dernier congé approuvé avant ou égal à celui-ci)
                $latestApprovedConge = Conge::where('salarie_id', $conge->salarie_id)
                    ->where('approbation', 2)
                    ->where('id', '<=', $id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $currentBalance = $latestApprovedConge ? $latestApprovedConge->n_jours_reste : 18;

                $newBalance = $currentBalance;
                $pdfPath = null;

                $updatedLeaves = [];

                if ($status == 2) { // Accepté
                    $newBalance = max(0, $currentBalance - $daysToUse);
                    Log::info("Solde réduit pour salarié {$conge->salarie_id}, congé {$id} → nouveau solde : {$newBalance}");

                    $pdfPath = $this->generateLeavePdf($conge,$newBalance);
                    $conge->pdf_path = $pdfPath;

                } 
                elseif ($status == 1 && $conge->approbation == 2) { // Refusé après avoir été accepté
                    $newBalance = min(18, $currentBalance + $daysToUse);
                    Log::info("Solde restauré pour salarié {$conge->salarie_id}, congé {$id} → nouveau solde : {$newBalance}");

                    // Suppression du PDF s'il existe
                    if ($conge->pdf_path && file_exists(public_path($conge->pdf_path))) {
                        unlink(public_path($conge->pdf_path));
                        $conge->pdf_path = null;
                    }
                } 
                elseif ($status == 1 && $conge->approbation == 0) { // Refusé alors qu'il était en attente
                    $newBalance = $currentBalance;
                    Log::info("Rejet d'une demande en attente pour congé {$id} → solde inchangé : {$newBalance}");
                }

                // Mise à jour du congé courant
                $conge->approbation   = $status;
                $conge->n_jours_reste = $newBalance;
                $conge->save();
                $updatedLeaves[$conge->id] = $newBalance;

                // ────────────────────────────────────────────────────────────────
                // Mise à jour des congés POSTÉRIEURS (propager le nouveau solde)
                // ────────────────────────────────────────────────────────────────
                $subsequentLeaves = Conge::where('salarie_id', $conge->salarie_id)
                    ->where('id', '>', $conge->id)
                    ->get();

                foreach ($subsequentLeaves as $subsequentConge) {
                    $subsequentConge->n_jours_reste = $newBalance;
                    $subsequentConge->save();
                    $updatedLeaves[$subsequentConge->id] = $newBalance;
                }

                // ────────────────────────────────────────────────────────────────
                // Notification au salarié
                // ────────────────────────────────────────────────────────────────
                $salarie = Salarie::find($conge->salarie_id);
                $user = User::where('id_salarie', $salarie->id)->first();

                if ($user) {
                    $user->notify(new CongeStatusNotification($conge, $salarie, $status));
                    Log::info('Notification de statut envoyée au salarié', [
                        'user_id'  => $user->id,
                        'conge_id' => $conge->id,
                        'status'   => $status,
                    ]);
                } else {
                    Log::warning('Aucun utilisateur lié au salarié ID ' . $salarie->id);
                }

                // ────────────────────────────────────────────────────────────────
                // Suppression des notifications "en attente" pour les superadmins
                // ────────────────────────────────────────────────────────────────
                $superadminIds = User::whereHas('role', function ($query) {
                    $query->where('name', 'superadmin');
                })->pluck('id');

                $deletedCount = DB::table('notifications')
                    ->whereIn('notifiable_id', $superadminIds)
                    ->where('notifiable_type', User::class)
                    ->where('type', CongeRequestNotification::class)
                    ->whereJsonContains('data->conge_id', $conge->id)
                    ->delete();

                Log::info("Notifications de demande supprimées : {$deletedCount} pour congé ID {$conge->id}");

                // ────────────────────────────────────────────────────────────────
                // Réponse JSON
                // ────────────────────────────────────────────────────────────────
                Log::info("Congé ID {$id} mis à jour → approbation = {$status}, solde = {$newBalance}");

                $message = $status == 2 
                    ? 'Demande de congé acceptée avec succès.'
                    : 'Demande de congé refusée avec succès.';

                return response()->json([
                    'success'       => true,
                    'message'       => $message,
                    'n_jours_reste' => $newBalance,
                    'salarie_id'    => $conge->salarie_id,
                    'pdf_path'      => $pdfPath,
                    'updated_leaves' => $updatedLeaves
                ]);

            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'approbation/refus du congé ID ' . $id, [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur serveur : ' . $e->getMessage()
                ], 500);
            }
        }

        public function destroy($id)
        {
            try {
                Log::debug('Suppression demandée pour congé ID: ' . $id);

                $conge = Conge::findOrFail($id);
                if (!$conge) {
                    Log::error("Congé ID {$id} introuvable dans la table conger.");
                    return response()->json(['success' => false, 'message' => 'Demande de congé introuvable.'], 404);
                }

                // Restore leave balance if the leave was approved
                if ($conge->approbation == 2) {
                    $startDate = Carbon::parse($conge->date_debut);
                    $workingDays = 0;
                    $currentDate = $startDate->copy();
                    $endDate = $startDate->copy()->addDays($conge->num_j - 1);
                    while ($currentDate <= $endDate) {
                        if (!$currentDate->isWeekend()) {
                            $workingDays++;
                        }
                        $currentDate->addDay();
                    }

                    // Find the latest approved leave to get the current balance
                    $latestApprovedConge = Conge::where('salarie_id', $conge->salarie_id)
                        ->where('approbation', 2)
                        ->where('id', '<', $conge->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    $currentBalance = $latestApprovedConge ? $latestApprovedConge->n_jours_reste : 18;

                    // Restore the leave balance
                    $newBalance = min(18, $currentBalance + $workingDays);
                    Log::info("Restauration de n_jours_reste pour salarie_id {$conge->salarie_id}, congé ID {$id}: {$newBalance}");

                    // Update subsequent leaves
                    $subsequentLeaves = Conge::where('salarie_id', $conge->salarie_id)
                        ->where('id', '>', $conge->id)
                        ->get();
                    foreach ($subsequentLeaves as $subsequentConge) {
                        $subsequentConge->n_jours_reste = $newBalance;
                        $subsequentConge->save();
                        Log::info("Mise à jour de n_jours_reste pour congé ID {$subsequentConge->id}: {$newBalance}");
                    }
                }

                // Delete associated signature file if it exists
                if ($conge->signature && file_exists(public_path($conge->signature))) {
                    unlink(public_path($conge->signature));
                    Log::info("Fichier de signature supprimé pour congé ID {$id}: {$conge->signature}");
                }

                // Delete associated PDF if it exists
                if ($conge->pdf_path && file_exists(public_path($conge->pdf_path))) {
                    unlink(public_path($conge->pdf_path));
                    Log::info("Fichier PDF supprimé pour congé ID {$id}: {$conge->pdf_path}");
                }

                // Delete CongeRequestNotification for superadmins
                $superadmins = User::whereHas('role', function ($query) {
                    $query->where('name', 'superadmin');
                })->pluck('id');
                $deletedCount = DB::table('notifications')
                    ->whereIn('notifiable_id', $superadmins)
                    ->where('notifiable_type', User::class)
                    ->where('type', CongeRequestNotification::class)
                    ->whereJsonContains('data->conge_id', $conge->id)
                    ->delete();
                Log::info("Supprimé $deletedCount notifications de demande de congé pour congé ID {$conge->id}");

                // Delete CongeStatusNotification for the employee
                $user = User::where('id_salarie', $conge->salarie_id)->first();
                if ($user) {
                    $deletedUserNotif = DB::table('notifications')
                        ->where('notifiable_id', $user->id)
                        ->where('notifiable_type', User::class)
                        ->where('type', CongeStatusNotification::class)
                        ->whereJsonContains('data->conge_id', $conge->id)
                        ->delete();
                    Log::info("Supprimé $deletedUserNotif notifications de statut de congé pour l'utilisateur ID {$user->id}");
                }

                // Delete the Conge record
                $conge->delete();
                Log::info("Demande de congé ID {$id} supprimée avec succès");

                return response()->json([
                    'success' => true,
                    'message' => 'Demande de congé supprimée avec succès.'
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la suppression du congé ID ' . $id . ': ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
                return response()->json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
            }
        }

    protected function generateLeavePdf($conge,$newBalance)
    {
        try {
            $salarie = $conge->salarie;

            // Récupérer les paramètres de l'entreprise - AJOUT DE CETTE LIGNE POUR CORRIGER L'ERREUR
            $companySettings = \App\Models\CompanySettings::first();

            // Générer le nom du dossier basé sur le nom et le matricule
            $folderName = Str::slug($salarie->nom . '_' . $salarie->n_matricule_entreprise, '_');
            $basePath = 'assets/storage/salaries/' . $folderName . '/conges';

            // Créer le dossier si nécessaire
            $fullDirectoryPath = public_path($basePath);
            if (!file_exists($fullDirectoryPath)) {
                mkdir($fullDirectoryPath, 0755, true);
                Log::info('Dossier congés créé', ['path' => $basePath, 'full_path' => $fullDirectoryPath]);
            }

            // Vérifier si le dossier existe après création
            if (!file_exists($fullDirectoryPath)) {
                throw new \Exception("Échec de la création du dossier : $basePath");
            }

            // Préparer les données pour le PDF
            $data = [
                'nom' => $salarie->nom ?? 'N/A',
                'prenom' => $salarie->prenom ?? 'N/A',
                'matricule' => $salarie->n_matricule_entreprise ?? 'N/A',
                'date_debut' => Carbon::parse($conge->date_debut)->format('d/m/Y'),
                'date_fin' => Carbon::parse($conge->date_fin)->format('d/m/Y'),
                'n_jours_reste'   => $newBalance ?? $conge->n_jours_reste ?? 0,
                'nombre_jours' => $conge->num_j,
                'raison'            => $conge->raison,
                'signature_path' => public_path($conge->signature),
                'date_approbation' => Carbon::now()->format('d/m/Y'),
                'conge' => $conge,
                'companySettings' => $companySettings,  
            ];
            

            // Générer le PDF
            $pdf = Pdf::loadView('conges.pdf_template', $data);
            $filename = 'conge_' . $conge->id . '_' . time() . '.pdf';
            $fullPath = $basePath . '/' . $filename;

            // Sauvegarder le PDF
            $pdf->save(public_path($fullPath));

            // Vérifier si le fichier a été créé
            if (!file_exists(public_path($fullPath))) {
                throw new \Exception("Échec de la sauvegarde du PDF : $fullPath");
            }

            Log::info('PDF congé généré avec succès', ['path' => $fullPath]);

            // Retourner le chemin relatif
            return $fullPath;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du PDF pour congé ID ' . $conge->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function downloadPdf($id)
    {
        try {
            $conge = Conge::findOrFail($id);
            if (!$conge->pdf_path || !file_exists(public_path($conge->pdf_path))) {
                return redirect()->back()->with('error', 'Le fichier PDF n\'existe pas.');
            }
            return response()->download(public_path($conge->pdf_path), 'demande_conge_' . $id . '.pdf');
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du PDF pour congé ID ' . $id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors du téléchargement du PDF: ' . $e->getMessage());
        }
    }

    public function getCalendarEvents(Request $request)
    {
        try {
            // Récupérer uniquement les congés approuvés (approbation = 2)
            $conges = Conge::with('salarie')
                ->where('approbation', 2)
                ->get();
            Log::info('Fetched approved conges for calendar:', $conges->toArray());

            $events = [];
            foreach ($conges as $conge) {
                $startDate = Carbon::parse($conge->date_debut);
                $numDays = $conge->num_j;
                $currentDate = $startDate->copy();
                $workingDaysCounted = 0;

                // Préparer le nom du salarié
                $salarieName = ($conge->salarie->nom ?? 'N/A') . ' ' . ($conge->salarie->prenom ?? 'N/A');
                // Obtenir la couleur du salarié
                $color = $this->getSalarieColor($conge->salarie_id, $salarieName);

                while ($workingDaysCounted < $numDays) {
                    if (!$currentDate->isWeekend()) {
                        $events[] = [
                            'id' => $conge->id . '-' . $currentDate->toDateString(),
                            'title' => '✅ ' . $salarieName,
                               'start' => $currentDate->toDateString(),
                            'end' => $currentDate->toDateString(),
                            'backgroundColor' => $color,
                            'borderColor' => $color,
                            'textColor' => '#FFFFFF',
                            'extendedProps' => [
                                'salarieId' => $conge->salarie_id,
                                'salarieName' => $salarieName,
                                'raison' => $conge->raison ?? 'Aucune raison',
                                'nombreJours' => $conge->num_j,
                                'approbation' => $conge->approbation,
                                'statusClass' => 'approved'
                            ],
                            'classNames' => ['leave-event', 'approved']
                        ];
                        $workingDaysCounted++;
                    }
                    $currentDate->addDay();
                }
            }

            Log::info('Calendar events:', $events);
            return response()->json($events);
        } catch (\Exception $e) {
            Log::error('Error fetching calendar events: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la récupération des événements'], 500);
        }
    }
    protected function getSalarieColor($salarieId, $salarieName)
    {
        // Nouvelle palette de couleurs synchronisée avec le JavaScript
        static $colorPalette = [
            '#3498DB', // Bleu vif
            '#2ECC71', // Vert émeraude
            '#E74C3C', // Rouge corail
            '#F1C40F', // Jaune moutarde
            '#9B59B6', // Violet
            '#1ABC9C', // Turquoise
            '#E67E22', // Orange
            '#34495E', // Bleu-gris foncé
            '#D35400', // Orange brûlé
            '#7F8C8D', // Gris élégant
            '#2980B9', // Bleu profond
            '#27AE60', // Vert forêt
            '#C0392B', // Rouge brique
            '#F39C12', // Jaune doré
            '#8E44AD'  // Violet foncé
        ];

        // Map statique pour conserver les couleurs par salarié
        static $salarieColors = [];
        static $colorIndex = 0;

        // Si le salarié n'a pas encore de couleur, lui en attribuer une
        if (!isset($salarieColors[$salarieId])) {
            $salarieColors[$salarieId] = [
                'color' => $colorPalette[$colorIndex % count($colorPalette)],
                'name' => $salarieName ?: 'Employé inconnu'
            ];
            $colorIndex++;
            Log::info('New color assigned to salarieId', [
                'salarieId' => $salarieId,
                'color' => $salarieColors[$salarieId]['color'],
                'name' => $salarieName
            ]);
        }

        return $salarieColors[$salarieId]['color'];
    }

public function edit($id)
{
    try {
        Log::debug('Fetching conge data for edit, congeId: ' . $id);
        $conge = Conge::with('salarie')->findOrFail($id);

        if ($conge->approbation == 2) {
            Log::warning('Attempt to edit approved conge, congeId: ' . $id);
            return response()->json([
                'success' => false,
                'message' => 'Modification non autorisée pour les congés approuvés.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'conge' => [
                'id' => $conge->id,
                'salarie_id' => $conge->salarie_id,
                'date_debut' => Carbon::parse($conge->date_debut)->format('Y-m-d'),
                'num_j' => $conge->num_j,
                'raison' => $conge->raison, // Grâce à l'accessor, cela lit Raison
                'signature' => $conge->signature,
            ],
            'salarie' => [
                'id' => $conge->salarie->id,
                'nom' => $conge->salarie->nom,
                'prenom' => $conge->salarie->prenom,
                'email' => $conge->salarie->email,
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors du chargement du congé ID ' . $id . ': ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des données du congé: ' . $e->getMessage()
        ], 500);
    }
}


public function update(Request $request, $id)
{
    try {
        Log::debug('Mise à jour demandée pour congé ID: ' . $id . ', Données: ' . json_encode($request->all()));

        $conge = Conge::findOrFail($id);

        if ($conge->approbation == 2) {
            Log::warning('Tentative de modification d\'un congé approuvé, congeId: ' . $id);
            return response()->json([
                'success' => false,
                'message' => 'Modification non autorisée pour les congés approuvés.'
            ], 403);
        }

        $validated = $request->validate([
            'salarie_id'     => 'required|exists:salaries,id',
            'date_debut'     => 'required|date',
            'nombre_jours'   => 'required|integer|min:1',
            'raison'         => 'required|string',
            'signature'      => 'required|string|starts_with:data:image/png;base64,',
        ]);

        // ─── Gestion signature (suppression ancienne + nouvelle) ────────────────
        $directory = public_path('assets/img/signatures');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $signatureData    = str_replace('data:image/png;base64,', '', $validated['signature']);
        $signatureData    = str_replace(' ', '+', $signatureData);
        $signatureContent = base64_decode($signatureData);

        if ($signatureContent === false) {
            throw new \Exception('Données de signature invalides');
        }

        // Supprimer l'ancienne signature si elle existe
        if ($conge->signature && file_exists(public_path($conge->signature))) {
            unlink(public_path($conge->signature));
            Log::info("Ancienne signature supprimée pour congé ID {$id}: {$conge->signature}");
        }

        // Sauvegarder la nouvelle
        $filename = 'signature_' . time() . '_' . uniqid() . '.png';
        $path     = 'assets/img/signatures/' . $filename;
        file_put_contents(public_path($path), $signatureContent);

        // ─── Calcul de la nouvelle date_fin (samedi inclus, dimanche exclu) ─────
        $start   = \Carbon\Carbon::parse($validated['date_debut']);
        $jours   = (int) $validated['nombre_jours'];

        $current = $start->copy();
        $counted = 0;

        while ($counted < $jours) {
            $current->addDay();
            $weekday = $current->weekday(); // 0=lun ... 5=sam, 6=dim

            if ($weekday <= 5) {  // lundi à samedi → on compte
                $counted++;
            }
            // dimanche → on saute (ne compte pas)
        }

        $date_fin = $current->subDay()->format('Y-m-d');

        // ─── Mise à jour du congé ───────────────────────────────────────────────
        $conge->update([
            'salarie_id' => $validated['salarie_id'],
            'date_debut' => $validated['date_debut'],
            'date_fin'   => $date_fin,                    // ← ajouté / mis à jour
            'num_j'      => $jours,
            'raison'     => $validated['raison'],
            'signature'  => $path,
        ]);

        // ─── Solde restant (logique existante) ──────────────────────────────────
        $latestApprovedConge = Conge::where('salarie_id', $validated['salarie_id'])
            ->where('approbation', 2)
            ->orderBy('created_at', 'desc')
            ->first();

        $n_jours_reste = $latestApprovedConge ? $latestApprovedConge->n_jours_reste : 18;

        $conge->n_jours_reste = $n_jours_reste;
        $conge->save();

        // ─── Notifications superadmins (inchangé) ───────────────────────────────
        $salarie = Salarie::find($validated['salarie_id']);
        $superAdmins = User::whereHas('role', function ($query) {
            $query->where('name', 'superadmin');
        })->get();

        $currentUser  = Auth::user();
        $isSuperAdmin = $currentUser && $currentUser->role && $currentUser->role->name === 'superadmin';

        foreach ($superAdmins as $admin) {
            if ($isSuperAdmin && $admin->id === $currentUser->id) {
                Log::info('Skipping notification pour le superadmin qui modifie', [
                    'superadmin_id' => $admin->id,
                    'conge_id'      => $conge->id,
                ]);
                continue;
            }
            $admin->notify(new CongeRequestNotification($conge, $salarie));
            Log::info('Notification mise à jour envoyée', [
                'superadmin_id' => $admin->id,
                'conge_id'      => $conge->id,
            ]);
        }

        if ($superAdmins->isEmpty()) {
            Log::warning('Aucun superadmin trouvé pour notification mise à jour');
        }

        // ─── Réponse ────────────────────────────────────────────────────────────
        return response()->json([
            'success' => true,
            'message' => 'Demande de congé mise à jour avec succès.',
            'conge'   => [
                'id'           => $conge->id,
                'salarie_id'   => $conge->salarie_id,
                'date_debut'   => $conge->date_debut,
                'date_fin'     => $conge->date_fin,          // ← ajouté ici aussi
                'num_j'        => $conge->num_j,
                'raison'       => $conge->raison,
                'signature'    => $conge->signature,
                'n_jours_reste'=> $conge->n_jours_reste,
            ],
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur mise à jour congé ID ' . $id . ': ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur : ' . $e->getMessage()
        ], 500);
    }
}
}
