<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use App\Models\CompanyDocuments;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Projet; // Ajout du modèle Projet
use App\Notifications\DocumentExpiredNotification;
use App\Notifications\DefinitiveReceptionNotification; // Ajout de la notification pour réception définitive
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CheckExpiredDocumentsOnLogin
{
    public function handle(Login $event)
    {
        $user = $event->user;

        if ($user->role && $user->role->name === 'superadmin') {
            Log::info('Superadmin login detected, checking expired documents and upcoming receptions.', ['user_id' => $user->id]);

            $today = Carbon::today();
            $companyDocuments = CompanyDocuments::first();

            if (!$companyDocuments) {
                Log::info('No company documents found for superadmin login.', ['user_id' => $user->id]);
            }

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

            $expiredDocuments = [];

            // Check single-file company documents
            foreach ($documentFields as $field => $info) {
                if ($companyDocuments && $companyDocuments->$field && $companyDocuments->{$info['expires_at']}) {
                    $expiresAt = Carbon::parse($companyDocuments->{$info['expires_at']});
                    if ($expiresAt->lt($today)) {
                        $expiredDocuments[] = [
                            'label' => $info['label'],
                            'expires_at' => $expiresAt->format('Y-m-d'),
                            'project_name' => 'Tous les projets actifs',
                            'file_path' => $companyDocuments->$field,
                        ];
                    }
                }
            }

            // Check vehicle documents
            $vehicles = Vehicle::all();
            foreach ($vehicles as $index => $vehicle) {
                $vehicleIndex = $index + 1;
                $matricule = $vehicle->matricule;

                // Assurance
                if ($vehicle->assurance_path && $vehicle->assurance_expires_at) {
                    $expiresAt = Carbon::parse($vehicle->assurance_expires_at);
                    if ($expiresAt->lt($today)) {
                        $expiredDocuments[] = [
                            'label' => "Assurance Véhicule $vehicleIndex (Matricule: $matricule)",
                            'expires_at' => $expiresAt->format('Y-m-d'),
                            'project_name' => 'Tous les projets actifs',
                            'file_path' => $vehicle->assurance_path,
                        ];
                    }
                }

                // Carte Grise
                if ($vehicle->carte_grise_path && $vehicle->carte_grise_expires_at) {
                    $expiresAt = Carbon::parse($vehicle->carte_grise_expires_at);
                    if ($expiresAt->lt($today)) {
                        $expiredDocuments[] = [
                            'label' => "Carte Grise Véhicule $vehicleIndex (Matricule: $matricule)",
                            'expires_at' => $expiresAt->format('Y-m-d'),
                            'project_name' => 'Tous les projets actifs',
                            'file_path' => $vehicle->carte_grise_path,
                        ];
                    }
                }

                // Visite Technique
                if ($vehicle->visite_technique_path && $vehicle->visite_technique_expires_at) {
                    $expiresAt = Carbon::parse($vehicle->visite_technique_expires_at);
                    if ($expiresAt->lt($today)) {
                        $expiredDocuments[] = [
                            'label' => "Visite Technique Véhicule $vehicleIndex (Matricule: $matricule)",
                            'expires_at' => $expiresAt->format('Y-m-d'),
                            'project_name' => 'Tous les projets actifs',
                            'file_path' => $vehicle->visite_technique_path,
                        ];
                    }
                }

                // Contrat d'Achat
                if ($vehicle->contrat_achat_path && $vehicle->contrat_achat_expires_at) {
                    $expiresAt = Carbon::parse($vehicle->contrat_achat_expires_at);
                    if ($expiresAt->lt($today)) {
                        $expiredDocuments[] = [
                            'label' => "Contrat Achat Véhicule $vehicleIndex (Matricule: $matricule)",
                            'expires_at' => $expiresAt->format('Y-m-d'),
                            'project_name' => 'Tous les projets actifs',
                            'file_path' => $vehicle->contrat_achat_path,
                        ];
                    }
                }
            }

            // Check project reception_definitive
            $projects = Projet::whereNotNull('reception_definitive')->get();
            $upcomingReceptions = [];

            foreach ($projects as $project) {
                $receptionDate = Carbon::parse($project->reception_definitive);
                // Vérifier si la date est dans les 7 prochains jours
                if ($receptionDate->gte($today) && $receptionDate->lte($today->copy()->addDays(7))) {
                    $upcomingReceptions[] = [
                        'id' => $project->id,
                        'name' => $project->name ?? 'Projet sans nom', // Fallback si name est null
                        'reception_definitive' => $receptionDate->format('Y-m-d'),
                    ];
                }
            }

            Log::info('Documents and receptions checked.', [
                'user_id' => $user->id,
                'expired_documents_count' => count($expiredDocuments),
                'upcoming_receptions_count' => count($upcomingReceptions),
            ]);

            // Send notifications for expired documents
            foreach ($expiredDocuments as $document) {
                $notificationExists = DB::table('notifications')
                    ->where('notifiable_id', $user->id)
                    ->where('notifiable_type', User::class)
                    ->where('type', DocumentExpiredNotification::class)
                    ->where('data->document_label', $document['label'])
                    ->where('data->expires_at', $document['expires_at'])
                    ->whereNull('read_at')
                    ->exists();

                if (!$notificationExists) {
                    Notification::send($user, new DocumentExpiredNotification($document));
                    Log::info('Notification sent for expired document.', [
                        'user_id' => $user->id,
                        'document_label' => $document['label'],
                    ]);
                } else {
                    Log::info('Notification already exists for document.', [
                        'user_id' => $user->id,
                        'document_label' => $document['label'],
                    ]);
                }
            }

            // Send notifications for upcoming receptions
            foreach ($upcomingReceptions as $project) {
                $notificationExists = DB::table('notifications')
                    ->where('notifiable_id', $user->id)
                    ->where('notifiable_type', User::class)
                    ->where('type', DefinitiveReceptionNotification::class)
                    ->where('data->project_name', $project['name'])
                    ->where('data->reception_definitive', $project['reception_definitive'])
                    ->whereNull('read_at')
                    ->exists();

                if (!$notificationExists) {
                    Notification::send($user, new DefinitiveReceptionNotification($project));
                    Log::info('Notification sent for upcoming reception.', [
                        'user_id' => $user->id,
                        'project_name' => $project['name'],
                    ]);
                } else {
                    Log::info('Notification already exists for project reception.', [
                        'user_id' => $user->id,
                        'project_name' => $project['name'],
                    ]);
                }
            }
        } else {
            Log::info('User is not a superadmin or has no role.', ['user_id' => $user->id]);
        }
    }
}