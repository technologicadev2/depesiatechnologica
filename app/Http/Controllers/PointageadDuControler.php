<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Salarie;
use App\Models\Presence;
use App\Models\Absence;
use App\Models\Projet;
use App\Models\CompanySettings;
use App\Models\JourFerie; // Ajout du modèle JourFerie
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PointageadDuControler extends Controller
{

    public function index(Request $request)
    {
            // Définir la locale et le fuseau horaire
            Carbon::setLocale('fr');

            // Récupération de la date sélectionnée (via GET ?date= ou aujourd'hui par défaut)
            $selectedDateStr = $request->query('date', Carbon::today()->format('Y-m-d'));

            try {
                $selectedDate = Carbon::parse($selectedDateStr);
            } catch (\Exception $e) {
                $selectedDate = Carbon::today();
                $selectedDateStr = $selectedDate->format('Y-m-d');
            }

            // Log pour debug
            Log::info('Pointage admin - Date utilisée', [
                'selected_date' => $selectedDateStr,
                'system_date'   => date('Y-m-d'),
                'timezone'      => date_default_timezone_get(),
            ]);

            $companySettings = CompanySettings::first();
            $projets = Projet::where('cloture', 0)->select('id', 'intitule')->get();

            // Sous-requête pour les employés en congé à cette date
            $employeesOnLeave = DB::table('conger')
                ->select('salarie_id')
                ->where('date_debut', '<=', $selectedDateStr)
                ->whereRaw('
                    DATE_ADD(
                        date_debut,
                        INTERVAL (
                            num_j + 
                            FLOOR((DATEDIFF(
                                DATE_ADD(date_debut, INTERVAL num_j DAY),
                                date_debut
                            ) + 1) / 7) * 2
                        ) DAY
                    ) >= ?', [$selectedDateStr]);

            // Requête principale des salariés
            $salaries = Salarie::where('statut', 'actif')
                // ← Filtre principal sur la date d'embauche complète
                ->where('date_embauche', '<=', $selectedDateStr)
                // Exclure les employés en congé
                ->whereNotIn('id', $employeesOnLeave)
                // Logique employés sans projet OU responsables de projet
             
                ->select('id', 'nom', 'prenom', 'n_matricule_entreprise', 'photo', 'date_embauche')
                ->get()
                ->map(function ($salarie) use ($selectedDateStr) {
                    // Récupérer la présence et le projet pour la date sélectionnée
                    $presence = Presence::where('salarie_id', $salarie->id)
                        ->where('date', $selectedDateStr)
                        ->with(['projet' => function ($query) {
                            $query->select('id', 'intitule');
                        }])
                        ->first();

                    $salarie->project_name = $presence && $presence->projet ? $presence->projet->intitule : '-';
                    $salarie->photo_url = $salarie->photo
                        ? asset('storage/' . $salarie->photo)
                        : asset('assets/img/avatars/default.png');

                    return $salarie;
                });

                    // Présences existantes pour la date sélectionnée
                            $presences = Presence::where('date', $selectedDateStr)
                                ->pluck('statuts', 'salarie_id')
                                ->toArray();

                            // Récupération de toutes les dates des jours fériés
                            $feries = JourFerie::select('date_debut', 'date_fin')->get();
                            $joursFeries = [];

                        foreach ($feries as $ferie) {
                            $debut = Carbon::parse($ferie->date_debut);
                            $fin   = $ferie->date_fin ? Carbon::parse($ferie->date_fin) : $debut;

                            for ($date = $debut->copy(); $date->lte($fin); $date->addDay()) {
                                $joursFeries[] = $date->format('Y-m-d');
                            }
                        }

                        $joursFeries = array_unique($joursFeries);

                        return view('pointage.pointage_ad', compact(
                            'salaries',
                            'presences',
                            'companySettings',
                            'projets',
                            'joursFeries',
                            'selectedDateStr'   // ← passé à la vue pour flatpickr et affichage
                        ));
    }

    // ─── Méthode helper à ajouter dans la classe ───────────────────────────────
private function calculerJoursOuvres(string $dateDebut, string $dateFin): int
{
    $debut  = Carbon::parse($dateDebut);
    $fin    = Carbon::parse($dateFin);

    // Récupérer tous les jours fériés qui chevauchent la période
    $feries = JourFerie::where('date_debut', '<=', $dateFin)
        ->where(function ($query) use ($dateDebut) {
            $query->where('date_fin', '>=', $dateDebut)
                  ->orWhereNull('date_fin')
                  ->orWhere('date_fin', '=', $dateDebut);
        })
        ->get();

    // Construire un tableau plat de toutes les dates fériées
    $joursFerisSet = [];
    foreach ($feries as $ferie) {
        $d   = Carbon::parse($ferie->date_debut);
        $fin2 = $ferie->date_fin ? Carbon::parse($ferie->date_fin) : $d->copy();
        for ($cur = $d->copy(); $cur->lte($fin2); $cur->addDay()) {
            $joursFerisSet[$cur->format('Y-m-d')] = true;
        }
    }

    // Compter les jours en excluant dimanches et jours fériés
    $count = 0;
    for ($cur = $debut->copy(); $cur->lt($fin); $cur->addDay()) {
        if ($cur->isSunday()) {
            continue; // Exclure les dimanches
        }
        if (isset($joursFerisSet[$cur->format('Y-m-d')])) {
            continue; // Exclure les jours fériés
        }
        $count++;
    }

    return $count;
}

public function save(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'presences'              => 'required|array',
            'presences.*.salarie_id' => 'required|integer|exists:salaries,id',
            'presences.*.statuts'    => 'required|in:0,1',
            'presences.*.date'       => 'required|date_format:Y-m-d',
            'presences.*.mois'       => 'required|integer|between:1,12',
            'presences.*.heure'      => 'required|date_format:H:i:s',
            'presences.*.jour'       => 'required|integer|between:1,31',
            'presences.*.localisation' => 'nullable|string|max:255',
            'absences'               => 'sometimes|array',
            'absences.*.salarie_id'  => 'required|integer|exists:salaries,id',
            'absences.*.date_debut'  => 'required|date_format:Y-m-d',
            'heures'                 => 'nullable|integer|min:0',
            'type'                   => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()->all()
            ], 422);
        }

        $presences = $request->input('presences', []);
        $absences  = $request->input('absences', []);

        Log::info('Données de présence à enregistrer', [
            'presences'   => $presences,
            'absences'    => $absences,
            'system_date' => date('Y-m-d'),
        ]);

        DB::beginTransaction();

        // ────────────────────────────────────────────────
        // Détection dimanche / jour férié
        // ────────────────────────────────────────────────
        $currentDate = !empty($presences) ? $presences[0]['date'] : date('Y-m-d');

        $isSunday  = Carbon::parse($currentDate)->isSunday();
        $isHoliday = JourFerie::where('date_debut', '<=', $currentDate)
            ->where(function ($query) use ($currentDate) {
                $query->where('date_fin', '>=', $currentDate)
                      ->orWhereNull('date_fin')
                      ->orWhere('date_fin', '=', $currentDate);
            })
            ->exists();

        $restrictToChecked = $isSunday || $isHoliday;

        Log::info('Vérification du jour', [
            'date'              => $currentDate,
            'isSunday'          => $isSunday,
            'isHoliday'         => $isHoliday,
            'restrictToChecked' => $restrictToChecked,
        ]);

        // ────────────────────────────────────────────────
        // 1. Supprimer les absences du jour pour les présents
        // ────────────────────────────────────────────────
        $presentSalarieIds = array_column(
            array_filter($presences, fn($p) => $p['statuts'] == 1),
            'salarie_id'
        );

        if (!empty($presentSalarieIds)) {
            Absence::whereIn('salarie_id', $presentSalarieIds)
                ->where('date_debut', $currentDate)
                ->delete();
        }

        // ────────────────────────────────────────────────
        // 2. Clôturer les absences en cours des présents
        // ────────────────────────────────────────────────
        if (!empty($presentSalarieIds)) {
            foreach ($presentSalarieIds as $salarieId) {
                $latestAbsence = Absence::where('salarie_id', $salarieId)
                    ->whereNull('date_fin')
                    ->where('date_debut', '<', $currentDate)
                    ->orderBy('date_debut', 'desc')
                    ->first();

                if ($latestAbsence) {
                    $latestAbsence->update([
                        'date_fin'   => $currentDate,
                        'nbre_jours' => $this->calculerJoursOuvres(
                            $latestAbsence->date_debut,
                            $currentDate
                        ),
                    ]);
                }
            }
        }

        // ────────────────────────────────────────────────
        // 3. Filtrer les présences à sauvegarder
        // ────────────────────────────────────────────────
        $presencesToSave = $restrictToChecked
            ? array_filter($presences, fn($p) => $p['statuts'] == 1)
            : $presences;

        Log::info('Présences à enregistrer après filtrage', [
            'count'           => count($presencesToSave),
            'presencesToSave' => $presencesToSave,
        ]);

        // ────────────────────────────────────────────────
        // 4. Enregistrer / mettre à jour les présences
        // ────────────────────────────────────────────────
        foreach ($presencesToSave as $presence) {

            // ← Si présent, type_absence = null
            // ← Si absent, on vérifie le congé
            $typeAbsence = null;

            if ($presence['statuts'] == 0) {
                $enConge = DB::table('conger')
                    ->where('salarie_id', $presence['salarie_id'])
                    ->where('date_debut', '<=', $currentDate)
                    ->where('date_fin', '>=', $currentDate)
                    ->where('approbation', 2)
                    ->exists();

                $typeAbsence = $enConge ? 'c' : 'a';

                Log::info('Vérification congé pour absent (présence)', [
                    'salarie_id'  => $presence['salarie_id'],
                    'date'        => $currentDate,
                    'enConge'     => $enConge,
                    'typeAbsence' => $typeAbsence,
                ]);
            }

            Presence::updateOrCreate(
                [
                    'salarie_id' => $presence['salarie_id'],
                    'date'       => $presence['date'],
                ],
                [
                    'statuts'         => $presence['statuts'],
                    'type_absence'    => $typeAbsence, // ← 'c', 'a' ou null
                    'mois'            => $presence['mois'],
                    'heure'           => $presence['heure'],
                    'jour'            => $presence['jour'],
                    'localisation'    => $presence['localisation'] ?? null,
                    'heures'          => $presence['heures'] ?? 8,
                    'type_heure_supp' => $presence['type_heure_supp'] ?? null,
                ]
            );
        }

        // ────────────────────────────────────────────────
        // 5. Enregistrement des absences
        // ────────────────────────────────────────────────
        if (!$restrictToChecked) {
            foreach ($absences as $absence) {

                // ← Vérifier si l'employé est en congé
                $enConge = DB::table('conger')
                    ->where('salarie_id', $absence['salarie_id'])
                    ->where('date_debut', '<=', $currentDate)
                    ->where('date_fin', '>=', $currentDate)
                    ->where('approbation', 2)
                    ->exists();

                $justification = $enConge ? 'c' : null;

                Log::info('Vérification congé pour absence', [
                    'salarie_id'    => $absence['salarie_id'],
                    'date'          => $currentDate,
                    'enConge'       => $enConge,
                    'justification' => $justification,
                ]);

                $latestAbsence = Absence::where('salarie_id', $absence['salarie_id'])
                    ->orderBy('date_debut', 'desc')
                    ->first();

                if (!$latestAbsence || $latestAbsence->date_fin !== null) {
                    Absence::updateOrCreate(
                        [
                            'salarie_id' => $absence['salarie_id'],
                            'date_debut' => $absence['date_debut'],
                        ],
                        [
                            'date_fin'      => null,
                            'nbre_jours'    => null,
                            'justification' => $justification, // ← 'c' si congé, null sinon
                        ]
                    );
                } else {
                    Log::info('Insertion absence ignorée : absence en cours détectée', [
                        'salarie_id'      => $absence['salarie_id'],
                        'date_debut'      => $absence['date_debut'],
                        'last_absence_id' => $latestAbsence->id ?? null,
                    ]);
                }
            }
        } else {
            Log::info('Absences ignorées (dimanche ou férié)', [
                'date' => $currentDate,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Présences ' . ($restrictToChecked
                ? 'enregistrées (absences et présences non cochées ignorées pour dimanche ou jour férié)'
                : 'et absences enregistrées') . ' avec succès.'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Erreur sauvegarde présences/absences', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement.',
            'errors'  => [$e->getMessage()]
        ], 500);
    }
}
}