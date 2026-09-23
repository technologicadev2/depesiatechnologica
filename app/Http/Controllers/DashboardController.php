<?php

namespace App\Http\Controllers;

use App\Models\CompanyDocuments;
use App\Models\Vehicle;

use App\Models\Depences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Notifications\CongeRequestNotification;

use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {

            $totalVehicles = Vehicle::count();
            $period = $request->input('period', 'today');
            $year = $request->input('year', now()->year);
            $startDate = Carbon::today();
            $endDate = Carbon::today();
            $projectYears = DB::table('projet')
                ->select(DB::raw('YEAR(created_at) as year'))
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->toArray();
            if (!in_array(now()->year, $projectYears)) {
                $projectYears[] = now()->year;
                sort($projectYears, SORT_NUMERIC);
                $projectYears = array_reverse($projectYears);
            }    
            switch ($period) {
                case 'last_7_days':
                    $startDate = Carbon::now()->subDays(7);
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonth();
                    break;
                case 'today':
                default:
                    break;
            }


            $totalDepences = DB::table('depences')
                ->sum('montant') ?? 0;
            $previousStartDate = $startDate->copy()->subDays($startDate->diffInDays($endDate) + 1);
            $previousDepences = DB::table('depences')
                ->where('date', '>=', $previousStartDate)
                ->where('date', '<', $startDate)
                ->sum('montant') ?? 0;
            $depencesChange = $totalDepences - $previousDepences;

            // Données du graphique des dépenses
            $chartData = DB::table('depences')
                ->select(DB::raw("DATE_FORMAT(date, '%Y-%m') as month, SUM(montant) as total"))
                ->where('date', '>=', Carbon::now()->subYear())
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('total', 'month')
                ->toArray();

            $chartLabels = array_keys($chartData);
            $chartValues = array_values($chartData);

            // Filtre de période pour le graphique des salaires
            $currentYear = now()->year;
            if ($period === 'today') {
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
            } elseif ($period === 'last_7_days') {
                $startDate = now()->subDays(7)->startOfDay();
                $endDate = now()->endOfDay();
            } elseif ($period === 'last_month') {
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
            } elseif ($period === 'year') {
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
            }

            // Données du graphique des salaires
            $salaryChartData = DB::table('paiement_salaires')
                ->select(
                    DB::raw('mois as month_number'),
                    DB::raw('SUM(salaire) as total')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('annee', $currentYear)
                ->whereNotNull('mois')
                ->whereNotNull('salaire')
                ->groupBy('mois')
                ->orderBy('mois')
                ->get();

            $monthNames = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
            ];

            $monthsData = array_fill(1, 12, 0);
            foreach ($salaryChartData as $data) {
                if (isset($monthsData[$data->month_number])) {
                    $monthsData[$data->month_number] = (float) $data->total;
                }
            }

            $salaryChartLabels = ($period === 'year') ? array_values($monthNames) : array_map(function($data) use ($monthNames) {
                return $monthNames[$data->month_number] ?? $data->month_number;
            }, $salaryChartData->toArray());

            $salaryChartValues = ($period === 'year') ? array_values($monthsData) : $salaryChartData->pluck('total')->map(function($value) {
                return (float) $value;
            })->toArray();

            // Totaux
           $totalUsers = DB::table('users')->where('id', '!=', 2)->count();
            $totalSalaries = DB::table('salaries')->where('statut', 'actif')->count();

            // Statistiques de présence
            $presenceStats = DB::table('presence')
                ->join('salaries', 'presence.salarie_id', '=', 'salaries.id')
                ->where('salaries.statut', 'actif')
                ->where('presence.date', '>=', $startDate)
                ->where('presence.date', '<=', $endDate)
                ->select(
                    DB::raw('SUM(CASE WHEN presence.statuts = 1 THEN 1 ELSE 0 END) as total_present'),
                    DB::raw('SUM(CASE WHEN presence.statuts = 0 THEN 1 ELSE 0 END) as total_absent')
                )
                ->first();

            $totalPresent = $presenceStats->total_present ?? 0;
            $totalAbsent = $presenceStats->total_absent ?? 0;

            // Employés en congé
        // Employés en congé (approbation = 2)
        $totalOnLeave = DB::table('conger')
            ->join('salaries', 'conger.salarie_id', '=', 'salaries.id')
            ->where('salaries.statut', 'actif')
            ->where('conger.approbation', 2)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->where('conger.date_debut', '<=', $endDate)
                    ->whereRaw('DATE_ADD(conger.date_debut, INTERVAL conger.num_j DAY) >= ?', [$startDate]);
                });
            })
            ->distinct('conger.salarie_id')
            ->count('conger.salarie_id');

        $onLeaveEmployees = DB::table('conger')
            ->join('salaries', 'conger.salarie_id', '=', 'salaries.id')
            ->where('salaries.statut', 'actif')
            ->where('conger.approbation', 2)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->where('conger.date_debut', '<=', $endDate)
                    ->whereRaw('DATE_ADD(conger.date_debut, INTERVAL conger.num_j DAY) >= ?', [$startDate]);
                });
            })
            ->select('salaries.nom', 'salaries.prenom')
            ->distinct()
            ->get()
            ->map(function ($employee) {
                return trim($employee->prenom . ' ' . $employee->nom);
            })
            ->filter()
            ->values()
            ->toArray();

            // Notifications pour superadmins
            $superadmins = User::whereHas('role', function ($query) {
                $query->where('name', 'superadmin');
            })->pluck('id');

            $pendingLeaveNotifications = DB::table('notifications')
                ->whereIn('notifiable_id', $superadmins)
                ->where('notifiable_type', User::class)
                ->where('type', CongeRequestNotification::class)
                ->whereNull('read_at')
                ->count();

            $pendingLeaveNotificationDetails = DB::table('notifications')
                ->whereIn('notifiable_id', $superadmins)
                ->where('notifiable_type', User::class)
                ->where('type', CongeRequestNotification::class)
                ->whereNull('read_at')
                ->select('id', 'data')
                ->get()
                ->map(function ($notification) {
                    $data = json_decode($notification->data, true);
                    return [
                        'id' => $notification->id,
                        'message' => $data['message'] ?? 'Demande de congé',
                    ];
                })->toArray();

            $pendingDepenseNotifications = DB::table('notifications')
                ->whereIn('notifiable_id', $superadmins)
                ->where('notifiable_type', User::class)
                ->where('type', \App\Notifications\DepenseActionNotification::class)
                ->whereNull('read_at')
                ->count();

            $pendingDepenseNotificationDetails = DB::table('notifications')
                ->whereIn('notifiable_id', $superadmins)
                ->where('notifiable_type', User::class)
                ->where('type', \App\Notifications\DepenseActionNotification::class)
                ->whereNull('read_at')
                ->select('id', 'data')
                ->get()
                ->map(function ($notification) {
                    $data = json_decode($notification->data, true);
                    return [
                        'id' => $notification->id,
                        'message' => $data['message'] ?? 'Action sur une dépense',
                    ];
                })->toArray();

            // Listes des employés
            $presentEmployees = DB::table('presence')
                ->join('salaries', 'presence.salarie_id', '=', 'salaries.id')
                ->where('salaries.statut', 'actif')
                ->where('presence.date', '>=', $startDate)
                ->where('presence.date', '<=', $endDate)
                ->where('presence.statuts', 1)
                ->select('salaries.nom', 'salaries.prenom')
                ->distinct()
                ->get()
                ->map(function ($employee) {
                    return trim($employee->prenom . ' ' . $employee->nom);
                })
                ->filter()
                ->values()
                ->toArray();

            $onLeaveEmployees = DB::table('conger')
                ->join('salaries', 'conger.salarie_id', '=', 'salaries.id')
                ->where('salaries.statut', 'actif')
                ->where('conger.accepter', 1)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->where('conger.date_debut', '<=', $endDate)
                          ->whereRaw('DATE_ADD(conger.date_debut, INTERVAL conger.num_j DAY) >= ?', [$startDate]);
                    });
                })
                ->select('salaries.nom', 'salaries.prenom')
                ->distinct()
                ->get()
                ->map(function ($employee) {
                    return trim($employee->prenom . ' ' . $employee->nom);
                })
                ->filter()
                ->values()
                ->toArray();

            $absentEmployees = DB::table('presence')
                ->join('salaries', 'presence.salarie_id', '=', 'salaries.id')
                ->where('salaries.statut', 'actif')
                ->where('presence.date', '>=', $startDate)
                ->where('presence.date', '<=', $endDate)
                ->where('presence.statuts', 0)
                ->select('salaries.nom', 'salaries.prenom')
                ->distinct()
                ->get()
                ->map(function ($employee) {
                    return trim($employee->prenom . ' ' . $employee->nom);
                })
                ->filter()
                ->values()
                ->toArray();

      
            // Champs des documents
            $fields = [
                'marche_document' => 'Document Marché',
                'ordre_service_document' => 'Ordre de Service',
                'assurance_document' => 'Document Assurance',
                'demande_cautionnement' => 'Demande de Cautionnement',
                'caution_provision_document' => 'Document Caution Provisoire',
                'caution_definitif_document' => 'Document Caution Définitive',
            ];

            // Documents expirants
            $expiringDocuments = [];
            $thresholdDate = Carbon::today()->addDays(30);
            $urgentThreshold = Carbon::today()->addDays(7);
            $companyDocuments = CompanyDocuments::first();

            if ($companyDocuments) {
                $documentFields = [
                    'attestation_regularite_fiscale' => [
                        'label' => 'Attestation de Régularité Fiscale',
                        'expires_at' => 'attestation_regularite_fiscale_expires_at',
                        'file_path' => 'attestation_regularite_fiscale',
                    ],
                    'attestation_cnss' => [
                        'label' => 'Attestation CNSS',
                        'expires_at' => 'attestation_cnss_expires_at',
                        'file_path' => 'attestation_cnss',
                    ],
                    'attestation_soumission_marche' => [
                        'label' => 'Attestation Soumission Marché',
                        'expires_at' => 'attestation_soumission_marche_expires_at',
                        'file_path' => 'attestation_soumission_marche',
                    ],
                    'assurance_accident_travail' => [
                        'label' => 'Assurance Accident Travail',
                        'expires_at' => 'assurance_accident_travail_expires_at',
                        'file_path' => 'assurance_accident_travail',
                    ],
                    'assurance_responsabilite_civile' => [
                        'label' => 'Assurance Responsabilité Civile',
                        'expires_at' => 'assurance_responsabilite_civile_expires_at',
                        'file_path' => 'assurance_responsabilite_civile',
                    ],
                    'modele_rc_7' => [
                        'label' => 'Modèle RC 7',
                        'expires_at' => 'modele_rc_7_expires_at',
                        'file_path' => 'modele_rc_7',
                    ],
                    'modele_rc_9' => [
                        'label' => 'Modèle RC 9',
                        'expires_at' => 'modele_rc_9_expires_at',
                        'file_path' => 'modele_rc_9',
                    ],
                ];

                foreach ($documentFields as $field => $info) {
                    if ($companyDocuments->$field && $companyDocuments->{$info['expires_at']}) {
                        $expiresAt = Carbon::parse($companyDocuments->{$info['expires_at']});
                        $today = Carbon::today();

                        if ($expiresAt->lte($thresholdDate)) {
                            $daysUntilExpiration = $today->diffInDays($expiresAt, $expiresAt->isPast());
                            $isExpired = $expiresAt->lt($today);
                            $isUrgent = !$isExpired && $expiresAt->lte($urgentThreshold);

                            $expiringDocuments[] = [
                                'label' => $info['label'],
                                'expires_at' => $expiresAt->format('Y-m-d'),
                                'formatted_expires_at' => $expiresAt->format('d/m/Y'),
                                'days_until_expiration' => $daysUntilExpiration,
                                'is_urgent' => $isUrgent,
                                'is_expired' => $isExpired,
                                'project_id' => null,
                                'project_name' => 'Tous les projets actifs',
                                'file_path' => $companyDocuments->$field,
                                'category' => 'company_document',
                            ];
                        }
                    }
                }
            }

            // Documents des véhicules
            $vehicleExpired = 0;
            $vehicleUrgent = 0;
            $vehiclesPaginated = Vehicle::paginate(3);
            $vehicles = Vehicle::all()->map(function ($vehicle, $index) use (&$expiringDocuments, $thresholdDate, $urgentThreshold, &$vehicleExpired, &$vehicleUrgent) 
            {
                $vehicleIndex = $index + 1;
                $matricule = $vehicle->matricule;
                $today = Carbon::today();

                // Assurance
                if ($vehicle->assurance_path && $vehicle->assurance_expires_at) {
                    $expiresAt = Carbon::parse($vehicle->assurance_expires_at);
                    if ($expiresAt->lte($thresholdDate)) {
                        $daysUntilExpiration = $today->diffInDays($expiresAt, $expiresAt->isPast());
                        $isExpired = $expiresAt->lt($today);
                        $isUrgent = !$isExpired && $expiresAt->lte($urgentThreshold);
                        if ($isExpired) $vehicleExpired++;
                        elseif ($isUrgent) $vehicleUrgent++;
                        $expiringDocuments[] = [
                            'label' => "Assurance Véhicule $vehicleIndex (Matricule: $matricule)",
                            'expires_at' => $expiresAt->format('Y-m-d'),
                            'formatted_expires_at' => $expiresAt->format('d/m/Y'),
                            'days_until_expiration' => $daysUntilExpiration,
                            'is_urgent' => $isUrgent,
                            'is_expired' => $isExpired,
                            'project_id' => null,
                            'project_name' => 'Tous les projets actifs',
                            'file_path' => $vehicle->assurance_path,
                            'category' => 'vehicle_insurance',
                        ];
                    }
                }

                // Carte Grise
                if ($vehicle->carte_grise_path && $vehicle->carte_grise_expires_at) {
                    $expiresAt = Carbon::parse($vehicle->carte_grise_expires_at);
                    if ($expiresAt->lte($thresholdDate)) {
                        $daysUntilExpiration = $today->diffInDays($expiresAt, $expiresAt->isPast());
                        $isExpired = $expiresAt->lt($today);
                        $isUrgent = !$isExpired && $expiresAt->lte($urgentThreshold);
                        if ($isExpired) $vehicleExpired++;
                        elseif ($isUrgent) $vehicleUrgent++;
                        $expiringDocuments[] = [
                            'label' => "Carte Grise Véhicule $vehicleIndex (Matricule: $matricule)",
                            'expires_at' => $expiresAt->format('Y-m-d'),
                            'formatted_expires_at' => $expiresAt->format('d/m/Y'),
                            'days_until_expiration' => $daysUntilExpiration,
                            'is_urgent' => $isUrgent,
                            'is_expired' => $isExpired,
                            'project_id' => null,
                            'project_name' => 'Tous les projets actifs',
                            'file_path' => $vehicle->carte_grise_path,
                            'category' => 'vehicle_registration',
                        ];
                    }
                }

                // Visite Technique
                if ($vehicle->visite_technique_path && $vehicle->visite_technique_expires_at) {
                    $expiresAt = Carbon::parse($vehicle->visite_technique_expires_at);
                    if ($expiresAt->lte($thresholdDate)) {
                        $daysUntilExpiration = $today->diffInDays($expiresAt, $expiresAt->isPast());
                        $isExpired = $expiresAt->lt($today);
                        $isUrgent = !$isExpired && $expiresAt->lte($urgentThreshold);
                        if ($isExpired) $vehicleExpired++;
                        elseif ($isUrgent) $vehicleUrgent++;
                        $expiringDocuments[] = [
                            'label' => "Visite Technique Véhicule $vehicleIndex (Matricule: $matricule)",
                            'expires_at' => $expiresAt->format('Y-m-d'),
                            'formatted_expires_at' => $expiresAt->format('d/m/Y'),
                            'days_until_expiration' => $daysUntilExpiration,
                            'is_urgent' => $isUrgent,
                            'is_expired' => $isExpired,
                            'project_id' => null,
                            'project_name' => 'Tous les projets actifs',
                            'file_path' => $vehicle->visite_technique_path,
                            'category' => 'vehicle_inspection',
                        ];
                    }
                }

                // Contrat d'Achat
                if ($vehicle->contrat_achat_path && $vehicle->contrat_achat_expires_at) {
                    $expiresAt = Carbon::parse($vehicle->contrat_achat_expires_at);
                    if ($expiresAt->lte($thresholdDate)) {
                        $daysUntilExpiration = $today->diffInDays($expiresAt, $expiresAt->isPast());
                        $isExpired = $expiresAt->lt($today);
                        $isUrgent = !$isExpired && $expiresAt->lte($urgentThreshold);
                        if ($isExpired) $vehicleExpired++;
                        elseif ($isUrgent) $vehicleUrgent++;
                        $expiringDocuments[] = [
                            'label' => "Contrat Achat Véhicule $vehicleIndex (Matricule: $matricule)",
                            'expires_at' => $expiresAt->format('Y-m-d'),
                            'formatted_expires_at' => $expiresAt->format('d/m/Y'),
                            'days_until_expiration' => $daysUntilExpiration,
                            'is_urgent' => $isUrgent,
                            'is_expired' => $isExpired,
                            'project_id' => null,
                            'project_name' => 'Tous les projets actifs',
                            'file_path' => $vehicle->contrat_achat_path,
                            'category' => 'vehicle_purchase',
                        ];
                    }
                }

                // Formater les dates pour la table des véhicules
                $vehicle->formatted_assurance_expires_at = $vehicle->assurance_expires_at 
                    ? Carbon::parse($vehicle->assurance_expires_at)->format('d/m/Y') 
                    : null;
                $vehicle->formatted_carte_grise_expires_at = $vehicle->carte_grise_expires_at 
                    ? Carbon::parse($vehicle->carte_grise_expires_at)->format('d/m/Y') 
                    : null;
                $vehicle->formatted_visite_technique_expires_at = $vehicle->visite_technique_expires_at 
                    ? Carbon::parse($vehicle->visite_technique_expires_at)->format('d/m/Y') 
                    : null;
                $vehicle->formatted_contrat_achat_expires_at = $vehicle->contrat_achat_expires_at 
                    ? Carbon::parse($vehicle->contrat_achat_expires_at)->format('d/m/Y') 
                    : null;

                return $vehicle;
            })->all();

            // Trier les documents par statut d'expiration et date
            usort($expiringDocuments, function ($a, $b) {
                if ($a['is_expired'] !== $b['is_expired']) {
                    return $a['is_expired'] ? -1 : 1;
                }
                if ($a['is_urgent'] !== $b['is_urgent']) {
                    return $a['is_urgent'] ? -1 : 1;
                }
                return strcmp($a['expires_at'], $b['expires_at']);
            });

            // Progression des projets publics
      // Progression des projets publics
        $publicProjectsProgress = DB::table('projet')
            ->leftJoin('dossiers_pdf', 'projet.id', '=', 'dossiers_pdf.projet_id')
            ->leftJoinSub(
                DB::table('decompte')
                    ->select('projet_id', 'pourcentage', 'created_at')
                    ->whereIn('created_at', function ($query) {
                        $query->select(DB::raw('MAX(created_at)'))
                            ->from('decompte')
                            ->groupBy('projet_id');
                    }),
                'latest_decompte',
                'projet.id',
                '=',
                'latest_decompte.projet_id'
            )
            ->where('projet.type_projet', 'Public')
            ->where('projet.cloture', 0)
            ->when($year, function ($query, $year) {
                return $query->whereYear('projet.created_at', $year);
            })
            ->select(
                'projet.id',
                'projet.intitule',
                'projet.travaux_executier',
                'projet.budget',
                'projet.cloture',
                'dossiers_pdf.marche_document',
                'dossiers_pdf.ordre_service_document',
                'dossiers_pdf.assurance_document',
                'dossiers_pdf.demande_cautionnement',
                'dossiers_pdf.caution_provision_document',
                'dossiers_pdf.caution_definitif_document',
                'latest_decompte.pourcentage as latest_pourcentage'
            )
            ->orderBy('projet.created_at', 'desc')
            ->paginate(5, ['*'], 'public_page');

        $publicProjectsProgress->through(function ($project) use ($fields) {
            $progress = $project->latest_pourcentage ?? 0;

            $missingFields = [];
            $hasDossier = false;
            foreach ($fields as $field => $label) {
                if (!is_null($project->$field) && $project->$field !== '') {
                    $hasDossier = true;
                    break;
                }
            }

            if (!$hasDossier) {
                $missingFields = array_values($fields);
            } else {
                foreach ($fields as $field => $label) {
                    if (is_null($project->$field) || $project->$field === '') {
                        $missingFields[] = $label;
                    }
                }
            }

            if (in_array('Ordre de Service', $missingFields)) {
                $ordreServiceExists = DB::table('ordres_service')
                    ->where('projet_id', $project->id)
                    ->whereNotNull('document_path')
                    ->exists();
                if ($ordreServiceExists) {
                    $missingFields = array_diff($missingFields, ['Ordre de Service']);
                }
            }

            return [
                'id' => $project->id,
                'intitule' => $project->intitule ?? 'Projet sans nom',
                'progress' => round($progress, 2),
                'cloture' => $project->cloture,
                'missing_fields' => array_values($missingFields),
            ];
        });

            // Count des projets BC
            $bcProjectsCount = DB::table('projet')
                ->where('type_projet', 'Public')
                ->where('commande_type', 'BC')
                ->where('cloture', 0)
                ->count();

            // Progression des projets BC
            $bcProjectsProgress = DB::table('projet')
                ->leftJoin('dossiers_pdf', 'projet.id', '=', 'dossiers_pdf.projet_id')
                ->where('projet.type_projet', 'Public')
                ->where('projet.commande_type', 'BC')
                ->where('projet.cloture', 0)
                ->when($year, function ($query, $year) {
                    return $query->whereYear('projet.created_at', $year);
                })
                ->select(
                    'projet.id',
                    'projet.intitule',
                    'projet.travaux_executier',
                    'projet.budget',
                    'projet.cloture',
                    'dossiers_pdf.marche_document',
                    'dossiers_pdf.ordre_service_document',
                    'dossiers_pdf.assurance_document',
                    'dossiers_pdf.demande_cautionnement',
                    'dossiers_pdf.caution_provision_document',
                    'dossiers_pdf.caution_definitif_document'
                )
                ->orderBy('projet.created_at', 'desc')
                ->paginate(5, ['*'], 'bc_page');

            $bcProjectsProgress->through(function ($project) use ($fields) {
                $budget = floatval(str_replace(',', '', $project->budget ?? '0'));
                $travaux_executier = floatval(str_replace(',', '', $project->travaux_executier ?? '0'));
                $progress = ($budget > 0) ? ($travaux_executier / $budget) * 100 : 0;

                $missingFields = [];
                $hasDossier = false;
                foreach ($fields as $field => $label) {
                    if (!is_null($project->$field) && $project->$field !== '') {
                        $hasDossier = true;
                        break;
                    }
                }

                if (!$hasDossier) {
                    $missingFields = array_values($fields);
                } else {
                    foreach ($fields as $field => $label) {
                        if (is_null($project->$field) || $project->$field === '') {
                            $missingFields[] = $label;
                        }
                    }
                }

                if (in_array('Ordre de Service', $missingFields)) {
                    $ordreServiceExists = DB::table('ordres_service')
                        ->where('projet_id', $project->id)
                        ->whereNotNull('document_path')
                        ->exists();
                    if ($ordreServiceExists) {
                        $missingFields = array_diff($missingFields, ['Ordre de Service']);
                    }
                }

                return [
                    'id' => $project->id,
                    'intitule' => $project->intitule ?? 'Projet sans nom',
                    'progress' => round($progress, 2),
                    'cloture' => $project->cloture,
                    'missing_fields' => array_values($missingFields),
                ];
            });

            // Progression des projets privés
            $privateProjectsProgress = DB::table('projet')
                ->leftJoin('dossiers_pdf', 'projet.id', '=', 'dossiers_pdf.projet_id')
                ->where('projet.type_projet', 'Privé')
                ->where('projet.cloture', 0)
                ->when($year, function ($query, $year) {
                    return $query->whereYear('projet.created_at', $year);
                })
                ->select(
                    'projet.id',
                    'projet.intitule',
                    'projet.travaux_executier',
                    'projet.budget',
                    'projet.cloture',
                    'dossiers_pdf.marche_document',
                    'dossiers_pdf.ordre_service_document',
                    'dossiers_pdf.assurance_document',
                    'dossiers_pdf.demande_cautionnement',
                    'dossiers_pdf.caution_provision_document',
                    'dossiers_pdf.caution_definitif_document'
                )
                ->orderBy('projet.created_at', 'desc')
                ->paginate(5, ['*'], 'private_page');

            $privateProjectsProgress->through(function ($project) use ($fields) {
                $budget = floatval(str_replace(',', '', $project->budget ?? '0'));
                $travaux_executier = floatval(str_replace(',', '', $project->travaux_executier ?? '0'));
                $progress = ($budget > 0) ? ($travaux_executier / $budget) * 100 : 0;

                $missingFields = [];
                $hasDossier = false;
                foreach ($fields as $field => $label) {
                    if (!is_null($project->$field) && $project->$field !== '') {
                        $hasDossier = true;
                        break;
                    }
                }

                if (!$hasDossier) {
                    $missingFields = array_values($fields);
                } else {
                    foreach ($fields as $field => $label) {
                        if (is_null($project->$field) || $project->$field === '') {
                            $missingFields[] = $label;
                        }
                    }
                }

                if (in_array('Ordre de Service', $missingFields)) {
                    $ordreServiceExists = DB::table('ordres_service')
                        ->where('projet_id', $project->id)
                        ->whereNotNull('document_path')
                        ->exists();
                    if ($ordreServiceExists) {
                        $missingFields = array_diff($missingFields, ['Ordre de Service']);
                    }
                }

                return [
                    'id' => $project->id,
                    'intitule' => $project->intitule ?? 'Projet sans nom',
                    'progress' => round($progress, 2),
                    'cloture' => $project->cloture,
                    'missing_fields' => array_values($missingFields),
                ];
            });

            // Progression des projets clôturés
            $closedProjectsProgress = DB::table('projet')
                ->leftJoin('dossiers_pdf', 'projet.id', '=', 'dossiers_pdf.projet_id')
                ->leftJoin('decompte', 'projet.id', '=', 'decompte.projet_id')
                ->where('projet.cloture', 1)
                ->when($year, function ($query, $year) {
                    return $query->whereYear('projet.created_at', $year);
                })
                ->select(
                    'projet.id',
                    'projet.intitule',
                    'projet.travaux_executier',
                    'projet.budget',
                    'projet.cloture',
                    'dossiers_pdf.marche_document',
                    'dossiers_pdf.ordre_service_document',
                    'dossiers_pdf.assurance_document',
                    'dossiers_pdf.demande_cautionnement',
                    'dossiers_pdf.caution_provision_document',
                    'dossiers_pdf.caution_definitif_document',
                    DB::raw('SUM(decompte.pourcentage) as total_pourcentage')
                )
                ->groupBy(
                    'projet.id',
                    'projet.intitule',
                    'projet.travaux_executier',
                    'projet.budget',
                    'projet.cloture',
                    'dossiers_pdf.marche_document',
                    'dossiers_pdf.ordre_service_document',
                    'dossiers_pdf.assurance_document',
                    'dossiers_pdf.demande_cautionnement',
                    'dossiers_pdf.caution_provision_document',
                    'dossiers_pdf.caution_definitif_document'
                )
                ->orderBy('projet.created_at', 'desc')
                ->paginate(5, ['*'], 'closed_page');

            $closedProjectsProgress->through(function ($project) use ($fields) {
                $progress = $project->total_pourcentage ?? 0;

                $missingFields = [];
                $hasDossier = false;
                foreach ($fields as $field => $label) {
                    if (!is_null($project->$field) && $project->$field !== '') {
                        $hasDossier = true;
                        break;
                    }
                }

                if (!$hasDossier) {
                    $missingFields = array_values($fields);
                } else {
                    foreach ($fields as $field => $label) {
                        if (is_null($project->$field) || $project->$field === '') {
                            $missingFields[] = $label;
                        }
                    }
                }

                if (in_array('Ordre de Service', $missingFields)) {
                    $ordreServiceExists = DB::table('ordres_service')
                        ->where('projet_id', $project->id)
                        ->whereNotNull('document_path')
                        ->exists();
                    if ($ordreServiceExists) {
                        $missingFields = array_diff($missingFields, ['Ordre de Service']);
                    }
                }

                return [
                    'id' => $project->id,
                    'intitule' => $project->intitule ?? 'Projet sans nom',
                    'progress' => round($progress, 2),
                    'cloture' => $project->cloture,
                    'missing_fields' => array_values($missingFields),
                ];
            });


            // Définir les counts des projets
            $publicProjectsCount = $publicProjectsProgress->total();
            $privateProjectsCount = $privateProjectsProgress->total();
            $closedProjectsCount = $closedProjectsProgress->total();  
        // Fetch expenses per vehicle
        $vehicleExpenses = Depences::select('vehicle_id', DB::raw('SUM(montant) as total_expense'))
            ->whereNotNull('vehicle_id')
            ->groupBy('vehicle_id')
            ->with(['vehicle' => function ($query) {
                $query->select('id', 'matricule');
            }])
            ->get()
            ->map(function ($expense) {
                return [
                    'vehicle_id' => $expense->vehicle_id,
                    'matricule' => $expense->vehicle ? $expense->vehicle->matricule : 'Unknown',
                    'total_expense' => number_format($expense->total_expense, 2, '.', '')
                ];
            });

        // État de vidange par véhicule
        $vehicleVidangeStatus = collect();

        $allVehicles = DB::table('vehicules')->select('id', 'matricule')->get();

        foreach ($allVehicles as $vehicle) {
    $derniere = DB::table('depences')
        ->where('vehicle_id', $vehicle->id)
        ->orderBy('id', 'desc')
        ->select('etat_vidange', 'etat_amortisseur', 'etat_courroie', 'etat_pneus', 'etat_plaquettes', 'kilometrage')
        ->first();

    if ($derniere) {
        $vehicleVidangeStatus->push((object)[
            'matricule'        => $vehicle->matricule,
            'etat_vidange'     => $derniere->etat_vidange,
            'etat_amortisseur' => $derniere->etat_amortisseur,
            'etat_courroie'    => $derniere->etat_courroie,
            'etat_pneus'       => $derniere->etat_pneus,
            'etat_plaquettes'  => $derniere->etat_plaquettes,
            'kilometrage'      => $derniere->kilometrage,
        ]);
    }
}



     // Total Vehicle Expenses
            $totalVehicleExpenses = Depences::whereNotNull('vehicle_id')
                ->sum('montant') ?? 0;


   
            // Retourner la vue avec toutes les variables
            return view('dashboard.index', compact(
                'totalUsers',
                'totalSalaries',
                'totalDepences',
                'depencesChange',
                'currentYear',
                'chartLabels',
                'chartValues',
                'period',
                'totalPresent',
                'totalAbsent',
                'totalOnLeave',
                'publicProjectsProgress',
                'bcProjectsProgress',
                'privateProjectsProgress',
                'closedProjectsProgress',
                'fields',
                'presentEmployees',
                'onLeaveEmployees',
                'absentEmployees',
                'publicProjectsCount',
                'bcProjectsCount',
                'privateProjectsCount',
                'closedProjectsCount',
                'pendingLeaveNotifications',
                'pendingDepenseNotifications',
                'pendingLeaveNotificationDetails',
                'pendingDepenseNotificationDetails',
                'salaryChartLabels',
                'salaryChartValues',
                'expiringDocuments',
                'vehicles',
                'totalVehicles',
                'projectYears',
                'year',
                'vehicleExpired',
                'vehicleUrgent',
                'vehicleExpenses',
                'vehiclesPaginated',
                'totalVehicleExpenses',
                'vehicleVidangeStatus'
            ));
        } catch (\Exception $e) {
            Log::error('Error in DashboardController::index', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Une erreur est survenue lors du chargement du tableau de bord.');
        }
    }

public function getVehiclesPaginated(Request $request)
{
    $page = $request->get('page', 1);
    
    $vehicles = Vehicle::paginate(3, ['*'], 'page', $page);
    
    $vehicles->through(function ($vehicle) {
        $vehicle->formatted_assurance_expires_at = $vehicle->assurance_expires_at 
            ? Carbon::parse($vehicle->assurance_expires_at)->format('d/m/Y') 
            : null;
        $vehicle->formatted_carte_grise_expires_at = $vehicle->carte_grise_expires_at 
            ? Carbon::parse($vehicle->carte_grise_expires_at)->format('d/m/Y') 
            : null;
        $vehicle->formatted_visite_technique_expires_at = $vehicle->visite_technique_expires_at 
            ? Carbon::parse($vehicle->visite_technique_expires_at)->format('d/m/Y') 
            : null;
        $vehicle->formatted_contrat_achat_expires_at = $vehicle->contrat_achat_expires_at 
            ? Carbon::parse($vehicle->contrat_achat_expires_at)->format('d/m/Y') 
            : null;
        
        return $vehicle;
    });
    
    return response()->json([
        'vehicles' => $vehicles->items(),
        'current_page' => $vehicles->currentPage(),
        'last_page' => $vehicles->lastPage(),
        'per_page' => $vehicles->perPage(),
        'total' => $vehicles->total(),
        'from' => $vehicles->firstItem(),
        'to' => $vehicles->lastItem()
    ]);
}

}