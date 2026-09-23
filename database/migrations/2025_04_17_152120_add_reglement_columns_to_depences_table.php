<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('depences', function (Blueprint $table) {
            // Ajouter les nouvelles colonnes
            $table->foreignId('reglement_id')->nullable()->constrained('type_reglement')->onDelete('restrict');
            $table->string('reglement_depense', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depences', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère et les colonnes en cas de rollback
            $table->dropForeign(['reglement_id']);
            $table->dropColumn(['reglement_id', 'reglement_depense']);
        });
    }
};