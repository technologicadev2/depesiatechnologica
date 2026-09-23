<?php

namespace App\Console\Commands;

use App\Models\OrdreService;
use App\Models\Projet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateNbrjour extends Command
{
    protected $signature = 'ordre-service:update-nbrjour';
    protected $description = 'Update nbrjour for existing ordre_service pairs';

    public function handle()
    {
        $projets = Projet::whereHas('ordresService')->get();

        foreach ($projets as $projet) {
            $this->info("Processing projet ID: {$projet->id}");
            Log::info('Processing projet for nbrjour update', ['projet_id' => $projet->id]);

            $ordres = OrdreService::where('projet_id', $projet->id)
                ->orderBy('date_ordre', 'asc')
                ->get();

            $stops = $ordres->where('type', 'arret')->values();
            $resumes = $ordres->where('type', 'reprise')->values();

            foreach ($stops as $index => $stop) {
                if (isset($resumes[$index])) {
                    $stopDate = \Carbon\Carbon::parse($stop->date_ordre);
                    $resumeDate = \Carbon\Carbon::parse($resumes[$index]->date_ordre);

                    if ($resumeDate->gt($stopDate)) {
                        $days = $stopDate->diffInDays($resumeDate);
                        $stop->nbrjour = $days;
                        $stop->save();

                        $this->info("Updated stop ID: {$stop->id}, nbrjour: {$days}");
                        Log::info('Updated nbrjour for stop order', [
                            'stop_id' => $stop->id,
                            'reprise_id' => $resumes[$index]->id,
                            'nbrjour' => $days,
                        ]);
                    } else {
                        $this->warn("Invalid date order for stop ID: {$stop->id}");
                        Log::warning('Invalid date order', [
                            'stop_id' => $stop->id,
                            'reprise_id' => $resumes[$index]->id,
                            'stop_date' => $stopDate->toDateString(),
                            'resume_date' => $resumeDate->toDateString(),
                        ]);
                    }
                } else {
                    $this->warn("No resume found for stop ID: {$stop->id}");
                    Log::warning('No resume found for stop', ['stop_id' => $stop->id]);
                }
            }
        }

        $this->info('Nbrjour update completed.');
    }
}