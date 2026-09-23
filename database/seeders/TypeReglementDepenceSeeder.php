<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeReglementDepenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            ['designation' => 'Espèce', 'code' => 'ESP'],
            ['designation' => 'Chèque', 'code' => 'CHQ'],
            ['designation' => 'Versement', 'code' => 'VRS'],
            ['designation' => 'Virement', 'code' => 'VIR'],
        ];

        foreach ($types as $type) {
            DB::table('type_reglement')->insert([
                'designation' => $type['designation'],
                'code' => $type['code'],
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => null, 
                'updated_by' => null,
                'deleted_by' => null,
            ]);
        }
    }
}