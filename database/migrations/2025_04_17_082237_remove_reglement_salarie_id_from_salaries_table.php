<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['reglement_salarie_id']);
            // Supprimer la colonne
            $table->dropColumn('reglement_salarie_id');
        });
    }

    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            // Recréer la colonne et la clé étrangère en cas de rollback
            $table->foreignId('reglement_salarie_id')->nullable()->constrained('type_reglement_salarie');
        });
    }
};