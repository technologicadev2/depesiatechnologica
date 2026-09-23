<?php



namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NatureAvancementSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('nature_depences')->insert([
            'designation' => 'Avancements de salaires',
            'code' => 'AVS-001', // Optional: Adjust as needed
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);
    }
}
