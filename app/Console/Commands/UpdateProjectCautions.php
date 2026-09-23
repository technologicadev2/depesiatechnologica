<?php

namespace App\Console\Commands;

use App\Models\Projet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateProjectCautions extends Command
{
    protected $signature = 'projects:update-cautions';
    protected $description = 'Update caution_definitif and total_decompte for existing public projects';

    public function handle()
    {
        try {
            Projet::where('type_projet', 'Public')->get()->each(function ($projet) {
                $rg = $projet->budget * 0.07;
                $total_decompte = $projet->budget - $rg;
                $caution_definitif = $projet['commande_type'] === 'Marche' ? $projet['budget'] * 0.03 : 0;
                $projet->update([
                    'rg' => $rg,
                    'total_decompte' => $total_decompte,
                    'caution_definitif' => $caution_definitif,
                ]);

                Log::info('Updated project', [
                    'projet_id' => $projet->id,
                    'caution_definitif' => $caution_definitif,
                    'total_decompte' => $total_decompte,
                ]);
            });

            $this->info('All public projects updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating projects', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('An error occurred while updating projects.');
        }
    }
}
