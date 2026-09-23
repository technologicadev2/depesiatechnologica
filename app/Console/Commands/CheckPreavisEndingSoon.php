<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Preavis;
use App\Models\Salarie;
use App\Models\User;
use App\Notifications\PreavisEndingSoonNotification;
use Carbon\Carbon;

class CheckPreavisEndingSoon extends Command
{
    protected $signature = 'preavis:check-ending-soon';
    protected $description = 'Envoie une notification quand un préavis se termine dans moins de 7 jours';

    public function handle()
    {
        $today      = Carbon::today();
        $limitDate  = $today->copy()->addDays(7);

        $preavisEndingSoon = Preavis::whereNotNull('date_fin')
            ->whereBetween('date_fin', [$today, $limitDate])
            ->with('salarie')
            ->get();

        foreach ($preavisEndingSoon as $preavis) {
            $salarie  = $preavis->salarie;
            $daysLeft = $today->diffInDays($preavis->date_fin, false);

            // Éviter les doublons : on envoie seulement si pas déjà notifié pour ce préavis
            $alreadyNotified = \Illuminate\Support\Facades\DB::table('notifications')
                ->where('type', PreavisEndingSoonNotification::class)
                ->whereJsonContains('data->preavis_id', $preavis->id)
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            // Notifier tous les utilisateurs qui doivent recevoir cette alerte
            // → ici je notifie tous les superadmin + les utilisateurs ayant le rôle "RH" (adaptez selon vos rôles)
            $users = User::whereHas('role', function ($q) {
                $q->whereIn('name', ['superadmin', 'RH', 'Administrateur']); // changez les noms de rôles si besoin
            })->get();

            foreach ($users as $user) {
                $user->notify(new PreavisEndingSoonNotification($preavis, $salarie, $daysLeft));
            }
        }

        $this->info('Vérification préavis terminée.');
    }
}