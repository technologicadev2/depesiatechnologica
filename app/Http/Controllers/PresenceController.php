<?php

namespace App\Http\Controllers;

use App\Models\CompanySettings;
use App\Models\JourFerie;
use Illuminate\Http\Request;
use App\Models\Projet;
use App\Models\Salarie;
use App\Models\Presence;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Absence;
use Carbon\Carbon; 
use Illuminate\Support\Facades\Log;
class PresenceController extends Controller
{
public function index()
{
    // Récupérer les projets
    $projets = Projet::all();

    // Récupérer les salariés
    $salaries = Salarie::all();
    $companySettings = CompanySettings::first();

    // Récupérer les données de présence agrégées
    $presences = Presence::select(
        'date',
        'id_projet',
        DB::raw('SUM(CASE WHEN statuts = 1 THEN 1 ELSE 0 END) as presents'),
        DB::raw('SUM(CASE WHEN statuts = 0 THEN 1 ELSE 0 END) as absents')
    )
    ->leftJoin('projet', 'presence.id_projet', '=', 'projet.id')
    ->groupBy('date', 'id_projet')
    ->get();

    // Récupérer les jours fériés
    $holidays = JourFerie::all()->map(function ($holiday) {
        $start = \Carbon\Carbon::parse($holiday->date_debut);
        $end = \Carbon\Carbon::parse($holiday->date_fin);
        $days = [];
        // Générer un événement pour chaque jour du jour férié
        while ($start->lte($end)) {
            $days[] = [
                'title' => $holiday->nom,
                'start' => $start->format('Y-m-d'),
                'end' => $start->format('Y-m-d'),
                'allDay' => true,
                'extendedProps' => [
                    'calendar' => 'holiday',
                    'isHoliday' => true,
                ],
            ];
            $start->addDay();
        }
        return $days;
    })->flatten(1)->toArray();

    // Formater les événements de présence pour FullCalendar
    $events = $presences->map(function ($presence, $index) {
        $title = $presence->id_projet
            ? "<span class='event-project-title'>" . ($presence->projet->intitule ?? 'Projet Inconnu') . "</span>"
            : "<span class='event-project-title'>Sans Projet</span>";
        $calendar = $presence->id_projet
            ? "projet-{$presence->id_projet}"
            : 'sans-projet';

        return [
            'id' => $index + 1,
            'title' => $title . "<div class='event-stats'><span class='present'>{$presence->presents}</span><span class='absent'>{$presence->absents}</span></div>",
            'start' => $presence->date,
            'end' => $presence->date,
            'allDay' => true,
            'extendedProps' => [
                'calendar' => $calendar,
                'presents' => $presence->presents,
                'absents' => $presence->absents,
                'salarie_id' => null,
                'statuts' => null,
            ],
        ];
    })->toArray();

    // Fusionner les événements de présence avec les jours fériés
    $events = array_merge($events, $holidays);

    return view('presence.presence', compact('projets', 'salaries', 'events', 'companySettings'));
}
   public function getEmployeesByStatus(Request $request)
{
    $projectId = $request->query('project_id');
    $date = $request->query('date');
    $status = $request->query('status');

    if ($projectId === 'sans-projet') {
        $projectId = null;
    }

    if (!$date || !in_array($status, ['0', '1'])) {
        return response()->json(['error' => 'Paramètres invalides'], 400);
    }

    $query = Presence::where('date', $date)
        ->where('statuts', $status)
        ->with('salarie');

    if ($projectId === null) {
        $query->whereNull('id_projet');
    } else {
        $query->where('id_projet', $projectId);
    }

    // ← Changer get()->pluck('salarie') par get()->map sur la PRESENCE
    $employees = $query->get()
        ->map(function ($presence) {
            if (!$presence->salarie) return null;
            return [
                'prenom'       => $presence->salarie->prenom,
                'nom'          => $presence->salarie->nom,
                'id'           => $presence->salarie->id,
                'type_absence' => $presence->type_absence ?? null, // ← vient de la présence
            ];
        })
        ->filter()
        ->values();

    return response()->json(['employees' => $employees]);
}

    public function savePointage(Request $request)
    {
        try {
            $presences = $request->input('presences', []);
            $errors = [];

            foreach ($presences as $presence) {
                if (!isset($presence['salarie_id'], $presence['statuts'])) {
                    $errors[] = [
                        'salarie_id' => $presence['salarie_id'] ?? 'inconnu',
                        'id_projet' => $presence['id_projet'] ?? null,
                        'error' => 'Données invalides'
                    ];
                    continue;
                }

                Presence::updateOrCreate(
                    [
                        'salarie_id' => $presence['salarie_id'],
                        'id_projet' => $presence['id_projet'] ?? null,
                        'date' => now()->toDateString(), // Ou utiliser $presence['date'] si fourni
                    ],
                    [
                        'statuts' => $presence['statuts'],
                        'localisation' => $presence['localisation'] ?? null
                    ]
                );
            }

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certaines présences n\'ont pas pu être enregistrées',
                    'errors' => $errors
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Présences enregistrées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ], 500);
        }
    }

public function getEvents()
{
    $presences = Presence::select(
        'date',
        'id_projet',
        DB::raw('SUM(CASE WHEN statuts = 1 THEN 1 ELSE 0 END) as presents'),
        DB::raw('SUM(CASE WHEN statuts = 0 THEN 1 ELSE 0 END) as absents')
    )
    ->leftJoin('projet', 'presence.id_projet', '=', 'projet.id')
    ->groupBy('date', 'id_projet')
    ->get();

    $holidays = JourFerie::all()->map(function ($holiday) {
        $start = \Carbon\Carbon::parse($holiday->date_debut);
        $end = \Carbon\Carbon::parse($holiday->date_fin);
        $days = [];
        while ($start->lte($end)) {
            $days[] = [
                'title' => $holiday->nom,
                'start' => $start->format('Y-m-d'),
                'end' => $start->format('Y-m-d'),
                'allDay' => true,
                'extendedProps' => [
                    'calendar' => 'holiday',
                    'isHoliday' => true,
                ],
            ];
            $start->addDay();
        }
        return $days;
    })->flatten(1)->toArray();

    $events = $presences->map(function ($presence, $index) {
        $title = $presence->id_projet
            ? "<span class='event-project-title'>" . ($presence->projet->intitule ?? 'Projet Inconnu') . "</span>"
            : "<span class='event-project-title'>Sans Projet</span>";
        $calendar = $presence->id_projet
            ? "projet-{$presence->id_projet}"
            : 'sans-projet';

        return [
            'id' => $index + 1,
            'title' => $title . "<div class='event-stats'><span class='present'>{$presence->presents}</span><span class='absent'>{$presence->absents}</span></div>",
            'start' => $presence->date,
            'end' => $presence->date,
            'allDay' => true,
            'extendedProps' => [
                'calendar' => $calendar,
                'presents' => $presence->presents,
                'absents' => $presence->absents,
                'salarie_id' => null,
                'statuts' => null,
            ],
        ];
    })->toArray();

    // Fusionner avec les jours fériés
    $events = array_merge($events, $holidays);

    return response()->json($events);
}
    
public function getPointageByMonth(Request $request)
{
    $month = $request->query('month');
    if (!$month) {
        return response()->json(['error' => 'Mois non spécifié'], 400);
    }

    try {
        $companySettings = CompanySettings::first();
        $startOfMonth = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        // SOLUTION 2: Récupérer d'abord, puis trier numériquement avec sortBy
        $salaries = Salarie::where('statut', 'actif')
        ->where('date_embauche', '<=', $endOfMonth)
            ->leftJoin('fonctions', 'salaries.fonction_id', '=', 'fonctions.id')
            ->select(
                'salaries.id',
                'salaries.nom',
                'salaries.prenom',
                'salaries.n_matricule_entreprise',
                'fonctions.designation as fonction'
            )
            ->get()
            ->sortBy(function ($salarie) {
                // Convertir le matricule en entier pour le tri numérique croissant
                return (int) $salarie->n_matricule_entreprise;
            })
            ->values(); // Réindexer la collection

        $presences = Presence::whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->with('salarie')
            ->get()
            ->groupBy('salarie_id');

        $holidays = JourFerie::whereBetween('date_debut', [$startOfMonth, $endOfMonth])
            ->orWhereBetween('date_fin', [$startOfMonth, $endOfMonth])
            ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('date_debut', '<', $startOfMonth)
                      ->where('date_fin', '>', $endOfMonth);
            })
            ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('date_debut', '>=', $startOfMonth)
                      ->where('date_fin', '<=', $endOfMonth);
            })
            ->get()
            ->map(function ($holiday) use ($startOfMonth, $endOfMonth) {
                $start = max($holiday->date_debut, $startOfMonth);
                $end = min($holiday->date_fin, $endOfMonth);
                return [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                    'name' => $holiday->nom,
                ];
            });

        $pointageData = [];
        foreach ($salaries as $salarie) {
            $row = [
                'n_matricule_entreprise' => $salarie->n_matricule_entreprise ?? 'N/A',
                'nom' => trim($salarie->nom . ' ' . $salarie->prenom),
                'fonction' => $salarie->fonction ?? 'N/A',
                'days' => [],
                'total' => 0,
            ];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = \Carbon\Carbon::parse($month . '-' . sprintf('%02d', $day));
                $dateStr = $currentDate->format('Y-m-d');
                $row['days'][$day] = '';

                $isHoliday = false;
                foreach ($holidays as $holiday) {
                    $holidayStart = \Carbon\Carbon::parse($holiday['start']);
                    $holidayEnd = \Carbon\Carbon::parse($holiday['end']);
                    if ($currentDate->between($holidayStart, $holidayEnd)) {
                        $isHoliday = true;
                        break;
                    }
                }

                if (isset($presences[$salarie->id])) {
                   foreach ($presences[$salarie->id] as $presence) {
        if (\Carbon\Carbon::parse($presence->date)->day === $day) {
            if ($presence->statuts == 1) {
                $row['days'][$day] = 'X';
                $row['total'] += 1;
            } elseif ($presence->statuts == 0 && $presence->type_absence == 'c') {
                $row['days'][$day] = 'C'; // ← Afficher C pour congé
            }
        }
    }
                }
            }
            $pointageData[] = $row;
        }

        $moisFrancais = [
            'January' => 'Janvier',
            'February' => 'Février',
            'March' => 'Mars',
            'April' => 'Avril',
            'May' => 'Mai',
            'June' => 'Juin',
            'July' => 'Juillet',
            'August' => 'Août',
            'September' => 'Septembre',
            'October' => 'Octobre',
            'November' => 'Novembre',
            'December' => 'Décembre'
        ];

        $monthName = $moisFrancais[$startOfMonth->format('F')] ?? $startOfMonth->format('F');

        return response()->json([
            'month' => $monthName,
            'daysInMonth' => $daysInMonth,
            'pointage' => $pointageData,
            'holidays' => $holidays->toArray(),
            'company' => [
                'nom_etreprise' => $companySettings ? $companySettings->nom_etreprise : 'N/A',
                'logo' => $companySettings && $companySettings->logo && Storage::exists($companySettings->logo)
                    ? 'data:image/' . pathinfo($companySettings->logo, PATHINFO_EXTENSION) . ';base64,' . base64_encode(Storage::get($companySettings->logo))
                    : asset('assets/img/favicon/anassi2.jpg'),
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
    }
}
//////////////////////////update statut dans calender ////////////////
public function togglePresence(Request $request)
{
    $validated = $request->validate([
        'salarie_id' => 'required|integer',
        'statuts' => 'required|boolean',
        'date' => 'required|date',
        'id_projet' => 'nullable|integer'
    ]);

    $salarieId = $validated['salarie_id'];
    $date = Carbon::parse($validated['date']);
    $newStatus = $validated['statuts'] ? 1 : 0;
    $projectId = $validated['id_projet'] ?? null;

    DB::beginTransaction();

    try {
        // Vérifier si le jour est un dimanche ou un jour férié
        $isSunday = $date->isSunday();
        $isHoliday = JourFerie::where('date_debut', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->where('date_fin', '>=', $date)
                      ->orWhereNull('date_fin')
                      ->orWhere('date_fin', '=', $date);
            })
            ->exists();

        $restrictToChecked = $isSunday || $isHoliday;

        // ===============================================
        // CAS 1: Marquer PRÉSENT (statuts = 1) - LOGIQUE AJUSTÉE
        // ===============================================
        if ($newStatus === 1) {
            $absences = Absence::where('salarie_id', $salarieId)
                ->where('date_debut', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->where('date_fin', '>', $date)
                          ->orWhereNull('date_fin');
                })
                ->get();

            foreach ($absences as $absence) {
                $debut = Carbon::parse($absence->date_debut);
                $fin = $absence->date_fin ? Carbon::parse($absence->date_fin) : null;

                // Absence d'un seul jour
                if ($debut->equalTo($date) && ($fin ? $fin->equalTo($date) : false)) {
                    $absence->delete();
                    continue;
                } else if ($debut->equalTo($date) && $fin === null) {
                    $absence->delete();
                    continue;
                }

                // Date au début
                if ($debut->equalTo($date)) {
                    $newDateDebut = $date->copy()->addDay();
                    $newDateDebutStr = $newDateDebut->format('Y-m-d');
                    $absence->date_debut = $newDateDebutStr;
                    if ($fin && $newDateDebutStr >= $fin->format('Y-m-d')) {
                        $absence->delete();
                    } else {
                        $absence->nbre_jours = $fin ? $newDateDebut->diffInDays($fin) : null;
                        $absence->save();
                    }
                    continue;
                }

                // Date à la fin
                if ($fin && $fin->equalTo($date)) {
                    $newDateFin = $date->copy()->subDay();
                    $newDateFinStr = $newDateFin->format('Y-m-d');
                    if ($newDateFinStr <= $absence->date_debut) {
                        $absence->delete();
                    } else {
                        $absence->date_fin = $newDateFinStr;
                        $absence->nbre_jours = $debut->diffInDays(Carbon::parse($newDateFinStr));
                        $absence->save();
                    }
                    continue;
                }

                // Date au milieu - AJUSTÉ POUR VOTRE EXEMPLE
                $firstPart = $absence->replicate();
                $firstPart->date_fin = $date->format('Y-m-d'); // Changé à $date au lieu de subDay
                $firstDebutStr = $firstPart->date_debut;
                $firstFinStr = $firstPart->date_fin;
                if ($firstDebutStr < $firstFinStr) { // Changé à < au lieu de !==
                    $firstPart->nbre_jours = Carbon::parse($firstDebutStr)->diffInDays(Carbon::parse($firstFinStr));
                    $firstPart->save();
                }

                $absence->date_debut = $date->copy()->addDay()->format('Y-m-d');
                $secondDebutStr = $absence->date_debut;
                if ($absence->date_fin) {
                    if ($secondDebutStr >= $absence->date_fin) {
                        $absence->delete();
                    } else {
                        $absence->nbre_jours = Carbon::parse($secondDebutStr)->diffInDays(Carbon::parse($absence->date_fin));
                        $absence->save();
                    }
                } else {
                    $absence->nbre_jours = null;
                    $absence->save();
                }
            }
        }

        // ===============================================
        // CAS 2: Marquer ABSENT (statuts = 0) - LOGIQUE PRÉCÉDENTE
        // ===============================================
        if ($newStatus === 0 && !$restrictToChecked) {
            $previousDay = $date->copy()->subDay();
            $previousAbsence = Absence::where('salarie_id', $salarieId)
                ->where('date_debut', '<=', $previousDay)
                ->whereNull('date_fin')
                ->orderBy('date_debut', 'desc')
                ->first();

            if ($previousAbsence) {
                Log::info('Insertion d\'absence ignorée : absence ouverte trouvée pour le jour précédent', [
                    'salarie_id' => $salarieId,
                    'previous_day' => $previousDay->format('Y-m-d'),
                    'previous_absence' => $previousAbsence->toArray(),
                ]);
            } else {
                $currentAbsence = Absence::where('salarie_id', $salarieId)
                    ->where('date_debut', '<=', $date)
                    ->where(function ($query) use ($date) {
                        $query->where('date_fin', '>', $date)
                              ->orWhereNull('date_fin');
                    })
                    ->first();

                if ($currentAbsence) {
                    Log::info('Insertion d\'absence ignorée : absence existante pour la date actuelle', [
                        'salarie_id' => $salarieId,
                        'date_debut' => $date->format('Y-m-d'),
                        'existing_absence' => $currentAbsence->toArray(),
                    ]);
                } else {
                    $nextDay = $date->copy()->addDay();
                    $nextDayStr = $nextDay->format('Y-m-d');
                    
                    $nextAbsence = Absence::where('salarie_id', $salarieId)
                        ->where('date_debut', '<=', $nextDay)
                        ->where(function ($query) use ($nextDay) {
                            $query->where('date_fin', '>', $nextDay)
                                  ->orWhereNull('date_fin');
                        })
                        ->first();

                    if ($nextAbsence) {
                        Log::info('🔄 Modification date_debut de l\'absence existante', [
                            'salarie_id' => $salarieId,
                            'old_date_debut' => $nextAbsence->date_debut,
                            'new_date_debut' => $date->format('Y-m-d'),
                            'date_fin' => $nextAbsence->date_fin
                        ]);

                        $nextAbsence->date_debut = $date->format('Y-m-d');
                        
                        if ($nextAbsence->date_fin) {
                            $newNbreJours = Carbon::parse($date->format('Y-m-d'))->diffInDays(Carbon::parse($nextAbsence->date_fin));
                            $nextAbsence->nbre_jours = $newNbreJours;
                        } else {
                            $nextAbsence->nbre_jours = null;
                        }
                        
                        $nextAbsence->save();

                        Log::info('✅ Absence modifiée avec succès', [
                            'salarie_id' => $salarieId,
                            'new_date_debut' => $nextAbsence->date_debut,
                            'date_fin' => $nextAbsence->date_fin,
                            'nbre_jours' => $nextAbsence->nbre_jours
                        ]);

                    } else {
                        $nextPresence = Presence::where('salarie_id', $salarieId)
                            ->where('date', $nextDayStr)
                            ->where('statuts', 1)
                            ->first();

                        if ($nextPresence) {
                            Absence::create([
                                'salarie_id' => $salarieId,
                                'date_debut' => $date->format('Y-m-d'),
                                'date_fin' => $nextDayStr,
                                'motif' => 'Absence marquée via pointage',
                                'statut' => 'validee',
                                'nbre_jours' => Carbon::parse($date->format('Y-m-d'))->diffInDays(Carbon::parse($nextDayStr))
                            ]);
                        } else {
                            Absence::create([
                                'salarie_id' => $salarieId,
                                'date_debut' => $date->format('Y-m-d'),
                                'date_fin' => null,
                                'motif' => 'Absence marquée via pointage',
                                'statut' => 'validee',
                                'nbre_jours' => null
                            ]);
                        }
                    }
                }
            }
        } elseif ($newStatus === 0 && $restrictToChecked) {
            Log::info('Enregistrement d\'absence ignoré : jour est un dimanche ou un jour férié.', [
                'date' => $date->format('Y-m-d'),
                'isSunday' => $isSunday,
                'isHoliday' => $isHoliday,
            ]);
        }

        // Mettre à jour/créer la présence
        $presence = Presence::updateOrCreate(
            [
                'salarie_id' => $salarieId,
                'date' => $date->format('Y-m-d'),
                'id_projet' => $projectId
            ],
            [
                'statuts' => $newStatus,
                'mois' => $date->month,
                'jour' => $date->day,
                'heure' => now()->format('H:i:s'),
                'localisation' => null // Ajustez selon vos besoins
            ]
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'presence' => $presence,
            'message' => $newStatus === 1 ? 'Salarié marqué présent' : 'Salarié marqué absent'
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Erreur lors du basculement de présence', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ], 500);
    }
}


}