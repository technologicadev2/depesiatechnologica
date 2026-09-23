<?php

namespace Database\Seeders;

use App\Models\Cotisations;
use App\Models\FraisProfessionnel;
use App\Models\ImpotSurRevenu;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ParametresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cotisation1 = Cotisations::create([
            'cnss_pp' => 16.98 ,
            'amo_pp' => 4.11 ,

            'cnss_ps' => 4.48 ,
            'amo_ps' => 2.26  ,
            'fp_ps' => 0.25 ,

            'charge_de_famille' => 30 ,
        ]);

        /////////////////////////////////////////

        $ImpotSurRevenu1 = ImpotSurRevenu::create([
            'revenu_min' => 0,
            'revenu_max' => 2500,
            'taux' => 0,
            'somme_a_deduire' => 0,
        ]);
        $ImpotSurRevenu2 = ImpotSurRevenu::create([
            'revenu_min' => 2501,
            'revenu_max' => 4166,
            'taux' => 10,
            'somme_a_deduire' => 250,
        ]);
        $ImpotSurRevenu3 = ImpotSurRevenu::create([
            'revenu_min' => 4167,
            'revenu_max' => 5000,
            'taux' => 20,
            'somme_a_deduire' => 666.67,
        ]);
        $ImpotSurRevenu4 = ImpotSurRevenu::create([
            'revenu_min' => 5001,
            'revenu_max' => 6666,
            'taux' => 30,
            'somme_a_deduire' => 1166.67,
        ]);
        $ImpotSurRevenu5 = ImpotSurRevenu::create([
            'revenu_min' => 6667,
            'revenu_max' => 15000,
            'taux' => 34,
            'somme_a_deduire' => 1433.33,
        ]);
        $ImpotSurRevenu6 = ImpotSurRevenu::create([
            'revenu_min' => 15001,
            'revenu_max' => null,
            'taux' => 38,
            'somme_a_deduire' => 2916.67,
        ]);

        /////////////////////////////////////////////////

        $FraisProfessionnel1 = FraisProfessionnel::create([
            'sbi_min' => 0,
            'sbi_max' => 6500,
            'taux' => 35,
            'somme_a_deduire' => 0,
        ]);
        $FraisProfessionnel2 = FraisProfessionnel::create([
            'sbi_min' => 6501,
            'sbi_max' => null,
            'taux' => 25,
            'somme_a_deduire' => 2916.67,
        ]);
        
      
    }
}
